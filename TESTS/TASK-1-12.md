# TESTS: TASK-1-12 — Scheduler for Automated Checks

## Test Plan
- Unit: `tests/Unit/Services/Scheduler/RankCheckSchedulerTest.php`
- Unit: queue scheduling support (if scheduler depends on queue): `tests/Unit/Services/Queue/JobQueueTest.php`

## Execution
- Command: `php artisan test --filter RankCheckSchedulerTest`
- Expected: all scheduler unit tests pass

## Results
- **FAIL** — SQLite in-memory test DB cannot run migration with index prefix:
  - Error: `SQLSTATE[HY000]: General error: 1 no such function: source_url`
  - Source: `create index "idx_source_target" on "backlinks" (source_url(191), target_url(191))`
  - Location: migration for `backlinks` table

## Notes
- This failure blocks running scheduler unit tests locally using SQLite. Adjust migration for SQLite (conditional index) or run tests on MySQL test DB.
