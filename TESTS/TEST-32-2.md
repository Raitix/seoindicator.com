# TEST-32-2: Comparison Registry and Data Loading Tests

## Task Reference
- **Task:** [TASK-32-2](../TASKS/TASK-32-2.md)
- **Spec:** SPEC-32 (Competitor Comparison Landing Pages)
- **Status:** Tests Coverage In Progress

## Overview
Tests for the comparison registry system that manages competitor data and generates comparison data structures. This module provides helper functions for loading, validating, and transforming competitor data.

---

## Test Files

| File | Tests | Purpose |
|------|-------|---------|
| `frontend/__tests__/lib/compare/registry.test.ts` | 120+ | Registry structure and helper functions |
| `frontend/__tests__/lib/compare/competitors/seoindicator.test.ts` | 50+ | SEOIndicator data validation |

---

## Test Scenarios

### RG-STR: Registry Structure Tests (15 tests)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| RG-STR-001 | COMPETITOR_REGISTRY is defined | - | Object exists, not null |
| RG-STR-002 | COMPETITOR_REGISTRY contains seoindicator | - | Has 'seoindicator' key |
| RG-STR-003 | COMPETITOR_REGISTRY contains ahrefs | - | Has 'ahrefs' key |
| RG-STR-004 | COMPETITOR_REGISTRY contains semrush | - | Has 'semrush' key |
| RG-STR-005 | COMPETITOR_REGISTRY contains moz | - | Has 'moz' key |
| RG-STR-006 | COMPETITOR_REGISTRY contains ubersuggest | - | Has 'ubersuggest' key |
| RG-STR-007 | COMPETITOR_REGISTRY values are CompetitorData | - | All values pass isCompetitorData() |
| RG-STR-008 | COMPARISON_SLUGS is defined | - | Array exists |
| RG-STR-009 | COMPARISON_SLUGS contains seoindicator-vs-ahrefs | - | Slug in array |
| RG-STR-010 | COMPARISON_SLUGS contains seoindicator-vs-semrush | - | Slug in array |
| RG-STR-011 | COMPARISON_SLUGS entries have valid format | - | All match pattern `*-vs-*` |
| RG-STR-012 | COMPARISON_SLUGS contains cross-competitor slugs | - | Has ahrefs-vs-semrush |
| RG-STR-013 | Registry has minimum 5 competitors | - | Length >= 5 |
| RG-STR-014 | COMPARISON_SLUGS has minimum 10 entries | - | Length >= 10 |
| RG-STR-015 | All registry IDs match keys | - | id === key for each entry |

### RG-GET: getCompetitorById() Tests (12 tests)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| RG-GET-001 | Returns seoindicator data | 'seoindicator' | CompetitorData object |
| RG-GET-002 | Returns ahrefs data | 'ahrefs' | CompetitorData object |
| RG-GET-003 | Returns semrush data | 'semrush' | CompetitorData object |
| RG-GET-004 | Returns null for invalid ID | 'nonexistent' | null |
| RG-GET-005 | Returns null for empty string | '' | null |
| RG-GET-006 | Returns null for null input | null | null |
| RG-GET-007 | Returns null for undefined input | undefined | null |
| RG-GET-008 | Returns null for number input | 123 | null |
| RG-GET-009 | Returns null for object input | {} | null |
| RG-GET-010 | Case-sensitive lookup | 'Ahrefs' | null |
| RG-GET-011 | Returns correct data structure | 'seoindicator' | Object has all CompetitorData fields |
| RG-GET-012 | Returned data is valid CompetitorData | 'ahrefs' | isCompetitorData() returns true |

