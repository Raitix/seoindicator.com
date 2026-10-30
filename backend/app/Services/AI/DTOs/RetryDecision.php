<?php

namespace App\Services\AI\DTOs;

/**
 * Data Transfer Object for retry decisions.
 * 
 * Encapsulates the decision of whether to retry a failed request,
 * along with the delay and reason for the decision.
 */
class RetryDecision
{
    /**
     * Create a new retry decision.
     * 
     * @param bool $shouldRetry Whether the request should be retried
     * @param int $delayMs Delay in milliseconds before the next retry
     * @param string|null $reason Optional reason for the decision
     */
    public function __construct(
        public readonly bool $shouldRetry,
        public readonly int $delayMs = 0,
        public readonly ?string $reason = null,
    ) {}

    /**
     * Create a decision to retry.
     * 
     * @param int $delayMs Delay in milliseconds before retry
     * @param string|null $reason Optional reason for retry
     * @return self
     */
    public static function retry(int $delayMs, ?string $reason = null): self
    {
        return new self(
            shouldRetry: true,
            delayMs: $delayMs,
            reason: $reason,
        );
    }

    /**
     * Create a decision to not retry.
     * 
     * @param string|null $reason Optional reason for not retrying
     * @return self
     */
    public static function fail(?string $reason = null): self
    {
        return new self(
            shouldRetry: false,
            delayMs: 0,
            reason: $reason,
        );
    }
}
