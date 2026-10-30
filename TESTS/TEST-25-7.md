# TEST-25-7: GoogleOAuthService Tests

## Overview
Test suite for TASK-25-7: Create GoogleOAuthService for Google OAuth 2.0 authentication flow.

---

## Test Categories

### 1. getRedirectUrl() Tests (GR)
Tests for OAuth redirect URL generation with CSRF state protection.

| Test ID | Description | Expected Result |
|---------|-------------|-----------------|
| GR-001 | Returns valid Google OAuth URL | URL contains accounts.google.com |
| GR-002 | URL contains client_id parameter | Client ID from config present |
| GR-003 | URL contains redirect_uri parameter | Redirect URI from config present |
| GR-004 | URL contains state parameter | Random 40-char state generated |
| GR-005 | State stored in cache | Cache has 'oauth_state_{state}' key |
| GR-006 | State expires in 10 minutes | Cache TTL is 600 seconds |
| GR-007 | URL contains response_type=code | Authorization code flow |
| GR-008 | URL contains scope parameter | email and profile scopes |
| GR-009 | URL encoded correctly | Special characters properly encoded |
| GR-010 | Different state on each call | New random state each invocation |
| GR-011 | Stateless mode for SPA | Uses stateless() method |
| GR-012 | Multiple calls generate unique states | No state collision |

### 2. handleCallback() Tests (HC)
Tests for OAuth callback processing.

| Test ID | Description | Expected Result |
|---------|-------------|-----------------|
| HC-001 | Valid code and state returns user | Returns array with user and token |
| HC-002 | Invalid state throws exception | InvalidOAuthStateException |
| HC-003 | Missing state throws exception | InvalidOAuthStateException |
| HC-004 | Expired state throws exception | State removed from cache |
| HC-005 | Used state cannot be reused | One-time use enforced |
| HC-006 | Invalid authorization code throws exception | OAuth failed gracefully |
| HC-007 | Denied authorization throws exception | Access denied handled |
| HC-008 | State consumed after use | Cache key removed |
| HC-009 | Returns is_new_user flag | Indicates new vs existing user |
| HC-010 | Returns Sanctum token | Auth token for API access |

### 3. findOrCreateUser() Tests (FU)
Tests for user creation and linking logic.

| Test ID | Description | Expected Result |
|---------|-------------|-----------------|
| FU-001 | New user creates User record | User persisted to database |
| FU-002 | New user creates SocialAccount record | Social account linked |
| FU-003 | New user email auto-verified | email_verified_at set |
| FU-004 | New user name from Google | User name matches Google name |
| FU-005 | New user avatar from Google | Avatar URL stored |
| FU-006 | Existing user by provider_id returns user | No duplicate user created |
| FU-007 | Existing user by email links account | Social account linked to existing user |
| FU-008 | Existing user email already verified | Stays verified |
| FU-009 | Tokens encrypted in database | SocialAccount has encrypted casts |
| FU-010 | Updates existing social account tokens | New tokens saved |
| FU-011 | Refresh token stored when provided | provider_refresh_token set |
| FU-012 | Token expiration stored | provider_token_expires_at set |
| FU-013 | Google avatar URL valid format | HTTPS URL expected |
| FU-014 | User password null for OAuth-only | No password set |
| FU-015 | Multiple OAuth providers for same user | Multiple social_accounts allowed |

### 4. Account Linking Tests (AL)
Tests for email-based account linking.

| Test ID | Description | Expected Result |
|---------|-------------|-----------------|
| AL-001 | Same email links to existing user | Social account linked to existing |
| AL-002 | Verified Google email auto-verifies user | email_verified_at set |
| AL-003 | Unverified Google email does not verify | email_verified_at remains null |
| AL-004 | Existing user with password keeps password | Password not cleared |
| AL-005 | Multiple Google accounts same email | Uses first match |
| AL-006 | Account linking creates audit log | Security event logged |
| AL-007 | Provider uniqueness enforced | Same Google ID can't link twice |
| AL-008 | Different provider same email allowed | Can have Google + GitHub |

### 5. CSRF State Tests (CS)
Tests for state parameter CSRF protection.

| Test ID | Description | Expected Result |
|---------|-------------|-----------------|
| CS-001 | State is 40 characters | Str::random(40) length |
| CS-002 | State is alphanumeric | Only URL-safe characters |
| CS-003 | State stored before redirect | Cache::put called |
| CS-004 | State TTL is 10 minutes | 600 seconds |
| CS-005 | State validated on callback | Cache::has check |
| CS-006 | State removed after use | Cache::forget called |
| CS-007 | Expired state rejected | Time-based rejection |
| CS-008 | Man-in-middle attack prevented | State mismatch rejected |

### 6. Error Handling Tests (EH)
Tests for error scenarios.

| Test ID | Description | Expected Result |
|---------|-------------|-----------------|
| EH-001 | Google API error handled gracefully | OAuthFailedException |
| EH-002 | Network timeout handled | ConnectionException caught |
| EH-003 | Invalid token response handled | MalformedTokenException |
| EH-004 | Missing email scope handled | InsufficientScopeException |
| EH-005 | Empty Google profile handled | Default values used |
| EH-006 | Rate limiting on OAuth calls | Proper error message |
| EH-007 | Socialite not configured handled | ConfigurationException |
| EH-008 | Database transaction rollback on error | No partial data |
| EH-009 | Exception includes context | User-friendly message |
| EH-010 | Error logged for debugging | Log::error called |