### RG-SLUG: getComparisonBySlug() Tests (15 tests)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| RG-SLUG-001 | Returns comparison data for valid slug | 'seoindicator-vs-ahrefs' | ComparisonData object |
| RG-SLUG-002 | Returns comparison data for semrush | 'seoindicator-vs-semrush' | ComparisonData object |
| RG-SLUG-003 | Returns null for invalid slug | 'nonexistent-vs-foo' | null |
| RG-SLUG-004 | Returns null for empty string | '' | null |
| RG-SLUG-005 | Returns null for null input | null | null |
| RG-SLUG-006 | Returns null for malformed slug | 'seoindicator-ahrefs' | null |
| RG-SLUG-007 | Returns null for slug with extra parts | 'a-vs-b-vs-c' | null |
| RG-SLUG-008 | Returns correct slug in response | 'seoindicator-vs-ahrefs' | data.slug === 'seoindicator-vs-ahrefs' |
| RG-SLUG-009 | Returns correct competitor IDs | 'seoindicator-vs-ahrefs' | data.competitors = ['seoindicator', 'ahrefs'] |
| RG-SLUG-010 | Returns cross-competitor comparison | 'ahrefs-vs-semrush' | ComparisonData object |
| RG-SLUG-011 | Response has title | 'seoindicator-vs-ahrefs' | data.title is string |
| RG-SLUG-012 | Response has metaDescription | 'seoindicator-vs-ahrefs' | data.metaDescription is string |
| RG-SLUG-013 | Response has featureComparison | 'seoindicator-vs-ahrefs' | data.featureComparison is array |
| RG-SLUG-014 | Response has pricingComparison | 'seoindicator-vs-ahrefs' | data.pricingComparison has seoindicator and competitor |
| RG-SLUG-015 | Response has FAQs | 'seoindicator-vs-ahrefs' | data.faqs is array |

### RG-PARSE: parseSlug() Tests (14 tests)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| RG-PARSE-001 | Parses seoindicator-vs-ahrefs | 'seoindicator-vs-ahrefs' | ['seoindicator', 'ahrefs'] |
| RG-PARSE-002 | Parses seoindicator-vs-semrush | 'seoindicator-vs-semrush' | ['seoindicator', 'semrush'] |
| RG-PARSE-003 | Parses cross-competitor slug | 'ahrefs-vs-semrush' | ['ahrefs', 'semrush'] |
| RG-PARSE-004 | Returns null for slug without -vs- | 'seoindicator-ahrefs' | null |
| RG-PARSE-005 | Returns null for empty string | '' | null |
| RG-PARSE-006 | Returns null for null input | null | null |
| RG-PARSE-007 | Returns null for only -vs- | '-vs-' | null |
| RG-PARSE-008 | Returns null for slug starting with -vs- | '-vs-ahrefs' | null |
| RG-PARSE-009 | Returns null for slug ending with -vs- | 'seoindicator-vs-' | null |
| RG-PARSE-010 | Handles multiple -vs- correctly | 'a-vs-b-vs-c' | null (or takes first two) |
| RG-PARSE-011 | Returns tuple of strings | 'a-vs-b' | Array.isArray, length 2 |
| RG-PARSE-012 | Extracts first competitor correctly | 'seoindicator-vs-ahrefs' | result[0] === 'seoindicator' |
| RG-PARSE-013 | Extracts second competitor correctly | 'seoindicator-vs-ahrefs' | result[1] === 'ahrefs' |
| RG-PARSE-014 | Case-sensitive parsing | 'SEOINDICATOR-vs-ahrefs' | ['SEOINDICATOR', 'ahrefs'] or null |

### RG-GEN: generateComparisonData() Tests (15 tests)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| RG-GEN-001 | Generates valid comparison data | 'seoindicator', 'ahrefs' | ComparisonData object |
| RG-GEN-002 | Generates correct slug | 'seoindicator', 'ahrefs' | data.slug === 'seoindicator-vs-ahrefs' |
| RG-GEN-003 | Generates correct competitor IDs | 'seoindicator', 'ahrefs' | data.competitors = ['seoindicator', 'ahrefs'] |
| RG-GEN-004 | Returns null for invalid first competitor | 'nonexistent', 'ahrefs' | null |
| RG-GEN-005 | Returns null for invalid second competitor | 'seoindicator', 'nonexistent' | null |
| RG-GEN-006 | Returns null for both invalid | 'foo', 'bar' | null |
| RG-GEN-007 | Returns null for null first param | null, 'ahrefs' | null |
| RG-GEN-008 | Returns null for null second param | 'seoindicator', null | null |
| RG-GEN-009 | Generates feature comparison | 'seoindicator', 'ahrefs' | data.featureComparison is array |
| RG-GEN-010 | Generates pricing comparison | 'seoindicator', 'ahrefs' | data.pricingComparison has both |
| RG-GEN-011 | Generates pros/cons | 'seoindicator', 'ahrefs' | data.prosCons has both |
| RG-GEN-012 | Generates use cases | 'seoindicator', 'ahrefs' | data.useCases has both |
| RG-GEN-013 | Generates verdict | 'seoindicator', 'ahrefs' | data.verdict is string |
| RG-GEN-014 | Generates FAQs | 'seoindicator', 'ahrefs' | data.faqs is array |
| RG-GEN-015 | Generated data passes validation | 'seoindicator', 'ahrefs' | isComparisonData() returns true |

