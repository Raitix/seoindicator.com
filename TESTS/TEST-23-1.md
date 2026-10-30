# TEST-23-1: Database Migrations - Backlinks Tables

## Task Reference
- **Task:** TASK-23-1
- **Feature:** FEAT-023 (Backlinks Indicator)
- **Specification:** SPEC-023
- **Status:** Tests Coverage In Progress

---

## Overview
Tests for the backlinks database migrations. Verifies schema structure, column definitions, indexes, foreign key constraints, enum validation, and rollback functionality for four tables:
- `backlinks` - Individual backlink records
- `referring_domains` - Aggregated domain-level metrics
- `anchor_texts` - Anchor text distribution analysis
- `backlink_snapshots` - Historical tracking

---

## Test Files

| File | Type | Tests | Assertions |
|------|------|-------|------------|
| `backend/tests/Feature/Migrations/CreateBacklinksTablesTest.php` | Integration | 80+ | 200+ |

---

## Test Scenarios

### BACKLINKS TABLE

#### Schema Structure Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| BL-01 | Table exists | Run migration | `backlinks` table created |
| BL-02 | Primary key | Inspect `id` column | BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY |
| BL-03 | project_id column | Inspect column | BIGINT UNSIGNED NOT NULL, FK to projects.id |
| BL-04 | source_url column | Inspect column | VARCHAR(2083) NOT NULL |
| BL-05 | source_domain column | Inspect column | VARCHAR(255) NOT NULL |
| BL-06 | target_url column | Inspect column | VARCHAR(2083) NOT NULL |
| BL-07 | anchor_text column | Inspect column | VARCHAR(500) NULL |
| BL-08 | link_type column | Inspect column | ENUM('dofollow', 'nofollow', 'unknown') DEFAULT 'unknown' |
| BL-09 | first_seen_at column | Inspect column | TIMESTAMP NULL |
| BL-10 | last_checked_at column | Inspect column | TIMESTAMP NULL |
| BL-11 | is_active column | Inspect column | TINYINT(1) DEFAULT 1 |
| BL-12 | Timestamps | Inspect columns | created_at, updated_at TIMESTAMP |

#### Index Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| BL-13 | project_domain index | Check index | INDEX on (project_id, source_domain) |
| BL-14 | source_url index | Check index | INDEX on source_url(255) |
| BL-15 | target_url index | Check index | INDEX on target_url(255) |
| BL-16 | is_active index | Check index | INDEX on is_active |

#### Foreign Key Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| BL-17 | project_id foreign key | Check constraint | FK to projects.id ON DELETE CASCADE |
| BL-18 | Cascade delete project | Delete project | Related backlinks deleted |

#### Enum Validation Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| BL-19 | Valid link_type: dofollow | Insert with 'dofollow' | Success |
| BL-20 | Valid link_type: nofollow | Insert with 'nofollow' | Success |
| BL-21 | Valid link_type: unknown | Insert with 'unknown' | Success |
| BL-22 | Default link_type | Insert without link_type | Defaults to 'unknown' |
| BL-23 | Invalid link_type | Insert with 'invalid' | SQL error / rejected |

#### Edge Cases

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| BL-24 | Long source_url | URL: 2083 chars | Success (max length) |
| BL-25 | Long anchor_text | Text: 500 chars | Success (max length) |
| BL-26 | Null anchor_text | Insert without anchor_text | Success (nullable) |
| BL-27 | Null first_seen_at | Insert without first_seen_at | Success (nullable) |
| BL-28 | Null last_checked_at | Insert without last_checked_at | Success (nullable) |
| BL-29 | Unicode in source_domain | Domain: 例え.jp | Handled correctly |
| BL-30 | is_active false | Insert with is_active = 0 | Success |

#### Security Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| BL-31 | SQL injection in source_url | URL with malicious SQL | Parameterized / safe |
| BL-32 | SQL injection in anchor_text | Text with malicious SQL | Parameterized / safe |
| BL-33 | XSS in anchor_text | Script tag in text | Stored safely (output escaped) |

---

### REFERRING_DOMAINS TABLE

