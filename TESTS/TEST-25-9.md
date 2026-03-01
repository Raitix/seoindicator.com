# TEST-25-9: Handle Account Linking (Email Match) Tests

## Overview
Test suite for TASK-25-9: Implement logic to link Google OAuth accounts with existing email-based accounts when email addresses match. Security-critical functionality to prevent duplicate accounts and ensure proper identity verification.

---

## Security Considerations

**This is a HIGH RISK task** due to:
- Account hijacking potential if email verification is bypassed
- Identity confusion if wrong accounts are linked
- Audit trail requirements for security compliance
- Multiple edge cases around email verification status

---

## Test Categories

### 1. Basic Account Linking Tests (BAL)
Tests for core account linking functionality when Google email matches existing user.

| Test ID | Description | Expected Result |
|---------|-------------|-----------------|
| BAL-001 | Google email matches existing user - links accounts | SocialAccount created with existing user_id |
| BAL-002 | Existing user password preserved | Password remains unchanged after linking |
| BAL-003 | User's email marked as verified | email_verified_at set (Google verified) |
| BAL-004 | Avatar updated from Google if not already set | User avatar updated |
| BAL-005 | Avatar NOT updated if already set | Existing avatar preserved |
| BAL-006 | Social account tokens stored encrypted | provider_token encrypted in DB |
| BAL-007 | Refresh token stored for future use | provider_refresh_token set |
| BAL-008 | Token expiration tracked | provider_token_expires_at set |
| BAL-009 | Returns existing user (not new) | Same user_id returned |
| BAL-010 | No duplicate user created | users table count unchanged |

### 2. Email Verification Tests (EV)
Tests for email verification handling during account linking.

| Test ID | Description | Expected Result |
|---------|-------------|-----------------|
| EV-001 | Unverified user becomes verified via Google | email_verified_at set when Google links |
| EV-002 | Already verified user stays verified | email_verified_at unchanged |
| EV-003 | Google email MUST be verified to auto-verify | Only verified Google emails trigger auto-verify |
| EV-004 | Unverified Google email does NOT verify user | email_verified_at remains null |
| EV-005 | Verification timestamp is recent | email_verified_at set to current time |
| EV-006 | Multiple links don't change verification time | First verification preserved |

### 3. Security Validation Tests (SV)
Tests for security-critical account linking validations.

| Test ID | Description | Expected Result |
|---------|-------------|-----------------|
| SV-001 | Google email verified check before linking | Unverified Google email rejected or logged |
| SV-002 | Audit log entry created for linking | Security event logged with details |
| SV-003 | Cannot link same Google account twice | Unique constraint on (provider, provider_id) |
| SV-004 | Multiple social providers allowed per user | Can have Google + GitHub + Facebook |
| SV-005 | Provider uniqueness enforced across users | Google ID can only link to one user |
| SV-006 | Account linking idempotent | Multiple calls with same data safe |
| SV-007 | Email case sensitivity handled correctly | John@Example.com matches john@example.com |
| SV-008 | Email normalization before matching | Trimmed, lowercase comparison |
| SV-009 | Audit log includes IP and timestamp | Security trail complete |
| SV-010 | Rate limiting on linking attempts | Prevents brute force |

### 4. Existing Social Account Tests (ESA)
Tests for handling users who already have social accounts.

| Test ID | Description | Expected Result |
|---------|-------------|-----------------|
| ESA-001 | User with GitHub account links Google | Second social account created |
| ESA-002 | User with existing Google account re-auths | Tokens updated, not duplicated |
| ESA-003 | Same Google ID different email - reject | Error or security alert |
| ESA-004 | Multiple Google accounts same user | Only one Google account per user |
| ESA-005 | Social account list updated correctly | User->socialAccounts includes new |

### 5. Edge Case Tests (EC)
Tests for edge cases and unusual scenarios.

| Test ID | Description | Expected Result |
|---------|-------------|-----------------|
| EC-001 | User with soft-deleted account | Treat as new user |
| EC-002 | Email with + addressing (user+tag@gmail.com) | Exact match required |
| EC-003 | Unicode email addresses | Handled correctly |
| EC-004 | Very long email (500+ chars) | Validation applied |
| EC-005 | Empty or null email from Google | Error thrown |
| EC-006 | Email changed on Google side | Links to original matched email |
| EC-007 | Concurrent linking requests | No race condition |
| EC-008 | Transaction rollback on partial failure | No partial data |
| EC-009 | User deleted during linking flow | Graceful error handling |
| EC-010 | Database connection lost mid-transaction | Proper cleanup |

