# TEST-25-8: GoogleAuthController Tests

## Overview
Test suite for TASK-25-8: Implement GoogleAuthController for Google OAuth 2.0 API endpoints.

---

## Task Reference
- Task: [TASK-25-8](../TASKS/TASK-25-8.md)
- Specification: [SPEC-025](../SPECIFICATIONS/SPEC-025.md)
- Feature: [FEAT-025](../FEATURES/FEAT-025.md)
- Dependency: TASK-25-7 (GoogleOAuthService)

---

## API Endpoints Under Test

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/v1/auth/google` | Initiate Google OAuth (returns redirect URL) |
| POST | `/api/v1/auth/google/callback` | Handle OAuth callback (code + state) |

---

## Test Categories

### 1. Redirect Endpoint Tests (RE)
Tests for POST `/api/v1/auth/google` - OAuth initiation endpoint.

| Test ID | Description | Input | Expected Output |
|---------|-------------|-------|-----------------|
| RE-001 | Returns redirect URL | Valid request | 200, `{redirect_url: "https://accounts.google.com/..."}` |
| RE-002 | URL contains state parameter | Valid request | State parameter in URL |
| RE-003 | URL contains client_id | Valid request | Client ID from config |
| RE-004 | URL contains redirect_uri | Valid request | Redirect URI from config |
| RE-005 | URL contains scope | Valid request | email, profile scopes |
| RE-006 | Works without authentication | No auth header | 200 (public endpoint) |
| RE-007 | Rate limiting not applied | 10 rapid requests | All return 200 |
| RE-008 | Different state on each call | 2 requests | Different state values |
| RE-009 | URL is valid Google OAuth URL | Valid request | URL starts with accounts.google.com |
| RE-010 | Response structure | Valid request | `{redirect_url: string}` |
| RE-011 | No sensitive data in response | Valid request | No client_secret exposed |
| RE-012 | Works with authenticated user | Authenticated | 200, can link account |

### 2. Callback Endpoint Tests (CB)
Tests for POST `/api/v1/auth/google/callback` - OAuth callback processing.

| Test ID | Description | Input | Expected Output |
|---------|-------------|-------|-----------------|
| CB-001 | Valid callback returns user data | Valid code + state | 200, user + token |
| CB-002 | Returns is_new_user flag | New user | `{is_new_user: true}` |
| CB-003 | Returns is_new_user false | Existing user | `{is_new_user: false}` |
| CB-004 | Returns Sanctum token | Valid callback | `{token: "1\|..."}` |
| CB-005 | Returns user avatar URL | Valid callback | `{avatar: "https://..."}` |
| CB-006 | Returns user object | Valid callback | `{id, name, email, email_verified}` |
| CB-007 | Email verified for Google users | Valid callback | `email_verified: true` |
| CB-008 | Missing code returns 422 | No code | 422 validation error |
| CB-009 | Missing state returns 422 | No state | 422 validation error |
| CB-010 | Invalid state returns error | Invalid state | 400, InvalidOAuthStateException |
| CB-011 | Invalid code returns error | Invalid code | 400, OAuth failed |
| CB-012 | Success response structure | Valid callback | `{success: true, data: {...}}` |
| CB-013 | Rate limiting on callback | 6th request/min | 429 Too Many Requests |
| CB-014 | Rate limit headers present | Any request | X-RateLimit-* headers |
| CB-015 | Rate limit per IP | Different IPs | Independent limits |

### 3. Rate Limiting Tests (RL)
Tests for rate limiting on callback endpoint.

| Test ID | Description | Input | Expected Output |
|---------|-------------|-------|-----------------|
| RL-001 | 5 callbacks allowed per minute | 5 rapid requests | All 200 |
| RL-002 | 6th callback blocked | 6th request within minute | 429 Too Many Requests |
| RL-003 | Rate limit per IP | 5 from IP A, 5 from IP B | All 200 |
| RL-004 | Rate limit resets after minute | Wait 60s after 5 | 6th succeeds |
| RL-005 | Rate limit headers present | Any request | X-RateLimit-Limit, X-RateLimit-Remaining |
| RL-006 | Retry-After header on 429 | After rate limited | `Retry-After: <seconds>` |
| RL-007 | Rate limit error response | After limit | `{error: "rate_limit_exceeded", retry_after: <seconds>}` |
| RL-008 | Rate limit does not affect redirect | 10 redirect requests | All 200 |

### 4. Error Handling Tests (EH)
Tests for error scenarios.

| Test ID | Description | Input | Expected Output |
|---------|-------------|-------|-----------------|
| EH-001 | User denied access | Google returns `error=access_denied` | 400, `{error: "access_denied"}` |
| EH-002 | Invalid state parameter | Random state | 400, `{error: "invalid_state"}` |
| EH-003 | Expired state | State older than 10 min | 400, `{error: "invalid_state"}` |
| EH-004 | OAuth service error | Service throws exception | 500, generic error message |
| EH-005 | Network timeout | Service timeout | 504, `{error: "timeout"}` |
| EH-006 | Invalid authorization code | Bad code from Google | 400, `{error: "invalid_grant"}` |
| EH-007 | Google API error | Google returns 500 | 502, `{error: "oauth_failed"}` |
| EH-008 | Missing Google credentials | Config missing | 500, `{error: "configuration_error"}` |
| EH-009 | Error response structure | Any error | `{success: false, error: {code, message}}` |
| EH-010 | No stack trace in production | Any error | No stack trace exposed |

### 5. Validation Tests (VL)
Tests for request validation.

| Test ID | Description | Input | Expected Output |
|---------|-------------|-------|-----------------|
| VL-001 | Code required | Missing code | 422, validation error |
| VL-002 | State required | Missing state | 422, validation error |
| VL-003 | Code must be string | Code as array | 422, validation error |
| VL-004 | State must be string | State as number | 422, validation error |
| VL-005 | Code max length | Code > 1000 chars | 422, validation error |
| VL-006 | State max length | State > 100 chars | 422, validation error |
| VL-007 | Validation error format | Invalid input | `{message, errors: {field: [messages]}}` |
| VL-008 | Empty request body | `{}` | 422, multiple validation errors |

### 6. Security Tests (SEC)
Tests for security-critical functionality.

| Test ID | Description | Input | Expected Output |
|---------|-------------|-------|-----------------|
| SEC-001 | CSRF protection via state | Invalid state | 400, state mismatch |
| SEC-002 | State cannot be reused | Same state twice | 400, invalid state |
| SEC-003 | No token in URL | Check redirect URL | Token not in query params |
| SEC-004 | SQL injection in code | `' OR 1=1` in code | Sanitized, error or 400 |
| SEC-005 | SQL injection in state | `' DROP TABLE` in state | Sanitized, error or 400 |
| SEC-006 | XSS in error messages | `<script>` in input | Escaped in response |
| SEC-007 | Path traversal in code | `../../etc/passwd` | Sanitized or rejected |
| SEC-008 | No sensitive data in logs | Check log output | No tokens logged |
| SEC-009 | HTTPS enforced | Check redirect URL | https://accounts.google.com |
| SEC-010 | Timing attack prevention | Multiple failed attempts | Consistent response time |

