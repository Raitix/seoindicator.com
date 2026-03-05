# TEST-33-1: Add Visibility Columns to Audits Table

## Task Reference
- **Task:** TASK-33-1
- **Specification:** SPEC-33 (Public SEO Audit Reports & Embeddable Score Widget)
- **Feature:** FEAT-033

## Overview
Unit tests for database migration that adds visibility columns to the audits table. Tests verify column structure, defaults, indexes, model integration, and migration reversibility.

## Test Files

| File | Location | Purpose |
|------|----------|---------|
| `AddVisibilityToAuditsMigrationTest.php` | `backend/tests/Unit/Migrations/` | Migration structure tests |
| `AuditVisibilityTest.php` | `backend/tests/Unit/Models/` | Model integration tests |

## Test Scenarios

### Migration Structure Tests (MST)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| MST-001 | `visibility` column exists | Query column | Column exists with ENUM type |
| MST-002 | `visibility` has correct values | Check ENUM values | ['private', 'public'] |
| MST-003 | `visibility` default is 'private' | Query default value | 'private' |
| MST-004 | `public_hash` column exists | Query column | VARCHAR(32) NULL column |
| MST-005 | `public_hash` is nullable | Insert audit without hash | Success, NULL stored |
| MST-006 | `public_hash` has UNIQUE constraint | Insert duplicate hash | SQLException on second insert |
| MST-007 | `public_hash_created_at` column exists | Query column | TIMESTAMP NULL column |
| MST-008 | `public_hash_created_at` is nullable | Insert audit without timestamp | Success, NULL stored |
| MST-009 | Index on `public_hash` exists | Query indexes | Index `idx_public_hash` exists |

### Migration Reversibility Tests (MRT)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| MRT-001 | Down method removes visibility column | Rollback migration | Column does not exist |
| MRT-002 | Down method removes public_hash column | Rollback migration | Column does not exist |
| MRT-003 | Down method removes public_hash_created_at | Rollback migration | Column does not exist |
| MRT-004 | Down method removes public_hash index | Rollback migration | Index does not exist |
| MRT-005 | Re-migrate after rollback | Run migration again | All columns recreated |

### Data Integrity Tests (DIT)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| DIT-001 | Existing audits get 'private' default | Migrate with existing audits | All audits have visibility='private' |
| DIT-002 | Existing audits have NULL public_hash | Migrate with existing audits | All audits have public_hash=NULL |
| DIT-003 | New audit without visibility specified | Create audit | visibility='private' (default) |
| DIT-004 | New audit with visibility='public' | Create with visibility='public' | Stored correctly |
| DIT-005 | New audit with public_hash | Create with hash | Stored correctly |
| DIT-006 | Duplicate public_hash rejected | Insert same hash twice | SQLException (UNIQUE constraint) |

### Model Integration Tests (MIT)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| MIT-001 | Audit model has visibility fillable | Check $fillable array | 'visibility' in fillable |
| MIT-002 | Audit model has public_hash fillable | Check $fillable array | 'public_hash' in fillable |
| MIT-003 | Audit model has public_hash_created_at fillable | Check $fillable array | 'public_hash_created_at' in fillable |
| MIT-004 | Audit model casts visibility as enum | Check $casts array | visibility cast to string/enum |
| MIT-005 | Audit model casts public_hash_created_at as datetime | Check $casts array | public_hash_created_at cast to datetime |
| MIT-006 | Create audit with all new fields | Model::create() | Audit persisted with all fields |
| MIT-007 | Update audit visibility | $audit->visibility = 'public' | Value persisted |
| MIT-008 | Update audit public_hash | $audit->public_hash = 'abc123' | Value persisted |

### Edge Cases and Security Tests (ECST)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| ECST-001 | Invalid visibility value | visibility='invalid' | SQLException (ENUM constraint) |
| ECST-002 | public_hash with 32 chars | Valid 32-char string | Stored correctly |
| ECST-003 | public_hash longer than 32 chars | 33-char string | Truncated or error (VARCHAR(32)) |
| ECST-004 | public_hash empty string | '' | Stored as empty or NULL |
| ECST-005 | public_hash with special characters | '!@#$%^&*()' | Stored correctly |
| ECST-006 | public_hash with unicode | 'αβγδε' | Stored correctly |
| ECST-007 | public_hash_created_at in future | Future timestamp | Stored correctly |
| ECST-008 | public_hash_created_at in past | Past timestamp | Stored correctly |
| ECST-009 | NULL visibility (if allowed) | NULL for visibility | Uses default 'private' |

