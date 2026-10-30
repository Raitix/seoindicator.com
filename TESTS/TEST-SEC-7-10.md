# TEST-SEC-7-10: SERP Features API Security Tests

## Overview
Comprehensive security test coverage for the SERP Features API authentication and IDOR vulnerability fixes.

## Test Classification
- **Type:** Security Tests (Feature Tests)
- **Priority:** Critical
- **Risk Coverage:** HIGH (Authentication + Authorization)

## Test File Location
`tests/Feature/Security/SerpFeatureSecurityTest.php`

---

## Security Test Matrix

### Authentication Tests (401 Unauthorized)

| Test ID | Scenario | Endpoint | Expected Status |
|---------|----------|----------|-----------------|
| AUTH-001 | Unauthenticated keyword features | `GET /api/v1/keywords/{id}/serp-features` | 401 |
| AUTH-002 | Unauthenticated site features | `GET /api/v1/sites/{siteId}/serp-features` | 401 |
| AUTH-003 | Unauthenticated summary | `GET /api/v1/sites/{siteId}/serp-features/summary` | 401 |

### IDOR Protection Tests (403 Forbidden)

| Test ID | Scenario | Endpoint | Expected Status |
|---------|----------|----------|-----------------|
| IDOR-001 | User accessing another's keyword features | `GET /api/v1/keywords/{id}/serp-features` | 403 |
| IDOR-002 | User accessing another's site features | `GET /api/v1/sites/{siteId}/serp-features` | 403 |
| IDOR-003 | User accessing another's summary | `GET /api/v1/sites/{siteId}/serp-features/summary` | 403 |

### Authorization Success Tests (200 OK)

| Test ID | Scenario | Endpoint | Expected Status |
|---------|----------|----------|-----------------|
| AUTHZ-001 | User accessing own keyword features | `GET /api/v1/keywords/{id}/serp-features` | 200 |
| AUTHZ-002 | User accessing own site features | `GET /api/v1/sites/{siteId}/serp-features` | 200 |
| AUTHZ-003 | User accessing own summary | `GET /api/v1/sites/{siteId}/serp-features/summary` | 200 |

### Not Found Tests (404 Not Found)

| Test ID | Scenario | Expected Status |
|---------|----------|-----------------|
| NF-001 | Non-existent keyword | 404 |
| NF-002 | Non-existent site | 404 |
| NF-003 | Non-existent site for summary | 404 |

### Input Validation Tests (404 - Route Not Matched)

| Test ID | Scenario | Input | Expected Status |
|---------|----------|-------|-----------------|
| INJ-001 | SQL injection attempt | `1' OR '1'='1` | 404 (regex blocks) |
| INJ-002 | Path traversal attempt | `../etc/passwd` | 404 (regex blocks) |
| INJ-003 | XSS attempt in ID | `<script>alert(1)</script>` | 404 (regex blocks) |
| INJ-004 | Negative ID | `-1` | 404 (regex blocks) |
| INJ-005 | Float ID | `1.5` | 404 (regex blocks) |
| INJ-006 | Empty ID | (empty) | 404 (route not matched) |

### Edge Cases

| Test ID | Scenario | Expected Status |
|---------|----------|-----------------|
| EDGE-001 | Keyword with deleted site | 403 (no ownership) |
| EDGE-002 | Site with no SERP features | 200 (empty data) |
| EDGE-003 | Multiple users, same keyword ID | 403 for non-owner |
| EDGE-004 | Large siteId value | 404 if not found |
| EDGE-005 | Zero as ID | 404 (route regex `[0-9]+` requires at least one digit) |

---

## Test Implementation Details

### Test Class Structure

```php
<?php

namespace Tests\Feature\Security;

use Tests\TestCase;
use App\Models\User;
use App\Models\Site;
use App\Models\Keyword;
use App\Models\SerpFeature;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SerpFeatureSecurityTest extends TestCase
{
    use RefreshDatabase;

    // Helper methods for test setup
    private function createUserWithSite(): array { ... }
    private function createKeywordForSite(Site $site): Keyword { ... }
    
    // Authentication tests (AUTH-*)
    // IDOR protection tests (IDOR-*)
    // Authorization success tests (AUTHZ-*)
    // Not found tests (NF-*)
    // Input validation tests (INJ-*)
    // Edge cases (EDGE-*)
}
```

### Factory Usage

```php
// Create user with site
$user = User::factory()->create();
$site = Site::factory()->create(['user_id' => $user->id]);

// Create keyword for site
$keyword = Keyword::factory()->create(['site_id' => $site->id]);

// Create another user (for IDOR tests)
$otherUser = User::factory()->create();
$otherSite = Site::factory()->create(['user_id' => $otherUser->id]);
```

### Request Patterns

```php
// Unauthenticated request
$response = $this->getJson('/api/v1/sites/1/serp-features');

// Authenticated request (own resource)
$response = $this->actingAs($user)
    ->getJson("/api/v1/sites/{$site->id}/serp-features");

// IDOR test (other user's resource)
$response = $this->actingAs($attacker)
    ->getJson("/api/v1/sites/{$ownerSite->id}/serp-features");

// Injection test
$response = $this->actingAs($user)
    ->getJson("/api/v1/sites/1' OR '1'='1/serp-features");
```

