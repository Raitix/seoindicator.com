# TESTS/SPEC-7-4.md — Unit Tests for SERP Feature Detector Service

## Task Reference
- Task: [TASK-7-4](../TASKS/TASK-7-4.md)
- Feature: FEAT-007
- Specification: SPEC-007
- Risk: Medium
- Priority: High

---

## Overview

Comprehensive unit tests for the `SERPFeatureDetector` service class. This service orchestrates detection of all SERP feature types by delegating to pluggable detector implementations. Tests verify detection logic, URL matching, error handling, and mock SERP data support.

---

## Test Files

| File | Purpose |
|------|---------|
| `tests/unit/serp-feature-detector.test.js` | Detector service unit tests |
| `tests/unit/serp-feature-detectors/*.test.js` | Individual detector implementations |

---

## Test Scenarios

### 1. SERPFeatureDetector Class

#### Constructor

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| C-001 | Create with default detectors | No args | Instance with 10 feature detectors |
| C-002 | Create with custom detectors | `[customDetector]` | Instance with provided detectors |
| C-003 | Create with empty detectors | `[]` | Instance with empty detector array |
| C-004 | Detector array is immutable | Modify returned array | Original unchanged |

---

### 2. detectFeatures Method

#### Success Cases

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| D-001 | Returns array of SERPFeature objects | Valid SERP result | Array of features |
| D-002 | Each feature has required fields | Valid SERP result | type, isPresent present |
| D-003 | URL matching for target site | SERP with target URL | isPresent: true for matching |
| D-004 | URL matching for competitor | SERP with other URL | isPresent: false for feature |
| D-005 | Empty SERP result | `{}` | All features with isPresent: false |
| D-006 | Null SERP result handled gracefully | `null` | Returns empty array or throws documented error |
| D-007 | All feature types detected | Full SERP result | 10 feature objects returned |
| D-008 | Detection includes position | local_pack in position 2 | position: 2 in result |

#### URL Matching Logic

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| U-001 | Exact hostname match | target: `example.com`, feature: `example.com` | isPresent: true |
| U-002 | Subdomain match | target: `www.example.com`, feature: `example.com` | isPresent: false (or configurable) |
| U-003 | www prefix handled | target: `example.com`, feature: `www.example.com` | isPresent: true |
| U-004 | Protocol ignored | target: `https://example.com`, feature: `http://example.com` | isPresent: true |
| U-005 | Path comparison | target: `example.com/page`, feature: `example.com/other` | Depends on feature type |
| U-006 | Query params ignored | target: `example.com?ref=1`, feature: `example.com` | isPresent: true |
| U-007 | Trailing slash ignored | target: `example.com/`, feature: `example.com` | isPresent: true |
| U-008 | Case insensitive hostname | target: `Example.COM`, feature: `example.com` | isPresent: true |

#### Error Handling

| ID | Scenario | Input | Expected Behavior |
|----|----------|-------|-------------------|
| E-001 | Malformed SERP data | `{ corrupted: true }` | Returns features with defaults or throws |
| E-002 | Invalid URL in SERP | `{ answerBox: { url: "not-a-url" } }` | Handles gracefully, continues detection |
| E-003 | Missing required fields | `{ answerBox: {} }` | Returns feature with available data |
| E-004 | Circular reference in SERP | Object with circular ref | Does not hang, throws or handles |
| E-005 | Detector throws error | Faulty detector | Logs error, continues with other detectors |

---

### 3. Pluggable Detector Architecture

#### Detector Interface

| ID | Scenario | Expected Behavior |
|----|----------|-------------------|
| P-001 | Detector has detect method | `detector.detect(serpResult, targetUrl)` exists |
| P-002 | Detector returns SERPFeature | Result has type and isPresent |
| P-003 | Detector has featureType property | Returns SERPFeatureType string |
| P-004 | Async detection supported | `detect()` can return Promise |

#### Individual Detectors

| ID | Detector | Description |
|----|----------|-------------|
| P-005 | FeaturedSnippetDetector | Detects answer box at position 0 |
| P-006 | LocalPackDetector | Detects map with 3 local listings |
| P-007 | ImagePackDetector | Detects horizontal image row |
| P-008 | VideoDetector | Detects video thumbnails |
| P-009 | PeopleAlsoAskDetector | Detects expandable Q&A |
| P-010 | KnowledgePanelDetector | Detects entity sidebar |
| P-011 | SitelinksDetector | Detects additional page links |
| P-012 | ReviewsDetector | Detects star ratings |
| P-013 | FAQDetector | Detects FAQ accordion |
| P-014 | ProductDetector | Detects product rich results |

---

### 4. Featured Snippet Detection

#### Detection Logic

