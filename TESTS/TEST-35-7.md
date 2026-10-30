# TEST-35-7: Google OAuth Service for GBP

## Task Reference
- **Task:** TASK-35-7
- **Specification:** SPEC-035: Local SEO & Google Business Profile Tracking
- **Priority:** High
- **Risk:** High
- **Depends On:** TASK-35-2 (BusinessLocation model)

## Overview

Red-phase test coverage for the Google Business Profile OAuth service. The suite locks down:
- authorization URL generation
- CSRF state handling
- token exchange and refresh behavior
- encrypted token persistence
- valid-token retrieval with refresh fallback
- disconnect / revoke behavior

Because the current controller contract from TASK-35-13 already depends on a wrapper-style interface, this test plan keeps the controller-facing methods intact **and** adds service-level token lifecycle expectations required by TASK-35-7.

## Files

| File | Type | Tests | Description |
|------|------|-------|-------------|
| `backend/tests/Unit/Services/LocalSEO/GoogleOAuthServiceTest.php` | Unit | 21 | Authorization URL, state validation, token exchange, refresh, storage, disconnect |
| `backend/app/Services/LocalSEO/Contracts/GoogleOAuthServiceInterface.php` | Contract | - | Extended with token lifecycle methods needed by this task |
| `backend/app/Services/LocalSEO/GoogleOAuthService.php` | Skeleton | - | Minimal compile-safe scaffolding for new contract methods |

## Test Scenarios

### GOS-CLS: Class & Contract

| ID | Scenario | Expected Outcome |
|----|----------|------------------|
| GOS-CLS-001 | Service class exists | `GoogleOAuthService` autoloads |
| GOS-CLS-002 | Implements interface | Resolved service implements `GoogleOAuthServiceInterface` |
| GOS-CLS-003 | Interface exposes lifecycle methods | Contract includes URL/state, callback, refresh, store, get-valid-token, disconnect helpers |
| GOS-CLS-004 | Token exchange helper available | Service exposes protected code-exchange helper for callback flow |

### GOS-URL: Authorization URL

| ID | Scenario | Expected Outcome |
|----|----------|------------------|
| GOS-URL-001 | Build authorization URL | URL contains Google host, `business.manage` scope, `code`, `offline`, `consent`, callback URL, and returned state |
| GOS-URL-002 | Store state in session | Session stores state + location binding for CSRF validation |
| GOS-URL-003 | Unique state values | Each initiation generates a fresh high-entropy state |

### GOS-STATE: CSRF Validation

| ID | Scenario | Expected Outcome |
|----|----------|------------------|
| GOS-STATE-001 | Matching session state | `validateState()` returns `true` |
| GOS-STATE-002 | Missing session state | `validateState()` returns `false` |
| GOS-STATE-003 | Mismatched state | `validateState()` returns `false` |

### GOS-EXCH: Token Exchange / Refresh

| ID | Scenario | Expected Outcome |
|----|----------|------------------|
| GOS-EXCH-001 | Exchange auth code | Posts authorization-code grant to Google token endpoint and returns normalized `{access_token, refresh_token, expires_at}` |
| GOS-EXCH-002 | Exchange failure | Returns `null` on token endpoint error |
| GOS-EXCH-003 | Refresh access token | Posts refresh-token grant, preserves refresh token, returns normalized payload |

### GOS-STOR: Token Storage

| ID | Scenario | Expected Outcome |
|----|----------|------------------|
| GOS-STOR-001 | Encrypt stored payload | `storeTokens()` writes encrypted JSON into `gbp_tokens`, never plaintext |
| GOS-STOR-002 | Preserve refresh token | Stored payload retains refresh token for later refresh operations |

### GOS-ACCESS: Valid Access Token Retrieval

