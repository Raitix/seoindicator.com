# TASK-11-2: Create Project Members Table

## STATUS
`In Development`

## PRIORITY
`High`

## RISK
`Low`

## Description
Create the `project_members` table to enable user assignments at the project level, with roles specific to individual projects.

## Schema Requirements
```sql
CREATE TABLE project_members (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    project_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    role ENUM('editor', 'viewer') DEFAULT 'viewer',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,

    UNIQUE KEY idx_project_user (project_id, user_id)
);
```

## Acceptance Criteria
- [ ] Migration creates the `project_members` table with indexes.
- [ ] Foreign key constraints enforce referential integrity.
- [ ] Test records validated in staging.

## Effort Estimate
1-2 hours.

---

## Comments
### [2026-02-20 17:30] Agent: SpecSystemAnalyst
Database task created to define roles and permissions at the project level.

### [2026-02-26 18:10] ProjectManager: Prioritization Complete
- **Priority: High** (confirmed) - Foundation database task for project-level permissions
- **Risk: Low** (confirmed) - Non-destructive migration, adds table only
- **Dependency analysis:** No dependencies (foundation task)
- **Blocking:** TASK-11-4 runtime dependency (PermissionValidator needs `project_members` table)
- **Execution order:** Should complete before TASK-11-4 deployment proceeds
- **Status: To Write Tests** - Ready for PreTester to write migration tests

### [2026-02-26 19:03] Developer: Starting Implementation
- Creating migration for `project_members` table
- Lock file created: `.locks/TASK-11-2.lock`

### [2026-02-26 19:30] PreTester: Tests Written
- **Unit tests:** `tests/unit/TASK-11-2.test.js` - Schema validation, role enum, unique constraints
- **Integration tests:** `tests/integration/TASK-11-2.integration.test.js` - CRUD, FK constraints, cascade delete
- **Test documentation:** `tests/TEST-TASK-11-2.md` - Full test coverage matrix
- **Coverage areas:**
  - TC-1.x: Schema validation (5 tests)
  - TC-2.x: Foreign key constraints (6 tests)
  - TC-3.x: Unique constraints (4 tests)
  - TC-4.x: Role enum validation (4 tests)
  - TC-5.x: CRUD operations (5 tests)
- **Prerequisites:** Requires `projects` and `users` tables to exist
- **Status: To Develop** - Ready for Developer to implement migration