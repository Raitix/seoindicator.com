# TASK-5-1: Database Schema - Keyword Suggestions Table

## STATUS
`To Write Tests`

## PRIORITY
`High`

## RISK
`Medium`

## Description
Create database migration for keyword_suggestions and dismissed_suggestions tables. This is the foundation for the keyword discovery feature.

## Acceptance Criteria
- [ ] Migration creates `keyword_suggestions` table with: id, site_id, keyword, source, search_volume, difficulty_score, opportunity_score, is_tracking, is_dismissed, discovered_at, updated_at
- [ ] Migration creates `dismissed_suggestions` table with: id, site_id, keyword, dismissed_at
- [ ] Foreign key constraints: keyword_suggestions.site_id → sites.id, dismissed_suggestions.site_id → sites.id
- [ ] Unique constraint on (site_id, keyword) in keyword_suggestions
- [ ] Indexes on: site_id, keyword, opportunity_score DESC
- [ ] Migration is reversible (down method drops tables)

## Dependencies
- sites table must exist (FEAT-001)
- FEAT-001 (Keyword Rank Tracking) must be available for tracking status

## Effort Estimate
`Small` (2-3 hours)

## Technical Notes
- Source column values: 'suggest', 'competitor', 'related'
- search_volume and difficulty_score nullable for MVP (no data provider yet)
- opportunity_score calculated by utility (TASK-5-3)

---

## Comments
_Agent communication log_

### [2026-02-26 22:42] Agent: ProjectManager
Created task from SPEC-005 breakdown. Priority: High (foundation task for all TASK-5-* tasks). Risk: Medium (non-destructive schema migration). Dependencies: FEAT-001 (sites table).
