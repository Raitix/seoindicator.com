# TEST-16-2: Keyword Density Checker Frontend Tests

## Task Reference
- **Task:** [TASK-16-2](../TASKS/TASK-16-2.md)
- **Specification:** [SPEC-016](../SPECIFICATIONS/SPEC-016.md)
- **Feature:** [FEAT-016](../FEATURES/FEAT-016.md)
- **Dependency:** TASK-16-3 (Backend API Proxy)

---

## Overview

Test suite for the Keyword Density Checker frontend component. This tool allows users to analyze keyword frequency, densities, and heading relevance from URLs or custom HTML input.

### Key Test Areas
1. **Unit Tests** - Component rendering, input validation, keyword analysis logic
2. **Integration Tests** - API integration, error handling, data flow
3. **E2E Tests** - Full user workflows, accessibility, responsive design

---

## Test Files

| File | Type | Purpose |
|------|------|---------|
| `frontend/__tests__/components/tools/KeywordDensityForm.test.tsx` | Unit | Form component rendering and validation |
| `frontend/__tests__/components/tools/KeywordDensityResults.test.tsx` | Unit | Results display component |
| `frontend/__tests__/components/tools/KeywordDensityWarning.test.tsx` | Unit | Warning/indicator components |
| `frontend/__tests__/lib/utils/keywordDensityAnalyzer.test.ts` | Unit | Keyword density calculation logic |
| `frontend/__tests__/lib/utils/headingAnalyzer.test.ts` | Unit | Heading relevance analysis |
| `frontend/__tests__/integration/keywordDensityTool.integration.test.tsx` | Integration | API integration and data flow |
| `frontend/e2e/keyword-density-checker.spec.ts` | E2E | Full user workflows |

---

## Test Scenarios

### Unit Tests - KeywordDensityForm Component

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| UT-001 | Renders form with URL input | Default props | Input with placeholder "Enter page URL" |
| UT-002 | Renders form with HTML textarea | Default props | Textarea with placeholder "Or paste HTML here" |
| UT-003 | Renders keyword input field | Default props | Input for target keyword |
| UT-004 | URL input validation - valid URL | "https://example.com" | No error message |
| UT-005 | URL input validation - invalid URL | "not-a-url" | Error: "Please enter a valid URL" |
| UT-006 | URL input validation - empty | "" | Error: "URL is required" (on submit) |
| UT-007 | HTML textarea accepts valid HTML | `<html><body>Test content</body></html>` | No error |
| UT-008 | HTML textarea empty allowed | "" | No error (optional field) |
| UT-009 | Only one input active (URL/HTML) | URL entered, then HTML | URL cleared, HTML used |
| UT-010 | Keyword input validation - empty | "" on submit | Error: "Keyword is required" |
| UT-011 | Keyword input validation - special chars | "test@keyword!" | Accepted (valid keyword) |
| UT-012 | Keyword input validation - max length | 100+ character keyword | Warning: "Keyword too long" |
| UT-013 | Submit button disabled on loading | loading=true | Button disabled, shows loading state |
| UT-014 | Submit calls onAnalyze with correct data | Click button | onAnalyze({ url, keyword }) or { html, keyword } |
| UT-015 | Form clears on reset | Click reset button | All fields cleared |
| UT-016 | Initial values populated | initialUrl, initialKeyword props | Form populated with initial values |

### Unit Tests - KeywordDensityResults Component

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| UT-017 | Displays keyword density percentage | density=2.5% | "2.5%" displayed with status indicator |
| UT-018 | Green status for optimal density | density=1-2% | Green indicator, "Good" label |
| UT-019 | Yellow warning for low density | density<1% | Yellow indicator, "Too low" label |
| UT-020 | Red warning for high density | density>3% | Red indicator, "Over-optimized" label |
| UT-021 | Displays keyword count | count=15 | "15 occurrences" |
| UT-022 | Displays total word count | totalWords=500 | "500 total words" |
| UT-023 | Displays keyword positions | positions=[10, 45, 120] | List of position badges |
| UT-024 | Displays heading analysis | headings array | Headings list with keyword match indicators |
| UT-025 | Shows keyword in heading success | keyword in H1 | Green checkmark, "Found in H1" |
| UT-026 | Shows keyword missing from headings | keyword not in any heading | Warning, "Not found in headings" |
| UT-027 | Shows multiple heading matches | keyword in H1 and H2 | Both indicators shown |
| UT-028 | Displays analysis timestamp | analyzedAt | "Analyzed: YYYY-MM-DD HH:MM" |
| UT-029 | Shows loading skeleton | loading=true | Skeleton placeholders displayed |
| UT-030 | Handles empty results | no keywords found | "No keywords found" message |

