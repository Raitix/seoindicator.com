# TEST-31-4: UnifiedPromptService Tests

## Task Reference
- **Task:** [TASK-31-4](../TASKS/TASK-31-4.md)
- **Specification:** SPEC-31: Unified AI Prompt + Context Management
- **Agent:** PreTester
- **Status:** To Develop (Tests Complete)

## Overview

Unit tests for `UnifiedPromptService` that builds consolidated AI prompts containing all 17 SEO indicators for single-request analysis.

## Test File

| File | Tests | Assertions | Status |
|------|-------|------------|--------|
| `backend/tests/Unit/Services/AI/UnifiedPromptServiceTest.php` | 115+ | 150+ | TDD |

## Test Categories

### Service Instantiation (5 tests)
| ID | Scenario | Expected |
|----|----------|----------|
| INST-001 | Service can be instantiated | Instance of UnifiedPromptService |
| INST-002 | Service has buildPrompt method | Method exists |
| INST-003 | Service has getPromptTemplate method | Method exists |
| INST-004 | Service has getIndicatorDefinitions method | Method exists |
| INST-005 | Service has getJsonSchema method | Method exists |

### buildPrompt() Method (10 tests)
| ID | Scenario | Input | Expected |
|----|----------|-------|----------|
| BP-001 | Returns string | URL + HTML | String output |
| BP-002 | Includes URL | https://example.com/page | URL in prompt |
| BP-003 | Includes page title | HTML with title | Title in prompt |
| BP-004 | Includes meta description | HTML with meta | Description in prompt |
| BP-005 | Handles missing title | HTML without title | "Unknown" fallback |
| BP-006 | Handles missing meta | HTML without meta | "Not set" fallback |
| BP-007 | Includes HTML content | HTML with content | Content in prompt |
| BP-008 | Accepts options array | URL + HTML + options | String output |
| BP-009 | Escapes HTML content | Script in HTML | Safe output |
| BP-010 | Handles empty HTML | Empty string | Valid prompt |

### Indicator Definitions (18 tests)
| ID | Scenario | Expected |
|----|----------|----------|
| IND-001 | Returns array | Array of indicators |
| IND-002 | Has 17 indicators | Count = 17 |
| IND-003 | Includes url | id = "url" |
| IND-004 | Includes title_tag | id = "title_tag" |
| IND-005 | Includes meta_description | id = "meta_description" |
| IND-006 | Includes robots_txt | id = "robots_txt" |
| IND-007 | Includes sitemap_xml | id = "sitemap_xml" |
| IND-008 | Includes h1_headings | id = "h1_headings" |
| IND-009 | Includes filenames | id = "filenames" |
| IND-010 | Includes image_alt | id = "image_alt" |
| IND-011 | Includes links | id = "links" |
| IND-012 | Includes 404_page | id = "404_page" |
| IND-013 | Includes mobile_friendly | id = "mobile_friendly" |
| IND-014 | Includes language | id = "language" |
| IND-015 | Includes favicon | id = "favicon" |
| IND-016 | Includes canonical | id = "canonical" |
| IND-017 | Includes social_meta | id = "social_meta" |
| IND-018 | Includes aria | id = "aria" |
| IND-019 | Includes page_speed | id = "page_speed" |

### Indicator Structure (5 tests)
| ID | Scenario | Expected |
|----|----------|----------|
| STR-001 | Each indicator has id | id field exists |
| STR-002 | Each indicator has name | name field exists |
| STR-003 | Each indicator has weight | weight field exists |
| STR-004 | Each indicator has category | category field exists |
| STR-005 | Each indicator has checks | checks field exists |

### Indicator Weights (11 tests)
| ID | Scenario | Expected |
|----|----------|----------|
| WGT-001 | Weights sum to 1.00 | Total = 1.00 (±0.001) |
| WGT-002 | title_tag weight = 0.10 | 0.10 |
| WGT-003 | h1_headings weight = 0.10 | 0.10 |
| WGT-004 | url weight = 0.08 | 0.08 |
| WGT-005 | meta_description weight = 0.08 | 0.08 |
| WGT-006 | image_alt weight = 0.08 | 0.08 |
| WGT-007 | links weight = 0.08 | 0.08 |
| WGT-008 | mobile_friendly weight = 0.08 | 0.08 |
| WGT-009 | favicon weight = 0.02 | 0.02 |
| WGT-010 | page_speed weight = 0.02 | 0.02 |
| WGT-011 | All weights in range 0-1 | 0 < weight ≤ 1 |

