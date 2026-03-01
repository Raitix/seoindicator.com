# TASK-22-5: PlaywrightRendererService Implementation

## STATUS
`To Write Tests`

## PRIORITY
`High`

## RISK
`High`

## Related Specification
SPEC-022: Headless Crawler Service (JS-Rendered HTML)

## Related Feature
FEAT-022: Headless Crawler Service (JS-Rendered HTML)

---

## Description
Implement the PlaywrightRendererService that renders JavaScript-heavy pages and extracts rendered HTML and content.

## Acceptance Criteria

### Service Structure
- [ ] Service created at `app/Services/Playwright/PlaywrightRendererService.php`
- [ ] Constructor injection for BrowserLauncher and Logger

### Core Methods
- [ ] `render(string $url, array $options = []): RenderResult` - Main render method
- [ ] `extractContent(Page $page): ExtractedContent` - Extract HTML and text
- [ ] `extractLinks(Page $page): array` - Extract all links from page
- [ ] `extractMetadata(Page $page): array` - Extract meta tags, title, etc.
- [ ] `close(): void` - Cleanup browser instances

### RenderResult Data Structure
- [ ] `url` - Final URL after redirects
- [ ] `originalUrl` - Initial URL requested
- [ ] `title` - Page title
- [ ] `renderedHtml` - Full rendered HTML
- [ ] `structuredText` - Extracted readable text
- [ ] `meta` - Metadata object
- [ ] `links` - Array of links
- [ ] `size` - Content size in bytes
- [ ] `loadTime` - Time to render in ms
- [ ] `statusCode` - HTTP status code
- [ ] `error` - Error message if failed

### Render Options
- [ ] `waitForSelector` - CSS selector to wait for
- [ ] `waitTime` - Additional wait in ms after page load
- [ ] `userAgent` - Custom user agent
- [ ] `viewport` - Custom viewport dimensions
- [ ] `headers` - Custom HTTP headers
- [ ] `cookies` - Cookies to set
- [ ] `timeout` - Page timeout override

### Rendering Logic
- [ ] Navigate to URL with configured timeout
- [ ] Wait for page load state 'networkidle'
- [ ] Apply custom wait for selector if specified
- [ ] Apply additional wait time if specified
- [ ] Handle JavaScript errors gracefully
- [ ] Capture console logs (optional, debug mode)

### Content Extraction
- [ ] Extract full HTML after rendering
- [ ] Extract page title
- [ ] Extract meta description
- [ ] Extract meta robots
- [ ] Extract canonical URL
- [ ] Extract og:title, og:description, og:image (optional)
- [ ] Calculate content size

### Structured Text Extraction
- [ ] Remove script and style tags
- [ ] Remove hidden elements (display: none, visibility: hidden)
- [ ] Extract visible text content
- [ ] Preserve heading structure (h1-h6)
- [ ] Preserve link text
- [ ] Consider using Readability.js or similar for article extraction

### Link Extraction
- [ ] Extract all anchor tags with href
- [ ] Identify internal vs external links (based on domain)
- [ ] Extract link text
- [ ] Extract rel attribute
- [ ] Extract target attribute
- [ ] Handle relative URLs (convert to absolute)

### Error Handling
- [ ] TimeoutException - Page took too long to load
- [ ] NavigationException - Navigation failed
- [ ] RenderException - Content extraction failed
- [ ] Log all errors with URL and options context
- [ ] Return partial results if possible

### Performance
- [ ] Track render time
- [ ] Implement request cancellation support
- [ ] Close page context after extraction
- [ ] Memory cleanup after each render

### Tests
- [ ] Unit test for render with static page
- [ ] Unit test for render with JS-rendered page
- [ ] Unit test for content extraction
- [ ] Unit test for link extraction
- [ ] Unit test for metadata extraction
- [ ] Test with timeout scenario
- [ ] Test with navigation error

## Dependencies
- TASK-22-4 (Playwright Integration)

