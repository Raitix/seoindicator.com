# TEST-23-4: Backlink Sync Command and Job

## Task Reference
- **Task:** TASK-23-4
- **Feature:** FEAT-023 (Backlinks Indicator)
- **Specification:** SPEC-023
- **Status:** Tests Complete

---

## Overview
Tests for the Artisan command `backlinks:sync` and queued job `SyncBacklinksJob` that handle backlink data synchronization from Google Search Console.

---

## Test Files

| File | Type | Tests | Assertions |
|------|------|-------|------------|
| `backend/tests/Unit/Jobs/SyncBacklinksJobTest.php` | Unit | 35+ | 70+ |
| `backend/tests/Unit/Commands/BacklinksSyncCommandTest.php` | Unit | 25+ | 50+ |
| `backend/tests/Feature/Jobs/SyncBacklinksJobIntegrationTest.php` | Integration | 15+ | 30+ |

---

## SYNC_BACKLINKS_JOB TESTS

### 1. Job Construction Tests (JOB-CON)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| JOB-CON-01 | Create with project ID | `projectId: 1` | Job instance created with projectId |
| JOB-CON-02 | Create with force flag | `force: true` | Job instance with force = true |
| JOB-CON-03 | Default force flag | Omit force | force = false |
| JOB-CON-04 | Job implements ShouldQueue | Check interface | Implements ShouldQueue |
| JOB-CON-05 | Job uses Queueable trait | Check traits | Uses Queueable trait |
| JOB-CON-06 | Job tries configuration | Check $tries | Default = 3 |
| JOB-CON-07 | Job timeout configuration | Check $timeout | Default = 300 (5 min) |
| JOB-CON-08 | Unique job ID | Call uniqueId() | Returns "backlink-sync-{projectId}" |

### 2. Job Execution Tests (JOB-EXEC)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| JOB-EXEC-01 | Execute with valid project | Valid project ID | Processes sync, logs success |
| JOB-EXEC-02 | Execute with non-existent project | Invalid ID | Logs warning, returns early |
| JOB-EXEC-03 | Execute with project without domain | No domain set | Logs warning, returns early |
| JOB-EXEC-04 | Execute with no GSC connection | No active connection | Logs warning, returns early |
| JOB-EXEC-05 | Execute with expired GSC connection | Expired token | Logs warning, returns early |
| JOB-EXEC-06 | Execute with force flag | force = true | Bypasses rate limit check |
| JOB-EXEC-07 | Execute creates snapshot | Valid sync | Creates BacklinkSnapshot |
| JOB-EXEC-08 | Execute with exception | Service throws exception | Re-throws, logs error |
| JOB-EXEC-09 | Execute respects queue | Check queue | Queued on 'default' queue |

### 3. Retry Logic Tests (JOB-RETRY)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| JOB-RETRY-01 | Retry on transient error | GSC API 500 error | Retries up to 3 times |
| JOB-RETRY-02 | Retry on rate limit | GSC API 429 error | Retries with backoff |
| JOB-RETRY-03 | Retry on connection timeout | Network timeout | Retries up to 3 times |
| JOB-RETRY-04 | No retry on permanent error | Invalid project ID | No retry, logs warning |
| JOB-RETRY-05 | Max retries exceeded | 3 failures | Job marked as failed |
| JOB-RETRY-06 | Exponential backoff | Multiple retries | Delay increases each retry |

### 4. Chunked Processing Tests (JOB-CHUNK)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| JOB-CHUNK-01 | Process small dataset | 10 backlinks | Processes in one chunk |
| JOB-CHUNK-02 | Process large dataset | 1000+ backlinks | Processes in chunks of 100 |
| JOB-CHUNK-03 | Chunk with progress callback | Large dataset | Callback receives progress |
| JOB-CHUNK-04 | Chunk memory efficiency | 10000 backlinks | Memory stays under 128MB |
| JOB-CHUNK-05 | Chunk failure isolation | One chunk fails | Other chunks continue |

