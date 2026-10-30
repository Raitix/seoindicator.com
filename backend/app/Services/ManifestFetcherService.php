<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Manifest Fetcher Service for TASK-24-3.
 *
 * Fetches and parses web app manifest from target URLs.
 * Handles HTML parsing to find manifest link and JSON retrieval.
 *
 * Features:
 * - Manifest URL extraction from HTML
 * - Relative URL resolution
 * - SSRF protection via URLValidationService
 * - Manifest size limits (10KB max)
 * - Request timeout handling (30s total)
 * - BOM stripping for UTF-8 manifests
 * - Cross-origin manifest URL support
 *
 * @see TASKS/TASK-24-3.md
 * @see TESTS/SPEC-24-3.md
 */
class ManifestFetcherService
{
    /**
     * Maximum manifest size in bytes (10KB).
     */
    private const MAX_MANIFEST_SIZE = 10 * 1024;

    /**
     * Request timeout in seconds.
     */
    private const TIMEOUT = 30;

    /**
     * URL Validation Service for SSRF protection.
     */
    private URLValidationService $urlValidationService;

    /**
     * HTML Fetcher Service for HTTP requests.
     */
    private HtmlFetcherService $htmlFetcherService;

    /**
     * Create a new ManifestFetcherService instance.
     */
    public function __construct(
        URLValidationService $urlValidationService,
        HtmlFetcherService $htmlFetcherService
    ) {
        $this->urlValidationService = $urlValidationService;
        $this->htmlFetcherService = $htmlFetcherService;
    }

