# TASK-22-6: Content Processing and Structured Text Extraction

## STATUS
`To Write Tests`

## PRIORITY
`High`

## RISK
`Low`

## Related Specification
SPEC-022: Headless Crawler Service (JS-Rendered HTML)

## Related Feature
FEAT-022: Headless Crawler Service (JS-Rendered HTML)

---

## Description
Create the ContentProcessorService for advanced HTML-to-text conversion and metadata extraction with structured output suitable for SEO analysis.

## Acceptance Criteria

### Service Structure
- [ ] Service created at `app/Services/Crawler/ContentProcessorService.php`
- [ ] Constructor injection for Logger

### Core Methods
- [ ] `extractStructuredText(string $html): string` - Convert HTML to readable text
- [ ] `extractMetadata(string $html): MetadataResult` - Extract meta tags
- [ ] `calculateSize(string $content): int` - Calculate byte size
- [ ] `processContent(string $html): ProcessedContent` - Full processing pipeline

### Structured Text Extraction
- [ ] Use Symfony DomCrawler (already installed) or native DOMDocument
- [ ] Remove script tags
- [ ] Remove style tags
- [ ] Remove noscript tags
- [ ] Remove hidden elements (aria-hidden, style="display:none")
- [ ] Remove navigation elements (nav, menus)
- [ ] Remove footer content
- [ ] Remove sidebar content
- [ ] Preserve heading hierarchy (prefix with ##, ###, etc.)
- [ ] Preserve list structures (prefix with -, 1., etc.)
- [ ] Preserve link text (with [link text] format)
- [ ] Preserve image alt text (with [alt: text] format)
- [ ] Normalize whitespace
- [ ] Limit output to reasonable size (500KB max)

### Metadata Extraction
- [ ] Extract title (from <title> and og:title)
- [ ] Extract meta description
- [ ] Extract meta robots
- [ ] Extract canonical URL
- [ ] Extract og:type
- [ ] Extract og:image
- [ ] Extract og:url
- [ ] Extract twitter:card
- [ ] Extract twitter:title
- [ ] Extract twitter:description
- [ ] Extract lang attribute
- [ ] Extract charset

### MetadataResult Structure
```php
[
    'title' => string|null,
    'description' => string|null,
    'robots' => string|null,
    'canonical' => string|null,
    'og' => [
        'title' => string|null,
        'description' => string|null,
        'image' => string|null,
        'type' => string|null,
    ],
    'twitter' => [
        'card' => string|null,
        'title' => string|null,
        'description' => string|null,
    ],
    'language' => string|null,
    'charset' => string|null,
]
```

### Content Analysis (Optional for MVP)
- [ ] Detect main content area (article, main, #content)
- [ ] Word count
- [ ] Reading time estimate
- [ ] Language detection (optional)

### Size Calculation
- [ ] Calculate byte size (not character count)
- [ ] Support UTF-8 correctly
- [ ] Return bytes as integer

### Error Handling
- [ ] Handle malformed HTML gracefully
- [ ] Return empty string/null on extraction failure
- [ ] Log warnings for unexpected content
- [ ] Never throw exceptions for content processing

### Performance
- [ ] Process content in memory efficiently
- [ ] Avoid regex for HTML parsing (use DOM)
- [ ] Set memory limit for processing (128MB)

### Tests
- [ ] Unit test for text extraction with complex HTML
- [ ] Unit test for metadata extraction
- [ ] Unit test for size calculation
- [ ] Test with malformed HTML
- [ ] Test with empty content
- [ ] Test with very large content
- [ ] Test with Unicode content

## Dependencies
- None (uses existing Symfony DomCrawler)

## Effort Estimate
`Medium` (3-4 hours)

## Notes
- This service is used after Playwright renders the page
- Can be used independently for static HTML processing
- Consider caching processed content for repeated requests

## Comments
_Leave notes for the next agent here_

### ProjectManager Assessment — 2026-03-16 12:50 GMT+2
- **Priority:** Upgraded Medium → HIGH
- **Risk:** LOW confirmed - Standard Laravel service with no external APIs
- **Dependencies:** None - All Phase 1 tasks (TASK-22-1, 22-2, 22-3) are Done ✅
- **Phase Context:** Phase 2 Rendering Engine - processes rendered HTML from PlaywrightRendererService (TASK-22-5 Done)
- **Downstream Impact:** Essential for crawler functionality - enables API endpoints to return processed content, SEO analysis features
- **Key Insight:** Without this service, the crawler can render pages but cannot extract meaningful SEO data. Foundation service for the entire crawler feature.
- **TDD Ready:** PreTester can design tests immediately with Symfony DomCrawler interface
- **Status:** `To Write Tests` - ready for PreTester
