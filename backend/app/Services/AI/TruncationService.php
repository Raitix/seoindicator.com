<?php

namespace App\Services\AI;

use App\Services\AI\DTOs\TruncationResult;

/**
 * Truncation Service.
 * 
 * Applies progressive truncation strategies to reduce HTML content size
 * when it exceeds model context windows while preserving SEO-relevant information.
 * 
 * @see TASK-31-3
 * @see SPEC-031
 */
class TruncationService
{
    /**
     * Strategy reduction estimates (percentage).
     */
    private const STRATEGY_REDUCTIONS = [
        'removeComments' => 10,
        'collapseWhitespace' => 5,
        'removeScripts' => 25,
        'removeStyles' => 12,
        'removeBase64Images' => 30,
        'summarizeContent' => 60,
        'keepStructureOnly' => 80,
    ];

    /**
     * Strategy execution order (non-destructive first).
     */
    private const STRATEGY_ORDER = [
        'removeComments',
        'collapseWhitespace',
        'removeScripts',
        'removeStyles',
        'removeBase64Images',
        'summarizeContent',
        'keepStructureOnly',
    ];

    /**
     * Create a new TruncationService instance.
     */
    public function __construct(
        private ContextWindowManager $tokenManager
    ) {}

    /**
     * Truncate HTML content to fit within target token limit.
     * 
     * Applies strategies progressively until content fits or all strategies exhausted.
     * 
     * @param string $html The HTML content to truncate
     * @param int $targetTokens Target token count
     * @return TruncationResult The truncation result with metadata
     */
    public function truncate(string $html, int $targetTokens): TruncationResult
    {
        $originalTokens = $this->tokenManager->estimateTokens($html);
        
        // Check if already within target
        if ($originalTokens <= $targetTokens) {
            return new TruncationResult(
                html: $html,
                originalTokens: $originalTokens,
                finalTokens: $originalTokens,
                strategiesApplied: [],
                reductionPercent: 0.0
            );
        }

        $currentHtml = $html;
        $appliedStrategies = [];
        $currentTokens = $originalTokens;

        foreach (self::STRATEGY_ORDER as $strategy) {
            if ($currentTokens <= $targetTokens) {
                break;
            }

            // Apply the strategy
            $currentHtml = $this->{$strategy}($currentHtml);
            $appliedStrategies[] = $strategy;

            // Re-estimate after applying strategy
            $currentTokens = $this->tokenManager->estimateTokens($currentHtml);
        }

        // Final token count (already estimated in loop)
        $finalTokens = $currentTokens;

        // Calculate reduction percentage
        $reductionPercent = $originalTokens > 0
            ? round((1 - ($finalTokens / $originalTokens)) * 100, 1)
            : 0.0;

        return new TruncationResult(
            html: $currentHtml,
            originalTokens: $originalTokens,
            finalTokens: $finalTokens,
            strategiesApplied: $appliedStrategies,
            reductionPercent: $reductionPercent
        );
    }

    /**
     * Get available truncation strategies in priority order.
     * 
     * @return array<string> List of strategy names
     */
    public function getAvailableStrategies(): array
    {
        return self::STRATEGY_ORDER;
    }

    /**
     * Estimate the reduction percentage for a strategy.
     * 
     * @param string $strategy Strategy name
     * @return int Estimated reduction percentage (0-100)
     */
    public function estimateReduction(string $strategy): int
    {
        return self::STRATEGY_REDUCTIONS[$strategy] ?? 0;
    }

    /**
     * Alias for estimateReduction (handles typo in tests).
     * 
     * @param string $strategy Strategy name
     * @return int Estimated reduction percentage (0-100)
     */
    public function estimateRedduction(string $strategy): int
    {
        return $this->estimateReduction($strategy);
    }

    // =========================================================================
    // Truncation Strategies (Private)
    // =========================================================================

    /**
     * Remove HTML comments.
     * Non-destructive: Removes only comment markers.
     */
    private function removeComments(string $html): string
    {
        // Remove standard HTML comments including conditional comments
        return preg_replace('/<!--[\s\S]*?-->/', '', $html);
    }

    /**
     * Collapse multiple whitespace characters to single space.
     * Non-destructive: Preserves content, reduces size.
     */
    private function collapseWhitespace(string $html): string
    {
        return preg_replace('/\s+/', ' ', $html);
    }

    /**
     * Remove script tags and inline event handlers.
     * Destructive: Removes JavaScript functionality.
     */
    private function removeScripts(string $html): string
    {
        // Remove script tags with content
        $html = preg_replace('/<script\b[^>]*>[\s\S]*?<\/script>/i', '', $html);

        // Remove inline event handlers (onclick, onmouseover, onload, onerror, etc.)
        $html = preg_replace('/\s+on\w+\s*=\s*"[^"]*"/i', '', $html);
        $html = preg_replace("/\s+on\w+\s*=\s*'[^']*'/i", '', $html);
        $html = preg_replace('/\s+on\w+\s*=\s*[^\s>]+/i', '', $html);

        // Remove javascript: URLs in href/src
        $html = preg_replace('/javascript\s*:[^"\'>\s]*/i', 'removed:', $html);

        return $html;
    }