### Indicator Categories (5 tests)
| ID | Scenario | Expected |
|----|----------|----------|
| CAT-001 | Categories are valid | Content/Technical/Accessibility/Performance |
| CAT-002 | Content category indicators | title_tag, meta_description, h1_headings, filenames, links, social_meta |
| CAT-003 | Technical category indicators | url, robots_txt, sitemap_xml, 404_page, mobile_friendly, language, favicon, canonical |
| CAT-004 | Accessibility category indicators | image_alt, aria |
| CAT-005 | Performance category indicators | page_speed |

### JSON Schema (8 tests)
| ID | Scenario | Expected |
|----|----------|----------|
| SCH-001 | Returns array | Array schema |
| SCH-002 | Has overall_score | Key exists |
| SCH-003 | Has overall_summary | Key exists |
| SCH-004 | Has category_scores | Key exists |
| SCH-005 | Has indicators | Key exists |
| SCH-006 | Has top_priorities | Key exists |
| SCH-007 | Schema in prompt | Keys in prompt |
| SCH-008 | Build prompt includes schema | All schema keys present |

### Prompt Template (10 tests)
| ID | Scenario | Expected |
|----|----------|----------|
| TMP-001 | Returns string | String template |
| TMP-002 | Has {url} placeholder | Placeholder exists |
| TMP-003 | Has {title} placeholder | Placeholder exists |
| TMP-004 | Has {meta_description} placeholder | Placeholder exists |
| TMP-005 | Has {indicators} placeholder | Placeholder exists |
| TMP-006 | Has {html_content} placeholder | Placeholder exists |
| TMP-007 | Contains SEO header | "SEO" in template |
| TMP-008 | Contains JSON instruction | "JSON" in template |

### Page Info Extraction (8 tests)
| ID | Scenario | Input | Expected |
|----|----------|-------|----------|
| EXT-001 | Extracts title | HTML with title | Title in prompt |
| EXT-002 | Extracts meta description | HTML with meta | Description in prompt |
| EXT-003 | Handles empty title | Empty title tag | Valid prompt |
| EXT-004 | Handles missing title | No title tag | Valid prompt |
| EXT-005 | Handles empty meta | Empty description | Valid prompt |
| EXT-006 | Handles multiple meta tags | Multiple meta elements | Correct description |
| EXT-007 | Handles special chars | &amp; etc. | Safe output |
| EXT-008 | Handles Unicode | テスト, Описание | Unicode preserved |

### Edge Cases (10 tests)
| ID | Scenario | Input | Expected |
|----|----------|-------|----------|
| EDG-001 | Empty HTML | "" | Valid prompt |
| EDG-002 | Malformed HTML | Unclosed tags | Valid prompt |
| EDG-003 | Very long HTML | 10K paragraphs | Valid prompt |
| EDG-004 | Very long URL | 500+ char path | Valid prompt |
| EDG-005 | URL with query params | ?param=value | URL in prompt |
| EDG-006 | URL with fragment | #section | URL in prompt |
| EDG-007 | HTML with scripts | <script> tag | Safe output |
| EDG-008 | HTML with styles | <style> tag | Safe output |
| EDG-009 | HTML with comments | <!-- comment --> | Valid prompt |
| EDG-010 | HTML with CDATA | <![CDATA[ ]]> | Valid prompt |

### Security (4 tests)
| ID | Scenario | Expected |
|----|----------|----------|
| SEC-001 | HTML entities preserved | Safe handling |
| SEC-002 | XSS in URL is safe | No execution |
| SEC-003 | XSS in HTML is safe | No execution |
| SEC-004 | No dangerous functions | No eval/exec/system |

### Integration (2 tests)
| ID | Scenario | Expected |
|----|----------|----------|
| INT-001 | Full workflow | All components present |
| INT-002 | Valid for AI | Contains analysis keywords |

### Performance (2 tests)
| ID | Scenario | Expected |
|----|----------|----------|
| PRF-001 | buildPrompt < 500ms | Performance OK |
| PRF-002 | Definitions cached | Same result twice |