## Effort Estimate
`High` (5-6 hours)

## Notes
- Use 'networkidle' for JS-rendered pages (wait for XHR to complete)
- Consider screenshot capture for debugging (optional)
- Handle SPA routing (hash-based, history API)
- May need to handle cookie consent popups (future enhancement)

## Comments
_Leave notes for the next agent here_

### ProjectManager (2026-03-01 23:50 GMT+2)
**Priority Confirmed: High**
- Core rendering service for SPEC-022 Phase 2 (Rendering Engine)
- Implements Playwright page rendering, content extraction, link/metadata extraction
- Enables TASK-22-8 (Depth Controller) and TASK-22-10 (Link Queue) functionality

**Risk Confirmed: High**
- External browser process management (Playwright)
- Memory management critical (rendered HTML can be large)
- Network timeouts, navigation failures, JavaScript errors
- Resource cleanup (browser instances, page contexts)
- Content size tracking (up to 3MB per job)

**Dependency Status:**
| Dependency | Status | Notes |
|------------|--------|-------|
| TASK-22-4 (Playwright Integration) | To Write Tests ⏳ | BrowserLauncher needed for PlaywrightRendererService |
| TASK-22-3 (CrawlerService Skeleton) | Tests Coverage In Progress ⏳ | Service interface for job management |

**Critical Dependency - BLOCKED for Development:**
- TASK-22-4 (Playwright Integration) is `To Write Tests` - NOT implemented
- PlaywrightRendererService requires:
  - `BrowserLauncher` service from TASK-22-4
  - Browser instance management (launch, getSharedInstance, close)
  - Configuration from `config/playwright.php`

**TDD Approach - Tests Can Proceed:**
- PreTester CAN design tests NOW using mocked interfaces
- Test design: Mock `BrowserLauncher`, `Browser`, `Page` interfaces
- Define `RenderResult` and `ExtractedContent` data structures from acceptance criteria
- Tests will fail until TASK-22-4 reaches `To Test Locally` (expected TDD behavior)

**RenderResult Data Structure (from spec):**
```php
[
    'url' => string,           // Final URL after redirects
    'originalUrl' => string,   // Initial URL requested
    'title' => string,         // Page title
    'renderedHtml' => string,  // Full rendered HTML
    'structuredText' => string,// Extracted readable text
    'meta' => array,           // Metadata object
    'links' => array,          // Array of links
    'size' => int,             // Content size in bytes
    'loadTime' => int,         // Time to render in ms
    'statusCode' => int,       // HTTP status code
    'error' => string|null,    // Error message if failed
]
```

**Key Methods to Test:**
1. `render($url, $options)` - Main render method with configurable options
2. `extractContent(Page $page)` - Extract HTML and structured text
3. `extractLinks(Page $page)` - Extract all links (internal/external)
4. `extractMetadata(Page $page)` - Extract meta tags, title, canonical, OG
5. `close()` - Cleanup browser instances

**Render Options to Test:**
- `waitForSelector` - CSS selector to wait for
- `waitTime` - Additional wait in ms
- `userAgent` - Custom user agent
- `viewport` - Custom viewport dimensions
- `headers` - Custom HTTP headers
- `cookies` - Cookies to set
- `timeout` - Page timeout override

**Error Scenarios to Test:**
- TimeoutException - Page took too long
- NavigationException - Navigation failed
- RenderException - Content extraction failed
- Partial results on error

**Execution Order:**
1. TASK-22-4 must reach `To Test Locally` (BrowserLauncher implemented)
2. Then Developer can implement PlaywrightRendererService
3. This service enables Phase 3 (Crawl Logic) tasks

**Parallel Development Opportunity:**
- TASK-22-6 (ContentProcessorService) has NO dependency on TASK-22-4/22-5
- Can be developed in parallel using static HTML input
- Both tasks feed into TASK-22-8 (Depth Controller)

**Handoff:** Ready for PreTester to design tests with mocked BrowserLauncher/Page interfaces.
