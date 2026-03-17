# TASK-37-1: Fix Token Validation in AuthContext

## STATUS
`To Develop`

## PRIORITY
`Critical`

## RISK
`High`

## Specification
SPEC-037

## Description
Fix AuthContext to validate tokens with the API before considering a user authenticated. Currently, the presence of any token in localStorage causes the app to treat the user as authenticated, leading to redirect loops and error pages.

## Problem
1. AuthContext reads token from localStorage
2. Sets `isAuthenticated = true` based on token presence only
3. Redirects to `/dashboard/` before verifying token validity
4. Dashboard API fails with 401
5. Shows error instead of login page

## Solution
Modify `initializeAuth` in `AuthContext.tsx`:
1. Always call `/api/v1/auth/user` to verify token
2. On 401 response, clear localStorage and set user to null
3. Only set `isAuthenticated = true` if API confirms validity
4. Add loading state during token verification

## Acceptance Criteria
- [ ] Token validity verified with API before setting authenticated state
- [ ] Expired tokens are cleared from localStorage
- [ ] Users with expired tokens see login page, not dashboard error
- [ ] Loading state shown while verifying token
- [ ] No redirect to dashboard for invalid tokens
- [ ] Unit tests for token validation scenarios

## Technical Details

### File to Modify
`frontend/lib/contexts/AuthContext.tsx`

### Code Changes
```typescript
// In initializeAuth function:
// Remove the fallback user logic that sets isAuthenticated without API verification
// Always call fetchUser() to verify token
// On any error (401, network, etc), clear storage and set user to null
```

## Dependencies
- None

## Effort Estimate
`Small` (2-3 hours)

## Related Issues
- FEAT-037
- SPEC-037

## Comments

### ProjectManager Assessment (2026-03-16 19:56)
**Priority:** Critical (confirmed) - Authentication bug blocking user login
**Risk:** High (confirmed) - Security-critical token validation changes
**Dependencies:** None - task can proceed immediately

**Rationale:**
- Authentication bug causes redirect loops for users with expired tokens
- Users see error pages instead of login page
- Direct impact on user ability to authenticate
- High business impact - blocks user access to application

**Status Changed:** To Prioritize → To Write Tests

**Handoff to PreTester:**
- Test scenarios needed for token validation logic
- Test expired token handling
- Test localStorage clearing on 401
- Test loading state during verification
- Tests can be designed immediately - no dependencies blocking

### PreTester Test Design (2026-03-17 18:13)
**Status:** To Develop ✅
**Tests Written:** 42 test scenarios across 10 categories
**Test File:** `frontend/__tests__/lib/contexts/AuthContext.token-validation.test.tsx`
**Documentation:** `TESTS/TEST-37-1.md`

**Test Categories:**
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

**Root Cause Identified:**
The bug is in `preserveFallbackAuth()` which sets `user` from localStorage even when the API fails with 401. This causes:
1. `isAuthenticated = true` (because user is not null)
2. Redirect to dashboard
3. Dashboard API fails with 401
4. User sees error instead of login page

**Key Implementation Notes for Developer:**
1. Remove fallback user logic that bypasses API verification
2. Always call `fetchUser()` during initialization - don't set user from localStorage first
3. Clear storage on 401 (UNAUTHORIZED, TOKEN_EXPIRED errors)
4. Preserve stored user only for network errors (not auth errors)
5. Show loading state until API verification complete

**Acceptance Criteria Mapping:**
- AC1 (API verification) → TV-001a, TV-009a
- AC2 (Clear expired tokens) → TV-002a, TV-006a, TV-009b
- AC3 (Show login, not error) → TV-004c, TV-009c
- AC4 (Loading state) → TV-003, TV-009d
- AC5 (No redirect for invalid) → TV-004, TV-009e
- AC6 (Unit tests) → All 42 tests

**Status Changed:** To Write Tests → To Develop

