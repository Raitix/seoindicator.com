# TASK-31-9: Retry Policy Implementation

## STATUS
`To Prioritize`

## PRIORITY
`Medium`

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
        
        // Add jitter (±10%)
        $jitter = $delay * self::JITTER_PERCENT * (mt_rand(0, 100) / 100 - 0.5) * 2;
        $delay = (int) ($delay + $jitter);
        
        return $delay;
    }
}
```

## Retry Flow Diagram

```
Attempt 1 ──► Failure
    │
    ├─► Non-retryable (4xx) ──► Throw immediately
    │
    └─► Retryable (5xx, 429, timeout)
            │
            └─► Wait 1s ± jitter ──► Attempt 2 ──► Failure
                                            │
                                            └─► Wait 2s ± jitter ──► Attempt 3 ──► Failure
                                                                    │
                                                                    └─► Wait 4s ± jitter ──► Attempt 4
                                                                                            │
                                                                                            └─► Max retries ──► Throw
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
