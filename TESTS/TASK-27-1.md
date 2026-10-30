# TESTS/TASK-27-1.md — Admin Database Tables Migration Tests

## Task Reference
- **Task:** TASK-27-1
- **Specification:** SPEC-027: Admin Dashboard (Ops + Usage)
- **Type:** Unit Test (Database Migration)
- **Created:** 2026-03-02

## Overview

Tests for database migration creating `admin_activity_logs` and `system_metrics_cache` tables. These tables support the admin dashboard's activity auditing and metrics caching functionality.

## Test Files

| File | Type | Tests | Assertions |
|------|------|-------|------------|
| `backend/tests/Unit/Migrations/CreateAdminTablesMigrationTest.php` | Unit | 50+ | 80+ |

## Test Scenarios

### Category 1: admin_activity_logs Table Structure (AL-001 to AL-020)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| AL-001 | Table exists after migration | Run migration | Table `admin_activity_logs` exists |
| AL-002 | Primary key column | Check `id` column | bigIncrements, auto-increment, primary key |
| AL-003 | admin_user_id column | Check column | bigInteger unsigned, nullable |
| AL-004 | action column | Check column | string(100), not null |
| AL-005 | target_type column | Check column | string(100), nullable |
| AL-006 | target_id column | Check column | bigInteger unsigned, nullable |
| AL-007 | metadata column | Check column | json, nullable |
| AL-008 | ip_address column | Check column | string(45), nullable (IPv6 support) |
| AL-009 | user_agent column | Check column | text, nullable |
| AL-010 | created_at column | Check column | timestamp, nullable |
| AL-011 | Foreign key to users | Check FK constraint | admin_user_id → users.id ON DELETE SET NULL |
| AL-012 | Index on admin_user_id | Check index | Index exists on admin_user_id |
| AL-013 | Index on action | Check index | Index exists on action |
| AL-014 | Index on created_at | Check index | Index exists on created_at |
| AL-015 | Composite index (admin_user_id, created_at) | Check composite | Composite index exists |
| AL-016 | Insert valid record | Valid data | Record inserted successfully |
| AL-017 | Insert with null admin_user_id | Null admin_user_id | Record inserted (nullable) |
| AL-018 | Insert with null optional fields | Null target_type, target_id, metadata | Record inserted successfully |
| AL-019 | JSON metadata storage | JSON data | JSON stored and retrievable |
| AL-020 | IPv6 address storage | IPv6 address string | Stored correctly in ip_address |

### Category 2: system_metrics_cache Table Structure (SM-001 to SM-018)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| SM-001 | Table exists after migration | Run migration | Table `system_metrics_cache` exists |
| SM-002 | Primary key column | Check `id` column | bigIncrements, auto-increment, primary key |
| SM-003 | metric_key column | Check column | string(100), unique, not null |
| SM-004 | metric_value column | Check column | decimal(15,4), not null |
| SM-005 | metadata column | Check column | json, nullable |
| SM-006 | cached_at column | Check column | timestamp, not null, default current |
| SM-007 | expires_at column | Check column | timestamp, nullable |
| SM-008 | Unique index on metric_key | Check unique constraint | Unique constraint on metric_key |
| SM-009 | Index on expires_at | Check index | Index exists on expires_at |
| SM-010 | Insert valid metric | Valid data | Record inserted successfully |
| SM-011 | Unique constraint violation | Duplicate metric_key | Error: duplicate key violation |
| SM-012 | Insert with null expires_at | Null expires_at | Record inserted (no expiry) |
| SM-013 | Insert with null metadata | Null metadata | Record inserted successfully |
| SM-014 | Decimal precision | Value with 4 decimal places | Stored correctly |
| SM-015 | Large metric_value | Value near max decimal(15,4) | Stored correctly |
| SM-016 | JSON metadata storage | Complex JSON data | JSON stored and retrievable |
| SM-017 | Metric key length | 100 char key | Stored correctly |
| SM-018 | Metric key exceeds max length | 101 char key | Truncated or error (depends on DB) |

### Category 3: Migration Reversibility (MR-001 to MR-010)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| MR-001 | Down method drops admin_activity_logs | Run down migration | Table `admin_activity_logs` does not exist |
| MR-002 | Down method drops system_metrics_cache | Run down migration | Table `system_metrics_cache` does not exist |
| MR-003 | Re-run migration after rollback | Migrate, rollback, migrate | Both tables recreated successfully |
| MR-004 | Data preserved in other tables | Migrate, add data to users, rollback | Users data preserved |
| MR-005 | Foreign key dropped on rollback | Run down migration | FK constraint removed |
| MR-006 | Indexes dropped on rollback | Run down migration | All indexes removed |
| MR-007 | Migration is idempotent | Run migration twice | No errors, tables exist |
| MR-008 | Fresh migration works | migrate:fresh | Tables created cleanly |
| MR-009 | Foreign key constraint enforced | Insert with invalid admin_user_id | FK constraint error (or SET NULL) |
| MR-010 | Rollback order correct | Run rollback | Tables dropped in correct order |

