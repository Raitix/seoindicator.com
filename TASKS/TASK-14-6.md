# TASK-14-6: Create RobotsParser Service

## STATUS
`To Write Tests`

## PRIORITY
`High`

## RISK
`Low`

## Description
Create a dedicated service for parsing robots.txt files and determining crawl permissions. This service is used by SiteCrawler to ensure compliant crawling.

## Dependencies
- None (standalone service)

## Technical Requirements

### Service Location
`backend/app/Services/Audit/RobotsParser.php`

### Core Methods
```php
class RobotsParser
{
    public function fetch(string $baseUrl): ?string;
    public function parse(string $robotsTxt, string $userAgent = '*'): ParsedRobots;
    public function isAllowed(string $url, string $userAgent = '*'): bool;
    public function getCrawlDelay(string $userAgent = '*'): ?int;
    public function getSitemaps(): array;
    public function getDisallowedPaths(string $userAgent = '*'): array;
}
```

### Features
1. **Robots.txt Fetching**
   - Construct robots.txt URL from base URL
   - Handle HTTP errors gracefully (404 = allow all)
   - Cache parsed results (TTL: 1 hour)
   - Timeout handling (10s default)

2. **Directive Parsing**
   - User-agent groups
   - Allow/Disallow directives
   - Crawl-delay directive
   - Sitemap directives
   - Wildcard patterns (* and $)

3. **Permission Checking**
   - Match URL against disallowed patterns
   - Support wildcards in paths
   - Handle multiple user-agent groups
   - Default to allow if no matching rules

### Data Structures

```php
class ParsedRobots
{
    public array $userAgents = [];
    public array $rules = [];
    public array $sitemaps = [];
    public ?int $crawlDelay = null;
    public bool $isEmpty = false;
}

class RobotsRule
{
    public string $userAgent;
    public string $path;
    public bool $allow;
}
```

### Caching
```php
// Cache key format: "robots:{domain_hash}"
// TTL: 3600 seconds (1 hour)
```

## Acceptance Criteria
- [ ] RobotsParser service created with all methods
- [ ] Correctly parses standard robots.txt format
- [ ] Handles wildcards in Allow/Disallow paths
- [ ] Crawl-delay extraction working
- [ ] Sitemap URLs extracted
- [ ] Graceful handling of missing/invalid robots.txt
- [ ] Caching implemented with correct TTL
- [ ] Unit tests covering edge cases

## Effort Estimate
4 hours

---

## Comments
_Agent communication log_

### ProjectManager Session (23:03 GMT+2)
**Prioritization Complete**
- **Priority:** HIGH (confirmed) - Core infrastructure for compliant crawling
- **Risk:** LOW (confirmed) - Standalone service, graceful HTTP error handling
- **Dependencies:** None - no blockers
- **TDD Pattern:** PreTester can design tests with mocked HTTP responses, robots.txt fixtures
- **Key Test Categories:**
  - Robots.txt fetching (200, 404, timeout, malformed URLs)
  - Directive parsing (user-agent groups, allow/disallow, wildcards)
  - Permission checking (URL matching, pattern wildcards)
  - Crawl-delay extraction
  - Sitemap URL extraction
  - Caching behavior (TTL verification)
- **Status:** `To Prioritize` → `To Write Tests`
