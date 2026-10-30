# TEST-9-1: Annotations Table Migration Tests

## Task Reference
- **Task:** [TASK-9-1](../TASKS/TASK-9-1.md)
- **Feature:** FEAT-009 Keyword Annotations
- **Specification:** SPEC-009

---

## Overview

Tests for the `annotations` table migration. This table enables keyword-level annotations for SEO events, allowing users to document significant changes, updates, and events that may impact keyword rankings.

---

## Test Files

| File | Type | Purpose |
|------|------|---------|
| `backend/tests/Feature/Migrations/CreateAnnotationsTableTest.php` | Feature | Migration structure, constraints, indexes |
| `backend/tests/Unit/Models/AnnotationTest.php` | Unit | Model behavior and relationships |
| `backend/database/seeders/AnnotationSeeder.php` | Seeder | Test data generation |

---

## Test Scenarios

### Migration File Tests (MT)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| MT-001 | Migration file exists | `database/migrations/*_create_annotations_table.php` | File found with valid naming |
| MT-002 | Migration creates annotations table | Run migration | Table `annotations` created |
| MT-003 | Table has all required columns | Inspect schema | id, keyword_id, project_id, date, title, description, category, created_at, updated_at |
| MT-004 | Primary key is BIGINT UNSIGNED AUTO_INCREMENT | Inspect `id` column | Correct type and auto-increment |
| MT-005 | keyword_id column is BIGINT UNSIGNED NOT NULL | Inspect `keyword_id` column | Correct type, nullable=false |
| MT-006 | project_id column is BIGINT UNSIGNED NOT NULL | Inspect `project_id` column | Correct type, nullable=false |
| MT-007 | date column is DATE NOT NULL | Inspect `date` column | Correct type, nullable=false |
| MT-008 | title column is VARCHAR(255) NOT NULL | Inspect `title` column | Correct type, nullable=false |
| MT-009 | description column is TEXT NULL | Inspect `description` column | Correct type, nullable=true |
| MT-010 | category column is VARCHAR(50) NOT NULL DEFAULT 'other' | Inspect `category` column | Correct type, default='other' |
| MT-011 | created_at column has default CURRENT_TIMESTAMP | Inspect `created_at` column | Default value set |
| MT-012 | updated_at has ON UPDATE CURRENT_TIMESTAMP | Inspect `updated_at` column | Auto-update configured |
| MT-013 | Migration can be rolled back | `php artisan migrate:rollback` | Table dropped successfully |

---

### Foreign Key Constraint Tests (FK)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| FK-001 | Foreign key exists on keyword_id | Inspect FK constraints | FK `annotations_keyword_id_foreign` exists |
| FK-002 | FK keyword_id references keywords.id | Inspect FK definition | References `keywords(id)` |
| FK-003 | FK keyword_id has ON DELETE CASCADE | Inspect FK action | CASCADE on delete |
| FK-004 | Foreign key exists on project_id | Inspect FK constraints | FK `annotations_project_id_foreign` exists |
| FK-005 | FK project_id references projects.id | Inspect FK definition | References `projects(id)` |
| FK-006 | FK project_id has ON DELETE CASCADE | Inspect FK action | CASCADE on delete |
| FK-007 | Deleting keyword deletes all its annotations | Create keyword + annotations, delete keyword | All keyword's annotations deleted |
| FK-008 | Deleting project deletes all its annotations | Create project + annotations, delete project | All project's annotations deleted |
| FK-009 | Creating annotation with non-existent keyword_id fails | Insert with invalid keyword_id | SQL constraint error |
| FK-010 | Creating annotation with non-existent project_id fails | Insert with invalid project_id | SQL constraint error |
| FK-011 | Annotation can be created with valid keyword_id and project_id | Create with existing keys | Annotation created successfully |

---

### Index Tests (IX)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| IX-001 | Index exists on keyword_id | `SHOW INDEX FROM annotations` | `idx_annotations_keyword_id` found |
| IX-002 | Index exists on project_id | `SHOW INDEX FROM annotations` | `idx_annotations_project_id` found |
| IX-003 | Index exists on date | `SHOW INDEX FROM annotations` | `idx_annotations_date` found |
| IX-004 | Index exists on category | `SHOW INDEX FROM annotations` | `idx_annotations_category` found |
| IX-005 | Query by keyword_id uses index | EXPLAIN SELECT * FROM annotations WHERE keyword_id = ? | Index used |
| IX-006 | Query by project_id uses index | EXPLAIN SELECT * FROM annotations WHERE project_id = ? | Index used |
| IX-007 | Query by date uses index | EXPLAIN SELECT * FROM annotations WHERE date = ? | Index used |
| IX-008 | Query by category uses index | EXPLAIN SELECT * FROM annotations WHERE category = ? | Index used |
| IX-009 | Date range query uses index | EXPLAIN SELECT * FROM annotations WHERE date BETWEEN ? AND ? | Index used |
| IX-010 | Composite query benefits from indexes | Query with project_id + date | Uses appropriate index |

