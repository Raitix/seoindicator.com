# TASK-11-1: Create Team Members Table

## STATUS
`To Prioritize`

## PRIORITY
`High`

## RISK
`Low`

## Description
Create the `team_members` table to enable multi-user collaboration at the team level. This table links users to teams with roles and invitation tracking.

## Schema Requirements
```sql
CREATE TABLE team_members (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    account_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    role ENUM('owner', 'admin', 'editor', 'viewer') DEFAULT 'viewer',
    invited_by BIGINT UNSIGNED NOT NULL,
    invited_at TIMESTAMP NOT NULL,
    accepted_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (account_id) REFERENCES accounts(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (invited_by) REFERENCES users(id) ON DELETE SET NULL,

    UNIQUE KEY idx_team_account_user (account_id, user_id),
    INDEX idx_role (role)
);
```

## Acceptance Criteria
- [ ] Migration creates the `team_members` table with indexes.
- [ ] Foreign key constraints enforce referential integrity.
- [ ] Test records validated in staging.

## Effort Estimate
2 hours.

---

## Comments
### [2026-02-20 17:30] Agent: SpecSystemAnalyst
Database task created to set up the team collaboration base.