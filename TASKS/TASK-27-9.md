# TASK-27-9: Credit/Balance Aggregation Service

## STATUS
`To Prioritize`

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

---
*Created by SpecSystemAnalyst from SPEC-027*
