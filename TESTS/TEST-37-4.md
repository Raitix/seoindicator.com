# TEST-37-4: Graceful Token Expiry Handling

## Task Reference
- **Task:** TASK-37-4
- **Specification:** SPEC-037
- **Feature:** FEAT-037
- **Priority:** Critical
- **Risk:** High

## Overview

Tests for graceful token expiry handling throughout the application. When any API call returns 401, the application should clear the invalid session and redirect to login with a user-friendly message.

## Test Files

| File | Tests | Description |
|------|-------|-------------|
| `frontend/__tests__/lib/api/token-expiry-handler.test.ts` | 45+ | Unit tests for 401 interceptor |
| `frontend/__tests__/integration/token-expiry-flow.test.tsx` | 15+ | Integration tests for full flow |

## Acceptance Criteria Mapping

| AC ID | Criteria | Test IDs |
|-------|----------|----------|
| AC-001 | 401 responses trigger automatic logout | TH-001, TH-002, TH-003 |
| AC-002 | localStorage cleared on any 401 response | TH-004, TH-005, TH-006 |
| AC-003 | User redirected to login page | TH-007, TH-008, TH-009 |
| AC-004 | Current URL saved as redirect target | TH-010, TH-011, TH-012 |
| AC-005 | User-friendly "session expired" message shown | TH-013, TH-014, TH-015 |
| AC-006 | No raw error messages for auth failures | TH-016, TH-017, TH-018 |
| AC-007 | Unit tests for 401 handling | All TH-* tests |

## Test Scenarios

### 1. Token Expiry Handler Unit Tests (TH-*)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| TH-001 | 401 response triggers logout | 401 status code | `handleTokenExpiry()` called |
| TH-002 | Non-401 response does not trigger logout | 500 status code | `handleTokenExpiry()` NOT called |
| TH-003 | Multiple 401s handled gracefully | Rapid 401 responses | Single logout redirect (debounced) |
| TH-004 | localStorage token cleared on 401 | Token in localStorage | Token removed |
| TH-005 | localStorage user cleared on 401 | User in localStorage | User removed |
| TH-006 | localStorage redirect set on 401 | Protected route path | Redirect URL stored |
| TH-007 | Redirect to /login on 401 | 401 response | `window.location.href = '/login'` |
| TH-008 | No redirect if already on /login | 401 on login page | No redirect loop |
| TH-009 | No redirect if already on /register | 401 on register page | No redirect loop |
| TH-010 | Current path stored as redirect | Path '/dashboard/reports' | Stored as redirect target |
| TH-011 | Query params preserved in redirect | Path '/dashboard?tab=overview' | Full path stored |
| TH-012 | Hash preserved in redirect | Path '/dashboard#section' | Full path stored |
| TH-013 | Session expired message in URL | 401 response | `?message=session_expired` param |
| TH-014 | User-friendly message text | Any 401 | "Your session has expired" |
| TH-015 | No technical error details shown | 401 with error body | Only friendly message |
| TH-016 | No stack trace in UI | 401 with stack trace | Stack trace not displayed |
| TH-017 | No API error message in UI | 401 with "Token invalid" | Friendly message instead |
| TH-018 | No HTTP status code in UI | 401 response | Status code not shown to user |

### 2. Interceptor Integration Tests (II-*)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| II-001 | Interceptor registered on fetch | Global fetch | Interceptor wraps fetch |
| II-002 | Successful response passes through | 200 response | Response returned unchanged |
| II-003 | 401 from protected endpoint | GET /api/v1/user | Token expiry handler invoked |
| II-004 | 401 from public endpoint | GET /api/v1/health | No token expiry handler |
| II-005 | 401 during request | POST with 401 | Request fails, handler invoked |
| II-006 | Multiple concurrent 401s | 3 parallel 401s | Single redirect |
| II-007 | 401 followed by success | 401 then 200 | First triggers logout |
| II-008 | Interceptor preserves response data | 401 with body | Body still readable |
| II-009 | Interceptor handles network error | Network failure | No token expiry (not 401) |
| II-010 | Interceptor handles timeout | Request timeout | No token expiry (not 401) |

### 3. Storage Handling Tests (SH-*)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| SH-001 | Token removed from localStorage | seoindicator_token | localStorage.removeItem called |
| SH-002 | User removed from localStorage | seoindicator_user | localStorage.removeItem called |
| SH-003 | Redirect stored in localStorage | '/dashboard' | localStorage.setItem called |
| SH-004 | Existing redirect preserved | '/admin' already stored | Not overwritten |
| SH-005 | Storage errors handled gracefully | localStorage throws | No crash, continues |
| SH-006 | Private browsing mode | localStorage unavailable | Graceful fallback |
| SH-007 | Corrupt localStorage data | Invalid JSON in storage | Cleared without error |
| SH-008 | Multiple tabs sync logout | Tab A logs out | Tab B also logs out |

### 4. Redirect Flow Tests (RF-*)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| RF-001 | Redirect to login with message | 401 on protected route | /login?message=session_expired |
| RF-002 | Post-login redirect preserved | Was on /dashboard | Redirect to /dashboard after login |
| RF-003 | Login page detects session expired | URL param present | Show "Session expired" toast |
| RF-004 | Login page shows normal UI | No URL param | Normal login UI |
| RF-005 | Protected route after login | Redirect param set | Navigate to stored path |
| RF-006 | Clear redirect after navigation | Redirect used | localStorage cleared |
| RF-007 | No redirect loop on login 401 | 401 on /login | Stay on /login |
| RF-008 | No redirect loop on register 401 | 401 on /register | Stay on /register |
| RF-009 | Auth callback handles 401 | 401 on OAuth callback | Redirect to login with error |
| RF-010 | API routes excluded | 401 on API route | No browser redirect |

