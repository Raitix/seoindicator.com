# SPEC-001-1: Database Setup - Keywords & Rankings Tables

## STATUS`Implementing`

## PRIORITY
`High`

## RISK
`Medium`

## Description
Create database migrations for keywords and ranking_history tables. This is the foundation for the keyword rank tracking feature.

## Acceptance Criteria
- [x] Migration creates `keywords` table with: id, user_id, site_id, keyword, target_url, search_engine, location, device, status, last_checked_at, created_at, updated_at
  - *Note: keywords table already exists, added site_id column via migration*
- [x] Migration creates `ranking_history` table with: id, keyword_id, position, url_found, serp_features, checked_at, check_duration_ms, error
  - *Created ranking_history table with all required columns*
- [x] Foreign key constraints: keywords.site_id → sites.id, ranking_history.keyword_id → keywords.id
  - *Added foreign key constraints in migrations*
- [x] Unique constraint on (site_id, keyword, search_engine, location, device) in keywords table
  - *Added unique constraint named keywords_unique*
- [x] Indexes on: keywords.site_id, keywords.status, keywords.last_checked_at, ranking_history.keyword_id, ranking_history.checked_at, ranking_history(keyword_id, checked_at)
  - *All indexes created as specified*
- [x] Migration is reversible (down method drops tables)
  - *Both migrations have proper down() methods*
- [ ] Migration runs successfully on local MySQL/SQLite
  - *Need to test migration execution*

## Dependencies
- sites table must exist (or create as part of this task)

## Effort Estimate
`Small` (2-3 hours)

## Technical Notes
- Use Laravel migration syntax (PHP) or TypeScript migration tool
- Tables follow existing naming conventions
- Consider soft deletes for keywords (allow re-activation)
- serp_features column stores JSON data

---

## Comments
_Agent communication log_

### [2026-02-20 09:44] Agent: SpecSystemAnalyst
Created task for database migration. This is the foundation task - must be completed before backend work can begin. Set priority to High due to dependency chain.

### [2026-02-21 18:55] Agent: Developer
**Starting Implementation**

1. Found existing keywords migration (2026_02_20_010700_create_keywords_table.php) but it needs updates to match SPEC-001 requirements.
2. Need to add site_id column and create ranking_history table.
3. First, checking if sites table exists or needs to be created.
