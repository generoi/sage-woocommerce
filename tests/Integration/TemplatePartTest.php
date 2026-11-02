<?php

namespace Genero\Sage\WooCommerce\Tests\Integration;

use WP_UnitTestCase;

class TemplatePartTest extends WP_UnitTestCase
{
    private function render(string $slug, string $name = ''): string
    {
        ob_start();
        wc_get_template_part($slug, $name);

        return trim(ob_get_clean());
    }

    public function testRendersBladePartWithoutCoreFile(): void
    {
        $this->assertSame('theme-content-custom-part', $this->render('content', 'custom-part'));
    }

    public function testRendersBladePartWithOnlyASlug(): void
    {
        $this->assertSame('theme-custom-slug', $this->render('custom-slug'));
    }

    public function testFallsBackToSlugWhenNameDoesNotExist(): void
    {
        $this->assertSame('theme-custom-slug', $this->render('custom-slug', 'does-not-exist'));
    }

    public function testRendersBladeOverrideOfCorePart(): void
    {
        $this->assertSame('theme-content-single-product', $this->render('content', 'single-product'));
    }

    public function testPartThatExistsNowhereRendersNothing(): void
    {
        $this->assertSame('', $this->render('does-not', 'exist'));
    }

    public function testPathFromAnEarlierFilterIsKept(): void
    {
        $plugin = dirname(__DIR__) . '/fixtures/payment-plugin/templates/custom-template.php';
        $filter = fn () => $plugin;
        add_filter('wc_get_template_part', $filter, 9);

        $this->assertSame('plugin-custom-template', $this->render('does-not', 'exist'));

        remove_filter('wc_get_template_part', $filter, 9);
    }
}
