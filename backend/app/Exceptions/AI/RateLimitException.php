<?php

namespace App\Exceptions\AI;

use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Exception thrown when AI API rate limit is exceeded (HTTP 429).
 * 
 * Supports Retry-After header for proper backoff handling.
 */
class RateLimitException extends HttpException
{
    /**
     * The retry-after value in seconds, if provided.
     */
    protected ?int $retryAfter;

    /**
     * Create a new rate limit exception.
     * 
     * @param string $message The exception message
     * @param int $statusCode HTTP status code (default 429)
     * @param \Throwable|null $previous Previous exception (nullable for flexibility)
     * @param array $headers Response headers
     * @param int $code The internal exception code
     * @param int|null $retryAfter Explicit retry-after value in seconds
     * @param array $retryAfterHeaders Headers containing retry-after key
     */
    public function __construct(
        string $message = 'Rate limit exceeded',
        int $statusCode = 429,
        ?\Throwable $previous = null,
        array $headers = [],
        int $code = 0,
        ?int $retryAfter = null,
        array $retryAfterHeaders = []
    ) {
        // Merge retry-after headers if provided
        if (!empty($retryAfterHeaders)) {
            $headers = array_merge($headers, $retryAfterHeaders);
        }
        
        // Extract retry-after from explicit value or headers
        if ($retryAfter === null && isset($headers['retry-after'])) {
            $retryAfter = $this->parseRetryAfter($headers['retry-after']);
        }
        
        $this->retryAfter = $retryAfter;
        
        parent::__construct($statusCode, $message, $previous, $headers, $code);
    }

    /**
     * Get the retry-after value in seconds.
     * 
     * @return int|null Seconds until retry is allowed, or null if not specified
     */
    public function getRetryAfter(): ?int
    {
        return $this->retryAfter;
    }

    /**
     * Parse retry-after header value.
     * 
     * Can be either an integer (seconds) or a date string.
     * 
     * @param mixed $value The retry-after header value
     * @return int|null Parsed seconds, or null if invalid
     */
    protected function parseRetryAfter($value): ?int
    {
        if ($value === null) {
            return null;
        }

        // If already an integer
        if (is_int($value)) {
            return max(0, $value);
        }

        // If string, try to parse
        if (is_string($value)) {
            // Try parsing as integer seconds
            if (ctype_digit($value)) {
                return (int) $value;
            }

            // Try parsing as HTTP date
            try {
                $date = new \DateTime($value);
                $now = new \DateTime();
                $diff = $date->getTimestamp() - $now->getTimestamp();
                return max(0, $diff);
            } catch (\Exception $e) {
                return null;
            }
        }

        return null;
    }
}
