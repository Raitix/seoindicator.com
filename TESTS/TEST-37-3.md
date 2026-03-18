# TEST-37-3: Dashboard Error Handling Tests

## Task Reference
- **Task:** TASK-37-3: Fix Dashboard Error Handling
- **Specification:** SPEC-037: Production Critical Fixes - Auth & Navigation
- **Status:** To Develop

## Overview
Test suite for dashboard page error handling, specifically detecting 401 authentication errors and redirecting to login instead of showing an error message.

---

## Test Files

| File | Tests | Description |
|------|-------|-------------|
| `frontend/__tests__/app/dashboard/error-handling.test.tsx` | 47 | Dashboard 401 error handling tests |

---

## Test Scenarios

### DE-001: 401 Error Detection (4 tests)
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| DE-001.1 | Detects 401 from error.status property | `{ status: 401 }` error | Redirect to /login |
| DE-001.2 | Detects 401 with 'Unauthorized' message | Error with 'Unauthorized' text | Redirect to /login |
| DE-001.3 | Detects 401 from status code 401 | `{ status: 401 }` | Redirect to /login |
| DE-001.4 | Detects 401 from error.response.status | `{ response: { status: 401 } }` | Redirect to /login |

### DE-002: localStorage Clearing on 401 (3 tests)
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| DE-002.1 | Clears auth storage on 401 | 401 error | `clearAuthStorage()` called |
| DE-002.2 | Clears storage before redirect | 401 error | Clear called before push |
| DE-002.3 | No clear for non-401 errors | 500 error | `clearAuthStorage()` not called |

### DE-003: Redirect to Login (3 tests)
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| DE-003.1 | Redirects to /login/ on 401 | 401 error | `router.push('/login')` |
| DE-003.2 | No error message for 401 | 401 error | No error UI shown |
| DE-003.3 | Immediate redirect (no delay) | 401 error | Redirect within 100ms |

### DE-004: No Error Message for Auth Failures (4 tests)
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| DE-004.1 | No error UI for 401 | 401 error | No error message or retry button |
| DE-004.2 | Shows error UI for 500 | 500 error | Error message + retry button shown |
| DE-004.3 | Shows error UI for network errors | Network error | Error UI shown |
| DE-004.4 | Shows error UI for timeout | Timeout error | Error UI shown |

### DE-005: Loading State During Auth Check (3 tests)
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| DE-005.1 | Shows skeleton while fetching | Loading state | Skeleton elements visible |
| DE-005.2 | Shows auth loading state | Auth loading | auth-loading testid present |
| DE-005.3 | Transitions loading to content | Data loaded | Dashboard content shown |

### DE-006: Non-Auth Error Handling (5 tests)
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| DE-006.1 | Retry button for 500 | 500 error | Retry button visible |
| DE-006.2 | Retry button for network errors | Network error | Retry button visible |
| DE-006.3 | Retry button calls mutate | Click retry | `mutate()` called |
| DE-006.4 | Error UI for 404 | 404 error | Error message shown |
| DE-006.5 | Error UI for 403 | 403 error | Error message shown |

### DE-007: Error Status Code Detection (6 tests)
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| DE-007.1 | Detects from error.status | `{ status: 401 }` | Redirect |
| DE-007.2 | Detects from error.response.status | `{ response: { status: 401 } }` | Redirect |
| DE-007.3 | Detects from error.statusCode | `{ statusCode: 401 }` | Redirect |
| DE-007.4 | Detects '401' in message | `'HTTP 401: Unauthorized'` | Redirect |
| DE-007.5 | Detects 'Unauthorized' in message | `'Unauthorized access'` | Redirect |
| DE-007.6 | No redirect for 500 + 'Unauthorized' | `{ status: 500, message: 'Unauthorized' }` | Error UI |

### DE-008: Edge Cases (5 tests)
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| DE-008.1 | Handles null error | `null` | Dashboard renders |
| DE-008.2 | Handles undefined error | `undefined` | Dashboard renders |
| DE-008.3 | Handles error with no message | `{}` | Error UI shown |
| DE-008.4 | Handles string status code | `{ status: '401' }` | Redirect |
| DE-008.5 | Handles unmount during async | Unmount during 401 | No errors thrown |

### DE-009: Integration with Auth Context (3 tests)
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| DE-009.1 | Works when authenticated | Auth context returns authenticated | Redirect on 401 |
| DE-009.2 | Redirects when not authenticated | Auth context returns not auth | Redirect to login |
| DE-009.3 | Shows auth loading state | Auth context loading | Auth loading state |

