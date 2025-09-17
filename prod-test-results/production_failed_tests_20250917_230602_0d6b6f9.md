# Production Test Failure Report

**Workflow Run**: [https://github.com/Raitix/seoindicator.com/actions/runs/17812874092](https://github.com/Raitix/seoindicator.com/actions/runs/17812874092)
**Commit**: [0d6b6f9e519fc24d7d21318008f73ebf36f73e65](https://github.com/Raitix/seoindicator.com/commit/0d6b6f9e519fc24d7d21318008f73ebf36f73e65)
**Branch**: main
**Deployed to**: https://seoindicator.com
**Date**: Wed Sep 17 23:06:02 UTC 2025

---

# Production Test Failure Report - Wed Sep 17 23:06:00 UTC 2025

## Deployment & Test Summary
- **Date**: Wed Sep 17 23:06:00 UTC 2025
- **Commit**: 0d6b6f9e519fc24d7d21318008f73ebf36f73e65
- **Branch**: main
- **Deployed to**: https://seoindicator.com
- **Test Environment**: Production

## Test Output

### Console Output
```
    Timeout: 5000ms

    Call log:
    [2m  - Expect "toHaveTitle" with timeout 5000ms[22m
    [2m    9 × unexpected value "SEOIndicatorY.com - Analyze your SEO parameters"[22m


      18 |     
      19 |     // Check that the page title is present
    > 20 |     await expect(page).toHaveTitle(/SEOIndicator.com/);
         |                        ^
      21 |   });
      22 |
      23 |   test('should have visible content on the page', async ({ page }) => {
        at /home/runner/work/seoindicator.com/seoindicator.com/tests/homepage.spec.js:20:24

    attachment #1: screenshot (image/png) ──────────────────────────────────────────────────────────
    local-test-results/homepage-SEOIndicator-com--9a90f-d-the-homepage-successfully-chromium-retry1/test-failed-1.png
    ────────────────────────────────────────────────────────────────────────────────────────────────

    Error Context: local-test-results/homepage-SEOIndicator-com--9a90f-d-the-homepage-successfully-chromium-retry1/error-context.md

    attachment #3: trace (application/zip) ─────────────────────────────────────────────────────────
    local-test-results/homepage-SEOIndicator-com--9a90f-d-the-homepage-successfully-chromium-retry1/trace.zip
    Usage:

        npx playwright show-trace local-test-results/homepage-SEOIndicator-com--9a90f-d-the-homepage-successfully-chromium-retry1/trace.zip

    ────────────────────────────────────────────────────────────────────────────────────────────────

    Retry #2 ───────────────────────────────────────────────────────────────────────────────────────

    Test was interrupted.

    attachment #1: screenshot (image/png) ──────────────────────────────────────────────────────────
    local-test-results/homepage-SEOIndicator-com--9a90f-d-the-homepage-successfully-chromium-retry2/test-failed-1.png
    ────────────────────────────────────────────────────────────────────────────────────────────────

  2 failed
    [chromium] › tests/homepage.spec.js:4:3 › SEOIndicator.com Homepage › should have correct page title 
    [chromium] › tests/homepage.spec.js:12:3 › SEOIndicator.com Homepage › should load the homepage successfully 
  1 passed (43.2s)
  1 error was not a part of any test, see above for details
::error file=tests/homepage.spec.js,title=[chromium] › tests/homepage.spec.js:4:3 › SEOIndicator.com Homepage › should have correct page title,line=9,col=24::  1) [chromium] › tests/homepage.spec.js:4:3 › SEOIndicator.com Homepage › should have correct page title %0A    Error: expect(page).toHaveTitle(expected) failed%0A%0A    Expected string: "SEOIndicator.com - Analyze your SEO parameters"%0A    Received string: "SEOIndicatorY.com - Analyze your SEO parameters"%0A    Timeout: 5000ms%0A%0A    Call log:%0A      - Expect "toHaveTitle" with timeout 5000ms%0A        9 × unexpected value "SEOIndicatorY.com - Analyze your SEO parameters"%0A%0A%0A       7 |     %0A       8 |     // Check that the page title is correct%0A    >  9 |     await expect(page).toHaveTitle('SEOIndicator.com - Analyze your SEO parameters');%0A         |                        ^%0A      10 |   });%0A      11 |%0A      12 |   test('should load the homepage successfully', async ({ page }) => {%0A        at /home/runner/work/seoindicator.com/seoindicator.com/tests/homepage.spec.js:9:24
::error file=tests/homepage.spec.js,title=[chromium] › tests/homepage.spec.js:4:3 › SEOIndicator.com Homepage › should have correct page title,line=6,col=16::  1) [chromium] › tests/homepage.spec.js:4:3 › SEOIndicator.com Homepage › should have correct page title %0A%0A    Retry #1 ───────────────────────────────────────────────────────────────────────────────────────%0A    TimeoutError: page.goto: Timeout 15000ms exceeded.%0A    Call log:%0A      - navigating to "https://seoindicator.com/", waiting until "load"%0A%0A%0A      4 |   test('should have correct page title', async ({ page }) => {%0A      5 |     // Navigate to the homepage%0A    > 6 |     await page.goto('/');%0A        |                ^%0A      7 |     %0A      8 |     // Check that the page title is correct%0A      9 |     await expect(page).toHaveTitle('SEOIndicator.com - Analyze your SEO parameters');%0A        at /home/runner/work/seoindicator.com/seoindicator.com/tests/homepage.spec.js:6:16
::error file=tests/homepage.spec.js,title=[chromium] › tests/homepage.spec.js:4:3 › SEOIndicator.com Homepage › should have correct page title,line=9,col=24::  1) [chromium] › tests/homepage.spec.js:4:3 › SEOIndicator.com Homepage › should have correct page title %0A%0A    Retry #2 ───────────────────────────────────────────────────────────────────────────────────────%0A    Error: expect(page).toHaveTitle(expected) failed%0A%0A    Expected string: "SEOIndicator.com - Analyze your SEO parameters"%0A    Received string: "SEOIndicatorY.com - Analyze your SEO parameters"%0A    Timeout: 5000ms%0A%0A    Call log:%0A      - Expect "toHaveTitle" with timeout 5000ms%0A        9 × unexpected value "SEOIndicatorY.com - Analyze your SEO parameters"%0A%0A%0A       7 |     %0A       8 |     // Check that the page title is correct%0A    >  9 |     await expect(page).toHaveTitle('SEOIndicator.com - Analyze your SEO parameters');%0A         |                        ^%0A      10 |   });%0A      11 |%0A      12 |   test('should load the homepage successfully', async ({ page }) => {%0A        at /home/runner/work/seoindicator.com/seoindicator.com/tests/homepage.spec.js:9:24
::error file=tests/homepage.spec.js,title=[chromium] › tests/homepage.spec.js:12:3 › SEOIndicator.com Homepage › should load the homepage successfully,line=14,col=33::  2) [chromium] › tests/homepage.spec.js:12:3 › SEOIndicator.com Homepage › should load the homepage successfully %0A    TimeoutError: page.goto: Timeout 15000ms exceeded.%0A    Call log:%0A      - navigating to "https://seoindicator.com/", waiting until "load"%0A%0A%0A      12 |   test('should load the homepage successfully', async ({ page }) => {%0A      13 |     // Navigate to the homepage%0A    > 14 |     const response = await page.goto('/');%0A         |                                 ^%0A      15 |     %0A      16 |     // Check that the page loads successfully (status 200)%0A      17 |     expect(response.status()).toBe(200);%0A        at /home/runner/work/seoindicator.com/seoindicator.com/tests/homepage.spec.js:14:33
::error file=tests/homepage.spec.js,title=[chromium] › tests/homepage.spec.js:12:3 › SEOIndicator.com Homepage › should load the homepage successfully,line=20,col=24::  2) [chromium] › tests/homepage.spec.js:12:3 › SEOIndicator.com Homepage › should load the homepage successfully %0A%0A    Retry #1 ───────────────────────────────────────────────────────────────────────────────────────%0A    Error: expect(page).toHaveTitle(expected) failed%0A%0A    Expected pattern: /SEOIndicator.com/%0A    Received string:  "SEOIndicatorY.com - Analyze your SEO parameters"%0A    Timeout: 5000ms%0A%0A    Call log:%0A      - Expect "toHaveTitle" with timeout 5000ms%0A        9 × unexpected value "SEOIndicatorY.com - Analyze your SEO parameters"%0A%0A%0A      18 |     %0A      19 |     // Check that the page title is present%0A    > 20 |     await expect(page).toHaveTitle(/SEOIndicator.com/);%0A         |                        ^%0A      21 |   });%0A      22 |%0A      23 |   test('should have visible content on the page', async ({ page }) => {%0A        at /home/runner/work/seoindicator.com/seoindicator.com/tests/homepage.spec.js:20:24
::error file=tests/homepage.spec.js,title=[chromium] › tests/homepage.spec.js:12:3 › SEOIndicator.com Homepage › should load the homepage successfully::  2) [chromium] › tests/homepage.spec.js:12:3 › SEOIndicator.com Homepage › should load the homepage successfully %0A%0A    Retry #2 ───────────────────────────────────────────────────────────────────────────────────────%0A    Test was interrupted.
::notice title=🎭 Playwright Run Summary::  2 failed%0A    [chromium] › tests/homepage.spec.js:4:3 › SEOIndicator.com Homepage › should have correct page title %0A    [chromium] › tests/homepage.spec.js:12:3 › SEOIndicator.com Homepage › should load the homepage successfully %0A  1 passed (43.2s)
```

### Environment Information
- **OS**: Linux runnervmf4ws1 6.11.0-1018-azure #18~24.04.1-Ubuntu SMP Sat Jun 28 04:46:03 UTC 2025 x86_64 x86_64 x86_64 GNU/Linux
- **Node Version**: v22.19.0
- **Playwright Version**: Version 1.55.0
- **Browser**: Chromium
- **Target URL**: https://seoindicator.com

## Next Steps
1. Check the production site: https://seoindicator.com
2. Review test failures and fix issues
3. Re-run tests after fixes
4. Check deployment logs if site is not accessible
