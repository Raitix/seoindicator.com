# TESTS/TASK-22-3.md — CrawlerService Skeleton Tests

## Task Reference
- **Task:** TASK-22-3: CrawlerService Skeleton
- **Specification:** SPEC-022: Headless Crawler Service
- **Feature:** FEAT-022: Headless Crawler Service

---

## Overview
Unit tests for the CrawlerService skeleton with job management methods. Tests are designed using TDD approach with mocked model dependencies since TASK-22-2 (Models) is not yet implemented.

---

## Test Files

| File | Tests | Assertions | Description |
|------|-------|------------|-------------|
| `Unit/Services/CrawlerServiceTest.php` | 60+ | 120+ | Service method tests |
| `Unit/Config/CrawlerConfigTest.php` | 15+ | 30+ | Configuration tests |

---

## Test Scenarios

### 1. Configuration Tests (CC)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CC-001 | Config file exists | N/A | File `config/crawler.php` exists |
| CC-002 | Max depth default | `config('crawler.max_depth')` | 2 |
| CC-003 | Max pages default | `config('crawler.max_pages')` | 50 |
| CC-004 | Max content size default | `config('crawler.max_content_size')` | 3145728 (3MB) |
| CC-005 | Page timeout default | `config('crawler.page_timeout')` | 30 |
| CC-006 | Job timeout default | `config('crawler.job_timeout')` | 300 (5 min) |
| CC-007 | Retention days default | `config('crawler.retention_days')` | 7 |
| CC-008 | Default user agent | `config('crawler.user_agent')` | 'SEOIndicator/1.0 Crawler' |
| CC-009 | Default wait time | `config('crawler.default_wait_time')` | 2000 |
| CC-010 | Max redirects | `config('crawler.max_redirects')` | 5 |
| CC-011 | All config keys present | N/A | All required keys defined |
| CC-012 | Config values are integers | N/A | Numeric values validated |
| CC-013 | Max content size is 3MB | N/A | 3 * 1024 * 1024 = 3145728 |
| CC-014 | Environment override | Set CRAWLER_MAX_DEPTH=3 | Config returns 3 |
| CC-015 | Invalid depth capped | depth=5 | Capped to max_depth (2) |

### 2. createJob Method Tests (CJ)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CJ-001 | Create basic job | userId=1, url='https://example.com' | CrawlJob created with status='queued' |
| CJ-002 | Generate unique job_id | N/A | job_id format: 'crawl-{uuid}' |
| CJ-003 | Validate URL format | url='not-a-url' | Throws InvalidUrlException |
| CJ-004 | Validate URL protocol | url='ftp://example.com' | Throws InvalidUrlException (only http/https) |
| CJ-005 | Set default options | options=[] | Uses config defaults |
| CJ-006 | Merge custom options | options={'waitTime':5000} | Merges with defaults |
| CJ-007 | Set expiry date | N/A | expires_at = now + retention_days |
| CJ-008 | Set initial status | N/A | status = 'queued' |
| CJ-009 | Dispatch to queue | N/A | Job dispatched to queue |
| CJ-010 | Associate with user | userId=1 | user_id = 1 |
| CJ-011 | Associate with project | options={'project_id':1} | project_id = 1 |
| CJ-012 | Validate SSRF blocked URLs | url='http://localhost' | Throws SSRFException |
| CJ-013 | Validate SSRF private IP | url='http://192.168.1.1' | Throws SSRFException |
| CJ-014 | Validate SSRF cloud metadata | url='http://169.254.169.254' | Throws SSRFException |
| CJ-015 | Validate max depth override | options={'depth':5} | Capped to config max_depth |
| CJ-016 | Return created job | N/A | Returns CrawlJob instance |
| CJ-017 | Log job creation | N/A | Log entry with job_id and url |
| CJ-018 | Job_id uniqueness check | N/A | job_id is unique |

