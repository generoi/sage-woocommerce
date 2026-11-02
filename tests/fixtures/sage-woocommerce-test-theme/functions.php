<?php

use Genero\Sage\WooCommerce\WooCommerceServiceProvider;
use Roots\Acorn\Application;

add_action('after_setup_theme', function () {
    Application::configure(__DIR__)
        ->withProviders([WooCommerceServiceProvider::class])
        ->boot();
}, 0);
