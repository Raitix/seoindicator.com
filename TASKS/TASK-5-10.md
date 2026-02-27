# TASK-5-10: Suggestions List Page

## STATUS
`To Write Tests`

## PRIORITY
`Medium`

## RISK
`Low`

## Description
Create the main suggestions list page where users can view and manage their keyword suggestions.

## Acceptance Criteria
- [ ] Page: /sites/:siteId/suggestions
- [ ] Table showing: keyword, source, opportunity score, tracking status
- [ ] Sortable by: opportunity score, keyword, source
- [ ] Filterable by: source (suggest, question), tracking status
- [ ] Pagination with 20 items per page
- [ ] Bulk actions: Track Selected, Dismiss Selected
- [ ] Individual actions: Track, Dismiss
- [ ] Empty state when no suggestions
- [ ] Loading state during API calls

## Dependencies
- TASK-5-7 (Suggestions CRUD API)
- TASK-5-9 (Track/Dismiss endpoints)

## Effort Estimate
`Medium` (4-6 hours)

## Technical Notes
- Use existing UI components (table, pagination, filters)
- Follow existing page patterns in project
- Responsive design for mobile

---

## Comments
_Agent communication log_

### [2026-02-27 17:52] Agent: PreTester
**Test Design Complete**

Created comprehensive test file for SuggestionsPage component:
- **Test File**: `frontend/__tests__/app/sites/[siteId]/suggestions/SuggestionsPage.test.tsx`
- **Test Count**: 35+ test scenarios

**Test Coverage:**
- Basic rendering (5 tests): heading, table, filters, pagination
- Table content (4 tests): keyword, source, score, status display
- Filtering (3 tests): by source, by tracking status
- Sorting (2 tests): opportunity score, keyword
- Selection (3 tests): individual, select all, deselect all
- Bulk actions (3 tests): track selected, dismiss selected
- Individual actions (3 tests): track, dismiss, no actions for tracked
- Empty state (1 test): no suggestions display
- Pagination (3 tests): controls, disabled prev, navigation
- Loading state (2 tests): show/hide overlay
- Accessibility (3 tests): table, dropdowns, checkboxes
- Adversarial (3 tests): XSS, rapid changes, selection stress

**TDD Status:** Tests mock the component - waiting for Developer implementation

**Dependencies Status:**
- TASK-5-7 (Suggestions CRUD API): Done ✓
- TASK-5-9 (Track/Dismiss endpoints): Testing Prod

Status: Tests Coverage In Progress → To Develop (waiting for frontend implementation)

### [2026-02-26 22:42] Agent: ProjectManager
Created task from SPEC-005 breakdown. Priority: Medium (UI layer). Risk: Low (frontend-only, no backend changes). Dependencies: TASK-5-7, TASK-5-9.
