# TESTS/SPEC-7-5.md — Unit Tests for Featured Snippet Detection

## Task Reference
- Task: [TASK-7-5](../TASKS/TASK-7-5.md)
- Feature: FEAT-007
- Specification: SPEC-007
- Risk: Low
- Priority: High

---

## Overview

Comprehensive unit tests for the `detectFeaturedSnippet` method in the SERP Feature Detector. Featured snippets are the highest-impact SERP feature with +30-50% CTR improvement. Tests verify answer box detection, URL matching, and edge case handling.

---

## Test Files

| File | Purpose |
|------|---------|
| `tests/unit/serp-feature-detectors/featured-snippet.test.js` | Featured snippet detector unit tests |

---

## Test Scenarios

### 1. Basic Detection Logic

#### No Featured Snippet Present

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| B-001 | No answer box in SERP | `{}` | `{ type: 'featured_snippet', isPresent: false }` |
| B-002 | Undefined answer box | `{ answerBox: undefined }` | `{ type: 'featured_snippet', isPresent: false }` |
| B-003 | Null answer box | `{ answerBox: null }` | `{ type: 'featured_snippet', isPresent: false }` |
| B-004 | Empty answer box object | `{ answerBox: {} }` | `{ type: 'featured_snippet', isPresent: false }` |

#### Featured Snippet Present - Our URL

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| B-005 | Our URL in answer box | `answerBox: { url: 'https://ours.com/page' }`, target: `ours.com` | `isPresent: true` |
| B-006 | Our URL with www prefix | `answerBox: { url: 'https://www.ours.com' }`, target: `ours.com` | `isPresent: true` |
| B-007 | Our URL with path | `answerBox: { url: 'https://ours.com/seo-tools' }`, target: `ours.com` | `isPresent: true` |
| B-008 | Our URL with query params | `answerBox: { url: 'https://ours.com/page?ref=1' }`, target: `ours.com` | `isPresent: true` |
| B-009 | Our URL with trailing slash | `answerBox: { url: 'https://ours.com/' }`, target: `ours.com` | `isPresent: true` |
| B-010 | Target URL with www, answer box without | `answerBox: { url: 'https://ours.com' }`, target: `www.ours.com` | `isPresent: true` |

#### Featured Snippet Present - Competitor URL

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| B-011 | Competitor URL | `answerBox: { url: 'https://theirs.com' }`, target: `ours.com` | `isPresent: false` |
| B-012 | Similar domain (not match) | `answerBox: { url: 'https://ours-compare.com' }`, target: `ours.com` | `isPresent: false` |
| B-013 | Subdomain of competitor | `answerBox: { url: 'https://blog.theirs.com' }`, target: `ours.com` | `isPresent: false` |

---

### 2. URL Matching Logic

#### Hostname Extraction

| ID | Scenario | Input Target URL | Expected Hostname |
|----|----------|------------------|-------------------|
| U-001 | Standard HTTPS | `https://example.com` | `example.com` |
| U-002 | Standard HTTP | `http://example.com` | `example.com` |
| U-003 | With www prefix | `https://www.example.com` | `www.example.com` |
| U-004 | With subdomain | `https://blog.example.com` | `blog.example.com` |
| U-005 | With path | `https://example.com/page` | `example.com` |
| U-006 | With query params | `https://example.com?a=1` | `example.com` |
| U-007 | With fragment | `https://example.com#section` | `example.com` |
| U-008 | With port | `https://example.com:443` | `example.com` |
| U-009 | Trailing slash | `https://example.com/` | `example.com` |

#### Domain Matching

| ID | Scenario | Target | Answer Box URL | Expected |
|----|----------|--------|----------------|----------|
| U-010 | Exact match | `example.com` | `https://example.com` | `true` |
| U-011 | Target with www, answer without | `www.example.com` | `https://example.com` | `true` (normalized) |
| U-012 | Target without www, answer with | `example.com` | `https://www.example.com` | `true` (normalized) |
| U-013 | Case insensitive hostname | `Example.COM` | `https://example.com` | `true` |
| U-014 | Protocol difference | `http://example.com` | `https://example.com` | `true` |
| U-015 | Different paths same domain | `example.com/page1` | `example.com/page2` | `true` (same domain) |
| U-016 | Subdomain matches main domain | `example.com` | `blog.example.com` | `false` (not same) |
| U-017 | Main domain matches subdomain | `blog.example.com` | `example.com` | `false` (not same) |

---

### 3. Answer Box Types

#### Paragraph Snippet

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| A-001 | Paragraph type with all fields | `answerBox: { type: 'organic', url, title, snippet }` | All fields extracted |
| A-002 | Paragraph snippet text | `answerBox: { snippet: 'Long text...' }` | snippet field populated |

#### List Snippet

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| A-003 | List type answer box | `answerBox: { type: 'list', list: ['item1', 'item2'] }` | Detected, isPresent based on URL |
| A-004 | List with URL | `answerBox: { type: 'list', url: 'https://ours.com' }` | `isPresent: true` if ours |

