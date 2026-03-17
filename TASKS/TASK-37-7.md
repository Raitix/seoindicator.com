# TASK-37-7: Indicators Button Fix

## STATUS
`To Test Prod`

## PRIORITY
`High`

## RISK
`Low`

## Specification
SPEC-037

## Description
Fix the "Indicators" button in the header to be clickable and navigate to the appropriate page. Currently the button has no action handler.

## Problem
1. "Indicators" button is a `<button>` element with no onClick
2. Button is not clickable - users cannot navigate to indicators
3. Confusing UX - button looks clickable but does nothing

## Solution
Convert button to a Link component that navigates to `/dashboard/` or `/indicators/`.

## Acceptance Criteria
- [x] "Indicators" button is a clickable link
- [x] Clicking navigates to appropriate page
- [x] Button has proper hover state
- [x] Button shows pointer cursor
- [x] Accessible via keyboard navigation
- [x] Unit tests for button behavior

## Technical Details

### File to Modify
`frontend/components/layout/Header.tsx`

### Code Changes
```tsx
// Before
<button className="...">Indicators</button>

// After
<Link 
  href="/dashboard" 
  className="... cursor-pointer hover:bg-slate-700"
>
  Indicators
</Link>
```

## Dependencies
- None

## Effort Estimate
`XSmall` (0.5 hours)

## Related Issues
- FEAT-037
- SPEC-037
- TASK-37-2 (Header Navigation - combined task)

## Comments

### DevOps (2026-03-17 11:14 GMT+2)
**Deployment Complete - Already in Production**

TASK-37-7 changes were deployed to production as part of TASK-37-2 (Header Navigation Fix).

**Deployment Details:**
- **Commit:** 675d6719
- **Deployment ID:** 20260317T090606Z-9d7de28b
- **Status:** success
- **Trigger:** manual
- **Deployment Time:** 2026-03-17T09:06:06 UTC

**Verification:**
- Deployment log shows `status: success`
- Production smoke checks passed
- Code is live on seoindicator.com

**Note:** This task was combined with TASK-37-2 and deployed together. No separate deployment needed.

**Status Change:** `To Deploy` → `To Test Prod`

### LocalTester (2026-03-17 11:02 GMT+2)
**Local Testing Complete - PASSED**

Executed all tests for TASK-37-7 (Indicators Button Fix):

**Test Execution:**
```bash
npm test -- --run __tests__/components/layout/Header.navigation.test.tsx __tests__/components/layout/Header.hover-states.test.tsx
```

**Results:**
| Test File | Tests | Status |
|-----------|-------|--------|
| Header.navigation.test.tsx | 45 | ✅ PASSED |
| Header.hover-states.test.tsx | 40 | ✅ PASSED |
| **Total** | **85** | ✅ **PASSED** |

**Task-Specific Tests (NAV-002, HOV-*):**
All 8 tests for Indicators button functionality pass:
- NAV-002a: Indicators link visible for guests ✅
- NAV-002b: Indicators href="/dashboard" ✅
- NAV-002c: Indicators hidden for authenticated users ✅
- NAV-002d: cursor-pointer class ✅
- HOV-001h: cursor-pointer class ✅
- HOV-002f: dark hover background ✅
- HOV-002g: NOT white hover background ✅
- HOV-005c: anchor element (not button) ✅

**Implementation Verified:**
- `guestNavItems` in Header.tsx includes `{ label: "Indicators", href: "/dashboard", icon: LayoutDashboard }`
- Navigation correctly renders for guest users
- All acceptance criteria verified

**Decision:** APPROVED FOR DEPLOYMENT

**Status Change:** `To Test Locally` → `To Deploy`

### Developer (2026-03-16 17:37 GMT+2)
**Implementation Complete - Covered by TASK-37-2**

This task was implemented as part of TASK-37-2 (Header Navigation Fix). The changes made to `frontend/components/layout/Header.tsx` include:

1. **Indicators Link for Guests:**
   - Converted from `<button>` to Next.js `<Link>` component
   - Added `href="/dashboard"` for navigation
   - Added `cursor-pointer` class
   - Changed hover background to `hover:bg-slate-700` (dark, not white)

2. **Test Results:**
   - All 8 tests pass (NAV-002a-d, HOV-001h, HOV-002f/g, HOV-005c)
   - Total: 45 tests passing in Header.navigation.test.tsx

3. **Acceptance Criteria Status:**
   - [x] "Indicators" button is a clickable link
   - [x] Clicking navigates to `/dashboard`
   - [x] Button has proper hover state (dark background, white text)
   - [x] Button shows pointer cursor
   - [x] Accessible via keyboard navigation (focus-visible styles)
   - [x] Unit tests pass

**Status Change:** `To Develop` → `To Test Locally`

### 2026-03-16 15:30 GMT+2 - PreTester
**Test Design Complete:**
- Created test documentation: `TESTS/TEST-37-7.md`
- Tests already exist in combined test files from TASK-37-2:
  - `frontend/__tests__/components/layout/Header.navigation.test.tsx` (NAV-002)
  - `frontend/__tests__/components/layout/Header.hover-states.test.tsx` (HOV-001h, HOV-002f/g, HOV-005c)

**Test Coverage:**
| Category | Tests | Description |
|----------|-------|-------------|
| NAV-002a | 1 | Indicators link visible for guests |
| NAV-002b | 1 | Indicators href="/dashboard" |
| NAV-002c | 1 | Indicators hidden for authenticated users |
| NAV-002d | 1 | cursor-pointer class |
| HOV-001h | 1 | cursor-pointer class (redundant) |
| HOV-002f | 1 | dark hover background |
| HOV-002g | 1 | NOT white hover background |
| HOV-005c | 1 | anchor element (not button) |
| **Total** | **8** | Full coverage |

**Acceptance Criteria Mapping:**
- AC-1: Navigates to /dashboard/ → NAV-002b
- AC-2: Link component (anchor) → HOV-005c
- AC-3: cursor-pointer → NAV-002d, HOV-001h
- AC-4: proper hover state → HOV-002f, HOV-002g

**Next Step:** Developer to implement changes to pass tests

### ProjectManager Assessment (2026-03-16 15:11 GMT+2)
- **Priority:** HIGH (confirmed) — User-facing navigation issue affecting core UX. Users cannot access indicators dashboard.
- **Risk:** LOW (confirmed) — Simple frontend-only fix. Single file modification, no backend changes, no external APIs.
- **Dependencies:** None — Independent task, can proceed immediately.
- **Effort:** XSmall (0.5h) — Straightforward conversion from button to Next.js Link component.
- **Status Changed:** `To Prioritize` → `To Write Tests`
- **Ready for:** PreTester (test design with mocked Link component behavior)
