# TASK-27-9: Credit/Balance Aggregation Service

## STATUS
`To Write Tests`

## PRIORITY
`High`

## RISK
`Medium`

## Parent Specification
SPEC-027: Admin Dashboard (Ops + Usage)

## Description
Create service for aggregating credit balances, spending, and revenue metrics. Integrates with the credits/billing system from FEAT-026.

## Acceptance Criteria
- [ ] Service class created at `app/Services/Admin/CreditAggregationService.php`
- [ ] Methods for:
  - Total balance held across all users
  - Average balance per user
  - Users with balance count
  - Low balance user count (threshold configurable)
  - Total spend (all time, today, this week, this month)
  - Revenue metrics
- [ ] Integrates with credit_transactions table from FEAT-026
- [ ] Results cached via AdminStatsService
- [ ] Unit tests for all aggregation methods
- [ ] Handles missing FEAT-026 tables gracefully

## Dependencies
- FEAT-026 (Credits/Billing system) - CRITICAL
- TASK-27-3 (AdminStatsService)

## Effort Estimate
`Medium` (2-4 hours)

## Technical Notes
- This task depends on FEAT-026 being completed first
- If FEAT-026 not available, create stub methods that return placeholder data
- Use database transactions for consistency
- Consider real-time balance calculation vs cached

## Response Format
```json
{
  "balances": {
    "totalHeld": 15678.50,
    "averagePerUser": 12.54,
    "usersWithBalance": 890,
    "lowBalanceCount": 234
  },
  "spend": {
    "total": 45678.90,
    "today": 567.80,
    "thisWeek": 3456.70,
    "thisMonth": 12345.60
  },
  "revenue": {
    "total": 78900.00,
    "thisMonth": 15600.00,
    "thisWeek": 3900.00,
    "today": 780.00
  }
}
```

## Comments

### 2026-03-13 - ProjectManager
**Prioritization Complete**

- **Priority: High** (confirmed) - Essential for Admin Dashboard MVP billing/revenue insights
- **Risk: Medium** (confirmed) - Aggregation logic only, no external APIs

**Dependency Analysis:**
| Dependency | Status | Notes |
|------------|--------|-------|
| FEAT-026 (Credits/Billing) | Partial | Tables NOT created yet |
| TASK-26-1 (Migrations) | `To Develop` | `credits`/`credit_transactions` tables PENDING ❌ |
| TASK-26-5 (CreditService) | `Done` | Service exists, works with mocked DB ✅ |
| TASK-27-3 (AdminStatsService) | `To Write Tests` | Caching layer progressing ✅ |

**Critical Finding:**
- `credits` and `credit_transactions` tables do NOT exist in production
- Migrations are created but PENDING (not run yet)
- CreditService was tested with in-memory SQLite, not actual tables

**Mitigation (from Technical Notes):**
- Task explicitly allows: "If FEAT-026 not available, create stub methods that return placeholder data"
- PreTester CAN design tests with mocked Credit/CreditTransaction model interfaces
- Implementation will use stub methods returning placeholder data initially
- When TASK-26-1 completes, stubs replaced with real aggregation queries

**TDD Pattern Applied:**
- Tests use `Credit::mock()` and `CreditTransaction::mock()` for interface-based testing
- Test all aggregation methods with sample data structures
- Mock database queries for balance/spend/revenue calculations
- Implementation creates service with stub methods, swaps to real queries when tables exist

**Key Methods for PreTester:**
- `getTotalBalanceHeld(): float`
- `getAverageBalancePerUser(): float`
- `getUsersWithBalanceCount(): int`
- `getLowBalanceUserCount(float $threshold): int`
- `getTotalSpend(string $period): float`
- `getRevenueMetrics(): array`

**Downstream Impact:**
- Enables TASK-27-10 (Credits Stats API) for dashboard endpoints
- Provides billing insights for admin dashboard overview
- Integration with AdminStatsService caching layer

**Handoff:** Ready for PreTester to design tests using TDD with mocked model interfaces.

---
*Created by SpecSystemAnalyst from SPEC-027*