### Unit Tests - KeywordDensityWarning Component

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| UT-031 | Displays over-optimization warning | density>3% | Warning badge with "Over-optimized" |
| UT-032 | Displays under-optimization warning | density<1% | Warning badge with "Too low" |
| UT-033 | Displays optimal density success | 1%<=density<=3% | Success badge with "Good" |
| UT-034 | Heading relevance warning | keyword not in H1 | "Consider adding keyword to main heading" |
| UT-035 | Multiple heading warnings | keyword missing from multiple | Consolidated warning list |
| UT-036 | Dismissible warnings | Click dismiss | Warning hidden, state updated |
| UT-037 | Warning severity indicator | type="critical" | Red alert styling |
| UT-038 | Warning severity indicator | type="warning" | Yellow alert styling |
| UT-039 | Warning severity indicator | type="info" | Blue info styling |
| UT-040 | Warning with recommendation | warning object | Recommendation text displayed |

### Unit Tests - Keyword Density Analyzer Utility

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| UT-041 | Calculate single keyword density | "test", 5 occurrences, 100 words | density=5% |
| UT-042 | Calculate zero occurrences | "test", 0 occurrences, 100 words | density=0% |
| UT-043 | Case-insensitive keyword match | "SEO", text has "seo" | Match counted |
| UT-044 | Partial word match excluded | "test", text has "testing" | No match (whole word only) |
| UT-045 | Multiple keyword forms | "SEO", text has "SEO", "seo", "Seo" | All counted (3 occurrences) |
| UT-046 | Strip HTML tags before counting | `<p>test</p>` | "test" counted, tags ignored |
| UT-047 | Handle special characters | "C++", text has "C++" | Match counted |
| UT-048 | Handle multi-word keywords | "search engine", text analysis | Multi-word match counted |
| UT-049 | Extract keyword positions | "test" in text | Array of character positions |
| UT-050 | Calculate density for multiple keywords | keywords array | Map of keyword → density |

### Unit Tests - Heading Analyzer Utility

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| UT-051 | Extract H1 from HTML | HTML with H1 | { tag: "h1", text: "..." } |
| UT-052 | Extract all headings | HTML with H1-H6 | Array of heading objects |
| UT-053 | Check keyword in heading | keyword="SEO", h1="SEO Guide" | { match: true, heading: "h1" } |
| UT-054 | Case-insensitive heading match | keyword="seo", h1="SEO Guide" | { match: true } |
| UT-055 | No keyword in heading | keyword="test", h1="Welcome" | { match: false } |
| UT-056 | Multiple heading levels | H1, H2, H3 | All extracted in order |
| UT-057 | Missing H1 | HTML without H1 | Warning in output |
| UT-058 | Multiple H1 tags | HTML with 2 H1s | Warning: "Multiple H1 tags detected" |
| UT-059 | Empty heading | `<h1></h1>` | Warning: "Empty heading found" |
| UT-060 | Heading hierarchy check | H1 → H3 (skip H2) | Warning: "Heading hierarchy skipped" |

### Integration Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| IT-001 | Analyze URL with keyword | URL + keyword | API called, results displayed |
| IT-002 | Analyze HTML with keyword | HTML + keyword | Local analysis, results displayed |
| IT-003 | API error handling | API returns 500 | Error message displayed |
| IT-004 | API timeout handling | Request timeout | Timeout error message |
| IT-005 | Rate limit handling | Rate limit exceeded | Rate limit message, retry button |
| IT-006 | Network error handling | Network offline | Network error message |
| IT-007 | Real-time preview | Change HTML input | Density recalculated live |
| IT-008 | Form reset | Click reset | All fields cleared |
| IT-009 | Export results | Click export | Results exported as JSON |
| IT-010 | Compare multiple keywords | Enter 2+ keywords | Side-by-side comparison |

