<?php

namespace App\Services\Audit;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\ConnectionException;
use DOMDocument;
use DOMXPath;

/**
 * Service for checking broken links in HTML content.
 *
 * This service extracts links from HTML, validates them by making HTTP requests,
 * and categorizes them as valid, broken, redirect, timeout, or error.
 *
 * Features:
 * - Extracts links from anchor, img, link, script, iframe, video, and source elements
 * - HEAD requests for external links with GET fallback
 * - SSRF protection (blocks localhost, private IPs, cloud metadata)
 * - Rate limiting for external domains
 * - URL caching to avoid duplicate checks
 * - Comprehensive error handling
 *
 * @see TEST-14-10
 * @see TASK-14-10
 */
class BrokenLinkChecker
{
    /**
     * Default configuration.
     */
    protected array $config = [
        'timeout' => 10,
        'max_redirects' => 5,
        'user_agent' => 'SEOIndicator-Bot/1.0',
        'check_external' => true,
        'rate_limit_ms' => 100,
        'retries' => 2,
    ];

    /**
     * Cache of already-checked URLs.
     *
     * @var array<string, BrokenLinkResult>
     */
    protected array $urlCache = [];

    /**
     * Timestamps of last request per domain (for rate limiting).
     *
     * @var array<string, float>
     */
    protected array $lastRequestTime = [];

    /**
     * SSRF blocked IP patterns.
     */
    protected const SSRF_BLOCKED_PATTERNS = [
        // Localhost
        '/^localhost$/i',
        '/^127\.\d+\.\d+\.\d+$/',
        '/^::1$/',
        '/^\[::1\]$/',
        // Private IP ranges
        '/^10\.\d+\.\d+\.\d+$/',
        '/^172\.(1[6-9]|2\d|3[01])\.\d+\.\d+$/',
        '/^192\.168\.\d+\.\d+$/',
        // Cloud metadata
        '/^169\.254\.169\.254$/',
        // Link-local
        '/^169\.254\.\d+\.\d+$/',
        // IPv6 private
        '/^fc[0-9a-f]{2}:/i',
        '/^fd[0-9a-f]{2}:/i',
    ];

    /**
     * URL schemes to skip.
     */
    protected const SKIP_SCHEMES = [
        'javascript',
        'mailto',
        'tel',
        'data',
        'blob',
        'file',
        'ftp',
        'ssh',
    ];

    /**
     * Create a new BrokenLinkChecker instance.
     */
    public function __construct(array $config = [])
    {
        $this->config = array_merge($this->config, $config);
    }

    /**
     * Get current configuration.
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * Set configuration.
     */
    public function setConfig(array $config): void
    {
        $this->config = array_merge($this->config, $config);
    }

    /**
     * Check all links in HTML content.
     *
     * @param string $html HTML content to analyze
     * @param string $baseUrl Base URL for resolving relative links
     * @param array $options Optional overrides for configuration
     */
    public function check(string $html, string $baseUrl, array $options = []): CheckSummary
    {
        $previousConfig = $this->config;
        if (!empty($options)) {
            $this->config = array_merge($this->config, $options);
        }

        $links = $this->extractLinks($html, $baseUrl);
        $summary = new CheckSummary();

        foreach ($links as $linkData) {
            $url = $linkData['url'];

            // Skip if already checked (cache hit) - count each unique URL only once
            if (isset($this->urlCache[$url])) {
                continue;
            }

            $result = $this->checkUrl($url, $baseUrl, $linkData);
            $this->urlCache[$url] = $result;

            // Skip 'skipped' results (e.g., when external checking is disabled)
            if ($result->type !== 'skipped') {
                $summary->addResult($result);
            }
        }

        $this->config = $previousConfig;
        return $summary;
    }

