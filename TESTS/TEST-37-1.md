# TEST-37-1: Token Validation in AuthContext

## Task Reference
- **Task:** TASK-37-1
- **Specification:** SPEC-037
- **Priority:** Critical
- **Risk:** High

## Overview
Tests for AuthContext token validation logic to prevent redirect loops when users have expired tokens in localStorage.

## Problem Statement
1. AuthContext reads token from localStorage
2. Sets `isAuthenticated = true` based on token presence only
3. Redirects to `/dashboard/` before verifying token validity
4. Dashboard API fails with 401
5. Shows error instead of login page

## Solution Approach
Modify `initializeAuth` in `AuthContext.tsx`:
1. Always call `/api/v1/auth/user` to verify token
2. On 401 response, clear localStorage and set user to null
3. Only set `isAuthenticated = true` if API confirms validity
4. Add loading state during token verification

---

## Test File
`frontend/__tests__\lib\contexts\AuthContext.token-validation.test.tsx`

## Test Summary

| Category | Tests | Description |
|----------|-------|-------------|
| TV-001 | 4 | Token Validation on Initialization |
| TV-002 | 4 | Expired Token Handling |
| TV-003 | 4 | Loading State During Verification |
| TV-004 | 4 | Redirect Prevention for Invalid Tokens |
| TV-005 | 3 | Network Error Handling |
| TV-006 | 5 | Storage Clearing Behavior |
| TV-007 | 5 | Edge Cases |
| TV-008 | 3 | Successful Authentication Flow |
| TV-009 | 6 | Acceptance Criteria Verification |
| TV-010 | 4 | Integration Scenarios |
| **Total** | **42** | |

---

## Test Categories

### TV-001: Token Validation on Initialization

| Test ID | Description | Expected Behavior |
|---------|-------------|-------------------|
| TV-001a | Call fetchUser API on mount | `fetchUser()` must be called to verify token |
| TV-001b | Set isAuthenticated only after API confirms | User authenticated only after successful API response |
| TV-001c | No fallback user on 401 | `clearAuthStorage()` called, user set to null |
| TV-001d | Set user to null when API returns null | Storage cleared when no valid session |

### TV-002: Expired Token Handling

| Test ID | Description | Expected Behavior |
|---------|-------------|-------------------|
| TV-002a | Clear localStorage on 401 | Both token and user data removed |
| TV-002b | Show login page for expired tokens | No redirect to dashboard |
| TV-002c | Handle TOKEN_EXPIRED error code | Specific handling for expired tokens |
| TV-002d | Clear both token and user data | Complete storage cleanup |

### TV-003: Loading State During Verification

| Test ID | Description | Expected Behavior |
|---------|-------------|-------------------|
| TV-003a | Show loading state while verifying | `isLoading = true` during API call |
| TV-003b | Clear loading after success | `isLoading = false` after valid response |
| TV-003c | Clear loading after failure | `isLoading = false` after error |
| TV-003d | No flash of authenticated state | No premature redirect during loading |

### TV-004: Redirect Prevention for Invalid Tokens

| Test ID | Description | Expected Behavior |
|---------|-------------|-------------------|
| TV-004a | No dashboard redirect for expired token | Stay on login page |
| TV-004b | No dashboard redirect for invalid token | Stay on login page |
| TV-004c | No redirect loop for expired tokens | **Critical bug fix** |
| TV-004d | Stay on login page with expired token | No automatic redirect |

### TV-005: Network Error Handling

| Test ID | Description | Expected Behavior |
|---------|-------------|-------------------|
| TV-005a | Handle network errors gracefully | No crash, appropriate state |
| TV-005b | Don't clear token on network errors | Token may still be valid |
| TV-005c | Distinguish 401 from network errors | Different handling for each |

### TV-006: Storage Clearing Behavior

| Test ID | Description | Expected Behavior |
|---------|-------------|-------------------|
| TV-006a | Clear token from localStorage on 401 | `removeItem(TOKEN)` called |
| TV-006b | Clear user data on 401 | `removeItem(USER)` called |
| TV-006c | Clear both together | Single `clearAuthStorage()` call |
| TV-006d | Handle null/empty token | No error, no unnecessary clear |
| TV-006e | Handle whitespace-only token | Treated as no token |

### TV-007: Edge Cases

