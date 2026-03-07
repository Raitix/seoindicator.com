<?php

namespace App\Services\AIVisibility\DTOs;

use JsonSerializable;

/**
 * Data Transfer Object for a brand mention in AI response.
 *
 * Represents a single mention of a brand within an AI response,
 * including its position and surrounding context.
 *
 * @property-read string $brand The brand name that was mentioned
 * @property-read int $position The character position in the response
 * @property-read string $context The surrounding context (100 chars)
 */
readonly class Mention implements JsonSerializable
{
    /**
     * @param string $brand The brand name mentioned
     * @param int $position Character position in response (0-indexed)
     * @param string $context Surrounding context (~100 chars)
     */
    public function __construct(
        public string $brand,
        public int $position,
        public string $context,
    ) {
        if ($position < 0) {
            throw new \InvalidArgumentException('position must be non-negative');
        }
    }

    /**
     * Create a mention from array data.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            brand: $data['brand'],
            position: $data['position'],
            context: $data['context'],
        );
    }

    /**
     * Convert to array for JSON serialization.
     */
    public function jsonSerialize(): array
    {
        return [
            'brand' => $this->brand,
            'position' => $this->position,
            'context' => $this->context,
        ];
    }
}
