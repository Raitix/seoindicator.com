# SPEC-003-3: Backend - Domain Validation Service

## STATUS
`In Development`

## PRIORITY
`Medium`

## RISK
`Low`

## Description
Create a service to validate competitor domains before adding them. Ensures domain is accessible and properly formatted, with SSRF protection.

## Acceptance Criteria
- [ ] DomainValidator service validates domain format (regex for valid domain)
- [ ] Service checks DNS record exists (A or CNAME)
- [ ] Service verifies domain is accessible (HTTP/HTTPS request with timeout)
- [ ] SSRF protection: block internal IPs (127.0.0.0/8, 10.0.0.0/8, 172.16.0.0/12, 192.168.0.0/16)
- [ ] SSRF protection: block localhost, local, internal domains
- [ ] Rate limiting on validation requests (max 10 per minute per user)
- [ ] Returns validation result with reason if invalid
- [ ] Unit tests for all validation rules

## Dependencies
- None (standalone service)

## Effort Estimate
`Small` (2-3 hours)

## Technical Notes
- Use PHP's filter_var for IP validation
- Use checkdnsrr for DNS checks
- Use cURL with timeout for accessibility check
- Can be developed in parallel with SPEC-003-1

---

## Comments
_Agent communication log_

### [2026-02-20 00:48] Agent: PreTester
**Test Design Complete**

**Tests Created:**
1. `TESTS/SPEC-003-3.md` - Test documentation (31+ test cases documented)
2. `backend/tests/Unit/Services/DomainValidatorTest.php` - Unit tests (40 tests)
   - Valid/invalid domain format tests
   - DNS record check tests (mocked)
   - Accessibility tests (mocked HTTP)
   - SSRF protection tests:
     - IPv4 loopback, private ranges (10/8, 172.16/12, 192.168/16)
     - Link-local (169.254/16)
     - IPv6 loopback and local addresses
     - Localhost keyword and internal patterns
     - Decimal/hex/octal IP encoding bypasses
     - IPv6 mapped IPv4 addresses
     - DNS rebinding attack mitigation
   - Rate limiting tests (per user, reset)
   - Validation result structure tests
3. `backend/tests/Feature/Services/DomainValidatorIntegrationTest.php` - Integration tests (18 tests)
   - Full validation flow
   - Network timeout/failure handling
   - Rate limiting integration
   - Edge cases (unicode, trailing dot, port, etc.)

**Security Test Coverage:**
- SSRF via all private IP ranges
- SSRF via IPv6 addresses
- SSRF via encoded IPs (decimal, hex, octal, URL)
- SSRF via DNS rebinding
- SSRF via redirects
- SSRF via IPv6 mapped IPv4

**Developer Requirements:**
Create the following files:
- `backend/app/Services/DomainValidator.php`
- `backend/app/Exceptions/DomainValidationException.php`

**Required Service Methods:**
- `validate(string $domain): array`
- `validateFormat(?string $domain): array`
- `validateDns(string $domain): array`
- `validateIp(string $ip): array`
- `checkAccessibility(string $domain): array`
- `validateWithRateLimit(string $domain, string $userId): array`

**Test Execution:**
```bash
cd backend
php artisan test --filter=DomainValidatorTest
php artisan test --filter=DomainValidatorIntegrationTest
```

**Handoff:** → Developer for implementation

### [2026-02-20 00:42] Agent: ProjectManager
**Prioritization Complete**
- Priority: Medium ✓ (supporting service, not on critical path)
- Risk: Low ✓ (standalone service, easily testable)
- **NO DEPENDENCY** - Can be developed in parallel with SPEC-003-1
- **Handoff:** → PreTester (can start immediately)

### [2026-02-19 23:24] Agent: SpecSystemAnalyst
Created task for domain validation service. Security-critical component to prevent SSRF attacks. Can be developed in parallel with database migration.
