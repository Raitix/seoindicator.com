# TASK-27-8: Admin Usage API Endpoints

## STATUS
`To Prioritize`

## PRIORITY
`Medium`

## RISK
`Medium`

## Parent Specification
SPEC-027: Admin Dashboard (Ops + Usage)

## Description
Create API endpoints for admin usage metrics. Provides overview, request breakdowns, token statistics, and usage trends.

## Acceptance Criteria
- [ ] Controller created at `app/Http/Controllers/Api/V1/Admin/UsageController.php`
- [ ] GET `/api/v1/admin/usage/overview` returns aggregated usage stats
- [ ] GET `/api/v1/admin/usage/requests` returns request counts by endpoint
- [ ] GET `/api/v1/admin/usage/tokens` returns token usage by model
- [ ] GET `/api/v1/admin/usage/trends` returns usage trends (time series)
- [ ] All endpoints protected by AdminAuthorizationMiddleware
- [ ] Rate limiting: 60 requests/minute
- [ ] Optional query parameters: start_date, end_date, granularity
- [ ] Feature tests cover all endpoints
- [ ] API documentation updated

## Dependencies
- TASK-27-2 (AdminAuthorizationMiddleware)
- TASK-27-7 (UsageAggregationService)

## Effort Estimate
`Medium` (2-4 hours)

## Technical Notes
- Use Laravel API resources for transformation
- Support date range filtering
- Consider adding export functionality (CSV) in future

## Comments

---
*Created by SpecSystemAnalyst from SPEC-027*
