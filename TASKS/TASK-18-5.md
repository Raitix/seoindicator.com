# TASK-18-5: HtmlFetcherService (with SSRF Protection)

## STATUS
`Tests Coverage In Progress`

## PRIORITY
`High`

## RISK
`High`

## Description
Create service to fetch HTML content from URLs with comprehensive SSRF protection, timeout handling, and caching.

## Acceptance Criteria
- [ ] Fetches HTML content from validated URLs
- [ ] SSRF protection using URLValidationService (private IPs, localhost, metadata endpoints blocked)
- [ ] Timeout handling (30 second max)
- [ ] Response size limit (10MB max)
- [ ] Caches successful responses (1 hour TTL)
- [ ] Handles SSL certificate errors gracefully
- [ ] Handles redirects (max 5)
- [ ] Returns HtmlFetcherResult with content, headers, timing
- [ ] Proper error handling with specific error types
- [ ] Content-Type validation (text/html only)
- [ ] Unit tests with mocked HTTP responses
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

### [2026-02-28] PreTester
**Test Documentation Created:** TESTS/TEST-18-5.md (80+ test scenarios)
**Test File Created:** backend/tests/Unit/Services/HtmlFetcherServiceTest.php (50+ test methods)

**Test Coverage:**
| Category | Tests | Description |
|----------|-------|-------------|
| HTML Fetch (HF) | 13 | Success, errors, HTTP status codes |
| Content-Type (CT) | 9 | Validation of response types |
| SSRF Protection (SSRF) | 9 | Localhost, private IPs, cloud metadata, protocols |
| Caching (CA) | 4 | Cache behavior, TTL, invalidation |
| Redirects (RH) | 4 | 301, 302, redirect chains |
| SSL/TLS (SSL) | 2 | Certificate handling |
| Result Structure (RS) | 3 | Response format |
| Edge Cases | 5 | Empty URL, unicode, custom options |

**Security Critical:**
- All SSRF bypass attempts must be blocked
- Must use URLValidationService from FEAT-016
- Audit logging required for all fetch attempts
- SecurityAuditor review required before deployment

**Status:** Tests written, ready for Developer implementation. HIGH RISK.
