# TEST-14-7: ProcessCrawlJob Queue Worker Tests

## Task Reference
- Task: [TASK-14-7](../TASKS/TASK-14-7.md)
- Feature: FEAT-014 - Site Audit System
- Agent: PreTester

## Overview
Comprehensive test suite for ProcessCrawlJob - the queue job that orchestrates the full SEO audit crawl workflow. Tests cover job lifecycle, progress tracking, error handling, retry logic, resource management, event dispatching, and integration with SiteCrawler and IssueDetector services.

## Test Files

| File | Tests | Assertions | Status |
|------|-------|------------|--------|
| `backend/tests/Unit/Jobs/ProcessCrawlJobTest.php` | 85+ | 180+ | To Develop |
| `backend/tests/Feature/Jobs/ProcessCrawlJobIntegrationTest.php` | 35+ | 70+ | To Develop |

## Acceptance Criteria Under Test

| AC | Description | Test Category |
|----|-------------|---------------|
| AC-1 | ProcessCrawlJob created with queue implementation | JI, QUEUE |
| AC-2 | Audit status updates correctly throughout lifecycle | STATUS |
| AC-3 | Progress tracking working (cache-based) | PROGRESS |
| AC-4 | Error handling with retry logic | RETRY, ERROR |
| AC-5 | Memory and time limits enforced | RESOURCE |
| AC-6 | Events dispatched for external listeners | EVENTS |
| AC-7 | Unit tests for job execution | All categories |
| AC-8 | Feature tests for full audit flow | INT |

## Test Scenarios

### Category: Job Instantiation (JI)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| JI-001 | Job can be instantiated | new ProcessCrawlJob() | Job instance created |
| JI-002 | Job implements ShouldQueue | Instance check | Implements interface |
| JI-003 | Job properties set correctly | auditId, baseUrl | Properties stored |
| JI-004 | Default tries value | No override | 3 attempts |
| JI-005 | Default timeout value | No override | 3600 seconds |
| JI-006 | Custom tries value | tries: 5 | 5 attempts |
| JI-007 | Custom timeout value | timeout: 7200 | 7200 seconds |
| JI-008 | Job serialization | Serialize/unserialize | Properties preserved |

### Category: Job Lifecycle - Status Updates (STATUS)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| ST-001 | Status updates to running on start | handle() called | Audit status = 'running' |
| ST-002 | Status updates to completed on success | All pages crawled | Audit status = 'completed' |
| ST-003 | Status updates to failed on exception | Unhandled exception | Audit status = 'failed' |
| ST-004 | Status preserved on retry | First attempt fails | Status remains 'running' |
| ST-005 | Status updates after max retries | 3rd attempt fails | Audit status = 'failed' |
| ST-006 | Status includes error message | Failed with message | error_message populated |
| ST-007 | Status updated in database | Any status change | Database record updated |
| ST-008 | Status timestamp updated | Status change | updated_at changes |
| ST-009 | Running audit cannot be re-processed | Already running | Job skips gracefully |
| ST-010 | Completed audit cannot be re-processed | Already completed | Job skips gracefully |

### Category: Progress Tracking (PROGRESS)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| PR-001 | Initial progress stored | Job starts | pages_crawled = 0, issues_found = 0 |
| PR-002 | Progress updated after each page | Page crawled | pages_crawled incremented |
| PR-003 | Issues count updated | Issue detected | issues_found incremented |
| PR-004 | Current URL tracked | Crawling page | current_url updated |
| PR-005 | Progress cached every 10 pages | 10 pages crawled | Cache updated |
| PR-006 | Progress cache key format | auditId = 123 | audit:progress:123 |
| PR-007 | Progress cache TTL | Any update | 24 hours |
| PR-008 | Progress retrieved from cache | After cache | Same values |
| PR-009 | Progress persisted to database | Job completes | audit.pages_crawled matches |
| PR-010 | Progress survives interruption | Job killed, restarted | Progress restored from cache |
| PR-011 | Progress percentage calculated | 50 of 100 pages | 50% complete |
| PR-012 | Large page counts handled | 10,000+ pages | Memory efficient |

