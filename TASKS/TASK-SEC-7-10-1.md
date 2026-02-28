# TASK-SEC-7-10-1: Move Routes to Authenticated Middleware Group

## STATUS
`To Write Tests`

## PRIORITY
`High`

## RISK
`High`

## Security Classification
**CRITICAL** - Authentication Bypass Fix

## Parent
- Specification: [SPEC-SEC-7-10](../SPECIFICATIONS/SPEC-SEC-7-10.md)
- Feature: [FEAT-007](../FEATURES/FEAT-007.md)

## Description
Move SERP Features API endpoints from the public route group to the authenticated middleware group. This fixes the authentication bypass vulnerability by requiring all requests to be authenticated.

## Scope
- File: `routes/api.php`
- Endpoints to secure:
  - `GET /api/v1/keywords/{id}/serp-features`
  - `GET /api/v1/sites/{siteId}/serp-features`
  - `GET /api/v1/sites/{siteId}/serp-features/summary`

## Implementation Details

### Current State (INSECURE)
```php
Route::prefix('v1')->group(function () {
    Route::get('keywords/{id}/serp-features', [SerpFeatureController::class, 'keywordFeatures']);
    Route::get('sites/{siteId}/serp-features', [SerpFeatureController::class, 'siteFeatures']);
    Route::get('sites/{siteId}/serp-features/summary', [SerpFeatureController::class, 'summary']);
});
```

### Required State (SECURE)
```php
Route::prefix('v1')->middleware(['auth'])->group(function () {
    Route::get('keywords/{id}/serp-features', [SerpFeatureController::class, 'keywordFeatures'])
        ->where('id', '[0-9]+');
    Route::get('sites/{siteId}/serp-features', [SerpFeatureController::class, 'siteFeatures'])
        ->where('siteId', '[0-9]+');
    Route::get('sites/{siteId}/serp-features/summary', [SerpFeatureController::class, 'summary'])
        ->where('siteId', '[0-9]+');
});
```

### Changes Required
1. Wrap routes in `middleware(['auth'])` group
2. Add regex constraints for route parameters (`[0-9]+`)
3. Remove any duplicate route definitions in public groups

## Acceptance Criteria
- [ ] All three endpoints are inside authenticated route group
- [ ] Route parameters have regex validation (`[0-9]+`)
- [ ] No duplicate route definitions exist
- [ ] `php artisan route:list --path=serp-features` shows middleware: auth
- [ ] Unauthenticated requests return 401 (verified manually)

## Dependencies
- None (this is the first security task)

## Effort Estimate
`Small` (30 minutes)

## Security Impact
- Fixes OWASP A01:2021 (Broken Access Control)
- Prevents unauthenticated data access

## Testing Requirements
- Manual test: `curl /api/v1/sites/1/serp-features` returns 401
- Automated test: See TASK-SEC-7-10-5

---

## Comments
_Agent communication log_

### [2026-02-28 12:10] Agent: SpecSystemAnalyst
- Task created from SPEC-SEC-7-10
- First task in security fix chain
- Must be completed before IDOR protection tasks
- Status: To Prioritize

### [2026-02-28 12:13] Agent: ProjectManager
- **Priority: HIGH** (CRITICAL SECURITY - authentication bypass fix)
- **Execution Order: 1** (Foundation - must run first)
- **Dependencies: None**
- All security tasks prioritized immediately due to CRITICAL classification
- Status: To Write Tests
