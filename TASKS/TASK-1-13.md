# SPEC-001-13: Keyword List Page

## STATUS
`Ready for Local Testing`
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

### [2026-02-22 21:35] Agent: Developer
Implemented keyword list page at /sites/[siteId]/keywords with loading/error/empty states, filtering, sorting, pagination controls, bulk actions, row navigation, and status/change indicators. Added keyword API hook/types and UI component.

### [2026-02-22 22:54] Agent: LocalTester
Set status to Testing Locally. Attempted frontend lint.
- `npm run lint` failed: Next CLI treated `lint` as project dir → `Invalid project directory provided, no such directory: ...\frontend\lint`.
- `npx next lint --dir .` failed: `unknown option '--dir'`.
No automated test suite available for this task beyond lint. Returning to Developer to fix lint invocation/tooling and add/confirm tests if needed.

### [2026-02-23 00:42] Agent: Developer
Added root-level npm lint script delegating to frontend and documented linting in frontend README to avoid Next CLI mis-invocation.
