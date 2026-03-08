# TASK-35-9: GBP Insights Sync Service

## STATUS
`To Write Tests`

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

## Comments

### ProjectManager Session (2026-03-08 22:25 GMT+2)
- **Priority:** HIGH (confirmed) - Foundation service for GBP insights data pipeline
- **Risk:** LOW (confirmed) - Standard Laravel service, external API calls delegated to GBPApiService

**Dependency Status:**
| Dependency | Status | Ready? |
|------------|--------|--------|
| TASK-35-3 (GBP-Related Models) | To Write Tests | ✅ YES (TDD pattern) |
| TASK-35-8 (GBP API Client Service) | To Write Tests | ✅ YES (TDD pattern) |

**Technical Assessment:**
- Service aggregates GBP insights from API into local storage
- Key methods: syncInsights(), getInsights(), getInsightsTrend(), getTotalMetrics(), getTopPerformingDay()
- Queue integration for background sync (dispatch SyncInsightsJob)
- 1-hour caching TTL for insights data
- Upsert handling for duplicate dates

**TDD Pattern Applied:**
- PreTester CAN design tests with mocked GBPApiService interface before dependencies complete
- Model interfaces available from TASK-35-3 PreTester phase
- API service interface available from TASK-35-8 PreTester phase
- Tests use interface-based mocking (Laravel's `mock()` helper)

**Downstream Impact:**
| Downstream Task | Purpose |
|-----------------|---------|
| TASK-35-14 (GBP API Controllers) | Exposes insights endpoints to frontend |
| TASK-35-17 (Location Management Page) | UI displays insights trends |
| Dashboard/Reporting | Insights visualization and analytics |

**Key Lesson:** Service tasks that sync external data into local storage should be HIGH priority when:
1. They enable downstream dashboards and reporting features
2. They provide data foundation for multiple UI components
3. They follow established service patterns (queue, cache, error handling)
4. Dependencies are progressing in parallel (TDD pattern enables test design)

**Status:** `To Write Tests` - PreTester can design tests with mocked service interfaces

---

*Created by SpecSystemAnalyst on 2026-03-08*
