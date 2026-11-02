const { test, expect } = require('@playwright/test');
const { wp, script, useRedis, expectShopRendersThroughBlade } = require('./helpers');

const THANKYOU = 'checkout/thankyou.php';
const EMAIL_ITEMS = 'emails/email-order-items.php';

/** Buy the product as a guest, and return the order id from the thank-you URL. */
async function placeOrder(page) {
  await page.goto(`/?add-to-cart=${process.env.E2E_PRODUCT_ID}`);
  await page.goto('/checkout/');

  await page.fill('#billing_first_name', 'Test');
  await page.fill('#billing_last_name', 'Customer');
  await page.fill('#billing_address_1', 'Testgatan 1');
  await page.fill('#billing_postcode', '00100');
  await page.fill('#billing_city', 'Helsinki');
  await page.fill('#billing_phone', '0401234567');
  await page.fill('#billing_email', 'customer@example.com');

  await expect(page.locator('.blockUI')).toHaveCount(0);
  await page.click('#place_order');
  await page.waitForURL('**/checkout/order-received/**');

  return page.url().match(/order-received\/(\d+)/)[1];
}

async function expectThankYouPage(page, order) {
  await expect(page.locator('body')).toContainText(`theme-thankyou order-${order}`);
  await expect(page.locator('body')).not.toContainText('Failed to open stream');
}

function expectOrderEmail(order) {
  // Matched on the order number in the email's heading, not on the item rows
  // under test, so an email with a blank item table is still found.
  const customer = script('state').mails.find(
    (mail) => mail.to === 'customer@example.com' && mail.body.includes(`#${order}`),
  );

  expect(customer, `customer email for order ${order}`).toBeTruthy();
  expect(customer.body).toContain(`theme-email-items order-${order} 1`);
  expect(customer.body).not.toContain('Failed to open stream');
}

// Deploys wipe compiled views while template paths stay in the object cache,
// which only happens with a cache that outlives a request.
test.describe('with a persistent object cache', () => {
  useRedis(test);

  // wp-env copies wp-content to the PHPUnit environment, where a cached Acorn
  // config turns the warnings wp-phpunit's bootstrap raises into exceptions.
  test.afterAll(() => {
    wp('acorn', 'optimize:clear');
  });

  test('shop, product and cart pages render after a deploy', async ({ page }) => {
    await expectShopRendersThroughBlade(page);

    wp('acorn', 'optimize');

    await expectShopRendersThroughBlade(page);
  });

  test('a deploy wiping compiled views does not blank orders placed afterwards', async ({ page }) => {
    // A customer's order leaves both loader paths in the persistent cache.
    const before = await placeOrder(page);
    await expectThankYouPage(page, before);
    expectOrderEmail(before);

    const cached = script('state');
    expect(cached.persistent).toBe(true);
    expect(cached.templates[THANKYOU].cached).toContain('-loader.php');
    expect(cached.templates[EMAIL_ITEMS].cached).toContain('-loader.php');
    expect(cached.templates[THANKYOU].exists).toBe(true);

    // What a deploy runs after flushing the object cache.
    wp('acorn', 'optimize');

    // The state production was stuck in. Without this the test could pass
    // because the bug did not reproduce, rather than because it was handled.
    const stale = script('state');
    expect(stale.templates[THANKYOU].cached).toBe(cached.templates[THANKYOU].cached);
    expect(stale.templates[THANKYOU].exists).toBe(false);
    expect(stale.templates[EMAIL_ITEMS].cached).toBe(cached.templates[EMAIL_ITEMS].cached);
    expect(stale.templates[EMAIL_ITEMS].exists).toBe(false);

    // The next customer: a fresh browser session, a real checkout.
    const context = await page.context().browser().newContext({ baseURL: 'http://localhost:8888' });
    const next = await context.newPage();
    const after = await placeOrder(next);

    await expectThankYouPage(next, after);
    expectOrderEmail(after);

    // Reloading is what customers would do on a blank page.
    await next.reload();
    await expectThankYouPage(next, after);

    await context.close();
  });
});
