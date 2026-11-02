const { test, expect } = require('@playwright/test');
const { wp, visit } = require('./helpers');

test.describe('templates a theme gets from vendor:publish', () => {
  test.beforeAll(() => {
    wp('theme', 'activate', 'sage-woocommerce-published-theme');
    wp('acorn', 'optimize:clear');
  });

  test.afterAll(() => {
    wp('theme', 'activate', 'sage-woocommerce-test-theme');
    wp('acorn', 'optimize:clear');
  });

  /** The parts of the archive that have to appear once and in the layout. */
  async function expectArchive(page, title) {
    const app = page.locator('#app');

    await expect(app.locator('.woocommerce-products-header')).toHaveCount(1);
    await expect(app.locator('h1')).toHaveCount(1);
    await expect(app.locator('h1')).toHaveText(title);
    await expect(app).toContainText('hooked-into-shop-loop-header');
    await expect(app.locator('ul.products li.product').first()).toContainText('E2E product');
    await expect(app.locator('.woocommerce-ordering')).toHaveCount(1);
  }

  /** Sage themes have no sidebar.php, so WordPress would fall back to its own. */
  async function expectNoDefaultSidebar(page) {
    await expect(page.locator('#app')).toContainText('hooked-into-get-sidebar-shop');
    await expect(page.locator('#app')).toContainText('hooked-into-woocommerce-sidebar');
    await expect(page.locator('#sidebar, .widget, #secondary')).toHaveCount(0);
  }

  test('the shop page renders', async ({ page }) => {
    await visit(page, '/shop/');

    await expectArchive(page, 'Shop');
    await expectNoDefaultSidebar(page);
  });

  test('a category page renders its title and description once', async ({ page }) => {
    await visit(page, process.env.E2E_CATEGORY_URL);

    await expectArchive(page, 'E2E category');
    await expect(page.locator('.woocommerce-products-header')).toContainText('E2E category description');
    await expect(page.getByText('E2E category description')).toHaveCount(1);
    await expectNoDefaultSidebar(page);
  });

  test('a product page renders and can add to the cart', async ({ page }) => {
    await visit(page, process.env.E2E_PRODUCT_URL);

    await expect(page.locator('#app h1.product_title')).toHaveText('E2E product');
    await expectNoDefaultSidebar(page);

    await page.locator('button.single_add_to_cart_button').click();
    await expect(page.locator('#app')).toContainText('has been added to your cart');
  });
});
