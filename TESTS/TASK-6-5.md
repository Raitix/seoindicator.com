# TASK-6-5: Rate Limiter Middleware Tests

## Task Reference
- **Task:** [TASK-6-5](../TASKS/TASK-6-5.md)
- **Feature:** [FEAT-006](../FEATURES/FEAT-006.md) - API Access & Webhooks
- **Specification:** [SPEC-006](../SPECIFICATIONS/SPEC-006.md)

---

## Overview

Tests for the `RateLimiter` middleware that enforces rate limiting on API endpoints. The middleware implements a sliding window algorithm for per-API-key rate limiting, returns appropriate headers on each response, and blocks requests that exceed the configured limit with a 429 response.

---

## Test Files

| File | Type | Purpose |
|------|------|---------|
| `backend/tests/Unit/Middleware/RateLimiterTest.php` | Unit | Middleware unit tests |
| `backend/tests/Unit/Services/RateLimiterServiceTest.php` | Unit | Rate limiter service tests |
| `backend/tests/Integration/Middleware/RateLimiterIntegrationTest.php` | Integration | Full middleware pipeline tests |
| `backend/tests/Feature/Api/RateLimitHeadersTest.php` | Feature | API response header tests |

---

## Service: RateLimiterService

### Expected Service Interface

```php
class RateLimiterService
{
    /**
     * Check if request is allowed for the given key.
     */
    public function isAllowed(string $key, int $maxRequests, int $windowMs): bool;
    
    /**
     * Get remaining requests for the given key.
     */
    public function getRemaining(string $key, int $maxRequests, int $windowMs): int;
    
    /**
     * Get the reset timestamp for the given key.
     */
    public function getResetTime(string $key, int $windowMs): int;
    
    /**
     * Get current request count for the given key.
     */
    public function getCount(string $key): int;
    
    /**
     * Reset the rate limit counter for the given key.
     */
    public function reset(string $key): void;
    
    /**
     * Increment the request count for the given key.
     */
    public function increment(string $key, int $windowMs): void;
}
```

---

## Middleware: RateLimiter

### Expected Middleware Interface

```php
class RateLimiter
{
    /**
     * Handle an incoming request.
     */
    public function handle($request, Closure $next): Response;
    
    /**
     * Get the rate limit key for the request.
     */
    protected function resolveRequestSignature($request): string;
    
    /**
     * Build the rate limit exceeded response.
     */
    protected function buildResponse(int $retryAfter): JsonResponse;
}
```

---

## Rate Limit Check Tests (RL)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| RL-001 | First request allowed | key=new, max=60 | isAllowed=true |
| RL-002 | Request within limit | count=59, max=60 | isAllowed=true |
| RL-003 | Request at limit | count=60, max=60 | isAllowed=false |
| RL-004 | Request over limit | count=61, max=60 | isAllowed=false |
| RL-005 | Different keys isolated | key1 at limit, key2 new | key2 allowed |
| RL-006 | Sliding window expires old requests | Old requests outside window | Count decreased |
| RL-007 | Custom max requests | max=100, count=99 | isAllowed=true |
| RL-008 | Zero max requests | max=0 | isAllowed=false |
| RL-009 | Very high max requests | max=10000, count=5000 | isAllowed=true |
| RL-010 | Window boundary test | Request at window edge | Correctly counted |

---

## Sliding Window Algorithm Tests (SW)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| SW-001 | Window starts at first request | First request | Window established |
| SW-002 | Requests within window counted | Requests within 60s | All counted |
| SW-003 | Old requests expire | Request 61s old | Not counted |
| SW-004 | Partially overlapping windows | Two window periods | Correct count |
| SW-005 | Window reset after expiry | All requests expired | New window starts |
| SW-006 | Precise timing at boundary | Request at exactly 60s | Handled correctly |
| SW-007 | Multiple windows per key | Rapid requests across windows | Correct tracking |
| SW-008 | Window size configuration | 30s, 60s, 120s windows | Correct behaviour |
| SW-009 | Sub-second precision | Requests < 1s apart | All counted |
| SW-010 | Clock skew handling | System time changes | Graceful handling |

---

## Remaining Requests Tests (RM)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| RM-001 | Full remaining on new key | New key, max=60 | remaining=60 |
| RM-002 | Decreased after requests | 5 requests, max=60 | remaining=55 |
| RM-003 | Zero at limit | 60 requests, max=60 | remaining=0 |
| RM-004 | Never negative | 65 requests, max=60 | remaining=0 |
| RM-005 | Recovers after window expiry | Expired requests | remaining restored |
| RM-006 | Correct with custom max | max=100, 25 requests | remaining=75 |
| RM-007 | Different keys independent | key1 at 0, key2 new | key2 remaining=60 |
| RM-008 | Remaining updates immediately | After each request | Accurate count |

