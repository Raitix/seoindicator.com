# TASK-9-1: Create Annotations Table

## STATUS
`To Write Tests`

## PRIORITY
`High`

## RISK
`Low`

## Description
Implement the `annotations` table in MySQL to enable keyword-level annotations for SEO events.

## Schema Requirements
```sql
CREATE TABLE annotations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    keyword_id BIGINT UNSIGNED NOT NULL,
    project_id BIGINT UNSIGNED NOT NULL,
    date DATE NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NULL,
    category VARCHAR(50) NOT NULL DEFAULT 'other',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (keyword_id) REFERENCES keywords(id) ON DELETE CASCADE,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    INDEX idx_annotations_keyword_id (keyword_id),
    INDEX idx_annotations_project_id (project_id),
    INDEX idx_annotations_date (date),
    INDEX idx_annotations_category (category)
);
```

## Acceptance Criteria
- [ ] Migration file creates the `annotations` table with specified constraints.
- [ ] Foreign keys `keyword_id` and `project_id` ensure referential integrity.
- [ ] Indexes support querying by `keyword_id`, `project_id`, `date`, and `category`.
- [ ] Seeder file for populating sample annotations for testing.

## Dependencies
- Task depends on existing `projects` and `keywords` tables already defined.

## Effort Estimate
2-3 hours.

---

## Comments
### [2026-02-20 17:50] Agent: SpecSystemAnalyst
Task for database schema creation to support keyword annotations.