### 3. getJob Method Tests (GJ)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| GJ-001 | Get existing job | jobId='crawl-abc123' | Returns CrawlJob |
| GJ-002 | Get non-existent job | jobId='crawl-nonexistent' | Returns null |
| GJ-003 | Include page count | Existing job | pages_crawled included |
| GJ-004 | Include total size | Existing job | total_size included |
| GJ-005 | Invalid job_id format | jobId='invalid' | Returns null |
| GJ-006 | Load pages relationship | Existing job | pages loaded if requested |
| GJ-007 | By job_id not id | jobId='crawl-abc123' | Queries by job_id, not id |

### 4. processJob Method Tests (PJ)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| PJ-001 | Skeleton method exists | N/A | Method exists and is callable |
| PJ-002 | Job must exist | jobId='nonexistent' | Throws CrawlJobNotFoundException |
| PJ-003 | Job status validated | status='completed' | Throws InvalidJobStatusException |
| PJ-004 | Sets status to running | status='queued' | status updated to 'running' |
| PJ-005 | Sets started_at | N/A | started_at timestamp set |
| PJ-006 | Try-catch wrapper | Exception during crawl | Catches, sets failed status |
| PJ-007 | Log errors | Exception | Error logged with context |
| PJ-008 | Skeleton returns void | N/A | Returns null (to be implemented later) |

### 5. cancelJob Method Tests (CA)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CA-001 | Cancel queued job | status='queued' | Returns true, status='cancelled' |
| CA-002 | Cancel running job | status='running' | Returns true, status='cancelled' |
| CA-003 | Cannot cancel completed | status='completed' | Returns false |
| CA-004 | Cannot cancel failed | status='failed' | Returns false |
| CA-005 | Cannot cancel already cancelled | status='cancelled' | Returns false |
| CA-006 | Non-existent job | jobId='nonexistent' | Throws CrawlJobNotFoundException |
| CA-007 | Records timestamp | N/A | completed_at set to now |
| CA-008 | Log cancellation | N/A | Log entry with job_id |
| CA-009 | Dispatch cancellation event | N/A | Event dispatched for worker |

### 6. cleanup Method Tests (CL)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CL-001 | Delete expired jobs | 2 expired jobs | Returns 2 |
| CL-002 | No expired jobs | 0 expired jobs | Returns 0 |
| CL-003 | Cascade delete pages | Job with 5 pages | Pages deleted with job |
| CL-004 | Only deletes expired | 1 expired, 1 active | Only expired deleted |
| CL-005 | Log cleanup count | 5 deleted | Log entry with count |
| CL-006 | Delete by expires_at | expires_at < now | Correct query |

### 7. Error Handling Tests (EH)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| EH-001 | CrawlJobNotFoundException | Invalid jobId | Exception with job_id context |
| EH-002 | CrawlJobCancellationException | Cannot cancel | Exception with reason |
| EH-003 | InvalidUrlException | Invalid URL | Exception with URL context |
| EH-004 | SSRFException | Blocked URL | Exception with reason |
| EH-005 | Log with context | Any error | Log includes job_id, url, user_id |
| EH-006 | Don't expose internals | Exception | User-friendly message |

### 8. Edge Cases Tests (EC)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| EC-001 | Empty URL | url='' | Throws InvalidUrlException |
| EC-002 | Null URL | url=null | Throws InvalidUrlException |
| EC-003 | Very long URL | url=2084 chars | Throws InvalidUrlException |
| EC-004 | Unicode URL | url='https://例え.jp' | Validated and accepted |
| EC-005 | Maximum depth | depth=2 | Accepted |
| EC-006 | Depth exceeds max | depth=10 | Capped to max_depth |
| EC-007 | Negative depth | depth=-1 | Set to 0 |
| EC-008 | Zero depth | depth=0 | Accepted |
| EC-009 | Empty options | options=[] | Uses all defaults |
| EC-010 | Large options object | options with 100 keys | Handled gracefully |

