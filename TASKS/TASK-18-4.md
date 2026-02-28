# TASK-18-4: DomainValidatorService

## STATUS
`To Prioritize`

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
