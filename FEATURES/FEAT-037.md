# FEAT-037: Production Critical Fixes - Auth & Navigation

## STATUS
`To Prioritize`

## PRIORITY
`Critical`

## RISK
`High`

## Description
Emergency production fixes for authentication flow and navigation issues discovered during CEO production audit on 2026-03-13. Multiple critical paths were affected by stale localStorage tokens causing incorrect redirects.

## Business Impact
- Users with stale/expired tokens see "Failed to load dashboard data" instead of login page
- AuthContext incorrectly considers users authenticated based on stale localStorage
- Navigation buttons have UX issues (hover states, cursor, protected route visibility)
- "Sign In" button in header is not clickable
- "Indicators" button in header is not clickable

## Root Cause Identified
**STALE LOCALSTORAGE TOKEN ISSUE**

### Evidence:
1. When localStorage contains an old/expired token:
   - AuthContext initializes with `isLoading: true`, `isAuthenticated: true` (based on stored user)
   - AuthContext redirects to `/dashboard/` immediately
   - Dashboard tries to fetch data with expired token → API returns 401
   - Dashboard shows "Failed to load dashboard data" error
   - Login page is never shown because of redirect

2. After clearing localStorage:
   - Login page renders correctly with "Sign In" form
   - Email and password fields work
   - Google OAuth button is present

### The Flow Issue:
```
User visits /login/
  → AuthContext sees stored token in localStorage
  → AuthContext considers user "authenticated"
  → Redirect to /dashboard/
  → Dashboard fetch fails (expired token)
  → Shows "Failed to load dashboard data"
```

### Expected Flow:
```
User visits /login/
  → AuthContext checks if token is VALID (not just present)
  → If token invalid/expired → clear localStorage, show login
  → If token valid → redirect to dashboard
```

## Issues to Fix

### CRITICAL
1. **Auth redirect logic** - Don't redirect to dashboard if token is expired/invalid
2. **Token validation on init** - Verify token with API before considering user authenticated

### HIGH
3. **"Sign In" button not clickable** - Header button has no action
4. **"Indicators" button not clickable** - Header button has no action

### MEDIUM
5. **Navigation hover states broken** - White background makes white text unreadable
6. **Missing pointer cursor** - Nav buttons don't show pointer on hover
7. **Protected routes visible to guests** - Dashboard, Competitors, Comparison shown to non-authenticated users

## Acceptance Criteria
- [ ] /login/ always shows login form (not dashboard error) when user is unauthenticated
- [ ] Expired tokens are cleared from localStorage automatically
- [ ] Token validity is verified with API before redirecting to protected routes
- [ ] "Sign In" button navigates to /login/
- [ ] "Indicators" button navigates appropriately
- [ ] Nav buttons show pointer cursor on hover
- [ ] Nav buttons have visible hover state (not white-on-white)
- [ ] Protected nav items hidden from non-authenticated users
- [ ] All authentication flows tested with automated tests

## Technical Solution

### 1. Fix AuthContext Token Validation
In `AuthContext.tsx`, the `initializeAuth` function should:
1. Check if token exists in localStorage
2. Call `/api/v1/auth/user` to verify token is still valid
3. If 401 returned, clear localStorage and set `isAuthenticated: false`
4. Only redirect to dashboard if API confirms user is authenticated

### 2. Fix Header Navigation
In the header component:
1. Make "Sign In" button a link to `/login/`
2. Make "Indicators" button a link to `/dashboard/` or `/indicators/`
3. Add `cursor-pointer` class to nav buttons
4. Fix hover background color (use accent color instead of white)
5. Conditionally show protected nav items based on `isAuthenticated`

## Related Files
- `frontend/lib/contexts/AuthContext.tsx`
- `frontend/components/layout/Header.tsx`
- `frontend/app/dashboard/page.tsx`

## Dependencies
- None (emergency fix)

## Effort Estimate
**Medium** (4-6 hours)

## Notes
- SEO Guides (18 guides) are working correctly
- API authentication works when valid token present
- Local build files are correct

## Comments

### 2026-03-16 16:30 GMT+2 - SpecSystemAnalyst
**Task Breakdown Complete:**
- 17 tasks created for SPEC-037
- Status updated to "To Prioritize"
- Ready for ProjectManager to prioritize

**Task Summary:**
| Priority | Count | Tasks |
|----------|-------|-------|
| Critical | 3 | TASK-37-1, 37-4, 37-5 |
| High | 5 | TASK-37-2, 37-3, 37-6, 37-7, 37-12, 37-15, 37-16 |
| Medium | 9 | TASK-37-8 through 37-11, 37-13, 37-14, 37-17 |

**Note:** TASK-37-2 is already at "To Develop" status with tests written.

### 2026-03-13 - Architect
Created specification SPEC-037 with 17 tasks across 5 groups:
- Group 1 (Auth): 3 tasks - Token validation, expiry handling, login redirect
- Group 2 (Dashboard): 1 task - 401 error handling
- Group 3 (Navigation): 4 tasks - Button fixes, hover states, visibility
- Group 4 (Functional): 2 tasks - Route middleware, post-login redirect
- Group 5 (Verification): 7 tasks - Tests and documentation

**Risk Assessment:** High risk due to auth system changes. SecurityAuditor review required before deployment.

**Root Cause Confirmed:** Stale localStorage tokens cause AuthContext to consider users authenticated before validating token with API. This triggers premature redirect to dashboard, which then fails with 401.

**Spec File:** SPECIFICATIONS/SPEC-037.md
