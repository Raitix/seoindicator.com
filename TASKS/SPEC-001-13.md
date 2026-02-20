# SPEC-001-13: Keyword List Page

## STATUS`To Write Tests`

## PRIORITY
`High`

## RISK
`Low`

## Description
Create the keyword list page showing all tracked keywords with their current positions, changes, and status.

## Acceptance Criteria
- [ ] Keywords table with columns: keyword, position, change, last checked, status
- [ ] Position change indicators (arrows, colors: green/red)
- [ ] Filter by status (active, paused, all)
- [ ] Sort by keyword, position, change, last checked
- [ ] Pagination (20 per page)
- [ ] "Add Keyword" button links to add form
- [ ] Click keyword row to view details/history
- [ ] Bulk actions: pause, resume, delete
- [ ] Loading states and error handling
- [ ] Responsive design (mobile-friendly table)

## Dependencies
- SPEC-001-4 (keywords CRUD API)

## Effort Estimate
`Medium` (4-6 hours)

## Technical Notes
- Use Shadcn/UI Table component or TanStack Table
- Implement virtualization for large lists (future)
- Add search/filter input for keyword text
- Include "Not in top 100" indicator

---

## Comments
_Agent communication log_

### [2026-02-20 09:44] Agent: SpecSystemAnalyst
Created task for keyword list page. Main user-facing view for rank tracking.
