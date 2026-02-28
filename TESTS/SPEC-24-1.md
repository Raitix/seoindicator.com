# TESTS/SPEC-24-1: Database Migration - PWA Analyses Table

## Related
- Task: TASKS/TASK-24-1.md
- Specification: SPECIFICATIONS/SPEC-024.md
- Feature: FEATURES/FEAT-024.md

## Test Status
- [x] Tests designed
- [x] Unit tests written
- [ ] Integration tests written
- [ ] E2E tests written (N/A - migration task)
- [ ] All tests passing locally
- [ ] All tests passing in production

## Dependency Note
- Requires `sites` table (exists from FEAT-001)
- No other dependencies - foundation task for PWA Indicator feature

## Test Cases

### Category 1: Schema Structure (SC-001 to SC-020)

#### SC-001: Migration Creates pwa_analyses Table
- **Type:** Feature/Integration
- **Description:** Verify pwa_analyses table is created with correct schema
- **Preconditions:** Database connection established, sites table exists
- **Steps:**
  1. Run migration
  2. Check table exists
- **Expected Result:** Table created successfully

#### SC-002: Primary Key Column
- **Type:** Feature/Integration
- **Description:** Verify id column is BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
- **Preconditions:** Table exists
- **Steps:**
  1. Query column information
  2. Verify type, auto_increment, primary key
- **Expected Result:** id is BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY

#### SC-003: site_id Column
- **Type:** Feature/Integration
- **Description:** Verify site_id column is BIGINT UNSIGNED NOT NULL
- **Preconditions:** Table exists
- **Steps:**
  1. Query column information
  2. Verify type and nullable status
- **Expected Result:** site_id is BIGINT UNSIGNED NOT NULL

#### SC-004: Boolean Field Columns
- **Type:** Feature/Integration
- **Description:** Verify TINYINT(1) columns for boolean fields
- **Fields:** has_manifest, manifest_valid, has_name, has_short_name, has_start_url, has_display, has_background_color, has_theme_color, has_description, icons_192_present, icons_512_present, icons_180_present
- **Preconditions:** Table exists
- **Steps:**
  1. Query each column information
  2. Verify type TINYINT(1) and default 0
- **Expected Result:** All boolean fields are TINYINT(1) with default 0

#### SC-005: manifest_url Column
- **Type:** Feature/Integration
- **Description:** Verify manifest_url column is VARCHAR(500) nullable
- **Preconditions:** Table exists
- **Steps:**
  1. Query column information
  2. Verify type VARCHAR(500) and nullable
- **Expected Result:** manifest_url is VARCHAR(500) NULL

#### SC-006: manifest_json Column
- **Type:** Feature/Integration
- **Description:** Verify manifest_json column is JSON nullable
- **Preconditions:** Table exists
- **Steps:**
  1. Query column information
  2. Verify type JSON and nullable
- **Expected Result:** manifest_json is JSON NULL

#### SC-007: icon_errors Column
- **Type:** Feature/Integration
- **Description:** Verify icon_errors column is JSON nullable
- **Preconditions:** Table exists
- **Steps:**
  1. Query column information
  2. Verify type JSON and nullable
- **Expected Result:** icon_errors is JSON NULL

#### SC-008: Icon Count Columns
- **Type:** Feature/Integration
- **Description:** Verify icons_total and icons_valid are INT UNSIGNED with default 0
- **Preconditions:** Table exists
- **Steps:**
  1. Query columns information
  2. Verify type INT UNSIGNED, default 0
- **Expected Result:** Both columns are INT UNSIGNED DEFAULT 0

#### SC-009: pwa_score Column
- **Type:** Feature/Integration
- **Description:** Verify pwa_score column is TINYINT UNSIGNED with default 0
- **Preconditions:** Table exists
- **Steps:**
  1. Query column information
  2. Verify type TINYINT UNSIGNED, default 0
- **Expected Result:** pwa_score is TINYINT UNSIGNED DEFAULT 0 (0-100 range)

#### SC-010: readiness_level Column
- **Type:** Feature/Integration
- **Description:** Verify readiness_level column is ENUM with correct values
- **Preconditions:** Table exists
- **Steps:**
  1. Query column information
  2. Verify type ENUM('none', 'basic', 'enhanced', 'full')
  3. Verify default 'none'
- **Expected Result:** readiness_level is ENUM with correct values, default 'none'

