<?php

namespace App\Services\SEO;

/**
 * Immutable DTO representing the calculated SEO score result.
 *
 * @see TASK-18-7
 */
final readonly class ScoreResult
{
    /**
     * @param int $overallScore Overall score 0-100
     * @param array<string, int|null> $categoryScores Scores per category (content, technical, accessibility, performance)
     * @param array<string, array{score: int, weight: float}> $breakdown Breakdown by indicator
     * @param array{passed: int, warnings: int, failed: int} $summary Summary counts
     */
    public function __construct(
        public int $overallScore,
        public array $categoryScores,
        public array $breakdown,
        public array $summary
    ) {
        if ($overallScore < 0 || $overallScore > 100) {
            throw new \InvalidArgumentException('Overall score must be between 0 and 100');
        }
    }

    /**
     * Check if the overall score is good (80+).
     */
    public function isGoodScore(): bool
    {
        return $this->overallScore >= 80;
    }

    /**
     * Check if the overall score is moderate (60-79).
     */
    public function isModerateScore(): bool
    {
        return $this->overallScore >= 60 && $this->overallScore < 80;
    }

    /**
     * Check if the overall score is poor (0-59).
     */
    public function isPoorScore(): bool
    {
        return $this->overallScore < 60;
    }

    /**
     * Get human-readable score label.
     */
    public function getScoreLabel(): string
    {
        if ($this->isGoodScore()) {
            return 'Good';
        }
        if ($this->isModerateScore()) {
            return 'Moderate';
        }
        return 'Poor';
    }

    /**
     * Get score for a specific category.
     */
    public function getCategoryScore(string $category): ?int
    {
        return $this->categoryScores[$category] ?? null;
    }

    /**
     * Get the category with the lowest score.
     */
    public function getWorstCategory(): ?string
    {
        $validScores = array_filter($this->categoryScores, fn ($score) => $score !== null);
        if (empty($validScores)) {
            return null;
        }
        return array_keys($validScores, min($validScores))[0] ?? null;
    }

    /**
     * Get the category with the highest score.
     */
    public function getBestCategory(): ?string
    {
        $validScores = array_filter($this->categoryScores, fn ($score) => $score !== null);
        if (empty($validScores)) {
            return null;
        }
        return array_keys($validScores, max($validScores))[0] ?? null;
    }

    /**
     * Get total number of indicators.
     */
    public function getTotalIndicators(): int
    {
        return $this->summary['passed'] + $this->summary['warnings'] + $this->summary['failed'];
    }

    /**
     * Get pass rate percentage.
     */
    public function getPassRate(): float
    {
        $total = $this->getTotalIndicators();
        if ($total === 0) {
            return 0.0;
        }
        return round(($this->summary['passed'] / $total) * 100, 2);
    }

    /**
     * Convert to array representation.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'overallScore' => $this->overallScore,
            'categoryScores' => $this->categoryScores,
            'breakdown' => $this->breakdown,
            'summary' => $this->summary,
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
            overallScore: $data['overallScore'],
            categoryScores: $data['categoryScores'],
            breakdown: $data['breakdown'],
            summary: $data['summary']
        );
    }
}
