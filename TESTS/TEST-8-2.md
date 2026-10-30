# TEST-8-2: Modify Existing Tables for Multi-Site Support

## Task Reference
- **Task:** [TASK-8-2](../TASKS/TASK-8-2.md)
- **Feature:** FEAT-008 Multi-Site Project Management
- **Specification:** SPEC-008

---

## Overview

Tests for modifying existing tables (`keywords`, `competitors`, `reports`, `alert_settings`) to include `project_id` foreign key columns. These modifications enable multi-site data isolation by scoping all data to specific projects.

---

## Test Files

| File | Type | Purpose |
|------|------|---------|
| `backend/tests/Unit/Migrations/AddProjectIdToKeywordsTableTest.php` | Unit | Keywords table migration tests |
| `backend/tests/Unit/Migrations/AddProjectIdToCompetitorsTableTest.php` | Unit | Competitors table migration tests |
| `backend/tests/Unit/Migrations/AddProjectIdToReportsTableTest.php` | Unit | Reports table migration tests |
| `backend/tests/Unit/Migrations/AddProjectIdToAlertSettingsTableTest.php` | Unit | Alert settings table migration tests |
| `backend/tests/Feature/Database/ProjectIdConstraintsTest.php` | Integration | Cross-table FK constraints |
| `backend/tests/Feature/Database/ProjectDataIsolationTest.php` | Integration | Data isolation by project |

---

## Test Scenarios

### Keywords Table Migration Tests (KW)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| KW-001 | Migration adds project_id column | Run migration | Column `project_id` exists on keywords table |
| KW-002 | project_id is BIGINT UNSIGNED NULL | Inspect column | Correct type, nullable=true |
| KW-003 | project_id column positioned after user_id | Inspect column order | Column after `user_id` |
| KW-004 | Index on project_id created | `SHOW INDEX FROM keywords` | `idx_keywords_project_id` exists |
| KW-005 | Foreign key constraint exists | Inspect FK constraints | `fk_keywords_project` exists |
| KW-006 | FK references projects.id | Inspect FK definition | References `projects(id)` |
| KW-007 | FK has ON DELETE CASCADE | Inspect FK action | CASCADE on delete |
| KW-008 | Existing keywords retain project_id NULL | Run migration on populated table | Existing rows have NULL project_id |
| KW-009 | Migration can be rolled back | `php artisan migrate:rollback` | Column removed successfully |
| KW-010 | Keyword can be created with project_id | Insert with project_id | Row created successfully |
| KW-011 | Keyword can be created without project_id | Insert with project_id=NULL | Row created (NULL allowed) |
| KW-012 | Keyword with non-existent project_id fails | Insert with invalid project_id | SQL constraint error |

### Competitors Table Migration Tests (CP)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CP-001 | Migration adds project_id column | Run migration | Column `project_id` exists on competitors table |
| CP-002 | project_id is BIGINT UNSIGNED NULL | Inspect column | Correct type, nullable=true |
| CP-003 | project_id column positioned after user_id | Inspect column order | Column after `user_id` |
| CP-004 | Index on project_id created | `SHOW INDEX FROM competitors` | `idx_competitors_project_id` exists |
| CP-005 | Foreign key constraint exists | Inspect FK constraints | `fk_competitors_project` exists |
| CP-006 | FK references projects.id | Inspect FK definition | References `projects(id)` |
| CP-007 | FK has ON DELETE CASCADE | Inspect FK action | CASCADE on delete |
| CP-008 | Existing competitors retain project_id NULL | Run migration on populated table | Existing rows have NULL project_id |
| CP-009 | Migration can be rolled back | `php artisan migrate:rollback` | Column removed successfully |
| CP-010 | Competitor can be created with project_id | Insert with project_id | Row created successfully |
| CP-011 | Competitor can be created without project_id | Insert with project_id=NULL | Row created (NULL allowed) |
| CP-012 | Competitor with non-existent project_id fails | Insert with invalid project_id | SQL constraint error |

