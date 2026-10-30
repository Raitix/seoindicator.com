# TESTS/TASK-25-6: Configure Google OAuth Credentials

## Task Reference
- Task: [TASK-25-6](../TASKS/TASK-25-6.md)
- Specification: [SPEC-025](../SPECIFICATIONS/SPEC-025.md)
- Feature: [FEAT-025](../FEATURES/FEAT-025.md)

---

## Overview

Tests for verifying Google OAuth 2.0 credential configuration. This task involves setting up credentials in Google Cloud Console and configuring the application to use them. Tests verify that environment variables, configuration files, and credential format are correctly set up.

---

## Test Files

| File | Type | Tests | Description |
|------|------|-------|-------------|
| `backend/tests/Feature/Auth/GoogleOAuthConfigurationTest.php` | Feature | 18 | Environment variables and config tests |
| `backend/tests/Feature/Auth/GoogleOAuthCredentialsFormatTest.php` | Feature | 12 | Credential format validation |
| `backend/tests/Feature/Auth/GoogleOAuthRedirectUriTest.php` | Feature | 8 | Redirect URI configuration tests |
| `backend/tests/Unit/Services/Auth/GoogleOAuthServiceSetupTest.php` | Unit | 7 | Service-level configuration tests |
| **Total** | | **45** | |

---

## Test Scenarios

### 1. GoogleOAuthConfigurationTest

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| GC-001 | GOOGLE_CLIENT_ID env set | Check env('GOOGLE_CLIENT_ID') | Non-empty string |
| GC-002 | GOOGLE_CLIENT_SECRET env set | Check env('GOOGLE_CLIENT_SECRET') | Non-empty string |
| GC-003 | GOOGLE_REDIRECT_URI env set | Check env('GOOGLE_REDIRECT_URI') | Non-empty string |
| GC-004 | Google config exists in services | Check config('services.google') | Array with client_id, client_secret, redirect |
| GC-005 | Client ID from env | Check config('services.google.client_id') | Matches env('GOOGLE_CLIENT_ID') |
| GC-006 | Client secret from env | Check config('services.google.client_secret') | Matches env('GOOGLE_CLIENT_SECRET') |
| GC-007 | Redirect URI from env | Check config('services.google.redirect') | Matches env('GOOGLE_REDIRECT_URI') |
| GC-008 | Client ID not empty placeholder | Check config value | Not 'your-client-id', 'xxx', empty |
| GC-009 | Client secret not empty placeholder | Check config value | Not 'your-secret', 'xxx', empty |
| GC-010 | Services config returns array | Check config('services') | Valid array structure |
| GC-011 | Google provider key exists | Check config('services.google') | Key exists (not null) |
| GC-012 | All required keys present | Check services.google | Has client_id, client_secret, redirect |
| GC-013 | No extra sensitive keys exposed | Check config structure | Only expected keys present |
| GC-014 | Config cached properly | Run config:cache | Config reflects env values |
| GC-015 | Env values not logged | Check logs | Credentials not in log files |
| GC-016 | Services config valid PHP | Include config file | Returns array, no syntax errors |
| GC-017 | Socialite can use Google driver | Socialite::driver('google') | Returns Google provider instance |
| GC-018 | Google provider has credentials | Check provider config | Client ID and secret injected |

### 2. GoogleOAuthCredentialsFormatTest

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CF-001 | Client ID format valid | Check GOOGLE_CLIENT_ID | Matches `*.apps.googleusercontent.com` |
| CF-002 | Client ID has correct prefix | Check first chars | Starts with numeric ID |
| CF-003 | Client ID has apps suffix | Check ending | Ends with `.apps.googleusercontent.com` |
| CF-004 | Client secret format valid | Check GOOGLE_CLIENT_SECRET | Matches `GOCSPX-*` or similar |
| GA-005 | Client secret minimum length | Check length | At least 20 characters |
| CF-006 | Redirect URI is valid URL | Check GOOGLE_REDIRECT_URI | Valid URL format |
| CF-007 | Redirect URI uses HTTPS | Check scheme | Starts with `https://` (production) |
| CF-008 | Redirect URI has callback path | Check path | Ends with `/auth/google/callback` |
| CF-009 | Redirect URI contains domain | Check host | Contains valid domain |
| CF-010 | Client ID not production in tests | Check environment | Test env uses test credentials |
| CF-011 | Credentials can be different per env | Check .env.local | Local overrides work |
| CF-012 | Empty credentials fail gracefully | Set empty values | Service returns null, not crash |

