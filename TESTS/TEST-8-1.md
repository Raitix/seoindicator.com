# TEST-8-1: Projects Table Migration Tests

## Task Reference
- **Task:** [TASK-8-1](../TASKS/TASK-8-1.md)
- **Feature:** FEAT-008 Multi-Site Project Management
- **Specification:** SPEC-008

---

## Overview

Tests for the `projects` table migration. This table stores project information for multi-site data organization, linking projects to user accounts with support for default projects, archiving, and basic metadata.

---

## Test Files

| File | Type | Purpose |
|------|------|---------|
| `backend/tests/Unit/Migrations/CreateProjectsTableTest.php` | Unit | Migration file structure validation |
| `backend/tests/Unit/Models/ProjectTest.php` | Unit | Model behavior and relationships |
| `backend/tests/Feature/Database/ProjectsTableConstraintsTest.php` | Integration | FK constraints, indexes, cascades |
| `backend/database/seeders/ProjectSeeder.php` | Seeder | Test data generation |

---

## Test Scenarios

### Migration File Tests (MT)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| MT-001 | Migration file exists | `database/migrations/*_create_projects_table.php` | File found with valid naming |
| MT-002 | Migration creates projects table | Run migration | Table `projects` created |
| MT-003 | Table has correct columns | Inspect schema | All required columns present with correct types |
| MT-004 | Primary key is BIGINT UNSIGNED AUTO_INCREMENT | Inspect `id` column | Correct type and auto-increment |
| MT-005 | user_id column is BIGINT UNSIGNED NOT NULL | Inspect `user_id` column | Correct type, nullable=false |
| MT-006 | name column is VARCHAR(255) NOT NULL | Inspect `name` column | Correct type, nullable=false |
| MT-007 | domain column is VARCHAR(500) NULL | Inspect `domain` column | Correct type, nullable=true |
| MT-008 | notes column is TEXT NULL | Inspect `notes` column | Correct type, nullable=true |
| MT-009 | is_default column is BOOLEAN DEFAULT FALSE | Inspect `is_default` column | Correct type, default=false |
| MT-010 | archived_at column is TIMESTAMP NULL | Inspect `archived_at` column | Correct type, nullable=true |
| MT-011 | created_at column has default CURRENT_TIMESTAMP | Inspect `created_at` column | Default value set |
| MT-012 | updated_at has ON UPDATE CURRENT_TIMESTAMP | Inspect `updated_at` column | Auto-update configured |
| MT-013 | Migration can be rolled back | `php artisan migrate:rollback` | Table dropped successfully |

---

### Foreign Key Constraint Tests (FK)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| FK-001 | Foreign key exists on user_id | Inspect FK constraints | FK `projects_user_id_foreign` exists |
| FK-002 | FK references users.id | Inspect FK definition | References `users(id)` |
| FK-003 | FK has ON DELETE CASCADE | Inspect FK action | CASCADE on delete |
| FK-004 | Deleting user deletes all their projects | Create user + projects, delete user | All user's projects deleted |
| FK-005 | Creating project with non-existent user_id fails | Insert with invalid user_id | SQL constraint error |
| FK-006 | Multiple projects can share same user_id | Create multiple projects for same user | All projects created successfully |
| FK-007 | Project can be created with valid user_id | Create project with existing user | Project created successfully |

---

### Index Tests (IX)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| IX-001 | Index exists on user_id | `SHOW INDEX FROM projects` | `idx_projects_user_id` found |
| IX-002 | Index exists on archived_at | `SHOW INDEX FROM projects` | `idx_projects_archived` found |
| IX-003 | Query by user_id uses index | EXPLAIN SELECT * FROM projects WHERE user_id = ? | Index used (key column shows index) |
| IX-004 | Query by archived_at uses index | EXPLAIN SELECT * FROM projects WHERE archived_at IS NOT NULL | Index used |
| IX-005 | Query by archived_at IS NULL uses index | EXPLAIN SELECT * FROM projects WHERE archived_at IS NULL | Index can be used for NULL check |
| IX-006 | Composite query benefits from indexes | Query with user_id + archived_at | Uses appropriate index |

