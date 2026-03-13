# TASK-19-1: Database Migration - Analysis Jobs Tables

## STATUS
`Testing Prod`

## PRIORITY
`High`

## RISK
`Medium`

## Description
Create database migrations for `analysis_jobs` and `analysis_job_results` tables. This is the foundation for the progressive analysis system.

## Acceptance Criteria
- [ ] Migration creates `analysis_jobs` table with:
  - id, user_id, site_id, status (enum: pending, running, completed, cancelled, failed)
  - total_indicators, completed_indicators, failed_indicators, progress_percent
  - started_at, completed_at, cancelled_at, error_message
  - created_at, updated_at
- [ ] Migration creates `analysis_job_results` table with:
  - id, job_id, indicator_slug, indicator_name, status (enum: pending, running, completed, failed, skipped)
  - score, max_score, percentage, data (JSON), recommendations (JSON)
  - error_message, started_at, completed_at, processing_time_ms
  - created_at, updated_at
- [ ] Foreign key constraints: analysis_jobs.user_id â†’ users.id, analysis_jobs.site_id â†’ sites.id, analysis_job_results.job_id â†’ analysis_jobs.id
- [ ] Indexes on: user_id, site_id, status, created_at for jobs; job_id, status for results
- [ ] Unique constraint on (job_id, indicator_slug) in analysis_job_results
- [ ] Migration is reversible (down method drops tables)
- [ ] Migration runs successfully on local MySQL

## Dependencies
- users table must exist
- sites table must exist
- TASK-16-1 (Tools infrastructure - URL validation)

## Effort Estimate
`Small` (2-3 hours)

## Technical Notes
- Use Laravel migration syntax (PHP)
- Tables follow existing naming conventions
- JSON columns for flexible data storage
- Consider soft deletes for job history retention

---

## Comments
_Agent communication log_

### [2026-02-28] Agent: SpecSystemAnalyst
Created task for database migration. Foundation task for Progressive Analysis system - must be completed before backend services can be built. Set priority to High due to dependency chain.

### [2026-02-28] Agent: LocalTester
**Local Testing Completed Successfully**

Created comprehensive migration test file: `tests/Feature/Migrations/CreateAnalysisJobsTablesTest.php`

**Test Results:**
- 62 tests passed (141 assertions)
- Duration: 3.54s

**Test Coverage:**
- Table existence verification
- Column existence and types
- Foreign key constraints (with cascade delete)
- Foreign key constraint violations (invalid references)
- Index verification (user_id, site_id, status, created_at, job_id)
- Enum validation for status fields
- Default values
- Nullable columns
- JSON storage (data, recommendations)
- Unique constraint on (job_id, indicator_slug)
- Boundary values (progress_percent, percentage)
- Relationship tests (job has many results, belongs to user/site)
- Security tests (SQL injection, XSS)

**All acceptance criteria verified:**
- âœ… analysis_jobs table with all required columns
- âœ… analysis_job_results table with all required columns
- âœ… Foreign key constraints working
- âœ… Indexes created
- âœ… Unique constraint on (job_id, indicator_slug)
- âœ… Migration is reversible

**Status:** Ready for DevOps deployment.
