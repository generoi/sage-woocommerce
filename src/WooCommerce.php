<?php

namespace Genero\Sage\WooCommerce;

use Illuminate\Contracts\Container\Container as ContainerContract;
use Roots\Acorn\Sage\ViewFinder;
use Roots\Acorn\View\FileViewFinder;
use Illuminate\Support\Str;

use function Roots\view;

class WooCommerce
{
    protected ViewFinder $sageFinder;
    protected FileViewFinder $fileFinder;
    protected ContainerContract $app;

    public function __construct(
        ViewFinder $sageFinder,
        FileViewFinder $fileFinder,
        ContainerContract $app
    ) {
        $this->app = $app;
        $this->fileFinder = $fileFinder;
        $this->sageFinder = $sageFinder;
    }

    /**
     * Load template hook overrides file if available in app/ folder of theme.
     */
    public function loadThemeTemplateHooks()
    {
        $templateHooks = locate_template('app/wc-template-hooks.php', false, false);

        if ($templateHooks) {
            require_once $templateHooks;
        }
    }

    /**
     * Declare theme support.
     */
    public function addThemeSupport(): void
    {
        add_theme_support('woocommerce');
    }

    /**
     * Support blade templates for the main template include.
     */
    public function templateInclude(string $template): string
    {
        if (!$this->isWooCommerceTemplate($template)) {
            return $template;
        }
        return $this->locateThemeTemplate($template) ?: $template;
    }

    /**
     * Support blade templates for the woocommerce comments/reviews.
     */
    public function reviewsTemplate(string $template): string
    {
        if (!$this->isWooCommerceTemplate($template)) {
            return $template;
        }

        return $this->template($template);
    }

    /**
     * Filter a template path, taking into account theme templates and creating
     * blade loaders as needed.
     */
    public function template(string $template, string $templateName = ''): string
    {
        // Locate any matching template within the theme.
        $themeTemplate = $this->locateThemeTemplate($templateName ?: $template);
        if (!$themeTemplate) {
            return $template;
        }

        // Return filename for status screen
        if (is_admin() &&
            !wp_doing_ajax() &&
            function_exists('get_current_screen') &&
            get_current_screen() &&
            get_current_screen()->id === 'woocommerce_page_wc-status'
        ) {
            return $themeTemplate;
        }

        // Include directly unless it's a blade file.
        if (!Str::endsWith($themeTemplate, '.blade.php')) {
            return $themeTemplate;
        }

        // We have a template, create a loader file and return it's path.
        return view(
            $this->fileFinder->getPossibleViewNameFromPath(realpath($themeTemplate))
        )->makeLoader();
    }

    /**
     * Filter the path wc_get_template() is about to include, which WooCommerce
     * may have read from its object cache without an expiry.
     *
     * A cached path that no longer exists, such as a deleted loader, is treated
     * as a cache miss. Any other path is resolved by path rather than by name so
     * that overrides from earlier filters are kept, eg. Klarna Checkout replacing
     * checkout/form-checkout.php at priority 999.
     *
     * @param mixed $args
     * @param mixed $templatePath
     * @param mixed $defaultPath
     */
    public function cachedTemplate(
        string $template,
        string $templateName = '',
        $args = [],
        $templatePath = '',
        $defaultPath = ''
    ): string {
        if ($templateName && !file_exists($template)) {
            $template = wc_locate_template($templateName, $templatePath, $defaultPath);
        }

        return $this->template($template);
    }

    /**
     * Filter the path wc_get_template_part() is about to load.
     *
     * WooCommerce only looks for .php files, so a part that only exists as a Blade
     * view arrives here without a path, and is looked up in the order WooCommerce
     * would have. A part that exists nowhere stays empty, which WooCommerce skips.
     *
     * @param mixed $slug
     * @param mixed $name
     */
    public function templatePart(string $template, $slug = '', $name = ''): string
    {
        if ($template && file_exists($template)) {
            return $this->template($template);
        }

        foreach (array_filter([$name ? "{$slug}-{$name}.php" : '', $slug ? "{$slug}.php" : '']) as $part) {
            if ($located = $this->template('', $part)) {
                return $located;
            }
        }

        return '';
    }

    /**
     * Check if template is a WooCommerce template.
     */
    protected function isWooCommerceTemplate(string $template): bool
    {
        return $this->relativeTemplatePath($template) !== $template;
    }

    /**
     * Return the theme relative template path.
     */
    protected function relativeTemplatePath(string $template): string
    {
        $defaultPaths = [
            // WooCommerce plugin templates
            \WC_ABSPATH . 'templates/',
        ];

        if (is_child_theme()) {
            // Parent theme templates in woocommerce/ subfolder.
            $defaultPaths[] = get_template_directory() . '/' . WC()->template_path();
        }

        return str_replace(
            apply_filters('sage-woocommerce/templates', $defaultPaths),
            '',
            $template
        );
    }

    /**
     * Locate the theme's WooCommerce blade template when available.
     */
    protected function locateThemeTemplate(string $template): string
    {
        // Absolute plugin template path -> woocommerce/single-product.php
        $themeTemplate = WC()->template_path() . $this->relativeTemplatePath($template);
        $directories = array_unique([get_stylesheet_directory(), get_template_directory()]);

        // A candidate is only accepted when it resolves inside one of Acorn's
        // registered view paths, matching the containment roots/acorn#572
        // applies to the theme's own template hierarchy.
        $viewPaths = [];

        foreach ($this->fileFinder->getPaths() as $path) {
            $viewPaths[] = trailingslashit(wp_normalize_path(realpath($path) ?: $path));
        }

        // Resolve the candidates directly. WordPress' locate_template() rejects
        // theme-relative paths containing `..`, which is how Acorn addresses
        // views that live outside the theme directory.
        foreach ($this->sageFinder->locate($themeTemplate) as $candidate) {
            foreach ($directories as $directory) {
                $path = realpath("{$directory}/{$candidate}");

                if ($path && is_file($path) && Str::startsWith(wp_normalize_path($path), $viewPaths)) {
                    return $path;
                }
            }
        }

        return '';
    }
}
