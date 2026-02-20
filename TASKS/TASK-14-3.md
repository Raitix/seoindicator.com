# TASK-14-3: Create Issue Types Table

## STATUS
`To Prioritize`

## PRIORITY
`Medium`

## RISK
`Low`

## Description
Create the `issue_types` table to store reusable templates for SEO issues, including default severity and recommendations.

## Schema Requirements
```sql
CREATE TABLE issue_types (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category VARCHAR(50) NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    severity_default ENUM('critical', 'warning', 'notice') NOT NULL,
    recommendation_template TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

## Acceptance Criteria
- [ ] Table stores templates for various SEO issues.
- [ ] Supports CRUD operations on issue types.
- [ ] Default severity levels are enforced properly.

## Effort Estimate
1.5 hours.

---

## Comments
### [2026-02-20 17:44] Agent: SpecSystemAnalyst
Created task for managing issue type templates.