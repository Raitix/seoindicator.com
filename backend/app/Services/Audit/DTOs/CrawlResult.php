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
        public readonly ?string $html = null,
        public readonly array $links = [],
        public readonly array $metadata = [],
        public readonly ?string $errorMessage = null,
        public readonly array $headers = [],
        public readonly int $depth = 0,
        public readonly array $redirectChain = [],
    ) {}

    // =========================================================================
    // Status Checks
    // =========================================================================

    /**
     * Check if the crawl was successful.
     */
    public function isSuccess(): bool
    {
        return $this->statusCode >= 200 && $this->statusCode < 400 && $this->errorMessage === null;
    }

    /**
     * Check if the crawl resulted in an error.
     */
    public function isError(): bool
    {
        return $this->errorMessage !== null || $this->statusCode >= 400;
    }

    /**
     * Check if the crawl was a failure (alias for isError).
     */
    public function isFailure(): bool
    {
        return $this->isError();
    }

    /**
     * Check if this is a redirect.
     */
    public function isRedirect(): bool
    {
        return in_array($this->statusCode, [301, 302, 303, 307, 308]);
    }

    /**
     * Check if this is a client error (4xx).
     */
    public function isClientError(): bool
    {
        return $this->statusCode >= 400 && $this->statusCode < 500;
    }

    /**
     * Check if this is a server error (5xx).
     */
    public function isServerError(): bool
    {
        return $this->statusCode >= 500 && $this->statusCode < 600;
    }

    // =========================================================================
    // Link Accessors
    // =========================================================================

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

    // =========================================================================
    // Metadata Accessors
    // =========================================================================

    /**
     * Get a specific metadata value.
     */
    public function getMetadata(string $key, mixed $default = null): mixed
    {
        return $this->metadata[$key] ?? $default;
    }

    /**
     * Get the page title from metadata.
     */
    public function getTitle(): ?string
    {
        return $this->metadata['title'] ?? null;
    }

    /**
     * Get the meta description from metadata.
     */
    public function getDescription(): ?string
    {
        return $this->metadata['description'] ?? null;
    }

    /**
     * Get the canonical URL from metadata.
     */
    public function getCanonical(): ?string
    {
        return $this->metadata['canonical'] ?? null;
    }

    /**
     * Get H1 tags from metadata.
     */
    public function getH1Tags(): array
    {
        return $this->metadata['h1'] ?? [];
    }

    // =========================================================================
    // Header Accessors
    // =========================================================================

    /**
     * Get a specific header value (case-insensitive).
     */
    public function getHeader(string $name, mixed $default = null): mixed
    {
        // Try exact match first
        if (isset($this->headers[$name])) {
            $value = $this->headers[$name];
            return is_array($value) ? ($value[0] ?? $default) : $value;
        }

        // Try case-insensitive match
        $lowerName = strtolower($name);
        foreach ($this->headers as $key => $value) {
            if (strtolower($key) === $lowerName) {
                return is_array($value) ? ($value[0] ?? $default) : $value;
            }
        }

        return $default;
    }

    // =========================================================================
    // Serialization
    // =========================================================================

    /**
     * Convert to array representation (camelCase keys).
     */
    public function toArray(): array
    {
        return [
            'url' => $this->url,
            'statusCode' => $this->statusCode,
            'responseTimeMs' => $this->responseTimeMs,
            'html' => $this->html,
            'links' => $this->links,
            'metadata' => $this->metadata,
            'errorMessage' => $this->errorMessage,
            'headers' => $this->headers,
            'depth' => $this->depth,
            'redirectChain' => $this->redirectChain,
        ];
    }

    /**
     * Create from array (accepts both camelCase and snake_case).
     */
    public static function fromArray(array $data): self
    {
        return new self(
            url: $data['url'],
            statusCode: $data['statusCode'] ?? $data['status_code'] ?? 0,
            responseTimeMs: $data['responseTimeMs'] ?? $data['response_time_ms'] ?? 0.0,
            html: $data['html'] ?? null,
            links: $data['links'] ?? [],
            metadata: $data['metadata'] ?? [],
            errorMessage: $data['errorMessage'] ?? $data['error_message'] ?? null,
            headers: $data['headers'] ?? [],
            depth: $data['depth'] ?? 0,
            redirectChain: $data['redirectChain'] ?? $data['redirect_chain'] ?? [],
        );
    }

    /**
     * JSON serialize.
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    // =========================================================================
    // Factory Methods
    // =========================================================================

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