### RG-REL: getRelatedComparisons() Tests (12 tests)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| RG-REL-001 | Returns related comparisons for seoindicator | 'seoindicator' | Array of slugs |
| RG-REL-002 | Returns related comparisons for ahrefs | 'ahrefs' | Array of slugs |
| RG-REL-003 | Returns empty array for invalid ID | 'nonexistent' | [] |
| RG-REL-004 | Returns empty array for null input | null | [] |
| RG-REL-005 | Returns empty array for empty string | '' | [] |
| RG-REL-006 | Excludes self-referencing comparisons | 'seoindicator' | No 'seoindicator-vs-seoindicator' |
| RG-REL-007 | Returns maximum 5 related comparisons | 'seoindicator' | Length <= 5 |
| RG-REL-008 | Returns slugs containing competitor | 'ahrefs' | All slugs contain 'ahrefs' |
| RG-REL-009 | Returns valid slugs only | 'seoindicator' | All in COMPARISON_SLUGS |
| RG-REL-010 | Case-sensitive matching | 'Ahrefs' | [] or correct matches |
| RG-REL-011 | Returns array type | 'seoindicator' | Array.isArray === true |
| RG-REL-012 | All results are strings | 'seoindicator' | All items typeof === 'string' |

### RG-VAL: validateCompetitorData() Tests (20 tests)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| RG-VAL-001 | Returns true for valid data | valid CompetitorData | true |
| RG-VAL-002 | Returns false for null | null | false |
| RG-VAL-003 | Returns false for undefined | undefined | false |
| RG-VAL-004 | Returns false for empty object | {} | false |
| RG-VAL-005 | Returns false for string | 'data' | false |
| RG-VAL-006 | Returns false for array | [] | false |
| RG-VAL-007 | Returns false missing id | { ...CompetitorData, id: undefined } | false |
| RG-VAL-008 | Returns false missing name | { ...CompetitorData, name: undefined } | false |
| RG-VAL-009 | Returns false missing pricing | { ...CompetitorData, pricing: undefined } | false |
| RG-VAL-010 | Returns false for empty pricing | { ...CompetitorData, pricing: [] } | false |
| RG-VAL-011 | Returns false missing features | { ...CompetitorData, features: undefined } | false |
| RG-VAL-012 | Returns false missing pros | { ...CompetitorData, pros: undefined } | false |
| RG-VAL-013 | Returns false missing cons | { ...CompetitorData, cons: undefined } | false |
| RG-VAL-014 | Returns false missing lastUpdated | { ...CompetitorData, lastUpdated: undefined } | false |
| RG-VAL-015 | Returns false for invalid pricing item | { ...CompetitorData, pricing: [{}] } | false |
| RG-VAL-016 | Returns true with optional logo | { ...CompetitorData, logo: 'url' } | true |
| RG-VAL-017 | Returns false for invalid lastUpdated | { ...CompetitorData, lastUpdated: 'invalid' } | false |
| RG-VAL-018 | Returns false for negative price | { ...CompetitorData, pricing: [{ monthlyPrice: -10 }] } | false |
| RG-VAL-019 | Returns false for invalid use case audience | { ...CompetitorData, useCases: [{ audience: 'invalid' }] } | false |
| RG-VAL-020 | Validates all fields recursively | deeply nested invalid | false |

