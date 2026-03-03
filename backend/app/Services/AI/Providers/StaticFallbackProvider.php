<?php

namespace App\Services\AI\Providers;

use App\Services\AI\Contracts\AIProviderInterface;
use App\Services\AI\DTOs\AIResponse;

/**
 * Static fallback provider for AI suggestions.
 *
 * Provides rule-based SEO suggestions without external dependencies.
 * Used as a fallback when AI providers are unavailable or rate-limited.
 */
class StaticFallbackProvider implements AIProviderInterface
{
    /**
     * Indicator types supported by this provider.
     */
    private const SUPPORTED_INDICATORS = [
        'title',
        'meta_description',
        'robots',
        'sitemap',
        'headings',
        'image_alt',
        'filenames',
        'url',
        'links',
        '404',
    ];

    /**
     * Generate an AI response for the given prompt.
     *
     * @param string $prompt JSON-encoded context with indicator type and data
     * @param array<string, mixed> $options Optional parameters (ignored for static provider)
     * @return AIResponse The response containing SEO suggestions
     */
    public function generate(string $prompt, array $options = []): AIResponse
    {
        $startTime = microtime(true);

        // Parse context from prompt
        $context = $this->parseContext($prompt);
        $indicator = $context['indicator'] ?? 'general';

        // Route to appropriate analyzer
        $result = $this->analyze($indicator, $context);

        // Build response
        $response = [
            'score' => $result['score'],
            'issues' => $result['issues'],
            'suggestions' => $result['suggestions'],
            'best_practices' => $result['best_practices'],
            'provider' => 'static_fallback',
            'model' => 'rule-based',
            'generated_at' => date('c'),
            'indicator' => $indicator,
        ];

        $latencyMs = (int) ((microtime(true) - $startTime) * 1000);

        return AIResponse::success(
            json_encode($response),
            0, // No tokens used for static provider
            $latencyMs
        );
    }

    /**
     * Check if the provider is available.
     *
     * Static provider is always available as it has no external dependencies.
     *
     * @return bool Always returns true
     */
    public function isAvailable(): bool
    {
        return true;
    }

    /**
     * Get the name of this provider.
     *
     * @return string Provider name
     */
    public function getName(): string
    {
        return 'static_fallback';
    }

    /**
     * Parse the context from the prompt.
     *
     * @param string $prompt JSON-encoded or plain text context
     * @return array<string, mixed> Parsed context
     */
    private function parseContext(string $prompt): array
    {
        // Try to decode as JSON first
        $decoded = json_decode($prompt, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }

        // Return empty context if not valid JSON
        return [];
    }

    /**
     * Route to appropriate analyzer based on indicator type.
     *
     * @param string $indicator Indicator type
     * @param array<string, mixed> $context Analysis context
     * @return array{score: int, issues: array, suggestions: array, best_practices: array}
     */
    private function analyze(string $indicator, array $context): array
    {
        return match ($indicator) {
            'title' => $this->analyzeTitle($context),
            'meta_description' => $this->analyzeMetaDescription($context),
            'robots' => $this->analyzeRobots($context),
            'sitemap' => $this->analyzeSitemap($context),
            'headings' => $this->analyzeHeadings($context),
            'image_alt' => $this->analyzeImageAlt($context),
            'filenames' => $this->analyzeFilenames($context),
            'url' => $this->analyzeUrl($context),
            'links' => $this->analyzeLinks($context),
            '404' => $this->analyze404($context),
            default => $this->analyzeDefault($context),
        };
    }

    // =====================================================
    // Title Analysis
    // =====================================================

