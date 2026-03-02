<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;

/**
 * Playwright Renderer Service.
 *
 * Provides browser automation via Playwright for rendering JavaScript-heavy pages.
 * This service manages the browser lifecycle and extracts SEO-relevant content.
 *
 * @see TASK-22-5
 * @see TASK-22-15
 * @see SPEC-022
 */
class PlaywrightRendererService
{
    /**
     * Default browser options.
     */
    private const DEFAULT_OPTIONS = [
        'headless' => true,
        'timeout' => 30000,
        'waitUntil' => 'networkidle',
        'userAgent' => null,
        'viewport' => [
            'width' => 1920,
            'height' => 1080,
        ],
    ];

    /**
     * Maximum size for rendered HTML (in bytes).
     */
    private const MAX_HTML_SIZE = 10 * 1024 * 1024; // 10MB

    /**
     * The current browser instance.
     */
    protected mixed $browser = null;

    /**
     * Whether the browser is currently running.
     */
    protected bool $browserRunning = false;

    /**
     * Launch a new browser instance.
     *
     * @param array $options Browser launch options
     * @return mixed The browser instance
     * @throws \Exception If browser fails to launch
     */
    public function launchBrowser(array $options = []): mixed
    {
        if ($this->browserRunning && $this->browser) {
            return $this->browser;
        }

        $options = array_merge(self::DEFAULT_OPTIONS, $options);

        Log::info('PlaywrightRenderer: Launching browser', [
            'headless' => $options['headless'],
            'timeout' => $options['timeout'],
        ]);

        try {
            // In a real implementation, this would use Playwright PHP bindings
            // For now, we'll create a placeholder that can be mocked in tests
            $this->browser = (object) [
                'id' => Str::random(16),
                'options' => $options,
                'launchedAt' => now(),
            ];

            $this->browserRunning = true;

            Log::debug('PlaywrightRenderer: Browser launched', [
                'browserId' => $this->browser->id,
            ]);

            return $this->browser;
        } catch (\Exception $e) {
            Log::error('PlaywrightRenderer: Failed to launch browser', [
                'error' => $e->getMessage(),
            ]);

            throw new \Exception('Failed to launch Playwright browser: ' . $e->getMessage());
        }
    }

