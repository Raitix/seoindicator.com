# TESTS/TASK-31-4.md — UnifiedPromptService Test Documentation

## Task Reference
- **Task:** TASK-31-4
- **Specification:** SPEC-31 (Unified AI Prompt + Context Management)
- **Related Tasks:** TASK-31-13 (Unified AI Request)
- **Agent:** PreTester

---

## Overview

Unit tests for `UnifiedPromptService` - a service that builds consolidated AI prompts containing all 17 SEO indicators in a single request for unified analysis.

**Test File:** `backend/tests/Unit/Services/AI/UnifiedPromptServiceTest.php`

---

## Test Files

| File | Tests | Status |
|------|-------|--------|
| `tests/Unit/Services/AI/UnifiedPromptServiceTest.php` | 106 | Ready for Implementation |

---

## Test Scenarios

### 1. Service Instantiation (5 tests)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| 1.1 | Service can be instantiated | `new UnifiedPromptService()` | Instance created |
| 1.2 | Has buildPrompt method | `method_exists($service, 'buildPrompt')` | `true` |
| 1.3 | Has getPromptTemplate method | `method_exists($service, 'getPromptTemplate')` | `true` |
| 1.4 | Has getIndicatorDefinitions method | `method_exists($service, 'getIndicatorDefinitions')` | `true` |
| 1.5 | Has getJsonSchema method | `method_exists($service, 'getJsonSchema')` | `true` |

### 2. buildPrompt() Method (10 tests)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| 2.1 | Returns string | URL + HTML | Non-empty string |
| 2.2 | Includes URL | `https://example.com/page` | Prompt contains URL |
| 2.3 | Includes page title | `<title>My Test Page</title>` | Prompt contains title |
| 2.4 | Includes meta description | `<meta name="description" content="...">` | Prompt contains description |
| 2.5 | Handles missing title | HTML without `<title>` | Returns "Unknown" |
| 2.6 | Handles missing meta description | HTML without description | Returns "Not set" |
| 2.7 | Includes HTML content | HTML with unique text | Prompt contains text |
| 2.8 | Accepts options array | URL + HTML + `['custom' => 'option']` | Returns string |
| 2.9 | Escapes HTML content | `<script>alert("xss")</script>` | Safe in markdown block |
| 2.10 | Handles all edge cases | Empty/malformed/long HTML | Returns valid string |

### 3. 17 Indicator Definitions (17 tests)

| ID | Indicator | Expected ID |
|----|-----------|-------------|
| 3.1 | URL Structure | `url` |
| 3.2 | Title Tag | `title_tag` |
| 3.3 | Meta Description | `meta_description` |
| 3.4 | robots.txt | `robots_txt` |
| 3.5 | sitemap.xml | `sitemap_xml` |
| 3.6 | H1 Headings | `h1_headings` |
| 3.7 | Filenames | `filenames` |
| 3.8 | Image Alt Text | `image_alt` |
| 3.9 | Links | `links` |
| 3.10 | 404 Page | `404_page` |
| 3.11 | Mobile Friendly | `mobile_friendly` |
| 3.12 | Language | `language` |
| 3.13 | Favicon | `favicon` |
| 3.14 | Canonical | `canonical` |
| 3.15 | Social Meta | `social_meta` |
| 3.16 | ARIA | `aria` |
| 3.17 | Page Speed | `page_speed` |

### 4. Indicator Structure (5 tests)

Each indicator must have:
- `id` - Unique identifier (snake_case)
- `name` - Display name
- `weight` - Scoring weight (0-1)
- `category` - Content/Technical/Accessibility/Performance
- `checks` - Array of validation checks

### 5. Indicator Weights (11 tests)

| Indicator | Weight | Category |
|-----------|--------|----------|
| title_tag | 0.10 | Content |
| h1_headings | 0.10 | Content |
| url | 0.08 | Technical |
| meta_description | 0.08 | Content |
| image_alt | 0.08 | Accessibility |
| links | 0.08 | Content |
| mobile_friendly | 0.08 | Technical |
| favicon | 0.02 | Technical |
| page_speed | 0.02 | Performance |
| **Total** | **1.00** | - |

### 6. Indicator Categories (4 tests)

| Category | Indicators |
|----------|------------|
| Content | title_tag, meta_description, h1_headings, filenames, links, social_meta |
| Technical | url, robots_txt, sitemap_xml, 404_page, mobile_friendly, language, favicon, canonical |
| Accessibility | image_alt, aria |
| Performance | page_speed |

### 7. JSON Schema (6 tests)

The schema defines the expected AI response format:

```php
[
    'overall_score' => 'integer 0-100',
    'overall_summary' => 'string',
    'category_scores' => 'array with Content/Technical/Accessibility/Performance',
    'indicators' => 'array of all 17 indicator results',
    'top_priorities' => 'array of priority items'
]
```

### 8. Prompt Template (8 tests)

Template must contain placeholders:
- `{url}` - Page URL
- `{title}` - Page title
- `{meta_description}` - Meta description
- `{indicators}` - Indicator definitions JSON
- `{html_content}` - HTML content

### 9. Page Info Extraction (8 tests)

| ID | Scenario | Expected Behavior |
|----|----------|-------------------|
| 9.1 | Extract title | Parse `<title>` tag |
| 9.2 | Extract meta description | Parse `<meta name="description">` |
| 9.3 | Empty title | Return "Unknown" |
| 9.4 | Missing title tag | Return "Unknown" |
| 9.5 | Empty meta description | Return "Not set" |
| 9.6 | Multiple meta tags | Extract correct one |
| 9.7 | Special characters | Handle HTML entities |
| 9.8 | Unicode content | Preserve unicode chars |

