# TEST-16-3: Backend API Proxy for SEO Tools Tests

## Task Reference
- **Task:** [TASK-16-3](../TASKS/TASK-16-3.md)
- **Specification:** [SPEC-016](../SPECIFICATIONS/SPEC-016.md)
- **Feature:** [FEAT-016](../FEATURES/FEAT-016.md)
- **Dependents:** TASK-16-1 (Meta Tag Analyzer), TASK-16-2 (Keyword Density Checker)

---

## Overview

Test suite for the Backend API Proxy that handles external page fetches for SEO tool analysis, avoiding CORS issues and implementing security controls.

### Key Test Areas
1. **Unit Tests** - Controller logic, Cheerio parsing, validation utilities
2. **Integration Tests** - Endpoint behavior, external fetch handling, rate limiting
3. **Security Tests** - Rate limiting, CAPTCHA enforcement, input sanitization
4. **Performance Tests** - Response times, concurrent requests, resource cleanup

---

## Test Files

| File | Type | Purpose |
|------|------|---------|
| `tests/Unit/Controllers/APIProxyControllerTest.php` | Unit | Controller method tests |
| `tests/Unit/Services/CheerioParserServiceTest.php` | Unit | HTML parsing logic |
| `tests/Unit/Services/RateLimitServiceTest.php` | Unit | Rate limiting logic |
| `tests/Unit/Validators/URLValidatorTest.php` | Unit | URL validation |
| `tests/Feature/API/ProxyEndpointTest.php` | Integration | Full endpoint tests |
| `tests/Feature/Security/RateLimitTest.php` | Security | Rate limit enforcement |
| `tests/Feature/Security/CaptchaEnforcementTest.php` | Security | CAPTCHA trigger tests |
| `tests/Feature/External/FetchTest.php` | Integration | External URL fetching |

---

## Test Scenarios

### Unit Tests - APIProxyController

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| UT-001 | Valid URL request | `{ "url": "https://example.com" }` | HTTP 200 with parsed HTML data |
| UT-002 | Invalid URL format | `{ "url": "not-a-url" }` | HTTP 422 with validation error |
| UT-003 | Empty URL request | `{ "url": "" }` | HTTP 422 with "URL required" error |
| UT-004 | Missing URL field | `{}` | HTTP 422 with "URL required" error |
| UT-005 | URL with query params | `{ "url": "https://example.com?query=1" }` | HTTP 200, params preserved |
| UT-006 | URL with fragment | `{ "url": "https://example.com#section" }` | HTTP 200, fragment handled |
| UT-007 | HTTP URL (non-HTTPS) | `{ "url": "http://example.com" }` | HTTP 200, fetch proceeds |
| UT-008 | URL with port | `{ "url": "https://example.com:8080" }` | HTTP 200, port preserved |
| UT-009 | localhost URL blocked | `{ "url": "http://localhost" }` | HTTP 403, security error |
| UT-010 | Private IP URL blocked | `{ "url": "http://192.168.1.1" }` | HTTP 403, security error |
| UT-011 | Internal IP blocked | `{ "url": "http://10.0.0.1" }` | HTTP 403, security error |
| UT-012 | 127.0.0.1 blocked | `{ "url": "http://127.0.0.1" }` | HTTP 403, security error |
| UT-013 | file:// protocol blocked | `{ "url": "file:///etc/passwd" }` | HTTP 422, protocol not allowed |
| UT-014 | ftp:// protocol blocked | `{ "url": "ftp://example.com" }` | HTTP 422, protocol not allowed |
| UT-015 | javascript: protocol blocked | `{ "url": "javascript:alert(1)" }` | HTTP 422, protocol not allowed |
| UT-016 | data: protocol blocked | `{ "url": "data:text/html,..." }` | HTTP 422, protocol not allowed |
| UT-017 | URL max length exceeded | URL > 2048 chars | HTTP 422, "URL too long" error |
| UT-018 | Request timeout handling | Slow remote server | HTTP 504, timeout error |
| UT-019 | Connection refused handling | Unreachable host | HTTP 502, connection error |
| UT-020 | DNS failure handling | Non-existent domain | HTTP 502, DNS error |

