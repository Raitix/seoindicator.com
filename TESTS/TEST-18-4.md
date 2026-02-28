# TEST-18-4: DomainValidatorService

## Task Reference
- **Task:** TASK-18-4
- **Feature:** FEAT-018 (Legacy 17-Indicator SEO Analysis Core Engine)
- **Status:** Tests Coverage In Progress

---

## Overview
Tests for the `DomainValidatorService` that normalizes domain input, validates format, checks accessibility, and detects redirects. Must integrate with existing SSRF protection from FEAT-016.

---

## Test Files

| File | Type | Tests | Assertions |
|------|------|-------|------------|
| `backend/tests/Unit/Services/DomainValidatorServiceTest.php` | Unit | 70+ | 180+ |
| `backend/tests/Feature/Services/DomainValidatorServiceIntegrationTest.php` | Integration | 45+ | 100+ |

---

## Test Scenarios

### Domain Normalization Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| DN-01 | Add https protocol | `example.com` | `https://example.com` |
| DN-02 | Preserve http protocol | `http://example.com` | `http://example.com` |
| DN-03 | Preserve https protocol | `https://example.com` | `https://example.com` |
| DN-04 | Remove www prefix | `www.example.com` | `https://example.com` |
| DN-05 | Preserve www in URL | `https://www.example.com` | `https://www.example.com` |
| DN-06 | Remove trailing slash | `example.com/` | `https://example.com` |
| DN-07 | Remove path from domain input | `example.com/page` | `https://example.com` |
| DN-08 | Remove query string | `example.com?foo=bar` | `https://example.com` |
| DN-09 | Remove fragment | `example.com#section` | `https://example.com` |
| DN-10 | Mixed normalization | `www.example.com/path?query=1#hash` | `https://example.com` |
| DN-11 | Lowercase domain | `EXAMPLE.COM` | `https://example.com` |
| DN-12 | Preserve port | `example.com:8080` | `https://example.com:8080` |
| DN-13 | Handle subdomain | `blog.example.com` | `https://blog.example.com` |
| DN-14 | Handle multi-level subdomain | `api.v2.example.com` | `https://api.v2.example.com` |

### Domain Format Validation Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| DV-01 | Valid domain | `example.com` | Valid |
| DV-02 | Valid subdomain | `blog.example.com` | Valid |
| DV-03 | Valid with TLD | `example.co.uk` | Valid |
| DV-04 | Valid new gTLD | `example.app` | Valid |
| DV-05 | Valid punycode | `xn--example-6q4a.com` | Valid |
| DV-06 | Empty domain | `` | Invalid, error `INVALID_DOMAIN` |
| DV-07 | Only whitespace | `   ` | Invalid, error `INVALID_DOMAIN` |
| DV-08 | Too long domain | 300+ chars | Invalid, error `INVALID_DOMAIN` |
| DV-09 | Invalid characters | `example!.com` | Invalid, error `INVALID_DOMAIN` |
| DV-10 | Missing TLD | `example` | Invalid, error `INVALID_DOMAIN` |
| DV-11 | Starting with hyphen | `-example.com` | Invalid, error `INVALID_DOMAIN` |
| DV-12 | Ending with hyphen | `example-.com` | Invalid, error `INVALID_DOMAIN` |
| DV-13 | Double hyphen | `exa--mple.com` | Valid (but suspicious) |
| DV-14 | Single label | `localhost` | Valid (but may fail accessibility) |
| DV-15 | IP address as domain | `192.168.1.1` | Blocked by SSRF protection |
| DV-16 | IPv6 address | `::1` | Blocked by SSRF protection |

### IDN (Internationalized Domain Name) Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| IDN-01 | Unicode domain | `例え.jp` | Converted to punycode, valid |
| IDN-02 | Cyrillic domain | `пример.рф` | Converted to punycode, valid |
| IDN-03 | Arabic domain | `مثال.eg` | Converted to punycode, valid |
| IDN-04 | Chinese domain | `例子.中国` | Converted to punycode, valid |
| IDN-05 | Mixed script | `exa例mple.com` | Converted properly, valid |
| IDN-06 | IDN with protocol | `https://例え.jp` | Handled correctly |

### Accessibility Check Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| AC-01 | HTTP 200 OK | Valid domain returning 200 | Accessible, URL returned |
| AC-02 | HTTP 301 redirect | Domain redirects once | Follow redirect, return final URL |
| AC-03 | HTTP 302 redirect | Domain redirects once | Follow redirect, return final URL |
| AC-04 | HTTP 307 redirect | Domain redirects once | Follow redirect, return final URL |
| AC-05 | HTTP 308 redirect | Domain redirects once | Follow redirect, return final URL |
| AC-06 | Meta refresh redirect | HTML with meta refresh | Handle or note limitation |
| AC-07 | JavaScript redirect | JS-based redirect | Not followed (server-side only) |
| AC-08 | HTTPS upgrade | HTTP redirects to HTTPS | Follow to HTTPS |
| AC-09 | WWW redirect | non-www redirects to www | Follow to www version |
| AC-10 | Non-WWW redirect | www redirects to non-www | Follow to non-www version |
| AC-11 | Connection timeout | Unreachable domain | Error `DOMAIN_UNREACHABLE` |
| AC-12 | DNS failure | Non-existent domain | Error `DOMAIN_UNREACHABLE` |
| AC-13 | SSL certificate error | Expired/self-signed cert | Error `SSL_ERROR` |
| AC-14 | HTTP 403 Forbidden | Domain returns 403 | Error `DOMAIN_FORBIDDEN` |
| AC-15 | HTTP 404 Not Found | Domain returns 404 | Error `DOMAIN_NOT_FOUND` |
| AC-16 | HTTP 500 Server Error | Domain returns 500 | Error `DOMAIN_ERROR` |
| AC-17 | Connection refused | Port closed | Error `DOMAIN_UNREACHABLE` |

