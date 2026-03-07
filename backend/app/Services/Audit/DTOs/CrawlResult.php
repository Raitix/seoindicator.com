<?php

namespace App\Services\Audit\DTOs;

/**
 * Data Transfer Object for crawl results.
 * 
 * Represents the result of crawling a single URL,
 * including the HTML content, extracted links, and metadata.
 */
class CrawlResult implements \JsonSerializable
{
    /**
     * @param string $url The crawled URL
     * @param int $statusCode HTTP status code
     * @param float $responseTimeMs Response time in milliseconds
     * @param string|null $html HTML content (null on error)
     * @param array $links Extracted links ['internal' => [...], 'external' => [...]]
     * @param array $metadata Page metadata (title, description, h1, etc.)
     * @param string|null $errorMessage Error details if failed
     * @param array $headers Response headers
     * @param int $depth Crawl depth (0 = homepage)
     * @param array $redirectChain Redirect history
     */
    public function __construct(
        public readonly string $url,
        public readonly int $statusCode,
        public readonly float $responseTimeMs,
        public readonly ?string $html,
        public readonly array $links = [],
        public readonly array $metadata = [],
        public readonly ?string $errorMessage = null,
        public readonly array $headers = [],
        public readonly int $depth = 0,
        public readonly array $redirectChain = [],
    ) {}

    /**
     * Check if the crawl was successful.
     */
    public function isSuccess(): bool
    {
        return $this->statusCode >= 200 && $this->statusCode < 300 && $this->errorMessage === null;
    }

    /**
     * Check if the crawl resulted in an error.
     */
    public function isError(): bool
    {
        return $this->errorMessage !== null || $this->statusCode >= 400;
    }

    /**
     * Check if this is a redirect.
     */
    public function isRedirect(): bool
    {
        return in_array($this->statusCode, [301, 302, 303, 307, 308]);
    }

    /**
     * Get internal links.
     */
    public function getInternalLinks(): array
    {
        return $this->links['internal'] ?? [];
    }

    /**
     * Get external links.
     */
    public function getExternalLinks(): array
    {
        return $this->links['external'] ?? [];
    }

    /**
     * Get a specific metadata value.
     */
    public function getMetadata(string $key, mixed $default = null): mixed
    {
        return $this->metadata[$key] ?? $default;
    }

    /**
     * Convert to array representation.
     */
    public function toArray(): array
    {
        return [
            'url' => $this->url,
            'status_code' => $this->statusCode,
            'response_time_ms' => $this->responseTimeMs,
            'html' => $this->html,
            'links' => $this->links,
            'metadata' => $this->metadata,
            'error_message' => $this->errorMessage,
            'headers' => $this->headers,
            'depth' => $this->depth,
            'redirect_chain' => $this->redirectChain,
        ];
    }

    /**
     * Create from array.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            url: $data['url'],
            statusCode: $data['status_code'] ?? $data['statusCode'] ?? 0,
            responseTimeMs: $data['response_time_ms'] ?? $data['responseTimeMs'] ?? 0.0,
            html: $data['html'] ?? null,
            links: $data['links'] ?? [],
            metadata: $data['metadata'] ?? [],
            errorMessage: $data['error_message'] ?? $data['errorMessage'] ?? null,
            headers: $data['headers'] ?? [],
            depth: $data['depth'] ?? 0,
            redirectChain: $data['redirect_chain'] ?? $data['redirectChain'] ?? [],
        );
    }

    /**
     * JSON serialize.
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * Create a successful crawl result.
     *
     * @param string $url The crawled URL
     * @param int $statusCode HTTP status code
     * @param float $responseTimeMs Response time in milliseconds
     * @param string|null $html HTML content
     * @param array $links Extracted links
     * @param array $metadata Page metadata
     * @param array $headers Response headers
     * @param array $redirectChain Redirect history
     * @param int $depth Crawl depth
     * @return self
     */
    public static function success(
        string $url,
        int $statusCode,
        float $responseTimeMs,
        ?string $html = null,
        array $links = [],
        array $metadata = [],
        array $headers = [],
        array $redirectChain = [],
        int $depth = 0
    ): self {
        return new self(
            url: $url,
            statusCode: $statusCode,
            responseTimeMs: $responseTimeMs,
            html: $html,
            links: $links,
            metadata: $metadata,
            errorMessage: null,
            headers: $headers,
            depth: $depth,
            redirectChain: $redirectChain,
        );
    }

    /**
     * Create a failed crawl result.
     *
     * @param string $url The URL that failed
     * @param string $errorMessage Error description
     * @param int $statusCode HTTP status code (0 for network errors)
     * @param float $responseTimeMs Response time in milliseconds
     * @param int $depth Crawl depth
     * @return self
     */
    public static function failure(
        string $url,
        string $errorMessage,
        int $statusCode = 0,
        float $responseTimeMs = 0.0,
        int $depth = 0
    ): self {
        return new self(
            url: $url,
            statusCode: $statusCode,
            responseTimeMs: $responseTimeMs,
            html: null,
            links: [],
            metadata: [],
            errorMessage: $errorMessage,
            headers: [],
            depth: $depth,
            redirectChain: [],
        );
    }
}
