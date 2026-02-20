# TASK-13-1: Create Backlink Summaries Table

## STATUS
`To Develop`

## PRIORITY
`High`

## RISK
`Low`

## Description
Create the `backlink_summaries` table to store aggregated backlink metrics per domain for each project.

## Schema Requirements
```sql
CREATE TABLE backlink_summaries (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    project_id BIGINT UNSIGNED NOT NULL,
    domain VARCHAR(255) NOT NULL,
    total_backlinks INT UNSIGNED DEFAULT 0,
    referring_domains INT UNSIGNED DEFAULT 0,
    domain_authority FLOAT UNSIGNED DEFAULT 0,
    checked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    UNIQUE KEY idx_project_domain (project_id, domain)
);
```

## Acceptance Criteria
- [ ] Create table for storing backlink metrics.
- [ ] Validate referential integrity with `projects` table.
- [ ] Test data handling in staging.

## Effort Estimate
2 hours.

---

## Comments
### [2026-02-20 19:32] ProjectManager: Prioritization Complete
- Priority: High (correct - foundation for backlink monitoring)
- Risk: Low (simple schema, no security concerns)
- Dependencies: projects table (FEAT-001)
- Execution order: First in FEAT-13, enables all other backlink tasks
- Can be developed in parallel with TASK-13-2
- Status: To Develop

### [2026-02-20 17:39] Agent: SpecSystemAnalyst
Created task for backlink summary aggregation.