### Category 4: Data Integrity (DI-001 to DI-008)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| DI-001 | Insert complete activity log | All fields populated | Record inserted with all values |
| DI-002 | Query by admin_user_id | Filter by user | Efficient index usage |
| DI-003 | Query by action | Filter by action | Efficient index usage |
| DI-004 | Query by date range | Filter by created_at range | Efficient index usage |
| DI-005 | Expired metrics query | Filter expires_at < now | Efficient index usage |
| DI-006 | Upsert metric by key | INSERT ON DUPLICATE KEY | Single record per metric_key |
| DI-007 | Bulk insert metrics | Multiple metric records | All inserted with unique keys |
| DI-008 | Cascade behavior on user delete | Delete user with logs | SET NULL on admin_user_id |

### Category 5: Security & Edge Cases (SE-001 to SE-010)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| SE-001 | SQL injection in action column | Malicious SQL string | Stored as literal string (escaped) |
| SE-002 | SQL injection in metadata JSON | Malicious JSON | Stored as literal JSON (escaped) |
| SE-003 | XSS in user_agent column | Script tag | Stored as literal text (escaped) |
| SE-004 | Empty action column | Empty string | Stored (or validation error if not null) |
| SE-005 | Null action column | Null value | Database error (not null constraint) |
| SE-006 | Invalid JSON in metadata | Non-JSON string | Database error (JSON validation) |
| SE-007 | Very long user_agent | 10000 char string | Truncated or stored (text column) |
| SE-008 | Unicode in action | Unicode characters | Stored correctly |
| SE-009 | Negative metric_value | Negative decimal | Stored correctly |
| SE-010 | Zero metric_value | Zero value | Stored correctly |

## Mock Data

### admin_activity_logs Sample
```php
[
    'admin_user_id' => 1,
    'action' => 'user.ban',
    'target_type' => 'User',
    'target_id' => 42,
    'metadata' => json_encode(['reason' => 'Spam', 'duration' => '7d']),
    'ip_address' => '192.168.1.1',
    'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
    'created_at' => now(),
]
```

### system_metrics_cache Sample
```php
[
    'metric_key' => 'daily.active_users',
    'metric_value' => 1234.5678,
    'metadata' => json_encode(['period' => '2026-03-02', 'source' => 'analytics']),
    'cached_at' => now(),
    'expires_at' => now()->addHours(24),
]
```

## Coverage Targets

| Category | Target | Tests |
|----------|--------|-------|
| Table Structure | 100% columns | 20+ |
| Indexes | 100% indexes | 10+ |
| Foreign Keys | 100% FKs | 5+ |
| Reversibility | 100% rollback | 10+ |
| Data Integrity | Key scenarios | 8+ |
| Security | Key scenarios | 10+ |
| **Total** | **50+ tests** | **80+ assertions** |

## Test Execution

```bash
# Run all migration tests
cd backend
php artisan test --filter=CreateAdminTablesMigrationTest

# Run with coverage
php artisan test --filter=CreateAdminTablesMigrationTest --coverage

# Run specific category
php artisan test --filter="test_al"  # admin_activity_logs tests
php artisan test --filter="test_sm"  # system_metrics_cache tests
php artisan test --filter="test_mr"  # Migration reversibility tests
```

## Dependencies

- Laravel testing framework
- PHPUnit
- In-memory SQLite (for unit tests)
- MySQL/MariaDB (for FK constraint tests)

## Notes for Developer

1. **SQLite Limitations:** SQLite doesn't enforce foreign key constraints the same way as MySQL. Use `markTestSkipped()` for FK-specific tests when running on SQLite.

2. **JSON Column:** Ensure JSON column type is used (not TEXT with JSON validation). Laravel's `json()` method creates proper JSON columns on MySQL.

3. **IPv6 Support:** `ip_address VARCHAR(45)` is standard for IPv6 (max length is 45 chars).

4. **Decimal Precision:** `decimal(15,4)` allows values up to 999,999,999,999.9999 - sufficient for most metrics.

5. **Foreign Key Behavior:** `ON DELETE SET NULL` allows logs to persist when admin user is deleted, with admin_user_id becoming NULL.

## Comments

### 2026-03-02 - PreTester
- Test documentation created
- Designed 50+ test scenarios covering all acceptance criteria
- Migration tests follow existing patterns from TASK-8-3 (Data Migration tests)
- Focus on: table structure, indexes, foreign keys, reversibility, edge cases