#### Schema Structure Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| RD-01 | Table exists | Run migration | `referring_domains` table created |
| RD-02 | Primary key | Inspect `id` column | BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY |
| RD-03 | project_id column | Inspect column | BIGINT UNSIGNED NOT NULL, FK to projects.id |
| RD-04 | domain column | Inspect column | VARCHAR(255) NOT NULL |
| RD-05 | backlinks_count column | Inspect column | INT UNSIGNED DEFAULT 0 |
| RD-06 | domain_trust_score column | Inspect column | TINYINT UNSIGNED DEFAULT 0 (0-100) |
| RD-07 | first_seen_at column | Inspect column | TIMESTAMP NULL |
| RD-08 | last_seen_at column | Inspect column | TIMESTAMP NULL |
| RD-09 | Timestamps | Inspect columns | created_at, updated_at TIMESTAMP |

#### Index Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| RD-10 | Unique project_domain | Check constraint | UNIQUE on (project_id, domain) |
| RD-11 | backlinks_count index | Check index | INDEX on backlinks_count DESC |

#### Foreign Key Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| RD-12 | project_id foreign key | Check constraint | FK to projects.id ON DELETE CASCADE |
| RD-13 | Cascade delete project | Delete project | Related domains deleted |

#### Constraint Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| RD-14 | Unique project_domain | Insert duplicate domain for same project | Error / rejected |
| RD-15 | Same domain different project | Insert same domain for different project | Success |
| RD-16 | Duplicate domain same project | Insert domain twice for same project | Error / rejected |

#### Edge Cases

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| RD-17 | Domain trust score 0 | Insert with score 0 | Success |
| RD-18 | Domain trust score 100 | Insert with score 100 | Success |
| RD-19 | Domain trust score > 100 | Insert with score 101 | Error / rejected (TINYINT UNSIGNED) |
| RD-20 | Null first_seen_at | Insert without first_seen_at | Success (nullable) |
| RD-21 | Null last_seen_at | Insert without last_seen_at | Success (nullable) |
| RD-22 | Unicode domain | Domain: 例え.jp | Handled correctly |

#### Security Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| RD-23 | SQL injection in domain | Domain with malicious SQL | Parameterized / safe |
| RD-24 | XSS in domain | Script tag in domain | Stored safely |

---

### ANCHOR_TEXTS TABLE

#### Schema Structure Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| AT-01 | Table exists | Run migration | `anchor_texts` table created |
| AT-02 | Primary key | Inspect `id` column | BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY |
| AT-03 | project_id column | Inspect column | BIGINT UNSIGNED NOT NULL, FK to projects.id |
| AT-04 | anchor_text column | Inspect column | VARCHAR(500) NOT NULL |
| AT-05 | backlinks_count column | Inspect column | INT UNSIGNED DEFAULT 0 |
| AT-06 | is_branded column | Inspect column | TINYINT(1) DEFAULT 0 |
| AT-07 | is_exact_match column | Inspect column | TINYINT(1) DEFAULT 0 |
| AT-08 | Timestamps | Inspect columns | created_at, updated_at TIMESTAMP |

#### Index Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| AT-09 | project_anchor index | Check index | INDEX on (project_id, anchor_text(255)) |
| AT-10 | branded index | Check index | INDEX on is_branded |

#### Foreign Key Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| AT-11 | project_id foreign key | Check constraint | FK to projects.id ON DELETE CASCADE |
| AT-12 | Cascade delete project | Delete project | Related anchor texts deleted |

#### Edge Cases

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| AT-13 | Long anchor_text | Text: 500 chars | Success (max length) |
| AT-14 | is_branded true | Insert with is_branded = 1 | Success |
| AT-15 | is_exact_match true | Insert with is_exact_match = 1 | Success |
| AT-16 | Both flags true | Insert with both = 1 | Success (both can be true) |
| AT-17 | Unicode anchor_text | Text: "点击这里" | Handled correctly |
| AT-18 | Empty anchor_text | Insert with empty string | Depends on business logic |
| AT-19 | Special characters | Text: "<a>link</a>" | Stored safely |

#### Security Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| AT-20 | SQL injection in anchor_text | Text with malicious SQL | Parameterized / safe |
| AT-21 | XSS in anchor_text | Script tag in text | Stored safely (output escaped) |

---

