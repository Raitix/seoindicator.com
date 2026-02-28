<?php

namespace App\Services\Analysis\Indicators;

use RuntimeException;
use DOMDocument;

/**
 * Headings Indicator Processor.
 *
 * Analyzes heading structure (H1-H6) for SEO best practices.
 */
class HeadingsProcessor extends IndicatorProcessor
{
    public function slug(): string
    {
        return 'headings';
    }

    public function name(): string
    {
        return 'Headings Structure';
    }

    public function maxScore(): float
    {
        return 100.0;
    }

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

            // Count all headings
            $headings = $this->extractHeadings($dom);
            $data['headings'] = $headings;
            $data['counts'] = $this->countHeadings($headings);

            // Analyze H1
            $h1Result = $this->analyzeH1($headings);
            $score += $h1Result['score'];
            $recommendations = array_merge($recommendations, $h1Result['recommendations']);
            $data['h1_analysis'] = $h1Result['data'];

            // Analyze heading hierarchy
            $hierarchyResult = $this->analyzeHierarchy($headings);
            $score += $hierarchyResult['score'];
            $recommendations = array_merge($recommendations, $hierarchyResult['recommendations']);
            $data['hierarchy_analysis'] = $hierarchyResult['data'];

            // Analyze heading content quality
            $contentResult = $this->analyzeHeadingContent($headings);
            $score += $contentResult['score'];
            $recommendations = array_merge($recommendations, $contentResult['recommendations']);
            $data['content_analysis'] = $contentResult['data'];

            // Generate outline
            $data['outline'] = $this->generateOutline($headings);

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
     * Extract all headings from the page.
     */
    protected function extractHeadings(DOMDocument $dom): array
    {
        $headings = [];
        $levels = ['h1', 'h2', 'h3', 'h4', 'h5', 'h6'];

        foreach ($levels as $level) {
            $nodes = $this->query($dom, $level);
            foreach ($nodes as $node) {
                $text = trim($node->textContent);
                $headings[] = [
                    'level' => (int) substr($level, 1),
                    'text' => $text,
                    'length' => strlen($text),
                ];
            }
        }

        // Sort by level to maintain document order (DOMNodeList is already in document order)
        usort($headings, function ($a, $b) {
            return $a['level'] <=> $b['level'];
        });

        return $headings;
    }

    /**
     * Count headings by level.
     */
    protected function countHeadings(array $headings): array
    {
        $counts = ['h1' => 0, 'h2' => 0, 'h3' => 0, 'h4' => 0, 'h5' => 0, 'h6' => 0, 'total' => 0];

        foreach ($headings as $heading) {
            $key = 'h' . $heading['level'];
            $counts[$key]++;
            $counts['total']++;
        }

        return $counts;
    }

    /**
     * Analyze H1 tag usage.
     */
    protected function analyzeH1(array $headings): array
    {
        $score = 0.0;
        $data = [];
        $recommendations = [];

        $h1s = array_filter($headings, fn($h) => $h['level'] === 1);
        $h1Count = count($h1s);

        $data['count'] = $h1Count;
        $data['texts'] = array_map(fn($h) => $h['text'], $h1s);

        if ($h1Count === 0) {
            $recommendations[] = $this->recommendation(
                'high',
                'Missing H1 tag',
                'Add a single H1 tag as the main heading of your page. This is critical for SEO.'
            );
        } elseif ($h1Count === 1) {
            $score += 40; // Perfect H1 usage

            // Check H1 content
            $h1 = reset($h1s);
            $data['length'] = $h1['length'];

            if ($h1['length'] > 0 && $h1['length'] < 20) {
                $recommendations[] = $this->recommendation(
                    'low',
                    'H1 tag is very short',
                    "H1 is only {$h1['length']} characters. Consider making it more descriptive."
                );
            } elseif ($h1['length'] > 70) {
                $recommendations[] = $this->recommendation(
                    'low',
                    'H1 tag is too long',
                    "H1 is {$h1['length']} characters. Consider keeping it under 70 characters for clarity."
                );
            }
        } else {
            $score += 10; // Partial credit for having H1s
            $recommendations[] = $this->recommendation(
                'high',
                'Multiple H1 tags found',
                "Found {$h1Count} H1 tags. Use only one H1 tag per page as the main heading."
            );
        }

        return [
            'score' => $score,
            'data' => $data,
            'recommendations' => $recommendations,
        ];
    }

    /**
     * Analyze heading hierarchy for proper nesting.
     */
    protected function analyzeHierarchy(array $headings): array
    {
        $score = 0.0;
        $data = [];
        $recommendations = [];

        if (empty($headings)) {
            $recommendations[] = $this->recommendation(
                'high',
                'No headings found',
                'Add heading tags to structure your content for better SEO and accessibility.'
            );

            return [
                'score' => $score,
                'data' => $data,
                'recommendations' => $recommendations,
            ];
        }

        $score += 20; // Points for having headings

        // Check for skipped levels
        $skippedLevels = [];
        $previousLevel = 0;

        foreach ($headings as $heading) {
            if ($previousLevel > 0 && $heading['level'] > $previousLevel + 1) {
                // Skipped one or more levels
                for ($i = $previousLevel + 1; $i < $heading['level']; $i++) {
                    $skippedLevels[] = [
                        'after' => $previousLevel,
                        'before' => $heading['level'],
                        'missing' => $i,
                    ];
                }
            }
            $previousLevel = $heading['level'];
        }

        $data['skipped_levels'] = $skippedLevels;
        $data['has_proper_hierarchy'] = empty($skippedLevels);

        if (!empty($skippedLevels)) {
            $score += 10;
            $recommendations[] = $this->recommendation(
                'medium',
                'Heading hierarchy has skipped levels',
                'Avoid skipping heading levels (e.g., H1 → H3). Maintain a logical hierarchy for better SEO and accessibility.'
            );
        } else {
            $score += 30;
        }

        return [
            'score' => $score,
            'data' => $data,
            'recommendations' => $recommendations,
        ];
    }

    /**
     * Analyze heading content quality.
     */
    protected function analyzeHeadingContent(array $headings): array
    {
        $score = 0.0;
        $data = [];
        $recommendations = [];

        if (empty($headings)) {
            return [
                'score' => $score,
                'data' => $data,
                'recommendations' => $recommendations,
            ];
        }

        // Check for empty headings
        $emptyHeadings = array_filter($headings, fn($h) => empty($h['text']));
        $data['empty_count'] = count($emptyHeadings);

        if ($data['empty_count'] > 0) {
            $recommendations[] = $this->recommendation(
                'medium',
                'Empty heading tags found',
                "Found {$data['empty_count']} empty heading tags. Remove or add content to them."
            );
        } else {
            $score += 10;
        }

        return [
            'score' => $score,
            'data' => $data,
            'recommendations' => $recommendations,
        ];
    }

    /**
     * Generate a heading outline for display.
     */
    protected function generateOutline(array $headings): array
    {
        $outline = [];

        foreach ($headings as $heading) {
            $outline[] = [
                'level' => $heading['level'],
                'text' => $heading['text'],
                'indent' => str_repeat('  ', $heading['level'] - 1),
            ];
        }

        return $outline;
    }
}
