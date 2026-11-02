<?php

use Genero\Sage\WooCommerce\WooCommerceServiceProvider;
use Roots\Acorn\Application;
use Roots\Acorn\Sage\SageServiceProvider;

require_once WP_PLUGIN_DIR . '/sage-woocommerce/vendor/autoload.php';

add_action('after_setup_theme', function () {
    Application::configure(__DIR__)
        ->withProviders([SageServiceProvider::class, WooCommerceServiceProvider::class])
        ->boot();
}, 0);

// Keeps what would have been sent, for the e2e test to read from another process.
add_filter('pre_wp_mail', function ($return, array $mail) {
    $mails = get_option('sage_woocommerce_e2e_mails', []);
    $mails[] = [
        'to' => is_array($mail['to']) ? implode(',', $mail['to']) : $mail['to'],
        'subject' => $mail['subject'],
        'body' => $mail['message'],
    ];
    update_option('sage_woocommerce_e2e_mails', $mails, false);

    return true;
}, 10, 2);
