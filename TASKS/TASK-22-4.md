# TASK-22-4: Playwright Integration and Configuration

## STATUS
`To Develop`

## PRIORITY
`High`

## RISK
`High`

## Related Specification
SPEC-022: Headless Crawler Service (JS-Rendered HTML)

## Related Feature
FEAT-022: Headless Crawler Service (JS-Rendered HTML)

---

## Description
Set up Playwright PHP integration for headless browser automation. Configure browser instances, launch options, and connection pooling.

## Acceptance Criteria

### Package Installation
- [ ] Install `playwright/playwright` PHP package via Composer
- [ ] Verify Playwright browser binaries are available (Chromium, Firefox, WebKit)
- [ ] Document browser installation: `npx playwright install chromium`

### Configuration File
- [ ] Create `config/playwright.php` with:
  - [ ] `browser` - default browser (chromium/firefox/webkit)
  - [ ] `headless` - bool (true for production)
  - [ ] `timeout` - default page timeout (30000ms)
  - [ ] `slow_mo` - delay between actions (0 for production)
  - [ ] `viewport` - default viewport dimensions (1280x720)
  - [ ] `user_agent` - default user agent string
  - [ ] `ignore_https_errors` - bool (false for production)
  - [ ] `downloads_path` - path for downloads (null)
  - [ ] `max_instances` - max browser instances (3)
  - [ ] `instance_timeout` - instance lifetime before restart (5 minutes)

### Browser Launcher Service
- [ ] Create `app/Services/Playwright/BrowserLauncher.php`
- [ ] `launch(array $options = []): Browser` - Launch new browser instance
- [ ] `getSharedInstance(): Browser` - Get or create shared browser instance
- [ ] `closeInstance(Browser $browser): void` - Close specific browser
- [ ] `closeAll(): void` - Close all browser instances

### Browser Context Configuration
- [ ] Support incognito context creation
- [ ] Configure geolocation spoofing (disabled by default)
- [ ] Configure locale and timezone
- [ ] Configure permissions (notifications, geolocation, etc.)
- [ ] Support custom cookies and localStorage

### Error Handling
- [ ] Catch and log Playwright exceptions
- [ ] Handle browser crash gracefully
- [ ] Implement browser health check before use
- [ ] Restart browser on consecutive failures

### Resource Management
- [ ] Track active browser instances
- [ ] Implement browser instance pooling (optional for MVP)
- [ ] Force garbage collection on browser close
- [ ] Monitor memory usage per instance

### Testing
- [ ] Unit test for browser launch
- [ ] Unit test for configuration loading
- [ ] Integration test for page navigation
- [ ] Test browser instance cleanup

## Dependencies
- None (external package)

## Effort Estimate
`Medium` (3-4 hours)

## Notes
- Chromium is preferred for MVP (best JS rendering support)
- Consider using persistent browser context for logged-in sessions (future)
- Browser instances should be closed after job completion to prevent memory leaks
- Server needs sufficient RAM for browser instances (512MB+ per instance)

## Security Considerations
- Run browsers in sandboxed environment if possible
- Block auto-downloads to prevent malicious file downloads
- Validate all URLs before navigation (SSRF protection in separate task)
- Never execute user-provided JavaScript

## Comments
_Leave notes for the next agent here_

### PreTester (2026-03-02 08:15 GMT+2)
**Tests Complete - Ready for Developer**

**Test Files Created:**
1. `tests/Unit/Services/Playwright/BrowserLauncherTest.php` - 45+ tests
2. `tests/Unit/Config/PlaywrightConfigTest.php` - 30+ tests
3. `tests/Integration/Services/Playwright/BrowserOperationsTest.php` - 25+ tests

**Supporting Files Created:**
1. `config/playwright.php` - Configuration file with all required settings
2. `app/Services/Playwright/BrowserLauncher.php` - Service skeleton
3. `app/Services/Playwright/Contracts/BrowserInterface.php` - Browser contract
4. `app/Services/Playwright/Contracts/BrowserContextInterface.php` - Context contract
5. `app/Services/Playwright/Contracts/PageInterface.php` - Page contract
6. `TESTS/TASK-22-4.md` - Test documentation

**Test Coverage:**
- BL-001 to BL-015: Browser launch and lifecycle tests
- BC-001 to BC-008: Browser context configuration tests
- PC-001 to PC-015: Configuration loading and validation tests
- IO-001 to IO-015: Integration browser operation tests
- Security tests: SSRF protection, download blocking

**Key Design Decisions:**
- Interface-based mocking allows tests to run without Playwright installed
- Configuration matches acceptance criteria exactly
- BrowserLauncher skeleton throws exceptions (Developer to implement)
- All tests use Mockery for mocking browser instances

**Running Tests:**
```bash
php artisan test --filter=Playwright
```

**Handoff to Developer:**
1. Install `playwright/playwright` PHP package
2. Implement BrowserLauncher::launch() using contracts
3. Replace skeleton implementations with real Playwright calls
4. All tests should pass after implementation

### ProjectManager (2026-03-01 23:35 GMT+2)
**Priority Confirmed: High**
- Foundation task for Phase 2 Rendering Engine
- Enables TASK-22-5 (PlaywrightRendererService) and TASK-22-6 (ContentProcessorService)
- Critical infrastructure for entire crawler functionality

**Risk Confirmed: High**
- External browser process management
- Memory management critical (512MB+ per instance)
- Resource exhaustion potential
- Browser lifecycle and cleanup complexity
- Instance pooling considerations for production

**Dependencies:**
- TASK-22-3 (CrawlerService Skeleton): `To Write Tests` ⏳
- PreTester can design tests NOW using interface-based mocking
- Implementation waits for TASK-22-3 to reach `To Test Locally`

**Execution Order:**
- TASK-22-3 → TASK-22-4 (this task) → TASK-22-5/22-6 (parallel)

**Handoff to PreTester:**
- Test browser launch with various options
- Test configuration loading
- Test instance lifecycle (launch, close, cleanup)
- Test error handling (browser crash, timeout)
- Test resource tracking and health checks
- Mock Browser interface for unit tests
