# TEST-31-9: Retry Policy Implementation

## Task Reference
- **Task:** [TASK-31-9](../TASKS/TASK-31-9.md)
- **Specification:** SPEC-31: Unified AI Prompt + Context Management
- **Feature:** FEAT-031

## Overview
Comprehensive unit tests for the `RetryPolicy` service that handles retry logic for AI API requests. Tests cover exponential backoff with jitter, retryable vs non-retryable error classification, rate limit handling with Retry-After header, and content-aware retry strategies.

## Test Files

| File | Tests | Status |
|------|-------|--------|
| `backend/tests/Unit/Services/AI/RetryPolicyTest.php` | 85+ | TDD (service not implemented) |

## Test Scenarios

### Service Instantiation (SI)
| ID | Scenario | Expected Output |
|----|----------|-----------------|
| SI-01 | Service can be instantiated | Instance of RetryPolicy |
| SI-02 | has shouldRetry method | true |
| SI-03 | has getDelayMs method | true |
| SI-04 | has getMaxRetries method | true |
| SI-05 | has isRetryableError method | true |
| SI-06 | has handleRateLimit method | true |
| SI-07 | has getRetryStrategy method | true |

### shouldRetry() Tests (SR)
| ID | Scenario | Exception | Attempt | Expected |
|----|----------|-----------|---------|----------|
| SR-01 | Rate limit 429 | RateLimitException | 1 | true |
| SR-02 | Server error 500 | HttpException(500) | 1 | true |
| SR-03 | Bad gateway 502 | HttpException(502) | 1 | true |
| SR-04 | Service unavailable 503 | HttpException(503) | 1 | true |
| SR-05 | Gateway timeout 504 | HttpException(504) | 1 | true |
| SR-06 | Timeout | TimeoutException | 1 | true |
| SR-07 | Service unavailable | ServiceUnavailableException | 1 | true |
| SR-08 | Connection failed | ConnectionException | 1 | true |
| SR-09 | Bad request 400 | HttpException(400) | 1 | false |
| SR-10 | Unauthorized 401 | HttpException(401) | 1 | false |
| SR-11 | Forbidden 403 | HttpException(403) | 1 | false |
| SR-12 | Not found 404 | HttpException(404) | 1 | false |
| SR-13 | Validation 422 | HttpException(422) | 1 | false |
| SR-14 | Generic exception | Exception | 1 | false |
| SR-15 | Max retries exceeded | HttpException(500) | 4 | false |
| SR-16 | At max retry boundary | HttpException(500) | 3 | false |
| SR-17 | Attempt 1 for retryable | HttpException(500) | 1 | true |
| SR-18 | Attempt 2 for retryable | HttpException(500) | 2 | true |

### getDelayMs() - Exponential Backoff (GD)
| ID | Scenario | Attempt | Expected Range |
|----|----------|---------|----------------|
| GD-01 | First retry | 1 | 900-1100ms (1000 ± 10%) |
| GD-02 | Second retry | 2 | 1800-2200ms (2000 ± 10%) |
| GD-03 | Third retry | 3 | 3600-4400ms (4000 ± 10%) |
| GD-04 | Fifth retry (capped) | 5 | 9000-11000ms (10000 max ± 10%) |
| GD-05 | High attempt (capped) | 10 | 9000-11000ms |
| GD-06 | Jitter variation | 1 (10x) | Multiple unique values |
| GD-07 | Zero attempt | 0 | 900-1100ms |
| GD-08 | Negative attempt | -1 | >= 0 |

### getMaxRetries() Tests (MR)
| ID | Scenario | Expected |
|----|----------|----------|
| MR-01 | Default max retries | 3 |
| MR-02 | Returns integer | is_int |
| MR-03 | Positive value | > 0 |

