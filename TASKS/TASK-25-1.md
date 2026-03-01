# TASK-25-1: Install Laravel Sanctum & Socialite Packages

## STATUS
`In Development`

## PRIORITY
`High`

## RISK
`Medium`

## Related
- Specification: [SPEC-025](../SPECIFICATIONS/SPEC-025.md)
- Feature: [FEAT-025](../FEATURES/FEAT-025.md)

---

## Description
Install and configure Laravel Sanctum (SPA authentication) and Laravel Socialite (OAuth abstraction) packages. These provide the foundation for all authentication functionality.

---

## Acceptance Criteria
- [ ] Laravel Sanctum installed via Composer
- [ ] Laravel Socialite installed via Composer
- [ ] Sanctum configuration published
- [ ] SANCTUM_STATEFUL_DOMAINS configured for frontend URL
- [ ] SESSION_DOMAIN configured correctly
- [ ] CORS configuration updated for SPA authentication
- [ ] Basic Sanctum middleware registered
- [ ] Services config updated with Socialite providers placeholder

---

## Dependencies
- None (foundation task)

---

## Effort Estimate
`Small` (1-2 hours)

---

## Technical Notes
- Run: `composer require laravel/sanctum laravel/socialite`
- Publish Sanctum config: `php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"`
- Update `config/sanctum.php` with stateful domains
- Update `config/cors.php` for SPA support
- Add Socialite services to `config/services.php`:
```php
'google' => [
    'client_id' => env('GOOGLE_CLIENT_ID'),
    'client_secret' => env('GOOGLE_CLIENT_SECRET'),
    'redirect' => env('GOOGLE_REDIRECT_URI'),
],
```

---

## Comments
_Agent communication log_

### [2026-03-01 11:10 GMT+2] Agent: PreTester
**Test Design Complete:**

Created 43 tests across 4 test files to verify Sanctum and Socialite installation/configuration:

| Test File | Tests | Coverage |
|-----------|-------|----------|
| SanctumInstallationTest.php | 15 | Package installation, config, middleware, session |
| SocialiteInstallationTest.php | 12 | Package installation, Google OAuth config |
| SanctumCorsConfigurationTest.php | 10 | CORS for SPA cookie-based auth |
| PackageVersionTest.php | 6 | Version compatibility checks |

**Test Documentation:** `TESTS/TASK-25-1.md`

**Key Test Scenarios:**
- Verify packages installed via Composer
- Verify config files published and valid
- Verify SANCTUM_STATEFUL_DOMAINS configuration
- Verify SESSION_DOMAIN and same_site settings
- Verify CORS allows credentials for SPA auth
- Verify Google OAuth provider configured in services.php

**Notes for Developer:**
1. Tests will fail until packages are installed (expected TDD behavior)
2. See `TESTS/TASK-25-1.md` for detailed implementation notes
3. Ensure `personal_access_tokens` migration is run after Sanctum install

Status updated: `To Develop`

---
*Created by PreTester*

### [2026-03-01 10:54 GMT+2] Agent: ProjectManager
**Prioritization Analysis:**

**Priority Confirmed: High**
- Foundation task - enables all 25 authentication tasks
- No dependencies - can start immediately
- Required for Phase 1, which blocks Phase 2 (Google OAuth)
- Small effort (1-2 hours), high impact

**Execution Order:**
- TASK-25-1 (this) → First (foundation)
- TASK-25-2, 25-3, 25-4, 25-5 → Can run in parallel after TASK-25-1

**Risk Assessment: Medium** (appropriate)
- Package installation is low-risk
- Configuration changes require careful testing
- No data migration involved

Status updated: `To Write Tests`

---
*Created by SpecSystemAnalyst*

### [2026-02-28 20:55 GMT+2] Agent: SpecSystemAnalyst
Created task as part of SPEC-025 breakdown (Phase 1: Core Authentication).

---
*Created by SpecSystemAnalyst*
