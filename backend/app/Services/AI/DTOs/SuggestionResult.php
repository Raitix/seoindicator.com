<?php

namespace App\Services\AI\DTOs;

use DateTimeInterface;
use JsonSerializable;

/**
 * Data Transfer Object for AI suggestion results.
 *
 * Contains structured SEO suggestions for a specific indicator,
 * including score, issues, recommendations, and best practices.
 *
 * @property-read string $indicator The indicator type (e.g., 'title', 'meta_description')
 * @property-read int $score Score from 0-100
 * @property-read array<int, string> $issues List of identified issues
 * @property-read array<int, array{type: string, text: string, reason?: string}> $suggestions List of suggestions
 * @property-read array<int, string> $bestPractices List of best practice tips
 * @property-read string $provider The provider name used
 * @property-read string $model The model name used
 * @property-read DateTimeInterface $generatedAt When the result was generated
 */
readonly class SuggestionResult implements JsonSerializable
{
    /**
     * Valid suggestion types.
     */
    private const VALID_TYPES = ['improve', 'rewrite', 'add', 'remove', 'warning'];

    /**
     * Create a new SuggestionResult instance.
     *
     * @param string $indicator The indicator type
     * @param int $score Score from 0-100
     * @param array<int, string> $issues List of identified issues
     * @param array<int, array{type: string, text: string, reason?: string}> $suggestions List of suggestions
     * @param array<int, string> $bestPractices List of best practice tips
     * @param string $provider The provider name
     * @param string $model The model name
     * @param DateTimeInterface $generatedAt When the result was generated
     * @throws \InvalidArgumentException If validation fails
     */
    public function __construct(
        public string $indicator,
        public int $score,
        public array $issues,
        public array $suggestions,
        public array $bestPractices,
        public string $provider,
        public string $model,
        public DateTimeInterface $generatedAt
    ) {
        $this->validateScore($score);
        $this->validateSuggestions($suggestions);
    }

    /**
     * Validate the score is within bounds.
     *
     * @param int $score
     * @throws \InvalidArgumentException
     */
    private function validateScore(int $score): void
    {
        if ($score < 0 || $score > 100) {
            throw new \InvalidArgumentException('score must be between 0 and 100');
        }
    }

    /**
     * Validate suggestion structures.
     *
     * @param array<int, array{type: string, text: string, reason?: string}> $suggestions
     * @throws \InvalidArgumentException
     */
    private function validateSuggestions(array $suggestions): void
    {
        foreach ($suggestions as $suggestion) {
            if (!isset($suggestion['type'])) {
                throw new \InvalidArgumentException('Each suggestion must have a "type" field');
            }

            if (!in_array($suggestion['type'], self::VALID_TYPES, true)) {
                throw new \InvalidArgumentException(
                    'Invalid suggestion type: ' . $suggestion['type'] . '. Valid types: ' . implode(', ', self::VALID_TYPES)
                );
            }

            if (!isset($suggestion['text'])) {
                throw new \InvalidArgumentException('Each suggestion must have a "text" field');
            }
        }
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

        $generatedAt = $data['generatedAt'];
        if (is_string($generatedAt)) {
            $generatedAt = new \DateTimeImmutable($generatedAt);
        }

        return new self(
            indicator: $data['indicator'],
            score: $data['score'],
            issues: $data['issues'] ?? [],
            suggestions: $data['suggestions'] ?? [],
            bestPractices: $data['bestPractices'] ?? [],
            provider: $data['provider'],
            model: $data['model'],
            generatedAt: $generatedAt
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
            'indicator' => $this->indicator,
            'score' => $this->score,
            'issues' => $this->issues,
            'suggestions' => $this->suggestions,
            'bestPractices' => $this->bestPractices,
            'provider' => $this->provider,
            'model' => $this->model,
            'generatedAt' => $this->generatedAt->format('c'),
        ];
    }
}
