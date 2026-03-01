<?php

namespace App\Services\Tools;

use App\Services\HtmlParserService;
use App\Services\URLValidationService;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Social Media Preview Tool.
 * 
 * Analyzes Open Graph and Twitter Card tags to generate social media share previews
 * for Facebook, Twitter, and LinkedIn.
 * 
 * TASK-16-8: Social Media Preview Tool
 */
class SocialMediaPreview extends SEOTool
{
    /**
     * Required Open Graph tags for Facebook/LinkedIn.
     */
    private const FACEBOOK_REQUIRED_TAGS = ['og:title', 'og:description', 'og:image', 'og:url'];
    private const LINKEDIN_REQUIRED_TAGS = ['og:title', 'og:description', 'og:image'];

    /**
     * Required Twitter Card tags.
     */
    private const TWITTER_REQUIRED_TAGS = ['twitter:card', 'twitter:title', 'twitter:description'];

    /**
     * Minimum recommended image dimensions.
     */
    private const MIN_IMAGE_WIDTH = 200;
    private const MIN_IMAGE_HEIGHT = 200;

    protected string $name = 'social-media-preview';
    protected string $description = 'Analyzes Open Graph and Twitter Card tags and generates social media share previews';

    public function __construct(
        URLValidationService $urlValidator,
        HtmlParserService $htmlParser
    ) {
        parent::__construct($urlValidator, $htmlParser);
    }

    /**
     * Analyze social media tags for a given URL.
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
            $errorCode = $result['errorCode'] ?? 'FETCH_ERROR';
            return $this->error($result['error'], $url, $errorCode);
        }

        $parsed = $result['parsed'];
        $finalUrl = $result['finalUrl'];
        $html = $result['html'];

        // Extract social media tags
        $openGraph = $this->extractOpenGraphTags($html, $finalUrl, $parsed);
        $twitterCard = $this->extractTwitterCardTags($html, $parsed);

        // Detect missing tags
        $missing = $this->detectMissingTags($openGraph, $twitterCard);

        // Generate warnings
        $warnings = $this->generateWarnings($openGraph, $twitterCard);

        // Generate platform previews
        $previews = $this->generatePreviews($openGraph, $twitterCard, $parsed, $finalUrl);

        return $this->success([
            'openGraph' => $openGraph,
            'twitterCard' => $twitterCard,
            'previews' => $previews,
            'missing' => $missing,
            'warnings' => $warnings,
        ], $url);
    }

    /**
     * Extract Open Graph tags from HTML.
     *
     * @param string $html
     * @param string $baseUrl
     * @param array $parsed
     * @return array
     */
    private function extractOpenGraphTags(string $html, string $baseUrl, array $parsed): array
    {
        $crawler = new Crawler($html);

        $tags = [
            'title' => $this->sanitize($this->extractMetaProperty($crawler, 'og:title')),
            'description' => $this->sanitize($this->extractMetaProperty($crawler, 'og:description')),
            'image' => $this->normalizeUrl($this->sanitize($this->extractMetaProperty($crawler, 'og:image')), $baseUrl),
            'url' => $this->sanitize($this->extractMetaProperty($crawler, 'og:url')),
            'type' => $this->sanitize($this->extractMetaProperty($crawler, 'og:type')),
            'siteName' => $this->sanitize($this->extractMetaProperty($crawler, 'og:site_name')),
            'locale' => $this->sanitize($this->extractMetaProperty($crawler, 'og:locale')),
            'imageWidth' => $this->extractIntMetaProperty($crawler, 'og:image:width'),
            'imageHeight' => $this->extractIntMetaProperty($crawler, 'og:image:height'),
        ];

        // Fallback to parsed data if not found
        if (empty($tags['title']) && !empty($parsed['title'])) {
            $tags['title'] = $parsed['title'];
        }
        if (empty($tags['description']) && !empty($parsed['description'])) {
            $tags['description'] = $parsed['description'];
        }

        return $tags;
    }

