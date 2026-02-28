<?php

namespace App\Services\Analysis\Indicators;

use RuntimeException;
use DOMDocument;

/**
 * Images Indicator Processor.
 *
 * Analyzes images for SEO best practices including alt text, sizes, and lazy loading.
 */
class ImagesProcessor extends IndicatorProcessor
{
    public function slug(): string
    {
        return 'images';
    }

    public function name(): string
    {
        return 'Images Optimization';
    }

    public function maxScore(): float
    {
        return 100.0;
    }

    /**
     * Size threshold for large images (500KB).
     */
    protected int $largeImageThreshold = 512000;

    public function process(string $url): IndicatorResult
    {
        $startTime = microtime(true);

        try {
            $html = $this->fetchUrl($url);
            $dom = $this->parseHtml($html);

            $score = 0.0;
            $maxScore = $this->maxScore();
            $data = [];
            $recommendations = [];

            // Extract all images
            $images = $this->extractImages($dom);
            $data['total_count'] = count($images);

            if ($data['total_count'] === 0) {
                // No images - not necessarily bad
                $data['images'] = [];
                $data['summary'] = [
                    'with_alt' => 0,
                    'without_alt' => 0,
                    'with_lazy_loading' => 0,
                    'large_images' => 0,
                ];

                $processingTime = $this->calculateProcessingTime($startTime);

                return IndicatorResult::success(
                    slug: $this->slug(),
                    name: $this->name(),
                    score: 100.0, // Perfect score if no images
                    maxScore: $maxScore,
                    data: $data,
                    recommendations: [],
                    processingTimeMs: $processingTime,
                );
            }

            // Analyze alt text
            $altResult = $this->analyzeAltText($images);
            $score += $altResult['score'];
            $data['alt_analysis'] = $altResult['data'];
            $recommendations = array_merge($recommendations, $altResult['recommendations']);

            // Analyze lazy loading
            $lazyResult = $this->analyzeLazyLoading($images);
            $score += $lazyResult['score'];
            $data['lazy_loading_analysis'] = $lazyResult['data'];
            $recommendations = array_merge($recommendations, $lazyResult['recommendations']);

            // Analyze image formats
            $formatResult = $this->analyzeFormats($images);
            $score += $formatResult['score'];
            $data['format_analysis'] = $formatResult['data'];
            $recommendations = array_merge($recommendations, $formatResult['recommendations']);

            // Check for decorative images (empty alt)
            $decorativeResult = $this->analyzeDecorativeImages($images);
            $score += $decorativeResult['score'];
            $data['decorative_analysis'] = $decorativeResult['data'];

            // Summary
            $data['summary'] = [
                'with_alt' => $altResult['data']['with_alt'],
                'without_alt' => $altResult['data']['without_alt'],
                'with_lazy_loading' => $lazyResult['data']['count'],
                'large_images' => 0, // Would require HTTP HEAD requests to check
            ];

            // Store image details
            $data['images'] = array_map(function ($img) {
                return [
                    'src' => $img['src'],
                    'alt' => $img['alt'],
                    'has_alt' => !empty($img['alt']),
                    'lazy' => $img['lazy'],
                ];
            }, $images);

            $processingTime = $this->calculateProcessingTime($startTime);

            return IndicatorResult::success(
                slug: $this->slug(),
                name: $this->name(),
                score: min($score, $maxScore),
                maxScore: $maxScore,
                data: $data,
                recommendations: $recommendations,
                processingTimeMs: $processingTime,
            );

        } catch (RuntimeException $e) {
            $processingTime = $this->calculateProcessingTime($startTime);

            return IndicatorResult::failed(
                slug: $this->slug(),
                name: $this->name(),
                maxScore: $this->maxScore(),
                error: $e->getMessage(),
                processingTimeMs: $processingTime,
            );
        }
    }

    /**
     * Extract all images from the page.
     */
    protected function extractImages(DOMDocument $dom): array
    {
        $images = [];

        $nodes = $this->query($dom, 'img');
        foreach ($nodes as $node) {
            $src = $node->hasAttributes() ? ($node->attributes->getNamedItem('src')?->nodeValue ?? null) : null;
            if (!$src) {
                continue;
            }

            $images[] = [
                'src' => $src,
                'alt' => $node->hasAttributes() ? ($node->attributes->getNamedItem('alt')?->nodeValue ?? null) : null,
                'title' => $node->hasAttributes() ? ($node->attributes->getNamedItem('title')?->nodeValue ?? null) : null,
                'width' => $node->hasAttributes() ? ($node->attributes->getNamedItem('width')?->nodeValue ?? null) : null,
                'height' => $node->hasAttributes() ? ($node->attributes->getNamedItem('height')?->nodeValue ?? null) : null,
                'loading' => $node->hasAttributes() ? ($node->attributes->getNamedItem('loading')?->nodeValue ?? null) : null,
                'lazy' => $node->hasAttributes() && $node->attributes->getNamedItem('loading')?->nodeValue === 'lazy',
            ];
        }

        return $images;
    }