---

## Reset Time Tests (RT)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| RT-001 | Reset time in future | Current request | Unix timestamp > now |
| RT-002 | Reset time approximately 60s ahead | 60s window | reset ≈ now + 60 |
| RT-003 | Reset time with custom window | 30s window | reset ≈ now + 30 |
| RT-004 | Reset time is Unix timestamp | Any request | Integer timestamp |
| RT-005 | Reset time adjusts with window | Requests in window | Correct end time |
| RT-006 | Reset time after window expiry | Expired window | New window reset |
| RT-007 | Multiple keys have different resets | key1, key2 | Different reset times |
| RT-008 | Reset time is consistent | Same key, same window | Same reset time |

---

## Request Count Tests (RC)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| RC-001 | Count starts at 0 | New key | count=0 |
| RC-002 | Count increments | After increment | count increased |
| RC-003 | Count reflects all requests | 10 requests | count=10 |
| RC-004 | Count isolated per key | key1=10, key2=5 | Independent counts |
| RC-005 | Count after window expiry | Old requests | count=0 or reduced |
| RC-006 | Count accurate with rapid requests | 100 rapid requests | count=100 |
| RC-007 | Count resets with reset() | After reset() | count=0 |

---

## Increment Tests (IN)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| IN-001 | Increment new key | New key | count=1 |
| IN-002 | Increment existing key | Existing count=5 | count=6 |
| IN-003 | Increment with timestamp | Manual timestamp | Stored correctly |
| IN-004 | Rapid increments | 100 increments | All recorded |
| IN-005 | Increment isolated per key | key1++, key2++ | Independent |
| IN-006 | Increment stores timestamp | After increment | Timestamp stored |

---

## Reset Tests (RS)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| RS-001 | Reset clears count | After reset | count=0 |
| RS-002 | Reset allows new requests | After reset | isAllowed=true |
| RS-003 | Reset one key doesn't affect others | Reset key1 | key2 unchanged |
| RS-004 | Reset non-existent key | Invalid key | No error |
| RS-005 | Reset then increment | Reset, then increment | count=1 |
| RS-006 | Reset clears history | After reset | No old requests counted |

---

## Middleware Integration Tests (MI)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| MI-001 | Request passes with valid key | Valid API key | Response 200 |
| MI-002 | Request blocked at limit | API key at limit | Response 429 |
| MI-003 | Different API keys have separate limits | Two keys | Independent limits |
| MI-004 | Rate limit headers present | Any request | X-RateLimit-* headers |
| MI-005 | Retry-After header on 429 | Blocked request | Retry-After header |
| MI-006 | Per-key configurable limit | Key with rate_limit=120 | Custom limit applied |
| MI-007 | Default limit applied | Key without custom limit | 60 req/min |
| MI-008 | Missing API key | No Authorization header | Pass through (auth middleware handles) |
| MI-009 | Revoked key | Revoked API key | Pass through (auth handles) |
| MI-010 | Concurrent requests | Multiple simultaneous requests | Correct counting |

---

## Header Tests (HD)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| HD-001 | X-RateLimit-Limit header | Request with API key | Header = max requests |
| HD-002 | X-RateLimit-Remaining header | Request with API key | Header = remaining |
| HD-003 | X-RateLimit-Reset header | Request with API key | Header = Unix timestamp |
| HD-004 | Headers on successful request | 200 response | All headers present |
| HD-005 | Headers on 429 response | Rate limited | All headers present |
| HD-006 | Remaining decrements | Multiple requests | Decreasing value |
| HD-007 | Limit reflects key setting | Custom rate_limit | Correct value |
| HD-008 | Reset time is future | Any request | > current time |
| HD-009 | Headers on error response | Server error | Headers present |
| HD-010 | Headers on non-API route | Web route | No rate limit headers |

---

## 429 Response Tests (FR)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| FR-001 | Correct status code | Rate limited | 429 Too Many Requests |
| FR-002 | JSON response body | Rate limited | JSON with error object |
| FR-003 | Error code in response | Rate limited | code="RATE_LIMIT_EXCEEDED" |
| FR-004 | Message in response | Rate limited | message describes limit |
| FR-005 | Retry-After in body | Rate limited | retry_after field |
| FR-006 | Retry-After header | Rate limited | Retry-After header |
| FR-007 | Consistent retry_after | Body and header | Same value |
| FR-008 | Response format matches spec | Rate limited | Matches SPEC-006 format |

### Expected 429 Response Format

```json
{
    "success": false,
    "error": {
        "code": "RATE_LIMIT_EXCEEDED",
        "message": "Rate limit exceeded. Retry after 30 seconds.",
        "retry_after": 30
    }
}
```

---

