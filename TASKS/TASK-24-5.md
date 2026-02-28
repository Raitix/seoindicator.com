# TASK-24-5: IconValidatorService

## STATUS
`To Develop`

## PRIORITY
`High`

## RISK
`Medium`

## Description
Create service to validate PWA icon URLs for accessibility and correctness. Checks if icons exist and are properly sized.

## Acceptance Criteria
- [ ] IconValidatorService created with methods:
  - validateIcons(array $icons, string $baseUrl): IconValidationResult
  - validateIcon(array $icon, string $baseUrl): IconResult
  - checkIconAccessible(string $url): bool
- [ ] Validates required icon sizes: 192x192, 512x512
- [ ] Validates recommended icon sizes: 180x180 (iOS)
- [ ] Resolves relative icon URLs against base URL
- [ ] Uses HEAD requests for accessibility checks (faster than GET)
- [ ] Concurrent icon validation (up to 5 simultaneous checks)
- [ ] Timeout per icon: 10 seconds
- [ ] IconValidationResult DTO with:
  - total, valid counts
  - icons array with individual results
  - required size presence flags
  - errors array
- [ ] Reuses SSRF protection from TASK-16-1
- [ ] Unit and integration tests

## Dependencies
- TASK-16-1 (Tools infrastructure - URL validation, SSRF protection)
- TASK-24-3 (ManifestFetcherService)

## Effort Estimate
`Medium` (4-6 hours)

## Technical Notes
- Use Laravel HTTP Client with concurrent requests
- Parse sizes string ("192x192" or "any")
- Handle maskable icon purpose
- Log inaccessible icons as warnings

---

## Comments
_Agent communication log_

### [2026-03-01 00:52 GMT+2] Agent: PreTester
**Test Coverage Complete**

Created comprehensive test suite for IconValidatorService:

**Test Documentation:** `tests/SPEC-24-5.md`
**Test Implementation:** `backend/tests/Unit/Services/IconValidatorServiceTest.php`

**Test Categories (70 tests):**
1. **validateIcons() Method (12 tests):** Main entry point for validating icon arrays
   - Success, missing sizes, empty array, null inputs, inaccessible icons, timeout, concurrent validation
   - Relative/absolute URL resolution, mixed sizes, SVG "any" size
2. **validateIcon() Method (8 tests):** Single icon validation
   - Valid icon, invalid size, missing src, maskable icons, query params, protocol-relative, multiple sizes, data URI
3. **checkIconAccessible() Method (10 tests):** HEAD request accessibility checks
   - 200/404/403/500 responses, timeout, redirects, invalid/null URLs, content-type/size warnings
4. **SSRF Protection (8 tests):** Security validation using URLValidationService
   - Localhost, private IP, cloud metadata, decimal IP, hex IP, file protocol, redirect to private, cross-origin CDN
5. **Size Parsing (8 tests):** Parse "WxH" and "any" formats
   - Standard format, "any", multiple sizes, invalid/empty, non-square, case sensitivity, whitespace
6. **URL Resolution (8 tests):** Relative to absolute URL conversion
   - Relative path, subdirectory, absolute URL, protocol-relative, query/fragment, port, data URI
7. **Result DTO (6 tests):** Output structure validation
   - Complete structure, count accuracy, errors array, size flags, warnings, success flag logic
8. **Edge Cases (10 tests):** Malformed input, Unicode, etc.
   - Empty src, long URL, Unicode, special chars, missing sizes/type, icon index, concurrent limits, HEAD fallback

**Key Dependencies:**
- URLValidationService for SSRF protection (TASK-16-1) - Done ✅
- Laravel HTTP Client for concurrent requests (pool method, max 5 at a time)

**Security Focus:** All SSRF tests are High priority. The service MUST reuse URLValidationService for all URL validations to prevent SSRF attacks through icon URLs.

**Implementation Requirements:**
- HEAD requests for faster accessibility checks (fallback to GET if HEAD not supported)
- 10 second timeout per icon
- Maximum 5 concurrent requests
- Required sizes: 192x192, 512x512 (PWA requirements)
- Recommended sizes: 180x180 (iOS)
- Handle "any" size for SVG icons
- Partial success: return success even if some icons fail

Status updated to `To Develop`. Ready for Developer to implement the service.

### [2026-02-28 23:28 GMT+2] Agent: ProjectManager
**Prioritization Review Complete**

- **Priority Confirmed:** `High` (foundation task for PWA scoring, critical for accurate icon validation)
- **Risk Confirmed:** `Medium` (external URL checks with SSRF protection, timeouts, concurrent processing)
- **Dependency Analysis:**
  - TASK-16-1 (Tools infrastructure - URL validation, SSRF protection): **Done** ✅
  - TASK-24-3 (ManifestFetcherService): **To Write Tests** (already prioritized, in pipeline)
- **Execution Order:** After TASK-24-3 completes (needs manifest fetch results)
- **Security Considerations:**
  - Reuses URL validation service from TASK-16-1 for SSRF protection
  - HEAD requests for faster accessibility checks
  - 10-second timeout per icon prevents hanging
  - Concurrent validation limited to 5 simultaneous checks
- **Queue Position:** Ready for PreTester after TASK-24-3 dependency resolves

Status updated to `To Write Tests`. Ready for PreTester to create test cases for icon validation scenarios.

### [2026-02-28] Agent: SpecSystemAnalyst
Created task for icon validation. Medium risk due to external URL checks - mitigated by timeouts, concurrent processing, and SSRF protection. Critical for accurate PWA scoring.
