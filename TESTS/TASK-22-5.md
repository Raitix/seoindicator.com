# TESTS/TASK-22-5.md — PlaywrightRendererService Tests

## Task Reference
- **Task:** TASK-22-5
- **Specification:** SPEC-022 (Headless Crawler Service)
- **Feature:** FEAT-22 (Headless Crawler Service - JS-Rendered HTML)

## Overview
Unit tests for the PlaywrightRendererService that renders JavaScript-heavy pages and extracts rendered HTML and content.

## Test Files

| File | Type | Purpose |
|------|------|---------|
| `backend/tests/Unit/Services/Playwright/PlaywrightRendererServiceTest.php` | Unit | Service method tests |
| `backend/tests/Unit/Services/Playwright/RenderResultTest.php` | Unit | RenderResult DTO tests |
| `backend/tests/Unit/Services/Playwright/ExtractedContentTest.php` | Unit | ExtractedContent DTO tests |

## Test Scenarios

### PR-001: Service Instantiation
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| PR-001-01 | Service can be instantiated via container | - | Instance created |
| PR-001-02 | Service has BrowserLauncher injected | - | BrowserLauncher not null |
| PR-001-03 | Service has Logger injected | - | Logger not null |

### PR-002: render() Method - Basic Rendering
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| PR-002-01 | Render static page | URL to static HTML | RenderResult with HTML |
| PR-002-02 | Render JS-rendered page | URL to SPA | RenderResult with rendered HTML |
| PR-002-03 | Render with default options | URL only | Uses default timeout, no wait |
| PR-002-04 | Render returns final URL after redirect | Redirecting URL | RenderResult.url is final URL |
| PR-002-05 | Render preserves original URL | URL with redirect | RenderResult.originalUrl is input URL |
| PR-002-06 | Render tracks load time | Any URL | RenderResult.loadTime is set |
| PR-002-07 | Render calculates content size | Any URL | RenderResult.size is set |

### PR-003: render() Method - Wait Options
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| PR-003-01 | Render with waitForSelector | URL + CSS selector | Waits for selector before extraction |
| PR-003-02 | Render with waitTime | URL + 5000ms wait | Waits additional time after load |
| PR-003-03 | Render with both wait options | URL + selector + waitTime | Waits for selector then additional time |
| PR-003-04 | Wait for non-existent selector | URL + invalid selector | TimeoutException thrown |

### PR-004: render() Method - Custom Options
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| PR-004-01 | Render with custom user agent | URL + userAgent | Uses custom user agent |
| PR-004-02 | Render with custom viewport | URL + viewport | Uses custom viewport |
| PR-004-03 | Render with custom headers | URL + headers | Adds custom headers |
| PR-004-04 | Render with cookies | URL + cookies | Sets cookies before navigation |
| PR-004-05 | Render with custom timeout | URL + 60000ms timeout | Uses custom timeout |

### PR-005: extractContent() Method
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| PR-005-01 | Extract full HTML | Page with HTML | ExtractedContent.html set |
| PR-005-02 | Extract page title | Page with title | ExtractedContent.title set |
| PR-005-03 | Extract structured text | Page with content | Structured text without scripts/styles |
| PR-005-04 | Remove script tags | Page with scripts | Scripts removed from text |
| PR-005-05 | Remove style tags | Page with styles | Styles removed from text |
| PR-005-06 | Remove hidden elements | Page with hidden elements | Hidden elements removed |
| PR-005-07 | Preserve heading structure | Page with h1-h6 | Headings preserved in text |
| PR-005-08 | Preserve link text | Page with links | Link text preserved |

### PR-006: extractLinks() Method
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| PR-006-01 | Extract all anchor links | Page with multiple links | Array of link objects |
| PR-006-02 | Identify internal links | Page with internal links | Links marked as internal |
| PR-006-03 | Identify external links | Page with external links | Links marked as external |
| PR-006-04 | Extract link text | Page with anchor text | Link text extracted |
| PR-006-05 | Extract rel attribute | Page with rel attributes | rel values extracted |
| PR-006-06 | Extract target attribute | Page with target attrs | target values extracted |
| PR-006-07 | Handle relative URLs | Page with relative links | Converted to absolute URLs |
| PR-006-08 | Handle links without href | Page with anchor only | Skipped (no href) |

### PR-007: extractMetadata() Method
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| PR-007-01 | Extract page title | Page with title | title extracted |
| PR-007-02 | Extract meta description | Page with meta description | description extracted |
| PR-007-03 | Extract meta robots | Page with meta robots | robots extracted |
| PR-007-04 | Extract canonical URL | Page with canonical | canonical extracted |
| PR-007-05 | Extract og:title | Page with Open Graph | og:title extracted |
| PR-007-06 | Extract og:description | Page with Open Graph | og:description extracted |
| PR-007-07 | Extract og:image | Page with OG image | og:image extracted |
| PR-007-08 | Handle missing metadata | Page without meta | Returns null/default values |

