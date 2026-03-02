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

### 2026-03-02 - ProjectManager
**Prioritization Complete**

- **Priority: High** (confirmed) - Core admin dashboard API endpoint, user stats are essential for ops visibility
- **Risk: Medium** (confirmed) - New API endpoints, non-destructive, PII protection required
- **Dependencies:**
  - TASK-27-2 (AdminAuthorizationMiddleware): `To Develop` ✅ In pipeline
  - TASK-27-4 (User stats aggregation queries): `To Write Tests` ✅ Already prioritized

**Execution Order:** Phase 2 of SPEC-027
1. TASK-27-1 (Testing Locally) → Foundation
2. TASK-27-2 (To Develop) → Security layer
3. TASK-27-3 (Prioritization In Progress) → Caching service
4. TASK-27-4 (To Write Tests) → Data aggregation
5. TASK-27-5 (This task) → API endpoint (depends on both 27-2 and 27-4)

**Technical Assessment:**
- Controller: `UserStatsController.php` with 3 endpoints
- Routes: `/api/v1/admin/users/stats`, `/recent`, `/active`
- All routes protected by AdminAuthorizationMiddleware (TASK-27-2)
- Uses aggregated data from TASK-27-4 queries
- Rate limiting: 60 req/min
- PII protection: exclude emails, mask names
- Pagination: 20 items default

**Risk Mitigation:**
- AdminAuthorizationMiddleware provides security (High-risk component)
- Rate limiting prevents abuse
- PII masking ensures privacy compliance
- Feature tests must verify authorization, rate limiting, PII protection

**Blockers:** None - Dependencies are in progress

**Handoff:** Ready for PreTester to design tests. Focus on:
- Authorization (all 3 endpoints require admin)
- Response format validation (matches TASK-27-4 spec)
- Pagination behavior
- Rate limiting verification
- PII masking verification (no emails, names masked)
- Error responses (401, 403, 429)

---
*Created by SpecSystemAnalyst from SPEC-027*
