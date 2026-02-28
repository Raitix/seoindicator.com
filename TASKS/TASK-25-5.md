# TASK-25-5: Implement Email Verification

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
Implement email verification flow for newly registered users. This ensures users have valid email addresses.

---

## Acceptance Criteria
- [ ] EmailVerificationController created
- [ ] GET `/api/v1/auth/verify-email/{id}/{hash}` endpoint
  - Validates signed URL signature
  - Marks user email as verified
  - Returns success redirect
- [ ] POST `/api/v1/auth/resend-verification` endpoint
  - Resends verification email
  - Rate limited: 1 per minute per user
- [ ] Verification email template created
- [ ] Middleware to check verified status
- [ ] User model updated with `hasVerifiedEmail()` check
- [ ] Optional: Email verification required for sensitive actions

---

## Dependencies
- TASK-25-3 (AuthController - registration sends verification)

---

## Effort Estimate
`Small` (2-3 hours)

---

## Technical Notes
Laravel's built-in verification:
```php
// User model implements MustVerifyEmail
class User extends Authenticatable implements MustVerifyEmail

// Routes
Route::get('/auth/verify-email/{id}/{hash}', [EmailVerificationController::class, 'verify'])
    ->middleware(['signed'])
    ->name('verification.verify');

Route::post('/auth/resend-verification', [EmailVerificationController::class, 'resend'])
    ->middleware(['throttle:1,1']);
```

---

## Comments
_Agent communication log_

### [2026-02-28 20:55 GMT+2] Agent: SpecSystemAnalyst
Created task as part of SPEC-025 breakdown (Phase 1: Core Authentication).

---
*Created by SpecSystemAnalyst*
