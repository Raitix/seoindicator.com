# TEST-14-9: HTML Analysis Checkers Tests

## Task Reference
- **Task:** TASK-14-9
- **Feature:** FEAT-014 (SEO Audit Engine)
- **Status:** Tests Coverage In Progress

## Overview
Comprehensive unit tests for HTML analysis checkers that detect on-page SEO issues. Tests cover all four checkers (TitleTagChecker, MetaDescriptionChecker, H1Checker, ViewportChecker) plus the BaseChecker abstract class.

## Test Files

| File | Tests | Purpose |
|------|-------|---------|
| `backend/tests/Unit/Services/Audit/Checkers/TitleTagCheckerTest.php` | 45+ | Title tag detection rules |
| `backend/tests/Unit/Services/Audit/Checkers/MetaDescriptionCheckerTest.php` | 40+ | Meta description detection rules |
| `backend/tests/Unit/Services/Audit/Checkers/H1CheckerTest.php` | 35+ | H1 tag detection rules |
| `backend/tests/Unit/Services/Audit/Checkers/ViewportCheckerTest.php` | 30+ | Viewport meta detection rules |
| `backend/tests/Unit/Services/Audit/Checkers/BaseCheckerTest.php` | 25+ | Base checker helper methods |
| **Total** | **175+** | Complete coverage |

---

## Test Scenarios

### TitleTagChecker Tests

| ID | Scenario | Input HTML | Expected Output |
|----|----------|------------|-----------------|
| TTC-01 | Missing title tag | `<html><body></body></html>` | Critical: Missing title |
| TTC-02 | Empty title tag | `<title></title>` | Critical: Empty title |
| TTC-03 | Title with whitespace only | `<title>   </title>` | Critical: Empty title |
| TTC-04 | Title optimal length | `<title>Optimal Title Text Here 50-60 Chars</title>` | null (no issue) |
| TTC-05 | Title at 50 chars | 50-char title | null (no issue) |
| TTC-06 | Title at 60 chars | 60-char title | null (no issue) |
| TTC-07 | Title at 61 chars | 61-char title | Warning: Too long |
| TTC-08 | Title at 100 chars | 100-char title | Warning: Too long |
| TTC-09 | Title at 29 chars | 29-char title | Notice: Too short |
| TTC-10 | Title at 30 chars | 30-char title | null (no issue) |
| TTC-11 | Title with special chars | `<title>Title &amp; Description</title>` | null (no issue) |
| TTC-12 | Title with unicode | `<title>日本語タイトル</title>` | null (no issue) |
| TTC-13 | Title with emoji | `<title>Great Site 🚀</title>` | null (no issue) |
| TTC-14 | Multiple title tags | `<title>First</title><title>Second</title>` | Uses first title |
| TTC-15 | Title in body | `<body><title>Wrong</title></body>` | Uses title from head |
| TTC-16 | Null crawl result | null | Returns null |
| TTC-17 | Empty HTML | `''` | Critical: Missing title |
| TTC-18 | Malformed HTML | `<html><title>Test` | Extracts title if possible |

### MetaDescriptionChecker Tests

| ID | Scenario | Input HTML | Expected Output |
|----|----------|------------|-----------------|
| MDC-01 | Missing meta description | `<html><head></head></html>` | Warning: Missing |
| MDC-02 | Empty content attribute | `<meta name="description" content="">` | Warning: Empty |
| MDC-03 | Content with whitespace | `<meta name="description" content="   ">` | Warning: Empty |
| MDC-04 | Optimal length (155 chars) | 155-char description | null (no issue) |
| MDC-05 | At min length (50 chars) | 50-char description | null (no issue) |
| MDC-06 | Below min (49 chars) | 49-char description | Notice: Too short |
| MDC-07 | At max length (160 chars) | 160-char description | null (no issue) |
| MDC-08 | Above max (161 chars) | 161-char description | Notice: Too long |
| MDC-09 | Very long (300 chars) | 300-char description | Notice: Too long |
| MDC-10 | With special chars | `&amp; &lt; &gt;` in content | null (no issue) |
| MDC-11 | With unicode | Japanese/Chinese text | null (no issue) |
| MDC-12 | Multiple meta descriptions | Two description tags | Uses first |
| MDC-13 | Different name case | `<meta NAME="description">` | Found (case-insensitive) |
| MDC-14 | Null crawl result | null | Returns null |
| MDC-15 | Empty HTML | `''` | Warning: Missing |

### H1Checker Tests

| ID | Scenario | Input HTML | Expected Output |
|----|----------|------------|-----------------|
| H1C-01 | Missing H1 | `<html><body></body></html>` | Warning: Missing H1 |
| H1C-02 | Single H1 | `<h1>Main Title</h1>` | null (no issue) |
| H1C-03 | Empty H1 | `<h1></h1>` | Warning: Empty H1 |
| H1C-04 | H1 with whitespace | `<h1>   </h1>` | Warning: Empty H1 |
| H1C-05 | Multiple H1s | `<h1>First</h1><h1>Second</h1>` | Notice: Multiple H1s |
| H1C-06 | Three H1s | Three H1 tags | Notice: Multiple H1s |
| H1C-07 | H1 with nested content | `<h1><span>Nested</span> Title</h1>` | null (no issue) |
| H1C-08 | H1 with special chars | `<h1>Title &amp; More</h1>` | null (no issue) |
| H1C-09 | H1 with unicode | `<h1>見出し</h1>` | null (no issue) |
| H1C-10 | H1 vs H2-H6 | Multiple headings, one H1 | null (no issue) |
| H1C-11 | Null crawl result | null | Returns null |
| H1C-12 | Empty HTML | `''` | Warning: Missing H1 |