### isRetryableError() Tests (IR)
| ID | Scenario | Exception | Expected |
|----|----------|-----------|----------|
| IR-01 | RateLimitException | RateLimitException | true |
| IR-02 | TimeoutException | TimeoutException | true |
| IR-03 | ServiceUnavailableException | ServiceUnavailableException | true |
| IR-04 | ConnectionException | ConnectionException | true |
| IR-05 | HTTP 500 | HttpException(500) | true |
| IR-06 | HTTP 502 | HttpException(502) | true |
| IR-07 | HTTP 503 | HttpException(503) | true |
| IR-08 | HTTP 504 | HttpException(504) | true |
| IR-09 | HTTP 429 | HttpException(429) | true |
| IR-10 | HTTP 400 | HttpException(400) | false |
| IR-11 | HTTP 401 | HttpException(401) | false |
| IR-12 | HTTP 403 | HttpException(403) | false |
| IR-13 | HTTP 404 | HttpException(404) | false |
| IR-14 | HTTP 422 | HttpException(422) | false |
| IR-15 | Generic exception | Exception | false |

### handleRateLimit() Tests (HR)
| ID | Scenario | Retry-After | Expected |
|----|----------|-------------|----------|
| HR-01 | Valid header | 30s | shouldRetry=true, delayMs=30000 |
| HR-02 | Long wait (>60s) | 120s | shouldRetry=false |
| HR-03 | Boundary (60s) | 60s | shouldRetry=true, delayMs=60000 |
| HR-04 | No header | null | Default exponential backoff |
| HR-05 | Zero value | 0 | shouldRetry=true |

### getRetryStrategy() Tests (GR)
| ID | Scenario | Content Size | Attempt | Expected |
|----|----------|--------------|---------|----------|
| GR-01 | Small content | <50K tokens | 1 | standard |
| GR-02 | Medium content | ~2.5K tokens | 1 | standard |
| GR-03 | Large content retry | >50K tokens | 2 | truncation |
| GR-04 | Large first attempt | >50K tokens | 1 | standard |
| GR-05 | Truncation reduction | >50K tokens | 2 | targetTokens = 70% |

### Integration Tests (IT)
| ID | Scenario | Description |
|----|----------|-------------|
| IT-01 | Full retry cycle 500 | Attempts 1,2,3 with increasing delays |
| IT-02 | No retry client error | 400 never retries |
| IT-03 | Rate limit backoff | RateLimitException with backoff |
| IT-04 | Connection failure | ConnectionException is retryable |

### Edge Cases (EC)
| ID | Scenario | Expected |
|----|----------|----------|
| EC-01 | Null exception | false |
| EC-02 | Empty attempt | true (treats as 0) |
| EC-03 | Very large attempt | false |
| EC-04 | Unknown HTTP status (418) | false |
| EC-05 | Multiple rate limits | Correct delays per exception |
| EC-06 | Empty content strategy | standard |
| EC-07 | Unicode content strategy | Handles correctly |

### Security Tests (SC)
| ID | Scenario | Expected |
|----|----------|----------|
| SC-01 | Sensitive data in exception | Returns boolean only |
| SC-02 | Malformed Retry-After header | Uses default |
| SC-03 | Negative Retry-After | Treated as zero/default |

### Performance Tests (PF)
| ID | Scenario | Threshold |
|----|----------|-----------|
| PF-01 | shouldRetry 1000x | < 100ms |
| PF-02 | getDelayMs 1000x | < 100ms |
| PF-03 | handleRateLimit 1000x | < 100ms |

## Mock Data

### Retryable HTTP Status Codes
```php
private const RETRYABLE_CODES = [
    429, // Rate limited
    500, // Internal server error
    502, // Bad gateway
    503, // Service unavailable
    504, // Gateway timeout
];
```

### Non-Retryable HTTP Status Codes
```php
private const NON_RETRYABLE_CODES = [
    400, // Bad request
    401, // Unauthorized
    403, // Forbidden
    404, // Not found
    422, // Validation error
];
```

### Retry Configuration
```php
private const MAX_RETRIES = 3;
private const BASE_DELAY_MS = 1000;
private const MAX_DELAY_MS = 10000;
private const JITTER_PERCENT = 0.1; // 10% jitter
```

### RetryDecision DTO
```php
class RetryDecision
{
    public bool $shouldRetry;
    public int $delayMs;
    public ?string $reason;
}
```

