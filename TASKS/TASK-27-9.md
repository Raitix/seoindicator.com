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

### 2026-03-02 - ProjectManager
**Prioritization Complete**

- **Priority: High** (confirmed) - Essential for Admin Dashboard MVP, provides critical billing/revenue insights
- **Risk: Medium** (confirmed) - Aggregation logic only, no external APIs, integrates with existing CreditService

**Dependency Analysis:**

| Dependency | Status | Impact |
|------------|--------|--------|
| FEAT-026 (Credits/Billing) | Partial | Task allows stub methods if not available |
| TASK-26-1 (Migrations) | `In Development` | Tables not created yet - PARTIAL BLOCKER |
| TASK-26-5 (CreditService) | `Done` ✅ | Core service exists, can be used/mocked |
| TASK-27-3 (AdminStatsService) | `Prioritization In Progress` | Caching layer - integration point |

**Blocker Mitigation:**
- Task spec allows: "If FEAT-026 not available, create stub methods that return placeholder data"
- TDD approach: PreTester CAN design tests with mocked Credit model interface
- Implementation waits for TASK-26-1 to reach `To Test Locally` (tables must exist)

**Phase 4 Context (Credit & Billing):**
- TASK-27-9 (this task): Credit/balance aggregation service
- TASK-27-10: Admin credits API endpoints - depends on this task

**Service Methods to Implement:**
1. `getTotalBalanceHeld(): float` - Sum of all user balances
2. `getAverageBalancePerUser(): float` - Average balance across users with credits
3. `getUsersWithBalanceCount(): int` - Count of users with balance > 0
4. `getLowBalanceUserCount(float $threshold): int` - Users below threshold (configurable)
5. `getTotalSpend(string $period): float` - All time/today/week/month spend
6. `getRevenueMetrics(): array` - Revenue breakdown by period

**Integration Points:**
- `credit_transactions` table from TASK-26-1 (table structure documented)
- `credits` table from TASK-26-1 (balance tracking)
- `AdminStatsService` from TASK-27-3 (caching layer, optional)
- `CreditService` from TASK-26-5 (balance queries, already Done)

**Response Format (from spec):**
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

**Test Coverage Requirements for PreTester:**
- Mock Credit and CreditTransaction models (tables may not exist yet)
- Test aggregation queries with mock data
- Test period calculations (today/week/month)
- Test configurable low balance threshold
- Test graceful handling when tables don't exist (stub data)
- Test caching via AdminStatsService (if available)

**Execution Order:**
- Phase 1-3 tasks first (User Stats, Usage Metrics)
- TASK-26-1 must reach `To Test Locally` before Developer can implement
- TASK-27-9 → TASK-27-10 (API endpoints depend on this service)

**Handoff:** Ready for PreTester to design tests with TDD approach. Tests can use mocked model interfaces until tables exist.

---
*Created by SpecSystemAnalyst from SPEC-027*
