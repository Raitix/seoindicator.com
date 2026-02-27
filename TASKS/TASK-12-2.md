# TASK-12-2: Create GSC Data Table

## STATUS
`To Test Locally`

## PRIORITY
`High`

## RISK
`Low`

## Description
Create the `gsc_data` table to store imported Search Console metrics (clicks, impressions, CTR, position) per query and date.

## Schema Requirements
```sql
CREATE TABLE gsc_data (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    project_id BIGINT UNSIGNED NOT NULL,
    query VARCHAR(255) NOT NULL,
    page VARCHAR(255) NULL,
    clicks INT UNSIGNED NOT NULL DEFAULT 0,
    impressions INT UNSIGNED NOT NULL DEFAULT 0,
    ctr FLOAT UNSIGNED NOT NULL DEFAULT 0,
    avg_position FLOAT UNSIGNED NOT NULL DEFAULT 0,
    data_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    INDEX idx_query_date (query, data_date),
    INDEX idx_project_date (project_id, data_date)
);
```

## Acceptance Criteria
- [x] Migration creates the `gsc_data` table.
- [x] Indexes support efficient querying by project, query, and date.
- [x] Test data import validates schema constraints.

## Effort Estimate
2 hours.

---

## Comments
### [2026-02-27 23:45] Developer: Implementation Already Complete
- Migration already exists: `2026_02_21_191000_create_gsc_data_table.php`
- Model exists: `backend/app/Models/GSCData.php` with comprehensive scopes and relationships
- Tests passing: 9 tests, 44 assertions in `CreateGSCDataTableTest.php`
- Schema is MORE comprehensive than task requirements:
  - Uses `decimal` for CTR and position (more precise than `float`)
  - Includes both `project_id` and `site_id` FKs
  - Has additional indexes for better query performance
  - Has unique constraint to prevent duplicate data imports
- Factory exists with states: recent, highPerforming, opportunity
- Status: Moving to LocalTester for verification

### [2026-02-20 18:51] ProjectManager: Prioritization Complete
- Priority: High (correct - foundation for GSC data storage)
- Risk: Low (simple schema, no security concerns)
- Execution order: Second in FEAT-12, after TASK-12-1
- Can be developed in parallel with TASK-12-1 (no direct dependency)
- Status: To Develop

### [2026-02-20 17:34] Agent: SpecSystemAnalyst
Database task for storing imported GSC metrics.