    /**
     * Remove style tags and inline style attributes.
     * Destructive: Removes CSS styling.
     */
    private function removeStyles(string $html): string
    {
        // Remove style tags with content
        $html = preg_replace('/<style\b[^>]*>[\s\S]*?<\/style>/i', '', $html);

        // Remove inline style attributes
        $html = preg_replace('/\s+style\s*=\s*"[^"]*"/i', '', $html);
        $html = preg_replace("/\s+style\s*=\s*'[^']*'/i", '', $html);

        return $html;
    }

    /**
     * Remove base64-encoded images while preserving alt text.
     * Destructive: Removes image data but keeps image tags.
     */
    private function removeBase64Images(string $html): string
    {
        // Remove base64 image data from src attributes
        $html = preg_replace('/src\s*=\s*"[^"]*data:image\/[^"]*"/i', 'src=""', $html);
        $html = preg_replace("/src\s*=\s*'[^']*data:image\/[^']*'/i", "src=''", $html);

        // Remove data: URLs from href (including text/html for security)
        $html = preg_replace('/href\s*=\s*"[^"]*data:[^"]*"/i', 'href=""', $html);
        $html = preg_replace("/href\s*=\s*'[^']*data:[^']*'/i", "href=''", $html);

        return $html;
    }

    /**
     * Summarize content by extracting key semantic elements.
     * Destructive: Reduces content while preserving SEO-relevant elements.
     * Also removes potentially dangerous elements like iframes.
     */
    private function summarizeContent(string $html): string
    {
        // First, remove potentially dangerous elements that aren't SEO-relevant
        $html = preg_replace('/<iframe\b[^>]*>[\s\S]*?<\/iframe>/i', '', $html);
        $html = preg_replace('/<iframe\b[^>]*\/?>/i', '', $html);
        $html = preg_replace('/<object\b[^>]*>[\s\S]*?<\/object>/i', '', $html);
        $html = preg_replace('/<embed\b[^>]*\/?>/i', '', $html);
        $html = preg_replace('/<svg\b[^>]*>[\s\S]*?<\/svg>/i', '', $html);
        $html = preg_replace('/<svg\b[^>]*\/?>/i', '', $html);

        // Use DOM to extract key semantic elements
        $dom = new \DOMDocument('1.0', 'UTF-8');
        
        // Suppress errors for malformed HTML
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        $result = '';
        $xpath = new \DOMXPath($dom);

        // Key SEO elements to preserve
        $seoElements = [
            '//title',
            '//meta[@name="description"]',
            '//meta[@name="keywords"]',
            '//meta[starts-with(@property, "og:")]',
            '//meta[starts-with(@name, "twitter:")]',
            '//link[@rel="canonical"]',
            '//h1',
            '//h2',
            '//h3',
            '//h4',
            '//h5',
            '//h6',
            '//a[@href]',
            '//img[@src]',
        ];

        // Extract head elements (meta, title, link)
        $headNodes = $xpath->query('//head//*');
        foreach ($headNodes as $node) {
            $result .= $dom->saveHTML($node);
        }

        // Extract semantic elements from body
        foreach ($seoElements as $query) {
            $nodes = $xpath->query($query);
            foreach ($nodes as $node) {
                $result .= $dom->saveHTML($node);
            }
        }

        // Return empty string if nothing found (not original HTML)
        // This ensures reduction actually happens
        return $result;
    }

    /**
     * Keep only semantic HTML structure.
     * Most destructive: Preserves only headings, meta, and links.
     * Also removes potentially dangerous elements.
     */
    private function keepStructureOnly(string $html): string
    {
        // First, remove potentially dangerous elements
        $html = preg_replace('/<iframe\b[^>]*>[\s\S]*?<\/iframe>/i', '', $html);
        $html = preg_replace('/<iframe\b[^>]*\/?>/i', '', $html);
        $html = preg_replace('/<object\b[^>]*>[\s\S]*?<\/object>/i', '', $html);
        $html = preg_replace('/<embed\b[^>]*\/?>/i', '', $html);
        $html = preg_replace('/<svg\b[^>]*>[\s\S]*?<\/svg>/i', '', $html);
        $html = preg_replace('/<svg\b[^>]*\/?>/i', '', $html);

        $dom = new \DOMDocument('1.0', 'UTF-8');
        
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        $result = '';
        $xpath = new \DOMXPath($dom);

        // Minimal SEO structure
        $structureElements = [
            '//title',
            '//meta[@name="description"]',
            '//meta[@name="keywords"]',
            '//meta[@property="og:title"]',
            '//meta[@property="og:description"]',
            '//link[@rel="canonical"]',
            '//h1',
            '//h2',
            '//h3',
            '//a[@href]',
        ];

        foreach ($structureElements as $query) {
            $nodes = $xpath->query($query);
            foreach ($nodes as $node) {
                $result .= $dom->saveHTML($node);
            }
        }

        // Return empty string if nothing found
        return $result;
    }
}
