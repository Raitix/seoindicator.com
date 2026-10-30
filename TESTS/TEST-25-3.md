# TEST-25-3: AuthController API Tests

## Task Reference
- Task: [TASK-25-3](../TASKS/TASK-25-3.md)
- Specification: [SPEC-025](../SPECIFICATIONS/SPEC-025.md)
- Feature: [FEAT-025](../FEATURES/FEAT-025.md)

---

## Overview
Tests for the AuthController API endpoints covering user registration, login, logout, and user retrieval. Uses Laravel Sanctum for API authentication.

---

## Test Files

| File | Tests | Description |
|------|-------|-------------|
| `tests/Feature/Controllers/Api/V1/AuthControllerTest.php` | 25+ | Controller endpoint tests |
| `tests/Unit/Services/AuthServiceTest.php` | 15+ | Auth service unit tests |

---

## Test Scenarios

### 1. Registration Endpoint (POST /api/v1/auth/register)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| REG-001 | Successful registration | Valid name, email, password | 201, user created with email_verified: false |
| REG-002 | Missing name | No name field | 422 validation error |
| REG-003 | Missing email | No email field | 422 validation error |
| REG-004 | Missing password | No password field | 422 validation error |
| REG-005 | Invalid email format | email="not-an-email" | 422 validation error |
| REG-006 | Duplicate email | Email already exists | 422 validation error |
| REG-007 | Password too short | password="short" | 422 validation error |
| REG-008 | Password no numbers | password="allletters" | 422 validation error |
| REG-009 | Password no letters | password="12345678" | 422 validation error |
| REG-010 | Password confirmation mismatch | password != password_confirmation | 422 validation error |
| REG-011 | Name too long | name > 255 chars | 422 validation error |
| REG-012 | Email too long | email > 255 chars | 422 validation error |
| REG-013 | Response structure | Valid registration | {success, message, data: {user}} |
| REG-014 | Password hashed | Valid registration | Password not in plaintext |
| REG-015 | Verification email sent | Valid registration | Mail sent (mock verification) |

### 2. Login Endpoint (POST /api/v1/auth/login)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| LOG-001 | Successful login | Valid email, password | 200, {user, token} |
| LOG-002 | Invalid email | Non-existent email | 401 unauthorized |
| LOG-003 | Invalid password | Wrong password | 401 unauthorized |
| LOG-004 | Missing email | No email field | 422 validation error |
| LOG-005 | Missing password | No password field | 422 validation error |
| LOG-006 | Rate limiting exceeded | 6 failed attempts within 1 minute | 429 too many requests |
| LOG-007 | Rate limit reset | Wait 1 minute after lockout | Can attempt login again |
| LOG-008 | Remember parameter | remember=true | Token has longer expiration |
| LOG-009 | Response structure | Valid login | {success, data: {user, token}} |
| LOG-010 | Token is valid | Use returned token | Can access protected routes |
| LOG-011 | Unverified email | Login with unverified email | Still allowed to login |
| LOG-012 | SQL injection attempt | email="admin'--" | No injection, treated as string |

### 3. Logout Endpoint (POST /api/v1/auth/logout)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| OUT-001 | Successful logout | Valid token | 200, success message |
| OUT-002 | Token revoked | Try to use revoked token | 401 unauthorized |
| OUT-003 | Unauthenticated | No token | 401 unauthorized |
| OUT-004 | Invalid token | Invalid token | 401 unauthorized |
| OUT-005 | Multiple tokens | User has multiple sessions | Only current token revoked |
| OUT-006 | Response structure | Valid logout | {success, message} |

### 4. Get User Endpoint (GET /api/v1/auth/user)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| USR-001 | Authenticated user | Valid token | 200, user data |
| USR-002 | Unauthenticated | No token | 401 unauthorized |
| USR-003 | Invalid token | Invalid token | 401 unauthorized |
| USR-004 | Response structure | Valid request | {success, data: {user}} |
| USR-005 | User with relationships | User with social accounts | Social accounts included |

### 5. Security Tests

