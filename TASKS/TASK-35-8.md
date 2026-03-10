# TASK-35-8: GBP API Client Service

## STATUS
`To Develop`

## SPECIFICATION
SPEC-035: Local SEO & Google Business Profile Tracking

## PRIORITY
`High`

## RISK
`Medium`

## Description
Create GBPApiService for interacting with Google Business Profile API to fetch account information, locations, insights, and reviews.

## Acceptance Criteria
- [ ] GBPApiService created with interface
- [ ] Method: getAccounts(string $accessToken): array
  - Lists all GBP accounts user has access to
  - Returns array of account objects
- [ ] Method: getLocations(string $accountId, string $accessToken): array
  - Lists all locations under an account
  - Returns array of location objects with place_id, name, address
- [ ] Method: getLocation(string $locationName, string $accessToken): array
  - Gets detailed location info
  - locationName format: accounts/{accountId}/locations/{locationId}
- [ ] Method: getInsights(string $locationName, string $accessToken, array $dateRange): array
  - Fetches GBP insights data
  - Returns views, searches, calls, directions, website_clicks, photos_views
- [ ] Method: getReviews(string $locationName, string $accessToken, int $pageSize = 50): array
  - Fetches reviews with pagination
  - Returns array of review objects
- [ ] Method: respondToReview(string $locationName, string $reviewId, string $reply, string $accessToken): bool
  - Posts owner response to review
- [ ] Rate limiting compliance with Google API quotas
- [ ] Error handling for API errors (401, 403, 429, etc.)
- [ ] Automatic token refresh on 401 responses
- [ ] Unit tests with mocked API responses
- [ ] Integration tests with GBP API sandbox

## Dependencies
- TASK-35-7 (Google OAuth Service)

## Effort Estimate
4 hours

## Technical Notes
- Use google/apiclient package
- GBP API base URL: https://mybusiness.googleapis.com/v4
- Consider implementing retry logic with exponential backoff
- Cache API responses where appropriate (insights can be cached)

---

*Created by SpecSystemAnalyst on 2026-03-08*

## Comments

### PreTester Session (2026-03-10 16:14 GMT+02:00)
- **Status:** `To Write Tests` → `Tests Coverage In Progress` → `To Develop`
- Recycled the stale task lock, added the red-phase GBP API client contract, and created a compile-safe `GBPApiService` scaffold plus container binding so Developer has a concrete target instead of starting from a blank class.
- **Files created / updated:**
  - `PROJECT-SEOINDICATOR/TESTS/TEST-35-8.md`
  - `backend/app/Services/GBP/GBPApiService.php`
  - `backend/app/Providers/AppServiceProvider.php`
  - `backend/tests/Unit/Services/GBP/GBPApiServiceTest.php`
  - `backend/tests/Feature/Services/GBP/GBPApiServiceSandboxTest.php`
- **Coverage delivered:** 12 task-focused tests + 1 env-gated sandbox smoke stub covering:
  - class/interface/container wiring
  - account-path normalization
  - accounts / locations / location / insights / reviews / reply request contracts
  - 401 refresh-retry expectation via TASK-35-7 OAuth helper
  - 429 exponential-backoff expectation
- **Execution:**
  - `php -l app/Services/GBP/GBPApiService.php` ✅
  - `php -l app/Providers/AppServiceProvider.php` ✅
  - `php -l tests/Unit/Services/GBP/GBPApiServiceTest.php` ✅
  - `php -l tests/Feature/Services/GBP/GBPApiServiceSandboxTest.php` ✅
  - `composer install --no-interaction --prefer-dist --ignore-platform-reqs` ✅ (needed because `backend/vendor` was missing and local CLI PHP lacks `ext-gd` / `ext-zip`)
  - `php artisan test tests/Unit/Services/GBP/GBPApiServiceTest.php tests/Feature/Services/GBP/GBPApiServiceSandboxTest.php` ➜ **6 passing / 8 failing / 1 skipped / 12 assertions**
- **Expected red failures left for Developer:**
  1. implement the shared HTTP request pipeline in `GBPApiService::requestJson()`;
  2. send authenticated requests to the documented GBP v4 endpoints and normalize location payloads (`place_id`, `name`, `address`);
  3. implement one-time 401 refresh retry using TASK-35-7 token refresh support without changing the public method signatures;
  4. implement 429 exponential backoff / retry behavior;
  5. keep the env-gated sandbox smoke optional.
- **Important design note:** task acceptance requires automatic refresh on 401, but the public service contract only receives an access token. The red suite therefore defines an internal refresh-token-resolution hook expectation without expanding the public interface; Developer should solve that bridge internally against TASK-35-7 token storage.