### Category: Crawl Processing (CRAWL)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CR-001 | Processes URLs from SiteCrawler | Mocked crawler | URLs processed |
| CR-002 | Detects issues on each page | Mocked detector | Issues recorded |
| CR-003 | Updates audit after each page | Page processed | Audit updated |
| CR-004 | Respects max pages limit | limit: 100 | Stops at 100 |
| CR-005 | Continues on individual page error | One page fails | Continues crawling |
| CR-006 | Aggregates all issues | Multiple pages | All issues collected |
| CR-007 | Filters duplicate issues | Same issue twice | Single issue recorded |
| CR-008 | Prioritizes critical issues | Critical + warning | Critical first |
| CR-009 | Handles empty crawl results | No pages found | Audit marked complete |
| CR-010 | Handles only error pages | All pages fail | Audit marked failed |

### Category: Error Handling (ERROR)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| ERR-001 | Handles crawler exception | SiteCrawler throws | Logged, marked failed |
| ERR-002 | Handles detector exception | IssueDetector throws | Logged, continues |
| ERR-003 | Handles database exception | DB connection lost | Job fails gracefully |
| ERR-004 | Handles cache exception | Redis unavailable | Continues without cache |
| ERR-005 | Handles timeout | Execution > 3600s | Job killed |
| ERR-006 | Logs exception details | Any exception | Exception logged |
| ERR-007 | Stores error in audit record | Exception thrown | error_message populated |
| ERR-008 | Continues after page error | One page fails | Other pages processed |
| ERR-009 | Aggregates page errors | Multiple failures | All errors logged |
| ERR-010 | Handles malformed CrawlResult | Invalid data | Graceful handling |

### Category: Retry Logic (RETRY)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| RT-001 | First attempt sets attempt = 1 | Job starts | attempt() = 1 |
| RT-002 | Second attempt after failure | First fails | attempt() = 2 |
| RT-003 | Third attempt after two failures | Two failures | attempt() = 3 |
| RT-004 | Max attempts triggers failure | Third failure | failed() called |
| RT-005 | Release back to queue on retry | Retry needed | Job released |
| RT-006 | Backoff delay on retry | Retry requested | Delay applied |
| RT-007 | Different backoff per attempt | attempt 1, 2, 3 | Increasing delays |
| RT-008 | Transient errors trigger retry | Connection timeout | Job retried |
| RT-009 | Permanent errors do not retry | Invalid auditId | Job fails immediately |
| RT-010 | failed() method called | Max retries exceeded | failed() executed |
| RT-011 | failed() updates audit status | Max retries | Status = 'failed' |
| RT-012 | failed() dispatches event | Max retries | AuditFailed dispatched |

### Category: Resource Management (RESOURCE)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| RES-001 | Memory limit check at 80% | Memory approaching limit | Job stops gracefully |
| RES-002 | Memory limit triggers completion | Memory threshold hit | Audit marked partial |
| RES-003 | Max execution time check | Time approaching limit | Job stops gracefully |
| RES-004 | Graceful shutdown on signal | SIGTERM received | Job completes current page |
| RES-005 | Progress saved before shutdown | Shutdown requested | Progress cached |
| RES-006 | Memory usage tracked | During execution | Logged periodically |
| RES-007 | Execution time tracked | During execution | Logged periodically |
| RES-008 | Configurable memory threshold | MEMORY_LIMIT=512M | Custom threshold applied |

### Category: Events Dispatched (EVENTS)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| EV-001 | AuditStarted dispatched | Job begins | Event dispatched |
| EV-002 | AuditProgress dispatched | Every 10 pages | Event dispatched |
| EV-003 | AuditCompleted dispatched | Job succeeds | Event dispatched |
| EV-004 | AuditFailed dispatched | Job fails | Event dispatched |
| EV-005 | Event contains audit data | Any event | Audit model included |
| EV-006 | Event contains progress data | AuditProgress | Progress details |
| EV-007 | Event contains error data | AuditFailed | Error message |
| EV-008 | Events are dispatchable | All events | Can have listeners |
| EV-009 | Events are serializable | Any event | Can be queued |