### Unit Tests - Cheerio Parser Service

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| UT-021 | Extract title tag | HTML with `<title>Test Page</title>` | `{ title: "Test Page" }` |
| UT-022 | Extract meta description | HTML with meta description | `{ description: "..." }` |
| UT-023 | Extract canonical URL | HTML with canonical link | `{ canonical: "..." }` |
| UT-024 | Extract Open Graph title | HTML with og:title | `{ ogTitle: "..." }` |
| UT-025 | Extract Open Graph description | HTML with og:description | `{ ogDescription: "..." }` |
| UT-026 | Extract Open Graph image | HTML with og:image | `{ ogImage: "..." }` |
| UT-027 | Extract Open Graph URL | HTML with og:url | `{ ogUrl: "..." }` |
| UT-028 | Extract Twitter Card type | HTML with twitter:card | `{ twitterCard: "summary" }` |
| UT-029 | Extract Twitter title | HTML with twitter:title | `{ twitterTitle: "..." }` |
| UT-030 | Extract Twitter description | HTML with twitter:description | `{ twitterDescription: "..." }` |
| UT-031 | Extract Twitter image | HTML with twitter:image | `{ twitterImage: "..." }` |
| UT-032 | Extract all headings | HTML with H1-H6 | Array of heading objects |
| UT-033 | Extract images with alt text | HTML with img tags | Array of image objects |
| UT-034 | Extract all links | HTML with anchor tags | Array of link objects |
| UT-035 | Calculate word count | HTML with text content | Total word count |
| UT-036 | Extract meta keywords | HTML with meta keywords | `{ keywords: [...] }` |
| UT-037 | Extract viewport meta | HTML with viewport | `{ viewport: "..." }` |
| UT-038 | Extract robots meta | HTML with robots meta | `{ robots: "..." }` |
| UT-039 | Handle malformed HTML | Invalid HTML structure | Parsed gracefully |
| UT-040 | Handle empty HTML | Empty string | Empty result object |
| UT-041 | Handle HTML without head | HTML missing head section | Body content parsed |
| UT-042 | Strip script tags | HTML with scripts | Scripts removed from output |
| UT-043 | Strip style tags | HTML with styles | Styles removed from text |
| UT-044 | Preserve special characters | HTML with UTF-8 chars | Characters preserved |
| UT-045 | Handle large HTML | 1MB+ HTML document | Parsed within time limit |

### Unit Tests - Rate Limit Service

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| UT-046 | First request from IP | IP not in cache | `{ allowed: true, remaining: 3 }` |
| UT-047 | Second request from IP | IP has 1 prior request | `{ allowed: true, remaining: 2 }` |
| UT-048 | Third request from IP | IP has 2 prior requests | `{ allowed: true, remaining: 1 }` |
| UT-049 | Fourth request (CAPTCHA required) | IP has 3 prior requests | `{ allowed: false, captchaRequired: true }` |
| UT-050 | CAPTCHA passed | Valid CAPTCHA token | `{ allowed: true, remaining: 3 }` |
| UT-051 | Invalid CAPTCHA token | Invalid token | `{ allowed: false, captchaRequired: true }` |
| UT-052 | Rate limit expiry | 24 hours passed | Counter reset to 0 |
| UT-053 | Different IP tracking | Two different IPs | Separate counters |
| UT-054 | IPv6 support | IPv6 address | Tracked correctly |
| UT-055 | IPv4 mapped IPv6 | IPv4-mapped IPv6 | Normalized to IPv4 |
| UT-056 | X-Forwarded-For handling | Proxied request | Real IP extracted |
| UT-057 | Cache key format | IP address | Consistent key format |
| UT-058 | Counter increment | Multiple requests | Accurate counting |
| UT-059 | Counter reset | Reset API call | Counter set to 0 |
| UT-060 | Concurrent requests | Simultaneous requests | Atomic counter updates |

