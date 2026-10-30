# TEST-25-4: Password Reset Flow Tests

## Task Reference
- Task: [TASK-25-4](../TASKS/TASK-25-4.md)
- Specification: [SPEC-025](../SPECIFICATIONS/SPEC-025.md)
- Feature: [FEAT-025](../FEATURES/FEAT-025.md)

---

## Overview

Test suite for the password reset flow including:
- `POST /api/v1/auth/forgot-password` - Request password reset link
- `POST /api/v1/auth/reset-password` - Reset password with token

**Security Requirements:**
- Token expiration: 60 minutes
- Rate limiting: 1 request per email per minute
- Generic responses (no email enumeration)
- Token revocation on password reset
- Password strength validation

---

## Test Files

| File | Type | Tests | Description |
|------|------|-------|-------------|
| `PasswordResetControllerTest.php` | Feature | 45 | API endpoint tests |
| `PasswordResetSecurityTest.php` | Security | 25 | Security-focused tests |

---

## Test Scenarios

### Forgot Password Endpoint (FP-*)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| FP-001 | Valid email (exists) | `{ email: "user@example.com" }` | 200, success message, reset email sent |
| FP-002 | Valid email (not exists) | `{ email: "nonexistent@example.com" }` | 200, success message (no enumeration) |
| FP-003 | Missing email | `{ }` | 422, validation error |
| FP-004 | Invalid email format | `{ email: "not-an-email" }` | 422, validation error |
| FP-005 | Empty email | `{ email: "" }` | 422, validation error |
| FP-006 | Rate limit exceeded | 2 requests within 1 minute | 429, rate limit error |
| FP-007 | Rate limit reset | Request after 1 minute | 200, success |
| FP-008 | SQL injection in email | `{ email: "test'; DROP TABLE users;--@example.com" }` | 200 or 422, no SQL error |
| FP-009 | XSS in email | `{ email: "<script>alert('xss')</script>@example.com" }` | 422, validation error |
| FP-010 | Unicode email | `{ email: "tëst@example.com" }` | 200, handled safely |
| FP-011 | Email with whitespace | `{ email: "  user@example.com  " }` | 200, email trimmed |
| FP-012 | Case-insensitive email | `{ email: "USER@EXAMPLE.COM" }` | 200, same as lowercase |

### Reset Password Endpoint (RP-*)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| RP-001 | Valid reset (all fields correct) | Valid token, email, password | 200, new token returned |
| RP-002 | Invalid token | Wrong token value | 422, invalid token error |
| RP-003 | Expired token | Token older than 60 minutes | 422, expired token error |
| RP-004 | Wrong email | Token for different email | 422, invalid token error |
| RP-005 | Missing token | `{ email, password, password_confirmation }` | 422, validation error |
| RP-006 | Missing email | `{ token, password, password_confirmation }` | 422, validation error |
| RP-007 | Missing password | `{ token, email, password_confirmation }` | 422, validation error |
| RP-008 | Missing password_confirmation | `{ token, email, password }` | 422, validation error |
| RP-009 | Password too short | Password < 8 chars | 422, validation error |
| RP-010 | Password no numbers | Password without digits | 422, validation error |
| RP-011 | Password no letters | Password without letters | 422, validation error |
| RP-012 | Password confirmation mismatch | Different passwords | 422, validation error |
| RP-013 | Token already used | Reuse valid token | 422, invalid token error |
| RP-014 | Existing tokens revoked | After reset | All user tokens invalidated |
| RP-015 | New token created | After reset | New Sanctum token returned |
| RP-016 | Password hashed | After reset | Password stored as bcrypt hash |
| RP-017 | SQL injection in token | Malicious token string | 422, no SQL error |
| RP-018 | XSS in password | `<script>` in password | 200, stored safely |

### Token Expiration Tests (TE-*)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| TE-001 | Token 1 minute old | Token created 1 min ago | 200, valid |
| TE-002 | Token 59 minutes old | Token created 59 min ago | 200, valid |
| TE-003 | Token 60 minutes old | Token created 60 min ago | 422, expired |
| TE-004 | Token 61 minutes old | Token created 61 min ago | 422, expired |
| TE-005 | Token 24 hours old | Token created 24 hrs ago | 422, expired |

### Security Tests (SEC-*)

