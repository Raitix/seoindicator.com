# TEST-16-4: Tool Embedding System Tests

## Task Reference
- Task: [TASK-16-4](../TASKS/TASK-16-4.md)
- Feature: [FEAT-016](../FEATURES/FEAT-016.md)
- Specification: [SPEC-16](../SPECIFICATIONS/SPEC-16.md)

---

## Overview

Test suite for the ToolWidget React component and tool embedding system. Tests cover component rendering, URL validation, state management, error handling, results display, reset functionality, responsive design, and accessibility.

---

## Test Files

| Test File | Location | Description |
|-----------|----------|-------------|
| `ToolWidget.test.tsx` | `frontend/components/tools/__tests__/` | Unit tests for ToolWidget component |
| `ToolWidget.integration.test.tsx` | `frontend/components/tools/__tests__/` | Integration tests with API mocking |
| `useToolAnalysis.test.ts` | `frontend/hooks/__tests__/` | Tests for tool analysis hook |
| `urlValidation.test.ts` | `frontend/lib/utils/__tests__/` | URL validation utility tests |

---

## Test Scenarios

### TW-001: Component Rendering

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| TW-001-01 | Renders with required props | `tool="meta-tag-analyzer" title="Test" description="Test desc"` | Component renders, displays title and description |
| TW-001-02 | Renders with placeholder | `placeholder="Enter your URL"` | Placeholder text shown in input |
| TW-001-03 | Renders with showHelp=true | `showHelp={true}` | Help icon/tooltip visible |
| TW-001-04 | Renders with showHelp=false | `showHelp={false}` | No help icon/tooltip |
| TW-001-05 | Renders meta-tag-analyzer tool | `tool="meta-tag-analyzer"` | URL input visible, tool-specific config applied |
| TW-001-06 | Renders keyword-density tool | `tool="keyword-density"` | URL input visible, tool-specific config applied |
| TW-001-07 | Renders mobile-friendly tool | `tool="mobile-friendly"` | URL input visible, tool-specific config applied |
| TW-001-08 | Renders social-preview tool | `tool="social-preview"` | URL input visible, tool-specific config applied |
| TW-001-09 | Throws with invalid tool prop | `tool="invalid-tool"` | Error thrown (invalid prop) |

### TW-002: URL Input Validation

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| TW-002-01 | Accepts valid http URL | `http://example.com` | Valid, no error |
| TW-002-02 | Accepts valid https URL | `https://example.com` | Valid, no error |
| TW-002-03 | Accepts URL with path | `https://example.com/page` | Valid, no error |
| TW-002-04 | Accepts URL with query params | `https://example.com?page=1` | Valid, no error |
| TW-002-05 | Rejects empty input | `""` | Error: "Please enter a URL" |
| TW-002-06 | Rejects whitespace only | `"   "` | Error: "Please enter a URL" |
| TW-002-07 | Rejects non-URL string | `not-a-url` | Error: INVALID_URL message |
| TW-002-08 | Rejects ftp protocol | `ftp://example.com` | Error: INVALID_URL message |
| TW-002-09 | Rejects javascript: protocol | `javascript:alert(1)` | Error: INVALID_URL message |
| TW-002-10 | Rejects data: protocol | `data:text/html,<script>` | Error: INVALID_URL message |
| TW-002-11 | Trims whitespace from URL | `"  https://example.com  "` | URL trimmed before submission |
| TW-002-12 | Handles unicode in URL | `https://例え.jp` | Valid, normalized URL |
| TW-002-13 | Handles long URL (2000+ chars) | `https://example.com/${'a'.repeat(2000)}` | Valid, accepts input |

### TW-003: State Management

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| TW-003-01 | Initial state is idle | Component mount | Shows URL input, no loading spinner |
| TW-003-02 | Transitions to loading on submit | Valid URL submitted | Shows spinner, "Analyzing..." text |
| TW-003-03 | Transitions to success on response | API returns results | Shows results, hides spinner |
| TW-003-04 | Transitions to error on failure | API returns error | Shows error message |
| TW-003-05 | Disables input during loading | Loading state | Input disabled, submit button disabled |
| TW-003-06 | Re-enables input after success | Success state | Input enabled for "Analyze Another" |
| TW-003-07 | Re-enables input after error | Error state | Input enabled for retry |
| TW-003-08 | Clears error on new submission | Error state → new submit | Error cleared, loading shown |

### TW-004: Error Handling

