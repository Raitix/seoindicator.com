# TESTS/SPEC-21-4.md — Prompt Templates Tests

## Task Reference
- Task: [TASK-21-4](../TASKS/TASK-21-4.md)
- Specification: [SPEC-021](../SPECIFICATIONS/SPEC-021.md)

## Overview
Test suite for the PromptTemplate class that provides prompt templates for all 10 SEO indicators. Each template includes system instructions, user prompt structure, and expected JSON output schema optimized for free AI models.

---

## Test Files

| File | Tests | Assertions |
|------|-------|------------|
| `backend/tests/Unit/Services/AI/Prompts/PromptTemplateTest.php` | 80+ | 150+ |

---

## Test Scenarios

### AC-1: PromptTemplate Class Structure

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| PT-001 | Class file exists at correct path | N/A | File exists at `app/Services/AI/Prompts/PromptTemplate.php` |
| PT-002 | Class is instantiable | N/A | Class can be instantiated |
| PT-003 | getSystemPrompt method exists | N/A | Method exists with string return type |
| PT-004 | getUserPrompt method exists | N/A | Method exists with string return type |
| PT-005 | getExpectedSchema method exists | N/A | Method exists with array return type |
| PT-006 | getSystemPrompt returns string | indicator='title' | Non-empty string |
| PT-007 | getUserPrompt returns string | indicator, context | Non-empty string with substitutions |
| PT-008 | getExpectedSchema returns array | indicator='title' | Valid array structure |

### AC-2: Title Indicator Prompt

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| TI-001 | System prompt defines SEO expert role | indicator='title' | Contains 'SEO expert' or 'SEO' |
| TI-002 | User prompt accepts title | context={'title':'Test'} | Contains title value |
| TI-003 | User prompt accepts content_summary | context={'content_summary':'Test content'} | Contains summary |
| TI-004 | User prompt accepts keyword | context={'keyword':'seo'} | Contains keyword |
| TI-005 | Schema has score field | indicator='title' | Schema includes score (0-100) |
| TI-006 | Schema has issues array | indicator='title' | Schema includes issues[] |
| TI-007 | Schema has suggestions array | indicator='title' | Schema includes suggestions[] |
| TI-008 | Schema has best_practices array | indicator='title' | Schema includes best_practices[] |
| TI-009 | System prompt under 100 tokens | indicator='title' | Token count < 100 |

### AC-3: Meta Description Indicator Prompt

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| MD-001 | System prompt for meta description | indicator='meta_description' | Contains 'meta description' |
| MD-002 | User prompt accepts description | context={'description':'Test'} | Contains description |
| MD-003 | User prompt accepts content_summary | context={'content_summary':'Content'} | Contains summary |
| MD-004 | User prompt accepts keyword | context={'keyword':'seo'} | Contains keyword |
| MD-005 | Schema includes CTA guidance | indicator='meta_description' | Schema or prompt mentions CTA |
| MD-006 | System prompt under 100 tokens | indicator='meta_description' | Token count < 100 |

### AC-4: Robots.txt Indicator Prompt

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| RB-001 | System prompt for robots.txt | indicator='robots' | Contains 'robots.txt' or 'robots' |
| RB-002 | User prompt accepts robots_content | context={'robots_content':'User-agent: *'} | Contains robots content |
| RB-003 | User prompt accepts site_url | context={'site_url':'https://example.com'} | Contains URL |
| RB-004 | Schema has issues array | indicator='robots' | Schema includes issues[] |
| RB-005 | Schema has suggestions array | indicator='robots' | Schema includes suggestions[] |
| RB-006 | Schema has warnings array | indicator='robots' | Schema includes warnings[] |