### 7. Integration Tests (INT)
Tests for end-to-end OAuth flow.

| Test ID | Description | Input | Expected Output |
|---------|-------------|-------|-----------------|
| INT-001 | Full OAuth flow | Redirect → Callback | User created, token returned |
| INT-002 | Existing user login | OAuth with existing email | User found, token returned |
| INT-003 | New user registration | OAuth with new email | User created, token returned |
| INT-004 | Token is valid | Use returned token | Can access protected routes |
| INT-005 | User can logout | Delete token | Token invalidated |
| INT-006 | Avatar URL stored | Valid OAuth | User has avatar_url |
| INT-007 | Multiple OAuth sessions | OAuth twice | Same user, new token |
| INT-008 | Response includes all user fields | Valid callback | id, name, email, avatar, email_verified |

### 8. Response Structure Tests (RS)
Tests for response format consistency.

| Test ID | Description | Input | Expected Output |
|---------|-------------|-------|-----------------|
| RS-001 | Redirect response structure | Valid redirect request | `{redirect_url: string}` |
| RS-002 | Success callback structure | Valid callback | `{success: true, data: {user, token, is_new_user}}` |
| RS-003 | User object structure | Valid callback | `{id, name, email, avatar, email_verified}` |
| RS-004 | Error response structure | Any error | `{success: false, error: {code, message}}` |
| RS-005 | Validation error structure | Invalid input | `{message, errors: {...}}` |
| RS-006 | Rate limit error structure | After limit | `{success: false, error: {code: "rate_limit_exceeded", ...}}` |

### 9. Edge Cases Tests (EC)
Tests for edge cases and special scenarios.

