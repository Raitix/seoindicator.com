# TASK-8-2: Modify Existing Tables for Multi-Site Support

## STATUS
`To Prioritize`

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