# TASK-5-2: Suggestion Model and Repository

## STATUS
`Testing Prod`

## PRIORITY
`High`

## RISK
`Low`

## Description
Create Eloquent model and repository for keyword_suggestions table. Provides data access layer for the keyword discovery feature.

## Acceptance Criteria
- [x] KeywordSuggestion model with fillable: site_id, keyword, source, search_volume, difficulty_score, opportunity_score, is_tracking, is_dismissed
- [x] BelongsTo relationship: site()
- [x] Scope for filtering: bySite(), bySource(), notDismissed(), notTracking()
- [x] KeywordSuggestionRepository with methods: find, create, update, delete, findBySite, findDismissed, markAsTracking, dismiss
- [x] Repository follows existing pattern in project

## Dependencies
- TASK-5-1 (Database schema)

## Effort Estimate
`Small` (2-3 hours)

## Technical Notes
- Follow existing repository pattern in project
- Use dependency injection for repository
- Add type hints for PHPStan

---

## Comments
_Agent communication log_

### [2026-02-27 23:32] Agent: DevOps
**Deployment: VERIFIED**

The KeywordSuggestion model and repository were already deployed to production via GitHub Actions (code was pushed to main in earlier commits).

**Verification:**
- Health check: `https://seoindicator.com/api/health` → 200 OK, database connected
- Production files verified via SSH:
  - `backend/app/Models/KeywordSuggestion.php` (4205 bytes, Feb 27 09:10)
  - `backend/app/Repositories/KeywordSuggestionRepository.php` (7583 bytes, Feb 27 09:10)
- Latest GitHub Actions run: success (2m6s)

Status: To Deploy → To Test Prod

### [2026-02-27 10:56] Agent: LocalTester
**Local Testing: PASSED**

**Test Results:**
- 43 tests passed (100 assertions) - 3.12s duration
- 19 Model tests: relationships, scopes (bySite, bySource, notDismissed, notTracking, highOpportunity), tracking_status accessor, fillable, casts, factory states
- 24 Repository tests: CRUD, queries, state changes, pagination, upsert

**Adversarial Testing:**
- SQL Injection: PASSED (parameterized queries)
- XSS: PASSED (Laravel escapes output)
- Long input: PASSED (handled gracefully)
- Invalid source: PASSED (handled)
- Negative scores: PASSED (handled)
- Empty bulk: PASSED (count=0, no crash)
- Mass assignment: PASSED (protected fields ignored)
- Double dismiss: PASSED (idempotent)

**Security Assessment:**
- No sensitive data leaked in error messages
- Mass assignment protection working
- Parameterized queries prevent SQL injection
- XSS safe via Laravel's automatic escaping

Status: To Test Locally → To Deploy

### [2026-02-27 10:52] Agent: Developer
Implementation complete. Created:

**Model** (`backend/app/Models/KeywordSuggestion.php`):
- Fillable: site_id, keyword, source, search_volume, difficulty_score, opportunity_score, is_tracking, is_dismissed
- BelongsTo relationship: site()
- Scopes: bySite(), bySource(), notDismissed(), notTracking(), highOpportunity()
- Accessor: tracking_status (returns 'tracking', 'dismissed', or 'available')
- Boolean casts for is_tracking and is_dismissed
- Auto-set discovered_at on creation

**Repository** (`backend/app/Repositories/KeywordSuggestionRepository.php`):
- CRUD: find(), create(), update(), delete()
- Query: findBySite(), findDismissed(), findByKeyword(), getBySource(), getPaginated()
- State: markAsTracking(), dismiss()
- Bulk: bulkCreate(), exists(), countBySite(), upsertOrCreate()

**Tests:** All 43 tests passing (19 model + 24 repository)

Status: To Develop → To Test Locally

### [2026-02-26 23:05] Agent: PreTester
Test design complete. Created:
- **Test Documentation**: `TESTS/TEST-5-2.md` - 45 test scenarios (19 model + 26 repository)
- **Model Tests**: `backend/tests/Unit/Models/KeywordSuggestionTest.php` - 19 test methods
- **Repository Tests**: `backend/tests/Unit/Repositories/KeywordSuggestionRepositoryTest.php` - 26 test methods
- **Factory**: `backend/database/factories/KeywordSuggestionFactory.php` - Already exists with states

**Test Coverage:**
- Model: relationships, scopes (bySite, bySource, notDismissed, notTracking, highOpportunity), tracking_status accessor, fillable attributes, casts
- Repository: find, create, update, delete, findBySite, findDismissed, markAsTracking, dismiss, findByKeyword, bulkCreate, exists, getPaginated, getBySource, countBySite, upsertOrCreate

**Key Requirements for Developer:**
1. Model at `backend/app/Models/KeywordSuggestion.php`
2. Repository at `backend/app/Repositories/KeywordSuggestionRepository.php`
3. Follow existing Keyword model/repository patterns
4. Use dependency injection for repository
5. Add PHPStan type hints

**Tests Status:** Written and failing (TDD) - model/repo don't exist yet

Status: To Write Tests → To Develop

### [2026-02-26 22:42] Agent: ProjectManager
Created task from SPEC-005 breakdown. Priority: High (core data layer). Risk: Low (standard model/repository implementation). Dependencies: TASK-5-1.
