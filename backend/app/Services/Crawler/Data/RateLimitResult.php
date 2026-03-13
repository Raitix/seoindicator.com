<?php

namespace App\Services\Crawler\Data;

use Carbon\Carbon;
use InvalidArgumentException;

/**
 * Data Transfer Object for rate limit check results.
 *
 * @see TASK-22-13
 * @see SPEC-022
 */
class RateLimitResult implements \JsonSerializable
{
    /**
     * Create a new rate limit result.
     *
     * @param bool $allowed Whether the request is allowed
     * @param int $limit Maximum requests allowed
     * @param int $remaining Remaining requests in current window
     * @param Carbon $resetAt When the window resets
     * @param int|null $retryAfter Seconds until retry (null if allowed)
     */
    public function __construct(
        public readonly bool $allowed,
        public readonly int $limit,
        public readonly int $remaining,
        public readonly Carbon $resetAt,
        public readonly ?int $retryAfter = null,
    ) {
        $this->validate();
    }

    /**
     * Create an allowed result.
     *
     * @param int $limit Maximum requests allowed
     * @param int $remaining Remaining requests
     * @param Carbon $resetAt When the window resets
     * @return static
     */
    public static function allowed(int $limit, int $remaining, Carbon $resetAt): static
    {
        // Cap remaining to limit (business rule: remaining cannot exceed limit)
        $remaining = min($remaining, $limit);

        return new static(
            allowed: true,
            limit: $limit,
            remaining: $remaining,
            resetAt: $resetAt,
            retryAfter: null,
        );
    }

    /**
     * Create a denied result.
     *
     * @param int $limit Maximum requests allowed
     * @param Carbon $resetAt When the window resets
     * @param int $retryAfter Seconds until retry
     * @return static
     */
    public static function denied(int $limit, Carbon $resetAt, int $retryAfter): static
    {
        return new static(
            allowed: false,
            limit: $limit,
            remaining: 0,
            resetAt: $resetAt,
            retryAfter: $retryAfter,
        );
    }

    /**
     * Create from array.
     *
     * @param array $data
     * @return static
     * @throws InvalidArgumentException
     */
    public static function fromArray(array $data): static
    {
        if (!isset($data['allowed']) || !is_bool($data['allowed'])) {
            throw new InvalidArgumentException('Field "allowed" is required and must be boolean.');
        }

        if (!isset($data['limit']) || !is_int($data['limit'])) {
            throw new InvalidArgumentException('Field "limit" is required and must be integer.');
        }

        if (!isset($data['remaining']) || !is_int($data['remaining'])) {
            throw new InvalidArgumentException('Field "remaining" is required and must be integer.');
        }

        if (!isset($data['resetAt']) || !($data['resetAt'] instanceof Carbon)) {
            throw new InvalidArgumentException('Field "resetAt" is required and must be a Carbon instance.');
        }

        if (isset($data['retryAfter']) && !is_int($data['retryAfter'])) {
            throw new InvalidArgumentException('Field "retryAfter" must be integer or null.');
        }

        return new static(
            allowed: $data['allowed'],
            limit: $data['limit'],
            remaining: $data['remaining'],
            resetAt: $data['resetAt'],
            retryAfter: $data['retryAfter'] ?? null,
        );
    }

    /**
     * Convert to array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'allowed' => $this->allowed,
            'limit' => $this->limit,
            'remaining' => $this->remaining,
            'resetAt' => $this->resetAt->toIso8601ZuluString(),
            'retryAfter' => $this->retryAfter,
        ];
    }

    /**
     * Get rate limit headers for HTTP response.
     *
     * @return array<string, int|string>
     */
    public function getHeaders(): array
    {
        $headers = [
            'X-RateLimit-Limit' => $this->limit,
            'X-RateLimit-Remaining' => $this->remaining,
            'X-RateLimit-Reset' => $this->resetAt->timestamp,
        ];

        if ($this->retryAfter !== null) {
            $headers['Retry-After'] = $this->retryAfter;
        }

        return $headers;
    }

    /**
     * JSON serialization.
     *
     * @return array
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * Validate the result data.
     *
     * @throws InvalidArgumentException
     */
    protected function validate(): void
    {
        if ($this->remaining < 0) {
            throw new InvalidArgumentException('Remaining cannot be negative.');
        }

        if ($this->allowed && $this->retryAfter !== null) {
            throw new InvalidArgumentException('retryAfter must be null when allowed is true.');
        }

        if (!$this->allowed && $this->retryAfter === null) {
            throw new InvalidArgumentException('retryAfter must be set when allowed is false.');
        }
    }
}
