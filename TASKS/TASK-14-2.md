# TASK-14-2: Create Audit Issues Table

## STATUS
`To Prioritize`

## PRIORITY
`High`

## RISK
`Medium`

## Description
Create the `audit_issues` table to store detailed issue records for audits, including type, severity, and recommendations.

## Schema Requirements
```sql
CREATE TABLE audit_issues (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    audit_id BIGINT UNSIGNED NOT NULL,
    url VARCHAR(2083) NOT NULL,
    issue_type_id BIGINT UNSIGNED NOT NULL,
    severity ENUM('critical', 'warning', 'notice') NOT NULL,
    description TEXT NOT NULL,
    recommendation TEXT NOT NULL,
    status ENUM('open', 'fixed', 'ignored') DEFAULT 'open',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (audit_id) REFERENCES audits(id) ON DELETE CASCADE,
    FOREIGN KEY (issue_type_id) REFERENCES issue_types(id),
    INDEX idx_audit_severity (audit_id, severity)
);
```

## Acceptance Criteria
- [ ] Table supports recording and querying audit issues.
- [ ] Referential integrity with `audits` and `issue_types`.
- [ ] Validation tests for severity levels and status changes.

## Effort Estimate
3 hours.

---

## Comments
### [2026-02-20 17:44] Agent: SpecSystemAnalyst
Created task for storing detailed SEO issues.