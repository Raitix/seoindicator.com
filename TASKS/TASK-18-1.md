# TASK-18-1: Database Migration - seo_analyses Table

## STATUS
`To Develop`

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

### [2026-02-28] PreTester
**Test Coverage Designed:**
- Created `TEST-18-1.md` with 35+ test scenarios
- Created `backend/tests/Feature/Migrations/CreateSeoAnalysesTableTest.php`
- 31 test methods covering:
  - Schema structure (13 tests): all columns with correct types
  - Index validation (5 tests): user_domain, session_domain, normalized_url, status, expires_at
  - Foreign key constraints (2 tests): FK to users.id with CASCADE delete
  - Enum validation (4 tests): pending, running, completed, failed
  - Migration rollback (2 tests): rollback and re-run
  - Security (3 tests): SQL injection, XSS, length limits
  - JSON handling (4 tests): empty, nested, valid objects
  - Edge cases (3 tests): unicode, large data, null values
- Tests follow TDD approach - will pass once migration is implemented
- Status: **Tests Written, Awaiting Implementation**

### [2026-02-28] PreTester (Session Complete)
**Tests verified and ready for Developer handoff.**
- All test files present and complete
- Test documentation in `TESTS/TEST-18-1.md`
- Test implementation in `backend/tests/Feature/Migrations/CreateSeoAnalysesTableTest.php`
- Status updated to `To Develop`
- Developer: Run `php artisan test --filter=CreateSeoAnalysesTableTest` to verify migration
