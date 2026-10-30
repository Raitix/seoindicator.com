<?php

namespace App\Services\AI;

use App\Exceptions\AI\RateLimitException;
use App\Exceptions\AI\TimeoutException;
use App\Exceptions\AI\ServiceUnavailableException;
use App\Services\AI\DTOs\RetryDecision;
use App\Services\AI\DTOs\RetryStrategy;
use Exception;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Retry Policy Service for AI API requests.
 * 
 * Implements intelligent retry logic with exponential backoff, jitter,
 * and retry decisions based on error type.
 * 
 * @see TASK-31-9
 * @see SPEC-031
 */
class RetryPolicy
{
    /**
     * Maximum number of retry attempts.
     */
    private const MAX_RETRIES = 3;

    /**
     * Base delay in milliseconds for exponential backoff.
     */
    private const BASE_DELAY_MS = 1000;

    /**
     * Maximum delay in milliseconds (cap for exponential backoff).
     */
    private const MAX_DELAY_MS = 10000;

    /**
     * Jitter percentage to randomize delays.
     */
    private const JITTER_PERCENT = 0.1;

    /**
     * Maximum acceptable wait time for rate limits (60 seconds).
     */
    private const MAX_RATE_LIMIT_WAIT_SECONDS = 60;

    /**
     * Token threshold for content-aware truncation strategy.
     */
    private const LARGE_CONTENT_THRESHOLD = 50000;

    /**
     * Truncation ratio for large content (70% = 30% reduction).
     */
    private const TRUNCATION_RATIO = 0.7;

    /**
     * HTTP status codes that are retryable.
     */
    private const RETRYABLE_CODES = [
        429, // Rate limited
        500, // Internal server error
        502, // Bad gateway
        503, // Service unavailable
        504, // Gateway timeout
    ];

    /**
     * HTTP status codes that are NOT retryable (client errors).
     */
    private const NON_RETRYABLE_CODES = [
        400, // Bad request
        401, // Unauthorized
        403, // Forbidden
        404, // Not found
        422, // Validation error
    ];

    /**
     * Context Window Manager for token estimation.
     */
    private ?ContextWindowManager $contextManager;

    /**
     * Create a new retry policy instance.
     * 
     * @param ContextWindowManager|null $contextManager Optional context manager for token estimation
     */
    public function __construct(?ContextWindowManager $contextManager = null)
    {
        $this->contextManager = $contextManager ?? new ContextWindowManager();
    }

    /**
     * Determine if a request should be retried.
     * 
     * @param Exception|null $exception The exception that occurred
     * @param int $attempt The current attempt number (1-indexed)
     * @return bool True if should retry, false otherwise
     */
    public function shouldRetry(?Exception $exception, int $attempt): bool
    {
        // Handle null exception gracefully
        if ($exception === null) {
            return false;
        }

        // Check if max retries exceeded
        if ($attempt >= self::MAX_RETRIES) {
            return false;
        }

        // Log retry attempt with context
        $this->logRetryAttempt($exception, $attempt);

        return $this->isRetryableError($exception);
    }

    /**
     * Calculate delay in milliseconds before next retry.
     * 
     * Uses exponential backoff with jitter to prevent thundering herd.
     * 
     * @param int $attempt The current attempt number (1-indexed)
     * @return int Delay in milliseconds
     */
    public function getDelayMs(int $attempt): int
    {
        // Normalize negative attempts to 0 (treated as first attempt)
        if ($attempt < 0) {
            $attempt = 0;
        }

        // Treat attempt 0 as attempt 1 (first retry)
        if ($attempt === 0) {
            $attempt = 1;
        }

        // Calculate exponential backoff: base * 2^(attempt-1)
        $delay = self::BASE_DELAY_MS * pow(2, $attempt - 1);

        // Cap at maximum delay
        $delay = min($delay, self::MAX_DELAY_MS);

        // Apply jitter (±10% randomization)
        $jitterRange = $delay * self::JITTER_PERCENT;
        $jitter = (mt_rand(0, 100) / 100 - 0.5) * 2 * $jitterRange;
        $delay = (int) ($delay + $jitter);

        // Ensure non-negative
        return max(0, $delay);
    }

