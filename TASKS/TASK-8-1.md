# TASK-8-1: Create Projects Table

## STATUS
`To Prioritize`

## PRIORITY
`High`

## RISK
`Low`

## Description
Create the projects table for organizing multi-site data by users. This table stores basic project information and links each project to a user.

## Schema Requirements
```sql
CREATE TABLE projects (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,
    domain VARCHAR(500) NULL,
    notes TEXT NULL,
    is_default BOOLEAN DEFAULT FALSE,
    archived_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_projects_user_id (user_id),
    INDEX idx_projects_archived (archived_at)
);
```

## Acceptance Criteria
- [ ] Migration file for creating `projects` table is implemented.
- [ ] Project data includes user-level constraints (linked to user accounts).
- [ ] Foreign key constraints tested for deleting/cascading effects on projects.
- [ ] Indexes implemented for `user_id` and `archived_at` fields.
- [ ] Seeder file for inserting initial test data.

## Dependencies
- Requires user authentication system (pre-existing).

## Effort Estimate
2-3 hours.

---

## Comments
### [2026-02-20 17:15] Agent: SpecSystemAnalyst
Task created to define and implement the core `projects` table for multi-site data organization in the database.