# TEST-16-1: Meta Tag Analyzer Frontend Tests

## Task Reference
- **Task:** [TASK-16-1](../TASKS/TASK-16-1.md)
- **Specification:** [SPEC-016](../SPECIFICATIONS/SPEC-016.md)
- **Feature:** [FEAT-016](../FEATURES/FEAT-016.md)
- **Dependency:** TASK-16-3 (Backend API Proxy)

---

## Overview

Test suite for the Meta Tag Analyzer frontend component. This tool allows users to analyze meta tags from URLs or custom HTML, displaying title/meta length validation and SERP preview in real-time.

### Key Test Areas
1. **Unit Tests** - Component rendering, input validation, analysis logic
2. **Integration Tests** - API integration, error handling, data flow
3. **E2E Tests** - Full user workflows, accessibility, responsive design

---

## Test Files

| File | Type | Purpose |
|------|------|---------|
| `frontend/__tests__/components/tools/MetaTagToolForm.test.tsx` | Unit | Component rendering and validation |
| `frontend/__tests__/components/tools/SerpPreview.test.tsx` | Unit | SERP preview component |
| `frontend/__tests__/components/tools/MetaTagResults.test.tsx` | Unit | Results display component |
| `frontend/__tests__/lib/validators/metaTagValidator.test.ts` | Unit | Input validation logic |
| `frontend/__tests__/lib/utils/metaTagAnalyzer.test.ts` | Unit | Analysis utilities |
| `frontend/__tests__/integration/metaTagTool.integration.test.tsx` | Integration | API integration and data flow |
| `frontend/e2e/meta-tag-analyzer.spec.ts` | E2E | Full user workflows |

---

## Test Scenarios

### Unit Tests - MetaTagToolForm Component

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| UT-001 | Renders form with URL input | Default props | Input with placeholder "Enter page URL" |
| UT-002 | Renders form with HTML textarea | Default props | Textarea with placeholder "Or paste HTML here" |
| UT-003 | URL input validation - valid URL | "https://example.com" | No error message |
| UT-004 | URL input validation - invalid URL | "not-a-url" | Error: "Please enter a valid URL" |
| UT-005 | URL input validation - empty | "" | Error: "URL is required" |
| UT-006 | HTML textarea validation - valid HTML | `<html><head><title>Test</title></head></html>` | No error |
| UT-007 | HTML textarea validation - empty | "" | No error (optional field) |
| UT-008 | Only one input active at a time | URL entered, then HTML entered | URL cleared, HTML used |
| UT-009 | Submit button disabled on loading | loading=true | Button disabled |
| UT-010 | Submit button calls onAnalyze | Click button | onAnalyze called with input data |

### Unit Tests - SerpPreview Component

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| UT-011 | Renders SERP preview container | Default props | Container with Google-style preview |
| UT-012 | Displays title in preview | title="Example Page" | Title displayed in preview |
| UT-013 | Displays URL in preview | url="https://example.com" | URL displayed in green |
| UT-014 | Displays description in preview | description="Example description" | Description in gray text |
| UT-015 | Truncates long title (>60 chars) | title="A very long title that exceeds sixty characters limit" | Title truncated with "..." |
| UT-016 | Truncates long description (>160 chars) | description="A very long description..." | Description truncated with "..." |
| UT-017 | Shows warning for short title | title="Short" (5 chars) | Warning badge displayed |
| UT-018 | Shows warning for missing description | description="" | Warning indicator shown |
| UT-019 | Mobile preview toggle | Click mobile button | Preview switches to mobile layout |
| UT-020 | Desktop preview default | Default state | Desktop layout displayed |

### Unit Tests - MetaTagResults Component

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| UT-021 | Displays title length status | titleLength=55 | "55/60 characters" (green) |
| UT-022 | Displays title too long warning | titleLength=75 | "75/60 characters" (red) |
| UT-023 | Displays title too short warning | titleLength=25 | "25/60 characters" (yellow) |
| UT-024 | Displays description length status | descriptionLength=155 | "155/160 characters" (green) |
| UT-025 | Displays description too long warning | descriptionLength=180 | "180/160 characters" (red) |
| UT-026 | Displays missing title error | no title | Error message displayed |
| UT-027 | Displays missing description error | no description | Warning message displayed |
| UT-028 | Displays keyword in title indicator | keyword in title | Green checkmark |
| UT-029 | Displays keyword missing from title | keyword not in title | Yellow warning |
| UT-030 | Shows analysis timestamp | analysisTime | "Analyzed: YYYY-MM-DD HH:MM" |