### Reports Table Migration Tests (RP)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| RP-001 | Migration adds project_id column | Run migration | Column `project_id` exists on reports table |
| RP-002 | project_id is BIGINT UNSIGNED NULL | Inspect column | Correct type, nullable=true |
| RP-003 | project_id column positioned after user_id | Inspect column order | Column after `user_id` |
| RP-004 | Index on project_id created | `SHOW INDEX FROM reports` | `idx_reports_project_id` exists |
| RP-005 | Foreign key constraint exists | Inspect FK constraints | `fk_reports_project` exists |
| RP-006 | FK references projects.id | Inspect FK definition | References `projects(id)` |
| RP-007 | FK has ON DELETE CASCADE | Inspect FK action | CASCADE on delete |
| RP-008 | Existing reports retain project_id NULL | Run migration on populated table | Existing rows have NULL project_id |
| RP-009 | Migration can be rolled back | `php artisan migrate:rollback` | Column removed successfully |
| RP-010 | Report can be created with project_id | Insert with project_id | Row created successfully |
| RP-011 | Report can be created without project_id | Insert with project_id=NULL | Row created (NULL allowed) |
| RP-012 | Report with non-existent project_id fails | Insert with invalid project_id | SQL constraint error |

### Alert Settings Table Migration Tests (AS)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| AS-001 | Migration adds project_id column | Run migration | Column `project_id` exists on alert_settings table |
| AS-002 | project_id is BIGINT UNSIGNED NULL | Inspect column | Correct type, nullable=true |
| AS-003 | project_id column positioned after user_id | Inspect column order | Column after `user_id` |
| AS-004 | Index on project_id created | `SHOW INDEX FROM alert_settings` | `idx_alert_settings_project_id` exists |
| AS-005 | Foreign key constraint exists | Inspect FK constraints | `fk_alert_settings_project` exists |
| AS-006 | FK references projects.id | Inspect FK definition | References `projects(id)` |
| AS-007 | FK has ON DELETE CASCADE | Inspect FK action | CASCADE on delete |
| AS-008 | Existing alert_settings retain project_id NULL | Run migration on populated table | Existing rows have NULL project_id |
| AS-009 | Migration can be rolled back | `php artisan migrate:rollback` | Column removed successfully |
| AS-010 | Alert setting can be created with project_id | Insert with project_id | Row created successfully |
| AS-011 | Alert setting can be created without project_id | Insert with project_id=NULL | Row created (NULL allowed) |
| AS-012 | Alert setting with non-existent project_id fails | Insert with invalid project_id | SQL constraint error |

---

### Foreign Key Cascade Tests (FK)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| FK-001 | Deleting project deletes all keywords | Create project + keywords, delete project | All project's keywords deleted |
| FK-002 | Deleting project deletes all competitors | Create project + competitors, delete project | All project's competitors deleted |
| FK-003 | Deleting project deletes all reports | Create project + reports, delete project | All project's reports deleted |
| FK-004 | Deleting project deletes all alert_settings | Create project + alerts, delete project | All project's alerts deleted |
| FK-005 | Deleting project with mixed data | Project with all 4 types of data | All 4 types deleted |
| FK-006 | Deleting project preserves other projects' data | Two projects, delete one | Other project's data intact |
| FK-007 | Deleting user cascades through projects | User with projects + keywords | User → Projects → Keywords all deleted |

---

### Data Isolation Tests (DI)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| DI-001 | Keywords isolated by project | Query keywords with project_id=X | Only keywords for project X returned |
| DI-002 | Competitors isolated by project | Query competitors with project_id=X | Only competitors for project X returned |
| DI-003 | Reports isolated by project | Query reports with project_id=X | Only reports for project X returned |
| DI-004 | Alert settings isolated by project | Query alerts with project_id=X | Only alerts for project X returned |
| DI-005 | Mixed project_id values | Some NULL, some set | Queries filter correctly by project |
| DI-006 | NULL project_id access | Query with project_id=NULL | Returns rows without project assignment |
| DI-007 | Cross-project data leak attempt | User queries project A, tries to access B | Authorization prevents access |
| DI-008 | Project scope enforced on create | Create keyword with different user's project_id | Validation/authorization error |

---

### Index Performance Tests (IX)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| IX-001 | Keywords query by project_id uses index | EXPLAIN SELECT * FROM keywords WHERE project_id = ? | Index used |
| IX-002 | Competitors query by project_id uses index | EXPLAIN SELECT * FROM competitors WHERE project_id = ? | Index used |
| IX-003 | Reports query by project_id uses index | EXPLAIN SELECT * FROM reports WHERE project_id = ? | Index used |
| IX-004 | Alert settings query by project_id uses index | EXPLAIN SELECT * FROM alert_settings WHERE project_id = ? | Index used |
| IX-005 | Composite query (project_id + user_id) | Query with both filters | Uses optimal index |
| IX-006 | Index cardinality reasonable | ANALYZE TABLE | Index statistics updated |

---