| Test ID | Description | Input | Expected Output |
|---------|-------------|-------|-----------------|
| EC-001 | Very long authorization code | 1000+ char code | Handled gracefully |
| EC-002 | Unicode in state | `你好世界` in state | URL encoded properly |
| EC-003 | Special characters in code | `!@#$%^&*()` in code | URL decoded properly |
| EC-004 | Empty string code | `code: ""` | 422, validation error |
| EC-005 | Whitespace in code | `code: "   "` | Trimmed or rejected |
| EC-006 | Concurrent callback requests | Same state, 2 requests | One succeeds, one fails |
| EC-007 | Google returns no email | OAuth without email | Error: email required |
| EC-008 | Google returns no name | OAuth without name | Default name used |
| EC-009 | Very long user name | 1000+ char name | Truncated to 255 chars |
| EC-010 | Missing avatar | Google returns no avatar | `avatar: null` |

---

## Test Files

| Type | File Path |
|------|-----------|
| Feature Tests | `backend/tests/Feature/Api/V1/Auth/GoogleAuthControllerTest.php` |
| Security Tests | `backend/tests/Security/Auth/GoogleAuthSecurityTest.php` |
| Rate Limit Tests | `backend/tests/Feature/Api/V1/Auth/GoogleAuthRateLimitTest.php` |

---

## Mock Strategy

### GoogleOAuthService Mock
```php
// Mock the service for controller tests
$mockService = Mockery::mock(GoogleOAuthService::class);
$mockService->shouldReceive('getRedirectUrl')
    ->once()
    ->andReturn('https://accounts.google.com/o/oauth2/auth?...');

$mockService->shouldReceive('handleCallback')
    ->with('valid_code', 'valid_state')
    ->once()
    ->andReturn([
        'user' => $user,
        'token' => '1|abc123...',
        'is_new_user' => true,
    ]);

$this->app->instance(GoogleOAuthService::class, $mockService);
```

### Exception Mock
```php
$mockService->shouldReceive('handleCallback')
    ->with('invalid_code', 'valid_state')
    ->once()
    ->andThrow(new InvalidOAuthStateException());
```

---

## Test Data

### Valid OAuth Response
```json
{
  "success": true,
  "data": {
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "john@example.com",
      "email_verified": true,
      "avatar": "https://lh3.googleusercontent.com/..."
    },
    "token": "1|abc123def456...",
    "is_new_user": false
  }
}
```

### Redirect Response
```json
{
  "redirect_url": "https://accounts.google.com/o/oauth2/auth?client_id=...&redirect_uri=...&state=...&scope=email+profile&response_type=code"
}
```

### Error Response
```json
{
  "success": false,
  "error": {
    "code": "invalid_state",
    "message": "Invalid OAuth state parameter. Please try again."
  }
}
```

### Validation Error Response
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "code": ["The code field is required."],
    "state": ["The state field is required."]
  }
}
```

---

## Exception Classes Used

| Exception | HTTP Code | Error Code | Description |
|-----------|-----------|------------|-------------|
| `InvalidOAuthStateException` | 400 | `invalid_state` | CSRF state mismatch |
| `OAuthFailedException` | 400 | `oauth_failed` | General OAuth failure |
| `AccessDeniedException` | 400 | `access_denied` | User denied access |
| `ConfigurationException` | 500 | `configuration_error` | Missing OAuth config |
| `ThrottleRequestsException` | 429 | `rate_limit_exceeded` | Too many requests |

---

## Rate Limiting Configuration

| Endpoint | Limit | Window | Key |
|----------|-------|--------|-----|
| `/api/v1/auth/google/callback` | 5 | per minute | IP address |
| `/api/v1/auth/google` | None | - | - |

---

## Total Tests

| Category | Count |
|----------|-------|
| Redirect Endpoint Tests | 12 |
| Callback Endpoint Tests | 15 |
| Rate Limiting Tests | 8 |
| Error Handling Tests | 10 |
| Validation Tests | 8 |
| Security Tests | 10 |
| Integration Tests | 8 |
| Response Structure Tests | 6 |
| Edge Cases Tests | 10 |
| **Total** | **87 tests** |

---

## Coverage Targets

| Metric | Target |
|--------|--------|
| Line Coverage | ≥ 90% |
| Branch Coverage | ≥ 85% |
| Method Coverage | 100% |

---

## Test Execution

```bash
# Run all GoogleAuthController tests
php artisan test --filter GoogleAuth