#### SC-011: Timestamp Columns
- **Type:** Feature/Integration
- **Description:** Verify analyzed_at, created_at, updated_at columns
- **Preconditions:** Table exists
- **Steps:**
  1. Query column information
  2. Verify type TIMESTAMP and defaults
- **Expected Result:**
  - analyzed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
  - created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
  - updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP

### Category 2: Foreign Key Constraints (FK-001 to FK-003)

#### FK-001: Foreign Key to sites.id
- **Type:** Feature/Integration
- **Description:** Verify foreign key from site_id to sites.id
- **Preconditions:** sites table exists
- **Steps:**
  1. Attempt to insert record with non-existent site_id
  2. Verify database rejects insert
  3. Create site, then insert with valid site_id
  4. Verify insert succeeds
- **Expected Result:** Foreign key constraint enforces referential integrity

#### FK-002: Cascade Delete Behavior
- **Type:** Feature/Integration
- **Description:** Verify CASCADE delete on site deletion
- **Preconditions:** Site with pwa_analyses record exists
- **Steps:**
  1. Create site
  2. Create pwa_analysis record for site
  3. Delete site
  4. Verify pwa_analysis record is deleted
- **Expected Result:** pwa_analyses records cascade deleted with site

#### FK-003: Multiple Analyses Per Site Allowed
- **Type:** Feature/Integration
- **Description:** Verify multiple analysis records can exist per site
- **Preconditions:** sites table exists
- **Steps:**
  1. Create site
  2. Create multiple pwa_analysis records with different analyzed_at timestamps
  3. Verify all records exist
- **Expected Result:** Multiple analyses per site allowed (no unique constraint on site_id)

### Category 3: Indexes (IX-001 to IX-003)

#### IX-001: Composite Index on (site_id, analyzed_at)
- **Type:** Feature/Integration
- **Description:** Verify composite index for efficient queries
- **Preconditions:** Table exists
- **Steps:**
  1. Query index information
  2. Verify composite index exists on (site_id, analyzed_at)
- **Expected Result:** Index `idx_site_analyzed` exists

#### IX-002: Index on pwa_score
- **Type:** Feature/Integration
- **Description:** Verify index for score-based queries
- **Preconditions:** Table exists
- **Steps:**
  1. Query index information
  2. Verify index exists on pwa_score
- **Expected Result:** Index `idx_pwa_score` exists

#### IX-003: Foreign Key Index
- **Type:** Feature/Integration
- **Description:** Verify automatic index on foreign key
- **Preconditions:** Table exists
- **Steps:**
  1. Query index information
  2. Verify index exists on site_id (FK automatically indexed)
- **Expected Result:** Index exists on site_id

### Category 4: Enum Validation (EV-001 to EV-004)

#### EV-001: Valid readiness_level Values
- **Type:** Feature/Integration
- **Description:** Verify all valid ENUM values are accepted
- **Preconditions:** Table exists, site created
- **Steps:**
  1. Insert record with readiness_level = 'none'
  2. Insert record with readiness_level = 'basic'
  3. Insert record with readiness_level = 'enhanced'
  4. Insert record with readiness_level = 'full'
- **Expected Result:** All valid values accepted

#### EV-002: Invalid readiness_level Rejected
- **Type:** Feature/Integration
- **Description:** Verify invalid ENUM values are rejected
- **Preconditions:** Table exists, site created
- **Steps:**
  1. Attempt to insert with readiness_level = 'invalid'
  2. Verify database rejects insert
- **Expected Result:** Invalid ENUM value rejected

#### EV-003: Default readiness_level is 'none'
- **Type:** Feature/Integration
- **Description:** Verify default value for readiness_level
- **Preconditions:** Table exists, site created
- **Steps:**
  1. Insert record without specifying readiness_level
  2. Query record
  3. Verify readiness_level = 'none'
- **Expected Result:** Default value is 'none'

#### EV-004: Case Sensitivity
- **Type:** Feature/Integration
- **Description:** Verify ENUM values are case-sensitive
- **Preconditions:** Table exists, site created
- **Steps:**
  1. Attempt to insert with readiness_level = 'BASIC' (uppercase)
  2. Verify database rejects or converts value
- **Expected Result:** Case-sensitive validation (lowercase only)

### Category 5: Migration Rollback (RB-001 to RB-002)

#### RB-001: Migration Down Drops Table
- **Type:** Feature/Integration
- **Description:** Verify migration rollback drops table
- **Preconditions:** Migration has been run
- **Steps:**
  1. Run migration:up
  2. Verify table exists
  3. Run migration:down
  4. Verify table does not exist
