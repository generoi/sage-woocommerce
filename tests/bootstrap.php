<?php

/**
 * Boots WordPress, WooCommerce and a minimal Sage theme running Acorn.
 *
 * What this package does is defined by how WooCommerce's template functions,
 * its object cache and Acorn's compiled views interact, so the suite runs
 * against the real ones.
 */

use Illuminate\Contracts\Console\Kernel;
use Roots\Acorn\Application;

require_once dirname(__DIR__) . '/vendor/autoload.php';

require_once getenv('WP_PHPUNIT__DIR') . '/includes/functions.php';

tests_add_filter('muplugins_loaded', function () {
    // wp-env names the directory after the zip, eg. woocommerce.latest-stable.
    require_once glob(dirname(__DIR__, 2) . '/woocommerce*/woocommerce.php')[0];
});

$theme = fn () => 'sage-woocommerce-test-theme';
tests_add_filter('pre_option_template', $theme);
tests_add_filter('pre_option_stylesheet', $theme);

// WooCommerce's email templates expect an option only its settings screen sets.
tests_add_filter('pre_option_woocommerce_email_footer_text', fn () => '{site_title}');

// Under PHPUnit Acorn runs in console mode without WP_CLI, so its kernel never
// bootstraps by itself. The theme configures the application at priority 0.
tests_add_filter('after_setup_theme', function () {
    $app = Application::getInstance();

    if (! $app->hasBeenBootstrapped()) {
        $app->make(Kernel::class)->bootstrap();
    }
}, 1);

require getenv('WP_PHPUNIT__DIR') . '/includes/bootstrap.php';