    /**
     * Analyze title tag for SEO.
     *
     * @param array<string, mixed> $context
     * @return array{score: int, issues: array, suggestions: array, best_practices: array}
     */
    private function analyzeTitle(array $context): array
    {
        $title = $context['title'] ?? '';
        $keyword = $context['keyword'] ?? null;
        $score = 100;
        $issues = [];
        $suggestions = [];

        // Handle empty/whitespace title
        $trimmedTitle = trim($title);
        if ($trimmedTitle === '') {
            $score = 40;
            $issues[] = 'Title tag is empty. Add a descriptive title.';

            if ($keyword) {
                $suggestions[] = [
                    'type' => 'rewrite',
                    'text' => $this->generateTitleSuggestion($keyword),
                    'reason' => 'Includes target keyword and follows SEO best practices',
                ];
            }

            return [
                'score' => $score,
                'issues' => $issues,
                'suggestions' => $suggestions,
                'best_practices' => $this->getTitleBestPractices(),
            ];
        }

        // Length check
        $length = mb_strlen($trimmedTitle);

        if ($length < 30) {
            $penalty = (30 - $length) * 2;
            $score -= min($penalty, 30);
            $issues[] = "Title is too short ({$length} chars). Aim for 50-60 characters for optimal SEO.";

            $suggestions[] = [
                'type' => 'improve',
                'text' => $this->extendTitleSuggestion($trimmedTitle, $keyword),
                'reason' => 'Extended to optimal length while maintaining relevance',
            ];
        } elseif ($length > 60) {
            $penalty = min(($length - 60) * 1, 25);
            $score -= $penalty;
            $issues[] = "Title is too long ({$length} chars). Keep under 60 characters to prevent truncation.";

            $suggestions[] = [
                'type' => 'rewrite',
                'text' => mb_substr($trimmedTitle, 0, 57) . '...',
                'reason' => 'Truncated to fit within display limits',
            ];
        }

        // Keyword check
        if ($keyword && trim($keyword) !== '') {
            if (stripos($trimmedTitle, $keyword) === false) {
                $score -= 25;
                $issues[] = "Target keyword '{$keyword}' not found in title.";

                $suggestions[] = [
                    'type' => 'rewrite',
                    'text' => $this->generateTitleSuggestion($keyword),
                    'reason' => "Includes target keyword '{$keyword}'",
                ];
            }
        }

        // Check for all caps
        if (preg_match('/^[A-Z\s\d\W]+$/', $trimmedTitle) && mb_strlen($trimmedTitle) > 5) {
            $score -= 10;
            $issues[] = 'Title is in all caps. Use title case for better readability.';
        }

        // Check for duplicate words (words > 3 chars that appear more than once)
        $words = preg_split('/\s+/', strtolower($trimmedTitle));
        $wordCounts = array_count_values(array_filter($words, fn($w) => mb_strlen($w) > 3));
        foreach ($wordCounts as $word => $count) {
            if ($count > 1) {
                $score -= 5;
                $issues[] = "Duplicate word '{$word}' in title.";
                break;
            }
        }

        // Check for stop words only
        $stopWords = ['the', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'a', 'an'];
        $nonStopWords = array_diff($words, $stopWords);
        if (count($nonStopWords) < 2) {
            $score = min($score, 60);
            $issues[] = 'Title contains mostly stop words. Add meaningful keywords.';
        }

        // Check for brand-only title (common patterns)
        $brandPatterns = [
            '/^[A-Z][a-z]+\s+(Corp|Inc|LLC|Ltd|Company)$/i',
            '/^[A-Z][a-z]+$/',  // Single word brand
        ];
        foreach ($brandPatterns as $pattern) {
            if (preg_match($pattern, $trimmedTitle)) {
                $score -= 15;
                $issues[] = 'Title appears to be brand-only. Add descriptive content.';
                break;
            }
        }

        return [
            'score' => max(0, $score),
            'issues' => $issues,
            'suggestions' => $suggestions,
            'best_practices' => $this->getTitleBestPractices(),
        ];
    }

    /**
     * Generate a title suggestion based on keyword.
     */
    private function generateTitleSuggestion(?string $keyword): string
    {
        if ($keyword && trim($keyword) !== '') {
            return ucfirst($keyword) . ' 2026 | Premium Quality & Best Value';
        }
        return 'Page Title Here | Your Brand Name';
    }

    /**
     * Extend a short title suggestion.
     */
    private function extendTitleSuggestion(string $title, ?string $keyword): string
    {
        if ($keyword && stripos($title, $keyword) === false) {
            return $title . ' - ' . ucfirst($keyword) . ' Guide 2026';
        }
        return $title . ' | Premium Quality Products & Services';
    }

    /**
     * Get title best practices.
     */
    private function getTitleBestPractices(): array
    {
        return [
            'Keep title under 60 characters to prevent truncation in search results.',
            'Place primary keyword near the beginning of the title.',
            'Make each page title unique and descriptive.',
            'Use title case for better readability.',
            'Include brand name at the end if applicable.',
        ];
    }

    // =====================================================
    // Meta Description Analysis
    // =====================================================

    /**
     * Analyze meta description for SEO.
     *
     * @param array<string, mixed> $context
     * @return array{score: int, issues: array, suggestions: array, best_practices: array}
     */
    private function analyzeMetaDescription(array $context): array
    {
        $description = $context['description'] ?? '';
        $keyword = $context['keyword'] ?? null;
        $title = $context['title'] ?? null;
        $score = 100;
        $issues = [];
        $suggestions = [];

        // Handle empty description
        $trimmedDesc = trim($description);
        if ($trimmedDesc === '') {
            $score = 40;
            $issues[] = 'Meta description is empty. Add a compelling description.';

            if ($keyword) {
                $suggestions[] = [
                    'type' => 'add',
                    'text' => $this->generateDescriptionSuggestion($keyword),
                    'reason' => 'Generated description with target keyword and CTA',
                ];
            }

            return [
                'score' => $score,
                'issues' => $issues,
                'suggestions' => $suggestions,
                'best_practices' => $this->getMetaDescriptionBestPractices(),
            ];
        }

        // Length check
        $length = mb_strlen($trimmedDesc);

        if ($length < 120) {
            $penalty = (120 - $length);
            $score -= min($penalty, 50);
            $issues[] = "Meta description is too short ({$length} chars). Aim for 150-160 characters.";

            $suggestions[] = [
                'type' => 'improve',
                'text' => $trimmedDesc . ' ' . $this->getCtaSuggestion(),
                'reason' => 'Extended with call-to-action for better engagement',
            ];
        } elseif ($length > 160) {
            $penalty = min(($length - 160) / 2, 20);
            $score -= $penalty;
            $issues[] = "Meta description is too long ({$length} chars). Keep under 160 characters.";
        }

        // Check for title duplication
        if ($title && trim($title) !== '' && $trimmedDesc === trim($title)) {
            $score -= 30;
            $issues[] = 'Meta description is identical to title. Create unique description.';

            $suggestions[] = [
                'type' => 'rewrite',
                'text' => $this->generateDescriptionSuggestion($keyword ?? 'products'),
                'reason' => 'Unique description that expands on the title',
            ];
        }

        // Keyword check
        if ($keyword && trim($keyword) !== '') {
            if (stripos($trimmedDesc, $keyword) === false) {
                $score -= 15;
                $issues[] = "Target keyword '{$keyword}' not found in description.";
            }
        }

        // CTA check
        $ctaPatterns = [
            '/\b(buy|shop|order|get|find|discover|learn|explore|try|start|download|call|contact)\b/i',
            '/\b(now|today|here)\s*$/i',
            '/\d{3}[-.\s]?\d{4}/', // Phone numbers
            '/\$\d+/', // Prices
        ];

        $hasCta = false;
        foreach ($ctaPatterns as $pattern) {
            if (preg_match($pattern, $trimmedDesc)) {
                $hasCta = true;
                break;
            }
        }

        if (!$hasCta) {
            $score -= 10;
            $issues[] = 'No clear call-to-action found. Add a CTA to improve click-through rate.';
        }

        // Check for multiple CTAs (spammy)
        $ctaCount = 0;
        foreach (['buy now', 'shop now', 'order today', 'get it now', 'call now'] as $cta) {
            if (stripos($trimmedDesc, $cta) !== false) {
                $ctaCount++;
            }
        }
        if ($ctaCount > 2) {
            $score -= 15;
            $issues[] = 'Too many call-to-action phrases. Keep it natural.';
        }

        // Check for HTML entities
        if (preg_match('/&(amp|lt|gt|quot|#\d+);/', $trimmedDesc)) {
            $score -= 5;
            $issues[] = 'HTML entities detected. Use proper characters instead.';
        }

        return [
            'score' => max(0, $score),
            'issues' => $issues,
            'suggestions' => $suggestions,
            'best_practices' => $this->getMetaDescriptionBestPractices(),
        ];
    }

