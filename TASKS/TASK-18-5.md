# TASK-18-5: HtmlFetcherService (with SSRF Protection)

## STATUS
`To Deploy`

## PRIORITY
`High`

## RISK
`High`

## Description
Create service to fetch HTML content from URLs with comprehensive SSRF protection, timeout handling, and caching.

## Acceptance Criteria
- [x] Fetches HTML content from validated URLs
- [x] SSRF protection using URLValidationService (private IPs, localhost, metadata endpoints blocked)
- [x] Timeout handling (30 second max)
- [x] Response size limit (10MB max)
- [x] Caches successful responses (1 hour TTL)
- [x] Handles SSL certificate errors gracefully
- [x] Handles redirects (max 5)
- [x] Returns HtmlFetcherResult with content, headers, timing
- [x] Proper error handling with specific error types
- [x] Content-Type validation (text/html only)
- [x] Unit tests with mocked HTTP responses
- [ ] Integration tests with real domains

## Dependencies
- FEAT-016 (URLValidationService)
- TASK-18-4 (DomainValidatorService)

## Effort Estimate
`Medium` (3-4 hours)

## Technical Notes
- HIGH RISK: SSRF vulnerability potential
- Use existing proven URLValidationService from FEAT-016
- Stream response for large files (detect early)
- Log all fetch attempts for security audit

---

## Comments
### [2026-02-28] SpecSystemAnalyst
Created from SPEC-018 Section 10.2. HIGH RISK task - requires thorough security review.

### [2026-02-28 21:23 GMT+2] PreTester
**Test Documentation:** TESTS/TEST-18-5.md (80+ scenarios)
**Test File:** backend/tests/Unit/Services/HtmlFetcherServiceTest.php (70 test methods)

**Test Coverage:**
| Category | Tests | Description |
|----------|-------|-------------|
| HTML Fetch (HF) | 9 | Success, errors, HTTP status codes, large HTML |
| Content-Type (CT) | 9 | Validation of HTML, JSON, XML, PDF, binary |
| SSRF Protection (SSRF) | 14 | Localhost, private IPs, cloud metadata, DNS rebinding, IPv6, redirect attacks |
| Timeout (TO) | 4 | Default, custom, connection timeout, redirect timeout |
| Response Size (SIZE) | 4 | Small, large, limit check, custom limit |
| Header Extraction (HE) | 4 | Content-Type, Content-Length, Server, case handling |
| Error Handling (EH) | 4 | Invalid URL, connection refused, empty response, rate limiting |
| Redirects (RH) | 6 | 301, 302, 307, 308, redirect chains, too many redirects |
| Caching (CA) | 5 | Cache hit/miss, TTL, invalidation, query params |
| SSL/TLS (SSL) | 2 | Valid/expired certificates |
| Result Structure (RS) | 3 | Response format, timing, helper methods |
| Edge Cases | 5 | Empty URL, null URL, unicode URL, custom options |
| Integration (INT) | 3 | URLValidationService integration |

**Dependencies:**
- URLValidationService from FEAT-016 (must use for SSRF protection)
- DomainValidatorService from TASK-18-4

**Security Critical:**
- All SSRF bypass attempts must be blocked (localhost, private IPs, metadata endpoints)
- Redirects to blocked URLs must be caught
- DNS rebinding prevention required
- Audit logging required for all fetch attempts
- SecurityAuditor review required before deployment

**Implementation Notes:**
- Use HTTP client with configurable timeout (default 30s)
- Stream response for early size detection
- Cache successful responses (1 hour TTL)
- Return structured result with content, headers, timing
- Content-Type validation (text/html only)

**Status:** Tests complete. Ready for Developer implementation. HIGH RISK.

### [2026-02-28 21:40 GMT+2] Developer
**Implementation Complete.**

**Files Created:**
- `backend/app/Services/HtmlFetcherService.php`

**Files Modified:**
- `backend/tests/Unit/Services/HtmlFetcherServiceTest.php` (fixed line 108: invalid `Http::response()->timeout()` → `Http::failedConnection()`)

**Test Results:**
- 70 tests passed
- 137 assertions
- Duration: ~4 seconds

**Key Implementation Details:**
1. Disables Laravel's automatic redirect following to validate each redirect URL for SSRF
2. Tracks cached URLs for efficient `clearAllCache()` implementation
3. Comprehensive error codes: TIMEOUT, SSL_ERROR, CONNECTION_FAILED, BLOCKED_URL, BLOCKED_REDIRECT, BLOCKED_PROTOCOL, RESPONSE_TOO_LARGE, INVALID_CONTENT_TYPE, NOT_FOUND, FORBIDDEN, RATE_LIMITED, HTTP_ERROR
4. Logs security-relevant blocked requests to 'security' channel

**Status:** Ready for LocalTester. All acceptance criteria complete except integration tests with real domains (requires local testing environment).
