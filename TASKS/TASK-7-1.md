# TASK-7-1: Database Schema - SERP Features Tables

## STATUS
`Tests Coverage In Progress`

## PRIORITY
`High`

## RISK
`Medium`

## Related
- Feature: FEAT-007
- Specification: SPEC-007
- Test: TESTS/SPEC-7-1.md

## Description
Create the database schema for SERP feature tracking, including `serp_features` table for storing feature presence data and `serp_feature_history` table for tracking feature changes over time. Also extend the `rankings` table to include a JSON column for quick feature access.

## Acceptance Criteria
- [ ] `serp_features` table created with all required columns (id, ranking_id, keyword_id, feature_type, is_present, position, url, title, snippet, checked_at)
- [ ] `serp_feature_history` table created with all required columns (id, keyword_id, feature_type, action, previous_url, current_url, detected_at)
- [ ] Foreign key constraints properly defined
- [ ] Indexes created for efficient queries (keyword_id, feature_type, is_present, checked_at, detected_at)
- [ ] Unique constraint on (ranking_id, feature_type) in serp_features
- [ ] `rankings` table extended with `serp_features` JSON column
- [ ] Migration file created and tested
- [ ] Rollback migration created

## Implementation Notes
- Use SQLite (existing database)
- JSON column for `serp_features` in rankings table should store: `{"featured_snippet": true, "local_pack": false, ...}`
- Indexes critical for query performance when filtering by feature type
- Migration should be idempotent where possible

## Dependencies
- FEAT-001 (Keyword Rank Tracking) - requires rankings and keywords tables

## Effort Estimate
Medium (3-5 hours)

---

## Comments
_Agent communication log_

### [2026-02-20 16:58] Agent: SpecSystemAnalyst
Created task for database schema. Foundation task - must be completed before other SERP feature tasks. Risk Medium due to schema changes affecting existing rankings table.
