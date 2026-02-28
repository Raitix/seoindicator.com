# TASK-5-14: Integration Tests

## STATUS
`To Develop`

## PRIORITY
`Medium`

## RISK
`Low`

## Description
Create end-to-end integration tests for the keyword discovery workflow, covering API endpoints and database interactions.

## Acceptance Criteria
- [ ] Test: Generate suggestions → verify stored in database
- [ ] Test: List suggestions → verify pagination and filtering
- [ ] Test: Track suggestion → verify keyword created and suggestion updated
- [ ] Test: Bulk track → verify multiple keywords created
- [ ] Test: Dismiss suggestion → verify suggestion marked as dismissed
- [ ] Test: Authorization → verify user cannot access other users' suggestions
- [ ] Test: Rate limiting → verify generate endpoint respects limits
- [ ] Test: Full workflow → generate → list → track → verify keyword tracking
- [ ] Tests run against test database with migrations

## Dependencies
- All implementation tasks (TASK-5-1 through TASK-5-9)

## Effort Estimate
`Medium` (4-6 hours)

## Technical Notes
- Use Laravel's testing features (RefreshDatabase)
- Test against SQLite in-memory database
- Consider API tests with Http::fake() for external calls
- Run in CI pipeline after unit tests

---

## Comments
_Agent communication log_

### [2026-02-27 17:52] Agent: PreTester
**Test Design Complete**

Created comprehensive integration test documentation:
- **Test Documentation**: `TESTS/TEST-5-14.md` - 28 integration test scenarios

**Test Coverage:**
- Generate suggestions workflow (5 tests): storage, source filtering, maxResults
- List suggestions workflow (5 tests): pagination, filtering, sorting, defaults
- Track suggestion workflow (4 tests): single track, idempotency, keyword creation, bulk
- Dismiss suggestion workflow (4 tests): single dismiss, idempotency, list behavior
- Authorization workflow (3 tests): cross-user access, unauthenticated access
- Full user flow (3 tests): complete workflow, rediscovery, export
- Error handling (4 tests): invalid site, non-existent ID, empty array, invalid source

**Test Files to Create:**
- `backend/tests/Feature/Integration/KeywordDiscoveryIntegrationTest.php`
- `backend/tests/Feature/Integration/SuggestionWorkflowTest.php`

**TDD Status:** Tests waiting for all implementation tasks to complete

**Dependencies Status:**
- TASK-5-1 through TASK-5-9: Various stages (some Done, some in testing)
- Frontend tasks (TASK-5-10 to TASK-5-12): Tests ready, waiting for implementation

Status: Tests Coverage In Progress → To Develop (waiting for all implementation complete)

### [2026-02-26 22:42] Agent: ProjectManager
Created task from SPEC-005 breakdown. Priority: Medium (nice to have). Risk: Low (testing only). Dependencies: All implementation tasks.
