<?php

namespace App\Services\AI\Providers;

use App\Services\AI\Contracts\AIProviderInterface;
use App\Services\AI\DTOs\AIResponse;
use DateTimeImmutable;
use InvalidArgumentException;

/**
 * Static Fallback AI Provider.
 *
 * Provides rule-based SEO suggestions without external AI dependencies.
 * This ensures the suggestion system always returns useful results,
 * even when AI providers are unavailable or rate-limited.
 *
 * Implements the AIProviderInterface for seamless integration with
 * the AI suggestion service.
 */
class StaticFallbackProvider implements AIProviderInterface
{
    /**
     * Valid indicator types and their analyzer methods.
     */
    private const INDICATOR_ANALYZERS = [
        'title' => 'analyzeTitle',
        'meta_description' => 'analyzeMetaDescription',
        'robots' => 'analyzeRobots',
        'sitemap' => 'analyzeSitemap',
        'headings' => 'analyzeHeadings',
        'image_alt' => 'analyzeImageAlt',
        'filenames' => 'analyzeFilenames',
        'url' => 'analyzeUrl',
        'links' => 'analyzeLinks',
        '404' => 'analyzeNotFound',
    ];

    /**
     * Common call-to-action phrases.
     */
    private const CTA_PHRASES = [
        'buy now', 'shop now', 'order now', 'get started', 'learn more',
        'sign up', 'subscribe', 'contact us', 'call today', 'book now',
        'try free', 'download', 'view more', 'read more', 'discover',
        'explore', 'find out', 'see more', 'start now', 'join now',
        'save now', 'shop today', 'order today', 'get it now',
    ];

    /**
     * Stop words for title analysis.
     */
    private const STOP_WORDS = [
        'the', 'a', 'an', 'and', 'or', 'but', 'in', 'on', 'at', 'to',
        'for', 'of', 'with', 'by', 'from', 'is', 'it', 'this', 'that',
    ];

    /**
     * Generate an AI response for the given prompt.
     *
     * @param string $prompt The prompt/context to analyze (JSON encoded context)
     * @param array<string, mixed> $options Optional parameters (ignored for static provider)
     * @return AIResponse The response containing SEO suggestions
     */
    public function generate(string $prompt, array $options = []): AIResponse
    {
        $startTime = microtime(true);

        try {
            $context = $this->parseContext($prompt);
            $indicator = $context['indicator'] ?? 'unknown';
            $result = $this->analyze($indicator, $context);

            $latencyMs = (int) ((microtime(true) - $startTime) * 1000);

            $content = json_encode([
                'indicator' => $indicator,
                'score' => $result['score'],
                'issues' => $result['issues'],
                'suggestions' => $result['suggestions'],
                'best_practices' => $result['best_practices'],
                'provider' => 'static_fallback',
                'model' => 'rule-based',
                'generated_at' => (new DateTimeImmutable())->format('c'),
            ], JSON_THROW_ON_ERROR);

            return new AIResponse(
                success: true,
                content: $content,
                tokensUsed: 0,
                latencyMs: $latencyMs,
                errorMessage: null
            );
        } catch (\Exception $e) {
            $latencyMs = (int) ((microtime(true) - $startTime) * 1000);
            return AIResponse::error($e->getMessage(), $latencyMs);
        }
    }

    /**
     * Check if the provider is available.
     *
     * The static fallback is always available since it has no external dependencies.
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
     * @return string The provider name
     */
    public function getName(): string
    {
        return 'static_fallback';
    }

    /**
     * Parse the prompt/context string into an array.
     *
     * @param string $prompt The raw prompt string
     * @return array<string, mixed> The parsed context
     */
    private function parseContext(string $prompt): array
    {
        if (empty(trim($prompt))) {
            return [];
        }

        // Try to decode as JSON
        $decoded = json_decode($prompt, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }

        // Return raw prompt as context
        return ['raw' => $prompt];
    }

    /**
     * Route analysis to the appropriate indicator analyzer.
     *
     * @param string $indicator The indicator type
     * @param array<string, mixed> $context The analysis context
     * @return array<string, mixed> The analysis result
     */
    private function analyze(string $indicator, array $context): array
    {
        $analyzer = self::INDICATOR_ANALYZERS[$indicator] ?? null;

        if ($analyzer && method_exists($this, $analyzer)) {
            return $this->$analyzer($context);
        }

        return $this->analyzeUnknown($context);
    }

    // =====================================================
    // Title Analysis
    // =====================================================