| ID | Scenario | Error Code | Expected Message |
|----|----------|------------|------------------|
| TW-004-01 | Invalid URL error | INVALID_URL | "Please enter a valid URL (e.g., https://example.com)" |
| TW-004-02 | Fetch failed error | FETCH_FAILED | "Could not access the page. Please check the URL and try again." |
| TW-004-03 | Timeout error | TIMEOUT | "Analysis took too long. Please try again." |
| TW-004-04 | Rate limited error | RATE_LIMITED | "You've made too many requests. Please wait a moment." |
| TW-004-05 | Unknown error | UNKNOWN | "An unexpected error occurred. Please try again." |
| TW-004-06 | Network offline | NETWORK_ERROR | "Network error. Please check your connection." |
| TW-004-07 | Retry button appears | Any error | Retry button visible |
| TW-004-08 | Retry button triggers re-analysis | Click retry | Loading state, API called again |
| TW-004-09 | Error cleared on successful retry | Retry → success | Error removed, results shown |

### TW-005: Results Display

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| TW-005-01 | Results appear after success | API success response | Results container visible |
| TW-005-02 | Meta tag results format | Meta tag analyzer results | Title, description, warnings shown |
| TW-005-03 | Keyword density results format | Keyword density results | Density percentage, recommendations shown |
| TW-005-04 | Mobile-friendly results format | Mobile-friendly results | Preview, issues list shown |
| TW-005-05 | Social preview results format | Social preview results | Facebook/Twitter cards shown |
| TW-005-06 | Results are scrollable for long content | Large results data | Scrollable container with max-height |
| TW-005-07 | Results show warning icons | Issues found | Warning indicators visible |
| TW-005-08 | Results show success checkmarks | No issues | Success indicators visible |
| TW-005-09 | Empty results handled | Empty data array | "No results found" message |
| TW-005-10 | Results timestamp shown | Any results | "Analyzed at: [timestamp]" visible |

### TW-006: Reset Functionality

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| TW-006-01 | "Analyze Another" button after success | Success state | Button visible with correct text |
| TW-006-02 | Click resets to idle state | Click "Analyze Another" | Returns to idle state |
| TW-006-03 | URL input cleared on reset | Click "Analyze Another" | Input field empty |
| TW-006-04 | Results cleared on reset | Click "Analyze Another" | Results container hidden |
| TW-006-05 | Error cleared on reset | Error state → reset | Error message removed |
| TW-006-06 | Focus returns to input on reset | Click "Analyze Another" | URL input focused |
| TW-006-07 | Button does not appear during loading | Loading state | "Analyze Another" button hidden |
| TW-006-08 | Button does not appear during error | Error state | "Analyze Another" button hidden (use retry instead) |

### TW-007: Responsive Design

| ID | Scenario | Viewport | Expected Output |
|----|----------|----------|-----------------|
| TW-007-01 | Mobile layout (320px) | 320x568 | Full-width input, stacked results |
| TW-007-02 | Mobile landscape | 568x320 | Compact layout, readable |
| TW-007-03 | Tablet portrait | 768x1024 | Centered container, 2-column results possible |
| TW-007-04 | Tablet landscape | 1024x768 | Full layout with side margins |
| TW-007-05 | Desktop (1280px) | 1280x720 | Centered container, max-width applied |
| TW-007-06 | Large desktop (1920px) | 1920x1080 | Max-width container, not stretched |
| TW-007-07 | Input field responsive | Mobile | Full-width, touch-friendly (44px min height) |
| TW-007-08 | Button responsive | Mobile | Full-width, touch-friendly (44px min height) |
| TW-007-09 | Results container responsive | All viewports | Scrollable, readable text |
| TW-007-10 | No horizontal overflow | All viewports | No horizontal scroll |

### TW-008: Accessibility

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| TW-008-01 | Input has accessible label | Component render | `<label>` or `aria-label` present |
| TW-008-02 | Input has aria-describedby | Component render | Linked to help text/error message |
| TW-008-03 | Error message has role="alert" | Error state | Screen reader announces error |
| TW-008-04 | Loading spinner has aria-busy | Loading state | `aria-busy="true"` on container |
| TW-008-05 | Results region has aria-live | Success state | Screen reader announces results |
| TW-008-06 | Keyboard navigation | Tab key | Focus moves through interactive elements |
| TW-008-07 | Enter key submits form | Enter in input | Form submitted, analysis starts |
| TW-008-08 | Escape key cancels (if loading) | Escape during loading | Analysis cancelled (if implemented) |
| TW-008-09 | Focus visible on all interactive elements | Tab through | Focus outline visible |
| TW-008-10 | Color contrast meets WCAG AA | All states | Minimum 4.5:1 contrast ratio |
| TW-008-11 | Focus trap in modal (if used) | Modal open | Tab cycles within modal |
| TW-008-12 | Screen reader announces state changes | Any state change | State announced via aria-live |

