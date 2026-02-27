# TEST-12-5: GSC Data Sync Service Tests

## Overview
Tests for `GSCDataSync` service that handles Google Search Console API data fetching and storage.

## Test Location
`backend/tests/Unit/Services/GSCDataSyncServiceTest.php`

## Test Coverage

### Unit Tests (13 tests, 45 assertions)

| Test Name | Purpose |
|-----------|---------|
| `gsc api response parsing` | Parses API response with clicks, impressions, CTR, position |
| `batch processing` | Handles pagination with startRow for large datasets |
| `date range calculation` | Calculates proper date ranges for sync |
| `weekly data aggregation` | Aggregates daily data into weekly summaries |
| `empty response handling` | Gracefully handles empty API responses |
| `malformed response handling` | Handles invalid JSON and missing fields |
| `rate limit detection` | Detects 429 responses and quota errors |
| `connection validation before sync` | Validates active connection before sync |
| `ctr calculation validation` | Verifies CTR = clicks/impressions formula |
| `position average calculation` | Validates position averaging logic |
| `sync lock mechanism` | Prevents concurrent syncs via cache lock |
| `opportunity score calculation` | Calculates opportunity = impressions * (1 - CTR) |
| `query matching similarity` | Matches queries to keywords using similarity threshold |

### Integration Tests

**GSCOAuthServiceTest** (11 tests)
- Token encryption on storage
- Token expiration detection
- Token refresh flow
- Authorization URL generation
- Rate limit backoff
- Connection status transitions
- Connection cascade delete

**GSCOAuthIntegrationTest** (9 tests)
- Full OAuth connection flow
- OAuth token refresh flow
- OAuth connection revocation
- Connection deletion
- Duplicate connection prevention
- Different users same property
- OAuth error handling (invalid_grant, rate_limit)
- Token expiration triggers refresh

**GSCApiEndpointsTest** (11 tests)
- List connections endpoint
- List connections returns user only
- Connect endpoint
- Disconnect endpoint
- Disconnect own property
- GSC data endpoint
- GSC data invalid date
- Opportunities endpoint
- Trend endpoint
- Tokens not exposed
- Manual sync endpoint

## Running Tests

```bash
# Run all GSC tests
php artisan test --filter=GSC

# Run only DataSync tests
php artisan test --filter=GSCDataSync

# Run with coverage
php artisan test --filter=GSC --coverage
```

## Test Data

Tests use mocked HTTP responses to simulate GSC API:
- Valid responses with search analytics data
- Empty responses
- Malformed JSON
- Rate limit (429) errors
- Authentication errors

## Acceptance Criteria Verified

- [x] Daily sync job imports GSC data for all connected projects
- [x] Rate limiting handled gracefully without data loss
- [x] Historical import supports custom date ranges
- [x] Unit and integration tests verify sync logic

## Last Updated
2026-02-28 by LocalTester