### Unit Tests - URL Validator

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| UT-061 | Valid HTTPS URL | "https://example.com" | `{ valid: true }` |
| UT-062 | Valid HTTP URL | "http://example.com" | `{ valid: true }` |
| UT-063 | URL with path | "https://example.com/page" | `{ valid: true }` |
| UT-064 | URL with query string | "https://example.com?a=1&b=2" | `{ valid: true }` |
| UT-065 | URL with fragment | "https://example.com#section" | `{ valid: true }` |
| UT-066 | URL with subdomain | "https://www.example.com" | `{ valid: true }` |
| UT-067 | URL with port | "https://example.com:443" | `{ valid: true }` |
| UT-068 | International domain | "https://例子.测试" | `{ valid: true }` |
| UT-069 | Punycode domain | "https://xn--fsqu00a.xn--0zwm56d" | `{ valid: true }` |
| UT-070 | Missing protocol | "example.com" | `{ valid: false, error: "..." }` |
| UT-071 | Invalid protocol | "ftp://example.com" | `{ valid: false, error: "..." }` |
| UT-072 | Invalid domain | "https://-invalid.com" | `{ valid: false, error: "..." }` |
| UT-073 | Localhost blocked | "http://localhost" | `{ valid: false, error: "Private IPs not allowed" }` |
| UT-074 | 127.0.0.1 blocked | "http://127.0.0.1" | `{ valid: false, error: "..." }` |
| UT-075 | 10.x.x.x blocked | "http://10.0.0.1" | `{ valid: false, error: "..." }` |
| UT-076 | 172.16.x.x blocked | "http://172.16.0.1" | `{ valid: false, error: "..." }` |
| UT-077 | 192.168.x.x blocked | "http://192.168.1.1" | `{ valid: false, error: "..." }` |
| UT-078 | 169.254.x.x blocked | "http://169.254.1.1" | `{ valid: false, error: "..." }` |
| UT-079 | Empty string | "" | `{ valid: false, error: "URL required" }` |
| UT-080 | Null input | null | `{ valid: false, error: "URL required" }` |

### Integration Tests - Proxy Endpoint

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| IT-001 | Successful fetch and parse | Valid external URL | HTTP 200 with parsed data |
| IT-002 | Fetch with redirects | URL that redirects | Follows redirects, returns final page |
| IT-003 | Fetch with max redirects | URL with many redirects | HTTP 310 if > 5 redirects |
| IT-004 | GZIP response handling | Server returns gzipped | Decompressed correctly |
| IT-005 | Brotli response handling | Server returns Brotli | Decompressed correctly |
| IT-006 | UTF-8 encoding | UTF-8 encoded page | Characters preserved |
| IT-007 | Non-UTF-8 encoding | Latin-1 encoded page | Converted to UTF-8 |
| IT-008 | Large response handling | 5MB+ HTML page | Handled within memory limits |
| IT-009 | Empty response | Server returns empty | HTTP 200 with empty data |
| IT-010 | Binary file rejection | PDF/image URL | HTTP 415, "Invalid content type" |
| IT-011 | JSON response handling | API endpoint returns JSON | HTTP 200, JSON parsed |
| IT-012 | User-Agent header | Any request | Custom User-Agent sent |
| IT-013 | Accept-Language header | Request with language | Language header forwarded |
| IT-014 | Custom timeout | Slow server | Times out after 30 seconds |
| IT-015 | Connection reuse | Multiple requests | Connection pooling works |
| IT-016 | SSL certificate validation | Expired cert | HTTP 502, SSL error |
| IT-017 | Self-signed cert handling | Self-signed cert | HTTP 502, SSL error |
| IT-018 | HTTP/2 support | HTTP/2 server | Works correctly |
| IT-019 | Chunked transfer encoding | Chunked response | Handled correctly |
| IT-020 | Connection keep-alive | Keep-alive response | Connection managed properly |

