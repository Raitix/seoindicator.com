# TASK-23-5: Scheduled Daily Backlink Sync

## STATUS
`To Prioritize`

## PRIORITY
`Medium`

## RISK
`Low`

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
