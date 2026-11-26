import { defineConfig, devices } from '@playwright/test';

// Adresse du site Drupal à tester (ex. https://vsk-e2e.ddev.site), donnée par
// run.sh. Aucune adresse de projet ici : le parcours vise une instance locale.
const baseURL = process.env.E2E_BASE_URL;
if (!baseURL) {
  throw new Error('E2E_BASE_URL manquant : lancer par ./run.sh <instance> (voir README.md).');
}

export default defineConfig({
  testDir: './specs',
  globalSetup: './global-setup.ts',
  // Un parcours contributeur complet (popins, médiathèque, deux enregistrements).
  timeout: 5 * 60 * 1000,
  expect: { timeout: 15 * 1000 },
  fullyParallel: false,
  workers: 1,
  retries: 0,
  forbidOnly: !!process.env.CI,
  outputDir: process.env.E2E_OUTPUT_DIR || 'test-results',
  reporter: [
    ['list'],
    ['html', { open: 'never', outputFolder: process.env.E2E_REPORT_DIR || 'playwright-report' }],
  ],
  use: {
    ...devices['Desktop Chrome'],
    viewport: { width: 1400, height: 1000 },
    baseURL,
    // Certificats ddev locaux (mkcert) : pas forcément connus du navigateur de Playwright.
    ignoreHTTPSErrors: true,
    locale: 'en-US',
    trace: 'on',
    video: 'on',
    screenshot: 'on',
    actionTimeout: 20 * 1000,
    navigationTimeout: 60 * 1000,
  },
});
