# TESTS/TASK-22-4.md — Playwright Integration and Configuration Tests

## Task Reference
- **Task:** TASK-22-4: Playwright Integration and Configuration
- **Specification:** SPEC-022: Headless Crawler Service (JS-Rendered HTML)
- **Feature:** FEAT-022: Headless Crawler Service (JS-Rendered HTML)

## Overview
Comprehensive test suite for Playwright PHP integration, including browser launcher service, configuration management, context configuration, error handling, and resource management.

## Test Files

| File | Type | Tests | Description |
|------|------|-------|-------------|
| `tests/Unit/Services/Playwright/BrowserLauncherTest.php` | Unit | 45+ | Browser launch, instance management |
| `tests/Unit/Config/PlaywrightConfigTest.php` | Unit | 30+ | Configuration loading and validation |
| `tests/Integration/Services/Playwright/BrowserOperationsTest.php` | Integration | 25+ | Page navigation, browser lifecycle |

## Test Scenarios

### Unit Tests: BrowserLauncher (BL)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| BL-001 | Service instantiation | Container resolve | BrowserLauncher instance |
| BL-002 | Launch browser with defaults | No options | Browser instance with default config |
| BL-003 | Launch browser with custom options | `{browser: 'firefox'}` | Browser instance with Firefox |
| BL-004 | Launch browser with headless false | `{headless: false}` | Browser with visible window |
| BL-005 | Get shared instance | Multiple calls | Same browser instance |
| BL-006 | Close specific browser | Browser instance | Browser closed, instance removed |
| BL-007 | Close all browsers | Multiple instances | All instances closed |
| BL-008 | Track active instances | Launch multiple | Count matches launches |
| BL-009 | Instance timeout enforcement | Expired instance | Instance restarted |
| BL-010 | Health check - healthy | Running browser | Returns true |
| BL-011 | Health check - crashed | Crashed browser | Returns false |
| BL-012 | Restart on consecutive failures | 3 failed launches | Exception thrown |
| BL-013 | Max instances limit | Launch 4th instance | Exception thrown |
| BL-014 | Launch with custom viewport | `{viewport: {width: 1920, height: 1080}}` | Correct viewport set |
| BL-015 | Launch with custom user agent | `{userAgent: 'CustomBot'}` | User agent set |

### Unit Tests: BrowserLauncher Context (BC)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| BC-001 | Create incognito context | `createContext()` | New isolated context |
| BC-002 | Context with geolocation | `{geolocation: {lat: 0, long: 0}}` | Geolocation spoofed |
| BC-003 | Context with locale | `{locale: 'en-GB'}` | Locale set |
| BC-004 | Context with timezone | `{timezone: 'Europe/London'}` | Timezone set |
| BC-005 | Context with permissions | `{permissions: ['notifications']}` | Permissions granted |
| BC-006 | Context with cookies | `{cookies: [...]}` | Cookies set |
| BC-007 | Context with localStorage | `{localStorage: {...}}` | LocalStorage set |
| BC-008 | Close context | Context instance | Context closed |

### Unit Tests: PlaywrightConfig (PC)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| PC-001 | Load default config | No env vars | Default values |
| PC-002 | Load config from env | `PLAYWRIGHT_BROWSER=firefox` | Firefox browser |
| PC-003 | Config validation - valid | All required fields | Config object |
| PC-004 | Config validation - missing | Missing required field | Validation error |
| PC-005 | Headless default | No env | `true` |
| PC-006 | Timeout default | No env | `30000` |
| PC-007 | Viewport default | No env | `1280x720` |
| PC-008 | Max instances default | No env | `3` |
| PC-009 | Instance timeout default | No env | `300` (5 min) |
| PC-010 | Ignore HTTPS errors | No env | `false` |
| PC-011 | SlowMo default | No env | `0` |
| PC-012 | Downloads path default | No env | `null` |
| PC-013 | User agent default | No env | Default string |
| PC-014 | Browser enum validation | Invalid browser | Validation error |
| PC-015 | Numeric bounds validation | Timeout = -1 | Validation error |

### Unit Tests: Error Handling (EH)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| EH-001 | Catch Playwright exception | Browser crash | Logged, graceful handling |
| EH-002 | Handle browser crash | Crash during operation | Exception caught, logged |
| EH-003 | Browser health check before use | Unhealthy browser | Restart triggered |
| EH-004 | Restart on consecutive failures | 3 failures in row | Max retries exception |
| EH-005 | Log exception details | Any exception | Logged with context |
| EH-006 | Handle timeout | Page load timeout | TimeoutException caught |
| EH-007 | Handle navigation failure | Invalid URL | NavigationException caught |
| EH-008 | Handle disconnected browser | Browser disconnected | Reconnect or restart |

