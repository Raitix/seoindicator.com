<?php

namespace App\Services\Crawler;

use DateTimeInterface;
use Illuminate\Support\Str;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Link Extractor Service for HTML content.
 *
 * Extracts links from rendered HTML including anchor links, images,
 * stylesheets, icons, and iframes with comprehensive attribute extraction.
 *
 * @see TASK-22-10
 * @see SPEC-022
 */
class LinkExtractorService
{
    /**
     * Base URL for internal/external detection.
     */
    protected string $baseUrl;

    /**
     * Whether to include iframes.
     */
    protected bool $includeIframes;

    /**
     * URL normalizer for consistent URL handling.
     */
    protected UrlNormalizer $urlNormalizer;

    /**
     * Create a new LinkExtractorService instance.
     *
     * @param string $baseUrl Base URL for internal/external detection
     * @param bool $includeIframes Whether to include iframes
     */
    public function __construct(string $baseUrl = '', bool $includeIframes = false)
    {
        $this->baseUrl = $baseUrl;
        $this->includeIframes = $includeIframes;
        $this->urlNormalizer = new UrlNormalizer();
    }

    /**
     * Extract links from HTML content.
     *
     * @param string|null $html The HTML content
     * @param string $baseUrl The base URL for resolving relative URLs
     * @param int $depth The current crawl depth
     * @param string|null $parentUrl The parent URL
     * @param bool $stripFragments Whether to strip URL fragments
     * @return array Array of extracted links
     */
    public function extract(
        ?string $html,
        string $baseUrl,
        int $depth = 0,
        ?string $parentUrl = null,
        bool $stripFragments = true
    ): array {
        if (empty($html)) {
            return [];
        }

        $links = [];
        $baseParsed = parse_url($baseUrl);

        try {
            $crawler = new Crawler($html);

            // Extract anchor links
            $links = array_merge($links, $this->extractAnchorLinks($crawler, $baseUrl, $baseParsed, $depth, $parentUrl, $stripFragments));

            // Extract images
            $links = array_merge($links, $this->extractImages($crawler, $baseUrl, $baseParsed, $depth, $parentUrl));

            // Extract stylesheets
            $links = array_merge($links, $this->extractStylesheets($crawler, $baseUrl, $baseParsed, $depth, $parentUrl));

            // Extract icons
            $links = array_merge($links, $this->extractIcons($crawler, $baseUrl, $baseParsed, $depth, $parentUrl));

            // Extract iframes (if enabled)
            if ($this->includeIframes) {
                $links = array_merge($links, $this->extractIframes($crawler, $baseUrl, $baseParsed, $depth, $parentUrl));
            }

            // Extract JavaScript onclick links
            $links = array_merge($links, $this->extractJavaScriptLinks($crawler, $baseUrl, $baseParsed, $depth, $parentUrl, $stripFragments));

        } catch (\Exception $e) {
            // Return empty array on parsing errors
        }

        // Remove duplicates by URL
        $seenUrls = [];
        $uniqueLinks = [];

        foreach ($links as $link) {
            $normalizedUrl = $this->urlNormalizer->normalize($link['url']);
            if (!isset($seenUrls[$normalizedUrl])) {
                $seenUrls[$normalizedUrl] = true;
                $uniqueLinks[] = $link;
            }
        }

        return $uniqueLinks;
    }

    /**
     * Extract anchor links from HTML.
     */
    protected function extractAnchorLinks(
        Crawler $crawler,
        string $baseUrl,
        array $baseParsed,
        int $depth,
        ?string $parentUrl,
        bool $stripFragments
    ): array {
        $links = [];

        $crawler->filter('a[href]')->each(function (Crawler $node) use ($baseUrl, $baseParsed, $depth, $parentUrl, $stripFragments, &$links) {
            $href = trim($node->attr('href') ?? '');

            if (empty($href)) {
                return;
            }

            // Skip special protocols
            if ($this->shouldSkipUrl($href)) {
                return;
            }

            // Skip hash-only links (same-page fragments)
            if (str_starts_with($href, '#')) {
                return;
            }

            // Resolve URL
            $resolvedUrl = $this->resolveUrl($href, $baseUrl, $baseParsed, $stripFragments);

            if (empty($resolvedUrl)) {
                return;
            }

            // Skip mailto/tel links
            $scheme = strtolower(parse_url($resolvedUrl, PHP_URL_SCHEME) ?? '');
            if (in_array($scheme, ['mailto', 'tel', 'javascript'])) {
                return;
            }

            $link = [
                'url' => $resolvedUrl,
                'text' => $this->extractText($node) ?: null,
                'title' => $node->attr('title') ?: null,
                'rel' => $node->attr('rel') ?: null,
                'target' => $node->attr('target') ?: null,
                'internal' => $this->isInternalLink($resolvedUrl, $baseParsed),
                'type' => 'link',
                'depth' => $depth,
                'parentUrl' => $parentUrl,
                'discovered' => now(),
            ];

            $links[] = $link;
        });

        return $links;
    }

