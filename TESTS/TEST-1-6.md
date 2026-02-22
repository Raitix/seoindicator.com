# TEST-1-6: Dashboard API Endpoint (Local)

## STATUS
`Failed`

## Test Run
- **Date:** 2026-02-23 00:11 (Europe/Kiev)
- **Command:** `php artisan test --testsuite=Feature --filter DashboardControllerTest`
- **Environment:** Laravel tests (sqlite :memory:)

## Results
- **Failing:** 11 tests (all in `DashboardControllerTest`)
- **Failure cause:** Migration error before assertions

## Error
```
SQLSTATE[HY000]: General error: 1 no such function: source_url (Connection: sqlite, Database: :memory:, SQL: create index "idx_source_target" on "backlinks" (source_url(191), target_url(191)))
```

## Notes
- SQLite does not support column length prefixes in index definitions.
- Adjust backlinks migration to be sqlite-compatible (conditional index by driver or remove prefix lengths for sqlite), then rerun tests.
