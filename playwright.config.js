const { defineConfig } = require('@playwright/test');

module.exports = defineConfig({
  testDir: './tests/e2e',
  globalSetup: require.resolve('./tests/e2e/global-setup.js'),
  // The test deliberately changes state shared by the whole site.
  workers: 1,
  timeout: 180_000,
  reporter: [['list'], ['html', { open: 'never' }]],
  use: {
    baseURL: 'http://localhost:8888',
    trace: 'retain-on-failure',
  },
});
