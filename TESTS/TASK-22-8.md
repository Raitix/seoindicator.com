# TESTS/TASK-22-8.md — DepthControllerService Tests

## Task Reference
- **Task:** TASK-22-8: Depth Controller for L0/L1/L2 Crawling
- **Specification:** SPEC-022: Headless Crawler Service
- **Feature:** FEAT-022: Headless Crawler Service

---

## Overview
Unit tests for the DepthControllerService that manages multi-level crawling (L0/L1/L2), link discovery, filtering, and queue management.

---

## Test Files

| File | Type | Purpose |
|------|------|---------|
| `backend/tests/Unit/Services/Crawler/DepthControllerServiceTest.php` | Unit | Service method tests |
| `backend/tests/Unit/Services/Crawler/UrlNormalizerTest.php` | Unit | URL normalization tests |
| `backend/tests/Unit/Services/Crawler/LinkFilterTest.php` | Unit | Link filtering tests |

---

## Test Scenarios

### DC-001: Service Instantiation
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| DC-001-01 | Service can be instantiated via container | - | Instance created |
| DC-001-02 | Service has CrawlJob model injected | - | CrawlJob not null |
| DC-001-03 | Service has Logger injected | - | Logger not null |
| DC-001-04 | Service has UrlValidatorService injected | - | UrlValidatorService not null |
| DC-001-05 | Service loads config defaults | - | Uses config values |

### DC-002: shouldCrawl() Method
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| DC-002-01 | URL at depth 0, maxDepth 2 | url, depth=0, maxDepth=2 | true |
| DC-002-02 | URL at depth 1, maxDepth 2 | url, depth=1, maxDepth=2 | true |
| DC-002-03 | URL at depth 2, maxDepth 2 | url, depth=2, maxDepth=2 | true |
| DC-002-04 | URL at depth 3, maxDepth 2 | url, depth=3, maxDepth=2 | false (exceeds max) |
| DC-002-05 | URL already crawled | url in crawled set | false |
| DC-002-06 | URL in queue | url in pending queue | false |
| DC-002-07 | URL blocked by SSRF | url in blocked list | false |
| DC-002-08 | Empty URL | url="" | false |
| DC-002-09 | Null URL | url=null | false |
| DC-002-10 | Negative depth | depth=-1 | false |
| DC-002-11 | Depth exceeds max_pages | queue at max_pages | false |
| DC-002-12 | URL normalized before check | URL with trailing slash | Checked against normalized form |

### DC-003: extractLinks() Method - Basic Extraction
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| DC-003-01 | Extract single link | `<a href="/page">Link</a>` | ['https://example.com/page'] |
| DC-003-02 | Extract multiple links | Multiple anchor tags | Array of all hrefs |
| DC-003-03 | No links in HTML | `<p>Text</p>` | Empty array |
| DC-003-04 | Empty HTML | "" | Empty array |
| DC-003-05 | Null HTML | null | Empty array |
| DC-003-06 | Extract from complex HTML | Full HTML document | All anchor hrefs |
| DC-003-07 | Links in script tags | `<script>var link='url'</script>` | Script links ignored |
| DC-003-08 | Links in comments | `<!-- <a href="/hidden"> -->` | Comment links ignored |

### DC-004: extractLinks() Method - URL Resolution
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| DC-004-01 | Relative URL | href="/page" | https://example.com/page |
| DC-004-02 | Relative URL with path | href="sub/page" from /dir/ | https://example.com/dir/sub/page |
| DC-004-03 | Protocol-relative URL | href="//other.com" | https://other.com |
| DC-004-04 | Absolute URL | href="https://other.com" | https://other.com |
| DC-004-05 | Root-relative URL | href="/root/page" | https://example.com/root/page |
| DC-004-06 | Parent directory URL | href="../page" from /dir/sub/ | https://example.com/dir/page |
| DC-004-07 | Query string preserved | href="/page?foo=bar" | https://example.com/page?foo=bar |
| DC-004-08 | Fragment included | href="/page#section" | https://example.com/page#section |
| DC-004-09 | Multiple query params | href="/page?a=1&b=2" | https://example.com/page?a=1&b=2 |
| DC-004-10 | URL-encoded params | href="/page?q=%20test%20" | https://example.com/page?q=%20test%20 |

