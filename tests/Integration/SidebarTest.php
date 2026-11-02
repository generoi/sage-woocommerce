<?php

namespace Genero\Sage\WooCommerce\Tests\Integration;

use Roots\Acorn\Application;
use WP_UnitTestCase;

class SidebarTest extends WP_UnitTestCase
{
    private function replaceDefaultSidebar(): void
    {
        add_action('woocommerce_sidebar', 'woocommerce_get_sidebar', 10);

        Application::getInstance()->make('woocommerce')->replaceDefaultSidebar();
    }

    public function testThemeWithoutSidebarFiresTheActionWithoutLoadingOne(): void
    {
        $names = [];
        add_action('get_sidebar', function ($name) use (&$names) {
            $names[] = $name;
        });

        $this->replaceDefaultSidebar();

        ob_start();
        do_action('woocommerce_sidebar');
        $output = trim(ob_get_clean());

        $this->assertFalse(has_action('woocommerce_sidebar', 'woocommerce_get_sidebar'));
        $this->assertSame(['shop'], $names);
        $this->assertSame('', $output);
    }

    public function testThemeWithSidebarKeepsTheCoreCallback(): void
    {
        add_filter('stylesheet_directory', fn () => dirname(__DIR__) . '/fixtures/theme-with-sidebar');

        $this->replaceDefaultSidebar();

        $this->assertSame(10, has_action('woocommerce_sidebar', 'woocommerce_get_sidebar'));
    }
}
