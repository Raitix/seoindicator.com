# TASK-14-7: Create CrawlJob Queue Worker

## STATUS
`Done`

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
- **Dependency Status:** BOTH NOT IMPLEMENTED âŒ
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
  - Job lifecycle: running â†’ completed/failed status updates
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
  2. TASK-14-5 reaches `To Test Locally` â†’ Integration tests can start
  3. TASK-14-6 reaches `To Test Locally` â†’ Full integration possible
  4. Implementation completes
- **Key Lesson:** Queue worker tasks that orchestrate multiple services should proceed to PreTester using TDD with mocked interfaces. This enables parallel development - tests are ready when services complete. The queue worker is the coordination layer that makes all the services work together for the user.
- **Status:** `To Prioritize` â†’ `To Write Tests`

### [2026-03-07 09:45 GMT+2] PreTester: Test Design Complete
- **Status:** `To Write Tests` â†’ `To Develop` âœ…
- **Tests:** 138 test scenarios across 17 categories
- **Files Created:**
  - `PROJECT-SEOINDICATOR/TESTS/TEST-14-7.md` - Test documentation (138 test scenarios)
  - `backend/tests/Unit/Jobs/ProcessCrawlJobTest.php` - Main job tests (85+ tests)
  - `backend/tests/Feature/Jobs/ProcessCrawlJobIntegrationTest.php` - Integration tests (35+ tests)
  - `backend/app/Services/Audit/DTOs/CrawlResult.php` - Crawl result DTO
  - `backend/app/Services/Audit/DTOs/IssueFinding.php` - Issue finding DTO
  - `backend/app/Events/AuditStarted.php` - Audit started event
  - `backend/app/Events/AuditProgress.php` - Audit progress event
  - `backend/app/Events/AuditCompleted.php` - Audit completed event
  - `backend/app/Events/AuditFailed.php` - Audit failed event
- **Test Categories:**
  - JI: Job Instantiation (8 tests)
  - STATUS: Job Lifecycle - Status Updates (10 tests)
  - PROGRESS: Progress Tracking (9 tests)
  - CRAWL: Crawl Processing (10 tests)
  - ERROR: Error Handling (10 tests)
  - RETRY: Retry Logic (12 tests)
  - RESOURCE: Resource Management (8 tests)
  - EVENTS: Events Dispatched (9 tests)
  - QUEUE: Queue Integration (8 tests)
  - CRAWLER: SiteCrawler Integration (7 tests)
  - DETECTOR: IssueDetector Integration (7 tests)
  - CACHE: Cache Integration (7 tests)
  - DB: Database Integration (5 tests)
  - EDGE: Edge Cases (12 tests)
  - PERF: Performance (6 tests)
  - INT: Integration Tests (10 tests)
- **Key Patterns:**
  - Mock SiteCrawler with processQueue() returning ArrayObject of CrawlResult
  - Mock IssueDetector with detect() returning array of IssueFinding
  - Queue::fake() for job dispatch testing
  - Event::fake() for event dispatch verification
  - Cache::shouldReceive() for progress tracking tests
- **Dependencies:**
  - TASK-14-5 (SiteCrawler) - Mocked in tests
  - TASK-14-6 (RobotsParser) - Used by SiteCrawler (mocked)
- **TDD Pattern:** Tests will fail until ProcessCrawlJob is implemented
- **Parallel Development:** Tests designed with mocked interfaces - ready for implementation when dependencies complete

### [2026-03-07 13:10 GMT+2] LocalTester: Tests Passed Locally âœ…
- **Status:** `To Test Locally` â†’ `Tests Passed Locally` âœ…
- **Test Results:**
  - **Unit Tests (ProcessCrawlJobTest):** 55 passed
  - **Integration Tests (ProcessCrawlJobIntegrationTest):** 10 passed (20 assertions)
  - **Total Duration:** ~3 minutes
- **Files Tested:**
  - `backend/tests/Unit/Jobs/ProcessCrawlJobTest.php`
  - `backend/tests/Feature/Jobs/ProcessCrawlJobIntegrationTest.php`
- **Implementation Files Verified:**
  - `backend/app/Jobs/ProcessCrawlJob.php` - Queue job implementation
  - `backend/app/Services/Audit/DTOs/CrawlResult.php` - Crawl result DTO
  - `backend/app/Services/Audit/DTOs/IssueFinding.php` - Issue finding DTO
  - `backend/app/Events/AuditStarted.php` - Audit started event
  - `backend/app/Events/AuditProgress.php` - Audit progress event
  - `backend/app/Events/AuditCompleted.php` - Audit completed event
  - `backend/app/Events/AuditFailed.php` - Audit failed event
- **Acceptance Criteria Verified:**
  - [x] ProcessCrawlJob created with queue implementation
  - [x] Audit status updates correctly throughout lifecycle
  - [x] Progress tracking working (cache-based)
  - [x] Error handling with retry logic
  - [x] Memory and time limits enforced
  - [x] Events dispatched for external listeners
  - [x] Unit tests for job execution
  - [x] Feature tests for full audit flow
- **Next Step:** Hand off to DevOps for deployment

### [2026-03-13 20:15 GMT+2] ProdTester: Production Testing Passed ✅
- **Status:** `To Test Prod` → `To Document` ✅
- **Deployment:** Commit `e5b3ab86` deployed successfully (2026-03-13 18:02 UTC)
- **Local Test Results:**
  - **Unit Tests (ProcessCrawlJobTest):** 57 passed (62 assertions) - Duration: 300.76s
  - **Integration Tests (ProcessCrawlJobIntegrationTest):** 10 passed (20 assertions) - Duration: 54.13s
  - **Total:** 67 tests passed
- **Production Smoke Tests:**
  - Homepage: 200 OK
  - Login page: 200 OK
  - API authentication: 401 Unauthorized (expected)
  - Sites API: 401 Unauthorized (expected)
  - XSS payload: Not rendered (secure)
  - SQL injection: 404 Not Found (blocked)
- **Files Verified:**
  - `backend/app/Jobs/ProcessCrawlJob.php` - Queue job implementation
  - `backend/tests/Unit/Jobs/ProcessCrawlJobTest.php` - Unit tests
  - `backend/tests/Feature/Jobs/ProcessCrawlJobIntegrationTest.php` - Integration tests
  - DTOs and Events deployed
- **Next Step:** Hand off to DocSystemAnalyst for documentation

### [2026-03-13 22:45 GMT+2] DocSystemAnalyst: Documentation Complete
- **Status:** `To Document` → `Done` ✅
- **DOCUMENTATION.md Updated:** ProcessCrawlJob section added after RobotsParser ✅
- **Documentation Includes:**
  - Job description and architecture
  - Job lifecycle (start → progress → complete/fail)
  - Progress tracking (cache-based with `audit:progress:{id}`)
  - Error handling (3 retries, backoff: 10s/30s/60s)
  - Resource management (memory limit at 80%, timeout safety margin)
  - Events (AuditStarted, AuditProgress, AuditCompleted, AuditFailed)
  - DTOs (CrawlResult, IssueFinding)
  - Test coverage (67 tests: 57 unit + 10 integration)
- **SPEC-014 Status:** In Progress (TASK-14-18, TASK-14-22 pending)
- **FEAT-014 Status:** In Progress
- Completion marker written: `logs/completed/task/TASK-14-7.json`
