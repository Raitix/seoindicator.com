# TASK-27-7: Usage Aggregation Service

## STATUS
`To Prioritize`

## PRIORITY
`Medium`

## RISK
`Low`

## Parent Specification
SPEC-027: Admin Dashboard (Ops + Usage)

## Description
Create service for aggregating usage metrics including request counts, token usage, and analysis counts. Provides time-based aggregation (daily, weekly, monthly).

## Acceptance Criteria
- [ ] Service class created at `app/Services/Admin/UsageAggregationService.php`
- [ ] Methods for:
  - Request counts by endpoint
  - Token usage aggregation (by model if available)
  - Analysis job counts
  - Time-based filtering (today, this week, this month)
- [ ] Integrates with RequestTrackingMiddleware data
- [ ] Integrates with existing analysis_jobs table
- [ ] Results cached via AdminStatsService
- [ ] Unit tests for all aggregation methods
- [ ] Performance optimized with proper indexes

## Dependencies
- TASK-27-3 (AdminStatsService)
- TASK-27-6 (RequestTrackingMiddleware data)
- Existing analysis_jobs table

## Effort Estimate
`Medium` (2-4 hours)

## Technical Notes
- Use database-level aggregation (GROUP BY, SUM)
- Pre-aggregate daily stats via scheduled job
- Consider materialized views for complex queries

## Response Format
```json
{
  "requests": {
    "total": 156789,
    "today": 2340,
    "thisWeek": 15680,
    "thisMonth": 48920
  },
  "tokens": {
    "total": 12500000,
    "today": 156000,
    "thisWeek": 890000,
    "thisMonth": 3200000,
    "byModel": {
      "gpt-4": 5000000,
      "gpt-3.5-turbo": 6000000,
      "claude-3": 1500000
    }
  },
  "analyses": {
    "total": 12340,
    "today": 156,
    "thisWeek": 890,
    "thisMonth": 3450
  }
}
```

## Comments

---
*Created by SpecSystemAnalyst from SPEC-027*