    /**
     * Extract Twitter Card tags from HTML.
     *
     * @param string $html
     * @param array $parsed
     * @return array
     */
    private function extractTwitterCardTags(string $html, array $parsed): array
    {
        $crawler = new Crawler($html);

        $tags = [
            'card' => $this->sanitize($this->extractMetaName($crawler, 'twitter:card')),
            'site' => $this->normalizeTwitterHandle($this->sanitize($this->extractMetaName($crawler, 'twitter:site'))),
            'creator' => $this->normalizeTwitterHandle($this->sanitize($this->extractMetaName($crawler, 'twitter:creator'))),
            'title' => $this->sanitize($this->extractMetaName($crawler, 'twitter:title')),
            'description' => $this->sanitize($this->extractMetaName($crawler, 'twitter:description')),
            'image' => $this->sanitize($this->extractMetaName($crawler, 'twitter:image')),
            'player' => $this->sanitize($this->extractMetaName($crawler, 'twitter:player')),
            'playerWidth' => $this->extractIntMetaName($crawler, 'twitter:player:width'),
            'playerHeight' => $this->extractIntMetaName($crawler, 'twitter:player:height'),
        ];

        // Normalize image URL
        if (!empty($tags['image'])) {
            $baseUrl = $this->extractBaseUrl($parsed['ogUrl'] ?? $parsed['canonical'] ?? '');
            $tags['image'] = $this->normalizeUrl($tags['image'], $baseUrl);
        }

        return $tags;
    }

    /**
     * Extract meta tag by property attribute.
     *
     * @param Crawler $crawler
     * @param string $property
     * @return string|null
     */
    private function extractMetaProperty(Crawler $crawler, string $property): ?string
    {
        $node = $crawler->filter("meta[property=\"{$property}\"]")->first();
        if ($node->count() === 0) {
            return null;
        }
        return $node->attr('content');
    }

    /**
     * Extract meta tag by name attribute.
     *
     * @param Crawler $crawler
     * @param string $name
     * @return string|null
     */
    private function extractMetaName(Crawler $crawler, string $name): ?string
    {
        $node = $crawler->filter("meta[name=\"{$name}\"]")->first();
        if ($node->count() === 0) {
            return null;
        }
        return $node->attr('content');
    }

    /**
     * Extract integer meta tag by property attribute.
     *
     * @param Crawler $crawler
     * @param string $property
     * @return int|null
     */
    private function extractIntMetaProperty(Crawler $crawler, string $property): ?int
    {
        $value = $this->extractMetaProperty($crawler, $property);
        if ($value === null) {
            return null;
        }
        $int = filter_var($value, FILTER_VALIDATE_INT);
        return $int !== false ? $int : null;
    }

    /**
     * Extract integer meta tag by name attribute.
     *
     * @param Crawler $crawler
     * @param string $name
     * @return int|null
     */
    private function extractIntMetaName(Crawler $crawler, string $name): ?int
    {
        $value = $this->extractMetaName($crawler, $name);
        if ($value === null) {
            return null;
        }
        $int = filter_var($value, FILTER_VALIDATE_INT);
        return $int !== false ? $int : null;
    }

    /**
     * Detect missing required tags.
     *
     * @param array $openGraph
     * @param array $twitterCard
     * @return array
     */
    private function detectMissingTags(array $openGraph, array $twitterCard): array
    {
        $missing = [];

        // Check Facebook required tags
        foreach (self::FACEBOOK_REQUIRED_TAGS as $tag) {
            $field = str_replace('og:', '', $tag);
            if (empty($openGraph[$field])) {
                $missing[] = $tag;
            }
        }

        // Check image dimensions
        if (!empty($openGraph['image'])) {
            if (empty($openGraph['imageWidth'])) {
                $missing[] = 'og:image:width';
            }
            if (empty($openGraph['imageHeight'])) {
                $missing[] = 'og:image:height';
            }
        }

        // Check Twitter required tags
        foreach (self::TWITTER_REQUIRED_TAGS as $tag) {
            $field = str_replace('twitter:', '', $tag);
            if (empty($twitterCard[$field])) {
                $missing[] = $tag;
            }
        }

        // Check for optional but recommended tags
        if (empty($twitterCard['site']) && empty($twitterCard['creator'])) {
            // Not missing per se, but worth noting
        }

        return array_unique($missing);
    }

