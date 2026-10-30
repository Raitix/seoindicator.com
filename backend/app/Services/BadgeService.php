<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use InvalidArgumentException;

/**
 * Service for generating SVG badges with SEO scores.
 *
 * Generates badges in three sizes (small, medium, large) with
 * score color coding, theme support, and caching.
 *
 * @see TASK-33-6
 * @see SPEC-033
 */
class BadgeService
{
    /**
     * Valid size options with dimensions.
     */
    private const SIZES = [
        'small' => ['width' => 120, 'height' => 40],
        'medium' => ['width' => 150, 'height' => 100],
        'large' => ['width' => 300, 'height' => 150],
    ];

    /**
     * Valid theme options with colors.
     */
    private const THEMES = [
        'light' => ['bg' => '#ffffff', 'text' => '#1f2937', 'secondary' => '#6b7280'],
        'dark' => ['bg' => '#1f2937', 'text' => '#f9fafb', 'secondary' => '#9ca3af'],
    ];

    /**
     * Score color ranges.
     */
    private const SCORE_COLORS = [
        ['min' => 90, 'max' => 100, 'color' => '#22c55e'],
        ['min' => 70, 'max' => 89, 'color' => '#3b82f6'],
        ['min' => 50, 'max' => 69, 'color' => '#eab308'],
        ['min' => 30, 'max' => 49, 'color' => '#f97316'],
        ['min' => 0, 'max' => 29, 'color' => '#ef4444'],
    ];

    /**
     * Cache TTL in seconds (1 hour).
     */
    private const CACHE_TTL = 3600;

    /**
     * Generate an SVG badge for the given score.
     *
     * @param int $score SEO score (0-100)
     * @param string $size Badge size: 'small', 'medium', 'large'
     * @param string|null $theme Theme: 'light' or 'dark' (default: 'light')
     * @param array|null $categoryScores Category breakdown for large badge
     * @param bool $forceFresh Bypass cache and regenerate
     * @return string SVG markup
     * @throws InvalidArgumentException If parameters are invalid
     */
    public function generate(
        int $score,
        string $size,
        ?string $theme = null,
        ?array $categoryScores = null,
        bool $forceFresh = false
    ): string {
        // Validate inputs
        $this->validateScore($score);
        $size = $this->validateSize($size);
        $theme = $this->validateTheme($theme);

        // Check cache
        $cacheKey = $this->getCacheKey($score, $size, $theme);
        
        if (!$forceFresh && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        // Generate SVG
        $svg = $this->buildSvg($score, $size, $theme, $categoryScores);

        // Cache the result
        Cache::put($cacheKey, $svg, self::CACHE_TTL);

        return $svg;
    }

    /**
     * Validate score is within bounds.
     */
    private function validateScore(int $score): void
    {
        if ($score < 0 || $score > 100) {
            throw new InvalidArgumentException('Score must be between 0 and 100');
        }
    }

    /**
     * Validate size (case-sensitive).
     */
    private function validateSize(string $size): string
    {
        if (!isset(self::SIZES[$size])) {
            throw new InvalidArgumentException('Invalid size. Must be: small, medium, or large');
        }

        return $size;
    }

    /**
     * Validate theme (case-sensitive).
     */
    private function validateTheme(?string $theme): string
    {
        $theme = $theme ?? 'light';

        if (!isset(self::THEMES[$theme])) {
            throw new InvalidArgumentException('Invalid theme. Must be: light or dark');
        }

        return $theme;
    }

    /**
     * Get color for score range.
     */
    private function getScoreColor(int $score): string
    {
        foreach (self::SCORE_COLORS as $range) {
            if ($score >= $range['min'] && $score <= $range['max']) {
                return $range['color'];
            }
        }

        return '#ef4444'; // Default to red
    }

    /**
     * Generate cache key.
     */
    private function getCacheKey(int $score, string $size, string $theme): string
    {
        $hash = md5($score . $size . $theme);
        return "badge:{$hash}:{$size}:{$theme}";
    }

    /**
     * Build the SVG markup.
     */
    private function buildSvg(
        int $score,
        string $size,
        string $theme,
        ?array $categoryScores
    ): string {
        $dimensions = self::SIZES[$size];
        $colors = self::THEMES[$theme];
        $scoreColor = $this->getScoreColor($score);

        $width = $dimensions['width'];
        $height = $dimensions['height'];

        $svg = $this->getSvgHeader($width, $height, $score);
        $svg .= $this->getSvgBackground($width, $height, $colors['bg']);
        $svg .= $this->getScoreElement($score, $scoreColor, $colors, $size);

        if ($size === 'large' && $categoryScores) {
            $svg .= $this->getCategoryBreakdown($categoryScores, $colors, $width, $height);
        }

        $svg .= $this->getBranding($colors, $size, $width, $height);
        $svg .= '</svg>';

        return $svg;
    }

    /**
     * Get SVG header with accessibility attributes.
     */
    private function getSvgHeader(int $width, int $height, int $score): string
    {
        $ariaLabel = "SEO Score: {$score} out of 100";
        
        return sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg" width="%d" height="%d" viewBox="0 0 %d %d" role="img" aria-label="%s">',
            $width,
            $height,
            $width,
            $height,
            $ariaLabel
        );
    }