| ID | Scenario | SERP Input | Expected Output |
|----|----------|------------|-----------------|
| FS-001 | No answer box | `{}` | `{ type: 'featured_snippet', isPresent: false }` |
| FS-002 | Answer box exists, our URL | `{ answerBox: { url: 'https://ours.com' } }` | `isPresent: true` |
| FS-003 | Answer box exists, competitor URL | `{ answerBox: { url: 'https://theirs.com' } }` | `isPresent: false` |
| FS-004 | Answer box with snippet | `{ answerBox: { snippet: 'Text...' } }` | snippet included in result |
| FS-005 | Answer box with title | `{ answerBox: { title: 'Title' } }` | title included in result |

---

### 5. Local Pack Detection

#### Detection Logic

| ID | Scenario | SERP Input | Expected Output |
|----|----------|------------|-----------------|
| LP-001 | No local pack | `{}` | `{ type: 'local_pack', isPresent: false }` |
| LP-002 | Local pack exists, our URL | `{ localPack: [{ url: 'https://ours.com' }] }` | `isPresent: true, position: 1` |
| LP-003 | Local pack position 2 | `{ localPack: [{}, { url: 'https://ours.com' }] }` | `position: 2` |
| LP-004 | Local pack position 3 | `{ localPack: [{}, {}, { url: 'https://ours.com' }] }` | `position: 3` |
| LP-005 | Local pack exists, not in top 3 | `{ localPack: [{}, {}, {}] }` | `isPresent: false` |
| LP-006 | Empty local pack array | `{ localPack: [] }` | `isPresent: false` |

---

### 6. Sitelinks Detection

#### Detection Logic

| ID | Scenario | SERP Input | Expected Output |
|----|----------|------------|-----------------|
| SL-001 | No sitelinks | `{}` | `{ type: 'sitelinks', isPresent: false }` |
| SL-002 | Sitelinks on our result | `{ sitelinks: ['page1', 'page2'] }` | `isPresent: true` |
| SL-003 | Sitelinks on competitor | SERP with other domain | `isPresent: false` |

---

### 7. Reviews Detection

#### Detection Logic

| ID | Scenario | SERP Input | Expected Output |
|----|----------|------------|-----------------|
| RV-001 | No reviews | `{}` | `{ type: 'reviews', isPresent: false }` |
| RV-002 | Stars on our result | `{ reviews: { rating: 4.5, count: 100 } }` | `isPresent: true` |
| RV-003 | Stars with rating | SERP with rating | Rating included in result |
| RV-004 | Stars with count | SERP with count | Count included in result |

---

### 8. Mock SERP Data Support

#### Mock Data Handling

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| M-001 | Accept mock SERP result | Mock data object | Processed normally |
| M-002 | Mock flag respected | `{ _mock: true, ... }` | Mock data used if provided |
| M-003 | Mock data can force presence | `{ featured_snippet: true }` | Returns isPresent: true |
| M-004 | Mock data can simulate position | `{ local_pack: { position: 2 } }` | Returns correct position |

---

### 9. Logging and Debugging

| ID | Scenario | Expected Behavior |
|----|----------|-------------------|
| L-001 | Detection start logged | Logs keyword and target URL |
| L-002 | Each detector result logged | Debug level logging |
| L-003 | Detection errors logged | Error level with context |
| L-004 | Performance timing logged | Duration of detection |
| L-005 | Verbose mode available | More detailed logging |

---

### 10. Edge Cases and Adversarial Tests

#### Input Validation

| ID | Scenario | Input | Expected Behavior |
|----|----------|-------|-------------------|
| X-001 | Empty keyword string | `keyword: ''` | Returns features (or throws) |
| X-002 | Empty target URL | `targetUrl: ''` | Returns all isPresent: false |
| X-003 | Invalid target URL | `targetUrl: 'not-a-url'` | Handles gracefully |
| X-004 | Very long keyword | 500+ char keyword | Processes without error |
| X-005 | Special chars in keyword | `"test<script>alert(1)</script>"` | Sanitized or handled |

#### SERP Result Edge Cases

| ID | Scenario | Input | Expected Behavior |
|----|----------|-------|-------------------|
| X-006 | Deeply nested SERP data | 10+ levels deep | No stack overflow |
| X-007 | Very large SERP result | 1MB+ JSON | Handles without memory issue |
| X-008 | Unicode in URLs | `https://例え.jp/パス` | Correctly matches |
| X-009 | Punycode URLs | `https://xn--example.com/` | Correctly matches |
| X-010 | Multiple answer boxes | Array of answer boxes | Uses first or documents behavior |

---

## Mock Data