### Security Tests - Rate Limiting

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| ST-001 | First request allowed | New IP | Request proceeds |
| ST-002 | Second request allowed | IP with 1 request | Request proceeds |
| ST-003 | Third request allowed | IP with 2 requests | Request proceeds |
| ST-004 | Fourth request blocked | IP with 3 requests | HTTP 429, CAPTCHA required |
| ST-005 | CAPTCHA unlocks access | Valid CAPTCHA response | Request proceeds, counter reset |
| ST-006 | Invalid CAPTCHA rejected | Wrong CAPTCHA answer | HTTP 429, CAPTCHA still required |
| ST-007 | Different IPs independent | Multiple IPs | Separate rate limits |
| ST-008 | Same IP, different headers | IP spoofing attempt | Real IP used |
| ST-009 | IPv4 and IPv6 separate | Same host, different IP versions | Separate counters |
| ST-010 | Rate limit headers included | Any request | X-RateLimit-* headers present |
| ST-011 | Rate limit window sliding | Requests over time | Window calculated correctly |
| ST-012 | Counter reset after window | 24 hours passed | Counter back to 0 |
| ST-013 | Concurrent requests handled | 10 simultaneous requests | Correct counting |
| ST-014 | Race condition prevented | Rapid concurrent requests | Atomic counter updates |
| ST-015 | Cache persistence | Server restart | Cache survives (Redis) |

### Security Tests - SSRF Prevention

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| ST-016 | localhost blocked | "http://localhost" | HTTP 403 |
| ST-017 | 127.0.0.1 blocked | "http://127.0.0.1" | HTTP 403 |
| ST-018 | 127.0.0.1 with port | "http://127.0.0.1:8080" | HTTP 403 |
| ST-019 | 0.0.0.0 blocked | "http://0.0.0.0" | HTTP 403 |
| ST-020 | 10.x.x.x blocked | "http://10.0.0.1" | HTTP 403 |
| ST-021 | 172.16-31.x.x blocked | "http://172.16.0.1" | HTTP 403 |
| ST-022 | 192.168.x.x blocked | "http://192.168.1.1" | HTTP 403 |
| ST-023 | 169.254.x.x blocked | "http://169.254.1.1" | HTTP 403 |
| ST-024 | DNS rebinding attempt | Domain resolves to internal IP | IP checked after resolution |
| ST-025 | IPv6 localhost blocked | "http://[::1]" | HTTP 403 |
| ST-026 | IPv6 link-local blocked | "http://[fe80::1]" | HTTP 403 |
| ST-027 | IPv6 unique local blocked | "http://[fc00::1]" | HTTP 403 |
| ST-028 | Decimal IP blocked | "http://2130706433" (127.0.0.1) | HTTP 403 |
| ST-029 | Hex IP blocked | "http://0x7f000001" (127.0.0.1) | HTTP 403 |
| ST-030 | Octal IP blocked | "http://0177.0.0.1" (127.0.0.1) | HTTP 403 |
| ST-031 | Mixed notation blocked | "http://127.0.0.0x1" | HTTP 403 |
| ST-032 | Cloud metadata blocked | "http://169.254.169.254" | HTTP 403 |
| ST-033 | AWS metadata blocked | "http://169.254.169.254/latest/meta-data/" | HTTP 403 |
| ST-034 | GCP metadata blocked | "http://metadata.google.internal" | HTTP 403 |
| ST-035 | Azure metadata blocked | "http://169.254.169.254/metadata/instance" | HTTP 403 |

### Security Tests - Input Validation

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| ST-036 | SQL injection in URL | "https://example.com?id=1' OR '1'='1" | URL accepted, query passed |
| ST-037 | XSS in response | Page with XSS payload | Payload escaped in output |
| ST-038 | CRLF injection in URL | "https://example.com\r\nX-Injected: true" | URL rejected or sanitized |
| ST-039 | Null byte injection | "https://example.com%00.evil.com" | URL rejected or sanitized |
| ST-040 | Unicode normalization | "https://example.com/‑test" | Normalized correctly |
| ST-041 | Long URL attack | URL > 8000 chars | HTTP 414 or 422 |
| ST-042 | Deep recursion HTML | Deeply nested tags | Handled without stack overflow |
| ST-043 | Billion laughs attack | XML entity expansion | Protected, request rejected |
| ST-044 | Zip bomb style HTML | Highly compressible HTML | Size limit enforced |
| ST-045 | Malformed URL encoding | "https://example.com/%ZZ" | URL rejected |