| ID | Scenario | Attack Vector | Expected Behavior |
|----|----------|---------------|-------------------|
| SEC-001 | SQL injection in email | email="admin'--" | No injection, safe query |
| SEC-002 | SQL injection in password | password="' OR 1=1--" | No injection, safe query |
| SEC-003 | XSS in name | name="<script>alert(1)</script>" | Sanitized/escaped |
| SEC-004 | Brute force attack | 100 rapid login attempts | Rate limited after 5 |
| SEC-005 | Token hijacking | Use another user's token | 401 if token doesn't match |
| SEC-006 | Session fixation | Reuse old session | Session regenerated |
| SEC-007 | CSRF protection | Cross-site request | Blocked by Sanctum |
| SEC-008 | Mass assignment | Extra fields in request | Ignored |

### 6. Edge Cases

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| EC-001 | Empty request body | {} | 422 validation error |
| EC-002 | Unicode in name | name="日本語 🎉" | Stored correctly |
| EC-003 | Special chars in password | password="P@ss!#$%^&*()" | Accepted if meets rules |
| EC-004 | Whitespace in email | email=" user@example.com " | Trimmed |
| EC-005 | Case-insensitive email | email="User@Example.com" | Normalized to lowercase |
| EC-006 | Very long password | password > 1000 chars | Handled gracefully |
| EC-007 | Concurrent logins | Multiple simultaneous logins | Each creates separate token |
| EC-008 | Login after password change | Password changed elsewhere | Old tokens revoked |

---

## Mock Data

### User Factory States
```php
// Standard user
User::factory()->create([
    'email' => 'test@example.com',
    'password' => bcrypt('Password123'),
]);

// Unverified user
User::factory()->unverified()->create();

// User with social accounts
User::factory()->hasSocialAccounts()->create();
```

---

## API Request Examples

### Registration
```json
POST /api/v1/auth/register
{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "SecurePassword123",
  "password_confirmation": "SecurePassword123"
}
```

### Login
```json
POST /api/v1/auth/login
{
  "email": "john@example.com",
  "password": "SecurePassword123",
  "remember": true
}
```

### Response Format
```json
{
  "success": true,
  "message": "Registration successful. Please verify your email.",
  "data": {
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "john@example.com",
      "email_verified": false
    }
  }
}
```

---

## Coverage Targets

| Category | Target |
|----------|--------|
| Line Coverage | 95%+ |
| Branch Coverage | 90%+ |
| Method Coverage | 100% |
| Controller Methods | 100% |

---

## Test Execution

```bash
# Run auth controller tests
php artisan test tests/Feature/Controllers/Api/V1/AuthControllerTest.php

# Run with coverage
php artisan test --coverage tests/Feature/Controllers/Api/V1/AuthControllerTest.php

# Run all auth tests
php artisan test --filter=Auth
```

---

## Dependencies
- Laravel Sanctum (authentication)
- Laravel Mail (verification emails)
- TASK-25-1 (Sanctum & Socialite packages)

---

## Notes for Developer

### Route Registration
```php
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login'])
    ->middleware('throttle:5,1');
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/user', [AuthController::class, 'user']);
});
```

### Password Validation Rules
- Minimum 8 characters
- Must contain letters
- Must contain numbers

### Rate Limiting Configuration
```php
// In RouteServiceProvider or route middleware
RateLimiter::for('login', function (Request $request) {
    return Limit::perMinute(5)->by($request->email);
});
```

---

## Comments
_Agent communication log_

### [2026-03-01 14:50 GMT+2] Agent: PreTester
Created test documentation for TASK-25-3 (AuthController).

**Test Coverage:**
- 25+ feature tests for controller endpoints
- 15+ unit tests for AuthService
- Security tests for common attack vectors
- Edge cases for validation and data handling

**Key Test Categories:**
1. Registration (15 scenarios) - validation, duplicate handling, password rules
2. Login (12 scenarios) - authentication, rate limiting, token generation
3. Logout (6 scenarios) - token revocation
4. Get User (5 scenarios) - authenticated user retrieval
5. Security (8 scenarios) - injection, XSS, brute force
6. Edge Cases (8 scenarios) - unicode, whitespace, concurrent access

Ready for test file creation.

---
*Created by PreTester*
