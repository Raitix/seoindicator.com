<?php

namespace App\Services\SEO\Checkers;

/**
 * Enum representing SEO indicator score levels.
 *
 * Uses a 1-3 star system:
 * - 3 = Excellent (all criteria met)
 * - 2 = Good (minor issues)
 * - 1 = Poor (significant issues)
 * - 0 = N/A (indicator not applicable)
 *
 * @see TASK-18-3
 */
enum IndicatorScore: int
{
    case NOT_APPLICABLE = 0;
    case POOR = 1;
    case GOOD = 2;
    case EXCELLENT = 3;

    /**
     * Check if this score represents a passing result.
     * Scores 2 and 3 are considered passing.
     */
    public function isPassing(): bool
    {
        return $this === self::GOOD || $this === self::EXCELLENT;
    }

    /**
     * Get human-readable label for the score.
     */
    public function label(): string
    {
        return match ($this) {
            self::NOT_APPLICABLE => 'N/A',
            self::POOR => 'Poor',
            self::GOOD => 'Good',
            self::EXCELLENT => 'Excellent',
        };
    }

    /**
     * Get a description of what this score means.
     */
    public function description(): string
    {
        return match ($this) {
            self::NOT_APPLICABLE => 'This indicator is not applicable to the analyzed page',
            self::POOR => 'Significant issues detected that need immediate attention',
            self::GOOD => 'Minor issues detected with room for improvement',
            self::EXCELLENT => 'All criteria met, no issues detected',
        };
    }

    /**
     * Get the color for this score (for frontend use).
     */
    public function color(): string
    {
        return match ($this) {
            self::NOT_APPLICABLE => 'gray',
            self::POOR => 'red',
            self::GOOD => 'yellow',
            self::EXCELLENT => 'green',
        };
    }

    /**
     * Get the number of stars (for display).
     */
    public function stars(): int
    {
        return $this->value;
    }

    /**
     * Check if a raw integer value is a valid score.
     */
    public static function isValid(int $value): bool
    {
        return $value >= 0 && $value <= 3;
    }

    /**
     * Try to create from an integer value.
     * Returns null if the value is not a valid score.
     */
    public static function tryFromInt(int $value): ?self
    {
        return self::tryFrom($value);
    }

    /**
     * Create from an integer value.
     * Throws InvalidArgumentException if the value is not a valid score.
     */
    public static function fromInt(int $value): self
    {
        $result = self::tryFrom($value);
        
        if ($result === null) {
            throw new \InvalidArgumentException(
                sprintf('Invalid score: %d. Must be between 0 and 3', $value)
            );
        }
        
        return $result;
    }

    /**
     * Get all score values as an array.
     *
     * @return array<int>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get human-readable label for a raw score value.
     */
    public static function getLabel(int $value): string
    {
        return self::fromInt($value)->label();
    }

    /**
     * Compare two scores.
     * Returns: -1 if this < other, 0 if equal, 1 if this > other
     */
    public function compare(self $other): int
    {
        return $this->value <=> $other->value;
    }

    /**
     * Check if this score is better than another.
     */
    public function isBetterThan(self $other): bool
    {
        return $this->value > $other->value;
    }

    /**
     * Check if this score is worse than another.
     */
    public function isWorseThan(self $other): bool
    {
        return $this->value < $other->value;
    }
}
