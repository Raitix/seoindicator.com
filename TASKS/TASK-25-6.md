# TASK-25-6: Configure Google OAuth Credentials

## STATUS
`To Develop`

## PRIORITY
`High`

## RISK
`Medium`

## Related
- Specification: [SPEC-025](../SPECIFICATIONS/SPEC-025.md)
- Feature: [FEAT-025](../FEATURES/FEAT-025.md)

---

## Description
Set up Google OAuth 2.0 credentials in Google Cloud Console and configure the application to use them. This enables Google sign-in functionality.

---

## Acceptance Criteria
- [ ] Google Cloud Project created or identified
- [ ] OAuth 2.0 Client ID created (Web application type)
- [ ] Authorized JavaScript origins configured
- [ ] Authorized redirect URIs configured
- [ ] Client ID and Client Secret obtained
- [ ] Environment variables added to `.env`:
  - `GOOGLE_CLIENT_ID`
  - `GOOGLE_CLIENT_SECRET`
  - `GOOGLE_REDIRECT_URI`
- [ ] Services config updated with Google provider
- [ ] Documentation updated with setup instructions

---

## Dependencies
- TASK-25-1 (Sanctum & Socialite packages - Socialite config structure)

---

## Effort Estimate
`Small` (1-2 hours)

---

## Technical Notes
Google Cloud Console steps:
1. Go to console.cloud.google.com
2. Create/select project
3. Enable Google+ API
4. Create OAuth 2.0 credentials (Web application)
5. Add authorized origins (e.g., http://localhost:3000)
6. Add redirect URI: `http://localhost:8000/api/v1/auth/google/callback`

Config in `config/services.php`:
```php
'google' => [
    'client_id' => env('GOOGLE_CLIENT_ID'),
    'client_secret' => env('GOOGLE_CLIENT_SECRET'),
    'redirect' => env('GOOGLE_REDIRECT_URI'),
],
```

**FREE Tier**: 100 OAuth logins per day.

---

## Comments
_Agent communication log_

### [2026-03-01 15:20 GMT+2] Agent: PreTester
**Test Design Complete:**

Created 45 tests across 4 test files to verify Google OAuth credentials configuration:

| Test File | Tests | Coverage |
|-----------|-------|----------|
| GoogleOAuthConfigurationTest.php | 18 | Environment variables, config structure |
| GoogleOAuthCredentialsFormatTest.php | 12 | Client ID/secret format validation |
| GoogleOAuthRedirectUriTest.php | 8 | Redirect URI configuration |
| GoogleOAuthServiceSetupTest.php | 7 | Socialite integration |

**Test Documentation:** `TESTS/TASK-25-6.md`

**Key Test Scenarios:**
- Verify GOOGLE_CLIENT_ID, GOOGLE_CLIENT_SECRET, GOOGLE_REDIRECT_URI env vars set
- Verify config/services.php has Google provider with all required keys
- Verify Client ID format matches `*.apps.googleusercontent.com` pattern
- Verify Client Secret has minimum 20 characters
- Verify Redirect URI is valid URL with callback path
- Verify Socialite can use Google driver with configured credentials
- Verify credentials are not exposed in logs

**Notes for Developer:**
1. Tests require Socialite package installed (TASK-25-1 dependency)
2. Tests will fail until credentials are configured in .env (expected TDD behavior)
3. Follow Google Cloud Console setup steps in `TESTS/TASK-25-6.md`
4. Use separate credentials for development vs production
5. FREE tier: 100 OAuth logins/day

Status updated: `To Develop`

---
*Created by PreTester*

### [2026-03-01 14:49 GMT+2] Agent: ProjectManager
**Prioritization Analysis:**

**Priority Confirmed: High**
- Part of authentication system (critical path)
- Google OAuth is expected core feature
- Small effort (1-2 hours), high impact
- FREE tier available (100 OAuth logins/day)

**Risk Assessment: Medium** (confirmed)
- External service configuration (Google Cloud Console)
- Credential management (secrets handling)
- Not destructive but requires careful configuration
- Config changes are reversible

**Dependency Status:**
- TASK-25-1 (Sanctum & Socialite packages) is `In Development`
- Once TASK-25-1 completes, the Socialite config structure will be in place
- This task can proceed with test design now, implementation after TASK-25-1

**Execution Order:**
- TASK-25-1 must complete first (Socialite installation provides config structure)
- After TASK-25-1: This task can configure actual Google credentials

Status updated: `To Write Tests`

---
*Created by SpecSystemAnalyst*

### [2026-02-28 20:55 GMT+2] Agent: SpecSystemAnalyst
Created task as part of SPEC-025 breakdown (Phase 2: Google OAuth).

---
*Created by SpecSystemAnalyst*
