<?php

/**
 * A theme with nothing but the templates a theme gets from vendor:publish, which
 * .wp-env.json mounts at resources/views/woocommerce.
 */

use Genero\Sage\WooCommerce\WooCommerceServiceProvider;
use Roots\Acorn\Application;
use Roots\Acorn\Sage\SageServiceProvider;

require_once WP_PLUGIN_DIR . '/sage-woocommerce/vendor/autoload.php';

add_action('after_setup_theme', function () {
    Application::configure(__DIR__)
        ->withProviders([SageServiceProvider::class, WooCommerceServiceProvider::class])
        ->boot();
}, 0);

add_action('woocommerce_sidebar', function () {
    echo '<p>hooked-into-woocommerce-sidebar</p>';
}, 5);

add_action('woocommerce_shop_loop_header', function () {
    echo '<p>hooked-into-shop-loop-header</p>';
}, 5);

add_action('get_sidebar', function ($name) {
    echo '<p>hooked-into-get-sidebar-' . esc_html($name) . '</p>';
});
