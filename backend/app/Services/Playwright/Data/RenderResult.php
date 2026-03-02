<?php

namespace App\Services\Playwright\Data;

/**
 * Data Transfer Object for render results.
 *
 * @see TASK-22-5
 * @see SPEC-022
 */
class RenderResult implements \JsonSerializable
{
    public function __construct(
        public readonly string $url,
        public readonly string $originalUrl,
        public readonly ?string $title,
        public readonly ?string $renderedHtml,
        public readonly ?string $structuredText,
        public readonly array $meta,
        public readonly array $links,
        public readonly int $size,
        public readonly int $loadTime,
        public readonly int $statusCode,
        public readonly ?string $error,
    ) {}

    /**
     * Check if the render was successful.
     */
    public function isSuccess(): bool
    {
        return $this->error === null;
    }

    /**
     * Get formatted size string.
     */
    public function getFormattedSize(): string
    {
        $kb = $this->size / 1024;
        if ($kb < 1024) {
            return round($kb, 1) . ' KB';
        }
        return round($kb / 1024, 2) . ' MB';
    }

    /**
     * Get formatted load time string.
     */
    public function getFormattedLoadTime(): string
    {
        if ($this->loadTime < 1000) {
            return $this->loadTime . 'ms';
        }
        return round($this->loadTime / 1000, 1) . 's';
    }

    /**
     * Convert to array.
     */
    public function toArray(): array
    {
        return [
            'url' => $this->url,
            'originalUrl' => $this->originalUrl,
            'title' => $this->title,
            'renderedHtml' => $this->renderedHtml,
            'structuredText' => $this->structuredText,
            'meta' => $this->meta,
            'links' => $this->links,
            'size' => $this->size,
            'loadTime' => $this->loadTime,
            'statusCode' => $this->statusCode,
            'error' => $this->error,
        ];
    }

    /**
     * JSON serialization.
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
