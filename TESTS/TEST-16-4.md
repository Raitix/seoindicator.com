# TEST-16-4: Tool Embedding System Tests

## STATUS
`Tests Coverage In Progress`

## Related
- Task: [TASK-16-4](../TASKS/TASK-16-4.md)
- Feature: [FEAT-016](../FEATURES/FEAT-016.md)

---

## Test Coverage Summary

| Category | Tests | Assertions | Status |
|----------|-------|------------|--------|
| ToolWidget Component | 15 | 15 | ✅ |
| URL Input Handling | 12 | 12 | ✅ |
| Loading States | 8 | 8 | ✅ |
| Error Display | 10 | 10 | ✅ |
| Result Rendering | 12 | 12 | ✅ |
| Responsive Design | 6 | 6 | ✅ |
| Embedding Integration | 8 | 8 | ✅ |
| **Total** | **71** | **71** | ✅ |

---

## Test Scenarios

### ToolWidget Component (WIDGET)

| ID | Scenario | Props | Expected |
|----|----------|-------|----------|
| WIDGET-001 | Renders with title | title="Test" | Shows title |
| WIDGET-002 | Renders with description | description="Desc" | Shows desc |
| WIDGET-003 | Tool configuration | tool="meta-tag-analyzer" | Correct tool |
| WIDGET-004 | Placeholder text | placeholder="Enter URL" | Placeholder shown |
| WIDGET-005 | Help toggle | showHelp=true | Help visible |
| WIDGET-006 | No help by default | showHelp=false | Help hidden |
| WIDGET-007 | Analyze button | Valid URL | Button enabled |
| WIDGET-008 | Disabled button | Invalid URL | Button disabled |
| WIDGET-009 | Custom styling | className prop | Styles applied |
| WIDGET-010 | Event handlers | onAnalyze callback | Handler called |
| WIDGET-011 | Accessibility | ARIA attributes | Proper labels |
| WIDGET-012 | Keyboard navigation | Tab/Enter | Works correctly |
| WIDGET-013 | Focus management | After analysis | Focus on results |
| WIDGET-014 | Reset functionality | Analyze Another | Resets form |
| WIDGET-015 | Multiple instances | Two widgets | Independent state |

### URL Input Handling (URL)

| ID | Scenario | Input | Expected |
|----|----------|-------|----------|
| URL-001 | Valid URL | https://example.com | Accepted |
| URL-002 | Invalid URL | not-a-url | Error shown |
| URL-003 | Empty input | "" | Error shown |
| URL-004 | With protocol | http://example.com | Accepted |
| URL-005 | Without protocol | example.com | Auto-add https |
| URL-006 | Trim whitespace | " example.com " | Trimmed |
| URL-007 | URL validation | Real-time | Shows validation |
| URL-008 | Blocked URL | localhost | Error shown |
| URL-009 | Very long URL | 2000+ chars | Handled |
| URL-010 | Special characters | URL with ?& | Accepted |
| URL-011 | Unicode domain | 例え.jp | Converted |
| URL-012 | Paste support | Ctrl+V | Works |

### Loading States (LOAD)

| ID | Scenario | Condition | Expected |
|----|----------|-----------|----------|
| LOAD-001 | Idle state | Initial | Input visible |
| LOAD-002 | Loading state | During fetch | Spinner shown |
| LOAD-003 | Loading text | During fetch | "Analyzing..." |
| LOAD-004 | Progress indicator | Long request | Shows progress |
| LOAD-005 | Cancel button | During load | Cancel option |
| LOAD-006 | Timeout handling | 30s timeout | Error shown |
| LOAD-007 | Success state | After fetch | Results shown |
| LOAD-008 | Error state | Fetch failed | Error shown |

### Error Display (ERR)

| ID | Scenario | Error Code | Expected Message |
|----|----------|------------|------------------|
| ERR-001 | Invalid URL | INVALID_URL | "Please enter a valid URL..." |
| ERR-002 | URL blocked | URL_BLOCKED | "URL not allowed..." |
| ERR-003 | Fetch failed | FETCH_FAILED | "Could not access the page..." |
| ERR-004 | Timeout | TIMEOUT | "Analysis took too long..." |
| ERR-005 | Rate limited | RATE_LIMITED | "Too many requests..." |
| ERR-006 | Parse error | PARSE_ERROR | "Could not parse page..." |
| ERR-007 | Tool error | TOOL_ERROR | "Analysis failed..." |
| ERR-008 | Network error | NETWORK_ERROR | "Network error..." |
| ERR-009 | Retry button | Any error | "Try Again" button |
| ERR-010 | Error icon | Any error | Warning icon shown |

### Result Rendering (RES)

| ID | Scenario | Result Type | Expected |
|----|----------|-------------|----------|
| RES-001 | Title result | Meta Tag | Shows title value |
| RES-002 | Warning badges | Warnings | Badge styling |
| RES-003 | SERP preview | Meta Tag | Preview card |
| RES-004 | Density meter | Keyword | Visual meter |
| RES-005 | Placement checkmarks | Keyword | ✓ for present |
| RES-006 | Recommendations | Both | Bullet list |
| RES-007 | Copy button | Any result | Copy to clipboard |
| RES-008 | Export option | Any result | Export button |
| RES-009 | Collapsible sections | Large result | Expand/collapse |
| RES-010 | Tabs for multiple | Multiple tools | Tab navigation |
| RES-011 | Empty result | No data | "No issues found" |
| RES-012 | Long content | Many warnings | Scrollable |

### Responsive Design (RESP)

| ID | Scenario | Viewport | Expected |
|----|----------|----------|----------|
| RESP-001 | Desktop | > 1024px | Full layout |
| RESP-002 | Tablet | 768-1024px | Adapted layout |
| RESP-003 | Mobile | < 768px | Stacked layout |
| RESP-004 | Input width | Mobile | Full width |
| RESP-005 | Results width | Mobile | Full width |
| RESP-006 | Touch targets | Mobile | 48px min |

### Embedding Integration (EMBED)

| ID | Scenario | Location | Expected |
|----|----------|----------|----------|
| EMBED-001 | In guide page | /seo-guides/meta-description | Widget visible |
| EMBED-002 | In tools page | /tools/meta-tag-analyzer | Widget visible |
| EMBED-003 | Multiple tools | Same page | Independent |
| EMBED-004 | Deep linking | URL with ?url= | Pre-filled |
| EMBED-005 | Context aware | Guide about meta | Relevant tool |
| EMBED-006 | Analytics events | User interaction | Tracked |
| EMBED-007 | Session storage | Previous results | Restored |
| EMBED-008 | URL parameters | Share URL | Works |

---

## Test Files

| File | Location | Tests |
|------|----------|-------|
| ToolWidget.test.tsx | `frontend/__tests__/components/tools/` | 71 |

---

## Completion Date
2026-02-27
