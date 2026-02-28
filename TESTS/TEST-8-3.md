# TEST-8-3: Data Migration for Default Projects

## Task Reference
- **Task:** [TASK-8-3.md](../TASKS/TASK-8-3.md)
- **Feature:** FEAT-008 Multi-Site Project Management
- **Priority:** High
- **Risk:** Medium

---

## Overview
Test suite for data migration that assigns all existing user data to default projects. This migration ensures backward compatibility when introducing the multi-site project feature.

**Migration Steps:**
1. Create default project for each existing user
2. Assign keywords, competitors, reports to default projects
3. Apply NOT NULL constraint to `project_id` columns

**Risk Assessment:** Medium - Data migration with NOT NULL constraint requires careful validation.

---

## Test Files

| Type | Location | Status |
|------|----------|--------|
| Unit | `tests/Unit/Migrations/CreateDefaultProjectsMigrationTest.php` | ✅ Created (25 tests) |
| Feature | `tests/Feature/Migrations/DataMigrationTest.php` | ✅ Created (79 tests) |
| Feature | `tests/Feature/Migrations/DataMigrationRollbackTest.php` | ✅ Created (10 tests) |

---

## Test Scenarios

### Category 1: Default Project Creation (DP-001 to DP-010)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| DP-001 | Create default project for single user | 1 user exists | 1 project with `is_default=true` created |
| DP-002 | Create default projects for multiple users | 5 users exist | 5 projects created, each with `is_default=true` |
| DP-003 | Default project has correct attributes | User with ID 1 | Project: `name='Default Project'`, `user_id=1`, `is_default=true`, `domain=NULL` |
| DP-004 | No duplicate default projects | Migration runs twice | Only 1 default project per user (idempotent) |
| DP-005 | Handle users without existing data | User with no keywords/reports | Default project still created |
| DP-006 | Skip users that already have default project | User with existing default project | No new project created |
| DP-007 | Default project timestamps set correctly | User created | `created_at` and `updated_at` are current time |
| DP-008 | Default project linked to correct user | Multiple users | Each project's `user_id` matches corresponding user |
| DP-009 | Empty users table | No users exist | No projects created, no errors |
| DP-010 | Large user count | 1000 users | All 1000 default projects created efficiently |

### Category 2: Keywords Assignment (KA-001 to KA-010)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| KA-001 | Assign single keyword to default project | 1 keyword, user has default project | Keyword's `project_id` set to default project ID |
| KA-002 | Assign multiple keywords for same user | 10 keywords for user | All 10 keywords have same `project_id` |
| KA-003 | Assign keywords for multiple users | 5 users with 3 keywords each | Each user's keywords assigned to their default project |
| KA-004 | Keywords already with project_id | Keyword with `project_id=5` | Not modified (idempotent) |
| KA-005 | Keyword with NULL project_id | Keyword without project | Assigned to user's default project |
| KA-006 | Keyword user has no default project | Orphan keyword scenario | Migration fails or skips (define behavior) |
| KA-007 | Large keyword count per user | 500 keywords for user | All assigned efficiently (< 30s) |
| KA-008 | Keywords with same user_id across sites | User has keywords in multiple sites | All assigned to single default project |
| KA-009 | Verify foreign key integrity | Keywords assigned | All `project_id` values exist in `projects` table |
| KA-010 | Verify user-project match | Keywords assigned | `keyword.user_id === project.user_id` |

### Category 3: Competitors Assignment (CA-001 to CA-008)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CA-001 | Assign single competitor to default project | 1 competitor | `project_id` set to default project |
| CA-002 | Assign multiple competitors for user | 5 competitors | All assigned to default project |
| CA-003 | Competitors with soft deletes | 3 competitors (1 deleted) | Only active competitors assigned |
| CA-004 | Competitor already has project_id | Competitor with `project_id=3` | Not modified |
| CA-005 | Verify foreign key integrity | Competitors assigned | All `project_id` values valid |
| CA-006 | Verify user-project match | Competitors assigned | `competitor.user_id === project.user_id` |
| CA-007 | Large competitor count | 100 competitors | All assigned efficiently |
| CA-008 | User without competitors | User with 0 competitors | No error, migration continues |

### Category 4: Reports Assignment (RA-001 to RA-008)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| RA-001 | Assign single report to default project | 1 report | `project_id` set to default project |
| RA-002 | Assign multiple reports for user | 10 reports | All assigned to default project |
| RA-003 | Reports with different statuses | pending, completed, failed | All statuses handled correctly |
| RA-004 | Report already has project_id | Report with `project_id=2` | Not modified |
| RA-005 | Verify foreign key integrity | Reports assigned | All `project_id` values valid |
| RA-006 | Verify user-project match | Reports assigned | Report's site user matches project user |
| RA-007 | Large report count | 50 reports | All assigned efficiently |
| RA-008 | Reports with NULL site_id | Orphan report | Skipped or handled gracefully |