### 6. Avatar Handling Tests (AV)
Tests for avatar URL handling during account linking.

| Test ID | Description | Expected Result |
|---------|-------------|-----------------|
| AV-001 | User has no avatar - Google provides one | Avatar updated from Google |
| AV-002 | User has avatar - Google provides different | User's avatar preserved |
| AV-003 | Google provides no avatar | User's avatar unchanged (null stays null) |
| AV-004 | Invalid Google avatar URL | Stored as-is (validation elsewhere) |
| AV-005 | Very long avatar URL | Truncated to max length |

### 7. Password Confirmation Tests (PC)
Tests for optional password confirmation feature.

| Test ID | Description | Expected Result |
|---------|-------------|-----------------|
| PC-001 | Password confirmation required flag true | Requires password before linking |
| PC-002 | Password confirmation required flag false | Links without password |
| PC-003 | Correct password provided | Linking proceeds |
| PC-004 | Incorrect password provided | Linking rejected |
| PC-005 | User has no password (OAuth-only) | Skip password check |
| PC-006 | Rate limiting on password attempts | Prevents brute force |

### 8. Audit Log Tests (AL)
Tests for audit logging functionality.

| Test ID | Description | Expected Result |
|---------|-------------|-----------------|
| AL-001 | Audit log created on successful link | Entry in audit_logs table |
| AL-002 | Audit log includes user_id | Correct user referenced |
| AL-003 | Audit log includes provider | 'google' recorded |
| AL-004 | Audit log includes provider_id | Google ID recorded |
| AL-005 | Audit log includes timestamp | accurate created_at |
| AL-006 | Audit log includes IP address | Request IP captured |
| AL-007 | Audit log includes user agent | Browser info captured |
| AL-008 | Failed link attempt logged | Failed events recorded |
| AL-009 | Audit log queryable by user | Can retrieve user's history |
| AL-010 | Audit log immutable | Cannot be modified after creation |

### 9. Integration Tests (INT)
End-to-end integration tests.

| Test ID | Description | Expected Result |
|---------|-------------|-----------------|
| INT-001 | Full linking flow works end-to-end | User can login both ways |
| INT-002 | User can login with password after link | Password still works |
| INT-003 | User can login with Google after link | OAuth still works |
| INT-004 | Linked account appears in profile | Social account visible |
| INT-005 | Unlink account removes social record | User can unlink |
| INT-006 | Relink same account after unlink | Works as expected |
| INT-007 | Session persists through linking | User stays logged in |
| INT-008 | API returns linked providers | /api/user includes providers |

### 10. Security Attack Tests (SA)
Tests for security attack scenarios.

| Test ID | Description | Expected Result |
|---------|-------------|-----------------|
| SA-001 | Account hijacking via email change | Prevented by provider_id check |
| SA-002 | CSRF attack on linking endpoint | Token validation prevents |
| SA-003 | Race condition (simultaneous links) | Only one succeeds |
| SA-004 | Email enumeration via linking | No information leaked |
| SA-005 | SQL injection in email field | Parameterized queries prevent |
| SA-006 | XSS in user data during linking | Sanitized/escaped |
| SA-007 | Replay attack with old tokens | Timestamp/expiration check |
| SA-008 | Malicious redirect after linking | Only whitelisted redirects |

---

## Test File Locations

| Type | File Path |
|------|-----------|
| Unit Tests | `backend/tests/Unit/Services/Auth/AccountLinkingServiceTest.php` |
| Feature Tests | `backend/tests/Feature/Auth/AccountLinkingFlowTest.php` |
| Integration Tests | `backend/tests/Integration/Auth/AccountLinkingIntegrationTest.php` |

---

## Mock Strategy

