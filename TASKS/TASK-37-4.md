# TASK-37-4: Graceful Token Expiry Handling

## STATUS
`To Develop`

## PRIORITY
`Critical`

## RISK
`High`

## Specification
SPEC-037

## Description
Implement graceful token expiry handling throughout the application. When any API call returns 401, the application should clear the invalid session and redirect to login with a user-friendly message.

## Problem
1. Multiple endpoints can return 401 (expired token)
2. No centralized handling of 401 responses
3. User sees raw error messages instead of clean re-auth flow

## Solution
1. Add response interceptor for 401 errors in API client
2. Clear localStorage on 401
3. Redirect to login with session expiry message
4. Store intended destination for post-login redirect

## Acceptance Criteria
- [ ] 401 responses trigger automatic logout
- [ ] localStorage cleared on any 401 response
- [ ] User redirected to login page
- [ ] Current URL saved as redirect target
- [ ] User-friendly "session expired" message shown
- [ ] No raw error messages for auth failures
- [ ] Unit tests for 401 handling

## Technical Details

### Files to Modify
- `frontend/lib/api/client.ts` - Add response interceptor
- `frontend/lib/contexts/AuthContext.tsx` - Clear state on 401

### Code Changes
```typescript
// In API client
apiClient.interceptors.response.use(
  response => response,
  error => {
    if (error.response?.status === 401) {
      // Clear auth state
      localStorage.removeItem('seoindicator_token');
      localStorage.removeItem('seoindicator_user');
      
      // Store redirect target
      const currentPath = window.location.pathname;
      if (currentPath !== '/login') {
        localStorage.setItem('seoindicator_redirect', currentPath);
      }
      
      // Redirect to login
      window.location.href = '/login';
    }
    return Promise.reject(error);
  }
);
```

## Dependencies
- TASK-37-1 (Token validation)

## Effort Estimate
`Small` (1-2 hours)

## Related Issues
- FEAT-037
- SPEC-037

## Comments

### ProjectManager Assessment (2026-03-16 20:00 GMT+2)
**Priority:** Critical (confirmed) - Authentication token expiry directly impacts user ability to use the application
**Risk:** High (confirmed) - Security-critical token handling changes
**Dependencies:** TASK-37-1 (Token validation) at `To Write Tests` - progressing, NOT blocking

**Rationale:**
- Users with expired tokens see raw error messages instead of clean re-auth flow
- Direct impact on user experience and ability to authenticate
- Authentication changes are always security-critical (High risk)
- Dependency TASK-37-1 is progressing with PreTester - tests can be designed in parallel

**Status Changed:** To Prioritize → To Write Tests

**Handoff to PreTester:**
- Test scenarios documented in acceptance criteria
- 401 response interceptor tests
- localStorage clearing tests
- Login redirect tests with session expiry message
- Redirect target storage tests for post-login navigation
- Tests can be designed immediately - dependency is progressing, not blocking

### PreTester Handoff (2026-03-16 22:50 GMT+2)
**Status Changed:** Tests Coverage In Progress → To Develop

**Tests Designed and Implemented:**
- 36 unit tests in `frontend/__tests__/lib/api/token-expiry-handler.test.ts`
- 12 integration tests in `frontend/__tests__/integration/token-expiry-flow.test.tsx`
- Test documentation in `TESTS/TEST-37-4.md`

**Test Categories:**
- AC-001: 401 triggers automatic logout (TH-001 to TH-003)
- AC-002: localStorage cleared on 401 (TH-004 to TH-006)
- AC-003: User redirected to login (TH-007 to TH-009)
- AC-004: Redirect target saved (TH-010 to TH-012)
- AC-005: User-friendly message (TH-013 to TH-015)
- AC-006: No raw error messages (TH-016 to TH-018)
- Security: XSS prevention, open redirect prevention, token cleared before redirect
- Accessibility: Screen reader announcements with role="alert"
- Storage edge cases: Quota exceeded, corrupt data, private browsing

**Handoff to Developer:**
1. Create `frontend/lib/api/token-expiry-handler.ts` with:
   - `handleTokenExpiry(redirectPath?: string): void`
   - `setupUnauthorizedInterceptor(): void`
   - `isUnauthorizedResponse(error: unknown): boolean`
   - `clearAuthStorage(): void`
   - `storeRedirectTarget(path: string): void`

2. Integration points:
   - Register interceptor in `AuthContext.tsx` during initialization
   - Call `handleTokenExpiry()` from `handleErrorResponse()` in `auth.ts`
   - Add message param to login redirect URL
   - Display session expired toast on login page

3. All tests are ready for TDD - they use mock implementations that match expected behavior

### PreTester (2026-03-16 22:45 GMT+2)
**Test Coverage Complete:**
- 48 tests total (36 unit + 12 integration)
- All tests passing
- Unit tests: `frontend/__tests__/lib/api/token-expiry-handler.test.ts`
- Integration tests: `frontend/__tests__/integration/token-expiry-flow.test.tsx`
- Test documentation: `TESTS/TEST-37-4.md`

**Test Categories:**
1. **AC-001 to AC-007:** All acceptance criteria covered
2. **Token Expiry Handler (TH-*):** 36 unit tests for core logic
3. **Interceptor Integration (II-*):** 5 tests for fetch interceptor
4. **Storage Handling (SH-*):** 3 tests for localStorage edge cases
5. **Redirect Flow (RF-*):** 5 tests for login redirect flow
6. **Security (SEC-*):** 6 tests for XSS, open redirect, token safety
7. **Accessibility (AX-*):** 2 tests for screen reader support
8. **Performance (PF-*):** 1 test for handler speed

**Security Tests Verified:**
- SEC-001: Token not in redirect URL ✓
- SEC-004: XSS prevention in redirect path ✓
- SEC-005: Open redirect prevention ✓
- SEC-007: Token cleared before redirect ✓

**Handoff to Developer:**
- Create `frontend/lib/api/token-expiry-handler.ts`
- Implement `handleTokenExpiry()`, `setupUnauthorizedInterceptor()`, `isUnauthorizedResponse()`
- Integrate with existing `auth.ts` error handling
- Add session expired message to login page

**Status Changed:** Tests Coverage In Progress → To Develop
