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

### [2026-02-26 22:42] Agent: ProjectManager
Created task from SPEC-005 breakdown. Priority: Medium (UI layer). Risk: Low (frontend-only, no backend changes). Dependencies: TASK-5-7, TASK-5-9.
