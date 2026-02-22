# SPEC-003-7: Frontend - Comparison Dashboard

## STATUS
`To Develop`

## PRIORITY
`High`

## RISK
`Medium`

## Description
Create the main comparison dashboard showing side-by-side ranking comparisons. Users can see how their rankings compare to competitors with filtering and export options.

## Acceptance Criteria
- [ ] Summary cards showing: Total Keywords, I'm Winning, They're Winning, Keyword Gaps
- [ ] Comparison table with columns: Keyword, My Position, Competitor Positions (with diff), Status
- [ ] Filter dropdown: All, I'm Winning, They're Winning, Keyword Gaps, Both Rank
- [ ] Sort options: by keyword, by my position, by competitor position, by diff
- [ ] Competitor visibility toggle (show/hide specific competitors)
- [ ] Status badges with color coding (green=winning, red=losing, yellow=gap)
- [ ] Pagination or infinite scroll for large keyword sets
- [ ] CSV export functionality
- [ ] Responsive design (table scrolls on mobile)
- [ ] Loading skeleton states

## Dependencies
- SPEC-003-5 (Comparison API must exist)

## Effort Estimate
`Large` (8-12 hours)

## Technical Notes
- Use Shadcn/UI Table component or TanStack Table
- Implement virtualization for large datasets
- Consider chart mini-sparklines in table rows
- Export uses browser download, not server

---

## Comments
_Agent communication log_

### [2026-02-20 09:53] Agent: PreTester
**Test Design Complete**

**Tests Created:**
1. `TESTS/SPEC-003-7.md` - Test documentation (26 test cases)
   - ComparisonDashboard (summary cards, loading, empty state)
   - ComparisonTable (rows, columns, diff, status badges)
   - FilterDropdown (options, filter changes)
   - SortControl (options, sort changes)
   - CompetitorVisibilityToggle (show/hide)
   - Pagination (navigation, indicator)
   - ExportButton (CSV download, respects filters)
   - Responsive design (mobile scroll, stacking)
   - Performance (large dataset, virtualization)

**Testing Framework Required:**
- Jest + React Testing Library (not yet installed)

**Developer Requirements:**
- TanStack Table for virtualization
- Papa Parse for CSV export

**Handoff:** → Developer for implementation

### [2026-02-20 00:42] Agent: ProjectManager
**Prioritization Complete**
- Priority: High ✓ (main user-facing feature)
- Risk: Medium ✓ (complex UI with filtering, sorting, export)
- Dependency: SPEC-003-5 (Comparison API must exist)
- **Effort:** Large (8-12 hours) - plan accordingly
- **Handoff:** → PreTester

### [2026-02-19 23:24] Agent: SpecSystemAnalyst
Created task for comparison dashboard. Main user-facing feature. Large effort due to filtering, sorting, and table complexity.

### [2026-02-22 18:22] Agent: Developer
Status set to In Development. Starting frontend implementation for comparison dashboard (table, filters, export, pagination, loading states).

### [2026-02-22 18:40] Agent: Developer
Implemented comparison dashboard UI:
- New /comparison page with ComparisonDashboard component
- Summary cards, filters, sorting, competitor visibility toggles
- TanStack Table-based table, pagination, responsive overflow
- CSV export via papaparse
- Loading skeleton + empty state
Notes:
- Uses mock data for now (API wiring pending SPEC-003-5)
- Added dependencies: @tanstack/react-table, papaparse
Status moved to To Test Locally.

### [2026-02-22 21:25] Agent: LocalTester
**Local Test Attempt:**
- Ran `npm run lint` in `frontend/` → FAILED
  - Error: `Invalid project directory provided, no such directory: ...\frontend\lint`
- Jest + React Testing Library not installed (per tests doc), so component/integration tests cannot run yet.

**Result:** FAIL (tests not runnable). Returning to Developer.
**Request:** Fix lint config/script and add Jest + React Testing Library; provide runnable tests per `TESTS/SPEC-003-7.md`.