    /**
     * Generate a description suggestion.
     */
    private function generateDescriptionSuggestion(?string $keyword): string
    {
        if ($keyword && trim($keyword) !== '') {
            return "Discover the best {$keyword} with premium quality and great prices. Shop now for exclusive deals and free shipping!";
        }
        return 'Explore our premium products and services. Great prices, fast shipping, and excellent customer support. Shop today!';
    }

    /**
     * Get a CTA suggestion.
     */
    private function getCtaSuggestion(): string
    {
        $ctas = [
            'Shop now for the best deals!',
            'Discover more today.',
            'Get started now!',
            'Learn more here.',
        ];
        return $ctas[array_rand($ctas)];
    }

    /**
     * Get meta description best practices.
     */
    private function getMetaDescriptionBestPractices(): array
    {
        return [
            'Keep meta description between 150-160 characters.',
            'Include target keyword naturally in the description.',
            'Add a clear call-to-action to improve click-through rate.',
            'Make each description unique for every page.',
            'Summarize the page content accurately.',
        ];
    }

    // =====================================================
    // Robots.txt Analysis
    // =====================================================

    /**
     * Analyze robots.txt for SEO.
     *
     * @param array<string, mixed> $context
     * @return array{score: int, issues: array, suggestions: array, best_practices: array}
     */
    private function analyzeRobots(array $context): array
    {
        $content = $context['robots_content'] ?? '';
        $score = 100;
        $issues = [];
        $suggestions = [];

        // Handle empty robots.txt
        $trimmedContent = trim($content);
        if ($trimmedContent === '') {
            $score = 50;
            $issues[] = 'Robots.txt is empty. Add directives for crawlers.';

            $suggestions[] = [
                'type' => 'add',
                'text' => "User-agent: *\nAllow: /\nSitemap: https://yoursite.com/sitemap.xml",
                'reason' => 'Basic robots.txt configuration allowing all crawlers',
            ];

            return [
                'score' => $score,
                'issues' => $issues,
                'suggestions' => $suggestions,
                'best_practices' => $this->getRobotsBestPractices(),
            ];
        }

        // Parse directives
        $lines = preg_split('/\r?\n/', $trimmedContent);
        $hasUserAgent = false;
        $hasAllow = false;
        $hasDisallow = false;
        $hasSitemap = false;
        $disallowAll = false;
        $disallowPatterns = [];

        foreach ($lines as $line) {
            $line = trim($line);

            // Skip comments and empty lines
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            $lowerLine = strtolower($line);

            if (str_starts_with($lowerLine, 'user-agent:')) {
                $hasUserAgent = true;
            } elseif (str_starts_with($lowerLine, 'allow:')) {
                $hasAllow = true;
            } elseif (str_starts_with($lowerLine, 'disallow:')) {
                $hasDisallow = true;
                $path = trim(substr($line, 9));

                if ($path === '/' || $path === '/*') {
                    $disallowAll = true;
                }

                $disallowPatterns[] = $path;
            } elseif (str_starts_with($lowerLine, 'sitemap:')) {
                $hasSitemap = true;
            } elseif (str_starts_with($lowerLine, 'crawl-delay:')) {
                // Crawl delay is recognized
            }
        }

        // Check for blocking entire site
        if ($disallowAll) {
            $score = 30;
            $issues[] = 'CRITICAL: Robots.txt blocks all crawlers from the entire site. This prevents indexing.';

            $suggestions[] = [
                'type' => 'warning',
                'text' => "User-agent: *\nAllow: /",
                'reason' => 'Allow crawlers to index your site',
            ];
        }

        // Check for missing User-agent
        if (!$hasUserAgent) {
            $score -= 20;
            $issues[] = 'No User-agent directive found. Add User-agent: * for all crawlers.';
        }

        // Check for blocking CSS/JS
        $cssJsPatterns = ['/css', '/js', '/javascript', '/style', '/scripts', '/*.css', '/*.js'];
        foreach ($disallowPatterns as $pattern) {
            foreach ($cssJsPatterns as $cssJs) {
                if (stripos($pattern, $cssJs) !== false) {
                    $score -= 15;
                    $issues[] = "Blocking CSS/JS files ({$pattern}) can prevent proper rendering in search results.";
                    break 2;
                }
            }
        }

        // Check for exposing admin paths
        $adminPatterns = ['/admin', '/wp-admin', '/administrator', '/backend'];
        foreach ($disallowPatterns as $pattern) {
            foreach ($adminPatterns as $admin) {
                if (stripos($pattern, $admin) !== false) {
                    $issues[] = "Note: Disallowing admin paths ({$pattern}) is good, but listing them in robots.txt reveals their existence.";
                    break 2;
                }
            }
        }

        // Check for sitemap reference
        if (!$hasSitemap) {
            $score -= 10;
            $issues[] = 'No sitemap reference found. Add Sitemap: URL to help crawlers discover your pages.';

            $suggestions[] = [
                'type' => 'add',
                'text' => 'Sitemap: https://yoursite.com/sitemap.xml',
                'reason' => 'Add sitemap URL to help crawlers discover all pages',
            ];
        }

        // Check for malformed directives
        $validDirectives = ['user-agent', 'allow', 'disallow', 'sitemap', 'crawl-delay', 'host', 'request-rate'];
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            if (!preg_match('/^([a-z-]+):/i', $line, $matches)) {
                $score -= 5;
                $issues[] = "Potentially malformed directive: '{$line}'";
            } elseif (!in_array(strtolower($matches[1]), $validDirectives)) {
                $score -= 3;
                $issues[] = "Unknown directive '{$matches[1]}' in robots.txt";
            }
        }

