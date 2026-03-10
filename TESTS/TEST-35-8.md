# TEST-35-8: GBP API Client Service

## Task Reference
- **Task:** TASK-35-8
- **Specification:** SPEC-035: Local SEO & Google Business Profile Tracking
- **Priority:** High
- **Risk:** Medium
- **Depends On:** TASK-35-7 (Google OAuth Service)

## Overview

Red-phase coverage for the Google Business Profile API client that will sit underneath the GBP insights/reviews sync services. The suite defines the request contract for:
- account discovery
- location listing and single-location fetches
- insights reporting
- reviews pagination
- owner replies
- 401 refresh retry behavior
- 429 exponential backoff behavior

Because the current backend composer manifest still does **not** include `google/apiclient`, the tests intentionally lock the service to a transport-agnostic HTTP contract. Developer may implement this via Laravel HTTP client or Google client libraries as long as the public service contract and test expectations stay green.

## Files

| File | Type | Tests | Description |
|------|------|-------|-------------|
| `backend/app/Services/GBP/GBPApiService.php` | Skeleton | - | Compile-safe scaffold for TASK-35-8 public contract + helper hooks |
| `backend/tests/Unit/Services/GBP/GBPApiServiceTest.php` | Unit | 12 | Request contract, path normalization, retry/refresh expectations |
| `backend/tests/Feature/Services/GBP/GBPApiServiceSandboxTest.php` | Feature | 1 env-gated | Optional live smoke when sandbox credentials are configured |

## Test Scenarios

### GBP-CLS: Class & Container

| ID | Scenario | Expected Outcome |
|----|----------|------------------|
| GBP-CLS-001 | Service class exists | `GBPApiService` autoloads |
| GBP-CLS-002 | Implements interface | Service implements `GBPApiServiceInterface` |
| GBP-CLS-003 | Interface exposes all required methods | accounts, locations, location, insights, reviews, reply |
| GBP-CLS-004 | Container binding resolves contract | `app(GBPApiServiceInterface::class)` returns service |

### GBP-CFG: Basic Helpers

| ID | Scenario | Expected Outcome |
|----|----------|------------------|
| GBP-CFG-001 | Base URL default | Uses `https://mybusiness.googleapis.com/v4` when config unset |
| GBP-CFG-002 | Account normalization | Raw `123` becomes `accounts/123` |

### GBP-ACC: Accounts API

| ID | Scenario | Input | Expected Outcome |
|----|----------|-------|------------------|
| GBP-ACC-001 | List accounts | `accessToken` | GET `/accounts`, bearer auth, returns `accounts[]` |

### GBP-LOC: Locations API

| ID | Scenario | Input | Expected Outcome |
|----|----------|-------|------------------|
| GBP-LOC-001 | List account locations | `accountId=123` | GET `/accounts/123/locations`, normalize `place_id`, `name`, `address` |
| GBP-LOC-002 | Get single location | `accounts/123/locations/456` | GET full resource path, returns detail payload |

### GBP-INS: Insights API

| ID | Scenario | Input | Expected Outcome |
|----|----------|-------|------------------|
| GBP-INS-001 | Report insights | `locationName + dateRange` | POST `/accounts/{id}/locations:reportInsights`, body includes requested location + time range |

### GBP-REV: Reviews API

| ID | Scenario | Input | Expected Outcome |
|----|----------|-------|------------------|
| GBP-REV-001 | List reviews with pagination | `pageSize=25`, `pageToken=page-1` | GET `/reviews?pageSize=25&pageToken=page-1` |
| GBP-REV-002 | Reply to review | `reviewId`, `reply` | PUT `/reply` with `{comment: reply}` and returns `true` |

### GBP-ERR: Retry / Refresh / Quotas

| ID | Scenario | Input | Expected Outcome |
|----|----------|-------|------------------|
| GBP-ERR-001 | 401 auto-refresh retry | expired access token | Resolve refresh token, call TASK-35-7 refresh helper, retry once with new access token |
| GBP-ERR-002 | 429 exponential backoff | rate-limited response | Delay sequence starts at `1000ms`, retries request, then returns success |

