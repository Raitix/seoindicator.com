<<<<<<< HEAD
// @ts-check
const { defineConfig, devices } = require('@playwright/test');
const fs = require('fs');
const path = require('path');

// Generate timestamp for reports
const now = new Date();
const timestamp = now.toLocaleDateString('en-GB').replace(/\//g, '-') + '_' + 
                 now.toLocaleTimeString('en-GB', {hour12: false}).replace(/:/g, '-');

// Determine output directory based on environment
const isProduction = process.env.BASE_URL === 'https://seoindicator.com' || process.env.CI;
const outputDir = isProduction ? './prod-test-results' : './local-test-results';

// Function to handle report cleanup and timestamping
function handleReportCleanup() {
  const playwrightReportPath = './playwright-report/index.html';
  const newReportPath = path.join(outputDir, `index_${timestamp}.html`);
  
  if (fs.existsSync(playwrightReportPath)) {
    // Ensure output directory exists
    if (!fs.existsSync(outputDir)) {
      fs.mkdirSync(outputDir, { recursive: true });
    }
    
    fs.renameSync(playwrightReportPath, newReportPath);
    console.log(`Report saved to: ${newReportPath}`);
    
    // Remove empty playwright-report directory
    try {
      fs.rmdirSync('./playwright-report');
    } catch (err) {
      // Directory not empty or other error, ignore
    }
  }
  
  // Remove .last-run.json file if it exists
  const lastRunPath = path.join(outputDir, '.last-run.json');
  if (fs.existsSync(lastRunPath)) {
    fs.unlinkSync(lastRunPath);
    console.log('Removed .last-run.json file');
  }
}

// Register cleanup function to run after tests
process.on('exit', handleReportCleanup);
process.on('SIGINT', () => {
  handleReportCleanup();
  process.exit(0);
});
process.on('SIGTERM', () => {
  handleReportCleanup();
  process.exit(0);
});

=======
const { defineConfig } = require('@playwright/test');
>>>>>>> d58f04db878e4d583ae76b3478f0a955539c0036

module.exports = defineConfig({
  testDir: './tests',
<<<<<<< HEAD
  outputDir: outputDir,
  /* Run tests in files in parallel */
  fullyParallel: true,
  /* Fail the build on CI if you accidentally left test.only in the source code. */
  forbidOnly: !!process.env.CI,
  /* Retry on CI only */
  retries: process.env.CI ? 2 : 0,
  /* Optimize workers for CI performance */
  workers: process.env.CI ? 4 : undefined,
  /* Reporter to use. See https://playwright.dev/docs/test-reporters */
  reporter: process.env.CI ? [['github'], ['html']] : [
    ['list'],
    ['html', { 
      outputFolder: './playwright-report',
      open: 'never'
    }]
  ],
  /* Shared settings for all the projects below. See https://playwright.dev/docs/api/class-testoptions. */
  use: {
    /* Base URL to use in actions like `await page.goto('/')`. */
    baseURL: process.env.BASE_URL || (process.env.CI ? 'https://seoindicator.com' : 'http://localhost'),

    /* Collect trace when retrying the failed test. See https://playwright.dev/docs/trace-viewer */
    trace: 'on-first-retry',
    
    /* Performance optimizations for CI */
    screenshot: 'only-on-failure', // Only affects automatic screenshots, not explicit page.screenshot() calls
    video: 'off', // Disable video recording completely
    actionTimeout: process.env.BASE_URL ? 10000 : 5000, // Longer timeout for production
    navigationTimeout: process.env.BASE_URL ? 60000 : 15000, // Much longer timeout for production
    // Disable unnecessary features for faster execution
    ignoreHTTPSErrors: true,
    bypassCSP: true,
  },

  /* Configure projects for major browsers */
  projects: [
    {
      name: 'chromium',
      use: { 
        ...devices['Desktop Chrome'],
        // Use stable Chrome channel in CI for faster setup
        ...(process.env.CI ? { channel: 'chrome' } : {})
      },
    },

    // {
    //   name: 'firefox',
    //   use: { ...devices['Desktop Firefox'] },
    // },

    // {
    //   name: 'webkit',
    //   use: { ...devices['Desktop Safari'] },
    // },

    /* Test against mobile viewports. */
    // {
    //   name: 'Mobile Chrome',
    //   use: { ...devices['Pixel 5'] },
    // },
    // {
    //   name: 'Mobile Safari',
    //   use: { ...devices['iPhone 12'] },
    // },

    /* Test against branded browsers. */
    // {
    //   name: 'Microsoft Edge',
    //   use: { ...devices['Desktop Edge'], channel: 'msedge' },
    // },
    // {
    //   name: 'Google Chrome',
    //   use: { ...devices['Desktop Chrome'], channel: 'chrome' },
    // },
  ],

  /* Run your local dev server before starting the tests */
  // webServer: {
  //   command: 'npm run start',
  //   url: 'http://127.0.0.1:3000',
  //   reuseExistingServer: !process.env.CI,
  // },
});
=======
  timeout: 30000,
  retries: 2,
  reporter: 'html',
  use: {
    browserName: 'chromium',
    headless: true,
    viewport: { width: 1280, height: 720 },
    video: 'retain-on-failure',
    screenshot: 'on',
  },
});
>>>>>>> d58f04db878e4d583ae76b3478f0a955539c0036
