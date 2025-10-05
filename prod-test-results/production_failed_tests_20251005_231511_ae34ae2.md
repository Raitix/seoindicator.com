# Production Test Failure Report

**Workflow Run**: [https://github.com/Raitix/seoindicator.com/actions/runs/18265658568](https://github.com/Raitix/seoindicator.com/actions/runs/18265658568)
**Commit**: [ae34ae27d0f7d131046b691af568887798d7a961](https://github.com/Raitix/seoindicator.com/commit/ae34ae27d0f7d131046b691af568887798d7a961)
**Branch**: main
**Deployed to**: https://seoindicator.com
**Date**: Sun Oct  5 23:15:11 UTC 2025

---

# Production Test Failure Report - Sun Oct  5 23:15:09 UTC 2025

## Deployment & Test Summary
- **Date**: Sun Oct  5 23:15:09 UTC 2025
- **Commit**: ae34ae27d0f7d131046b691af568887798d7a961
- **Branch**: main
- **Deployed to**: https://seoindicator.com
- **Test Environment**: Production

## Test Output

### Console Output
```
      10 |     console.log('Navigating to localhost');
    > 11 |     const response = await page.goto('http://localhost');
         |                                 ^
      12 |     console.log('Response status:', response.status());
      13 |     
      14 |     // Wait for page to load
        at /home/runner/work/seoindicator.com/seoindicator.com/tests/vercel-integration.spec.js:11:33

    attachment #1: screenshot (image/png) ──────────────────────────────────────────────────────────
    prod-test-results/vercel-integration-Vercel--6966f--get-results-from-Vercel-AI-chromium-retry1/test-failed-1.png
    ────────────────────────────────────────────────────────────────────────────────────────────────

    attachment #2: trace (application/zip) ─────────────────────────────────────────────────────────
    prod-test-results/vercel-integration-Vercel--6966f--get-results-from-Vercel-AI-chromium-retry1/trace.zip
    Usage:

        npx playwright show-trace prod-test-results/vercel-integration-Vercel--6966f--get-results-from-Vercel-AI-chromium-retry1/trace.zip

    ────────────────────────────────────────────────────────────────────────────────────────────────

    Retry #2 ───────────────────────────────────────────────────────────────────────────────────────

    Error: page.goto: net::ERR_CONNECTION_REFUSED at http://localhost/
    Call log:
    [2m  - navigating to "http://localhost/", waiting until "load"[22m


       9 |     // Navigate to localhost
      10 |     console.log('Navigating to localhost');
    > 11 |     const response = await page.goto('http://localhost');
         |                                 ^
      12 |     console.log('Response status:', response.status());
      13 |     
      14 |     // Wait for page to load
        at /home/runner/work/seoindicator.com/seoindicator.com/tests/vercel-integration.spec.js:11:33

    attachment #1: screenshot (image/png) ──────────────────────────────────────────────────────────
    prod-test-results/vercel-integration-Vercel--6966f--get-results-from-Vercel-AI-chromium-retry2/test-failed-1.png
    ────────────────────────────────────────────────────────────────────────────────────────────────

  1 failed
    [chromium] › tests/vercel-integration.spec.js:5:3 › Vercel AI Integration Test › should analyze raitisozols.com and get results from Vercel AI 
  3 passed (7.3s)
  1 error was not a part of any test, see above for details
::error file=tests/vercel-integration.spec.js,title=[chromium] › tests/vercel-integration.spec.js:5:3 › Vercel AI Integration Test › should analyze raitisozols.com and get results from Vercel AI,line=11,col=33::  1) [chromium] › tests/vercel-integration.spec.js:5:3 › Vercel AI Integration Test › should analyze raitisozols.com and get results from Vercel AI %0A    Error: page.goto: net::ERR_CONNECTION_REFUSED at http://localhost/%0A    Call log:%0A      - navigating to "http://localhost/", waiting until "load"%0A%0A%0A       9 |     // Navigate to localhost%0A      10 |     console.log('Navigating to localhost');%0A    > 11 |     const response = await page.goto('http://localhost');%0A         |                                 ^%0A      12 |     console.log('Response status:', response.status());%0A      13 |     %0A      14 |     // Wait for page to load%0A        at /home/runner/work/seoindicator.com/seoindicator.com/tests/vercel-integration.spec.js:11:33
::error file=tests/vercel-integration.spec.js,title=[chromium] › tests/vercel-integration.spec.js:5:3 › Vercel AI Integration Test › should analyze raitisozols.com and get results from Vercel AI,line=11,col=33::  1) [chromium] › tests/vercel-integration.spec.js:5:3 › Vercel AI Integration Test › should analyze raitisozols.com and get results from Vercel AI %0A%0A    Retry #1 ───────────────────────────────────────────────────────────────────────────────────────%0A    Error: page.goto: net::ERR_CONNECTION_REFUSED at http://localhost/%0A    Call log:%0A      - navigating to "http://localhost/", waiting until "load"%0A%0A%0A       9 |     // Navigate to localhost%0A      10 |     console.log('Navigating to localhost');%0A    > 11 |     const response = await page.goto('http://localhost');%0A         |                                 ^%0A      12 |     console.log('Response status:', response.status());%0A      13 |     %0A      14 |     // Wait for page to load%0A        at /home/runner/work/seoindicator.com/seoindicator.com/tests/vercel-integration.spec.js:11:33
::error file=tests/vercel-integration.spec.js,title=[chromium] › tests/vercel-integration.spec.js:5:3 › Vercel AI Integration Test › should analyze raitisozols.com and get results from Vercel AI,line=11,col=33::  1) [chromium] › tests/vercel-integration.spec.js:5:3 › Vercel AI Integration Test › should analyze raitisozols.com and get results from Vercel AI %0A%0A    Retry #2 ───────────────────────────────────────────────────────────────────────────────────────%0A    Error: page.goto: net::ERR_CONNECTION_REFUSED at http://localhost/%0A    Call log:%0A      - navigating to "http://localhost/", waiting until "load"%0A%0A%0A       9 |     // Navigate to localhost%0A      10 |     console.log('Navigating to localhost');%0A    > 11 |     const response = await page.goto('http://localhost');%0A         |                                 ^%0A      12 |     console.log('Response status:', response.status());%0A      13 |     %0A      14 |     // Wait for page to load%0A        at /home/runner/work/seoindicator.com/seoindicator.com/tests/vercel-integration.spec.js:11:33
::notice title=🎭 Playwright Run Summary::  1 failed%0A    [chromium] › tests/vercel-integration.spec.js:5:3 › Vercel AI Integration Test › should analyze raitisozols.com and get results from Vercel AI %0A  3 passed (7.3s)
Report saved to: prod-test-results/index_05-10-2025_23-15-02.html
Removed .last-run.json file
```

### Environment Information
- **OS**: Linux runnervmwhb2z 6.11.0-1018-azure #18~24.04.1-Ubuntu SMP Sat Jun 28 04:46:03 UTC 2025 x86_64 x86_64 x86_64 GNU/Linux
- **Node Version**: v22.20.0
- **Playwright Version**: Version 1.55.0
- **Browser**: Chromium
- **Target URL**: https://seoindicator.com

## Next Steps
1. Check the production site: https://seoindicator.com
2. Review test failures and fix issues
3. Re-run tests after fixes
4. Check deployment logs if site is not accessible
