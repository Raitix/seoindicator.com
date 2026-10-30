# SPEC-037: Production Critical Fixes - Auth & Navigation

## STATUS
`To Prioritize`

## PRIORITY
`Critical`

## RISK
`High`

## Overview
Emergency production fixes for authentication flow failures and navigation issues discovered during CEO production audit on 2026-03-13. Multiple critical paths were affected by stale localStorage tokens causing incorrect redirects and broken user experience.

---

## Root Cause Analysis

### The Core Problem
When localStorage contains an old/expired token:
1. AuthContext initializes with `isLoading: true`, `isAuthenticated: true` (based on stored user)
2. AuthContext redirects to `/dashboard/` immediately
3. Dashboard tries to fetch data with expired token → API returns 401
4. Dashboard shows "Failed to load dashboard data" error
5. Login page is never shown because of redirect

### Current Flow (Broken)
```
User visits /login/
  → AuthContext sees stored token in localStorage
  → AuthContext considers user "authenticated" (token presence only)
  → Redirect to /dashboard/
  → Dashboard fetch fails (expired token)
  → Shows "Failed to load dashboard data"
```

### Expected Flow (Fixed)
```
User visits /login/
  → AuthContext checks if token is VALID (API call)
  → If token invalid/expired → clear localStorage, show login
  → If token valid → redirect to dashboard
```

---

## Technical Specification

### Group 1: Auth Token Validation (CRITICAL)

#### 1.1 AuthContext Token Validation Fix
**File:** `frontend/lib/contexts/AuthContext.tsx`

**Current Behavior:**
- Checks localStorage for token presence
- Sets `isAuthenticated: true` if token exists (regardless of validity)

**Required Changes:**
1. Add `validateToken()` method that calls `/api/v1/auth/user`
2. On initialization, verify token with API before setting `isAuthenticated`
3. If API returns 401, clear localStorage and set `isAuthenticated: false`
4. Only redirect to protected routes after token validation succeeds

**Implementation:**
```typescript
// AuthContext.tsx
const validateToken = async (): Promise<boolean> => {
  const token = localStorage.getItem('seoindicator_token');
  if (!token) return false;
  
  try {
    await apiClient.get('/api/v1/auth/user');
    return true;
  } catch (error) {
    // Token is invalid - clear storage
    localStorage.removeItem('seoindicator_token');
    localStorage.removeItem('seoindicator_user');
    return false;
  }
};

// In initializeAuth:
const isValid = await validateToken();
if (!isValid) {
  setUser(null);
  setIsAuthenticated(false);
  router.push('/login');
  return;
}
```

#### 1.2 Backend Token Validation Endpoint
**File:** `backend/app/Http/Controllers/Api/V1/AuthController.php`

**Endpoint:** `GET /api/v1/auth/user`
- Already exists
- Returns 401 for invalid/expired tokens
- Returns user data for valid tokens

**No backend changes required** - existing endpoint is correct.

#### 1.3 Graceful Token Expiry Handling
**Files:**
- `frontend/lib/contexts/AuthContext.tsx`
- `frontend/lib/api/client.ts`

**Requirements:**
- On any 401 response from protected endpoints, trigger re-authentication
- Clear localStorage on 401
- Redirect to login page with current URL as redirect target
- Show user-friendly message about session expiry

---

### Group 2: Dashboard Data Fetching (HIGH)

#### 2.1 Dashboard Loading State Fix
**File:** `frontend/app/dashboard/page.tsx`

**Current Behavior:**
- Shows "Failed to load dashboard data" on fetch failure
- No redirect to login on 401

**Required Changes:**
1. Detect 401 errors specifically
2. Trigger AuthContext logout/clear action
3. Redirect to login page
4. Never show raw error message for auth failures

**Implementation:**
```typescript
// Dashboard page
const { data, error, isLoading } = useDashboardData();

useEffect(() => {
  if (error?.status === 401) {
    // Auth context should handle this, but also handle here as fallback
    router.push('/login');
  }
}, [error, router]);
```