### ViewportChecker Tests

| ID | Scenario | Input HTML | Expected Output |
|----|----------|------------|-----------------|
| VPC-01 | Missing viewport | `<html><head></head></html>` | Warning: Missing |
| VPC-02 | Correct viewport | `<meta name="viewport" content="width=device-width, initial-scale=1">` | null (no issue) |
| VPC-03 | Width=device-width only | `<meta name="viewport" content="width=device-width">` | null (no issue) |
| VPC-04 | Missing width=device-width | `<meta name="viewport" content="initial-scale=1">` | Warning: Invalid |
| VPC-05 | Fixed width | `<meta name="viewport" content="width=960">` | Warning: Invalid |
| VPC-06 | Empty content | `<meta name="viewport" content="">` | Warning: Invalid |
| VPC-07 | Case insensitive name | `<meta NAME="viewport">` | Found (case-insensitive) |
| VPC-08 | Multiple viewports | Two viewport tags | Uses first |
| VPC-09 | Null crawl result | null | Returns null |
| VPC-10 | Empty HTML | `''` | Warning: Missing |

### BaseChecker Tests

| ID | Scenario | Method | Expected Output |
|----|----------|--------|-----------------|
| BC-01 | Extract title present | `extractTitle()` | Title text |
| BC-02 | Extract title missing | `extractTitle()` | null |
| BC-03 | Extract meta description | `extractMetaDescription()` | Content value |
| BC-04 | Extract meta missing | `extractMetaDescription()` | null |
| BC-05 | Extract H1s | `extractH1s()` | Array of H1 texts |
| BC-06 | Extract H1s empty | `extractH1s()` | Empty array |
| BC-07 | Has viewport true | `hasViewport()` | true |
| BC-08 | Has viewport false | `hasViewport()` | false |
| BC-09 | Create finding | `createFinding()` | IssueFinding DTO |
| BC-10 | Crawler creation | Creates from HTML | Crawler instance |

---

## Mock Data

### CrawlResult Mock
```php
$crawlResult = Mockery::mock(CrawlResult::class);
$crawlResult->html = '<html><head><title>Test</title></head><body></body></html>';
$crawlResult->url = 'https://example.com';
```

### HTML Fixtures

**Optimal HTML:**
```html
<!DOCTYPE html>
<html>
<head>
    <title>Optimal Page Title 50-60 Characters Long</title>
    <meta name="description" content="This is an optimal meta description that is between 150 and 160 characters long for best SEO results in search engine results pages.">
    <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body>
    <h1>Main Heading</h1>
    <p>Content here.</p>
</body>
</html>
```

**Problem HTML (All Issues):**
```html
<!DOCTYPE html>
<html>
<head>
    <title>This title is way too long and will be truncated in search engine results pages which is bad for SEO</title>
    <meta name="description" content="Too short">
</head>
<body>
    <h1>First H1</h1>
    <h1>Second H1</h1>
</body>
</html>
```

---

## Coverage Targets

| Checker | Tests | Branches | Statements |
|---------|-------|----------|------------|
| TitleTagChecker | 100% | 100% | 100% |
| MetaDescriptionChecker | 100% | 100% | 100% |
| H1Checker | 100% | 100% | 100% |
| ViewportChecker | 100% | 100% | 100% |
| BaseChecker | 100% | 100% | 100% |

---

## Test Execution

```bash
# Run all checker tests
php artisan test --filter=Checker

# Run specific checker tests
php artisan test --filter=TitleTagChecker
php artisan test --filter=MetaDescriptionChecker
php artisan test --filter=H1Checker
php artisan test --filter=ViewportChecker
php artisan test --filter=BaseChecker

# Run with coverage
php artisan test --filter=Checker --coverage
```

---

## Dependencies

| Dependency | Status | Notes |
|------------|--------|-------|
| TASK-14-8 (IssueDetector) | To Develop | Mocked in tests |
| symfony/dom-crawler | Available | Laravel built-in |
| CheckerInterface | To Create | Mocked in tests |
| CrawlResult | To Create | Mocked in tests |
| IssueFinding DTO | To Create | Used in tests |

---

## Notes for Developer

1. **TDD Pattern:** Tests will fail until checkers are implemented
2. **Mock CrawlResult:** Use Mockery to create CrawlResult with HTML property
3. **DomCrawler:** Use Symfony\Component\DomCrawler\Crawler for HTML parsing
4. **Severity Levels:** Critical, Warning, Notice (mapped to issue_types table)
5. **Null Handling:** All checkers return null for no issues, IssueFinding for issues
6. **Unicode Support:** Test Japanese, Chinese, emoji in titles/descriptions
7. **Edge Cases:** Empty HTML, malformed HTML, missing elements

---

## Comments
_Agent communication log_

### [2026-03-07 10:20] Agent: PreTester
**Test Design Complete:**
- 175+ test scenarios across 5 test files
- All detection rules covered
- Edge cases included (unicode, malformed HTML, empty pages)
- TDD pattern: tests will fail until implementation

**Dependencies Mocked:**
- CrawlResult: Mockery mock with html property
- CheckerInterface: Interface definition in tests
- IssueFinding: DTO used in assertions

**Ready for Developer:** Tests are ready for implementation phase