---

### Model Tests (MD)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| MD-001 | Project model exists | `backend/app/Models/Project.php` | File exists |
| MD-002 | Model has correct fillable attributes | Inspect `$fillable` | `user_id`, `name`, `domain`, `notes`, `is_default`, `archived_at` |
| MD-003 | Model has correct cast types | Inspect `$casts` | `is_default` => `boolean`, timestamps cast correctly |
| MD-004 | Model belongs to User relationship | `$project->user` | Returns User model |
| MD-005 | User has many Projects relationship | `$user->projects` | Returns Collection of Project models |
| MD-006 | Model can be created with factory | `Project::factory()->create()` | Project persisted to database |
| MD-007 | Model timestamps are auto-managed | Create project, check timestamps | created_at, updated_at set automatically |
| MD-008 | archived_at can be set and cleared | Set archived_at, then clear | Value persists and clears correctly |
| MD-009 | is_default defaults to false | Create project without is_default | Value is `false` |
| MD-010 | Model soft-delete behavior | Archive project (set archived_at) | Project not deleted but archived |

---

### Seeder Tests (SD)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| SD-001 | ProjectSeeder class exists | `database/seeders/ProjectSeeder.php` | File exists |
| SD-002 | Seeder creates test projects | Run seeder | Projects created in database |
| SD-003 | Seeder respects existing data | Run seeder twice | No duplicate projects or controlled behavior |
| SD-004 | Seeder creates projects for existing users | Run seeder | Projects linked to valid users |
| SD-005 | Seeder creates mix of default and non-default projects | Run seeder | Some `is_default=true`, some `false` |
| SD-006 | Seeder can be run via artisan | `php artisan db:seed --class=ProjectSeeder` | Command succeeds |
| SD-007 | Seeder creates archived and active projects | Run seeder | Mix of `archived_at` NULL and set values |

---

### Edge Case Tests (EC)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| EC-001 | Empty project name fails | Insert with name="" | Validation/constraint error |
| EC-002 | Very long project name (255 chars) | Name exactly 255 chars | Accepted |
| EC-003 | Project name exceeds 255 chars | Name 256 chars | Truncated or error (VARCHAR limit) |
| EC-004 | Very long domain (500 chars) | Domain exactly 500 chars | Accepted |
| EC-005 | Domain exceeds 500 chars | Domain 501 chars | Truncated or error |
| EC-006 | Notes with special characters | Notes with `<>&"'` etc | Stored and retrieved correctly |
| EC-007 | Notes with Unicode/emoji | Notes with emoji 🚀📈 | Stored and retrieved correctly |
| EC-008 | Notes with very long text | Notes > 65,535 chars (TEXT limit) | Stored correctly (TEXT can hold ~64KB) |
| EC-009 | Domain can be NULL | Create project with domain=null | Accepted |
| EC-010 | Notes can be NULL | Create project with notes=null | Accepted |
| EC-011 | Multiple default projects per user | Set is_default=true for multiple projects | Business logic decision needed |
| EC-012 | archived_at set to past date | Set archived_at to yesterday | Accepted |
| EC-013 | archived_at set to future date | Set archived_at to tomorrow | Accepted (business rule?) |
| EC-014 | Create project with minimum data | Only user_id + name | Project created (all optional fields null) |
| EC-015 | Delete user with many projects | User with 100 projects | All 100 projects deleted via CASCADE |

---

### Data Integrity Tests (DI)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| DI-001 | Cannot insert duplicate user_id with is_default=true | Two projects with is_default=true for same user | Business rule decision (allow or constraint) |
| DI-002 | Mass assignment protection | Try to set non-fillable attribute via mass assignment | Attribute not set |
| DI-003 | Timestamps are immutable after creation | Try to modify created_at via model | updated_at changes, created_at unchanged |
| DI-004 | Concurrent project creation | Two requests create project simultaneously | Both projects created (no race condition) |
| DI-005 | Project count per user | Query count after multiple inserts | Correct count returned |

---

