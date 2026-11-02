const { execFileSync } = require('node:child_process');
const { expect } = require('@playwright/test');

const PLUGIN = 'wp-content/plugins/sage-woocommerce';
const REDIS = 'sage-woocommerce-redis';

/** Run WP-CLI in its own process, as a deploy step would. */
function wp(...args) {
  return execFileSync('npx', ['@wordpress/env@latest', 'run', 'cli', 'wp', ...args], {
    encoding: 'utf8',
    stdio: ['ignore', 'pipe', 'pipe'],
    timeout: 120_000,
  }).trim();
}

/** Run a script from tests/e2e and read the JSON it prints. */
function script(name, ...args) {
  const output = wp('eval-file', `${PLUGIN}/tests/e2e/${name}.php`, ...args);

  return JSON.parse(output.slice(output.indexOf('{')));
}

function docker(...args) {
  return execFileSync('docker', args, { encoding: 'utf8', stdio: ['ignore', 'pipe', 'ignore'] }).trim();
}

/**
 * Run the tests of a describe block against a real Redis object cache, for tests
 * of state that has to outlive a request. Everything else runs on WordPress'
 * default in-memory cache.
 *
 * The drop-in is removed afterwards as wp-env cannot start with one pointing at
 * a Redis that is gone.
 */
function useRedis(test) {
  test.beforeAll(() => {
    const network = docker('network', 'ls', '--filter', 'name=sage-woocommerce', '--format', '{{.Name}}').split('\n')[0];

    docker('rm', '-f', REDIS);
    docker('run', '-d', '--name', REDIS, '--network', network, 'redis:7-alpine');

    wp('redis', 'enable', '--force');
    wp('cache', 'flush');
  });

  test.afterAll(() => {
    wp('redis', 'disable');
    docker('rm', '-f', REDIS);
  });
}

async function expectShopRendersThroughBlade(page) {
  await page.goto('/shop/');
  await expect(page.locator('body')).toContainText('theme-archive-product');

  await page.goto(process.env.E2E_PRODUCT_URL);
  await expect(page.locator('body')).toContainText('theme-content-single-product E2E product');

  await page.goto(`/?add-to-cart=${process.env.E2E_PRODUCT_ID}`);
  await page.goto('/cart/');
  await expect(page.locator('body')).toContainText('theme-cart');
  await expect(page.locator('body')).not.toContainText('Failed to open stream');
}

module.exports = { wp, script, useRedis, expectShopRendersThroughBlade };
