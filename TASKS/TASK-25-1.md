# TASK-25-1: Install Laravel Sanctum & Socialite Packages

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

### [2026-02-28 20:55 GMT+2] Agent: SpecSystemAnalyst
Created task as part of SPEC-025 breakdown (Phase 1: Core Authentication).

---
*Created by SpecSystemAnalyst*
