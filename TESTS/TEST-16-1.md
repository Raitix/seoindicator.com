# TEST-16-1: Tools Infrastructure Tests

## STATUS
`Tests Coverage In Progress`

## Related
- Task: [TASK-16-1](../TASKS/TASK-16-1.md)
- Feature: [FEAT-016](../FEATURES/FEAT-016.md)

---

## Test Coverage Summary

| Category | Tests | Assertions | Status |
|----------|-------|------------|--------|
| URL Validation | 15 | 15 | ✅ |
| URL Sanitization | 10 | 10 | ✅ |
| URL Blocklist | 12 | 12 | ✅ |
| CORS Proxy Logic | 8 | 8 | ✅ |
| SEOTool Base Class | 14 | 14 | ✅ |
| Error Handling | 8 | 8 | ✅ |
| **Total** | **67** | **67** | ✅ |

---

## Test Scenarios

### URL Validation (URL-VAL)

| ID | Scenario | Input | Expected |
|----|----------|-------|----------|
| URL-VAL-001 | Valid HTTP URL | `http://example.com` | `true` |
| URL-VAL-002 | Valid HTTPS URL | `https://example.com` | `true` |
| URL-VAL-003 | Valid URL with path | `https://example.com/page` | `true` |
| URL-VAL-004 | Valid URL with query | `https://example.com?page=1` | `true` |
| URL-VAL-005 | Invalid URL - no protocol | `example.com` | `false` |
| URL-VAL-006 | Invalid URL - malformed | `htp://example.com` | `false` |
| URL-VAL-007 | Empty string | `` | `false` |
| URL-VAL-008 | Null value | `null` | `false` |
| URL-VAL-009 | IP address URL | `http://192.168.1.1` | `true` |
| URL-VAL-010 | URL with port | `https://example.com:8080` | `true` |
| URL-VAL-011 | URL with fragment | `https://example.com#section` | `true` |
| URL-VAL-012 | Very long URL | 2084 char URL | `false` |
| URL-VAL-013 | URL with unicode domain | `https://例え.jp` | `true` |
| URL-VAL-014 | URL with special chars | `https://example.com/path?query=test&foo=bar` | `true` |
| URL-VAL-015 | Data URI | `data:text/html,<h1>Test</h1>` | `false` |

### URL Sanitization (URL-SAN)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| URL-SAN-001 | Trim whitespace | `  https://example.com  ` | `https://example.com` |
| URL-SAN-002 | Lowercase protocol | `HTTPS://example.com` | `https://example.com` |
| URL-SAN-003 | Remove tracking params | `https://example.com?utm_source=x` | `https://example.com` |
| URL-SAN-004 | Preserve hash fragment | `https://example.com#section` | `https://example.com#section` |
| URL-SAN-005 | Normalize www prefix | `https://www.example.com` | `https://example.com` (configurable) |
| URL-SAN-006 | Handle double slashes | `https://example.com//path` | `https://example.com/path` |
| URL-SAN-007 | URL decode encoded chars | `https://example.com/%70ath` | `https://example.com/path` |
| URL-SAN-008 | Remove null bytes | `https://example.com%00path` | `https://example.com/path` |
| URL-SAN-009 | Handle unicode domains | `https://例え.jp` | `https://xn--r8jz45g.jp` |
| URL-SAN-010 | Preserve auth info | `https://user:pass@example.com` | `https://user:pass@example.com` |

### URL Blocklist (URL-BLK)

| ID | Scenario | URL | Blocked |
|----|----------|-----|---------|
| URL-BLK-001 | Localhost | `http://localhost` | `true` |
| URL-BLK-002 | Localhost with port | `http://localhost:3000` | `true` |
| URL-BLK-003 | 127.0.0.1 | `http://127.0.0.1` | `true` |
| URL-BLK-004 | 127.x.x.x range | `http://127.0.0.5` | `true` |
| URL-BLK-005 | 10.x.x.x private | `http://10.0.0.1` | `true` |
| URL-BLK-006 | 172.16.x.x private | `http://172.16.0.1` | `true` |
| URL-BLK-007 | 192.168.x.x private | `http://192.168.1.1` | `true` |
| URL-BLK-008 | file:// protocol | `file:///etc/passwd` | `true` |
| URL-BLK-009 | ftp:// protocol | `ftp://example.com` | `true` |
| URL-BLK-010 | Internal hostname | `http://internal.example.com` | `true` |
| URL-BLK-011 | AWS metadata | `http://169.254.169.254` | `true` |
| URL-BLK-012 | Valid external URL | `https://google.com` | `false` |