### 5. Stale Backlink Deactivation Tests (JOB-STALE)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| JOB-STALE-01 | Mark missing backlinks inactive | Backlinks not in latest sync | is_active = false |
| JOB-STALE-02 | Preserve existing backlinks | Backlinks in latest sync | is_active remains true |
| JOB-STALE-03 | Update last_checked_at | All synced backlinks | last_checked_at updated |
| JOB-STALE-04 | Deactivation timestamp | Cutoff date provided | Only older backlinks deactivated |

### 6. Logging Tests (JOB-LOG)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| JOB-LOG-01 | Log sync start | Job starts | Info log with project_id |
| JOB-LOG-02 | Log sync completion | Job completes | Info log with snapshot_id |
| JOB-LOG-03 | Log sync failure | Exception thrown | Error log with exception |
| JOB-LOG-04 | Log project not found | Invalid project_id | Warning log |
| JOB-LOG-05 | Log no GSC connection | Missing connection | Warning log |
| JOB-LOG-06 | Log retry attempt | Retry triggered | Info log with attempt number |

### 7. Failed Job Handling Tests (JOB-FAIL)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| JOB-FAIL-01 | failed() method called | Job fails | Logs error with project_id |
| JOB-FAIL-02 | Failed job doesn't retry | Max attempts reached | Job marked failed |
| JOB-FAIL-03 | Failed job releases lock | Lock held | Lock released |

---

## BACKLINKS_SYNC_COMMAND TESTS

### 1. Command Signature Tests (CMD-SIG)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CMD-SIG-01 | Command signature | `php artisan backlinks:sync` | Command registered |
| CMD-SIG-02 | Project ID argument | `backlinks:sync 1` | Processes project 1 |
| CMD-SIG-03 | All flag | `backlinks:sync --all` | Processes all projects |
| CMD-SIG-04 | Force flag | `backlinks:sync 1 --force` | Forces sync |
| CMD-SIG-05 | Combined options | `backlinks:sync --all --force` | All projects, forced |

### 2. Single Project Sync Tests (CMD-SINGLE)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CMD-SINGLE-01 | Sync valid project | Project ID exists | Dispatches SyncBacklinksJob |
| CMD-SINGLE-02 | Sync non-existent project | Invalid project ID | Error message, exit code 1 |
| CMD-SINGLE-03 | Sync project without GSC | No GSC connection | Warning message, exit code 0 |
| CMD-SINGLE-04 | Sync project without domain | No domain set | Warning message, exit code 0 |
| CMD-SINGLE-05 | Sync already in progress | Lock exists | Warning message, skips sync |

### 3. All Projects Sync Tests (CMD-ALL)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CMD-ALL-01 | Sync all projects | `--all` flag | Dispatches jobs for all projects |
| CMD-ALL-02 | Sync only with GSC | Multiple projects | Only GSC-connected projects |
| CMD-ALL-03 | Sync only active | Archived project | Skips archived projects |
| CMD-ALL-04 | No projects to sync | No valid projects | Info message, exit code 0 |
| CMD-ALL-05 | Progress bar display | Multiple projects | Shows progress bar |

### 4. Progress Bar Tests (CMD-PROG)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CMD-PROG-01 | Progress bar for single | `backlinks:sync 1` | Shows progress bar |
| CMD-PROG-02 | Progress bar for all | `--all` flag | Shows progress bar with count |
| CMD-PROG-03 | Progress updates | Job processes | Progress bar advances |
| CMD-PROG-04 | Progress completion | Job completes | Shows "Completed" message |
| CMD-PROG-05 | No progress in quiet | `--quiet` flag | No progress bar shown |

### 5. Error Handling Tests (CMD-ERR)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CMD-ERR-01 | Invalid project ID | Non-numeric ID | Error message, exit code 1 |
| CMD-ERR-02 | Database error | DB connection fail | Error message, exit code 1 |
| CMD-ERR-03 | Queue unavailable | Queue down | Warning, tries sync anyway |
| CMD-ERR-04 | Permission denied | Unauthorized user | Error message (if auth required) |

### 6. Output Format Tests (CMD-OUT)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CMD-OUT-01 | Success output | Job dispatched | "Sync queued for project X" |
| CMD-OUT-02 | Failure output | Error occurred | Error message in red |
| CMD-OUT-03 | Summary output | `--all` complete | Shows total synced count |
| CMD-OUT-04 | JSON output | `--json` flag | JSON formatted response |
| CMD-OUT-05 | Verbose output | `-v` flag | Detailed debug info |

