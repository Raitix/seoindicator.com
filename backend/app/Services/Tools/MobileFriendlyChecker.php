<?php

namespace App\Services\Tools;

use App\Services\HtmlParserService;
use App\Services\URLValidationService;

/**
 * Mobile-Friendly Checker Tool.
 * 
 * Analyzes a page's mobile readiness by checking:
 * - Viewport meta tag presence and configuration
 * - Responsive design indicators (media queries, flexible grids)
 * - Font sizes for mobile readability
 * - Tap target sizes (links, buttons)
 * - Horizontal scroll issues
 * 
 * TASK-16-7: Mobile-Friendly Checker Tool
 */
class MobileFriendlyChecker extends SEOTool
{
    /**
     * Minimum tap target size (48x48 pixels recommended by Google).
     */
    private const MIN_TAP_TARGET_SIZE = 48;

    /**
     * Minimum readable font size.
     */
    private const MIN_FONT_SIZE = 14;

    /**
     * Mobile viewport width.
     */
    private const MOBILE_VIEWPORT_WIDTH = 320;

    /**
     * Score weights.
     */
    private const VIEWPORT_WEIGHT = 30;
    private const RESPONSIVE_WEIGHT = 25;
    private const FONT_SIZE_WEIGHT = 20;
    private const TAP_TARGET_WEIGHT = 25;

    protected string $name = 'mobile-friendly';
    protected string $description = 'Analyzes mobile-friendliness including viewport, responsive design, and tap targets';

    public function __construct(
        URLValidationService $urlValidator,
        HtmlParserService $htmlParser
    ) {
        parent::__construct($urlValidator, $htmlParser);
    }

    /**
     * Analyze a URL for mobile-friendliness.
     *
     * @param string $url URL to analyze
     * @param array $options Additional options
     * @return ToolResult
     */
    public function analyze(string $url, array $options = []): ToolResult
    {
        // Fetch and parse the URL
        $result = $this->fetchAndParse($url);

        if (!$result['success']) {
            $errorCode = $result['errorCode'] ?? 'FETCH_ERROR';
            return $this->error($result['error'], $url, $errorCode);
        }

        $parsed = $result['parsed'];
        $html = $result['html'];
        $finalUrl = $result['finalUrl'];

        // Analyze mobile-friendliness
        $analysis = $this->analyzeMobileFriendliness($parsed, $html, $finalUrl);

        return $this->success($analysis, $url);
    }

    /**
     * Perform the mobile-friendliness analysis.
     *
     * @param array $parsed Parsed HTML data
     * @param string $html Raw HTML content
     * @param string $finalUrl Final URL after redirects
     * @return array
     */
    private function analyzeMobileFriendliness(array $parsed, string $html, string $finalUrl): array
    {
        // Analyze viewport
        $viewportAnalysis = $this->analyzeViewport($parsed, $html);

        // Analyze responsive indicators
        $responsiveAnalysis = $this->analyzeResponsiveIndicators($html);

        // Analyze font sizes
        $fontAnalysis = $this->analyzeFontSizes($html);

        // Analyze tap targets
        $tapTargetAnalysis = $this->analyzeTapTargets($html, $parsed);

        // Check for horizontal scroll issues
        $horizontalScrollAnalysis = $this->analyzeHorizontalScroll($html);

        // Generate issues and recommendations
        $issues = $this->generateIssues(
            $viewportAnalysis,
            $responsiveAnalysis,
            $fontAnalysis,
            $tapTargetAnalysis,
            $horizontalScrollAnalysis
        );

        $recommendations = $this->generateRecommendations(
            $viewportAnalysis,
            $responsiveAnalysis,
            $fontAnalysis,
            $tapTargetAnalysis
        );

        // Calculate mobile score
        $mobileScore = $this->calculateMobileScore(
            $viewportAnalysis,
            $responsiveAnalysis,
            $fontAnalysis,
            $tapTargetAnalysis
        );

        return [
            'mobileScore' => $mobileScore,
            'viewport' => $viewportAnalysis,
            'responsiveIndicators' => $responsiveAnalysis,
            'readability' => $fontAnalysis,
            'tapTargets' => $tapTargetAnalysis,
            'horizontalScroll' => $horizontalScrollAnalysis,
            'issues' => $issues,
            'recommendations' => $recommendations,
            'previewUrl' => $finalUrl,
        ];
    }

