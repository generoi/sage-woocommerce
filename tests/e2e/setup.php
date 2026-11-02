<?php

/**
 * Prepares a shop that can take a guest order through the classic checkout.
 */

update_option('woocommerce_default_country', 'FI');
update_option('woocommerce_coming_soon', 'no');
update_option('woocommerce_store_pages_only', 'no');
update_option('woocommerce_cod_settings', [
    'enabled' => 'yes',
    'title' => 'Cash on delivery',
    'enable_for_virtual' => 'yes',
]);

wp_update_post([
    'ID' => wc_get_page_id('checkout'),
    'post_content' => '[woocommerce_checkout]',
]);

wp_update_post([
    'ID' => wc_get_page_id('cart'),
    'post_content' => '[woocommerce_cart]',
]);

$product = new WC_Product_Simple();
$product->set_name('E2E product');
$product->set_regular_price('10');
$product->set_virtual(true);
$product->set_status('publish');
$product->save();

echo wp_json_encode([
    'product' => $product->get_id(),
    'productUrl' => get_permalink($product->get_id()),
]);
