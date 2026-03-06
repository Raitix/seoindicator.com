# TEST-14-5: SiteCrawler Service Tests

## Task Reference
- Task: [TASK-14-5](../TASKS/TASK-14-5.md)
- Feature: FEAT-014 - Site Audit System
- Agent: PreTester

## Overview
Comprehensive test suite for SiteCrawler service - the foundation service for the technical SEO audit pipeline. Tests cover robots.txt compliance, rate limiting, URL queue management, content extraction, and crawl orchestration with proper SSRF protection.

## Test Files

| File | Tests | Assertions | Status |
|------|-------|------------|--------|
| `backend/tests/Unit/Services/Audit/SiteCrawlerTest.php` | 120+ | 250+ | To Develop |
| `backend/tests/Unit/Services/Audit/CrawlResultTest.php` | 45+ | 80+ | To Develop |
| `backend/tests/Unit/Services/Audit/CrawlQueueTest.php` | 55+ | 100+ | To Develop |
| `backend/tests/Feature/Services/SiteCrawlerIntegrationTest.php` | 40+ | 80+ | To Develop |

## Acceptance Criteria Under Test

| AC | Description | Test Category |
|----|-------------|---------------|
| AC-1 | SiteCrawler service created with all core methods | SI, METHODS |
| AC-2 | Robots.txt parsing and compliance working | ROBOTS |
| AC-3 | Rate limiting enforced per domain | RATE |
| AC-4 | URL queue management with deduplication | QUEUE, DEDUP |
| AC-5 | Max depth and page limits respected | LIMITS |
| AC-6 | CrawlResult data structure implemented | RESULT |
| AC-7 | Unit tests for all core methods | All categories |
| AC-8 | Configuration options documented | CONFIG |

## Test Scenarios

### Category: Service Instantiation (SI)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| SI-001 | Service can be instantiated | Container resolve | Instance of SiteCrawler |
| SI-002 | Dependencies injected | Constructor | HttpClient, RobotsParser, Logger available |
| SI-003 | Default configuration applied | No config | Default limits applied (depth:3, pages:500, rate:2/s) |
| SI-004 | Custom configuration | Config array | Custom values applied |
| SI-005 | Interface implementation | Instance check | Implements SiteCrawlerInterface |

### Category: Core Methods (METHODS)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| METH-001 | startCrawl() initializes state | auditId, baseUrl | Crawl state initialized |
| METH-002 | enqueueUrl() adds to queue | URL string | URL added with depth |
| METH-003 | processQueue() returns Generator | Initial queue | Yields CrawlResult objects |
| METH-004 | fetchPage() returns CrawlResult | Valid URL | CrawlResult with HTML |
| METH-005 | extractLinks() returns array | HTML + baseUrl | Array of internal/external URLs |
| METH-006 | shouldCrawl() respects robots.txt | Disallowed URL | false |
| METH-007 | shouldCrawl() respects rate limits | Rate limited domain | false temporarily |
| METH-008 | startCrawl() validates baseUrl | Invalid URL | Throws exception |

