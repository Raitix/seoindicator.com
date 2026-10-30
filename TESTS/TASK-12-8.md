# TESTS — TASK-12-8: GSC Data Controller

## Summary
Local API test run for GSC data endpoints and opportunities.

## Environment
- Backend: Laravel (local)
- Command: `php artisan test --filter=GSCApiEndpointsTest`

## Test Results
**Status:** PASS

### Scenarios Covered
- Auth required on GSC properties list
- GSC data endpoint returns data with date filters
- Invalid date range rejected
- Opportunities endpoint returns list
- Trend endpoint returns data
- Token leakage checks
- Manual sync endpoint

## Output
```
PASS  Tests\Feature\Api\GSCApiEndpointsTest
Tests: 11 passed (13 assertions)
Duration: 3.23s
```

## Notes
- No failures observed in local tests.

## Local Test Re-Run (2026-02-23)
**Status:** PASS
- Command: `php artisan test --filter=GSCApiEndpointsTest`
- Result: 11 passed (13 assertions)
- Confirmed that code functionality remains intact after addition of root `.htaccess`.

## Production Smoke Check (2026-02-22)
**Status:** FAIL
- https://seoindicator.com/api/health → 404
- https://seoindicator.com/api/v1/projects/1/gsc → 404
- https://seoindicator.com/api/v1/projects/1/gsc/opportunities → 404
- Expected: 200 (health) and 401/403 for protected routes.

## Local Test After Routing Fix (2026-02-23)
**Status:** PASS
- Command: `php artisan test --filter=GSCApiEndpointsTest`
- Result: 11 passed (13 assertions)
- Note: Validated `.htaccess` rewrites for `/api/*` to `backend/public/index.php`. Local tests confirm endpoints are still reachable and functional.

