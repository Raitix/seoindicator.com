# TASK-22-8: Depth Controller for L0/L1/L2 Crawling

## STATUS
`To Prioritize`

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