---

### Model Tests (MD)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| MD-001 | Annotation model exists | `backend/app/Models/Annotation.php` | File exists |
| MD-002 | Model has correct fillable attributes | Inspect `$fillable` | `keyword_id`, `project_id`, `date`, `title`, `description`, `category` |
| MD-003 | Model has correct cast types | Inspect `$casts` | `date` => `date`, timestamps cast correctly |
| MD-004 | Model belongs to Keyword relationship | `$annotation->keyword` | Returns Keyword model |
| MD-005 | Model belongs to Project relationship | `$annotation->project` | Returns Project model |
| MD-006 | Keyword has many Annotations relationship | `$keyword->annotations` | Returns Collection of Annotation models |
| MD-007 | Project has many Annotations relationship | `$project->annotations` | Returns Collection of Annotation models |
| MD-008 | Model can be created with factory | `Annotation::factory()->create()` | Annotation persisted to database |
| MD-009 | Model timestamps are auto-managed | Create annotation, check timestamps | created_at, updated_at set automatically |
| MD-010 | category defaults to 'other' | Create annotation without category | Value is `'other'` |

---

### Seeder Tests (SD)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| SD-001 | AnnotationSeeder class exists | `database/seeders/AnnotationSeeder.php` | File exists |
| SD-002 | Seeder creates test annotations | Run seeder | Annotations created in database |
| SD-003 | Seeder respects existing data | Run seeder twice | No duplicates or controlled behavior |
| SD-004 | Seeder creates annotations for existing keywords | Run seeder | Annotations linked to valid keywords |
| SD-005 | Seeder creates annotations for existing projects | Run seeder | Annotations linked to valid projects |
| SD-006 | Seeder uses various categories | Run seeder | Mix of category values created |
| SD-007 | Seeder can be run via artisan | `php artisan db:seed --class=AnnotationSeeder` | Command succeeds |
| SD-008 | Seeder creates annotations with various dates | Run seeder | Mix of past, present dates |

---

### Edge Case Tests (EC)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| EC-001 | Empty title fails | Insert with title="" | Validation/constraint error |
| EC-002 | Title exactly 255 chars | Title exactly 255 chars | Accepted |
| EC-003 | Title exceeds 255 chars | Title 256 chars | Truncated or error (VARCHAR limit) |
| EC-004 | Very long description | Description > 65,535 chars | Stored correctly (TEXT limit) |
| EC-005 | Description can be NULL | Create with description=null | Accepted |
| EC-006 | Description with special characters | Description with `<>&"'` etc | Stored and retrieved correctly |
| EC-007 | Description with Unicode/emoji | Description with emoji 🚀📈 | Stored and retrieved correctly |
| EC-008 | Date in the past | Set date to past | Accepted |
| EC-009 | Date in the future | Set date to future | Accepted (for planned events) |
| EC-010 | category with max length (50 chars) | Category exactly 50 chars | Accepted |
| EC-011 | category exceeds 50 chars | Category 51 chars | Truncated or error |
| EC-012 | Multiple annotations for same keyword on same date | Two annotations, same keyword, same date | Both created (no unique constraint) |
| EC-013 | Annotation with minimal data | Only required fields | Annotation created |
| EC-014 | Delete keyword with many annotations | Keyword with 100 annotations | All 100 annotations deleted via CASCADE |
| EC-015 | Delete project with many annotations | Project with 100 annotations | All 100 annotations deleted via CASCADE |

---

### Category Value Tests (CV)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CV-001 | category defaults to 'other' | Create without specifying category | Value is 'other' |
| CV-002 | Valid category values accepted | Set category to 'algorithm-update' | Accepted |
| CV-003 | Custom category values allowed | Set category to custom value | Accepted (no enum restriction) |
| CV-004 | category is case-sensitive | 'Algorithm-Update' vs 'algorithm-update' | Stored as provided |
| CV-005 | category with spaces | 'content update' | Accepted |

---

### Data Integrity Tests (DI)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| DI-001 | Cannot create annotation without keyword_id | Insert with keyword_id=null | SQL constraint error |
| DI-002 | Cannot create annotation without project_id | Insert with project_id=null | SQL constraint error |
| DI-003 | Cannot create annotation without date | Insert with date=null | SQL constraint error |
| DI-004 | Cannot create annotation without title | Insert with title=null | SQL constraint error |
| DI-005 | Mass assignment protection | Try to set non-fillable attribute | Attribute not set |
| DI-006 | Concurrent annotation creation | Two requests create annotation simultaneously | Both annotations created |
| DI-007 | Annotation count queries | Query count after multiple inserts | Correct count returned |

---