#### Table Snippet

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| A-005 | Table type answer box | `answerBox: { type: 'table', data: [...] }` | Detected, isPresent based on URL |
| A-006 | Table with URL | `answerBox: { type: 'table', url: 'https://ours.com' }` | `isPresent: true` if ours |

#### Unknown Answer Box Type

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| A-007 | Unknown type with URL | `answerBox: { type: 'unknown', url }` | Still detects based on URL |
| A-008 | Missing type field | `answerBox: { url, snippet }` | Detects based on URL |

---

### 4. Return Value Structure

#### Required Fields

| ID | Scenario | Expected Fields |
|----|----------|-----------------|
| R-001 | Always returns type | `{ type: 'featured_snippet', ... }` |
| R-002 | Always returns isPresent | `{ ..., isPresent: boolean }` |

#### Optional Fields (when present in answer box)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| R-003 | URL extracted | `answerBox: { url: 'https://example.com' }` | `url: 'https://example.com'` |
| R-004 | Title extracted | `answerBox: { title: 'Page Title' }` | `title: 'Page Title'` |
| R-005 | Snippet extracted | `answerBox: { snippet: 'Text...' }` | `snippet: 'Text...'` |
| R-006 | All optional fields | Full answer box | All fields included |

#### Optional Fields (when absent)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| R-007 | No URL in answer box | `answerBox: { title: 'Title' }` | url is undefined |
| R-008 | No title in answer box | `answerBox: { url: 'https://...' }` | title is undefined |
| R-009 | No snippet in answer box | `answerBox: { url: 'https://...' }` | snippet is undefined |

---

### 5. Edge Cases and Error Handling

#### Malformed SERP Data

| ID | Scenario | Input | Expected Behavior |
|----|----------|-------|-------------------|
| E-001 | Missing SERP result | `null` | Returns `{ type: 'featured_snippet', isPresent: false }` |
| E-002 | SERP is not object | `"string"` | Returns `{ type: 'featured_snippet', isPresent: false }` |
| E-003 | answerBox is string | `{ answerBox: "invalid" }` | Returns `{ type: 'featured_snippet', isPresent: false }` |
| E-004 | answerBox is number | `{ answerBox: 123 }` | Returns `{ type: 'featured_snippet', isPresent: false }` |
| E-005 | answerBox is array | `{ answerBox: [] }` | Returns `{ type: 'featured_snippet', isPresent: false }` |

#### Malformed URLs

| ID | Scenario | Input | Expected Behavior |
|----|----------|-------|-------------------|
| E-006 | Invalid URL in answer box | `{ answerBox: { url: 'not-a-url' } }` | Gracefully handles, isPresent: false |
| E-007 | Relative URL in answer box | `{ answerBox: { url: '/page' } }` | Cannot extract domain, isPresent: false |
| E-008 | Empty URL string | `{ answerBox: { url: '' } }` | isPresent: false |
| E-009 | Invalid target URL | Valid answer box, target: `not-a-url` | Gracefully handles, isPresent: false |
| E-010 | Empty target URL | Valid answer box, target: `''` | isPresent: false |

#### Unicode and International Domains

| ID | Scenario | Input | Expected Behavior |
|----|----------|-------|-------------------|
| E-011 | Unicode URL | `answerBox: { url: 'https://例え.jp' }` | Correctly parses hostname |
| E-012 | Punycode URL | `answerBox: { url: 'https://xn--r8jz45g.jp' }` | Correctly parses hostname |
| E-013 | Unicode target matches Unicode answer | Target: `例え.jp`, Answer: `例え.jp` | `isPresent: true` |
| E-014 | Unicode snippet text | `{ answerBox: { snippet: '日本語テキスト' } }` | Preserved in output |

#### Very Long Content

| ID | Scenario | Input | Expected Behavior |
|----|----------|-------|-------------------|
| E-015 | Very long snippet | 10000+ char snippet | Stored without truncation |
| E-016 | Very long title | 500+ char title | Stored without truncation |
| E-017 | Very long URL | 2000+ char URL | Stored without truncation |

---

### 6. Integration with SERPFeatureDetector

| ID | Scenario | Expected Behavior |
|----|----------|-------------------|
| I-001 | Called via detectFeatures | Returns SERPFeature object |
| I-002 | Feature type is correct | `type: 'featured_snippet'` |
| I-003 | Compatible with feature array | Can be combined with other features |

---

### 7. Adversarial Tests

#### Security

| ID | Scenario | Input | Expected Behavior |
|----|----------|-------|-------------------|
| S-001 | XSS in snippet | `{ answerBox: { snippet: '<script>alert(1)</script>' } }` | Stored as-is (sanitization at display layer) |
| S-002 | XSS in title | `{ answerBox: { title: '<img src=x onerror=alert(1)>' } }` | Stored as-is |
| S-003 | SQL injection in URL | `{ answerBox: { url: "https://example.com'; DROP TABLE--" } }` | Treated as URL string, no execution |

#### Injection Attempts

| ID | Scenario | Input | Expected Behavior |
|----|----------|-------|-------------------|
| S-004 | Prototype pollution in answerBox | `{ answerBox: { __proto__: { polluted: true } } }` | Ignored, no pollution |
| S-005 | Prototype pollution in SERP | `{ __proto__: { polluted: true } }` | Ignored, no pollution |

