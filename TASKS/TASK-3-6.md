# SPEC-003-6: Frontend - Competitor Management UI

## STATUS
`To Develop`

## PRIORITY
`Medium`

## RISK
`Low`

## Description
Create frontend UI for managing competitors - add, edit, delete, and list competitors. Part of the competitor comparison feature.

## Acceptance Criteria
- [x] Competitor list page showing all tracked competitors
- [x] Add competitor modal/form with domain, displayName, notes fields
- [x] Edit competitor functionality (displayName, notes only - domain immutable)
- [x] Delete competitor with confirmation dialog
- [x] Domain validation feedback (real-time format check)
- [x] Display competitor stats: tracked keywords count
- [x] Competitor limit indicator (e.g., "2/3 competitors used")
- [x] Responsive design (mobile-friendly)
- [x] Error handling for API failures
- [x] Loading states for all async operations

## Dependencies
- SPEC-003-2 (CRUD API must exist)

## Effort Estimate
`Medium` (4-6 hours)

## Technical Notes
- Use Shadcn/UI components (Dialog, Form, Button, Card)
- Use React Hook Form for form handling
- Use SWR or React Query for data fetching
- Add to existing settings/navigation structure

---

## Comments
_Agent communication log_

### [2026-02-20 09:52] Agent: PreTester
**Test Design Complete**

**Tests Created:**
1. `TESTS/SPEC-003-6.md` - Test documentation (20 test cases)
   - CompetitorList component (render, empty state, limit indicator)
   - AddCompetitorModal (validation, submit, loading, error)
   - EditCompetitorModal (domain immutable, update)
   - DeleteCompetitorDialog (confirmation, cancel/confirm)
   - DomainValidation (real-time, SSRF blocking)
   - Responsive design tests

**Testing Framework Required:**
- Jest + React Testing Library (not yet installed)
- Add to frontend/package.json

**Developer Requirements:**
- Components: CompetitorList, AddCompetitorModal, EditCompetitorModal, DeleteCompetitorDialog
- API hooks: useCompetitors, useCreateCompetitor, useUpdateCompetitor, useDeleteCompetitor

**Handoff:** → Developer for implementation

### [2026-02-20 00:42] Agent: ProjectManager
**Prioritization Complete**
- Priority: Medium ✓ (UI task, can be developed in parallel with backend)
- Risk: Low ✓ (standard CRUD UI)
- Dependency: SPEC-003-2 (API must exist)
- **Handoff:** → PreTester

### [2026-02-19 23:24] Agent: SpecSystemAnalyst
Created task for competitor management UI. Standard CRUD UI with good UX. Can be developed in parallel with backend comparison engine.

### [2026-02-22 18:25] Agent: Developer
Implemented competitor management UI with SWR hooks, CRUD modals, and validation.
- Added competitor components (list, card, add/edit/delete dialogs) with loading/error states.
- Added domain sanitizer/validator (SSRF/local IP blocking) and React Hook Form usage.
- Added competitors page + nav link from home.
- Added API hooks + types and shadcn dialog/label/textarea UI.
- Updated frontend deps: swr, react-hook-form, radix dialog/label.
Status moved to **To Test Locally**.

### [2026-02-22 23:35] Agent: LocalTester
**Testing Locally → FAIL (blocked by missing test setup)**
- No Jest/RTL configuration or test scripts in `frontend/package.json` (only dev/build/lint/start).
- No task-specific test files present (expected in `frontend/__tests__/components/*Competitor*.test.tsx`).
- Unable to execute automated tests per `TESTS/SPEC-003-6.md`.

**Request to Developer:**
1. Add Jest + React Testing Library devDependencies and `test` scripts per `TESTS/SPEC-003-6.md`.
2. Implement component/integration tests for competitor UI.
3. Reassign to LocalTester for execution.

Status returned to **To Develop**.
