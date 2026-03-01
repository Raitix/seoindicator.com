<?php

namespace App\Services\AI\DTOs;

use JsonSerializable;

/**
 * Data Transfer Object for AI provider responses.
 *
 * Represents a response from an AI provider, containing either
 * successful content or error information.
 *
 * @property-read bool $success Whether the request was successful
 * @property-read string|null $content The generated content (null on failure)
 * @property-read int|null $tokensUsed Number of tokens used (null if not tracked)
 * @property-read int|null $latencyMs Response latency in milliseconds (null if not tracked)
 * @property-read string|null $errorMessage Error message (null on success)
 */
readonly class AIResponse implements JsonSerializable
{
    /**
     * Create a new AIResponse instance.
     *
     * @param bool $success Whether the request was successful
     * @param string|null $content The generated content
     * @param int|null $tokensUsed Number of tokens used
     * @param int|null $latencyMs Response latency in milliseconds
     * @param string|null $errorMessage Error message
     * @throws \InvalidArgumentException If tokensUsed or latencyMs are negative
     */
    public function __construct(
        public bool $success,
        public ?string $content,
        public ?int $tokensUsed,
        public ?int $latencyMs,
        public ?string $errorMessage
    ) {
        if ($tokensUsed !== null && $tokensUsed < 0) {
            throw new \InvalidArgumentException('tokensUsed must be non-negative');
        }

        if ($latencyMs !== null && $latencyMs < 0) {
            throw new \InvalidArgumentException('latencyMs must be non-negative');
        }
    }

    /**
     * Create a successful response.
     *
     * @param string $content The generated content
     * @param int|null $tokensUsed Number of tokens used
     * @param int|null $latencyMs Response latency in milliseconds
     * @return self
     */
    public static function success(
        string $content,
        ?int $tokensUsed = null,
        ?int $latencyMs = null
    ): self {
        return new self(
            success: true,
            content: $content,
            tokensUsed: $tokensUsed,
            latencyMs: $latencyMs,
            errorMessage: null
        );
    }

    /**
     * Create an error response.
     *
     * @param string $errorMessage The error message
     * @param int|null $latencyMs Response latency in milliseconds
     * @return self
     */
    public static function error(string $errorMessage, ?int $latencyMs = null): self
    {
        return new self(
            success: false,
            content: null,
            tokensUsed: null,
            latencyMs: $latencyMs,
            errorMessage: $errorMessage
        );
    }

    /**
     * Create an instance from JSON data.
     *
     * @param string $json JSON string
     * @return self
     * @throws \JsonException If JSON is invalid
     */
    public static function fromJson(string $json): self
    {
        $data = json_decode($json, true, flags: JSON_THROW_ON_ERROR);

        return new self(
            success: $data['success'],
            content: $data['content'] ?? null,
            tokensUsed: $data['tokensUsed'] ?? null,
            latencyMs: $data['latencyMs'] ?? null,
            errorMessage: $data['errorMessage'] ?? null
        );
    }

    /**
     * Convert to array for JSON serialization.
     *
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'success' => $this->success,
            'content' => $this->content,
            'tokensUsed' => $this->tokensUsed,
            'latencyMs' => $this->latencyMs,
            'errorMessage' => $this->errorMessage,
        ];
    }
}