### BACKLINK_SNAPSHOTS TABLE

#### Schema Structure Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| BS-01 | Table exists | Run migration | `backlink_snapshots` table created |
| BS-02 | Primary key | Inspect `id` column | BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY |
| BS-03 | project_id column | Inspect column | BIGINT UNSIGNED NOT NULL, FK to projects.id |
| BS-04 | total_backlinks column | Inspect column | INT UNSIGNED DEFAULT 0 |
| BS-05 | referring_domains_count column | Inspect column | INT UNSIGNED DEFAULT 0 |
| BS-06 | dofollow_count column | Inspect column | INT UNSIGNED DEFAULT 0 |
| BS-07 | nofollow_count column | Inspect column | INT UNSIGNED DEFAULT 0 |
| BS-08 | avg_domain_trust column | Inspect column | DECIMAL(5,2) DEFAULT 0 |
| BS-09 | anchor_diversity_score column | Inspect column | DECIMAL(5,2) DEFAULT 0 |
| BS-10 | snapshot_date column | Inspect column | DATE NOT NULL |
| BS-11 | created_at column | Inspect column | TIMESTAMP DEFAULT CURRENT_TIMESTAMP |

#### Index Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| BS-12 | Unique project_date | Check constraint | UNIQUE on (project_id, snapshot_date) |
| BS-13 | snapshot_date index | Check index | INDEX on snapshot_date |

#### Foreign Key Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| BS-14 | project_id foreign key | Check constraint | FK to projects.id ON DELETE CASCADE |
| BS-15 | Cascade delete project | Delete project | Related snapshots deleted |

#### Constraint Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| BS-16 | Unique project_date | Insert duplicate date for same project | Error / rejected |
| BS-17 | Same date different project | Insert same date for different project | Success |
| BS-18 | Multiple dates same project | Insert different dates for same project | Success |

#### Decimal Precision Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| BS-19 | avg_domain_trust precision | Insert 45.67 | Stored as 45.67 |
| BS-20 | avg_domain_trust max | Insert 100.00 | Success |
| BS-21 | avg_domain_trust decimal | Insert 45.678 | Rounded to 45.68 |
| BS-22 | anchor_diversity_score precision | Insert 78.90 | Stored as 78.90 |
| BS-23 | Negative values blocked | Insert -1.00 | Error / rejected (UNSIGNED) |

#### Edge Cases

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| BS-24 | All zeros | Insert with all counts = 0 | Success |
| BS-25 | Large counts | Insert with 1,000,000 backlinks | Success (INT UNSIGNED) |
| BS-26 | Future snapshot_date | Insert with tomorrow's date | Success |
| BS-27 | Past snapshot_date | Insert with yesterday's date | Success |

---

### MIGRATION ROLLBACK TESTS

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| RB-01 | Rollback removes backlinks | `php artisan migrate:rollback --step=1` | `backlinks` table dropped |
| RB-02 | Rollback removes referring_domains | Continue rollback | `referring_domains` table dropped |
| RB-03 | Rollback removes anchor_texts | Continue rollback | `anchor_texts` table dropped |
| RB-04 | Rollback removes backlink_snapshots | Continue rollback | `backlink_snapshots` table dropped |
| RB-05 | Re-run migration | `php artisan migrate` | All 4 tables recreated |
| RB-06 | Migration idempotency | Run migration twice | No errors (already exists) |

---

### CROSS-TABLE INTEGRATION TESTS

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CT-01 | Project cascade to all tables | Delete project | All 4 tables' records deleted |
| CT-02 | Backlinks reference project | Insert backlink with project_id | Success |
| CT-03 | Referring domain references project | Insert domain with project_id | Success |
| CT-04 | Anchor text references project | Insert anchor with project_id | Success |
| CT-05 | Snapshot references project | Insert snapshot with project_id | Success |

---

### SQLITE COMPATIBILITY TESTS

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| SQ-01 | SQLite table creation | Run on SQLite | All tables created |
| SQ-02 | SQLite index creation | Run on SQLite | All indexes created |
| SQ-03 | SQLite foreign keys | Run on SQLite | FKs enforced (if enabled) |
| SQ-04 | SQLite enum handling | Insert enum values | Stored as string |
| SQ-05 | SQLite decimal handling | Insert DECIMAL values | Stored as REAL |

