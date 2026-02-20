# TASK-13-3: Create Backlink Changes Table

## STATUS
`To Develop`

## PRIORITY
`Medium`

## RISK
`Low`

## Description
Create the `backlink_changes` table to store detected changes (new, lost, or modified) for backlinks tracked by projects.

## Schema Requirements
```sql
CREATE TABLE backlink_changes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    project_id BIGINT UNSIGNED NOT NULL,
    backlink_id BIGINT UNSIGNED NOT NULL,
    change_type ENUM('new', 'lost', 'modified') NOT NULL,
    details JSON NOT NULL,
    detected_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (backlink_id) REFERENCES backlinks(id) ON DELETE CASCADE,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    INDEX idx_project_change (project_id, change_type)
);
```

## Acceptance Criteria
- [ ] Table supports tracking backlink changes.
- [ ] Referential integrity for `backlinks` and `projects`.
- [ ] Indexes optimize change queries by project and type.

## Effort Estimate
2 hours.

---

## Comments
### [2026-02-20 19:41] ProjectManager: Prioritization Complete
- Priority: Medium (correct - change tracking is valuable but not critical for MVP)
- Risk: Low (simple schema, JSON details column for flexibility)
- Dependencies: TASK-13-2 (backlinks table must exist)
- Execution order: After TASK-13-2 is complete
- Enables: Alerts for new/lost backlinks, change history UI
- Status: To Develop

### [2026-02-20 17:39] Agent: SpecSystemAnalyst
Created task for tracking backlink changes.