# TEST-18-1: Database Migration - seo_analyses Table

## Task Reference
- **Task:** TASK-18-1
- **Feature:** FEAT-018 (Legacy 17-Indicator SEO Analysis Core Engine)
- **Status:** Tests Coverage In Progress

---

## Overview
Tests for the `seo_analyses` table migration. Verifies schema structure, column definitions, indexes, foreign key constraints, and rollback functionality.

---

## Test Files

| File | Type | Tests | Assertions |
|------|------|-------|------------|
| `backend/tests/Feature/Migrations/CreateSeoAnalysesTableTest.php` | Integration | 25+ | 60+ |

---

## Test Scenarios

### Database Schema Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| DB-01 | Table exists | Run migration | `seo_analyses` table created |
| DB-02 | Primary key | Inspect `id` column | BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY |
| DB-03 | user_id column | Inspect column | BIGINT UNSIGNED NULL, FK to users.id |
| DB-04 | session_id column | Inspect column | VARCHAR(64) NULL |
| DB-05 | domain column | Inspect column | VARCHAR(500) NOT NULL |
| DB-06 | normalized_url column | Inspect column | VARCHAR(500) NOT NULL |
| DB-07 | overall_score column | Inspect column | TINYINT UNSIGNED NOT NULL |
| DB-08 | status column | Inspect column | ENUM('pending', 'running', 'completed', 'failed') DEFAULT 'pending' |
| DB-09 | error_message column | Inspect column | TEXT NULL |
| DB-10 | analysis_data column | Inspect column | JSON NOT NULL |
| DB-11 | checked_at column | Inspect column | TIMESTAMP NULL |
| DB-12 | expires_at column | Inspect column | TIMESTAMP NULL |
| DB-13 | Timestamps | Inspect columns | created_at, updated_at TIMESTAMP |

### Index Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| IX-01 | user_domain index | Check index | INDEX on (user_id, domain) |
| IX-02 | session_domain index | Check index | INDEX on (session_id, domain) |
| IX-03 | domain_url index | Check index | INDEX on (normalized_url) |
| IX-04 | status index | Check index | INDEX on status |
| IX-05 | expires_at index | Check index | INDEX on expires_at |

### Foreign Key Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| FK-01 | user_id foreign key | Check constraint | FK to users.id ON DELETE CASCADE |
| FK-02 | Cascade delete user | Delete user record | Related analyses deleted |

### Enum Validation Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| EN-01 | Valid status values | Insert with 'pending' | Success |
| EN-02 | Valid status values | Insert with 'running' | Success |
| EN-03 | Valid status values | Insert with 'completed' | Success |
| EN-04 | Valid status values | Insert with 'failed' | Success |
| EN-05 | Invalid status value | Insert with 'invalid' | SQL error / rejected |

### Migration Rollback Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| RB-01 | Rollback migration | `php artisan migrate:rollback` | Table dropped |
| RB-02 | Re-run migration | `php artisan migrate` | Table recreated |

### Model Integration Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| MI-01 | Create analysis | Insert valid data | Record created with ID |
| MI-02 | JSON casting | Store JSON in analysis_data | JSON properly encoded/decoded |
| MI-03 | Nullable user_id | Insert without user_id | Success (anonymous analysis) |
| MI-04 | Nullable session_id | Insert without session_id | Success |
| MI-05 | Score range valid | Insert score 0-100 | Success |
| MI-06 | Score range invalid | Insert score 101 | Error / rejected |
| MI-07 | Score range invalid | Insert score -1 | Error / rejected |
| MI-08 | Domain required | Insert without domain | Error / rejected |

### Security Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| SC-01 | SQL injection in domain | Domain: `example.com'; DROP TABLE seo_analyses;--` | Sanitized / rejected |
| SC-02 | SQL injection in error_message | Malicious SQL in error_message | Sanitized / safe |
| SC-03 | XSS in domain | Domain: `<script>alert(1)</script>` | Sanitized / rejected |
| SC-04 | Long domain handling | Domain: 600 chars | Truncated or error (max 500) |
| SC-05 | Long normalized_url | URL: 600 chars | Truncated or error (max 500) |

### JSON Column Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| JS-01 | Valid JSON object | `{"score": 78, "indicators": []}` | Stored correctly |
| JS-02 | Empty JSON object | `{}` | Stored correctly |
| JS-03 | Nested JSON | `{"level1": {"level2": {"level3": "value"}}}` | Stored correctly |
| JS-04 | JSON array | `[{"id": "url", "score": 3}]` | Stored correctly |
| JS-05 | Invalid JSON | `not json` | Error / rejected |

### Edge Cases

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| EC-01 | Empty analysis_data | `{}` | Success |
| EC-02 | Large JSON blob | 100KB JSON in analysis_data | Success (if within limits) |
| EC-03 | Unicode domain | Domain: `例え.jp` | Handled correctly |
| EC-04 | Null checked_at | Insert without checked_at | Success (nullable) |
| EC-05 | Future expires_at | expires_at: +24 hours | Success |
| EC-06 | Past expires_at | expires_at: -1 hour | Success (business logic handles expiry) |

---

## Mock Data

### Valid Analysis Record
```json
{
  "user_id": 1,
  "session_id": null,
  "domain": "example.com",
  "normalized_url": "https://example.com",
  "overall_score": 78,
  "status": "completed",
  "error_message": null,
  "analysis_data": {
    "overallScore": 78,
    "categoryScores": {
      "content": 82,
      "technical": 75,
      "accessibility": 70,
      "performance": 85
    },
    "indicators": []
  },
  "checked_at": "2026-02-28 12:00:00",
  "expires_at": "2026-03-01 12:00:00"
}
```

### Anonymous Analysis Record
```json
{
  "user_id": null,
  "session_id": "abc123def456",
  "domain": "example.org",
  "normalized_url": "https://example.org",
  "overall_score": 65,
  "status": "completed",
  "error_message": null,
  "analysis_data": {},
  "checked_at": "2026-02-28 12:00:00",
  "expires_at": "2026-03-01 12:00:00"
}
```

---

## Coverage Targets

| Category | Target | Description |
|----------|--------|-------------|
| Schema Structure | 100% | All columns, types, defaults |
| Indexes | 100% | All indexes present |
| Foreign Keys | 100% | FK constraints and cascade |
| Enum Values | 100% | All status values |
| Rollback | 100% | Migration rollback works |
| Security | 100% | SQL injection, XSS, limits |
| Edge Cases | 90% | Boundary conditions |

---

## Test Execution

```bash
# Run specific test file
php artisan test --filter=CreateSeoAnalysesTableTest

# Run with coverage
php artisan test --filter=CreateSeoAnalysesTableTest --coverage

# Run all migration tests
php artisan test --filter=Migrations
```

---

## Dependencies
- Laravel migrations system
- users table (for FK constraint)
- SQLite/MySQL database connection

---

## Notes for Developer
- Migration should use proper naming convention: `YYYY_MM_DD_HHMMSS_create_seo_analyses_table.php`
- JSON column should use Laravel's native JSON support
- Status enum values must match specification exactly
- Indexes are critical for performance on large datasets
- Consider adding composite indexes based on query patterns

---

## Comments

### [2026-02-28] PreTester
- Test documentation created
- 35+ test scenarios covering schema, indexes, constraints, security
- Ready for test implementation