### CORS Proxy Logic (CORS)

| ID | Scenario | Description | Expected |
|----|----------|-------------|----------|
| CORS-001 | Successful fetch | Fetch external URL | HTML content returned |
| CORS-002 | Timeout handling | URL takes > 5s to respond | TimeoutException |
| CORS-003 | 404 response | URL returns 404 | FetchException with code |
| CORS-004 | 403 response | URL returns 403 | FetchException with code |
| CORS-005 | Redirect handling | URL redirects | Follow up to 5 redirects |
| CORS-006 | Content-Type validation | Non-HTML response | ParseException |
| CORS-007 | Size limit | Response > 5MB | SizeLimitException |
| CORS-008 | User-Agent header | Set custom UA | Header included in request |

### SEOTool Base Class (TOOL)

| ID | Scenario | Method | Expected |
|----|----------|--------|----------|
| TOOL-001 | Get tool name | `getName()` | Returns string |
| TOOL-002 | Abstract analyze | `analyze()` | Throws if not implemented |
| TOOL-003 | Fetch URL | `fetchUrl()` | Returns HTML string |
| TOOL-004 | Parse HTML | `parseHtml()` | Returns CheerioAPI |
| TOOL-005 | Validate URL | `validateUrl()` | Returns bool |
| TOOL-006 | Get tool result | `getResult()` | Returns ToolResult DTO |
| TOOL-007 | Handle fetch error | `fetchUrl(invalid)` | Returns null with error |
| TOOL-008 | Parse malformed HTML | `parseHtml(bad)` | Returns CheerioAPI |
| TOOL-009 | Timeout configuration | Constructor | Default 5s timeout |
| TOOL-010 | Custom timeout | Constructor | Respects custom timeout |
| TOOL-011 | Result timestamp | `getResult()` | Includes timestamp |
| TOOL-012 | Result URL | `getResult()` | Includes analyzed URL |
| TOOL-013 | Error in result | `getResult()` | Includes error if failed |
| TOOL-014 | Success flag | `getResult()` | Includes success boolean |

### Error Handling (ERR)

| ID | Scenario | Condition | Expected |
|----|----------|-----------|----------|
| ERR-001 | Invalid URL error | Validation fails | INVALID_URL code |
| ERR-002 | URL blocked error | Blocklist match | URL_BLOCKED code |
| ERR-003 | Fetch failed error | HTTP error | FETCH_FAILED code |
| ERR-004 | Timeout error | Request timeout | TIMEOUT code |
| ERR-005 | Parse error | HTML parse fail | PARSE_ERROR code |
| ERR-006 | Rate limited error | Too many requests | RATE_LIMITED code |
| ERR-007 | Error message | Any error | User-friendly message |
| ERR-008 | Error details | Any error | Technical details for logging |

---

## Test Files

| File | Location | Tests |
|------|----------|-------|
| UrlValidatorTest.php | `backend/tests/Unit/Services/Tools/` | 37 |
| UrlBlocklistTest.php | `backend/tests/Unit/Services/Tools/` | 12 |
| CorsProxyServiceTest.php | `backend/tests/Unit/Services/Tools/` | 8 |
| SEOToolTest.php | `backend/tests/Unit/Services/Tools/` | 14 |

---

## Security Considerations

- All URL inputs validated and sanitized
- No SQL injection possible (no DB queries)
- SSRF protection via blocklist
- No XSS vectors in URL handling
- Rate limiting prevents abuse

---

## Completion Date
2026-02-27
