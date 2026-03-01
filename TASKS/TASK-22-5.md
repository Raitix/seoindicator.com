# TASK-22-5: PlaywrightRendererService Implementation

## STATUS
`To Prioritize`

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