### 5. Edge Cases (EC-*)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| EC-001 | 401 with no token in storage | 401, no token | Still redirect to login |
| EC-002 | 401 during initial load | 401 on page load | Show login, not error |
| EC-003 | 401 during form submission | 401 on POST | Preserve form data warning |
| EC-004 | 401 with unsaved changes | 401, dirty form | Warning about data loss |
| EC-005 | Multiple API calls in flight | 401 on first, others pending | Cancel pending requests |
| EC-006 | 401 with retry logic | 401, retry enabled | No retry, redirect immediately |
| EC-007 | Safari private mode | localStorage throws | Graceful handling |
| EC-008 | Firefox container tabs | Different containers | Isolated storage |
| EC-009 | Browser back button after logout | Navigate back | Redirect to login again |
| EC-010 | Session expired in iframe | 401 in iframe | Parent redirects to login |

### 6. Security Tests (SEC-*)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| SEC-001 | No token in redirect URL | Redirect to login | Token NOT in URL params |
| SEC-002 | No user data in redirect URL | Redirect to login | User data NOT in URL |
| SEC-003 | No sensitive headers logged | 401 response | Auth header not logged |
| SEC-004 | XSS in redirect path | Path with <script> | Sanitized redirect |
| SEC-005 | Open redirect prevention | External redirect URL | Blocked, use default |
| SEC-006 | CRLF injection in message | Message with \r\n | Sanitized output |
| SEC-007 | Token cleared before redirect | 401 response | Token gone before navigation |
| SEC-008 | Multiple clear attempts | Double 401 | No error thrown |

### 7. Performance Tests (PF-*)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| PF-001 | Handler completes in <100ms | 401 response | Fast cleanup |
| PF-002 | No memory leaks | Multiple 401s | Listeners cleaned up |
| PF-003 | Debounced rapid 401s | 10 rapid 401s | Single redirect |
| PF-004 | Interceptor adds <5ms overhead | Normal request | Minimal latency |

### 8. Accessibility Tests (AX-*)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| AX-001 | Screen reader announces session expired | 401 response | aria-live announcement |
| AX-002 | Focus moves to login form | Redirect to login | Focus on email input |
| AX-003 | Error message is accessible | Session expired | role="alert" on message |
| AX-004 | Keyboard navigation preserved | Redirect to login | Tab order correct |

## Mock Data

### Mock 401 Response
```typescript
const mockUnauthorizedResponse = {
  status: 401,
  statusText: 'Unauthorized',
  json: async () => ({
    success: false,
    message: 'Unauthenticated.',
  }),
};
```

### Mock User in localStorage
```typescript
const mockStoredUser = {
  id: 1,
  name: 'John Doe',
  email: 'john@example.com',
  email_verified: true,
};
```

### Mock Token in localStorage
```typescript
const mockStoredToken = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...';
```

## Coverage Targets

| Category | Target | Description |
|----------|--------|-------------|
| Statements | 95% | All handler logic |
| Branches | 90% | Edge case paths |
| Functions | 100% | All exported functions |
| Lines | 95% | Critical paths |

## Test Execution Commands

```bash
# Run unit tests
cd frontend
npm test -- --grep "token-expiry-handler"

# Run integration tests
npm test -- --grep "token-expiry-flow"

# Run all tests with coverage
npm test -- --coverage --grep "token-expiry"
```

## Dependencies

| Dependency | Status | Notes |
|------------|--------|-------|
| AuthContext | Exists | Needs integration |
| API fetch wrapper | Exists | Interceptor needed |
| localStorage | Native | Browser API |
| SPEC-037 | Active | Parent specification |

## Notes for Developer

### Implementation Requirements

1. **Create `frontend/lib/api/token-expiry-handler.ts`**
   - Export `handleTokenExpiry()` function
   - Export `setupUnauthorizedInterceptor()` function
   - Export `isUnauthorizedResponse()` type guard

2. **Key Functions**
   ```typescript
   // Main handler
   export function handleTokenExpiry(redirectPath?: string): void;
   
   // Interceptor setup
   export function setupUnauthorizedInterceptor(): void;
   
   // Type guard
   export function isUnauthorizedResponse(error: unknown): boolean;
   
   // Clear auth storage
   export function clearAuthStorage(): void;
   
   // Store redirect target
   export function storeRedirectTarget(path: string): void;
   ```

3. **Integration Points**
   - Register interceptor in `AuthContext.tsx` during initialization
   - Call `handleTokenExpiry()` from `handleErrorResponse()` in auth.ts
   - Add message param to login redirect URL
   - Display session expired toast on login page

4. **Security Considerations**
   - Never include token in URL params
   - Sanitize redirect paths
   - Block external redirects
   - Clear sensitive data before navigation

5. **User Experience**
   - Show toast notification before redirect
   - Preserve redirect target for post-login
   - Use friendly "session expired" language
   - No technical error details

## Comments

### PreTester (2026-03-16 20:30 GMT+2)
- Test documentation created
- 45+ unit tests designed
- 15+ integration tests designed
- Coverage targets: 95% statements, 90% branches, 100% functions
- Security tests include XSS prevention, open redirect prevention
- Accessibility tests include screen reader announcements

---

Last updated: 2026-03-16
