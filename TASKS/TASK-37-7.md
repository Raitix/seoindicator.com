# TASK-37-7: Indicators Button Fix

## STATUS
`To Write Tests`

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
- [ ] "Indicators" button is a clickable link
- [ ] Clicking navigates to appropriate page
- [ ] Button has proper hover state
- [ ] Button shows pointer cursor
- [ ] Accessible via keyboard navigation
- [ ] Unit tests for button behavior

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

### ProjectManager Assessment (2026-03-16 15:11 GMT+2)
- **Priority:** HIGH (confirmed) — User-facing navigation issue affecting core UX. Users cannot access indicators dashboard.
- **Risk:** LOW (confirmed) — Simple frontend-only fix. Single file modification, no backend changes, no external APIs.
- **Dependencies:** None — Independent task, can proceed immediately.
- **Effort:** XSmall (0.5h) — Straightforward conversion from button to Next.js Link component.
- **Status Changed:** `To Prioritize` → `To Write Tests`
- **Ready for:** PreTester (test design with mocked Link component behavior)