### Assertion Patterns

```php
// Status assertions
$response->assertStatus(401);  // Unauthenticated
$response->assertStatus(403);  // Forbidden (IDOR blocked)
$response->assertStatus(404);  // Not found / route not matched
$response->assertStatus(200);  // Success

// JSON structure assertions
$response->assertJsonStructure(['message']);
$response->assertJson(['message' => 'Unauthenticated.']);
$response->assertJson(['message' => 'Unauthorized access to this site.']);
```

---

## Test Scenarios by Task

### TASK-SEC-7-10-1: Route Authentication Middleware

**Tests verify:**
- Routes are inside `auth` middleware group
- Unauthenticated requests return 401
- Route parameters have regex validation

**Test Methods:**
1. `test_unauthenticated_keyword_features_returns_401()`
2. `test_unauthenticated_site_features_returns_401()`
3. `test_unauthenticated_summary_returns_401()`
4. `test_route_rejects_non_numeric_keyword_id()`
5. `test_route_rejects_non_numeric_site_id()`

### TASK-SEC-7-10-2: keywordFeatures IDOR Protection

**Tests verify:**
- User can access their own keyword's features
- User cannot access another user's keyword features
- Non-existent keyword returns 404

**Test Methods:**
1. `test_user_can_access_own_keyword_features()`
2. `test_user_cannot_access_other_users_keyword_features()`
3. `test_non_existent_keyword_returns_404()`

### TASK-SEC-7-10-3: siteFeatures IDOR Protection

**Tests verify:**
- User can access their own site's features
- User cannot access another user's site features
- Non-existent site returns 404

**Test Methods:**
1. `test_user_can_access_own_site_features()`
2. `test_user_cannot_access_other_users_site_features()`
3. `test_non_existent_site_returns_404()`

### TASK-SEC-7-10-4: summary IDOR Protection

**Tests verify:**
- User can access their own site's summary
- User cannot access another user's site summary
- Non-existent site returns 404

**Test Methods:**
1. `test_user_can_access_own_site_summary()`
2. `test_user_cannot_access_other_users_site_summary()`
3. `test_non_existent_site_summary_returns_404()`

### TASK-SEC-7-10-5: Security Test File

**This task creates the actual test file containing all tests above.**

### TASK-SEC-7-10-6: Deployment Verification

**No automated tests required - manual verification:**
- `curl` tests against production
- Verify 401 for unauthenticated
- Verify 403 for IDOR
- Verify 200 for legitimate

---

## Test Coverage Summary

| Category | Tests | Purpose |
|----------|-------|---------|
| Authentication (401) | 3 | Verify auth middleware |
| IDOR Protection (403) | 3 | Verify authorization checks |
| Authorization Success (200) | 3 | Verify legitimate access works |
| Not Found (404) | 3 | Verify resource not found handling |
| Input Validation (404) | 6 | Verify regex route constraints |
| Edge Cases | 5 | Verify boundary conditions |
| **Total** | **23** | Complete security coverage |

---

## Prerequisites for Test Execution

1. **Database:** SQLite in-memory or test database
2. **Factories:** User, Site, Keyword factories must exist
3. **Models:** User, Site, Keyword models with relationships
4. **Routes:** Routes defined in `routes/api.php`
5. **Controller:** SerpFeatureController with methods

---

## Running Tests

```bash
# Run all SERP Feature security tests
php artisan test --filter=SerpFeatureSecurityTest

# Run specific test method
php artisan test --filter=test_unauthenticated_site_features_returns_401

# Run with verbose output
php artisan test --filter=SerpFeatureSecurityTest -v

# Run all security tests in project
php artisan test tests/Feature/Security/
```

---

## OWASP Coverage

| OWASP Top 10 (2021) | Test Coverage |
|---------------------|---------------|
| A01: Broken Access Control | AUTH-*, IDOR-*, AUTHZ-* |
| A03: Injection | INJ-* (SQL injection, path traversal) |
| A05: Security Misconfiguration | AUTH-* (missing authentication) |

---

## Lessons Learned

### Security Testing Best Practices
1. **Test both positive and negative cases:** 200 for valid, 401/403 for invalid
2. **Test all endpoints individually:** Don't assume one endpoint = all secure
3. **Test injection patterns:** SQL, XSS, path traversal
4. **Use realistic factory data:** Tests should reflect production scenarios
5. **Document expected behavior:** Tests serve as executable documentation

### Common Pitfalls
1. **Testing only happy path:** Always test failure scenarios
2. **Assuming route regex is enough:** IDOR can still occur with valid IDs
3. **Forgetting edge cases:** Zero, negative, null, empty values
4. **Not testing cross-user scenarios:** Critical for IDOR detection

---

## Test Status

- [ ] Test file created at `tests/Feature/Security/SerpFeatureSecurityTest.php`
- [ ] All 23 test methods implemented
- [ ] Tests pass after implementation tasks complete
- [ ] Tests fail before implementation (verify vulnerability exists)

---

## Created By
PreTester Agent

## Date
2026-02-28

## Related Files
- Specification: `SPECIFICATIONS/SPEC-SEC-7-10.md`
- Tasks: `TASKS/TASK-SEC-7-10-1.md` through `TASKS/TASK-SEC-7-10-6.md`