- **Expected Result:** Table dropped on rollback

#### RB-002: Migration Re-run After Rollback
- **Type:** Feature/Integration
- **Description:** Verify migration can be re-run after rollback
- **Preconditions:** Migration rolled back
- **Steps:**
  1. Run migration:up
  2. Run migration:down
  3. Run migration:up again
  4. Verify table exists with correct schema
- **Expected Result:** Migration is idempotent

### Category 6: JSON Handling (JH-001 to JH-004)

#### JH-001: Valid JSON in manifest_json
- **Type:** Feature/Integration
- **Description:** Verify valid JSON can be stored in manifest_json
- **Preconditions:** Table exists, site created
- **Steps:**
  1. Insert record with valid JSON object
  2. Query record
  3. Verify JSON is stored correctly
- **Expected Result:** JSON stored and retrievable

#### JH-002: Null JSON Values Allowed
- **Type:** Feature/Integration
- **Description:** Verify NULL values allowed for JSON columns
- **Preconditions:** Table exists, site created
- **Steps:**
  1. Insert record with manifest_json = NULL
  2. Insert record with icon_errors = NULL
  3. Query records
  4. Verify NULL values stored
- **Expected Result:** NULL values allowed for JSON columns

#### JH-003: Empty JSON Object
- **Type:** Feature/Integration
- **Description:** Verify empty JSON object can be stored
- **Preconditions:** Table exists, site created
- **Steps:**
  1. Insert record with manifest_json = '{}'
  2. Query record
  3. Verify empty object stored correctly
- **Expected Result:** Empty JSON object allowed

#### JH-004: Nested JSON Object
- **Type:** Feature/Integration
- **Description:** Verify nested JSON structure can be stored
- **Preconditions:** Table exists, site created
- **Steps:**
  1. Insert record with nested manifest_json
  2. Query record
  3. Verify nested structure preserved
- **Expected Result:** Nested JSON preserved correctly

### Category 7: Score Range Validation (SR-001 to SR-003)

#### SR-001: pwa_score Minimum Value
- **Type:** Feature/Integration
- **Description:** Verify pwa_score can be 0
- **Preconditions:** Table exists, site created
- **Steps:**
  1. Insert record with pwa_score = 0
  2. Query record
  3. Verify score is 0
- **Expected Result:** pwa_score = 0 allowed

#### SR-002: pwa_score Maximum Value
- **Type:** Feature/Integration
- **Description:** Verify pwa_score can be 100
- **Preconditions:** Table exists, site created
- **Steps:**
  1. Insert record with pwa_score = 100
  2. Query record
  3. Verify score is 100
- **Expected Result:** pwa_score = 100 allowed

#### SR-003: pwa_score Out of Range
- **Type:** Feature/Integration
- **Description:** Verify behavior when pwa_score > 100
- **Note:** TINYINT UNSIGNED max is 255, so database won't reject
- **Preconditions:** Table exists, site created
- **Steps:**
  1. Insert record with pwa_score = 101
  2. Verify value is stored (application layer should validate)
- **Expected Result:** Value stored (application validation required)

### Category 8: Security Tests (SEC-001 to SEC-003)

#### SEC-001: SQL Injection Prevention
- **Type:** Security
- **Description:** Verify SQL injection in manifest_url is prevented
- **Preconditions:** Table exists, site created
- **Steps:**
  1. Insert record with manifest_url containing SQL injection payload
  2. Verify payload stored as string, not executed
- **Expected Result:** Payload stored as string (Eloquent parameterization)

#### SEC-002: XSS Prevention in JSON
- **Type:** Security
- **Description:** Verify XSS payloads in manifest_json are stored safely
- **Preconditions:** Table exists, site created
- **Steps:**
  1. Insert record with manifest_json containing <script> tags
  2. Query record
  3. Verify script stored as string, not executed
- **Expected Result:** XSS payload stored as string

#### SEC-003: Large JSON Handling
- **Type:** Security
- **Description:** Verify large JSON objects can be stored
- **Preconditions:** Table exists, site created
- **Steps:**
  1. Insert record with large manifest_json (>10KB)
  2. Verify storage succeeds
- **Expected Result:** Large JSON stored successfully (no size limit at DB level)

## Test File Locations

| Test Type | Location |
|-----------|----------|
| Feature/Integration | `backend/tests/Feature/Migrations/CreatePwaAnalysesTableTest.php` |

## Test Scenarios Summary

