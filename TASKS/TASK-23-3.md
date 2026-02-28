# TASK-23-3: GSC Backlink Fetcher Service

## STATUS
`To Prioritize`

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
