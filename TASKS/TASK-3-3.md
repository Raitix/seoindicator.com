# SPEC-003-3: Backend - Domain Validation Service

## STATUS
`To Test Locally`

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

### [2026-03-17 18:30] Agent: LocalTester
**Local Testing Complete - 4 BUGS FOUND**

**Test Results:**
- Unit tests: 31 passed, 3 failed (167 assertions)
- Integration tests: 19 passed, 1 failed (49 assertions)

**BUG 1: SSRF Internal Domain Patterns Not Blocked**
- Test: `test_ssrf_internal_domain_patterns`
- Domains like `service.internal.example.com`, `test.internal`, `app.local`, `api.localhost` are NOT being blocked
- Expected: These should return `valid: false` with code `SSRF_BLOCKED`
- Actual: They pass validation as valid domains
- Root Cause: The blocked keywords check in `validateFormat()` is not catching keywords when they appear as subdomain parts
- File: `app/Services/DomainValidator.php` - `validateFormat()` method

**BUG 2: IPv6 Unique Local Addresses Not Blocked**
- Test: `test_ssrf_ipv6_local`
- IPs: `fc00::1`, `fd00::1`, `fc00:1234:5678::1` are NOT being blocked
- Expected: These should return `valid: false` with code `SSRF_BLOCKED`
- Actual: They pass validation
- Root Cause: The `Str::startsWith(strtolower($ip), 'fc')` check may have case-sensitivity or string comparison issue
- File: `app/Services/DomainValidator.php` - `validateIp()` method, IPv6 unique local section

**BUG 3: Hex-Encoded IP Not Blocked**
- Test: `test_ssrf_hex_octal_encoding`
- Input: `0x7f.0x00.0x00.0x01` (hex-encoded 127.0.0.1) is NOT being blocked
- Expected: Should return `valid: false` with code `SSRF_BLOCKED`
- Actual: Passes validation
- Root Cause: The regex `/^0x[0-9a-f]+\./i` should match this pattern but isn't working correctly
- File: `app/Services/DomainValidator.php` - `validateFormat()` method, hex encoding check

**BUG 4: validateFormat() Missing 'domain' Key in Success Result**
- Test: `test_domain_normalization` (integration)
- Issue: `$result['domain']` key is undefined after successful `validateFormat()` call
- Expected: Success result should include normalized domain
- Actual: `Undefined array key "domain"` error
- Root Cause: The success return in `validateFormat()` doesn't include the `domain` key
- File: `app/Services/DomainValidator.php` - `validateFormat()` return statement

**Action Required:**
Fix all 4 bugs in `app/Services/DomainValidator.php`. Then re-run tests:
```bash
php artisan test tests/Unit/Services/DomainValidatorTest.php
php artisan test tests/Feature/Services/DomainValidatorIntegrationTest.php
```

**Handoff:** → Developer for bug fixes

### [2026-03-17 19:05 GMT+2] Agent: Developer
**All Tests Now Passing**

Verified all tests pass after previous fixes:

| Test Suite | Tests | Assertions | Status |
|------------|-------|------------|--------|
| Unit Tests | 34 | 173 | ✅ ALL PASS |
| Integration Tests | 20 | 50 | ✅ ALL PASS |
| **TOTAL** | **54** | **223** | **100%** |

**Previously Reported Bugs - Now Fixed:**
1. ✅ SSRF internal domain patterns - blocked correctly
2. ✅ IPv6 unique local addresses (fc00::/7) - blocked correctly  
3. ✅ Hex-encoded IPs (0x7f.0x00.0x00.0x01) - blocked correctly
4. ✅ validateFormat() returns 'domain' key in success result

**Test Commands:**
```bash
php artisan test tests/Unit/Services/DomainValidatorTest.php
php artisan test tests/Feature/Services/DomainValidatorIntegrationTest.php
```

Status updated: `To Test Locally` - Ready for LocalTester verification

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