### RG-VCD: validateComparisonData() Tests (15 tests)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| RG-VCD-001 | Returns true for valid data | valid ComparisonData | true |
| RG-VCD-002 | Returns false for null | null | false |
| RG-VCD-003 | Returns false for undefined | undefined | false |
| RG-VCD-004 | Returns false for empty object | {} | false |
| RG-VCD-005 | Returns false missing slug | { ...ComparisonData, slug: undefined } | false |
| RG-VCD-006 | Returns false missing title | { ...ComparisonData, title: undefined } | false |
| RG-VCD-007 | Returns false missing competitors | { ...ComparisonData, competitors: undefined } | false |
| RG-VCD-008 | Returns false for wrong competitor count | { ...ComparisonData, competitors: ['a'] } | false |
| RG-VCD-009 | Returns false missing featureComparison | { ...ComparisonData, featureComparison: undefined } | false |
| RG-VCD-010 | Returns false missing pricingComparison | { ...ComparisonData, pricingComparison: undefined } | false |
| RG-VCD-011 | Returns false missing prosCons | { ...ComparisonData, prosCons: undefined } | false |
| RG-VCD-012 | Returns false missing verdict | { ...ComparisonData, verdict: undefined } | false |
| RG-VCD-013 | Returns false missing faqs | { ...ComparisonData, faqs: undefined } | false |
| RG-VCD-014 | Returns false for invalid slug format | { ...ComparisonData, slug: 'invalid' } | false |
| RG-VCD-015 | Validates nested structures | deeply nested invalid | false |

### RG-EDGE: Edge Cases (10 tests)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| RG-EDGE-001 | Handles Unicode competitor ID | '한글-vs-ahrefs' | null (or handled gracefully) |
| RG-EDGE-002 | Handles very long slug | 'a'.repeat(1000) + '-vs-' + 'b'.repeat(1000) | null or handles gracefully |
| RG-EDGE-003 | Handles special characters in slug | 'seoindicator-vs-@#$%' | null |
| RG-EDGE-004 | Registry is immutable | Attempt to modify | Error or no effect |
| RG-EDGE-005 | COMPARISON_SLUGS is immutable | Attempt to modify | Error or no effect |
| RG-EDGE-006 | Concurrent access to getComparisonBySlug | Multiple calls | Consistent results |
| RG-EDGE-007 | Memory efficiency with many competitors | 100+ competitors | No memory leak |
| RG-EDGE-008 | Handles circular references in data | Invalid nested data | Returns false for validation |
| RG-EDGE-009 | Handles prototype pollution attempt | __proto__ injection | Returns false |
| RG-EDGE-010 | Handles extremely large arrays | 10000+ features | Handles gracefully |

### RG-TS: TypeScript Types (5 tests)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| RG-TS-001 | getCompetitorById returns CompetitorData \| null | 'seoindicator' | Correct type |
| RG-TS-002 | getComparisonBySlug returns ComparisonData \| null | 'seoindicator-vs-ahrefs' | Correct type |
| RG-TS-003 | parseSlug returns [string, string] \| null | 'a-vs-b' | Correct type |
| RG-TS-004 | generateComparisonData returns ComparisonData \| null | 'a', 'b' | Correct type |
| RG-TS-005 | getRelatedComparisons returns string[] | 'seoindicator' | Correct type |

### AC: Acceptance Criteria (7 tests)

| ID | Scenario | Expected Result |
|----|----------|-----------------|
| AC-001 | Registry exports all competitors from single entry point | COMPETITOR_REGISTRY contains all competitors |
| AC-002 | getComparisonBySlug() returns valid ComparisonData for all slugs | All COMPARISON_SLUGS return valid data |
| AC-003 | parseSlug() correctly extracts competitor IDs | Returns tuple or null correctly |
| AC-004 | Invalid slugs return null (not throw errors) | No exceptions thrown |
| AC-005 | Validation functions catch malformed data | Returns false for invalid data |
| AC-006 | SEOIndicator data file created with accurate info | File exists, data is valid |
| AC-007 | Unit tests for all helper functions | Tests pass |

---

## Mock Data

### Valid CompetitorData

