# TASK-12-8: GSC Data Controller

## STATUS
`Done`

## PRIORITY
`High`

## RISK
`Low`

## Description
Develop `GSCDataController` to expose GSC metrics and opportunity keywords via API.

## Endpoints
1. **GET /api/projects/{projectId}/gsc** - Retrieve GSC data with date filters.
2. **GET /api/projects/{projectId}/gsc/opportunities** - List opportunity keywords.

## Code Example
```php
class GSCDataController extends Controller
{
    public function index(int $projectId, Request $request);
    public function opportunities(int $projectId);
}
```

## Acceptance Criteria
- [x] Data retrieval endpoints return GSC metrics with pagination.
- [x] Opportunity keywords calculated based on CTR gaps.
- [x] Authorization ensures user has project access.

## Dependencies
- TASK-12-5: Data sync service must populate data.
- TASK-12-6: Query matching must link keywords to GSC data.

## Effort Estimate
3-4 hours.

---

## Comments
### [2026-02-20 19:21] ProjectManager: Prioritization Complete
- Priority: High (correct - exposes GSC data and opportunities to frontend)
- Risk: Low (read-only API, standard authorization)
- Dependencies: TASK-12-5 (Data Sync), TASK-12-6 (Query Matching)
- Execution order: After data sync and matching are operational
- Enables TASK-12-10 (Dashboard Component)
- Status: To Develop

### [2026-02-20 17:36] Agent: SpecSystemAnalyst
API controller for GSC data retrieval and opportunity analysis.
### [2026-02-22 16:21] Agent: Developer
**Implementation Complete**

- Added GSCDataController with index/opportunities endpoints (site-verified by user)
- Added routes under /api/v1/projects/{projectId}/gsc
- Pagination and date filters included; opportunities use CTR-gap heuristic (scopeOpportunities)

**Status:** ? To Test Locally

### [2026-02-22 18:19] Agent: LocalTester
**Local Test PASS**

- Command: `php artisan test --filter=GSCApiEndpointsTest`
- Result: 11 passed (13 assertions)
- Notes: No failures observed. Endpoints validated (gsc data + opportunities + auth/date validation).

**Status:** To Deploy

### [2026-02-22 18:28] Agent: DevOps
**Deployment Complete**

- Workflow enabled; lock acquired.
- Deployment triggered via existing GitHub Actions pipeline (push to main).
- No deployment errors observed in local context.

**Status:** To Test Prod

### [2026-02-22 18:34] Agent: ProdTester
**Prod Test FAIL**

- Smoke checks against production returned 404:
  - https://seoindicator.com/api/health
  - https://seoindicator.com/api/v1/projects/1/gsc
  - https://seoindicator.com/api/v1/projects/1/gsc/opportunities
- Expected: 200 for health; 401/403 for protected endpoints (route exists).
- Indicates routes not deployed or webroot/base path mismatch.

**Status:** To Develop
- Created TASK-12-12 for documentation discrepancy / prod routing validation.

### [2026-02-22 23:45] Agent: Developer
**Fix for prod 404 routing**

- Added root `.htaccess` to rewrite `/api/*` requests to `backend/public/index.php` (preserves Laravel API routes under /api).
- No code changes to controller; prod 404 likely due to webroot not routing to backend.

### [2026-02-23 14:45] Agent: LocalTester
**Local Test PASS**

- Command: `php artisan test --filter=GSCApiEndpointsTest`
- Result: 11 passed (13 assertions)
- Confirmed `.htaccess` exists and contains rewrite rules for `/api/*` to `backend/public/index.php`.
- Local environment remains stable after routing changes.

**Status:** To Deploy

### [2026-02-23 15:00] Agent: DevOps
**Deployment Complete**

- Workflow enabled; lock acquired.
- Pushed pending commits to origin/main (fc9cb91..2d3d903).
- Verified production endpoints:
  - `/api/health` → 200 OK (database connected)
  - `/api/v1/projects/1/gsc` → 401 Unauthenticated (correct - protected route)
  - `/api/v1/projects/1/gsc/opportunities` → 401 Unauthenticated (correct - protected route)
- `.htaccess` routing fix confirmed working in production.

**Status:** To Test Prod

### [2026-02-27 18:45] Agent: ProdTester
**Prod Test PASS**

**Smoke Tests:**
- `/api/health` → 200 OK ✓
- `/api/v1/projects/1/gsc` → 401 Unauthenticated ✓
- `/api/v1/projects/1/gsc/opportunities` → 401 Unauthenticated ✓

**Security Tests (adversarial):**
- SQL injection in path parameter → 401 (rejected at auth layer) ✓
- XSS in query params → 401 (rejected at auth layer) ✓
- Path traversal attempt → 404 (rejected at routing) ✓
- Invalid project ID → 401 ✓

**Unit Tests (local):**
- GSCApiEndpointsTest: 11 passed (13 assertions) ✓
- GSCSecurityTest: 14 passed (37 assertions) ✓

**Security Headers Present:**
- Content-Security-Policy: upgrade-insecure-requests ✓
- Cache-Control: no-cache, private ✓

**Configuration Fix Applied:**
- Added APP_KEY to phpunit.xml for local test environment

**Status:** To Document

### [2026-02-27 19:05] DocSystemAnalyst: Documentation Complete
- Added GSC Data Controller section to DOCUMENTATION.md
- Documented API endpoints: GET /api/v1/projects/{projectId}/gsc and /opportunities
- Included query parameters, response format, authorization, test coverage
- Production status: Deployed, routing fix verified, tests passing (25 tests, 50 assertions)

**Status:** Done

### [2026-03-10 12:33] DocSystemAnalyst: Re-verification Complete
- Re-verified TASK-12-8 remains `Done`.
- Verified controller + routes still present in `backend/app/Http/Controllers/Api/V1/GSCDataController.php` and `backend/routes/api.php`.
- Re-ran targeted backend coverage: `php artisan test tests/Feature/Api/GSCApiEndpointsTest.php tests/Security/GSCSecurityTest.php` → 25 passed (50 assertions).
- Verified production unauthenticated behavior: `/api/v1/projects/1/gsc` and `/api/v1/projects/1/gsc/opportunities` both return 401 JSON.
- Corrected `DOCUMENTATION.md` drift to match implementation: `perPage` default is 20, response meta includes `lastPage`, ownership is enforced via `Site` lookup, and missing/foreign sites return 404 rather than 403.

**Status:** Done

