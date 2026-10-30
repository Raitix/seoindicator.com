# TESTS/SPEC-7-1.md - Database Schema: SERP Features Tables

## Overview
Test documentation for TASK-7-1: Database schema for SERP feature tracking tables.

## Test File
`backend/tests/Feature/Migrations/CreateSerpFeaturesTablesTest.php`

## Test Summary

| Category | Tests | Status |
|----------|-------|--------|
| Table Structure (TS) | 6 | ✅ PASS |
| Column Types (CT) | 9 | ✅ PASS |
| Foreign Keys (FK) | 6 | ✅ PASS |
| Indexes (IX) | 7 | ✅ PASS |
| Migration Rollback (MR) | 3 | ✅ PASS (1 skipped on SQLite) |
| Edge Cases (EC) | 7 | ✅ PASS |
| Security (SC) | 2 | ✅ PASS |
| **Total** | **40** | **39 passed, 1 skipped** |

## Acceptance Criteria Coverage

| Criterion | Test Coverage | Status |
|-----------|---------------|--------|
| `serp_features` table created with all required columns | TS-001, TS-003, CT-001 to CT-007 | ✅ |
| `serp_feature_history` table created with all required columns | TS-002, TS-004, CT-008, CT-009 | ✅ |
| Foreign key constraints properly defined | FK-001 to FK-006 | ✅ |
| Indexes created for efficient queries | IX-001 to IX-007 | ✅ |
| Migration file created and tested | All tests | ✅ |
| Rollback migration created | MR-001 to MR-003 | ✅ |

## Test Details

### Table Structure Tests (TS)

| Test ID | Description | Assertion |
|---------|-------------|-----------|
| TS-001 | serp_features table exists after migration | Schema::hasTable('serp_features') |
| TS-002 | serp_feature_history table exists after migration | Schema::hasTable('serp_feature_history') |
| TS-003 | serp_features table has all required columns | 11 columns verified |
| TS-004 | serp_feature_history table has all required columns | 9 columns verified |
| TS-005 | serp_features.id is auto-increment primary key | Auto-increment verified |
| TS-006 | serp_feature_history.id is auto-increment primary key | Auto-increment verified |

### Column Type Tests (CT)

| Test ID | Description | Assertion |
|---------|-------------|-----------|
| CT-001 | feature_type is string with max 50 chars | 50 char value stored correctly |
| CT-002 | is_present is boolean defaulting to false | Default value verified |
| CT-003 | position is nullable unsigned integer | Null and integer values verified |
| CT-004 | url column accepts max 2083 characters | 2083 char URL stored correctly |
| CT-005 | title is nullable string | Null and string values verified |
| CT-006 | snippet is nullable text | Long text (3000+ chars) stored correctly |
| CT-007 | checked_at is timestamp | Carbon instance verified |
| CT-008 | action in history is enum with gained/lost values | Both values verified |
| CT-009 | detected_at defaults to current timestamp | Default value verified |

### Foreign Key Tests (FK)

| Test ID | Description | Assertion |
|---------|-------------|-----------|
| FK-001 | serp_features.keyword_id references keywords.id | Valid FK verified |
| FK-002 | serp_feature_history.keyword_id references keywords.id | Valid FK verified |
| FK-003 | Deleting keyword cascades to serp_features | Cascade delete verified |
| FK-004 | Deleting keyword cascades to serp_feature_history | Cascade delete verified |
| FK-005 | Invalid keyword_id fails on serp_features | QueryException expected |
| FK-006 | Invalid keyword_id fails on serp_feature_history | QueryException expected |

### Index Tests (IX)

| Test ID | Description | Assertion |
|---------|-------------|-----------|
| IX-001 | serp_features has index on keyword_id and feature_type | Index verified |
| IX-002 | serp_features has index on feature_type | Index verified |
| IX-003 | serp_features has index on is_present | Index verified |
| IX-004 | serp_features has index on checked_at | Index verified |
| IX-005 | serp_feature_history has index on keyword_id and feature_type | Index verified |
| IX-006 | serp_feature_history has index on feature_type | Index verified |
| IX-007 | serp_feature_history has index on detected_at | Index verified |

### Migration Rollback Tests (MR)

