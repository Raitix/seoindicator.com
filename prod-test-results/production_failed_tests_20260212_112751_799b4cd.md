# Production Test Failure Report

**Workflow Run**: [https://github.com/Raitix/seoindicator.com/actions/runs/21944685359](https://github.com/Raitix/seoindicator.com/actions/runs/21944685359)
**Commit**: [799b4cd61bd3873abcbe4059cca1d6e74147735e](https://github.com/Raitix/seoindicator.com/commit/799b4cd61bd3873abcbe4059cca1d6e74147735e)
**Branch**: main
**Deployed to**: https://seoindicator.com
**Date**: Thu Feb 12 11:27:51 UTC 2026

---

# Production Test Failure Report - Thu Feb 12 11:27:50 UTC 2026

## Deployment & Test Summary
- **Date**: Thu Feb 12 11:27:50 UTC 2026
- **Commit**: 799b4cd61bd3873abcbe4059cca1d6e74147735e
- **Branch**: main
- **Deployed to**: https://seoindicator.com
- **Test Environment**: Production

## Test Output

### Console Output
```
    Call log:
    [2m  - Expect "toBeVisible" with timeout 10000ms[22m
    [2m  - waiting for locator('.progress-container')[22m
    [2m    14 × locator resolved to <div class="progress-container mt-3 mb-4">…</div>[22m
    [2m       - unexpected value "hidden"[22m


      68 |     if (!isVisible) {
      69 |       console.log('Waiting for progress bar to appear...');
    > 70 |       await expect(progressContainer).toBeVisible({ timeout: 10000 });
         |                                       ^
      71 |     }
      72 |     console.log('Progress bar appeared');
      73 |
        at /home/runner/work/seoindicator.com/seoindicator.com/tests/indicator-checks.spec.js:70:39

    attachment #1: screenshot (image/png) ──────────────────────────────────────────────────────────
    prod-test-results/indicator-checks-Indicator-72b4f--indicator-with-AI-response-chromium/test-failed-1.png
    ────────────────────────────────────────────────────────────────────────────────────────────────

    Error Context: prod-test-results/indicator-checks-Indicator-72b4f--indicator-with-AI-response-chromium/error-context.md

    Retry #1 ───────────────────────────────────────────────────────────────────────────────────────

    Test was interrupted.

    attachment #1: screenshot (image/png) ──────────────────────────────────────────────────────────
    prod-test-results/indicator-checks-Indicator-72b4f--indicator-with-AI-response-chromium-retry1/test-failed-1.png
    ────────────────────────────────────────────────────────────────────────────────────────────────

    attachment #2: trace (application/zip) ─────────────────────────────────────────────────────────
    prod-test-results/indicator-checks-Indicator-72b4f--indicator-with-AI-response-chromium-retry1/trace.zip
    Usage:

        npx playwright show-trace prod-test-results/indicator-checks-Indicator-72b4f--indicator-with-AI-response-chromium-retry1/trace.zip

    ────────────────────────────────────────────────────────────────────────────────────────────────

  2 failed
    [chromium] › tests/homepage.spec.js:29:3 › SEOIndicator.com Homepage › should show the updated main header 
    [chromium] › tests/indicator-checks.spec.js:5:3 › Indicator Checks Test › should analyze raitisozols.com and verify URL indicator with AI response 
  3 passed (25.4s)
  1 error was not a part of any test, see above for details
::error file=tests/homepage.spec.js,title=[chromium] › tests/homepage.spec.js:29:3 › SEOIndicator.com Homepage › should show the updated main header,line=38,col=30::  1) [chromium] › tests/homepage.spec.js:29:3 › SEOIndicator.com Homepage › should show the updated main header %0A    Error: expect(locator).toHaveText(expected) failed%0A%0A    Locator: locator('h1.main-title')%0A    Expected string: "AI Powered SEO Analysis XXX"%0A    Received string: "AI Powered SEO Analysis"%0A    Timeout: 5000ms%0A%0A    Call log:%0A      - Expect "toHaveText" with timeout 5000ms%0A      - waiting for locator('h1.main-title')%0A        9 × locator resolved to <h1 class="mb-4 main-title">AI Powered SEO Analysis</h1>%0A          - unexpected value "AI Powered SEO Analysis"%0A%0A%0A      36 |     // Check that the main header text is updated%0A      37 |     const mainHeader = page.locator('h1.main-title');%0A    > 38 |     await expect(mainHeader).toHaveText('AI Powered SEO Analysis XXX');%0A         |                              ^%0A      39 |   });%0A      40 |%0A      41 |   test('should have visible content on the page', async ({ page }) => {%0A        at /home/runner/work/seoindicator.com/seoindicator.com/tests/homepage.spec.js:38:30
::error file=tests/homepage.spec.js,title=[chromium] › tests/homepage.spec.js:29:3 › SEOIndicator.com Homepage › should show the updated main header,line=38,col=30::  1) [chromium] › tests/homepage.spec.js:29:3 › SEOIndicator.com Homepage › should show the updated main header %0A%0A    Retry #1 ───────────────────────────────────────────────────────────────────────────────────────%0A    Error: expect(locator).toHaveText(expected) failed%0A%0A    Locator: locator('h1.main-title')%0A    Expected string: "AI Powered SEO Analysis XXX"%0A    Received string: "AI Powered SEO Analysis"%0A    Timeout: 5000ms%0A%0A    Call log:%0A      - Expect "toHaveText" with timeout 5000ms%0A      - waiting for locator('h1.main-title')%0A        9 × locator resolved to <h1 class="mb-4 main-title">AI Powered SEO Analysis</h1>%0A          - unexpected value "AI Powered SEO Analysis"%0A%0A%0A      36 |     // Check that the main header text is updated%0A      37 |     const mainHeader = page.locator('h1.main-title');%0A    > 38 |     await expect(mainHeader).toHaveText('AI Powered SEO Analysis XXX');%0A         |                              ^%0A      39 |   });%0A      40 |%0A      41 |   test('should have visible content on the page', async ({ page }) => {%0A        at /home/runner/work/seoindicator.com/seoindicator.com/tests/homepage.spec.js:38:30
::error file=tests/homepage.spec.js,title=[chromium] › tests/homepage.spec.js:29:3 › SEOIndicator.com Homepage › should show the updated main header,line=38,col=30::  1) [chromium] › tests/homepage.spec.js:29:3 › SEOIndicator.com Homepage › should show the updated main header %0A%0A    Retry #2 ───────────────────────────────────────────────────────────────────────────────────────%0A    Error: expect(locator).toHaveText(expected) failed%0A%0A    Locator: locator('h1.main-title')%0A    Expected string: "AI Powered SEO Analysis XXX"%0A    Received string: "AI Powered SEO Analysis"%0A    Timeout: 5000ms%0A%0A    Call log:%0A      - Expect "toHaveText" with timeout 5000ms%0A      - waiting for locator('h1.main-title')%0A        9 × locator resolved to <h1 class="mb-4 main-title">AI Powered SEO Analysis</h1>%0A          - unexpected value "AI Powered SEO Analysis"%0A%0A%0A      36 |     // Check that the main header text is updated%0A      37 |     const mainHeader = page.locator('h1.main-title');%0A    > 38 |     await expect(mainHeader).toHaveText('AI Powered SEO Analysis XXX');%0A         |                              ^%0A      39 |   });%0A      40 |%0A      41 |   test('should have visible content on the page', async ({ page }) => {%0A        at /home/runner/work/seoindicator.com/seoindicator.com/tests/homepage.spec.js:38:30
::error file=tests/indicator-checks.spec.js,title=[chromium] › tests/indicator-checks.spec.js:5:3 › Indicator Checks Test › should analyze raitisozols.com and verify URL indicator with AI response,line=70,col=39::  2) [chromium] › tests/indicator-checks.spec.js:5:3 › Indicator Checks Test › should analyze raitisozols.com and verify URL indicator with AI response %0A    Error: expect(locator).toBeVisible() failed%0A%0A    Locator:  locator('.progress-container')%0A    Expected: visible%0A    Received: hidden%0A    Timeout:  10000ms%0A%0A    Call log:%0A      - Expect "toBeVisible" with timeout 10000ms%0A      - waiting for locator('.progress-container')%0A        14 × locator resolved to <div class="progress-container mt-3 mb-4">…</div>%0A           - unexpected value "hidden"%0A%0A%0A      68 |     if (!isVisible) {%0A      69 |       console.log('Waiting for progress bar to appear...');%0A    > 70 |       await expect(progressContainer).toBeVisible({ timeout: 10000 });%0A         |                                       ^%0A      71 |     }%0A      72 |     console.log('Progress bar appeared');%0A      73 |%0A        at /home/runner/work/seoindicator.com/seoindicator.com/tests/indicator-checks.spec.js:70:39
::error file=tests/indicator-checks.spec.js,title=[chromium] › tests/indicator-checks.spec.js:5:3 › Indicator Checks Test › should analyze raitisozols.com and verify URL indicator with AI response::  2) [chromium] › tests/indicator-checks.spec.js:5:3 › Indicator Checks Test › should analyze raitisozols.com and verify URL indicator with AI response %0A%0A    Retry #1 ───────────────────────────────────────────────────────────────────────────────────────%0A    Test was interrupted.
::notice title=🎭 Playwright Run Summary::  2 failed%0A    [chromium] › tests/homepage.spec.js:29:3 › SEOIndicator.com Homepage › should show the updated main header %0A    [chromium] › tests/indicator-checks.spec.js:5:3 › Indicator Checks Test › should analyze raitisozols.com and verify URL indicator with AI response %0A  3 passed (25.4s)
Removed .last-run.json file
```

### Environment Information
- **OS**: Linux runnervmwffz4 6.11.0-1018-azure #18~24.04.1-Ubuntu SMP Sat Jun 28 04:46:03 UTC 2025 x86_64 x86_64 x86_64 GNU/Linux
- **Node Version**: v24.13.0
- **Playwright Version**: Version 1.55.0
- **Browser**: Chrome (stable channel)
- **Target URL**: https://seoindicator.com

## Next Steps
1. Check the production site: https://seoindicator.com
2. Review test failures and fix issues
3. Re-run tests after fixes
4. Check deployment logs if site is not accessible
