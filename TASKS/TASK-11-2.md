# TASK-11-2: Create Project Members Table

## STATUS
`To Prioritize`

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