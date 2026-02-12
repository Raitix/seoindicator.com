# Production Test Failure Report

**Workflow Run**: [https://github.com/Raitix/seoindicator.com/actions/runs/21944005020](https://github.com/Raitix/seoindicator.com/actions/runs/21944005020)
**Commit**: [01417e8e5b57a3e204e1fdd5f9424ac7e32c6840](https://github.com/Raitix/seoindicator.com/commit/01417e8e5b57a3e204e1fdd5f9424ac7e32c6840)
**Branch**: main
**Deployed to**: https://seoindicator.com
**Date**: Thu Feb 12 11:06:32 UTC 2026

---

# Production Test Failure Report - Thu Feb 12 11:06:30 UTC 2026

## Deployment & Test Summary
- **Date**: Thu Feb 12 11:06:30 UTC 2026
- **Commit**: 01417e8e5b57a3e204e1fdd5f9424ac7e32c6840
- **Branch**: main
- **Deployed to**: https://seoindicator.com
- **Test Environment**: Production

## Test Output

### Console Output
```
    Error Context: prod-test-results/indicator-checks-Indicator-72b4f--indicator-with-AI-response-chromium-retry1/error-context.md

    attachment #3: trace (application/zip) ─────────────────────────────────────────────────────────
    prod-test-results/indicator-checks-Indicator-72b4f--indicator-with-AI-response-chromium-retry1/trace.zip
    Usage:

        npx playwright show-trace prod-test-results/indicator-checks-Indicator-72b4f--indicator-with-AI-response-chromium-retry1/trace.zip

    ────────────────────────────────────────────────────────────────────────────────────────────────

    Retry #2 ───────────────────────────────────────────────────────────────────────────────────────

    Error: [2mexpect([22m[31mlocator[39m[2m).[22mtoBeVisible[2m()[22m failed

    Locator:  locator('.progress-container')
    Expected: visible
    Received: hidden
    Timeout:  10000ms

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

  1 failed
    [chromium] › tests/indicator-checks.spec.js:5:3 › Indicator Checks Test › should analyze raitisozols.com and verify URL indicator with AI response 
  4 passed (48.3s)
  1 error was not a part of any test, see above for details
::error file=tests/indicator-checks.spec.js,title=[chromium] › tests/indicator-checks.spec.js:5:3 › Indicator Checks Test › should analyze raitisozols.com and verify URL indicator with AI response,line=70,col=39::  1) [chromium] › tests/indicator-checks.spec.js:5:3 › Indicator Checks Test › should analyze raitisozols.com and verify URL indicator with AI response %0A    Error: expect(locator).toBeVisible() failed%0A%0A    Locator:  locator('.progress-container')%0A    Expected: visible%0A    Received: hidden%0A    Timeout:  10000ms%0A%0A    Call log:%0A      - Expect "toBeVisible" with timeout 10000ms%0A      - waiting for locator('.progress-container')%0A        14 × locator resolved to <div class="progress-container mt-3 mb-4">…</div>%0A           - unexpected value "hidden"%0A%0A%0A      68 |     if (!isVisible) {%0A      69 |       console.log('Waiting for progress bar to appear...');%0A    > 70 |       await expect(progressContainer).toBeVisible({ timeout: 10000 });%0A         |                                       ^%0A      71 |     }%0A      72 |     console.log('Progress bar appeared');%0A      73 |%0A        at /home/runner/work/seoindicator.com/seoindicator.com/tests/indicator-checks.spec.js:70:39
::error file=tests/indicator-checks.spec.js,title=[chromium] › tests/indicator-checks.spec.js:5:3 › Indicator Checks Test › should analyze raitisozols.com and verify URL indicator with AI response,line=70,col=39::  1) [chromium] › tests/indicator-checks.spec.js:5:3 › Indicator Checks Test › should analyze raitisozols.com and verify URL indicator with AI response %0A%0A    Retry #1 ───────────────────────────────────────────────────────────────────────────────────────%0A    Error: expect(locator).toBeVisible() failed%0A%0A    Locator:  locator('.progress-container')%0A    Expected: visible%0A    Received: hidden%0A    Timeout:  10000ms%0A%0A    Call log:%0A      - Expect "toBeVisible" with timeout 10000ms%0A      - waiting for locator('.progress-container')%0A        14 × locator resolved to <div class="progress-container mt-3 mb-4">…</div>%0A           - unexpected value "hidden"%0A%0A%0A      68 |     if (!isVisible) {%0A      69 |       console.log('Waiting for progress bar to appear...');%0A    > 70 |       await expect(progressContainer).toBeVisible({ timeout: 10000 });%0A         |                                       ^%0A      71 |     }%0A      72 |     console.log('Progress bar appeared');%0A      73 |%0A        at /home/runner/work/seoindicator.com/seoindicator.com/tests/indicator-checks.spec.js:70:39
::error file=tests/indicator-checks.spec.js,title=[chromium] › tests/indicator-checks.spec.js:5:3 › Indicator Checks Test › should analyze raitisozols.com and verify URL indicator with AI response,line=70,col=39::  1) [chromium] › tests/indicator-checks.spec.js:5:3 › Indicator Checks Test › should analyze raitisozols.com and verify URL indicator with AI response %0A%0A    Retry #2 ───────────────────────────────────────────────────────────────────────────────────────%0A    Error: expect(locator).toBeVisible() failed%0A%0A    Locator:  locator('.progress-container')%0A    Expected: visible%0A    Received: hidden%0A    Timeout:  10000ms%0A%0A    Call log:%0A      - Expect "toBeVisible" with timeout 10000ms%0A      - waiting for locator('.progress-container')%0A        14 × locator resolved to <div class="progress-container mt-3 mb-4">…</div>%0A           - unexpected value "hidden"%0A%0A%0A      68 |     if (!isVisible) {%0A      69 |       console.log('Waiting for progress bar to appear...');%0A    > 70 |       await expect(progressContainer).toBeVisible({ timeout: 10000 });%0A         |                                       ^%0A      71 |     }%0A      72 |     console.log('Progress bar appeared');%0A      73 |%0A        at /home/runner/work/seoindicator.com/seoindicator.com/tests/indicator-checks.spec.js:70:39
::notice title=🎭 Playwright Run Summary::  1 failed%0A    [chromium] › tests/indicator-checks.spec.js:5:3 › Indicator Checks Test › should analyze raitisozols.com and verify URL indicator with AI response %0A  4 passed (48.3s)
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
