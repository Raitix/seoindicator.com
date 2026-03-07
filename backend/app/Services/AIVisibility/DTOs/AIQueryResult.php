<?php

namespace App\Services\AIVisibility\DTOs;

use JsonSerializable;

/**
 * Data Transfer Object for AI Query Results.
 *
 * Represents the result of an AI platform query, including
 * the response, metadata, and parsed mentions.
 *
 * @property-read bool $success Whether the query was successful
 * @property-read string|null $responseText The AI response text
 * @property-read string|null $model The model used for generation
 * @property-read int|null $tokensUsed Number of tokens used
 * @property-read int|null $latencyMs Response latency in milliseconds
 * @property-read string|null $errorMessage Error message (null on success)
 * @property-read string|null $platform The platform queried (chatgpt, perplexity)
 * @property-read string|null $queryText The original query text
 * @property-read array $mentions Parsed brand mentions
 * @property-read string|null $sentiment Overall sentiment of the response
 */
readonly class AIQueryResult implements JsonSerializable
{
    /**
     * @param array<Mention> $mentions Parsed brand mentions
     */
    public function __construct(
        public bool $success,
        public ?string $responseText,
        public ?string $model,
        public ?int $tokensUsed,
        public ?int $latencyMs,
        public ?string $errorMessage,
        public ?string $platform = null,
        public ?string $queryText = null,
        public array $mentions = [],
        public ?string $sentiment = null,
    ) {
        if ($tokensUsed !== null && $tokensUsed < 0) {
            throw new \InvalidArgumentException('tokensUsed must be non-negative');
        }

        if ($latencyMs !== null && $latencyMs < 0) {
            throw new \InvalidArgumentException('latencyMs must be non-negative');
        }
    }

    /**
     * Create a successful query result.
     */
    public static function success(
        string $responseText,
        string $model,
        int $tokensUsed,
        int $latencyMs,
        string $platform,
        string $queryText,
        array $mentions = [],
        ?string $sentiment = null,
    ): self {
        return new self(
            success: true,
            responseText: $responseText,
            model: $model,
            tokensUsed: $tokensUsed,
            latencyMs: $latencyMs,
            errorMessage: null,
            platform: $platform,
            queryText: $queryText,
            mentions: $mentions,
            sentiment: $sentiment,
        );
    }

    /**
     * Create an error query result.
     */
    public static function error(
        string $errorMessage,
        ?int $latencyMs = null,
        ?string $platform = null,
        ?string $queryText = null,
    ): self {
        return new self(
            success: false,
            responseText: null,
            model: null,
            tokensUsed: null,
            latencyMs: $latencyMs,
            errorMessage: $errorMessage,
            platform: $platform,
            queryText: $queryText,
            mentions: [],
            sentiment: null,
        );
    }

    /**
     * Create from JSON data.
     *
     * @throws \JsonException
     */
    public static function fromJson(string $json): self
    {
        $data = json_decode($json, true, flags: JSON_THROW_ON_ERROR);

        return new self(
            success: $data['success'],
            responseText: $data['responseText'] ?? null,
            model: $data['model'] ?? null,
            tokensUsed: $data['tokensUsed'] ?? null,
            latencyMs: $data['latencyMs'] ?? null,
            errorMessage: $data['errorMessage'] ?? null,
            platform: $data['platform'] ?? null,
            queryText: $data['queryText'] ?? null,
            mentions: $data['mentions'] ?? [],
            sentiment: $data['sentiment'] ?? null,
        );
    }

    /**
     * Convert to array for JSON serialization.
     */
    public function jsonSerialize(): array
    {
        return [
            'success' => $this->success,
            'responseText' => $this->responseText,
            'model' => $this->model,
            'tokensUsed' => $this->tokensUsed,
            'latencyMs' => $this->latencyMs,
            'errorMessage' => $this->errorMessage,
            'platform' => $this->platform,
            'queryText' => $this->queryText,
            'mentions' => $this->mentions,
            'sentiment' => $this->sentiment,
        ];
    }
}