### Unit Tests - Meta Tag Validator

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| UT-031 | Valid URL with https | "https://example.com" | { valid: true } |
| UT-032 | Valid URL with http | "http://example.com" | { valid: true } |
| UT-033 | Invalid URL - no protocol | "example.com" | { valid: false, error: "..." } |
| UT-034 | Invalid URL - special chars | "https://exa<mple.com" | { valid: false, error: "..." } |
| UT-035 | URL with path and query | "https://example.com/page?query=1" | { valid: true } |
| UT-036 | URL max length validation | URL > 2083 chars | { valid: false, error: "..." } |
| UT-037 | HTML with valid structure | `<html><head>...</head><body></body></html>` | { valid: true } |
| UT-038 | HTML with script tags (sanitization) | `<script>alert('xss')</script>` | Script removed in output |
| UT-039 | HTML with inline styles | `<style>body{color:red}</style>` | Styles preserved |
| UT-040 | Empty HTML input | "" | { valid: true, isEmpty: true } |

### Unit Tests - Meta Tag Analyzer Utility

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| UT-041 | Extract title from HTML | HTML with `<title>Test</title>` | { title: "Test" } |
| UT-042 | Extract meta description | HTML with meta description | { description: "..." } |
| UT-043 | Extract canonical URL | HTML with canonical link | { canonical: "..." } |
| UT-044 | Extract Open Graph title | HTML with og:title | { ogTitle: "..." } |
| UT-045 | Extract Open Graph description | HTML with og:description | { ogDescription: "..." } |
| UT-046 | Extract Open Graph image | HTML with og:image | { ogImage: "..." } |
| UT-047 | Extract Twitter Card data | HTML with twitter:card | { twitterCard: "..." } |
| UT-048 | Calculate title length | title string | Character count |
| UT-049 | Calculate description length | description string | Character count |
| UT-050 | Detect duplicate meta tags | HTML with duplicate meta | { warnings: [...] } |

### Integration Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| IT-001 | Analyze URL via API | Valid URL | API called, results displayed |
| IT-002 | Analyze custom HTML | HTML string | Local analysis, results displayed |
| IT-003 | API error handling | API returns 500 | Error message displayed |
| IT-004 | API timeout handling | Request timeout | Timeout error message |
| IT-005 | Rate limit handling | Rate limit exceeded | Rate limit message, retry button |
| IT-006 | Network error handling | Network offline | Network error message |
| IT-007 | Results update in real-time | Change HTML input | Preview updates immediately |
| IT-008 | Form reset functionality | Click reset | Form cleared, results cleared |
| IT-009 | Analysis history (if implemented) | Multiple analyses | History displayed |
| IT-010 | Export results | Click export | Results exported as JSON/PDF |

### E2E Tests

| ID | Scenario | Steps | Expected Result |
|----|----------|-------|-----------------|
| E2E-001 | Full URL analysis flow | 1. Navigate to tool<br>2. Enter URL<br>3. Click Analyze<br>4. View results | Results displayed with SERP preview |
| E2E-002 | Full HTML analysis flow | 1. Navigate to tool<br>2. Paste HTML<br>3. View preview | Live preview updates, results shown |
| E2E-003 | Error recovery | 1. Enter invalid URL<br>2. See error<br>3. Correct URL<br>4. Analyze | Error clears, analysis succeeds |
| E2E-004 | Mobile preview toggle | 1. Analyze URL<br>2. Click mobile toggle | Preview switches to mobile view |
| E2E-005 | Accessibility - keyboard nav | 1. Navigate with Tab | All controls accessible via keyboard |
| E2E-006 | Accessibility - screen reader | 1. Use screen reader | All elements have proper ARIA labels |
| E2E-007 | Responsive design - mobile | 1. View on mobile viewport | Layout adjusts appropriately |
| E2E-008 | Responsive design - tablet | 1. View on tablet viewport | Layout adjusts appropriately |
| E2E-009 | Tool embedded in guide page | 1. Navigate to guide<br>2. See embedded tool | Tool renders within guide context |
| E2E-010 | Standalone tool page | 1. Navigate to /tools/meta-tag-analyzer | Tool renders as standalone page |

