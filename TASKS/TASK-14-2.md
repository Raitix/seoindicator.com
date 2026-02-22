# TASK-14-2: Create Audit Issues Table

## STATUS
`To Develop`

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
- [x] Table supports recording and querying audit issues.
  - Table created: `audit_issues` with all required columns
  - Indexes created for efficient querying
- [x] Referential integrity with `audits` and `issue_types`.
  - Foreign key constraints: audit_id → audits(id) ON DELETE CASCADE
  - Foreign key constraints: issue_type_id → issue_types(id) ON DELETE CASCADE
  - Both audits and issue_types tables created as dependencies
- [x] Validation tests for severity levels and status changes.
  - Models include scopes and methods for severity and status management
  - Casts defined for proper type handling

## Effort Estimate
3 hours.

---

## Comments
### [2026-02-20 19:51] ProjectManager: Prioritization Complete
- Priority: High (correct - core table for storing detected SEO issues)
- Risk: Medium (foreign keys to audits and issue_types, severity validation)
- Dependencies: TASK-14-1 (audits table), TASK-14-3 (issue_types table)
- Execution order: After both TASK-14-1 and TASK-14-3 are complete
- Stores: URL, issue type, severity, description, recommendation, status
- Status: To Develop

### [2026-02-20 17:44] Agent: SpecSystemAnalyst
Created task for storing detailed SEO issues.