### E2E Tests

| ID | Scenario | Steps | Expected Result |
|----|----------|-------|-----------------|
| E2E-001 | Full URL analysis flow | 1. Navigate to tool<br>2. Enter URL<br>3. Enter keyword<br>4. Click Analyze | Results displayed with density and heading analysis |
| E2E-002 | Full HTML analysis flow | 1. Navigate to tool<br>2. Paste HTML<br>3. Enter keyword<br>4. View results | Live density calculation, results shown |
| E2E-003 | Over-optimization warning | 1. Analyze page with high density | Warning displayed prominently |
| E2E-004 | Heading relevance display | 1. Analyze page<br>2. Check heading section | H1-H6 with keyword match indicators |
| E2E-005 | Error recovery | 1. Enter invalid URL<br>2. See error<br>3. Correct and retry | Error clears, analysis succeeds |
| E2E-006 | Accessibility - keyboard nav | Navigate with Tab | All controls accessible via keyboard |
| E2E-007 | Accessibility - screen reader | Use screen reader | All elements have proper ARIA labels |
| E2E-008 | Responsive design - mobile | View on mobile viewport | Layout adjusts appropriately |
| E2E-009 | Tool embedded in guide page | Navigate to guide with tool | Tool renders within guide context |
| E2E-010 | Standalone tool page | Navigate to /tools/keyword-density | Tool renders as standalone page |
| E2E-011 | Multiple keyword comparison | Enter 3 keywords, analyze | Comparison table displayed |
| E2E-012 | Export functionality | Analyze, click export | JSON file downloaded |

---

## Mock Data

### Valid URL Analysis Response
```json
{
  "success": true,
  "data": {
    "url": "https://example.com/seo-guide",
    "keyword": "SEO",
    "analysis": {
      "density": 2.3,
      "occurrences": 12,
      "totalWords": 520,
      "positions": [15, 89, 156, 234, 312, 401, 456, 512, 678, 745, 823, 912],
      "status": "good"
    },
    "headings": [
      { "tag": "h1", "text": "SEO Best Practices Guide", "keywordFound": true },
      { "tag": "h2", "text": "Introduction to SEO", "keywordFound": true },
      { "tag": "h2", "text": "On-Page Optimization", "keywordFound": false },
      { "tag": "h3", "text": "Meta Tags for SEO", "keywordFound": true },
      { "tag": "h3", "text": "Content Strategy", "keywordFound": false }
    ],
    "warnings": [
      { "type": "info", "message": "Consider adding keyword to 'Content Strategy' heading" }
    ],
    "analyzedAt": "2026-02-26T19:30:00Z"
  }
}
```

### Valid HTML Input
```html
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>SEO Best Practices Guide 2026</title>
</head>
<body>
  <h1>SEO Best Practices Guide</h1>
  <p>Learn the essential SEO techniques for 2026. SEO is crucial for online visibility.</p>
  
  <h2>Introduction to SEO</h2>
  <p>SEO helps your website rank higher in search results. Good SEO practices improve user experience.</p>
  
  <h2>On-Page Optimization</h2>
  <p>On-page SEO includes meta tags, content optimization, and internal linking.</p>
  
  <h3>Meta Tags for SEO</h3>
  <p>Meta tags are essential for SEO. They tell search engines about your page content.</p>
  
  <h3>Content Strategy</h3>
  <p>Create valuable content that addresses user needs and incorporates SEO best practices.</p>
  
  <h2>Technical SEO</h2>
  <p>Technical SEO ensures your site is crawlable and indexable by search engines.</p>
  
  <p>Follow these SEO guidelines to improve your website's search performance.</p>
</body>
</html>
```

