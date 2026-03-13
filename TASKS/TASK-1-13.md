# SPEC-001-13: Keyword List Page

## STATUS
`In Deployment`
## PRIORITY
`High`

## RISK
`Low`

## Description
Create the keyword list page showing all tracked keywords with their current positions, changes, and status.

## Acceptance Criteria
- [x] Keywords table with columns: keyword, position, change, last checked, status
- [x] Position change indicators (arrows, colors: green/red)
- [x] Filter by status (active, paused, all)
- [x] Sort by keyword, position, change, last checked
- [x] Pagination (20 per page)
- [x] "Add Keyword" button links to add form
- [x] Click keyword row to view details/history
- [x] Bulk actions: pause, resume, delete
- [x] Loading states and error handling
- [x] Responsive design (mobile-friendly table)

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
- `npm run lint` failed: Next CLI treated `lint` as project dir â†’ `Invalid project directory provided, no such directory: ...\frontend\lint`.
- `npx next lint --dir .` failed: `unknown option '--dir'`.
No automated test suite available for this task beyond lint. Returning to Developer to fix lint invocation/tooling and add/confirm tests if needed.

### [2026-02-23 00:42] Agent: Developer
Added root-level npm lint script delegating to frontend and documented linting in frontend README to avoid Next CLI mis-invocation.

### [2026-03-13 18:12] Agent: LocalTester
**LOCAL TESTING PASSED**

**Test Results:**
- KeywordFilterBar tests: 23/23 passed (590ms)
- Build: SUCCESS (exit code 0)
- Lint: No errors in keyword components

**Acceptance Criteria Verification:**
1. ✅ Keywords table with columns: keyword, position, change, last checked, status
2. ✅ Position change indicators (arrows, colors: green/red) - `getChangeBadge()` with ArrowUp/ArrowDown icons, emerald-300/rose-300 colors
3. ✅ Filter by status (active, paused, all) - KeywordFilterBar status dropdown
4. ✅ Sort by keyword, position, change, last checked - KeywordFilterBar sortBy control
5. ✅ Pagination (20 per page) - `const PER_PAGE = 20` with prev/next controls
6. ✅ "Add Keyword" button links to add form - `/sites/${siteId}/keywords/new`
7. ✅ Click keyword row to view details/history - row onClick navigates to `/sites/${siteId}/keywords/${keyword.id}`
8. ✅ Bulk actions: pause, resume, delete - `handleBulkAction()` with all three actions
9. ✅ Loading states and error handling - Loader2 spinner, error message with retry button
10. ✅ Responsive design (mobile-friendly table) - `overflow-x-auto` wrapper

**Status changed:** Testing Locally → To Deploy
