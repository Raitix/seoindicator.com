# TASK-31-9: Retry Policy Implementation

## STATUS
`To Test Locally`

## PRIORITY
`High`

## RISK
`Low`

## Specification
SPEC-31: Unified AI Prompt + Context Management

## Description
Implement a comprehensive retry policy for AI requests with exponential backoff, jitter, and intelligent retry decisions based on error type.

## Component Location
- **Service:** `backend/app/Services/AI/RetryPolicy.php`
- **Tests:** `backend/tests/Unit/Services/AI/RetryPolicyTest.php`

## Core Functionalities

| Method | Description |
|--------|-------------|
| `shouldRetry(Exception $e, int $attempt): bool` | Determine if request should be retried |
| `getDelayMs(int $attempt): int` | Calculate delay before next retry |
| `getMaxRetries(): int` | Return maximum retry count |
| `isRetryableError(Exception $e): bool` | Check if error is retryable |

## Retry Configuration

```php
class RetryPolicy
{
    private const MAX_RETRIES = 3;
    private const BASE_DELAY_MS = 1000;
    private const MAX_DELAY_MS = 10000;
    private const JITTER_PERCENT = 0.1; // 10% jitter
    
    // Retryable HTTP status codes
    private const RETRYABLE_CODES = [
        429, // Rate limited
        500, // Internal server error
        502, // Bad gateway
        503, // Service unavailable
        504, // Gateway timeout
    ];
    
    // Non-retryable errors (client errors, validation failures)
    private const NON_RETRYABLE_CODES = [
        400, // Bad request
        401, // Unauthorized
        403, // Forbidden
        404, // Not found
        422, // Validation error
    ];
    
    public function shouldRetry(Exception $e, int $attempt): bool
    {
        // Check max retries
        if ($attempt >= self::MAX_RETRIES) {
            return false;
        }
        
        // Check error type
        return match (true) {
            $e instanceof RateLimitException => true,
            $e instanceof TimeoutException => true,
            $e instanceof ServiceUnavailableException => true,
            $e instanceof ConnectionException => true,
            $e instanceof HttpException && in_array($e->getStatusCode(), self::RETRYABLE_CODES) => true,
            default => false,
        };
    }
    
    public function getDelayMs(int $attempt): int
    {
        // Exponential backoff: 1s, 2s, 4s
        $delay = self::BASE_DELAY_MS * pow(2, $attempt - 1);
        
        // Cap at max delay
        $delay = min($delay, self::MAX_DELAY_MS);
        
        // Add jitter (Â±10%)
        $jitter = $delay * self::JITTER_PERCENT * (mt_rand(0, 100) / 100 - 0.5) * 2;
        $delay = (int) ($delay + $jitter);
        
        return $delay;
    }
}
```

## Retry Flow Diagram

```
Attempt 1 â”€â”€â–º Failure
    â”‚
    â”œâ”€â–º Non-retryable (4xx) â”€â”€â–º Throw immediately
    â”‚
    â””â”€â–º Retryable (5xx, 429, timeout)
            â”‚
            â””â”€â–º Wait 1s Â± jitter â”€â”€â–º Attempt 2 â”€â”€â–º Failure
                                            â”‚
                                            â””â”€â–º Wait 2s Â± jitter â”€â”€â–º Attempt 3 â”€â”€â–º Failure
                                                                    â”‚
                                                                    â””â”€â–º Wait 4s Â± jitter â”€â”€â–º Attempt 4
                                                                                            â”‚
                                                                                            â””â”€â–º Max retries â”€â”€â–º Throw
```

## Rate Limit Handling

```php
public function handleRateLimit(RateLimitException $e): RetryDecision
{
    $retryAfter = $e->getRetryAfter();
    
    if ($retryAfter > 60) {
        // Don't wait more than 60 seconds
        return RetryDecision::fail('Rate limit exceeds acceptable wait time');
    }
    
    return RetryDecision::retry($retryAfter * 1000);
}
```

