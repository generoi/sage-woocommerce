const { test } = require('@playwright/test');
const { expectShopRendersThroughBlade } = require('./helpers');

test('shop, product and cart pages render through Blade', async ({ page }) => {
  await expectShopRendersThroughBlade(page);
});
