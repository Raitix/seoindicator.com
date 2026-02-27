# TASK-5-13: Unit Tests

## STATUS
`In Development`

## PRIORITY
`High`

## RISK
`Low`

## Description
Create comprehensive unit tests for all keyword discovery components: models, repositories, services, and utilities.

## Acceptance Criteria
- [x] KeywordSuggestion model tests: relationships, scopes, accessors
- [x] KeywordSuggestionRepository tests: CRUD operations, queries
- [x] OpportunityScorer tests: all scoring scenarios
- [x] GoogleSuggestFetcher tests: parsing, error handling, caching (mocked)
- [x] QuestionGenerator tests: all prefix combinations
- [x] SuggestionService tests: aggregation, deduplication, storage (mocked)
- [x] API controller tests: request validation, response formatting
- [ ] Minimum 80% code coverage for new code (requires implementation)

## Dependencies
- All implementation tasks (TASK-5-1 through TASK-5-9)

## Effort Estimate
`Medium` (4-6 hours)

## Technical Notes
- Use PHPUnit for backend tests
- Mock external API calls (Google Suggest)
- Use factories for test data
- Run in CI pipeline

---

## Comments
_Agent communication log_

### [2026-02-26 23:15] Agent: PreTester: Test Design Complete
Created comprehensive unit tests for all keyword discovery components:

**Test Documentation:**
- `TESTS/TEST-5-13.md` - Full test specification with 75+ test scenarios

**Test Files Created:**
1. `backend/tests/Unit/Models/KeywordSuggestionTest.php` - 20 test methods
   - Relationships: site() BelongsTo
   - Scopes: bySite, bySource, notDismissed, notTracking, highOpportunity
   - Accessors: tracking_status
   - Configuration: fillable, casts, timestamps

2. `backend/tests/Unit/Repositories/KeywordSuggestionRepositoryTest.php` - 25 test methods
   - CRUD: find, create, update, delete
   - Queries: findBySite, findDismissed, findByKeyword, getBySource
   - State changes: markAsTracking, dismiss
   - Bulk operations: bulkCreate, upsertOrCreate
   - Pagination: getPaginated

3. `backend/tests/Unit/Services/OpportunityScorerTest.php` - 25 test methods
   - Base score: 50
   - Long-tail bonus: +10 for 3+ words, +5 for 5+ words
   - Question prefix bonus: +15
   - Commercial intent bonus: +10
   - Case insensitivity, edge cases

4. `backend/tests/Unit/Services/Fetchers/GoogleSuggestFetcherTest.php` - 25 test methods
   - HTTP response parsing
   - Error handling: 429, 500, network errors
   - URL encoding, Unicode support
   - Caching with TTL
   - Timeout configuration

5. `backend/tests/Unit/Services/Fetchers/QuestionGeneratorTest.php` - 20 test methods
   - All 7 question prefixes
   - Multi-word keywords
   - Case preservation
   - Unicode support
   - Custom prefixes

6. `backend/tests/Unit/Services/SuggestionServiceTest.php` - 15 test methods
   - Multi-source aggregation
   - Deduplication
   - Opportunity score calculation
   - Error handling
   - Logging

7. `backend/tests/Feature/Api/V1/SuggestionControllerTest.php` - 25 test methods
   - CRUD endpoints
   - Filtering and pagination
   - Validation
   - Authorization
   - Export

**Factory Created:**
- `backend/database/factories/KeywordSuggestionFactory.php`
  - States: tracking, dismissed, highOpportunity, lowOpportunity
  - Source states: fromSuggest, fromCompetitor, fromRelated

**Total: 155+ test methods across 7 test files**

Status: Tests Coverage In Progress → To Develop

### [2026-02-26 22:42] Agent: ProjectManager
Created task from SPEC-005 breakdown. Priority: High (quality assurance). Risk: Low (testing only, no production impact). Dependencies: All backend implementation tasks.