### Model Relationship Tests (MR)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| MR-001 | Project has many keywords | `$project->keywords` | Returns Collection of Keyword models |
| MR-002 | Keyword belongs to project | `$keyword->project` | Returns Project model |
| MR-003 | Project has many competitors | `$project->competitors` | Returns Collection of Competitor models |
| MR-004 | Competitor belongs to project | `$competitor->project` | Returns Project model |
| MR-005 | Project has many reports | `$project->reports` | Returns Collection of Report models |
| MR-006 | Report belongs to project | `$report->project` | Returns Project model |
| MR-007 | Project has many alert_settings | `$project->alertSettings` | Returns Collection of AlertSetting models |
| MR-008 | AlertSetting belongs to project | `$alertSetting->project` | Returns Project model |
| MR-009 | Eager loading works | `Project::with('keywords')->get()` | N+1 query prevented |
| MR-010 | Lazy loading works | `$project->keywords->first()` | Related models loaded on access |

---

### Edge Case Tests (EC)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| EC-001 | Migrate existing populated table | Table with 1000 rows | All rows get project_id=NULL |
| EC-002 | Assign project to existing keyword | Update keyword set project_id=X | Update succeeds |
| EC-003 | Unassign project from keyword | Update keyword set project_id=NULL | Update succeeds |
| EC-004 | Query with project_id list | WHERE project_id IN (1,2,3) | Uses index, returns correct rows |
| EC-005 | Mass update project_id | UPDATE keywords SET project_id=X WHERE ... | Bulk update succeeds |
| EC-006 | Transaction with project assignment | Create project + keywords in transaction | Atomic commit or rollback |
| EC-007 | Concurrent project assignment | Two requests assign same keyword | Last write wins or constraint error |
| EC-008 | Project deletion with large dataset | Project with 10,000 keywords | CASCADE completes without timeout |
| EC-009 | NULL project_id with site_id | Keyword has site_id set, project_id NULL | Both NULLs coexist (transition state) |
| EC-010 | Foreign key check disabled | SET FOREIGN_KEY_CHECKS=0 | Migration works in special cases |

---

### Backward Compatibility Tests (BC)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| BC-001 | Existing queries still work | Query without project_id filter | Returns all rows (including NULLs) |
| BC-002 | Existing model methods work | `$keyword->site` | Relationship still functional |
| BC-003 | Existing factories work | `Keyword::factory()->create()` | Creates with project_id=NULL |
| BC-004 | Existing seeders work | Run existing seeders | Data created successfully |
| BC-005 | API endpoints unchanged | GET /api/v1/sites/{id}/keywords | Response structure unchanged |
| BC-006 | Existing tests pass | Run existing test suite | All tests still pass |

---

## Mock Data

### Sample Projects
```php
Project::create([
    'id' => 1,
    'user_id' => 1,
    'name' => 'E-commerce Site',
    'domain' => 'https://shop.example.com',
]);

Project::create([
    'id' => 2,
    'user_id' => 1,
    'name' => 'Blog Site',
    'domain' => 'https://blog.example.com',
]);
```

### Sample Keywords with Project Assignment
```php
// Keyword assigned to project 1
Keyword::create([
    'user_id' => 1,
    'site_id' => 1,
    'project_id' => 1,
    'keyword' => 'buy shoes online',
    'search_engine' => 'google',
    'location' => 'us',
    'device' => 'desktop',
]);

// Keyword not assigned to any project (legacy data)
Keyword::create([
    'user_id' => 1,
    'site_id' => 1,
    'project_id' => null,
    'keyword' => 'legacy keyword',
    'search_engine' => 'google',
    'location' => 'us',
    'device' => 'desktop',
]);
```

### Sample Competitors
```php
Competitor::create([
    'user_id' => 1,
    'project_id' => 1,
    'domain' => 'competitor1.com',
    'name' => 'Main Competitor',
]);
```

### Sample Reports
```php
Report::create([
    'site_id' => 1,
    'project_id' => 1,
    'name' => 'Weekly Report',
    'type' => 'weekly',
    'format' => 'pdf',
    'status' => 'completed',
    'date_range_start' => '2026-02-19',
    'date_range_end' => '2026-02-26',
]);
```

### Sample Alert Settings
```php
AlertSetting::create([
    'user_id' => 1,
    'project_id' => 1,
    'alert_type' => 'ranking_drop',
    'threshold' => 5,
    'enabled' => true,
]);
```

---

## Coverage Targets