    /**
     * Analyze title tag SEO.
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
            'Keep title under 60 characters for optimal display in search results.',
            'Place primary keyword near the beginning of the title.',
            'Make each page title unique and descriptive.',
            'Include brand name at the end if appropriate.',
        ];

        // Length analysis
        $length = mb_strlen(trim($title));

        if ($length === 0) {
            $score -= 50;
            $issues[] = 'Title is empty. Add a descriptive title for this page.';
            $suggestions[] = [
                'type' => 'add',
                'text' => $keyword ? "{$keyword} | Your Brand" : 'Page Title | Your Brand',
                'reason' => 'Add a descriptive title that includes your target keyword.',
            ];
        } elseif ($length < 30) {
            $score -= 20;
            $issues[] = "Title is too short ({$length} chars). Aim for 50-60 characters.";
            $suggestions[] = [
                'type' => 'improve',
                'text' => $this->expandTitle($title, $keyword),
                'reason' => 'Expand your title to be more descriptive.',
            ];

            // Check for brand-only titles (short titles that look like brand names)
            $words = preg_split('/\s+/', trim($title));
            $wordCount = count(array_filter($words));
            
            // Check if it looks like a brand name
            $brandIndicators = ['corp', 'inc', 'ltd', 'llc', 'co', 'company', 'group', 'studio', 'agency'];
            $titleLower = mb_strtolower($title);
            $hasBrandIndicator = false;
            foreach ($brandIndicators as $indicator) {
                if (str_contains($titleLower, $indicator)) {
                    $hasBrandIndicator = true;
                    break;
                }
            }
            
            // Brand-only detection: 1-3 words with brand indicator OR just 1-2 capitalized words
            if ($wordCount <= 3 && ($hasBrandIndicator || $wordCount <= 2)) {
                $score -= 15;
                $issues[] = 'Title appears to be a brand name only. Add descriptive content.';
                $suggestions[] = [
                    'type' => 'improve',
                    'text' => $title . ' | Products & Services',
                    'reason' => 'Add descriptive content after your brand name.',
                ];
            }
        } elseif ($length > 60) {
            $score -= 15;
            $issues[] = "Title is too long ({$length} chars). Keep under 60 characters to avoid truncation.";
            $suggestions[] = [
                'type' => 'rewrite',
                'text' => mb_substr($title, 0, 57) . '...',
                'reason' => 'Shorten the title to fit within search result limits.',
            ];
        }

        // Keyword analysis
        if ($keyword) {
            $keywordLower = mb_strtolower($keyword);
            $titleLower = mb_strtolower($title);

            if (!str_contains($titleLower, $keywordLower)) {
                $score -= 25;
                $issues[] = "Target keyword '{$keyword}' not found in title.";
                $suggestions[] = [
                    'type' => 'rewrite',
                    'text' => "{$keyword} | " . ($title ?: 'Your Brand'),
                    'reason' => 'Include your target keyword in the title.',
                ];
            } else {
                // Check keyword position (earlier is better)
                $keywordPosition = mb_strpos($titleLower, $keywordLower);
                if ($keywordPosition > 20) {
                    $score -= 5;
                    $issues[] = 'Consider placing the keyword earlier in the title.';
                }
            }
        }

        // All caps check
        if (mb_strlen($title) > 3 && $title === mb_strtoupper($title) && preg_match('/[a-zA-Z]/', $title)) {
            $score -= 10;
            $issues[] = 'Title is in all caps. Use proper title case for better readability.';
        }

        // Duplicate words check
        $words = preg_split('/\s+/', mb_strtolower(trim($title)));
        $wordCounts = array_count_values(array_filter($words));
        foreach ($wordCounts as $word => $count) {
            if ($count > 1 && mb_strlen($word) > 3 && !in_array($word, ['best', 'free', 'your'])) {
                $score -= 3;
                $issues[] = "Duplicate word '{$word}' detected in title.";
                break;
            }
        }

        // Stop words only check
        if ($length > 0) {
            $nonStopWords = array_diff(array_filter($words), self::STOP_WORDS);
            if (empty($nonStopWords) && !empty($words)) {
                $score -= 25;
                $issues[] = 'Title contains only common words. Add more descriptive content.';
            }
        }

        // Generate suggestions for short/empty titles
        if ($length < 30 && $keyword && empty($suggestions)) {
            $suggestions[] = [
                'type' => 'rewrite',
                'text' => "{$keyword} - Premium Quality & Best Value",
                'reason' => 'Expand title with keyword and value proposition.',
            ];
        }

        return [
            'score' => max(0, min(100, $score)),
            'issues' => $issues,
            'suggestions' => $suggestions,
            'best_practices' => $bestPractices,
        ];
    }

    /**
     * Expand a short title with relevant content.
     */
    private function expandTitle(string $title, ?string $keyword): string
    {
        $title = trim($title);
        if ($keyword && !str_contains(mb_strtolower($title), mb_strtolower($keyword))) {
            return "{$keyword} | {$title}";
        }
        return $title . ' | Premium Quality';
    }

