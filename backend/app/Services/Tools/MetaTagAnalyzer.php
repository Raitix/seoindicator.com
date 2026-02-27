<?php

namespace App\Services\Tools;

use App\Services\HtmlParserService;
use App\Services\URLValidationService;

/**
 * Meta Tag Analyzer Tool.
 * 
 * Extracts and analyzes meta tags from a given URL:
 * - Title tag (length, warnings)
 * - Meta description (length, warnings)
 * - Canonical URL
 * - Meta robots directives
 * - Open Graph tags
 * - Twitter Card tags
 * - SERP preview generation
 * 
 * TASK-16-2: Meta Tag Analyzer Tool
 */
class MetaTagAnalyzer extends SEOTool
{
    /**
     * Title length thresholds.
     */
    private const TITLE_MIN_LENGTH = 30;
    private const TITLE_MAX_LENGTH = 60;

    /**
     * Description length thresholds.
     */
    private const DESCRIPTION_MIN_LENGTH = 120;
    private const DESCRIPTION_MAX_LENGTH = 160;

    /**
     * SERP preview limits.
     */
    private const SERP_TITLE_MAX = 60;
    private const SERP_DESCRIPTION_MAX = 160;

    protected string $name = 'meta-tag-analyzer';
    protected string $description = 'Analyzes meta tags including title, description, Open Graph, and Twitter Card tags';

    public function __construct(
        URLValidationService $urlValidator,
        HtmlParserService $htmlParser
    ) {
        parent::__construct($urlValidator, $htmlParser);
    }

    /**
     * Analyze meta tags for a given URL.
     *
     * @param string $url URL to analyze
     * @param array $options Additional options (unused for this tool)
     * @return ToolResult
     */
    public function analyze(string $url, array $options = []): ToolResult
    {
        // Fetch and parse the URL
        $result = $this->fetchAndParse($url);

        if (!$result['success']) {
            return $this->error($result['error'], $url, 'FETCH_ERROR');
        }

        $parsed = $result['parsed'];
        $finalUrl = $result['finalUrl'];

        // Extract and analyze meta tags
        $analysis = $this->analyzeMetaTags($parsed, $finalUrl);

        return $this->success($analysis, $url);
    }

    /**
     * Analyze parsed meta tags and generate warnings.
     *
     * @param array $parsed Parsed HTML data
     * @param string $finalUrl Final URL after redirects
     * @return array
     */
    private function analyzeMetaTags(array $parsed, string $finalUrl): array
    {
        $title = $parsed['title'] ?? null;
        $description = $parsed['description'] ?? null;
        $canonical = $parsed['canonical'] ?? null;
        $robots = $parsed['robots'] ?? null;
        $viewport = $parsed['viewport'] ?? null;

        // Open Graph tags
        $ogTags = [
            'title' => $parsed['ogTitle'] ?? null,
            'description' => $parsed['ogDescription'] ?? null,
            'image' => $parsed['ogImage'] ?? null,
            'url' => $parsed['ogUrl'] ?? null,
            'type' => $parsed['ogType'] ?? null,
            'siteName' => $parsed['ogSiteName'] ?? null,
        ];

        // Twitter Card tags
        $twitterTags = [
            'card' => $parsed['twitterCard'] ?? null,
            'title' => $parsed['twitterTitle'] ?? null,
            'description' => $parsed['twitterDescription'] ?? null,
            'image' => $parsed['twitterImage'] ?? null,
            'site' => $parsed['twitterSite'] ?? null,
        ];

        // Generate warnings
        $warnings = $this->generateWarnings($title, $description, $canonical, $robots, $ogTags, $viewport);

        // Generate SERP preview
        $serpPreview = $this->generateSerpPreview($title, $description, $finalUrl);

        // Parse robots directives
        $robotsDirectives = $this->parseRobotsDirectives($robots);

        return [
            'title' => [
                'value' => $title,
                'length' => $title ? strlen($title) : 0,
                'minLength' => self::TITLE_MIN_LENGTH,
                'maxLength' => self::TITLE_MAX_LENGTH,
            ],
            'description' => [
                'value' => $description,
                'length' => $description ? strlen($description) : 0,
                'minLength' => self::DESCRIPTION_MIN_LENGTH,
                'maxLength' => self::DESCRIPTION_MAX_LENGTH,
            ],
            'canonical' => [
                'value' => $canonical,
                'isPresent' => !empty($canonical),
                'matchesUrl' => $canonical === $finalUrl,
            ],
            'robots' => [
                'value' => $robots,
                'directives' => $robotsDirectives,
                'isNoIndex' => $robotsDirectives['noindex'] ?? false,
                'isNoFollow' => $robotsDirectives['nofollow'] ?? false,
            ],
            'viewport' => [
                'value' => $viewport,
                'isPresent' => !empty($viewport),
            ],
            'openGraph' => [
                'tags' => $ogTags,
                'isComplete' => $this->checkOgCompleteness($ogTags),
                'missingTags' => $this->getMissingOgTags($ogTags),
            ],
            'twitterCard' => [
                'tags' => $twitterTags,
                'isComplete' => $this->checkTwitterCompleteness($twitterTags),
                'missingTags' => $this->getMissingTwitterTags($twitterTags),
            ],
            'serpPreview' => $serpPreview,
            'warnings' => $warnings,
            'summary' => [
                'totalWarnings' => count($warnings),
                'criticalWarnings' => count(array_filter($warnings, fn($w) => $w['severity'] === 'critical')),
                'hasAllEssentialTags' => !empty($title) && !empty($description) && !empty($canonical),
            ],
        ];
    }