| Test ID | Description | Expected Behavior |
|---------|-------------|-------------------|
| TV-007a | Corrupted user data | Handle gracefully, fetch fresh |
| TV-007b | Malformed JSON in localStorage | Parse error handled |
| TV-007c | Concurrent initialization calls | No race conditions |
| TV-007d | Token with special characters | No error during processing |
| TV-007e | Very long tokens | No memory/performance issues |

### TV-008: Successful Authentication Flow

| Test ID | Description | Expected Behavior |
|---------|-------------|-------------------|
| TV-008a | Authenticate with valid token | User authenticated, data stored |
| TV-008b | Refresh user data from API | Fresh data replaces stale |
| TV-008c | Redirect to dashboard for valid user | Normal auth flow works |

### TV-009: Acceptance Criteria Verification

| Test ID | AC | Description |
|---------|-----|-------------|
| TV-009a | AC1 | Token validity verified with API before setting authenticated state |
| TV-009b | AC2 | Expired tokens are cleared from localStorage |
| TV-009c | AC3 | Users with expired tokens see login page, not dashboard error |
| TV-009d | AC4 | Loading state shown while verifying token |
| TV-009e | AC5 | No redirect to dashboard for invalid tokens |
| TV-009f | AC6 | Unit tests for token validation scenarios |

### TV-010: Integration Scenarios

| Test ID | Description | Expected Behavior |
|---------|-------------|-------------------|
| TV-010a | Page refresh with valid token | Authenticated state restored |
| TV-010b | Page refresh with expired token | Storage cleared, login shown |
| TV-010c | New tab with stored token | Validated independently |
| TV-010d | Server error during verification | Graceful degradation |

---

## Key Patterns

### Mock Setup
```typescript
// Mock auth API functions
const mockFetchUser = vi.fn();
const mockGetStoredAuthToken = vi.fn();
const mockGetStoredUserData = vi.fn();
const mockClearAuthStorage = vi.fn();

vi.mock("@/lib/api/auth", () => ({
  fetchUser: () => mockFetchUser(),
  getStoredAuthToken: () => mockGetStoredAuthToken(),
  // ...
}));
```

### Error Code Handling
```typescript
const authError = new Error("Unauthorized");
(authError as any).code = "UNAUTHORIZED";
mockFetchUser.mockRejectedValue(authError);
```

### Loading State Testing
```typescript
let resolveFetchUser: (value: MockUser) => void;
const fetchUserPromise = new Promise<MockUser>((resolve) => {
  resolveFetchUser = resolve;
});
mockFetchUser.mockReturnValue(fetchUserPromise);

// Test loading state here

await act(async () => {
  resolveFetchUser!(mockValidUser);
});
```

---

## Dependencies
- None (tests use mocked dependencies)

---

## Implementation Notes for Developer

### Files to Modify
- `frontend/lib/contexts/AuthContext.tsx`

### Key Changes Required

1. **Remove fallback user logic that bypasses API verification**
   ```typescript
   // Current (problematic):
   const preserveFallbackAuth = React.useCallback((candidate: User | null = null) => {
     const fallbackUser = getFallbackUser(candidate);
     if (!fallbackUser) return false;
     setUser(fallbackUser); // Sets user without API verification!
     return true;
   }, []);

   // Fix: Only use fallback for network errors, NOT for 401
   ```

2. **Always call fetchUser() during initialization**
   ```typescript
   // Remove the stored user display before API verification
   // This causes the redirect loop:
   if (storedUser) {
     setUser(storedUser); // PROBLEM: Sets isAuthenticated = true
   }
   ```

3. **Clear storage on 401, preserve on network errors**
   ```typescript
   } catch (err) {
     if (err.code === 'UNAUTHORIZED' || err.code === 'TOKEN_EXPIRED') {
       // Clear storage for auth errors
       setUser(null);
       clearAuthStorage();
     } else {
       // For network errors, preserve stored user (may still be valid)
       // But don't set isAuthenticated = true
     }
   }
   ```

4. **Show loading state during verification**
   - Keep `isLoading = true` until API response received
   - Don't redirect until verification complete

### Root Cause Analysis
The bug is in `preserveFallbackAuth()` which sets `user` from localStorage even when the API fails with 401. This causes:
1. `isAuthenticated = true` (because user is not null)
2. Redirect to dashboard
3. Dashboard API fails with 401
4. User sees error instead of login page

### TDD Pattern
Tests are written before implementation. Run tests to see failures, then implement the fix.

---

## Last Updated
2026-03-17 by PreTester
