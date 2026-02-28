# TASK-18-1: Database Migration - seo_analyses Table

## STATUS
`To Prioritize`

## PRIORITY
`High`

## RISK
`Medium`

## Description
Create the database migration for the `seo_analyses` table to store analysis results, including user/session tracking, domain info, scores, and cached analysis data.

## Acceptance Criteria
- [ ] Migration file created with proper naming convention
- [ ] Table includes all required columns (id, user_id, session_id, domain, normalized_url, overall_score, status, error_message, analysis_data JSON, checked_at, expires_at)
- [ ] Proper indexes on user_id, session_id, normalized_url, status, expires_at
- [ ] Foreign key constraint on user_id (nullable)
- [ ] Status enum includes: pending, running, completed, failed
- [ ] Migration runs successfully on fresh database
- [ ] Migration rollback works correctly

## Dependencies
- None (foundation task)

## Effort Estimate
`Small` (1-2 hours)

## Technical Notes
- Use Laravel migration best practices
- JSON column for analysis_data should be castable
- Consider composite indexes for common queries
- Add comments on important columns

---

## Comments
### [2026-02-28] SpecSystemAnalyst
Created from SPEC-018 Section 10.1. Foundation task for SEO analysis storage.
