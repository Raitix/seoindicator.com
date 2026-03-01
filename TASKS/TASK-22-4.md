# TASK-22-4: Playwright Integration and Configuration

## STATUS
`To Prioritize`

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