    /**
     * Analyze viewport meta tag.
     */
    private function analyzeViewport(array $parsed, string $html = ''): array
    {
        $viewportContent = $parsed['viewport'] ?? null;

        // Check if viewport meta tag exists in HTML (even with empty content)
        $viewportTagExists = $viewportContent !== null 
            || preg_match('/<meta[^>]+name\s*=\s*["\']?viewport["\']?[^>]*>/i', $html);

        $analysis = [
            'present' => $viewportTagExists,
            'content' => $viewportContent,
            'valid' => false,
            'hasDeviceWidth' => false,
            'hasInitialScale' => false,
            'userScalable' => true,
            'maxScale' => null,
            'hasFixedWidth' => false,
        ];

        if (!$viewportContent) {
            return $analysis;
        }

        $content = strtolower($viewportContent);

        // Check for device-width
        $analysis['hasDeviceWidth'] = str_contains($content, 'width=device-width')
            || str_contains($content, 'width = device-width');

        // Check for fixed width viewport (e.g., width=960)
        if (preg_match('/width\s*=\s*(\d+)/', $content, $matches)) {
            $width = (int) $matches[1];
            if ($width > 0 && !$analysis['hasDeviceWidth']) {
                $analysis['hasFixedWidth'] = true;
            }
        }

        // Check for initial-scale
        $analysis['hasInitialScale'] = str_contains($content, 'initial-scale');

        // Valid viewport has device-width or initial-scale
        $analysis['valid'] = $analysis['hasDeviceWidth'] || $analysis['hasInitialScale'];

        // Check for accessibility issues
        if (str_contains($content, 'user-scalable=no')) {
            $analysis['userScalable'] = false;
        }

        // Extract maximum-scale (note: attribute is "maximum-scale", not "max-scale")
        if (preg_match('/maximum-scale\s*=\s*([\d.]+)/', $content, $matches)) {
            $analysis['maxScale'] = (float) $matches[1];
        }

        return $analysis;
    }

    /**
     * Analyze responsive design indicators in CSS.
     */
    private function analyzeResponsiveIndicators(string $html): array
    {
        $analysis = [
            'hasMediaQueries' => false,
            'hasFlexibleGrid' => false,
            'hasFlexibleImages' => false,
            'mediaQueryCount' => 0,
        ];

        $htmlLower = strtolower($html);

        // Check for media queries
        $analysis['hasMediaQueries'] = (bool) preg_match('/@media\s*\(/i', $html);
        if ($analysis['hasMediaQueries']) {
            preg_match_all('/@media\s*\(/i', $html, $matches);
            $analysis['mediaQueryCount'] = count($matches[0] ?? []);
        }

        // Check for flexible grids (flexbox, grid, percentage widths)
        $analysis['hasFlexibleGrid'] = (bool) preg_match('/display\s*:\s*(flex|grid)/i', $html)
            || (bool) preg_match('/width\s*:\s*\d+%/i', $html);

        // Check for responsive images
        $analysis['hasFlexibleImages'] = (bool) preg_match('/max-width\s*:\s*100%/i', $html)
            || (bool) preg_match('/height\s*:\s*auto/i', $html);

        return $analysis;
    }

    /**
     * Analyze font sizes for mobile readability.
     */
    private function analyzeFontSizes(string $html): array
    {
        $analysis = [
            'fontSizeMin' => null,
            'fontSizeWarning' => false,
            'smallFontCount' => 0,
        ];

        // Extract font-size declarations
        preg_match_all('/font-size\s*:\s*([\d.]+)(px|pt|rem|em)/i', $html, $matches, PREG_SET_ORDER);

        $fontSizes = [];
        foreach ($matches as $match) {
            $size = (float) $match[1];
            $unit = strtolower($match[2]);

            // Convert to pixels for comparison
            $pxSize = match ($unit) {
                'rem' => $size * 16,
                'em' => $size * 16, // Assuming base size of 16px
                'pt' => $size * 1.333,
                default => $size,
            };

            $fontSizes[] = $pxSize;
        }

        if (!empty($fontSizes)) {
            $analysis['fontSizeMin'] = (int) min($fontSizes);
            $analysis['fontSizeWarning'] = $analysis['fontSizeMin'] < self::MIN_FONT_SIZE;

            foreach ($fontSizes as $size) {
                if ($size < self::MIN_FONT_SIZE) {
                    $analysis['smallFontCount']++;
                }
            }
        }

        return $analysis;
    }

