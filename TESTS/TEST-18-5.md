# TEST-18-5: HtmlFetcherService (with SSRF Protection)

## Task Reference
- **Task:** TASK-18-5
- **Feature:** FEAT-018 (Legacy 17-Indicator SEO Analysis Core Engine)
- **Status:** Tests Coverage In Progress

---

## Overview
Tests for the `HtmlFetcherService` that fetches HTML content from URLs with comprehensive SSRF protection, timeout handling, response size limits, and caching. This is a HIGH RISK task due to SSRF vulnerability potential.

---

## Test Files

| File | Type | Tests | Assertions |
|------|------|-------|------------|
| `backend/tests/Unit/Services/HtmlFetcherServiceTest.php` | Unit | 50+ | 130+ |
| `backend/tests/Feature/Services/HtmlFetcherServiceIntegrationTest.php` | Integration | 15+ | 40+ |

---

## Test Scenarios

### HTML Fetch Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| HF-01 | Successful fetch | Valid HTML URL | HtmlFetcherResult with content, headers, timing |
| HF-02 | Fetch with redirects | URL with 1-5 redirects | Follow redirects, return final content |
| HF-03 | Fetch with too many redirects | URL with 6+ redirects | Error `REDIRECT_LIMIT_EXCEEDED` |
| HF-04 | Fetch with redirect loop | Circular redirect chain | Error `REDIRECT_LOOP` |
| HF-05 | Fetch large HTML | 5MB HTML response | Success (under limit) |
| HF-06 | Fetch oversized HTML | 15MB HTML response | Error `RESPONSE_TOO_LARGE` |
| HF-07 | Fetch with timeout | Response > 30 seconds | Error `TIMEOUT` |
| HF-08 | Fetch with connection error | Unreachable URL | Error `CONNECTION_FAILED` |
| HF-09 | Fetch with DNS error | Non-existent domain | Error `DNS_FAILURE` |
| HF-10 | Fetch with SSL error | Expired/self-signed certificate | Error `SSL_ERROR` |
| HF-11 | Fetch with HTTP error | 500 Internal Server Error | Error with status code |
| HF-12 | Fetch with HTTP 404 | Not Found | Error `NOT_FOUND` |
| HF-13 | Fetch with HTTP 403 | Forbidden | Error `FORBIDDEN` |

### Content-Type Validation Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CT-01 | HTML content-type | `text/html` | Success |
| CT-02 | HTML with charset | `text/html; charset=utf-8` | Success |
| CT-03 | XHTML content-type | `application/xhtml+xml` | Success (treat as HTML) |
| CT-04 | JSON rejected | `application/json` | Error `INVALID_CONTENT_TYPE` |
| CT-05 | XML rejected | `application/xml` | Error `INVALID_CONTENT_TYPE` |
| CT-06 | PDF rejected | `application/pdf` | Error `INVALID_CONTENT_TYPE` |
| CT-07 | Image rejected | `image/jpeg` | Error `INVALID_CONTENT_TYPE` |
| CT-08 | Binary rejected | `application/octet-stream` | Error `INVALID_CONTENT_TYPE` |
| CT-09 | No content-type header | Missing header | Accept with warning |
| CT-10 | Multipart rejected | `multipart/form-data` | Error `INVALID_CONTENT_TYPE` |

### SSRF Protection Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| SSRF-01 | Localhost blocked | `http://localhost/page.html` | Error `BLOCKED_URL` |
| SSRF-02 | 127.0.0.1 blocked | `http://127.0.0.1/page.html` | Error `BLOCKED_URL` |
| SSRF-03 | 10.x.x.x blocked | `http://10.0.0.1/page.html` | Error `BLOCKED_URL` |
| SSRF-04 | 172.16-31.x.x blocked | `http://172.16.0.1/page.html` | Error `BLOCKED_URL` |
| SSRF-05 | 192.168.x.x blocked | `http://192.168.1.1/page.html` | Error `BLOCKED_URL` |
| SSRF-06 | Cloud metadata blocked | `http://169.254.169.254/latest/meta-data` | Error `BLOCKED_URL` |
| SSRF-07 | file:// protocol blocked | `file:///etc/passwd` | Error `BLOCKED_PROTOCOL` |
| SSRF-08 | ftp:// protocol blocked | `ftp://example.com/file` | Error `BLOCKED_PROTOCOL` |
| SSRF-09 | gopher:// protocol blocked | `gopher://example.com` | Error `BLOCKED_PROTOCOL` |
| SSRF-10 | DNS rebinding blocked | Domain resolving to private IP | Error `BLOCKED_URL` |
| SSRF-11 | IPv6 localhost blocked | `http://[::1]/page.html` | Error `BLOCKED_URL` |
| SSRF-12 | Redirect to blocked URL | External → localhost | Error `BLOCKED_REDIRECT` |
| SSRF-13 | Redirect to private IP | External → 10.0.0.1 | Error `BLOCKED_REDIRECT` |

