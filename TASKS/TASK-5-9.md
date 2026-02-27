# TASK-5-9: Track/Dismiss Endpoints

## STATUS
`To Develop`

## PRIORITY
`High`

## RISK
`Medium`

## Description
Create API endpoints for adding suggestions to tracking and dismissing suggestions the user doesn't want.

## Acceptance Criteria
- [ ] POST /api/v1/sites/:siteId/suggestions/:suggestionId/track - Add single suggestion to tracking
- [ ] Creates keyword record in keywords table (FEAT-001)
- [ ] Updates suggestion.is_tracking = true
- [ ] Returns 201 with { keyword, suggestion }
- [ ] POST /api/v1/sites/:siteId/suggestions/bulk-track - Add multiple suggestions
- [ ] Request body: { suggestionIds: array }
- [ ] Returns 201 with { added: array, skipped: array }
- [ ] POST /api/v1/sites/:siteId/suggestions/:suggestionId/dismiss - Dismiss suggestion
- [ ] Updates suggestion.is_dismissed = true
- [ ] Adds record to dismissed_suggestions table
- [ ] Returns 200 with updated suggestion
- [ ] Authorization: user must own the site

## Dependencies
- TASK-5-1 (Database schema)
- TASK-5-2 (Suggestion model and repository)
- FEAT-001 (Keywords table for tracking)

## Effort Estimate
`Medium` (3-4 hours)

## Technical Notes
- Use database transaction for bulk operations
- Consider soft delete for dismissed (allow restore)
- Validate suggestion belongs to site before operation

---

## Comments
_Agent communication log_

### [2026-02-27 16:35] Agent: PreTester
**Test Design Complete**

Tests already exist and are comprehensive. Verified:
- **Test File**: `backend/tests/Feature/Api/V1/SuggestionControllerTest.php`
- **Test Documentation**: Tests cover track, bulk-track, dismiss endpoints

**Test Coverage:**
- Track endpoint: 9 tests (single suggestion tracking)
- Bulk track endpoint: 12 tests (multiple suggestions)
- Dismiss endpoint: 8 tests (suggestion dismissal)
- Authorization tests: 6 tests (auth checks)
- Security tests: 8 tests (SQL injection, XSS, etc.)

**Total Tests:** 43 tests covering TASK-5-9

**Tests Status:** All 43 tests passing

Status: Tests Coverage In Progress → To Develop

### [2026-02-26 22:42] Agent: ProjectManager
Created task from SPEC-005 breakdown. Priority: High (user interaction endpoints). Risk: Medium (write operations with FK constraints). Dependencies: TASK-5-1, TASK-5-2, FEAT-001.