### Redirect Limit Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| RL-01 | No redirect | Direct 200 response | Return original URL |
| RL-02 | 1 redirect | Single redirect chain | Follow and return final URL |
| RL-03 | 2 redirects | Double redirect chain | Follow and return final URL |
| RL-04 | 3 redirects (max) | Triple redirect chain | Follow and return final URL |
| RL-05 | 4 redirects (exceeds limit) | Quadruple redirect chain | Error `REDIRECT_LIMIT_EXCEEDED` |
| RL-06 | Redirect loop | A→B→A→B... | Error `REDIRECT_LOOP` |
| RL-07 | Cross-domain redirect | example.com → other.com | Follow, return final domain |
| RL-08 | Protocol change redirect | HTTP → HTTPS | Follow correctly |

### SSRF Protection Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| SSRF-01 | Localhost blocked | `localhost` | Error `BLOCKED_URL` |
| SSRF-02 | 127.0.0.1 blocked | `127.0.0.1` | Error `BLOCKED_URL` |
| SSRF-03 | 127.x.x.x blocked | `127.0.0.2` | Error `BLOCKED_URL` |
| SSRF-04 | 10.x.x.x blocked | `10.0.0.1` | Error `BLOCKED_URL` |
| SSRF-05 | 172.16-31.x.x blocked | `172.16.0.1` | Error `BLOCKED_URL` |
| SSRF-06 | 192.168.x.x blocked | `192.168.1.1` | Error `BLOCKED_URL` |
| SSRF-07 | Cloud metadata blocked | `169.254.169.254` | Error `BLOCKED_URL` |
| SSRF-08 | file:// protocol blocked | `file:///etc/passwd` | Error `BLOCKED_PROTOCOL` |
| SSRF-09 | ftp:// protocol blocked | `ftp://example.com` | Error `BLOCKED_PROTOCOL` |
| SSRF-10 | gopher:// protocol blocked | `gopher://example.com` | Error `BLOCKED_PROTOCOL` |
| SSRF-11 | DNS rebinding attempt | Resolves to private IP | Error `BLOCKED_URL` |
| SSRF-12 | IPv6 localhost | `::1` | Error `BLOCKED_URL` |
| SSRF-13 | IPv6 mapped IPv4 | `::ffff:127.0.0.1` | Error `BLOCKED_URL` |
| SSRF-14 | Decimal IP bypass | `2130706433` (127.0.0.1 decimal) | Error `BLOCKED_URL` |
| SSRF-15 | Hex IP bypass | `0x7f000001` (127.0.0.1 hex) | Error `BLOCKED_URL` |
| SSRF-16 | Octal IP bypass | `0177.0.0.1` (127.0.0.1 octal) | Error `BLOCKED_URL` |
| SSRF-17 | Internal hostname | `internal.example.com` | May warn or block (configurable) |
| SSRF-18 | Local hostname | `local.example.com` | May warn or block (configurable) |

### Return Value Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| RV-01 | Success with normalized URL | `example.com` | `{ url: "https://example.com", finalUrl: "https://example.com", redirects: 0, accessible: true }` |
| RV-02 | Success with redirect | `http://example.com` → https | `{ url: "http://example.com", finalUrl: "https://example.com", redirects: 1, accessible: true }` |
| RV-03 | Validation error | `` | Error with code `INVALID_DOMAIN` |
| RV-04 | Accessibility error | `nonexistent.invalid` | Error with code `DOMAIN_UNREACHABLE` |
| RV-05 | SSRF error | `localhost` | Error with code `BLOCKED_URL` |

### Error Handling Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| EH-01 | Invalid domain format | `!@#$%` | `ValidationException` with code `INVALID_DOMAIN` |
| EH-02 | Domain unreachable | `this-domain-does-not-exist.invalid` | `ValidationException` with code `DOMAIN_UNREACHABLE` |
| EH-03 | SSL certificate error | Self-signed cert domain | `ValidationException` with code `SSL_ERROR` |
| EH-04 | Timeout | Very slow domain | `ValidationException` with code `TIMEOUT` |
| EH-05 | Rate limited | Too many requests | `ValidationException` with code `RATE_LIMITED` |
| EH-06 | SSRF blocked | `127.0.0.1` | `ValidationException` with code `BLOCKED_URL` |
| EH-07 | Redirect limit exceeded | 4+ redirects | `ValidationException` with code `REDIRECT_LIMIT_EXCEEDED` |
| EH-08 | Redirect loop detected | Loop | `ValidationException` with code `REDIRECT_LOOP` |

