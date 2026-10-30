<?php

namespace App\Services\Audit\Checkers;

use App\Services\Audit\DTOs\IssueFinding;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Checker for image alt text SEO issues.
 *
 * Detection Rules:
 * - Missing alt attribute → Notice (accessibility issue)
 * - Empty alt="" attribute → Notice (decorative, but flagged for review)
 * - Alt text > 125 chars → Notice (too long for screen readers)
 * - Empty src attribute → Notice (broken image)
 *
 * @see TASK-14-12
 */
class ImageAltChecker extends BaseChecker
{
    /**
     * Issue type identifier.
     *
     * @var string
     */
    protected string $issueType = 'missing_image_alt';

    /**
     * Checker name.
     *
     * @var string
     */
    protected string $name = 'image_alt_checker';

    /**
     * Checker priority.
     *
     * @var int
     */
    protected int $priority = 0;

    /**
     * Maximum number of image src values to include in context.
     *
     * @var int
     */
    protected int $maxContextImages = 50;

    /**
     * Maximum URL length to store in context.
     *
     * @var int
     */
    protected int $maxSrcLength = 500;

    /**
     * Check a crawl result for image alt text issues.
     *
     * @param mixed $result The crawl result to check
     * @return IssueFinding|null Returns an issue finding if detected, null otherwise
     */
    public function check($result): ?IssueFinding
    {
        // Handle null result
        if ($result === null) {
            return null;
        }

        // Get HTML content
        $html = $result->html ?? null;

        // Handle null, empty, or whitespace-only HTML
        if (empty($html) || trim($html) === '') {
            return null;
        }

        // Get configuration values
        $maxAltLength = config('audit.images.max_alt_length', 125);
        $reportEmptyAlt = config('audit.images.report_empty_alt', true);

        // Parse HTML and extract images
        $crawler = new Crawler($html);
        $rawImages = $this->extractImages($crawler);

        // No images found
        if (empty($rawImages)) {
            return null;
        }

        // Analyze images
        $analysis = $this->analyzeImages($rawImages, $maxAltLength);

        // Check if there are any issues
        $hasIssues = $analysis['missing_alt_count'] > 0
            || ($reportEmptyAlt && $analysis['empty_alt_count'] > 0)
            || $analysis['too_long_count'] > 0
            || $analysis['empty_src_count'] > 0;

        if (!$hasIssues) {
            return null;
        }

        // Build context
        $context = $this->buildContext($analysis);

        // Build description
        $description = $this->buildDescription($analysis);

        // Build recommendation
        $recommendation = $this->buildRecommendation($analysis);

        return $this->createFinding(
            issueType: $this->issueType,
            severity: 'notice',
            description: $description,
            recommendation: $recommendation,
            context: $context
        );
    }

    /**
     * Analyze images for alt text issues.
     *
     * @param array $images The extracted images
     * @param int $maxAltLength Maximum allowed alt text length
     * @return array Analysis results
     */
    protected function analyzeImages(array $images, int $maxAltLength): array
    {
        $analysis = [
            'total_images' => count($images),
            'missing_alt' => [],
            'empty_alt' => [],
            'too_long' => [],
            'empty_src' => [],
            'missing_alt_count' => 0,
            'empty_alt_count' => 0,
            'too_long_count' => 0,
            'empty_src_count' => 0,
        ];

        foreach ($images as $image) {
            $src = $image['src'] ?? null;
            $alt = $image['alt'] ?? null;

            // Track empty src
            if (empty($src)) {
                $analysis['empty_src_count']++;
                $analysis['empty_src'][] = $image;
                continue; // Skip other checks for images without src
            }

            // Check alt attribute
            if ($alt === null) {
                // Missing alt attribute
                $analysis['missing_alt_count']++;
                $analysis['missing_alt'][] = $this->truncateSrc($src);
            } elseif ($alt === '') {
                // Empty alt (decorative)
                $analysis['empty_alt_count']++;
                $analysis['empty_alt'][] = $this->truncateSrc($src);
            } elseif (strlen($alt) > $maxAltLength) {
                // Alt text too long
                $analysis['too_long_count']++;
                $analysis['too_long'][] = [
                    'src' => $this->truncateSrc($src),
                    'alt_length' => strlen($alt),
                ];
            }
        }

        return $analysis;
    }