        // Boost score if well-formed
        if ($hasUserAgent && ($hasAllow || !$hasDisallow)) {
            $score = min($score + 5, 100);
        }

        return [
            'score' => max(0, $score),
            'issues' => $issues,
            'suggestions' => $suggestions,
            'best_practices' => $this->getRobotsBestPractices(),
        ];
    }

    /**
     * Get robots.txt best practices.
     */
    private function getRobotsBestPractices(): array
    {
        return [
            'Test your robots.txt in Google Search Console before deploying.',
            'Add sitemap URL to help search engines discover your content.',
            'Be careful not to block important CSS/JS files.',
            'Use specific User-agent directives for different crawlers if needed.',
            'Avoid listing sensitive paths that could reveal admin areas.',
        ];
    }

    // =====================================================
    // Sitemap Analysis
    // =====================================================

    /**
     * Analyze sitemap for SEO.
     *
     * @param array<string, mixed> $context
     * @return array{score: int, issues: array, suggestions: array, best_practices: array}
     */
    private function analyzeSitemap(array $context): array
    {
        $sitemapUrl = $context['sitemap_url'] ?? '';
        $pageCount = $context['page_count'] ?? 0;
        $score = 100;
        $issues = [];
        $suggestions = [];

        // Check for sitemap URL
        if (empty($sitemapUrl)) {
            $score -= 20;
            $issues[] = 'No sitemap URL provided. Ensure sitemap.xml exists.';
        } elseif (!filter_var($sitemapUrl, FILTER_VALIDATE_URL)) {
            $score -= 15;
            $issues[] = 'Invalid sitemap URL format.';
        }

        // Check for page count
        if ($pageCount === 0) {
            $score -= 25;
            $issues[] = 'Sitemap appears to be empty. Add URLs to your sitemap.';

            $suggestions[] = [
                'type' => 'add',
                'text' => 'Add all indexable page URLs to your sitemap',
                'reason' => 'Sitemaps help search engines discover your content',
            ];
        } elseif ($pageCount < 10) {
            $issues[] = "Sitemap contains only {$pageCount} URLs. Consider adding more pages if available.";
        }

        // General sitemap advice
        if (empty($issues)) {
            $score = 90;
        }

        return [
            'score' => max(0, $score),
            'issues' => $issues,
            'suggestions' => $suggestions,
            'best_practices' => $this->getSitemapBestPractices(),
        ];
    }

    /**
     * Get sitemap best practices.
     */
    private function getSitemapBestPractices(): array
    {
        return [
            'Include all indexable pages in your sitemap.',
            'Keep sitemap under 50,000 URLs or 50MB (split if larger).',
            'Update sitemap when adding new content.',
            'Reference sitemap in robots.txt.',
            'Submit sitemap to Google Search Console and Bing Webmaster Tools.',
        ];
    }

    // =====================================================
    // Headings Analysis
    // =====================================================

    /**
     * Analyze heading structure for SEO.
     *
     * @param array<string, mixed> $context
     * @return array{score: int, issues: array, suggestions: array, best_practices: array}
     */
    private function analyzeHeadings(array $context): array
    {
        $headings = $context['headings'] ?? [];
        $title = $context['title'] ?? null;
        $keyword = $context['keyword'] ?? null;
        $score = 100;
        $issues = [];
        $suggestions = [];

        // Handle no headings
        if (empty($headings)) {
            $score = 50;
            $issues[] = 'No headings found. Add H1 and subsequent headings for structure.';

            $suggestions[] = [
                'type' => 'add',
                'text' => '<h1>Page Title</h1>',
                'reason' => 'Start with an H1 heading for the main topic',
            ];

            return [
                'score' => $score,
                'issues' => $issues,
                'suggestions' => $suggestions,
                'best_practices' => $this->getHeadingsBestPractices(),
            ];
        }

        // Count H1s
        $h1Count = 0;
        $h1Text = '';
        $levels = [];
        $emptyHeadings = [];

        foreach ($headings as $heading) {
            $level = $heading['level'] ?? 0;
            $text = trim($heading['text'] ?? '');

            $levels[] = $level;

            if ($level === 1) {
                $h1Count++;
                if ($h1Text === '') {
                    $h1Text = $text;
                }
            }

            if ($text === '') {
                $emptyHeadings[] = "H{$level}";
            }
        }

        // Check for missing H1
        if ($h1Count === 0) {
            $score -= 30;
            $issues[] = 'No H1 heading found. Every page should have exactly one H1.';

            $suggestions[] = [
                'type' => 'add',
                'text' => '<h1>' . ($keyword ? ucfirst($keyword) . ' Guide' : 'Main Page Title') . '</h1>',
                'reason' => 'Add a descriptive H1 heading',
            ];
        }

        // Check for multiple H1s
        if ($h1Count > 1) {
            $score -= 20;
            $issues[] = "Multiple H1 headings found ({$h1Count}). Use only one H1 per page.";
        }

        // Check for empty headings
        if (!empty($emptyHeadings)) {
            $score -= 10;
            $issues[] = 'Empty headings detected: ' . implode(', ', array_unique($emptyHeadings));
        }

        // Check hierarchy
        $prevLevel = 0;
        foreach ($levels as $level) {
            if ($prevLevel > 0 && $level > $prevLevel + 1) {
                $score -= 10;
                $issues[] = "Heading hierarchy skipped: H{$prevLevel} jumps to H{$level}. Use sequential heading levels.";

                $suggestions[] = [
                    'type' => 'warning',
                    'text' => 'Maintain proper heading hierarchy (H1 → H2 → H3)',
                    'reason' => 'Skipped heading levels can confuse screen readers and SEO',
                ];
                break;
            }
            $prevLevel = $level;
        }

        // Check H1 matches title
        if ($h1Text !== '' && $title && trim($title) !== '') {
            if (stripos($h1Text, $title) !== false || stripos($title, $h1Text) !== false) {
                // H1 and title are related - good
            } else {
                // They differ - note but don't penalize heavily
                $issues[] = 'H1 and page title differ. Consider aligning them for consistency.';
            }
        }

        // Check keyword in H1
        if ($keyword && trim($keyword) !== '' && $h1Text !== '') {
            if (stripos($h1Text, $keyword) === false) {
                $score -= 10;
                $issues[] = "Target keyword '{$keyword}' not found in H1 heading.";
            } else {
                // Keyword in H1 is good - boost score slightly
                $score = min($score + 5, 100);
            }
        }

        // Valid hierarchy check - good structure gets bonus
        if ($h1Count === 1 && empty($emptyHeadings)) {
            // Check if hierarchy is well-structured
            $hasGoodStructure = true;
            $prevLevel = 0;
            foreach ($levels as $level) {
                if ($prevLevel > 0 && $level > $prevLevel + 1) {
                    $hasGoodStructure = false;
                    break;
                }
                $prevLevel = $level;
            }
            // Valid structure gets a slight bonus already reflected in base score
        }

        return [
            'score' => max(0, $score),
            'issues' => $issues,
            'suggestions' => $suggestions,
            'best_practices' => $this->getHeadingsBestPractices(),
        ];
    }

    /**
     * Get headings best practices.
     */
    private function getHeadingsBestPractices(): array
    {
        return [
            'Use exactly one H1 heading per page for the main topic.',
            'Maintain proper heading hierarchy (H1 → H2 → H3).',
            'Include target keywords naturally in headings.',
            'Make headings descriptive and scannable.',
            'Use H2-H6 for content sections and subsections.',
        ];
    }

    // =====================================================
    // Image Alt Analysis
    // =====================================================

    /**
     * Analyze image alt text for SEO.
     *
     * @param array<string, mixed> $context
     * @return array{score: int, issues: array, suggestions: array, best_practices: array}
     */
    private function analyzeImageAlt(array $context): array
    {
        $images = $context['images'] ?? [];
        $keyword = $context['keyword'] ?? null;
        $score = 100;
        $issues = [];
        $suggestions = [];

        // Handle no images
        if (empty($images)) {
            return [
                'score' => 85,
                'issues' => ['No images found to analyze.'],
                'suggestions' => [],
                'best_practices' => $this->getImageAltBestPractices(),
            ];
        }

        $missingAlt = 0;
        $emptyAlt = 0;
        $keywordInAlt = 0;

        foreach ($images as $image) {
            $alt = $image['alt'] ?? null;
            $src = $image['src'] ?? '';

            if ($alt === null) {
                $missingAlt++;
            } elseif (trim($alt) === '') {
                $emptyAlt++;
            } elseif ($keyword && stripos($alt, $keyword) !== false) {
                $keywordInAlt++;
            }
        }

        $totalImages = count($images);
        $problematicImages = $missingAlt + $emptyAlt;

        if ($missingAlt > 0) {
            $penalty = ($missingAlt / $totalImages) * 40;
            $score -= (int) $penalty;
            $issues[] = "{$missingAlt} image(s) missing alt attribute. Add descriptive alt text.";

            $suggestions[] = [
                'type' => 'add',
                'text' => 'Add alt="descriptive text" to all images',
                'reason' => 'Alt text improves accessibility and SEO',
            ];
        }

        if ($emptyAlt > 0) {
            $penalty = ($emptyAlt / $totalImages) * 20;
            $score -= (int) $penalty;
            $issues[] = "{$emptyAlt} image(s) have empty alt text.";
        }

        if ($keyword && $keywordInAlt === 0 && $totalImages > 0) {
            $issues[] = "Target keyword '{$keyword}' not found in any image alt text.";
        }

        return [
            'score' => max(0, $score),
            'issues' => $issues,
            'suggestions' => $suggestions,
            'best_practices' => $this->getImageAltBestPractices(),
        ];
    }

    /**
     * Get image alt best practices.
     */
    private function getImageAltBestPractices(): array
    {
        return [
            'Add descriptive alt text to all meaningful images.',
            'Keep alt text concise (under 125 characters).',
            'Include relevant keywords naturally in alt text.',
            'Use empty alt="" for decorative images.',
            'Describe the image content, not just keywords.',
        ];
    }

    // =====================================================
    // Filenames Analysis
    // =====================================================

    /**
     * Analyze filenames for SEO.
     *
     * @param array<string, mixed> $context
     * @return array{score: int, issues: array, suggestions: array, best_practices: array}
     */
    private function analyzeFilenames(array $context): array
    {
        $filenames = $context['filenames'] ?? [];
        $score = 100;
        $issues = [];
        $suggestions = [];

        // Handle no filenames
        if (empty($filenames)) {
            return [
                'score' => 85,
                'issues' => ['No filenames found to analyze.'],
                'suggestions' => [],
                'best_practices' => $this->getFilenamesBestPractices(),
            ];
        }

        $problems = [
            'spaces' => 0,
            'special_chars' => 0,
            'generic' => 0,
            'uppercase' => 0,
        ];

        $genericNames = ['image', 'img', 'file', 'document', 'photo', 'picture', 'untitled', 'dsc', 'img_', 'screenshot'];

        foreach ($filenames as $filename) {
            $name = $filename['name'] ?? $filename;
            $name = basename($name);

            // Check for spaces
            if (preg_match('/\s/', $name)) {
                $problems['spaces']++;
            }

            // Check for special characters
            if (preg_match('/[^a-z0-9._-]/i', $name)) {
                $problems['special_chars']++;
            }

            // Check for generic names
            $nameWithoutExt = pathinfo($name, PATHINFO_FILENAME);
            foreach ($genericNames as $generic) {
                if (stripos($nameWithoutExt, $generic) === 0) {
                    $problems['generic']++;
                    break;
                }
            }

            // Check for uppercase
            if (preg_match('/[A-Z]/', $name)) {
                $problems['uppercase']++;
            }
        }

        $totalFiles = count($filenames);

        if ($problems['spaces'] > 0) {
            $score -= min(20, $problems['spaces'] * 5);
            $issues[] = "{$problems['spaces']} filename(s) contain spaces. Use hyphens instead.";
        }

        if ($problems['special_chars'] > 0) {
            $score -= min(15, $problems['special_chars'] * 3);
            $issues[] = "{$problems['special_chars']} filename(s) contain special characters.";
        }

        if ($problems['generic'] > 0) {
            $score -= min(15, $problems['generic'] * 3);
            $issues[] = "{$problems['generic']} filename(s) are generic. Use descriptive names.";

            $suggestions[] = [
                'type' => 'improve',
                'text' => 'Use descriptive filenames like "red-widget-product.jpg"',
                'reason' => 'Descriptive filenames improve SEO and organization',
            ];
        }

        if ($problems['uppercase'] > 0) {
            $score -= min(5, $problems['uppercase'] * 2);
            $issues[] = "{$problems['uppercase']} filename(s) use uppercase. Use lowercase.";
        }

        return [
            'score' => max(0, $score),
            'issues' => $issues,
            'suggestions' => $suggestions,
            'best_practices' => $this->getFilenamesBestPractices(),
        ];
    }

    /**
     * Get filenames best practices.
     */
    private function getFilenamesBestPractices(): array
    {
        return [
            'Use lowercase letters only in filenames.',
            'Separate words with hyphens, not spaces or underscores.',
            'Include relevant keywords in filenames.',
            'Keep filenames short but descriptive.',
            'Avoid special characters except hyphens and periods.',
        ];
    }

    // =====================================================
    // URL Analysis
    // =====================================================

    /**
     * Analyze URL structure for SEO.
     *
     * @param array<string, mixed> $context
     * @return array{score: int, issues: array, suggestions: array, best_practices: array}
     */
    private function analyzeUrl(array $context): array
    {
        $url = $context['url'] ?? '';
        $keyword = $context['keyword'] ?? null;
        $score = 100;
        $issues = [];
        $suggestions = [];

        // Handle empty URL
        if (trim($url) === '') {
            $score = 40;
            $issues[] = 'URL is empty. Provide a valid URL for analysis.';

            return [
                'score' => $score,
                'issues' => $issues,
                'suggestions' => $suggestions,
                'best_practices' => $this->getUrlBestPractices(),
            ];
        }

        $parsed = parse_url($url);

        // Protocol check
        $scheme = $parsed['scheme'] ?? '';
        if ($scheme !== 'https') {
            $score -= 15;
            $issues[] = 'URL is not using HTTPS. Secure URLs are preferred by search engines.';

            $suggestions[] = [
                'type' => 'improve',
                'text' => preg_replace('/^http:/', 'https:', $url),
                'reason' => 'HTTPS provides security and SEO benefits',
            ];
        }

        // Check for query parameters
        if (isset($parsed['query']) && $parsed['query'] !== '') {
            $score -= 15;
            $issues[] = 'URL contains query parameters. Clean URLs are preferred for SEO.';

            $suggestions[] = [
                'type' => 'rewrite',
                'text' => 'Consider URL rewriting to hide query parameters',
                'reason' => 'Clean URLs are easier for users and search engines to understand',
            ];
        }

        // Check for dynamic URL patterns
        $path = $parsed['path'] ?? '';
        if (preg_match('/\.(php|asp|aspx|jsp)\?/i', $url) || preg_match('/\?id=/i', $url)) {
            $score -= 15;
            $issues[] = 'URL appears to be dynamic. Consider using static, SEO-friendly URLs.';
        }

        // Check for underscores
        if (strpos($path, '_') !== false) {
            $score -= 10;
            $issues[] = 'URL contains underscores. Use hyphens instead for better SEO.';
        }

        // Check for uppercase
        if (preg_match('/[A-Z]/', $path)) {
            $score -= 5;
            $issues[] = 'URL contains uppercase letters. Use lowercase for consistency.';
        }

        // Check URL length
        if (mb_strlen($path) > 100) {
            $score -= 10;
            $issues[] = 'URL path is very long. Keep URLs concise.';
        }

        // Check for keyword
        if ($keyword && trim($keyword) !== '') {
            $slug = basename($path);
            if (stripos($slug, $keyword) === false && stripos($path, $keyword) === false) {
                $score -= 10;
                $issues[] = "Target keyword '{$keyword}' not found in URL.";

                $suggestions[] = [
                    'type' => 'improve',
                    'text' => 'Include target keyword in the URL slug',
                    'reason' => "Keyword in URL helps with '{$keyword}' rankings",
                ];
            }
        }

        // Root URL
        if ($path === '' || $path === '/') {
            $score = max($score, 70); // Root URLs are valid but less descriptive
        }

        return [
            'score' => max(0, $score),
            'issues' => $issues,
            'suggestions' => $suggestions,
            'best_practices' => $this->getUrlBestPractices(),
        ];
    }

    /**
     * Get URL best practices.
     */
    private function getUrlBestPractices(): array
    {
        return [
            'Use HTTPS for all URLs.',
            'Keep URLs short, descriptive, and readable.',
            'Use hyphens to separate words (not underscores).',
            'Include relevant keywords in the URL slug.',
            'Avoid query parameters when possible.',
        ];
    }

    // =====================================================
    // Links Analysis
    // =====================================================

    /**
     * Analyze links for SEO.
     *
     * @param array<string, mixed> $context
     * @return array{score: int, issues: array, suggestions: array, best_practices: array}
     */
    private function analyzeLinks(array $context): array
    {
        $links = $context['links'] ?? [];
        $baseUrl = $context['base_url'] ?? '';
        $score = 100;
        $issues = [];
        $suggestions = [];

        // Handle no links
        if (empty($links)) {
            $score = 50;
            $issues[] = 'No links found. Add internal and external links for better SEO.';

            $suggestions[] = [
                'type' => 'add',
                'text' => 'Add relevant internal links to other pages on your site',
                'reason' => 'Internal links help distribute page authority and improve navigation',
            ];

            return [
                'score' => $score,
                'issues' => $issues,
                'suggestions' => $suggestions,
                'best_practices' => $this->getLinksBestPractices(),
            ];
        }

        $internalLinks = 0;
        $externalLinks = 0;
        $brokenLinks = 0;
        $genericAnchors = 0;
        $missingNoopener = 0;
        $missingNofollow = 0;

        $genericAnchorTexts = ['click here', 'read more', 'learn more', 'here', 'more', 'link'];

        foreach ($links as $link) {
            $type = $link['type'] ?? 'internal';
            $status = $link['status'] ?? 200;
            $anchor = $link['anchor'] ?? '';
            $rel = $link['rel'] ?? '';
            $target = $link['target'] ?? '';

            if ($type === 'internal') {
                $internalLinks++;
            } elseif ($type === 'external') {
                $externalLinks++;

                // Check for nofollow on external links
                if (strpos($rel, 'nofollow') === false) {
                    $missingNofollow++;
                }

                // Check for noopener on target="_blank"
                if ($target === '_blank' && strpos($rel, 'noopener') === false) {
                    $missingNoopener++;
                }
            }

            // Check for broken links
            if ($status >= 400) {
                $brokenLinks++;
            }

            // Check for generic anchor text
            $lowerAnchor = strtolower(trim($anchor));
            if (in_array($lowerAnchor, $genericAnchorTexts)) {
                $genericAnchors++;
            }
        }

        $totalLinks = count($links);

        // Evaluate internal links
        if ($internalLinks === 0 && $externalLinks === 0) {
            // Already handled above
        } elseif ($internalLinks === 0) {
            $score -= 20;
            $issues[] = 'No internal links found. Add links to other pages on your site.';
        } elseif ($internalLinks > 100) {
            $score -= 10;
            $issues[] = "Too many internal links ({$internalLinks}). This can dilute link value.";
        }

        // Evaluate external links
        if ($externalLinks > 10) {
            $score -= 10;
            $issues[] = "Many external links ({$externalLinks}). Ensure they add value.";
        }

        // Check for broken links
        if ($brokenLinks > 0) {
            $penalty = min(30, $brokenLinks * 10);
            $score -= $penalty;
            $issues[] = "{$brokenLinks} broken link(s) found. Fix or remove them.";

            $suggestions[] = [
                'type' => 'remove',
                'text' => 'Fix or remove broken links',
                'reason' => 'Broken links hurt user experience and SEO',
            ];
        }

        // Check for missing noopener
        if ($missingNoopener > 0) {
            $score -= $missingNoopener * 5;
            $issues[] = "{$missingNoopener} external link(s) with target='_blank' missing noopener. Security risk.";
        }

        // Check for missing nofollow on external links
        if ($missingNofollow > 0 && $externalLinks > 0) {
            $issues[] = "{$missingNofollow} external link(s) without nofollow. Consider adding for SEO.";

            $suggestions[] = [
                'type' => 'improve',
                'text' => 'Add rel="nofollow" to external links',
                'reason' => 'Nofollow helps control link equity distribution',
            ];
        }

        // Check for generic anchor text
        if ($genericAnchors > 0) {
            $score -= min(15, $genericAnchors * 5);
            $issues[] = "{$genericAnchors} link(s) with generic anchor text (e.g., 'click here'). Use descriptive anchors.";
        }

        return [
            'score' => max(0, $score),
            'issues' => $issues,
            'suggestions' => $suggestions,
            'best_practices' => $this->getLinksBestPractices(),
        ];
    }

    /**
     * Get links best practices.
     */
    private function getLinksBestPractices(): array
    {
        return [
            'Include relevant internal links to improve site navigation.',
            'Use descriptive anchor text instead of "click here".',
            'Add rel="noopener" to links with target="_blank".',
            'Consider rel="nofollow" for external links.',
            'Fix broken links promptly to maintain good user experience.',
        ];
    }

    // =====================================================
    // 404 Analysis
    // =====================================================

    /**
     * Analyze 404 errors for SEO.
     *
     * @param array<string, mixed> $context
     * @return array{score: int, issues: array, suggestions: array, best_practices: array}
     */
    private function analyze404(array $context): array
    {
        $errorUrls = $context['error_urls'] ?? [];
        $referrers = $context['referrers'] ?? [];
        $score = 100;
        $issues = [];
        $suggestions = [];

        // Handle no 404 errors
        if (empty($errorUrls)) {
            return [
                'score' => 100,
                'issues' => [],
                'suggestions' => [],
                'best_practices' => $this->get404BestPractices(),
            ];
        }

        $errorCount = count($errorUrls);

        $score -= min(50, $errorCount * 5);
        $issues[] = "{$errorCount} 404 error(s) detected. These pages are not found.";

        // Common 404 causes
        $causes = [
            'URL typos in links',
            'Deleted pages without redirects',
            'Changed URL structure',
            'Case sensitivity issues',
        ];

        $suggestions[] = [
            'type' => 'improve',
            'text' => 'Implement 301 redirects for these URLs',
            'reason' => 'Redirects preserve link equity and improve user experience',
        ];

        if (!empty($referrers)) {
            $issues[] = 'Some 404s have incoming links. Fix these priority URLs.';
        }

        return [
            'score' => max(0, $score),
            'issues' => $issues,
            'suggestions' => $suggestions,
            'best_practices' => $this->get404BestPractices(),
        ];
    }

    /**
     * Get 404 best practices.
     */
    private function get404BestPractices(): array
    {
        return [
            'Implement 301 redirects for moved or deleted pages.',
            'Create a helpful custom 404 page.',
            'Monitor 404 errors in Google Search Console.',
            'Fix internal broken links immediately.',
            'Reach out to sites linking to your 404 pages.',
        ];
    }

    // =====================================================
    // Default Analysis
    // =====================================================

    /**
     * Default analysis for unknown indicator types.
     *
     * @param array<string, mixed> $context
     * @return array{score: int, issues: array, suggestions: array, best_practices: array}
     */
    private function analyzeDefault(array $context): array
    {
        return [
            'score' => 75,
            'issues' => ['No specific analysis available for this indicator type.'],
            'suggestions' => [],
            'best_practices' => [
                'Ensure your content is well-structured and optimized.',
                'Use appropriate meta tags and headings.',
                'Follow general SEO best practices.',
            ],
        ];
    }
}