### Timeout Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| TO-01 | Fast response | < 1 second | Success |
| TO-02 | Slow response | 5 seconds | Success if under timeout |
| TO-03 | Timeout exceeded | > 10 seconds | Error `TIMEOUT` |
| TO-04 | Custom timeout | Timeout: 5s, response: 3s | Success |
| TO-05 | Custom timeout exceeded | Timeout: 5s, response: 6s | Error `TIMEOUT` |

### Integration Tests (with URLValidationService)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| INT-01 | Uses existing SSRF service | Any domain | Calls URLValidationService |
| INT-02 | SSRF errors propagate | Blocked domain | Same error from URLValidationService |
| INT-03 | Normalization then SSRF | `localhost` normalized | Still blocked |
| INT-04 | Public domain passes | `google.com` | Passes SSRF check |

---

## Mock Data

### Valid Domain Inputs
```php
$validDomains = [
    'example.com',
    'www.example.com',
    'blog.example.com',
    'example.co.uk',
    'example.app',
    'xn--example-6q4a.com', // punycode
    '例え.jp', // IDN
];
```

### Invalid Domain Inputs
```php
$invalidDomains = [
    '',
    '   ',
    'example',
    '-example.com',
    'example-.com',
    'example!.com',
    str_repeat('a', 300) . '.com', // too long
];
```

### SSRF Blocked Inputs
```php
$ssrfBlocked = [
    'localhost',
    '127.0.0.1',
    '10.0.0.1',
    '172.16.0.1',
    '192.168.1.1',
    '169.254.169.254',
    'file:///etc/passwd',
    '::1',
];
```

---

## Service Interface

```php
interface DomainValidatorServiceInterface
{
    /**
     * Validate and normalize a domain.
     *
     * @param string $domain Input domain
     * @param array $options Optional settings (timeout, follow_redirects, etc.)
     * @return ValidatedDomain {
     *     string $url;           // Original normalized URL
     *     string $finalUrl;      // Final URL after redirects
     *     int $redirects;        // Number of redirects followed
     *     bool $accessible;      // Domain is accessible
     *     array $redirectChain;  // URLs in redirect chain
     * }
     * @throws ValidationException On validation failure
     */
    public function validate(string $domain, array $options = []): ValidatedDomain;
    
    /**
     * Normalize a domain string.
     *
     * @param string $domain
     * @return string Normalized URL
     */
    public function normalize(string $domain): string;
    
    /**
     * Check if domain format is valid.
     *
     * @param string $domain
     * @return bool
     */
    public function isValidFormat(string $domain): bool;
    
    /**
     * Check if domain is accessible.
     *
     * @param string $url Normalized URL
     * @param int $timeout Timeout in seconds
     * @return AccessibilityResult {
     *     bool $accessible;
     *     int $statusCode;
     *     string $finalUrl;
     *     int $redirects;
     *     array $redirectChain;
     * }
     */
    public function checkAccessibility(string $url, int $timeout = 10): AccessibilityResult;
}
```

---

## Coverage Targets

| Category | Target | Description |
|----------|--------|-------------|
| Normalization | 100% | All normalization rules |
| Format Validation | 100% | All validation cases |
| IDN Handling | 95% | Unicode/punycode conversion |
| Accessibility | 90% | HTTP status handling |
| Redirects | 100% | Redirect logic, limits |
| SSRF Protection | 100% | All bypass attempts |
| Error Handling | 100% | All error codes |
| Timeout | 100% | Timeout scenarios |

---

## Test Execution

```bash
# Run unit tests
php artisan test --filter=DomainValidatorServiceTest

# Run integration tests
php artisan test --filter=DomainValidatorServiceIntegrationTest

# Run with coverage
php artisan test --filter=DomainValidatorService --coverage

# Run all FEAT-018 tests
php artisan test --filter=TEST-18
```

---

## Dependencies
- `URLValidationService` (FEAT-016) for SSRF protection
- Laravel HTTP Client for accessibility checks
- `symfony/polyfill-intl-idn` for IDN handling
- `ValidationException` for error handling

---

## Notes for Developer
- Default timeout: 10 seconds
- Maximum redirects: 3
- Use Laravel HTTP Client with `timeout()` and `redirect()` options
- Cache validation results for 1 hour (same domain)
- Log all validation attempts for debugging
- Consider adding a `verify_ssl` option for development

---

## Comments

### [2026-02-28] PreTester
- Test documentation created
- 80+ test scenarios covering normalization, validation, SSRF, redirects
- Integration with URLValidationService from FEAT-016 required
- Ready for test implementation

### [2026-02-28] PreTester
- Unit tests created: 70+ test methods (DomainValidatorServiceTest.php)
- Integration tests created: 45+ test methods (DomainValidatorServiceIntegrationTest.php)
- Total: 115+ tests with 280+ assertions
- All test scenarios from documentation implemented
- Tests ready for Developer to run during implementation