## Per-Key Configuration Tests (PK)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| PK-001 | Key with default limit | rate_limit=60 | 60 req/min |
| PK-002 | Key with custom limit | rate_limit=300 | 300 req/min |
| PK-003 | Key with zero limit | rate_limit=0 | All requests blocked |
| PK-004 | Key with high limit | rate_limit=10000 | 10000 req/min |
| PK-005 | Limit change mid-window | Update rate_limit | New limit for next request |
| PK-006 | Different keys different limits | key1=60, key2=300 | Independent |
| PK-007 | Limit from API key model | ApiKey->rate_limit | Used correctly |
| PK-008 | Limit null falls back to default | rate_limit=null | Default 60 |

---

## Storage Backend Tests (SB)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| SB-001 | In-memory cache storage | Default config | Uses cache |
| SB-002 | SQLite storage option | SQLite config | Uses database |
| SB-003 | Redis storage option | Redis config | Uses Redis |
| SB-004 | Cache key format | API key ID | Consistent key format |
| SB-005 | Storage cleanup | Old entries | Cleaned automatically |
| SB-006 | Persistent storage survives restart | SQLite/Redis | Data preserved |
| SB-007 | In-memory lost on restart | Array/Cache | Data lost (acceptable) |

---

## Concurrency Tests (CC)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CC-001 | Concurrent requests counted correctly | 10 parallel requests | All counted |
| CC-002 | No race condition on limit check | Concurrent at limit | Correct decision |
| CC-003 | Atomic increment | Concurrent increments | Accurate count |
| CC-004 | Lock-free implementation | High concurrency | No deadlocks |
| CC-005 | Burst requests | 100 instant requests | Correctly limited |
| CC-006 | Distributed locking (Redis) | Multiple workers | Consistent count |

---

## Edge Cases Tests (EC)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| EC-001 | Empty rate limit key | key="" | Graceful handling |
| EC-002 | Very long key name | 1000 char key | Handled correctly |
| EC-003 | Special characters in key | key="test@#$%" | Handled correctly |
| EC-004 | Unicode in key | key="test日本語" | Handled correctly |
| EC-005 | Negative windowMs | windowMs=-1000 | Error or default |
| EC-006 | Zero windowMs | windowMs=0 | Error or default |
| EC-007 | Negative maxRequests | maxRequests=-1 | Error or zero |
| EC-008 | Float windowMs | windowMs=500.5 | Handled |
| EC-009 | Very large maxRequests | maxRequests=PHP_INT_MAX | Handled |
| EC-010 | Key with null bytes | key with \0 | Handled safely |
| EC-011 | Rapid key creation/deletion | Many unique keys | No memory leak |
| EC-012 | Clock going backwards | System time change | Graceful handling |

---

## Security Tests (SC)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| SC-001 | Key enumeration prevention | Wrong keys | Same response time |
| SC-002 | No SQL injection via key | key="'; DROP TABLE" | Safe handling |
| SC-003 | Cache key collision prevention | Similar keys | Independent |
| SC-004 | DoS via many unique keys | 10000 unique keys | System stable |
| SC-005 | Header injection prevention | key with \r\n | Sanitized |
| SC-006 | Memory exhaustion prevention | Many active keys | Bounded memory |
| SC-007 | Rate limit bypass attempts | Various techniques | All blocked |
| SC-008 | Timing attack on limit check | Compare times | Consistent |

---

## Performance Tests (PF)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| PF-001 | Check overhead | isAllowed() | < 1ms |
| PF-002 | Increment overhead | increment() | < 1ms |
| PF-003 | Full middleware overhead | Request through middleware | < 5ms added |
| PF-004 | Memory per active key | 1000 active keys | Bounded growth |
| PF-005 | Cleanup performance | 10000 stale entries | < 100ms |
| PF-006 | Burst handling | 100 concurrent checks | All handled |
| PF-007 | Cache hit performance | Repeated same-key checks | Fast |

---

## Integration Tests (IT)

| ID | Scenario | Actions | Expected Output |
|----|----------|---------|-----------------|
| IT-001 | Full request cycle | Make request → check headers | Correct headers |
| IT-002 | Exhaust limit | 60 requests → 61st | 429 on 61st |
| IT-003 | Wait for window reset | Exhaust → wait 60s → request | Allowed again |
| IT-004 | Multiple API keys | Two keys alternating | Independent limits |
| IT-005 | Custom limit applied | Create key with limit=10 → test | Limited at 10 |
| IT-006 | End-to-end with auth | Authenticated API request | Rate limited correctly |
| IT-007 | Unauthenticated request | Request without API key | Passes through |
| IT-008 | Mixed authenticated/unauthenticated | Various requests | Correct handling |
| IT-009 | API key revoked mid-window | Revoke key → request | Auth fails first |
| IT-010 | Key deleted mid-window | Delete key → request | Auth fails first |