---

## Mock Data

### Valid Backlink Record
```json
{
  "project_id": 1,
  "source_url": "https://example.com/blog/post",
  "source_domain": "example.com",
  "target_url": "https://myproject.com/page",
  "anchor_text": "SEO tools",
  "link_type": "dofollow",
  "first_seen_at": "2026-02-15 10:00:00",
  "last_checked_at": "2026-02-28 12:00:00",
  "is_active": 1
}
```

### Valid Referring Domain Record
```json
{
  "project_id": 1,
  "domain": "example.com",
  "backlinks_count": 45,
  "domain_trust_score": 72,
  "first_seen_at": "2026-01-15 10:00:00",
  "last_seen_at": "2026-02-28 12:00:00"
}
```

### Valid Anchor Text Record
```json
{
  "project_id": 1,
  "anchor_text": "best SEO tools",
  "backlinks_count": 150,
  "is_branded": false,
  "is_exact_match": true
}
```

### Valid Snapshot Record
```json
{
  "project_id": 1,
  "total_backlinks": 1250,
  "referring_domains_count": 89,
  "dofollow_count": 900,
  "nofollow_count": 350,
  "avg_domain_trust": 45.67,
  "anchor_diversity_score": 78.90,
  "snapshot_date": "2026-02-28"
}
```

---

## Coverage Targets

| Category | Target | Description |
|----------|--------|-------------|
| Schema Structure | 100% | All columns, types, defaults for all 4 tables |
| Indexes | 100% | All indexes present on all tables |
| Foreign Keys | 100% | FK constraints and cascade on all tables |
| Enum Values | 100% | All link_type values tested |
| Unique Constraints | 100% | All unique constraints tested |
| Decimal Precision | 100% | DECIMAL fields precision verified |
| Rollback | 100% | All migrations rollback correctly |
| Security | 100% | SQL injection, XSS on all text fields |
| Edge Cases | 90% | Boundary conditions |
| SQLite Compatibility | 100% | Tests pass on SQLite |

---

## Test Execution

```bash
# Run specific test file
php artisan test --filter=CreateBacklinksTablesTest

# Run with coverage
php artisan test --filter=CreateBacklinksTablesTest --coverage

# Run all migration tests
php artisan test --filter=Migrations

# Run on SQLite (for local testing)
DB_CONNECTION=sqlite php artisan test --filter=CreateBacklinksTablesTest
```

---

## Dependencies
- Laravel migrations system
- `projects` table (for FK constraint) - TASK-8-1
- SQLite/MySQL database connection
- User model factory (for project ownership)

---

## Notes for Developer

### Migration File Naming
Use proper naming convention:
- `YYYY_MM_DD_HHMMSS_create_backlinks_table.php`
- `YYYY_MM_DD_HHMMSS_create_referring_domains_table.php`
- `YYYY_MM_DD_HHMMSS_create_anchor_texts_table.php`
- `YYYY_MM_DD_HHMMSS_create_backlink_snapshots_table.php`

### Implementation Order
1. Create `backlinks` table first (most complex)
2. Create `referring_domains` table (has unique constraint)
3. Create `anchor_texts` table
4. Create `backlink_snapshots` table (historical)

### SQLite Considerations
- ENUM types are not native in SQLite - use string columns with validation in app layer
- DECIMAL stored as REAL in SQLite
- Foreign keys disabled by default in SQLite - enable with `DB::statement('PRAGMA foreign_keys = ON')`

### URL Length Handling
- HTTP spec limits URLs to 2083 characters for some browsers
- VARCHAR(2083) matches this spec
- Consider validating URL length in app layer before insert

### Index Strategy
- Composite indexes on frequently queried columns
- `(project_id, source_domain)` for backlinks - common query pattern
- `(project_id, domain)` unique - prevents duplicates per project
- `(project_id, snapshot_date)` unique - one snapshot per project per day

---

## Comments

### [2026-02-28 22:10] Agent: PreTester
- Test documentation created for all 4 tables
- 80+ test scenarios covering schema, indexes, constraints, security
- SQLite compatibility tests included
- Ready for test implementation
