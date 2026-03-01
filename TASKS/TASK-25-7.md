# TASK-25-7: Create GoogleOAuthService

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
Create the GoogleOAuthService that handles the OAuth flow logic - generating redirect URLs, processing callbacks, and managing user creation/linking.

---

## Acceptance Criteria
- [ ] GoogleOAuthService created
- [ ] `getRedirectUrl()` - Generates Google OAuth redirect URL with state CSRF token
- [ ] `handleCallback($code, $state)` - Processes OAuth callback
- [ ] `findOrCreateUser(SocialiteUser $googleUser)` - Creates new user or links existing
- [ ] State parameter validation for CSRF protection
- [ ] User creation with Google data (name, email, avatar)
- [ ] Social account record created/updated with tokens
- [ ] Email auto-verified for Google sign-ins
- [ ] Token encryption at rest
- [ ] Error handling for denied access, invalid state

---

## Dependencies
- TASK-25-2 (social_accounts table)
- TASK-25-6 (Google OAuth credentials)

---

## Effort Estimate
`Medium` (4-5 hours)

---

## Technical Notes
```php
class GoogleOAuthService
{
    public function getRedirectUrl(): string
    {
        $state = Str::random(40);
        Cache::put('oauth_state_' . $state, true, now()->addMinutes(10));
        return Socialite::driver('google')
            ->stateless()
            ->with(['state' => $state])
            ->redirect()->getTargetUrl();
    }

    public function handleCallback(string $code, string $state): array
    {
        // Validate state
        if (!Cache::has('oauth_state_' . $state)) {
            throw new InvalidOAuthStateException();
        }
        
        $googleUser = Socialite::driver('google')->stateless()->user();
        return $this->findOrCreateUser($googleUser);
    }
}
```

---

## Comments
_Agent communication log_

### [2026-03-01 15:30 GMT+2] Agent: PreTester
**Test Design Complete**

**Test Documentation Created:**
- `TESTS/TEST-25-7.md` - 91 test scenarios across 9 categories

**Test Files Created:**
- `backend/tests/Unit/Services/Auth/GoogleOAuthServiceTest.php` (91 tests)
- `backend/tests/Integration/Services/Auth/GoogleOAuthFlowTest.php` (18 tests)

**Exception Classes Created:**
- `InvalidOAuthStateException` - CSRF state validation failures
- `OAuthFailedException` - General OAuth errors
- `InsufficientScopeException` - Missing required scopes
- `ConfigurationException` - Missing OAuth config
- `AccessDeniedException` - User denied authorization

**Test Categories:**
1. **getRedirectUrl() Tests (12 tests)** - URL generation, state CSRF protection
2. **handleCallback() Tests (10 tests)** - Callback processing, state validation
3. **findOrCreateUser() Tests (15 tests)** - User creation, social account linking
4. **Account Linking Tests (8 tests)** - Email-based linking, security
5. **CSRF State Tests (8 tests)** - State generation, validation, expiration
6. **Error Handling Tests (10 tests)** - API errors, network issues, rate limits
7. **Security Tests (10 tests)** - Token encryption, CSRF prevention, XSS
8. **Integration Tests (8 tests)** - Full OAuth flow end-to-end
9. **Edge Case Tests (10 tests)** - Unicode, timeouts, concurrent requests

**Key Test Findings:**
- State parameter must be 40 chars, alphanumeric, 10-minute TTL
- Tokens encrypted via SocialAccount model's encrypted casts
- State consumed after single use (one-time token)
- Account linking preserves existing user's password
- Multiple OAuth providers allowed per user
- Unique constraint on (provider, provider_id) prevents hijacking

**Dependencies Verified:**
- SocialAccount model has encrypted casts (TASK-25-2) ✅
- Socialite facade available for mocking ✅
- User factory supports OAuth scenarios ✅

**Status:** `To Write Tests` → `To Develop`

### [2026-03-01 15:03 GMT+2] Agent: ProjectManager
**Prioritization Analysis:**

**Priority Confirmed: High**
- Foundation service for Google OAuth (Phase 2 of SPEC-025)
- Security-critical component (OAuth token handling, CSRF protection)
- Enables entire Google sign-in flow for user accounts
- Required for TASK-25-8 (GoogleAuthController) and TASK-25-9 (Google OAuth Frontend)

**Risk Confirmed: High**
- OAuth token handling (access/refresh tokens)
- CSRF protection via state parameter
- User creation/linking logic (identity management)
- Token encryption at rest (must use SocialAccount encrypted casts)
- Error handling for denied access, invalid state

**Dependency Status:**
- TASK-25-2 (social_accounts table): `To Document` ✅ ESSENTIALLY DONE
  - Migration deployed to production
  - Model with encrypted casts implemented
  - All tests passing (64 tests, 119 assertions)
  - Just needs DocSystemAnalyst documentation
- TASK-25-6 (Google OAuth credentials): `To Write Tests` ✅ IN PROGRESS
  - Tests being designed now
  - Configuration task - no blocking code dependency

**Execution Order:**
1. TASK-25-6 → Complete configuration
2. TASK-25-7 (this) → Implement GoogleOAuthService
3. TASK-25-8 → Implement GoogleAuthController (depends on this)

**Notes for PreTester:**
- Test CSRF state parameter generation/validation
- Test user creation with Google data
- Test social account linking
- Test token encryption at rest
- Test error scenarios (denied access, invalid state, expired state)
- Mock Socialite for unit tests

Status updated: `To Write Tests`

### [2026-02-28 20:55 GMT+2] Agent: SpecSystemAnalyst
Created task as part of SPEC-025 breakdown (Phase 2: Google OAuth).

**High Risk**: OAuth token handling, CSRF protection critical.

---
*Created by SpecSystemAnalyst*
