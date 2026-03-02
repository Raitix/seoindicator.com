# TASK-27-4: User Stats Aggregation Queries

## STATUS
`To Prioritize`

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

---
*Created by SpecSystemAnalyst from SPEC-027*