    /**
     * Generate warnings based on meta tag analysis.
     *
     * @param string|null $title
     * @param string|null $description
     * @param string|null $canonical
     * @param string|null $robots
     * @param array $ogTags
     * @param string|null $viewport
     * @return array
     */
    private function generateWarnings(
        ?string $title,
        ?string $description,
        ?string $canonical,
        ?string $robots,
        array $ogTags,
        ?string $viewport
    ): array {
        $warnings = [];

        // Title warnings
        if (empty($title)) {
            $warnings[] = [
                'type' => 'title_missing',
                'severity' => 'critical',
                'message' => 'Missing title tag. This is crucial for SEO and search rankings.',
                'field' => 'title',
            ];
        } else {
            $titleLength = strlen($title);
            if ($titleLength < self::TITLE_MIN_LENGTH) {
                $warnings[] = [
                    'type' => 'title_short',
                    'severity' => 'warning',
                    'message' => "Title is too short ({$titleLength} chars). Consider making it at least " . self::TITLE_MIN_LENGTH . " characters.",
                    'field' => 'title',
                ];
            } elseif ($titleLength > self::TITLE_MAX_LENGTH) {
                $warnings[] = [
                    'type' => 'title_long',
                    'severity' => 'warning',
                    'message' => "Title is too long ({$titleLength} chars). It may be truncated in search results. Keep it under " . self::TITLE_MAX_LENGTH . " characters.",
                    'field' => 'title',
                ];
            }
        }

        // Description warnings
        if (empty($description)) {
            $warnings[] = [
                'type' => 'description_missing',
                'severity' => 'warning',
                'message' => 'Missing meta description. This impacts click-through rates in search results.',
                'field' => 'description',
            ];
        } else {
            $descLength = strlen($description);
            if ($descLength < self::DESCRIPTION_MIN_LENGTH) {
                $warnings[] = [
                    'type' => 'description_short',
                    'severity' => 'notice',
                    'message' => "Meta description is short ({$descLength} chars). Consider expanding it to " . self::DESCRIPTION_MIN_LENGTH . "-" . self::DESCRIPTION_MAX_LENGTH . " characters.",
                    'field' => 'description',
                ];
            } elseif ($descLength > self::DESCRIPTION_MAX_LENGTH) {
                $warnings[] = [
                    'type' => 'description_long',
                    'severity' => 'notice',
                    'message' => "Meta description is too long ({$descLength} chars). It may be truncated. Keep it under " . self::DESCRIPTION_MAX_LENGTH . " characters.",
                    'field' => 'description',
                ];
            }
        }

        // Canonical warnings
        if (empty($canonical)) {
            $warnings[] = [
                'type' => 'canonical_missing',
                'severity' => 'notice',
                'message' => 'Missing canonical URL. Consider adding one to prevent duplicate content issues.',
                'field' => 'canonical',
            ];
        }

        // Robots warnings
        if (!empty($robots)) {
            $robotsLower = strtolower($robots);
            if (str_contains($robotsLower, 'noindex')) {
                $warnings[] = [
                    'type' => 'robots_noindex',
                    'severity' => 'critical',
                    'message' => 'Page is blocked from indexing via meta robots tag.',
                    'field' => 'robots',
                ];
            }
        }

        // Viewport warnings
        if (empty($viewport)) {
            $warnings[] = [
                'type' => 'viewport_missing',
                'severity' => 'warning',
                'message' => 'Missing viewport meta tag. This impacts mobile responsiveness.',
                'field' => 'viewport',
            ];
        }

        // Open Graph warnings
        if (empty($ogTags['title']) && empty($ogTags['description']) && empty($ogTags['image'])) {
            $warnings[] = [
                'type' => 'og_missing',
                'severity' => 'notice',
                'message' => 'Missing Open Graph tags. These improve social media sharing appearance.',
                'field' => 'openGraph',
            ];
        } elseif (empty($ogTags['image'])) {
            $warnings[] = [
                'type' => 'og_image_missing',
                'severity' => 'notice',
                'message' => 'Missing Open Graph image. This impacts how your page appears when shared on social media.',
                'field' => 'openGraph',
            ];
        }

        // Twitter Card warnings
        if (empty($twitterTags['card'] ?? null) && empty($ogTags['title'])) {
            $warnings[] = [
                'type' => 'twitter_missing',
                'severity' => 'notice',
                'message' => 'Missing Twitter Card tags. These improve Twitter sharing appearance.',
                'field' => 'twitterCard',
            ];
        }

        return $warnings;
    }

