# TASK-37-3: Fix Dashboard Error Handling

## STATUS
`To Prioritize`

## PRIORITY
`High`

## RISK
`Medium`

## Specification
SPEC-037

## BLOCKED_BY
TASK-37-1

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

---

## Comments

### ProjectManager Assessment (2026-03-16 13:16 GMT+2)
**Status:** Remains `To Prioritize` — blocked on dependency
**Priority:** HIGH (confirmed) - Authentication UX is critical for user experience
**Risk:** MEDIUM (confirmed) - Frontend-only changes, standard error handling patterns

**Blocker Analysis:**
- TASK-37-1 (Token Validation in AuthContext) is at `To Prioritize` - NOT IMPLEMENTED
- This task depends on TASK-37-1 because:
  1. TASK-37-1 validates tokens BEFORE redirecting to dashboard
  2. This task handles 401 errors ONCE the user is already on dashboard
  3. Logical flow requires prevention (TASK-37-1) before handling (TASK-37-3)

**Unblocks when:** TASK-37-1 reaches `To Test Locally` or later status

**Technical Files:**
- TASK-37-1 modifies: `frontend/lib/contexts/AuthContext.tsx`
- This task modifies: `frontend/app/dashboard/page.tsx`
- Files are different, allowing parallel development once TASK-37-1 is ready

**Effort:** Small (1-2 hours) - straightforward error handling logic

**Next Agent:** Developer (after TASK-37-1 completes PreTester phase)

### ProjectManager Status Update (2026-03-16 13:19 GMT+2)
**Status Changed:** `To Prioritize` → `Blocked`
**Reason:** Dependency TASK-37-1 not yet implemented

**Action Taken:**
- Status updated to `Blocked` to prevent infinite orchestrator loops
- Task will remain blocked until TASK-37-1 reaches `To Test Locally` or later

**Unblocks when:** TASK-37-1 (Token Validation in AuthContext) is implemented and tested

### ProjectManager Status Check (2026-03-16 20:16 GMT+2)
**Status:** `Blocked` (confirmed - status field corrected)
**Dependency Check:** TASK-37-1 is at `To Write Tests` - NOT YET IMPLEMENTED

**Dependency Status Breakdown:**
- TASK-37-1: `To Write Tests` → PreTester will write tests → Developer implements → LocalTester tests
- This task: Still blocked until TASK-37-1 reaches `To Test Locally` or later

**Assessment:** Task remains blocked. No changes needed. Will unblock when TASK-37-1 implementation is complete.
