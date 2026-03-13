# TASK-27-4: User Stats Aggregation Queries

## STATUS
`To Write Tests`

## PRIORITY
`High`

## RISK
`Low`

## Parent Specification
SPEC-027: Admin Dashboard (Ops + Usage)

## Description
Implement efficient database queries for user statistics aggregation. Provides counts for total users, active users, new registrations, and growth metrics.

## Acceptance Criteria
- [ ] Repository or service methods for:
  - Total user count
  - Active users count (logged in within 30 days)
  - New registrations: today, this week, this month
  - Growth rate calculation (percentage change)
  - Users by plan breakdown
- [ ] All queries optimized with proper indexes
- [ ] Queries use Eloquent or Query Builder efficiently
- [ ] Results cached via AdminStatsService
- [ ] Unit tests verify calculation accuracy
- [ ] Performance test confirms queries complete < 100ms

## Dependencies
- TASK-27-3 (AdminStatsService for caching)

## Effort Estimate
`Medium` (2-4 hours)

## Technical Notes
- Use Carbon for date calculations
- Consider using database-level aggregation (GROUP BY)
- Index users.created_at and users.last_login_at
- Handle edge cases: division by zero for growth rates

## Response Format
```json
{
  "total": 1250,
  "active": 890,
  "newThisMonth": 156,
  "newThisWeek": 42,
  "newToday": 8,
  "growthRate": {
    "monthly": 14.2,
    "weekly": 5.8,
    "daily": 1.2
  },
  "byPlan": {
    "free": 1000,
    "pro": 200,
    "enterprise": 50
  }
}
```

## Comments

### 2026-03-13 - ProjectManager
**Prioritization Complete**

- **Priority: High** (confirmed) - Core analytics for Admin Dashboard, required for user growth metrics
- **Risk: Low** (confirmed) - Read-only database queries, no external APIs, no data modifications
- **Dependencies:** TASK-27-3 (AdminStatsService for caching) — IN PROGRESS ⏳
  - Status: `To Write Tests` (already prioritized, awaiting PreTester)
  - This task can proceed to test design phase in parallel
  - PreTester should design tests using mock interface for caching layer

**Downstream Impact:**
- Enables TASK-27-5 (User Stats API) for dashboard endpoints
- Required for Admin Dashboard "User Growth" widget
- All user-related analytics depend on these queries

**TDD Pattern:** PreTester can design tests immediately using:
- Mock AdminStatsService interface for caching
- Test query accuracy with test fixtures (known user counts)
- Test growth rate calculations (edge cases: zero divisions)
- Test performance with seeded database (target <100ms)

**Key Implementation Details:**
- Repository methods: `getTotalUserCount()`, `getActiveUserCount()`, `getNewRegistrations()`, `getGrowthRate()`, `getUsersByPlan()`
- Use Eloquent or Query Builder with proper indexes
- Index: `users.created_at`, `users.last_login_at`
- Cache via AdminStatsService with namespace `admin:stats:users`
- Default cache TTL: 5 minutes (inherited from service)

**Handoff:** Ready for PreTester to design tests for user stats aggregation queries.

---
*Created by SpecSystemAnalyst from SPEC-027*
