<?php

namespace App\Services\AI\Providers;

use App\Services\AI\Contracts\AIProviderInterface;
use App\Services\AI\DTOs\AIResponse;
use DateTimeImmutable;

/**
 * Static fallback provider for SEO suggestions.
 *
 * Provides rule-based SEO analysis without external AI dependencies.
 * Always available and responds quickly for graceful degradation scenarios.
 */
class StaticFallbackProvider implements AIProviderInterface
{
    /**
     * Valid suggestion types.
     */
    private const VALID_SUGGESTION_TYPES = ['improve', 'rewrite', 'add', 'remove', 'warning'];

    /**
     * Generic stop words that don't add SEO value.
     */
    private const STOP_WORDS = ['the', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by', 'a', 'an', 'is', 'are', 'was', 'were', 'be', 'been', 'being', 'have', 'has', 'had', 'do', 'does', 'did', 'will', 'would', 'could', 'should', 'may', 'might', 'must', 'shall'];

    /**
     * Generic anchor text patterns.
     */
    private const GENERIC_ANCHORS = ['click here', 'read more', 'learn more', 'here', 'more', 'link', 'this', 'click', 'go', 'visit'];

    /**
     * Generate SEO suggestions based on the provided context.
     *
     * @param string $prompt JSON-encoded context with indicator type and content
     * @param array<string, mixed> $options Optional parameters
     * @return AIResponse The analysis result
     */
    public function generate(string $prompt, array $options = []): AIResponse
    {
        $startTime = microtime(true);

        // Parse context from prompt
        $context = $this->parseContext($prompt);
        $indicator = $context['indicator'] ?? 'unknown';

        // Analyze based on indicator type
        $result = $this->analyzeIndicator($indicator, $context);

        $latencyMs = (int) ((microtime(true) - $startTime) * 1000);

        return AIResponse::success(
            content: json_encode($result, JSON_UNESCAPED_UNICODE),
            tokensUsed: 0,
            latencyMs: $latencyMs
        );
    }

    /**
     * Always available - no external dependencies.
     *
     * @return bool Always true
     */
    public function isAvailable(): bool
    {
        return true;
    }

    /**
     * Get the provider name.
     *
     * @return string
     */
    public function getName(): string
    {
        return 'static_fallback';
    }

    /**
     * Parse context from prompt string.
     *
     * @param string $prompt
     * @return array<string, mixed>
     */
    private function parseContext(string $prompt): array
    {
        // Try JSON decode first
        $decoded = json_decode($prompt, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }

        // Try to extract indicator from text
        if (preg_match('/indicator[:\s]+["\']?(\w+)["\']?/i', $prompt, $matches)) {
            return ['indicator' => $matches[1]];
        }

        // Default context
        return ['indicator' => 'unknown'];
    }

    /**
     * Analyze based on indicator type.
     *
     * @param string $indicator
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    private function analyzeIndicator(string $indicator, array $context): array
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
            default => $this->analyzeUnknown($context),
        };
    }

    /**
     * Analyze title tag.
     *
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    private function analyzeTitle(array $context): array
    {
        $title = $context['title'] ?? '';
        $keyword = $context['keyword'] ?? null;
        $score = 100;
        $issues = [];
        $suggestions = [];
        $bestPractices = [
            'Keep title between 50-60 characters for optimal display',
            'Place primary keyword near the beginning of the title',
            'Make each page title unique and descriptive',
            'Include brand name at the end if appropriate',
            'Avoid keyword stuffing and clickbait titles',
        ];

        // Length analysis
        $trimmedTitle = trim($title);
        $length = mb_strlen($trimmedTitle);

        // Whitespace-only check (different from empty)
        if (mb_strlen($title) > 0 && $length === 0) {
            $score = 40;
            $issues[] = 'Title contains only whitespace. Add a descriptive title for your page.';
            $suggestions[] = [
                'type' => 'add',
                'text' => 'Add a descriptive title like "Your Product/Service - Brand Name"',
                'reason' => 'Every page needs a unique, descriptive title',
            ];
        } elseif ($length === 0) {
            $score = 45;
            $issues[] = 'Title is empty. Add a descriptive title for your page.';
            $suggestions[] = [
                'type' => 'add',
                'text' => 'Add a descriptive title like "Your Product/Service - Brand Name"',
                'reason' => 'Every page needs a unique, descriptive title',
            ];
        } elseif ($length < 30) {
            // Progressive penalty for short titles
            $shortPenalty = (int) ((30 - $length) / 5) * 5;
            $score -= min(40, $shortPenalty);
            $issues[] = "Title is too short ({$length} chars). Aim for 50-60 characters for better SEO.";
            $suggestions[] = [
                'type' => 'improve',
                'text' => $this->suggestLongerTitle($title, $keyword),
                'reason' => 'Longer titles provide more context for search engines',
            ];

            // Check for brand-only title (short with few words, looks like a brand name)
            $words = preg_split('/\s+/', $trimmedTitle);
            $wordCount = count(array_filter($words));
            
            // Common words that indicate a descriptive title, not a brand
            $descriptiveWords = ['home', 'page', 'welcome', 'about', 'contact', 'services', 'products', 'blog', 'news', 'faq', 'help', 'search'];
            $lowerWords = array_map('mb_strtolower', $words);
            $hasDescriptiveWord = !empty(array_intersect($lowerWords, $descriptiveWords));
            
            // Single word up to 15 chars OR 2 words up to 20 chars without descriptive words - likely brand
            if (!$hasDescriptiveWord && (($wordCount === 1 && $length <= 15) || ($wordCount === 2 && $length <= 20))) {
                $score -= 15;
                $issues[] = 'Title appears to be brand-only. Add descriptive keywords.';
            }
        } elseif ($length > 60) {
            // Progressive penalty for long titles
            $excess = $length - 60;
            $longPenalty = min(45, 10 + (int) ($excess / 10) * 5);
            $score -= $longPenalty;
            $issues[] = "Title is too long ({$length} chars). Search engines may truncate it. Keep under 60 characters.";
        }

        // Whitespace check (leading/trailing)
        if ($length > 0 && $trimmedTitle !== $title) {
            $score -= 5;
            $issues[] = 'Title has leading or trailing whitespace.';
        }

        // Keyword analysis
        if ($keyword && $length > 0) {
            $titleLower = mb_strtolower($title);
            $keywordLower = mb_strtolower($keyword);

            if (mb_strpos($titleLower, $keywordLower) === false) {
                $score -= 25;
                $issues[] = "Target keyword '{$keyword}' not found in title.";

                $suggestions[] = [
                    'type' => 'rewrite',
                    'text' => $this->suggestTitleWithKeyword($title, $keyword),
                    'reason' => 'Including target keyword improves relevance signals',
                ];
            } elseif (mb_strpos($titleLower, $keywordLower) === 0) {
                // Keyword at start - bonus
                $score = min(100, $score + 5);
            }
        }

        // All caps check
        if ($trimmedTitle === mb_strtoupper($trimmedTitle) && $length > 3) {
            $score -= 15;
            $issues[] = 'Title is in all caps. Use proper title case for better readability.';
        }

        // Duplicate words check
        if ($length > 0) {
            $words = preg_split('/\s+/', mb_strtolower($trimmedTitle));
            $wordCounts = array_count_values(array_filter($words));
            foreach ($wordCounts as $word => $count) {
                if ($count > 2 && mb_strlen($word) > 2 && !in_array($word, self::STOP_WORDS, true)) {
                    $score -= 5;
                    $issues[] = "Word '{$word}' appears {$count} times. Consider removing duplicates.";
                    break;
                }
            }

            // Stop words only check
            $nonStopWords = array_diff(array_filter($words), self::STOP_WORDS);
            if (count($nonStopWords) === 0) {
                $score -= 30;
                $issues[] = 'Title contains only stop words. Add meaningful keywords.';
            }
        }

        return [
            'indicator' => 'title',
            'score' => max(0, min(100, $score)),
            'issues' => $issues,
            'suggestions' => $suggestions,
            'best_practices' => $bestPractices,
            'provider' => 'static_fallback',
            'model' => 'rule-based',
            'generated_at' => (new DateTimeImmutable())->format('c'),
        ];
    }

    /**
     * Suggest a longer title.
     */
    private function suggestLongerTitle(string $title, ?string $keyword): string
    {
        if ($keyword) {
            return "{$keyword} - Premium Quality | Your Brand";
        }
        return "{$title} - Learn More | Your Brand";
    }

    /**
     * Suggest title with keyword.
     */
    private function suggestTitleWithKeyword(string $title, string $keyword): string
    {
        $keyword = trim($keyword);
        return "{$keyword} - " . trim($title) . " | Your Brand";
    }

    /**
     * Analyze meta description.
     *
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    private function analyzeMetaDescription(array $context): array
    {
        $description = $context['description'] ?? '';
        $keyword = $context['keyword'] ?? null;
        $title = $context['title'] ?? null;
        $score = 100;
        $issues = [];
        $suggestions = [];
        $bestPractices = [
            'Keep meta description between 150-160 characters',
            'Include a clear call-to-action to encourage clicks',
            'Make each meta description unique for every page',
            'Include target keywords naturally in the description',
            'Avoid duplicating the title tag as meta description',
        ];

        $length = mb_strlen(trim($description));

        // Empty check
        if ($length === 0) {
            $score = 45;
            $issues[] = 'Meta description is empty. Add a compelling description to improve click-through rates.';
            $suggestions[] = [
                'type' => 'add',
                'text' => 'Write a compelling 155-character description that includes your target keyword and a call-to-action.',
                'reason' => 'Meta descriptions improve click-through rates from search results',
            ];
        } else {
            // Length analysis
            if ($length < 120) {
                // Progressive penalty for short descriptions (more lenient)
                $shortPenalty = (int) ((120 - $length) / 40) * 5;
                $score -= min(20, $shortPenalty);
                $issues[] = "Meta description is too short ({$length} chars). Aim for 150-160 characters.";
                
                // Add suggestion for short descriptions
                $suggestions[] = [
                    'type' => 'improve',
                    'text' => 'Expand your meta description to 150-160 characters for better visibility in search results.',
                    'reason' => 'Longer descriptions provide more context and can improve click-through rates',
                ];
            } elseif ($length > 160) {
                $score -= min(10 + (int) (($length - 160) / 20), 25);
                $issues[] = "Meta description is too long ({$length} chars). Search engines may truncate it. Keep under 160 characters.";
            }

            // Duplicate of title check
            if ($title && trim(mb_strtolower($description)) === trim(mb_strtolower($title))) {
                $score -= 20;
                $issues[] = 'Meta description duplicates the title. Write unique content for each.';
            }

            // Keyword check
            if ($keyword) {
                if (mb_stripos($description, $keyword) === false) {
                    $score -= 15;
                    $issues[] = "Target keyword '{$keyword}' not found in meta description.";
                }
            }

            // CTA check
            $ctaPatterns = ['buy', 'shop', 'order', 'get', 'find', 'discover', 'learn', 'try', 'start', 'call', 'visit', 'download', 'sign up', 'contact', 'book', 'save', 'free'];
            $hasCta = false;
            $ctaCount = 0;
            foreach ($ctaPatterns as $cta) {
                if (mb_stripos($description, $cta) !== false) {
                    $hasCta = true;
                    $ctaCount++;
                }
            }

            if (!$hasCta) {
                $score -= 10;
                $suggestions[] = [
                    'type' => 'improve',
                    'text' => 'Consider adding a call-to-action like "Shop now", "Learn more", or "Get started today".',
                    'reason' => 'CTAs improve click-through rates from search results',
                ];
            } elseif ($ctaCount === 1) {
                // Bonus for having exactly one CTA (ideal)
                $score += 5;
            }

            // Multiple CTAs check (spammy)
            if ($ctaCount >= 3) {
                $score -= 20;
                $issues[] = 'Multiple calls-to-action detected. This may appear spammy. Focus on one clear CTA.';
            }

            // HTML entities check
            if (preg_match('/&[a-z]+;/i', $description)) {
                $score -= 5;
                $issues[] = 'HTML entities detected in meta description. Use plain text instead.';
            }
        }

        return [
            'indicator' => 'meta_description',
            'score' => max(0, min(100, $score)),
            'issues' => $issues,
            'suggestions' => $suggestions,
            'best_practices' => $bestPractices,
            'provider' => 'static_fallback',
            'model' => 'rule-based',
            'generated_at' => (new DateTimeImmutable())->format('c'),
        ];
    }

    /**
     * Analyze robots.txt content.
     *
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    private function analyzeRobots(array $context): array
    {
        $content = $context['robots_content'] ?? '';
        $score = 100;
        $issues = [];
        $suggestions = [];
        $bestPractices = [
            'Always include a robots.txt file in your root directory',
            'Reference your XML sitemap in robots.txt',
            'Use Allow directives to explicitly permit crawling',
            'Be careful with Disallow: / as it blocks all crawlers',
            'Test your robots.txt in Google Search Console',
        ];

        // Empty check
        if (empty(trim($content))) {
            $score = 50;
            $issues[] = 'Robots.txt is empty or missing. Add crawl directives.';
            $suggestions[] = [
                'type' => 'add',
                'text' => "User-agent: *\nAllow: /\nSitemap: https://yoursite.com/sitemap.xml",
                'reason' => 'A basic robots.txt allows all crawlers and references your sitemap',
            ];

            return [
                'indicator' => 'robots',
                'score' => $score,
                'issues' => $issues,
                'suggestions' => $suggestions,
                'best_practices' => $bestPractices,
                'provider' => 'static_fallback',
                'model' => 'rule-based',
                'generated_at' => (new DateTimeImmutable())->format('c'),
            ];
        }

        // Check for blocking all
        if (preg_match('/disallow:\s*\/\s*$/im', $content)) {
            $score -= 50;
            $issues[] = 'WARNING: robots.txt blocks all crawlers from the entire site with "Disallow: /"';
            $suggestions[] = [
                'type' => 'warning',
                'text' => 'Remove "Disallow: /" or change to specific paths if this is unintentional',
                'reason' => 'Blocking all crawlers prevents search engine indexing',
            ];
        }

        // Check for blocking CSS/JS
        if (preg_match('/disallow:\s*\/css\//im', $content) || preg_match('/disallow:\s*\/js\//im', $content)) {
            $score -= 20;
            $issues[] = 'CSS or JavaScript directories are blocked. This may affect rendering and indexing.';
        }

        // Check for sitemap reference
        if (!preg_match('/sitemap:/i', $content)) {
            $score -= 10;
            $issues[] = 'No sitemap reference found in robots.txt.';
            $suggestions[] = [
                'type' => 'add',
                'text' => 'Add: Sitemap: https://yoursite.com/sitemap.xml',
                'reason' => 'Sitemap reference helps search engines discover your content',
            ];
        }

        // Check for User-agent
        if (!preg_match('/user-agent:/i', $content)) {
            $score -= 15;
            $issues[] = 'No User-agent directive found. Add one to specify crawler rules.';
        }

        // Check for admin path exposure
        if (preg_match('/disallow:\s*\/(admin|wp-admin)\//im', $content)) {
            // This is a note, not necessarily an issue
            $issues[] = 'Note: Admin paths are listed in robots.txt. While this prevents crawling, it also reveals these paths exist.';
        }

        return [
            'indicator' => 'robots',
            'score' => max(0, min(100, $score)),
            'issues' => $issues,
            'suggestions' => $suggestions,
            'best_practices' => $bestPractices,
            'provider' => 'static_fallback',
            'model' => 'rule-based',
            'generated_at' => (new DateTimeImmutable())->format('c'),
        ];
    }

    /**
     * Analyze sitemap.
     *
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    private function analyzeSitemap(array $context): array
    {
        $content = $context['sitemap_content'] ?? '';
        $urlCount = $context['url_count'] ?? 0;
        $score = 100;
        $issues = [];
        $suggestions = [];
        $bestPractices = [
            'Keep sitemap under 50MB and 50,000 URLs',
            'Include only canonical URLs that should be indexed',
            'Use lastmod to indicate when content was last modified',
            'Compress large sitemaps with gzip',
            'Submit sitemap to Google Search Console and Bing Webmaster Tools',
        ];

        // Empty check
        if (empty(trim($content))) {
            $score = 40;
            $issues[] = 'Sitemap is empty or missing.';
            $suggestions[] = [
                'type' => 'add',
                'text' => 'Generate an XML sitemap with all important pages',
                'reason' => 'Sitemaps help search engines discover and index your content',
            ];

            return [
                'indicator' => 'sitemap',
                'score' => $score,
                'issues' => $issues,
                'suggestions' => $suggestions,
                'best_practices' => $bestPractices,
                'provider' => 'static_fallback',
                'model' => 'rule-based',
                'generated_at' => (new DateTimeImmutable())->format('c'),
            ];
        }

        // XML validation
        libxml_use_internal_errors(true);
        $xmlValid = simplexml_load_string($content) !== false;
        libxml_clear_errors();

        if (!$xmlValid) {
            $score -= 31;
            $issues[] = 'Sitemap contains invalid XML. Fix syntax errors.';
        }

        // Check for urlset or sitemapindex
        $isSitemapIndex = preg_match('/<sitemapindex/i', $content);
        $hasUrlset = preg_match('/<urlset/i', $content);

        if (!$isSitemapIndex && !$hasUrlset) {
            $score -= 20;
            $issues[] = 'Sitemap must have either <urlset> or <sitemapindex> root element.';
        }

        // URL count check
        if ($urlCount === 0 && $hasUrlset) {
            $score -= 25;
            $issues[] = 'Sitemap contains no URLs. Add pages to your sitemap.';
        } elseif ($urlCount > 50000) {
            $score -= 10;
            $issues[] = "Sitemap has {$urlCount} URLs. Split into multiple sitemaps (limit is 50,000 per file).";
        }

        // Check for lastmod
        if ($hasUrlset && !preg_match('/<lastmod>/i', $content)) {
            $score -= 5;
            $issues[] = 'Consider adding <lastmod> tags to indicate content freshness.';
        }

        // Check for loc tags
        if ($hasUrlset && !preg_match('/<loc>/i', $content)) {
            $score -= 30;
            $issues[] = 'URLs missing <loc> tags. Each URL entry must have a location.';
        }

        return [
            'indicator' => 'sitemap',
            'score' => max(0, min(100, $score)),
            'issues' => $issues,
            'suggestions' => $suggestions,
            'best_practices' => $bestPractices,
            'provider' => 'static_fallback',
            'model' => 'rule-based',
            'generated_at' => (new DateTimeImmutable())->format('c'),
        ];
    }

    /**
     * Analyze heading structure.
     *
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    private function analyzeHeadings(array $context): array
    {
        $headings = $context['headings'] ?? [];
        $title = $context['title'] ?? null;
        $keyword = $context['keyword'] ?? null;
        $score = 100;
        $issues = [];
        $suggestions = [];
        $bestPractices = [
            'Use exactly one H1 heading per page',
            'Maintain logical heading hierarchy (H1 → H2 → H3)',
            'Include target keywords in headings naturally',
            'Make headings descriptive and scannable',
            'Avoid skipping heading levels',
        ];

        // No headings check
        if (empty($headings)) {
            $score = 50;
            $issues[] = 'No headings found on the page. Add structured headings for better SEO.';
            $suggestions[] = [
                'type' => 'add',
                'text' => 'Add an H1 heading and organize content with H2-H6 subheadings',
                'reason' => 'Headings improve readability and help search engines understand content structure',
            ];

            return [
                'indicator' => 'headings',
                'score' => $score,
                'issues' => $issues,
                'suggestions' => $suggestions,
                'best_practices' => $bestPractices,
                'provider' => 'static_fallback',
                'model' => 'rule-based',
                'generated_at' => (new DateTimeImmutable())->format('c'),
            ];
        }

        // Count H1s
        $h1Count = 0;
        $h1Text = '';
        $levels = [];
        $emptyHeadings = 0;

        foreach ($headings as $heading) {
            $level = $heading['level'] ?? 0;
            $text = trim($heading['text'] ?? '');

            $levels[] = $level;

            if ($level === 1) {
                $h1Count++;
                $h1Text = $text;
            }

            if ($text === '') {
                $emptyHeadings++;
            }
        }

        // H1 checks
        if ($h1Count === 0) {
            $score -= 30;
            $issues[] = 'No H1 heading found. Every page should have exactly one H1.';
            $suggestions[] = [
                'type' => 'add',
                'text' => 'Add an H1 heading that describes the main topic of the page',
                'reason' => 'H1 is the most important heading for SEO',
            ];
        } elseif ($h1Count > 1) {
            $score -= 20;
            $issues[] = "Multiple H1 headings found ({$h1Count}). Use only one H1 per page.";
            $suggestions[] = [
                'type' => 'remove',
                'text' => 'Keep only one H1 and change others to H2 or lower',
                'reason' => 'Multiple H1s dilute the page\'s main topic signal',
            ];
        }

        // Empty headings check
        if ($emptyHeadings > 0) {
            $score -= 10;
            $issues[] = "{$emptyHeadings} heading(s) are empty. Add descriptive text.";
        }

        // Hierarchy check
        $prevLevel = 0;
        foreach ($levels as $level) {
            if ($prevLevel > 0 && $level > $prevLevel + 1) {
                $score -= 10;
                $issues[] = "Heading hierarchy skips from H{$prevLevel} to H{$level}. Maintain proper nesting.";
                break;
            }
            $prevLevel = $level;
        }

        // H1 vs title check
        if ($h1Count === 1 && $title) {
            $h1Lower = mb_strtolower(trim($h1Text));
            $titleLower = mb_strtolower(trim($title));
            if ($h1Lower === $titleLower) {
                // This is good for SEO
            }
        }

        // Keyword in H1 check
        if ($keyword && $h1Count === 1 && $h1Text) {
            if (mb_stripos($h1Text, $keyword) === false) {
                $score -= 10;
                $issues[] = "Target keyword '{$keyword}' not found in H1 heading.";
            }
        }

        return [
            'indicator' => 'headings',
            'score' => max(0, min(100, $score)),
            'issues' => $issues,
            'suggestions' => $suggestions,
            'best_practices' => $bestPractices,
            'provider' => 'static_fallback',
            'model' => 'rule-based',
            'generated_at' => (new DateTimeImmutable())->format('c'),
        ];
    }

    /**
     * Analyze image alt text.
     *
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    private function analyzeImageAlt(array $context): array
    {
        $images = $context['images'] ?? [];
        $keyword = $context['keyword'] ?? null;
        $score = 100;
        $issues = [];
        $suggestions = [];
        $bestPractices = [
            'Add descriptive alt text to all meaningful images',
            'Keep alt text under 125 characters',
            'Include keywords naturally when relevant',
            'Use empty alt="" for decorative images',
            'Avoid generic descriptions like "image" or "photo"',
        ];

        // No images check
        if (empty($images)) {
            return [
                'indicator' => 'image_alt',
                'score' => 80,
                'issues' => [],
                'suggestions' => [],
                'best_practices' => $bestPractices,
                'provider' => 'static_fallback',
                'model' => 'rule-based',
                'generated_at' => (new DateTimeImmutable())->format('c'),
            ];
        }

        $totalImages = count($images);
        $missingAlt = 0;
        $genericAlt = 0;
        $longAlt = 0;
        $keywordStuffed = 0;
        $withKeyword = 0;

        foreach ($images as $image) {
            $alt = $image['alt'] ?? null;

            if ($alt === null || $alt === '') {
                $missingAlt++;
                continue;
            }

            $altLength = mb_strlen($alt);

            // Check for generic alt text
            $genericPatterns = ['image', 'photo', 'picture', 'img', 'icon', 'logo', 'graphic', 'graphic'];
            $altLower = mb_strtolower($alt);
            foreach ($genericPatterns as $pattern) {
                if ($altLower === $pattern || $altLower === $pattern . 's') {
                    $genericAlt++;
                    break;
                }
            }

            // Check for too long alt
            if ($altLength > 125) {
                $longAlt++;
            }

            // Check for keyword stuffing
            if ($keyword) {
                $keywordCount = substr_count($altLower, mb_strtolower($keyword));
                if ($keywordCount > 2) {
                    $keywordStuffed++;
                } elseif ($keywordCount > 0) {
                    $withKeyword++;
                }
            }
        }

        // Score adjustments
        $coverage = ($totalImages - $missingAlt) / $totalImages;

        if ($missingAlt > 0) {
            $score -= (int) ($missingAlt / $totalImages * 40);
            $issues[] = "{$missingAlt} image(s) missing alt text out of {$totalImages}.";
            $suggestions[] = [
                'type' => 'add',
                'text' => 'Add descriptive alt text to all meaningful images',
                'reason' => 'Alt text improves accessibility and helps search engines understand images',
            ];
        }

        if ($genericAlt > 0) {
            $score -= 10;
            $issues[] = "{$genericAlt} image(s) have generic alt text like 'image' or 'photo'. Use descriptive text.";
        }

        if ($longAlt > 0) {
            $score -= 5;
            $issues[] = "{$longAlt} image(s) have alt text over 125 characters. Keep it concise.";
        }

        if ($keywordStuffed > 0) {
            $score -= 15;
            $issues[] = "{$keywordStuffed} image(s) have keyword stuffing in alt text. Use natural language.";
        }

        if ($keyword && $withKeyword > 0) {
            $score += 5; // Bonus for including keyword naturally
        }

        return [
            'indicator' => 'image_alt',
            'score' => max(0, min(100, $score)),
            'issues' => $issues,
            'suggestions' => $suggestions,
            'best_practices' => $bestPractices,
            'provider' => 'static_fallback',
            'model' => 'rule-based',
            'generated_at' => (new DateTimeImmutable())->format('c'),
        ];
    }

    /**
     * Analyze filenames.
     *
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    private function analyzeFilenames(array $context): array
    {
        $files = $context['files'] ?? [];
        $score = 100;
        $issues = [];
        $suggestions = [];
        $bestPractices = [
            'Use lowercase letters with hyphens (e.g., my-file.jpg)',
            'Keep filenames descriptive and concise',
            'Avoid special characters, spaces, and underscores',
            'Include relevant keywords when appropriate',
            'Use proper file extensions',
        ];

        // No files check
        if (empty($files)) {
            return [
                'indicator' => 'filenames',
                'score' => 80,
                'issues' => [],
                'suggestions' => [],
                'best_practices' => $bestPractices,
                'provider' => 'static_fallback',
                'model' => 'rule-based',
                'generated_at' => (new DateTimeImmutable())->format('c'),
            ];
        }

        $issuesFound = 0;

        foreach ($files as $file) {
            $name = $file['name'] ?? '';
            $fileIssues = [];

            // Spaces check
            if (preg_match('/\s/', $name)) {
                $fileIssues[] = 'contains spaces';
            }

            // Uppercase check
            if (preg_match('/[A-Z]/', $name)) {
                $fileIssues[] = 'contains uppercase';
            }

            // Special characters check
            if (preg_match('/[^a-zA-Z0-9.\-_]/', $name)) {
                $fileIssues[] = 'contains special characters';
            }

            // Underscores check
            if (preg_match('/_/', $name)) {
                $fileIssues[] = 'uses underscores';
            }

            // Generic name check
            $genericPatterns = ['image', 'img', 'photo', 'pic', 'file', 'document', 'doc'];
            $baseName = pathinfo($name, PATHINFO_FILENAME);
            foreach ($genericPatterns as $pattern) {
                if (preg_match('/^' . preg_quote($pattern, '/') . '\d*$/i', $baseName)) {
                    $fileIssues[] = 'is generic';
                    break;
                }
            }

            // Too long check
            if (mb_strlen($name) > 100) {
                $fileIssues[] = 'is too long';
            }

            // Hash-like check
            if (preg_match('/^[a-f0-9]{16,}$/i', $baseName)) {
                $fileIssues[] = 'appears to be a hash';
            }

            // Missing extension
            if (!preg_match('/\.[a-z0-9]+$/i', $name)) {
                $fileIssues[] = 'missing extension';
            }

            $issuesFound += count($fileIssues);
        }

        // Calculate score based on issues
        if ($issuesFound > 0) {
            $penalty = min(50, $issuesFound * 5);
            $score -= $penalty;

            if ($issuesFound >= count($files) * 2) {
                $issues[] = 'Multiple filename issues detected across files.';
            }

            $suggestions[] = [
                'type' => 'improve',
                'text' => 'Use lowercase filenames with hyphens instead of spaces or underscores',
                'reason' => 'SEO-friendly filenames improve discoverability and URL readability',
            ];
        }

        return [
            'indicator' => 'filenames',
            'score' => max(0, min(100, $score)),
            'issues' => $issues,
            'suggestions' => $suggestions,
            'best_practices' => $bestPractices,
            'provider' => 'static_fallback',
            'model' => 'rule-based',
            'generated_at' => (new DateTimeImmutable())->format('c'),
        ];
    }

    /**
     * Analyze URL structure.
     *
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    private function analyzeUrl(array $context): array
    {
        $url = $context['url'] ?? '';
        $keyword = $context['keyword'] ?? null;
        $score = 100;
        $issues = [];
        $suggestions = [];
        $bestPractices = [
            'Use hyphens to separate words in URLs',
            'Keep URLs short, descriptive, and lowercase',
            'Avoid dynamic URL parameters when possible',
            'Use HTTPS for security and SEO benefits',
            'Include target keywords naturally in the URL path',
        ];

        // Empty check
        if (empty($url)) {
            $score = 40;
            $issues[] = 'URL is empty.';

            return [
                'indicator' => 'url',
                'score' => $score,
                'issues' => $issues,
                'suggestions' => $suggestions,
                'best_practices' => $bestPractices,
                'provider' => 'static_fallback',
                'model' => 'rule-based',
                'generated_at' => (new DateTimeImmutable())->format('c'),
            ];
        }

        // Parse URL
        $parsed = parse_url($url);
        $path = $parsed['path'] ?? '';
        $scheme = $parsed['scheme'] ?? '';
        $host = $parsed['host'] ?? '';
        $query = $parsed['query'] ?? '';

        // HTTPS check
        if ($scheme !== 'https') {
            $score -= 15;
            $issues[] = 'URL is not using HTTPS. Secure URLs are preferred by search engines.';
        }

        // Query parameters check
        if (!empty($query)) {
            $score -= 20;
            $issues[] = 'URL contains query parameters. Consider using clean URLs when possible.';
            $suggestions[] = [
                'type' => 'rewrite',
                'text' => 'Use URL rewriting to create clean, SEO-friendly URLs',
                'reason' => 'Clean URLs are easier for users and search engines to understand',
            ];
        }

        // Dynamic URL check
        if (preg_match('/\.php|\.asp|\.aspx/i', $path)) {
            $score -= 15;
            $issues[] = 'URL appears to be dynamic (contains .php, .asp, etc.). Consider URL rewriting.';
        }

        // Underscores check
        if (preg_match('/_/', $path)) {
            $score -= 10;
            $issues[] = 'URL contains underscores. Use hyphens instead for better SEO.';
        }

        // Uppercase check
        if (preg_match('/[A-Z]/', $path)) {
            $score -= 5;
            $issues[] = 'URL contains uppercase letters. Use lowercase for consistency.';
        }

        // Length check
        if (mb_strlen($path) > 100) {
            $score -= 10;
            $issues[] = 'URL path is very long. Keep it concise and focused.';
        }

        // Keyword check
        if ($keyword) {
            $pathLower = mb_strtolower($path);
            $keywordLower = mb_strtolower($keyword);
            $keywordInUrl = str_replace(' ', '-', $keywordLower);

            if (mb_strpos($pathLower, $keywordInUrl) !== false) {
                $score += 5; // Bonus for keyword in URL
            } else {
                // Penalty for missing target keyword
                $score -= 5;
                $issues[] = "Target keyword '{$keyword}' not found in URL path.";
            }
        } else {
            // Without keyword context, penalize generic paths
            $pathSegments = array_filter(explode('/', trim($path, '/')));
            $lastSegment = end($pathSegments);
            
            // Check for generic/numeric path segments
            if ($lastSegment && preg_match('/^(item-\d+|product|page|post|category)$/i', $lastSegment)) {
                $score -= 5;
                $issues[] = 'URL path appears generic. Include descriptive keywords.';
            }
        }

        return [
            'indicator' => 'url',
            'score' => max(0, min(100, $score)),
            'issues' => $issues,
            'suggestions' => $suggestions,
            'best_practices' => $bestPractices,
            'provider' => 'static_fallback',
            'model' => 'rule-based',
            'generated_at' => (new DateTimeImmutable())->format('c'),
        ];
    }

    /**
     * Analyze links.
     *
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    private function analyzeLinks(array $context): array
    {
        $links = $context['links'] ?? [];
        $baseUrl = $context['base_url'] ?? 'https://example.com';
        $score = 100;
        $issues = [];
        $suggestions = [];
        $bestPractices = [
            'Include relevant internal links on every page',
            'Use descriptive anchor text instead of "click here"',
            'Add rel="noopener" to external links with target="_blank"',
            'Fix or remove broken links promptly',
            'Balance internal and external links appropriately',
        ];

        // No links check
        if (empty($links)) {
            $score = 50;
            $issues[] = 'No links found on the page. Add relevant internal and external links.';
            $suggestions[] = [
                'type' => 'add',
                'text' => 'Add internal links to related pages and external links to authoritative sources',
                'reason' => 'Links help search engines discover content and establish site structure',
            ];

            return [
                'indicator' => 'links',
                'score' => $score,
                'issues' => $issues,
                'suggestions' => $suggestions,
                'best_practices' => $bestPractices,
                'provider' => 'static_fallback',
                'model' => 'rule-based',
                'generated_at' => (new DateTimeImmutable())->format('c'),
            ];
        }

        $internalCount = 0;
        $externalCount = 0;
        $brokenCount = 0;
        $genericAnchorCount = 0;
        $missingNoopener = 0;
        $missingNofollow = 0;

        foreach ($links as $link) {
            $type = $link['type'] ?? 'internal';
            $status = $link['status'] ?? 200;
            $anchor = $link['anchor'] ?? '';
            $target = $link['target'] ?? null;
            $rel = $link['rel'] ?? '';

            if ($type === 'internal') {
                $internalCount++;
            } elseif ($type === 'external') {
                $externalCount++;

                // Check for nofollow on external
                if ($rel === null || !preg_match('/nofollow/i', $rel)) {
                    $missingNofollow++;
                }
            }

            // Broken check
            if ($status >= 400) {
                $brokenCount++;
            }

            // Generic anchor check
            $anchorLower = mb_strtolower(trim($anchor));
            if (in_array($anchorLower, self::GENERIC_ANCHORS, true)) {
                $genericAnchorCount++;
            }

            // Security check for target="_blank"
            if ($target === '_blank' && ($rel === null || !preg_match('/noopener/i', $rel))) {
                $missingNoopener++;
            }
        }

        // Score adjustments
        if ($brokenCount > 0) {
            $penalty = min(40, $brokenCount * 10);
            $score -= $penalty;
            $issues[] = "{$brokenCount} broken link(s) found. Fix or remove them.";
        }

        if ($internalCount === 0) {
            $score -= 20;
            $issues[] = 'No internal links found. Add links to other pages on your site.';
        } elseif ($internalCount > 100) {
            $score -= 10;
            $issues[] = 'Many internal links may dilute link equity. Consider reducing.';
        }

        if ($externalCount > 10) {
            $score -= 10;
            $issues[] = 'Many external links detected. Ensure they are relevant and properly attributed.';
        }

        if ($genericAnchorCount > 0) {
            $score -= min(15, $genericAnchorCount * 5);
            $issues[] = "{$genericAnchorCount} link(s) use generic anchor text like 'click here'. Use descriptive text.";
        }

        if ($missingNoopener > 0) {
            $score -= min(10, $missingNoopener * 3);
            $issues[] = "{$missingNoopener} external link(s) with target='_blank' missing rel='noopener'. This is a security risk.";
        }

        if ($missingNofollow > 0) {
            $suggestions[] = [
                'type' => 'improve',
                'text' => "Consider adding rel='nofollow' to {$missingNofollow} external link(s) you don't vouch for",
                'reason' => 'nofollow helps prevent passing authority to untrusted sites',
            ];
        }

        return [
            'indicator' => 'links',
            'score' => max(0, min(100, $score)),
            'issues' => $issues,
            'suggestions' => $suggestions,
            'best_practices' => $bestPractices,
            'provider' => 'static_fallback',
            'model' => 'rule-based',
            'generated_at' => (new DateTimeImmutable())->format('c'),
        ];
    }

    /**
     * Analyze 404 page.
     *
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    private function analyze404(array $context): array
    {
        $url = $context['url'] ?? '';
        $referrer = $context['referrer'] ?? null;
        $score = 70; // Base score for 404
        $issues = [];
        $suggestions = [];
        $bestPractices = [
            'Create a custom 404 page with helpful navigation',
            'Include links to popular pages and a search function',
            'Monitor 404 errors in Google Search Console',
            'Set up 301 redirects for moved or deleted pages',
            'Keep the URL structure consistent to prevent future 404s',
        ];

        // Parse URL
        $parsed = parse_url($url);
        $path = $parsed['path'] ?? '';

        // Empty URL
        if (empty($url)) {
            $score = 50;
            $issues[] = 'URL is empty.';

            return [
                'indicator' => '404',
                'score' => $score,
                'issues' => $issues,
                'suggestions' => $suggestions,
                'best_practices' => $bestPractices,
                'provider' => 'static_fallback',
                'model' => 'rule-based',
                'generated_at' => (new DateTimeImmutable())->format('c'),
            ];
        }

        // Analyze potential causes
        $potentialCauses = [];

        // File extension in URL
        if (preg_match('/\.(html?|php|aspx?)$/i', $path)) {
            $potentialCauses[] = 'URL contains file extension which may indicate outdated structure';
        }

        // Query parameters
        if (!empty($parsed['query'])) {
            $potentialCauses[] = 'URL has query parameters that may no longer be valid';
        }

        // Trailing slash
        if (preg_match('/\/$/', $path) && mb_strlen($path) > 1) {
            // This could be a trailing slash mismatch
        }

        // Referrer analysis
        if ($referrer) {
            $referrerParsed = parse_url($referrer);
            $referrerHost = $referrerParsed['host'] ?? '';
            $urlHost = $parsed['host'] ?? '';

            if ($referrerHost === $urlHost) {
                $issues[] = 'Broken internal link detected. The linking page on your site should be updated.';
            } else {
                $issues[] = 'External site is linking to this non-existent page. Consider setting up a redirect.';
            }
        }

        // Suggestions
        $suggestions[] = [
            'type' => 'improve',
            'text' => 'Set up a 301 redirect to a relevant existing page',
            'reason' => 'Redirects preserve link equity and improve user experience',
        ];

        $suggestions[] = [
            'type' => 'add',
            'text' => 'Create a custom 404 page with navigation and search',
            'reason' => 'Custom 404 pages help users find what they were looking for',
        ];

        if (!empty($potentialCauses)) {
            foreach ($potentialCauses as $cause) {
                $issues[] = $cause;
            }
        }

        return [
            'indicator' => '404',
            'score' => max(0, min(100, $score)),
            'issues' => $issues,
            'suggestions' => $suggestions,
            'best_practices' => $bestPractices,
            'provider' => 'static_fallback',
            'model' => 'rule-based',
            'generated_at' => (new DateTimeImmutable())->format('c'),
        ];
    }

    /**
     * Analyze unknown indicator type.
     *
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    private function analyzeUnknown(array $context): array
    {
        return [
            'indicator' => 'unknown',
            'score' => 70,
            'issues' => ['Unknown indicator type provided. Unable to provide specific analysis.'],
            'suggestions' => [
                [
                    'type' => 'warning',
                    'text' => 'Provide a valid indicator type for specific SEO analysis',
                    'reason' => 'Valid indicators include: title, meta_description, robots, sitemap, headings, image_alt, filenames, url, links, 404',
                ],
            ],
            'best_practices' => [
                'Ensure all page elements are optimized for search engines',
                'Follow Google\'s SEO guidelines for best results',
                'Regular audits help maintain SEO health',
            ],
            'provider' => 'static_fallback',
            'model' => 'rule-based',
            'generated_at' => (new DateTimeImmutable())->format('c'),
        ];
    }
}