### Category 5: NOT NULL Constraint (NN-001 to NN-010)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| NN-001 | Apply NOT NULL after migration | All data migrated | Constraint applied successfully |
| NN-002 | Insert keyword without project_id | Keyword creation | Fails with constraint violation |
| NN-003 | Insert competitor without project_id | Competitor creation | Fails with constraint violation |
| NN-004 | Insert report without project_id | Report creation | Fails with constraint violation |
| NN-005 | Update keyword to NULL project_id | Update attempt | Fails with constraint violation |
| NN-006 | Verify existing data compliance | Post-migration check | All records have non-NULL project_id |
| NN-007 | Rollback removes NOT NULL | Rollback migration | Constraint removed, NULL allowed |
| NN-008 | Re-apply migration | Run migration again | Idempotent, constraint remains |
| NN-009 | Constraint name is descriptive | Check constraint | Named appropriately (e.g., `keywords_project_id_not_null`) |
| NN-010 | Performance impact of constraint | Large dataset | Insert/update performance acceptable |

### Category 6: Migration Safety (MS-001 to MS-010)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| MS-001 | Transaction rollback on error | Simulated error mid-migration | All changes rolled back |
| MS-002 | Migration is idempotent | Run migration twice | Same result, no duplicates |
| MS-003 | Dry run mode | `--dry-run` flag | Preview changes, no actual modifications |
| MS-004 | Progress reporting | Large dataset | Progress logged/printed |
| MS-005 | Error logging | Simulated error | Error details logged |
| MS-006 | Resume capability | Interrupted migration | Can resume from where it stopped |
| MS-007 | Memory efficiency | 10,000 records | Memory usage stays under limit |
| MS-008 | Lock handling | Concurrent migration attempt | Second attempt waits or fails gracefully |
| MS-009 | Staging validation | Run in staging env | Completes without errors |
| MS-010 | Production validation | Run in production | Data integrity verified |

### Category 7: Edge Cases (EC-001 to EC-015)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| EC-001 | No users in database | Empty users table | Migration completes, no errors |
| EC-002 | No keywords in database | Empty keywords table | Migration completes, no errors |
| EC-003 | No competitors in database | Empty competitors table | Migration completes, no errors |
| EC-004 | No reports in database | Empty reports table | Migration completes, no errors |
| EC-005 | User with data but no keywords | User with reports only | Reports assigned correctly |
| EC-006 | User with keywords only | User with keywords, no reports | Keywords assigned correctly |
| EC-007 | Orphaned keywords (no user) | Keyword with invalid user_id | Skipped or logged as error |
| EC-008 | User with multiple existing projects | User has 2 projects | Default project created, data assigned to it |
| EC-009 | User with existing default project | Default project already exists | Skipped, existing default used |
| EC-010 | Very long project name | User with special characters | Handled correctly (trimmed/escaped) |
| EC-011 | Unicode in user data | Keywords with emoji | Migration handles correctly |
| EC-012 | Concurrent data modifications | User adds keyword during migration | Handled by transaction |
| EC-013 | Database connection interruption | Connection lost mid-migration | Transaction rolled back |
| EC-014 | Disk space exhaustion | Low disk space | Appropriate error message |
| EC-015 | Timeout during large migration | 50,000+ records | Chunked processing completes |

### Category 8: Data Integrity Verification (DI-001 to DI-008)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| DI-001 | All keywords have project_id | Post-migration count | `COUNT(*) = COUNT(project_id)` |
| DI-002 | All competitors have project_id | Post-migration count | `COUNT(*) = COUNT(project_id)` |
| DI-003 | All reports have project_id | Post-migration count | `COUNT(*) = COUNT(project_id)` |
| DI-004 | All project_ids are valid | Check FK integrity | No orphaned records |
| DI-005 | All users have default project | Users vs projects | Each user has exactly 1 default project |
| DI-006 | No data loss | Before/after counts | Record counts match |
| DI-007 | Timestamps preserved | Original timestamps | `created_at`, `updated_at` unchanged |
| DI-008 | Data relationships intact | FK relationships | All relationships work correctly |

---

## Mock Data Samples

### Sample Users
```sql
INSERT INTO users (id, name, email, password, created_at, updated_at) VALUES
(1, 'User One', 'user1@test.com', 'hashed', NOW(), NOW()),
(2, 'User Two', 'user2@test.com', 'hashed', NOW(), NOW()),
(3, 'User Three', 'user3@test.com', 'hashed', NOW(), NOW());
```

### Sample Keywords
```sql
INSERT INTO keywords (id, user_id, site_id, keyword, project_id, created_at) VALUES
(1, 1, 1, 'seo tools', NULL, NOW()),
(2, 1, 1, 'keyword research', NULL, NOW()),
(3, 2, 1, 'backlink checker', NULL, NOW()),
(4, 3, 2, 'rank tracker', NULL, NOW());
```

