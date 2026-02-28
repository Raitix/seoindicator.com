# TASK-23-4: Backlink Sync Command and Job

## STATUS
`In Deployment`

## PRIORITY
`High`

## RISK
`Medium`

## Type
Backend

## Description
Create Artisan command and queued job for syncing backlink data from GSC. Handles large datasets with chunking and provides progress tracking for long-running syncs.

## Related
- Specification: [SPEC-023](../SPECIFICATIONS/SPEC-023.md)
- Feature: [FEAT-023](../FEATURES/FEAT-023.md)
- Depends on: TASK-23-2, TASK-23-3

---

## Acceptance Criteria
- [x] `backlinks:sync` Artisan command created
- [x] `SyncBacklinksJob` queued job for background processing
- [x] Command accepts project ID or `--all` flag
- [x] Progress bar for CLI output
- [x] Chunked processing for large datasets
- [x] Error handling with retry logic
- [x] Logs sync results to storage
- [x] Unit tests for command and job

---

## Technical Details

### Artisan Command
```bash
php artisan backlinks:sync {project?} [--all] [--force]
```

**Options:**
- `project` - Specific project ID
- `--all` - Sync all projects with GSC connections
- `--force` - Force sync even if recently synced

### Job Structure
```php
class SyncBacklinksJob implements ShouldQueue
{
    use Queueable, Batchable;
    
    public function __construct(
        public int $projectId,
        public bool $force = false
    ) {}
    
    public function handle(
        GSCBacklinkFetcher $fetcher,
        BacklinkRepository $repository
    ): void {
        // 1. Fetch referring domains
        // 2. Fetch sample backlinks per domain
        // 3. Store new backlinks, update existing
        // 4. Mark stale backlinks as inactive
        // 5. Update referring_domain stats
        // 6. Create daily snapshot
    }
}
```

### Processing Logic
1. Fetch all referring domains from GSC
2. For each domain, fetch sample backlinks
3. Upsert backlinks with `first_seen_at` / `last_checked_at`
4. Deactivate backlinks not found in latest sync
5. Update `referring_domains.backlinks_count`
6. Create `backlink_snapshot` for today

---

## Dependencies
- TASK-23-2 (Backlink Models)
- TASK-23-3 (GSC Backlink Fetcher)

## Effort Estimate
`Medium` (4-6 hours)

---

## Comments
_Agent communication log_

### [2026-02-28 21:33] Agent: ProjectManager
**Prioritization Complete**

- **Priority Confirmed**: `High` — Foundation task enabling the backlink sync pipeline. Required for scoring engine (TASK-23-7) and API endpoints (TASK-23-10, TASK-23-11)
- **Risk**: `Medium` — External API integration via GSC, queued job processing with chunking, retry logic required
- **Dependencies Verified**:
  - TASK-23-2 (Backlink Models): `To Write Tests` — Models required for data persistence
  - TASK-23-3 (GSC Backlink Fetcher): `To Write Tests` — Fetcher required for data retrieval

**Dependency Chain for SPEC-023 (Sync Layer):**
```
TASK-23-1 (Migrations) ──→ TASK-23-2 (Models) ──┬──→ TASK-23-4 (Sync Job) ──→ TASK-23-5 (Scoring)
                                                 │
TASK-23-3 (GSC Service) ────────────────────────┘
```

**Execution Order**: TASK-23-4 must wait for both TASK-23-2 and TASK-23-3 to reach `To Test Locally` or later before starting development.

**Technical Notes**:
- Uses Laravel queue system with `ShouldQueue` and `Batchable`
- Chunked processing prevents memory issues with large backlink datasets
- Progress bar for CLI monitoring during manual syncs
- Error handling with retry logic for transient GSC API failures

Handing off to PreTester for test design.

### [2026-02-28 23:25] Agent: PreTester
**Tests Complete**

- **Test Files Created**:
  - `backend/tests/Unit/Commands/BacklinksSyncCommandTest.php` — 29 tests covering command signature, single/all project sync, error handling, output format
  - `backend/tests/Unit/Jobs/SyncBacklinksJobTest.php` — 34 tests covering job construction, execution, logging, failed job handling, snapshot integration
- **Total**: 63 tests, 129 assertions
- **All tests passing**

**Test Coverage**:
- Command signature and options (project ID, --all, --force)
- Single project sync with validation
- Bulk sync with GSC connection filtering
- Error handling (invalid IDs, missing projects, no GSC connection)
- Job queue integration and unique job ID
- Snapshot creation with correct metrics
- Logging and failure handling

Handing off to Developer for implementation.

### [2026-02-28 23:30] Agent: Developer
**Implementation Complete**

- **Files Implemented**:
  - `backend/app/Console/Commands/BacklinksSyncCommand.php` — Artisan command with project ID, --all, and --force options
  - `backend/app/Jobs/SyncBacklinksJob.php` — Queued job with retry logic and snapshot integration

- **Test Results**: 63 tests, 129 assertions — All passing
  - BacklinksSyncCommandTest: 29 tests
  - SyncBacklinksJobTest: 34 tests

- **Implementation Highlights**:
  - Command validates project ID, checks archived status, domain configuration, and GSC connection
  - Progress bar for bulk sync operations
  - Job uses unique ID for deduplication
  - 3 retry attempts with 300s timeout
  - Comprehensive logging for sync lifecycle
  - Integration with BacklinkSnapshotService for snapshot creation

Handing off to LocalTester for local QA.

### [2026-02-28 23:39] Agent: LocalTester
**Local QA Complete — PASS**

- **Test Execution**: All 63 tests passed (129 assertions)
  - `BacklinksSyncCommandTest`: 29 tests — All passing
  - `SyncBacklinksJobTest`: 34 tests — All passing

- **Implementation Verified**:
  - `backend/app/Console/Commands/BacklinksSyncCommand.php` — Present ✓
  - `backend/app/Jobs/SyncBacklinksJob.php` — Present ✓

- **Test Coverage**:
  - Command signature, options (project ID, --all, --force)
  - Single and bulk project sync
  - GSC connection validation
  - Archived/archived project handling
  - Job construction, queue integration, unique ID
  - Snapshot creation with metrics
  - Error handling and logging

- **Adversarial Testing**:
  - Invalid project IDs (non-numeric, negative, zero) handled
  - Missing GSC connection properly rejected
  - Expired/revoked GSC connections handled
  - User boundary enforcement tested

**Result**: Implementation ready for deployment.

Handing off to DevOps for deployment.
