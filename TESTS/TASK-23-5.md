# TESTS/TASK-23-5: Scheduled Daily Backlink Sync

## Task Reference
- Task: [TASK-23-5](../TASKS/TASK-23-5.md)
- Specification: [SPEC-023](../SPECIFICATIONS/SPEC-023.md)
- Feature: [FEAT-023](../FEATURES/FEAT-023.md)
- Depends on: TASK-23-4 (Backlink Sync Command/Job)

---

## Overview
Tests for Laravel scheduled task that automatically syncs backlink data daily. Covers schedule configuration, environment settings, job dispatch logic, and logging.

---

## Test Files

| File | Type | Tests | Assertions |
|------|------|-------|------------|
| `backend/tests/Unit/Console/BacklinksScheduleTest.php` | Unit | 40 | 50+ |
| `backend/tests/Unit/Services/BacklinkSyncSchedulerServiceTest.php` | Unit | 32 | 40+ |

**Total:** 72 tests

---

## Test Scenarios

### Schedule Configuration (SC)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| SC-001 | Schedule registered in kernel | N/A | Command found in schedule events |
| SC-002 | Schedule runs daily at 03:00 | Default config | Cron expression contains '03:00' |
| SC-003 | Schedule respects custom sync time | sync_time: '05:30' | Cron expression contains '30 5' |
| SC-004 | withoutOverlapping constraint | N/A | Constraint enabled |
| SC-005 | onOneServer constraint | N/A | Constraint enabled |

### Environment Configuration (EC)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| EC-001 | Sync disabled when config false | sync_enabled: false | Disabled |
| EC-002 | Sync enabled by default | No config | Enabled |
| EC-003 | Sync time defaults to 03:00 | No config | '03:00' |
| EC-004 | Invalid sync time handled | sync_time: 'invalid' | Fallback to default |
| EC-005 | Valid 24-hour format validation | '00:00', '12:00', '23:59' | Passes regex |

### Active GSC Connection Filter (GF)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| GF-001 | Only active GSC projects synced | Active + no GSC + expired | Only active included |
| GF-002 | Archived projects excluded | Archived + active | Only active included |
| GF-003 | Revoked GSC tokens excluded | is_active: false | Excluded |
| GF-004 | Expired tokens excluded | expires_at < now | Excluded |

### Last Sync Timestamp (LS)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| LS-001 | Projects synced today skipped | last_synced_at: now | Excluded |
| LS-002 | Projects synced yesterday included | last_synced_at: yesterday | Included |
| LS-003 | Projects never synced included | last_synced_at: null | Included |
| LS-004 | Force flag overrides check | force: true | All included |

### Job Dispatch (JD)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| JD-001 | Jobs dispatched for each project | 2 projects | 2 jobs dispatched |
| JD-002 | Jobs delayed to spread load | 5 projects | Delays spread over 30 min |
| JD-003 | Job batch tracks progress | 3 projects | Batch created |
| JD-004 | Empty project list | 0 projects | No jobs dispatched |

### Logging (LG)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| LG-001 | Scheduled run start logged | N/A | Log message with 'start' |
| LG-002 | Scheduled run completion logged | N/A | Log message with 'complete' |
| LG-003 | Sync results logged with count | 5 synced | Log with projects_synced: 5 |
| LG-004 | Errors logged | Exception | Log with error context |
| LG-005 | Skipped projects logged | 3 skipped | Log with skipped: 3 |

### Edge Cases (ED)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| ED-001 | Timezone handling | Various timezones | Valid timezone |
| ED-002 | Large number of projects | 100 projects | All jobs dispatched |
| ED-003 | Concurrent trigger | withoutOverlapping | Lock prevents duplicate |
| ED-004 | Missing GSC property URL | null property_url | Handled gracefully |

### Service Tests (SV)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| SV-001 | Service resolved from container | N/A | Instance created |
| SV-002 | getProjectsNeedingSync returns correct | Mixed projects | Only eligible |
| SV-003 | dispatchSyncJobs dispatches jobs | Projects collection | Jobs dispatched |
| SV-004 | runScheduledSync returns result | N/A | Array with dispatched/skipped |
| SV-005 | forceSync includes today synced | force: true | All eligible included |
| SV-006 | getSyncStats returns statistics | N/A | Stats array |

---

## Mock Data

### Project with Active GSC Connection
```php
Project::factory()->create([
    'is_archived' => false,
    'backlinks_last_synced_at' => null,
]);

GSCConnection::factory()->create([
    'project_id' => $project->id,
    'is_active' => true,
    'expires_at' => now()->addDays(30),
]);
```

