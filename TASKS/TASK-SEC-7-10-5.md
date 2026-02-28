# TASK-SEC-7-10-5: Create Security Test File

## STATUS
`To Test Locally`

## PRIORITY
`High`

## RISK
`Medium`

## Security Classification
**CRITICAL** - Security Test Coverage

## Parent
- Specification: [SPEC-SEC-7-10](../SPECIFICATIONS/SPEC-SEC-7-10.md)
- Feature: [FEAT-007](../FEATURES/FEAT-007.md)

## Description
Create comprehensive security test file to verify all authentication and authorization fixes. Tests must cover all security scenarios defined in the specification.

## Scope
- File: `tests/Feature/Security/SerpFeatureSecurityTest.php`

## Implementation Details

### Test Matrix (from SPEC-SEC-7-10)

| Test ID | Scenario | Expected Result |
|---------|----------|-----------------|
| SEC-001 | Unauthenticated request to keywords endpoint | 401 Unauthorized |
| SEC-002 | Unauthenticated request to sites endpoint | 401 Unauthorized |
| SEC-003 | Unauthenticated request to summary endpoint | 401 Unauthorized |
| SEC-004 | Authenticated user accessing own site | 200 OK |
| SEC-005 | Authenticated user accessing another user's site | 403 Forbidden |
| SEC-006 | Authenticated user accessing non-existent site | 404 Not Found |
| SEC-007 | SQL injection in siteId parameter | Blocked (parameterized) |
| SEC-008 | Path traversal in siteId parameter | Blocked (regex validation) |

### Required Test Methods

```php
<?php

namespace Tests\Feature\Security;

use Tests\TestCase;
use App\Models\User;
use App\Models\Site;
use App\Models\Keyword;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SerpFeatureSecurityTest extends TestCase
{
    use RefreshDatabase;

    // SEC-001: Unauthenticated keyword features
    public function test_unauthenticated_keyword_features_returns_401(): void
    {
        $response = $this->getJson('/api/v1/keywords/1/serp-features');
        $response->assertStatus(401);
    }

    // SEC-002: Unauthenticated site features
    public function test_unauthenticated_site_features_returns_401(): void
    {
        $response = $this->getJson('/api/v1/sites/1/serp-features');
        $response->assertStatus(401);
    }

    // SEC-003: Unauthenticated summary
    public function test_unauthenticated_summary_returns_401(): void
    {
        $response = $this->getJson('/api/v1/sites/1/serp-features/summary');
        $response->assertStatus(401);
    }

    // SEC-004: Authenticated user own site
    public function test_authenticated_user_can_access_own_site(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)
            ->getJson("/api/v1/sites/{$site->id}/serp-features");

        $response->assertStatus(200);
    }

    // SEC-005: IDOR protection
    public function test_user_cannot_access_other_users_site(): void
    {
        $owner = User::factory()->create();
        $attacker = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $owner->id]);

        $response = $this->actingAs($attacker)
            ->getJson("/api/v1/sites/{$site->id}/serp-features");

        $response->assertStatus(403);
    }

    // SEC-006: Non-existent site
    public function test_non_existent_site_returns_404(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->getJson('/api/v1/sites/99999/serp-features');

        $response->assertStatus(404);
    }

    // SEC-007: SQL injection protection (parameterized queries)
    public function test_sql_injection_is_blocked(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->getJson("/api/v1/sites/1' OR '1'='1/serp-features");

        // Regex validation should reject non-numeric IDs
        $response->assertStatus(404);
    }

    // SEC-008: Path traversal protection
    public function test_path_traversal_is_blocked(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->getJson("/api/v1/sites/../etc/passwd/serp-features");

        // Regex validation should reject non-numeric IDs
        $response->assertStatus(404);
    }

    // Additional: IDOR for keyword endpoint
    public function test_user_cannot_access_other_users_keyword(): void
    {
        $owner = User::factory()->create();
        $attacker = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $owner->id]);
        $keyword = Keyword::factory()->create(['site_id' => $site->id]);

        $response = $this->actingAs($attacker)
            ->getJson("/api/v1/keywords/{$keyword->id}/serp-features");

        $response->assertStatus(403);
    }

    // Additional: Summary endpoint IDOR
    public function test_user_cannot_access_other_users_summary(): void
    {
        $owner = User::factory()->create();
        $attacker = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $owner->id]);

        $response = $this->actingAs($attacker)
            ->getJson("/api/v1/sites/{$site->id}/serp-features/summary");

        $response->assertStatus(403);
    }
}
```

## Acceptance Criteria
- [ ] Test file created at correct location
- [ ] All 8 security scenarios from spec covered
- [ ] Tests use `RefreshDatabase` trait
- [ ] Factory usage for User, Site, Keyword models
- [ ] Descriptive test method names
- [ ] All tests pass after implementation tasks complete
- [ ] Tests fail before implementation (verify vulnerability exists)

## Dependencies
- None (can be created in parallel with implementation tasks)
- Tests will fail until TASK-SEC-7-10-1 through TASK-SEC-7-10-4 are complete

## Effort Estimate
`Medium` (1-2 hours)

## Security Impact
- Provides regression protection
- Documents security requirements as executable tests
- Enables continuous security verification

---

## Comments
_Agent communication log_

### [2026-02-28 12:10] Agent: SpecSystemAnalyst
- Task created from SPEC-SEC-7-10
- Comprehensive security test coverage
- Can run in parallel with implementation tasks
### [2026-02-28 12:55] Agent: PreTester
- Security test file created: `tests/Feature/Api/V1/Security/SerpFeatureSecurityTest.php`
- Test documentation created: `TESTS/TEST-SEC-7-10.md`
- 41 tests covering:
  - Authentication (4 tests): 401 for unauthenticated requests
  - IDOR Protection (8 tests): 403 for cross-user access, 200 for owner access
  - Input Validation (10 tests): Invalid IDs, missing resources, parameter ranges
  - SQL Injection (3 tests): Route constraints block injection
  - XSS Protection (2 tests): No script reflection in responses
  - Path Traversal (2 tests): Numeric route constraints block traversal
  - Edge Cases (8 tests): Direct ownership, empty data, parameter capping
  - Date Validation (3 tests): Format and range validation
- All 41 tests passing (65 assertions)
- Routes already have authentication middleware and IDOR protection implemented
- Status: To Develop (tests verify implementation is correct)

### [2026-02-28 12:13] Agent: ProjectManager
- **Priority: HIGH** (CRITICAL SECURITY - test coverage required)
- **Execution Order: 2** (Can run parallel with implementation Tasks 2-4)
- **Dependencies: None** (but tests will fail until implementation complete)
- PreTester can create tests in parallel with Developer work
- Tests serve as TDD specification for security requirements
- Status: To Write Tests

### [2026-02-28 13:05] Agent: Developer
- **TEST VERIFICATION COMPLETE**
- All 41 security tests pass (65 assertions)
- Test file: `tests/Feature/Api/V1/Security/SerpFeatureSecurityTest.php`
- Coverage verified: Authentication, IDOR, Input Validation, SQL Injection, XSS, Path Traversal
- No code changes required - tests verify existing security implementation
- Status: To Test Locally