### Over-Optimized Response (Density > 3%)
```json
{
  "success": true,
  "data": {
    "url": "https://example.com/spammy-page",
    "keyword": "SEO",
    "analysis": {
      "density": 5.8,
      "occurrences": 35,
      "totalWords": 600,
      "status": "over-optimized"
    },
    "headings": [
      { "tag": "h1", "text": "SEO SEO SEO Best SEO Practices SEO", "keywordFound": true }
    ],
    "warnings": [
      { "type": "warning", "message": "Keyword density is too high (5.8%). This may be seen as keyword stuffing." },
      { "type": "warning", "message": "H1 contains multiple keyword repetitions. Consider a more natural heading." }
    ],
    "analyzedAt": "2026-02-26T19:35:00Z"
  }
}
```

### Error Response
```json
{
  "success": false,
  "error": {
    "code": "FETCH_ERROR",
    "message": "Unable to fetch the page. Please check the URL and try again."
  }
}
```

---

## Coverage Targets

| Category | Target | Priority |
|----------|--------|----------|
| KeywordDensityForm component | 90% | High |
| KeywordDensityResults component | 90% | High |
| KeywordDensityWarning component | 90% | High |
| Keyword density analyzer | 100% | High |
| Heading analyzer | 100% | High |
| Integration tests | 80% | Medium |
| E2E tests | 70% | Medium |

**Overall Target:** 85% code coverage

---

## Test Execution

```bash
# Run all unit tests
cd frontend && npm test

# Run specific test file
npm test -- __tests__/components/tools/KeywordDensityForm.test.tsx

# Run with coverage
npm run test:coverage

# Run E2E tests
npm run test:e2e

# Run specific E2E test
npx playwright test e2e/keyword-density-checker.spec.ts
```

---

## Dependencies

### Required for Testing
- Jest (unit tests)
- React Testing Library
- @testing-library/user-event
- Playwright (E2E tests)
- MSW (Mock Service Worker for API mocking)

### Mock Services
- Mock API responses for `/api/tools/keyword-density`
- Mock fetch for URL analysis (when using API proxy)

---

## Notes for Developer

### Component Structure
```
components/tools/
├── KeywordDensityForm.tsx         # Main form component
├── KeywordDensityForm.test.tsx
├── KeywordDensityResults.tsx      # Results display
├── KeywordDensityResults.test.tsx
├── KeywordDensityWarning.tsx      # Warning indicators
├── KeywordDensityWarning.test.tsx
└── index.ts                       # Exports
```

### Utility Structure
```
lib/utils/
├── keywordDensityAnalyzer.ts      # Density calculation logic
├── keywordDensityAnalyzer.test.ts
├── headingAnalyzer.ts             # Heading extraction & analysis
├── headingAnalyzer.test.ts
└── textUtils.ts                   # Text processing helpers
```

### Key Implementation Notes
1. **Real-time Calculation:** For HTML input, calculate density live as user types
2. **Debounced API Calls:** URL analysis should debounce input (300ms)
3. **Whole Word Matching:** Only count whole word matches, not partial
4. **Case Insensitivity:** Keyword matching should be case-insensitive
5. **HTML Stripping:** Remove HTML tags before counting words
6. **Warning Thresholds:**
   - < 1% = Too low (yellow)
   - 1-3% = Good (green)
   - > 3% = Over-optimized (red)

### API Integration
- Wait for TASK-16-3 completion for full integration testing
- Use MSW to mock API responses during development
- Test both success and error paths

### Security Considerations
- Sanitize HTML input to prevent XSS
- Validate URLs before making API calls
- Handle sensitive content appropriately
- Rate limit API calls client-side

---

## Comments

### [2026-02-26 19:18] Agent: PreTester
Created TEST-16-2 documentation. Test suite covers:
- 60 unit test scenarios
- 10 integration test scenarios
- 12 E2E test scenarios

Key focus areas:
1. Keyword density calculation accuracy
2. Heading relevance analysis
3. Warning display for over/under optimization
4. Client-side validation
5. Real-time analysis for HTML input
6. Accessibility compliance

**Dependency Note:** Integration tests require TASK-16-3 (Backend API Proxy) to be complete. Use MSW mocks for unit/integration tests until API is available.
