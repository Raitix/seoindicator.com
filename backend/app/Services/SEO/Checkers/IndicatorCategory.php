<?php

namespace App\Services\SEO\Checkers;

/**
 * Enum representing SEO indicator categories.
 *
 * @see TASK-18-3
 */
enum IndicatorCategory: string
{
    case CONTENT = 'content';
    case TECHNICAL = 'technical';
    case ACCESSIBILITY = 'accessibility';
    case PERFORMANCE = 'performance';

    /**
     * Get human-readable label for the category.
     */
    public function label(): string
    {
        return match ($this) {
            self::CONTENT => 'Content',
            self::TECHNICAL => 'Technical SEO',
            self::ACCESSIBILITY => 'Accessibility',
            self::PERFORMANCE => 'Performance',
        };
    }

    /**
     * Get description for the category.
     */
    public function description(): string
    {
        return match ($this) {
            self::CONTENT => 'Content-related SEO factors like titles, headings, and meta descriptions',
            self::TECHNICAL => 'Technical SEO factors like URL structure, canonicals, and robots.txt',
            self::ACCESSIBILITY => 'Accessibility factors like alt text and ARIA labels',
            self::PERFORMANCE => 'Performance factors like page speed and response times',
        };
    }

    /**
     * Get the icon name for the category (for frontend use).
     */
    public function icon(): string
    {
        return match ($this) {
            self::CONTENT => 'document-text',
            self::TECHNICAL => 'cog',
            self::ACCESSIBILITY => 'eye',
            self::PERFORMANCE => 'lightning-bolt',
        };
    }

    /**
     * Get the color for the category (for frontend use).
     */
    public function color(): string
    {
        return match ($this) {
            self::CONTENT => 'blue',
            self::TECHNICAL => 'purple',
            self::ACCESSIBILITY => 'green',
            self::PERFORMANCE => 'orange',
        };
    }

    /**
     * Try to create from a string value.
     * Returns null if the value is not a valid category.
     */
    public static function tryFromValue(string $value): ?self
    {
        return self::tryFrom($value);
    }

    /**
     * Create from a string value.
     * Throws InvalidArgumentException if the value is not a valid category.
     */
    public static function fromValue(string $value): self
    {
        $result = self::tryFrom($value);
        
        if ($result === null) {
            throw new \InvalidArgumentException(
                sprintf('Invalid category: %s. Must be one of: %s', $value, implode(', ', self::values()))
            );
        }
        
        return $result;
    }

    /**
     * Get all category values as an array.
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get all categories as an associative array.
     *
     * @return array<string, string>
     */
    public static function toArray(): array
    {
        $result = [];
        foreach (self::cases() as $case) {
            $result[$case->value] = $case->label();
        }
        return $result;
    }
}