### Caching Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CA-01 | First fetch not cached | New URL | Cache miss, fetch from network |
| CA-02 | Second fetch cached | Same URL within 1 hour | Cache hit, no network call |
| CA-03 | Cache key includes URL | Different URLs | Different cache keys |
| CA-04 | Cache respects TTL | After 1 hour | Cache expired, re-fetch |
| CA-05 | Cache invalidated on error | Fetch fails | Not cached |
| CA-06 | Cache invalidation API | Manual cache clear | Cache cleared |
| CA-07 | Cache with query params | URL with query string | Cached separately |
| CA-08 | Cache with different headers | Same URL, different Accept header | Different cache entries |

### Timeout Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| TO-01 | Default timeout | No timeout specified | 30 second timeout |
| TO-02 | Custom timeout | timeout: 5 seconds | 5 second timeout |
| TO-03 | Connection timeout | Slow connection establishment | Connection timeout |
| TO-04 | Read timeout | Slow data transfer | Read timeout |
| TO-05 | Timeout during redirect | Redirect to slow server | Timeout on redirect |

### Response Size Limit Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| RS-01 | Small response | 100KB | Success |
| RS-02 | Medium response | 5MB | Success |
| RS-03 | Large response at limit | 10MB | Success (at limit) |
| RS-04 | Response exceeds limit | 10.1MB | Error `RESPONSE_TOO_LARGE` |
| RS-05 | Content-Length check | Header says 15MB | Error `RESPONSE_TOO_LARGE` (early) |
| RS-06 | Stream early termination | Streaming, exceeds 10MB | Stop streaming, error |
| RS-07 | No Content-Length header | Transfer-Encoding: chunked | Check during streaming |
| RS-08 | Custom size limit | limit: 5MB, response: 6MB | Error `RESPONSE_TOO_LARGE` |

### Redirect Handling Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| RH-01 | No redirect | 200 OK | Return content |
| RH-02 | 301 permanent redirect | 301 → new URL | Follow to new URL |
| RH-03 | 302 temporary redirect | 302 → new URL | Follow to new URL |
| RH-04 | 307 temporary redirect | 307 → new URL | Follow to new URL |
| RH-05 | 308 permanent redirect | 308 → new URL | Follow to new URL |
| RH-06 | Meta refresh redirect | HTML with `<meta http-equiv="refresh">` | Not followed (server-side only) |
| RH-07 | JavaScript redirect | JS window.location | Not followed (server-side only) |
| RH-08 | Cross-protocol redirect | HTTP → HTTPS | Follow correctly |
| RH-09 | Cross-domain redirect | example.com → other.com | Follow, update final URL |
| RH-10 | Redirect with query params | URL with params → new URL | Preserve/handle params |

### SSL/TLS Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| SSL-01 | Valid SSL certificate | HTTPS with valid cert | Success |
| SSL-02 | Expired SSL certificate | HTTPS with expired cert | Error `SSL_ERROR` |
| SSL-03 | Self-signed certificate | HTTPS with self-signed cert | Error `SSL_ERROR` |
| SSL-04 | Wrong hostname in cert | Certificate mismatch | Error `SSL_ERROR` |
| SSL-05 | Untrusted root CA | Unknown CA | Error `SSL_ERROR` |
| SSL-06 | TLS 1.2 required | Server requires TLS 1.2+ | Success with TLS 1.2 |
| SSL-07 | TLS 1.0 rejected | Server only supports TLS 1.0 | May fail (security) |
| SSL-08 | Mixed content warning | HTTPS page with HTTP resources | Warning logged |

### Header Extraction Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| HE-01 | Extract Content-Type | Response with Content-Type | Included in result |
| HE-02 | Extract Content-Length | Response with Content-Length | Included in result |
| HE-03 | Extract Content-Encoding | Gzip response | Included in result |
| HE-04 | Extract Last-Modified | Response with date | Included in result |
| HE-05 | Extract ETag | Response with ETag | Included in result |
| HE-06 | Extract Server header | Server identification | Included in result |
| HE-07 | Extract X-Request-Id | Custom header | Included in result |
| HE-08 | Handle multiple Set-Cookie | Multiple cookies | All captured |
| HE-09 | Case-insensitive headers | content-type vs Content-Type | Normalized |

### Error Handling Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| EH-01 | Invalid URL format | `not a url` | Error `INVALID_URL` |
| EH-02 | Connection refused | Port closed | Error `CONNECTION_FAILED` |
| EH-03 | Connection reset | Server closes connection | Error `CONNECTION_RESET` |
| EH-04 | Malformed response | Invalid HTTP response | Error `INVALID_RESPONSE` |
| EH-05 | Empty response | 200 OK with no body | Success (empty content) |
| EH-06 | Truncated response | Incomplete transfer | Error `INCOMPLETE_RESPONSE` |
| EH-07 | Rate limited by target | 429 Too Many Requests | Error `RATE_LIMITED` |

### Result Structure Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| RS-01 | Success result structure | Valid fetch | `{ success: true, content: string, url: string, finalUrl: string, statusCode: int, headers: array, timing: array, cached: bool }` |
| RS-02 | Error result structure | Failed fetch | `{ success: false, error: string, errorCode: string, url: string }` |
| RS-03 | Timing data | Successful fetch | `{ total: ms, dns: ms, connect: ms, tls: ms, transfer: ms }` |
| RS-04 | Redirect chain | Multiple redirects | `redirectChain: [{ url, statusCode }]` |

