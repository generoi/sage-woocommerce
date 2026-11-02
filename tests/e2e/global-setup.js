const { wp, script } = require('./helpers');

module.exports = async () => {
  wp('theme', 'activate', 'sage-woocommerce-test-theme');
  wp('acorn', 'optimize:clear');

  const { product, productUrl } = script('setup');
  process.env.E2E_PRODUCT_ID = String(product);
  process.env.E2E_PRODUCT_URL = productUrl;
};
