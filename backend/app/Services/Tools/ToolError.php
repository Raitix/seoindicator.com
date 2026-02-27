<?php

namespace App\Services\Tools;

/**
 * Tool Error DTO.
 * 
 * Represents an error from the SEO tools API.
 * 
 * TASK-16-12: Error Handling & User Feedback
 */
class ToolError
{
    public const INVALID_URL = 'INVALID_URL';
    public const URL_BLOCKED = 'URL_BLOCKED';
    public const FETCH_FAILED = 'FETCH_FAILED';
    public const TIMEOUT = 'TIMEOUT';
    public const RATE_LIMITED = 'RATE_LIMITED';
    public const PARSE_ERROR = 'PARSE_ERROR';
    public const TOOL_ERROR = 'TOOL_ERROR';

    /**
     * Error code definitions.
     */
    public const ERROR_DEFINITIONS = [
        self::INVALID_URL => [
            'http_status' => 400,
            'message' => 'Invalid URL format',
            'suggestion' => 'Please enter a valid URL (e.g., https://example.com)',
            'retryable' => false,
        ],
        self::URL_BLOCKED => [
            'http_status' => 403,
            'message' => 'URL not allowed',
            'suggestion' => 'This URL is not allowed for analysis. Try a different URL.',
            'retryable' => false,
        ],
        self::FETCH_FAILED => [
            'http_status' => 502,
            'message' => 'Could not fetch page',
            'suggestion' => 'The page may be blocking automated access. Try a different URL.',
            'retryable' => true,
        ],
        self::TIMEOUT => [
            'http_status' => 504,
            'message' => 'Analysis timed out',
            'suggestion' => 'The page took too long to load. Try again or use a different URL.',
            'retryable' => true,
        ],
        self::RATE_LIMITED => [
            'http_status' => 429,
            'message' => 'Too many requests',
            'suggestion' => 'Please wait a moment before trying again.',
            'retryable' => true,
        ],
        self::PARSE_ERROR => [
            'http_status' => 500,
            'message' => 'Could not parse page',
            'suggestion' => 'The page content could not be analyzed. Try a different URL.',
            'retryable' => false,
        ],
        self::TOOL_ERROR => [
            'http_status' => 500,
            'message' => 'Analysis failed',
            'suggestion' => 'An unexpected error occurred. Please try again later.',
            'retryable' => true,
        ],
    ];

    public string $code;
    public string $message;
    public int $httpStatus;
    public ?string $details;
    public ?string $suggestion;
    public bool $retryable;
    public array $context;
    public string $requestId;
    public string $timestamp;

    public function __construct(
        string $code,
        string $message,
        int $httpStatus,
        ?string $details = null,
        ?string $suggestion = null,
        bool $retryable = false,
        array $context = []
    ) {
        $this->code = $code;
        $this->message = $message;
        $this->httpStatus = $httpStatus;
        $this->details = $details;
        $this->suggestion = $suggestion;
        $this->retryable = $retryable;
        $this->context = $context;
        $this->requestId = uniqid('req_');
        $this->timestamp = now()->toISOString();
    }

    /**
     * Create from error code.
     */
    public static function fromCode(string $code, array $context = [], ?string $customMessage = null): self
    {
        $definition = self::ERROR_DEFINITIONS[$code] ?? self::ERROR_DEFINITIONS[self::TOOL_ERROR];

        return new self(
            code: $code,
            message: $customMessage ?? $definition['message'],
            httpStatus: $definition['http_status'],
            details: $context['details'] ?? null,
            suggestion: $definition['suggestion'],
            retryable: $definition['retryable'],
            context: $context
        );
    }

    /**
     * Check if error is retryable.
     */
    public function isRetryable(): bool
    {
        return $this->retryable;
    }

    /**
     * Get retry delay in milliseconds.
     */
    public function getRetryDelay(int $attempt = 1): int
    {
        if (!$this->retryable) {
            return 0;
        }

        // Exponential backoff: 1s, 2s, 4s...
        $baseDelay = 1000;
        return $baseDelay * pow(2, $attempt - 1);
    }

    /**
     * Convert to array for JSON response.
     */
    public function toArray(): array
    {
        return [
            'code' => $this->code,
            'message' => $this->message,
            'details' => $this->details,
            'suggestion' => $this->suggestion,
            'request_id' => $this->requestId,
            'timestamp' => $this->timestamp,
        ];
    }

    /**
     * Convert to response array.
     */
    public function toResponse(): array
    {
        return [
            'success' => false,
            'error' => $this->toArray(),
        ];
    }

    /**
     * Get HTTP status code.
     */
    public function getHttpStatus(): int
    {
        return $this->httpStatus;
    }

    /**
     * Get error code.
     */
    public function getCode(): string
    {
        return $this->code;
    }

    /**
     * Get all valid error codes.
     */
    public static function getValidCodes(): array
    {
        return array_keys(self::ERROR_DEFINITIONS);
    }

    /**
     * Check if a code is valid.
     */
    public static function isValidCode(string $code): bool
    {
        return isset(self::ERROR_DEFINITIONS[$code]);
    }
}
