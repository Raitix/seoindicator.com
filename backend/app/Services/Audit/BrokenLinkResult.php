<?php

namespace App\Services\Audit;

/**
 * Data Transfer Object for a single broken link check result.
 *
 * Contains all information about a URL that was checked, including
 * the URL itself, the HTTP status code (if available), the type
 * of result (valid, broken, redirect, etc.), and contextual information
 * about where the link was found.
 *
 * @see TEST-14-10
 * @see TASK-14-10
 */
class BrokenLinkResult
{
    /**
     * @param string $url The checked URL (absolute)
     * @param string $sourceUrl Page where link was found
     * @param int|null $statusCode HTTP status code (null for connection errors)
     * @param string $type One of: 'valid', 'broken', 'redirect', 'timeout', 'error', 'access_denied', 'auth_required', 'gone', 'rate_limited', 'blocked'
     * @param string|null $redirectUrl Destination URL for redirects
     * @param int $responseTimeMs Response time in milliseconds
     * @param string|null $linkText Anchor text or alt text
     * @param string $elementType HTML element type: 'a', 'img', 'link', 'script', 'iframe'
     * @param string|null $errorMessage Error details if applicable
     * @param string|null $surroundingText Context from HTML
     */
    public function __construct(
        public readonly string $url,
        public readonly string $sourceUrl,
        public readonly ?int $statusCode,
        public readonly string $type,
        public readonly ?string $redirectUrl = null,
        public readonly int $responseTimeMs = 0,
        public readonly ?string $linkText = null,
        public readonly string $elementType = 'a',
        public readonly ?string $errorMessage = null,
        public readonly ?string $surroundingText = null,
    ) {}

    /**
     * Create a result for a valid URL.
     */
    public static function valid(string $url, string $sourceUrl, int $responseTimeMs = 0, int $statusCode = 200): self
    {
        return new self(
            url: $url,
            sourceUrl: $sourceUrl,
            statusCode: $statusCode,
            type: 'valid',
            responseTimeMs: $responseTimeMs,
        );
    }

    /**
     * Create a result for a broken URL (404, 410, etc.).
     */
    public static function broken(string $url, string $sourceUrl, int $statusCode, string $errorMessage = null): self
    {
        return new self(
            url: $url,
            sourceUrl: $sourceUrl,
            statusCode: $statusCode,
            type: self::determineType($statusCode),
            errorMessage: $errorMessage,
        );
    }

    /**
     * Create a result for a redirect.
     */
    public static function redirect(string $url, string $sourceUrl, int $statusCode, string $redirectUrl): self
    {
        return new self(
            url: $url,
            sourceUrl: $sourceUrl,
            statusCode: $statusCode,
            type: 'redirect',
            redirectUrl: $redirectUrl,
        );
    }

    /**
     * Create a result for a blocked URL (SSRF protection).
     */
    public static function blocked(string $url, string $sourceUrl, string $reason): self
    {
        return new self(
            url: $url,
            sourceUrl: $sourceUrl,
            statusCode: null,
            type: 'blocked',
            errorMessage: $reason,
        );
    }

    /**
     * Create a result for a timeout.
     */
    public static function timeout(string $url, string $sourceUrl, string $errorMessage): self
    {
        return new self(
            url: $url,
            sourceUrl: $sourceUrl,
            statusCode: null,
            type: 'timeout',
            errorMessage: $errorMessage,
        );
    }

    /**
     * Create a result for a connection error or HTTP error.
     */
    public static function error(string $url, string $sourceUrl, string $errorMessage, string $type = 'error', int $statusCode = null): self
    {
        return new self(
            url: $url,
            sourceUrl: $sourceUrl,
            statusCode: $statusCode,
            type: $type,
            errorMessage: $errorMessage,
        );
    }

    /**
     * Determine the type based on HTTP status code.
     */
    public static function determineType(int $statusCode): string
    {
        return match (true) {
            $statusCode >= 200 && $statusCode < 300 => 'valid',
            $statusCode >= 300 && $statusCode < 400 => 'redirect',
            $statusCode === 401 => 'auth_required',
            $statusCode === 403 => 'access_denied',
            $statusCode === 404 => 'broken',
            $statusCode === 410 => 'gone',
            $statusCode === 429 => 'rate_limited',
            $statusCode >= 400 && $statusCode < 500 => 'error',
            $statusCode === 504 => 'timeout',
            $statusCode >= 500 => 'error',
            default => 'error',
        };
    }

    /**
     * Convert to array for serialization.
     */
    public function toArray(): array
    {
        return [
            'url' => $this->url,
            'sourceUrl' => $this->sourceUrl,
            'statusCode' => $this->statusCode,
            'type' => $this->type,
            'redirectUrl' => $this->redirectUrl,
            'responseTimeMs' => $this->responseTimeMs,
            'linkText' => $this->linkText,
            'elementType' => $this->elementType,
            'errorMessage' => $this->errorMessage,
            'surroundingText' => $this->surroundingText,
        ];
    }
}
