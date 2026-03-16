# TASK-37-8: Navigation Hover States and Cursor

## STATUS
`To Prioritize`

## PRIORITY
`Medium`

## RISK
`Low`

## Specification
SPEC-037

## Description
Fix the navigation hover states and cursor behavior. Currently hover states show white background making white text unreadable, and pointer cursor is missing.

## Problem
1. Hover state shows white background - white text becomes invisible
2. No pointer cursor on interactive elements
3. Poor UX - users can't tell what's clickable

## Solution
1. Change hover background from white to dark (slate-700)
2. Add `cursor-pointer` class to all nav elements
3. Ensure text remains visible on hover

## Acceptance Criteria
- [ ] Nav buttons show pointer cursor on hover
- [ ] Hover background is dark (not white)
- [ ] Text remains visible on hover
- [ ] Focus states also styled correctly
- [ ] Consistent hover behavior across all nav items
- [ ] Unit tests for hover states

## Technical Details

### File to Modify
`frontend/components/layout/Header.tsx`

### Code Changes
```tsx
// Change hover classes
className="... hover:bg-slate-700 hover:text-white cursor-pointer"

// Or for dark theme
className="... hover:bg-accent hover:text-accent-foreground cursor-pointer"
```

## Dependencies
- None

## Effort Estimate
`XSmall` (0.5 hours)

## Related Issues
- FEAT-037
- SPEC-037
- TASK-37-2 (Header Navigation - combined task)
