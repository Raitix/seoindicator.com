# TASK-37-3: Fix Dashboard Error Handling

## STATUS
`To Prioritize`

## PRIORITY
`High`

## RISK
`Medium`

## Specification
SPEC-037

## Description
Fix the dashboard page to handle authentication errors gracefully. Currently, when a user's session expires or token is invalid, the dashboard shows "Failed to load dashboard data" instead of redirecting to login.

## Problem
1. User has expired/invalid token
2. Dashboard API returns 401
3. Dashboard shows error message
4. User stays on dashboard page, confused

## Solution
1. Detect 401 error from dashboard API
2. Clear localStorage
3. Redirect to `/login/`

## Acceptance Criteria
- [ ] Dashboard detects 401 error from API
- [ ] localStorage cleared on 401 error
- [ ] User redirected to /login/ on auth failure
- [ ] No error message shown for auth failures (just redirect)
- [ ] Loading state shown while checking auth
- [ ] Unit tests for error handling

## Technical Details

### File to Modify
`frontend/app/dashboard/page.tsx`

### Code Changes
```tsx
// In DashboardPage component
// Handle error state
if (error) {
  // Check if it's an auth error
  if (error.status === 401 || error.message?.includes('Unauthorized')) {
    // Clear auth storage and redirect
    clearAuthStorage();
    router.push('/login');
    return null;
  }
  
  // Show error UI for other errors
  return <ErrorUI />;
}
```

## Dependencies
- TASK-37-1 (Token validation fix)

## Effort Estimate
`Small` (1-2 hours)

## Related Issues
- FEAT-037
- SPEC-037
