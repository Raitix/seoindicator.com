# TASK-35-9: GBP Insights Sync Service

## STATUS
`To Prioritize`

## SPECIFICATION
SPEC-035: Local SEO & Google Business Profile Tracking

## PRIORITY
`High`

## RISK
`Low`

## Description
Create GBPInsightsService for syncing and aggregating Google Business Profile insights data.

## Acceptance Criteria
- [ ] GBPInsightsService created with interface
- [ ] Method: syncInsights(BusinessLocation $location, ?array $dateRange = null): void
  - Fetches insights from GBP API
  - Stores daily snapshots in gbp_insights table
  - Handles duplicate dates (upsert)
- [ ] Method: getInsights(BusinessLocation $location, array $dateRange): Collection
  - Returns insights for date range
  - Eager loads related data efficiently
- [ ] Method: getInsightsTrend(BusinessLocation $location, int $days = 90): array
  - Returns aggregated trends with daily breakdown
  - Calculates week-over-week and month-over-month changes
- [ ] Method: getTotalMetrics(BusinessLocation $location, array $dateRange): array
  - Aggregates total views, searches, actions
  - Returns summary with breakdowns
- [ ] Method: getTopPerformingDay(BusinessLocation $location, int $days = 30): ?array
  - Identifies best performing day
- [ ] Caching: 1-hour TTL for insights data
- [ ] Queue integration for background sync
  - dispatch(new SyncInsightsJob($location))
- [ ] Error handling and logging
- [ ] Unit tests with mocked GBP API
- [ ] Feature tests for sync operations

## Dependencies
- TASK-35-3 (GBP-Related Models)
- TASK-35-8 (GBP API Client Service)

## Effort Estimate
3 hours

## Technical Notes
- Insights are available with 1-2 day delay from Google
- Consider batch sync for multiple locations
- Store raw API response in addition to parsed data for flexibility

---

*Created by SpecSystemAnalyst on 2026-03-08*