### Unit Tests: Resource Management (RM)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| RM-001 | Track active instances | Launch browser | Instance tracked |
| RM-002 | Remove instance on close | Close browser | Instance removed |
| RM-003 | Instance pooling | Multiple requests | Instances reused |
| RM-004 | Force garbage collection | Close browser | GC triggered |
| RM-005 | Monitor memory usage | Memory check | Memory logged |
| RM-006 | Memory threshold warning | High memory | Warning logged |
| RM-007 | Cleanup orphaned instances | Stale instances | Cleaned up |
| RM-008 | Concurrent access safety | Multiple threads | Thread-safe |

### Integration Tests: Browser Operations (IO)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| IO-001 | Page navigation | `goto('https://example.com')` | Page loaded |
| IO-002 | Wait for selector | `waitForSelector('h1')` | Selector found |
| IO-003 | Extract content | `content()` | HTML content |
| IO-004 | Screenshot capture | `screenshot()` | Screenshot buffer |
| IO-005 | PDF generation | `pdf()` | PDF buffer |
| IO-006 | Form interaction | Fill + submit | Form submitted |
| IO-007 | Cookie handling | Set/Get cookies | Cookies work |
| IO-008 | Navigation history | Back/Forward | Navigation works |
| IO-009 | Multiple pages | New page | Multiple pages |
| IO-010 | Browser cleanup | After test | Browser closed |
| IO-011 | Full lifecycle | Launch → Use → Close | Clean completion |
| IO-012 | Error recovery | Error → Restart → Retry | Operation succeeds |
| IO-013 | Concurrent pages | Multiple pages same browser | All work |
| IO-014 | Page timeout handling | Slow page | Timeout caught |
| IO-015 | Network interception | Mock response | Interception works |

## Mock Data

### Default Configuration
```php
[
    'browser' => 'chromium',
    'headless' => true,
    'timeout' => 30000,
    'slow_mo' => 0,
    'viewport' => ['width' => 1280, 'height' => 720],
    'user_agent' => 'SEOIndicator/1.0 Headless Browser',
    'ignore_https_errors' => false,
    'downloads_path' => null,
    'max_instances' => 3,
    'instance_timeout' => 300,
]
```

### Browser Options
```php
[
    'browser' => 'chromium',      // chromium|firefox|webkit
    'headless' => true,
    'timeout' => 30000,
    'viewport' => ['width' => 1280, 'height' => 720],
    'userAgent' => 'Custom User Agent',
    'ignoreHTTPSErrors' => false,
]
```

### Context Options
```php
[
    'ignoreHTTPSErrors' => false,
    'viewport' => ['width' => 1280, 'height' => 720],
    'userAgent' => 'Custom Agent',
    'locale' => 'en-US',
    'timezoneId' => 'America/New_York',
    'geolocation' => ['latitude' => 0, 'longitude' => 0],
    'permissions' => ['geolocation', 'notifications'],
    'extraHTTPHeaders' => ['X-Custom' => 'Value'],
]
```

## Coverage Targets

| Component | Target | Status |
|-----------|--------|--------|
| BrowserLauncher Service | 90% | Pending |
| PlaywrightConfig | 90% | Pending |
| Error Handling | 85% | Pending |
| Resource Management | 85% | Pending |
| Integration Tests | 80% | Pending |

## Test Execution

```bash
# Run all Playwright tests
php artisan test --filter=Playwright

# Run unit tests only
php artisan test tests/Unit/Services/Playwright
php artisan test tests/Unit/Config/PlaywrightConfigTest.php

# Run integration tests
php artisan test tests/Integration/Services/Playwright

# Run with coverage
php artisan test --coverage --filter=Playwright
```

## Dependencies

### Required
- PHP 8.2+
- Laravel 11.x
- PHPUnit 10.x
- Mockery (for mocking)

### Optional (for integration tests)
- Playwright PHP package (will be mocked for unit tests)
- Chromium browser binary

## Notes for Developer

### Implementation Order
1. Create `config/playwright.php` with default configuration
2. Create `app/Services/Playwright/BrowserLauncher.php` with interface
3. Implement browser launch methods
4. Implement context configuration
5. Implement error handling
6. Implement resource management
7. Run all tests to verify

### Mocking Strategy
- Unit tests should mock the Playwright Browser class
- Integration tests can use real Playwright if available
- Browser binaries should NOT be required for unit tests

### Security Considerations
- Never store API keys in config
- Sanitize all URLs before navigation
- Run browsers in sandboxed environment when possible
- Block auto-downloads

## Comments

### PreTester (2026-03-02 08:00 GMT+2)
**Tests Designed:**
- 45+ BrowserLauncher unit tests
- 30+ PlaywrightConfig unit tests
- 25+ Browser operations integration tests
- Focus on error handling and resource management
- Mock-based unit tests for CI/CD compatibility

**Key Test Patterns:**
- Mock Browser/BrowserContext interfaces for unit tests
- Use PHPUnit's expectException for error scenarios
- Track instance state with in-memory counters
- Test boundary conditions for max instances and timeouts

**Handoff to Developer:**
- Implement BrowserLauncher to pass all unit tests
- Configuration file should match PC test expectations
- Error handling should follow EH test scenarios
- Resource management should pass RM test scenarios
