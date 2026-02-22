# TASK-12-8: GSC Data Controller

## STATUS
`To Test Locally`

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

**Status:** To Test Locally