### TW-009: Security

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| TW-009-01 | XSS in URL input | `<script>alert(1)</script>` | Sanitized, no script execution |
| TW-009-02 | XSS in results data | Malicious HTML in API response | Sanitized before render |
| TW-009-03 | XSS in error message | `<img onerror=alert(1)>` in error | Sanitized, no execution |
| TW-009-04 | No eval or innerHTML | Code review | No dangerous DOM APIs used |
| TW-009-05 | URL protocol validation | `javascript:alert(1)` | Rejected before fetch |
| TW-009-06 | CSRF token included (if needed) | API request | Token in headers (if required) |

### TW-010: Performance

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| TW-010-01 | Component renders < 100ms | Initial render | No performance warning |
| TW-010-02 | State transition < 50ms | Idle → Loading | Smooth transition |
| TW-010-03 | Results render < 200ms | Large results data | No blocking |
| TW-010-04 | No memory leaks on unmount | Mount → Unmount | Event listeners cleaned up |
| TW-010-05 | Debounced input validation | Rapid typing | Validation debounced (300ms) |

---

## Mock Data

### Valid Meta Tag Analyzer Response
```json
{
  "success": true,
  "data": {
    "url": "https://example.com",
    "title": "Example Domain",
    "titleLength": 14,
    "titleWarnings": ["Title is too short (recommended: 50-60 characters)"],
    "description": "This domain is for use in illustrative examples...",
    "descriptionLength": 52,
    "descriptionWarnings": [],
    "canonical": "https://example.com/",
    "robots": "index, follow",
    "ogTitle": "Example Domain",
    "ogDescription": "This domain is for use in illustrative examples...",
    "ogImage": null,
    "analyzedAt": "2026-02-27T15:40:00Z"
  }
}
```

### Valid Keyword Density Response
```json
{
  "success": true,
  "data": {
    "url": "https://example.com",
    "keyword": "example",
    "totalCount": 5,
    "density": 1.2,
    "placements": {
      "title": true,
      "headings": true,
      "firstParagraph": true,
      "metaDescription": false
    },
    "recommendations": [
      "Consider adding the keyword to your meta description"
    ],
    "analyzedAt": "2026-02-27T15:40:00Z"
  }
}
```

### Error Response
```json
{
  "success": false,
  "error": {
    "code": "FETCH_FAILED",
    "message": "Could not access the page. Please check the URL and try again."
  }
}
```

### Rate Limited Response
```json
{
  "success": false,
  "error": {
    "code": "RATE_LIMITED",
    "message": "You've made too many requests. Please wait a moment.",
    "retryAfter": 60
  }
}
```

---

## Coverage Targets

| Category | Target Coverage |
|----------|-----------------|
| Statements | 85% |
| Branches | 80% |
| Functions | 90% |
| Lines | 85% |

---

## Test Execution

```bash
# Run all tests
npm test

# Run specific test file
npm test -- ToolWidget.test.tsx

# Run with coverage
npm run test:coverage

# Run integration tests
npm run test:integration
```

---

## Dependencies

- TASK-16-1 (Infrastructure - API routes)
- TASK-16-2 (Meta Tag Analyzer)
- TASK-16-3 (Keyword Density Checker)

---

## Notes for Developer

1. **Component Props Interface:**
```typescript
interface ToolWidgetProps {
  tool: 'meta-tag-analyzer' | 'keyword-density' | 'mobile-friendly' | 'social-preview';
  title: string;
  description: string;
  placeholder?: string;
  showHelp?: boolean;
}
```

2. **State Machine:**
```
idle → loading → success → idle (via reset)
                 ↘ error → loading (via retry)
                         ↘ idle (via reset)
```

3. **API Endpoint:**
- `POST /api/tools/analyze`
- Body: `{ tool: string, url: string, keyword?: string }`

4. **Error Messages (use exact text):**
| Code | Message |
|------|---------|
| INVALID_URL | "Please enter a valid URL (e.g., https://example.com)" |
| FETCH_FAILED | "Could not access the page. Please check the URL and try again." |
| TIMEOUT | "Analysis took too long. Please try again." |
| RATE_LIMITED | "You've made too many requests. Please wait a moment." |

5. **Accessibility Requirements:**
- All interactive elements must be keyboard accessible
- Use semantic HTML (`<form>`, `<button>`, `<label>`)
- ARIA attributes for dynamic content
- Focus management on state changes

---

## Comments

### [2026-02-27 15:40] Agent: PreTester
Created test documentation for ToolWidget component. Test coverage designed for:
- Component rendering (9 scenarios)
- URL validation (13 scenarios)
- State management (8 scenarios)
- Error handling (9 scenarios)
- Results display (10 scenarios)
- Reset functionality (8 scenarios)
- Responsive design (10 scenarios)
- Accessibility (12 scenarios)
- Security (6 scenarios)
- Performance (5 scenarios)

Total: 90 test scenarios covering all acceptance criteria.

Status: Tests Coverage In Progress
