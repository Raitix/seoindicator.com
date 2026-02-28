# TEST-23-3: GSC Backlink Fetcher Service Tests

## Overview
Unit and integration tests for `GSCBacklinkFetcher` service that fetches backlink data from Google Search Console API.

---

## Test Categories

### 1. Referring Domains Fetching (REF)
| ID | Scenario | Expected Result |
|----|----------|-----------------|
| REF-01 | Fetch referring domains from GSC | Returns array of domains with backlink counts |
| REF-02 | Handle pagination (50+ domains) | Aggregates all pages into single result |
| REF-03 | No referring domains found | Returns empty array, no error |
| REF-04 | Connection inactive/expired | Throws RuntimeException |
| REF-05 | API returns malformed response | Handles gracefully, logs error |
| REF-06 | Domain with special characters | Properly URL-encoded in requests |
| REF-07 | Large dataset (500+ domains) | Memory-efficient pagination |
| REF-08 | Concurrent fetch requests | Lock prevents duplicate fetches |

### 2. Sample Backlinks Fetching (SBL)
| ID | Scenario | Expected Result |
|----|----------|-----------------|
| SBL-01 | Fetch sample backlinks for domain | Returns array of source URLs |
| SBL-02 | Domain with no backlinks | Returns empty array |
| SBL-03 | Target URL extraction | Correctly extracts target URLs |
| SBL-04 | Anchor text extraction | Parses anchor text from link data |
| SBL-05 | Link type detection (dofollow/nofollow) | Correctly identifies link type |
| SBL-06 | Pagination for large backlink sets | Aggregates all pages |
| SBL-07 | Invalid domain parameter | Returns empty array, logs warning |

### 3. Full Backlink Sync (SYNC)
| ID | Scenario | Expected Result |
|----|----------|-----------------|
| SYNC-01 | Sync all backlinks for project | Fetches domains, then samples per domain |
| SYNC-02 | Project without GSC connection | Throws RuntimeException |
| SYNC-03 | Sync with progress callback | Callback receives progress updates |
| SYNC-04 | Sync cancellation support | Can be cancelled mid-sync |
| SYNC-05 | Partial sync failure | Continues with other domains, logs errors |
| SYNC-06 | Deduplication of backlinks | Existing backlinks updated, not duplicated |
| SYNC-07 | Stale backlink deactivation | Marks missing backlinks as inactive |

### 4. Rate Limiting (RL)
| ID | Scenario | Expected Result |
|----|----------|-----------------|
| RL-01 | API rate limit (429) | Exponential backoff, retry up to 5 times |
| RL-02 | Rate limit with Retry-After header | Uses header value for wait time |
| RL-03 | Concurrent request throttling | Max 200 requests/minute enforced |
| RL-04 | Cache-based rate tracking | Tracks API usage in cache |
| RL-05 | Rate limit lock timeout | Throws exception after max retries |
| RL-06 | Backoff pattern: 1s, 2s, 4s, 8s, 16s | Correct exponential sequence |

### 5. OAuth Integration (OAUTH)
| ID | Scenario | Expected Result |
|----|----------|-----------------|
| OAUTH-01 | Token auto-refresh | Uses OAuthService.ensureValidToken() |
| OAUTH-02 | Refresh failure handling | Throws RuntimeException with clear message |
| OAUTH-03 | Token expiry during sync | Auto-refreshes mid-sync |
| OAUTH-04 | Invalid/revoked token | Updates connection status to expired |
| OAUTH-05 | Reuses existing GSC connection | Finds connection by property_url match |

### 6. Error Handling (ERR)
| ID | Scenario | Expected Result |
|----|----------|-----------------|
| ERR-01 | Network timeout (30s) | Throws TimeoutException |
| ERR-02 | Invalid JSON response | Throws ParseException with context |
| ERR-03 | 403 Forbidden (insufficient scope) | Throws AuthorizationException |
| ERR-04 | 404 Not Found (property not verified) | Throws PropertyNotFoundException |
| ERR-05 | 500 Server Error | Retries with backoff, then throws |
| ERR-06 | Empty response body | Returns empty result, logs warning |
| ERR-07 | Connection reset | Retries once, then throws |

