# TASK-37-5: Login Page Redirect Loop Prevention

## STATUS
`To Write Tests`

## PRIORITY
`Critical`

## RISK
`High`

## Specification
SPEC-037

## Description
Prevent redirect loops on the login page when users have invalid tokens in localStorage. The login page should clear invalid tokens before rendering and show the login form immediately.

## Problem
1. User visits /login with stale token in localStorage
2. AuthContext may redirect to dashboard before checking token validity
3. Dashboard fails, user stuck in loop

## Solution
1. Clear invalid tokens on login page mount
2. Don't redirect from login page based on token presence alone
3. Show login form immediately if not authenticated

## Acceptance Criteria
- [ ] Login page clears invalid tokens on mount
- [ ] No redirect from /login to /dashboard without valid token
- [ ] Login form renders immediately for unauthenticated users
- [ ] Loading state shown while checking token validity
- [ ] No redirect loops possible
- [ ] Unit tests for login page behavior

## Technical Details

### File to Modify
`frontend/app/login/page.tsx`

### Code Changes
```tsx
// In LoginPage component
useEffect(() => {
  // Clear any stale tokens on mount
  const token = localStorage.getItem('seoindicator_token');
  if (token) {
    // Verify token with API
    fetchUser().catch(() => {
      // Token invalid, clear it
      localStorage.removeItem('seoindicator_token');
      localStorage.removeItem('seoindicator_user');
    });
  }
}, []);

// Only redirect if we have a VALID user
if (isAuthenticated && user) {
  return <Navigate to="/dashboard" />;
}

// Show login form
return <LoginForm />;
```

## Dependencies
- TASK-37-1 (Token validation) - `To Write Tests` ⏳

## Effort Estimate
`Small` (1-2 hours)

## Related Issues
- FEAT-037
- SPEC-037