    /**
     * Analyze tap target sizes.
     */
    private function analyzeTapTargets(string $html, array $parsed): array
    {
        $analysis = [
            'links' => 0,
            'buttons' => 0,
            'smallTargets' => 0,
            'warning' => null,
        ];

        // Count links (from parsed data or regex)
        $analysis['links'] = $parsed['linkCount'] ?? preg_match_all('/<a\s[^>]*href/i', $html, $matches);

        // Count buttons
        preg_match_all('/<button[^>]*>/i', $html, $buttonMatches);
        preg_match_all('/<input[^>]*type\s*=\s*["\']?(submit|button|reset)["\']?[^>]*>/i', $html, $inputMatches);
        $analysis['buttons'] = count($buttonMatches[0] ?? []) + count($inputMatches[0] ?? []);

        // Check for small tap targets in CSS
        // This is a simplified check - real implementation would parse computed styles
        $smallSizePattern = '/(width|height)\s*:\s*([123]?\d)px/i';
        preg_match_all($smallSizePattern, $html, $smallMatches);
        
        $potentialSmallTargets = 0;
        foreach ($smallMatches[2] ?? [] as $size) {
            if ((int) $size < self::MIN_TAP_TARGET_SIZE) {
                $potentialSmallTargets++;
            }
        }

        // Estimate small targets (approximation)
        $analysis['smallTargets'] = min($potentialSmallTargets, $analysis['links'] + $analysis['buttons']);

        if ($analysis['smallTargets'] > 0) {
            $analysis['warning'] = "{$analysis['smallTargets']} tap target(s) may be too small for mobile";
        }

        return $analysis;
    }

    /**
     * Check for horizontal scroll issues.
     */
    private function analyzeHorizontalScroll(string $html): array
    {
        $analysis = [
            'detected' => false,
            'cause' => null,
        ];

        // Check for fixed-width elements wider than viewport
        if (preg_match('/width\s*:\s*([89]\d{2}|\d{4,})px/i', $html, $matches)) {
            $width = (int) $matches[1];
            if ($width > self::MOBILE_VIEWPORT_WIDTH) {
                $analysis['detected'] = true;
                $analysis['cause'] = "Element with fixed width of {$width}px";
                return $analysis;
            }
        }

        // Check for wide tables
        if (preg_match('/<table[^>]*width\s*=\s*["\']?(\d{3,})/i', $html, $matches)) {
            $width = (int) $matches[1];
            if ($width > self::MOBILE_VIEWPORT_WIDTH) {
                $analysis['detected'] = true;
                $analysis['cause'] = "Table with width of {$width}px";
                return $analysis;
            }
        }

        // Check for wide images
        if (preg_match('/<img[^>]*width\s*=\s*["\']?(\d{3,})/i', $html, $matches)) {
            $width = (int) $matches[1];
            if ($width > self::MOBILE_VIEWPORT_WIDTH) {
                $analysis['detected'] = true;
                $analysis['cause'] = "Image with width of {$width}px";
                return $analysis;
            }
        }

        return $analysis;
    }

    /**
     * Generate issues list.
     */
    private function generateIssues(
        array $viewport,
        array $responsive,
        array $fonts,
        array $tapTargets,
        array $horizontalScroll
    ): array {
        $issues = [];

        // Viewport issues
        if (!$viewport['present']) {
            $issues[] = [
                'severity' => 'critical',
                'message' => 'Missing viewport meta tag. This is essential for mobile devices.',
                'category' => 'viewport',
            ];
        } elseif ($viewport['hasFixedWidth']) {
            $issues[] = [
                'severity' => 'warning',
                'message' => 'Viewport uses a fixed width which may not scale properly on mobile devices.',
                'category' => 'viewport',
            ];
        } elseif (!$viewport['valid']) {
            $issues[] = [
                'severity' => 'warning',
                'message' => 'Viewport meta tag is not properly configured for mobile.',
                'category' => 'viewport',
            ];
        }

        if (!$viewport['userScalable']) {
            $issues[] = [
                'severity' => 'warning',
                'message' => 'user-scalable=no prevents users from zooming. This is an accessibility issue.',
                'category' => 'viewport',
            ];
        }

        if ($viewport['maxScale'] !== null && $viewport['maxScale'] < 2) {
            $issues[] = [
                'severity' => 'warning',
                'message' => 'maximum-scale is set below 2. This limits zooming capability.',
                'category' => 'viewport',
            ];
        }

        // Responsive issues
        if (!$responsive['hasMediaQueries'] && !$responsive['hasFlexibleGrid']) {
            $issues[] = [
                'severity' => 'warning',
                'message' => 'No responsive design indicators found (media queries or flexible layouts).',
                'category' => 'responsive',
            ];
        }

        // Font size issues
        if ($fonts['fontSizeWarning']) {
            $issues[] = [
                'severity' => 'warning',
                'message' => "Some font sizes are smaller than " . self::MIN_FONT_SIZE . "px which may be hard to read on mobile.",
                'category' => 'readability',
            ];
        }

        // Tap target issues
        if ($tapTargets['smallTargets'] > 0) {
            $issues[] = [
                'severity' => 'warning',
                'message' => "{$tapTargets['smallTargets']} tap target(s) are smaller than 48x48 pixels.",
                'category' => 'tap_targets',
            ];
        }

        // Horizontal scroll issues
        if ($horizontalScroll['detected']) {
            $issues[] = [
                'severity' => 'warning',
                'message' => "Horizontal scroll detected: {$horizontalScroll['cause']}.",
                'category' => 'horizontal_scroll',
            ];
        }

        return $issues;
    }

