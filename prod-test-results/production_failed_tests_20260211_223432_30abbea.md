# Production Test Failure Report

**Workflow Run**: [https://github.com/Raitix/seoindicator.com/actions/runs/21925676015](https://github.com/Raitix/seoindicator.com/actions/runs/21925676015)
**Commit**: [30abbea00f6c6a24d8aba14690839665e527cba3](https://github.com/Raitix/seoindicator.com/commit/30abbea00f6c6a24d8aba14690839665e527cba3)
**Branch**: main
**Deployed to**: https://seoindicator.com
**Date**: Wed Feb 11 22:34:32 UTC 2026

---

# Production Test Failure Report - Wed Feb 11 22:34:30 UTC 2026

## Deployment & Test Summary
- **Date**: Wed Feb 11 22:34:30 UTC 2026
- **Commit**: 30abbea00f6c6a24d8aba14690839665e527cba3
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
    prod-test-results/indicator-checks-Indicator-72b4f--indicator-with-AI-response-chromium-retry2/test-failed-1.png
    ────────────────────────────────────────────────────────────────────────────────────────────────

    Error Context: prod-test-results/indicator-checks-Indicator-72b4f--indicator-with-AI-response-chromium-retry2/error-context.md

  2) [chromium] › tests/homepage.spec.js:15:3 › SEOIndicator.com Homepage › should load the homepage successfully 

    TimeoutError: page.goto: Timeout 15000ms exceeded.
    Call log:
    [2m  - navigating to "https://seoindicator.com/", waiting until "load"[22m


      15 |   test('should load the homepage successfully', async ({ page }) => {
      16 |     // Navigate to the homepage
    > 17 |     const response = await page.goto('/');
         |                                 ^
      18 |     
      19 |     // Check that the page loads successfully (status 200)
      20 |     expect(response.status()).toBe(200);
        at /home/runner/work/seoindicator.com/seoindicator.com/tests/homepage.spec.js:17:33

  1 failed
    [chromium] › tests/indicator-checks.spec.js:5:3 › Indicator Checks Test › should analyze raitisozols.com and verify URL indicator with AI response 
  1 flaky
    [chromium] › tests/homepage.spec.js:15:3 › SEOIndicator.com Homepage › should load the homepage successfully 
  2 passed (52.0s)
  1 error was not a part of any test, see above for details
::error file=tests/indicator-checks.spec.js,title=[chromium] › tests/indicator-checks.spec.js:5:3 › Indicator Checks Test › should analyze raitisozols.com and verify URL indicator with AI response,line=70,col=39::  1) [chromium] › tests/indicator-checks.spec.js:5:3 › Indicator Checks Test › should analyze raitisozols.com and verify URL indicator with AI response %0A    Error: expect(locator).toBeVisible() failed%0A%0A    Locator:  locator('.progress-container')%0A    Expected: visible%0A    Received: hidden%0A    Timeout:  10000ms%0A%0A    Call log:%0A      - Expect "toBeVisible" with timeout 10000ms%0A      - waiting for locator('.progress-container')%0A        14 × locator resolved to <div class="progress-container mt-3 mb-4">…</div>%0A           - unexpected value "hidden"%0A%0A%0A      68 |     if (!isVisible) {%0A      69 |       console.log('Waiting for progress bar to appear...');%0A    > 70 |       await expect(progressContainer).toBeVisible({ timeout: 10000 });%0A         |                                       ^%0A      71 |     }%0A      72 |     console.log('Progress bar appeared');%0A      73 |%0A        at /home/runner/work/seoindicator.com/seoindicator.com/tests/indicator-checks.spec.js:70:39
::error file=tests/indicator-checks.spec.js,title=[chromium] › tests/indicator-checks.spec.js:5:3 › Indicator Checks Test › should analyze raitisozols.com and verify URL indicator with AI response,line=70,col=39::  1) [chromium] › tests/indicator-checks.spec.js:5:3 › Indicator Checks Test › should analyze raitisozols.com and verify URL indicator with AI response %0A%0A    Retry #1 ───────────────────────────────────────────────────────────────────────────────────────%0A    Error: expect(locator).toBeVisible() failed%0A%0A    Locator:  locator('.progress-container')%0A    Expected: visible%0A    Received: hidden%0A    Timeout:  10000ms%0A%0A    Call log:%0A      - Expect "toBeVisible" with timeout 10000ms%0A      - waiting for locator('.progress-container')%0A        14 × locator resolved to <div class="progress-container mt-3 mb-4">…</div>%0A           - unexpected value "hidden"%0A%0A%0A      68 |     if (!isVisible) {%0A      69 |       console.log('Waiting for progress bar to appear...');%0A    > 70 |       await expect(progressContainer).toBeVisible({ timeout: 10000 });%0A         |                                       ^%0A      71 |     }%0A      72 |     console.log('Progress bar appeared');%0A      73 |%0A        at /home/runner/work/seoindicator.com/seoindicator.com/tests/indicator-checks.spec.js:70:39
::error file=tests/indicator-checks.spec.js,title=[chromium] › tests/indicator-checks.spec.js:5:3 › Indicator Checks Test › should analyze raitisozols.com and verify URL indicator with AI response,line=70,col=39::  1) [chromium] › tests/indicator-checks.spec.js:5:3 › Indicator Checks Test › should analyze raitisozols.com and verify URL indicator with AI response %0A%0A    Retry #2 ───────────────────────────────────────────────────────────────────────────────────────%0A    Error: expect(locator).toBeVisible() failed%0A%0A    Locator:  locator('.progress-container')%0A    Expected: visible%0A    Received: hidden%0A    Timeout:  10000ms%0A%0A    Call log:%0A      - Expect "toBeVisible" with timeout 10000ms%0A      - waiting for locator('.progress-container')%0A        14 × locator resolved to <div class="progress-container mt-3 mb-4">…</div>%0A           - unexpected value "hidden"%0A%0A%0A      68 |     if (!isVisible) {%0A      69 |       console.log('Waiting for progress bar to appear...');%0A    > 70 |       await expect(progressContainer).toBeVisible({ timeout: 10000 });%0A         |                                       ^%0A      71 |     }%0A      72 |     console.log('Progress bar appeared');%0A      73 |%0A        at /home/runner/work/seoindicator.com/seoindicator.com/tests/indicator-checks.spec.js:70:39
::error file=tests/homepage.spec.js,title=[chromium] › tests/homepage.spec.js:15:3 › SEOIndicator.com Homepage › should load the homepage successfully,line=17,col=33::  2) [chromium] › tests/homepage.spec.js:15:3 › SEOIndicator.com Homepage › should load the homepage successfully %0A    TimeoutError: page.goto: Timeout 15000ms exceeded.%0A    Call log:%0A      - navigating to "https://seoindicator.com/", waiting until "load"%0A%0A%0A      15 |   test('should load the homepage successfully', async ({ page }) => {%0A      16 |     // Navigate to the homepage%0A    > 17 |     const response = await page.goto('/');%0A         |                                 ^%0A      18 |     %0A      19 |     // Check that the page loads successfully (status 200)%0A      20 |     expect(response.status()).toBe(200);%0A        at /home/runner/work/seoindicator.com/seoindicator.com/tests/homepage.spec.js:17:33
::notice title=🎭 Playwright Run Summary::  1 failed%0A    [chromium] › tests/indicator-checks.spec.js:5:3 › Indicator Checks Test › should analyze raitisozols.com and verify URL indicator with AI response %0A  1 flaky%0A    [chromium] › tests/homepage.spec.js:15:3 › SEOIndicator.com Homepage › should load the homepage successfully %0A  2 passed (52.0s)
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
