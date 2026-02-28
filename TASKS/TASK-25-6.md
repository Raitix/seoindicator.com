# TASK-25-6: Configure Google OAuth Credentials

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

### [2026-02-28 20:55 GMT+2] Agent: SpecSystemAnalyst
Created task as part of SPEC-025 breakdown (Phase 2: Google OAuth).

---
*Created by SpecSystemAnalyst*
