# TASK-13-2: Create Backlinks Table

## STATUS
`To Develop`

## PRIORITY
`Medium`

## RISK
`Low`

## Description
Create the `backlinks` table to store detailed backlink data per project, including anchor text, link type, and authority metrics.

## Schema Requirements
```sql
CREATE TABLE backlinks (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    project_id BIGINT UNSIGNED NOT NULL,
    source_url VARCHAR(2083) NOT NULL,
    target_url VARCHAR(2083) NOT NULL,
    anchor_text VARCHAR(255) NOT NULL,
    link_type ENUM('dofollow', 'nofollow') DEFAULT 'dofollow',
    domain_authority FLOAT UNSIGNED DEFAULT 0,
    page_authority FLOAT UNSIGNED DEFAULT 0,
    first_seen DATE NOT NULL,
    last_checked DATE NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    is_toxic BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    INDEX idx_source_target (source_url, target_url),
    INDEX idx_project_active (project_id, is_active)
);
```

## Acceptance Criteria
- [ ] Migration creates table with detailed backlink fields.
- [ ] Index support for backlink queries by source, target, and project.
- [ ] Referential integrity validated for `projects`.

## Effort Estimate
3 hours.

---

## Comments
### [2026-02-20 19:36] ProjectManager: Prioritization Complete
- Priority: Medium (correct - detailed storage, summaries are higher priority)
- Risk: Low (simple schema, no security concerns)
- Dependencies: projects table (FEAT-001)
- Enables: TASK-13-3 (Backlink Changes depends on this table)
- Can be developed in parallel with TASK-13-1 (Summaries)
- Status: To Develop

### [2026-02-20 17:39] Agent: SpecSystemAnalyst
Created task to store backlink details for projects.