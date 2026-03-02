<?php

namespace App\Services\Playwright\Data;

/**
 * Data Transfer Object for extracted content.
 *
 * @see TASK-22-5
 * @see SPEC-022
 */
class ExtractedContent implements \JsonSerializable
{
    public function __construct(
        public readonly string $html,
        public readonly ?string $title,
        public readonly ?string $structuredText,
        public readonly int $size,
    ) {}

    /**
     * Check if content has text.
     */
    public function hasText(): bool
    {
        return !empty($this->structuredText);
    }

    /**
     * Get word count from structured text.
     */
    public function getWordCount(): int
    {
        if (empty($this->structuredText)) {
            return 0;
        }
        return str_word_count($this->structuredText);
    }

    /**
     * Convert to array.
     */
    public function toArray(): array
    {
        return [
            'html' => $this->html,
            'title' => $this->title,
            'structuredText' => $this->structuredText,
            'size' => $this->size,
        ];
    }

    /**
     * Create from array.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            html: $data['html'] ?? '',
            title: $data['title'] ?? null,
            structuredText: $data['structuredText'] ?? null,
            size: $data['size'] ?? 0,
        );
    }

    /**
     * JSON serialization.
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