### GBP-SANDBOX: Optional Live Validation

| ID | Scenario | Expected Outcome |
|----|----------|------------------|
| GBP-SBX-001 | Sandbox smoke | When `GBP_SANDBOX_ACCESS_TOKEN` is configured, `getAccounts()` returns an array; otherwise test is skipped |

## Contract Decisions

### 1. API Base URL
- Default: `https://mybusiness.googleapis.com/v4`
- Read from `services.google.business_profile_base_url` when configured

### 2. Location Insights Endpoint
Task acceptance requests `getInsights(locationName, ...)`, but Google v4 insights are account-scoped (`accounts/{id}/locations:reportInsights`).
The tests therefore require the service to:
1. extract the parent account from `accounts/{accountId}/locations/{locationId}`
2. POST to `/accounts/{accountId}/locations:reportInsights`
3. include the target `locationName` in the request body

### 3. 401 Refresh Design Gap
The public task contract passes only an `accessToken`, while TASK-35-7 token refresh needs a refresh token.
The test suite therefore defines an internal expectation:
- the service must support resolving a refresh token for a failed access token (implementation strategy left to Developer)
- once resolved, it must call `GoogleOAuthServiceInterface::refreshAccessToken()` and retry once

This preserves the existing public method signatures while making the task acceptance achievable.

## Mock Data

### Accounts Response
```php
[
    'accounts' => [
        [
            'name' => 'accounts/123',
            'accountName' => 'Primary Agency',
            'type' => 'PERSONAL',
        ],
    ],
]
```

### Locations Response
```php
[
    'locations' => [
        [
            'name' => 'accounts/123/locations/456',
            'title' => 'SEOIndicator Riga',
            'locationKey' => ['placeId' => 'place-123'],
            'storefrontAddress' => [
                'addressLines' => ['Brivibas iela 1'],
                'locality' => 'Riga',
                'postalCode' => 'LV-1010',
                'regionCode' => 'LV',
            ],
        ],
    ],
]
```

### Reviews Response
```php
[
    'reviews' => [
        [
            'reviewId' => 'review-1',
            'comment' => 'Great service',
        ],
    ],
    'nextPageToken' => 'page-2',
]
```

## Test Execution

```bash
cd backend
php -l app/Services/GBP/GBPApiService.php
php -l tests/Unit/Services/GBP/GBPApiServiceTest.php
php -l tests/Feature/Services/GBP/GBPApiServiceSandboxTest.php
php artisan test tests/Unit/Services/GBP/GBPApiServiceTest.php
php artisan test tests/Feature/Services/GBP/GBPApiServiceSandboxTest.php
```

## Developer Notes

1. **Do not change the public interface** just to make token refresh easier; keep the task-level methods stable and solve refresh internally.
2. **Prefer a single request helper** that centralizes bearer auth, JSON parsing, 401 refresh retry, and 429 backoff.
3. **Normalize locations** into at least `place_id`, `name`, and `address`, because downstream tasks expect those fields.
4. **Composer mismatch:** `google/apiclient` is still absent from `backend/composer.json`. If Developer adds it, keep the same external behavior and tests.
5. **Sandbox test is env-gated** on purpose; it should remain optional and never fail CI/dev environments without Google credentials.

## Comments

### PreTester Session (2026-03-10 16:14 GMT+02:00)
- Wrote the red-phase GBP API client contract for TASK-35-8 and added a compile-safe scaffold so the service can be resolved and targeted by tests.
- Added container binding expectations, request-contract unit coverage, and an env-gated sandbox smoke placeholder.
- Execution summary: syntax checks passed; focused suite finished at **6 passing / 8 failing / 1 skipped / 12 assertions** after restoring `backend/vendor` with `composer install --ignore-platform-reqs`.
- Important design note for Developer: the task acceptance requires automatic refresh on 401, but the public method signatures only provide an access token. The tests therefore define an internal refresh-token-resolution hook without changing the public interface.
