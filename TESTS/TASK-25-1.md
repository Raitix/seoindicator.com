# TESTS/TASK-25-1: Install Laravel Sanctum & Socialite Packages

## Task Reference
- Task: [TASK-25-1](../TASKS/TASK-25-1.md)
- Specification: [SPEC-025](../SPECIFICATIONS/SPEC-025.md)
- Feature: [FEAT-025](../FEATURES/FEAT-025.md)

---

## Overview

Tests for verifying the installation and configuration of Laravel Sanctum (SPA authentication) and Laravel Socialite (OAuth abstraction) packages. These packages form the foundation of the authentication system.

---

## Test Files

| File | Type | Tests | Description |
|------|------|-------|-------------|
| `backend/tests/Feature/Auth/SanctumInstallationTest.php` | Feature | 15 | Sanctum package and configuration tests |
| `backend/tests/Feature/Auth/SocialiteInstallationTest.php` | Feature | 12 | Socialite package and configuration tests |
| `backend/tests/Feature/Auth/SanctumCorsConfigurationTest.php` | Feature | 10 | CORS configuration for SPA auth |
| `backend/tests/Unit/Services/Auth/PackageVersionTest.php` | Unit | 6 | Package version requirements |
| **Total** | | **43** | |

---

## Test Scenarios

### 1. SanctumInstallationTest

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| SI-001 | Sanctum package installed | Check composer.json | `laravel/sanctum` in require section |
| SI-002 | Sanctum version compatible | Check composer.lock | Version ^4.0 installed |
| SI-003 | Sanctum config published | Check config/sanctum.php | File exists with valid PHP array |
| SI-004 | Sanctum stateful domains configured | Check config/sanctum.php | `stateful` array contains frontend URLs |
| SI-005 | SANCTUM_STATEFUL_DOMAINS env parsed | Set env var | Config picks up env value |
| SI-006 | Sanctum middleware registered | Check bootstrap/app.php | Middleware alias `sanctum` exists |
| SI-007 | Session domain configured | Check config/session.php | `domain` matches backend domain |
| SI-008 | Session same-site for SPA | Check config/session.php | `same_site` set to 'lax' or 'none' |
| SI-009 | Sanctum guard configuration | Check config/sanctum.php | Guard set to 'web' for SPA |
| SI-010 | Expiration time configured | Check config/sanctum.php | `expiration` is set (not null) |
| SI-011 | Token table migration exists | Check migrations | `personal_access_tokens` migration exists |
| SI-012 | Sanctum cache store configured | Check config/sanctum.php | `cache_store` is defined |
| SI-013 | Middleware priority correct | Check middleware order | Sanctum runs after EncryptCookies |
| SI-014 | Auth middleware uses Sanctum | Test protected route | Returns 401 for unauthenticated |
| SI-015 | Sanctum token abilities | Create token with ability | Ability correctly stored and validated |

### 2. SocialiteInstallationTest

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| SO-001 | Socialite package installed | Check composer.json | `laravel/socialite` in require section |
| SO-002 | Socialite version compatible | Check composer.lock | Version ^5.0 installed |
| SO-003 | Google provider configured | Check config/services.php | `google` key exists with client_id, client_secret, redirect |
| SO-004 | Google client_id from env | Check config/services.php | `client_id` pulls from `GOOGLE_CLIENT_ID` |
| SO-005 | Google client_secret from env | Check config/services.php | `client_secret` pulls from `GOOGLE_CLIENT_SECRET` |
| SO-006 | Google redirect URI from env | Check config/services.php | `redirect` pulls from env |
| SO-007 | Services config valid array | Check config/services.php | Returns valid array structure |
| SO-008 | Socialite facade registered | Test Socialite::driver() | Returns Google provider instance |
| SO-009 | Google OAuth scope correct | Test Socialite::driver('google') | Default scopes: email, profile |
| SO-010 | Provider redirect generates URL | Call redirect() | Returns RedirectResponse to accounts.google.com |
| SO-011 | State parameter in OAuth URL | Parse redirect URL | Contains `state` parameter |
| SO-012 | Response type code | Parse redirect URL | Contains `response_type=code` |

