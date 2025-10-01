# Production Test Failure Report

**Workflow Run**: [https://github.com/Raitix/seoindicator.com/actions/runs/18168281933](https://github.com/Raitix/seoindicator.com/actions/runs/18168281933)
**Commit**: [fc9a23c57573dd55d23ab8323bb74855e1972c07](https://github.com/Raitix/seoindicator.com/commit/fc9a23c57573dd55d23ab8323bb74855e1972c07)
**Branch**: main
**Deployed to**: https://seoindicator.com
**Date**: Wed Oct  1 16:11:12 UTC 2025

---

# Production Test Failure Report - Wed Oct  1 16:11:10 UTC 2025

## Deployment & Test Summary
- **Date**: Wed Oct  1 16:11:10 UTC 2025
- **Commit**: fc9a23c57573dd55d23ab8323bb74855e1972c07
- **Branch**: main
- **Deployed to**: https://seoindicator.com
- **Test Environment**: Production

## Test Output

### Console Output
```

       8 |     // Navigate to localhost
       9 |     console.log('Navigating to localhost:8000...');
    > 10 |     const response = await page.goto('http://localhost:8000');
         |                                 ^
      11 |     console.log('Response status:', response.status());
      12 |     
      13 |     // Wait for page to load
        at /home/runner/work/seoindicator.com/seoindicator.com/tests/vercel-integration.spec.js:10:33

    attachment #1: screenshot (image/png) ──────────────────────────────────────────────────────────
    ci-test-results/vercel-integration-Vercel--6966f--get-results-from-Vercel-AI-chromium-retry1/test-failed-1.png
    ────────────────────────────────────────────────────────────────────────────────────────────────

    attachment #2: trace (application/zip) ─────────────────────────────────────────────────────────
    ci-test-results/vercel-integration-Vercel--6966f--get-results-from-Vercel-AI-chromium-retry1/trace.zip
    Usage:

        npx playwright show-trace ci-test-results/vercel-integration-Vercel--6966f--get-results-from-Vercel-AI-chromium-retry1/trace.zip

    ────────────────────────────────────────────────────────────────────────────────────────────────

    Retry #2 ───────────────────────────────────────────────────────────────────────────────────────

    Error: page.goto: net::ERR_CONNECTION_REFUSED at http://localhost:8000/
    Call log:
    [2m  - navigating to "http://localhost:8000/", waiting until "load"[22m


       8 |     // Navigate to localhost
       9 |     console.log('Navigating to localhost:8000...');
    > 10 |     const response = await page.goto('http://localhost:8000');
         |                                 ^
      11 |     console.log('Response status:', response.status());
      12 |     
      13 |     // Wait for page to load
        at /home/runner/work/seoindicator.com/seoindicator.com/tests/vercel-integration.spec.js:10:33

    attachment #1: screenshot (image/png) ──────────────────────────────────────────────────────────
    ci-test-results/vercel-integration-Vercel--6966f--get-results-from-Vercel-AI-chromium-retry2/test-failed-1.png
    ────────────────────────────────────────────────────────────────────────────────────────────────

  1 failed
    [chromium] › tests/vercel-integration.spec.js:4:3 › Vercel AI Integration Test › should analyze raitisozols.com and get results from Vercel AI 
  3 passed (7.5s)
  1 error was not a part of any test, see above for details
::error file=tests/vercel-integration.spec.js,title=[chromium] › tests/vercel-integration.spec.js:4:3 › Vercel AI Integration Test › should analyze raitisozols.com and get results from Vercel AI,line=10,col=33::  1) [chromium] › tests/vercel-integration.spec.js:4:3 › Vercel AI Integration Test › should analyze raitisozols.com and get results from Vercel AI %0A    Error: page.goto: net::ERR_CONNECTION_REFUSED at http://localhost:8000/%0A    Call log:%0A      - navigating to "http://localhost:8000/", waiting until "load"%0A%0A%0A       8 |     // Navigate to localhost%0A       9 |     console.log('Navigating to localhost:8000...');%0A    > 10 |     const response = await page.goto('http://localhost:8000');%0A         |                                 ^%0A      11 |     console.log('Response status:', response.status());%0A      12 |     %0A      13 |     // Wait for page to load%0A        at /home/runner/work/seoindicator.com/seoindicator.com/tests/vercel-integration.spec.js:10:33
::error file=tests/vercel-integration.spec.js,title=[chromium] › tests/vercel-integration.spec.js:4:3 › Vercel AI Integration Test › should analyze raitisozols.com and get results from Vercel AI,line=10,col=33::  1) [chromium] › tests/vercel-integration.spec.js:4:3 › Vercel AI Integration Test › should analyze raitisozols.com and get results from Vercel AI %0A%0A    Retry #1 ───────────────────────────────────────────────────────────────────────────────────────%0A    Error: page.goto: net::ERR_CONNECTION_REFUSED at http://localhost:8000/%0A    Call log:%0A      - navigating to "http://localhost:8000/", waiting until "load"%0A%0A%0A       8 |     // Navigate to localhost%0A       9 |     console.log('Navigating to localhost:8000...');%0A    > 10 |     const response = await page.goto('http://localhost:8000');%0A         |                                 ^%0A      11 |     console.log('Response status:', response.status());%0A      12 |     %0A      13 |     // Wait for page to load%0A        at /home/runner/work/seoindicator.com/seoindicator.com/tests/vercel-integration.spec.js:10:33
::error file=tests/vercel-integration.spec.js,title=[chromium] › tests/vercel-integration.spec.js:4:3 › Vercel AI Integration Test › should analyze raitisozols.com and get results from Vercel AI,line=10,col=33::  1) [chromium] › tests/vercel-integration.spec.js:4:3 › Vercel AI Integration Test › should analyze raitisozols.com and get results from Vercel AI %0A%0A    Retry #2 ───────────────────────────────────────────────────────────────────────────────────────%0A    Error: page.goto: net::ERR_CONNECTION_REFUSED at http://localhost:8000/%0A    Call log:%0A      - navigating to "http://localhost:8000/", waiting until "load"%0A%0A%0A       8 |     // Navigate to localhost%0A       9 |     console.log('Navigating to localhost:8000...');%0A    > 10 |     const response = await page.goto('http://localhost:8000');%0A         |                                 ^%0A      11 |     console.log('Response status:', response.status());%0A      12 |     %0A      13 |     // Wait for page to load%0A        at /home/runner/work/seoindicator.com/seoindicator.com/tests/vercel-integration.spec.js:10:33
::notice title=🎭 Playwright Run Summary::  1 failed%0A    [chromium] › tests/vercel-integration.spec.js:4:3 › Vercel AI Integration Test › should analyze raitisozols.com and get results from Vercel AI %0A  3 passed (7.5s)
```

### Environment Information
- **OS**: Linux runnervm3ublj 6.11.0-1018-azure #18~24.04.1-Ubuntu SMP Sat Jun 28 04:46:03 UTC 2025 x86_64 x86_64 x86_64 GNU/Linux
- **Node Version**: v22.19.0
- **Playwright Version**: Version 1.55.0
- **Browser**: Chromium
- **Target URL**: https://seoindicator.com

## Next Steps
1. Check the production site: https://seoindicator.com
2. Review test failures and fix issues
3. Re-run tests after fixes
4. Check deployment logs if site is not accessible