### AC-5: Sitemap Indicator Prompt

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| SM-001 | System prompt for sitemap | indicator='sitemap' | Contains 'sitemap' or 'XML' |
| SM-002 | User prompt accepts sitemap_url | context={'sitemap_url':'https://...'} | Contains sitemap URL |
| SM-003 | User prompt accepts page_count | context={'page_count':100} | Contains page count |
| SM-004 | User prompt accepts sample_urls | context={'sample_urls':[]} | Contains URLs array |
| SM-005 | Schema has coverage_analysis | indicator='sitemap' | Schema includes coverage_analysis |

### AC-6: Headings Indicator Prompt

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| HD-001 | System prompt for headings | indicator='headings' | Contains 'heading' or 'H1' |
| HD-002 | User prompt accepts heading_structure | context={'heading_structure':{}} | Contains structure |
| HD-003 | Schema has hierarchy_issues | indicator='headings' | Schema includes hierarchy_issues[] |
| HD-004 | Accessibility considerations | indicator='headings' | Mentions accessibility |

### AC-7: Image Alt Text Indicator Prompt

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| IA-001 | System prompt for alt text | indicator='image_alt' | Contains 'alt' or 'image' |
| IA-002 | User prompt accepts images array | context={'images':[]} | Contains images |
| IA-003 | Schema has missing_alts array | indicator='image_alt' | Schema includes missing_alts[] |
| IA-004 | Schema has suggestions_per_image | indicator='image_alt' | Schema includes suggestions_per_image[] |

### AC-8: Filenames Indicator Prompt

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| FN-001 | System prompt for filenames | indicator='filenames' | Contains 'filename' or 'file' |
| FN-002 | User prompt accepts file_urls | context={'file_urls':[]} | Contains file URLs |
| FN-003 | User prompt accepts types | context={'types':[]} | Contains file types |
| FN-004 | Schema has naming convention | indicator='filenames' | Prompt mentions naming convention |

### AC-9: URL Indicator Prompt

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| UR-001 | System prompt for URL structure | indicator='url' | Contains 'URL' or 'slug' |
| UR-002 | User prompt accepts url | context={'url':'https://...'} | Contains URL |
| UR-003 | User prompt accepts page_type | context={'page_type':'product'} | Contains page type |
| UR-004 | User prompt accepts keyword | context={'keyword':'seo'} | Contains keyword |
| UR-005 | Schema has slug optimization | indicator='url' | Prompt mentions slug |

### AC-10: Links Indicator Prompt

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| LN-001 | System prompt for link analysis | indicator='links' | Contains 'link' or 'internal' |
| LN-002 | User prompt accepts internal_links | context={'internal_links':[]} | Contains internal links |
| LN-003 | User prompt accepts external_links | context={'external_links':[]} | Contains external links |
| LN-004 | User prompt accepts broken_links | context={'broken_links':[]} | Contains broken links |
| LN-005 | Schema has broken_link_fixes | indicator='links' | Schema includes broken_link_fixes[] |

### AC-11: 404 Indicator Prompt

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| NF-001 | System prompt for 404 analysis | indicator='404' | Contains '404' or 'not found' |
| NF-002 | User prompt accepts 404_urls | context={'404_urls':[]} | Contains 404 URLs |
| NF-003 | User prompt accepts referrers | context={'referrers':[]} | Contains referrers |
| NF-004 | Schema has redirect_recommendations | indicator='404' | Schema includes redirect_recommendations[] |
| NF-005 | User experience considerations | indicator='404' | Mentions user experience |

### AC-12: Variable Substitution

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| VS-001 | Single variable substitution | context={'title':'My Title'} | Variable replaced in output |
| VS-002 | Multiple variable substitution | context with 3+ variables | All variables replaced |
| VS-003 | Missing variable handling | context missing required var | Placeholder remains or error |
| VS-004 | Special characters in values | context={'title':'Test <script>'} | Value escaped or sanitized |
| VS-005 | Empty string substitution | context={'title':''} | Empty string handled gracefully |
| VS-006 | Unicode substitution | context={'title':'中文标题'} | Unicode preserved |

