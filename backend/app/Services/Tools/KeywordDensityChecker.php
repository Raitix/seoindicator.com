<?php

namespace App\Services\Tools;

use App\Services\HtmlParserService;
use App\Services\URLValidationService;
use Illuminate\Support\Str;

/**
 * Keyword Density Checker Tool.
 * 
 * Analyzes keyword frequency and density in page content:
 * - Keyword frequency and density percentage
 * - Placement analysis (title, headings, first paragraph)
 * - Keyword stuffing detection
 * - Case-insensitive matching
 * - Phrase keyword support
 * 
 * TASK-16-3: Keyword Density Checker Tool
 */
class KeywordDensityChecker extends SEOTool
{
    /**
     * Density thresholds.
     */
    private const DENSITY_LOW = 0.5;
    private const DENSITY_GOOD = 2.0;
    private const DENSITY_ACCEPTABLE = 3.0;

    /**
     * Density status values.
     */
    private const STATUS_LOW = 'low';
    private const STATUS_GOOD = 'good';
    private const STATUS_ACCEPTABLE = 'acceptable';
    private const STATUS_STUFFING = 'stuffing';

    protected string $name = 'keyword-density-checker';
    protected string $description = 'Analyzes keyword frequency and density in page content with placement analysis and stuffing detection';

    public function __construct(
        URLValidationService $urlValidator,
        HtmlParserService $htmlParser
    ) {
        parent::__construct($urlValidator, $htmlParser);
    }

    /**
     * Analyze keyword density for a given URL and keyword.
     *
     * @param string $url URL to analyze
     * @param array $options Must contain 'keyword' key
     * @return ToolResult
     */
    public function analyze(string $url, array $options = []): ToolResult
    {
        $keyword = $options['keyword'] ?? null;

        if (empty($keyword)) {
            return $this->error('Keyword is required for density analysis.', $url, 'MISSING_KEYWORD');
        }

        // Fetch and parse the URL
        $result = $this->fetchAndParse($url);

        if (!$result['success']) {
            $errorCode = $result['errorCode'] ?? 'FETCH_ERROR';
            return $this->error($result['error'], $url, $errorCode);
        }

        $parsed = $result['parsed'];
        $finalUrl = $result['finalUrl'];

        // Analyze keyword density
        $analysis = $this->analyzeKeywordDensity($keyword, $parsed, $finalUrl);

        return $this->success($analysis, $url);
    }

    /**
     * Analyze keyword density and placement.
     *
     * @param string $keyword
     * @param array $parsed
     * @param string $finalUrl
     * @return array
     */
    private function analyzeKeywordDensity(string $keyword, array $parsed, string $finalUrl): array
    {
        // Normalize keyword
        $normalizedKeyword = $this->normalizeText($keyword);
        $isPhrase = str_contains($normalizedKeyword, ' ');

        // Extract text content
        $textContent = $parsed['textContent'] ?? '';
        $totalWords = $parsed['wordCount'] ?? 0;

        // Calculate density
        $frequency = $this->countKeywordOccurrences($textContent, $keyword);
        $density = $totalWords > 0 ? $this->calculateDensity($frequency, $totalWords, $isPhrase) : 0;
        $densityStatus = $this->getDensityStatus($density);

        // Analyze placement
        $placement = $this->analyzePlacement($keyword, $parsed, $finalUrl);

        // Generate recommendations
        $recommendations = $this->generateRecommendations($keyword, $frequency, $density, $densityStatus, $placement);

        // Generate warnings
        $warnings = $this->generateWarnings($keyword, $density, $densityStatus);

        return [
            'keyword' => $keyword,
            'normalizedKeyword' => $normalizedKeyword,
            'isPhrase' => $isPhrase,
            'totalWords' => $totalWords,
            'keywordCount' => $frequency,
            'density' => round($density, 2),
            'densityStatus' => $densityStatus,
            'densityThresholds' => [
                'low' => self::DENSITY_LOW,
                'good' => self::DENSITY_GOOD,
                'acceptable' => self::DENSITY_ACCEPTABLE,
            ],
            'placement' => $placement,
            'recommendations' => $recommendations,
            'warnings' => $warnings,
            'summary' => [
                'hasGoodDensity' => $densityStatus === self::STATUS_GOOD,
                'hasGoodPlacement' => $placement['inTitle'] && $placement['inH1'],
                'riskLevel' => $densityStatus === self::STATUS_STUFFING ? 'high' : ($densityStatus === self::STATUS_ACCEPTABLE ? 'medium' : 'low'),
            ],
        ];
    }