    /**
     * Generate recommendations.
     */
    private function generateRecommendations(
        array $viewport,
        array $responsive,
        array $fonts,
        array $tapTargets
    ): array {
        $recommendations = [];

        // Viewport recommendations
        if ($viewport['present'] && $viewport['valid']) {
            $recommendations[] = [
                'type' => 'success',
                'message' => 'Viewport meta tag is properly configured.',
            ];
        } else {
            $recommendations[] = [
                'type' => 'info',
                'message' => 'Add a viewport meta tag: <meta name="viewport" content="width=device-width, initial-scale=1">',
            ];
        }

        // Responsive recommendations
        if ($responsive['hasMediaQueries']) {
            $recommendations[] = [
                'type' => 'success',
                'message' => 'Media queries detected for responsive design.',
            ];
        }

        if ($responsive['hasFlexibleGrid']) {
            $recommendations[] = [
                'type' => 'success',
                'message' => 'Flexible grid layout detected.',
            ];
        }

        if (!$responsive['hasMediaQueries'] && !$responsive['hasFlexibleGrid']) {
            $recommendations[] = [
                'type' => 'info',
                'message' => 'Consider using CSS media queries or flexible layouts (flexbox, grid) for responsive design.',
            ];
        }

        // Font recommendations
        if (!$fonts['fontSizeWarning']) {
            $recommendations[] = [
                'type' => 'success',
                'message' => 'Font sizes are readable on mobile devices.',
            ];
        } else {
            $recommendations[] = [
                'type' => 'info',
                'message' => 'Increase font sizes to at least 14px for better mobile readability.',
            ];
        }

        // Tap target recommendations
        if ($tapTargets['smallTargets'] === 0 && ($tapTargets['links'] > 0 || $tapTargets['buttons'] > 0)) {
            $recommendations[] = [
                'type' => 'success',
                'message' => 'Tap targets appear to be appropriately sized.',
            ];
        } elseif ($tapTargets['smallTargets'] > 0) {
            $recommendations[] = [
                'type' => 'info',
                'message' => 'Ensure tap targets (links, buttons) are at least 48x48 pixels.',
            ];
        }

        return $recommendations;
    }

    /**
     * Calculate the overall mobile score.
     */
    private function calculateMobileScore(
        array $viewport,
        array $responsive,
        array $fonts,
        array $tapTargets
    ): int {
        $score = 0;

        // Viewport score (30 points)
        if ($viewport['present']) {
            $score += 15;
            if ($viewport['valid']) {
                $score += 10;
            }
            if ($viewport['userScalable']) {
                $score += 5;
            }
        }

        // Responsive score (25 points)
        if ($responsive['hasMediaQueries']) {
            $score += 12;
        }
        if ($responsive['hasFlexibleGrid']) {
            $score += 8;
        }
        if ($responsive['hasFlexibleImages']) {
            $score += 5;
        }

        // Font size score (20 points)
        if ($fonts['fontSizeMin'] !== null && !$fonts['fontSizeWarning']) {
            $score += 20;
        } elseif ($fonts['fontSizeMin'] !== null) {
            // Partial points based on how close to minimum
            $ratio = $fonts['fontSizeMin'] / self::MIN_FONT_SIZE;
            $score += (int) (20 * $ratio * 0.5);
        }

        // Tap target score (25 points)
        $totalTargets = $tapTargets['links'] + $tapTargets['buttons'];
        if ($totalTargets > 0) {
            $goodRatio = 1 - ($tapTargets['smallTargets'] / $totalTargets);
            $score += (int) (self::TAP_TARGET_WEIGHT * $goodRatio);
        } else {
            $score += 15; // Default score if no targets
        }

        return min(100, max(0, $score));
    }
}
