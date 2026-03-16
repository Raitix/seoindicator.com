# TASK-37-9: Protected Route Visibility

## STATUS
`To Prioritize`

## PRIORITY
`Medium`

## RISK
`Medium`

## Specification
SPEC-037

## Description
Hide protected navigation items (Dashboard, Competitors, Comparison) from non-authenticated users in the header navigation.

## Problem
1. Dashboard, Competitors, Comparison nav items visible to guests
2. Guests click these items and get errors or redirects
3. Confusing UX - shows options user can't access

## Solution
Conditionally render protected nav items based on `isAuthenticated` state from AuthContext.

## Acceptance Criteria
- [ ] Dashboard nav item hidden from unauthenticated users
- [ ] Competitors nav item hidden from unauthenticated users
- [ ] Comparison nav item hidden from unauthenticated users
- [ ] Nav items appear when user logs in
- [ ] Nav items disappear when user logs out
- [ ] Unit tests for conditional rendering

## Technical Details

### File to Modify
`frontend/components/layout/Header.tsx`

### Code Changes
```tsx
// Conditionally render protected items
const { isAuthenticated } = useAuth();

// In nav rendering
{isAuthenticated && (
  <>
    <Link href="/dashboard">Dashboard</Link>
    <Link href="/competitors">Competitors</Link>
    <Link href="/comparison">Comparison</Link>
  </>
)}
```

## Dependencies
- None

## Effort Estimate
`XSmall` (0.5 hours)

## Related Issues
- FEAT-037
- SPEC-037
- TASK-37-2 (Header Navigation - combined task)