### Performance Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| PT-001 | Response time < 5s | Normal page | Complete in < 5 seconds |
| PT-002 | Timeout at 30s | Slow server | Timeout at 30 seconds |
| PT-003 | Concurrent requests | 10 concurrent requests | All handled successfully |
| PT-004 | High load | 100 requests/minute | Rate limiting kicks in |
| PT-005 | Memory usage | Large HTML | Memory freed after request |
| PT-006 | Connection cleanup | Many sequential requests | Connections closed properly |
| PT-007 | Parser performance | 1MB HTML | Parsed in < 2 seconds |
| PT-008 | Rate limit check speed | Cache lookup | < 10ms |
| PT-009 | CAPTCHA verification speed | Token validation | < 100ms |
| PT-010 | Full request overhead | Simple request | < 500ms overhead |

---

## Mock Data

### Valid Request Payload
```json
{
  "url": "https://example.com"
}
```

### Valid Response (HTML Parsed)
```json
{
  "success": true,
  "data": {
    "url": "https://example.com",
    "finalUrl": "https://example.com",
    "statusCode": 200,
    "contentType": "text/html",
    "title": "Example Domain",
    "description": "This domain is for use in illustrative examples...",
    "canonical": null,
    "ogTitle": null,
    "ogDescription": null,
    "ogImage": null,
    "twitterCard": null,
    "headings": [
      { "tag": "h1", "text": "Example Domain" }
    ],
    "wordCount": 42,
    "images": [],
    "links": [
      { "href": "https://www.iana.org/domains/example", "text": "More information..." }
    ],
    "warnings": [],
    "analyzedAt": "2026-02-26T19:45:00Z"
  }
}
```

### Rate Limit Exceeded Response
```json
{
  "success": false,
  "error": {
    "code": "RATE_LIMIT_EXCEEDED",
    "message": "You have exceeded the rate limit. Please complete the CAPTCHA to continue.",
    "captchaRequired": true,
    "captchaSiteKey": "recaptcha-site-key-here"
  }
}
```

### Validation Error Response
```json
{
  "success": false,
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "The given data was invalid.",
    "details": {
      "url": ["The URL format is invalid."]
    }
  }
}
```

### SSRF Blocked Response
```json
{
  "success": false,
  "error": {
    "code": "FORBIDDEN_URL",
    "message": "Access to this URL is not allowed for security reasons."
  }
}
```

### Fetch Error Response
```json
{
  "success": false,
  "error": {
    "code": "FETCH_ERROR",
    "message": "Unable to fetch the page. The server may be down or the URL may be incorrect.",
    "details": {
      "originalError": "Connection timed out"
    }
  }
}
```

---

## Coverage Targets

| Category | Target | Priority |
|----------|--------|----------|
| APIProxyController | 95% | High |
| Cheerio Parser Service | 95% | High |
| Rate Limit Service | 100% | High |
| URL Validator | 100% | High |
| Integration tests | 90% | High |
| Security tests | 95% | Critical |
| Performance tests | 70% | Medium |

**Overall Target:** 90% code coverage

---

## Test Execution

```bash
# Run all tests
php artisan test

# Run specific test file
php artisan test tests/Unit/Controllers/APIProxyControllerTest.php

# Run with coverage
php artisan test --coverage

# Run only unit tests
php artisan test --testsuite=Unit

# Run only feature tests
php artisan test --testsuite=Feature

# Run security tests specifically
php artisan test tests/Feature/Security/

# Run with parallel execution
php artisan test --parallel

# Run specific test method
php artisan test --filter testValidUrlRequest
```

---

## Dependencies

### Required Packages
- Laravel HTTP Client (Guzzle wrapper)
- `symfony/dom-crawler` or `cheerio` via Node.js bridge
- `predis/predis` for Redis rate limiting
- Laravel's built-in validation

### External Services
- Redis (for rate limit storage)
- Google reCAPTCHA API (or hCaptcha)
- Mock HTTP server for testing (e.g., wiremock, mockserver)

### Test Doubles
- Mock HTTP responses for external URLs
- Mock Redis for rate limiting tests
- Mock CAPTCHA verification service

---

## Notes for Developer

### Controller Structure
```
app/Http/Controllers/
├── APIProxyController.php      # Main controller
└── Concerns/
    └── HandlesRateLimiting.php # Rate limiting trait
```

