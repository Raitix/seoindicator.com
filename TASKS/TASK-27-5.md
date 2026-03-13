# TASK-27-5: Admin User Stats API Endpoint

## STATUS
`To Write Tests`

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

### 2026-03-13 - ProjectManager
**Prioritization Complete**

- **Priority: High** (confirmed) - API endpoint essential for Admin Dashboard user metrics, exposes user statistics to frontend
- **Risk: Medium** (confirmed) - Standard Laravel controller, delegates to services, no external APIs

**Dependency Status:**
- TASK-27-2 (AdminAuthorizationMiddleware): **Done** ✅ - Middleware exists, protects admin routes
- TASK-27-4 (User stats aggregation): `To Write Tests` - NOT YET IMPLEMENTED ❌

**TDD Pattern Applied:**
- PreTester CAN design tests with mocked service interfaces before dependencies complete
- Controller tests use Laravel HTTP testing with `$this->mock()` for service mocks
- Test categories: route registration, auth middleware, authorization, rate limiting, validation, response format, pagination

**Key Implementation Details:**
- Controller: `app/Http/Controllers/Api/V1/Admin/UserStatsController.php`
- Endpoints:
  - GET `/api/v1/admin/users/stats` - aggregated user stats
  - GET `/api/v1/admin/users/recent` - last 20 registered users (paginated)
  - GET `/api/v1/admin/users/active` - active users list (paginated)
- Protection: AdminAuthorizationMiddleware
- Rate limiting: 60 requests/minute
- PII protection: Exclude emails, mask names

**Downstream Impact:**
- Enables Admin Dashboard "User Growth" widget
- Required for user analytics visualization
- All user-related frontend components depend on these endpoints

**Execution Order:**
- Tests designed NOW with mocked service interfaces (parallel with TASK-27-3/27-4)
- Implementation waits for TASK-27-4 to reach `To Test Locally`
- Integration tests run when both layers ready

**Handoff:** Ready for PreTester to design tests for Admin User Stats API endpoints.

---
*Created by SpecSystemAnalyst from SPEC-027*
