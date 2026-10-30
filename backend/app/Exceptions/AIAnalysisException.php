<?php

namespace App\Exceptions;

use Exception;

/**
 * Exception thrown when AI analysis fails.
 *
 * Used by AIAnalysisService and AnalysisRunnerService to indicate
 * failures in the unified AI analysis flow.
 *
 * @see TASK-31-7
 * @see TASK-31-13
 */
class AIAnalysisException extends Exception
{
    /**
     * Error codes for different failure types.
     */
    public const CODE_CONTEXT_EXCEEDED = 413;
    public const CODE_AI_UNAVAILABLE = 503;
    public const CODE_INVALID_RESPONSE = 422;
    public const CODE_RATE_LIMITED = 429;
    public const CODE_TIMEOUT = 504;
    public const CODE_AUTH_FAILED = 401;
    public const CODE_UNKNOWN = 500;

    /**
     * Additional context about the error.
     *
     * @var array<string, mixed>
     */
    protected array $context = [];

    /**
     * Create a new AIAnalysisException instance.
     *
     * @param string $message Error message
     * @param int $code Error code
     * @param Exception|null $previous Previous exception
     * @param array $context Additional context
     */
    public function __construct(
        string $message = '',
        int $code = 0,
        ?Exception $previous = null,
        array $context = []
    ) {
        parent::__construct($message, $code, $previous);
        $this->context = $context;
    }

    /**
     * Get the error context.
     *
     * @return array<string, mixed>
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * Set the error context.
     *
     * @param array $context
     * @return self
     */
    public function setContext(array $context): self
    {
        $this->context = $context;
        return $this;
    }

    /**
     * Add context data.
     *
     * @param string $key
     * @param mixed $value
     * @return self
     */
    public function addContext(string $key, mixed $value): self
    {
        $this->context[$key] = $value;
        return $this;
    }

    /**
     * Create exception for context exceeded error.
     *
     * @param int $estimatedTokens
     * @param int $limit
     * @return self
     */
    public static function contextExceeded(int $estimatedTokens, int $limit): self
    {
        return new self(
            "Content exceeds context limit ({$estimatedTokens} > {$limit} tokens)",
            self::CODE_CONTEXT_EXCEEDED,
            null,
            ['estimated_tokens' => $estimatedTokens, 'limit' => $limit]
        );
    }

    /**
     * Create exception for AI unavailable error.
     *
     * @param string $reason
     * @return self
     */
    public static function aiUnavailable(string $reason = ''): self
    {
        return new self(
            "AI service unavailable: {$reason}",
            self::CODE_AI_UNAVAILABLE,
            null,
            ['reason' => $reason]
        );
    }

    /**
     * Create exception for invalid response error.
     *
     * @param string $details
     * @param string|null $rawResponse
     * @return self
     */
    public static function invalidResponse(string $details, ?string $rawResponse = null): self
    {
        return new self(
            "Invalid AI response: {$details}",
            self::CODE_INVALID_RESPONSE,
            null,
            ['details' => $details, 'raw_response' => $rawResponse]
        );
    }

    /**
     * Create exception for rate limited error.
     *
     * @param int $retryAfter Seconds until retry is allowed
     * @return self
     */
    public static function rateLimited(int $retryAfter = 60): self
    {
        return new self(
            "AI API rate limit exceeded. Retry after {$retryAfter} seconds.",
            self::CODE_RATE_LIMITED,
            null,
            ['retry_after' => $retryAfter]
        );
    }

    /**
     * Create exception for timeout error.
     *
     * @param int $timeoutMs
     * @return self
     */
    public static function timeout(int $timeoutMs): self
    {
        return new self(
            "AI request timed out after {$timeoutMs}ms",
            self::CODE_TIMEOUT,
            null,
            ['timeout_ms' => $timeoutMs]
        );
    }

    /**
     * Create exception for authentication failure.
     *
     * @param string $provider
     * @return self
     */
    public static function authFailed(string $provider = 'unknown'): self
    {
        return new self(
            "AI provider authentication failed: {$provider}",
            self::CODE_AUTH_FAILED,
            null,
            ['provider' => $provider]
        );
    }

    /**
     * Check if the error is retryable.
     *
     * @return bool
     */
    public function isRetryable(): bool
    {
        return in_array($this->code, [
            self::CODE_AI_UNAVAILABLE,
            self::CODE_RATE_LIMITED,
            self::CODE_TIMEOUT,
        ]);
    }

    /**
     * Get the recommended retry delay in seconds.
     *
     * @return int
     */
    public function getRetryDelay(): int
    {
        return match ($this->code) {
            self::CODE_RATE_LIMITED => $this->context['retry_after'] ?? 60,
            self::CODE_TIMEOUT => 5,
            self::CODE_AI_UNAVAILABLE => 30,
            default => 0,
        };
    }
}