### Service Structure
```
app/Services/
├── CheerioParserService.php    # HTML parsing (may use Node.js bridge)
├── RateLimitService.php        # Rate limiting logic
├── URLValidationService.php    # URL validation and SSRF protection
└── CaptchaService.php          # CAPTCHA verification
```

### Request/Response Structure
```
app/Http/Requests/
└── ProxyAnalyzeRequest.php     # Form request validation

app/Http/Resources/
└── ProxyAnalysisResource.php   # JSON response formatting
```

### Key Implementation Notes

1. **SSRF Prevention (CRITICAL):**
   - Validate URL BEFORE making any network request
   - Resolve DNS first, then check resolved IP
   - Block all private IP ranges (RFC 1918, link-local, etc.)
   - Block cloud metadata endpoints
   - Block unusual URL notations (decimal, hex, octal IPs)

2. **Rate Limiting:**
   - Use Redis for distributed rate limiting
   - Sliding window algorithm preferred
   - Store: `{prefix}:{ip}:{date}` → counter
   - TTL: 24 hours

3. **CAPTCHA Integration:**
   - Trigger after 3 requests per IP per day
   - Use Google reCAPTCHA v3 (invisible) or hCaptcha
   - Verify server-side with secret key
   - Reset counter on successful CAPTCHA

4. **HTML Parsing with Cheerio:**
   - Option A: Node.js microservice with Cheerio
   - Option B: PHP DOMDocument + DOMXPath
   - Option C: Symfony DomCrawler component
   - Strip scripts and styles before processing
   - Handle encoding properly (convert to UTF-8)

5. **Error Handling:**
   - Never expose internal errors to clients
   - Log detailed errors for debugging
   - Return generic error messages to users
   - Include error codes for frontend handling

6. **Response Headers:**
   ```
   X-RateLimit-Limit: 3
   X-RateLimit-Remaining: 2
   X-RateLimit-Reset: 1708992000
   ```

### Security Checklist

- [ ] SSRF protection implemented
- [ ] Rate limiting active
- [ ] CAPTCHA integration working
- [ ] Input validation complete
- [ ] Output sanitization applied
- [ ] Error messages sanitized
- [ ] Logging configured (no sensitive data)
- [ ] Timeout limits set
- [ ] Memory limits configured
- [ ] HTTPS only in production

### Performance Considerations

1. **Connection Pooling:** Reuse HTTP connections where possible
2. **Response Caching:** Consider caching parsed results for 5-15 minutes
3. **Memory Management:** Free DOM resources after parsing
4. **Timeout Configuration:** 30s total, 10s connection, 20s read
5. **Size Limits:** Reject responses > 10MB

---

## Comments

### [2026-02-26 19:35] Agent: PreTester
Created TEST-16-3 documentation. Test suite covers:
- 80 unit test scenarios
- 20 integration test scenarios
- 35 security test scenarios
- 10 performance test scenarios

**Critical Focus Areas:**
1. **SSRF Prevention** - Must block all internal/private IP access
2. **Rate Limiting** - Accurate counting with CAPTCHA enforcement
3. **Input Validation** - URL parsing and security checks
4. **Error Handling** - No information leakage

**Security Priority:** This is a backend API that fetches external URLs - SSRF protection is critical. All ST-016 through ST-045 tests are mandatory before deployment.

**Performance Requirements:** 
- Normal requests must complete in < 5 seconds
- Rate limit lookups must be < 10ms
- Memory must be properly freed after each request

**Integration Dependencies:**
- Redis required for rate limiting
- CAPTCHA service (reCAPTCHA or hCaptcha) required
- Cheerio/Node.js bridge OR Symfony DomCrawler for HTML parsing

### [2026-02-26 19:37] Agent: PreTester
**Test Priority Order for Developer:**
1. SSRF prevention tests (ST-016 to ST-035) - CRITICAL
2. Rate limiting tests (ST-001 to ST-015) - HIGH
3. URL validation tests (UT-061 to UT-080) - HIGH
4. Parser service tests (UT-021 to UT-045) - HIGH
5. Controller tests (UT-001 to UT-020) - HIGH
6. Integration tests (IT-001 to IT-020) - MEDIUM
7. Performance tests (PT-001 to PT-010) - MEDIUM

All tests must pass before this task can move to `To Test Locally`.
