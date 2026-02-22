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

