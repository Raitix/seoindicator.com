# TEST-1-19: E2E Tests for Critical Flows

## Task Reference
- Task: [TASK-1-19](../TASKS/TASK-1-19.md)
- Priority: Medium
- Risk: Low
- Status: Complete

## Test Scenarios

### E2E: Add Keyword Flow
| ID | Step | Action | Expected Result |
|----|------|--------|-----------------|
| E2E-001 | 1 | Navigate to keywords list | List visible |
| E2E-001 | 2 | Click "Add keyword" | Form displayed |
| E2E-001 | 3 | Enter keyword text | Text in field |
| E2E-001 | 4 | Enter target URL | URL in field |
| E2E-001 | 5 | Click "Add keyword" button | Success toast |
| E2E-001 | 6 | Verify success message | Toast visible |

### E2E: View Keyword List Flow
| ID | Step | Action | Expected Result |
|----|------|--------|-----------------|
| E2E-002 | 1 | Navigate to keywords list | List visible |
| E2E-002 | 2 | View keywords | Keywords displayed |
| E2E-002 | 3 | View position badges | Positions shown |
| E2E-002 | 4 | View change indicators | Changes shown |

### E2E: Navigate to Keyword Detail Flow
| ID | Step | Action | Expected Result |
|----|------|--------|-----------------|
| E2E-003 | 1 | Navigate to keywords list | List visible |
| E2E-003 | 2 | Click keyword row | Detail page opens |
| E2E-003 | 3 | Verify URL change | Correct URL pattern |

### E2E: Edit Keyword Flow
| ID | Step | Action | Expected Result |
|----|------|--------|-----------------|
| E2E-004 | 1 | Navigate to keyword edit | Edit form shown |
| E2E-004 | 2 | Verify form populated | Existing data visible |
| E2E-004 | 3 | Change target URL | New URL in field |
| E2E-004 | 4 | Click "Save" | Success toast |

### E2E: Delete Keyword Flow
| ID | Step | Action | Expected Result |
|----|------|--------|-----------------|
| E2E-005 | 1 | Navigate to keywords list | List visible |
| E2E-005 | 2 | Select keyword checkbox | Checkbox checked |
| E2E-005 | 3 | Click delete button | Bulk action triggered |

### E2E: Search and Filter Flow
| ID | Step | Action | Expected Result |
|----|------|--------|-----------------|
| E2E-006 | 1 | Navigate to keywords list | List visible |
| E2E-006 | 2 | Enter search term | Results filtered |
| E2E-006 | 3 | Clear search | All results shown |
| E2E-007 | 1 | Select sort option | Order changed |
| E2E-008 | 1 | Select all checkboxes | All selected |
| E2E-008 | 2 | Click bulk action | Action triggered |

### E2E: Dashboard Flow
| ID | Step | Action | Expected Result |
|----|------|--------|-----------------|
| E2E-050 | 1 | Navigate to dashboard | Summary cards visible |
| E2E-050 | 2 | View total keywords | Count displayed |
| E2E-050 | 3 | View average position | Value displayed |
| E2E-051 | 1 | View position changes | Changes displayed |
| E2E-052 | 1 | View top keywords | Top 5 shown |
| E2E-053 | 1 | View alerts | Alerts shown |
| E2E-054 | 1 | Click keyword list link | Navigate to keywords |
| E2E-055 | 1 | Click add keyword | Navigate to new form |
| E2E-056 | 1 | Click refresh button | Data refreshed |
| E2E-057 | 1 | Toggle auto-refresh | Checkbox toggled |
| E2E-058 | 1 | View loading state | Skeletons shown |

## Test Files
- `frontend/e2e/keyword-flow.spec.ts` - Keyword CRUD operations
- `frontend/e2e/dashboard.spec.ts` - Dashboard functionality
- `frontend/e2e/ranking-history.spec.ts` - Ranking history and detail views

## Running E2E Tests

```bash
# Run all E2E tests
npm run test:e2e

# Run with UI mode for debugging
npm run test:e2e:ui

# Run in debug mode
npm run test:e2e:debug

# View test report
npm run test:e2e:report
```

## Browser Coverage
| Browser | Status |
|---------|--------|
| Chromium | Required ✓ |
| Firefox | Optional |
| WebKit | Optional |

## Screenshot on Failure
- Enabled for debugging
- Saved to `frontend/test-results/`

## CI Integration
Tests run in CI pipeline with:
- Retries: 2
- Video on failure: retained
- Screenshot on failure: enabled
- Trace on retry: enabled

---

## Comments
_Agent communication log_

### [2026-02-24] Developer
Implemented comprehensive E2E tests using Playwright:
- Created `playwright.config.ts` with Chromium support
- Created `e2e/keyword-flow.spec.ts` for keyword CRUD operations
- Created `e2e/dashboard.spec.ts` for dashboard functionality
- Created `e2e/ranking-history.spec.ts` for ranking history views
- All tests use mock API responses for consistency
- Screenshot on failure enabled for debugging
- Tests cover all acceptance criteria from TASK-1-19
