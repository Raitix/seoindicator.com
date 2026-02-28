# SPEC-SEC-7-10: SERP Features API Authentication Security Fix

## STATUS
`To Break Into Tasks`

## PRIORITY
`High`

## RISK
`High`

## Security Classification
**CRITICAL** - Authentication Bypass Vulnerability

## Overview
This security specification addresses a critical authentication bypass vulnerability in the SERP Features API endpoints. The endpoints were deployed without authentication middleware, exposing sensitive SEO data to unauthenticated users.

## Vulnerability Details

### Affected Endpoints
| Endpoint | Current State | Required State |
|----------|---------------|----------------|
| `GET /api/v1/keywords/{id}/serp-features` | Public (no auth) | Authenticated |
| `GET /api/v1/sites/{siteId}/serp-features` | Public (no auth) | Authenticated + IDOR |
| `GET /api/v1/sites/{siteId}/serp-features/summary` | Public (no auth) | Authenticated + IDOR |

### Attack Vectors
1. **Data Exposure**: Any unauthenticated user can access SERP feature data
2. **IDOR (Insecure Direct Object Reference)**: Site IDs can be enumerated to discover other users' data
3. **Privacy Violation**: Competitive intelligence exposed without authorization

### Impact Assessment
- **Confidentiality**: HIGH - Sensitive SEO data exposed
- **Integrity**: LOW - Read-only endpoints
- **Availability**: LOW - No service disruption

## Related Items
- Feature: [FEAT-007](../FEATURES/FEAT-007.md)
- Specification: [SPEC-007](../SPECIFICATIONS/SPEC-007.md)
- Original Task: [TASK-7-10](../TASKS/TASK-7-10.md)
- Security Task: [TASK-7-10-SEC](../TASKS/TASK-7-10-SEC.md)

## Technical Specification

### Authentication Requirements
All endpoints must be moved inside the authenticated route group:

```php
// routes/api.php

// BEFORE (INSECURE):
Route::prefix('v1')->group(function () {
    Route::get('keywords/{id}/serp-features', [SerpFeatureController::class, 'keywordFeatures']);
    Route::get('sites/{siteId}/serp-features', [SerpFeatureController::class, 'siteFeatures']);
    Route::get('sites/{siteId}/serp-features/summary', [SerpFeatureController::class, 'summary']);
});

// AFTER (SECURE):
Route::prefix('v1')->middleware(['auth'])->group(function () {
    Route::get('keywords/{id}/serp-features', [SerpFeatureController::class, 'keywordFeatures'])
        ->where('id', '[0-9]+');
    Route::get('sites/{siteId}/serp-features', [SerpFeatureController::class, 'siteFeatures'])
        ->where('siteId', '[0-9]+');
    Route::get('sites/{siteId}/serp-features/summary', [SerpFeatureController::class, 'summary'])
        ->where('siteId', '[0-9]+');
});
```

### Authorization Requirements (IDOR Protection)

**Keyword Features Endpoint:**
```php
public function keywordFeatures(Request $request, int $id)
{
    $keyword = Keyword::findOrFail($id);
    $site = $keyword->site;
    
    // IDOR protection - verify user owns the site
    if ($site->user_id !== auth()->id()) {
        abort(403, 'Unauthorized access to this keyword.');
    }
    
    // ... existing logic
}
```

**Site Features Endpoint:**
```php
public function siteFeatures(Request $request, int $siteId)
{
    $site = Site::findOrFail($siteId);
    
    // IDOR protection
    if ($site->user_id !== auth()->id()) {
        abort(403, 'Unauthorized access to this site.');
    }
    
    // ... existing logic
}
```

**Summary Endpoint:**
```php
public function summary(Request $request, int $siteId)
{
    $site = Site::findOrFail($siteId);
    
    // IDOR protection
    if ($site->user_id !== auth()->id()) {
        abort(403, 'Unauthorized access to this site.');
    }
    
    // ... existing logic
}
```

### Response Codes

| Scenario | Code | Response |
|----------|------|----------|
| Unauthenticated request | 401 | `{"message": "Unauthenticated."}` |
| Valid user, own site | 200 | Normal response |
| Valid user, another's site | 403 | `{"message": "Unauthorized access to this site."}` |
| Non-existent site/keyword | 404 | `{"message": "No query results for model [Site]"}` |

## Test Requirements

### Security Test Matrix

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

### Automated Test Implementation
```php
// tests/Feature/Security/SerpFeatureSecurityTest.php

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
}
```

## Implementation Tasks

| Task ID | Description | Priority | Risk |
|---------|-------------|----------|------|
| SEC-7-10-1 | Move routes to authenticated middleware group | High | High |
| SEC-7-10-2 | Add IDOR protection to keywordFeatures method | High | High |
| SEC-7-10-3 | Add IDOR protection to siteFeatures method | High | High |
| SEC-7-10-4 | Add IDOR protection to summary method | High | High |
| SEC-7-10-5 | Create security test file (SerpFeatureSecurityTest.php) | High | Medium |
| SEC-7-10-6 | Deploy to production and verify | High | High |

## Rollback Plan
If issues arise after deployment:
1. Temporarily re-add routes to public group (emergency only)
2. Document the issue and create new fix task
3. Re-deploy with corrected authentication

## Deployment Checklist
- [ ] Routes moved to auth middleware
- [ ] IDOR protection added to all methods
- [ ] Security tests pass locally
- [ ] Security tests pass in staging
- [ ] Deploy to production
- [ ] Verify 401 for unauthenticated requests
- [ ] Verify 403 for IDOR attempts
- [ ] Verify 200 for legitimate requests

## Compliance
This fix addresses:
- OWASP A01:2021 - Broken Access Control
- OWASP A05:2021 - Security Misconfiguration

---

## Comments
_Agent communication log_

### [2026-02-28 12:05] Agent: Architect
- Security specification created
- Task breakdown defined (6 tasks)
- Risk: HIGH (authentication/authorization changes)
- Priority: HIGH (critical security vulnerability)
- Status: To Break Into Tasks