---

## INTEGRATION TESTS

### 1. Full Sync Flow Tests (INT-FLOW)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| INT-FLOW-01 | Command dispatches job | Run command | Job queued, job ID returned |
| INT-FLOW-02 | Job processes backlinks | Job executes | Backlinks stored in DB |
| INT-FLOW-03 | Job creates snapshot | Job completes | Snapshot created with metrics |
| INT-FLOW-04 | Job updates referring domains | Domains fetched | ReferringDomain records updated |
| INT-FLOW-05 | Job updates anchor texts | Anchors fetched | AnchorText records updated |

### 2. Rate Limiting Tests (INT-RATE)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| INT-RATE-01 | Rate limit check | Recent sync | Skip sync if within limit |
| INT-RATE-02 | Force bypasses rate limit | `--force` flag | Sync proceeds |
| INT-RATE-03 | Rate limit per project | Multiple projects | Independent rate limits |

### 3. Concurrent Sync Prevention Tests (INT-LOCK)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| INT-LOCK-01 | Lock prevents duplicate | Concurrent command | Second command skipped |
| INT-LOCK-02 | Lock released on success | Job completes | Lock released |
| INT-LOCK-03 | Lock released on failure | Job fails | Lock released |
| INT-LOCK-04 | Lock timeout | Stuck job | Lock expires after TTL |

---

## Mock Data Patterns

### GSC Backlink Data
```php
$gscBacklinks = [
    [
        'source_url' => 'https://example.com/article',
        'target_url' => 'https://myproject.com/page',
        'anchor_text' => 'great resource',
        'link_type' => 'dofollow',
    ],
    // ... more backlinks
];
```

### Project with GSC Connection
```php
$project = Project::factory()->create(['domain' => 'myproject.com']);
$connection = GSCConnection::factory()->create([
    'user_id' => $project->user_id,
    'status' => 'active',
]);
// Link project to connection via property_url
```

---

## Test Execution Commands

```bash
# Run all TASK-23-4 tests
php artisan test --filter "SyncBacklinksJob|BacklinksSyncCommand"

# Run job tests only
php artisan test tests/Unit/Jobs/SyncBacklinksJobTest.php

# Run command tests only
php artisan test tests/Unit/Commands/BacklinksSyncCommandTest.php

# Run integration tests
php artisan test tests/Feature/Jobs/SyncBacklinksJobIntegrationTest.php
```

---

## Coverage Targets

| Category | Target | Description |
|----------|--------|-------------|
| Job Construction | 100% | All properties and configuration |
| Job Execution | 100% | All execution paths |
| Retry Logic | 100% | All retry scenarios |
| Chunked Processing | 90% | Memory and chunk handling |
| Command Signature | 100% | All arguments and options |
| Command Execution | 100% | All execution paths |
| Progress Bar | 80% | CLI output testing |
| Integration | 90% | Full flow testing |

---

## Dependencies
- TASK-23-1 (Migrations) - Required for DB schema
- TASK-23-2 (Models) - Required for data models
- TASK-23-3 (GSC Fetcher) - Required for GSC API (mocked in unit tests)
- Project model and factory
- GSCConnection model and factory
- Laravel queue testing utilities

---

## Notes for Developer

### Job Implementation
- Job should use `Batchable` trait if processing large datasets
- Implement `uniqueId()` to prevent duplicate syncs
- Use `Redis::lock()` or cache lock for concurrency control
- Chunk backlinks in batches of 100 for memory efficiency

### Command Implementation
- Command class: `App\Console\Commands\BacklinksSyncCommand`
- Use Laravel's `withProgressBar()` for progress output
- Return appropriate exit codes (0 = success, 1 = error)
- Support `--quiet` and `--verbose` flags

### Error Handling
- Catch specific exceptions for retryable vs non-retryable errors
- Log all errors with context for debugging
- Update GSC connection status on permanent failures

---

## Comments

### [2026-02-28 23:08] Agent: PreTester
- Test documentation created for command and job
- 75+ test scenarios covering all acceptance criteria
- Unit tests and integration tests planned
- Ready for test implementation
