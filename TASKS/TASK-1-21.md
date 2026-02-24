# SPEC-001-21: Production Health Endpoint Missing (Docs Discrepancy)

## STATUS
`To Develop`

## PRIORITY
`High`

## RISK
`Low`

## Description
DOCUMENTATION.md states a production health endpoint exists at `GET /api/health`, but production returns 404. Align implementation with documentation or update docs to reflect reality.

## Acceptance Criteria
- [ ] If health endpoint is intended: `GET /api/health` returns `{ "status": "ok", "database": "connected" }` with HTTP 200 in production
- [ ] If endpoint is not intended: update DOCUMENTATION.md to remove or correct the health endpoint reference
- [ ] Automated test coverage added for health endpoint behavior (or explicit doc update test)

## Dependencies
- None

## Effort Estimate
`Small` (1-2 hours)

## Technical Notes
- Check backend route registration for `/api/health`
- Ensure prod routing is aligned with local dev
- If removing endpoint, ensure no callers depend on it

---

## Comments
_Agent communication log_

### [2026-02-22 23:10] Agent: ProdTester
Detected production doc mismatch: `https://seoindicator.com/api/health` returns 404, but DOCUMENTATION.md lists it as available. Created task per discrepancy rule.

### [2026-02-24 22:30] Agent: PreTester
**Investigation Results:**
- Health endpoint code EXISTS in codebase
- Route defined: `backend/routes/api.php` line 21: `Route::get('/health', [DeployController::class, 'health'])`
- Controller method: `backend/app/Http/Controllers/DeployController.php` method `health()`
- Returns: `{ "status": "ok", "timestamp": "...", "database": "connected|disconnected", "app": "SEOIndicator", "env": "..." }`

**Tests Created:**
- Feature tests: `backend/tests/Feature/Api/HealthEndpointTest.php` (10 tests, 29 assertions) ✓ ALL PASS
- Unit tests: `backend/tests/Unit/Controllers/DeployControllerTest.php` (5 tests, 10 assertions) ✓ ALL PASS
- Test doc: `TESTS/TEST-1-21.md`

**Root Cause:**
Production 404 is a DEPLOYMENT issue, not missing code. The endpoint exists and works locally.

**Developer Action Required:**
1. Deploy latest code to production
2. Verify nginx/apache routes `/api/*` correctly to Laravel
3. Verify PHP-FPM is running
4. Test: `curl https://seoindicator.com/api/health`

**Acceptance Criteria Status:**
- [x] Health endpoint returns correct response structure (verified via tests)
- [x] Automated test coverage added (15 tests, 39 assertions)
- [ ] Production deployment needs verification