    /**
     * Extract image src attributes.
     */
    protected function extractImages(
        Crawler $crawler,
        string $baseUrl,
        array $baseParsed,
        int $depth,
        ?string $parentUrl
    ): array {
        $links = [];

        $crawler->filter('img[src]')->each(function (Crawler $node) use ($baseUrl, $baseParsed, $depth, $parentUrl, &$links) {
            $src = trim($node->attr('src') ?? '');

            if (empty($src)) {
                return;
            }

            $resolvedUrl = $this->resolveUrl($src, $baseUrl, $baseParsed);

            if (empty($resolvedUrl)) {
                return;
            }

            $links[] = [
                'url' => $resolvedUrl,
                'text' => $node->attr('alt') ?: null,
                'title' => $node->attr('title') ?: null,
                'rel' => null,
                'target' => null,
                'internal' => $this->isInternalLink($resolvedUrl, $baseParsed),
                'type' => 'image',
                'depth' => $depth,
                'parentUrl' => $parentUrl,
                'discovered' => now(),
            ];
        });

        return $links;
    }

    /**
     * Extract stylesheet links.
     */
    protected function extractStylesheets(
        Crawler $crawler,
        string $baseUrl,
        array $baseParsed,
        int $depth,
        ?string $parentUrl
    ): array {
        $links = [];

        $crawler->filter('link[rel="stylesheet"][href]')->each(function (Crawler $node) use ($baseUrl, $baseParsed, $depth, $parentUrl, &$links) {
            $href = trim($node->attr('href') ?? '');

            if (empty($href)) {
                return;
            }

            $resolvedUrl = $this->resolveUrl($href, $baseUrl, $baseParsed);

            if (empty($resolvedUrl)) {
                return;
            }

            $links[] = [
                'url' => $resolvedUrl,
                'text' => null,
                'title' => null,
                'rel' => 'stylesheet',
                'target' => null,
                'internal' => $this->isInternalLink($resolvedUrl, $baseParsed),
                'type' => 'stylesheet',
                'depth' => $depth,
                'parentUrl' => $parentUrl,
                'discovered' => now(),
            ];
        });

        return $links;
    }

    /**
     * Extract icon links.
     */
    protected function extractIcons(
        Crawler $crawler,
        string $baseUrl,
        array $baseParsed,
        int $depth,
        ?string $parentUrl
    ): array {
        $links = [];

        $crawler->filter('link[rel="icon"], link[rel="shortcut icon"]')->each(function (Crawler $node) use ($baseUrl, $baseParsed, $depth, $parentUrl, &$links) {
            $href = trim($node->attr('href') ?? '');

            if (empty($href)) {
                return;
            }

            $resolvedUrl = $this->resolveUrl($href, $baseUrl, $baseParsed);

            if (empty($resolvedUrl)) {
                return;
            }

            $links[] = [
                'url' => $resolvedUrl,
                'text' => null,
                'title' => null,
                'rel' => 'icon',
                'target' => null,
                'internal' => $this->isInternalLink($resolvedUrl, $baseParsed),
                'type' => 'icon',
                'depth' => $depth,
                'parentUrl' => $parentUrl,
                'discovered' => now(),
            ];
        });

        return $links;
    }

    /**
     * Extract iframe src attributes.
     */
    protected function extractIframes(
        Crawler $crawler,
        string $baseUrl,
        array $baseParsed,
        int $depth,
        ?string $parentUrl
    ): array {
        $links = [];

        $crawler->filter('iframe[src]')->each(function (Crawler $node) use ($baseUrl, $baseParsed, $depth, $parentUrl, &$links) {
            $src = trim($node->attr('src') ?? '');

            if (empty($src)) {
                return;
            }

            $resolvedUrl = $this->resolveUrl($src, $baseUrl, $baseParsed);

            if (empty($resolvedUrl)) {
                return;
            }

            $links[] = [
                'url' => $resolvedUrl,
                'text' => null,
                'title' => $node->attr('title') ?: null,
                'rel' => null,
                'target' => null,
                'internal' => $this->isInternalLink($resolvedUrl, $baseParsed),
                'type' => 'iframe',
                'depth' => $depth,
                'parentUrl' => $parentUrl,
                'discovered' => now(),
            ];
        });

        return $links;
    }

