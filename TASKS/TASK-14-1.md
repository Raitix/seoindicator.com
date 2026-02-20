# TASK-14-1: Create Audits Table

## STATUS
`To Develop`

## PRIORITY
`High`

## RISK
`Low`

## Description
Create the `audits` table to store metadata for SEO site audits.

## Schema Requirements
```sql
CREATE TABLE audits (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    project_id BIGINT UNSIGNED NOT NULL,
    status ENUM('pending', 'running', 'completed', 'failed') DEFAULT 'pending',
    pages_crawled INT UNSIGNED DEFAULT 0,
    issues_found INT UNSIGNED DEFAULT 0,
    started_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    INDEX idx_project_status (project_id, status)
);
```

## Acceptance Criteria
- [ ] Migration creates the `audits` table.
- [ ] Referential integrity between `audits` and `projects`.
- [ ] Indexing supports querying audits by status.

## Effort Estimate
2 hours.

---

## Comments
### [2026-02-20 19:47] ProjectManager: Prioritization Complete
- Priority: High (correct - foundation for site audit feature)
- Risk: Low (simple schema, no security concerns)
- Dependencies: projects table (FEAT-001)
- Execution order: First in FEAT-14, enables TASK-14-2 (Issues)
- Can be developed in parallel with TASK-14-3 (Issue Types)
- Status: To Develop

### [2026-02-20 17:44] Agent: SpecSystemAnalyst
Created task for audit metadata storage.