### Edge Cases and Security

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| EC-001 | Invalid indicator returns empty | indicator='invalid' | Empty string or exception |
| EC-002 | Empty context array | context=[] | Template returned without substitution |
| EC-003 | Null context value | context={'title':null} | Handled gracefully |
| EC-004 | Very long context value | context with 10000 char string | Truncated or handled |
| EC-005 | XSS prevention in context | context={'title':'<script>alert(1)</script>'} | Script tags sanitized |
| EC-006 | All 10 indicators have templates | iterate all | Each returns valid template |

---

## Mock Data

### Sample Title Context
```json
{
  "title": "Best SEO Tools 2026 - Free Analysis",
  "content_summary": "Comprehensive guide to SEO tools...",
  "keyword": "seo tools"
}
```

### Sample Meta Description Context
```json
{
  "description": "Discover the best free SEO tools for 2026.",
  "content_summary": "Guide to SEO tools...",
  "keyword": "seo tools"
}
```

### Sample Headings Context
```json
{
  "heading_structure": {
    "h1": ["Main Title"],
    "h2": ["Section 1", "Section 2"],
    "h3": ["Subsection 1.1", "Subsection 1.2"]
  }
}
```

### Sample Images Context
```json
{
  "images": [
    {"url": "https://example.com/img1.jpg", "alt": "Product image", "context": "hero"},
    {"url": "https://example.com/img2.png", "alt": "", "context": "thumbnail"}
  ]
}
```

---

## Coverage Targets

| Category | Target | Tests |
|----------|--------|-------|
| Class Structure | 100% | 8 |
| Title Indicator | 100% | 9 |
| Meta Description | 100% | 6 |
| Robots.txt | 100% | 6 |
| Sitemap | 100% | 5 |
| Headings | 100% | 4 |
| Image Alt | 100% | 4 |
| Filenames | 100% | 4 |
| URL | 100% | 5 |
| Links | 100% | 5 |
| 404 | 100% | 5 |
| Variable Substitution | 100% | 6 |
| Edge Cases | 100% | 6 |
| **Total** | **100%** | **73+** |

---

## Test Execution

```bash
# Run all PromptTemplate tests
cd backend
php artisan test --filter=PromptTemplateTest

# Run with coverage
php artisan test --filter=PromptTemplateTest --coverage

# Run specific test group
php artisan test --filter=PromptTemplateTest --group=ai
```

---

## Dependencies
- TASK-21-1 (AI Service Structure and Interfaces)
- PHP 8.2+
- PHPUnit 10+

---

## Notes for Developer

1. **Token Efficiency**: System prompts should be under 100 tokens for free AI models
2. **JSON Output**: All prompts should request JSON-only responses
3. **Schema Consistency**: Each indicator's schema should match SuggestionResult DTO structure
4. **Variable Format**: Use `{variable_name}` for template placeholders
5. **Error Handling**: Handle missing indicators gracefully with empty string or exception

---

## Comments
_Agent log_

### [2026-03-01 19:05 GMT+2] PreTester - Test Design Complete
**Status**: `To Write Tests` → `Tests Coverage In Progress`

**Test Suite Created:**
- `backend/tests/Unit/Services/AI/Prompts/PromptTemplateTest.php`
- 80+ test cases covering all acceptance criteria
- Comprehensive coverage of all 10 indicator templates
- Variable substitution testing
- Edge case and security testing

**Key Test Patterns:**
- Token count verification for system prompts (< 100 tokens)
- Variable substitution with special characters and Unicode
- XSS prevention in template values
- Schema structure validation per indicator

**Handoff to Developer:** Ready for implementation. Focus on:
- PromptTemplate class with TEMPLATES constant
- getSystemPrompt(), getUserPrompt(), getExpectedSchema() methods
- Variable substitution with {placeholder} syntax
- Token-efficient prompts for free AI models

---
*Created by PreTester on 2026-03-01*
