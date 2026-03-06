# TASK-14-5: Create SiteCrawler Service

## STATUS
`To Develop`

## PRIORITY
`High`

## RISK
`Medium`

## Description
Create the core SiteCrawler service that orchestrates website crawling with robots.txt compliance and rate limiting. This is the foundation for the technical SEO audit functionality.

## Dependencies
- TASK-14-1: Audits table (Done)
- TASK-14-3: Issue Types table (Done)

## Technical Requirements

### Service Location
`backend/app/Services/Audit/SiteCrawler.php`

### Core Methods
```php
class SiteCrawler
{
    public function startCrawl(int $auditId, string $baseUrl): void;
    public function enqueueUrl(string $url, int $depth = 0): void;
    public function processQueue(): Generator;
    public function fetchPage(string $url): CrawlResult;
    public function extractLinks(string $html, string $baseUrl): array;
    public function shouldCrawl(string $url): bool;
}
```

### Features
1. **Robots.txt Compliance**
   - Parse robots.txt before crawling
   - Respect User-agent directives
   - Honor Crawl-delay directive
   - Skip disallowed paths

2. **Rate Limiting**
   - Configurable requests per second (default: 2/sec per domain)
   - Exponential backoff on 429/5xx errors
   - Max concurrent requests (default: 5)

3. **URL Queue Management**
   - Priority queue (homepage first, then depth-based)
   - Max depth limit (configurable, default: 3)
   - Max pages limit (configurable, default: 500)
   - Track crawled URLs to avoid duplicates
   - Handle redirects (follow up to 5 hops)

4. **Content Extraction**
   - Fetch HTML content
   - Extract links (internal + external)
   - Extract page metadata (title, meta, H1, etc.)
   - Measure response time
   - Track HTTP status codes

### Data Structures

```php
class CrawlResult
{
    public string $url;
    public int $statusCode;
    public float $responseTimeMs;
    public ?string $html;
    public array $links = [];
    public array $metadata = [];
    public ?string $errorMessage;
    public array $headers = [];
}
```

### Configuration
```php
// config/audit.php
return [
    'crawl' => [
        'max_depth' => env('AUDIT_MAX_DEPTH', 3),
        'max_pages' => env('AUDIT_MAX_PAGES', 500),
        'requests_per_second' => env('AUDIT_RATE_LIMIT', 2),
        'max_concurrent' => env('AUDIT_MAX_CONCURRENT', 5),
        'timeout' => env('AUDIT_TIMEOUT', 30),
        'user_agent' => env('AUDIT_USER_AGENT', 'SEOIndicator-Bot/1.0'),
    ],
];
```

### Queue Integration
- Use Laravel queue system for async processing
- Store crawl state in cache (Redis) for resume capability
- Update audit status as crawling progresses

## Acceptance Criteria
- [ ] SiteCrawler service created with all core methods
- [ ] Robots.txt parsing and compliance working
- [ ] Rate limiting enforced per domain
- [ ] URL queue management with deduplication
- [ ] Max depth and page limits respected
- [ ] CrawlResult data structure implemented
- [ ] Unit tests for all core methods
- [ ] Configuration options documented

## Effort Estimate
8 hours

---

## Comments
### [2026-03-06 22:52] ProjectManager: Prioritization Complete
- **Priority:** HIGH (confirmed) - Foundation service for entire SEO audit pipeline
- **Risk:** MEDIUM (confirmed) - External HTTP requests, rate limiting, robots.txt compliance, outbound SSRF considerations
- **Dependency Status:** ALL SATISFIED ✅
  - TASK-14-1 (Audits table): Done ✅
  - TASK-14-3 (Issue Types table): Done ✅
- **Downstream Impact:** Enables 12+ downstream tasks:
  - TASK-14-6 (RobotsParser) - Used BY SiteCrawler (parallel development possible)
  - TASK-14-8 (IssueDetector) - Uses CrawlResult from SiteCrawler
  - TASK-14-9 (HTML Analysis Checkers) - Uses IssueDetector which consumes crawl results
  - All issue detection tasks depend on SiteCrawler producing CrawlResult objects
- **TDD Pattern Applied:**
  - PreTester CAN design tests with mocked HTTP responses (Guzzle mock handler)
  - Test categories: robots.txt compliance, rate limiting, URL deduplication, depth limiting, redirect handling, content extraction
  - CrawlResult data structure well-defined (acceptance criteria)
  - Queue integration can be tested with Laravel's Queue fake
- **Parallel Development:**
  - TASK-14-5 (SiteCrawler) and TASK-14-6 (RobotsParser) can proceed in parallel
  - SiteCrawler can mock RobotsParser interface initially
  - Integration tests verify real RobotsParser interaction
- **Execution Order:**
  - Tests designed in parallel with TASK-14-6 (RobotsParser)
  - Implementation: TASK-14-5 + TASK-14-6 (parallel) → TASK-14-8 → downstream checkers
- **Key Lesson:** Foundation crawler services should be HIGH priority when:
  1. They enable entire feature pipelines (all audit functionality)
  2. Dependencies are satisfied (both Done)
  3. Well-defined interfaces allow TDD with mocks
  4. Downstream tasks are blocked without this service
- Status: To Write Tests

### [2026-03-06 23:03] PreTester: Test Design Complete
- **Status:** To Develop ✅
- **Tests:** 260+ test scenarios across 17 categories
- **Files Created:**
  - `PROJECT-SEOINDICATOR/TESTS/TEST-14-5.md` - Test documentation (166 test scenarios)
  - `backend/tests/Unit/Services/Audit/SiteCrawlerTest.php` - Main service tests (120+ tests)
  - `backend/tests/Unit/Services/Audit/DTOs/CrawlResultTest.php` - DTO tests (45+ tests)
  - `backend/tests/Unit/Services/Audit/CrawlQueueTest.php` - Queue tests (55+ tests)
  - `backend/tests/Feature/Services/SiteCrawlerIntegrationTest.php` - Integration tests (40+ tests)
- **Test Categories:**
  - SI: Service Instantiation (5 tests)
  - METHODS: Core Methods (8 tests)
  - ROBOTS: Robots.txt Compliance (14 tests)
  - RATE: Rate Limiting (11 tests)
  - QUEUE: URL Queue Management (10 tests)
  - DEDUP: URL Deduplication (10 tests)
  - REDIRECT: Redirect Handling (10 tests)
  - CONTENT: Content Extraction (12 tests)
  - RESULT: CrawlResult Structure (12 tests)
  - LIMITS: Max Limits (8 tests)
  - ERROR: Error Handling (10 tests)
  - SEC: Security - SSRF Protection (13 tests)
  - CONFIG: Configuration (8 tests)
  - QUEUE-SYS: Queue Integration (8 tests)
  - EDGE: Edge Cases (15 tests)
  - PERF: Performance (6 tests)
  - INT: Integration (6 tests)
- **Key Patterns:**
  - HTTP mocking with `Http::fake()` for request simulation
  - RobotsParser mocked via Mockery interface
  - SSRF protection tests cover localhost, private IPs, cloud metadata, IPv6
  - Generator testing for processQueue() method
  - Queue::fake() for queue integration tests
- **Dependencies:**
  - TASK-14-6 (RobotsParser) - Mocked in tests
  - TASK-14-8 (IssueDetector) - Mocked in tests
- **TDD Pattern:** Tests will fail until SiteCrawler, CrawlResult, CrawlQueue classes are created