    /**
     * Count keyword occurrences in text.
     *
     * @param string $text
     * @param string $keyword
     * @return int
     */
    private function countKeywordOccurrences(string $text, string $keyword): int
    {
        $normalizedText = $this->normalizeText($text);
        $normalizedKeyword = $this->normalizeText($keyword);

        if (empty($normalizedKeyword)) {
            return 0;
        }

        // For phrase keywords, count exact phrase matches
        if (str_contains($normalizedKeyword, ' ')) {
            return substr_count($normalizedText, $normalizedKeyword);
        }

        // For single keywords, count word boundary matches
        $pattern = '/\b' . preg_quote($normalizedKeyword, '/') . '\b/i';
        preg_match_all($pattern, $normalizedText, $matches);

        return count($matches[0] ?? []);
    }

    /**
     * Calculate keyword density percentage.
     *
     * @param int $keywordCount
     * @param int $totalWords
     * @param bool $isPhrase
     * @return float
     */
    private function calculateDensity(int $keywordCount, int $totalWords, bool $isPhrase): float
    {
        if ($totalWords === 0) {
            return 0.0;
        }

        // For phrases, each phrase counts as 1 occurrence
        // Density = (keyword occurrences / total words) * 100
        return ($keywordCount / $totalWords) * 100;
    }

    /**
     * Get density status based on percentage.
     *
     * @param float $density
     * @return string
     */
    private function getDensityStatus(float $density): string
    {
        if ($density < self::DENSITY_LOW) {
            return self::STATUS_LOW;
        } elseif ($density <= self::DENSITY_GOOD) {
            return self::STATUS_GOOD;
        } elseif ($density <= self::DENSITY_ACCEPTABLE) {
            return self::STATUS_ACCEPTABLE;
        } else {
            return self::STATUS_STUFFING;
        }
    }

    /**
     * Analyze keyword placement in various page elements.
     *
     * @param string $keyword
     * @param array $parsed
     * @param string $finalUrl
     * @return array
     */
    private function analyzePlacement(string $keyword, array $parsed, string $finalUrl): array
    {
        $normalizedKeyword = $this->normalizeText($keyword);

        // Check title
        $title = $parsed['title'] ?? '';
        $inTitle = !empty($title) && $this->containsKeyword($title, $keyword);

        // Check H1
        $headings = $parsed['headings'] ?? [];
        $h1Texts = array_filter($headings, fn($h) => $h['tag'] === 'h1');
        $inH1 = !empty($h1Texts) && $this->anyContainsKeyword($h1Texts, $keyword);

        // Check other headings
        $headingsWithKeyword = [];
        foreach ($headings as $heading) {
            if ($this->containsKeyword($heading['text'], $keyword)) {
                $headingsWithKeyword[] = strtoupper($heading['tag']);
            }
        }

        // Check meta description
        $description = $parsed['description'] ?? '';
        $inMetaDescription = !empty($description) && $this->containsKeyword($description, $keyword);

        // Check first paragraph (approximation: first 200 chars of text content)
        $textContent = $parsed['textContent'] ?? '';
        $firstParagraph = substr($textContent, 0, 200);
        $inFirstParagraph = $this->containsKeyword($firstParagraph, $keyword);

        // Check URL
        $inUrl = $this->containsKeyword($finalUrl, $keyword);

        // Check images alt text
        $images = $parsed['images'] ?? [];
        $imagesWithKeyword = 0;
        foreach ($images as $image) {
            if (!empty($image['alt']) && $this->containsKeyword($image['alt'], $keyword)) {
                $imagesWithKeyword++;
            }
        }

        return [
            'inTitle' => $inTitle,
            'inH1' => $inH1,
            'inMetaDescription' => $inMetaDescription,
            'inFirstParagraph' => $inFirstParagraph,
            'inUrl' => $inUrl,
            'headings' => array_unique($headingsWithKeyword),
            'imagesWithKeyword' => $imagesWithKeyword,
            'totalImages' => count($images),
        ];
    }