# Run specific test file
php artisan test tests/Feature/Api/V1/Auth/GoogleAuthControllerTest.php

# Run with coverage
php artisan test --coverage --filter GoogleAuth
```

---

## Dependencies

- TASK-25-1 (Sanctum & Socialite) - Required
- TASK-25-2 (social_accounts table) - Required
- TASK-25-6 (Google OAuth Credentials) - Required
- TASK-25-7 (GoogleOAuthService) - Required
- Laravel Sanctum
- Laravel Socialite

---

## Notes for Developer

1. **Controller Structure:**
   ```php
   class GoogleAuthController
   {
       public function redirect(GoogleOAuthService $service): JsonResponse
       {
           return response()->json([
               'redirect_url' => $service->getRedirectUrl(),
           ]);
       }

       public function callback(Request $request, GoogleOAuthService $service): JsonResponse
       {
           $validated = $request->validate([
               'code' => 'required|string|max:1000',
               'state' => 'required|string|max:100',
           ]);

           $result = $service->handleCallback($validated['code'], $validated['state']);
           
           return response()->json([
               'success' => true,
               'data' => $result,
           ]);
       }
   }
   ```

2. **Rate Limiting:**
   - Apply `throttle:5,1` middleware to callback endpoint
   - Key by IP address, not user
   - Return 429 with `retry_after` field

3. **Error Handling:**
   - Catch all OAuth exceptions
   - Return user-friendly messages
   - Log detailed errors for debugging
   - Never expose stack traces in production

4. **Security Considerations:**
   - State parameter is one-time use (consumed on callback)
   - Validate state before processing code
   - No sensitive data in redirect URL
   - HTTPS only for all OAuth URLs

---

## Comments
_Agent communication log_

### [2026-03-01 16:45 GMT+2] Agent: PreTester
**Test Design Complete**

**Test Files Created:**
1. `backend/tests/Feature/Api/V1/Auth/GoogleAuthControllerTest.php` - 48 tests
   - Redirect Endpoint Tests (RE): 12 tests
   - Callback Endpoint Tests (CB): 12 tests
   - Validation Tests (VL): 8 tests
   - Error Handling Tests (EH): 4 tests
   - Response Structure Tests (RS): 5 tests
   - Edge Cases Tests (EC): 7 tests

2. `backend/tests/Security/Auth/GoogleAuthSecurityTest.php` - 15 tests
   - CSRF Protection Tests: 3 tests
   - Injection Tests: 3 tests
   - Path Traversal Tests: 1 test
   - Logging Security Tests: 1 test
   - HTTPS Tests: 1 test
   - Timing Attack Tests: 1 test
   - Additional Security Tests: 5 tests

3. `backend/tests/Feature/Api/V1/Auth/GoogleAuthRateLimitTest.php` - 8 tests
   - Rate Limiting Tests (RL): 8 tests

4. `backend/tests/Integration/Auth/GoogleOAuthIntegrationTest.php` - 8 tests
   - Integration Tests (INT): 8 tests

**Total Tests: 79 tests**

**Key Test Findings:**
- Redirect endpoint is public (no auth required)
- Callback endpoint has rate limiting (5/min per IP)
- State parameter is one-time use for CSRF protection
- Response must include `is_new_user` flag for frontend flow
- Avatar URL must be returned from Google profile
- Token format: `^\d+\|[a-zA-Z0-9]+$` (Sanctum format)

**TDD Status:**
- Tests are written before implementation
- Tests will fail with 404 until routes/controller are implemented
- All tests use mocked GoogleOAuthService for isolation

**Exception Classes Required:**
- `InvalidOAuthStateException` - HTTP 400, code: `invalid_state`
- `OAuthFailedException` - HTTP 400, code: `oauth_failed`
- `AccessDeniedException` - HTTP 400, code: `access_denied`
- `ConfigurationException` - HTTP 500, code: `configuration_error`

**Status:** `To Develop`

### [2026-03-01 15:45 GMT+2] Agent: PreTester
**Test Design Started**

Creating test suite for GoogleAuthController with 87 test scenarios across 9 categories.

**Key Test Findings:**
- Redirect endpoint is public (no auth required)
- Callback endpoint has rate limiting (5/min per IP)
- State parameter is one-time use for CSRF protection
- Response must include `is_new_user` flag for frontend flow
- Avatar URL must be returned from Google profile

**Status:** Tests Coverage In Progress

---

*Created by PreTester for TASK-25-8*
