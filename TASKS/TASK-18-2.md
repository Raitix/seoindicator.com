# TASK-18-2: SEOAnalysis Model and Repository

## STATUS
`Tests Coverage In Progress`

## PRIORITY
`High`

## RISK
`Medium`

## Description
Create the Eloquent model and repository pattern for SEO analyses, including JSON casting, relationships, and data access methods.

## Acceptance Criteria
- [ ] SEOAnalysis model created with proper fillable/casts
- [ ] BelongsTo relationship to User model
- [ ] JSON casting for analysis_data column
- [ ] Status accessor/mutator methods
- [ ] Repository class with CRUD operations
- [ ] Find by domain method
- [ ] Find cached (non-expired) analysis method
- [ ] Scopes for common queries (byStatus, byUser, expired)
- [ ] Unit tests for model and repository

## Dependencies
- TASK-18-1 (Database migration)

## Effort Estimate
`Medium` (2-3 hours)

## Technical Notes
- Use Laravel's AsArrayObject or AsCollection for JSON casting
- Repository should handle caching internally
- Consider soft deletes for analysis history

---

## Comments
### [2026-02-28] SpecSystemAnalyst
Created from SPEC-018 Section 10.1. Model provides data access layer for analyses.

### [2026-02-28] PreTester
**Test Coverage Designed:**
- Created `TEST-18-2.md` with 70+ test scenarios
- Created `backend/tests/Unit/Models/SEOAnalysisTest.php` (35+ test methods)
  - Model creation, fillable attributes, JSON casting
  - Relationships (BelongsTo User)
  - Scopes (byStatus, byUser, bySession, expired, forDomain, etc.)
  - Model methods (markAsRunning, markAsCompleted, markAsFailed, isExpired)
  - Security tests (SQL injection, XSS, Unicode)
- Created `backend/tests/Unit/Repositories/SEOAnalysisRepositoryTest.php` (30+ test methods)
  - CRUD operations, findByDomain, findCached
  - Pagination, stats, cleanup
  - Caching with invalidation
- Created `backend/database/factories/SEOAnalysisFactory.php`
  - Factory with states: pending, running, completed, failed, expired, highScore, lowScore
- Status: **Tests Written, Awaiting Implementation**
