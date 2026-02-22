# TASK-10-1: Create Visibility Scores Table

## STATUS
`In Development`

## PRIORITY
`High`

## RISK
`Low`

## Description
Create a new database table (`visibility_scores`) to store daily calculated visibility scores for each project. These scores are used for trend analysis and dashboard metrics.

## Schema Requirements
```sql
CREATE TABLE visibility_scores (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    project_id BIGINT UNSIGNED NOT NULL,
    score TINYINT UNSIGNED NOT NULL, -- 0-100
    position_component DECIMAL(5,2) NOT NULL,
    serp_component DECIMAL(5,2) NOT NULL,
    volume_component DECIMAL(5,2) NOT NULL,
    keywords_count INT UNSIGNED NOT NULL DEFAULT 0,
    calculated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    INDEX idx_visibility_project_id (project_id),
    INDEX idx_visibility_calculated_at (calculated_at),
    UNIQUE KEY unique_project_date (project_id, DATE(calculated_at))
);
```

## Acceptance Criteria
- [ ] Migration creates the `visibility_scores` table.
- [ ] Foreign key references projects correctly.
- [ ] Indexes support efficient queries for trends and scoring.

## Effort Estimate
2-3 hours.

---

## Comments
### [2026-02-20 18:10] ProjectManager: Prioritization Complete
- TASK-10-1 prioritization confirmed as High for immediate development.
- Foundation for project visibility metrics but operates standalone from other database tasks.
- Next Status: To Develop, indicating readiness for implementation.

### [2026-02-20 17:58] Agent: SpecSystemAnalyst
Database task created to define the schema for storing project visibility scores over time.