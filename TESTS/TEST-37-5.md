# TEST-37-5: Login Page Redirect Loop Prevention

## Task Reference
- **Task:** TASK-37-5
- **Specification:** SPEC-037
- **Feature:** FEAT-037 (Production Audit Fix)
- **Priority:** Critical
- **Risk:** High

## Overview
Test suite for preventing redirect loops on the login page when users have invalid tokens in localStorage. The login page must clear invalid tokens before rendering and show the login form immediately.

## Test Files

| File | Tests | Purpose |
|------|-------|---------|
| `frontend/__tests__/app/login/page.test.tsx` | 27 (existing) + 15 (new) | Login page component tests |
| `frontend/__tests__/app/login/redirect-prevention.test.tsx` | 25 | Dedicated redirect loop prevention tests |

## Test Scenarios

### RLP-001: Token Cleanup on Mount
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| RLP-001-A | Stale token in localStorage | `seoindicator_token: "expired_token"` | Token removed, login form shown |
| RLP-001-B | Valid token in localStorage | `seoindicator_token: "valid_token"` | Token preserved, redirect to dashboard |
| RLP-001-C | No token in localStorage | Empty storage | Login form shown immediately |
| RLP-001-D | Corrupted token in localStorage | `seoindicator_token: "{invalid}"` | Token removed, login form shown |

### RLP-002: No Redirect Without Valid Token
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| RLP-002-A | Authenticated with valid user | `isAuthenticated: true, user: {...}` | Redirect to /dashboard |
| RLP-002-B | Authenticated but no user object | `isAuthenticated: true, user: null` | No redirect, show login form |
| RLP-002-C | Token present but API returns 401 | Token in storage, API fails | Token cleared, login form shown |
| RLP-002-D | isLoading state during validation | `isLoading: true` | Show loading state, no redirect |

### RLP-003: Loading State During Validation
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| RLP-003-A | Initial auth check in progress | `isLoading: true, isInitialized: false` | Loading indicator shown |
| RLP-003-B | Auth check completed | `isLoading: false, isInitialized: true` | Login form visible |
| RLP-003-C | Transition from loading to ready | Loading → Ready state | Smooth transition, no flash |

### RLP-004: Redirect Loop Prevention
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| RLP-004-A | Stale token, navigate to /login | Token expired, visit /login | Token cleared, form shown |
| RLP-004-B | Rapid navigate between login/dashboard | Navigate back and forth | No infinite loop |
| RLP-004-C | Token expires while on login page | Token becomes invalid | No redirect attempt |
| RLP-004-D | Multiple tabs with same session | Login in one tab, other tab has stale token | Other tab clears token |

### RLP-005: Login Form Immediate Render
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| RLP-005-A | Unauthenticated user visits /login | No token, visit /login | Form visible immediately |
| RLP-005-B | User logs out, stays on login | Logout action | Form shown, no redirect |
| RLP-005-C | Session expired notification | API returns 401 | Form shown with expired message |

### RLP-006: Integration with AuthContext
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| RLP-006-A | AuthContext initialization | Fresh page load | Loading → Form shown |
| RLP-006-B | AuthContext re-hydration | Page refresh with token | Validates token before redirect |
| RLP-006-C | AuthContext error state | API error during validation | Form shown, error displayed |

### RLP-007: Edge Cases
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| RLP-007-A | Empty token string | `seoindicator_token: ""` | Treated as no token |
| RLP-007-B | Whitespace-only token | `seoindicator_token: "   "` | Treated as no token |
| RLP-007-C | Very old token (expired days ago) | Token expired 7+ days ago | Token cleared |
| RLP-007-D | Token with special characters | `seoindicator_token: "abc\0def"` | Handled gracefully |

### RLP-008: Accessibility During Loading
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| RLP-008-A | Loading state announcement | Screen reader active | aria-live="polite" with "Loading..." |
| RLP-008-B | Form ready announcement | Form becomes visible | Screen reader notified |
| RLP-008-C | Focus management after loading | Loading → Form ready | Focus on email input |

## Mock Data

### Mock User
```typescript
const mockUser = {
  id: 1,
  name: "Test User",
  email: "test@example.com",
  email_verified: true,
  role: "user",
  created_at: "2026-01-01T00:00:00Z",
  updated_at: "2026-03-18T00:00:00Z",
};
```

### Mock Auth Context States
```typescript
const loadingState = {
  user: null,
  isAuthenticated: false,
  isLoading: true,
  isInitialized: false,
  error: null,
};

const authenticatedState = {
  user: mockUser,
  isAuthenticated: true,
  isLoading: false,
  isInitialized: true,
  error: null,
};

const unauthenticatedState = {
  user: null,
  isAuthenticated: false,
  isLoading: false,
  isInitialized: true,
  error: null,
};

const errorState = {
  user: null,
  isAuthenticated: false,
  isLoading: false,
  isInitialized: true,
  error: { message: "Session expired", code: "UNAUTHORIZED" },
};
```

### Mock LocalStorage
```typescript
const mockLocalStorage = {
  seoindicator_token: "mock_token_123",
  seoindicator_user: JSON.stringify(mockUser),
};
```

## Coverage Targets

| Component | Statements | Branches | Functions | Lines |
|-----------|------------|----------|-----------|-------|
| login/page.tsx | 90% | 85% | 95% | 90% |
| AuthContext.tsx (redirect logic) | 90% | 85% | 95% | 90% |

## Test Execution

```bash
# Run login page tests
cd frontend
npm test -- --grep "LoginPage"

# Run redirect prevention tests
npm test -- --grep "Redirect Loop Prevention"

# Run with coverage
npm test -- --coverage --grep "login"
```

## Dependencies
- TASK-37-1: Token validation in AuthContext (To Write Tests)
- AuthContext `isInitialized` flag
- AuthContext `preserveFallbackAuth` behavior

## Notes for Developer

### Key Implementation Points
1. Login page should NOT redirect based on token presence alone
2. Must verify token validity with API before redirect
3. Clear invalid tokens on mount to prevent loops
4. Show loading state during initial auth check
5. Handle edge cases (empty, whitespace, corrupted tokens)

### Code Pattern for Token Cleanup
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

### Code Pattern for Loading State
```tsx
// Show loading state during auth initialization
if (isLoading && !isInitialized) {
  return (
    <div className="flex items-center justify-center min-h-screen" role="status" aria-live="polite">
      <Loader2 className="h-8 w-8 animate-spin" />
      <span className="sr-only">Loading...</span>
    </div>
  );
}
```

## Comments

### PreTester (2026-03-18)
- Analyzed existing login page tests - 27 tests covering basic functionality
- Identified gap: no tests for redirect loop prevention
- Designed 25 new tests for redirect loop scenarios
- Created dedicated test file for RLP (Redirect Loop Prevention) scenarios
- Tests follow existing patterns from LESSONS-LEARNED/PreTester.md
- Coverage targets set at 90% statements, 85% branches

### Dependencies
- TASK-37-1 (Token Validation) is also in "To Write Tests" - tests should be compatible
- Both tasks affect AuthContext.tsx changes