### SocialiteUser Mock for Account Linking
```php
$mockGoogleUser = Mockery::mock(SocialiteUser::class);
$mockGoogleUser->id = '123456789';
$mockGoogleUser->email = 'existing@example.com'; // Matches existing user
$mockGoogleUser->name = 'John Doe';
$mockGoogleUser->avatar = 'https://lh3.googleusercontent.com/avatar';
$mockGoogleUser->token = 'ya29.a0_access_token';
$mockGoogleUser->refreshToken = '1//refresh_token';
$mockGoogleUser->expiresIn = 3600;
// Critical: email_verified flag
$mockGoogleUser->user['email_verified'] = true;
```

### Existing User Setup
```php
$existingUser = User::factory()->create([
    'email' => 'existing@example.com',
    'password' => bcrypt('password123'),
    'email_verified_at' => null, // Unverified initially
    'avatar' => null, // No avatar
]);
```

---

## Database Assertions

### After Successful Linking
```php
// User unchanged except verification/avatar
$this->assertEquals($existingUser->id, $linkedUser->id);
$this->assertNotNull($linkedUser->email_verified_at);
$this->assertNotNull($linkedUser->avatar);

// Social account created
$this->assertDatabaseHas('social_accounts', [
    'user_id' => $existingUser->id,
    'provider' => 'google',
    'provider_id' => '123456789',
]);

// Password still works
$this->assertTrue(Hash::check('password123', $linkedUser->password));
```

---

## Exception Classes

```php
class AccountLinkingException extends Exception {}
class EmailNotVerifiedException extends AccountLinkingException {}
class PasswordConfirmationRequiredException extends AccountLinkingException {}
class ProviderAlreadyLinkedException extends AccountLinkingException {}
```

---

## Total Tests
- Basic Account Linking Tests: 10
- Email Verification Tests: 6
- Security Validation Tests: 10
- Existing Social Account Tests: 5
- Edge Case Tests: 10
- Avatar Handling Tests: 5
- Password Confirmation Tests: 6
- Audit Log Tests: 10
- Integration Tests: 8
- Security Attack Tests: 8
- **Total: 78 tests**

---

## Key Test Scenarios

### Scenario 1: Happy Path - Unverified User Links Google
```
Given: Existing user with email 'john@example.com', unverified, no avatar
When: Google OAuth with same email, verified
Then: 
  - Social account linked to existing user
  - User's email marked verified
  - Avatar set from Google
  - Password preserved
  - Audit log created
```

### Scenario 2: Already Verified User Links Google
```
Given: Existing verified user with avatar
When: Google OAuth with same email
Then:
  - Social account linked
  - Verification timestamp unchanged
  - Avatar unchanged
  - Password preserved
```

### Scenario 3: Unverified Google Email
```
Given: Existing user, unverified
When: Google OAuth with unverified email
Then:
  - Social account linked (or rejected based on policy)
  - User's email NOT auto-verified
  - Warning logged
```

### Scenario 4: Account Hijacking Attempt
```
Given: Attacker has Google account with email 'victim@example.com'
And: Victim has existing account with different Google ID
When: Attacker tries to link their Google
Then:
  - Linking rejected (Google ID mismatch)
  - Security alert logged
  - No information leaked to attacker
```

### Scenario 5: Password Confirmation Required
```
Given: Existing user with password
And: System requires password confirmation for linking
When: User tries to link Google
Then:
  - Password prompt shown
  - Correct password required before linking
```

---

## Dependencies
- TASK-25-2 (social_accounts table) - Required
- TASK-25-7 (GoogleOAuthService) - Required
- User model with email_verified_at - Required
- Audit logging system - Optional but recommended

---

## Acceptance Criteria Mapping

| Acceptance Criteria | Test IDs |
|---------------------|----------|
| When Google email matches existing user, link accounts | BAL-001, BAL-009, BAL-010 |
| Social account record created with user_id of existing user | BAL-001, ESA-005 |
| User's email marked as verified (Google verified) | EV-001, EV-002, EV-003 |
| User's avatar updated from Google if not already set | AV-001, AV-002 |
| Security consideration: Verify Google email is verified | SV-001, EV-003, EV-004 |
| Optional: Require password confirmation before linking | PC-001 to PC-006 |
| Audit log for account linking events | AL-001 to AL-010 |
| Handle edge case: Multiple social accounts same provider | ESA-004 |

---

*Created by PreTester for TASK-25-9*