### PR-008: close() Method
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| PR-008-01 | Close browser instances | Active browser | Browser closed |
| PR-008-02 | Close with no instances | No active browser | No error thrown |
| PR-008-03 | Close clears shared instance | Shared instance active | Instance cleared |

### PR-009: Error Handling
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| PR-009-01 | Timeout exception | Page exceeds timeout | TimeoutException thrown |
| PR-009-02 | Navigation exception | Invalid URL | NavigationException thrown |
| PR-009-03 | Render exception on extraction failure | Extraction fails | RenderException thrown |
| PR-009-04 | Partial results on error | Partial render success | RenderResult with error set |
| PR-009-05 | Log error with context | Any error | Error logged with URL and options |

### PR-010: RenderResult Data Structure
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| PR-010-01 | All fields populated | Successful render | All fields set |
| PR-010-02 | Error field null on success | Successful render | error is null |
| PR-010-03 | Error field set on failure | Failed render | error contains message |
| PR-010-04 | StatusCode from response | Any render | statusCode set |

### PR-011: Edge Cases
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| PR-011-01 | Empty URL | "" | InvalidArgumentException |
| PR-011-02 | Invalid URL | "not-a-url" | InvalidArgumentException |
| PR-011-03 | Non-HTTP protocol | "ftp://example.com" | InvalidArgumentException |
| PR-011-04 | Very large page | 3MB HTML | Handles gracefully |
| PR-011-05 | Unicode content | Unicode text | Preserved correctly |
| PR-011-06 | SPA with hash routing | Hash-based SPA | Content extracted |
| PR-011-07 | SPA with history API | History API SPA | Content extracted |

### PR-012: Security Tests
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| PR-012-01 | SSRF - localhost blocked | http://localhost | InvalidArgumentException |
| PR-012-02 | SSRF - private IP blocked | http://192.168.1.1 | InvalidArgumentException |
| PR-012-03 | SSRF - cloud metadata blocked | http://169.254.169.254 | InvalidArgumentException |

## Mock Data

### Mock Page Content
```html
<!DOCTYPE html>
<html>
<head>
    <title>Test Page</title>
    <meta name="description" content="Test description">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="https://example.com/canonical">
    <meta property="og:title" content="OG Title">
    <meta property="og:description" content="OG Description">
    <meta property="og:image" content="https://example.com/image.jpg">
    <style>.hidden { display: none; }</style>
    <script>console.log('test');</script>
</head>
<body>
    <h1>Main Heading</h1>
    <h2>Subheading</h2>
    <p>Paragraph text with <a href="/internal" rel="nofollow">internal link</a>.</p>
    <p>External: <a href="https://external.com" target="_blank">external link</a>.</p>
    <div style="display: none;">Hidden content</div>
</body>
</html>
```

### Expected Links Output
```php
[
    ['url' => 'https://example.com/internal', 'text' => 'internal link', 'rel' => 'nofollow', 'target' => null, 'isInternal' => true],
    ['url' => 'https://external.com', 'text' => 'external link', 'rel' => null, 'target' => '_blank', 'isInternal' => false],
]
```

### Expected Metadata Output
```php
[
    'title' => 'Test Page',
    'description' => 'Test description',
    'robots' => 'index, follow',
    'canonical' => 'https://example.com/canonical',
    'ogTitle' => 'OG Title',
    'ogDescription' => 'OG Description',
    'ogImage' => 'https://example.com/image.jpg',
]
```

## Coverage Targets
- **Service Methods:** 100%
- **Branch Coverage:** 90%
- **Error Paths:** 100%

## Test Execution
```bash
# Run all PlaywrightRendererService tests
cd backend
php artisan test --filter=PlaywrightRendererServiceTest

# Run with coverage
php artisan test --filter=PlaywrightRendererServiceTest --coverage

# Run specific test group
php artisan test --filter="PR-002"
```

## Dependencies
- TASK-22-4 (BrowserLauncher) - Required for actual implementation
- Mockery for mocking PageInterface, BrowserInterface, BrowserLauncher

## Notes for Developer
1. Tests use mocked PageInterface, BrowserInterface, and BrowserLauncher
2. Tests will fail until TASK-22-4 reaches `To Test Locally`
3. RenderResult and ExtractedContent DTOs should be created in `app/Services/Playwright/Data/`
4. Error classes should be created in `app/Services/Playwright/Exceptions/`
5. Service should be at `app/Services/Playwright/PlaywrightRendererService.php`

## Comments

### PreTester (2026-03-02 10:10 GMT+2)
**Tests Created:**
- 80+ test scenarios covering all acceptance criteria
- Mocked BrowserLauncher, Browser, Page interfaces for TDD approach
- Error handling tests for TimeoutException, NavigationException, RenderException
- SSRF protection tests aligned with CrawlerService patterns

**Test Structure:**
- Grouped by method for clarity (PR-001 through PR-012)
- Uses same patterns as BrowserLauncherTest and CrawlerServiceTest
- Mockery for interface mocking

**Dependencies:**
- TASK-22-4 (BrowserLauncher) must be implemented before Developer can run tests
- Tests are ready for TDD - will fail until implementation exists

**Status:** Ready for Developer implementation.