```typescript
const validCompetitorData: CompetitorData = {
  id: 'test-competitor',
  name: 'Test Competitor',
  slug: 'test-competitor',
  website: 'https://example.com',
  logo: 'https://example.com/logo.png',
  tagline: 'A test competitor',
  pricing: [
    {
      name: 'Free',
      monthlyPrice: 'Free',
      annualPrice: 'Free',
      features: ['Feature 1', 'Feature 2']
    }
  ],
  features: [
    {
      name: 'Test Category',
      features: [
        { name: 'Feature', seoindicator: true, competitor: true }
      ]
    }
  ],
  pros: ['Pro 1', 'Pro 2'],
  cons: ['Con 1'],
  useCases: [
    {
      title: 'Use Case 1',
      description: 'Description',
      audience: 'solo',
      budget: 'free'
    }
  ],
  lastUpdated: '2026-03-05T00:00:00Z',
  source: 'Test data'
};
```

### Valid ComparisonData

```typescript
const validComparisonData: ComparisonData = {
  slug: 'seoindicator-vs-test',
  title: 'SEOIndicator vs Test',
  metaDescription: 'Compare SEOIndicator vs Test',
  competitors: ['seoindicator', 'test'],
  summary: 'A comparison',
  featureComparison: [],
  pricingComparison: {
    seoindicator: [],
    competitor: []
  },
  prosCons: {
    seoindicator: { pros: [], cons: [] },
    competitor: { pros: [], cons: [] }
  },
  useCases: {
    seoindicator: [],
    competitor: []
  },
  verdict: 'SEOIndicator wins',
  faqs: [],
  relatedComparisons: []
};
```

---

## Coverage Targets

| Category | Target |
|----------|--------|
| Registry Structure | 100% |
| getCompetitorById | 100% |
| getComparisonBySlug | 100% |
| parseSlug | 100% |
| generateComparisonData | 100% |
| getRelatedComparisons | 100% |
| validateCompetitorData | 100% |
| validateComparisonData | 100% |
| Edge Cases | 100% |
| Acceptance Criteria | 100% |

**Overall Target: 100% coverage for registry.ts**

---

## Test Execution

```bash
# Run registry tests
npm test -- frontend/__tests__/lib/compare/registry.test.ts

# Run SEOIndicator data tests
npm test -- frontend/__tests__/lib/compare/competitors/seoindicator.test.ts

# Run with coverage
npm run test:coverage -- frontend/__tests__/lib/compare/

# Run all comparison tests
npm test -- frontend/__tests__/lib/compare/
```

---

## Dependencies

- TASK-32-1 (Types and Interfaces) - Done ✅
- Type guards from types.ts: isCompetitorData, isComparisonData, isPricingTier, etc.

---

## Notes for Developer

1. **Registry Implementation:**
   - Use `export const COMPETITOR_REGISTRY: Record<string, CompetitorData>` pattern
   - Lazy load competitor data for bundle optimization
   - Cache parsed comparison data to avoid regeneration

2. **Helper Functions:**
   - All functions must handle null/undefined gracefully
   - Never throw exceptions for invalid input - return null
   - Use type guards from types.ts for validation

3. **SEOIndicator Data:**
   - Create `frontend/lib/compare/competitors/seoindicator.ts`
   - Include accurate pricing tiers (Free, Pro)
   - Feature categories must match comparison needs
   - Pros/cons should be honest and balanced

4. **Slug Format:**
   - Pattern: `{competitorA}-vs-{competitorB}`
   - Always lowercase
   - No spaces or special characters

5. **Performance:**
   - Consider memoization for generateComparisonData
   - Avoid re-creating objects on every call
   - Use Object.freeze for immutability

---

## Comments

### [2026-03-05 16:05 GMT+2] Agent: PreTester
**Test Design Complete:**
- Created 140+ test scenarios across 11 categories
- Tests cover all acceptance criteria
- Mock data provided for valid structures
- Edge cases and error handling included
- TypeScript type verification included

**Test Categories Summary:**
| Category | Tests |
|----------|-------|
| Registry Structure | 15 |
| getCompetitorById | 12 |
| getComparisonBySlug | 15 |
| parseSlug | 14 |
| generateComparisonData | 15 |
| getRelatedComparisons | 12 |
| validateCompetitorData | 20 |
| validateComparisonData | 15 |
| Edge Cases | 10 |
| TypeScript Types | 5 |
| Acceptance Criteria | 7 |
| **Total** | **140** |

**Status:** Updated to `Tests Coverage In Progress`