    // =====================================================
    // Meta Description Analysis
    // =====================================================

    /**
     * Analyze meta description SEO.
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
            'Keep meta description between 150-160 characters.',
            'Include a clear call-to-action to encourage clicks.',
            'Make each meta description unique for every page.',
            'Include your target keyword naturally in the description.',
        ];

        $length = mb_strlen(trim($description));

        // Length analysis
        if ($length === 0) {
            $score -= 50;
            $issues[] = 'Meta description is empty. Add a compelling description to improve click-through rates.';
            $suggestions[] = [
                'type' => 'add',
                'text' => $keyword
                    ? "Discover our {$keyword} with exceptional quality. Shop now for the best deals!"
                    : 'Discover our premium products with exceptional quality. Shop now for the best deals!',
                'reason' => 'Add a compelling meta description with a call-to-action.',
            ];
        } elseif ($length < 120) {
            $score -= 15;
            $issues[] = "Meta description is too short ({$length} chars). Aim for 150-160 characters.";
            $suggestions[] = [
                'type' => 'improve',
                'text' => $description . ' Shop now and enjoy free shipping on all orders!',
                'reason' => 'Expand the description to be more compelling.',
            ];
        } elseif ($length > 160) {
            $score -= 10;
            $issues[] = "Meta description is too long ({$length} chars). Keep under 160 characters to avoid truncation.";
            $suggestions[] = [
                'type' => 'rewrite',
                'text' => mb_substr($description, 0, 157) . '...',
                'reason' => 'Shorten to fit within search result limits.',
            ];
        }

        // Keyword check
        if ($keyword && $length > 0) {
            $keywordLower = mb_strtolower($keyword);
            $descLower = mb_strtolower($description);

            if (!str_contains($descLower, $keywordLower)) {
                $score -= 15;
                $issues[] = "Target keyword '{$keyword}' not found in meta description.";
            }
        }

        // CTA check
        $descLower = mb_strtolower($description);
        $ctaCount = 0;
        foreach (self::CTA_PHRASES as $cta) {
            if (str_contains($descLower, $cta)) {
                $ctaCount++;
            }
        }

        if ($length > 0 && $ctaCount === 0) {
            $score -= 10;
            $issues[] = 'No clear call-to-action found. Add phrases like "Shop now" or "Learn more" to encourage clicks.';
            $suggestions[] = [
                'type' => 'improve',
                'text' => $description . ' Shop now!',
                'reason' => 'Add a call-to-action to improve click-through rates.',
            ];
        } elseif ($ctaCount > 2) {
            $score -= 10;
            $issues[] = 'Multiple call-to-action phrases detected. This may appear spammy.';
        }

        // Duplicate of title check
        if ($title && mb_strtolower(trim($description)) === mb_strtolower(trim($title))) {
            $score -= 15;
            $issues[] = 'Meta description is identical to the title. Create a unique, more detailed description.';
        }

        // HTML entities check
        if (preg_match('/&[a-z]+;/i', $description)) {
            $score -= 5;
            $issues[] = 'HTML entities detected in meta description. Consider using plain text characters.';
        }

        return [
            'score' => max(0, min(100, $score)),
            'issues' => $issues,
            'suggestions' => $suggestions,
            'best_practices' => $bestPractices,
        ];
    }

    // =====================================================
    // Robots.txt Analysis
    // =====================================================

    /**
     * Analyze robots.txt configuration.
     *
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    private function analyzeRobots(array $context): array
    {
        // Support multiple context key names for flexibility
        $content = $context['content'] ?? $context['robots_content'] ?? '';
        $score = 100;
        $issues = [];
        $suggestions = [];
        $bestPractices = [
            'Use robots.txt to control crawler access to your site.',
            'Allow important pages to be crawled and indexed.',
            'Block sensitive or duplicate content from being crawled.',
            'Reference your sitemap.xml in robots.txt.',
        ];

        if (empty(trim($content))) {
            $score -= 50;
            $issues[] = 'No robots.txt content provided. Ensure robots.txt exists on your site.';
            $suggestions[] = [
                'type' => 'add',
                'text' => "User-agent: *\nAllow: /\nSitemap: https://example.com/sitemap.xml",
                'reason' => 'Add a basic robots.txt file to control crawler behavior.',
            ];
        } else {
            // Check for common directives
            $contentLower = mb_strtolower($content);

            if (!str_contains($contentLower, 'user-agent')) {
                $score -= 20;
                $issues[] = 'No User-agent directive found in robots.txt.';
            }

            if (!str_contains($contentLower, 'allow') && !str_contains($contentLower, 'disallow')) {
                $score -= 15;
                $issues[] = 'No Allow or Disallow directives found.';
            }

            if (!str_contains($contentLower, 'sitemap')) {
                $score -= 10;
                $issues[] = 'Sitemap location not specified in robots.txt.';
                $suggestions[] = [
                    'type' => 'add',
                    'text' => 'Sitemap: https://example.com/sitemap.xml',
                    'reason' => 'Add sitemap URL to help search engines discover your content.',
                ];
            }

            // Check for overly restrictive rules (blocks entire site)
            if (preg_match('/disallow:\s*\//i', $contentLower)) {
                $score -= 60;
                $issues[] = 'WARNING: Site appears to block all crawlers (Disallow: /). This prevents indexing.';
                $suggestions[] = [
                    'type' => 'warning',
                    'text' => 'Remove or modify "Disallow: /" if you want your site indexed.',
                    'reason' => 'Blocking all crawlers prevents your site from appearing in search results.',
                ];
            }

            // Check for blocking CSS/JS
            if (preg_match('/disallow:\s*\/css/i', $contentLower) || 
                preg_match('/disallow:\s*\/js/i', $contentLower) ||
                preg_match('/disallow:\s*.*\.css/i', $contentLower) ||
                preg_match('/disallow:\s*.*\.js/i', $contentLower)) {
                $score -= 10;
                $issues[] = 'CSS or JavaScript files are blocked. This can harm rendering and SEO.';
            }
        }

        return [
            'score' => max(0, min(100, $score)),
            'issues' => $issues,
            'suggestions' => $suggestions,
            'best_practices' => $bestPractices,
        ];
    }

    // =====================================================
    // Sitemap Analysis
    // =====================================================

    /**
     * Analyze sitemap.xml configuration.
     *
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    private function analyzeSitemap(array $context): array
    {
        // Support multiple context key names for flexibility
        $content = $context['content'] ?? $context['sitemap_content'] ?? '';
        $urls = $context['urls'] ?? [];
        $urlCount = $context['url_count'] ?? count($urls);
        $score = 100;
        $issues = [];
        $suggestions = [];
        $bestPractices = [
            'Include all important pages in your sitemap.',
            'Keep sitemap under 50MB and 50,000 URLs (use sitemap index for larger sites).',
            'Update sitemap when adding or removing pages.',
            'Submit sitemap to Google Search Console and Bing Webmaster Tools.',
        ];

        if (empty(trim($content)) && $urlCount === 0) {
            $score -= 60;
            $issues[] = 'No sitemap content provided. Ensure sitemap.xml exists on your site.';
            $suggestions[] = [
                'type' => 'add',
                'text' => '<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="https://www.sitemaps.org/schemas/sitemap/0.9">
  <url><loc>https://example.com/</loc></url>
</urlset>',
                'reason' => 'Add a sitemap.xml to help search engines discover your pages.',
            ];
        } else {
            // Check for XML structure
            if (!empty($content)) {
                if (!str_contains($content, '<?xml') && !str_contains($content, '<urlset') && !str_contains($content, '<sitemapindex')) {
                    $score -= 30;
                    $issues[] = 'Invalid XML structure. Sitemap must be valid XML.';
                }

                if (!str_contains($content, '<urlset') && !str_contains($content, '<sitemapindex')) {
                    $score -= 25;
                    $issues[] = 'Invalid sitemap structure. Missing urlset or sitemapindex element.';
                }
            }

            // Check URL count
            if ($urlCount === 0 && !empty($content)) {
                preg_match_all('/<loc>([^<]+)<\/loc>/i', $content, $matches);
                $foundUrls = count($matches[1] ?? []);
                if ($foundUrls === 0) {
                    $score -= 40;
                    $issues[] = 'No URLs found in sitemap.';
                } elseif ($foundUrls < 5) {
                    $score -= 10;
                    $issues[] = "Only {$foundUrls} URLs in sitemap. Ensure all important pages are included.";
                }
                $urlCount = $foundUrls;
            }

            // Check for lastmod
            if (!empty($content) && !str_contains($content, '<lastmod')) {
                $score -= 5;
                $issues[] = 'Consider adding <lastmod> tags to indicate when pages were last modified.';
            }

            // Check for changefreq and priority (optional but good)
            if (!empty($content) && !str_contains($content, '<changefreq') && !str_contains($content, '<priority')) {
                // Minor note, no penalty
            }
        }

        return [
            'score' => max(0, min(100, $score)),
            'issues' => $issues,
            'suggestions' => $suggestions,
            'best_practices' => $bestPractices,
        ];
    }

    // =====================================================
    // Headings Analysis
    // =====================================================

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
            'Use exactly one H1 heading per page.',
            'Maintain a logical heading hierarchy (H1 → H2 → H3).',
            'Include your target keyword in the H1 heading.',
            'Use headings to structure content, not just for styling.',
        ];

        if (empty($headings)) {
            $score -= 50;
            $issues[] = 'No headings found on this page. Add structured headings for better SEO.';
            $suggestions[] = [
                'type' => 'add',
                'text' => '<h1>Main Page Title</h1>',
                'reason' => 'Add an H1 heading to define the main topic of your page.',
            ];
        } else {
            // Count H1s
            $h1s = array_filter($headings, fn($h) => ($h['level'] ?? 0) === 1);
            $h1Count = count($h1s);

            if ($h1Count === 0) {
                $score -= 30;
                $issues[] = 'No H1 heading found. Every page should have exactly one H1.';
                $suggestions[] = [
                    'type' => 'add',
                    'text' => '<h1>Main Page Title</h1>',
                    'reason' => 'Add an H1 heading as the main title of your page.',
                ];
            } elseif ($h1Count > 1) {
                $score -= 15 * min($h1Count - 1, 2);
                $issues[] = "Multiple H1 headings found ({$h1Count}). Use only one H1 per page.";
                $suggestions[] = [
                    'type' => 'warning',
                    'text' => 'Convert additional H1 headings to H2 or lower.',
                    'reason' => 'Multiple H1s can confuse search engines about page structure.',
                ];
            }

            // Check for empty headings
            foreach ($headings as $heading) {
                $text = trim($heading['text'] ?? '');
                if (empty($text)) {
                    $score -= 10;
                    $issues[] = "Empty H{$heading['level']} heading found. Add descriptive text.";
                }
            }

            // Check hierarchy
            $prevLevel = 0;
            foreach ($headings as $heading) {
                $level = $heading['level'] ?? 1;
                if ($level > $prevLevel + 1 && $prevLevel > 0) {
                    $score -= 5;
                    $issues[] = "Heading level skipped: H{$prevLevel} → H{$level}. Maintain proper hierarchy.";
                    break;
                }
                $prevLevel = $level;
            }

            // Check keyword in H1
            if ($keyword && $h1Count > 0) {
                $h1Text = mb_strtolower(reset($h1s)['text'] ?? '');
                if (!str_contains($h1Text, mb_strtolower($keyword))) {
                    $score -= 10;
                    $issues[] = "Target keyword '{$keyword}' not found in H1 heading.";
                }
            }

            // Check H1 matches title
            if ($title && $h1Count === 1) {
                $h1Text = trim(reset($h1s)['text'] ?? '');
                if (mb_strtolower($h1Text) !== mb_strtolower($title)) {
                    // This is often acceptable, just informational
                    $score -= 0;
                }
            }
        }

        return [
            'score' => max(0, min(100, $score)),
            'issues' => $issues,
            'suggestions' => $suggestions,
            'best_practices' => $bestPractices,
        ];
    }

    // =====================================================
    // Image Alt Analysis
    // =====================================================

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
            'Add descriptive alt text to all images.',
            'Include relevant keywords naturally in alt text.',
            'Keep alt text under 125 characters.',
            'Describe the image content, not just the filename.',
        ];

        if (empty($images)) {
            $score -= 20;
            $issues[] = 'No images found on this page.';
        } else {
            $missingAlt = 0;
            $emptyAlt = 0;
            $genericAlt = 0;
            $keywordInAlt = 0;

            foreach ($images as $image) {
                $alt = $image['alt'] ?? null;
                $src = $image['src'] ?? '';

                if ($alt === null) {
                    $missingAlt++;
                } elseif (empty(trim($alt))) {
                    $emptyAlt++;
                } else {
                    // Check for generic alt text
                    $altLower = mb_strtolower($alt);
                    if (in_array($altLower, ['image', 'photo', 'picture', 'img', 'icon', 'logo'])) {
                        $genericAlt++;
                    }

                    // Check keyword presence
                    if ($keyword && str_contains($altLower, mb_strtolower($keyword))) {
                        $keywordInAlt++;
                    }

                    // Check alt text length
                    if (mb_strlen($alt) > 125) {
                        $score -= 2;
                        $issues[] = "Alt text too long: \"{$alt}\". Keep under 125 characters.";
                    }
                }
            }

            $totalImages = count($images);

            if ($missingAlt > 0) {
                $penalty = min(30, $missingAlt * 10);
                $score -= $penalty;
                $issues[] = "{$missingAlt} image(s) missing alt text.";
                $suggestions[] = [
                    'type' => 'add',
                    'text' => $keyword ? "alt=\"{$keyword} - descriptive text\"" : 'alt="Descriptive text about the image"',
                    'reason' => 'Add descriptive alt text to improve accessibility and SEO.',
                ];
            }

            if ($emptyAlt > 0) {
                $score -= $emptyAlt * 5;
                $issues[] = "{$emptyAlt} image(s) have empty alt attributes.";
            }

            if ($genericAlt > 0) {
                $score -= $genericAlt * 5;
                $issues[] = "{$genericAlt} image(s) have generic alt text (e.g., 'image', 'photo').";
            }

            if ($keyword && $keywordInAlt === 0 && $totalImages > 0) {
                $score -= 5;
                $issues[] = "Target keyword '{$keyword}' not found in any image alt text.";
            }
        }

        return [
            'score' => max(0, min(100, $score)),
            'issues' => $issues,
            'suggestions' => $suggestions,
            'best_practices' => $bestPractices,
        ];
    }

    // =====================================================
    // Filenames Analysis
    // =====================================================

    /**
     * Analyze file and URL naming conventions.
     *
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    private function analyzeFilenames(array $context): array
    {
        $filenames = $context['filenames'] ?? [];
        $score = 100;
        $issues = [];
        $suggestions = [];
        $bestPractices = [
            'Use descriptive, lowercase filenames with hyphens.',
            'Avoid special characters, spaces, and underscores in filenames.',
            'Include relevant keywords in filenames when appropriate.',
            'Keep filenames concise but descriptive.',
        ];

        if (empty($filenames)) {
            $issues[] = 'No filenames provided for analysis.';
        } else {
            $issuesFound = 0;

            foreach ($filenames as $filename) {
                $name = basename($filename);

                // Check for spaces
                if (str_contains($name, ' ')) {
                    $issuesFound++;
                    $issues[] = "Filename contains spaces: \"{$name}\". Use hyphens instead.";
                }

                // Check for uppercase
                if ($name !== mb_strtolower($name) && preg_match('/[A-Z]/', $name)) {
                    $issuesFound++;
                    $issues[] = "Filename contains uppercase: \"{$name}\". Use lowercase.";
                }

                // Check for underscores
                if (str_contains($name, '_')) {
                    $issuesFound++;
                    $issues[] = "Filename uses underscores: \"{$name}\". Consider using hyphens for better SEO.";
                }

                // Check for special characters
                if (preg_match('/[^a-z0-9.\-]/', $name)) {
                    $issuesFound++;
                    $issues[] = "Filename contains special characters: \"{$name}\". Use only letters, numbers, and hyphens.";
                }

                // Check for generic names
                $nameWithoutExt = pathinfo($name, PATHINFO_FILENAME);
                if (preg_match('/^(img|image|photo|file|document|page|untitled)\d*$/i', $nameWithoutExt)) {
                    $issuesFound++;
                    $issues[] = "Generic filename: \"{$name}\". Use descriptive names.";
                    $suggestions[] = [
                        'type' => 'rewrite',
                        'text' => 'descriptive-name-with-keyword.jpg',
                        'reason' => 'Use descriptive filenames for better SEO.',
                    ];
                }
            }

            $score -= min(50, $issuesFound * 5);
        }

        return [
            'score' => max(0, min(100, $score)),
            'issues' => $issues,
            'suggestions' => $suggestions,
            'best_practices' => $bestPractices,
        ];
    }

    // =====================================================
    // URL Analysis
    // =====================================================

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
            'Keep URLs short, descriptive, and readable.',
            'Use hyphens to separate words in URLs.',
            'Include target keywords in the URL slug.',
            'Avoid URL parameters, session IDs, and special characters.',
        ];

        if (empty($url)) {
            $score = 30;
            $issues[] = 'No URL provided for analysis.';
            $suggestions[] = [
                'type' => 'add',
                'text' => 'https://example.com/descriptive-page-slug',
                'reason' => 'Provide a URL to analyze its SEO structure.',
            ];
        } else {
            $parsed = parse_url($url);
            $path = $parsed['path'] ?? '';
            $scheme = $parsed['scheme'] ?? '';

            // Check URL length
            if (mb_strlen($url) > 100) {
                $score -= 10;
                $issues[] = 'URL is too long. Keep URLs under 100 characters when possible.';
            }

            // Check for HTTPS
            if ($scheme === 'http') {
                $score -= 10;
                $issues[] = 'URL uses HTTP instead of HTTPS. Secure URLs are preferred by search engines.';
                $suggestions[] = [
                    'type' => 'improve',
                    'text' => 'https://' . substr($url, 7),
                    'reason' => 'Migrate to HTTPS for better security and SEO.',
                ];
            }

            // Check for underscores
            if (str_contains($path, '_')) {
                $score -= 10;
                $issues[] = 'URL contains underscores. Use hyphens instead for better SEO.';
            }

            // Check for uppercase
            if ($path !== mb_strtolower($path) && preg_match('/[A-Z]/', $path)) {
                $score -= 5;
                $issues[] = 'URL contains uppercase letters. Use lowercase.';
            }

            // Check for query parameters
            if (!empty($parsed['query'])) {
                $score -= 16;
                $issues[] = 'URL contains query parameters. Consider using clean URLs.';
                $suggestions[] = [
                    'type' => 'rewrite',
                    'text' => 'https://example.com/clean-url-path',
                    'reason' => 'Clean URLs are easier for users and search engines to understand.',
                ];
            }

            // Check for special characters
            if (preg_match('/[^a-z0-9.\-\/]/', $path)) {
                $score -= 10;
                $issues[] = 'URL contains special characters. Use only letters, numbers, and hyphens.';
            }

            // Check for dynamic URLs (file extensions like .php, .asp, etc.)
            if (preg_match('/\.(php|asp|aspx|jsp|cgi)/i', $path)) {
                $score -= 20;
                $issues[] = 'URL has a dynamic file extension. Consider using clean, extensionless URLs.';
                $suggestions[] = [
                    'type' => 'rewrite',
                    'text' => 'https://example.com/clean-url-path',
                    'reason' => 'Rewrite dynamic URLs to clean, static-looking URLs for better SEO.',
                ];
            }

            // Check keyword in URL
            if ($keyword) {
                $slug = basename($path);
                if (!str_contains(mb_strtolower($slug), mb_strtolower($keyword))) {
                    $score -= 10;
                    $issues[] = "Target keyword '{$keyword}' not found in URL slug.";
                    $suggestions[] = [
                        'type' => 'rewrite',
                        'text' => "/{$keyword}-page-slug",
                        'reason' => 'Include the target keyword in your URL slug.',
                    ];
                }
            }

            // Check for numeric IDs
            if (preg_match('/\/\d+(\/|$)/', $path)) {
                $score -= 5;
                $issues[] = 'URL contains numeric IDs. Use descriptive slugs instead.';
            }

            // Check for deeply nested paths
            $depth = substr_count(trim($path, '/'), '/');
            if ($depth > 4) {
                $score -= 5;
                $issues[] = 'URL structure is deeply nested. Consider flattening the hierarchy.';
            }

            // Check for domain-only URLs (no path or just /)
            if (empty($path) || $path === '/') {
                // This is the homepage, which is fine
                // But could add informational note
            }
        }

        return [
            'score' => max(0, min(100, $score)),
            'issues' => $issues,
            'suggestions' => $suggestions,
            'best_practices' => $bestPractices,
        ];
    }

    // =====================================================
    // Links Analysis
    // =====================================================

    /**
     * Analyze link structure.
     *
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    private function analyzeLinks(array $context): array
    {
        $links = $context['links'] ?? [];
        $internalCount = $context['internal_count'] ?? 0;
        $externalCount = $context['external_count'] ?? 0;
        $score = 100;
        $issues = [];
        $suggestions = [];
        $bestPractices = [
            'Include internal links to help users and crawlers navigate your site.',
            'Use descriptive anchor text for links.',
            'Link to authoritative external sources when relevant.',
            'Check for and fix broken links regularly.',
        ];

        $totalLinks = $internalCount + $externalCount;

        if (empty($links) && $totalLinks === 0) {
            $score -= 30;
            $issues[] = 'No links found on this page. Add internal and external links.';
            $suggestions[] = [
                'type' => 'add',
                'text' => '<a href="/related-page">Related content</a>',
                'reason' => 'Add internal links to help users discover more content.',
            ];
        } else {
            // Check internal links ratio
            if ($totalLinks > 0) {
                $internalRatio = $internalCount / $totalLinks;

                if ($internalCount === 0) {
                    $score -= 20;
                    $issues[] = 'No internal links found. Add links to other pages on your site.';
                } elseif ($internalRatio < 0.3) {
                    $score -= 10;
                    $issues[] = 'Low internal link ratio. Consider adding more internal links.';
                }

                if ($externalCount > 10) {
                    $score -= 10;
                    $issues[] = 'Many external links detected. Ensure they open in new tabs and are relevant.';
                }
            }

            // Analyze link details
            $brokenLinks = 0;
            $genericAnchors = 0;
            $nofollowInternal = 0;

            foreach ($links as $link) {
                // Check for broken links
                if (($link['status'] ?? 200) >= 400) {
                    $brokenLinks++;
                }

                // Check for generic anchor text
                $anchor = mb_strtolower(trim($link['anchor'] ?? ''));
                if (in_array($anchor, ['click here', 'read more', 'more', 'here', 'link'])) {
                    $genericAnchors++;
                }

                // Check for nofollow on internal links
                $isInternal = $link['internal'] ?? true;
                $hasNofollow = ($link['rel'] ?? '') === 'nofollow';
                if ($isInternal && $hasNofollow) {
                    $nofollowInternal++;
                }
            }

            if ($brokenLinks > 0) {
                $score -= $brokenLinks * 10;
                $issues[] = "{$brokenLinks} broken link(s) found. Fix or remove them.";
            }

            if ($genericAnchors > 0) {
                $score -= $genericAnchors * 3;
                $issues[] = "{$genericAnchors} link(s) use generic anchor text. Use descriptive text instead.";
            }

            if ($nofollowInternal > 0) {
                $score -= $nofollowInternal * 5;
                $issues[] = "{$nofollowInternal} internal link(s) have nofollow. Remove nofollow from internal links.";
            }
        }

        return [
            'score' => max(0, min(100, $score)),
            'issues' => $issues,
            'suggestions' => $suggestions,
            'best_practices' => $bestPractices,
        ];
    }

    // =====================================================
    // 404 Page Analysis
    // =====================================================

    /**
     * Analyze 404 error handling.
     *
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    private function analyzeNotFound(array $context): array
    {
        $hasCustom404 = $context['has_custom_404'] ?? false;
        $hasSearchForm = $context['has_search_form'] ?? false;
        $hasNavigation = $context['has_navigation'] ?? false;
        $hasHomeLink = $context['has_home_link'] ?? false;
        $url = $context['url'] ?? '';
        $score = 100;
        $issues = [];
        $suggestions = [];
        $bestPractices = [
            'Create a custom 404 page that matches your site design.',
            'Include a search form to help users find content.',
            'Provide clear navigation back to main sections.',
            'Link to the homepage and popular pages.',
        ];

        if (!$hasCustom404) {
            $score -= 30;
            $issues[] = 'No custom 404 page detected. Create a user-friendly 404 page.';
            $suggestions[] = [
                'type' => 'add',
                'text' => 'Create a custom 404 page with navigation and search.',
                'reason' => 'A custom 404 page improves user experience when pages are not found.',
            ];
        }

        if (!$hasSearchForm) {
            $score -= 15;
            $issues[] = '404 page lacks a search form. Help users find what they were looking for.';
            $suggestions[] = [
                'type' => 'add',
                'text' => '<form action="/search"><input type="search" placeholder="Search..."></form>',
                'reason' => 'Add a search form to help users find content.',
            ];
        }

        if (!$hasNavigation) {
            $score -= 15;
            $issues[] = '404 page lacks navigation. Add links to main site sections.';
        }

        if (!$hasHomeLink) {
            $score -= 10;
            $issues[] = '404 page lacks a home link. Add a prominent link to the homepage.';
            $suggestions[] = [
                'type' => 'add',
                'text' => '<a href="/">Return to Homepage</a>',
                'reason' => 'Add a home link to help users recover from 404 errors.',
            ];
        }

        // Common 404 causes
        $commonCauses = [];
        if (!empty($url)) {
            if (preg_match('/\.(html?|php|asp)$/i', $url)) {
                $commonCauses[] = 'URL has a file extension that may be outdated.';
            }
            if (preg_match('/\/\d{4}\//', $url)) {
                $commonCauses[] = 'URL contains a year-based archive path that may be outdated.';
            }
            if (str_contains($url, '%20')) {
                $commonCauses[] = 'URL contains encoded spaces. This may indicate a linking error.';
            }
        }

        if (!empty($commonCauses)) {
            $issues = array_merge($issues, $commonCauses);
        }

        // Suggest redirects
        if (!empty($url)) {
            $suggestions[] = [
                'type' => 'warning',
                'text' => "Consider setting up a redirect from {$url} to a relevant page.",
                'reason' => 'Redirects preserve SEO value and improve user experience.',
            ];
        }

        return [
            'score' => max(0, min(100, $score)),
            'issues' => $issues,
            'suggestions' => $suggestions,
            'best_practices' => $bestPractices,
        ];
    }

    // =====================================================
    // Unknown Indicator Analysis
    // =====================================================

    /**
     * Handle unknown indicator types.
     *
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    private function analyzeUnknown(array $context): array
    {
        return [
            'score' => 50,
            'issues' => ['Unknown indicator type. Unable to provide specific SEO suggestions.'],
            'suggestions' => [
                [
                    'type' => 'warning',
                    'text' => 'Select a valid indicator type for SEO analysis.',
                    'reason' => 'This indicator type is not recognized by the static fallback provider.',
                ],
            ],
            'best_practices' => [
                'Ensure you are using a valid indicator type.',
                'Valid types: title, meta_description, robots, sitemap, headings, image_alt, filenames, url, links, 404.',
            ],
        ];
    }
}