---

## Mock Data

### Full SERPResult with Featured Snippet (Our URL)
```javascript
{
  keyword: 'seo tools',
  targetUrl: 'https://example.com',
  answerBox: {
    type: 'organic',
    url: 'https://example.com/seo-tools',
    title: 'Best SEO Tools 2026 - Complete Guide',
    snippet: 'The best SEO tools for 2026 include comprehensive keyword tracking, backlink analysis, and rank monitoring features...'
  }
}
```

### Expected Output (Our URL)
```javascript
{
  type: 'featured_snippet',
  isPresent: true,
  url: 'https://example.com/seo-tools',
  title: 'Best SEO Tools 2026 - Complete Guide',
  snippet: 'The best SEO tools for 2026 include comprehensive keyword tracking, backlink analysis, and rank monitoring features...'
}
```

### Full SERPResult with Featured Snippet (Competitor URL)
```javascript
{
  keyword: 'seo tools',
  targetUrl: 'https://example.com',
  answerBox: {
    type: 'organic',
    url: 'https://competitor.com/seo-tools',
    title: 'Top 10 SEO Tools',
    snippet: 'Compare the best SEO tools on the market...'
  }
}
```

### Expected Output (Competitor URL)
```javascript
{
  type: 'featured_snippet',
  isPresent: false,
  url: 'https://competitor.com/seo-tools',
  title: 'Top 10 SEO Tools',
  snippet: 'Compare the best SEO tools on the market...'
}
```

### SERPResult Without Featured Snippet
```javascript
{
  keyword: 'seo tools',
  targetUrl: 'https://example.com',
  // No answerBox property
  results: [
    { position: 1, url: 'https://competitor.com', title: '...' },
    { position: 2, url: 'https://example.com', title: '...' }
  ]
}
```

### Expected Output (No Featured Snippet)
```javascript
{
  type: 'featured_snippet',
  isPresent: false
}
```

---

## Coverage Targets

| Component | Target Coverage |
|-----------|-----------------|
| detectFeaturedSnippet method | 100% |
| URL hostname extraction | 100% |
| Domain matching logic | 100% |
| Error handling paths | 100% |
| All answer box types | 100% |

---

## Test Execution

```bash
# Run featured snippet detector tests
npm test -- tests/unit/serp-feature-detectors/featured-snippet.test.js

# Run with coverage
npm run test:coverage -- --filter=featured-snippet

# Run all SERP feature tests
npm test -- tests/unit/serp-feature-detectors/
```

---

## Dependencies

- TASK-7-2: SERP Feature Types and Models (must be implemented)
- TASK-7-4: SERP Feature Detector Service (parent detector class)
- `backend/src/models/serp-feature.ts` - Type definitions
- `backend/src/services/serp-feature-detector.ts` - Detector class

---

## Notes for Developer

### Method Signature
```typescript
private detectFeaturedSnippet(serp: SERPResult, targetUrl: string): SERPFeature {
  const snippet = serp.answerBox;
  if (!snippet) {
    return { type: 'featured_snippet', isPresent: false };
  }
  
  const targetDomain = new URL(targetUrl).hostname;
  const isOurs = snippet.url?.includes(targetDomain);
  
  return {
    type: 'featured_snippet',
    isPresent: isOurs,
    url: snippet.url,
    title: snippet.title,
    snippet: snippet.snippet
  };
}
```

### Implementation Notes

1. **URL Matching:**
   - Use `new URL()` for parsing (handles edge cases)
   - Compare hostnames only (not full URL)
   - Normalize www prefix (both `www.example.com` and `example.com` should match)
   - Case-insensitive hostname comparison

2. **Null Safety:**
   - Check if `serp.answerBox` exists before accessing properties
   - Check if `snippet.url` exists before URL parsing
   - Handle malformed URLs gracefully (try-catch around URL constructor)

3. **Return Value:**
   - Always return `type: 'featured_snippet'`
   - Always return `isPresent: boolean`
   - Include optional fields only if present in answer box

4. **Error Handling:**
   - Invalid URLs should not throw - return `isPresent: false`
   - Missing data should not throw - return with defaults

5. **Integration:**
   - This method is called from `SERPFeatureDetector.detectFeatures()`
   - Should not have side effects
   - Should be deterministic (same input = same output)

---

## Comments

### [2026-02-26 17:55] Agent: PreTester
Created test documentation for TASK-7-5 Featured Snippet Detection.

Test coverage includes:
- Basic detection logic: 13 scenarios
- URL matching logic: 17 scenarios
- Answer box types: 8 scenarios
- Return value structure: 9 scenarios
- Edge cases and error handling: 17 scenarios
- Integration tests: 3 scenarios
- Adversarial tests: 5 scenarios

Total: 72 test scenarios covering all acceptance criteria.

Key implementation notes:
1. Use URL constructor for hostname extraction (handles edge cases)
2. Normalize www prefix for domain matching
3. Case-insensitive hostname comparison
4. Graceful error handling - never throw on malformed input
5. Optional fields only included when present
6. Type and isPresent are always returned

Ready for Developer implementation.
