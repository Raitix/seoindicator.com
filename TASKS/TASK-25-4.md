# TASK-25-4: Implement Password Reset Flow

## STATUS
`To Write Tests`

## PRIORITY
`High`

## RISK
`Medium`

## Related
- Specification: [SPEC-025](../SPECIFICATIONS/SPEC-025.md)
- Feature: [FEAT-025](../FEATURES/FEAT-025.md)

---

## Description
Implement the password reset flow with forgot password and reset password endpoints. This allows users to recover access to their accounts.

---

## Acceptance Criteria
- [ ] PasswordResetController created
- [ ] POST `/api/v1/auth/forgot-password` endpoint
  - Validates: email
  - Sends password reset link email
  - Returns generic success (no email existence disclosure)
- [ ] POST `/api/v1/auth/reset-password` endpoint
  - Validates: token, email, password, password_confirmation
  - Verifies token validity
  - Updates user password
  - Revokes all existing tokens (security)
  - Returns success with new token
- [ ] Password reset email template created
- [ ] Rate limiting: 1 reset request per email per minute
- [ ] Token expiration: 60 minutes
- [ ] Proper error messages for invalid/expired tokens

---

## Dependencies
- TASK-25-3 (AuthController - uses same auth infrastructure)

---

## Effort Estimate
`Medium` (3-4 hours)

---

## Technical Notes
Laravel's built-in password reset:
```php
use Illuminate\Support\Facades\Password;

// Forgot password
$status = Password::sendResetLink($request->only('email'));

// Reset password
$status = Password::reset($credentials, function ($user, $password) {
    $user->forceFill([
        'password' => Hash::make($password)
    ])->setRememberToken(Str::random(60));
    $user->save();
    event(new PasswordReset($user));
});
```

Custom notification for branded emails.

---

## Comments
_Agent communication log_

### [2026-03-01 14:40 GMT+2] Agent: ProjectManager
**Prioritization Analysis:**

**Priority Confirmed: High**
- Essential for production authentication - users need password recovery
- Part of Phase 1 Core Authentication (critical path)
- No workaround - permanent lockout risk without this feature
- Uses Laravel's built-in Password facade (free, no external services)

**Risk Confirmed: Medium**
- Uses proven Laravel Password reset implementation
- Standard token-based reset with 60-minute expiration
- Rate limiting prevents abuse (1 request per email per minute)
- Generic responses prevent email enumeration
- Not High risk because using framework built-in, not custom auth

**Dependency Status:**
- TASK-25-3 (AuthController): `To Develop` ⚠️
- Tests CAN be written now (TDD approach)
- Development blocked until TASK-25-3 reaches `To Test Locally`

**Test Coverage Requirements (for PreTester):**
1. Forgot password validation (email required, valid format)
2. Reset password validation (token, email, password, password_confirmation)
3. Token expiration (60 minutes)
4. Rate limiting (1 request per email per minute)
5. Generic success response (no email enumeration)
6. Password update and existing token revocation
7. Error messages for invalid/expired tokens
8. Password strength validation (min 8 chars, letters + numbers)

**Execution Order:**
1. TASK-25-3 (To Develop) → Complete implementation first
2. TASK-25-4 (this) → Can start test writing now
3. Tests wait for TASK-25-3 to reach To Test Locally before integration tests

**Notes for PreTester:**
- Use Laravel's built-in Password facade (`Password::sendResetLink`, `Password::reset`)
- Custom notification for branded emails
- Token stored in `password_resets` table (Laravel default)
- Rate limiting via middleware or custom logic

Status updated: `To Write Tests`

### [2026-02-28 20:55 GMT+2] Agent: SpecSystemAnalyst
Created task as part of SPEC-025 breakdown (Phase 1: Core Authentication).

---
*Created by SpecSystemAnalyst*
