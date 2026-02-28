# SPEC-001-5: Ranking History API Endpoints

## STATUS
`To Deploy`
## PRIORITY
`High` (Security Issue)

## RISK
`Low`

## Description
Implement API endpoints for retrieving ranking history data. Powers the ranking charts and historical analysis.

## Acceptance Criteria
- [ ] GET /api/v1/keywords/:id/history - Get ranking history with date range
- [ ] Query params: from, to, limit with sensible defaults (30 days, 100 records)
- [ ] Response includes keyword details and history array
- [ ] Response includes summary: currentPosition, bestPosition, worstPosition, averagePosition, totalChecks
- [ ] Handle keywords with no history (empty array)
- [ ] POST /api/v1/keywords/:id/check - Trigger manual rank check
- [ ] Manual check returns job ID for status tracking
- [ ] Proper error handling for invalid keyword ID
- [ ] Integration tests for all endpoints

## Dependencies
- SPEC-001-3 (ranking repository)
- SPEC-001-4 (keyword endpoints for context)

## Effort Estimate
`Small` (2-3 hours)

## Technical Notes
- Date range defaults: from = 30 days ago, to = now
- Consider caching frequently accessed history
- Manual check creates job in queue (queue setup in later task)

---

## Comments
_Agent communication log_

### [2026-02-20 09:44] Agent: SpecSystemAnalyst
Created task for ranking history API. Required for chart visualization.

### [2026-02-22 18:10] Agent: Developer
Implemented RankingController history endpoint with date range/limit + stats, and manual check endpoint using JobQueue stub. Routes added under v1.

### [2026-02-22 23:55] Agent: Developer
Reviewed implementation and tests: RankingController + routes exist, JobQueue integration present, and Feature test `backend/tests/Feature/Api/V1/RankingControllerTest.php` covers history + manual check. Marked status To Test Locally for LocalTester to run tests.

### [2026-02-27 23:08] Agent: LocalTester
**LOCAL TEST RESULTS: ✅ PASS**

Ran `php artisan test --filter RankingControllerTest`:
- 10 tests passed, 71 assertions
- Duration: 2.45s

**Tests Verified:**
- GET /api/v1/keywords/{id}/history - Returns history with date range and limit support
- History response includes keyword details, history array, and summary stats
- Summary stats: currentPosition, bestPosition, worstPosition, averagePosition, totalChecks
- Empty history handled correctly (empty array, totalChecks: 0)
- Date range filtering works (from/to params)
- Limit parameter respected
- NULL positions handled in statistics
- POST /api/v1/keywords/{id}/check - Returns 202 with jobId and status "queued"
- Invalid keyword ID returns 404
- JobQueue mock integration working

**Routes Verified:**
- POST api/v1/keywords/{id}/check → RankingController@check
- GET api/v1/keywords/{id}/history → RankingController@history

**Recommendation:** Ready for deployment to production.

### [2026-02-27 23:14] Agent: DevOps
**DEPLOYMENT VERIFIED: ✅ SUCCESS**

Both ranking history API endpoints are live in production:

**Endpoints Verified:**
- `GET /api/v1/keywords/{id}/history` - Active, returns proper 404 for non-existent keywords
- `POST /api/v1/keywords/{id}/check` - Active, returns proper 404 for non-existent keywords

**Production Health Check:** PASS
- Health endpoint: https://seoindicator.com/api/health → status: ok
- Database: connected

**Deployment Method:** Code was already committed and pushed to origin/main. Deployment verified via production endpoint testing.

**Status:** Ready for ProdTester to validate in production environment.

### [2026-02-27 23:24] Agent: ProdTester
**PRODUCTION TEST RESULTS: ❌ FAIL - SECURITY ISSUE**

**Routes Exist and Functional:** ✅
- `GET /api/v1/keywords/{id}/history` - Returns data when keyword exists
- `POST /api/v1/keywords/{id}/check` - Returns 202 with jobId when keyword exists
- 404 returned for non-existent keywords ✅

**CRITICAL SECURITY ISSUE FOUND:** ❌
- **Missing Authentication:** Routes are defined in PUBLIC route group (no `auth` middleware)
- **Impact:** Unauthenticated users can:
  - Query ranking history for ANY keyword ID
  - Trigger manual rank checks for ANY keyword ID
- **Root Cause:** `routes/api.php` places RankingController routes outside the `middleware(['auth'])` group
- **Comparison:** `/api/v1/projects` correctly returns 401 for unauthenticated requests

**Code Location:**
```php
// routes/api.php - PUBLIC group (NO auth)
Route::prefix('v1')->group(function () {
    Route::get('keywords/{id}/history', [RankingController::class, 'history']);
    Route::post('keywords/{id}/check', [RankingController::class, 'check']);
});

// CORRECT pattern (authenticated):
Route::prefix('v1')->middleware(['auth'])->group(function () {
    Route::apiResource('projects', ProjectController::class);
});
```

**Additional Issue - Missing IDOR Protection:**
- Controller does not validate user owns the keyword
- Even with auth, any user could access any keyword's data

**Test Coverage Gap:**
- `RankingControllerTest.php` has no authentication tests
- All tests pass without authentication checks

**REQUIRED FIX:**
1. Move routes to authenticated group OR add `->middleware('auth')` to routes
2. Add user ownership validation in controller (IDOR protection)
3. Add authentication tests to test file

**Status:** Returning to Developer for security fix. Priority: High (Security).

### [2026-02-28 10:10] Agent: LocalTester
**LOCAL TEST RESULTS: ✅ ALL PASS**

Ran `php artisan test --filter RankingControllerTest`:
- **15 tests passed, 79 assertions**
- Duration: 2.76s

**Security Fixes Verified:**
- ✅ `test_history_requires_authentication` - Returns 401 for unauthenticated requests
- ✅ `test_check_requires_authentication` - Returns 401 for unauthenticated requests
- ✅ `test_user_cannot_access_other_users_keyword_history` - Returns 403 for IDOR attempt
- ✅ `test_user_cannot_check_other_users_keyword` - Returns 403 for IDOR attempt

**Routes Now Authenticated:**
```php
// routes/api.php - Now inside middleware(['auth']) group
Route::prefix('v1')->middleware(['auth'])->group(function () {
    Route::get('keywords/{id}/history', [RankingController::class, 'history']);
    Route::post('keywords/{id}/check', [RankingController::class, 'check']);
});
```

**IDOR Protection Implemented:**
- `RankingController::userOwnsKeyword()` validates ownership via:
  - Direct `user_id` on keyword
  - `site->user_id` relationship

**Recommendation:** Ready for deployment to production.
