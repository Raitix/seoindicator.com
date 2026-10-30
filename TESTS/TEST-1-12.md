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

---

## Extended Test Run (2026-02-23 11:55)

Command:
```
php artisan test --filter "RankCheck"
```

### Results Summary
| Test Suite | Tests | Assertions | Status |
|------------|-------|------------|--------|
| RankCheckerWorkerTest | 3 | 12 | PASS |
| RankCheckerServiceTest | 16 | — | PASS |
| RankCheckSchedulerTest | 2 | 6 | PASS |
| RankCheckerIntegrationTest | 10 | — | PASS |
| **Total** | **31** | **86** | **PASS** |

Duration: 2.41s

### Notes
- All scheduler, queue scheduling, and worker tests pass.
- ForceJsonResponse middleware added for API routes (unauth requests return JSON 401).
