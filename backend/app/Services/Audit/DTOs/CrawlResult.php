<?php

namespace App\Services\Audit\DTOs;

use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

/**
 * Data Transfer Object for crawl results.
 *
 * Represents the result of crawling a single URL during a site audit.
 *
 * @see TASK-14-5
 */
class CrawlResult implements Arrayable, JsonSerializable
{
    /**
     * Create a new CrawlResult instance.
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

    /**
     * Create a successful crawl result.
     */
    public static function success(
        string $url,
        int $statusCode,
        float $responseTimeMs,
        string $html,
        array $links = [],
        array $metadata = [],
        array $headers = [],
        int $depth = 0,
        array $redirectChain = [],
    ): self {
        return new self(
            url: $url,
            statusCode: $statusCode,
            responseTimeMs: $responseTimeMs,
            html: $html,
            links: $links,
            metadata: $metadata,
            headers: $headers,
            depth: $depth,
            redirectChain: $redirectChain,
        );
    }

    /**
     * Create a failed crawl result.
     */
    public static function failure(
        string $url,
        string $errorMessage,
        int $statusCode = 0,
        float $responseTimeMs = 0.0,
        int $depth = 0,
    ): self {
        return new self(
            url: $url,
            statusCode: $statusCode,
            responseTimeMs: $responseTimeMs,
            html: null,
            links: [],
            metadata: [],
            errorMessage: $errorMessage,
            depth: $depth,
        );
    }

    /**
     * Check if the crawl was successful.
     */
    public function isSuccess(): bool
    {
        return $this->errorMessage === null && $this->statusCode >= 200 && $this->statusCode < 400;
    }

    /**
     * Check if the crawl failed.
     */
    public function isFailure(): bool
    {
        return !$this->isSuccess();
    }

    /**
     * Check if the page is a redirect.
     */
    public function isRedirect(): bool
    {
        return in_array($this->statusCode, [301, 302, 303, 307, 308]);
    }

    /**
     * Check if the page is a client error.
     */
    public function isClientError(): bool
    {
        return $this->statusCode >= 400 && $this->statusCode < 500;
    }

    /**
     * Check if the page is a server error.
     */
    public function isServerError(): bool
    {
        return $this->statusCode >= 500;
    }

    /**
     * Get internal links from the result.
     */
    public function getInternalLinks(): array
    {
        return $this->links['internal'] ?? [];
    }

    /**
     * Get external links from the result.
     */
    public function getExternalLinks(): array
    {
        return $this->links['external'] ?? [];
    }

    /**
     * Get a metadata value.
     */
    public function getMetadata(string $key, mixed $default = null): mixed
    {
        return $this->metadata[$key] ?? $default;
    }

    /**
     * Get the page title.
     */
    public function getTitle(): ?string
    {
        return $this->getMetadata('title');
    }

    /**
     * Get the meta description.
     */
    public function getDescription(): ?string
    {
        return $this->getMetadata('description');
    }

    /**
     * Get the canonical URL.
     */
    public function getCanonical(): ?string
    {
        return $this->getMetadata('canonical');
    }

    /**
     * Get the H1 tags.
     */
    public function getH1Tags(): array
    {
        return $this->getMetadata('h1', []);
    }

    /**
     * Get the response header value.
     */
    public function getHeader(string $name): ?string
    {
        $name = strtolower($name);
        foreach ($this->headers as $key => $value) {
            if (strtolower($key) === $name) {
                return is_array($value) ? $value[0] : $value;
            }
        }
        return null;
    }

    /**
     * Convert to array representation.
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
     * Specify data which should be serialized to JSON.
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * Create from array.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            url: $data['url'],
            statusCode: $data['statusCode'] ?? 0,
            responseTimeMs: $data['responseTimeMs'] ?? 0.0,
            html: $data['html'] ?? null,
            links: $data['links'] ?? [],
            metadata: $data['metadata'] ?? [],
            errorMessage: $data['errorMessage'] ?? null,
            headers: $data['headers'] ?? [],
            depth: $data['depth'] ?? 0,
            redirectChain: $data['redirectChain'] ?? [],
        );
    }
}
