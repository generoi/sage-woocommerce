<?php

namespace Genero\Sage\WooCommerce;

use Roots\Acorn\ServiceProvider;
use Illuminate\Support\Str;

use function Roots\view;

class WooCommerceServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->viewFinder = $this->app['view.finder'];
        $this->sageFinder = $this->app['sage.finder'];
        $this->sage = $this->app['sage'];
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // Load the template hook overrides if available.
        locate_template('app/wc-template-hooks.php', true, true);

        // Unhook Sage's filter and run our filter after WooCommerce.
        remove_filter('comments_template', [$this->sage, 'filterCommentsTemplate']);
        add_filter('comments_template', [$this, 'reviewsTemplate'], 11);

        add_action('after_setup_theme', [$this, 'addThemeSupport']);
        add_filter('template_include', [$this, 'templateInclude'], 11);
        add_filter('woocommerce_locate_template', [$this, 'template']);
        add_filter('wc_get_template_part', [$this, 'template']);
    }

    public function applyThemeModifications(): void
    {
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
        if (strpos($template, \WC_ABSPATH) === -1) {
            return $template;
        }
        return $this->locateThemeTemplate($template) ?: $template;
    }

    /**
     * Support blade templates for the woocommerce comments/reviews.
     */
    public function reviewsTemplate(string $template): string
    {
        // Unless it's a WC template, keep using the Sage's default filter.
        if (strpos($template, \WC_ABSPATH) === -1) {
            return $this->sage->filterCommentsTemplate($template);
        }

        return $this->template($template);
    }

    /**
     * Filter a template path, taking into account theme templates and creating
     * blade loaders as needed.
     */
    public function template(string $template): string
    {
        // Locate any matching template within the theme.
        $themeTemplate = $this->locateThemeTemplate($template);
        if (!$themeTemplate) {
            return $template;
        }

        // Include directly unless it's a blade file.
        if (!Str::endsWith($themeTemplate, '.blade.php')) {
            return $themeTemplate;
        }

        // We have a template, create a loader file and return it's path.
        return view(
            $this->viewFinder->getPossibleViewNameFromPath($themeTemplate)
        )->makeLoader();
    }

    /**
     * Locate the theme's woocommerce blade template when available.
     */
    protected function locateThemeTemplate(string $template): string
    {
        $themeTemplate = WC()->template_path() . str_replace(\WC_ABSPATH . 'templates/', '', $template);
        return locate_template($this->sageFinder->locate($themeTemplate));
    }
}