| ID | Scenario | Expected Outcome |
|----|----------|------------------|
| GOS-ACCESS-001 | Token still valid | Returns decrypted access token directly |
| GOS-ACCESS-002 | Token expired | Refreshes token, persists updated payload, returns new access token |
| GOS-ACCESS-003 | No stored payload | Returns `null` |
| GOS-ACCESS-004 | Refresh fails | Returns `null` and does not overwrite stored payload |

### GOS-DISC: Disconnect / Revoke

| ID | Scenario | Expected Outcome |
|----|----------|------------------|
| GOS-DISC-001 | Disconnect connected location | Revokes Google token, clears `gbp_tokens`, sets `gbp_connected=false` |
| GOS-DISC-002 | Revoke failure | Still clears local storage and remains idempotent |

## Key Contract Decisions

### Controller Wrapper vs Service Lifecycle Contract

TASK-35-13 already established these controller-facing methods:
- `getAuthorizationUrl(int $locationId): array`
- `handleCallback(string $code, string $state): array`
- `disconnect(int $locationId): void`

TASK-35-7 adds deeper token lifecycle expectations now captured in the interface:
- `refreshAccessToken(string $refreshToken): ?array`
- `storeTokens(BusinessLocation $location, array $tokens): void`
- `getValidAccessToken(BusinessLocation $location): ?string`
- `disconnectLocation(BusinessLocation $location): void`

This keeps API-controller compatibility while giving Developer a precise service contract for secure token handling.

## Storage Expectations

Tests expect the task-level encrypted payload shape:

```php
$location->gbp_tokens = encrypt(json_encode([
    'access_token' => '...',
    'refresh_token' => '...',
    'expires_at' => '2026-03-10T12:00:00Z',
]));
```

## Mock / Config Expectations

### Google OAuth config
- `services.google.client_id`
- `services.google.client_secret`
- `app.url` for callback generation

### Token endpoint
- `https://oauth2.googleapis.com/token`

### Required scope
- `https://www.googleapis.com/auth/business.manage`

## Developer Notes

1. **Current mismatch to resolve:** the existing Local SEO OAuth implementation still leans on cache-based state + legacy token columns, while TASK-35-7 tests pin session-based CSRF state and encrypted `gbp_tokens` payload storage.
2. **Package note:** task notes still mention `league/oauth2-google`, but the backend composer manifest currently does not require it. Developer must either add the package or implement the same protocol-level behavior with Laravel HTTP while keeping the test contract green.
3. **Callback alignment:** after token exchange is implemented, `handleCallback()` should delegate to the exchange/store helpers so TASK-35-13 controller flow and TASK-35-7 token lifecycle stay consistent.
4. **Never expose tokens** in resources or API responses.
5. **High-risk item:** SecurityAuditor review remains mandatory before deployment.

## Test Execution

```bash
cd backend
php artisan test tests/Unit/Services/LocalSEO/GoogleOAuthServiceTest.php
```

## Comments

### PreTester Session (2026-03-10 11:03 GMT+2)
- Moved task into `Tests Coverage In Progress`, recycled the stale lock, and wrote the red-phase unit suite for GBP OAuth handling.
- Extended the Local SEO OAuth service contract with token lifecycle methods needed by the task while preserving the controller-facing wrapper used by TASK-35-13.
- Added compile-safe skeleton methods for encrypted `gbp_tokens` storage / retrieval / disconnect flow so the suite can define the target behavior without finishing implementation.
- Test execution result: `php artisan test tests/Unit/Services/LocalSEO/GoogleOAuthServiceTest.php` → **17 passing / 4 failing / 48 assertions**.
- Current red failures define the remaining implementation target:
  - move state storage / validation from cache to session,
  - implement authorization-code exchange against Google's token endpoint,
  - implement refresh-token exchange with normalized payload.
- Developer handoff focus:
  1. switch state storage from cache to session for CSRF validation,
  2. implement token exchange + refresh HTTP flow,
  3. align callback wrapper with encrypted `gbp_tokens` storage,
  4. keep disconnect idempotent and revoke-aware.