### DC-005: extractLinks() Method - Special URLs
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| DC-005-01 | Skip mailto: links | href="mailto:test@example.com" | Skipped |
| DC-005-02 | Skip javascript: links | href="javascript:void(0)" | Skipped |
| DC-005-03 | Skip tel: links | href="tel:+1234567890" | Skipped |
| DC-005-04 | Skip ftp: links | href="ftp://files.com" | Skipped |
| DC-005-05 | Skip file: links | href="file:///local/path" | Skipped |
| DC-005-06 | Skip data: URLs | href="data:text/html,..." | Skipped |
| DC-005-07 | Skip empty href | href="" | Skipped |
| DC-005-08 | Skip href="#" | href="#" | Skipped |
| DC-005-09 | Skip hash only | href="#section" (same page) | Skipped |
| DC-005-10 | Include hash with path | href="/page#section" | Included (full URL) |

### DC-006: extractLinks() Method - Link Attributes
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| DC-006-01 | Extract link text | `<a href="/page">Link Text</a>` | text: "Link Text" |
| DC-006-02 | Trim link text | `<a href="/page">  Trimmed  </a>` | text: "Trimmed" |
| DC-006-03 | Empty link text | `<a href="/page"></a>` | text: "" |
| DC-006-04 | Link with nested HTML | `<a href="/page"><span>Nested</span></a>` | text: "Nested" |
| DC-006-05 | Extract rel attribute | `<a href="/page" rel="nofollow">` | rel: "nofollow" |
| DC-006-06 | Extract rel noopener | `<a href="/page" rel="noopener">` | rel: "noopener" |
| DC-007-07 | Multiple rel values | `<a href="/page" rel="nofollow noopener">` | rel: "nofollow noopener" |
| DC-006-08 | Extract target attribute | `<a href="/page" target="_blank">` | target: "_blank" |
| DC-006-09 | Detect nofollow link | `<a href="/page" rel="nofollow">` | nofollow: true |
| DC-006-10 | Detect sponsored link | `<a href="/page" rel="sponsored">` | sponsored: true |
| DC-006-11 | Detect ugc link | `<a href="/page" rel="ugc">` | ugc: true |

### DC-007: filterLinks() Method - Basic Filtering
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| DC-007-01 | Filter empty array | [] | [] |
| DC-007-02 | Filter null input | null | [] |
| DC-007-03 | Pass valid links | [valid links] | All passed |
| DC-007-04 | Filter SSRF blocked | [blocked URL] | Empty array |
| DC-007-05 | Filter duplicates | [same URL twice] | Single URL |
| DC-007-06 | Filter already crawled | [crawled URL] | Empty array |
| DC-007-07 | Filter external domains | [external URL], follow_external=false | Empty array |
| DC-007-08 | Allow external domains | [external URL], follow_external=true | URL passed |
| DC-007-09 | Filter null URLs | [null URL] | Empty array |
| DC-007-10 | Filter invalid URLs | [invalid URL] | Empty array |

### DC-008: filterLinks() Method - File Extension Filtering
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| DC-008-01 | Skip PDF files | href="/doc.pdf" | Skipped |
| DC-008-02 | Skip JPG files | href="/image.jpg" | Skipped |
| DC-008-03 | Skip PNG files | href="/image.png" | Skipped |
| DC-008-04 | Skip GIF files | href="/image.gif" | Skipped |
| DC-008-05 | Skip SVG files | href="/image.svg" | Skipped |
| DC-008-06 | Skip WEBP files | href="/image.webp" | Skipped |
| DC-008-07 | Skip MP4 files | href="/video.mp4" | Skipped |
| DC-008-08 | Skip WEBM files | href="/video.webm" | Skipped |
| DC-008-09 | Skip ZIP files | href="/download.zip" | Skipped |
| DC-008-10 | Skip EXE files | href="/installer.exe" | Skipped |
| DC-008-11 | Case-insensitive extension | href="/IMAGE.JPG" | Skipped |
| DC-008-12 | Extension with query | href="/doc.pdf?v=1" | Skipped |
| DC-008-13 | Allow HTML URLs | href="/page.html" | Passed |

