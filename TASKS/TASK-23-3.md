# TASK-23-3: GSC Backlink Fetcher Service

## STATUS
`To Deploy`

## PRIORITY
`High`

## RISK
`Medium`

## Type
Backend

## Description
Create service to fetch backlink data from Google Search Console API. Reuses existing OAuth integration from FEAT-012. Implements the links endpoint to discover referring domains and sample backlink URLs.

## Related
- Specification: [SPEC-023](../SPECIFICATIONS/SPEC-023.md)
- Feature: [FEAT-023](../FEATURES/FEAT-023.md)
- Depends on: TASK-12-4, TASK-12-5

---

## Acceptance Criteria
- [x] `GSCBacklinkFetcher` service class created
- [x] Fetches referring domains from GSC links API
- [x] Extracts sample backlink URLs per domain
- [x] Handles pagination for large datasets
- [x] Implements rate limiting (GSC API quotas)
- [x] Reuses existing OAuth token management
- [x] Error handling for API failures
- [x] Unit tests with mocked GSC responses

---

## Technical Details

### GSC Links API Endpoint
```
GET https://www.googleapis.com/webmasters/v3/sites/{siteUrl}/links
```

**Response Structure:**
```json
{
  "sites": [
    { "siteUrl": "https://example.com" }
  ],
  "pages": [
    { "pageUrl": "https://example.com/page" }
  ]
}
```

### Service Methods

```php
class GSCBacklinkFetcher
{
    /**
     * Fetch all referring domains for a property
     */
    public function fetchReferringDomains(GSCConnection $connection): array;
    
    /**
     * Fetch sample backlink URLs for a domain
     */
    public function fetchSampleBacklinks(GSCConnection $connection, string $domain): array;
    
    /**
     * Fetch all backlink data for a project
     */
    public function fetchAllBacklinks(Project $project): array;
    
    /**
     * Handle API pagination
     */
    private function paginateRequest(string $url, array $params): array;
}
```

### Rate Limiting
- GSC API: ~200 requests per minute per project
- Implement exponential backoff on 429 responses
- Cache responses for 1 hour to reduce API calls

### OAuth Integration
- Reuse `OAuthService` from TASK-12-4
- Automatic token refresh via `ensureValidToken()`
- Connection status updates on failures

---

## Dependencies
- TASK-12-4 (GSC OAuth Service)
- TASK-12-5 (GSC Data Sync)
- TASK-23-2 (Backlink Models)

## Effort Estimate
`Medium` (4-6 hours)

---

## Comments
_Agent communication log_

### [2026-02-28 21:28] Agent: ProjectManager
**Prioritization Complete**

- **Priority**: `High` — Foundation service for GSC backlink data pipeline, required for sync job (TASK-23-4) and backlink history
- **Risk**: `Medium` — External API integration with rate limiting, pagination handling
- **Dependencies Verified**:
  - TASK-12-4 (OAuth Service): ✅ **Done** — Token management ready for reuse
  - TASK-12-5 (GSC Data Sync): ✅ **Done** — Pattern for GSC API integration established
  - TASK-23-2 (Backlink Models): `To Write Tests` — Can develop in parallel, models will be ready when GSC service tests run

**Reusability Note**: OAuthService from TASK-12-4 already implements `ensureValidToken()` for automatic token refresh. GSCBacklinkFetcher should reuse this pattern.

**Execution Order**: After TASK-23-2 (Models) reaches `To Develop`, tests can validate service → model integration.

Handing off to PreTester for test design.

### [2026-03-14 12:07 GMT+2] Agent: Developer
**Implementation Complete**

Service `GSCBacklinkFetcher` already implemented with comprehensive functionality:

| Feature | Status |
|---------|--------|
| `fetchReferringDomains()` | ✅ Implemented |
| `fetchSampleBacklinks()` | ✅ Implemented |
| `syncProject()` | ✅ Implemented |
| Pagination handling | ✅ Implemented |
| Rate limiting (200 req/min) | ✅ Implemented |
| Exponential backoff (1s, 2s, 4s, 8s, 16s) | ✅ Implemented |
| OAuth token auto-refresh | ✅ Implemented |
| Cache management (1hr TTL) | ✅ Implemented |
| Progress callback support | ✅ Implemented |
| Sync cancellation | ✅ Implemented |

**Test Results:** 55 tests, 125 assertions - ALL PASSING

**File Locations:**
- Service: `backend/app/Services/GSCBacklinkFetcher.php`
- Tests: `backend/tests/Unit/Services/GSCBacklinkFetcherTest.php`
- Test Doc: `TESTS/TEST-23-3.md`

**Dependencies Verified:**
- OAuthService: ✅ Reused with `ensureValidToken()`
- BacklinkRepository: ✅ Used for storage
- ReferringDomainRepository: ✅ Used for upsert
- AnchorTextRepository: ✅ Used for aggregation

**Handoff to LocalTester:** Ready for local testing and code review.