### Integration Tests (with URLValidationService)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| INT-01 | Uses URLValidationService | Any URL | Calls validation service |
| INT-02 | Validation errors propagate | Blocked URL | Same error from URLValidationService |
| INT-03 | Valid public URL passes | `https://example.com` | Passes all checks |

---

## Mock Data

### Valid HTML Response
```php
$htmlResponse = '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Test Page</title>
</head>
<body>
    <h1>Test Content</h1>
</body>
</html>';
```

### Large HTML Response
```php
$largeHtml = str_repeat('<p>Paragraph content here.</p>', 500000); // ~15MB
```

### Response Headers
```php
$headers = [
    'Content-Type' => 'text/html; charset=utf-8',
    'Content-Length' => '12345',
    'Server' => 'nginx/1.18.0',
    'Last-Modified' => 'Wed, 28 Feb 2026 12:00:00 GMT',
];
```

### SSRF Blocked URLs
```php
$blockedUrls = [
    'http://localhost/page.html',
    'http://127.0.0.1/admin',
    'http://10.0.0.1/internal',
    'http://172.16.0.1/private',
    'http://192.168.1.1/lan',
    'http://169.254.169.254/latest/meta-data',
    'file:///etc/passwd',
    'ftp://example.com/file',
    'gopher://example.com',
];
```

---

## Service Interface

```php
interface HtmlFetcherServiceInterface
{
    /**
     * Fetch HTML content from a URL.
     *
     * @param string $url URL to fetch
     * @param array $options Optional settings
     *   - timeout: int (seconds, default 30)
     *   - maxSize: int (bytes, default 10MB)
     *   - followRedirects: bool (default true)
     *   - maxRedirects: int (default 5)
     *   - useCache: bool (default true)
     *   - cacheTtl: int (seconds, default 3600)
     *   - verifySsl: bool (default true)
     * @return HtmlFetcherResult
     * @throws HtmlFetchException On fetch failure
     */
    public function fetch(string $url, array $options = []): HtmlFetcherResult;
    
    /**
     * Check if URL is fetchable (passes SSRF checks).
     *
     * @param string $url
     * @return bool
     */
    public function isFetchable(string $url): bool;
    
    /**
     * Clear cache for a specific URL.
     *
     * @param string $url
     */
    public function clearCache(string $url): void;
    
    /**
     * Clear all cached responses.
     */
    public function clearAllCache(): void;
}
```

---

## Result DTO

```php
class HtmlFetcherResult
{
    public bool $success;
    public ?string $content;
    public string $url;
    public string $finalUrl;
    public int $statusCode;
    public array $headers;
    public array $timing;
    public bool $cached;
    public array $redirectChain;
    public ?string $error;
    public ?string $errorCode;
}
```

---

## Coverage Targets

| Category | Target | Description |
|----------|--------|-------------|
| Basic Fetch | 100% | Success and failure cases |
| SSRF Protection | 100% | All bypass attempts |
| Content-Type | 100% | All validation cases |
| Caching | 95% | Cache behavior |
| Timeouts | 100% | All timeout scenarios |
| Size Limits | 100% | All limit scenarios |
| Redirects | 100% | All redirect types |
| SSL/TLS | 90% | Certificate handling |
| Error Handling | 100% | All error codes |

---

## Test Execution

```bash
# Run unit tests
php artisan test --filter=HtmlFetcherServiceTest

# Run integration tests
php artisan test --filter=HtmlFetcherServiceIntegrationTest

# Run with coverage
php artisan test --filter=HtmlFetcherService --coverage

# Run all FEAT-018 tests
php artisan test --filter=TEST-18
```

---

## Dependencies
- `URLValidationService` (FEAT-016) for SSRF protection
- Laravel HTTP Client or Guzzle
- Laravel Cache for response caching
- `HtmlFetchException` for error handling

---

## Security Considerations

### CRITICAL: SSRF Prevention
This service handles external HTTP requests and must prevent:
1. Access to internal resources (localhost, private IPs)
2. Access to cloud metadata endpoints
3. Protocol smuggling (file://, gopher://, etc.)
4. DNS rebinding attacks
5. Redirect-based SSRF

### Audit Logging
All fetch attempts must be logged with:
- Timestamp
- Source IP (if applicable)
- Requested URL
- Result (success/failure)
- Error code (if failed)

---

## Notes for Developer
- Default timeout: 30 seconds
- Maximum response size: 10MB
- Maximum redirects: 5
- Cache TTL: 1 hour (3600 seconds)
- Use streaming for large responses
- Log all fetch attempts for security audit
- Consider rate limiting per-user

---

## Comments

### [2026-02-28] PreTester
- Test documentation created
- 80+ test scenarios covering fetch, SSRF, caching, timeouts, size limits, redirects
- HIGH RISK: Security audit required before deployment
- Must use URLValidationService from FEAT-016
- Ready for test implementation