### Category: Queue Integration (QUEUE)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| QU-001 | Job queued to 'audits' queue | Dispatch job | Queue: audits |
| QU-002 | Job uses database connection | Database driver | Connection: database |
| QU-003 | Job has retry_after config | 300 seconds | Configured correctly |
| QU-004 | Job can be dispatched | ProcessCrawlJob::dispatch() | Job queued |
| QU-005 | Job can be dispatched after delay | dispatch()->delay() | Delayed job |
| QU-006 | Job handles queue restart | Queue restart | Job re-queued |
| QU-007 | Job unique ID tracking | Multiple jobs | Unique IDs |
| QU-008 | Job tags for monitoring | Job tags | audit:{id} tag |

### Category: SiteCrawler Integration (CRAWLER)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CL-001 | SiteCrawler injected correctly | Container resolve | SiteCrawler instance |
| CL-002 | SiteCrawler receives auditId | startCrawl() call | Correct auditId |
| CL-003 | SiteCrawler receives baseUrl | startCrawl() call | Correct baseUrl |
| CL-004 | Processes generator results | processQueue() | Each CrawlResult processed |
| CL-005 | Handles crawler returning null | Null result | Graceful handling |
| CL-006 | Handles crawler returning error | Error result | Error logged |
| CL-007 | Extracts links from results | CrawlResult | Links stored |

### Category: IssueDetector Integration (DETECTOR)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| DT-001 | IssueDetector injected correctly | Container resolve | IssueDetector instance |
| DT-002 | Detector receives CrawlResult | detect() call | CrawlResult passed |
| DT-003 | Detector returns IssueFinding[] | detect() result | Array of findings |
| DT-004 | Issues stored in database | Finding returned | Issue record created |
| DT-005 | Handles detector returning null | Null findings | Graceful handling |
| DT-006 | Handles detector exception | Detector throws | Logged, continues |
| DT-007 | Issues linked to audit | Issue created | audit_id FK set |

### Category: Cache Integration (CACHE)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CA-001 | Progress cached correctly | Cache::put() | Value stored |
| CA-002 | Progress retrieved correctly | Cache::get() | Value returned |
| CA-003 | Cache key format correct | auditId = 123 | audit:progress:123 |
| CA-004 | Cache TTL set | Any update | 86400 seconds |
| CA-005 | Cache cleared on completion | Job succeeds | Progress cleared |
| CA-006 | Cache persists on failure | Job fails | Progress remains |
| CA-007 | Handles cache unavailable | Redis down | Continues without cache |

### Category: Database Integration (DB)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| DB-001 | Audit record updated | Status change | Record updated |
| DB-002 | Issue records created | Findings detected | Records created |
| DB-003 | Transaction on status update | Status change | Wrapped in transaction |
| DB-004 | Handles concurrent updates | Parallel jobs | Race condition handled |
| DB-005 | Rollback on failure | Exception thrown | Transaction rolled back |

### Category: Edge Cases (EDGE)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| EG-001 | Non-existent audit | auditId = 99999 | Job fails gracefully |
| EG-002 | Audit already running | Status = running | Job skips |
| EG-003 | Audit already completed | Status = completed | Job skips |
| EG-004 | Invalid baseUrl | 'not-a-url' | Job fails |
| EG-005 | Empty baseUrl | '' | Job fails |
| EG-006 | Very large audit | 10,000 pages | Completes within limits |
| EG-007 | Zero pages found | No crawlable pages | Audit marked complete |
| EG-008 | All pages error | Every page fails | Audit marked failed |
| EG-009 | Unicode in URLs | /日本語/ | Properly handled |
| EG-010 | Extremely long URL | 2000+ char URL | Handled gracefully |
| EG-011 | Circular references | A→B→A | Detected and handled |
| EG-012 | Redirect chains | Long redirect chain | Follows up to limit |

### Category: Performance (PERF)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| PF-001 | 100 pages under 5 minutes | Small audit | Within time limit |
| PF-002 | 1000 pages under 30 minutes | Medium audit | Within time limit |
| PF-003 | Memory under 256MB | Any audit | Memory bounded |
| PF-004 | Progress update overhead | Every 10 pages | Minimal overhead |
| PF-005 | Event dispatch overhead | Every event | Minimal overhead |
| PF-006 | Database write batching | Multiple issues | Batched writes |

