# TASK-37-1: Fix Token Validation in AuthContext

## STATUS
`To Prioritize`

## PRIORITY
`Critical`

## RISK
`High`

## Specification
SPEC-037

## Description
Fix AuthContext to validate tokens with the API before considering a user authenticated. Currently, the presence of any token in localStorage causes the app to treat the user as authenticated, leading to redirect loops and error pages.

## Problem
1. AuthContext reads token from localStorage
2. Sets `isAuthenticated = true` based on token presence only
3. Redirects to `/dashboard/` before verifying token validity
4. Dashboard API fails with 401
5. Shows error instead of login page

## Solution
Modify `initializeAuth` in `AuthContext.tsx`:
1. Always call `/api/v1/auth/user` to verify token
2. On 401 response, clear localStorage and set user to null
3. Only set `isAuthenticated = true` if API confirms validity
4. Add loading state during token verification

## Acceptance Criteria
- [ ] Token validity verified with API before setting authenticated state
- [ ] Expired tokens are cleared from localStorage
- [ ] Users with expired tokens see login page, not dashboard error
- [ ] Loading state shown while verifying token
- [ ] No redirect to dashboard for invalid tokens
- [ ] Unit tests for token validation scenarios

## Technical Details

### File to Modify
`frontend/lib/contexts/AuthContext.tsx`

### Code Changes
```typescript
// In initializeAuth function:
// Remove the fallback user logic that sets isAuthenticated without API verification
// Always call fetchUser() to verify token
// On any error (401, network, etc), clear storage and set user to null
```

## Dependencies
- None

## Effort Estimate
`Small` (2-3 hours)

## Related Issues
- FEAT-037
- SPEC-037