### 3. GoogleOAuthRedirectUriTest

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| RU-001 | Redirect URI matches expected format | Check value | `/api/v1/auth/google/callback` path |
| RU-002 | Local redirect URI works | Check localhost URL | Valid for development |
| RU-003 | Production redirect URI configured | Check production URL | Uses correct domain |
| RU-004 | Redirect URI in Google Console | External verification | URI whitelisted (documented) |
| RU-005 | Multiple redirect URIs supported | Check config | Array or multiple values handled |
| RU-006 | Redirect URI has no trailing slash | Check format | No trailing slash on URL |
| RU-007 | Redirect URI includes protocol | Check format | Has http:// or https:// |
| RU-008 | Redirect URI port for local dev | Check localhost | Port specified if non-standard |

### 4. GoogleOAuthServiceSetupTest

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| GS-001 | Socialite driver returns Google | Socialite::driver('google') | Instance of Google provider |
| GS-002 | Provider has client ID | Check provider config | Client ID set on provider |
| GS-003 | Provider has client secret | Check provider config | Client secret set on provider |
| GS-004 | Provider has redirect URI | Check provider config | Redirect URI set on provider |
| GS-005 | Provider scopes include email | Check default scopes | Contains 'email' |
| GS-006 | Provider scopes include profile | Check default scopes | Contains 'profile' |
| GS-007 | Provider can generate redirect | Call redirect() | Returns RedirectResponse |

---

## Mock Data

### Sample .env Values (Development)
```env
# Google OAuth Configuration
GOOGLE_CLIENT_ID=123456789012-abcdefghijklmnopqrstuvwxyz.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=GOCSPX-abcdefghijklmnopqrstuvwxyz123456
GOOGLE_REDIRECT_URI=http://localhost:8000/api/v1/auth/google/callback
GOOGLE_REDIRECT_URI_PROD=https://seoindicator.com/api/v1/auth/google/callback
```

### Sample .env Values (Production)
```env
GOOGLE_CLIENT_ID=123456789012-abcdefghijklmnopqrstuvwxyz.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=GOCSPX-abcdefghijklmnopqrstuvwxyz123456
GOOGLE_REDIRECT_URI=https://seoindicator.com/api/v1/auth/google/callback
GOOGLE_REDIRECT_URI_PROD=
```

### Expected config/services.php Structure
```php
'google' => [
    'client_id' => env('GOOGLE_CLIENT_ID'),
    'client_secret' => env('GOOGLE_CLIENT_SECRET'),
    'redirect' => env('GOOGLE_REDIRECT_URI'),
    'redirect_uris' => array_filter([
        env('GOOGLE_REDIRECT_URI'),
        env('GOOGLE_REDIRECT_URI_PROD'),
    ]),
],
```

### Valid Client ID Format
```
{numeric_id}-{random_string}.apps.googleusercontent.com

Example:
123456789012-abcdefghijklmnopqrstuvwxyz.apps.googleusercontent.com

Pattern: /^\d+-[a-z0-9]+\.apps\.googleusercontent\.com$/
```

### Valid Client Secret Format
```
GOCSPX-{random_string}

Example:
GOCSPX-abcdefghijklmnopqrstuvwxyz123456

Pattern: /^GOCSPX-[A-Za-z0-9_-]+$/
Minimum length: 20 characters
```

---

## Coverage Targets

| Category | Target | Description |
|----------|--------|-------------|
| Environment Variables | 100% | All OAuth env vars present and valid |
| Configuration Files | 100% | services.php properly configured |
| Credential Format | 100% | Credentials match expected patterns |
| Service Integration | 100% | Socialite can use Google provider |
| Security | 100% | No credential exposure in logs/errors |

---