### Category: Integration Tests (INT)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| INT-001 | Full happy path | Valid audit | Complete successfully |
| INT-002 | Full error path | Invalid audit | Fails gracefully |
| INT-003 | Partial failure recovery | Some pages fail | Completes with errors |
| INT-004 | Resume from interruption | Job killed, restarted | Resumes from cache |
| INT-005 | Concurrent audit isolation | Two audits | Isolated state |
| INT-006 | Event listener triggered | AuditCompleted | Listener executed |
| INT-007 | Queue worker processes job | Job queued | Job processed |

## Mock Data

### ProcessCrawlJob Structure
```php
class ProcessCrawlJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    public int $auditId;
    public string $baseUrl;
    public int $tries = 3;
    public int $timeout = 3600;
    
    // Methods
    public function handle(SiteCrawler $crawler, IssueDetector $detector): void;
    public function failed(Throwable $exception): void;
}
```

### Progress Data Structure (Cache)
```php
[
    'audit_id' => 123,
    'pages_crawled' => 50,
    'issues_found' => 12,
    'current_url' => 'https://example.com/page/50',
    'started_at' => '2026-03-07T10:00:00Z',
    'last_updated' => '2026-03-07T10:15:00Z',
    'error_count' => 2,
    'errors' => [
        ['url' => 'https://example.com/timeout', 'error' => 'Connection timeout'],
    ],
]
```

### Mock CrawlResult
```php
$crawlResult = new CrawlResult(
    url: 'https://example.com/page/1',
    statusCode: 200,
    responseTimeMs: 150.5,
    html: '<html><head><title>Test</title></head><body>Content</body></html>',
    links: ['internal' => ['/page/2'], 'external' => ['https://other.com']],
    metadata: ['title' => 'Test', 'h1' => 'Heading'],
    errorMessage: null,
    headers: ['content-type' => 'text/html'],
    depth: 1,
    redirectChain: [],
);
```

### Mock IssueFinding
```php
$issueFinding = new IssueFinding(
    issueTypeId: 1,
    severity: 'high',
    message: 'Missing meta description',
    url: 'https://example.com/page/1',
    details: ['element' => 'head'],
    suggestion: 'Add a meta description tag',
);
```

### Mock Audit Model
```php
$audit = Audit::create([
    'id' => 123,
    'site_id' => 1,
    'status' => 'pending',
    'pages_crawled' => 0,
    'issues_found' => 0,
    'error_message' => null,
    'started_at' => null,
    'completed_at' => null,
]);
```

## Coverage Targets

| Category | Target | Tests |
|----------|--------|-------|
| Job Instantiation | 100% | 8 |
| Status Updates | 95% | 10 |
| Progress Tracking | 95% | 12 |
| Crawl Processing | 95% | 10 |
| Error Handling | 95% | 10 |
| Retry Logic | 100% | 12 |
| Resource Management | 95% | 8 |
| Events Dispatched | 100% | 9 |
| Queue Integration | 95% | 8 |
| SiteCrawler Integration | 95% | 7 |
| IssueDetector Integration | 95% | 7 |
| Cache Integration | 95% | 7 |
| Database Integration | 95% | 5 |
| Edge Cases | 90% | 12 |
| Performance | 85% | 6 |
| Integration | 85% | 7 |
| **Total** | **93%+** | **138** |

## Test Execution

```bash
# Run all ProcessCrawlJob tests
cd backend
php artisan test --filter=ProcessCrawlJob

# Run specific test files
php artisan test --filter=ProcessCrawlJobTest
php artisan test --filter=ProcessCrawlJobIntegrationTest

# Run with coverage
php artisan test --filter=ProcessCrawlJob --coverage

# Run specific test method
php artisan test --filter="testStatusUpdatesToRunningOnStart"
```

## Dependencies

| Dependency | Status | Notes |
|------------|--------|-------|
| TASK-14-1 (Audits table) | Done ✅ | Required for audit_id FK |
| TASK-14-5 (SiteCrawler Service) | To Test Locally | Mocked in tests |
| TASK-14-6 (RobotsParser Service) | To Develop | Used by SiteCrawler (mocked) |
| Queue System | Available | Laravel Queues |
| Redis | Required | Progress caching |
| Events | Available | Laravel Events |