---

## Mock Data

### Sample Rate Limit Key Format

```php
// Key format: "rate_limit:{api_key_id}"
"rate_limit:123"
```

### Sample Stored Data Structure

```php
// For sliding window implementation
[
    'requests' => [
        ['timestamp' => 1708368300.123, 'count' => 1],
        ['timestamp' => 1708368300.456, 'count' => 1],
        // ... more requests
    ],
    'window_start' => 1708368290.000,
]
```

### Sample Headers

```http
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 45
X-RateLimit-Reset: 1708368360
```

### Sample 429 Response

```json
{
    "success": false,
    "error": {
        "code": "RATE_LIMIT_EXCEEDED",
        "message": "Rate limit exceeded. Retry after 30 seconds.",
        "retry_after": 30
    }
}
```

---

## Coverage Targets

| Category | Target |
|----------|--------|
| Rate Limit Check Tests | 100% |
| Sliding Window Tests | 100% |
| Remaining Requests Tests | 100% |
| Reset Time Tests | 100% |
| Request Count Tests | 100% |
| Increment Tests | 100% |
| Reset Tests | 100% |
| Middleware Integration Tests | 100% |
| Header Tests | 100% |
| 429 Response Tests | 100% |
| Per-Key Configuration Tests | 100% |
| Storage Backend Tests | 100% |
| Concurrency Tests | 100% |
| Edge Cases | 100% |
| Security | 100% |
| Performance | Verified |
| Integration Tests | 100% |

---

## Test Execution

```bash
# Run all rate limiter tests
php artisan test --filter=RateLimiter

# Run specific test files
php artisan test --filter=RateLimiterTest
php artisan test --filter=RateLimiterServiceTest
php artisan test --filter=RateLimiterIntegrationTest
php artisan test --filter=RateLimitHeadersTest

# Run with coverage
php artisan test --filter=RateLimiter --coverage

# Run specific test
php artisan test --filter=test_request_allowed_within_limit

# Run security tests only
php artisan test --filter=RateLimiter --group=security
```

---

## Dependencies

- TASK-6-1 (Database schema - api_keys table) - Required for rate_limit field
- TASK-6-2 (API Key model and repository) - Required for accessing rate_limit per key
- TASK-6-3 (API Key generation and validation) - Required for auth middleware integration

---

## Notes for Developer

1. **Sliding Window Algorithm:** Implement a sliding window rather than fixed window to prevent burst traffic at window boundaries. Track individual request timestamps within the window.

2. **Storage Choice:** 
   - For MVP: Use Laravel's cache (file/database driver)
   - For production: Consider Redis for better performance and atomic operations
   - SQLite is acceptable for low-traffic scenarios

3. **Per-Key Limits:** The rate limit should be read from the API key's `rate_limit` field. Default to 60 if not set.

4. **Key Format:** Use a consistent key format: `rate_limit:{api_key_id}` or similar for cache/storage.

5. **Atomic Operations:** Ensure increment/check operations are atomic to prevent race conditions in concurrent scenarios.

6. **Header Values:**
   - `X-RateLimit-Limit`: Maximum requests per window
   - `X-RateLimit-Remaining`: Requests remaining in current window
   - `X-RateLimit-Reset`: Unix timestamp when window resets

7. **429 Response:** Must match the exact format specified in SPEC-006. Include both the JSON body `retry_after` field and the HTTP `Retry-After` header.

8. **Middleware Order:** The rate limiter should run AFTER authentication middleware so it has access to the validated API key.

9. **Graceful Degradation:** If the rate limit storage is unavailable, consider whether to:
   - Allow all requests (fail open)
   - Block all requests (fail closed)
   - Use a fallback mechanism
   Document the chosen approach.

10. **Testing Concurrency:** Use Laravel's parallel testing or write specific concurrent tests to verify race condition handling.

11. **Window Size:** Default window is 60 seconds (1 minute). This should be configurable.

12. **Cleanup:** Old rate limit entries should be cleaned up automatically. Consider:
    - TTL on cache entries
    - Scheduled cleanup job for database storage
    - Lazy cleanup on access

13. **Security Considerations:**
    - Don't expose internal key format in errors
    - Prevent timing attacks on limit checks
    - Handle malicious key formats safely

---

## Comments

### [2026-02-27 14:20] PreTester: Test Documentation Created
- Created TESTS/TASK-6-5.md with comprehensive test scenarios
- 120+ test scenarios covering service, middleware, headers, responses, and security
- Focus on sliding window algorithm for accurate rate limiting
- Includes per-key configuration tests for custom limits
- Following project test documentation format
- Status: Tests Coverage In Progress
