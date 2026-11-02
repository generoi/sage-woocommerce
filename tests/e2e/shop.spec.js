const { test, expect } = require('@playwright/test');
const { visit, expectShopRendersThroughBlade } = require('./helpers');

test('shop, product and cart pages render through Blade', async ({ page }) => {
  await expectShopRendersThroughBlade(page);
});

test('template parts that only exist as Blade views render, and missing ones are skipped', async ({ page }) => {
  await visit(page, '/shop/');

  const body = page.locator('body');
  await expect(body).toContainText('theme-content-custom-part');
  await expect(body).toContainText('theme-custom-slug');
  // The archive calls a part that exists nowhere just before this.
  await expect(body).toContainText('theme-archive-product-end');
});