    /**
     * Fetch and parse web app manifest from a URL.
     *
     * @param string|null $siteUrl The URL to fetch manifest from
     * @return array{
     *     success: bool,
     *     manifest: array|null,
     *     manifest_url: string|null,
     *     error: string|null,
     *     error_code: string|null
     * }
     */
    public function fetch(?string $siteUrl): array
    {
        // Validate URL is provided
        if ($siteUrl === null || $siteUrl === '') {
            return [
                'success' => false,
                'manifest' => null,
                'manifest_url' => null,
                'error' => 'URL is required',
                'error_code' => 'INVALID_URL',
            ];
        }

        // Validate URL for SSRF protection
        $validationResult = $this->urlValidationService->validate($siteUrl);
        if (!$validationResult['valid']) {
            return [
                'success' => false,
                'manifest' => null,
                'manifest_url' => null,
                'error' => $validationResult['error'] ?? 'Invalid URL',
                'error_code' => 'INVALID_URL',
            ];
        }

        $normalizedUrl = $validationResult['normalized_url'] ?? $siteUrl;

        // Fetch HTML content
        $htmlResult = $this->htmlFetcherService->fetch($normalizedUrl, [
            'timeout' => self::TIMEOUT,
        ]);

        if (!$htmlResult['success']) {
            return [
                'success' => false,
                'manifest' => null,
                'manifest_url' => null,
                'error' => $htmlResult['error'] ?? 'Failed to fetch HTML',
                'error_code' => 'HTML_FETCH_FAILED',
            ];
        }

        $html = $htmlResult['content'];
        $finalUrl = $htmlResult['finalUrl'] ?? $normalizedUrl;

        // Extract manifest URL from HTML
        $manifestHref = $this->extractManifestUrl($html, $finalUrl);

        if ($manifestHref === null) {
            Log::warning('No manifest link found in HTML', ['url' => $normalizedUrl]);
            
            return [
                'success' => false,
                'manifest' => null,
                'manifest_url' => null,
                'error' => 'No manifest link found in HTML',
                'error_code' => 'MANIFEST_NOT_FOUND',
            ];
        }

        // Resolve manifest URL to absolute
        $manifestUrl = $this->resolveManifestUrl($manifestHref, $finalUrl);

        if ($manifestUrl === null) {
            return [
                'success' => false,
                'manifest' => null,
                'manifest_url' => null,
                'error' => 'Failed to resolve manifest URL',
                'error_code' => 'INVALID_URL',
            ];
        }

        // Validate manifest URL for SSRF protection
        $manifestValidation = $this->urlValidationService->validate($manifestUrl);
        if (!$manifestValidation['valid']) {
            return [
                'success' => false,
                'manifest' => null,
                'manifest_url' => $manifestUrl,
                'error' => $manifestValidation['error'] ?? 'Access to this URL is not allowed for security reasons.',
                'error_code' => 'BLOCKED_URL',
            ];
        }

        // Fetch manifest JSON
        try {
            $response = Http::timeout(self::TIMEOUT)
                ->get($manifestUrl);

            if (!$response->successful()) {
                $statusCode = $response->status();
                
                return [
                    'success' => false,
                    'manifest' => null,
                    'manifest_url' => $manifestUrl,
                    'error' => "Failed to fetch manifest: HTTP {$statusCode}",
                    'error_code' => 'MANIFEST_FETCH_FAILED',
                ];
            }

            // Check manifest size
            $content = $response->body();
            if (strlen($content) > self::MAX_MANIFEST_SIZE) {
                return [
                    'success' => false,
                    'manifest' => null,
                    'manifest_url' => $manifestUrl,
                    'error' => 'Manifest exceeds maximum size limit (10KB)',
                    'error_code' => 'MANIFEST_TOO_LARGE',
                ];
            }

            // Strip BOM if present
            $content = $this->stripBom($content);

            // Parse JSON
            $manifest = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::warning('Invalid JSON in manifest', [
                    'url' => $manifestUrl,
                    'error' => json_last_error_msg(),
                ]);

                return [
                    'success' => false,
                    'manifest' => null,
                    'manifest_url' => $manifestUrl,
                    'error' => 'Invalid JSON in manifest',
                    'error_code' => 'INVALID_JSON',
                ];
            }

            Log::info('Manifest fetched successfully', [
                'url' => $normalizedUrl,
                'manifest_url' => $manifestUrl,
            ]);

            return [
                'success' => true,
                'manifest' => $manifest,
                'manifest_url' => $manifestUrl,
                'error' => null,
                'error_code' => null,
            ];
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            $message = $e->getMessage();
            
            if (str_contains($message, 'timeout') || str_contains($message, 'timed out')) {
                return [
                    'success' => false,
                    'manifest' => null,
                    'manifest_url' => $manifestUrl,
                    'error' => 'Request timed out',
                    'error_code' => 'TIMEOUT',
                ];
            }

            return [
                'success' => false,
                'manifest' => null,
                'manifest_url' => $manifestUrl,
                'error' => 'Connection failed: ' . $message,
                'error_code' => 'CONNECTION_FAILED',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'manifest' => null,
                'manifest_url' => $manifestUrl,
                'error' => 'Failed to fetch manifest: ' . $e->getMessage(),
                'error_code' => 'MANIFEST_FETCH_FAILED',
            ];
        }
    }

    /**
     * Extract manifest URL from HTML.
     *
     * Parses the HTML to find a <link rel="manifest"> element
     * and returns the href attribute value.
     *
     * @param string $html The HTML content to parse
     * @param string $baseUrl The base URL for resolving relative URLs
     * @return string|null The manifest URL or null if not found
     */
    public function extractManifestUrl(string $html, string $baseUrl): ?string
    {
        try {
            $crawler = new Crawler($html);

            // Find all link elements with rel="manifest"
            $manifestLinks = $crawler->filter('link[rel="manifest"]');

            if ($manifestLinks->count() === 0) {
                return null;
            }

            // Get the first manifest link's href attribute
            $href = $manifestLinks->first()->attr('href');

            if ($href === null || $href === '') {
                return null;
            }

            // Trim whitespace
            $href = trim($href);

            if ($href === '') {
                return null;
            }

            // Return the raw href - it may be relative, absolute, or protocol-relative
            // URL resolution is handled by resolveManifestUrl()
            return $href;
        } catch (\Exception $e) {
            Log::warning('Failed to parse HTML for manifest link', [
                'base_url' => $baseUrl,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Resolve relative manifest URL to absolute URL.
     *
     * Handles:
     * - Relative paths (e.g., /manifest.json)
     * - Relative paths with subdirectories (e.g., manifest.json)
     * - Absolute URLs (e.g., https://cdn.example.com/manifest.json)
     * - Protocol-relative URLs (e.g., //cdn.example.com/manifest.json)
     * - Non-HTTP schemes (e.g., file://, ftp://) - returned as-is for validation
     *
     * @param string|null $manifestUrl The manifest URL to resolve
     * @param string $baseUrl The base URL for resolution
     * @return string|null The resolved absolute URL or null if invalid
     */
    public function resolveManifestUrl(?string $manifestUrl, string $baseUrl): ?string
    {
        if ($manifestUrl === null || $manifestUrl === '') {
            return null;
        }

        // Trim whitespace
        $manifestUrl = trim($manifestUrl);

        if ($manifestUrl === '') {
            return null;
        }

        // Check if already an absolute URL with any scheme (http://, https://, file://, etc.)
        if (preg_match('/^[a-zA-Z][a-zA-Z0-9+.-]*:\/\//i', $manifestUrl)) {
            return $manifestUrl;
        }

        // Handle protocol-relative URL
        if (str_starts_with($manifestUrl, '//')) {
            // Use the scheme from base URL
            $parsedBase = parse_url($baseUrl);
            $scheme = $parsedBase['scheme'] ?? 'https';
            return $scheme . ':' . $manifestUrl;
        }

        // Parse base URL to get components
        $parsedBase = parse_url($baseUrl);
        
        if ($parsedBase === false || !isset($parsedBase['host'])) {
            return null;
        }

        $scheme = $parsedBase['scheme'] ?? 'https';
        $host = $parsedBase['host'];
        $port = isset($parsedBase['port']) ? ':' . $parsedBase['port'] : '';
        
        // For relative URLs, we only use the origin (scheme + host + port)
        // Query and fragment from base URL are ignored
        $origin = "{$scheme}://{$host}{$port}";

        // Handle absolute path (starts with /)
        if (str_starts_with($manifestUrl, '/')) {
            return $origin . $manifestUrl;
        }

        // Handle relative path
        // Get the directory path from base URL
        $basePath = $parsedBase['path'] ?? '/';
        
        // Ensure path ends with /
        if (!str_ends_with($basePath, '/')) {
            $basePath = dirname($basePath) . '/';
        }

        return $origin . $basePath . $manifestUrl;
    }

    /**
     * Strip UTF-8 BOM from content.
     *
     * @param string $content
     * @return string
     */
    private function stripBom(string $content): string
    {
        // UTF-8 BOM
        $bom = "\xEF\xBB\xBF";
        
        if (str_starts_with($content, $bom)) {
            return substr($content, 3);
        }

        return $content;
    }
}