### Valid SERPResult Object (Full)
```javascript
{
  keyword: 'seo tools',
  targetUrl: 'https://example.com',
  answerBox: {
    type: 'organic',
    url: 'https://example.com/seo-tools',
    title: 'Best SEO Tools 2026',
    snippet: 'The best SEO tools include comprehensive keyword tracking...'
  },
  localPack: [
    { name: 'Example SEO', url: 'https://example.com', rating: 4.8 },
    { name: 'Other SEO', url: 'https://other.com', rating: 4.5 },
    { name: 'Third SEO', url: 'https://third.com', rating: 4.2 }
  ],
  images: [
    { url: 'https://example.com/image1.jpg' },
    { url: 'https://other.com/image2.jpg' }
  ],
  videos: [
    { url: 'https://youtube.com/watch?v=abc', title: 'SEO Tutorial' }
  ],
  peopleAlsoAsk: [
    { question: 'What are SEO tools?', answer: '...' },
    { question: 'Best free SEO tools?', answer: '...' }
  ],
  knowledgePanel: {
    title: 'SEO',
    type: 'Topic',
    description: 'Search engine optimization...'
  },
  sitelinks: [
    { title: 'Pricing', url: 'https://example.com/pricing' },
    { title: 'Features', url: 'https://example.com/features' }
  ],
  reviews: {
    rating: 4.7,
    count: 250,
    source: 'Trustpilot'
  },
  faqs: [
    { question: 'How does it work?', answer: '...' }
  ],
  products: [
    { name: 'SEO Pro', price: '$99/mo', url: 'https://example.com/pro' }
  ]
}
```

### Expected Detection Output
```javascript
[
  { type: 'featured_snippet', isPresent: true, url: 'https://example.com/seo-tools', snippet: '...' },
  { type: 'local_pack', isPresent: true, position: 1 },
  { type: 'image_pack', isPresent: true, position: 1 },
  { type: 'video', isPresent: false },
  { type: 'people_also_ask', isPresent: true },
  { type: 'knowledge_panel', isPresent: false },
  { type: 'sitelinks', isPresent: true },
  { type: 'reviews', isPresent: true, rating: 4.7 },
  { type: 'faq', isPresent: true },
  { type: 'product', isPresent: true, url: 'https://example.com/pro' }
]
```

---

## Coverage Targets

| Component | Target Coverage |
|-----------|-----------------|
| SERPFeatureDetector class | 100% |
| detectFeatures method | 100% |
| URL matching logic | 100% |
| Error handling paths | 100% |
| All detector implementations | 90%+ |

---

## Test Execution

```bash
# Run all SERP feature detector tests
npm test -- tests/unit/serp-feature-detector.test.js

# Run with coverage
npm run test:coverage -- --filter=serp-feature-detector

# Run specific detector tests
npm test -- tests/unit/serp-feature-detectors/featured-snippet.test.js
```

---

## Dependencies

- TASK-7-2: SERP Feature Types and Models (must be implemented)
- `backend/src/models/serp-feature.ts` - Type definitions

---

## Notes for Developer

1. **File location:** `backend/src/services/serp-feature-detector.ts`
2. **Pluggable architecture:** Each feature type has a dedicated detector class
3. **Detector interface:**
   ```typescript
   interface FeatureDetector {
     featureType: SERPFeatureType;
     detect(serpResult: SERPResult, targetUrl: string): Promise<SERPFeature>;
   }
   ```
4. **URL matching:** Normalize URLs before comparison (remove protocol, www, trailing slash, query params)
5. **Error handling:** Each detector should handle missing data gracefully
6. **Logging:** Use configurable log level (debug for development, info for production)
7. **Mock support:** Accept `_mock: true` flag to use mock data for testing
8. **Position tracking:** Local pack tracks position 1-3, featured snippet is position 0

---

## Comments

### [2026-02-26 17:45] Agent: PreTester
Created test documentation for TASK-7-4 SERP Feature Detector Service.

Test coverage includes:
- Constructor tests: 4 scenarios
- detectFeatures method tests: 14 scenarios
- URL matching tests: 8 scenarios
- Error handling tests: 5 scenarios
- Pluggable detector tests: 14 scenarios
- Featured snippet detection: 5 scenarios
- Local pack detection: 6 scenarios
- Sitelinks detection: 3 scenarios
- Reviews detection: 4 scenarios
- Mock data support: 4 scenarios
- Logging tests: 5 scenarios
- Edge cases and adversarial tests: 10 scenarios

Total: 82+ test scenarios covering all acceptance criteria.

Key implementation notes:
1. Use dependency injection for detector array
2. URL matching should be case-insensitive for hostname
3. Normalize URLs before comparison (strip protocol, www, trailing slash)
4. Each detector should handle missing data gracefully (return isPresent: false)
5. Support mock SERP data for development/testing
6. Implement logging with configurable levels

Ready for Developer implementation.