| Test ID | Description | Assertion |
|---------|-------------|-----------|
| MR-001 | Rollback drops serp_feature_history table | Schema::hasTable() returns false |
| MR-002 | Rollback drops serp_features table | Schema::hasTable() returns false |
| MR-003 | Migration can be re-run after rollback | Tables recreated successfully |

### Edge Case Tests (EC)

| Test ID | Description | Assertion |
|---------|-------------|-----------|
| EC-001 | Multiple features per keyword allowed | 3 features stored for 1 keyword |
| EC-002 | Same feature_type can have multiple records per keyword | 2 records with same type stored |
| EC-003 | Position can be zero (featured snippet position) | Position 0 stored correctly |
| EC-004 | URL can be null when feature not present | Null URL verified |
| EC-005 | History previous_url and current_url can both be null | Null values verified |
| EC-006 | Unicode characters supported in snippet | Unicode text stored correctly |
| EC-007 | All 10 SERP feature types can be stored | All 10 types stored |

### Security Tests (SC)

| Test ID | Description | Assertion |
|---------|-------------|-----------|
| SC-001 | SQL injection attempt in feature_type is handled | Stored as literal string |
| SC-002 | XSS attempt in snippet is stored as-is | Stored as literal string |

## Related Tests

Additional test files for SERP features:

| Test File | Tests | Description |
|-----------|-------|-------------|
| `SerpFeatureRepositoryTest.php` | 25 | Repository CRUD operations |
| `SerpFeatureHistoryRepositoryTest.php` | 15 | History repository operations |
| `SerpFeatureSecurityTest.php` | 14 | Security-focused tests |

## Database Schema

### serp_features Table

```sql
CREATE TABLE serp_features (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    keyword_id BIGINT UNSIGNED NOT NULL,
    feature_type VARCHAR(50) NOT NULL,
    is_present BOOLEAN DEFAULT FALSE,
    position INT UNSIGNED NULL,
    url VARCHAR(2083) NULL,
    title VARCHAR(255) NULL,
    snippet TEXT NULL,
    checked_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (keyword_id) REFERENCES keywords(id) ON DELETE CASCADE,
    INDEX idx_keyword_feature (keyword_id, feature_type),
    INDEX idx_feature_type (feature_type),
    INDEX idx_is_present (is_present),
    INDEX idx_checked_at (checked_at)
);
```

### serp_feature_history Table

```sql
CREATE TABLE serp_feature_history (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    keyword_id BIGINT UNSIGNED NOT NULL,
    feature_type VARCHAR(50) NOT NULL,
    action ENUM('gained', 'lost') NOT NULL,
    previous_url VARCHAR(2083) NULL,
    current_url VARCHAR(2083) NULL,
    detected_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (keyword_id) REFERENCES keywords(id) ON DELETE CASCADE,
    INDEX idx_keyword_feature (keyword_id, feature_type),
    INDEX idx_feature_type (feature_type),
    INDEX idx_detected_at (detected_at)
);
```

## SERP Feature Types

The following feature types are supported:

| Type | Description |
|------|-------------|
| `featured_snippet` | Answer box at top of results |
| `local_pack` | Map with 3 local business listings |
| `image_pack` | Horizontal row of images in results |
| `video` | Video thumbnail in search results |
| `people_also_ask` | Expandable related questions |
| `knowledge_panel` | Entity information sidebar |
| `sitelinks` | Additional page links under result |
| `reviews` | Star ratings displayed with result |
| `faq` | FAQ rich result |
| `product` | Product listing rich result |

## Notes

1. **Schema Decision**: The migration uses `keyword_id` (not `ranking_id`) as the foreign key. This is intentional because SERP features are tracked per keyword, not per ranking check. Multiple ranking checks for the same keyword can reference the same feature state.

2. **No Unique Constraint**: The schema intentionally does NOT have a unique constraint on `(keyword_id, feature_type)` to allow historical tracking of feature states over time. The `checked_at` timestamp differentiates records.

3. **JSON Column in ranking_history**: The `serp_features` JSON column in the `ranking_history` table provides quick access to feature state during ranking checks without requiring a separate join.

## Production Status

- **Migration**: `2026_02_26_172400_create_serp_features_tables.php`
- **Tests**: 39/39 passing (1 skipped on SQLite)
- **Status**: Complete

---

Last updated: 2026-02-28
