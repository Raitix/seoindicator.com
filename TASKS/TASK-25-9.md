# TASK-25-9: Handle Account Linking (Email Match)

## STATUS
`To Write Tests`

## PRIORITY
`High`

## RISK
`High`

## Related
- Specification: [SPEC-025](../SPECIFICATIONS/SPEC-025.md)
- Feature: [FEAT-025](../FEATURES/FEAT-025.md)

---

## Description
Implement logic to link Google OAuth accounts with existing email-based accounts when email addresses match. This prevents duplicate accounts and provides seamless user experience.

---

## Acceptance Criteria
- [ ] When Google email matches existing user, link accounts
- [ ] Social account record created with user_id of existing user
- [ ] User's email marked as verified (Google verified)
- [ ] User's avatar updated from Google if not already set
- [ ] Security consideration: Verify Google email is verified
- [ ] Optional: Require password confirmation before linking
- [ ] Audit log for account linking events
- [ ] Handle edge case: Multiple social accounts same provider

---

## Dependencies
- TASK-25-7 (GoogleOAuthService - implements linking logic)

---

## Effort Estimate
`Small` (2-3 hours)

---

## Technical Notes
```php
protected function findOrCreateUser(SocialiteUser $googleUser): User
{
    // Find existing user by email
    $user = User::where('email', $googleUser->getEmail())->first();
    
    if ($user) {
        // Link Google account to existing user
        $this->linkSocialAccount($user, $googleUser);
        return $user;
    }
    
    // Create new user
    return $this->createUserFromGoogle($googleUser);
}

protected function linkSocialAccount(User $user, SocialiteUser $googleUser): void
{
    // Check if already linked
    $existing = SocialAccount::where('provider', 'google')
        ->where('provider_id', $googleUser->getId())
        ->first();
    
    if (!$existing) {
        SocialAccount::create([
            'user_id' => $user->id,
            'provider' => 'google',
            'provider_id' => $googleUser->getId(),
            'provider_token' => $googleUser->token,
            'provider_refresh_token' => $googleUser->refreshToken,
        ]);
    }
    
    // Mark email verified (Google verified it)
    if (!$user->hasVerifiedEmail()) {
        $user->markEmailAsVerified();
    }
}
```

---

## Comments
_Agent communication log_

### [2026-03-01 15:18 GMT+2] Agent: ProjectManager
**Prioritization Analysis:**

**Priority Confirmed: High**
- Security-critical account linking functionality
- Prevents duplicate accounts when Google email matches existing user
- Required for seamless user experience in Google OAuth flow
- Part of Phase 2 (Google OAuth) in SPEC-025

**Risk Confirmed: High**
- Account linking has security implications (potential account hijacking)
- Must verify Google email is verified before linking
- Audit logging required for security tracking
- Edge cases: Multiple social accounts same provider, password confirmation

**Dependency Status:**
- TASK-25-7 (GoogleOAuthService): `To Write Tests` ✅
  - Implements base `findOrCreateUser()` method
  - This task details the linking requirements
  - Can proceed in parallel with TASK-25-7

**Notes for PreTester:**
- Test account linking when Google email matches existing user
- Test email verification marking (Google verified)
- Test avatar update from Google if not already set
- Test security: Verify Google email is verified before linking
- Test audit logging for account linking events
- Test edge case: Multiple social accounts same provider
- Test optional: Password confirmation before linking

Status updated: `To Write Tests`

### [2026-02-28 20:55 GMT+2] Agent: SpecSystemAnalyst
Created task as part of SPEC-025 breakdown (Phase 2: Google OAuth).

**High Risk**: Account linking security implications - must verify Google email.

---
*Created by SpecSystemAnalyst*