### Query Performance Tests (QP)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| QP-001 | Select all projects for user | Query with user_id filter | Uses index, fast (<10ms for 1000 rows) |
| QP-002 | Select active projects only | Query with archived_at IS NULL | Uses index, fast |
| QP-003 | Select archived projects | Query with archived_at IS NOT NULL | Uses index, fast |
| QP-004 | Join with users table | Projects with user data | Efficient join |
| QP-005 | Pagination works correctly | Query with pagination | Correct offset/limit |

---

## Mock Data

### Sample User for Testing
```php
User::factory()->create([
    'id' => 1,
    'name' => 'Test User',
    'email' => 'test@example.com',
]);
```

### Sample Projects
```php
// Active project
Project::create([
    'user_id' => 1,
    'name' => 'Main Website',
    'domain' => 'https://example.com',
    'notes' => 'Primary company website',
    'is_default' => true,
    'archived_at' => null,
]);

// Archived project
Project::create([
    'user_id' => 1,
    'name' => 'Old Blog',
    'domain' => 'https://oldblog.example.com',
    'notes' => 'Discontinued blog',
    'is_default' => false,
    'archived_at' => '2026-01-15 10:00:00',
]);

// Minimal project
Project::create([
    'user_id' => 1,
    'name' => 'New Project',
]);
```

---

## Coverage Targets

| Category | Target | Tests |
|----------|--------|-------|
| Migration Structure | 100% | MT-001 to MT-013 (13 tests) |
| Foreign Key Constraints | 100% | FK-001 to FK-007 (7 tests) |
| Index Verification | 100% | IX-001 to IX-006 (6 tests) |
| Model Behavior | 100% | MD-001 to MD-010 (10 tests) |
| Seeder Functionality | 100% | SD-001 to SD-007 (7 tests) |
| Edge Cases | 100% | EC-001 to EC-015 (15 tests) |
| Data Integrity | 100% | DI-001 to DI-005 (5 tests) |
| Query Performance | 100% | QP-001 to QP-005 (5 tests) |

**Total: 68 test scenarios**

---

## Test Execution

```bash
# Run all project table tests
php artisan test --filter=Project

# Run specific test file
php artisan test tests/Unit/Migrations/CreateProjectsTableTest.php

# Run with coverage
php artisan test --coverage --filter=Project
```

---

## Dependencies

| Dependency | Status | Notes |
|------------|--------|-------|
| Users table | ✅ Exists | Pre-existing authentication system |
| User model | ✅ Exists | Pre-existing |

---

## Notes for Developer

### Implementation Checklist
- [ ] Create migration file `YYYY_MM_DD_HHMMSS_create_projects_table.php`
- [ ] Create Project model at `backend/app/Models/Project.php`
- [ ] Add `projects()` relationship to User model
- [ ] Create ProjectFactory for testing
- [ ] Create ProjectSeeder with test data
- [ ] Run `php artisan migrate` to apply migration

### Business Rule Decisions Needed
1. **Multiple default projects:** Should a user be allowed to have multiple `is_default=true` projects, or should setting one clear others?
2. **Future archive dates:** Should `archived_at` be allowed to be set in the future?

### Migration File Template
```php
Schema::create('projects', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->string('name');
    $table->string('domain', 500)->nullable();
    $table->text('notes')->nullable();
    $table->boolean('is_default')->default(false);
    $table->timestamp('archived_at')->nullable();
    $table->timestamps();

    $table->index('archived_at');
});
```

---

## Comments

### [2026-02-26 19:40] Agent: PreTester
**Test Documentation Created:**
- 68 test scenarios covering migration, constraints, indexes, models, seeders, edge cases, and performance
- Focus on foreign key CASCADE behavior (critical for multi-tenant data integrity)
- Index tests ensure efficient queries for user_id and archived_at filtering
- Edge cases cover VARCHAR limits, NULL handling, special characters, and Unicode support

**Key Testing Priorities:**
1. FK-004: CASCADE delete verification (most critical for data integrity)
2. EC-011: Multiple default projects behavior (business rule needed)
3. IX-003/IX-004: Index usage verification (performance critical for scale)

**Status:** `Tests Coverage In Progress` → Ready for Developer implementation