---

## Mock Data

### Valid URL Analysis Response
```json
{
  "success": true,
  "data": {
    "url": "https://example.com",
    "title": "Example Domain - Official Site",
    "titleLength": 28,
    "description": "This domain is for use in illustrative examples in documents.",
    "descriptionLength": 58,
    "canonical": "https://example.com",
    "ogTitle": "Example Domain",
    "ogDescription": "This domain is for use in illustrative examples.",
    "ogImage": null,
    "twitterCard": "summary",
    "warnings": [
      { "type": "info", "message": "Consider adding an Open Graph image" }
    ],
    "analyzedAt": "2026-02-26T19:15:00Z"
  }
}
```

### Valid HTML Input
```html
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>SEO Best Practices Guide 2026 | SEOIndicator</title>
  <meta name="description" content="Learn the essential SEO best practices for 2026. This comprehensive guide covers on-page optimization, technical SEO, and content strategy.">
  <meta name="keywords" content="SEO, search engine optimization, best practices">
  <link rel="canonical" href="https://seoindicator.com/guides/seo-best-practices">
  <meta property="og:title" content="SEO Best Practices Guide 2026">
  <meta property="og:description" content="Learn the essential SEO best practices for 2026.">
  <meta property="og:image" content="https://seoindicator.com/images/og-seo-guide.png">
  <meta property="og:url" content="https://seoindicator.com/guides/seo-best-practices">
  <meta name="twitter:card" content="summary_large_image">
</head>
<body>
  <h1>SEO Best Practices Guide 2026</h1>
  <p>Comprehensive guide to modern SEO techniques.</p>
</body>
</html>
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
| MetaTagToolForm component | 90% | High |
| SerpPreview component | 90% | High |
| MetaTagResults component | 90% | High |
| Input validators | 100% | High |
| Analysis utilities | 95% | High |
| Integration tests | 80% | Medium |
| E2E tests | 70% | Medium |

**Overall Target:** 85% code coverage

---

## Test Execution

```bash
# Run all unit tests
cd frontend && npm test

# Run specific test file
npm test -- __tests__/components/tools/MetaTagToolForm.test.tsx

# Run with coverage
npm run test:coverage

# Run E2E tests
npm run test:e2e

# Run specific E2E test
npx playwright test e2e/meta-tag-analyzer.spec.ts
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
- Mock API responses for `/api/tools/analyze`
- Mock fetch for URL analysis (when using API proxy)

---

## Notes for Developer

### Component Structure
```
components/tools/
├── MetaTagToolForm.tsx     # Main form component
├── MetaTagToolForm.test.tsx
├── SerpPreview.tsx         # SERP preview component
├── SerpPreview.test.tsx
├── MetaTagResults.tsx      # Results display
├── MetaTagResults.test.tsx
└── index.ts                # Exports
```

### Key Implementation Notes
1. **Real-time Preview:** SERP preview should update as user types HTML
2. **Debounced API Calls:** URL analysis should debounce input (300ms)
3. **Error Boundaries:** Wrap components in error boundaries
4. **Accessibility:** All inputs must have proper labels and ARIA attributes
5. **Mobile-First:** Design for mobile, enhance for desktop

### API Integration
- Wait for TASK-16-3 completion for full integration testing
- Use MSW to mock API responses during development
- Test both success and error paths

### Security Considerations
- Sanitize HTML input to prevent XSS
- Validate URLs before making API calls
- Rate limit API calls client-side (debounce)
- Handle sensitive data in meta tags (e.g., personal info in descriptions)

---

## Comments

### [2026-02-26 19:15] Agent: PreTester
Created TEST-16-1 documentation. Test suite covers:
- 50 unit test scenarios
- 10 integration test scenarios
- 10 E2E test scenarios

Key focus areas:
1. Input validation (URL and HTML)
2. SERP preview rendering
3. Real-time analysis feedback
4. Error handling and recovery
5. Accessibility compliance

**Dependency Note:** Integration tests require TASK-16-3 (Backend API Proxy) to be complete. Use MSW mocks for unit/integration tests until API is available.

### [2026-02-26 19:16] Agent: PreTester
Status updated to `Tests Coverage In Progress`. Proceeding with test file creation.
