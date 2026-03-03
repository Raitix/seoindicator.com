<?php

namespace App\Services\AI\Providers;

use App\Services\AI\Contracts\AIProviderInterface;
use App\Services\AI\DTOs\AIResponse;

/**
 * Static fallback provider for AI suggestions.
 *
 * Provides rule-based SEO suggestions without external dependencies.
 * Always available as a fallback when AI providers are unavailable.
 */
class StaticFallbackProvider implements AIProviderInterface
{
    /**
     * Valid indicator types.
     */
    private const VALID_INDICATORS = [
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
     * Valid suggestion types.
     */
    private const VALID_SUGGESTION_TYPES = ['improve', 'rewrite', 'add', 'remove', 'warning'];

    /**
     * Generate SEO suggestions based on the provided context.
     *
     * @param string $prompt JSON-encoded context with indicator and data
     * @param array<string, mixed> $options Optional parameters (ignored for static provider)
     * @return AIResponse The response with SEO suggestions
     */
    public function generate(string $prompt, array $options = []): AIResponse
    {
        $startTime = microtime(true);

        try {
            $context = $this->parseContext($prompt);
            $indicator = $context['indicator'] ?? 'title';

            $result = $this->analyze($indicator, $context);

            $latencyMs = (int) ((microtime(true) - $startTime) * 1000);

            return AIResponse::success(
                content: json_encode($result, JSON_UNESCAPED_UNICODE),
                tokensUsed: 0,
                latencyMs: $latencyMs
            );
        } catch (\Throwable $e) {
            $latencyMs = (int) ((microtime(true) - $startTime) * 1000);

            // Still return a valid response even on error
            return AIResponse::success(
                content: json_encode([
                    'indicator' => 'unknown',
                    'score' => 50,
                    'issues' => [],
                    'suggestions' => [],
                    'best_practices' => $this->getDefaultBestPractices(),
                    'provider' => 'static_fallback',
                    'model' => 'rule-based',
                    'generated_at' => date('c'),
                ]),
                tokensUsed: 0,
                latencyMs: $latencyMs
            );
        }
    }

    /**
     * Check if the provider is available.
     *
     * @return bool Always returns true for static provider
     */
    public function isAvailable(): bool
    {
        return true;
    }

    /**
     * Get the provider name.
     *
     * @return string The provider identifier
     */
    public function getName(): string
    {
        return 'static_fallback';
    }

    /**
     * Parse the prompt context.
     *
     * @param string $prompt JSON or plain text prompt
     * @return array<string, mixed> Parsed context
     */
    private function parseContext(string $prompt): array
    {
        // Handle null bytes
        $prompt = str_replace("\0", '', $prompt);

        // Try to decode as JSON
        $decoded = json_decode($prompt, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }

        // Return empty context for non-JSON prompts
        return [];
    }

    /**
     * Analyze an indicator based on context.
     *
     * @param string $indicator The indicator type
     * @param array<string, mixed> $context The analysis context
     * @return array<string, mixed> Analysis result
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
            default => $this->analyzeDefault($indicator, $context),
        };
    }

    /**
     * Analyze title tag.
     */
    private function analyzeTitle(array $context): array
    {
        $title = $context['title'] ?? '';
        $keyword = $context['keyword'] ?? null;
        $score = 100;
        $issues = [];
        $suggestions = [];

        $length = mb_strlen(trim($title));

        // Length checks
        if ($length === 0) {
            $score -= 60;
            $issues[] = 'Title is empty. Every page needs a unique, descriptive title.';
        } elseif ($length < 30) {
            // Progressive penalty for short titles
            $penalty = (int)((30 - $length) / 5) * 5 + 5;
            $score -= min(25, $penalty);
            $issues[] = "Title is too short ({$length} characters). Aim for 50-60 characters for optimal SEO.";
            $suggestions[] = $this->createSuggestion('rewrite', $this->generateTitleSuggestion($keyword), 'Expand your title to include more relevant keywords and value proposition.');
        } elseif ($length > 60) {
            // Progressive penalty for long titles
            $excess = $length - 60;
            $penalty = min(35, 15 + (int)($excess / 20) * 5);
            $score -= $penalty;
            $issues[] = "Title is too long ({$length} characters). Keep under 60 characters to prevent truncation in search results.";
        }

        // Keyword check
        if ($keyword && $length > 0) {
            if (stripos($title, $keyword) === false) {
                $score -= 25;
                $issues[] = "Target keyword '{$keyword}' not found in title.";
                $suggestions[] = $this->createSuggestion('improve', $this->addKeywordToTitle($title, $keyword), "Add your target keyword '{$keyword}' to improve relevance.");
            }
        }

        // All caps check
        if ($length > 0 && strtoupper($title) === $title && preg_match('/[A-Z]/', $title)) {
            $score -= 10;
            $issues[] = 'Title is in all caps. Use title case or sentence case for better readability.';
        }

        // Stop words check
        $stopWordsOnly = $this->isStopWordsOnly($title);
        if ($stopWordsOnly && $length > 0) {
            $score -= 40;
            $issues[] = 'Title contains only common words. Add specific, descriptive terms.';
        }

        // Duplicate words check
        $words = preg_split('/\s+/', strtolower($title));
        $wordCounts = array_count_values(array_filter($words));
        foreach ($wordCounts as $word => $count) {
            if ($count > 1 && strlen($word) > 3) {
                $issues[] = "Duplicate word '{$word}' detected. Avoid keyword stuffing.";
                $score -= 5;
                break;
            }
        }

        // Brand-only check (short title without descriptive content)
        // Penalize titles that look like brand names only (e.g., "ACME Corp")
        if ($length > 0 && $length <= 12 && strpos($title, '|') === false && strpos($title, '-') === false && strpos($title, ':') === false) {
            // Check if it looks like a brand-only title (short, no separators)
            $genericSuffixes = ['corp', 'inc', 'llc', 'ltd', 'co', 'company', 'llp'];
            $hasSuffix = false;
            foreach ($genericSuffixes as $suffix) {
                if (stripos($title, $suffix) !== false) {
                    $hasSuffix = true;
                    break;
                }
            }
            // Only penalize if it has a business suffix
            if ($hasSuffix) {
                $score -= 30;
                $issues[] = 'Title appears to be brand-only. Add descriptive content for better SEO.';
            }
        }

        return $this->buildResult('title', $score, $issues, $suggestions, $this->getTitleBestPractices());
    }

    /**
     * Analyze meta description.
     */
    private function analyzeMetaDescription(array $context): array
    {
        $description = $context['description'] ?? '';
        $keyword = $context['keyword'] ?? null;
        $title = $context['title'] ?? null;
        $score = 100;
        $issues = [];
        $suggestions = [];

        $length = mb_strlen(trim($description));

        // Length checks - be more lenient for descriptions with good content
        $hasKeyword = $keyword && stripos($description, $keyword) !== false;
        $hasCTA = $this->hasCTA($description);

        if ($length === 0) {
            $score -= 60;
            $issues[] = 'Meta description is empty. Every page should have a unique description.';
        } elseif ($length < 120) {
            // Reduced penalty if has keyword and CTA
            $basePenalty = min(15, (int)((120 - $length) / 30) * 5);
            if ($hasKeyword && $hasCTA) {
                $basePenalty = (int)($basePenalty / 2);
            }
            $score -= $basePenalty;
            if ($basePenalty > 5) {
                $issues[] = "Meta description is too short ({$length} characters). Aim for 150-160 characters.";
                $suggestions[] = $this->createSuggestion('improve', $this->expandDescription($description, $keyword), 'Expand your description to provide more context.');
            }
        } elseif ($length > 160) {
            $score -= 15;
            $issues[] = "Meta description is too long ({$length} characters). Keep under 160 characters to prevent truncation.";
        }

        // CTA check
        if ($length > 0) {
            $ctaCount = $this->countCTAs($description);
            if ($ctaCount === 0) {
                $score -= 10;
                $issues[] = 'No call-to-action found. Add phrases like "Shop now", "Learn more", or "Get started".';
                $suggestions[] = $this->createSuggestion('add', $this->addCTAToDescription($description), 'Add a call-to-action to encourage clicks.');
            } elseif ($ctaCount > 2) {
                $score -= 25;
                $issues[] = 'Too many call-to-action phrases. One clear CTA is more effective than multiple.';
            }
        }

        // Keyword check
        if ($keyword && $length > 0 && !$hasKeyword) {
            $score -= 15;
            $issues[] = "Target keyword '{$keyword}' not found in description.";
        }

        // Duplicate of title check
        if ($title && trim($description) !== '' && trim($description) === trim($title)) {
            $score -= 20;
            $issues[] = 'Meta description is identical to title. Create a unique, compelling description.';
        }

        // HTML entity check
        if (preg_match('/&(?:amp|lt|gt|quot|#\d+);/i', $description)) {
            $score -= 5;
            $issues[] = 'HTML entities detected. Use actual characters instead of encoded entities.';
        }

        // Phone number check - boost score for phone numbers with CTA
        if ($length > 0 && preg_match('/\b\d{3}[-.]?\d{4}\b/', $description)) {
            $score += 5; // Phone number is good for local SEO
        }

        // Price mention check - good for e-commerce
        if ($length > 0 && preg_match('/\$[\d,.]+|\d+\s*(dollars?|USD)/i', $description)) {
            $score += 5;
        }

        return $this->buildResult('meta_description', $score, $issues, $suggestions, $this->getMetaDescriptionBestPractices());
    }

    /**
     * Analyze robots.txt.
     */
    private function analyzeRobots(array $context): array
    {
        $robotsContent = $context['robots_content'] ?? '';
        $score = 100;
        $issues = [];
        $suggestions = [];

        $content = trim($robotsContent);

        if ($content === '') {
            $score -= 40;
            $issues[] = 'No robots.txt content provided. Add a robots.txt file to control crawler behavior.';
            $suggestions[] = $this->createSuggestion('add', "User-agent: *\nAllow: /\nSitemap: https://example.com/sitemap.xml", 'Create a basic robots.txt file.');
        }

        // Check for blocking entire site
        if (preg_match('/User-agent:\s*\*\s*Disallow:\s*\/\s*$/im', $content)) {
            $score -= 60;
            $issues[] = 'CRITICAL: Site is completely blocked from search engines. Remove "Disallow: /" to allow indexing.';
        }

        // Check for blocking CSS/JS
        if (preg_match('/Disallow:\s*\/css\//i', $content) || preg_match('/Disallow:\s*\/js\//i', $content)) {
            $score -= 20;
            $issues[] = 'CSS or JavaScript files are blocked. This can prevent proper rendering in search results.';
        }

        // Check for sitemap reference
        if (!preg_match('/Sitemap:\s*https?:\/\//i', $content)) {
            $score -= 10;
            $issues[] = 'No sitemap URL specified. Add a Sitemap directive to help search engines discover your pages.';
            $suggestions[] = $this->createSuggestion('add', 'Sitemap: https://example.com/sitemap.xml', 'Add your sitemap URL to robots.txt.');
        }

        // Check for exposing sensitive paths
        $sensitivePaths = ['admin', 'wp-admin', 'wp-login', 'login', 'dashboard'];
        foreach ($sensitivePaths as $path) {
            if (preg_match('/Disallow:\s*\/' . preg_quote($path, '/') . '/i', $content)) {
                $issues[] = "Sensitive path '/{$path}/' exposed in robots.txt. Consider using other security measures instead.";
                break;
            }
        }

        return $this->buildResult('robots', $score, $issues, $suggestions, $this->getRobotsBestPractices());
    }

    /**
     * Analyze sitemap.
     */
    private function analyzeSitemap(array $context): array
    {
        $sitemapContent = $context['sitemap_content'] ?? $context['content'] ?? '';
        $urlCount = $context['url_count'] ?? 0;
        $score = 100;
        $issues = [];
        $suggestions = [];

        $content = trim($sitemapContent);

        if ($content === '') {
            $score = 40;
            $issues[] = 'No sitemap content provided. Create an XML sitemap to help search engines discover your pages.';
        } else {
            // Check for XML structure
            $hasValidXml = preg_match('/<\?xml|<urlset|<sitemapindex/i', $content);
            if (!$hasValidXml) {
                $score = 40;
                $issues[] = 'Invalid sitemap format. Use proper XML structure with urlset or sitemapindex.';
            } else {
                // Check for URLs
                if (!preg_match('/<loc>/i', $content) && $urlCount === 0) {
                    $score -= 40;
                    $issues[] = 'No URLs found in sitemap. Add <loc> elements for each page.';
                } elseif ($urlCount > 0) {
                    // Boost score based on URL count
                    if ($urlCount >= 2) {
                        $score = min(100, $score + 5);
                    }
                    if ($urlCount >= 10) {
                        $score = min(100, $score + 5);
                    }
                }
            }
        }

        return $this->buildResult('sitemap', $score, $issues, $suggestions, $this->getSitemapBestPractices());
    }

    /**
     * Analyze heading structure.
     */
    private function analyzeHeadings(array $context): array
    {
        $headings = $context['headings'] ?? [];
        $title = $context['title'] ?? null;
        $keyword = $context['keyword'] ?? null;
        $score = 100;
        $issues = [];
        $suggestions = [];

        if (empty($headings)) {
            $score -= 50;
            $issues[] = 'No headings found. Use proper heading structure (H1-H6) to organize content.';
            $suggestions[] = $this->createSuggestion('add', 'Add an H1 heading followed by H2 subheadings', 'Create a logical heading hierarchy.');
            return $this->buildResult('headings', $score, $issues, $suggestions, $this->getHeadingsBestPractices());
        }

        // Count H1s
        $h1Count = 0;
        $h1Text = '';
        $previousLevel = 0;
        $hasEmptyHeadings = false;

        foreach ($headings as $heading) {
            $level = $heading['level'] ?? 0;
            $text = trim($heading['text'] ?? '');

            if ($level === 1) {
                $h1Count++;
                $h1Text = $text;
            }

            // Check for empty headings
            if ($text === '') {
                $hasEmptyHeadings = true;
            }

            // Check hierarchy
            if ($level > $previousLevel && $level > $previousLevel + 1 && $previousLevel > 0) {
                $issues[] = "Heading level skipped: H{$previousLevel} to H{$level}. Maintain proper hierarchy.";
                $score -= 10;
            }
            $previousLevel = $level;
        }

        // H1 checks
        if ($h1Count === 0) {
            $score -= 40;
            $issues[] = 'Missing H1 heading. Every page should have exactly one H1.';
            $suggestions[] = $this->createSuggestion('add', $keyword ? "{$keyword} | Your Brand" : 'Main Page Title', 'Add an H1 heading to define the main topic.');
        } elseif ($h1Count > 1) {
            $score -= 25;
            $issues[] = "Multiple H1 headings found ({$h1Count}). Use only one H1 per page.";
        }

        // Empty headings check
        if ($hasEmptyHeadings) {
            $score -= 15;
            $issues[] = 'Empty headings detected. All headings should contain descriptive text.';
        }

        // H1 matches title
        if ($h1Text && $title && $h1Text === $title) {
            // This is actually good - boost not needed as it's expected
        }

        // Keyword in H1
        if ($keyword && $h1Text && stripos($h1Text, $keyword) === false) {
            $score -= 10;
            $issues[] = "Target keyword '{$keyword}' not found in H1.";
        }

        return $this->buildResult('headings', $score, $issues, $suggestions, $this->getHeadingsBestPractices());
    }

    /**
     * Analyze image alt text.
     */
    private function analyzeImageAlt(array $context): array
    {
        $images = $context['images'] ?? [];
        $score = 100;
        $issues = [];
        $suggestions = [];

        if (empty($images)) {
            return $this->buildResult('image_alt', $score, $issues, $suggestions, $this->getImageAltBestPractices());
        }

        $missingAlt = 0;
        $genericAlt = 0;
        $keywordInAlt = 0;
        $keyword = $context['keyword'] ?? null;

        foreach ($images as $image) {
            $alt = $image['alt'] ?? null;
            $src = $image['src'] ?? '';

            if ($alt === null || $alt === '') {
                $missingAlt++;
            } else {
                // Check for generic alt text
                $genericPatterns = ['image', 'photo', 'picture', 'img', 'icon', 'logo'];
                if (in_array(strtolower($alt), $genericPatterns)) {
                    $genericAlt++;
                }

                // Check for keyword
                if ($keyword && stripos($alt, $keyword) !== false) {
                    $keywordInAlt++;
                }
            }
        }

        if ($missingAlt > 0) {
            $score -= min(40, $missingAlt * 10);
            $issues[] = "{$missingAlt} image(s) missing alt text. Add descriptive alt text for accessibility and SEO.";
        }

        if ($genericAlt > 0) {
            $score -= min(20, $genericAlt * 5);
            $issues[] = "{$genericAlt} image(s) have generic alt text. Use specific, descriptive text instead.";
        }

        return $this->buildResult('image_alt', $score, $issues, $suggestions, $this->getImageAltBestPractices());
    }

    /**
     * Analyze filenames for SEO.
     */
    private function analyzeFilenames(array $context): array
    {
        $files = $context['files'] ?? [];
        $keyword = $context['keyword'] ?? null;
        $score = 100;
        $issues = [];
        $suggestions = [];

        if (empty($files)) {
            return $this->buildResult('filenames', $score, $issues, $suggestions, $this->getFilenamesBestPractices());
        }

        $badPatterns = 0;
        $keywordInFilename = 0;

        foreach ($files as $file) {
            $filename = basename($file);

            // Check for bad patterns
            if (preg_match('/[^a-z0-9\-_.]/i', $filename) && !preg_match('/\.(jpg|jpeg|png|gif|webp|svg)$/i', $filename)) {
                $badPatterns++;
            }

            // Check for spaces or special chars
            if (preg_match('/[\s_]/', $filename)) {
                $issues[] = "Filename '{$filename}' contains spaces or underscores. Use hyphens instead.";
                $score -= 5;
            }

            // Check for keyword
            if ($keyword && stripos($filename, $keyword) !== false) {
                $keywordInFilename++;
            }
        }

        return $this->buildResult('filenames', $score, $issues, $suggestions, $this->getFilenamesBestPractices());
    }

    /**
     * Analyze URL structure.
     */
    private function analyzeUrl(array $context): array
    {
        $url = $context['url'] ?? '';
        $keyword = $context['keyword'] ?? null;
        $score = 100;
        $issues = [];
        $suggestions = [];

        if (empty($url)) {
            $score = 30;
            $issues[] = 'No URL provided for analysis.';
            return $this->buildResult('url', $score, $issues, $suggestions, $this->getUrlBestPractices());
        }

        $parsed = parse_url($url);

        // Protocol check
        if (($parsed['scheme'] ?? '') === 'http') {
            $score -= 15;
            $issues[] = 'URL uses HTTP instead of HTTPS. Secure URLs are preferred by search engines.';
        }

        // Dynamic URL patterns - check this first for better suggestions
        $isDynamic = preg_match('/\.(php|asp|aspx|jsp)/i', $url);
        if ($isDynamic) {
            $score -= 20;
            $issues[] = 'Dynamic URL detected. Consider using static URLs with rewrite rules.';
        }

        // Query parameters
        if (!empty($parsed['query'])) {
            $score -= 20;
            $issues[] = 'URL contains query parameters. Consider using clean URLs for better SEO.';
            $cleanUrl = $this->suggestCleanUrl($url);
            $suggestions[] = $this->createSuggestion('rewrite', "Rewrite to clean URL: {$cleanUrl}", 'Convert to a clean URL structure for better SEO.');
        } elseif ($isDynamic) {
            // Add suggestion for dynamic URLs even without query params
            $cleanUrl = $this->suggestCleanUrl($url);
            $suggestions[] = $this->createSuggestion('rewrite', "Rewrite to clean URL: {$cleanUrl}", 'Use URL rewriting to create clean, SEO-friendly URLs.');
        }

        // Underscores
        if (strpos($parsed['path'] ?? '', '_') !== false) {
            $score -= 10;
            $issues[] = 'URL contains underscores. Use hyphens instead for better readability.';
        }

        // URL length
        if (mb_strlen($url) > 100) {
            $score -= 10;
            $issues[] = 'URL is very long. Keep URLs concise and descriptive.';
        }

        // Keyword in URL
        if ($keyword && stripos($url, str_replace(' ', '-', $keyword)) !== false) {
            // Keyword found in URL - good
        } elseif ($keyword) {
            $issues[] = "Target keyword '{$keyword}' not found in URL slug.";
            $score -= 5;
        }

        return $this->buildResult('url', $score, $issues, $suggestions, $this->getUrlBestPractices());
    }

    /**
     * Analyze links.
     */
    private function analyzeLinks(array $context): array
    {
        $links = $context['links'] ?? [];
        $score = 100;
        $issues = [];
        $suggestions = [];

        $internalCount = 0;
        $externalCount = 0;
        $brokenCount = 0;

        foreach ($links as $link) {
            $type = $link['type'] ?? 'internal';
            if ($type === 'internal') {
                $internalCount++;
            } else {
                $externalCount++;
            }
            if (($link['status'] ?? 200) >= 400) {
                $brokenCount++;
            }
        }

        if ($internalCount === 0) {
            $score -= 20;
            $issues[] = 'No internal links found. Add internal links to help users and search engines navigate.';
            $suggestions[] = $this->createSuggestion('add', 'Add internal links to related content pages', 'Internal links help distribute page authority and improve navigation.');
        }

        if ($brokenCount > 0) {
            $score -= min(30, $brokenCount * 10);
            $issues[] = "{$brokenCount} broken link(s) detected. Fix or remove broken links.";
        }

        // Check for nofollow on external links
        foreach ($links as $link) {
            if (($link['type'] ?? '') === 'external' && !($link['nofollow'] ?? false)) {
                // This is fine for trusted links
            }
        }

        return $this->buildResult('links', $score, $issues, $suggestions, $this->getLinksBestPractices());
    }

    /**
     * Analyze 404 handling.
     */
    private function analyze404(array $context): array
    {
        $url = $context['url'] ?? '';
        $referrer = $context['referrer'] ?? null;
        $score = 100;
        $issues = [];
        $suggestions = [];

        // Parse the URL to understand the 404 context
        $parsed = parse_url($url);
        $path = $parsed['path'] ?? '';

        // Check for common patterns that suggest redirect opportunities
        $redirectPatterns = [
            '/old-/', '/products/', '/product/', '/category/', '/blog/', '/news/',
            '/archive/', '/deprecated/', '/legacy/', '/moved/', '/old'
        ];
        
        $shouldSuggestRedirect = false;
        foreach ($redirectPatterns as $pattern) {
            if (stripos($path, $pattern) !== false) {
                $shouldSuggestRedirect = true;
                break;
            }
        }

        // Always suggest redirect for any 404 URL
        if (!empty($path) && $path !== '/') {
            $shouldSuggestRedirect = true;
        }

        if ($shouldSuggestRedirect) {
            $suggestions[] = $this->createSuggestion(
                'improve',
                'Set up a 301 redirect to the most relevant existing page',
                'Redirect this broken URL to preserve SEO value and improve user experience.'
            );
        }

        // Check for referrer context
        if ($referrer) {
            $referrerHost = parse_url($referrer, PHP_URL_HOST);
            $currentHost = $parsed['host'] ?? '';
            
            if ($referrerHost === $currentHost) {
                $issues[] = 'Internal broken link detected. Fix the link on the referring page.';
            } else {
                $issues[] = 'External link points to a 404 page. Consider redirecting or contacting the linking site.';
            }
        }

        // Check for custom 404 page
        $hasCustom404 = $context['has_custom_404'] ?? false;
        if (!$hasCustom404) {
            $score -= 20;
            $issues[] = 'No custom 404 page detected. Create a helpful 404 page with navigation options.';
            $suggestions[] = $this->createSuggestion('add', 'Create a custom 404 page with navigation links and search functionality', 'Improve user experience for broken links.');
        }

        // Check for common URL issues
        if (strpos($path, '.html') !== false || strpos($path, '.php') !== false) {
            $issues[] = 'File extension in URL may indicate outdated URL structure.';
            $suggestions[] = $this->createSuggestion(
                'rewrite',
                'Remove file extensions from URLs and implement clean URLs',
                'Clean URLs are more user-friendly and SEO-friendly.'
            );
        }

        return $this->buildResult('404', $score, $issues, $suggestions, $this->get404BestPractices());
    }

    /**
     * Default analysis for unknown indicators.
     */
    private function analyzeDefault(string $indicator, array $context): array
    {
        return $this->buildResult($indicator, 50, [], [], $this->getDefaultBestPractices());
    }

    /**
     * Build the result array.
     */
    private function buildResult(string $indicator, int $score, array $issues, array $suggestions, array $bestPractices): array
    {
        return [
            'indicator' => $indicator,
            'score' => max(0, min(100, $score)),
            'issues' => $issues,
            'suggestions' => $suggestions,
            'best_practices' => $bestPractices,
            'provider' => 'static_fallback',
            'model' => 'rule-based',
            'generated_at' => date('c'),
        ];
    }

    /**
     * Create a suggestion array.
     */
    private function createSuggestion(string $type, string $text, string $reason = ''): array
    {
        return [
            'type' => in_array($type, self::VALID_SUGGESTION_TYPES) ? $type : 'improve',
            'text' => $text,
            'reason' => $reason,
        ];
    }

    // =====================================================
    // Helper Methods
    // =====================================================

    private function isStopWordsOnly(string $text): bool
    {
        $stopWords = ['the', 'a', 'an', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by'];
        $words = preg_split('/\s+/', strtolower(trim($text)));
        $words = array_filter($words);
        if (empty($words)) {
            return false;
        }
        foreach ($words as $word) {
            if (!in_array($word, $stopWords)) {
                return false;
            }
        }
        return true;
    }

    private function generateTitleSuggestion(?string $keyword): string
    {
        if ($keyword) {
            return ucfirst($keyword) . ' | Premium Quality Products 2026';
        }
        return 'Your Page Title | Brand Name';
    }

    private function addKeywordToTitle(string $title, string $keyword): string
    {
        if (empty($title)) {
            return ucfirst($keyword) . ' | Your Brand';
        }
        return ucfirst($keyword) . ' - ' . $title;
    }

    private function countCTAs(string $text): int
    {
        $ctaPatterns = [
            '/\b(buy\s*now|shop\s*now|order\s*now|get\s*started|learn\s*more|sign\s*up|subscribe|contact\s*us|call\s*now|book\s*now|try\s*free|free\s*trial|download\s*now|read\s*more|view\s*more|see\s*more|find\s*out\s*more|discover|explore|shop\s*our|shop\s*today|order\s*today|buy\s*today|order\s*here|get\s*it\s*now|act\s*now|start\s*now|join\s*now|register\s*now|apply\s*now|claim\s*now)\b/i'
        ];
        $count = 0;
        foreach ($ctaPatterns as $pattern) {
            $count += preg_match_all($pattern, $text);
        }
        return $count;
    }

    private function hasCTA(string $text): bool
    {
        return $this->countCTAs($text) > 0;
    }

    private function expandDescription(string $description, ?string $keyword): string
    {
        if ($keyword) {
            return "Discover our premium {$keyword} that deliver exceptional quality. Shop now and enjoy free shipping!";
        }
        return 'Discover our premium products that deliver exceptional quality and value. Shop now!';
    }

    private function addCTAToDescription(string $description): string
    {
        $cta = ' Shop now!';
        if (mb_strlen($description) + mb_strlen($cta) <= 160) {
            return $description . $cta;
        }
        return $description;
    }

    private function suggestCleanUrl(string $url): string
    {
        $parsed = parse_url($url);
        $path = $parsed['path'] ?? '/';
        $query = $parsed['query'] ?? '';

        if ($query) {
            $params = [];
            parse_str($query, $params);
            if (isset($params['id']) || isset($params['slug'])) {
                $slug = $params['slug'] ?? $params['id'] ?? '';
                $path = rtrim($path, '/') . '/' . $slug;
            }
        }

        return ($parsed['scheme'] ?? 'https') . '://' . ($parsed['host'] ?? 'example.com') . $path;
    }

    // =====================================================
    // Best Practices Methods
    // =====================================================

    private function getDefaultBestPractices(): array
    {
        return [
            'Keep content focused and relevant to your target audience.',
            'Use keywords naturally throughout your content.',
            'Ensure all pages have unique, descriptive titles and descriptions.',
            'Regularly audit your site for technical SEO issues.',
        ];
    }

    private function getTitleBestPractices(): array
    {
        return [
            'Keep titles between 50-60 characters for optimal display in search results.',
            'Place your primary keyword near the beginning of the title.',
            'Make each title unique and descriptive for its page.',
            'Avoid keyword stuffing - write for humans first.',
            'Include your brand name at the end when appropriate.',
        ];
    }

    private function getMetaDescriptionBestPractices(): array
    {
        return [
            'Keep meta descriptions between 150-160 characters.',
            'Include a clear call-to-action to encourage clicks.',
            'Naturally incorporate your target keyword.',
            'Make each description unique and compelling.',
            'Match the description to the page content for better user experience.',
        ];
    }

    private function getRobotsBestPractices(): array
    {
        return [
            'Always include a Sitemap directive pointing to your XML sitemap.',
            'Use "Allow: /" by default, only block specific directories.',
            'Do not block CSS, JavaScript, or image files needed for rendering.',
            'Use separate rules for different user agents when needed.',
            'Test your robots.txt in Google Search Console.',
        ];
    }

    private function getSitemapBestPractices(): array
    {
        return [
            'Include only canonical URLs in your sitemap.',
            'Keep the sitemap under 50,000 URLs or 50MB (uncompressed).',
            'Use lastmod dates accurately for freshness signals.',
            'Submit your sitemap to Google Search Console.',
            'Reference your sitemap in robots.txt.',
        ];
    }

    private function getHeadingsBestPractices(): array
    {
        return [
            'Use exactly one H1 heading per page.',
            'Maintain proper hierarchy: H1 → H2 → H3 (never skip levels).',
            'Include your target keyword in the H1.',
            'Use headings to structure content, not for visual styling.',
            'Make headings descriptive and scannable.',
        ];
    }

    private function getImageAltBestPractices(): array
    {
        return [
            'Add descriptive alt text to all meaningful images.',
            'Keep alt text under 125 characters for screen reader compatibility.',
            'Describe the image content, not just keywords.',
            'Use empty alt="" for decorative images only.',
            'Include keywords naturally when relevant to the image.',
        ];
    }

    private function getFilenamesBestPractices(): array
    {
        return [
            'Use lowercase letters and hyphens (not underscores or spaces).',
            'Include relevant keywords in filenames.',
            'Keep filenames short but descriptive.',
            'Use consistent naming conventions across your site.',
            'Avoid special characters and query parameters in filenames.',
        ];
    }

    private function getUrlBestPractices(): array
    {
        return [
            'Use HTTPS for all URLs.',
            'Keep URLs short, descriptive, and human-readable.',
            'Use hyphens to separate words (not underscores).',
            'Include relevant keywords in the URL slug.',
            'Avoid query parameters when possible - use clean URLs.',
        ];
    }

    private function getLinksBestPractices(): array
    {
        return [
            'Include internal links to help users navigate related content.',
            'Use descriptive anchor text instead of "click here".',
            'Check for and fix broken links regularly.',
            'Use rel="nofollow" for untrusted or sponsored external links.',
            'Link to authoritative external sources when relevant.',
        ];
    }

    private function get404BestPractices(): array
    {
        return [
            'Create a custom 404 page that matches your site design.',
            'Include navigation links to help users find content.',
            'Add a search function on the 404 page.',
            'Monitor 404 errors and redirect important broken URLs.',
            'Return proper 404 HTTP status code for not-found pages.',
        ];
    }
}