### Category: Robots.txt Compliance (ROBOTS)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| ROB-001 | Parse robots.txt | robots.txt with directives | Rules parsed correctly |
| ROB-002 | Respect User-agent wildcard | Disallow: /private/ | /private/ skipped |
| ROB-003 | Respect specific User-agent | User-agent: SEOIndicator-Bot | Bot-specific rules applied |
| ROB-004 | Honor Crawl-delay | Crawl-delay: 2 | 2 second delay between requests |
| ROB-005 | Skip disallowed paths | Disallow: /admin/ | /admin/* not crawled |
| ROB-006 | Allow explicitly allowed paths | Allow: /public/ | /public/ crawled |
| ROB-007 | Handle missing robots.txt | No robots.txt | Crawl all paths |
| ROB-008 | Handle malformed robots.txt | Invalid syntax | Graceful fallback |
| ROB-009 | Handle robots.txt timeout | No response | Default allow (with warning) |
| ROB-010 | Handle robots.txt redirect | 301 to new location | Follow redirect |
| ROB-011 | Handle robots.txt 403 | Access denied | Default allow (with warning) |
| ROB-012 | Handle large robots.txt | 500KB+ file | Parse within memory limits |
| ROB-013 | Cache robots.txt | Multiple requests | Single fetch per domain |
| ROB-014 | Sitemap reference | Sitemap: /sitemap.xml | Sitemap URL extracted |

### Category: Rate Limiting (RATE)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| RATE-001 | Default rate limit | No config | 2 requests/second per domain |
| RATE-002 | Custom rate limit | rate: 5 | 5 requests/second |
| RATE-003 | Per-domain tracking | Multiple domains | Independent limits |
| RATE-004 | Exponential backoff on 429 | HTTP 429 response | Delay increased |
| RATE-005 | Exponential backoff on 5xx | HTTP 503 response | Delay increased |
| RATE-006 | Max backoff limit | Multiple 429s | Cap at max delay (60s) |
| RATE-007 | Backoff reset | Successful request | Delay reset to normal |
| RATE-008 | Concurrent requests limit | 5 concurrent | Max 5 parallel requests |
| RATE-009 | Concurrent per domain | 3 per domain | Max 3 per domain |
| RATE-010 | Rate limit bypass for localhost | localhost URLs | No rate limiting |
| RATE-011 | Queue delay application | Rate limited | URL re-queued with delay |

### Category: URL Queue Management (QUEUE)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| QUEUE-001 | Priority queue - homepage first | Multiple URLs | Homepage dequeued first |
| QUEUE-002 | Depth-based priority | Depth 0, 1, 2 | Lower depth first |
| QUEUE-003 | Max depth limit | depth: 3, found depth 4 link | Not enqueued |
| QUEUE-004 | Max pages limit | 500 pages found | Stop at 500 |
| QUEUE-005 | Queue persistence | Redis storage | Survives restart |
| QUEUE-006 | Queue state tracking | Active crawl | Progress tracked |
| QUEUE-007 | Queue resume | Interrupted crawl | Resume from state |
| QUEUE-008 | Queue clear | Cancel crawl | Queue cleared |
| QUEUE-009 | Queue size tracking | 100 URLs queued | Size reported |
| QUEUE-010 | Priority boost | High-priority URL | Processed earlier |

### Category: URL Deduplication (DEDUP)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| DEDUP-001 | Skip duplicate URLs | Same URL twice | Crawled once |
| DEDUP-002 | Normalize before check | /page and /page/ | Treated as same |
| DEDUP-003 | Case sensitivity | /Page and /page | Treated as different (configurable) |
| DEDUP-004 | Query string handling | /page?a=1&b=2 vs /page?b=2&a=1 | Treated as same (sorted) |
| DEDUP-005 | Fragment handling | /page#section | Fragment stripped |
| DEDUP-006 | Tracking parameters | /page?utm_source=google | Tracking params stripped |
| DEDUP-007 | Protocol handling | http://example.com vs https://example.com | Configurable |
| DEDUP-008 | WWW handling | www.example.com vs example.com | Configurable |
| DEDUP-009 | Trailing slash | /page/ vs /page | Configurable |
| DEDUP-010 | Dedup cache | Large site | Memory-efficient storage |

### Category: Redirect Handling (REDIRECT)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| REDIR-001 | Follow 301 redirect | HTTP 301 with Location | New URL fetched |
| REDIR-002 | Follow 302 redirect | HTTP 302 with Location | New URL fetched |
| REDIR-003 | Follow 307/308 redirect | HTTP 307/308 | Method preserved |
| REDIR-004 | Max redirects limit | 5+ redirects | Stop, mark as error |
| REDIR-005 | Circular redirect | A→B→A | Detected, stopped |
| REDIR-006 | Cross-domain redirect | Internal→External | External not crawled |
| REDIR-007 | Redirect chain tracking | Multiple redirects | Chain logged |
| REDIR-008 | Meta refresh redirect | <meta http-equiv="refresh"> | Optional follow |
| REDIR-009 | JavaScript redirect | window.location | Not followed |
| REDIR-010 | Canonical URL | <link rel="canonical"> | URL extracted |

### Category: Content Extraction (CONTENT)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CONT-001 | Extract HTML | Valid page | HTML content |
| CONT-002 | Extract internal links | HTML with links | Internal URLs only |
| CONT-003 | Extract external links | HTML with links | External URLs only |
| CONT-004 | Extract metadata | HTML with meta | title, description, H1, etc. |
| CONT-005 | Measure response time | HTTP request | Response time in ms |
| CONT-006 | Track status codes | HTTP response | Status code recorded |
| CONT-007 | Extract headers | HTTP response | Headers array |
| CONT-008 | Handle gzip content | Content-Encoding: gzip | Decompressed |
| CONT-009 | Handle chunked content | Transfer-Encoding: chunked | Properly handled |
| CONT-010 | Handle binary content | PDF, image | Skip or handle gracefully |
| CONT-011 | Extract from sitemap | sitemap.xml URLs | URLs extracted |
| CONT-012 | Extract from hreflang | <link rel="alternate" hreflang> | Language URLs extracted |

### Category: CrawlResult Structure (RESULT)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| RES-001 | URL field populated | Any crawl | url string |
| RES-002 | Status code populated | HTTP response | statusCode int |
| RES-003 | Response time populated | HTTP request | responseTimeMs float |
| RES-004 | HTML populated on success | Valid page | html string |
| RES-005 | HTML null on error | Failed request | html null |
| RES-006 | Links array populated | Page with links | links array |
| RES-007 | Metadata populated | Page with meta | metadata array |
| RES-008 | Error message on failure | Failed request | errorMessage string |
| RES-009 | Headers populated | HTTP response | headers array |
| RES-010 | ToArray method | CrawlResult instance | Array representation |
| RES-011 | JsonSerialize method | CrawlResult instance | JSON serializable |
| RES-012 | Immutable after creation | Attempt to modify | New instance returned |

### Category: Max Limits (LIMITS)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| LIM-001 | Max depth enforcement | depth: 3, found depth 4 link | Not enqueued |
| LIM-002 | Max pages enforcement | pages: 500, found 600 | Stop at 500 |
| LIM-003 | Max URL length | URL > 2048 chars | Rejected or truncated |
| LIM-004 | Max HTML size | 100MB page | Truncated or rejected |
| LIM-005 | Max redirects | 10 redirect chain | Stop at max |
| LIM-006 | Max concurrent requests | 10 URLs | Limited to 5 concurrent |
| LIM-007 | Max crawl duration | 1 hour timeout | Crawl stopped |
| LIM-008 | Configurable limits | Custom config | Custom values applied |

### Category: Error Handling (ERROR)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| ERR-001 | Connection timeout | No response | Timeout error logged |
| ERR-002 | DNS failure | Invalid domain | DNS error logged |
| ERR-003 | SSL certificate error | Invalid cert | SSL error logged |
| ERR-004 | Connection refused | Server down | Connection error logged |
| ERR-005 | Malformed HTML | Invalid HTML | Graceful handling |
| ERR-006 | Empty response | Empty content | Handled gracefully |
| ERR-007 | Encoding detection | Unknown charset | UTF-8 fallback |
| ERR-008 | Partial content | Incomplete download | Retry or skip |
| ERR-009 | Error recovery | One page fails | Continue crawling |
| ERR-010 | Error reporting | Multiple errors | Aggregated report |

### Category: Security (SEC)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| SEC-001 | SSRF prevention - localhost | http://localhost/admin | Blocked |
| SEC-002 | SSRF prevention - 127.0.0.1 | http://127.0.0.1/admin | Blocked |
| SEC-003 | SSRF prevention - private IPs | http://192.168.1.1 | Blocked |
| SEC-004 | SSRF prevention - cloud metadata | http://169.254.169.254 | Blocked |
| SEC-005 | SSRF prevention - IPv6 localhost | http://[::1]/admin | Blocked |
| SEC-006 | SSRF prevention - IPv6 private | http://[fc00::1] | Blocked |
| SEC-007 | DNS rebinding protection | Dynamic DNS to internal | Blocked |
| SEC-008 | No credentials in URLs | http://user:pass@example.com | Credentials stripped |
| SEC-009 | No credentials in logs | URL with password | Password redacted |
| SEC-010 | User-Agent validation | Malicious user-agent | Sanitized |
| SEC-011 | Request header injection | Malicious headers | Sanitized |
| SEC-012 | Maximum URL length | Very long URL | Truncated or rejected |
| SEC-013 | Maximum header size | Large headers | Truncated |

### Category: Configuration (CONFIG)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CONF-001 | Max depth config | AUDIT_MAX_DEPTH=5 | 5 level depth |
| CONF-002 | Max pages config | AUDIT_MAX_PAGES=100 | 100 page limit |
| CONF-003 | Rate limit config | AUDIT_RATE_LIMIT=5 | 5 req/sec |
| CONF-004 | Max concurrent config | AUDIT_MAX_CONCURRENT=10 | 10 concurrent |
| CONF-005 | Timeout config | AUDIT_TIMEOUT=60 | 60s timeout |
| CONF-006 | User-agent config | AUDIT_USER_AGENT=CustomBot | Custom user-agent |
| CONF-007 | Config validation | Invalid config | Defaults applied |
| CONF-008 | Runtime config update | setConfig() call | Config updated |

### Category: Queue Integration (QUEUE-SYS)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| QS-001 | Queue job dispatch | startCrawl() | Job dispatched |
| QS-002 | Job retry on failure | Failed job | Retried up to 3 times |
| QS-003 | Job timeout | Long running | Job killed |
| QS-004 | Job progress tracking | Active job | Progress updated |
| QS-005 | Job cancellation | Cancel request | Job stopped |
| QS-006 | Job resume | Interrupted job | Resumed from state |
| QS-007 | Multiple jobs | Parallel crawls | Isolated state |
| QS-008 | Queue priority | Multiple crawls | Priority respected |

### Category: Edge Cases (EDGE)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| EDGE-001 | Empty URL | '' | Validation error |
| EDGE-002 | Invalid URL | 'not-a-url' | Validation error |
| EDGE-003 | Non-HTTP protocol | ftp://example.com | Rejected |
| EDGE-004 | JavaScript URL | javascript:void(0) | Rejected |
| EDGE-005 | Data URL | data:text/html,... | Rejected |
| EDGE-006 | Unicode in URL | /日本語/ページ | Properly encoded |
| EDGE-007 | Emoji in URL | /🎉/page | Properly encoded |
| EDGE-008 | Very large HTML | 100MB page | Handled gracefully |
| EDGE-009 | Infinite scroll page | Dynamic content | Crawls initial load |
| EDGE-010 | SPA without SSR | React/Vue app | Limited content |
| EDGE-011 | Cookie consent banner | GDPR popup | Handled |
| EDGE-012 | Rate limit headers | X-RateLimit-Remaining: 0 | Backoff applied |
| EDGE-013 | Self-referential link | Link to same page | Not enqueued |
| EDGE-014 | Orphan page | Unlinked page | Not discovered |
| EDGE-015 | Canonical to different URL | Canonical mismatch | URL tracked |

### Category: Performance (PERF)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| PERF-001 | Single page fetch < 30s | Normal page | Fetch completed |
| PERF-002 | 100 pages < 5 minutes | Medium site | Within time limit |
| PERF-003 | Memory usage stable | 500 pages | < 512MB |
| PERF-004 | Queue memory efficient | Large queue | Memory bounded |
| PERF-005 | Dedup cache efficient | 10K URLs | Memory bounded |
| PERF-006 | Concurrent requests | 5 parallel | 5x speedup |

### Category: Integration (INT)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| INT-001 | Full crawl workflow | Small site | All pages crawled |
| INT-002 | Integration with RobotsParser | robots.txt | Rules respected |
| INT-003 | Integration with IssueDetector | Crawl results | Issues detected |
| INT-004 | Integration with Audit model | auditId | Audit updated |
| INT-005 | Queue worker integration | Job dispatch | Job processed |
| INT-006 | Cache integration | Redis | State persisted |

## Mock Data

### CrawlResult DTO Structure
```php
class CrawlResult
{
    public string $url;              // The crawled URL
    public int $statusCode;          // HTTP status code
    public float $responseTimeMs;    // Response time in milliseconds
    public ?string $html;            // HTML content (null on error)
    public array $links;             // ['internal' => [...], 'external' => [...]]
    public array $metadata;          // ['title', 'description', 'h1', 'canonical', ...]
    public ?string $errorMessage;    // Error details if failed
    public array $headers;           // Response headers
    public int $depth;               // Crawl depth
    public array $redirectChain;     // Redirect history
}
```

### CrawlQueue Item Structure
```php
class CrawlQueueItem
{
    public string $url;
    public int $depth;
    public int $priority;
    public string $status;           // 'pending', 'processing', 'done', 'failed'
    public ?int $retryCount;
    public ?string $errorMessage;
    public string $addedAt;          // ISO timestamp
}
```

### Crawl State Structure
```php
class CrawlState
{
    public int $auditId;
    public string $baseUrl;
    public int $totalPages;
    public int $pagesCrawled;
    public int $pagesFailed;
    public string $status;           // 'pending', 'running', 'completed', 'failed'
    public string $startedAt;
    public ?string $completedAt;
    public array $errors;
}
```

## Coverage Targets

| Category | Target | Tests |
|----------|--------|-------|
| Service Instantiation | 100% | 5 |
| Core Methods | 95% | 8 |
| Robots.txt Compliance | 95% | 14 |
| Rate Limiting | 95% | 11 |
| URL Queue Management | 95% | 10 |
| URL Deduplication | 95% | 10 |
| Redirect Handling | 90% | 10 |
| Content Extraction | 95% | 12 |
| CrawlResult Structure | 100% | 12 |
| Max Limits | 95% | 8 |
| Error Handling | 95% | 10 |
| Security | 100% | 13 |
| Configuration | 95% | 8 |
| Queue Integration | 90% | 8 |
| Edge Cases | 90% | 15 |
| Performance | 85% | 6 |
| Integration | 85% | 6 |
| **Total** | **93%+** | **166** |

## Test Execution

```bash
# Run all SiteCrawler tests
cd backend
php artisan test --filter=SiteCrawler

# Run specific test categories
php artisan test --filter=SiteCrawlerTest
php artisan test --filter=CrawlResultTest
php artisan test --filter=CrawlQueueTest
php artisan test --filter=SiteCrawlerIntegrationTest

# Run with coverage
php artisan test --filter=SiteCrawler --coverage

# Run specific test method
php artisan test --filter="testRobotsTxtDisallowsPath"
```

## Dependencies

| Dependency | Status | Notes |
|------------|--------|-------|
| TASK-14-1 (Audits table) | Done ✅ | Required for audit_id FK |
| TASK-14-3 (Issue Types table) | Done ✅ | Required for issue detection |
| TASK-14-6 (RobotsParser) | To Develop | Mocked in tests |
| TASK-14-8 (IssueDetector) | To Develop | Mocked in tests |
| HttpClient | Available | Laravel HTTP client |
| Queue System | Available | Laravel Queues |
| Redis | Required | Queue persistence |

## Notes for Developer

1. **Service Interface Expected:**
   ```php
   interface SiteCrawlerInterface
   {
       public function startCrawl(int $auditId, string $baseUrl): void;
       public function enqueueUrl(string $url, int $depth = 0): void;
       public function processQueue(): Generator;
       public function fetchPage(string $url): CrawlResult;
       public function extractLinks(string $html, string $baseUrl): array;
       public function shouldCrawl(string $url): bool;
       public function cancelCrawl(): void;
       public function getStatus(): CrawlState;
   }
   ```

2. **HTTP Client Mocking:**
   ```php
   Http::fake([
       'https://example.com/' => Http::response('<html>...</html>', 200),
       'https://example.com/robots.txt' => Http::response('User-agent: *', 200),
       'https://example.com/404' => Http::response('', 404),
       'https://example.com/redirect' => Http::response('', 301, ['Location' => '/new']),
   ]);
   ```

3. **SSRF Protection Required:**
   - Block localhost, 127.0.0.1, ::1
   - Block private IP ranges (10.x, 172.16-31.x, 192.168.x)
   - Block cloud metadata IP (169.254.169.254)
   - Block IPv6 private ranges (fc00::/7, fe80::/10)
   - Consider DNS rebinding attacks

4. **Queue Mocking:**
   ```php
   Queue::fake();
   // Execute crawl
   Queue::assertPushed(CrawlJob::class);
   ```

5. **Generator Testing:**
   ```php
   $results = $crawler->processQueue();
   $firstResult = $results->current(); // Get first result
   $results->next(); // Move to next
   ```

6. **Performance Considerations:**
   - Use generators for memory efficiency
   - Implement queue persistence for resume
   - Cache robots.txt per domain
   - Use Redis for dedup tracking
   - Consider connection pooling

7. **Error Handling:**
   - Never throw exceptions from processQueue()
   - Return error CrawlResult instead
   - Log errors with context
   - Track error count per domain

## Comments

| Date | Agent | Comment |
|------|-------|---------|
| 2026-03-06 | PreTester | Test documentation created |
| 2026-03-06 | PreTester | 166 test scenarios defined across 17 categories |
| 2026-03-06 | PreTester | TDD pattern - tests will fail until implementation |
| 2026-03-06 | PreTester | SSRF protection tests included (SEC-001 to SEC-013) |

---

**Status:** To Develop
**Created:** 2026-03-06
**Last Updated:** 2026-03-06
