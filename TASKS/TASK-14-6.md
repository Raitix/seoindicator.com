# TASK-14-6: Create RobotsParser Service

## STATUS
`To Deploy`

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

### PreTester Session (06:48 UTC)
**Test Design Complete**
- Created comprehensive test suite: `backend/tests/Unit/Services/Audit/RobotsParserTest.php`
- 116 tests designed across 12 categories:
  - SI (Service Instantiation): 5 tests
  - FETCH (Robots.txt Fetching): 14 tests
  - PARSE (Directive Parsing): 18 tests
  - PERM (Permission Checking): 16 tests
  - WILDCARD (Pattern Matching): 12 tests
  - DELAY (Crawl Delay): 8 tests
  - SITEMAP (Sitemap Extraction): 6 tests
  - CACHE (Caching Behavior): 10 tests
  - ERROR (Error Handling): 10 tests
  - SEC (SSRF Prevention): 10 tests
  - EDGE (Edge Cases): 12 tests
  - INT (Integration): 5 tests
- Created DTOs: `ParsedRobots.php`, `RobotsRule.php`
- Test documentation: `TESTS/TEST-14-6.md`
- All acceptance criteria covered by tests
- Ready for Developer implementation

### Developer Session (07:38 GMT+2)
**Implementation Complete**
- Created `RobotsParser.php` with full implementation:
  - `fetch()`: HTTP fetching with SSRF protection, caching, timeout handling
  - `parse()`: Full robots.txt directive parsing (user-agent groups, allow/disallow, crawl-delay, sitemaps)
  - `isAllowed()`: Permission checking with wildcard pattern support, case-sensitive matching
  - `getCrawlDelay()`: Per-user-agent crawl delay extraction
  - `getSitemaps()`: Sitemap URL extraction
  - `getDisallowedPaths()`: Disallowed path listing
- Registered as singleton in `AppServiceProvider.php`
- All 126 unit tests pass
- Key features implemented:
  - SSRF protection (blocks localhost, private IPs, cloud metadata)
  - International domain support (punycode conversion)
  - Proper URL-encoded character handling
  - Case-sensitive path matching per robots.txt spec
  - Force refresh cache bypass
  - Graceful error handling for all HTTP scenarios
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

### LocalTester Session (09:58 GMT+2)
**Local Testing Complete - PASSED**
- **Test Results:** 126 passed, 182 assertions
- **Duration:** 54.16s (SSRF tests make DNS lookups for blocked IPs)
- **Categories Tested:**
  - SI (Service Instantiation): 5 tests ✅
  - FETCH (Robots.txt Fetching): 14 tests ✅
  - PARSE (Directive Parsing): 18 tests ✅
  - PERM (Permission Checking): 16 tests ✅
  - WILDCARD (Pattern Matching): 12 tests ✅
  - DELAY (Crawl Delay): 8 tests ✅
  - SITEMAP (Sitemap Extraction): 6 tests ✅
  - CACHE (Caching Behavior): 10 tests ✅
  - ERROR (Error Handling): 10 tests ✅
  - SEC (SSRF Prevention): 10 tests ✅
  - EDGE (Edge Cases): 12 tests ✅
  - INT (Integration): 5 tests ✅
- **Key Verifications:**
  - SSRF protection blocks localhost, private IPs, cloud metadata IPs
  - Wildcard pattern matching (* and $) works correctly
  - Case-sensitive path matching per robots.txt spec
  - Crawl-delay extraction per user-agent
  - Sitemap URL extraction
  - Caching with 1-hour TTL
  - Graceful error handling for all HTTP scenarios
- **Recommendation:** Ready for deployment to staging
