# TEST-1-12: Scheduler for Automated Checks — Local Test Report

## Scope
Validate RankCheckScheduler logic and manual trigger flow at unit-test level.

## Environment
- Local: Windows (XAMPP)
- Backend: Laravel
- DB: SQLite (in-memory) for tests

## Test Cases
1. Scheduler queues eligible keywords with priorities
   - Expected: jobs queued for eligible keywords, priority based on last_viewed_at
2. Scheduler skips when interval not reached
   - Expected: no jobs queued if last run within configured interval

## Execution
Command:
```
php artisan test --filter RankCheckSchedulerTest
```

## Results
- PASS (2 tests, 6 assertions)
- Duration: 1.22s

## Notes
- SQLite compatibility fix already applied (backlinks migration index).
