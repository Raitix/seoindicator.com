# TASK-23-4: Backlink Sync Command and Job

## STATUS
`To Write Tests`

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
- [ ] `backlinks:sync` Artisan command created
- [ ] `SyncBacklinksJob` queued job for background processing
- [ ] Command accepts project ID or `--all` flag
- [ ] Progress bar for CLI output
- [ ] Chunked processing for large datasets
- [ ] Error handling with retry logic
- [ ] Logs sync results to storage
- [ ] Unit tests for command and job

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