| ID | Scenario | Description |
|----|----------|-------------|
| SEC-001 | No email enumeration | Same response for existing/non-existing emails |
| SEC-002 | Token has sufficient entropy | Token is at least 64 random characters |
| SEC-003 | Rate limiting per email | Different emails have separate rate limits |
| SEC-004 | Rate limiting prevents abuse | Blocks rapid automated requests |
| SEC-005 | Timing attack prevention | Same response time for existing/non-existing emails |
| SEC-006 | Token single use | Token invalidated after successful reset |
| SEC-007 | All sessions invalidated | Password reset logs out all devices |
| SEC-008 | Remember token cleared | Laravel remember_token cleared on reset |

---

## Mock Data

### Valid Reset Request
```json
{
  "token": "abc123def456ghi789jkl012mno345pqr678stu901vwx234yz",
  "email": "user@example.com",
  "password": "NewSecurePassword123",
  "password_confirmation": "NewSecurePassword123"
}
```

### Password Reset Token (Database)
```sql
INSERT INTO password_reset_tokens (email, token, created_at)
VALUES ('user@example.com', '$2y$12$hashedtoken...', NOW());
```

### Rate Limit Key
```
password-reset:user@example.com
```

---

## Coverage Targets

| Category | Target | Tests |
|----------|--------|-------|
| Validation | 100% | 18 tests |
| Security | 100% | 25 tests |
| Rate Limiting | 100% | 6 tests |
| Token Expiration | 100% | 5 tests |
| Token Management | 100% | 6 tests |
| **Total** | **100%** | **70 tests** |

---

## Test Execution

```bash
# Run all password reset tests
php artisan test --filter PasswordReset

# Run feature tests only
php artisan test tests/Feature/Controllers/Api/V1/PasswordResetControllerTest.php

# Run security tests only
php artisan test tests/Security/PasswordResetSecurityTest.php

# Run with coverage
php artisan test tests/Feature/Controllers/Api/V1/PasswordResetControllerTest.php --coverage
```

---

## Dependencies

- TASK-25-1: Laravel Sanctum & Socialite packages (installed)
- TASK-25-3: AuthController (for token management patterns)
- Laravel Password facade (built-in)
- `password_resets` table (Laravel default)

---

## Notes for Developer

### Implementation Requirements

1. **PasswordResetController** with two methods:
   - `forgotPassword(Request)` - Send reset link
   - `resetPassword(Request)` - Reset with token

2. **Routes** (in `routes/api.php`):
   ```php
   Route::post('/auth/forgot-password', [PasswordResetController::class, 'forgotPassword']);
   Route::post('/auth/reset-password', [PasswordResetController::class, 'resetPassword']);
   ```

3. **Rate Limiting**:
   - Key: `password-reset:{email}`
   - Limit: 1 request per 60 seconds
   - Return 429 with `Retry-After` header

4. **Token Expiration**:
   - Default: 60 minutes (Laravel config)
   - Check in `config/auth.php` → `passwords.users.expire`

5. **Token Revocation**:
   ```php
   // Revoke all existing tokens
   $user->tokens()->delete();
   ```

6. **Email Template**:
   - Create branded password reset email
   - Include reset link with token

### Response Format

**Forgot Password (200):**
```json
{
  "success": true,
  "message": "If the email exists, a password reset link has been sent."
}
```

**Reset Password (200):**
```json
{
  "success": true,
  "message": "Password reset successfully.",
  "data": {
    "token": "1|abc123..."
  }
}
```

**Validation Error (422):**
```json
{
  "success": false,
  "message": "Validation failed.",
  "errors": {
    "email": ["The email field is required."]
  }
}
```

**Rate Limit Error (429):**
```json
{
  "success": false,
  "error": {
    "code": "RATE_LIMIT_EXCEEDED",
    "message": "Too many reset attempts. Please try again later.",
    "retry_after": 45
  }
}
```

---

## Comments

### [2026-03-01 14:55 GMT+2] Agent: PreTester
Created test documentation for password reset flow (TASK-25-4).

**Test Coverage:**
- 45 feature tests (validation, endpoints)
- 25 security tests (enumeration, injection, rate limiting)
- Total: 70 tests

**Security Tests Include:**
- Email enumeration prevention
- Rate limiting per email
- Token entropy validation
- Token single-use enforcement
- Session invalidation on reset
- Timing attack prevention

Status: `Tests Coverage In Progress`

---

*Created by PreTester*