### DE-010: Acceptance Criteria Verification (6 tests)
| ID | Scenario | Acceptance Criteria |
|----|----------|---------------------|
| DE-010.1 | AC1: Detects 401 error | Dashboard detects 401 error from API |
| DE-010.2 | AC2: Clears localStorage | localStorage cleared on 401 error |
| DE-010.3 | AC3: Redirects to login | User redirected to /login/ |
| DE-010.4 | AC4: No error message | No error message for auth failures |
| DE-010.5 | AC5: Loading state | Loading state shown while checking auth |
| DE-010.6 | AC6: Unit tests | Tests exist (this file) |

### DE-011: Security Considerations (3 tests)
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| DE-011.1 | No sensitive data in error | 500 error | No tokens/passwords in DOM |
| DE-011.2 | Clears tokens before redirect | 401 error | Clear before push |
| DE-011.3 | Handles XSS in error message | XSS in error message | No script execution |

### DE-012: Performance (2 tests)
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| DE-012.1 | Fast 401 redirect | 401 error | Redirect < 50ms |
| DE-012.2 | No unnecessary re-renders | Same error twice | Single redirect |

---

## Mock Data

### Mock User
```typescript
const mockUser = {
  id: 1,
  name: "John Doe",
  email: "john@example.com",
  email_verified: true,
};
```

### Mock Dashboard Data
```typescript
const mockDashboardData = {
  stats: {
    totalAnalyses: 127,
    thisMonth: 23,
    thisWeek: 8,
    today: 2,
  },
  costSummary: {
    totalCost: 12.45,
    thisMonthCost: 3.20,
    currency: "USD",
  },
  recentAnalyses: [],
  quickInsights: null,
};
```

### ApiError Class
```typescript
class ApiError extends Error {
  status: number;

  constructor(message: string, status: number) {
    super(message);
    this.status = status;
    this.name = "ApiError";
  }
}
```

---

## Coverage Targets

| Component | Statements | Branches | Functions | Lines |
|-----------|------------|----------|-----------|-------|
| Dashboard Page | 90% | 85% | 95% | 90% |

---

## Test Execution

```bash
# Run all dashboard error handling tests
npm test -- __tests__/app/dashboard/error-handling.test.tsx

# Run with coverage
npm test -- __tests__/app/dashboard/error-handling.test.tsx --coverage

# Run specific test category
npm test -- __tests__/app/dashboard/error-handling.test.tsx -t "DE-001"
```

---

## Dependencies

### Required Mocks
- `next/navigation` - useRouter, useSearchParams
- `@/lib/contexts/AuthContext` - useAuth
- `swr` - useSWR
- `@/lib/api/dashboard` - fetchDashboard
- `@/lib/api/auth` - clearAuthStorage, storeUserData, etc.

### Test Utilities
- `@testing-library/react` - render, screen, waitFor, act
- `vitest` - vi, describe, it, expect, beforeEach, afterEach

---

## Notes for Developer

### Implementation Required
The current dashboard page does not distinguish between 401 errors and other errors. The following changes are needed:

1. **Error Status Detection:** Add logic to detect 401 status from various error formats:
   - `error.status === 401`
   - `error.response?.status === 401`
   - `error.statusCode === 401`
   - `error.message?.includes('401')`
   - `error.message?.includes('Unauthorized')` (with status check)

2. **Auth Storage Clearing:** Import and call `clearAuthStorage()` from `@/lib/api/auth` before redirecting.

3. **Redirect Logic:** Use `router.push('/login')` for 401 errors.

4. **Error UI:** Only show error UI (retry button, error message) for non-401 errors.

### Example Implementation
```tsx
// In DashboardPage component
import { clearAuthStorage } from "@/lib/api/auth";

// Helper to check if error is 401
function isAuthError(error: unknown): boolean {
  if (!error) return false;
  
  const status = (error as any).status ?? 
                 (error as any).response?.status ?? 
                 (error as any).statusCode;
  
  if (status === 401 || status === '401') return true;
  
  // Also check message for 'Unauthorized' (only if status is 401)
  const message = (error as Error).message?.toLowerCase() || '';
  if (message.includes('unauthorized') && status === 401) return true;
  if (message.includes('401')) return true;
  
  return false;
}

// In error handling
if (error) {
  if (isAuthError(error)) {
    clearAuthStorage();
    router.push('/login');
    return null;
  }
  
  // Show error UI for other errors
  return <ErrorUI />;
}
```

---

## Comments

### PreTester Test Design (2026-03-18 13:26 GMT+2)
**Status:** To Develop ✅
**Tests Written:** 47 test scenarios across 12 categories
**Test File:** `frontend/__tests__/app/dashboard/error-handling.test.tsx`

**Test Results (Pre-Implementation):**
- Passed: 21 tests (non-401 error handling, loading states, existing functionality)
- Failed: 26 tests (401-specific detection and handling - requires implementation)

**Next Agent:** Developer - Implement 401 error handling in dashboard page

---

Last updated: 2026-03-18