    /**
     * Build the context array for the issue finding.
     *
     * @param array $analysis The image analysis results
     * @return array The context array
     */
    protected function buildContext(array $analysis): array
    {
        // Truncate images array to max size
        $missingAltImages = array_slice($analysis['missing_alt'], 0, $this->maxContextImages);

        $context = [
            'images' => $missingAltImages,
            'count' => $analysis['missing_alt_count'],
            'empty_alt_count' => $analysis['empty_alt_count'],
            'too_long_count' => $analysis['too_long_count'],
            'total_images' => $analysis['total_images'],
            'empty_src_count' => $analysis['empty_src_count'],
        ];

        // Add too_long_images if present
        if (!empty($analysis['too_long'])) {
            $context['too_long_images'] = array_slice($analysis['too_long'], 0, $this->maxContextImages);
        }

        return $context;
    }

    /**
     * Build the issue description.
     *
     * @param array $analysis The image analysis results
     * @return string The description
     */
    protected function buildDescription(array $analysis): string
    {
        $parts = [];

        if ($analysis['missing_alt_count'] > 0) {
            $parts[] = "{$analysis['missing_alt_count']} image(s) missing alt text";
        }

        if ($analysis['empty_alt_count'] > 0) {
            $parts[] = "{$analysis['empty_alt_count']} image(s) with empty alt text";
        }

        if ($analysis['too_long_count'] > 0) {
            $parts[] = "{$analysis['too_long_count']} image(s) with alt text exceeding 125 characters";
        }

        if ($analysis['empty_src_count'] > 0) {
            $parts[] = "{$analysis['empty_src_count']} image(s) with empty src attribute";
        }

        return ucfirst(implode(', ', $parts)) . '.';
    }

    /**
     * Build the recommendation text.
     *
     * @param array $analysis The image analysis results
     * @return string The recommendation
     */
    protected function buildRecommendation(array $analysis): string
    {
        $recommendations = [];

        if ($analysis['missing_alt_count'] > 0) {
            $recommendations[] = 'Add descriptive alt text to images missing the alt attribute. Alt text improves accessibility for screen readers and helps search engines understand image content.';
        }

        if ($analysis['empty_alt_count'] > 0) {
            $recommendations[] = 'Review images with empty alt text (alt=""). Empty alt is acceptable for decorative images, but meaningful images should have descriptive alt text.';
        }

        if ($analysis['too_long_count'] > 0) {
            $recommendations[] = 'Consider shortening alt text to 125 characters or less. Screen readers may truncate longer descriptions.';
        }

        if ($analysis['empty_src_count'] > 0) {
            $recommendations[] = 'Fix images with empty src attributes to ensure they display correctly.';
        }

        return implode(' ', $recommendations);
    }

    /**
     * Truncate a source URL if it's too long.
     *
     * @param string $src The source URL
     * @return string The truncated URL
     */
    protected function truncateSrc(string $src): string
    {
        // Remove control characters
        $src = preg_replace('/[\x00-\x1F\x7F]/u', '', $src);

        if (strlen($src) <= $this->maxSrcLength) {
            return $src;
        }

        return substr($src, 0, $this->maxSrcLength) . '...';
    }

    /**
     * Get the issue type this checker detects.
     *
     * @return string
     */
    public function getIssueType(): string
    {
        return $this->issueType;
    }

    /**
     * Get the checker name.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get the checker priority.
     *
     * @return int
     */
    public function getPriority(): int
    {
        return $this->priority;
    }
}