    /**
     * Get the maximum number of retries allowed.
     * 
     * @return int Maximum retry count
     */
    public function getMaxRetries(): int
    {
        return self::MAX_RETRIES;
    }

    /**
     * Check if an exception represents a retryable error.
     * 
     * @param Exception $exception The exception to check
     * @return bool True if retryable, false otherwise
     */
    public function isRetryableError(Exception $exception): bool
    {
        // Check for specific AI exception types
        if ($exception instanceof RateLimitException) {
            return true;
        }

        if ($exception instanceof TimeoutException) {
            return true;
        }

        if ($exception instanceof ServiceUnavailableException) {
            return true;
        }

        if ($exception instanceof ConnectionException) {
            return true;
        }

        // Check HTTP status codes
        if ($exception instanceof HttpException) {
            $statusCode = $exception->getStatusCode();
            return in_array($statusCode, self::RETRYABLE_CODES, true);
        }

        // Generic exceptions are not retryable
        return false;
    }

    /**
     * Handle rate limit exceptions with Retry-After header support.
     * 
     * @param RateLimitException $exception The rate limit exception
     * @return RetryDecision The retry decision
     */
    public function handleRateLimit(RateLimitException $exception): RetryDecision
    {
        $retryAfter = $exception->getRetryAfter();

        // If no retry-after header, use default exponential backoff
        if ($retryAfter === null) {
            return RetryDecision::retry(
                delayMs: $this->getDelayMs(1),
                reason: 'Rate limit - using default backoff'
            );
        }

        // Handle invalid/negative retry-after
        if ($retryAfter < 0) {
            return RetryDecision::retry(
                delayMs: $this->getDelayMs(1),
                reason: 'Rate limit with invalid retry-after - using default backoff'
            );
        }

        // Check if wait time exceeds acceptable limit
        if ($retryAfter > self::MAX_RATE_LIMIT_WAIT_SECONDS) {
            return RetryDecision::fail(
                reason: "Rate limit exceeds acceptable wait time ({$retryAfter}s > 60s)"
            );
        }

        // Use the retry-after value (convert to milliseconds)
        return RetryDecision::retry(
            delayMs: $retryAfter * 1000,
            reason: "Rate limit - respecting retry-after header ({$retryAfter}s)"
        );
    }

    /**
     * Get retry strategy for content-aware retry.
     * 
     * For large content on retry attempts, suggests truncation strategy.
     * 
     * @param string $content The content being sent
     * @param int $attempt The current attempt number
     * @return RetryStrategy The retry strategy
     */
    public function getRetryStrategy(string $content, int $attempt): RetryStrategy
    {
        // Empty content always uses standard strategy
        if ($content === '') {
            return RetryStrategy::standard();
        }

        // Estimate tokens for content
        $tokens = $this->contextManager->estimateTokens($content);

        // First attempt always uses standard strategy
        if ($attempt <= 1) {
            return RetryStrategy::standard();
        }

        // Large content on retry uses truncation strategy
        if ($tokens >= self::LARGE_CONTENT_THRESHOLD) {
            $targetTokens = (int) ($tokens * self::TRUNCATION_RATIO);
            return RetryStrategy::withTruncation($targetTokens);
        }

        // Default to standard strategy
        return RetryStrategy::standard();
    }

    /**
     * Log retry attempt with context.
     * 
     * @param Exception $exception The exception that occurred
     * @param int $attempt The current attempt number
     */
    protected function logRetryAttempt(Exception $exception, int $attempt): void
    {
        $context = [
            'attempt' => $attempt,
            'exception_class' => get_class($exception),
            'message' => $exception->getMessage(),
            'is_retryable' => $this->isRetryableError($exception),
        ];

        if ($exception instanceof HttpException) {
            $context['status_code'] = $exception->getStatusCode();
        }

        if ($exception instanceof RateLimitException) {
            $context['retry_after'] = $exception->getRetryAfter();
        }

        Log::info('AI request retry attempt', $context);
    }
}
