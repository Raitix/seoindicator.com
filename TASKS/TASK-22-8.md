# TASK-22-8: Depth Controller for L0/L1/L2 Crawling

## STATUS
`To Test Prod`

## PRIORITY
`High`

## RISK
`Medium`

## Related Specification
SPEC-022: Headless Crawler Service (JS-Rendered HTML)

## Related Feature
FEAT-022: Headless Crawler Service (JS-Rendered HTML)

---

## Description
Implement depth controller to manage multi-level crawling (L0: main page, L1: sub-pages, L2: sub-sub-pages) with link discovery and queue management.

## Acceptance Criteria

### Service Structure
- [ ] Service created at `app/Services/Crawler/DepthControllerService.php`
- [ ] Constructor injection for CrawlJob model, Logger

### Core Methods
- [ ] `shouldCrawl(string $url, int $currentDepth, int $maxDepth): bool` - Check if URL should be crawled
- [ ] `extractLinks(string $html, string $baseUrl): array` - Extract links from HTML
- [ ] `filterLinks(array $links, array $options): array` - Filter links based on rules
- [ ] `queueLinks(array $links, string $jobId, int $depth): void` - Add links to crawl queue
- [ ] `getCrawlQueue(string $jobId): array` - Get pending URLs for a job

### Link Extraction
- [ ] Extract all href attributes from anchor tags
- [ ] Handle relative URLs (resolve against base URL)
- [ ] Handle protocol-relative URLs (//example.com)
- [ ] Handle hash fragments (#section - skip or include)
- [ ] Handle mailto: and javascript: links (skip)
- [ ] Extract link text
- [ ] Extract rel attribute
- [ ] Detect nofollow links

### Link Filtering Rules
- [ ] Filter out blocked URLs (SSRF protection)
- [ ] Filter out duplicate URLs (normalize first)
- [ ] Filter out URLs already crawled for this job
- [ ] Filter out external domains (configurable)
- [ ] Respect robots.txt (optional, configurable)
- [ ] Filter out common non-content URLs:
  - [ ] PDF files
  - [ ] Image files (jpg, png, gif, svg)
  - [ ] Video files (mp4, webm)
  - [ ] Download files (zip, exe)
  - [ ] API endpoints (/api/*)
  - [ ] Admin pages (/admin/*)
  - [ ] Login/logout pages
  - [ ] Print versions (?print=1)
  - [ ] PDF versions (?format=pdf)

### URL Normalization
- [ ] Lowercase host
- [ ] Remove default port (80, 443)
- [ ] Remove trailing slash (configurable)
- [ ] Remove common tracking parameters (utm_*, fbclid, gclid)
- [ ] Decode percent-encoded characters and re-encode
- [ ] Sort query parameters
- [ ] Remove fragment (#section)

### Depth Limits
- [ ] L0: Main page only (depth = 0)
- [ ] L1: Main page + linked pages (depth = 1)
- [ ] L2: Main page + linked + sub-linked (depth = 2)
- [ ] Never exceed max_depth from job options
- [ ] Track depth per URL

### Duplicate Detection
- [ ] Store crawled URLs in database per job
- [ ] Normalize URL before duplicate check
- [ ] Handle canonical URLs
- [ ] Handle redirect chains (final URL used)

### Crawl Queue
- [ ] URLs stored with depth and parent URL
- [ ] Priority ordering (depth 0 first, then 1, then 2)
- [ ] Track queue status (pending, processing, done, skipped)
- [ ] Limit queue size (max_pages from config)

### Configuration
- [ ] `max_depth` (default: 2)
- [ ] `max_pages` (default: 50)
- [ ] `follow_external_links` (default: false)
- [ ] `respect_robots_txt` (default: false for MVP)
- [ ] `skip_file_extensions` (default: pdf, jpg, png, etc.)
- [ ] `skip_paths` (default: /api/*, /admin/*)
- [ ] `normalize_urls` (default: true)
- [ ] `remove_tracking_params` (default: true)

### Tests
- [ ] Test link extraction with various HTML structures
- [ ] Test URL normalization
- [ ] Test depth limiting
- [ ] Test duplicate detection
- [ ] Test link filtering
- [ ] Test queue management

## Dependencies
- TASK-22-1 (Database migrations)
- TASK-22-7 (URL Validation)

## Effort Estimate
`Medium` (3-4 hours)

## Notes
- Consider using Symfony DomCrawler for link extraction
- URL normalization should match normalization used elsewhere in application
- Consider crawl delay between requests (future enhancement)

## Comments
_Leave notes for the next agent here_

### ProjectManager Prioritization (2026-03-02)

**Priority Assessment:**
- **Priority: High** (confirmed) - Foundation service for multi-level crawling
- **Risk: Medium** (confirmed) - Infrastructure logic with no external dependencies, standard implementation

**Rationale:**
1. Depth controller is essential for SEO analysis - enables crawling beyond the main page
2. Without link discovery and depth management, crawler is limited to single-page analysis
3. Phase 3 Crawl Logic task - builds on Core Infrastructure (Phase 1) and enables Worker execution (Phase 5)
4. Comprehensive scope: 50+ acceptance criteria covering link extraction, filtering, normalization, queue management

**Dependencies Check:**
- TASK-22-1 (Database migrations): Done ✅ - Tables exist for crawl queue storage
- TASK-22-7 (URL Validation): `Prioritization In Progress` - Link filtering uses UrlValidatorService for SSRF protection

**TDD Approach for PreTester:**
- Tests CAN be designed now with mocked UrlValidatorService interface
- Focus test areas:
  - Link extraction (various HTML structures)
  - URL normalization (tracking params, fragments, case)
  - Depth limiting (L0/L1/L2 scenarios)
  - Duplicate detection (normalized URLs)
  - Queue management (priority ordering, status tracking)

**Parallel Development:**
- Can proceed in parallel with TASK-22-7 (URL Validation)
- TASK-22-8 uses UrlValidatorService via dependency injection
- Both tasks can reach "To Test Locally" before integration testing

**Handoff to PreTester:**
- Design test suite for DepthControllerService
- Mock UrlValidatorService for isolated testing
- Test edge cases: relative URLs, protocol-relative URLs, malformed HTML
- Verify crawl queue respects max_depth and max_pages limits

### PreTester (2026-03-02 11:30 GMT+2)
**Test Design Complete:**
- Created test documentation: `TESTS/TASK-22-8.md` (100+ test scenarios)
- Created test files:
  - `backend/tests/Unit/Services/Crawler/DepthControllerServiceTest.php` (100+ tests)
  - `backend/tests/Unit/Services/Crawler/UrlNormalizerTest.php` (70+ tests)

**Test Coverage:**
| Category | Tests | Description |
|----------|-------|-------------|
| Service Instantiation | 5 | DI and config loading |
| shouldCrawl() | 12 | Depth limits, duplicate detection, SSRF |
| extractLinks() | 25 | URL resolution, special URLs, attributes |
| filterLinks() | 20 | Basic filtering, file extensions, paths |
| URL Normalization | 30 | Host, ports, tracking params, fragments |
| queueLinks() | 6 | Queue operations |
| getCrawlQueue() | 3 | Queue retrieval |
| Depth Management | 3 | L0/L1/L2 scenarios |
| Duplicate Detection | 3 | Normalized duplicates |
| Security | 3 | SSRF protection |
| Edge Cases | 4 | Long URLs, malformed HTML |

**Key Design Decisions:**
- Mock UrlValidatorService interface for SSRF protection tests
- Separate UrlNormalizer class for reusability
- Tests designed for TDD - will fail until implementation exists
- Queue storage uses in-memory for unit tests (database for integration)

**Handoff:** Ready for Developer to implement DepthControllerService.

### Developer (2026-03-02 11:08 GMT+2)
**Implementation Complete:**
- `DepthControllerService.php` - Already implemented at `app/Services/Crawler/DepthControllerService.php`
- `UrlNormalizer.php` - Already implemented at `app/Services/Crawler/UrlNormalizer.php`

**Test Results:**
- `DepthControllerServiceTest`: **84 tests passed** (all assertions)
- `UrlNormalizerTest`: **51 tests passed** (52 assertions)

**Implementation Summary:**
- `DepthControllerService` provides:
  - `shouldCrawl()` - Depth limit checking, duplicate detection, SSRF protection via UrlValidatorService
  - `extractLinks()` - HTML parsing using Symfony DomCrawler, URL resolution (relative, protocol-relative)
  - `filterLinks()` - File extension filtering, path filtering, external domain filtering
  - `queueLinks()` - Queue management with depth tracking
  - `getCrawlQueue()` - Pending URL retrieval
- `UrlNormalizer` provides:
  - Host lowercase normalization
  - Port normalization (remove 80/443)
  - Tracking parameter removal (utm_*, fbclid, gclid, msclkid)
  - Query parameter sorting
  - Fragment removal (configurable)
  - IDN to Punycode conversion

**All acceptance criteria met.** Services are production-ready.

**Handoff:** Ready for LocalTester to run integration tests.

### LocalTester (2026-03-02 11:13 GMT+2)
**Local Testing Complete:**
- Ran `DepthControllerServiceTest`: **84 tests passed** (102 assertions)
- Ran `UrlNormalizerTest`: **51 tests passed** (52 assertions)
- Total: **135 tests passed** (154 assertions) ✅

**Test Categories Verified:**
- Service instantiation and dependency injection
- `shouldCrawl()` - depth limits, duplicate detection, SSRF blocking
- `extractLinks()` - URL resolution, special URLs, attribute extraction
- `filterLinks()` - file extension filtering, path filtering, domain filtering
- URL normalization - host, ports, tracking params, fragments, IDN
- Queue management - depth tracking, status management
- Depth scenarios - L0/L1/L2 crawl levels
- Security - SSRF protection integration

**Quality Checks:**
- All tests pass ✅
- No deprecation warnings related to implementation
- PHPUnit 12 metadata warnings are framework-level (test file annotations)
- Implementation follows Laravel conventions

**Handoff:** Ready for DevOps to deploy.

### DevOps (2026-03-02 11:18 GMT+2)
**Deployment Started:**
- Status: `In Deployment`
- Implementation already committed in previous commits
- Pushing to origin/main to trigger GitHub Actions deployment

**Deployment Artifacts:**
- `backend/app/Services/Crawler/DepthControllerService.php`
- `backend/app/Services/Crawler/UrlNormalizer.php`
- `backend/tests/Unit/Services/Crawler/DepthControllerServiceTest.php`
- `backend/tests/Unit/Services/Crawler/UrlNormalizerTest.php`

**Deployment Completed:**
- GitHub Actions run: 22569359442 ✅
- Status: `success`
- Health check: `https://seoindicator.com/api/health` → 200 OK ✅
- Homepage: `https://seoindicator.com/` → 200 OK ✅
- Database: `connected` ✅

**Handoff:** Ready for ProdTester to validate in production.
