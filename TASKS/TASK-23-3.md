# TASK-23-3: GSC Backlink Fetcher Service

## STATUS
`Tests Complete`

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
- [ ] `GSCBacklinkFetcher` service class created
- [ ] Fetches referring domains from GSC links API
- [ ] Extracts sample backlink URLs per domain
- [ ] Handles pagination for large datasets
- [ ] Implements rate limiting (GSC API quotas)
- [ ] Reuses existing OAuth token management
- [ ] Error handling for API failures
- [ ] Unit tests with mocked GSC responses

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
