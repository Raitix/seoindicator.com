# TASK-35-8: GBP API Client Service

## STATUS
`To Write Tests`

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
