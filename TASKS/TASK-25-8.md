# TASK-25-8: Implement GoogleAuthController

## STATUS
`To Develop`

## PRIORITY
`High`

## RISK
`Medium`

## Related
- Specification: [SPEC-025](../SPECIFICATIONS/SPEC-025.md)
- Feature: [FEAT-025](../FEATURES/FEAT-025.md)

---

## Description
Create the GoogleAuthController with endpoints to initiate Google OAuth and handle the callback. This provides the API surface for Google sign-in.

---

## Acceptance Criteria
- [ ] GoogleAuthController created
- [ ] POST `/api/v1/auth/google` endpoint
  - Returns redirect URL for Google OAuth
  - Includes state parameter for CSRF
- [ ] POST `/api/v1/auth/google/callback` endpoint
  - Accepts `code` and `state` parameters
  - Returns user data + Sanctum token
  - Includes `is_new_user` flag
- [ ] Rate limiting on callback endpoint
- [ ] Proper error responses for:
  - User denied access
  - Invalid state
  - OAuth errors
- [ ] Returns user avatar URL from Google

---

## Dependencies
- TASK-25-7 (GoogleOAuthService)

---

## Effort Estimate
`Small` (2-3 hours)

---

## Technical Notes
```php
class GoogleAuthController
{
    public function redirect(GoogleOAuthService $service)
    {
        return response()->json([
            'redirect_url' => $service->getRedirectUrl(),
        ]);
    }

    public function callback(Request $request, GoogleOAuthService $service)
    {
        $validated = $request->validate([
            'code' => 'required|string',
            'state' => 'required|string',
        ]);

        $result = $service->handleCallback($validated['code'], $validated['state']);
        
        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }
}
```

---

## Comments
_Agent communication log_

### [2026-03-01 16:45 GMT+2] Agent: PreTester
**Test Design Complete**

Created comprehensive test suite for GoogleAuthController with 79 tests across 4 test files:

1. **GoogleAuthControllerTest.php** (48 tests)
   - Redirect endpoint tests (12)
   - Callback endpoint tests (12)
   - Validation tests (8)
   - Error handling tests (4)
   - Response structure tests (5)
   - Edge case tests (7)

2. **GoogleAuthSecurityTest.php** (15 tests)
   - CSRF protection via state parameter
   - SQL injection prevention
   - XSS protection
   - Path traversal prevention
   - No sensitive data in logs/responses
   - HTTPS enforcement
   - Timing attack prevention

3. **GoogleAuthRateLimitTest.php** (8 tests)
   - 5 callbacks per minute per IP
   - Rate limit headers
   - Retry-After on 429

4. **GoogleOAuthIntegrationTest.php** (8 tests)
   - Full OAuth flow end-to-end
   - Existing user login
   - New user registration
   - Token validation
   - Avatar URL storage

**TDD Approach:**
Tests are written before implementation and will fail with 404 until the controller and routes are created.

**Notes for Developer:**
- Controller needs `redirect()` and `callback()` methods
- Mock GoogleOAuthService for controller tests
- Use `throttle:5,1` middleware on callback endpoint
- Return `is_new_user` flag for frontend flow
- Token format: `\d+|[a-zA-Z0-9]+` (Sanctum)

**Status:** `Tests Coverage In Progress` → `To Develop`

### [2026-03-01 15:08 GMT+2] Agent: ProjectManager
**Prioritization Analysis:**

**Priority Confirmed: High**
- Foundation API controller for Google OAuth (Phase 2 of SPEC-025)
- Provides the API surface for entire Google sign-in flow
- Two critical endpoints: `/api/v1/auth/google` (redirect) and `/api/v1/auth/google/callback`
- Required for frontend integration (TASK-25-9)

**Risk Confirmed: Medium**
- Controller implementation is standard Laravel pattern
- Delegates OAuth logic to GoogleOAuthService (TASK-25-7)
- Rate limiting on callback endpoint (security best practice)
- Error handling for OAuth failures (user denied, invalid state)
- Not as high-risk as the service layer (token handling is in TASK-25-7)

**Dependency Status:**
- TASK-25-7 (GoogleOAuthService): `To Write Tests` ✅ IN PROGRESS
  - Tests being designed by PreTester
  - Implementation not started yet
  - This task can proceed to test design in parallel

**Notes for PreTester:**
- Test POST `/api/v1/auth/google` returns redirect URL with state
- Test POST `/api/v1/auth/google/callback` with valid code/state
- Test callback returns user data + Sanctum token + `is_new_user` flag
- Test rate limiting on callback endpoint
- Test error responses:
  - User denied access (Google error)
  - Invalid state (CSRF failure)
  - OAuth errors (network, invalid code)
- Test user avatar URL is included in response
- Mock GoogleOAuthService for controller tests

Status updated: `To Write Tests`

### [2026-02-28 20:55 GMT+2] Agent: SpecSystemAnalyst
Created task as part of SPEC-025 breakdown (Phase 2: Google OAuth).

---
*Created by SpecSystemAnalyst*
