# TASK-22-6: Content Processing and Structured Text Extraction

## STATUS
`In Development`

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

### ProjectManager (2026-03-02 18:30 GMT+2)
**Prioritization Complete**

**Priority Confirmed: High**
- Foundation service for content processing in SPEC-022
- Used after Playwright renders pages to extract structured text and metadata
- Independent of TASK-22-4/22-5 - can process static HTML without browser

**Risk Confirmed: Low**
- Pure PHP processing, no external APIs
- Uses existing Symfony DomCrawler (already in Laravel dependencies)
- Standard DOM manipulation, no network calls
- Graceful error handling (never throw exceptions for content processing)

**Dependency Status:**
| Dependency | Status | Notes |
|------------|--------|-------|
| Phase 1 (TASK-22-1 to 22-3) | All Done ✅ | Infrastructure complete |
| TASK-22-4 (Playwright) | Done ✅ | Not required - can process static HTML |
| TASK-22-5 (RendererService) | Done ✅ | Not required - parallel service |
| Symfony DomCrawler | Available ✅ | Already installed via Laravel |

**Parallel Development:**
- This service operates independently of Playwright/browser tasks
- Can be used standalone for static HTML processing
- No blocking dependencies - ready for PreTester immediately

**Key Acceptance Criteria Summary:**
1. `extractStructuredText()` - HTML to readable text with heading/list/link preservation
2. `extractMetadata()` - Meta tags, OG, Twitter cards, canonical, robots
3. `calculateSize()` - Byte size calculation (UTF-8 aware)
4. `processContent()` - Full pipeline returning ProcessedContent DTO

**Security Considerations:**
- Handle malformed HTML gracefully (no exceptions)
- Limit output to 500KB max
- Memory limit: 128MB for processing
- Never execute embedded scripts

**Handoff to PreTester:**
- Test with complex HTML (nested elements, Unicode, large content)
- Test metadata extraction (all OG/Twitter tags)
- Test malformed HTML handling
- Test size calculation (bytes vs characters)
- Test empty/edge cases

### PreTester (2026-03-02 18:38 GMT+2)
**Test Design Complete**

**Files Created:**
- `backend/tests/Unit/Services/Crawler/ContentProcessorServiceTest.php` (107 tests)
- `TESTS/TASK-22-6.md` (test documentation)

**Test Categories:**
| Category | Tests | Description |
|----------|-------|-------------|
| Service Instantiation | 2 | Container binding, logger injection |
| extractStructuredText() Basic | 4 | Return types, null/empty handling |
| Script/Style Removal | 4 | script, style, noscript, multiple scripts |
| Hidden Element Removal | 5 | aria-hidden, display:none, nav, footer, aside |
| Heading Hierarchy | 7 | H1-H6 with markdown prefixes (#, ##, ###) |
| List Structures | 3 | Unordered, ordered, nested lists |
| Links and Images | 5 | Link text, image alt, empty alt |
| Whitespace Normalization | 3 | Multiple spaces, trimming, paragraphs |
| Size Limits | 2 | 500KB limit, large HTML |
| extractMetadata() Basic | 3 | Return type, null/empty handling |
| Title Extraction | 3 | title tag, og:title, both |
| Meta Description | 3 | description, og:description, missing |
| Robots Meta | 2 | robots directive, missing |
| Canonical URL | 2 | canonical link, missing |
| Open Graph Tags | 4 | og:type, og:image, og:url, all tags |
| Twitter Cards | 4 | twitter:card, title, description, all |
| Language and Charset | 4 | lang attribute, charset meta, Content-Type |
| calculateSize() | 8 | Bytes vs chars, UTF-8, emoji, null, empty |
| processContent() Pipeline | 6 | Full output, text, metadata, size, null |
| Error Handling | 3 | Malformed HTML, never throws exceptions |
| Unicode/Special Chars | 7 | Japanese, Spanish, Russian, entities, emoji |
| Security | 3 | No JS execution, SVG sanitized, no API keys |
| Performance | 2 | Memory efficient, fast processing |
| Integration | 1 | Full realistic HTML page |
| **Total** | **107** | |

**Key Test Patterns:**
- Never throws exceptions - always returns valid output
- Empty/null inputs return empty string/null values
- HTML entities decoded to actual characters
- UTF-8 multibyte characters handled in size calculation
- Hidden elements removed from text extraction
- Heading hierarchy preserved with markdown prefixes

**Ready for Developer implementation.**