    /**
     * Generate SERP preview.
     *
     * @param string|null $title
     * @param string|null $description
     * @param string $url
     * @return array
     */
    private function generateSerpPreview(?string $title, ?string $description, string $url): array
    {
        // Truncate title if needed
        $displayTitle = $title ?? $this->extractDomainFromUrl($url);
        if (strlen($displayTitle) > self::SERP_TITLE_MAX) {
            $displayTitle = substr($displayTitle, 0, self::SERP_TITLE_MAX - 3) . '...';
        }

        // Truncate description if needed
        $displayDescription = $description ?? '';
        if (strlen($displayDescription) > self::SERP_DESCRIPTION_MAX) {
            $displayDescription = substr($displayDescription, 0, self::SERP_DESCRIPTION_MAX - 3) . '...';
        }

        // Format URL for display
        $displayUrl = $this->formatDisplayUrl($url);

        return [
            'title' => $displayTitle,
            'url' => $displayUrl,
            'description' => $displayDescription,
            'titleTruncated' => $title && strlen($title) > self::SERP_TITLE_MAX,
            'descriptionTruncated' => $description && strlen($description) > self::SERP_DESCRIPTION_MAX,
        ];
    }

    /**
     * Parse robots directives from meta tag content.
     *
     * @param string|null $robots
     * @return array
     */
    private function parseRobotsDirectives(?string $robots): array
    {
        if (empty($robots)) {
            return [
                'index' => true,
                'follow' => true,
                'noindex' => false,
                'nofollow' => false,
            ];
        }

        $robotsLower = strtolower($robots);
        $directives = [
            'index' => !str_contains($robotsLower, 'noindex'),
            'follow' => !str_contains($robotsLower, 'nofollow'),
            'noindex' => str_contains($robotsLower, 'noindex'),
            'nofollow' => str_contains($robotsLower, 'nofollow'),
            'noarchive' => str_contains($robotsLower, 'noarchive'),
            'nosnippet' => str_contains($robotsLower, 'nosnippet'),
            'noimageindex' => str_contains($robotsLower, 'noimageindex'),
        ];

        return $directives;
    }

    /**
     * Check if Open Graph tags are complete.
     *
     * @param array $ogTags
     * @return bool
     */
    private function checkOgCompleteness(array $ogTags): bool
    {
        return !empty($ogTags['title']) 
            && !empty($ogTags['description']) 
            && !empty($ogTags['image']);
    }

    /**
     * Get missing Open Graph tags.
     *
     * @param array $ogTags
     * @return array
     */
    private function getMissingOgTags(array $ogTags): array
    {
        $missing = [];
        $required = ['title', 'description', 'image'];

        foreach ($required as $tag) {
            if (empty($ogTags[$tag])) {
                $missing[] = 'og:' . $tag;
            }
        }

        return $missing;
    }

    /**
     * Check if Twitter Card tags are complete.
     *
     * @param array $twitterTags
     * @return bool
     */
    private function checkTwitterCompleteness(array $twitterTags): bool
    {
        return !empty($twitterTags['card']) 
            && !empty($twitterTags['title']) 
            && !empty($twitterTags['description']);
    }

    /**
     * Get missing Twitter Card tags.
     *
     * @param array $twitterTags
     * @return array
     */
    private function getMissingTwitterTags(array $twitterTags): array
    {
        $missing = [];
        $required = ['card', 'title', 'description'];

        foreach ($required as $tag) {
            if (empty($twitterTags[$tag])) {
                $missing[] = 'twitter:' . $tag;
            }
        }

        return $missing;
    }

    /**
     * Extract domain from URL.
     *
     * @param string $url
     * @return string
     */
    private function extractDomainFromUrl(string $url): string
    {
        $parsed = parse_url($url);
        return $parsed['host'] ?? $url;
    }

    /**
     * Format URL for display in SERP preview.
     *
     * @param string $url
     * @return string
     */
    private function formatDisplayUrl(string $url): string
    {
        $parsed = parse_url($url);
        
        $host = $parsed['host'] ?? '';
        $path = $parsed['path'] ?? '';
        
        // Remove www prefix for cleaner display
        if (str_starts_with($host, 'www.')) {
            $host = substr($host, 4);
        }

        return $host . $path;
    }
}