### DC-009: filterLinks() Method - Path Filtering
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| DC-009-01 | Skip /api/* paths | href="/api/data" | Skipped |
| DC-009-02 | Skip /admin/* paths | href="/admin/settings" | Skipped |
| DC-009-03 | Skip /login paths | href="/login" | Skipped |
| DC-009-04 | Skip /logout paths | href="/logout" | Skipped |
| DC-009-05 | Skip /register paths | href="/register" | Skipped |
| DC-009-06 | Skip /signin paths | href="/signin" | Skipped |
| DC-009-07 | Skip /signup paths | href="/signup" | Skipped |
| DC-009-08 | Skip ?print=1 URLs | href="/page?print=1" | Skipped |
| DC-009-09 | Skip ?format=pdf URLs | href="/page?format=pdf" | Skipped |
| DC-009-10 | Custom skip paths | Custom config | Applied correctly |

### DC-010: filterLinks() Method - Robots.txt
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| DC-010-01 | Respect robots.txt disallow | Disallowed path | Skipped (if enabled) |
| DC-010-02 | Respect robots.txt allow | Allowed path | Passed |
| DC-010-03 | Skip robots.txt check | disabled in config | All URLs passed |
| DC-010-04 | Handle missing robots.txt | No file | All URLs passed |
| DC-010-05 | Crawl-delay respected | crawl-delay: 10 | Applied correctly |

### DC-011: URL Normalization
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| DC-011-01 | Lowercase host | HTTPS://EXAMPLE.COM/page | https://example.com/page |
| DC-011-02 | Remove default port 80 | http://example.com:80/page | http://example.com/page |
| DC-011-03 | Remove default port 443 | https://example.com:443/page | https://example.com/page |
| DC-011-04 | Keep custom port | https://example.com:8080/page | https://example.com:8080/page |
| DC-011-05 | Remove trailing slash | https://example.com/page/ | https://example.com/page |
| DC-011-06 | Keep trailing slash (config) | keep_trailing_slash=true | Kept as-is |
| DC-011-07 | Remove utm_* params | ?utm_source=google | Query removed |
| DC-011-08 | Remove fbclid param | ?fbclid=abc123 | Query removed |
| DC-011-09 | Remove gclid param | ?gclid=xyz789 | Query removed |
| DC-011-10 | Remove multiple tracking params | ?utm_source=google&fbclid=abc | All tracking removed |
| DC-011-11 | Keep other params | ?page=1&utm_source=x | ?page=1 |
| DC-011-12 | Decode and re-encode | ?q=%20test%20 | ?q=%20test%20 |
| DC-011-13 | Sort query parameters | ?b=2&a=1 | ?a=1&b=2 |
| DC-011-14 | Remove fragment | https://example.com/page#section | https://example.com/page |
| DC-011-15 | Normalize percent encoding | /path%2Fwith%2Fslashes | /path%2Fwith%2Fslashes |
| DC-011-16 | Handle IDN domains | https://例え.jp/page | https://xn--r8jz45g.jp/page |
| DC-011-17 | Handle IPv6 URLs | https://[::1]/page | https://[::1]/page |
| DC-011-18 | Empty query string | https://example.com/page? | https://example.com/page |

### DC-012: queueLinks() Method
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| DC-012-01 | Queue single link | [link], jobId, depth=1 | Link added to queue |
| DC-012-02 | Queue multiple links | [link1, link2], jobId, depth=1 | All added to queue |
| DC-012-03 | Queue empty array | [], jobId, depth=1 | No error, no items added |
| DC-012-04 | Skip if queue full | links when at max_pages | Only first max_pages added |
| DC-012-05 | Store parent URL | link with parent | parent_url stored |
| DC-012-06 | Store depth correctly | depth=2 | depth=2 stored |
| DC-012-07 | Set status pending | New link | status='pending' |
| DC-012-08 | Skip already queued | duplicate link | Skipped |
| DC-012-09 | Skip already crawled | crawled link | Skipped |
| DC-012-10 | Log queued links | Multiple links | Log entry created |

### DC-013: getCrawlQueue() Method
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| DC-013-01 | Get empty queue | jobId with no queue | Empty array |
| DC-013-02 | Get pending URLs | jobId with pending URLs | Array of pending URLs |
| DC-013-03 | Order by depth | URLs at depths 0,1,2 | Ordered depth 0 first |
| DC-013-04 | Filter by status | jobId, status='pending' | Only pending URLs |
| DC-013-05 | Include crawled URLs | jobId, status='done' | Crawled URLs included |
| DC-013-06 | Limit results | jobId, limit=10 | Max 10 results |
| DC-013-07 | Invalid jobId | jobId='invalid' | Empty array |
| DC-013-08 | Non-existent jobId | jobId='crawl-nonexistent' | Empty array |

### DC-014: Depth Management
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| DC-014-01 | L0: Main page only | max_depth=0 | Only seed URL crawled |
| DC-014-02 | L1: One level deep | max_depth=1 | Seed + direct links |
| DC-014-03 | L2: Two levels deep | max_depth=2 | Seed + links + sub-links |
| DC-014-04 | Track depth per URL | Multiple depths | Depth tracked correctly |
| DC-014-05 | Never exceed max_depth | depth=3, max_depth=2 | Blocked |
| DC-014-06 | Depth increments correctly | Parent at depth 1 | Child at depth 2 |

### DC-015: Duplicate Detection
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| DC-015-01 | Detect exact duplicate | Same URL twice | Detected |
| DC-015-02 | Detect normalized duplicate | /page vs /page/ | Detected after normalization |
| DC-015-03 | Detect query param duplicate | /page?a=1&b=2 vs /page?b=2&a=1 | Detected (sorted params) |
| DC-015-04 | Detect fragment duplicate | /page#section vs /page | Detected (fragment removed) |
| DC-015-05 | Handle redirect chains | URL redirects to same final URL | Detected |
| DC-015-06 | Handle canonical URLs | Canonical points to different URL | Use canonical |
| DC-015-07 | Case-insensitive host | EXAMPLE.COM/page vs example.com/page | Detected |

### DC-016: Queue Management
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| DC-016-01 | Priority by depth | URLs at various depths | Depth 0 first, then 1, then 2 |
| DC-016-02 | Status transitions | pending → processing → done | Transitions correct |
| DC-016-03 | Max pages limit | max_pages=50 | Max 50 URLs queued |
| DC-016-04 | Clear queue | Clear command | Queue emptied |
| DC-016-05 | Get queue stats | Stats request | Count by status |
| DC-016-06 | Mark URL processed | URL, status='done' | Status updated |
| DC-016-07 | Mark URL skipped | URL, status='skipped' | Status updated |
| DC-016-08 | Mark URL error | URL, status='error' | Status updated |

### DC-017: Configuration Tests
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| DC-017-01 | Default max_depth | config: default | 2 |
| DC-017-02 | Default max_pages | config: default | 50 |
| DC-017-03 | Default follow_external_links | config: default | false |
| DC-017-04 | Default respect_robots_txt | config: default | false |
| DC-017-05 | Custom max_depth | options: depth=1 | Used |
| DC-017-06 | Custom max_pages | options: max_pages=100 | Used |
| DC-017-07 | Custom skip extensions | config: skip_file_extensions | Applied |
| DC-017-08 | Custom skip paths | config: skip_paths | Applied |

### DC-018: Edge Cases
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| DC-018-01 | Very long URL | URL near 2083 chars | Handled correctly |
| DC-018-02 | Unicode in URL | https://例え.jp/パス | Normalized to punycode |
| DC-018-03 | Malformed HTML | Unclosed tags | Extracted correctly |
| DC-018-04 | Very large HTML | 5MB HTML document | Handled without error |
| DC-018-05 | Empty body | `<html><body></body></html>` | Empty links array |
| DC-018-06 | Special characters in URL | URL with emoji | Handled correctly |
| DC-018-07 | Circular links | A → B → A | No infinite loop |
| DC-018-08 | Self-referencing links | Page links to itself | Skipped |

### DC-019: Security Tests
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| DC-019-01 | XSS in URL | URL with <script> | Sanitized |
| DC-019-02 | SQL injection in URL | URL with SQL | Sanitized |
| DC-019-03 | SSRF localhost | http://localhost | Blocked |
| DC-019-04 | SSRF private IP | http://192.168.1.1 | Blocked |
| DC-019-05 | SSRF cloud metadata | http://169.254.169.254 | Blocked |
| DC-019-06 | SSRF internal domain | http://internal.local | Blocked |
| DC-019-07 | SSRF DNS rebinding | Domain resolves to private IP | Blocked after resolve |

### DC-020: Integration Tests
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| DC-020-01 | Full L0 crawl | max_depth=0 | Only seed URL |
| DC-020-02 | Full L1 crawl | max_depth=1 | Seed + direct links |
| DC-020-03 | Full L2 crawl | max_depth=2 | Seed + links + sub-links |
| DC-020-04 | With external filtering | follow_external=false | Only internal links |
| DC-020-05 | With robots.txt | respect_robots=true | Robots respected |
| DC-020-06 | With max_pages limit | max_pages=10 | Max 10 pages crawled |

---

## Mock Data

### Sample HTML with Various Links
```html
<!DOCTYPE html>
<html>
<head>
    <title>Test Page</title>
</head>
<body>
    <nav>
        <a href="/">Home</a>
        <a href="/about">About</a>
        <a href="/contact">Contact</a>
    </nav>
    <main>
        <a href="/page1">Page 1</a>
        <a href="/page2" rel="nofollow">Page 2 (nofollow)</a>
        <a href="https://external.com" target="_blank">External</a>
        <a href="/doc.pdf">PDF Document</a>
        <a href="/image.jpg">Image Link</a>
        <a href="mailto:test@example.com">Email</a>
        <a href="javascript:void(0)">JS Link</a>
        <a href="#section">Same Page Section</a>
        <a href="/page?utm_source=google">With Tracking</a>
        <a href="//cdn.example.com/resource">Protocol Relative</a>
        <a href="/admin/settings">Admin Page</a>
        <a href="/api/data">API Endpoint</a>
    </main>
</body>
</html>
```

### Expected Filtered Links (internal only, no files)
```php
[
    ['url' => 'https://example.com/', 'text' => 'Home', 'depth' => 1, 'nofollow' => false],
    ['url' => 'https://example.com/about', 'text' => 'About', 'depth' => 1, 'nofollow' => false],
    ['url' => 'https://example.com/contact', 'text' => 'Contact', 'depth' => 1, 'nofollow' => false],
    ['url' => 'https://example.com/page1', 'text' => 'Page 1', 'depth' => 1, 'nofollow' => false],
    ['url' => 'https://example.com/page2', 'text' => 'Page 2 (nofollow)', 'depth' => 1, 'nofollow' => true],
]
```

### Sample Crawl Queue
```php
[
    'job_id' => 'crawl-abc123',
    'urls' => [
        ['url' => 'https://example.com', 'depth' => 0, 'status' => 'done', 'parent' => null],
        ['url' => 'https://example.com/page1', 'depth' => 1, 'status' => 'pending', 'parent' => 'https://example.com'],
        ['url' => 'https://example.com/page2', 'depth' => 1, 'status' => 'pending', 'parent' => 'https://example.com'],
    ]
]
```

### Sample Configuration
```php
[
    'max_depth' => 2,
    'max_pages' => 50,
    'follow_external_links' => false,
    'respect_robots_txt' => false,
    'skip_file_extensions' => ['pdf', 'jpg', 'png', 'gif', 'svg', 'mp4', 'zip'],
    'skip_paths' => ['/api/*', '/admin/*', '/login', '/logout'],
    'normalize_urls' => true,
    'remove_tracking_params' => true,
    'tracking_params' => ['utm_*', 'fbclid', 'gclid', 'msclkid'],
]
```

---

## Coverage Targets

| Category | Target | Description |
|----------|--------|-------------|
| Service Methods | 100% | All public methods tested |
| Branch Coverage | 90% | All condition branches tested |
| Error Paths | 100% | All error scenarios tested |
| Edge Cases | 85% | Boundary conditions tested |

---

## Test Execution

```bash
# Run all DepthControllerService tests
cd backend
php artisan test --filter=DepthControllerServiceTest

# Run URL normalizer tests
php artisan test --filter=UrlNormalizerTest

# Run with coverage
php artisan test --filter=DepthController --coverage

# Run specific test group
php artisan test --filter="DC-003"
```

---

## Dependencies

| Dependency | Status | Notes |
|------------|--------|-------|
| TASK-22-1 (Migrations) | ✅ Done | Tables exist |
| TASK-22-7 (URL Validation) | ⏳ In Progress | Mock UrlValidatorService |

---

## Notes for Developer

1. **Service Location:** `app/Services/Crawler/DepthControllerService.php`
2. **Dependency Injection:** Inject CrawlJob model, Logger, UrlValidatorService via constructor
3. **Configuration:** Use `config('crawler.depth_controller.*')` or direct config keys
4. **URL Normalization:** Consider separate UrlNormalizer class for reusability
5. **Link Filtering:** Consider separate LinkFilter class for complex filtering logic
6. **DomCrawler:** Use Symfony DomCrawler for HTML parsing (Laravel includes it)
7. **Queue Storage:** Use crawl_pages table or in-memory cache for queue

### Recommended Class Structure

```php
// DepthControllerService.php
namespace App\Services\Crawler;

class DepthControllerService
{
    public function shouldCrawl(string $url, int $currentDepth, int $maxDepth): bool;
    public function extractLinks(string $html, string $baseUrl): array;
    public function filterLinks(array $links, array $options): array;
    public function queueLinks(array $links, string $jobId, int $depth): void;
    public function getCrawlQueue(string $jobId): array;
}

// UrlNormalizer.php (optional, for reusability)
namespace App\Services\Crawler;

class UrlNormalizer
{
    public function normalize(string $url, array $options = []): string;
    public function removeTrackingParams(string $url): string;
    public function sortQueryParams(string $url): string;
}

// LinkFilter.php (optional, for complex filtering)
namespace App\Services\Crawler;

class LinkFilter
{
    public function filter(array $links, array $options): array;
    public function shouldSkip(string $url, array $options): bool;
}
```

---

## Comments

### PreTester (2026-03-02)
**Test Design Complete:**
- 100+ test scenarios across 20 categories
- Covers all acceptance criteria from TASK-22-8
- Mocks UrlValidatorService for SSRF protection tests
- Comprehensive URL normalization tests
- Depth management tests (L0/L1/L2)
- Duplicate detection tests
- Queue management tests
- Security tests for SSRF and injection

**Patterns Used:**
- Same patterns as PlaywrightRendererServiceTest and CrawlerServiceTest
- Mockery for mocking dependencies
- PHPUnit assertions
- Grouped by method for clarity

**Handoff:** Ready for Developer to implement DepthControllerService.
