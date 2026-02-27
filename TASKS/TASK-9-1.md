# TASK-9-1: Create Annotations Table

## STATUS
`Testing Locally`

## PRIORITY
`High`

## RISK
`Low`

## Description
Implement the `annotations` table in MySQL to enable keyword-level annotations for SEO events.

## Schema Requirements
```sql
CREATE TABLE annotations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    keyword_id BIGINT UNSIGNED NOT NULL,
    project_id BIGINT UNSIGNED NOT NULL,
    date DATE NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NULL,
    category VARCHAR(50) NOT NULL DEFAULT 'other',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (keyword_id) REFERENCES keywords(id) ON DELETE CASCADE,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    INDEX idx_annotations_keyword_id (keyword_id),
    INDEX idx_annotations_project_id (project_id),
    INDEX idx_annotations_date (date),
    INDEX idx_annotations_category (category)
);
```

## Acceptance Criteria
- [x] Migration file creates the `annotations` table with specified constraints.
- [x] Foreign keys `keyword_id` and `project_id` ensure referential integrity.
- [x] Indexes support querying by `keyword_id`, `project_id`, `date`, and `category`.
- [x] Seeder file for populating sample annotations for testing.

## Dependencies
- Task depends on existing `projects` and `keywords` tables already defined.

## Effort Estimate
2-3 hours.

---

## Comments
### [2026-02-20 17:50] Agent: SpecSystemAnalyst
Task for database schema creation to support keyword annotations.

### [2026-02-27 11:20] Agent: PreTester
**Test Coverage Complete:**
- Created `TESTS/TEST-9-1.md` with 86 test scenarios covering:
  - Migration structure (13 tests)
  - Foreign key constraints (11 tests) - includes CASCADE delete tests for both keyword_id and project_id
  - Index verification (10 tests) - keyword_id, project_id, date, category
  - Model behavior (10 tests) - relationships to Keyword and Project
  - Seeder functionality (8 tests)
  - Edge cases (15 tests) - VARCHAR limits, NULL handling, special chars, Unicode
  - Category values (5 tests) - default 'other', custom values
  - Data integrity (7 tests)
  - Query performance (7 tests)

**Test Files Created:**
- `backend/tests/Feature/Migrations/CreateAnnotationsTableTest.php` - 20+ test methods
- `backend/database/seeders/AnnotationSeeder.php` - Sample data with 9 categories

**Key Implementation Notes:**
1. Multiple annotations allowed per keyword per date (no unique constraint)
2. Category is VARCHAR(50) with default 'other' (no enum restriction)
3. Both FKs use ON DELETE CASCADE for data integrity
4. Indexes on all foreign keys and query columns (keyword_id, project_id, date, category)

**Status:** Ready for Developer implementation.