<?php

namespace App\Services\SEO\Checkers;

/**
 * Immutable DTO representing the result of an SEO indicator check.
 *
 * @see TASK-18-3
 */
final readonly class IndicatorResult
{
    /**
     * @param string $id Unique identifier for the indicator (e.g., 'title_tag', 'meta_description')
     * @param string $name Human-readable name
     * @param string $category Category: 'content', 'technical', 'accessibility', 'performance'
     * @param int $score Score 0-3 (0=N/A, 1=Poor, 2=Good, 3=Excellent)
     * @param float $weight Weight 0.0-1.0 for scoring calculation
     * @param bool $passed Whether the indicator passed its check
     * @param array<string, mixed> $details Additional details about the check
     */
    public function __construct(
        public string $id,
        public string $name,
        public string $category,
        public int $score,
        public float $weight,
        public bool $passed,
        public array $details = []
    ) {
        if ($score < 0 || $score > 3) {
            throw new \InvalidArgumentException('Score must be between 0 and 3');
        }

        if ($weight < 0.0 || $weight > 1.0) {
            throw new \InvalidArgumentException('Weight must be between 0.0 and 1.0');
        }
    }

    /**
     * Check if this is an excellent result (score = 3).
     */
    public function isExcellent(): bool
    {
        return $this->score === 3;
    }

    /**
     * Check if this is a good result (score = 2).
     */
    public function isGood(): bool
    {
        return $this->score === 2;
    }

    /**
     * Check if this is a poor result (score = 1).
     */
    public function isPoor(): bool
    {
        return $this->score === 1;
    }

    /**
     * Check if this indicator is not applicable (score = 0).
     */
    public function isNotApplicable(): bool
    {
        return $this->score === 0;
    }

    /**
     * Get human-readable score label.
     */
    public function getScoreLabel(): string
    {
        return match ($this->score) {
            0 => 'N/A',
            1 => 'Poor',
            2 => 'Good',
            3 => 'Excellent',
            default => throw new \LogicException('Invalid score')
        };
    }

    /**
     * Convert to array representation.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'category' => $this->category,
            'score' => $this->score,
            'weight' => $this->weight,
            'passed' => $this->passed,
            'details' => $this->details,
        ];
    }

    /**
     * Convert to JSON string.
     */
    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_THROW_ON_ERROR);
    }

    /**
     * Create from array representation.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            name: $data['name'],
            category: $data['category'],
            score: $data['score'],
            weight: $data['weight'],
            passed: $data['passed'],
            details: $data['details'] ?? []
        );
    }
}
