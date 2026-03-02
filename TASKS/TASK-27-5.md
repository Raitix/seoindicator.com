# TASK-27-5: Admin User Stats API Endpoint

## STATUS
`To Prioritize`

## PRIORITY
`High`

## RISK
`Medium`

## Parent Specification
SPEC-027: Admin Dashboard (Ops + Usage)

## Description
Create API endpoints for admin user statistics. Provides endpoints for user counts, growth metrics, recent users, and active users lists.

## Acceptance Criteria
- [ ] Controller created at `app/Http/Controllers/Api/V1/Admin/UserStatsController.php`
- [ ] GET `/api/v1/admin/users/stats` returns aggregated user stats
- [ ] GET `/api/v1/admin/users/recent` returns last 20 registered users (paginated)
- [ ] GET `/api/v1/admin/users/active` returns active users list (paginated)
- [ ] All endpoints protected by AdminAuthorizationMiddleware
- [ ] Rate limiting: 60 requests/minute
- [ ] Responses exclude PII (no emails, names masked)
- [ ] Feature tests cover all endpoints
- [ ] API documentation updated

## Dependencies
- TASK-27-2 (AdminAuthorizationMiddleware)
- TASK-27-4 (User stats aggregation queries)

## Effort Estimate
`Medium` (2-4 hours)

## Technical Notes
- Use Laravel API resources for response transformation
- Pagination with default 20 items per page
- Consider adding query parameters for date ranges

## Response Format
See TASK-27-4 for stats response format.

## Comments

---
*Created by SpecSystemAnalyst from SPEC-027*
