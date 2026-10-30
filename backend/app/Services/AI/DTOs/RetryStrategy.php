<?php

namespace App\Services\AI\DTOs;

/**
 * Data Transfer Object for retry strategies.
 * 
 * Defines the strategy to use when retrying a failed AI request,
 * including optional truncation for large content.
 */
class RetryStrategy
{
    /**
     * Create a new retry strategy.
     * 
     * @param string $type Strategy type: 'standard' or 'truncation'
     * @param int|null $targetTokens Target token count for truncation strategy
     */
    public function __construct(
        public readonly string $type = 'standard',
        public readonly ?int $targetTokens = null,
    ) {}

    /**
     * Create a standard retry strategy.
     * 
     * @return self
     */
    public static function standard(): self
    {
        return new self(
            type: 'standard',
            targetTokens: null,
        );
    }

    /**
     * Create a truncation strategy for large content.
     * 
     * @param int $targetTokens Target token count after truncation
     * @return self
     */
    public static function withTruncation(int $targetTokens): self
    {
        return new self(
            type: 'truncation',
            targetTokens: $targetTokens,
        );
    }
}
