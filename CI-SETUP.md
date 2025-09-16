# GitHub Actions CI/CD Setup for Playwright Tests

This document explains how to set up and use GitHub Actions for running Playwright tests automatically.

## Workflow Overview

The GitHub Actions workflow (`.github/workflows/playwright.yml`) is configured to:

- **Trigger**: Run on pushes and pull requests to `main` and `develop` branches
- **Environment**: Ubuntu latest with Node.js LTS
- **Browsers**: Chromium with system dependencies
- **Artifacts**: Upload test reports and results for 30 days

## Workflow Steps

1. **Checkout code** - Downloads your repository
2. **Setup Node.js** - Installs Node.js LTS with npm caching
3. **Install dependencies** - Runs `npm ci` for faster, reliable installs
4. **Install Playwright browsers** - Installs Chromium with system dependencies
5. **Run tests** - Executes all Playwright tests
6. **Upload artifacts** - Saves test reports and results for debugging

## Key Features

### ✅ Modern Approach
- Uses the **recommended Playwright CLI** approach instead of the deprecated GitHub Action
- Based on [Microsoft's official documentation](https://github.com/microsoft/playwright-github-action)

### ✅ Performance Optimized
- **npm ci** for faster dependency installation
- **Node.js caching** to speed up builds
- **Single browser** (Chromium) for faster execution
- **Parallel test execution** where possible

### ✅ Robust Error Handling
- **60-minute timeout** prevents hanging builds
- **Artifact upload** on both success and failure
- **Retry logic** for flaky tests (2 retries on CI)

### ✅ Developer Friendly
- **HTML reports** for easy debugging
- **Test artifacts** available for download
- **Clear step naming** for easy troubleshooting

## Setup Instructions

### 1. Repository Setup
```bash
# Ensure your repository has the following files:
# - .github/workflows/playwright.yml
# - package.json
# - playwright.config.js
# - tests/ directory with your test files
```

### 2. Push to GitHub
```bash
git add .
git commit -m "Add Playwright tests with GitHub Actions"
git push origin main
```

### 3. Monitor Results
- Go to your repository on GitHub
- Click the "Actions" tab
- View the workflow runs and results
- Download artifacts if tests fail

## Local Testing

Test your CI setup locally:

```bash
# Install dependencies
npm ci

# Install browsers with system dependencies (like CI)
npm run install:browsers

# Run tests in CI mode
npm run test:ci
```

## Customization

### Change Trigger Branches
Edit `.github/workflows/playwright.yml`:
```yaml
on:
  push:
    branches: [ main, develop, feature/* ]  # Add your branches
  pull_request:
    branches: [ main ]  # Only main branch PRs
```

### Add More Browsers
Edit `playwright.config.js`:
```javascript
projects: [
  {
    name: 'chromium',
    use: { ...devices['Desktop Chrome'] },
  },
  {
    name: 'firefox',  // Uncomment this
    use: { ...devices['Desktop Firefox'] },
  },
]
```

### Change Artifact Retention
Edit `.github/workflows/playwright.yml`:
```yaml
retention-days: 7  # Keep artifacts for 7 days instead of 30
```

## Troubleshooting

### Tests Fail in CI but Pass Locally
- Check if your local server is running (CI doesn't have your local server)
- Consider using `webServer` configuration in `playwright.config.js`
- Verify environment variables and secrets

### Common CI Issues
- **Missing package-lock.json**: Run `npm install` locally to generate the lock file
- **Artifact upload warnings**: The workflow uses `if-no-files-found: ignore` to handle empty directories gracefully
- **Browser installation**: The workflow uses `--with-deps` flag for system dependencies

### Browser Installation Fails
- The workflow uses `--with-deps` flag for system dependencies
- Check GitHub Actions logs for specific error messages
- Ensure sufficient disk space in the runner

### Timeout Issues
- Increase timeout in workflow: `timeout-minutes: 120`
- Optimize tests to run faster
- Consider running fewer browsers in CI

## Best Practices

1. **Keep tests fast** - CI should complete in under 10 minutes
2. **Use specific selectors** - Avoid flaky tests
3. **Test critical paths** - Focus on user journeys
4. **Regular maintenance** - Update Playwright and dependencies
5. **Monitor results** - Check CI runs regularly

## Resources

- [Playwright GitHub Action Documentation](https://github.com/microsoft/playwright-github-action)
- [Playwright CI/CD Guide](https://playwright.dev/docs/ci)
- [GitHub Actions Documentation](https://docs.github.com/en/actions)
