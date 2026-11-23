import { defineConfig } from '@playwright/test';

export default defineConfig({
  testDir: './tests/e2e',
  timeout: 60000,
  expect: {
    timeout: 10000,
  },
  fullyParallel: false,
  workers: 1,
  reporter: 'list',
  use: {
    baseURL: 'http://127.0.0.1:8000',
    headless: true,
  },
  webServer: {
    command: 'php artisan serve --host=127.0.0.1 --port=8000',
    url: 'http://127.0.0.1:8000',
    reuseExistingServer: true,
    timeout: 120000,
  },
  projects: [
    {
      name: 'Desktop',
      use: {
        viewport: { width: 1280, height: 800 },
      },
    },
    {
      name: 'Mobile',
      use: {
        viewport: { width: 390, height: 844 },
      },
    },
  ],
});
