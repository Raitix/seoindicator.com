# TASK-4-22: Report Download Authorization Fix

## STATUS
`To Test Locally`

## PRIORITY
`High`

## RISK
`High`

## Related
- Feature: [FEAT-004](../FEATURES/FEAT-004.md)
- Specification: [SPEC-004](../SPECIFICATIONS/SPEC-004.md)
- Parent Task: [TASK-4-9](TASK-4-9.md)

## Description
Add authorization to report download endpoint to ensure users can only download reports for sites they own.

## Security Issue
The `/api/v1/reports/{reportId}/download` endpoint currently allows ANY user to download ANY report by knowing the report ID. This is a data exposure vulnerability.

## Acceptance Criteria
- [x] Add auth middleware to report routes
- [x] Verify user owns the site associated with the report
- [x] Return 401 Unauthorized if not authenticated
- [x] Return 403 Forbidden if user doesn't own the site
- [x] Add tests for authorization scenarios

## Technical Notes
- Add `auth` middleware to report routes in `routes/api.php`
- In `ReportController::download()`, check `$report->site->user_id === auth()->id()`
- Consider using Laravel Policies for cleaner authorization

## Routes Secured
```
GET /api/v1/sites/{siteId}/reports
POST /api/v1/sites/{siteId}/reports/generate
GET /api/v1/reports/{reportId}
DELETE /api/v1/reports/{reportId}
GET /api/v1/reports/{reportId}/download
```

---

## Comments
_Agent communication log_

### [2026-02-24 23:30] Agent: LocalTester
Created during local testing of TASK-4-9. Critical security vulnerability discovered - missing authorization on report download endpoint.

### [2026-02-25 14:45] Agent: Developer
**Implementation Complete**

**Changes Made:**

1. **Created ReportPolicy** (`backend/app/Policies/ReportPolicy.php`)
   - Policy class for clean authorization logic
   - Methods: `viewAny`, `view`, `create`, `delete`, `download`
   - All methods verify `$report->site->user_id === $user->id`

2. **Updated Routes** (`backend/routes/api.php`)
   - Moved report routes to authenticated middleware group
   - All report endpoints now require authentication

3. **Updated ReportController** (`backend/app/Http/Controllers/Api/V1/ReportController.php`)
   - Added authorization checks in all methods:
     - `index()`: Verify user owns the site
     - `show()`: Verify user owns the report's site
     - `destroy()`: Verify user owns the report's site
     - `generate()`: Verify user owns the site
     - `download()`: Verify user owns the report's site
   - Returns 403 FORBIDDEN when user doesn't have access

4. **Updated Tests** (`backend/tests/Feature/Api/V1/ReportControllerTest.php`)
   - Added 11 new authorization tests:
     - 5 tests for 401 Unauthorized (unauthenticated access)
     - 5 tests for 403 Forbidden (accessing other user's resources)
     - 1 test verifying owner can still access their own reports
   - Updated all existing tests to use `actingAs($user)`

**Test Results:**
- All 29 ReportControllerTest tests pass
- 79 assertions pass

**Security Fix Summary:**
- Unauthenticated requests → 401 Unauthorized
- Authenticated but wrong user → 403 Forbidden
- Owner → Full access as expected
