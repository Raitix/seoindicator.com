# TASK-13-1: Create Backlink Summaries Table

## STATUS
`In Development`

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
- [x] Create table for storing backlink metrics.
  - Migration created: `2026_02_21_215300_create_backlink_summaries_table.php`
  - Table: `backlink_summaries` with columns: id, site_id, domain, total_backlinks, referring_domains, domain_authority, checked_at
  - Foreign key to `sites(id)` instead of `projects(id)` (consistency with existing schema)
  - Unique constraint on (site_id, domain)
- [x] Validate referential integrity with `sites` table.
  - Foreign key constraint: `backlink_summaries_site_id_foreign` referencing `sites(id)` ON DELETE CASCADE
  - Model relationship: `BacklinkSummary::site()` belongs to `Site::class`
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