# SEOIndicator.com Automated Tests

This directory contains automated tests for the SEOIndicator.com website using Playwright.

## Prerequisites

Before running the tests, make sure you have:

1. **Node.js** installed (version 16 or higher)
2. **Your local web server running** on `http://localhost` (the tests expect your website to be accessible at this URL)

## Setup

1. **Install dependencies:**
   ```bash
   npm install
   ```

2. **Install Playwright browsers:**
   ```bash
   npx playwright install
   ```

## Running Tests

### Local Testing (localhost)

Run all tests against localhost:
```bash
npm test
```

Run tests with a visible browser window:
```bash
npm run test:headed
```

Run tests with Playwright's interactive UI:
```bash
npm run test:ui
```

Run tests in debug mode (step through tests):
```bash
npm run test:debug
```

### Production Testing (https://seoindicator.com)

Run all tests against production site:
```bash
npm run test:production
```

Run production tests with visible browser:
```bash
npm run test:production:headed
```

Run production tests with interactive UI:
```bash
npm run test:production:ui
```

Run production tests in debug mode:
```bash
npm run test:production:debug
```

### View Test Reports

Run tests and generate HTML report:
```bash
npm run test:report
```

View the last generated HTML report:
```bash
npm run test:show-report
```

## Test Files

- `homepage.spec.js` - Tests for the homepage functionality including:
  - Page title verification
  - Page load success
  - Basic content visibility

## Configuration

The tests are configured in `playwright.config.js` with the following settings:
- Base URL: `http://localhost`
- Tests run in parallel
- HTML reporter enabled
- Tests run on Chromium browser (Firefox and WebKit commented out for faster execution)

## GitHub Actions CI/CD

The project includes a GitHub Actions workflow (`.github/workflows/playwright.yml`) that automatically runs tests on:
- Push to `main` or `develop` branches
- Pull requests to `main` or `develop` branches

### CI Features:
- **Automated browser installation** with system dependencies
- **Test artifacts upload** (reports and screenshots)
- **Cross-platform compatibility** (runs on Ubuntu)
- **Timeout protection** (60 minutes max)
- **Artifact retention** (30 days)

### CI Setup:
1. Push your code to a GitHub repository
2. The workflow will automatically run on the specified triggers
3. View test results in the Actions tab of your repository
4. Download test artifacts if tests fail for debugging

### Local CI Testing:
Test your CI setup locally:
```bash
npm run test:ci
npm run install:browsers
```

## Adding New Tests

To add new tests:

1. Create a new `.spec.js` file in the `tests` directory
2. Use the Playwright test API to write your tests
3. Follow the existing naming convention: `describe` for test suites, `test` for individual tests

Example:
```javascript
const { test, expect } = require('@playwright/test');

test.describe('My Feature', () => {
  test('should do something', async ({ page }) => {
    await page.goto('/');
    await expect(page.locator('h1')).toHaveText('Expected Text');
  });
});
```

## Troubleshooting

### Tests fail with connection errors
- Make sure your local web server is running on `http://localhost`
- Check that your server is accessible from the command line

### Browser installation issues
- Run `npx playwright install --force` to reinstall browsers
- Make sure you have sufficient disk space for browser downloads

### Permission issues on Windows
- Run your terminal as Administrator if you encounter permission errors
- Make sure Windows Defender isn't blocking the browser downloads