### 7. Caching (CACHE)
| ID | Scenario | Expected Result |
|----|----------|-----------------|
| CACHE-01 | Cache hit for recent fetch | Returns cached response |
| CACHE-02 | Cache miss | Fetches from API, stores in cache |
| CACHE-03 | Cache TTL (1 hour) | Cached data expires after TTL |
| CACHE-04 | Cache invalidation on sync | Clears cache when new data synced |
| CACHE-05 | Cache key format | Uses project_id:backlinks:v1 format |

### 8. Data Transformation (TRANS)
| ID | Scenario | Expected Result |
|----|----------|-----------------|
| TRANS-01 | GSC links API response parsing | Correctly maps to internal format |
| TRANS-02 | Domain extraction from source URL | Extracts root domain correctly |
| TRANS-03 | URL normalization | Normalizes protocol, removes trailing slash |
| TRANS-04 | Anchor text sanitization | Strips HTML, trims whitespace |
| TRANS-05 | Link type from rel attribute | Maps rel=nofollow correctly |

### 9. Edge Cases (EDGE)
| ID | Scenario | Expected Result |
|----|----------|-----------------|
| EDGE-01 | Unicode domain names | IDN handling, punycode conversion |
| EDGE-02 | Very long URLs (2000+ chars) | Truncates or handles gracefully |
| EDGE-03 | Self-referencing backlinks | Filters out self-referencing links |
| EDGE-04 | Internal links in backlinks list | Filters out internal links |
| EDGE-05 | Malformed anchor text (HTML) | Sanitizes HTML entities |
| EDGE-06 | Null/empty fields in API response | Uses default values |

### 10. Integration Tests (INT)
| ID | Scenario | Expected Result |
|----|----------|-----------------|
| INT-01 | Full sync flow with mock API | Stores backlinks, domains, snapshots |
| INT-02 | Sync updates existing data | Updates counts, marks stale as inactive |
| INT-03 | Multiple projects, same GSC property | Isolates data per project |
| INT-04 | Snapshot creation after sync | Creates BacklinkSnapshot with metrics |
| INT-05 | ReferringDomain upsert | Updates existing domains, creates new |
| INT-06 | AnchorText aggregation | Aggregates anchor texts with counts |

---

## Test File Locations

| Type | Path |
|------|------|
| Unit Tests | `backend/tests/Unit/Services/GSCBacklinkFetcherTest.php` |
| Integration Tests | `backend/tests/Feature/Services/GSCBacklinkIntegrationTest.php` |

---

## Mock Data Patterns

### GSC Links API Response (sites)
```json
{
  "sites": [
    { "siteUrl": "https://referring-site1.com/" },
    { "siteUrl": "https://referring-site2.com/page" }
  ]
}
```

### GSC Links API Response (pages with samples)
```json
{
  "pages": [
    {
      "pageUrl": "https://referring-site1.com/linking-page",
      "targetUrl": "https://mysite.com/target",
      "anchorText": "great article",
      "linkType": "dofollow"
    }
  ]
}
```

### Rate Limit Response
```json
{
  "error": {
    "code": 429,
    "message": "Rate limit exceeded",
    "errors": [
      {
        "reason": "rateLimitExceeded"
      }
    ]
  }
}
```

---

## Expected Test Count

| Category | Unit Tests | Assertions |
|----------|-----------|------------|
| Referring Domains | 8 | 15+ |
| Sample Backlinks | 7 | 12+ |
| Full Sync | 7 | 20+ |
| Rate Limiting | 6 | 15+ |
| OAuth Integration | 5 | 10+ |
| Error Handling | 7 | 15+ |
| Caching | 5 | 10+ |
| Data Transformation | 5 | 12+ |
| Edge Cases | 6 | 15+ |
| **Total** | **56** | **124+** |

---

## Acceptance Criteria Verification

| Criteria | Test Coverage |
|----------|---------------|
| GSCBacklinkFetcher service class created | INT-01, SYNC-01 |
| Fetches referring domains from GSC links API | REF-01 to REF-08 |
| Extracts sample backlink URLs per domain | SBL-01 to SBL-07 |
| Handles pagination for large datasets | REF-02, REF-07, SBL-06 |
| Implements rate limiting (GSC API quotas) | RL-01 to RL-06 |
| Reuses existing OAuth token management | OAUTH-01 to OAUTH-05 |
| Error handling for API failures | ERR-01 to ERR-07 |
| Unit tests with mocked GSC responses | All unit tests |

---

## Created: 2026-02-28
## Agent: PreTester
