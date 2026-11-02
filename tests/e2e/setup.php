<?php

/**
 * Prepares a shop that can take a guest order through the classic checkout.
 */

delete_option('sage_woocommerce_e2e_mails');

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

$category = term_exists('e2e-category', 'product_cat') ?: wp_insert_term('E2E category', 'product_cat', [
    'slug' => 'e2e-category',
    'description' => 'E2E category description',
]);
wp_set_object_terms($product->get_id(), (int) $category['term_id'], 'product_cat');

echo wp_json_encode([
    'product' => $product->get_id(),
    'productUrl' => get_permalink($product->get_id()),
    'categoryUrl' => get_term_link((int) $category['term_id'], 'product_cat'),
]);
