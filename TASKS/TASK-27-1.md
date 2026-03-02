# TASK-27-1: Admin Database Tables Migration

## STATUS
`To Prioritize`

## PRIORITY
`High`

## RISK
`Medium`

## Parent Specification
SPEC-027: Admin Dashboard (Ops + Usage)

## Description
Create database migrations for `admin_activity_logs` and `system_metrics_cache` tables. These tables support the admin dashboard's activity auditing and metrics caching functionality.

## Acceptance Criteria
- [ ] Migration creates `admin_activity_logs` table with all columns:
  - id, admin_user_id, action, target_type, target_id, metadata (JSON)
  - ip_address, user_agent, created_at
  - Foreign key to users table
  - Indexes on admin_user_id, action, created_at
- [ ] Migration creates `system_metrics_cache` table with all columns:
  - id, metric_key (unique), metric_value, metadata (JSON)
  - cached_at, expires_at
  - Indexes on metric_key, expires_at
- [ ] Migration is reversible (down method drops tables)
- [ ] Migration runs successfully on existing database
- [ ] Unit test verifies table structure

## Dependencies
- None (foundation task)

## Effort Estimate
`Small` (1-2 hours)

## Technical Notes
- Use Laravel Schema Builder
- JSON columns for flexible metadata storage
- Consider using bigIncrements for ID columns
- ip_address VARCHAR(45) supports IPv6

## Comments

---
*Created by SpecSystemAnalyst from SPEC-027*