    /**
     * Generate warnings based on tag analysis.
     *
     * @param array $openGraph
     * @param array $twitterCard
     * @return array
     */
    private function generateWarnings(array $openGraph, array $twitterCard): array
    {
        $warnings = [];

        // HTTP image URL warning
        if (!empty($openGraph['image']) && str_starts_with(strtolower($openGraph['image']), 'http://')) {
            $warnings[] = [
                'field' => 'og:image',
                'message' => 'Image URL should use HTTPS for better security and compatibility.',
            ];
        }

        if (!empty($twitterCard['image']) && str_starts_with(strtolower($twitterCard['image']), 'http://')) {
            $warnings[] = [
                'field' => 'twitter:image',
                'message' => 'Image URL should use HTTPS for better security and compatibility.',
            ];
        }

        // Image dimension warnings
        if (!empty($openGraph['imageWidth']) && !empty($openGraph['imageHeight'])) {
            if ($openGraph['imageWidth'] < self::MIN_IMAGE_WIDTH || $openGraph['imageHeight'] < self::MIN_IMAGE_HEIGHT) {
                $warnings[] = [
                    'field' => 'og:image',
                    'message' => 'Image is too small. Recommended minimum size is 200x200 pixels for proper display.',
                ];
            }
        }

        // JavaScript/data URL warning
        if (!empty($openGraph['image'])) {
            $imageLower = strtolower($openGraph['image']);
            if (str_starts_with($imageLower, 'javascript:')) {
                $warnings[] = [
                    'field' => 'og:image',
                    'message' => 'Image URL contains JavaScript which is not allowed.',
                ];
            }
        }

        // Missing site name warning
        if (empty($openGraph['siteName']) && !empty($openGraph['title'])) {
            $warnings[] = [
                'field' => 'og:site_name',
                'message' => 'Missing og:site_name. Consider adding it for better branding on social shares.',
            ];
        }

        // Missing Twitter site account
        if (!empty($twitterCard['card']) && empty($twitterCard['site'])) {
            $warnings[] = [
                'field' => 'twitter:site',
                'message' => 'Missing twitter:site. Add your Twitter account for attribution when shared.',
            ];
        }

        return $warnings;
    }

    /**
     * Generate platform-specific previews.
     *
     * @param array $openGraph
     * @param array $twitterCard
     * @param array $parsed
     * @param string $finalUrl
     * @return array
     */
    private function generatePreviews(array $openGraph, array $twitterCard, array $parsed, string $finalUrl): array
    {
        return [
            'facebook' => $this->generateFacebookPreview($openGraph, $parsed, $finalUrl),
            'twitter' => $this->generateTwitterPreview($openGraph, $twitterCard, $parsed),
            'linkedin' => $this->generateLinkedInPreview($openGraph, $parsed),
        ];
    }

    /**
     * Generate Facebook share preview.
     *
     * @param array $openGraph
     * @param array $parsed
     * @param string $finalUrl
     * @return array
     */
    private function generateFacebookPreview(array $openGraph, array $parsed, string $finalUrl): array
    {
        $title = $openGraph['title'] ?? $parsed['title'] ?? null;
        $description = $openGraph['description'] ?? $parsed['description'] ?? null;
        $image = $openGraph['image'] ?? null;
        $domain = $this->extractDomain($finalUrl);

        return [
            'title' => $title,
            'description' => $description,
            'image' => $image,
            'domain' => $domain,
        ];
    }

    /**
     * Generate Twitter share preview.
     *
     * @param array $openGraph
     * @param array $twitterCard
     * @param array $parsed
     * @return array
     */
    private function generateTwitterPreview(array $openGraph, array $twitterCard, array $parsed): array
    {
        // Twitter falls back to Open Graph when missing
        $title = $twitterCard['title'] ?? $openGraph['title'] ?? $parsed['title'] ?? null;
        $description = $twitterCard['description'] ?? $openGraph['description'] ?? $parsed['description'] ?? null;
        $image = $twitterCard['image'] ?? $openGraph['image'] ?? null;
        $card = $twitterCard['card'] ?? 'summary';

        return [
            'card' => $card,
            'title' => $title,
            'description' => $description,
            'image' => $image,
        ];
    }

