<?php

namespace Genero\Sage\WooCommerce;

use Roots\Acorn\ServiceProvider;

class WooCommerceServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('woocommerce', WooCommerce::class);
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        if (defined('WC_ABSPATH')) {
            $this->app['woocommerce']->loadThemeTemplateHooks();
            $this->bindSetupAction();
            $this->bindFilters();
        }

        $this->publishes([
            __DIR__ . '/../publishes/resources/views' => $this->app->resourcePath('views/woocommerce'),
        ], 'WooCommerce Templates');

        $this->publishes([
            __DIR__ . '/../publishes/app/wc-template-hooks.php' => $this->app->path('wc-template-hooks.php'),
        ], 'WooCommerce Template Hook Overrides');
    }

    public function bindFilters()
    {
        $woocommerce = $this->app['woocommerce'];
        $sage = $this->app['sage'];

        add_filter('template_include', [$woocommerce, 'templateInclude'], 11);
        add_filter('woocommerce_locate_template', [$woocommerce, 'template']);
        add_filter('wc_get_template_part', [$woocommerce, 'template']);

        // Unhook Sage's filter and run our filter after WooCommerce.
        remove_filter('comments_template', [$sage, 'filterCommentsTemplate']);
        add_filter('comments_template', [$woocommerce, 'reviewsTemplate'], 11);
    }

    public function bindSetupAction()
    {
        add_action('after_setup_theme', [$this->app['woocommerce'], 'addThemeSupport']);
    }
}