### Configuration Values
```php
config(['backlinks.sync_enabled' => true]);
config(['backlinks.sync_time' => '03:00']);
```

---

## Coverage Targets

| Category | Target | Tests |
|----------|--------|-------|
| Schedule Configuration | 100% | SC-001 to SC-005 |
| Environment Config | 100% | EC-001 to EC-005 |
| GSC Connection Filter | 100% | GF-001 to GF-004 |
| Last Sync Timestamp | 100% | LS-001 to LS-004 |
| Job Dispatch | 100% | JD-001 to JD-004 |
| Logging | 100% | LG-001 to LG-005 |
| Edge Cases | 100% | ED-001 to ED-004 |
| Service Methods | 100% | SV-001 to SV-006 |

**Overall Target:** 100% of acceptance criteria covered

---

## Test Execution

```bash
# Run all TASK-23-5 tests
cd backend
php artisan test --filter=BacklinksSchedule
php artisan test --filter=BacklinkSyncSchedulerService

# Run with coverage
php artisan test --filter=BacklinksSchedule --coverage
php artisan test --filter=BacklinkSyncSchedulerService --coverage

# Run all tests
php artisan test
```

---

## Dependencies

| Dependency | Status | Notes |
|------------|--------|-------|
| TASK-23-4 (Sync Command/Job) | Done | SyncBacklinksJob used in tests |
| SyncBacklinksJob | Implemented | Dispatched by scheduler |
| GSCConnection model | Implemented | Used for filtering |
| Project model | Implemented | Used for queries |

---

## Expected Implementation

### Config File: `config/backlinks.php`
```php
return [
    'sync_enabled' => env('BACKLINKS_SYNC_ENABLED', true),
    'sync_time' => env('BACKLINKS_SYNC_TIME', '03:00'),
];
```

### Service: `app/Services/BacklinkSyncSchedulerService.php`
```php
class BacklinkSyncSchedulerService
{
    public function getProjectsNeedingSync(): Collection;
    public function dispatchSyncJobs(Collection $projects, int $spreadMinutes = 30): array;
    public function runScheduledSync(): array;
    public function forceSync(): array;
    public function getSyncStats(): array;
}
```

### Console Kernel: `app/Console/Kernel.php`
```php
protected function schedule(Schedule $schedule): void
{
    $syncTime = config('backlinks.sync_time', '03:00');
    
    $schedule->command('backlinks:sync --all')
        ->dailyAt($syncTime)
        ->withoutOverlapping()
        ->onOneServer()
        ->appendOutputTo(storage_path('logs/backlinks-sync.log'));
}
```

---

## Notes for Developer

1. **Migration Required**: Add `backlinks_last_synced_at` column to projects table if not exists
2. **Config File**: Create `config/backlinks.php` with sync_enabled and sync_time
3. **Service Class**: Create `BacklinkSyncSchedulerService` for reusable sync logic
4. **Schedule Registration**: Add backlinks:sync to Console Kernel schedule
5. **Job Batching**: Use `Bus::batch()` for tracking multiple project syncs
6. **Load Spreading**: Calculate delay per project: `30 minutes / project count`
7. **Logging**: Use structured logging with context arrays for monitoring

---

## Comments
_Agent communication log_

### [2026-03-03 10:59] Agent: PreTester
**Tests Complete**

- **Test Files Created:**
  - `backend/tests/Unit/Console/BacklinksScheduleTest.php` — 40 tests covering schedule configuration, environment settings, job dispatch, logging
  - `backend/tests/Unit/Services/BacklinkSyncSchedulerServiceTest.php` — 32 tests covering service methods, filtering, error handling
- **Total**: 72 tests

**Test Coverage:**
- Schedule registration and timing (daily at 03:00, configurable)
- Schedule constraints (withoutOverlapping, onOneServer)
- Environment configuration (BACKLINKS_SYNC_ENABLED, BACKLINKS_SYNC_TIME)
- Active GSC connection filtering (active, expired, revoked)
- Last sync timestamp logic (skip if synced today)
- Job dispatch with load spreading over 30 minutes
- Job batching for progress tracking
- Comprehensive logging (start, complete, errors, skipped)
- Edge cases (large projects, timezone, missing data)

**Expected Deliverables:**
1. `config/backlinks.php` — Configuration file
2. `BacklinkSyncSchedulerService` — Service class
3. Console Kernel schedule registration
4. Migration: `backlinks_last_synced_at` on projects table

Handing off to Developer for implementation.
