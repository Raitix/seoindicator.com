# TEST-5-1: Database Migration - Keyword Suggestions Tables

## Task Reference
- **Task**: [TASK-5-1](../TASKS/TASK-5-1.md)
- **Specification**: [SPEC-005](../SPECIFICATIONS/SPEC-005.md)
- **Feature**: [FEAT-005](../FEATURES/FEAT-005.md)

## Overview
Tests for the database migration that creates `keyword_suggestions` and `dismissed_suggestions` tables. These tables support the keyword discovery feature, allowing users to find new keyword opportunities.

---

## Test Files

| File | Type | Description |
|------|------|-------------|
| `backend/tests/Unit/Migrations/CreateKeywordSuggestionsTableTest.php` | Unit | Migration structure validation |

---

## Test Scenarios

### keyword_suggestions Table

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| KS-001 | Table exists | N/A | `keyword_suggestions` table exists |
| KS-002 | Primary key | N/A | `id` column is auto-increment primary key |
| KS-003 | site_id column | N/A | `site_id` column exists, BIGINT UNSIGNED, NOT NULL |
| KS-004 | keyword column | N/A | `keyword` column exists, TEXT/VARCHAR, NOT NULL |
| KS-005 | source column | N/A | `source` column exists, TEXT/VARCHAR, NOT NULL |
| KS-006 | search_volume column | N/A | `search_volume` column exists, INTEGER, nullable |
| KS-007 | difficulty_score column | N/A | `difficulty_score` column exists, INTEGER, nullable |
| KS-008 | opportunity_score column | N/A | `opportunity_score` column exists, INTEGER, nullable |
| KS-009 | is_tracking column | N/A | `is_tracking` column exists, BOOLEAN, DEFAULT FALSE |
| KS-010 | is_dismissed column | N/A | `is_dismissed` column exists, BOOLEAN, DEFAULT FALSE |
| KS-011 | discovered_at column | N/A | `discovered_at` column exists, TIMESTAMP, default CURRENT_TIMESTAMP |
| KS-012 | updated_at column | N/A | `updated_at` column exists, TIMESTAMP, auto-update |
| KS-013 | Foreign key to sites | Create suggestion with invalid site_id | Fails with FK constraint error |
| KS-014 | Unique constraint (site_id, keyword) | Duplicate (site_id, keyword) pair | Fails with unique constraint error |
| KS-015 | Index on site_id | N/A | Index `idx_suggestions_site_id` exists |
| KS-016 | Index on keyword | N/A | Index `idx_suggestions_keyword` exists |
| KS-017 | Index on opportunity_score | N/A | Index `idx_suggestions_opportunity` exists (DESC) |
| KS-018 | Cascade delete | Delete site | All suggestions for site are deleted |
| KS-019 | Source values | Insert with 'suggest', 'competitor', 'related' | All valid values accepted |

### dismissed_suggestions Table

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| DS-001 | Table exists | N/A | `dismissed_suggestions` table exists |
| DS-002 | Primary key | N/A | `id` column is auto-increment primary key |
| DS-003 | site_id column | N/A | `site_id` column exists, BIGINT UNSIGNED, NOT NULL |
| DS-004 | keyword column | N/A | `keyword` column exists, TEXT/VARCHAR, NOT NULL |
| DS-005 | dismissed_at column | N/A | `dismissed_at` column exists, TIMESTAMP, default CURRENT_TIMESTAMP |
| DS-006 | Foreign key to sites | Create dismissed with invalid site_id | Fails with FK constraint error |
| DS-007 | Index on site_id | N/A | Index `idx_dismissed_site` exists |
| DS-008 | Cascade delete | Delete site | All dismissed suggestions for site are deleted |

### Migration Rollback

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| RB-001 | Rollback drops dismissed_suggestions | Rollback migration | Table does not exist |
| RB-002 | Rollback drops keyword_suggestions | Rollback migration | Table does not exist |

---

## Mock Data

### Valid keyword_suggestions Record
```php
[
    'site_id' => 1,
    'keyword' => 'best seo tools 2026',
    'source' => 'suggest',
    'search_volume' => null,
    'difficulty_score' => null,
    'opportunity_score' => 75,
    'is_tracking' => false,
    'is_dismissed' => false,
]
```

### Valid dismissed_suggestions Record
```php
[
    'site_id' => 1,
    'keyword' => 'spam keyword',
]
```

### Valid Source Values
- `suggest` - From Google Suggest API
- `competitor` - From competitor analysis (post-MVP)
- `related` - From related searches (post-MVP)

---

## Coverage Targets

| Area | Target | Notes |
|------|--------|-------|
| Table structure | 100% | All columns, types, defaults |
| Constraints | 100% | FK, unique, cascades |
| Indexes | 100% | All defined indexes |
| Rollback | 100% | Both tables dropped |

---

## Test Execution

```bash
# Run this specific test
cd backend
php artisan test --filter CreateKeywordSuggestionsTableTest

# Run all migration tests
php artisan test tests/Unit/Migrations
```

---

## Dependencies

- sites table (TASK-1-1 / FEAT-001)
- Laravel Schema facade
- RefreshDatabase trait

---

## Notes for Developer

1. **Source column**: The `source` column uses TEXT/VARCHAR (not ENUM) for flexibility. Valid values are 'suggest', 'competitor', 'related'. Application-level validation should enforce these values.

2. **Nullable fields**: `search_volume` and `difficulty_score` are nullable for MVP since no data provider exists yet. These will be populated in post-MVP.

3. **opportunity_score**: Calculated by utility (TASK-5-3). Can be null initially.

4. **Migration naming**: Use timestamp format `2026_02_26_HHMMSS_create_keyword_suggestions_tables.php`

5. **SQLite vs MySQL**: Tests use SQLite in-memory. For MySQL-specific features, use MySQL testing database.

---

## Comments

### [2026-02-26 22:48] PreTester: Test Design Complete
- Created test documentation
- Created unit test file: `backend/tests/Unit/Migrations/CreateKeywordSuggestionsTableTest.php`
- 19 test scenarios for keyword_suggestions table
- 8 test scenarios for dismissed_suggestions table
- 2 rollback test scenarios
- Status: Tests Coverage In Progress → To Develop