### Performance Tests (PT)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| PT-001 | Index improves lookup by public_hash | EXPLAIN query | Index used for public_hash lookup |
| PT-002 | Migration runs in reasonable time | Run migration | < 5 seconds for 10K audits |

## Mock Data

### Existing Audit Before Migration
```php
[
    'id' => 1,
    'site_id' => 1,
    'status' => 'completed',
    'pages_crawled' => 50,
    'issues_found' => 12,
    'started_at' => '2026-03-05 10:00:00',
    'completed_at' => '2026-03-05 10:05:00',
]
```

### Audit After Migration (Default Values)
```php
[
    'id' => 1,
    'site_id' => 1,
    'status' => 'completed',
    'pages_crawled' => 50,
    'issues_found' => 12,
    'started_at' => '2026-03-05 10:00:00',
    'completed_at' => '2026-03-05 10:05:00',
    'visibility' => 'private',        // NEW: default value
    'public_hash' => null,            // NEW: nullable
    'public_hash_created_at' => null, // NEW: nullable
]
```

### Public Audit
```php
[
    'id' => 2,
    'site_id' => 1,
    'status' => 'completed',
    'pages_crawled' => 100,
    'issues_found' => 5,
    'visibility' => 'public',
    'public_hash' => 'a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6',
    'public_hash_created_at' => '2026-03-05 12:00:00',
]
```

## Coverage Targets

| Category | Target | Tests |
|----------|--------|-------|
| Migration Structure | 100% | 9 tests |
| Migration Reversibility | 100% | 5 tests |
| Data Integrity | 100% | 6 tests |
| Model Integration | 100% | 8 tests |
| Edge Cases & Security | 100% | 9 tests |
| Performance | 100% | 2 tests |
| **Total** | **100%** | **39 tests** |

## Test Execution

```bash
# Run all migration tests
php artisan test --filter=AddVisibilityToAuditsMigrationTest

# Run all model tests
php artisan test --filter=AuditVisibilityTest

# Run with coverage
php artisan test --filter="AddVisibilityToAuditsMigrationTest|AuditVisibilityTest" --coverage
```

## Dependencies
- Laravel migration system
- PHPUnit test framework
- Existing Audit model
- Database connection (SQLite for tests)

## Notes for Developer

### Migration File Location
`backend/database/migrations/2026_03_05_100000_add_visibility_to_audits_table.php`

### Migration Implementation Template
```php
Schema::table('audits', function (Blueprint $table) {
    $table->enum('visibility', ['private', 'public'])->default('private')->after('completed_at');
    $table->string('public_hash', 32)->nullable()->unique()->after('visibility');
    $table->timestamp('public_hash_created_at')->nullable()->after('public_hash');
    
    $table->index('public_hash', 'idx_public_hash');
});
```

### Model Updates Required
```php
// Add to $fillable array
protected $fillable = [
    // ... existing
    'visibility',
    'public_hash',
    'public_hash_created_at',
];

// Add to casts()
protected function casts(): array
{
    return [
        // ... existing
        'visibility' => 'string',
        'public_hash_created_at' => 'datetime',
    ];
}
```

---

## Comments

### [2026-03-05 21:43 GMT+2] PreTester - Test Design Complete

**Test Coverage Summary:**
- 39 total test scenarios designed
- 6 test categories covering all acceptance criteria
- Migration structure tests validate column definitions
- Reversibility tests ensure safe rollback
- Model integration tests verify fillable and casts

**Key Test Patterns:**
- Use SQLite in-memory database for migration tests
- Test ENUM values using Schema::getColumnType()
- Test UNIQUE constraint by attempting duplicate inserts
- Test model fillable/casts after migration runs

**Security Considerations:**
- Test invalid ENUM values are rejected
- Test public_hash length constraints (32 chars)
- Test special characters and unicode in public_hash

**Handoff to Developer:**
- Migration file location specified
- Model updates documented
- All acceptance criteria have corresponding tests
