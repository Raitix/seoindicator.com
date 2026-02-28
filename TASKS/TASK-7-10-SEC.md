# TASK-7-10-SEC: SERP Features Authentication Security Fix

## STATUS
`To Break Into Tasks`

## PRIORITY
`High`

## RISK
`High`

## Related
- Feature: FEAT-007
- Specification: SPEC-007
- **Security Specification: SPEC-SEC-7-10** (NEW)
- Original Task: TASK-7-10
- Security Fix For: TASK-7-10

## Description
**CRITICAL SECURITY FIX:** The SERP Features API endpoints were deployed without authentication, allowing unauthenticated access to sensitive SEO data. This task adds authentication and authorization to the SERP Features endpoints.

## Security Issue
The following endpoints are currently PUBLIC (no auth required):
- `GET /api/v1/keywords/{id}/serp-features`
- `GET /api/v1/sites/{siteId}/serp-features`
- `GET /api/v1/sites/{siteId}/serp-features/summary`

**Impact:**
- Any unauthenticated user can access SERP feature data for any site
- IDOR vulnerability - users can enumerate site IDs to discover data
- Privacy/data exposure risk for all users

## Acceptance Criteria
- [ ] All SERP Features endpoints require authentication
- [ ] User can only access SERP features for their own sites
- [ ] Cross-user access returns 403 Forbidden
- [ ] Unauthenticated access returns 401 Unauthorized
- [ ] IDOR protection via Site ownership check

## Implementation Notes

**Move routes to authenticated group in `routes/api.php`:**

```php
// BEFORE (public - INSECURE):
Route::prefix('v1')->group(function () {
    Route::get('keywords/{id}/serp-features', [SerpFeatureController::class, 'keywordFeatures']);
    Route::get('sites/{siteId}/serp-features', [SerpFeatureController::class, 'siteFeatures']);
    Route::get('sites/{siteId}/serp-features/summary', [SerpFeatureController::class, 'summary']);
});

// AFTER (authenticated - SECURE):
Route::prefix('v1')->middleware(['auth'])->group(function () {
    Route::get('keywords/{id}/serp-features', [SerpFeatureController::class, 'keywordFeatures'])
        ->where('id', '[0-9]+');
    Route::get('sites/{siteId}/serp-features', [SerpFeatureController::class, 'siteFeatures'])
        ->where('siteId', '[0-9]+');
    Route::get('sites/{siteId}/serp-features/summary', [SerpFeatureController::class, 'summary'])
        ->where('siteId', '[0-9]+');
});
```

**Add IDOR protection in controller:**

```php
// SerpFeatureController.php
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

## Test Cases Required
| Test ID | Scenario | Expected Result |
|---------|----------|-----------------|
| SEC-001 | Unauthenticated request to any endpoint | 401 Unauthorized |
| SEC-002 | Authenticated user accessing own site | 200 OK |
| SEC-003 | Authenticated user accessing another user's site | 403 Forbidden |
| SEC-004 | Invalid/non-existent site ID | 404 Not Found |
| SEC-005 | SQL injection in siteId parameter | Blocked |

## Rollback Plan
If issues arise, temporarily re-add routes to public group while fixing.

## Dependencies
- TASK-7-10 (original implementation)
- Existing authentication middleware
- Site model with user_id relationship

## Effort Estimate
Small (1-2 hours)

---

## Comments
_Agent communication log_

### [2026-02-28 12:00] Agent: ProdTester
**CRITICAL SECURITY VULNERABILITY FOUND**

During production testing of TASK-7-10, discovered that SERP Features API endpoints are deployed WITHOUT authentication:

**Test Results:**
- `GET /api/v1/sites/1/serp-features` → 200 OK (no auth) ❌
- `GET /api/v1/sites/1/serp-features/summary` → 200 OK (no auth) ❌
- `GET /api/v1/keywords/1/serp-features` → 404 (route issue) ❌

**Security Impact:**
- Data exposure: Anyone can access SERP feature data
- IDOR vulnerability: Site IDs can be enumerated
- Privacy risk: Competitive intelligence exposed

**Root Cause:**
Routes defined in `routes/api.php` outside the `auth` middleware group with comment "public read for now".

**Recommendation:**
IMMEDIATE FIX REQUIRED. Move routes to authenticated group and add IDOR protection.

### [2026-02-28 12:05] Agent: Architect
**Security Specification Created**

- Created SPEC-SEC-7-10.md with comprehensive technical specification
- Defined 6 implementation tasks (SEC-7-10-1 through SEC-7-10-6)
- Risk assessment: HIGH (authentication/authorization changes)
- Priority: HIGH (critical security vulnerability)
- Includes:
  - Authentication middleware requirements
  - IDOR protection code for all 3 endpoints
  - Security test matrix (8 test scenarios)
  - Automated test implementation examples
  - Deployment checklist and rollback plan
- Status updated: To Break Into Tasks
- Ready for SpecSystemAnalyst to create task files
