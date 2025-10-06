# Production Test Failure Report

**Workflow Run**: [https://github.com/Raitix/seoindicator.com/actions/runs/18266483540](https://github.com/Raitix/seoindicator.com/actions/runs/18266483540)
**Commit**: [4defd43206f12b7148c13861fafa9d2caab208fa](https://github.com/Raitix/seoindicator.com/commit/4defd43206f12b7148c13861fafa9d2caab208fa)
**Branch**: main
**Deployed to**: https://seoindicator.com
**Date**: Mon Oct  6 00:26:53 UTC 2025

---

# Production Test Failure Report - Mon Oct  6 00:26:51 UTC 2025

## Deployment & Test Summary
- **Date**: Mon Oct  6 00:26:51 UTC 2025
- **Commit**: 4defd43206f12b7148c13861fafa9d2caab208fa
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
        at /home/runner/work/seoindicator.com/seoindicator.com/tests/vercel-integration.spec.js:70:39

    attachment #1: screenshot (image/png) ──────────────────────────────────────────────────────────
    prod-test-results/vercel-integration-Vercel--6966f--get-results-from-Vercel-AI-chromium-retry2/test-failed-1.png
    ────────────────────────────────────────────────────────────────────────────────────────────────

    Error Context: prod-test-results/vercel-integration-Vercel--6966f--get-results-from-Vercel-AI-chromium-retry2/error-context.md

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
    [chromium] › tests/vercel-integration.spec.js:5:3 › Vercel AI Integration Test › should analyze raitisozols.com and get results from Vercel AI 
  1 flaky
    [chromium] › tests/homepage.spec.js:15:3 › SEOIndicator.com Homepage › should load the homepage successfully 
  2 passed (1.0m)
  1 error was not a part of any test, see above for details
::error file=tests/vercel-integration.spec.js,title=[chromium] › tests/vercel-integration.spec.js:5:3 › Vercel AI Integration Test › should analyze raitisozols.com and get results from Vercel AI,line=11,col=33::  1) [chromium] › tests/vercel-integration.spec.js:5:3 › Vercel AI Integration Test › should analyze raitisozols.com and get results from Vercel AI %0A    TimeoutError: page.goto: Timeout 15000ms exceeded.%0A    Call log:%0A      - navigating to "https://seoindicator.com/", waiting until "load"%0A%0A%0A       9 |     // Navigate to the configured base URL%0A      10 |     console.log('Navigating to base URL');%0A    > 11 |     const response = await page.goto('/');%0A         |                                 ^%0A      12 |     console.log('Response status:', response.status());%0A      13 |     %0A      14 |     // Wait for page to load%0A        at /home/runner/work/seoindicator.com/seoindicator.com/tests/vercel-integration.spec.js:11:33
::error file=tests/vercel-integration.spec.js,title=[chromium] › tests/vercel-integration.spec.js:5:3 › Vercel AI Integration Test › should analyze raitisozols.com and get results from Vercel AI,line=70,col=39::  1) [chromium] › tests/vercel-integration.spec.js:5:3 › Vercel AI Integration Test › should analyze raitisozols.com and get results from Vercel AI %0A%0A    Retry #1 ───────────────────────────────────────────────────────────────────────────────────────%0A    Error: expect(locator).toBeVisible() failed%0A%0A    Locator:  locator('.progress-container')%0A    Expected: visible%0A    Received: hidden%0A    Timeout:  10000ms%0A%0A    Call log:%0A      - Expect "toBeVisible" with timeout 10000ms%0A      - waiting for locator('.progress-container')%0A        14 × locator resolved to <div class="progress-container mt-3 mb-4">…</div>%0A           - unexpected value "hidden"%0A%0A%0A      68 |     if (!isVisible) {%0A      69 |       console.log('Waiting for progress bar to appear...');%0A    > 70 |       await expect(progressContainer).toBeVisible({ timeout: 10000 });%0A         |                                       ^%0A      71 |     }%0A      72 |     console.log('Progress bar appeared');%0A      73 |     %0A        at /home/runner/work/seoindicator.com/seoindicator.com/tests/vercel-integration.spec.js:70:39
::error file=tests/vercel-integration.spec.js,title=[chromium] › tests/vercel-integration.spec.js:5:3 › Vercel AI Integration Test › should analyze raitisozols.com and get results from Vercel AI,line=70,col=39::  1) [chromium] › tests/vercel-integration.spec.js:5:3 › Vercel AI Integration Test › should analyze raitisozols.com and get results from Vercel AI %0A%0A    Retry #2 ───────────────────────────────────────────────────────────────────────────────────────%0A    Error: expect(locator).toBeVisible() failed%0A%0A    Locator:  locator('.progress-container')%0A    Expected: visible%0A    Received: hidden%0A    Timeout:  10000ms%0A%0A    Call log:%0A      - Expect "toBeVisible" with timeout 10000ms%0A      - waiting for locator('.progress-container')%0A        14 × locator resolved to <div class="progress-container mt-3 mb-4">…</div>%0A           - unexpected value "hidden"%0A%0A%0A      68 |     if (!isVisible) {%0A      69 |       console.log('Waiting for progress bar to appear...');%0A    > 70 |       await expect(progressContainer).toBeVisible({ timeout: 10000 });%0A         |                                       ^%0A      71 |     }%0A      72 |     console.log('Progress bar appeared');%0A      73 |     %0A        at /home/runner/work/seoindicator.com/seoindicator.com/tests/vercel-integration.spec.js:70:39
::error file=tests/homepage.spec.js,title=[chromium] › tests/homepage.spec.js:15:3 › SEOIndicator.com Homepage › should load the homepage successfully,line=17,col=33::  2) [chromium] › tests/homepage.spec.js:15:3 › SEOIndicator.com Homepage › should load the homepage successfully %0A    TimeoutError: page.goto: Timeout 15000ms exceeded.%0A    Call log:%0A      - navigating to "https://seoindicator.com/", waiting until "load"%0A%0A%0A      15 |   test('should load the homepage successfully', async ({ page }) => {%0A      16 |     // Navigate to the homepage%0A    > 17 |     const response = await page.goto('/');%0A         |                                 ^%0A      18 |     %0A      19 |     // Check that the page loads successfully (status 200)%0A      20 |     expect(response.status()).toBe(200);%0A        at /home/runner/work/seoindicator.com/seoindicator.com/tests/homepage.spec.js:17:33
::notice title=🎭 Playwright Run Summary::  1 failed%0A    [chromium] › tests/vercel-integration.spec.js:5:3 › Vercel AI Integration Test › should analyze raitisozols.com and get results from Vercel AI %0A  1 flaky%0A    [chromium] › tests/homepage.spec.js:15:3 › SEOIndicator.com Homepage › should load the homepage successfully %0A  2 passed (1.0m)
Removed .last-run.json file
```

### Environment Information
- **OS**: Linux runnervmwhb2z 6.11.0-1018-azure #18~24.04.1-Ubuntu SMP Sat Jun 28 04:46:03 UTC 2025 x86_64 x86_64 x86_64 GNU/Linux
- **Node Version**: v22.20.0
- **Playwright Version**: Version 1.55.0
- **Browser**: Chrome (stable channel)
- **Target URL**: https://seoindicator.com

## Next Steps
1. Check the production site: https://seoindicator.com
2. Review test failures and fix issues
3. Re-run tests after fixes
4. Check deployment logs if site is not accessible
