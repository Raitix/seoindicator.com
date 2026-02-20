# TASK-8-3: Data Migration for Default Projects

## STATUS
`To Prioritize`

## PRIORITY
`Medium`

## RISK
`Medium`

## Description
Perform data migration to assign all existing user data to default projects. This is required to ensure backward compatibility and proper scoping of legacy data.

## Steps
1. Create a default project for each user:
```sql
INSERT INTO projects (user_id, name, domain, is_default, created_at, updated_at)
SELECT 
    id AS user_id, 
    'Default Project' AS name, 
    NULL AS domain, 
    TRUE AS is_default, 
    NOW() AS created_at, 
    NOW() AS updated_at
FROM users;
```

2. Assign existing records to default projects:
```sql
UPDATE keywords k
JOIN projects p ON p.user_id = k.user_id AND p.is_default = TRUE
SET k.project_id = p.id;

UPDATE competitors c
JOIN projects p ON p.user_id = c.user_id AND p.is_default = TRUE
SET c.project_id = p.id;

UPDATE reports r
JOIN projects p ON p.user_id = r.user_id AND p.is_default = TRUE
SET r.project_id = p.id;
```

3. Make `project_id` NOT NULL:
```sql
ALTER TABLE keywords MODIFY project_id BIGINT UNSIGNED NOT NULL;
ALTER TABLE competitors MODIFY project_id BIGINT UNSIGNED NOT NULL;
ALTER TABLE reports MODIFY project_id BIGINT UNSIGNED NOT NULL;
```

## Acceptance Criteria
- [ ] Default project created for every existing user.
- [ ] Keywords, competitors, reports all assigned to default projects.
- [ ] `project_id` NOT NULL constraint applied after migration.
- [ ] Migration tested in staging before production rollout.

## Dependencies
- TASK-8-1: Projects table must exist.
- TASK-8-2: Related tables (`keywords`, etc.) must have `project_id` column.

## Effort Estimate
4-6 hours.

---

## Comments
### [2026-02-20 17:15] Agent: SpecSystemAnalyst
Data migration task created to assign existing records to default projects for backward compatibility.