# TASK-25-3: Implement AuthController (Register, Login, Logout)

## STATUS
`To Develop`

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

### [2026-03-01 14:55 GMT+2] Agent: PreTester
**Test Creation Complete:**

**Test Files Created:**
- `tests/Feature/Controllers/Api/V1/AuthControllerTest.php` - 35 tests
- `TESTS/TEST-25-3.md` - Test documentation

**Test Coverage:**
| Category | Tests | Scenarios |
|----------|-------|-----------|
| Registration | 14 | REG-001 to REG-014 |
| Login | 9 | LOG-001 to LOG-012 |
| Logout | 4 | OUT-001 to OUT-005 |
| Get User | 3 | USR-001 to USR-005 |
| Security | 3 | SEC-001 to SEC-008 |
| Edge Cases | 5 | EC-001 to EC-008 |
| **Total** | **35+** | |

**TDD Status:**
- Tests return 404 (routes not implemented) - expected TDD behavior
- Tests will pass once AuthController is implemented

**Key Test Features:**
1. Password validation (min 8 chars, letters + numbers)
2. Rate limiting (5 attempts per minute on login)
3. Token revocation on logout
4. SQL injection prevention
5. XSS handling in name field
6. Mass assignment protection

**Status:** Updated to `To Develop`

### [2026-03-01 14:35 GMT+2] Agent: ProjectManager
**Prioritization Analysis:**

**Priority Confirmed: High**
- Core authentication API - all other auth features depend on this
- Required for Phase 1 (Core Authentication)
- Business-critical: Users cannot register/login without this
- Medium effort (4-6 hours), high impact

**Risk Confirmed: High**
- Authentication security is critical
- Password handling, token management
- Rate limiting to prevent brute force
- SecurityAuditor MUST review before deployment

**Dependency Status:**
- TASK-25-1 (Sanctum & Socialite packages): `In Development` ⚠️
- Development blocked until TASK-25-1 reaches `To Test Locally` or `Done`
- Tests CAN be written now (TDD approach)

**Test Coverage Requirements (for PreTester):**
1. Registration validation (name, email, password, password_confirmation)
2. Password strength validation (min 8 chars, letters + numbers)
3. Duplicate email rejection
4. Login with valid/invalid credentials
5. Rate limiting (5 attempts per minute)
6. Logout token revocation
7. User retrieval by token
8. Error response formatting

**Execution Order:**
1. TASK-25-1 (In Development) → Complete first
2. TASK-25-3 (this) → Can start test writing now
3. TASK-25-4, 25-5 → Can run in parallel after this

**Notes for Developer:**
- Uses Laravel Sanctum for token management
- Rate limiting via `throttle:5,1` middleware on login
- Form Requests for validation: StoreUserRequest, LoginRequest
- API Resource: UserResource for response formatting

Status updated: `To Write Tests`

### [2026-02-28 20:55 GMT+2] Agent: SpecSystemAnalyst
Created task as part of SPEC-025 breakdown (Phase 1: Core Authentication).

**High Risk**: Authentication security is critical. SecurityAuditor must review.

---
*Created by SpecSystemAnalyst*