### 7. Security Tests (SEC)
Tests for security-critical functionality.

| Test ID | Description | Expected Result |
|---------|-------------|-----------------|
| SEC-001 | Tokens encrypted at rest | encrypted cast verified |
| SEC-002 | Tokens not in API responses | Hidden in serialization |
| SEC-003 | State prevents CSRF | State validation required |
| SEC-004 | Redirect URI validated | Only configured URIs |
| SEC-005 | No token leakage in logs | Sensitive data masked |
| SEC-006 | SQL injection prevented | Parameterized queries |
| SEC-007 | XSS in user name prevented | Name sanitized |
| SEC-008 | Open redirect prevented | Only valid redirect URIs |
| SEC-009 | Token refresh capability | Refresh token stored |
| SEC-010 | Account hijacking prevented | Unique constraint on provider+id |

### 8. Integration Tests (INT)
Tests for end-to-end OAuth flow.

| Test ID | Description | Expected Result |
|---------|-------------|-----------------|
| INT-001 | Full OAuth flow works | Redirect → Callback → User |
| INT-002 | User can login after OAuth | Token is valid |
| INT-003 | User profile contains Google data | Name, email, avatar |
| INT-004 | Social account queryable | Can find by provider_id |
| INT-005 | Logout revokes token | Sanctum token deleted |
| INT-006 | Re-login uses existing account | No duplicate users |
| INT-007 | OAuth with existing session | Links to logged-in user |
| INT-008 | Socialite mock returns test data | Test doubles work |

### 9. Edge Case Tests (EC)
Tests for edge cases and special scenarios.

| Test ID | Description | Expected Result |
|---------|-------------|-----------------|
| EC-001 | Unicode in user name | Handled correctly |
| EC-002 | Very long user name | Truncated to 255 chars |
| EC-003 | Email with + sign | Handled correctly |
| EC-004 | Missing Google avatar | Null avatar allowed |
| EC-005 | Token expires immediately | Expiration handled |
| EC-006 | Concurrent OAuth requests | No race condition |
| EC-007 | Database connection lost | Transaction rollback |
| EC-008 | Empty Google ID | Validation error |
| EC-009 | Google ID with special chars | URL-safe handling |
| EC-010 | User deleted during OAuth | Graceful failure |

---

## Test File Locations

| Type | File Path |
|------|-----------|
| Unit Tests | `backend/tests/Unit/Services/Auth/GoogleOAuthServiceTest.php` |
| Integration Tests | `backend/tests/Integration/Services/Auth/GoogleOAuthFlowTest.php` |
| Feature Tests | `backend/tests/Feature/Services/GoogleOAuthServiceFeatureTest.php` |

---

## Mock Strategy

### Socialite Mock
```php
// Mock Socialite facade for unit tests
Socialite::shouldReceive('driver')
    ->with('google')
    ->andReturn($mockProvider);

$mockProvider->shouldReceive('stateless')
    ->andReturnSelf();

$mockProvider->shouldReceive('user')
    ->andReturn($mockGoogleUser);
```

### SocialiteUser Mock
```php
$mockGoogleUser = Mockery::mock(SocialiteUser::class);
$mockGoogleUser->id = '123456789';
$mockGoogleUser->name = 'John Doe';
$mockGoogleUser->email = 'john@example.com';
$mockGoogleUser->avatar = 'https://lh3.googleusercontent.com/...';
$mockGoogleUser->token = 'ya29.a0...';
$mockGoogleUser->refreshToken = '1//...';
$mockGoogleUser->expiresIn = 3600;
```

---

## Exception Classes

```php
// Custom exceptions for Google OAuth
class InvalidOAuthStateException extends Exception {}
class OAuthFailedException extends Exception {}
class InsufficientScopeException extends Exception {}
class ConfigurationException extends Exception {}
```

---

## Total Tests
- getRedirectUrl Tests: 12
- handleCallback Tests: 10
- findOrCreateUser Tests: 15
- Account Linking Tests: 8
- CSRF State Tests: 8
- Error Handling Tests: 10
- Security Tests: 10
- Integration Tests: 8
- Edge Case Tests: 10
- **Total: 91 tests**

---

## Key Test Scenarios

### Scenario 1: New User Registration via Google
```
Given: No existing user with Google email
When: User completes Google OAuth
Then: New user created, email verified, social account linked
```

### Scenario 2: Existing User Links Google
```
Given: User registered with email/password
When: Same email used for Google OAuth
Then: Social account linked to existing user, can login both ways
```

### Scenario 3: CSRF Attack Prevention
```
Given: Attacker generates OAuth URL
When: Attacker tries to use different state
Then: State validation fails, exception thrown
```

### Scenario 4: Token Refresh Flow
```
Given: User has expired Google token
When: User tries to use Google features
Then: Refresh token used to get new access token
```

### Scenario 5: Account Hijacking Prevention
```
Given: User A has Google ID 123 linked
When: User B tries to claim same Google ID
Then: Unique constraint prevents, error thrown
```

---

## Dependencies
- TASK-25-1 (Sanctum & Socialite) - Required
- TASK-25-2 (social_accounts table) - Required
- TASK-25-6 (Google OAuth Credentials) - Required
- Laravel Socialite
- Laravel Sanctum

---

*Created by PreTester for TASK-25-7*