    /**
     * Check a single URL.
     *
     * @param string $url URL to check
     * @param string|null $sourceUrl Source page URL (for context)
     * @param array $linkData Additional link context
     */
    public function checkUrl(string $url, ?string $sourceUrl = null, array $linkData = []): BrokenLinkResult
    {
        // Normalize URL
        $url = $this->normalizeUrl($url, $sourceUrl);
        $sourceUrl = $sourceUrl ?? '';

        // Check cache
        if (isset($this->urlCache[$url])) {
            return $this->urlCache[$url];
        }

        // Validate URL
        if (!$this->isValidUrl($url)) {
            return $this->createResult($url, $sourceUrl, null, 'error', 'Invalid URL format', $linkData);
        }

        // SSRF protection
        if ($this->isBlockedForSSRF($url)) {
            return BrokenLinkResult::blocked($url, $sourceUrl, 'SSRF protection: URL points to blocked host');
        }

        // Parse URL to determine if internal or external
        $urlParts = parse_url($url);
        $sourceParts = $sourceUrl ? parse_url($sourceUrl) : [];
        $isExternal = !$this->isSameDomain($urlParts, $sourceParts);

        // Skip external links if disabled
        if ($isExternal && !$this->config['check_external']) {
            return $this->createResult($url, $sourceUrl, null, 'skipped', 'External link checking disabled', $linkData);
        }

        // Make HTTP request
        $result = $this->makeRequest($url, $sourceUrl, $isExternal, $linkData);
        $this->urlCache[$url] = $result;

        return $result;
    }