## Content-Aware Retry

```php
public function getRetryStrategy(string $content, int $attempt): RetryStrategy
{
    $tokens = $this->contextManager->estimateTokens($content);
    
    // For large content, try truncation on retry
    if ($tokens > 50000 && $attempt > 1) {
        return RetryStrategy::withTruncation(
            targetTokens: (int) ($tokens * 0.7) // 30% reduction
        );
    }
    
    return RetryStrategy::standard();
}
```

## Acceptance Criteria
- [ ] `shouldRetry()` correctly identifies retryable errors
- [ ] Exponential backoff with jitter
- [ ] Max 3 retries by default
- [ ] Rate limit respects `Retry-After` header
- [ ] Non-retryable errors fail fast
- [ ] Logs retry attempts with context

## Dependencies
- TASK-31-2 (ContextWindowManager for content-aware retry)

## Effort Estimate
2 hours

## Comments
_Agent log_

### [2026-03-05 19:15 GMT+2] ProjectManager
**Prioritization Complete**

**Priority Upgrade:** Medium â†’ HIGH

**Rationale:**
1. **Critical for AI Reliability:** Retry policy is essential for production AI operations - without it, transient errors (rate limits, timeouts, 5xx errors) would cause complete analysis failures
2. **Low Risk, High Impact:** Standard Laravel service (no external APIs), but directly affects user-facing reliability
3. **Dependency Satisfied:** TASK-31-2 (ContextWindowManager) is Done âœ… - content-aware retry can use token estimation
4. **Phase Context:** Phase 2 AI Integration - AIAnalysisService (TASK-31-7) is Done, OpenRouter client (TASK-31-6) is Done - both need retry logic

**Dependency Analysis:**
- **TASK-31-2 (ContextWindowManager):** Done âœ… - provides token estimation for content-aware retry
- **No other dependencies** - Infrastructure service with clear interface

**TDD Approach:**
PreTester can design tests immediately using the defined interface:
- `shouldRetry(Exception $e, int $attempt): bool` - Identify retryable errors
- `getDelayMs(int $attempt): int` - Exponential backoff with jitter
- `getMaxRetries(): int` - Return max retry count (default 3)
- `isRetryableError(Exception $e): bool` - Check if error is retryable

**Key Test Scenarios:**
1. Retryable errors (429, 500, 502, 503, 504, timeout) â†’ shouldRetry = true
2. Non-retryable errors (400, 401, 403, 404, 422) â†’ shouldRetry = false
3. Max retries exceeded â†’ shouldRetry = false
4. Exponential backoff: 1s â†’ 2s â†’ 4s with jitter
5. Rate limit handling: respect Retry-After header
6. Content-aware retry: truncation for large content

**Risk Assessment:** LOW (confirmed)
- Standard Laravel service implementation
- Pure business logic, no external APIs
- Well-defined error categories and retry strategies

**Status:** `To Write Tests` - Ready for PreTester
**Next Agent:** PreTester for test design

### [2026-03-05 19:30 GMT+2] PreTester
**Test Design Complete**

**Tests Created:** 85+ unit tests in `backend/tests/Unit/Services/AI/RetryPolicyTest.php`

**Test Categories:**
| Category | Tests | Description |
|----------|-------|-------------|
| Service Instantiation (SI) | 7 | Method existence, instantiation |
| shouldRetry() (SR) | 18 | All retryable/non-retryable scenarios |
| getDelayMs() (GD) | 8 | Exponential backoff with jitter |
| getMaxRetries() (MR) | 3 | Max retry configuration |
| isRetryableError() (IR) | 15 | Error classification |
| handleRateLimit() (HR) | 5 | Rate limit with Retry-After header |
| getRetryStrategy() (GR) | 5 | Content-aware retry strategy |
| Integration (IT) | 4 | Full retry flows |
| Edge Cases (EC) | 7 | Null, empty, unicode, boundary |
| Security (SC) | 3 | Input validation, malformed data |
| Performance (PF) | 3 | Speed benchmarks (<100ms for 1000 calls) |
| Acceptance Criteria (AC) | 6 | All 6 ACs verified |

