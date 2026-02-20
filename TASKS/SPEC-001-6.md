# SPEC-001-6: Dashboard API Endpoint

## STATUS`To Write Tests`

## PRIORITY
`High`

## RISK
`Low`

## Description
Implement the dashboard summary API that provides an overview of ranking changes and key metrics.

## Acceptance Criteria
- [ ] GET /api/v1/sites/:siteId/dashboard - Get dashboard summary
- [ ] Response includes: totalKeywords, activeKeywords, rankedKeywords, notRankedKeywords
- [ ] Response includes: averagePosition across all keywords
- [ ] Response includes positionChanges: improved, declined, unchanged, new
- [ ] Response includes topKeywords (top 5 by position)
- [ ] Response includes alerts (significant drops, new rankings)
- [ ] Efficient single query or optimized multi-query approach
- [ ] Caching for 5 minutes (dashboard data doesn't change frequently)
- [ ] Integration tests for endpoint

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