    /**
     * Analyze alt text presence and quality.
     */
    protected function analyzeAltText(array $images): array
    {
        $score = 0.0;
        $data = [];
        $recommendations = [];

        $withAlt = 0;
        $withoutAlt = [];
        $shortAlt = [];

        foreach ($images as $img) {
            if (!empty($img['alt'])) {
                $withAlt++;
                if (strlen($img['alt']) < 3) {
                    $shortAlt[] = $img['src'];
                }
            } else {
                $withoutAlt[] = [
                    'src' => $img['src'],
                ];
            }
        }

        $data['with_alt'] = $withAlt;
        $data['without_alt'] = count($withoutAlt);
        $data['without_alt_list'] = array_slice($withoutAlt, 0, 10); // Limit list size
        $data['alt_percentage'] = count($images) > 0 
            ? round(($withAlt / count($images)) * 100, 1)
            : 100;

        // Score calculation
        if (count($images) > 0) {
            $altRatio = $withAlt / count($images);
            $score = $altRatio * 60; // Max 60 points for alt text coverage

            // Bonus for perfect coverage (use abs comparison for float safety)
            if (abs($altRatio - 1.0) < 0.0001) {
                $score += 10; // Perfect alt text coverage bonus
            }

            if ($altRatio < 1) {
                $recommendations[] = $this->recommendation(
                    'medium',
                    'Some images missing alt text',
                    "{$data['without_alt']} of {$data['total_count']} images are missing alt text. Add descriptive alt text for accessibility and SEO."
                );
            }

            if (!empty($shortAlt)) {
                $recommendations[] = $this->recommendation(
                    'low',
                    'Some images have very short alt text',
                    count($shortAlt) . ' images have alt text shorter than 3 characters. Consider adding more descriptive text.'
                );
            }
        } else {
            $score = 70;
        }

        return [
            'score' => $score,
            'data' => $data,
            'recommendations' => $recommendations,
        ];
    }

    /**
     * Analyze lazy loading usage.
     */
    protected function analyzeLazyLoading(array $images): array
    {
        $score = 0.0;
        $data = [];
        $recommendations = [];

        $withLazy = 0;
        $withoutLazy = [];

        foreach ($images as $img) {
            if ($img['lazy']) {
                $withLazy++;
            } else {
                // Only flag non-first images as lazy loading candidates
                $withoutLazy[] = $img['src'];
            }
        }

        $data['count'] = $withLazy;
        $data['without_lazy_count'] = count($withoutLazy);
        $data['percentage'] = count($images) > 0 
            ? round(($withLazy / count($images)) * 100, 1)
            : 100;

        // Score calculation (max 20 points)
        if (count($images) > 0) {
            $lazyRatio = $withLazy / count($images);
            $score = $lazyRatio * 20;

            // Recommend lazy loading for images below the fold
            // (simplified: recommend for most images except possibly the first)
            if (count($images) > 1 && $withLazy === 0) {
                $recommendations[] = $this->recommendation(
                    'low',
                    'Consider adding lazy loading to images',
                    'Use loading="lazy" attribute on images below the fold to improve page load performance.'
                );
            }
        } else {
            $score = 20;
        }

        return [
            'score' => $score,
            'data' => $data,
            'recommendations' => $recommendations,
        ];
    }

    /**
     * Analyze image formats.
     */
    protected function analyzeFormats(array $images): array
    {
        $score = 0.0;
        $data = [];
        $recommendations = [];

        $modernFormats = ['webp', 'avif'];
        $legacyFormats = ['png', 'jpg', 'jpeg', 'gif'];
        $svgImages = 0;

        $formatCounts = [];

        foreach ($images as $img) {
            $extension = strtolower(pathinfo(parse_url($img['src'], PHP_URL_PATH) ?? '', PATHINFO_EXTENSION));
            
            if ($extension) {
                $formatCounts[$extension] = ($formatCounts[$extension] ?? 0) + 1;
            }

            if ($extension === 'svg') {
                $svgImages++;
            }
        }

        $data['formats'] = $formatCounts;
        $data['modern_format_count'] = 0;
        $data['legacy_format_count'] = 0;

        foreach ($formatCounts as $format => $count) {
            if (in_array($format, $modernFormats)) {
                $data['modern_format_count'] += $count;
            } elseif (in_array($format, $legacyFormats)) {
                $data['legacy_format_count'] += $count;
            }
        }

        // Score calculation (max 20 points)
        $total = $data['modern_format_count'] + $data['legacy_format_count'];
        if ($total > 0) {
            $modernRatio = $data['modern_format_count'] / $total;
            $score = $modernRatio * 20;

            if ($data['legacy_format_count'] > 0) {
                $recommendations[] = $this->recommendation(
                    'low',
                    'Consider using modern image formats',
                    "Found {$data['legacy_format_count']} images in legacy formats. Consider converting to WebP or AVIF for better compression."
                );
            }
        } else {
            $score = 10; // Neutral score if no detectable images
        }

        return [
            'score' => $score,
            'data' => $data,
            'recommendations' => $recommendations,
        ];
    }

    /**
     * Analyze decorative images (empty alt is valid for decorative).
     */
    protected function analyzeDecorativeImages(array $images): array
    {
        $score = 0.0;
        $data = [];

        $decorativeCount = 0;
        foreach ($images as $img) {
            if ($img['alt'] === '') {
                $decorativeCount++;
            }
        }

        $data['decorative_count'] = $decorativeCount;
        $data['decorative_percentage'] = count($images) > 0 
            ? round(($decorativeCount / count($images)) * 100, 1)
            : 0;

        // Small bonus for properly marking decorative images
        $score = min($decorativeCount * 0.5, 5);

        return [
            'score' => $score,
            'data' => $data,
            'recommendations' => [],
        ];
    }
}
