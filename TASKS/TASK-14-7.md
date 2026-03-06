# TASK-14-7: Create CrawlJob Queue Worker

## STATUS
`To Write Tests`

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

### [2026-03-06 23:11 GMT+2] ProjectManager: Prioritization Complete
- **Priority:** HIGH (confirmed) - Foundation queue worker for entire SEO audit pipeline
- **Risk:** MEDIUM (confirmed) - Queue processing, memory management, error handling, progress tracking
- **Dependency Status:** BOTH NOT IMPLEMENTED ❌
  - TASK-14-5 (SiteCrawler): `To Write Tests` - NOT DONE
  - TASK-14-6 (RobotsParser): `To Write Tests` - NOT DONE
- **TDD Pattern Applied:**
  - PreTester CAN design tests with mocked service interfaces before dependencies complete
  - Mock `SiteCrawler` interface with `CrawlResult` data structure
  - Mock `IssueDetector` interface with `IssueFinding` data structure
  - Test categories: job lifecycle, progress tracking, error handling, memory limits, events, retry logic
  - Laravel's Queue fake for testing job dispatch and handling
  - Cache facade mocking for progress tracking verification
- **Key Features:**
  - Job lifecycle: running → completed/failed status updates
  - Progress tracking: cache-based (pages_crawled, issues_found, current_url)
  - Error handling: 3 retry attempts, graceful failure
  - Resource management: memory limit check (80%), timeout enforcement
  - Events: AuditStarted, AuditProgress, AuditCompleted, AuditFailed
- **Parallel Development:**
  - Tests designed with mocked interfaces in parallel with TASK-14-5 and TASK-14-6 development
  - Implementation waits for dependencies to reach `To Test Locally`
  - Integration tests run against real services when ready
- **Downstream Impact:** This queue worker is the **entry point** for all audit execution:
  - Enables non-blocking audit processing
  - Provides progress tracking for frontend
  - Coordinates SiteCrawler + IssueDetector
  - Handles failure recovery
- **Execution Order:**
  1. Tests designed (parallel with TASK-14-5, TASK-14-6 development)
  2. TASK-14-5 reaches `To Test Locally` → Integration tests can start
  3. TASK-14-6 reaches `To Test Locally` → Full integration possible
  4. Implementation completes
- **Key Lesson:** Queue worker tasks that orchestrate multiple services should proceed to PreTester using TDD with mocked interfaces. This enables parallel development - tests are ready when services complete. The queue worker is the coordination layer that makes all the services work together for the user.
- **Status:** `To Prioritize` → `To Write Tests`