### Sample Competitors
```sql
INSERT INTO competitors (id, user_id, domain, project_id, created_at) VALUES
(1, 1, 'competitor1.com', NULL, NOW()),
(2, 1, 'competitor2.com', NULL, NOW()),
(3, 2, 'competitor3.com', NULL, NOW());
```

### Sample Reports
```sql
INSERT INTO reports (id, site_id, name, type, status, project_id, created_at) VALUES
(1, 1, 'Weekly Report', 'weekly', 'completed', NULL, NOW()),
(2, 1, 'Monthly Report', 'monthly', 'pending', NULL, NOW()),
(3, 2, 'Daily Report', 'daily', 'completed', NULL, NOW());
```

---

## Coverage Targets

| Category | Test Count | Target Coverage |
|----------|------------|-----------------|
| Default Project Creation | 10 | 100% |
| Keywords Assignment | 10 | 100% |
| Competitors Assignment | 8 | 100% |
| Reports Assignment | 8 | 100% |
| NOT NULL Constraint | 10 | 100% |
| Migration Safety | 10 | 100% |
| Edge Cases | 15 | 100% |
| Data Integrity | 8 | 100% |
| **TOTAL** | **79** | **100%** |

---

## Test Execution

```bash
# Run all migration tests
php artisan test --filter=DataMigration

# Run specific test file
php artisan test tests/Feature/Migrations/DataMigrationTest.php

# Run with coverage
php artisan test --coverage --filter=DataMigration

# Run migration manually (staging)
php artisan migrate --path=database/migrations/xxx_create_default_projects_migration.php

# Rollback
php artisan migrate:rollback --step=1
```

---

## Dependencies

### Required (must exist before tests can pass)
- TASK-8-1: Projects table with `user_id`, `is_default` columns
- TASK-8-2: `project_id` columns added to `keywords`, `competitors`, `reports` tables

### External Services
- Database connection (MySQL/SQLite)
- No external API dependencies

---

## Security Considerations

| Concern | Mitigation |
|---------|------------|
| SQL Injection | Use parameterized queries in migration |
| Data Exposure | Migration runs in transaction, rollback on error |
| Data Loss | Backup before migration, validate after |
| Unauthorized Access | Migration runs via artisan (admin only) |

---

## Notes for Developer

### Critical Implementation Details

1. **Transaction Wrapping**: Wrap entire migration in a database transaction for atomic rollback.

2. **Chunked Processing**: Use chunking for large datasets:
   ```php
   Keyword::whereNull('project_id')->chunk(100, function ($keywords) {
       // Process batch
   });
   ```

3. **Idempotency**: Check for existing default projects and assigned records before processing.

4. **Logging**: Log each step with counts for audit trail:
   ```
   [INFO] Created 5 default projects
   [INFO] Assigned 150 keywords to projects
   [INFO] Assigned 25 competitors to projects
   [INFO] Assigned 30 reports to projects
   [INFO] Applied NOT NULL constraints
   ```

5. **Rollback Safety**: The rollback migration should:
   - Remove NOT NULL constraints
   - NOT delete created default projects (data preservation)
   - NOT nullify assigned project_ids (data preservation)

6. **Foreign Key Consideration**: For reports, the relationship goes through `site_id`. Verify that site's user matches project's user.

### Migration File Suggested Structure
```php
class CreateDefaultProjectsMigration extends Migration
{
    public function up()
    {
        DB::transaction(function () {
            // Step 1: Create default projects
            $this->createDefaultProjects();
            
            // Step 2: Assign keywords
            $this->assignKeywordsToProjects();
            
            // Step 3: Assign competitors
            $this->assignCompetitorsToProjects();
            
            // Step 4: Assign reports
            $this->assignReportsToProjects();
            
            // Step 5: Apply NOT NULL constraints
            $this->applyNotNullConstraints();
        });
    }
}
```

---

## Comments

### [2026-02-26 22:30] Agent: PreTester
**Test Documentation Created**

**Coverage Summary:**
- **79 test scenarios** across 8 categories
- Default Project Creation: 10 tests (DP-001 to DP-010)
- Keywords Assignment: 10 tests (KA-001 to KA-010)
- Competitors Assignment: 8 tests (CA-001 to CA-008)
- Reports Assignment: 8 tests (RA-001 to RA-008)
- NOT NULL Constraint: 10 tests (NN-001 to NN-010)
- Migration Safety: 10 tests (MS-001 to MS-010)
- Edge Cases: 15 tests (EC-001 to EC-015)
- Data Integrity: 8 tests (DI-001 to DI-008)

**Dependency Status:**
- TASK-8-1: `To Test Prod` - Projects table exists, deployed
- TASK-8-2: `Tests Coverage In Progress` - `project_id` columns being added

**Risk Considerations:**
- Medium risk due to NOT NULL constraint on production data
- Recommend staging validation before production rollout
- Transaction wrapping essential for rollback capability

**Status:** Changed from `To Write Tests` → `Tests Coverage In Progress`
