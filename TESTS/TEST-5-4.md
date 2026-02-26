# TEST-5-4: Google Suggest Fetcher (MVP)

## Task Reference
- **Task**: [TASK-5-4](../TASKS/TASK-5-4.md)
- **Specification**: [SPEC-005](../SPECIFICATIONS/SPEC-005.md)
- **Feature**: [FEAT-005](../FEATURES/FEAT-005.md)

## Overview
Tests for the Google Suggest Fetcher - a service that retrieves keyword suggestions from Google's free Suggest API. This is the primary FREE source for keyword discovery in MVP. Tests follow TDD principles - written before implementation.

---

## Test Files

| File | Type | Description |
|------|------|-------------|
| `backend/tests/Unit/Services/Fetchers/GoogleSuggestFetcherTest.php` | Unit | Implementation tests with mocked HTTP - **24 test methods** |

**Note:** Interface tests are covered implicitly through the implementation tests. The interface will be created by the Developer.

---

## Test Scenarios

### 1. SuggestFetcherInterface Tests (5 tests)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| I-001 | Interface exists | N/A | `SuggestFetcherInterface` defined in `App\Services` |
| I-002 | fetch method signature | N/A | `fetch(string $keyword): array` |
| I-003 | Returns array of strings | Mock implementation | `['suggestion 1', 'suggestion 2']` |
| I-004 | Returns empty array on error | Mock that throws | `[]` (no exceptions) |
| I-005 | Keyword parameter required | Missing keyword | TypeError (PHP enforces) |

### 2. GoogleSuggestFetcher Tests (32 tests)

#### Basic Functionality

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| GS-001 | Fetch returns array | 'seo tools' | Array of suggestion strings |
| GS-002 | Empty keyword returns empty array | '' | `[]` |
| GS-003 | Whitespace keyword returns empty array | '   ' | `[]` |
| GS-004 | Null bytes removed | "keyword\x00test" | Fetches 'keywordtest' |
| GS-005 | URL encoding applied | 'seo tools' | Request URL encodes keyword |
| GS-006 | Correct API endpoint | 'test' | Calls `suggestqueries.google.com/complete/search` |
| GS-007 | Client parameter set | Any keyword | `client=firefox` in URL |
| GS-008 | Parses JSON response | Valid JSON response | Returns suggestions array |
| GS-009 | Extracts suggestions from tuple | `["seo", ["seo tools", "seo optimization"]]` | `["seo tools", "seo optimization"]` |
| GS-010 | Returns first element ignored | `["seo", [...]]` | Original keyword not in output |

#### Error Handling

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| GS-011 | HTTP 429 rate limit | Rate limited response | Returns `[]` (no exception) |
| GS-012 | HTTP 500 server error | Server error response | Returns `[]` (no exception) |
| GS-013 | HTTP 403 forbidden | Forbidden response | Returns `[]` (no exception) |
| GS-014 | Network timeout | Timeout exception | Returns `[]` (no exception) |
| GS-015 | DNS resolution failure | Connection failed | Returns `[]` (no exception) |
| GS-016 | Invalid JSON response | `"not valid json"` | Returns `[]` (no exception) |
| GS-017 | Empty response body | Empty string | Returns `[]` (no exception) |
| GS-018 | Malformed response array | `["seo"]` (missing suggestions) | Returns `[]` (no exception) |
| GS-019 | Non-array suggestions | `["seo", "not an array"]` | Returns `[]` (no exception) |
| GS-020 | Suggestions with non-strings | `["seo", [1, 2, 3]]` | Filters to string values only |

#### Rate Limiting & Retry

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| GS-021 | Respects rate limit headers | 429 with Retry-After | Waits before retry (configurable) |
| GS-022 | Max retries configurable | Multiple failures | Stops after max retries |
| GS-023 | Exponential backoff | Multiple retries | Increasing delays between retries |
| GS-024 | Rate limit disabled | Config: rateLimit=false | No waiting on rate limit |

#### Caching (Optional Optimization)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| GS-025 | Cache hit returns cached | Cached keyword | Returns cached suggestions |
| GS-026 | Cache miss fetches fresh | Non-cached keyword | Fetches from API |
| GS-027 | Cache TTL 24 hours | Cached 23h ago | Returns cached (still valid) |
| GS-028 | Cache expired | Cached 25h ago | Fetches fresh from API |
| GS-029 | Cache disabled | Config: cache=false | Always fetches fresh |

#### Edge Cases & Security

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| GS-030 | Very long keyword | 500+ character keyword | Returns `[]` or truncated |
| GS-031 | Special characters | '<script>alert(1)</script>' | Keyword URL encoded, no XSS |
| GS-032 | Unicode/emoji keywords | 'café ☕ tools' | Properly encoded in URL |
| GS-033 | SQL injection attempt | "'; DROP TABLE--" | Treated as literal string |
| GS-034 | Newlines in keyword | "keyword\ninjection" | Newlines handled gracefully |
| GS-035 | Maximum suggestions limit | API returns 100+ | Limited to configured max |

