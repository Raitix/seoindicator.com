# TASK-27-1: Admin Database Tables Migration

## STATUS
`To Develop`

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

### 2026-03-02 - ProjectManager
**Prioritization Complete**
- **Priority: High** (confirmed) - Foundation task for SPEC-027, enables all 18 other tasks
- **Risk: Medium** (confirmed) - Non-destructive migration (add tables only), standard Laravel Schema Builder
- **Dependencies: None** - Can proceed immediately, no blockers
- **Execution Order:** First task in SPEC-027 pipeline (Phase 1 Backend Infrastructure)
- **Enables:** TASK-27-2 (AdminAuthorizationMiddleware), TASK-27-3 (AdminStatsService)

**Technical Notes Verified:**
- `admin_activity_logs` table: Foreign key to users, indexes on admin_user_id/action/created_at
- `system_metrics_cache` table: Unique metric_key, expires_at for cache invalidation
- IPv6 support via VARCHAR(45) for ip_address
- JSON columns for flexible metadata storage

**Handoff:** Ready for PreTester to design migration tests (table structure, indexes, foreign keys, reversibility)

### 2026-03-02 - PreTester
**Test Design Complete**

**Test Files Created:**
- `TESTS/TASK-27-1.md` - Test documentation with 50+ scenarios
- `backend/tests/Unit/Migrations/CreateAdminTablesMigrationTest.php` - Unit test with 80+ assertions

**Test Coverage:**
- **AL-001 to AL-020:** `admin_activity_logs` table structure, columns, indexes, foreign keys
- **SM-001 to SM-018:** `system_metrics_cache` table structure, unique constraints, decimal precision
- **MR-001 to MR-010:** Migration reversibility, rollback, re-run scenarios
- **DI-001 to DI-008:** Data integrity, queries, cascade behavior
- **SE-001 to SE-010:** Security & edge cases (SQL injection, XSS, unicode, validation)

**Key Test Patterns:**
- SQLite-compatible (marks MySQL-specific tests as skipped)
- Foreign key constraint tests (MySQL/MariaDB only)
- JSON column validation (MySQL enforces, SQLite does not)
- IPv6 address storage validation
- Decimal precision (15,4) validation

**Handoff:** Ready for Developer to implement migration. Run tests with:
```bash
cd backend
php artisan test --filter=CreateAdminTablesMigrationTest
```

---
*Created by SpecSystemAnalyst from SPEC-027*
