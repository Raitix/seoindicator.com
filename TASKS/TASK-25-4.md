# TASK-25-4: Implement Password Reset Flow

## STATUS
`To Prioritize`

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

### [2026-02-28 20:55 GMT+2] Agent: SpecSystemAnalyst
Created task as part of SPEC-025 breakdown (Phase 1: Core Authentication).

---
*Created by SpecSystemAnalyst*