    /**
     * Render a page and extract content.
     *
     * @param string $url The URL to render
     * @param array $options Render options
     * @return array The rendered page data
     * @throws \Exception If page fails to render
     */
    public function renderPage(string $url, array $options = []): array
    {
        if (!$this->browserRunning || !$this->browser) {
            throw new \Exception('Browser not launched. Call launchBrowser() first.');
        }

        $options = array_merge(self::DEFAULT_OPTIONS, $options);
        $startTime = microtime(true);

        Log::info('PlaywrightRenderer: Rendering page', [
            'url' => $this->sanitizeUrlForLog($url),
            'timeout' => $options['timeout'],
        ]);

        try {
            // In a real implementation, this would use Playwright to:
            // 1. Navigate to the URL
            // 2. Wait for network idle
            // 3. Extract HTML, text, meta, links
            // For now, return a placeholder structure that can be mocked

            $html = $this->fetchAndRender($url, $options);
            $size = strlen($html);

            if ($size > self::MAX_HTML_SIZE) {
                throw new \Exception("Rendered HTML exceeds maximum size of " . self::MAX_HTML_SIZE . " bytes");
            }

            $result = [
                'html' => $html,
                'text' => $this->extractText($html),
                'meta' => $this->extractMeta($html),
                'links' => $this->extractLinks($html, $url),
                'size' => $size,
                'renderTime' => round((microtime(true) - $startTime) * 1000),
            ];

            Log::debug('PlaywrightRenderer: Page rendered', [
                'url' => $this->sanitizeUrlForLog($url),
                'size' => $size,
                'renderTime' => $result['renderTime'] . 'ms',
            ]);

            return $result;
        } catch (\Exception $e) {
            Log::error('PlaywrightRenderer: Failed to render page', [
                'url' => $this->sanitizeUrlForLog($url),
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Close the browser instance.
     *
     * @param mixed $browser The browser instance to close (optional)
     * @return void
     */
    public function closeBrowser(mixed $browser = null): void
    {
        $browserToClose = $browser ?? $this->browser;

        if ($browserToClose) {
            Log::debug('PlaywrightRenderer: Closing browser', [
                'browserId' => $browserToClose->id ?? 'unknown',
            ]);

            // In a real implementation, this would close the Playwright browser
            // For now, we just clear the reference
            if ($browserToClose === $this->browser) {
                $this->browser = null;
                $this->browserRunning = false;
            }
        }
    }

    /**
     * Check if the browser is currently running.
     *
     * @return bool
     */
    public function isBrowserRunning(): bool
    {
        return $this->browserRunning;
    }

    /**
     * Fetch and render a URL using a simple HTTP client.
     *
     * In a production environment, this would use Playwright to render JavaScript.
     * This placeholder implementation just fetches static HTML.
     *
     * @param string $url The URL to fetch
     * @param array $options Fetch options
     * @return string The rendered HTML
     */
    protected function fetchAndRender(string $url, array $options): string
    {
        // This is a placeholder implementation
        // In production, this would use Playwright to render JavaScript
        // For now, we'll use a simple HTTP fetch

        $timeout = $options['timeout'] ?? 30000;

        try {
            $response = \Illuminate\Support\Facades\Http::timeout($timeout / 1000)
                ->withHeaders([
                    'User-Agent' => $options['userAgent'] ?? 'SEOIndicator Crawler/1.0',
                ])
                ->get($url);

            if (!$response->successful()) {
                throw new \Exception("HTTP error: {$response->status()}");
            }

            return $response->body();
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            if (str_contains($e->getMessage(), 'timed out')) {
                throw new \Exception("Timeout exceeded: {$timeout}ms");
            }
            throw new \Exception("Connection failed: " . $e->getMessage());
        }
    }

    /**
     * Extract text content from HTML.
     *
     * @param string $html The HTML content
     * @return string The extracted text
     */
    protected function extractText(string $html): string
    {
        // Remove script and style tags
        $text = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $html);
        $text = preg_replace('/<style\b[^>]*>(.*?)<\/style>/is', '', $text);

        // Remove HTML tags
        $text = strip_tags($text);

        // Decode HTML entities
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Normalize whitespace
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);

        return $text;
    }

    /**
     * Extract meta tags from HTML.
     *
     * @param string $html The HTML content
     * @return array The extracted meta tags
     */
    protected function extractMeta(string $html): array
    {
        $meta = [];

        // Extract title
        if (preg_match('/<title>(.*?)<\/title>/is', $html, $matches)) {
            $meta['title'] = trim($matches[1]);
        }

        // Extract meta tags
        preg_match_all('/<meta\s+[^>]*name=["\']([^"\']+)["\'][^>]*content=["\']([^"\']*)["\'][^>]*>/is', $html, $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            $meta[$match[1]] = $match[2];
        }

        // Extract Open Graph tags
        preg_match_all('/<meta\s+[^>]*property=["\']og:([^"\']+)["\'][^>]*content=["\']([^"\']*)["\'][^>]*>/is', $html, $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            $meta['og:' . $match[1]] = $match[2];
        }

        return $meta;
    }

    /**
     * Extract links from HTML.
     *
     * @param string $html The HTML content
     * @param string $baseUrl The base URL for resolving relative links
     * @return array The extracted links
     */
    protected function extractLinks(string $html, string $baseUrl): array
    {
        $links = [];
        $baseUrlParts = parse_url($baseUrl);
        $baseHost = $baseUrlParts['host'] ?? '';

        preg_match_all('/<a\s+[^>]*href=["\']([^"\']+)["\'][^>]*>/is', $html, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $href = $match[1];

            // Skip empty, anchor, or javascript links
            if (empty($href) || str_starts_with($href, '#') || str_starts_with($href, 'javascript:')) {
                continue;
            }

            // Resolve relative URLs
            if (!str_starts_with($href, 'http')) {
                $href = $this->resolveUrl($href, $baseUrl);
            }

            // Determine if internal or external
            $hrefParts = parse_url($href);
            $isInternal = ($hrefParts['host'] ?? '') === $baseHost;

            $links[] = [
                'url' => $href,
                'internal' => $isInternal,
                'text' => strip_tags($match[0] ?? ''),
            ];
        }

        return $links;
    }

    /**
     * Resolve a relative URL against a base URL.
     *
     * @param string $relativeUrl The relative URL
     * @param string $baseUrl The base URL
     * @return string The resolved URL
     */
    protected function resolveUrl(string $relativeUrl, string $baseUrl): string
    {
        // If already absolute, return as-is
        if (str_starts_with($relativeUrl, 'http://') || str_starts_with($relativeUrl, 'https://')) {
            return $relativeUrl;
        }

        $baseParts = parse_url($baseUrl);
        $scheme = $baseParts['scheme'] ?? 'https';
        $host = $baseParts['host'] ?? '';
        $port = isset($baseParts['port']) ? ':' . $baseParts['port'] : '';

        // Handle protocol-relative URLs
        if (str_starts_with($relativeUrl, '//')) {
            return $scheme . ':' . $relativeUrl;
        }

        // Handle absolute paths
        if (str_starts_with($relativeUrl, '/')) {
            return "{$scheme}://{$host}{$port}{$relativeUrl}";
        }

        // Handle relative paths
        $path = $baseParts['path'] ?? '/';
        $path = dirname($path);
        return "{$scheme}://{$host}{$port}{$path}/{$relativeUrl}";
    }

    /**
     * Sanitize a URL for logging (remove credentials).
     *
     * @param string $url The URL to sanitize
     * @return string The sanitized URL
     */
    protected function sanitizeUrlForLog(string $url): string
    {
        return preg_replace('/^(https?:\/\/)([^:]+):([^@]+)@/', '$1$2:***@', $url);
    }
}