### 3. SanctumCorsConfigurationTest

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| SC-001 | CORS config exists | Check config/cors.php | File exists (or default Laravel 12 CORS) |
| SC-002 | CORS paths include API | Check paths | `/api/*` in allowed paths |
| SC-003 | CORS allows credentials | Check supports_credentials | Set to `true` for SPA auth |
| SC-004 | CORS allowed origins | Check allowed_origins | Contains frontend domain(s) |
| SC-005 | CORS allowed methods | Check allowed_methods | Includes GET, POST, PUT, DELETE, OPTIONS |
| SC-006 | CORS allowed headers | Check allowed_headers | Includes Authorization, Content-Type, X-Requested-With |
| SC-007 | CORS exposed headers | Check exposed_headers | Includes X-RateLimit-* headers |
| SC-008 | Preflight request succeeds | OPTIONS /api/* | Returns 204 with CORS headers |
| SC-009 | Credentials in response | Authenticated request | Cookie sent with response |
| SC-010 | Wildcard origin blocked | Request with Origin: * | Returns 400 or strips wildcard |

### 4. PackageVersionTest

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| PV-001 | Sanctum version minimum | Check composer.lock | Version >= 4.0.0 |
| PV-002 | Socialite version minimum | Check composer.lock | Version >= 5.0.0 |
| PV-003 | Laravel framework compatible | Check composer.lock | Framework ^12.0 installed |
| PV-004 | PHP version compatible | Check composer.json | PHP ^8.2 required |
| PV-005 | No conflicts in dependencies | Run composer validate | No dependency conflicts |
| PV-006 | Autoload properly configured | Check composer.json | PSR-4 autoload correct |

---

## Mock Data

### Sample .env Values
```env
SANCTUM_STATEFUL_DOMAINS=localhost:3000,127.0.0.1:3000,seoindicator.com
SESSION_DOMAIN=.seoindicator.com
SESSION_SAME_SITE=lax

GOOGLE_CLIENT_ID=123456789.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=GOCSPX-xxxxxxxxxxxxx
GOOGLE_REDIRECT_URI=https://seoindicator.com/api/v1/auth/google/callback
```

### Sample config/sanctum.php
```php
return [
    'stateful' => explode(',', env('SANCTUM_STATEFUL_DOMAINS', '')),
    'guard' => ['web'],
    'expiration' => 60 * 24 * 7, // 7 days
    'token_prefix' => env('SANCTUM_TOKEN_PREFIX', ''),
];
```

### Sample config/services.php (Google section)
```php
'google' => [
    'client_id' => env('GOOGLE_CLIENT_ID'),
    'client_secret' => env('GOOGLE_CLIENT_SECRET'),
    'redirect' => env('GOOGLE_REDIRECT_URI'),
],
```

---

## Coverage Targets

| Category | Target | Description |
|----------|--------|-------------|
| Package Installation | 100% | All packages verified installed |
| Configuration Files | 100% | All config files exist and valid |
| Environment Variables | 100% | All required env vars documented |
| Middleware Registration | 100% | All middleware properly registered |
| CORS Configuration | 100% | SPA auth CORS properly configured |

---

## Test Execution

```bash
# Run all tests for this task
cd backend
php artisan test --filter "SanctumInstallation|SocialiteInstallation|SanctumCorsConfiguration|PackageVersion"

# Run specific test file
php artisan test tests/Feature/Auth/SanctumInstallationTest.php

# Run with coverage
php artisan test --coverage --filter "Sanctum|Socialite"
```

---

## Dependencies

### Required Packages
- `laravel/sanctum` ^4.0
- `laravel/socialite` ^5.0

### Environment Configuration
- `SANCTUM_STATEFUL_DOMAINS` - Comma-separated list of frontend domains
- `SESSION_DOMAIN` - Session cookie domain
- `GOOGLE_CLIENT_ID` - Google OAuth client ID
- `GOOGLE_CLIENT_SECRET` - Google OAuth client secret
- `GOOGLE_REDIRECT_URI` - OAuth callback URL

### Configuration Files
- `config/sanctum.php` - Sanctum configuration (published)
- `config/cors.php` - CORS configuration (may use Laravel 12 defaults)
- `config/services.php` - Socialite provider configuration
- `config/session.php` - Session configuration

---

## Notes for Developer

1. **Install packages via Composer:**
   ```bash
   composer require laravel/sanctum laravel/socialite
   ```

2. **Publish Sanctum configuration:**
   ```bash
   php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
   ```

3. **Run Sanctum migrations:**
   ```bash
   php artisan migrate
   ```

4. **Configure stateful domains:**
   - Add frontend URLs to `SANCTUM_STATEFUL_DOMAINS` in `.env`
   - Include both development (localhost:3000) and production domains

5. **Configure session:**
   - Set `SESSION_DOMAIN` for cross-subdomain authentication
   - Use `same_site=lax` for SPA authentication

6. **Configure Google OAuth:**
   - Create OAuth credentials in Google Cloud Console
   - Add client ID, secret, and redirect URI to `.env`

7. **Middleware registration (Laravel 12):**
   - Sanctum middleware should be auto-discovered
   - Verify `auth:sanctum` guard works on protected routes

---

## Comments
_Agent communication log_

### [2026-03-01 11:05 GMT+2] Agent: PreTester
Created test documentation for TASK-25-1 (Install Laravel Sanctum & Socialite Packages).

**Test Categories:**
- SanctumInstallationTest (15 tests): Package installation, config, middleware
- SocialiteInstallationTest (12 tests): Package installation, Google provider config
- SanctumCorsConfigurationTest (10 tests): CORS for SPA authentication
- PackageVersionTest (6 tests): Version compatibility checks

**Total: 43 tests**

Tests will return failures until Developer implements the package installation and configuration. This is expected TDD behavior.

---
*Created by PreTester*