    /**
     * Extract links from HTML content.
     *
     * @param string $html HTML content
     * @param string $baseUrl Base URL for resolving relative links
     * @return array<array{url: string, elementType: string, linkText: ?string, surroundingText: ?string}>
     */
    public function extractLinks(string $html, string $baseUrl): array
    {
        if (empty(trim($html))) {
            return [];
        }

        $links = [];
        $dom = new DOMDocument();

        // Suppress errors for malformed HTML
        libxml_use_internal_errors(true);
        $loaded = @$dom->loadHTML($html, LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();

        if (!$loaded) {
            return [];
        }

        $xpath = new DOMXPath($dom);
        $baseUrlParts = parse_url($baseUrl);

        // Extract anchor hrefs
        $nodes = $xpath->query('//a[@href]');
        foreach ($nodes as $node) {
            $href = $node->getAttribute('href');
            if ($link = $this->processLink($href, $baseUrl, $baseUrlParts, 'a', $node)) {
                $links[] = $link;
            }
        }

        // Extract image srcs
        $nodes = $xpath->query('//img[@src]');
        foreach ($nodes as $node) {
            $src = $node->getAttribute('src');
            if ($link = $this->processLink($src, $baseUrl, $baseUrlParts, 'img', $node)) {
                $links[] = $link;
            }
            // Also check srcset
            $srcset = $node->getAttribute('srcset');
            if ($srcset) {
                foreach ($this->parseSrcset($srcset) as $srcsetUrl) {
                    if ($link = $this->processLink($srcsetUrl, $baseUrl, $baseUrlParts, 'img', $node)) {
                        $links[] = $link;
                    }
                }
            }
        }

        // Extract link hrefs (stylesheets, icons, etc.)
        $nodes = $xpath->query('//link[@href]');
        foreach ($nodes as $node) {
            $href = $node->getAttribute('href');
            if ($link = $this->processLink($href, $baseUrl, $baseUrlParts, 'link', $node)) {
                $links[] = $link;
            }
        }

        // Extract script srcs
        $nodes = $xpath->query('//script[@src]');
        foreach ($nodes as $node) {
            $src = $node->getAttribute('src');
            if ($link = $this->processLink($src, $baseUrl, $baseUrlParts, 'script', $node)) {
                $links[] = $link;
            }
        }

        // Extract iframe srcs
        $nodes = $xpath->query('//iframe[@src]');
        foreach ($nodes as $node) {
            $src = $node->getAttribute('src');
            if ($link = $this->processLink($src, $baseUrl, $baseUrlParts, 'iframe', $node)) {
                $links[] = $link;
            }
        }

        // Extract video poster and src
        $nodes = $xpath->query('//video[@poster]');
        foreach ($nodes as $node) {
            $poster = $node->getAttribute('poster');
            if ($link = $this->processLink($poster, $baseUrl, $baseUrlParts, 'video', $node)) {
                $links[] = $link;
            }
        }
        $nodes = $xpath->query('//video[@src]');
        foreach ($nodes as $node) {
            $src = $node->getAttribute('src');
            if ($link = $this->processLink($src, $baseUrl, $baseUrlParts, 'video', $node)) {
                $links[] = $link;
            }
        }

        // Extract source srcs
        $nodes = $xpath->query('//source[@src]');
        foreach ($nodes as $node) {
            $src = $node->getAttribute('src');
            if ($link = $this->processLink($src, $baseUrl, $baseUrlParts, 'source', $node)) {
                $links[] = $link;
            }
        }

        return $links;
    }

    /**
     * Process a single link URL.
     */
    protected function processLink(string $href, string $baseUrl, array $baseUrlParts, string $elementType, $node): ?array
    {
        // Skip empty hrefs
        if (empty(trim($href))) {
            return null;
        }

        // Skip anchor-only links
        if (str_starts_with($href, '#')) {
            return null;
        }

        // Skip javascript:, mailto:, tel:, data:, etc.
        $scheme = parse_url($href, PHP_URL_SCHEME);
        if ($scheme && in_array(strtolower($scheme), self::SKIP_SCHEMES)) {
            return null;
        }

        // Resolve relative URL
        $absoluteUrl = $this->resolveUrl($href, $baseUrl);

        // Skip if we couldn't resolve
        if (!$absoluteUrl) {
            return null;
        }

        // Remove fragment for checking (but keep the original for reference)
        $checkUrl = $this->removeFragment($absoluteUrl);

        // Get link text
        $linkText = $this->getLinkText($node, $elementType);

        // Get surrounding text
        $surroundingText = $this->getSurroundingText($node);

        return [
            'url' => $checkUrl,
            'elementType' => $elementType,
            'linkText' => $linkText,
            'surroundingText' => $surroundingText,
        ];
    }

    /**
     * Parse srcset attribute to extract URLs.
     */
    protected function parseSrcset(string $srcset): array
    {
        $urls = [];
        $parts = explode(',', $srcset);

        foreach ($parts as $part) {
            $part = trim($part);
            $url = preg_split('/\s+/', $part)[0] ?? null;
            if ($url) {
                $urls[] = $url;
            }
        }

        return $urls;
    }

    /**
     * Resolve a URL relative to a base URL.
     */
    protected function resolveUrl(string $url, string $baseUrl): ?string
    {
        // Already absolute
        if (preg_match('/^https?:\/\//i', $url)) {
            return $url;
        }

        // Protocol-relative URL
        if (str_starts_with($url, '//')) {
            $baseScheme = parse_url($baseUrl, PHP_URL_SCHEME) ?? 'https';
            return $baseScheme . ':' . $url;
        }

        // Parse base URL
        $baseParts = parse_url($baseUrl);
        if (!$baseParts) {
            return null;
        }

        $scheme = $baseParts['scheme'] ?? 'https';
        $host = $baseParts['host'] ?? '';
        $port = isset($baseParts['port']) ? ':' . $baseParts['port'] : '';
        $basePath = $baseParts['path'] ?? '/';

        // Root-relative URL
        if (str_starts_with($url, '/')) {
            return $scheme . '://' . $host . $port . $url;
        }

        // Relative URL - handle base path
        // If base path ends with /, it's a directory, so use it as-is
        // Otherwise, get the directory of the file
        if (str_ends_with($basePath, '/')) {
            // Base is a directory, remove trailing slash for path resolution
            $baseDir = rtrim($basePath, '/');
        } else {
            // Base is a file, get its directory
            $baseDir = dirname($basePath);
            if ($baseDir === '.') {
                $baseDir = '';
            }
        }

        // Handle parent directory references
        $resolvedPath = $this->resolvePath($baseDir . '/' . $url);

        return $scheme . '://' . $host . $port . $resolvedPath;
    }

    /**
     * Resolve a path with . and .. references.
     */
    protected function resolvePath(string $path): string
    {
        $parts = [];
        $segments = explode('/', $path);

        foreach ($segments as $segment) {
            if ($segment === '..') {
                array_pop($parts);
            } elseif ($segment !== '.' && $segment !== '') {
                $parts[] = $segment;
            }
        }

        return '/' . implode('/', $parts);
    }

    /**
     * Remove fragment from URL.
     */
    protected function removeFragment(string $url): string
    {
        $pos = strpos($url, '#');
        if ($pos !== false) {
            return substr($url, 0, $pos);
        }
        return $url;
    }

    /**
     * Get link text from a node.
     */
    protected function getLinkText($node, string $elementType): ?string
    {
        if ($elementType === 'img') {
            return $node->getAttribute('alt') ?: null;
        }

        if ($elementType === 'a') {
            return trim($node->textContent) ?: null;
        }

        return null;
    }

    /**
     * Get surrounding text from a node.
     */
    protected function getSurroundingText($node): ?string
    {
        $parent = $node->parentNode;
        if ($parent) {
            $text = trim($parent->textContent);
            // Limit to 100 characters
            if (strlen($text) > 100) {
                return substr($text, 0, 97) . '...';
            }
            return $text ?: null;
        }
        return null;
    }

    /**
     * Make an HTTP request to check a URL.
     */
    protected function makeRequest(string $url, string $sourceUrl, bool $isExternal, array $linkData): BrokenLinkResult
    {
        // Rate limiting for external domains
        if ($isExternal) {
            $this->applyRateLimit($url);
        }

        $startTime = microtime(true);

        try {
            // For external URLs, try HEAD first then GET
            if ($isExternal) {
                return $this->makeExternalRequest($url, $sourceUrl, $linkData, $startTime);
            }

            // For internal URLs, use GET
            return $this->makeInternalRequest($url, $sourceUrl, $linkData, $startTime);

        } catch (ConnectionException $e) {
            $responseTime = (int) ((microtime(true) - $startTime) * 1000);
            $errorMessage = $e->getMessage();

            // Determine error type
            if (str_contains($errorMessage, 'timed out') || str_contains($errorMessage, 'timeout')) {
                return BrokenLinkResult::timeout($url, $sourceUrl, $errorMessage);
            }

            if (str_contains($errorMessage, 'SSL') || str_contains($errorMessage, 'certificate')) {
                return BrokenLinkResult::error($url, $sourceUrl, $errorMessage, 'error');
            }

            if (str_contains($errorMessage, 'resolve') || str_contains($errorMessage, 'DNS')) {
                return BrokenLinkResult::error($url, $sourceUrl, $errorMessage, 'broken');
            }

            return BrokenLinkResult::error($url, $sourceUrl, $errorMessage);

        } catch (\Exception $e) {
            $responseTime = (int) ((microtime(true) - $startTime) * 1000);
            return $this->createResult($url, $sourceUrl, null, 'error', $e->getMessage(), $linkData, null, $responseTime);
        }
    }

    /**
     * Make an external URL request (HEAD first, GET fallback).
     */
    protected function makeExternalRequest(string $url, string $sourceUrl, array $linkData, float $startTime): BrokenLinkResult
    {
        // Try HEAD request first
        try {
            $response = Http::withHeaders([
                'User-Agent' => $this->config['user_agent'],
            ])->timeout($this->config['timeout'])->maxRedirects(0)->head($url);

            $responseTime = (int) ((microtime(true) - $startTime) * 1000);
            $statusCode = $response->status();

            // HEAD not allowed, try GET
            if ($statusCode === 405 || $statusCode === 501) {
                return $this->makeGetRequest($url, $sourceUrl, $linkData, $startTime);
            }

            // Handle redirects - return redirect info, don't follow
            if ($statusCode >= 300 && $statusCode < 400 && $response->hasHeader('Location')) {
                $redirectUrl = $response->header('Location');
                $redirectUrl = $this->resolveUrl($redirectUrl, $url);

                return $this->createResult(
                    $url,
                    $sourceUrl,
                    $statusCode,
                    'redirect',
                    null,
                    $linkData,
                    $redirectUrl,
                    $responseTime
                );
            }

            return $this->createResult(
                $url,
                $sourceUrl,
                $statusCode,
                BrokenLinkResult::determineType($statusCode),
                null,
                $linkData,
                null,
                $responseTime
            );

        } catch (ConnectionException $e) {
            // HEAD failed, try GET
            return $this->makeGetRequest($url, $sourceUrl, $linkData, $startTime);
        }
    }

    /**
     * Make a GET request (for internal URLs or HEAD fallback).
     */
    protected function makeGetRequest(string $url, string $sourceUrl, array $linkData, float $startTime): BrokenLinkResult
    {
        try {
            $response = Http::withHeaders([
                'User-Agent' => $this->config['user_agent'],
            ])->timeout($this->config['timeout'])->maxRedirects(0)->get($url);

            $responseTime = (int) ((microtime(true) - $startTime) * 1000);
            $statusCode = $response->status();

            // Handle redirects - return redirect info, don't follow
            if ($statusCode >= 300 && $statusCode < 400 && $response->hasHeader('Location')) {
                $redirectUrl = $response->header('Location');
                $redirectUrl = $this->resolveUrl($redirectUrl, $url);

                return $this->createResult(
                    $url,
                    $sourceUrl,
                    $statusCode,
                    'redirect',
                    null,
                    $linkData,
                    $redirectUrl,
                    $responseTime
                );
            }

            return $this->createResult(
                $url,
                $sourceUrl,
                $statusCode,
                BrokenLinkResult::determineType($statusCode),
                null,
                $linkData,
                null,
                $responseTime
            );

        } catch (ConnectionException $e) {
            $responseTime = (int) ((microtime(true) - $startTime) * 1000);
            $errorMessage = $e->getMessage();

            if (str_contains($errorMessage, 'timed out') || str_contains($errorMessage, 'timeout')) {
                return BrokenLinkResult::timeout($url, $sourceUrl, $errorMessage);
            }

            if (str_contains($errorMessage, 'SSL') || str_contains($errorMessage, 'certificate')) {
                return BrokenLinkResult::error($url, $sourceUrl, $errorMessage, 'error');
            }

            if (str_contains($errorMessage, 'resolve') || str_contains($errorMessage, 'DNS')) {
                return BrokenLinkResult::error($url, $sourceUrl, $errorMessage, 'broken');
            }

            return BrokenLinkResult::error($url, $sourceUrl, $errorMessage);
        }
    }

    /**
     * Make an internal URL request.
     */
    protected function makeInternalRequest(string $url, string $sourceUrl, array $linkData, float $startTime): BrokenLinkResult
    {
        return $this->makeGetRequest($url, $sourceUrl, $linkData, $startTime);
    }

    /**
     * Apply rate limiting for a domain.
     */
    protected function applyRateLimit(string $url): void
    {
        $host = parse_url($url, PHP_URL_HOST) ?? '';

        if (!isset($this->lastRequestTime[$host])) {
            $this->lastRequestTime[$host] = microtime(true);
            return;
        }

        $elapsed = (microtime(true) - $this->lastRequestTime[$host]) * 1000;
        $requiredDelay = $this->config['rate_limit_ms'];

        if ($elapsed < $requiredDelay) {
            usleep((int) (($requiredDelay - $elapsed) * 1000));
        }

        $this->lastRequestTime[$host] = microtime(true);
    }

    /**
     * Create a BrokenLinkResult.
     */
    protected function createResult(
        string $url,
        string $sourceUrl,
        ?int $statusCode,
        string $type,
        ?string $errorMessage = null,
        array $linkData = [],
        ?string $redirectUrl = null,
        int $responseTimeMs = 0
    ): BrokenLinkResult {
        return new BrokenLinkResult(
            url: $url,
            sourceUrl: $sourceUrl,
            statusCode: $statusCode,
            type: $type,
            redirectUrl: $redirectUrl,
            responseTimeMs: $responseTimeMs,
            linkText: $linkData['linkText'] ?? null,
            elementType: $linkData['elementType'] ?? 'a',
            errorMessage: $errorMessage,
            surroundingText: $linkData['surroundingText'] ?? null,
        );
    }

    /**
     * Normalize a URL.
     */
    protected function normalizeUrl(string $url, ?string $baseUrl = null): string
    {
        // Remove fragment
        $url = $this->removeFragment($url);

        // Resolve relative URLs if base is provided
        if ($baseUrl && !preg_match('/^https?:\/\//i', $url)) {
            $resolved = $this->resolveUrl($url, $baseUrl);
            if ($resolved) {
                $url = $resolved;
            }
        }

        // Limit URL length
        if (strlen($url) > 2048) {
            $url = substr($url, 0, 2048);
        }

        return $url;
    }

    /**
     * Check if a URL is valid.
     */
    protected function isValidUrl(string $url): bool
    {
        // Must be http or https
        if (!preg_match('/^https?:\/\//i', $url)) {
            return false;
        }

        // Must be parseable
        $parts = parse_url($url);
        if (!$parts || !isset($parts['host'])) {
            return false;
        }

        return true;
    }

    /**
     * Check if a URL is blocked for SSRF protection.
     */
    protected function isBlockedForSSRF(string $url): bool
    {
        $host = parse_url($url, PHP_URL_HOST);

        if (!$host) {
            return false;
        }

        // Check against blocked patterns
        foreach (self::SSRF_BLOCKED_PATTERNS as $pattern) {
            if (preg_match($pattern, $host)) {
                return true;
            }
        }

        // Try to resolve the host and check IP
        $ips = gethostbynamel($host);
        if ($ips !== false) {
            foreach ($ips as $ip) {
                if ($this->isBlockedIP($ip)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check if an IP address is blocked.
     */
    protected function isBlockedIP(string $ip): bool
    {
        // Convert to long for comparison
        $long = ip2long($ip);
        if ($long === false) {
            return false;
        }

        // Localhost
        if ($ip === '127.0.0.1') {
            return true;
        }

        // Private ranges
        $privateRanges = [
            ['10.0.0.0', '10.255.255.255'],
            ['172.16.0.0', '172.31.255.255'],
            ['192.168.0.0', '192.168.255.255'],
            ['169.254.0.0', '169.254.255.255'], // Link-local
        ];

        foreach ($privateRanges as $range) {
            $start = ip2long($range[0]);
            $end = ip2long($range[1]);
            if ($long >= $start && $long <= $end) {
                return true;
            }
        }

        // Cloud metadata IP
        if ($ip === '169.254.169.254') {
            return true;
        }

        return false;
    }

    /**
     * Check if two URLs are on the same domain.
     */
    protected function isSameDomain(array $urlParts1, array $urlParts2): bool
    {
        if (empty($urlParts1) || empty($urlParts2)) {
            return false;
        }

        $host1 = strtolower($urlParts1['host'] ?? '');
        $host2 = strtolower($urlParts2['host'] ?? '');

        // Handle www prefix
        $host1 = preg_replace('/^www\./', '', $host1);
        $host2 = preg_replace('/^www\./', '', $host2);

        return $host1 === $host2;
    }

    /**
     * Clear the URL cache.
     */
    public function clearCache(): void
    {
        $this->urlCache = [];
        $this->lastRequestTime = [];
    }
}