### 10. Edge Cases (14 tests)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| 10.1 | Empty HTML | `''` | Valid string |
| 10.2 | Malformed HTML | `<div>Unclosed` | Valid string |
| 10.3 | Very long HTML | 10,000+ paragraphs | Valid string |
| 10.4 | Very long URL | 500+ chars | Valid string |
| 10.5 | URL with query params | `?param=value` | URL preserved |
| 10.6 | URL with fragment | `#section` | URL preserved |
| 10.7 | HTML with script tags | `<script>...</script>` | Safe handling |
| 10.8 | HTML with style tags | `<style>...</style>` | Safe handling |
| 10.9 | HTML with comments | `<!-- comment -->` | Valid string |
| 10.10 | HTML with CDATA | `<![CDATA[...]]>` | Valid string |
| 10.11 | XSS payload in URL | `<script>in url` | Safe handling |
| 10.12 | XSS payload in HTML | `<script>in html` | Safe handling |
| 10.13 | HTML entities | `&amp;` | Preserved |
| 10.14 | Unicode | `テスト` | Preserved |

### 11. Security (4 tests)

| ID | Scenario | Expected Behavior |
|----|----------|-------------------|
| 11.1 | HTML entities preserved | Safe context handling |
| 11.2 | XSS in URL safe | No code execution |
| 11.3 | XSS in HTML safe | No code execution |
| 11.4 | No dangerous functions | No `eval()`, `exec()`, `system()`, `passthru()` |

### 12. Integration (2 tests)

| ID | Scenario | Expected Output |
|----|----------|-----------------|
| 12.1 | Full prompt workflow | URL, title, description, schema all included |
| 12.2 | Valid for AI consumption | Contains analysis instructions |

### 13. Performance (2 tests)

| ID | Scenario | Target |
|----|----------|--------|
| 13.1 | buildPrompt speed | < 500ms for 1000 paragraphs |
| 13.2 | Indicator caching | Same result on repeated calls |

### 14. Acceptance Criteria (6 tests)

| ID | Criteria | Expected |
|----|----------|----------|
| 14.1 | Prompt contains all 17 indicators | All indicator names in prompt |
| 14.2 | JSON schema included | All schema keys in prompt |
| 14.3 | Page info extracted | Title and description present |
| 14.4 | Weights sum to 1.00 | Exact match within 0.001 |
| 14.5 | Template configurable | Placeholders present |
| 14.6 | HTML properly handled | Safe, valid output |

---

## Mock Data

### Sample HTML Input

```html
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Page - SEO Analysis</title>
    <meta name="description" content="This is a test page for SEO analysis.">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="https://example.com/test">
</head>
<body>
    <h1>Test Page</h1>
    <p>This is a test paragraph.</p>
    <img src="image.jpg" alt="Test image">
    <a href="/link">Internal Link</a>
</body>
</html>
```

### Expected Indicator Structure

```php
[
    'id' => 'title_tag',
    'name' => 'Title Tag',
    'weight' => 0.10,
    'category' => 'Content',
    'checks' => [
        'length_optimal' => '50-60 characters',
        'keyword_present' => 'Primary keyword in title',
        // ...
    ]
]
```

---

## Coverage Targets

| Area | Target | Current |
|------|--------|---------|
| Service Methods | 100% | TDD |
| Indicator Definitions | 100% | TDD |
| JSON Schema | 100% | TDD |
| Edge Cases | 100% | TDD |
| Security | 100% | TDD |

---

## Test Execution

```bash
# Run all UnifiedPromptService tests
cd backend
php artisan test --filter=UnifiedPromptServiceTest

# Run specific test group
php artisan test --filter=UnifiedPromptServiceTest --group=indicators

# Run with coverage
php artisan test --filter=UnifiedPromptServiceTest --coverage
```

---

## Dependencies

- PHP 8.1+
- PHPUnit 10.x
- Laravel 10.x
- DOMDocument (PHP ext-dom)

---

## Notes for Developer

### Implementation Priority

1. **Core Methods:**
   - `buildPrompt(string $url, string $html, array $options = []): string`
   - `getPromptTemplate(): string`
   - `getIndicatorDefinitions(): array`
   - `getJsonSchema(): array`

2. **Indicator Definitions:**
   - Define all 17 indicators with exact weights
   - Ensure weights sum to exactly 1.00
   - Use correct categories

3. **HTML Parsing:**
   - Use `DOMDocument` for reliable parsing
   - Handle malformed HTML gracefully
   - Preserve unicode and special characters

4. **Security:**
   - No `eval()`, `exec()`, `system()`, `passthru()`
   - XSS payloads should be in safe context (markdown code blocks)
   - Preserve HTML entities

5. **Performance:**
   - Cache indicator definitions
   - Target < 500ms for large HTML

### Test File Location

```
backend/
├── app/Services/AI/
│   └── UnifiedPromptService.php  ← CREATE THIS
└── tests/Unit/Services/AI/
    └── UnifiedPromptServiceTest.php  ← EXISTS (106 tests)
```

---

## Comments

| Date | Agent | Comment |
|------|-------|---------|
| 2026-03-05 | PreTester | Tests already exist - comprehensive coverage with 106 tests. Ready for Developer implementation. |
| 2026-03-05 | PreTester | All tests follow TDD pattern - will pass once service is implemented correctly. |

---

Last updated: 2026-03-05
