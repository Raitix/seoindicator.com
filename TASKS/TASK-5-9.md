# TASK-5-9: Track/Dismiss Endpoints

## STATUS
`To Test Prod`

## PRIORITY
`High`

## RISK
`Medium`

## Description
Create API endpoints for adding suggestions to tracking and dismissing suggestions the user doesn't want.

## Acceptance Criteria
- [x] POST /api/v1/sites/:siteId/suggestions/:suggestionId/track - Add single suggestion to tracking
- [x] Creates keyword record in keywords table (FEAT-001)
- [x] Updates suggestion.is_tracking = true
- [x] Returns 201 with { keyword, suggestion }
- [x] POST /api/v1/sites/:siteId/suggestions/bulk-track - Add multiple suggestions
- [x] Request body: { suggestionIds: array }
- [x] Returns 201 with { added: array, skipped: array }
- [x] POST /api/v1/sites/:siteId/suggestions/:suggestionId/dismiss - Dismiss suggestion
- [x] Updates suggestion.is_dismissed = true
- [x] Adds record to dismissed_suggestions table
- [x] Returns 200 with updated suggestion
- [x] Authorization: user must own the site

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

### [2026-02-27 17:25] Agent: DevOps
**Deployment Complete - SUCCESS**

Deployment to production completed successfully.

**Deployment Details:**
- **GitHub Actions Run:** 22492252069
- **Duration:** ~2 minutes
- **Jobs:**
  - build-and-test: ✓ (1m 9s)
  - deploy: ✓ (50s)
  - notify: ✓ (3s)

**Health Check:**
- Endpoint: `GET https://seoindicator.com/api/health`
- Status: `ok`
- Database: `connected`
- Timestamp: 2026-02-27T15:27:45+00:00

**Deployed Endpoints:**
- POST /api/v1/sites/:siteId/suggestions/:suggestionId/track
- POST /api/v1/sites/:siteId/suggestions/bulk-track
- POST /api/v1/sites/:siteId/suggestions/:suggestionId/dismiss

Status: In Deployment → To Test Prod

### [2026-02-27 17:20] Agent: LocalTester
**Local Testing Complete - PASS**

All tests pass for TASK-5-9 (Track/Dismiss Endpoints):

**Test Results:**
- **Total Tests:** 80 passed (192 assertions)
- **Duration:** 7.90s

**Test Coverage by Endpoint:**
| Endpoint | Tests | Status |
|----------|-------|--------|
| POST /track | 9 | ✓ PASS |
| POST /bulk-track | 11 | ✓ PASS |
| POST /dismiss | 8 | ✓ PASS |
| Authorization | 6 | ✓ PASS |
| Security/Adversarial | 7 | ✓ PASS |

**Security Tests Verified:**
- SQL injection in suggestionId: ✓
- SQL injection in bulk track: ✓
- XSS in keyword data: ✓
- Bulk track negative IDs: ✓
- Bulk track huge array (stress): ✓
- Invalid UUID handling: ✓
- Malformed JSON body: ✓

**Bug Fixed During Testing:**
- Fixed duplicate test method `test_numbers_in_keyword()` in `KeywordDensityCheckerTest.php`
- Renamed duplicate to `test_numbers_in_keyword_single_occurrence()`

**Test File:** `backend/tests/Feature/Api/V1/SuggestionControllerTest.php`

**Recommendation:** Ready for deployment to production.

Status: Testing Locally → To Deploy

### [2026-02-27 17:15] Agent: Developer
**Implementation Complete**

All three endpoints were already implemented in `SuggestionController.php`:
- `track()` - Single suggestion tracking ✓
- `bulkTrack()` - Bulk tracking with added/skipped arrays ✓
- `dismiss()` - Dismiss suggestion ✓

**Fix Applied:**
- Updated `dismiss()` method to insert record into `dismissed_suggestions` table per acceptance criteria
- Uses idempotent insert (checks existence before inserting)

**Test Results:**
- All 80 tests pass (192 assertions)
- Test file: `backend/tests/Feature/Api/V1/SuggestionControllerTest.php`

Status: In Development → To Test Locally

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
