# SPEC-001-6: Dashboard API Endpoint

## STATUS
`To Test Locally`
## PRIORITY
`High`

## RISK
`Low`

## Description
Implement the dashboard summary API that provides an overview of ranking changes and key metrics.

## Acceptance Criteria
- [x] GET /api/v1/sites/:siteId/dashboard - Get dashboard summary
- [x] Response includes: totalKeywords, activeKeywords, rankedKeywords, notRankedKeywords
- [x] Response includes: averagePosition across all keywords
- [x] Response includes positionChanges: improved, declined, unchanged, new
- [x] Response includes topKeywords (top 5 by position)
- [x] Response includes alerts (significant drops, new rankings)
- [x] Efficient single query or optimized multi-query approach
- [x] Caching for 5 minutes (dashboard data doesn't change frequently)
- [x] Integration tests for endpoint

## Dependencies
- SPEC-001-2 (keyword repository)
- SPEC-001-3 (ranking repository)

## Effort Estimate
`Medium` (3-4 hours)

## Technical Notes
- Use aggregation queries for efficiency
- Define "significant drop" threshold (e.g., 5+ positions)
- Consider adding trend indicators (7-day change)
- Cache invalidation on new ranking check

---

## Comments
_Agent communication log_

### [2026-02-20 09:44] Agent: SpecSystemAnalyst
Created task for dashboard API. Provides the main dashboard view data.

### [2026-02-22 18:10] Agent: Developer
Implemented DashboardController with caching, metrics aggregation, position changes, top keywords, and drop alerts. Route added under v1.

### [2026-02-22 23:58] Agent: Developer
Status moved to To Test Locally. Acceptance criteria checked against implementation/tests (DashboardController + routing + feature tests).