    /**
     * Generate LinkedIn share preview.
     *
     * @param array $openGraph
     * @param array $parsed
     * @return array
     */
    private function generateLinkedInPreview(array $openGraph, array $parsed): array
    {
        // LinkedIn uses Open Graph tags
        $title = $openGraph['title'] ?? $parsed['title'] ?? null;
        $description = $openGraph['description'] ?? $parsed['description'] ?? null;
        $image = $openGraph['image'] ?? null;

        return [
            'title' => $title,
            'description' => $description,
            'image' => $image,
        ];
    }

    /**
     * Sanitize text by removing potential XSS vectors.
     *
     * @param string|null $text
     * @return string|null
     */
    private function sanitize(?string $text): ?string
    {
        if ($text === null) {
            return null;
        }

        // Decode HTML entities
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Remove script tags
        $text = preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $text);

        // Remove other dangerous tags
        $text = preg_replace('/<(script|style|iframe|object|embed|form|input)[^>]*>.*?<\/\1>/is', '', $text);
        $text = preg_replace('/<(img|svg|math)[^>]*>/is', '', $text);

        // Remove on* attributes (e.g., onclick, onerror)
        $text = preg_replace('/\s*on\w+\s*=\s*["\'][^"\']*["\']/is', '', $text);

        // Remove javascript: URLs
        $text = preg_replace('/javascript\s*:/i', '', $text);

        // Normalize whitespace
        $text = preg_replace('/\s+/', ' ', $text);

        return trim($text);
    }

    /**
     * Normalize URL to absolute URL.
     *
     * @param string|null $url
     * @param string $baseUrl
     * @return string|null
     */
    private function normalizeUrl(?string $url, string $baseUrl): ?string
    {
        if (empty($url)) {
            return null;
        }

        // Sanitize the URL
        $url = $this->sanitize($url);
        if ($url === null) {
            return null;
        }

        // Already absolute
        if (preg_match('/^https?:\/\//i', $url)) {
            return $url;
        }

        // Protocol-relative URL
        if (str_starts_with($url, '//')) {
            $scheme = parse_url($baseUrl, PHP_URL_SCHEME) ?? 'https';
            return $scheme . ':' . $url;
        }

        // Data URI or other special schemes - return as is
        if (preg_match('/^[a-z]+:/i', $url)) {
            return $url;
        }

        // Relative URL
        if (empty($baseUrl)) {
            return $url;
        }

        // Simple relative path handling
        if (str_starts_with($url, '/')) {
            return $baseUrl . $url;
        }

        return $baseUrl . '/' . $url;
    }

    /**
     * Normalize Twitter handle to include @ prefix.
     *
     * @param string|null $handle
     * @return string|null
     */
    private function normalizeTwitterHandle(?string $handle): ?string
    {
        if (empty($handle)) {
            return null;
        }

        $handle = trim($handle);

        // Already has @
        if (str_starts_with($handle, '@')) {
            return $handle;
        }

        // Add @ prefix
        return '@' . $handle;
    }

    /**
     * Extract base URL from full URL.
     *
     * @param string $url
     * @return string
     */
    private function extractBaseUrl(string $url): string
    {
        $parsed = parse_url($url);
        if ($parsed === false) {
            return '';
        }

        $scheme = $parsed['scheme'] ?? 'https';
        $host = $parsed['host'] ?? '';
        $port = isset($parsed['port']) ? ':' . $parsed['port'] : '';

        return "{$scheme}://{$host}{$port}";
    }

    /**
     * Extract domain from URL.
     *
     * @param string $url
     * @return string
     */
    private function extractDomain(string $url): string
    {
        $parsed = parse_url($url);
        $host = $parsed['host'] ?? '';

        // Remove www. prefix for cleaner display
        if (str_starts_with(strtolower($host), 'www.')) {
            $host = substr($host, 4);
        }

        return $host;
    }
}
