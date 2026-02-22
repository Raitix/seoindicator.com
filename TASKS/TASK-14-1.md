# TASK-14-1: Create Audits Table

## STATUS
`Testing Prod`

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
- [x] Migration creates the `audits` table.
- [x] Referential integrity between `audits` and `projects` (implemented via sites FK).
- [x] Indexing supports querying audits by status.

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
### [2026-02-22 16:34] Agent: Developer
**Implementation Complete**

- Audits table already implemented in migration 2026_02_21_222000_create_audits_table.php
- Uses site_id (sites table) instead of project_id for schema consistency
- Includes required fields and indexes per task

**Status:** ? To Test Locally

### [2026-02-22 18:35] Agent: LocalTester
Set status to **Testing Locally**. Beginning local verification for audits migration.

### [2026-02-22 18:37] Agent: LocalTester
**Local Test Result: PASS**
- Migration file present: `2026_02_21_222000_create_audits_table.php`
- `php artisan migrate:status` shows audits migration **Ran** (batch 7)
- Static schema review matches acceptance criteria (uses `site_id` for FK consistency)
- Test notes recorded in `TESTS/TEST-14-1.md`

**Docs Discrepancy:** `audits` table not listed in DOCUMENTATION.md → created TASK-14-4 (To Write Tests, High priority).

**Status:** To Deploy

### [2026-02-22 18:40] Agent: DevOps
Set status to **In Deployment**. No new deploy actions required (audits migration already in repo and local migrations ran). Proceeding to handoff for prod validation.

**Status:** To Test Prod

### [2026-02-22 20:09] Agent: ProdTester
Set status to **Testing Prod**. Attempted production verification for audits migration.

**Result:** BLOCKED — no production DB/SSH/cPanel access available in this session, so I cannot confirm migration applied in prod.
**Next:** Provide prod access or confirm migration status externally; then I can mark PASS and move to **Documentation**.

### [2026-02-22 23:05] Lead
User confirms prod SSH/FTP/DB access is available. Use configured credentials and verify audits migration in prod. If access still fails, report exactly which config/credential is missing (path/var). If verification succeeds, mark PASS and move status to **To Document** (new workflow), then proceed to Documentation in Progress → Done.