**Key Implementation Notes for Developer:**
1. **Exception Classes Needed:**
   - `App\Exceptions\AI\RateLimitException` - HTTP 429 rate limit errors
   - `App\Exceptions\AI\TimeoutException` - Request timeout errors
   - `App\Exceptions\AI\ServiceUnavailableException` - Service unavailable errors

2. **DTOs Needed:**
   - `RetryDecision` - { shouldRetry: bool, delayMs: int, reason: ?string }
   - `RetryStrategy` - { type: string, targetTokens: ?int }

3. **Retry Configuration:**
   - MAX_RETRIES = 3
   - BASE_DELAY_MS = 1000
   - MAX_DELAY_MS = 10000
   - JITTER_PERCENT = 0.1 (10%)

4. **Retryable HTTP Codes:** 429, 500, 502, 503, 504
5. **Non-Retryable HTTP Codes:** 400, 401, 403, 404, 422
6. **Backoff Formula:** `min(BASE_DELAY * 2^(attempt-1), MAX_DELAY) Â± jitter`
7. **Rate Limit Handling:** Respect Retry-After header up to 60s max wait
8. **Content-Aware Retry:** Truncate 30% for content >50K tokens on retry (attempt 2+)

**TDD Pattern:** Tests will fail until service is implemented

**Documentation:** `TESTS/TEST-31-9.md`

**Status:** `To Develop` - Ready for Developer
**Next Agent:** Developer for implementation

### [2026-03-05 19:45 GMT+2] Developer
**Implementation Complete**

**Files Created:**
| File | Description |
|------|-------------|
| `backend/app/Exceptions/AI/RateLimitException.php` | HTTP 429 rate limit exception with Retry-After header support |
| `backend/app/Exceptions/AI/TimeoutException.php` | Request timeout exception |
| `backend/app/Exceptions/AI/ServiceUnavailableException.php` | Service unavailable exception |
| `backend/app/Services/AI/RetryPolicy.php` | Main retry policy service with all methods |
| `backend/app/Services/AI/DTOs/RetryDecision.php` | DTO for retry decisions |
| `backend/app/Services/AI/DTOs/RetryStrategy.php` | DTO for retry strategies |

**Test Results:** 84/84 tests passing (148 assertions)

**Implementation Highlights:**
1. **Exponential Backoff:** `BASE_DELAY * 2^(attempt-1)` with 10% jitter
2. **Max Delay Cap:** 10 seconds max wait between retries
3. **Rate Limit Handling:** Respects Retry-After header up to 60 seconds max
4. **Content-Aware Retry:** Uses ContextWindowManager for token estimation, triggers truncation at 50K+ tokens on retry (attempt 2+)
5. **Retryable Errors:** 429, 500, 502, 503, 504 + TimeoutException, ServiceUnavailableException, ConnectionException
6. **Non-Retryable Errors:** 400, 401, 403, 404, 422 (client errors fail fast)
7. **Logging:** All retry attempts logged with context (attempt, exception class, is_retryable)

**Configuration Constants:**
- `MAX_RETRIES = 3`
- `BASE_DELAY_MS = 1000` (1 second)
- `MAX_DELAY_MS = 10000` (10 seconds)
- `JITTER_PERCENT = 0.1` (10%)
- `MAX_RATE_LIMIT_WAIT_SECONDS = 60`
- `LARGE_CONTENT_THRESHOLD = 50000` tokens
- `TRUNCATION_RATIO = 0.7` (70% = 30% reduction)

**Performance:** All performance tests pass (<100ms for 1000 calls)

**Status:** `Ready for Local Testing` - Ready for LocalTester
**Next Agent:** LocalTester for validation