| Category | Target | Tests |
|----------|--------|-------|
| Keywords Table Migration | 100% | KW-001 to KW-012 (12 tests) |
| Competitors Table Migration | 100% | CP-001 to CP-012 (12 tests) |
| Reports Table Migration | 100% | RP-001 to RP-012 (12 tests) |
| Alert Settings Table Migration | 100% | AS-001 to AS-012 (12 tests) |
| Foreign Key Cascade | 100% | FK-001 to FK-007 (7 tests) |
| Data Isolation | 100% | DI-001 to DI-008 (8 tests) |
| Index Performance | 100% | IX-001 to IX-006 (6 tests) |
| Model Relationships | 100% | MR-001 to MR-010 (10 tests) |
| Edge Cases | 100% | EC-001 to EC-010 (10 tests) |
| Backward Compatibility | 100% | BC-001 to BC-006 (6 tests) |

**Total: 95 test scenarios**

---

## Test Execution

```bash
# Run all project_id migration tests
php artisan test --filter="ProjectId"

# Run specific test file
php artisan test tests/Feature/Database/ProjectIdConstraintsTest.php

# Run data isolation tests
php artisan test tests/Feature/Database/ProjectDataIsolationTest.php

# Run with coverage
php artisan test --coverage --filter="Project"
```

---

## Dependencies

| Dependency | Status | Notes |
|------------|--------|-------|
| Projects table (TASK-8-1) | 🔄 In Development | Required before migrations |
| Keywords table | ✅ Exists | From TASK-1-1 |
| Competitors table | ✅ Exists | Existing table |
| Reports table | ✅ Exists | From TASK-4-1 |
| Alert settings table | ✅ Exists | Existing table |

---

## Notes for Developer

### Implementation Checklist
- [ ] Create migration `add_project_id_to_keywords_table.php`
- [ ] Create migration `add_project_id_to_competitors_table.php`
- [ ] Create migration `add_project_id_to_reports_table.php`
- [ ] Create migration `add_project_id_to_alert_settings_table.php`
- [ ] Update Keyword model with `project()` relationship
- [ ] Update Competitor model with `project()` relationship
- [ ] Update Report model with `project()` relationship
- [ ] Update AlertSetting model with `project()` relationship
- [ ] Update Project model with `hasMany` relationships
- [ ] Run all migrations: `php artisan migrate`

### Migration Order
**CRITICAL:** These migrations must run AFTER the projects table is created (TASK-8-1).

Migration files should be timestamped to ensure correct order:
```
2026_02_26_120000_create_projects_table.php           (TASK-8-1)
2026_02_26_130000_add_project_id_to_keywords_table.php
2026_02_26_130100_add_project_id_to_competitors_table.php
2026_02_26_130200_add_project_id_to_reports_table.php
2026_02_26_130300_add_project_id_to_alert_settings_table.php
```

### Migration File Template
```php
// add_project_id_to_keywords_table.php
Schema::table('keywords', function (Blueprint $table) {
    $table->unsignedBigInteger('project_id')
        ->nullable()
        ->after('user_id');
    
    $table->foreign('project_id')
        ->references('id')
        ->on('projects')
        ->onDelete('cascade');
    
    $table->index('project_id', 'idx_keywords_project_id');
});

// Down method
Schema::table('keywords', function (Blueprint $table) {
    $table->dropForeign(['project_id']);
    $table->dropIndex('idx_keywords_project_id');
    $table->dropColumn('project_id');
});
```

### NULL Constraint Rationale
The `project_id` column is **nullable** to:
1. Support gradual migration of existing data
2. Allow legacy data to remain functional
3. Enable data migration task (TASK-8-3) to run separately

### Security Considerations
- **Data Isolation:** Ensure queries are scoped by project_id in middleware (TASK-8-4)
- **Authorization:** Verify user owns project before assigning data to it
- **Cascade Delete:** Test thoroughly to prevent accidental data loss

---

## Comments

### [2026-02-26 20:02] Agent: PreTester
**Test Documentation Created:**
- 95 test scenarios covering all 4 table migrations
- Focus on CASCADE delete behavior (critical for data integrity)
- Data isolation tests ensure multi-tenant security
- Backward compatibility tests ensure existing functionality preserved

**Critical Tests:**
- FK-001 to FK-007: CASCADE delete verification (most critical)
- DI-001 to DI-008: Data isolation by project (security critical)
- BC-001 to BC-006: Backward compatibility (transition safety)

**Dependency Note:**
- Requires TASK-8-1 (Projects table) to be completed first
- Migration order is critical - these must run after projects table

**Status:** `Tests Coverage In Progress` → Ready for Developer implementation
