# TASK-24-5: IconValidatorService

## STATUS
`To Write Tests`

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
