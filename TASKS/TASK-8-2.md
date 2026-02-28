# TASK-8-2: Modify Existing Tables for Multi-Site Support

## STATUS
`Tests Written`

## PRIORITY
`High`

## RISK
`Medium`

## Description
Modify the `keywords`, `competitors`, `reports`, and `alert_settings` tables to include `project_id`. These modifications ensure all data is scoped to specific projects.

## Schema Requirements
```sql
-- Add project_id to keywords
ALTER TABLE keywords 
    ADD COLUMN project_id BIGINT UNSIGNED NULL AFTER user_id,
    ADD INDEX idx_keywords_project_id (project_id),
    ADD CONSTRAINT fk_keywords_project 
        FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE;

-- Add project_id to competitors
ALTER TABLE competitors 
    ADD COLUMN project_id BIGINT UNSIGNED NULL AFTER user_id,
    ADD INDEX idx_competitors_project_id (project_id),
    ADD CONSTRAINT fk_competitors_project 
        FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE;

-- Add project_id to reports
ALTER TABLE reports 
    ADD COLUMN project_id BIGINT UNSIGNED NULL AFTER user_id,
    ADD INDEX idx_reports_project_id (project_id),
    ADD CONSTRAINT fk_reports_project 
        FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE;

-- Add project_id to alert_settings
ALTER TABLE alert_settings 
    ADD COLUMN project_id BIGINT UNSIGNED NULL AFTER user_id,
    ADD INDEX idx_alert_settings_project_id (project_id),
    ADD CONSTRAINT fk_alert_settings_project 
        FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE;
```

## Acceptance Criteria
- [ ] Migration files implement schema modifications for all existing tables.
- [ ] New `project_id` column linked to the `projects` table.
- [ ] Foreign key constraints ensure cascade delete behavior.
- [ ] Indexes on `project_id` for efficient queries.
- [ ] Tests validate correct behavior (e.g., data isolation by project).

## Dependencies
- TASK-8-1: Projects table must exist before these modifications.

## Effort Estimate
3-5 hours.

---

## Comments
### [2026-02-20 17:15] Agent: SpecSystemAnalyst
Task created to extend existing tables (`keywords`, `competitors`, `reports`, etc.) for multi-site project data segregation.

### [2026-02-26 18:50] Agent: ProjectManager
**Prioritization Decision:**
- **Priority: High** (confirmed) - Foundation task for FEAT-008 Multi-Site Project Management
- **Risk: Medium** (confirmed) - Non-destructive schema changes (ADD COLUMN, indexes, FKs)

**Dependency Analysis:**
- **Depends on:** TASK-8-1 (Create Projects Table) - status `To Write Tests`
- **Blocks:** TASK-8-3 (Data Migration), TASK-8-4 (Middleware), TASK-8-5 (Global Scope)

**Execution Order within FEAT-008:**
1. TASK-8-1 → Projects table (foundation) ← Already prioritized
2. TASK-8-2 → Modify tables (this task) ← Must run after TASK-8-1
3. TASK-8-3 → Data migration ← Requires both tables ready
4. TASK-8-4 → Middleware ← Context injection
5. TASK-8-5 → Global Scope ← Query filtering
6. TASK-8-6 → Policies ← Authorization
7. TASK-8-7 → CRUD Endpoints ← API layer
8. TASK-8-8 → Frontend ← UI components
9. TASK-8-9 → Unit Tests ← Backend testing
10. TASK-8-10 → Integration Tests ← E2E testing

**Risk Mitigation Notes:**
- Columns added as nullable first (safer approach)
- Foreign keys with CASCADE delete ensure referential integrity
- Indexes on `project_id` will improve query performance
- Task can proceed in parallel with TASK-8-1 if migrations are carefully ordered

### [2026-02-28 16:15 GMT+2] Agent: PreTester
**Tests Written:**
Created comprehensive migration test file: `backend/tests/Feature/Migrations/AddProjectIdToTablesTest.php`

**Test Coverage:**
- Column existence: Tests for `project_id` on keywords, competitors, reports, alert_settings
- Foreign key constraints: Verifies FKs to projects table on all four tables
- Indexes: Verifies indexes on `project_id` for query optimization
- Cascade delete: Tests that deleting a project cascades to related records
- Nullable support: Tests that `project_id` accepts null values (for gradual migration)
- Update capability: Tests that `project_id` can be updated to link records
- Rollback: Tests migration rollback removes columns
- Rerun: Tests migration can be re-run after rollback
- Data isolation: Tests records with different project_ids coexist properly
- Column position: Tests `project_id` is positioned after `user_id`

**Status:** `Tests Written` → Ready for Developer to create migration file