## Notes for Developer

1. **Job Interface Expected:**
   ```php
   class ProcessCrawlJob implements ShouldQueue
   {
       public int $auditId;
       public string $baseUrl;
       public int $tries = 3;
       public int $timeout = 3600;
       
       public function handle(SiteCrawler $crawler, IssueDetector $detector): void;
       public function failed(Throwable $exception): void;
   }
   ```

2. **Queue Mocking:**
   ```php
   Queue::fake();
   ProcessCrawlJob::dispatch($auditId, $baseUrl);
   Queue::assertPushed(ProcessCrawlJob::class, function ($job) use ($auditId) {
       return $job->auditId === $auditId;
   });
   ```

3. **Event Mocking:**
   ```php
   Event::fake([AuditStarted::class, AuditCompleted::class]);
   // Execute job
   Event::assertDispatched(AuditStarted::class);
   Event::assertDispatched(AuditCompleted::class);
   ```

4. **Cache Mocking:**
   ```php
   Cache::shouldReceive('put')
       ->with('audit:progress:123', Mockery::any(), 86400)
       ->once();
   Cache::shouldReceive('get')
       ->with('audit:progress:123')
       ->andReturn($progressData);
   ```

5. **SiteCrawler Mocking:**
   ```php
   $mockCrawler = Mockery::mock(SiteCrawler::class);
   $mockCrawler->shouldReceive('startCrawl')
       ->with($auditId, $baseUrl)
       ->once();
   $mockCrawler->shouldReceive('processQueue')
       ->andReturn(new ArrayObject([$crawlResult1, $crawlResult2]));
   $this->app->instance(SiteCrawler::class, $mockCrawler);
   ```

6. **IssueDetector Mocking:**
   ```php
   $mockDetector = Mockery::mock(IssueDetector::class);
   $mockDetector->shouldReceive('detect')
       ->with(Mockery::type(CrawlResult::class))
       ->andReturn([$issueFinding1, $issueFinding2]);
   $this->app->instance(IssueDetector::class, $mockDetector);
   ```

7. **Memory Limit Check Pattern:**
   ```php
   $memoryLimit = config('audit.memory_limit', 512) * 1024 * 1024; // Bytes
   $memoryUsage = memory_get_usage(true);
   $threshold = $memoryLimit * 0.8; // 80%
   
   if ($memoryUsage > $threshold) {
       // Save progress and exit gracefully
       $this->saveProgress();
       return;
   }
   ```

8. **Progress Update Pattern:**
   ```php
   $progress = [
       'audit_id' => $this->auditId,
       'pages_crawled' => $pagesCrawled,
       'issues_found' => $issuesFound,
       'current_url' => $currentUrl,
       'last_updated' => now()->toISOString(),
   ];
   Cache::put("audit:progress:{$this->auditId}", $progress, 86400);
   
   // Update every 10 pages
   if ($pagesCrawled % 10 === 0) {
       AuditProgress::dispatch($audit, $progress);
   }
   ```

9. **Retry Pattern with Backoff:**
   ```php
   public function backoff(): array
   {
       return [10, 30, 60]; // Seconds between retries
   }
   
   public function retryUntil(): DateTime
   {
       return now()->addHours(2); // Give up after 2 hours
   }
   ```

10. **Error Classification for Retry:**
    ```php
    // Transient errors (retry)
    - Connection timeouts
    - 5xx server errors
    - Rate limiting (429)
    
    // Permanent errors (fail immediately)
    - Invalid auditId
    - Invalid baseUrl
    - Missing required data
    ```

## Comments

| Date | Agent | Comment |
|------|-------|---------|
| 2026-03-07 | PreTester | Test documentation created |
| 2026-03-07 | PreTester | 138 test scenarios defined across 17 categories |
| 2026-03-07 | PreTester | TDD pattern - tests will fail until implementation |
| 2026-03-07 | PreTester | SiteCrawler and IssueDetector mocked for parallel development |

---

**Status:** To Develop
**Created:** 2026-03-07
**Last Updated:** 2026-03-07