---

## Mock Data

### Valid API Response
```json
["seo", ["seo tools", "seo optimization", "seo meaning", "seo analyst", "seo services", "seo checker", "seo marketing", "seo jobs", "seo certification", "seo specialist"]]
```

### Response Structure
```
[
  "original_keyword",    // Index 0: The queried keyword
  ["suggestion1", ...]   // Index 1: Array of suggestions
]
```

### HTTP Error Responses
```
429 Too Many Requests - Rate limited
500 Internal Server Error - Google error
403 Forbidden - IP blocked
```

### Exception Scenarios
```
GuzzleHttp\Exception\ConnectException - Network failure
GuzzleHttp\Exception\RequestException - HTTP error
```

---

## Coverage Targets

| Component | Target | Notes |
|-----------|--------|-------|
| SuggestFetcherInterface | 100% | Interface contract |
| GoogleSuggestFetcher | 100% | All methods and branches |
| Error paths | 100% | All failure modes |
| Edge cases | 100% | Security, special chars |

---

## Test Execution

```bash
# Run interface tests
cd backend
php artisan test tests/Unit/Services/SuggestFetcherInterfaceTest.php

# Run implementation tests
php artisan test tests/Unit/Services/GoogleSuggestFetcherTest.php

# Run all suggest tests
php artisan test --filter "SuggestFetcher"

# Run with coverage
php artisan test tests/Unit/Services/GoogleSuggestFetcherTest.php --coverage
```

---

## Dependencies

- Guzzle HTTP Client (or Laravel HTTP client)
- Laravel caching (optional)
- No external services for unit tests (mocked)

---

## Notes for Developer

1. **Interface Location**: `backend/app/Services/SuggestFetcherInterface.php`

2. **Implementation Location**: `backend/app/Services/GoogleSuggestFetcher.php`

3. **API Endpoint**: 
   ```
   http://suggestqueries.google.com/complete/search?client=firefox&q={keyword}
   ```
   Note: Uses HTTP (not HTTPS) for the legacy API.

4. **Response Format**: JSON array where index 0 is the queried keyword and index 1 is the suggestions array.

5. **Error Handling Philosophy**: Never throw exceptions. Return empty array on any failure. Log errors for debugging.

6. **Rate Limiting**: 
   - Google doesn't publish rate limits for this API
   - Implement configurable delays between requests
   - Respect 429 responses with Retry-After header
   - Consider IP rotation if needed (post-MVP)

7. **Caching Implementation** (optional):
   ```php
   $cacheKey = 'suggest:' . md5($keyword);
   $ttl = 86400; // 24 hours in seconds
   ```

8. **Configuration** (suggested):
   ```php
   // config/services.php
   'google_suggest' => [
       'enabled' => true,
       'cache' => true,
       'cache_ttl' => 86400, // 24 hours
       'timeout' => 5,       // seconds
       'max_suggestions' => 10,
       'retry_attempts' => 3,
       'retry_delay' => 1000, // ms
   ],
   ```

9. **HTTP Client Choice**: Use Laravel's HTTP client facade (`Http::get()`) or inject Guzzle for testability.

10. **Logging**: Log failures at warning level for monitoring:
    ```php
    Log::warning('Google Suggest API failed', [
        'keyword' => $keyword,
        'error' => $e->getMessage(),
    ]);
    ```

11. **Testing Strategy**:
    - Unit tests mock HTTP client
    - Integration tests use recorded responses (VCR-like) or skip if no network
    - Never make real API calls in CI

12. **IP Blocking Risk**: Excessive requests can result in temporary IP blocks. Start conservative with rate limiting.

---

## Security Checklist

| Check | Status | Notes |
|-------|--------|-------|
| No XSS from responses | ⚠️ Test | Suggestions could contain malicious content |
| No SQL injection | ⚠️ Test | Keywords treated as data, not queries |
| No SSRF | ✅ Safe | Fixed API endpoint, no user-controlled URL |
| No data leakage in logs | ⚠️ Test | Don't log sensitive keywords |
| HTTPS vs HTTP | ⚠️ Note | API uses HTTP (legacy) |

---

## Comments

### [2026-02-26 23:12] PreTester: Test Design Complete
- Created test documentation (TEST-5-4.md)
- Test file already exists: `backend/tests/Unit/Services/Fetchers/GoogleSuggestFetcherTest.php`
- 24 test methods covering all scenarios
- Tests failing as expected (implementation doesn't exist) - TDD approach
- Status: To Write Tests → To Develop

### [2026-02-26 23:10] PreTester: Test Design Complete
- Created test documentation
- 42 total test scenarios (5 interface + 32 implementation + 5 security)
- Tests follow TDD approach - will fail until implementation exists
- Mock-based unit tests for isolation
- Status: Tests Coverage In Progress
