# TASK-25-3: Implement AuthController (Register, Login, Logout)

## STATUS
`To Prioritize`

## PRIORITY
`High`

## RISK
`High`

## Related
- Specification: [SPEC-025](../SPECIFICATIONS/SPEC-025.md)
- Feature: [FEAT-025](../FEATURES/FEAT-025.md)

---

## Description
Create the AuthController with register, login, and logout methods. This is the core authentication API for email-based authentication.

---

## Acceptance Criteria
- [ ] AuthController created with register, login, logout methods
- [ ] POST `/api/v1/auth/register` endpoint
  - Validates: name, email, password, password_confirmation
  - Password: min 8 chars, letters + numbers required
  - Returns user data with email_verified: false
  - Sends verification email
- [ ] POST `/api/v1/auth/login` endpoint
  - Validates: email, password
  - Rate limited: 5 attempts per minute
  - Returns user data + Sanctum token
  - Supports "remember" parameter
- [ ] POST `/api/v1/auth/logout` endpoint
  - Revokes current access token
  - Returns success message
- [ ] GET `/api/v1/auth/user` endpoint
  - Returns authenticated user data
- [ ] AuthService created for business logic
- [ ] Form Requests for validation (StoreUserRequest, LoginRequest)
- [ ] API Resources for responses (UserResource)
- [ ] Proper error responses with validation messages

---

## Dependencies
- TASK-25-1 (Sanctum & Socialite packages)

---

## Effort Estimate
`Medium` (4-6 hours)

---

## Technical Notes
Routes:
```php
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/user', [AuthController::class, 'user']);
});
```

Rate limiting middleware:
```php
Route::middleware('throttle:5,1')->post('/auth/login', ...);
```

---

## Comments
_Agent communication log_

### [2026-02-28 20:55 GMT+2] Agent: SpecSystemAnalyst
Created task as part of SPEC-025 breakdown (Phase 1: Core Authentication).

**High Risk**: Authentication security is critical. SecurityAuditor must review.

---
*Created by SpecSystemAnalyst*