### RetryStrategy DTO
```php
class RetryStrategy
{
    public string $type; // 'standard' or 'truncation'
    public ?int $targetTokens; // For truncation strategy
}
```

## Coverage Targets

| Method | Target Coverage |
|--------|-----------------|
| shouldRetry() | 100% |
| getDelayMs() | 100% |
| getMaxRetries() | 100% |
| isRetryableError() | 100% |
| handleRateLimit() | 100% |
| getRetryStrategy() | 100% |

## Test Execution

```bash
# Run all RetryPolicy tests
php artisan test --filter=RetryPolicyTest

# Run specific test category
php artisan test --filter="test_sr_01"
php artisan test --filter="test_gd_01"
php artisan test --filter="test_ir_01"

# Run with coverage
php artisan test --filter=RetryPolicyTest --coverage
```

## Dependencies

| Dependency | Status | Usage |
|------------|--------|-------|
| TASK-31-2 (ContextWindowManager) | Done | Token estimation for content-aware retry |

## Exception Classes Required

The following exception classes should be created or exist:

1. `App\Exceptions\AI\RateLimitException` - HTTP 429 rate limit errors
2. `App\Exceptions\AI\TimeoutException` - Request timeout errors
3. `App\Exceptions\AI\ServiceUnavailableException` - Service unavailable errors

## Notes for Developer

### Implementation Priority
1. Create exception classes (RateLimitException, TimeoutException, ServiceUnavailableException)
2. Implement RetryPolicy service with all methods
3. Create RetryDecision and RetryStrategy DTOs
4. Integrate with AIAnalysisService

### Key Design Decisions
- **Max Retries:** 3 (configurable via constant)
- **Base Delay:** 1000ms (exponential: 1s → 2s → 4s)
- **Max Delay:** 10000ms (capped at 10 seconds)
- **Jitter:** ±10% randomization to prevent thundering herd
- **Retry-After Limit:** Max 60 seconds wait for rate limits

### Retry Flow
```
Attempt 1 → Failure → Wait 1s ± jitter → Attempt 2 → Failure → Wait 2s ± jitter → Attempt 3 → Failure → Wait 4s ± jitter → Give up
```

### Content-Aware Retry
For large content (>50K tokens) on retry:
1. First attempt: Standard retry
2. Second+ attempt: Apply 30% truncation

## Comments

### [2026-03-05 19:30 GMT+2] PreTester
**Test Design Complete**

**Tests Created:** 85+ unit tests in `backend/tests/Unit/Services/AI/RetryPolicyTest.php`

**Test Categories:**
| Category | Tests | Description |
|----------|-------|-------------|
| Service Instantiation | 7 | Method existence, instantiation |
| shouldRetry() | 18 | All retryable/non-retryable scenarios |
| getDelayMs() | 8 | Exponential backoff with jitter |
| getMaxRetries() | 3 | Max retry configuration |
| isRetryableError() | 15 | Error classification |
| handleRateLimit() | 5 | Rate limit with Retry-After |
| getRetryStrategy() | 5 | Content-aware retry |
| Integration | 4 | Full retry flows |
| Edge Cases | 7 | Null, empty, unicode, boundary |
| Security | 3 | Input validation, malformed data |
| Performance | 3 | Speed benchmarks |
| Acceptance Criteria | 6 | All ACs verified |

**Key Implementation Notes for Developer:**
1. **Exception Classes Needed:** RateLimitException, TimeoutException, ServiceUnavailableException
2. **DTOs Needed:** RetryDecision, RetryStrategy
3. **Retryable Codes:** 429, 500, 502, 503, 504 + timeout/connection
4. **Non-Retryable Codes:** 400, 401, 403, 404, 422
5. **Backoff Formula:** `min(BASE_DELAY * 2^(attempt-1), MAX_DELAY) ± jitter`
6. **Rate Limit Handling:** Respect Retry-After up to 60s max wait
7. **Content-Aware Retry:** Truncate 30% for >50K tokens on retry

**TDD Pattern:** Tests will fail until service is implemented

**Status:** `Tests Coverage In Progress` → Moving to `To Develop`
**Next Agent:** Developer for implementation
