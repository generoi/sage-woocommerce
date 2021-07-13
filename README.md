# Sage WooCommerce

Add WooCommerce support to Sage 10.

## Installation

Install the composer package (in the theme folder).

    composer require generoi/sage-woocommerce

Add the package to the cached package manifest.

    wp acorn package:discover

Publish the required `single-product.blade.php` and `archive-product.blade.php` views.

    wp acorn vendor:publish --tag="WooCommerce Templates"

Optionally publish a commented out `app/wc-template-hooks.php` file for customizing the WC template hooks.

    wp acorn vendor:publish --tag="WooCommerce Template Hook Overrides"

By default your theme has now declared WooCommerce support. To add support for specific features, add them to your `app/setup.php`

```php
add_theme_support('wc-product-gallery-zoom');
add_theme_support('wc-product-gallery-lightbox');
add_theme_support('wc-product-gallery-slider');
```

## Filters

```php
/**
 * Add support for WooCommerce Subscription templates.
 */
add_filter('sage-woocommerce/templates', function ($paths) {
    $paths[] = WP_PLUGIN_DIR . '/woocommerce-subscriptions/templates/',
    return $paths;
]);
```
