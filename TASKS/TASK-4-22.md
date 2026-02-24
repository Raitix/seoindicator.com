# TASK-4-22: Report Download Authorization Fix

## STATUS
`To Develop`

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
- [ ] Add auth middleware to report routes
- [ ] Verify user owns the site associated with the report
- [ ] Return 401 Unauthorized if not authenticated
- [ ] Return 403 Forbidden if user doesn't own the site
- [ ] Add tests for authorization scenarios

## Technical Notes
- Add `auth` middleware to report routes in `routes/api.php`
- In `ReportController::download()`, check `$report->site->user_id === auth()->id()`
- Consider using Laravel Policies for cleaner authorization

## Routes to Secure
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

### Recommended Fix
```php
// In ReportController::download()
$user = auth()->user();
if (!$user || $report->site->user_id !== $user->id) {
    return response()->json([
        'success' => false,
        'error' => [
            'code' => 'FORBIDDEN',
            'message' => 'You do not have access to this report.',
        ],
    ], 403);
}
```

Or create a ReportPolicy:
```php
class ReportPolicy
{
    public function download(User $user, Report $report): bool
    {
        return $report->site->user_id === $user->id;
    }
}
```
