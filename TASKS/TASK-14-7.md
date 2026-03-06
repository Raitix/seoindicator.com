# TASK-14-7: Create CrawlJob Queue Worker

## STATUS
`To Prioritize`

## PRIORITY
`High`

## RISK
`Medium`

## Description
Create the queue job that processes crawl requests asynchronously. This enables non-blocking audit execution with progress tracking and error recovery.

## Dependencies
- TASK-14-5: SiteCrawler Service
- TASK-14-6: RobotsParser Service

## Technical Requirements

### Job Location
`backend/app/Jobs/ProcessCrawlJob.php`

### Job Structure
```php
class ProcessCrawlJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    public int $auditId;
    public string $baseUrl;
    public int $tries = 3;
    public int $timeout = 3600; // 1 hour max
    
    public function handle(
        SiteCrawler $crawler,
        IssueDetector $detector
    ): void;
    
    public function failed(Throwable $exception): void;
}
```

### Features
1. **Job Lifecycle**
   - Update audit status to 'running' on start
   - Process URLs from queue
   - Detect issues on each page
   - Update audit progress (pages_crawled, issues_found)
   - Update audit status to 'completed' or 'failed'

2. **Progress Tracking**
   - Store progress in cache: `audit:progress:{id}`
   - Update every 10 pages
   - Track: pages_crawled, issues_found, current_url

3. **Error Handling**
   - Retry on transient errors (3 attempts)
   - Log failures to audit record
   - Mark audit as failed after max retries
   - Continue crawling on individual page errors

4. **Resource Management**
   - Memory limit check (stop at 80% of limit)
   - Max execution time check
   - Graceful shutdown on signal

### Queue Configuration
```php
// config/queue.php
'connections' => [
    'audit' => [
        'driver' => 'database',
        'table' => 'jobs',
        'queue' => 'audits',
        'retry_after' => 300,
    ],
],
```

### Events
```php
// Events to dispatch
AuditStarted::dispatch($audit);
AuditProgress::dispatch($audit, $progress);
AuditCompleted::dispatch($audit);
AuditFailed::dispatch($audit, $error);
```

## Acceptance Criteria
- [ ] ProcessCrawlJob created with queue implementation
- [ ] Audit status updates correctly throughout lifecycle
- [ ] Progress tracking working (cache-based)
- [ ] Error handling with retry logic
- [ ] Memory and time limits enforced
- [ ] Events dispatched for external listeners
- [ ] Unit tests for job execution
- [ ] Feature tests for full audit flow

## Effort Estimate
6 hours

---

## Comments
_Agent communication log_
