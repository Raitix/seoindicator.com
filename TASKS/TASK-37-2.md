# TASK-37-2: Fix Header Navigation

## STATUS
`To Prioritize`

## PRIORITY
`High`

## RISK
`Medium`

## Specification
SPEC-037

## Description
Fix the header navigation buttons to be clickable and have proper hover states. Currently "Sign In" and "Indicators" buttons are not functional, and hover states are broken (white-on-white).

## Issues
1. "Sign In" button is a `<button>` element with no onClick handler
2. "Indicators" button is a `<button>` element with no onClick handler
3. Nav buttons have white background on hover, making white text invisible
4. Nav buttons don't show pointer cursor
5. Protected routes (Dashboard, Competitors, Comparison) visible to guests

## Solution

### Fix Button Clickability
Convert buttons to links or add proper handlers:
- "Sign In" → Link to `/login/`
- "Indicators" → Link to `/dashboard/` or `/indicators/`

### Fix Hover States
Change hover class from `hover:bg-white` to `hover:bg-slate-700`

### Fix Cursor
Add `cursor-pointer` class to nav buttons

### Conditional Rendering
Use `isAuthenticated` to hide protected nav items from guests

## Acceptance Criteria
- [ ] "Sign In" button navigates to /login/
- [ ] "Indicators" button is clickable and navigates appropriately
- [ ] Nav buttons show pointer cursor on hover
- [ ] Nav buttons have visible hover state (dark background, white text)
- [ ] Dashboard nav item hidden from unauthenticated users
- [ ] Competitors nav item hidden from unauthenticated users
- [ ] Comparison nav item hidden from unauthenticated users
- [ ] Unit tests for navigation behavior

## Technical Details

### File to Modify
`frontend/components/layout/Header.tsx`

### Code Changes
```tsx
// Before
<button className="...">Sign In</button>

// After
<Link href="/login" className="... cursor-pointer hover:bg-slate-700">
  Sign In
</Link>

// Conditional rendering for protected items
{isAuthenticated && (
  <Link href="/dashboard" className="...">Dashboard</Link>
)}
```

## Dependencies
- None

## Effort Estimate
`Small` (1-2 hours)

## Related Issues
- FEAT-037
- SPEC-037
