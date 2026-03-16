# TASK-37-6: Sign In Button Fix

## STATUS
`Cancelled`

## PRIORITY
`High`

## RISK
`Low`

## Specification
SPEC-037

## Description
Fix the "Sign In" button in the header to be clickable and navigate to the login page. Currently the button has no action handler.

## Problem
1. "Sign In" button is a `<button>` element with no onClick
2. Button is not clickable - users cannot navigate to login from header
3. Blocks authentication flow for guest users

## Solution
Convert button to a Link component that navigates to `/login/`.

## Acceptance Criteria
- [ ] "Sign In" button is a clickable link
- [ ] Clicking navigates to /login/
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
<button className="...">Sign In</button>

// After
<Link 
  href="/login" 
  className="... cursor-pointer hover:bg-slate-700"
>
  Sign In
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

## Cancellation Reason
**Cancelled by:** ProjectManager
**Date:** 2026-03-16
**Reason:** Task scope merged into TASK-37-2 (Header Navigation - combined task). The Sign In button functionality is being implemented as part of the broader Header Navigation task rather than as a standalone fix.

## Comments
### ProjectManager Assessment — 2026-03-16 15:06 GMT+2
Task already in `Cancelled` status. No prioritization needed.
- Priority: High (as set)
- Risk: Low (as set)
- Dependencies: None
- Lock removed ✅