    /**
     * Extract JavaScript onclick links.
     */
    protected function extractJavaScriptLinks(
        Crawler $crawler,
        string $baseUrl,
        array $baseParsed,
        int $depth,
        ?string $parentUrl,
        bool $stripFragments
    ): array {
        $links = [];

        // Find elements with onclick containing location changes
        $crawler->filter('[onclick]')->each(function (Crawler $node) use ($baseUrl, $baseParsed, $depth, $parentUrl, $stripFragments, &$links) {
            $onclick = $node->attr('onclick') ?? '';

            // Extract URL from common JavaScript patterns
            $patterns = [
                "/location\.href\s*=\s*['\"]([^'\"]+)['\"]/i",
                "/window\.location\s*=\s*['\"]([^'\"]+)['\"]/i",
                "/window\.location\.href\s*=\s*['\"]([^'\"]+)['\"]/i",
            ];

            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $onclick, $matches)) {
                    $url = $matches[1];

                    if (!empty($url) && !str_starts_with($url, '#')) {
                        $resolvedUrl = $this->resolveUrl($url, $baseUrl, $baseParsed, $stripFragments);

                        if (!empty($resolvedUrl) && !$this->shouldSkipUrl($resolvedUrl)) {
                            $links[] = [
                                'url' => $resolvedUrl,
                                'text' => $this->extractText($node) ?: null,
                                'title' => $node->attr('title') ?: null,
                                'rel' => null,
                                'target' => null,
                                'internal' => $this->isInternalLink($resolvedUrl, $baseParsed),
                                'type' => 'link',
                                'depth' => $depth,
                                'parentUrl' => $parentUrl,
                                'discovered' => now(),
                            ];
                        }
                    }
                    break;
                }
            }
        });

        return $links;
    }

    /**
     * Resolve a URL relative to a base URL.
     */
    protected function resolveUrl(
        string $href,
        string $baseUrl,
        array $baseParsed,
        bool $stripFragments = true
    ): string {
        $href = trim($href);

        // Handle Unicode/UTF-8 URLs
        if (preg_match('/[^\x20-\x7E]/', $href)) {
            $href = $this->encodeUrl($href);
        }

        // Handle protocol-relative URLs
        if (str_starts_with($href, '//')) {
            $scheme = $baseParsed['scheme'] ?? 'https';
            $href = $scheme . ':' . $href;
        }

        // Handle absolute URLs
        if (str_starts_with($href, 'http://') || str_starts_with($href, 'https://')) {
            return $this->maybeStripFragment($href, $stripFragments);
        }

        // Handle special protocols
        if (preg_match('/^(mailto|tel|javascript|data|ftp|file):/i', $href)) {
            return '';
        }

        // Handle hash-only (same-page fragment)
        if (str_starts_with($href, '#')) {
            return '';
        }

        // Handle relative URLs
        $scheme = $baseParsed['scheme'] ?? 'https';
        $host = $baseParsed['host'] ?? '';
        $port = isset($baseParsed['port']) ? ':' . $baseParsed['port'] : '';

        // Handle root-relative URLs
        if (str_starts_with($href, '/')) {
            return $this->maybeStripFragment($scheme . '://' . $host . $port . $href, $stripFragments);
        }

        // Handle relative URLs
        $basePath = $baseParsed['path'] ?? '/';

        // If base path ends with '/', it's a directory
        if (str_ends_with($basePath, '/')) {
            $basePath = rtrim($basePath, '/');
        } else {
            // Base is a file, use parent directory
            $basePath = str_replace('\\', '/', dirname($basePath));
        }

        // Ensure base path ends with /
        if (!str_ends_with($basePath, '/')) {
            $basePath .= '/';
        }

        // Resolve parent directory references
        while (str_starts_with($href, '../')) {
            $basePath = str_replace('\\', '/', dirname($basePath));
            if (!str_ends_with($basePath, '/')) {
                $basePath .= '/';
            }
            $href = substr($href, 3);
        }

        // Handle current directory references
        if (str_starts_with($href, './')) {
            $href = substr($href, 2);
        }

        return $this->maybeStripFragment($scheme . '://' . $host . $port . $basePath . $href, $stripFragments);
    }

    /**
     * Strip fragment from URL if configured.
     */
    protected function maybeStripFragment(string $url, bool $strip): string
    {
        if ($strip && str_contains($url, '#')) {
            return strtok($url, '#');
        }
        return $url;
    }

    /**
     * Check if a URL should be skipped.
     */
    protected function shouldSkipUrl(string $href): bool
    {
        $href = strtolower(trim($href));

        $skipProtocols = ['mailto', 'javascript', 'tel', 'ftp', 'file', 'data'];

        foreach ($skipProtocols as $protocol) {
            if (str_starts_with($href, $protocol . ':')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if a link is internal to the base domain.
     */
    protected function isInternalLink(string $url, array $baseParsed): bool
    {
        $parsed = parse_url($url);

        if (!isset($parsed['host'])) {
            return true; // Relative URL, assume internal
        }

        $linkHost = strtolower($parsed['host']);
        $baseHost = strtolower($baseParsed['host'] ?? '');

        return $linkHost === $baseHost;
    }

    /**
     * Extract text content from a node.
     */
    protected function extractText(Crawler $node): string
    {
        return trim($node->text());
    }

    /**
     * Encode URL with Unicode characters.
     */
    protected function encodeUrl(string $url): string
    {
        // Split URL into parts
        $parsed = parse_url($url);

        if ($parsed === false) {
            return $url;
        }

        // Encode path
        if (isset($parsed['path'])) {
            $parsed['path'] = implode('/', array_map('rawurlencode', explode('/', $parsed['path'])));
        }

        // Rebuild URL
        $scheme = $parsed['scheme'] ?? 'https';
        $host = $parsed['host'] ?? '';
        $path = $parsed['path'] ?? '';
        $query = isset($parsed['query']) ? '?' . $parsed['query'] : '';

        return $scheme . '://' . $host . $path . $query;
    }
}
