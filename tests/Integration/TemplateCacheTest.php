<?php

namespace Genero\Sage\WooCommerce\Tests\Integration;

use Illuminate\Contracts\Console\Kernel;
use Roots\Acorn\Application;
use WP_UnitTestCase;

/**
 * wc_get_template() keeps the path it located in the object cache with no
 * expiry. For a Blade template that is a loader in the compiled views directory,
 * which view:clear and acorn optimize delete.
 */
class TemplateCacheTest extends WP_UnitTestCase
{
    private const THANKYOU = 'checkout/thankyou.php';

    private function render(string $name, array $args = [], string $path = '', string $default = ''): string
    {
        return trim(wc_get_template_html($name, $args, $path, $default));
    }

    private function cacheKey(string $name, string $path = '', string $default = ''): string
    {
        return sanitize_key(implode('-', ['template', $name, $path, $default, WC_VERSION]));
    }

    private function cachedPath(string $name): string
    {
        $cached = wp_cache_get($this->cacheKey($name), 'woocommerce');

        return wc_untokenize_path((string) $cached, wc_get_path_define_tokens());
    }

    private function pluginTemplates(): string
    {
        return dirname(__DIR__) . '/fixtures/payment-plugin/templates/';
    }

    public function testRendersBladeOverrideThroughLoader(): void
    {
        $this->assertSame('theme-thankyou marker', $this->render(self::THANKYOU, ['marker' => 'marker']));
        $this->assertStringEndsWith('-loader.php', $this->cachedPath(self::THANKYOU));
    }

    public function testCacheHitWithExistingLoaderRendersWithoutRelocating(): void
    {
        $this->render(self::THANKYOU);

        $located = 0;
        $count = function ($template) use (&$located) {
            $located++;

            return $template;
        };
        add_filter('woocommerce_locate_template', $count);

        $this->assertSame('theme-thankyou', $this->render(self::THANKYOU));
        $this->assertSame(0, $located);

        remove_filter('woocommerce_locate_template', $count);
    }

    public function testViewClearDoesNotBlankCachedTemplate(): void
    {
        $this->render(self::THANKYOU);
        $loader = $this->cachedPath(self::THANKYOU);
        $this->assertFileExists($loader);

        Application::getInstance()->make(Kernel::class)->call('view:clear');
        $this->assertFileDoesNotExist($loader);
        $this->assertSame($loader, $this->cachedPath(self::THANKYOU));

        $this->assertSame('theme-thankyou', $this->render(self::THANKYOU));
        $this->assertFileExists($loader);
    }

    public function testCachedLoaderFromPreviousReleaseRendersCurrentView(): void
    {
        wp_cache_set($this->cacheKey(self::THANKYOU), '/previous-release/old-loader.php', 'woocommerce');

        $this->assertSame('theme-thankyou', $this->render(self::THANKYOU));
    }

    public function testStalePathWithoutThemeOverrideFallsBackToCoreTemplate(): void
    {
        $name = 'myaccount/form-lost-password.php';
        $miss = $this->render($name);
        $this->assertNotSame('', $miss);

        wp_cache_set($this->cacheKey($name), '/previous-release/old-loader.php', 'woocommerce');

        $this->assertSame($miss, $this->render($name));
    }

    public function testStalePathKeepsCustomPluginTemplatePaths(): void
    {
        $name = 'custom-template.php';
        $default = $this->pluginTemplates();

        wp_cache_set($this->cacheKey($name, 'custom/', $default), '/previous-release/old-loader.php', 'woocommerce');

        $this->assertSame('plugin-custom-template', $this->render($name, [], 'custom/', $default));
    }

    /**
     * Klarna Checkout replaces checkout/form-checkout.php at priority 999, and
     * themes override its template. Resolving by name would ignore the swap.
     */
    public function testPluginSwappingTemplateKeepsThemeOverrideOfSwappedTemplate(): void
    {
        $plugin = $this->pluginTemplates();

        $paths = function (array $paths) use ($plugin) {
            $paths[] = $plugin;

            return $paths;
        };
        $swap = fn ($template, $name) => $name === 'myaccount/lost-password-confirmation.php'
            ? $plugin . 'payment-plugin-checkout.php'
            : $template;

        add_filter('sage-woocommerce/templates', $paths);
        add_filter('wc_get_template', $swap, 999, 2);

        $this->assertSame('theme-payment-plugin-checkout', $this->render('myaccount/lost-password-confirmation.php'));

        remove_filter('wc_get_template', $swap, 999);
        remove_filter('sage-woocommerce/templates', $paths);
    }

    public function testFilterCalledWithOnlyAPathLeavesMissingFileAlone(): void
    {
        $this->assertSame('/missing.php', apply_filters('wc_get_template', '/missing.php'));
    }
}
