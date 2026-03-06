# TASK-14-5: Create SiteCrawler Service

## STATUS
`To Prioritize`

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
_Agent communication log_