## Test Execution

```bash
# Run all tests for this task
cd backend
php artisan test --filter "GoogleOAuthConfiguration|GoogleOAuthCredentialsFormat|GoogleOAuthRedirectUri|GoogleOAuthServiceSetup"

# Run specific test file
php artisan test tests/Feature/Auth/GoogleOAuthConfigurationTest.php

# Run with coverage
php artisan test --coverage --filter "GoogleOAuth"
```

---

## Dependencies

### Required Packages
- `laravel/socialite` ^5.0 (from TASK-25-1)

### Environment Configuration
- `GOOGLE_CLIENT_ID` - Google OAuth client ID from Google Cloud Console
- `GOOGLE_CLIENT_SECRET` - Google OAuth client secret
- `GOOGLE_REDIRECT_URI` - primary OAuth callback URL
- `GOOGLE_REDIRECT_URI_PROD` - optional additional production callback URL for multi-environment registration

### External Requirements
- Google Cloud Console project with OAuth 2.0 credentials created
- Authorized JavaScript origins configured
- Authorized redirect URIs configured

### Configuration Files
- `config/services.php` - Google OAuth provider configuration

---

## Notes for Developer

### Google Cloud Console Setup Steps

1. **Go to Google Cloud Console:**
   ```
   https://console.cloud.google.com
   ```

2. **Create or select a project:**
   - Create new project named "SEOIndicator" or select existing

3. **Create OAuth 2.0 credentials:**
   - Go to "APIs & Services" → "Credentials"
   - Click "Create Credentials" → "OAuth client ID"
   - Select "Web application"
   - Add authorized JavaScript origins:
     - Development: `http://localhost:3000`
     - Production: `https://seoindicator.com`
   - Add authorized redirect URIs:
     - Development: `http://localhost:8000/api/v1/auth/google/callback`
     - Production: `https://seoindicator.com/api/v1/auth/google/callback`
   - Click "Create" to get Client ID and Client Secret

4. **Update .env file:**
   ```env
   GOOGLE_CLIENT_ID=your-client-id.apps.googleusercontent.com
   GOOGLE_CLIENT_SECRET=GOCSPX-your-secret
   GOOGLE_REDIRECT_URI=https://seoindicator.com/api/v1/auth/google/callback
   ```

6. **Update config/services.php (if not already done):**
   ```php
   'google' => [
       'client_id' => env('GOOGLE_CLIENT_ID'),
       'client_secret' => env('GOOGLE_CLIENT_SECRET'),
       'redirect' => env('GOOGLE_REDIRECT_URI'),
   ],
   ```

### FREE Tier Limits

Google OAuth 2.0 is FREE with reasonable limits:
- 100 OAuth logins per day on free tier
- No cost for basic profile/email scopes
- Sufficient for development and small-scale production

### Security Notes

1. **Never commit credentials to version control**
   - Keep `.env` in `.gitignore`
   - Use `.env.example` with placeholder values

2. **Rotate credentials periodically**
   - Generate new secret in Google Console
   - Update `.env` immediately

3. **Restrict redirect URIs**
   - Only whitelisted URIs work
   - Prevents credential misuse

4. **Use environment-specific credentials**
   - Development credentials for localhost
   - Production credentials for production domain

---

## Comments
_Agent communication log_

### [2026-03-01 15:14 GMT+2] Agent: PreTester
Created test documentation for TASK-25-6 (Configure Google OAuth Credentials).

**Test Categories:**
- GoogleOAuthConfigurationTest (18 tests): Environment variables, config structure
- GoogleOAuthCredentialsFormatTest (12 tests): Client ID/secret format validation
- GoogleOAuthRedirectUriTest (8 tests): Redirect URI configuration
- GoogleOAuthServiceSetupTest (7 tests): Socialite integration

**Total: 45 tests**

**Dependency Note:** Tests require TASK-25-1 (Sanctum & Socialite packages) to be completed first. The Socialite package provides the Google OAuth driver that these tests verify.

**Security Focus:** Tests verify credentials are not exposed in logs, use proper format, and redirect URIs are correctly configured.

---
*Created by PreTester*
