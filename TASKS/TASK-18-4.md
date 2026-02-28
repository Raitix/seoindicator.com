# TASK-18-4: DomainValidatorService

## STATUS
`In Development`

## PRIORITY
`High`

## RISK
`Medium`

## Description
Create service to normalize domain input, validate format, check accessibility, and detect redirects. Must integrate with existing SSRF protection.

## Acceptance Criteria
- [ ] Service normalizes domain (adds https://, handles www)
- [ ] Validates domain format (RFC compliant)
- [ ] Checks domain accessibility (HTTP/HTTPS connection test)
- [ ] Detects and follows redirects (with limit)
- [ ] Returns normalized URL with protocol
- [ ] Integrates with existing URLValidationService from FEAT-016
- [ ] SSRF protection active (blocks private IPs, localhost)
- [ ] Handles edge cases (IDN domains, punycode)
- [ ] Proper error handling with specific error codes
- [ ] Unit tests with mock HTTP responses

## Dependencies
- FEAT-016 (URLValidationService for SSRF protection)

## Effort Estimate
`Medium` (2-3 hours)

## Technical Notes
- Use Laravel HTTP Client for connection tests
- Timeout: 10 seconds for validation
- Follow up to 3 redirects max
- Return ValidationException on failure

---

## Comments
### [2026-02-28] SpecSystemAnalyst
Created from SPEC-018 Section 10.2. Critical for safe domain processing.

### [2026-02-28] PreTester
**Test Documentation Created:** TESTS/TEST-18-4.md (80+ test scenarios)
**Test File Created:** backend/tests/Unit/Services/DomainValidatorServiceTest.php (70+ test methods)

**Test Coverage:**
| Category | Tests | Description |
|----------|-------|-------------|
| Domain Normalization (DN) | 14 | Protocol handling, www, path/query removal, lowercase, ports |
| Format Validation (DV) | 15 | Valid/invalid domains, TLDs, punycode, IPs |
| IDN Handling (IDN) | 6 | Unicode domains (Japanese, Cyrillic, Arabic, Chinese), mixed script |
| SSRF Protection (SSRF) | 16 | Localhost, private IPs, cloud metadata, protocol bypass, IP bypass attempts |
| Error Handling (EH) | 4 | Exception types, error codes |
| Integration (INT) | 2 | URLValidationService integration |
| Edge Cases | 20+ | Spaces, case, URL encoding, hyphens, underscores, control chars |

**Key Integration Points:**
- Uses URLValidationService from FEAT-016 for SSRF protection
- Must mock HTTP client for accessibility tests
- ValidationException with error codes required

**Status:** Tests written, ready for Developer implementation.

### [2026-02-28] PreTester
**Integration Tests Created:** backend/tests/Feature/Services/DomainValidatorServiceIntegrationTest.php (45+ test methods)

**Integration Test Coverage:**
| Category | Tests | Description |
|----------|-------|-------------|
| Accessibility (AC) | 12 | HTTP status codes, redirects, DNS failures |
| Redirect Limits (RL) | 8 | 1-3 redirects, loop detection, cross-domain |
| Timeout (TO) | 3 | Fast/slow responses, custom timeout |
| Return Values (RV) | 5 | Complete structure, error codes |
| Error Handling (EH) | 4 | Rate limit, SSRF, redirect errors |
| Integration (INT) | 3 | URLValidationService integration |
| Full Flow | 4 | End-to-end validation scenarios |

**Total Tests:** 115+ test methods across unit + integration
**Status:** All tests complete, ready for Developer implementation.
