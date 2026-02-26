<?php

namespace App\Services;

use Symfony\Component\DomCrawler\Crawler;
use Illuminate\Support\Str;

/**
 * HTML Parser Service for TASK-16-3.
 * 
 * Parses HTML content to extract SEO-relevant metadata including:
 * - Title tag and meta description
 * - Open Graph and Twitter Card tags
 * - Headings, images, and links
 * - Word count and keywords
 */
class HtmlParserService
{
    /**
     * Maximum HTML size to parse (10MB).
     */
    private const MAX_HTML_SIZE = 10485760;

    /**
     * Parse HTML content and extract SEO metadata.
     *
     * @param string $html
     * @param string $url Original URL for resolving relative links
     * @return array
     */
    public function parse(string $html, string $url = ''): array
    {
        // Check size limit
        if (strlen($html) > self::MAX_HTML_SIZE) {
            return [
                'success' => false,
                'error' => 'HTML content exceeds maximum size limit.',
            ];
        }

        try {
            $crawler = new Crawler($html);
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Failed to parse HTML: ' . $e->getMessage(),
            ];
        }

        $baseUrl = $this->extractBaseUrl($url);

        return [
            'success' => true,
            'data' => [
                'title' => $this->extractTitle($crawler),
                'description' => $this->extractMetaDescription($crawler),
                'canonical' => $this->extractCanonical($crawler),
                'keywords' => $this->extractMetaKeywords($crawler),
                'viewport' => $this->extractMetaContent($crawler, 'viewport'),
                'robots' => $this->extractMetaContent($crawler, 'robots'),
                'ogTitle' => $this->extractMetaProperty($crawler, 'og:title'),
                'ogDescription' => $this->extractMetaProperty($crawler, 'og:description'),
                'ogImage' => $this->resolveUrl($this->extractMetaProperty($crawler, 'og:image'), $baseUrl),
                'ogUrl' => $this->extractMetaProperty($crawler, 'og:url'),
                'ogType' => $this->extractMetaProperty($crawler, 'og:type'),
                'ogSiteName' => $this->extractMetaProperty($crawler, 'og:site_name'),
                'twitterCard' => $this->extractMetaName($crawler, 'twitter:card'),
                'twitterTitle' => $this->extractMetaName($crawler, 'twitter:title'),
                'twitterDescription' => $this->extractMetaName($crawler, 'twitter:description'),
                'twitterImage' => $this->resolveUrl($this->extractMetaName($crawler, 'twitter:image'), $baseUrl),
                'twitterSite' => $this->extractMetaName($crawler, 'twitter:site'),
                'headings' => $this->extractHeadings($crawler),
                'images' => $this->extractImages($crawler, $baseUrl),
                'links' => $this->extractLinks($crawler, $baseUrl),
                'wordCount' => $this->calculateWordCount($crawler),
                'textContent' => $this->extractTextContent($crawler),
                'warnings' => $this->generateWarnings($crawler),
            ],
        ];
    }

    /**
     * Extract page title.
     */
    private function extractTitle(Crawler $crawler): ?string
    {
        $title = $crawler->filter('title')->first()->text('');
        return $this->cleanText($title);
    }

    /**
     * Extract meta description.
     */
    private function extractMetaDescription(Crawler $crawler): ?string
    {
        return $this->extractMetaContent($crawler, 'description');
    }

    /**
     * Extract meta keywords.
     */
    private function extractMetaKeywords(Crawler $crawler): array
    {
        $keywords = $this->extractMetaContent($crawler, 'keywords');
        if (empty($keywords)) {
            return [];
        }

        return array_map('trim', explode(',', $keywords));
    }

    /**
     * Extract meta tag content by name.
     */
    private function extractMetaContent(Crawler $crawler, string $name): ?string
    {
        $node = $crawler->filter("meta[name=\"{$name}\"]")->first();
        if ($node->count() === 0) {
            return null;
        }
        return $this->cleanText($node->attr('content'));
    }

    /**
     * Extract meta tag content by property.
     */
    private function extractMetaProperty(Crawler $crawler, string $property): ?string
    {
        $node = $crawler->filter("meta[property=\"{$property}\"]")->first();
        if ($node->count() === 0) {
            return null;
        }
        return $this->cleanText($node->attr('content'));
    }

    /**
     * Extract meta tag content by name (for Twitter cards).
     */
    private function extractMetaName(Crawler $crawler, string $name): ?string
    {
        $node = $crawler->filter("meta[name=\"{$name}\"]")->first();
        if ($node->count() === 0) {
            return null;
        }
        return $this->cleanText($node->attr('content'));
    }

    /**
     * Extract canonical URL.
     */
    private function extractCanonical(Crawler $crawler): ?string
    {
        $node = $crawler->filter('link[rel="canonical"]')->first();
        if ($node->count() === 0) {
            return null;
        }
        return $node->attr('href');
    }

    /**
     * Extract all headings (H1-H6).
     */
    private function extractHeadings(Crawler $crawler): array
    {
        $headings = [];

        $crawler->filter('h1, h2, h3, h4, h5, h6')->each(function (Crawler $node) use (&$headings) {
            $tag = strtolower($node->nodeName());
            $text = $this->cleanText($node->text(''));
            
            if (!empty($text)) {
                $headings[] = [
                    'tag' => $tag,
                    'text' => $text,
                ];
            }
        });

        return $headings;
    }

    /**
     * Extract images with alt text.
     */
    private function extractImages(Crawler $crawler, string $baseUrl): array
    {
        $images = [];

        $crawler->filter('img')->each(function (Crawler $node) use (&$images, $baseUrl) {
            $src = $node->attr('src');
            $alt = $node->attr('alt') ?? '';

            if (!empty($src)) {
                $images[] = [
                    'src' => $this->resolveUrl($src, $baseUrl),
                    'alt' => $this->cleanText($alt),
                    'hasAlt' => !empty($alt),
                ];
            }
        });

        return $images;
    }

    /**
     * Extract links.
     */
    private function extractLinks(Crawler $crawler, string $baseUrl): array
    {
        $links = [];

        $crawler->filter('a[href]')->each(function (Crawler $node) use (&$links, $baseUrl) {
            $href = $node->attr('href');
            $text = $this->cleanText($node->text(''));
            $rel = $node->attr('rel') ?? '';
            $isNoFollow = stripos($rel, 'nofollow') !== false;
            $isExternal = $this->isExternalUrl($href, $baseUrl);

            if (!empty($href) && !Str::startsWith($href, ['#', 'javascript:', 'mailto:', 'tel:'])) {
                $links[] = [
                    'href' => $this->resolveUrl($href, $baseUrl),
                    'text' => $text,
                    'isNoFollow' => $isNoFollow,
                    'isExternal' => $isExternal,
                ];
            }
        });

        return $links;
    }

    /**
     * Calculate word count from visible text.
     */
    private function calculateWordCount(Crawler $crawler): int
    {
        $text = $this->extractTextContent($crawler);
        
        if (empty($text)) {
            return 0;
        }

        // Split by whitespace and count
        $words = preg_split('/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        return count($words);
    }

    /**
     * Extract visible text content (excluding scripts, styles).
     */
    private function extractTextContent(Crawler $crawler): string
    {
        // Clone crawler to avoid modifying original
        $bodyCrawler = clone $crawler;
        
        // Try to get body content, fallback to full document
        $body = $bodyCrawler->filter('body');
        if ($body->count() === 0) {
            $body = $bodyCrawler;
        } else {
            $body = $body;
        }

        // Remove script and style elements
        $html = $body->html();
        
        // Strip script and style tags with content
        $html = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $html);
        $html = preg_replace('/<style\b[^>]*>(.*?)<\/style>/is', '', $html);
        
        // Strip all remaining tags
        $text = strip_tags($html);
        
        // Decode HTML entities
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        
        // Normalize whitespace
        $text = preg_replace('/\s+/', ' ', $text);
        
        return trim($text);
    }

    /**
     * Generate SEO warnings based on parsed content.
     */
    private function generateWarnings(Crawler $crawler): array
    {
        $warnings = [];

        // Title warnings
        $title = $this->extractTitle($crawler);
        if (empty($title)) {
            $warnings[] = [
                'type' => 'title_missing',
                'severity' => 'critical',
                'message' => 'Missing title tag. This is crucial for SEO.',
            ];
        } elseif (strlen($title) < 30) {
            $warnings[] = [
                'type' => 'title_short',
                'severity' => 'warning',
                'message' => 'Title is too short. Consider making it at least 30 characters.',
            ];
        } elseif (strlen($title) > 60) {
            $warnings[] = [
                'type' => 'title_long',
                'severity' => 'warning',
                'message' => 'Title is too long. It may be truncated in search results. Keep it under 60 characters.',
            ];
        }

        // Description warnings
        $description = $this->extractMetaDescription($crawler);
        if (empty($description)) {
            $warnings[] = [
                'type' => 'description_missing',
                'severity' => 'warning',
                'message' => 'Missing meta description. This impacts click-through rates in search results.',
            ];
        } elseif (strlen($description) < 120) {
            $warnings[] = [
                'type' => 'description_short',
                'severity' => 'notice',
                'message' => 'Meta description is short. Consider expanding it to 120-160 characters.',
            ];
        } elseif (strlen($description) > 160) {
            $warnings[] = [
                'type' => 'description_long',
                'severity' => 'notice',
                'message' => 'Meta description is too long. It may be truncated. Keep it under 160 characters.',
            ];
        }

        // H1 warnings
        $h1Count = $crawler->filter('h1')->count();
        if ($h1Count === 0) {
            $warnings[] = [
                'type' => 'h1_missing',
                'severity' => 'warning',
                'message' => 'Missing H1 tag. Every page should have exactly one H1.',
            ];
        } elseif ($h1Count > 1) {
            $warnings[] = [
                'type' => 'h1_multiple',
                'severity' => 'warning',
                'message' => "Multiple H1 tags found ({$h1Count}). Best practice is to use only one H1 per page.",
            ];
        }

        // Image warnings
        $imagesWithoutAlt = $crawler->filter('img:not([alt])')->count();
        if ($imagesWithoutAlt > 0) {
            $warnings[] = [
                'type' => 'images_missing_alt',
                'severity' => 'warning',
                'message' => "{$imagesWithoutAlt} image(s) missing alt text. This impacts accessibility and SEO.",
            ];
        }

        // Viewport warning
        $viewport = $this->extractMetaContent($crawler, 'viewport');
        if (empty($viewport)) {
            $warnings[] = [
                'type' => 'viewport_missing',
                'severity' => 'warning',
                'message' => 'Missing viewport meta tag. This impacts mobile responsiveness.',
            ];
        }

        // Canonical warning
        $canonical = $this->extractCanonical($crawler);
        if (empty($canonical)) {
            $warnings[] = [
                'type' => 'canonical_missing',
                'severity' => 'notice',
                'message' => 'Missing canonical URL. Consider adding one to prevent duplicate content issues.',
            ];
        }

        // Open Graph warnings
        $ogTitle = $this->extractMetaProperty($crawler, 'og:title');
        if (empty($ogTitle)) {
            $warnings[] = [
                'type' => 'og_missing',
                'severity' => 'notice',
                'message' => 'Missing Open Graph tags. These improve social media sharing.',
            ];
        }

        return $warnings;
    }

    /**
     * Clean and normalize text.
     */
    private function cleanText(?string $text): ?string
    {
        if ($text === null) {
            return null;
        }

        // Decode HTML entities
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        
        // Normalize whitespace
        $text = preg_replace('/\s+/', ' ', $text);
        
        return trim($text);
    }

    /**
     * Extract base URL from full URL.
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
     * Resolve relative URL to absolute URL.
     */
    private function resolveUrl(?string $url, string $baseUrl): ?string
    {
        if (empty($url)) {
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

        // Data URI or other special schemes
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
     * Check if URL is external.
     */
    private function isExternalUrl(string $url, string $baseUrl): bool
    {
        if (empty($url) || empty($baseUrl)) {
            return false;
        }

        // Resolve URL first
        $resolvedUrl = $this->resolveUrl($url, $baseUrl);
        
        $urlHost = parse_url($resolvedUrl, PHP_URL_HOST);
        $baseHost = parse_url($baseUrl, PHP_URL_HOST);

        return strtolower($urlHost ?? '') !== strtolower($baseHost ?? '');
    }
}