---

### Group 3: Header Navigation (HIGH)

#### 3.1 Sign In Button Fix
**File:** `frontend/components/layout/Header.tsx`

**Current Issue:**
- "Sign In" button has no action/onClick handler
- Button is not clickable

**Required Fix:**
```tsx
// For guest users
<Link href="/login" className="...">
  Sign In
</Link>

// OR button with onClick
<Button onClick={() => router.push('/login')}>
  Sign In
</Button>
```

#### 3.2 Indicators Button Fix
**File:** `frontend/components/layout/Header.tsx`

**Current Issue:**
- "Indicators" button has no action
- Button is not clickable

**Required Fix:**
```tsx
// For authenticated users
<Link href="/dashboard" className="...">
  Indicators
</Link>

// OR if there's a specific indicators page
<Link href="/indicators" className="...">
  Indicators
</Link>
```

#### 3.3 Navigation Hover States
**File:** `frontend/components/layout/Header.tsx`

**Current Issues:**
- White background on hover makes white text unreadable
- No pointer cursor on interactive elements

**Required Fixes:**
```tsx
// Add proper hover styles
className="... hover:bg-accent hover:text-accent-foreground cursor-pointer"

// Or specific classes
className="... hover:bg-slate-700 hover:text-white cursor-pointer"
```

#### 3.4 Protected Route Visibility
**File:** `frontend/components/layout/Header.tsx`

**Current Issue:**
- Dashboard, Competitors, Comparison shown to non-authenticated users

**Required Fix:**
```tsx
// Conditionally render protected nav items
{isAuthenticated && (
  <>
    <Link href="/dashboard">Dashboard</Link>
    <Link href="/competitors">Competitors</Link>
    <Link href="/comparison">Comparison</Link>
  </>
)}
```

---

### Group 4: Functional Fixes (MEDIUM)

#### 4.1 Login Page Redirect Loop Prevention
**File:** `frontend/app/login/page.tsx`

**Requirements:**
- Prevent redirect loop when token is invalid
- Clear invalid tokens before rendering
- Show login form immediately if not authenticated

#### 4.2 Protected Route Guard
**File:** `frontend/middleware.ts` (if exists) or layout component

**Requirements:**
- Check authentication before rendering protected routes
- Redirect to login if not authenticated
- Store intended destination for post-login redirect

---

### Group 5: Verification & Testing

#### 5.1 Automated Tests Required
- Auth flow with valid token
- Auth flow with expired token
- Auth flow with no token
- Navigation button clickability
- Protected route visibility toggling
- Session expiry handling

#### 5.2 Manual Verification Checklist
- [ ] Clear localStorage, visit /login → Shows login form
- [ ] Login with valid credentials → Redirects to dashboard
- [ ] Wait for token expiry → Next request redirects to login
- [ ] Click "Sign In" button → Navigates to /login
- [ ] Click "Indicators" button → Navigates correctly
- [ ] Hover nav buttons → Shows pointer cursor, visible hover state
- [ ] Protected nav items hidden when logged out

---

## Task Breakdown Structure

| Group | Task ID | Description | Priority | Risk |
|-------|---------|-------------|----------|------|
| Auth | TASK-37-1 | AuthContext token validation logic | Critical | High |
| Auth | TASK-37-2 | Graceful token expiry handling | Critical | High |
| Auth | TASK-37-3 | Login page redirect loop fix | Critical | High |
| Dashboard | TASK-37-4 | Dashboard 401 error handling | High | Medium |
| Navigation | TASK-37-5 | Sign In button fix | High | Low |
| Navigation | TASK-37-6 | Indicators button fix | High | Low |
| Navigation | TASK-37-7 | Nav hover states + cursor | Medium | Low |
| Navigation | TASK-37-8 | Protected route visibility | Medium | Medium |
| Functional | TASK-37-9 | Protected route middleware | Medium | Medium |
| Functional | TASK-37-10 | Post-login redirect handling | Medium | Low |
| Verification | TASK-37-11 | Auth flow tests | High | Medium |
| Verification | TASK-37-12 | Navigation tests | Medium | Low |
| Verification | TASK-37-13 | Protected route tests | Medium | Medium |
| Verification | TASK-37-14 | Manual verification checklist | High | Low |
| Verification | TASK-37-15 | Integration tests | High | Medium |
| Verification | TASK-37-16 | End-to-end auth flow test | High | Medium |
| Documentation | TASK-37-17 | Update DOCUMENTATION.md | Medium | Low |