    /**
     * Generate recommendations based on analysis.
     *
     * @param string $keyword
     * @param int $frequency
     * @param float $density
     * @param string $densityStatus
     * @param array $placement
     * @return array
     */
    private function generateRecommendations(
        string $keyword,
        int $frequency,
        float $density,
        string $densityStatus,
        array $placement
    ): array {
        $recommendations = [];

        // Title recommendations
        if ($placement['inTitle']) {
            $recommendations[] = [
                'type' => 'success',
                'message' => "✓ Keyword appears in the title tag.",
                'field' => 'title',
            ];
        } else {
            $recommendations[] = [
                'type' => 'suggestion',
                'message' => "Consider adding the keyword to your title tag.",
                'field' => 'title',
            ];
        }

        // H1 recommendations
        if ($placement['inH1']) {
            $recommendations[] = [
                'type' => 'success',
                'message' => "✓ Keyword appears in the H1 heading.",
                'field' => 'h1',
            ];
        } else {
            $recommendations[] = [
                'type' => 'suggestion',
                'message' => "Consider adding the keyword to your H1 heading.",
                'field' => 'h1',
            ];
        }

        // Meta description recommendations
        if ($placement['inMetaDescription']) {
            $recommendations[] = [
                'type' => 'success',
                'message' => "✓ Keyword appears in the meta description.",
                'field' => 'description',
            ];
        } else {
            $recommendations[] = [
                'type' => 'suggestion',
                'message' => "Consider adding the keyword to your meta description.",
                'field' => 'description',
            ];
        }

        // First paragraph recommendations
        if ($placement['inFirstParagraph']) {
            $recommendations[] = [
                'type' => 'success',
                'message' => "✓ Keyword appears in the first paragraph.",
                'field' => 'content',
            ];
        } else {
            $recommendations[] = [
                'type' => 'suggestion',
                'message' => "Consider mentioning the keyword in the first paragraph.",
                'field' => 'content',
            ];
        }

        // Density recommendations
        if ($densityStatus === self::STATUS_GOOD) {
            $recommendations[] = [
                'type' => 'success',
                'message' => "✓ Keyword density ({$density}%) is within the optimal range (0.5% - 2%).",
                'field' => 'density',
            ];
        } elseif ($densityStatus === self::STATUS_LOW) {
            $recommendations[] = [
                'type' => 'suggestion',
                'message' => "Keyword density ({$density}%) is low. Consider adding more natural mentions of the keyword.",
                'field' => 'density',
            ];
        } elseif ($densityStatus === self::STATUS_ACCEPTABLE) {
            $recommendations[] = [
                'type' => 'notice',
                'message' => "Keyword density ({$density}%) is slightly high. Monitor for any ranking impact.",
                'field' => 'density',
            ];
        }

        return $recommendations;
    }

    /**
     * Generate warnings based on analysis.
     *
     * @param string $keyword
     * @param float $density
     * @param string $densityStatus
     * @return array
     */
    private function generateWarnings(string $keyword, float $density, string $densityStatus): array
    {
        $warnings = [];

        // Keyword stuffing warning
        if ($densityStatus === self::STATUS_STUFFING) {
            $warnings[] = [
                'type' => 'keyword_stuffing',
                'severity' => 'warning',
                'message' => "⚠ Keyword density ({$density}%) is very high. This may be seen as keyword stuffing and could negatively impact rankings.",
                'field' => 'density',
            ];
        }

        // High density warning
        if ($densityStatus === self::STATUS_ACCEPTABLE) {
            $warnings[] = [
                'type' => 'high_density',
                'severity' => 'notice',
                'message' => "Keyword density ({$density}%) is above the optimal range. Consider reducing keyword mentions.",
                'field' => 'density',
            ];
        }

        return $warnings;
    }

    /**
     * Normalize text for comparison.
     *
     * @param string $text
     * @return string
     */
    private function normalizeText(string $text): string
    {
        // Convert to lowercase
        $text = strtolower($text);

        // Normalize whitespace
        $text = preg_replace('/\s+/', ' ', $text);

        return trim($text);
    }

    /**
     * Check if text contains keyword (case-insensitive).
     *
     * @param string $text
     * @param string $keyword
     * @return bool
     */
    private function containsKeyword(string $text, string $keyword): bool
    {
        $normalizedText = $this->normalizeText($text);
        $normalizedKeyword = $this->normalizeText($keyword);

        return str_contains($normalizedText, $normalizedKeyword);
    }

    /**
     * Check if any heading contains keyword.
     *
     * @param array $headings
     * @param string $keyword
     * @return bool
     */
    private function anyContainsKeyword(array $headings, string $keyword): bool
    {
        foreach ($headings as $heading) {
            if ($this->containsKeyword($heading['text'] ?? '', $keyword)) {
                return true;
            }
        }
        return false;
    }
}