    /**
     * Get background rectangle.
     */
    private function getSvgBackground(int $width, int $height, string $bgColor): string
    {
        return sprintf(
            '<rect width="%d" height="%d" fill="%s" rx="6"/>',
            $width,
            $height,
            $bgColor
        );
    }

    /**
     * Get score display element.
     */
    private function getScoreElement(
        int $score,
        string $scoreColor,
        array $colors,
        string $size
    ): string {
        $positions = [
            'small' => ['x' => 10, 'y' => 26, 'fontSize' => 20],
            'medium' => ['x' => 15, 'y' => 55, 'fontSize' => 36],
            'large' => ['x' => 20, 'y' => 60, 'fontSize' => 48],
        ];

        $pos = $positions[$size];

        return sprintf(
            '<text x="%d" y="%d" font-family="Arial, sans-serif" font-size="%d" font-weight="bold" fill="%s">%d</text>',
            $pos['x'],
            $pos['y'],
            $pos['fontSize'],
            $scoreColor,
            $score
        );
    }

    /**
     * Get category breakdown for large badge.
     */
    private function getCategoryBreakdown(
        array $categoryScores,
        array $colors,
        int $width,
        int $height
    ): string {
        $categories = [
            'content' => 'Content',
            'technical' => 'Technical',
            'accessibility' => 'Accessibility',
            'performance' => 'Performance',
        ];

        $y = 85;
        $x = 20;
        $svg = '';

        foreach ($categories as $key => $label) {
            if (!isset($categoryScores[$key])) {
                continue;
            }

            $score = $categoryScores[$key];
            $color = $this->getScoreColor($score);

            $svg .= sprintf(
                '<text x="%d" y="%d" font-family="Arial, sans-serif" font-size="12" fill="%s">%s:</text>',
                $x,
                $y,
                $colors['secondary'],
                $label
            );

            $svg .= sprintf(
                '<text x="%d" y="%d" font-family="Arial, sans-serif" font-size="12" font-weight="bold" fill="%s">%d</text>',
                $x + 90,
                $y,
                $color,
                $score
            );

            $y += 18;
        }

        return $svg;
    }

    /**
     * Get branding text.
     */
    private function getBranding(array $colors, string $size, int $width, int $height): string
    {
        $positions = [
            'small' => ['x' => 50, 'y' => 26, 'fontSize' => 10],
            'medium' => ['x' => 15, 'y' => 85, 'fontSize' => 11],
            'large' => ['x' => 180, 'y' => 60, 'fontSize' => 14],
        ];

        $pos = $positions[$size];

        return sprintf(
            '<text x="%d" y="%d" font-family="Arial, sans-serif" font-size="%d" fill="%s">SEOIndicator</text>',
            $pos['x'],
            $pos['y'],
            $pos['fontSize'],
            $colors['secondary']
        );
    }
}