**Total Tasks: 17**

---

## Dependencies

| Dependency | Status | Notes |
|------------|--------|-------|
| AuthContext | Exists | Requires modification |
| API endpoints | Exists | No backend changes needed |
| Header component | Exists | Requires modification |

---

## Security Considerations

### High Risk Items
1. **Token Validation** - Changes to auth flow are high-risk
2. **Session Management** - Affects user sessions

### SecurityAuditor Review Required
- All auth-related changes must be reviewed before deployment
- Task 1.1, 1.2, 1.3 require SecurityAuditor sign-off

### Mitigation Steps
1. Test extensively in local environment
2. Run full test suite before deployment
3. Deploy during low-traffic period
4. Monitor authentication errors after deployment
5. Have rollback plan ready

---

## Rollback Plan

If issues occur post-deployment:
1. Revert frontend changes
2. Previous auth flow will be restored
3. Users may need to clear localStorage manually

---

## Related Files

### Frontend
- `frontend/lib/contexts/AuthContext.tsx` - Core auth logic
- `frontend/components/layout/Header.tsx` - Navigation
- `frontend/app/dashboard/page.tsx` - Dashboard
- `frontend/app/login/page.tsx` - Login page
- `frontend/middleware.ts` - Route protection (if exists)

### Backend
- `backend/app/Http/Controllers/Api/V1/AuthController.php` - Auth endpoints (no changes)

---

## Effort Estimate
**Medium** (4-8 hours total)

| Group | Hours |
|-------|-------|
| Auth (Tasks 1-3) | 2-3h |
| Dashboard (Task 4) | 0.5h |
| Navigation (Tasks 5-8) | 1h |
| Functional (Tasks 9-10) | 1h |
| Verification (Tasks 11-17) | 1.5-2.5h |

---

## Comments

### 2026-03-16 16:30 GMT+2 - SpecSystemAnalyst
**Task Breakdown Complete:**
- Created all 17 tasks per spec's breakdown table
- **Note on existing tasks:**
  - TASK-37-1: Token Validation (To Prioritize) - matches spec TASK-37-1
  - TASK-37-2: Header Navigation (To Develop) - **combined task** that covers spec's TASK-37-5 through TASK-37-8; already has tests written by PreTester
  - TASK-37-3: Dashboard Error Handling (Blocked) - corresponds to spec's TASK-37-4; blocked on TASK-37-1
- **Newly created tasks:**
  - TASK-37-4: Graceful Token Expiry Handling (Critical) - new
  - TASK-37-5: Login Page Redirect Loop Prevention (Critical) - new
  - TASK-37-6 through TASK-37-17: Remaining tasks per spec breakdown

**Task Count:** 17 tasks total
- Critical: 3 (TASK-37-1, 37-4, 37-5)
- High: 5 (TASK-37-2, 37-3, 37-6, 37-7, 37-12, 37-15, 37-16)
- Medium: 9 (TASK-37-8 through TASK-37-11, 37-13, 37-14, 37-17)

**Status Updated:** SPEC-037 and FEAT-037 → "To Prioritize"

### 2026-03-13 - Architect
Created specification from FEAT-037. Root cause identified: stale localStorage tokens causing premature redirect to dashboard before token validation. 17 tasks organized across 5 groups. High risk due to auth system changes - SecurityAuditor review required before deployment.

---

Last updated: 2026-03-16