### Acceptance Criteria (6 tests)
| ID | Criteria | Status |
|----|----------|--------|
| AC-001 | Prompt contains all 17 indicators | TDD |
| AC-002 | JSON schema included in prompt | TDD |
| AC-003 | Page info extracted correctly | TDD |
| AC-004 | Indicator weights match specification | TDD |
| AC-005 | Prompt template is configurable | TDD |
| AC-006 | HTML content properly handled | TDD |

## 17 SEO Indicators

| ID | Name | Weight | Category |
|----|------|--------|----------|
| url | URL Structure | 0.08 | Technical |
| title_tag | Title Tag | 0.10 | Content |
| meta_description | Meta Description | 0.08 | Content |
| robots_txt | Robots.txt | 0.05 | Technical |
| sitemap_xml | XML Sitemap | 0.05 | Technical |
| h1_headings | H1 Headings | 0.10 | Content |
| filenames | SEO Filenames | 0.04 | Content |
| image_alt | Image Alt Text | 0.08 | Accessibility |
| links | Internal/External Links | 0.08 | Content |
| 404_page | 404 Page | 0.04 | Technical |
| mobile_friendly | Mobile-Friendliness | 0.08 | Technical |
| language | Language Declaration | 0.03 | Technical |
| favicon | Favicon | 0.02 | Technical |
| canonical | Canonical URL | 0.05 | Technical |
| social_meta | Social Meta Tags | 0.05 | Content |
| aria | ARIA Labels | 0.05 | Accessibility |
| page_speed | Page Speed | 0.02 | Performance |

**Total Weight: 1.00**

## Expected Service Interface

```php
class UnifiedPromptService
{
    /**
     * Build complete unified prompt for SEO analysis.
     */
    public function buildPrompt(string $url, string $html, array $options = []): string;
    
    /**
     * Return the prompt template.
     */
    public function getPromptTemplate(): string;
    
    /**
     * Return all 17 indicator definitions with weights.
     */
    public function getIndicatorDefinitions(): array;
    
    /**
     * Return the expected JSON response schema.
     */
    public function getJsonSchema(): array;
}
```

## Expected Indicator Structure

```php
[
    'id' => 'url',
    'name' => 'URL Structure',
    'weight' => 0.08,
    'category' => 'Technical',
    'checks' => 'HTTPS, length, keywords, readability'
]
```

## Expected JSON Schema

```php
[
    'overall_score' => '0-100',
    'overall_summary' => '2-3 sentence executive summary',
    'category_scores' => [
        'content' => '0-100',
        'technical' => '0-100',
        'accessibility' => '0-100',
        'performance' => '0-100'
    ],
    'indicators' => [
        ['id' => '...', 'score' => '1-3', 'passed' => 'bool', ...]
    ],
    'top_priorities' => [
        ['indicator' => '...', 'message' => '...']
    ]
]
```

## Test Execution

```bash
# Run specific test file
cd backend
php artisan test --filter=UnifiedPromptServiceTest

# Run with verbose output
php artisan test --filter=UnifiedPromptServiceTest -v

# Run specific group
php artisan test --filter=UnifiedPromptServiceTest --group=indicators
```

## Dependencies
- None (standalone service, no external dependencies)

## Related Tasks
- TASK-31-2: ContextWindowManager (Done ✅)
- TASK-31-3: TruncationService (Tests Complete)
- TASK-31-7: AIAnalysisService (uses UnifiedPromptService)

## Comments

### [2026-03-05 13:35 GMT+2] PreTester
**Test Design Complete**

Created 115+ tests covering:
- Service instantiation and method existence
- buildPrompt() functionality with URL/HTML/parameters
- All 17 SEO indicators with proper structure
- Indicator weights validation (sum = 1.00)
- Indicator categories validation
- JSON schema structure
- Prompt template placeholders
- Page info extraction (title, meta description)
- Edge cases (malformed HTML, Unicode, long inputs)
- Security (XSS prevention, no dangerous functions)
- Performance requirements

**Key Design Decisions:**
1. Used DOMDocument/DOMCrawler for HTML parsing
2. Weights sum exactly to 1.00 (within 0.001 delta)
3. Categories: Content, Technical, Accessibility, Performance
4. Graceful handling of missing/empty HTML elements
5. Template uses {placeholder} format for str_replace

**TDD Note:** Tests are ready for Developer implementation. Service should pass all tests.
