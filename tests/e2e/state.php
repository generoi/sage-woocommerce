<?php

/**
 * Reports what a separate process sees in the object cache and on disk.
 */

$paths = [];

foreach (['checkout/thankyou.php', 'emails/email-order-items.php'] as $name) {
    $key = sanitize_key(implode('-', ['template', $name, '', '', WC_VERSION]));
    $cached = (string) wp_cache_get($key, 'woocommerce');
    $path = wc_untokenize_path($cached, wc_get_path_define_tokens());

    $paths[$name] = [
        'cached' => $cached,
        'exists' => $path !== '' && file_exists($path),
    ];
}

echo wp_json_encode([
    'persistent' => wp_using_ext_object_cache(),
    'templates' => $paths,
    'mails' => get_option('sage_woocommerce_e2e_mails', []),
]);