### Query Performance Tests (QP)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| QP-001 | Select all annotations for keyword | Query with keyword_id filter | Uses index, fast (<10ms for 1000 rows) |
| QP-002 | Select all annotations for project | Query with project_id filter | Uses index, fast |
| QP-003 | Select annotations by date range | Query with date BETWEEN | Uses index, fast |
| QP-004 | Select annotations by category | Query with category filter | Uses index, fast |
| QP-005 | Join with keywords table | Annotations with keyword data | Efficient join |
| QP-006 | Join with projects table | Annotations with project data | Efficient join |
| QP-007 | Pagination works correctly | Query with pagination | Correct offset/limit |

---

## Mock Data

### Sample Annotation Categories
```php
$categories = [
    'other',           // Default
    'algorithm-update', // Google algorithm change
    'content-update',   // Website content changes
    'technical-change', // Technical SEO changes
    'link-building',    // New backlinks acquired
    'competition',      // Competitor activity
    'seasonal',         // Seasonal trends
    'manual-action',    // Google manual penalty
    'recovery',         // Recovery from issue
];
```

### Sample Annotations
```php
// Algorithm update annotation
Annotation::create([
    'keyword_id' => 1,
    'project_id' => 1,
    'date' => '2026-02-27',
    'title' => 'Google Core Update March 2026',
    'description' => 'Position dropped from #5 to #12 after core update rollout',
    'category' => 'algorithm-update',
]);

// Content update annotation
Annotation::create([
    'keyword_id' => 1,
    'project_id' => 1,
    'date' => '2026-02-20',
    'title' => 'Published new blog post',
    'description' => 'Added comprehensive guide targeting this keyword',
    'category' => 'content-update',
]);

// Minimal annotation
Annotation::create([
    'keyword_id' => 1,
    'project_id' => 1,
    'date' => '2026-02-15',
    'title' => 'Position spike observed',
]);
```

---

## Coverage Targets

| Category | Target | Tests |
|----------|--------|-------|
| Migration Structure | 100% | MT-001 to MT-013 (13 tests) |
| Foreign Key Constraints | 100% | FK-001 to FK-011 (11 tests) |
| Index Verification | 100% | IX-001 to IX-010 (10 tests) |
| Model Behavior | 100% | MD-001 to MD-010 (10 tests) |
| Seeder Functionality | 100% | SD-001 to SD-008 (8 tests) |
| Edge Cases | 100% | EC-001 to EC-015 (15 tests) |
| Category Values | 100% | CV-001 to CV-005 (5 tests) |
| Data Integrity | 100% | DI-001 to DI-007 (7 tests) |
| Query Performance | 100% | QP-001 to QP-007 (7 tests) |

**Total: 86 test scenarios**

---

## Test Execution

```bash
# Run all annotation table tests
php artisan test --filter=Annotation

# Run specific test file
php artisan test tests/Feature/Migrations/CreateAnnotationsTableTest.php

# Run with coverage
php artisan test --coverage --filter=Annotation
```

---

## Dependencies

| Dependency | Status | Notes |
|------------|--------|-------|
| Keywords table | ✅ Exists | Required for keyword_id FK |
| Projects table | ✅ Exists | Required for project_id FK |
| Keyword model | ✅ Exists | For relationship tests |
| Project model | ✅ Exists | For relationship tests |

---

## Notes for Developer

### Implementation Checklist
- [ ] Create migration file `YYYY_MM_DD_HHMMSS_create_annotations_table.php`
- [ ] Create Annotation model at `backend/app/Models/Annotation.php`
- [ ] Add `annotations()` relationship to Keyword model
- [ ] Add `annotations()` relationship to Project model
- [ ] Create AnnotationFactory for testing
- [ ] Create AnnotationSeeder with test data
- [ ] Run `php artisan migrate` to apply migration

### Migration File Template
```php
Schema::create('annotations', function (Blueprint $table) {
    $table->id();
    $table->foreignId('keyword_id')->constrained()->onDelete('cascade');
    $table->foreignId('project_id')->constrained()->onDelete('cascade');
    $table->date('date');
    $table->string('title');
    $table->text('description')->nullable();
    $table->string('category', 50)->default('other');
    $table->timestamps();

    $table->index('keyword_id');
    $table->index('project_id');
    $table->index('date');
    $table->index('category');
});
```

---

## Comments

### [2026-02-27 11:15] Agent: PreTester
**Test Documentation Created:**
- 86 test scenarios covering migration, constraints, indexes, models, seeders, edge cases, categories, and performance
- Focus on foreign key CASCADE behavior for both keyword_id and project_id
- Index tests ensure efficient queries for keyword_id, project_id, date, and category filtering
- Category tests cover default value and custom values (no enum restriction in schema)
- Edge cases cover VARCHAR limits, TEXT limits, NULL handling, special characters, and Unicode support

**Key Testing Priorities:**
1. FK-007/FK-008: CASCADE delete verification (critical for data integrity)
2. IX-003: Date index verification (critical for time-based queries)
3. EC-012: Multiple annotations per keyword per date allowed (no unique constraint)

**Status:** `Tests Coverage In Progress` → Ready for Developer implementation