| ID | Category | Description | Priority |
|----|----------|-------------|----------|
| SC-001 | Schema | Table creation | High |
| SC-002 | Schema | Primary key | High |
| SC-003 | Schema | site_id column | High |
| SC-004 | Schema | Boolean fields | High |
| SC-005 | Schema | manifest_url column | Medium |
| SC-006 | Schema | manifest_json column | High |
| SC-007 | Schema | icon_errors column | Medium |
| SC-008 | Schema | Icon count columns | Medium |
| SC-009 | Schema | pwa_score column | High |
| SC-010 | Schema | readiness_level ENUM | High |
| SC-011 | Schema | Timestamp columns | Medium |
| FK-001 | FK | site_id → sites.id | High |
| FK-002 | FK | CASCADE delete | High |
| FK-003 | FK | Multiple analyses per site | Medium |
| IX-001 | Index | Composite (site_id, analyzed_at) | Medium |
| IX-002 | Index | pwa_score index | Medium |
| IX-003 | Index | FK index | Low |
| EV-001 | Enum | Valid values | High |
| EV-002 | Enum | Invalid values rejected | High |
| EV-003 | Enum | Default value | Medium |
| EV-004 | Enum | Case sensitivity | Medium |
| RB-001 | Rollback | Drop table | High |
| RB-002 | Rollback | Re-run after rollback | High |
| JH-001 | JSON | Valid JSON | Medium |
| JH-002 | JSON | NULL values | Medium |
| JH-003 | JSON | Empty object | Low |
| JH-004 | JSON | Nested object | Medium |
| SR-001 | Score | Minimum value (0) | Medium |
| SR-002 | Score | Maximum value (100) | Medium |
| SR-003 | Score | Out of range | Low |
| SEC-001 | Security | SQL injection | High |
| SEC-002 | Security | XSS prevention | High |
| SEC-003 | Security | Large JSON | Low |

## Coverage Targets

| Category | Target | Status |
|----------|--------|--------|
| Schema structure | 100% | Pending |
| Foreign key constraints | 100% | Pending |
| Indexes | 100% | Pending |
| Enum validation | 100% | Pending |
| Migration rollback | 100% | Pending |
| JSON handling | 100% | Pending |
| Score range | 100% | Pending |
| Security | 100% | Pending |

## Test Execution Commands

```bash
# Run all migration tests
php artisan test --filter CreatePwaAnalysesTableTest

# Run specific test category
php artisan test --filter "SC-001"

# Run with coverage
php artisan test --coverage --filter CreatePwaAnalysesTableTest
```

## Dependencies
- `sites` table (exists from FEAT-001)

## Notes for Developer

### Migration File Location
`backend/database/migrations/YYYY_MM_DD_HHMMSS_create_pwa_analyses_table.php`

### Model Creation
After migration, create `PWAAnalysis` model at:
`backend/app/Models/PWAAnalysis.php`

### Expected Model Properties
```php
protected $fillable = [
    'site_id',
    'has_manifest',
    'manifest_url',
    'manifest_valid',
    'manifest_json',
    'has_name',
    'has_short_name',
    'has_start_url',
    'has_display',
    'has_background_color',
    'has_theme_color',
    'has_description',
    'icons_total',
    'icons_valid',
    'icons_192_present',
    'icons_512_present',
    'icons_180_present',
    'icon_errors',
    'pwa_score',
    'readiness_level',
    'analyzed_at',
];

protected $casts = [
    'manifest_json' => 'array',
    'icon_errors' => 'array',
    'has_manifest' => 'boolean',
    'manifest_valid' => 'boolean',
    'has_name' => 'boolean',
    // ... other boolean fields
    'analyzed_at' => 'datetime',
];
```

---

## Comments
_Agent communication log_

### [2026-02-28 23:59 GMT+2] Agent: PreTester
Created test documentation for TASK-24-1 (PWA Analyses Table Migration).

**Test Categories:**
1. Schema Structure (11 tests) - All columns verified
2. Foreign Key Constraints (3 tests) - FK to sites.id with CASCADE
3. Indexes (3 tests) - Composite and single-column indexes
4. Enum Validation (4 tests) - readiness_level ENUM values
5. Migration Rollback (2 tests) - Reversible migration
6. JSON Handling (4 tests) - manifest_json and icon_errors columns
7. Score Range Validation (3 tests) - pwa_score 0-100
8. Security Tests (3 tests) - SQL injection, XSS, large JSON

**Total Test Cases:** 33

Ready to create test implementation file.
