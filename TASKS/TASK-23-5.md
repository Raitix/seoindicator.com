# TASK-23-5: Scheduled Daily Backlink Sync

## STATUS
`To Develop`

## PRIORITY
`Medium` (Confirmed)

## RISK
`Low` (Confirmed)

## Type
Backend

## Description
Configure scheduled task to automatically sync backlink data daily. Uses Laravel's task scheduling to run sync jobs for all projects with active GSC connections.

## Related
- Specification: [SPEC-023](../SPECIFICATIONS/SPEC-023.md)
- Feature: [FEAT-023](../FEATURES/FEAT-023.md)
- Depends on: TASK-23-4

---

## Acceptance Criteria
- [ ] Scheduled task runs daily at configured time
- [ ] Only syncs projects with active GSC connections
- [ ] Respects last sync timestamp (skip if synced today)
- [ ] Dispatches jobs with delay to spread load
- [ ] Logs scheduled run results
- [ ] Configurable via environment variable

---

## Technical Details

### Schedule Configuration
```php
// app/Console/Kernel.php
protected function schedule(Schedule $schedule): void
{
    $schedule->command('backlinks:sync --all')
        ->dailyAt('03:00')
        ->withoutOverlapping()
        ->onOneServer()
        ->appendOutputTo(storage_path('logs/backlinks-sync.log'));
}
```

### Environment Configuration
```env
BACKLINKS_SYNC_ENABLED=true
BACKLINKS_SYNC_TIME=03:00
```

### Job Dispatch Strategy
- Use `bus::batch()` for multiple projects
- Spread jobs over 30 minutes to avoid GSC rate limits
- Track batch ID for monitoring

---

## Dependencies
- TASK-23-4 (Backlink Sync Command/Job)

## Effort Estimate
`Small` (1-2 hours)

---

## Comments
_Agent communication log_

### [2026-03-02 23:10] Agent: ProjectManager
**Prioritization Complete**

- **Priority Confirmed**: `Medium` — Production-readiness task that enables automated daily syncing. Not a foundation task (sync command/job already exists in TASK-23-4), but essential for complete feature automation.
- **Risk**: `Low` — Laravel scheduling is straightforward configuration. No external APIs, no security concerns, no complex logic.
- **Dependency Verified**: TASK-23-4 (Backlink Sync Command/Job): `Done` ✅ — Sync command and job fully implemented and tested.

**Execution Context:**
- Part of SPEC-023 Phase 2 (Data Sync)
- Completes the backlink sync pipeline by adding automation
- Without scheduled sync, users would need to manually trigger syncs via CLI/API

**Technical Notes:**
- Laravel's `schedule()` method in `app/Console/Kernel.php`
- `dailyAt('03:00')` timing configurable via env variable
- `withoutOverlapping()` prevents concurrent runs
- `onOneServer()` ensures single execution in multi-server setup
- Job dispatch uses `bus::batch()` for multiple projects with 30-minute spread

**Parallel Development:**
- TASK-23-6 (Snapshot Service) can proceed independently
- Both tasks depend only on TASK-23-4 (Done)

Handing off to PreTester for test design.

### [2026-03-03 11:16] Agent: PreTester
**Tests Complete**

- **Test Files Created:**
  - `backend/tests/Unit/Console/BacklinksScheduleTest.php` — 40 tests covering schedule configuration, environment settings, job dispatch, logging
  - `backend/tests/Unit/Services/BacklinkSyncSchedulerServiceTest.php` — 32 tests covering service methods, filtering, error handling
- **Test Documentation:** `TESTS/TASK-23-5.md`
- **Total:** 72 tests

**Test Coverage:**
- Schedule registration and timing (daily at configurable time, default 03:00)
- Schedule constraints (`withoutOverlapping`, `onOneServer`)
- Environment configuration (`BACKLINKS_SYNC_ENABLED`, `BACKLINKS_SYNC_TIME`)
- Active GSC connection filtering (active, expired, revoked tokens)
- Last sync timestamp logic (skip if synced today)
- Job dispatch with load spreading over 30 minutes
- Job batching for progress tracking
- Comprehensive logging (start, complete, errors, skipped count)
- Edge cases (large project counts, timezone handling, missing data)

**Expected Deliverables for Developer:**
1. `config/backlinks.php` — Configuration file with `sync_enabled` and `sync_time`
2. `app/Services/BacklinkSyncSchedulerService.php` — Service class with methods:
   - `getProjectsNeedingSync(): Collection`
   - `dispatchSyncJobs(Collection $projects, int $spreadMinutes = 30): array`
   - `runScheduledSync(): array`
   - `forceSync(): array`
   - `getSyncStats(): array`
3. Console Kernel schedule registration for `backlinks:sync --all`
4. Migration: Add `backlinks_last_synced_at` column to `projects` table

**Test Status:** All tests fail as expected (TDD) — service class does not exist yet. Tests document expected behavior for Developer implementation.

Handing off to Developer for implementation.