### 9. Security Tests (SC)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| SC-001 | SQL injection in job_id | jobId='crawl-1\'; DROP TABLE--' | No error, returns null |
| SC-002 | SQL injection in URL | url with SQL | Sanitized or rejected |
| SC-003 | XSS in error message | error='<script>alert(1)</script>' | Escaped in output |
| SC-004 | SSRF localhost | url='http://localhost' | Blocked |
| SC-005 | SSRF 127.x.x.x | url='http://127.0.0.1' | Blocked |
| SC-006 | SSRF 10.x.x.x | url='http://10.0.0.1' | Blocked |
| SC-007 | SSRF 172.16-31.x.x | url='http://172.16.0.1' | Blocked |
| SC-008 | SSRF 192.168.x.x | url='http://192.168.1.1' | Blocked |
| SC-009 | SSRF cloud metadata | url='http://169.254.169.254' | Blocked |
| SC-010 | SSRF IPv6 localhost | url='http://[::1]' | Blocked |
| SC-011 | SSRF file protocol | url='file:///etc/passwd' | Blocked |
| SC-012 | SSRF DNS rebinding | url resolves to private IP | Blocked after DNS resolve |

### 10. Integration Tests (IT)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| IT-001 | Full job lifecycle | Create → Process → Complete | Status transitions correct |
| IT-002 | Create and cancel | Create → Cancel | Status='cancelled' |
| IT-003 | Create and cleanup | Create expired → Cleanup | Job deleted |
| IT-004 | Multiple jobs | Create 3 jobs | All unique job_ids |
| IT-005 | Concurrent access | Same job_id | No race condition |

---

## Mock Data

### Sample CrawlJob Response
```json
{
  "id": 1,
  "user_id": 1,
  "project_id": null,
  "job_id": "crawl-abc123-def456-ghi789",
  "url": "https://example.com",
  "status": "queued",
  "depth": 1,
  "options": {
    "waitForSelector": null,
    "waitTime": 2000,
    "userAgent": "SEOIndicator/1.0 Crawler",
    "includeImages": false,
    "followRedirects": true,
    "maxRedirects": 5
  },
  "pages_crawled": 0,
  "total_size": 0,
  "error": null,
  "started_at": null,
  "completed_at": null,
  "expires_at": "2026-03-08T12:00:00Z",
  "created_at": "2026-03-01T12:00:00Z",
  "updated_at": "2026-03-01T12:00:00Z"
}
```

### Sample Options Input
```json
{
  "project_id": 1,
  "depth": 1,
  "waitForSelector": ".content",
  "waitTime": 3000,
  "userAgent": "CustomBot/1.0",
  "includeImages": true
}
```

---

## Coverage Targets

| Category | Target | Description |
|----------|--------|-------------|
| Statement Coverage | 90%+ | All service methods covered |
| Branch Coverage | 85%+ | All condition branches tested |
| Method Coverage | 100% | All public methods tested |

---

## Test Execution

```bash
# Run all crawler tests
php artisan test --filter=CrawlerService

# Run specific test file
php artisan test tests/Unit/Services/CrawlerServiceTest.php

# Run with coverage
php artisan test --coverage --filter=CrawlerService
```

---

## Dependencies

| Dependency | Status | Notes |
|------------|--------|-------|
| TASK-22-1 (Migrations) | ✅ Complete | Tables exist |
| TASK-22-2 (Models) | ⏳ To Develop | Tests use mocked models |
| TASK-1-10 (JobQueue) | ✅ Complete | Queue system available |

---

## Notes for Developer

1. **Model Mocking:** Tests use Mockery to mock CrawlJob and CrawlPage models
2. **Queue Mocking:** Queue facade is mocked for dispatch assertions
3. **Config:** Create `config/crawler.php` with all required keys
4. **Exceptions:** Create custom exception classes:
   - `CrawlJobNotFoundException`
   - `CrawlJobCancellationException`
   - `InvalidUrlException`
   - `SSRFException`
5. **Service Location:** `app/Services/CrawlerService.php`
6. **Dependency Injection:** Inject CrawlJob model and Logger via constructor

---

## Comments

### PreTester (2026-03-01 23:45 GMT+2)
**Test Design Complete:**
- Designed 75+ test scenarios across 10 categories
- Tests cover all 5 service methods (createJob, getJob, processJob, cancelJob, cleanup)
- SSRF protection tests include 12 different attack vectors
- Edge cases cover boundaries, nulls, and extreme values
- Security tests include SQL injection, XSS, and SSRF
- Integration tests verify full job lifecycle

**Handoff:** Ready for Developer to implement CrawlerService skeleton.
