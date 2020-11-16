<?php

namespace Genero\Sage\WooCommerce;

use Illuminate\Contracts\Container\Container as ContainerContract;
use Roots\Acorn\Sage\ViewFinder;
use Roots\Acorn\View\FileViewFinder;
use Illuminate\Support\Str;

use function Roots\view;

class WooCommerce
{
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
        locate_template('app/wc-template-hooks.php', true, true);
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

        return $this->templatePart($template);
    }

    /**
     * Add blade templates for the woocommerce status page if user is admin and
     * loaded wc status page.
     */
    public function getTemplate(string $template, string $templateName, array $args): string
    {
        $themeTemplate = $this->locateThemeTemplate($templateName);

        // return theme filename for status screen
        if (is_admin() &&
            !wp_doing_ajax() &&
            get_current_screen() &&
            get_current_screen()->id === 'woocommerce_page_wc-status') {
            return $themeTemplate ?: $template;
        }

        // return default template, output already rendered by "templatePart" method.
        return $template;
    }

    /**
     * Filter a template path, taking into account theme templates and creating
     * blade loaders as needed.
     */
    public function templatePart(string $template): string
    {
        // Locate any matching template within the theme.
        $themeTemplate = $this->locateThemeTemplate($template);

        // Include directly unless it's a blade file.
        if ($themeTemplate && Str::endsWith($themeTemplate, '.blade.php')) {
            // Gather data to be passed to view
            $data = array_merge(
                explode(' ', 'template_name template_path located args'),
                collect(get_body_class())->reduce(function ($data, $class) {
                    return apply_filters("sage/template/{$class}/data", $data);
                }, [])
            );
            // We have a template, create a loader file and return it's path.
            return view(
                $this->fileFinder->getPossibleViewNameFromPath(realpath($themeTemplate)),
                $data
            )->makeLoader();
        }

        return $template;
    }

    /**
     * Check if template is a WooCommerce template.
     */
    protected function isWooCommerceTemplate(string $template): bool
    {
        return strpos($template, \WC_ABSPATH) !== false;
    }

    /**
     * Locate the theme's WooCommerce blade template when available.
     */
    protected function locateThemeTemplate(string $template): string
    {
        $themeTemplate = WC()->template_path() . str_replace(\WC_ABSPATH . 'templates/', '', $template);
        return locate_template($this->sageFinder->locate($themeTemplate));
    }
}
