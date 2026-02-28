<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * HTML Fetcher Service with SSRF Protection.
 *
 * Fetches HTML content from URLs with comprehensive security measures:
 * - SSRF protection via URLValidationService
 * - Timeout handling
 * - Response size limits
 * - Caching
 * - SSL error handling
 * - Redirect following with validation
 *
 * @see TASK-18-5
 */
class HtmlFetcherService
{
    /**
     * Default timeout in seconds.
     */
    private const DEFAULT_TIMEOUT = 30;

    /**
     * Default maximum response size in bytes (10MB).
     */
    private const DEFAULT_MAX_SIZE = 10 * 1024 * 1024;

    /**
     * Default cache TTL in seconds (1 hour).
     */
    private const DEFAULT_CACHE_TTL = 3600;

    /**
     * Maximum redirect hops allowed.
     */
    private const MAX_REDIRECTS = 5;

    /**
     * Cache key for tracking cached URLs.
     */
    private const CACHED_URLS_KEY = 'html_fetcher_cached_urls';

    /**
     * Allowed content types for HTML responses.
     */
    private const ALLOWED_CONTENT_TYPES = [
        'text/html',
        'application/xhtml+xml',
    ];

    /**
     * Allowed URL protocols.
     */
    private const ALLOWED_PROTOCOLS = ['http', 'https'];

    /**
     * URL Validation Service for SSRF protection.
     */
    private URLValidationService $urlValidationService;

    /**
     * Create a new HtmlFetcherService instance.
     */
    public function __construct(URLValidationService $urlValidationService)
    {
        $this->urlValidationService = $urlValidationService;
    }

    /**
     * Fetch HTML content from a URL.
     *
     * @param string|null $url The URL to fetch
     * @param array $options Optional configuration
     *   - timeout: int Maximum time in seconds (default: 30)
     *   - maxSize: int Maximum response size in bytes (default: 10MB)
     *   - cacheTtl: int Cache TTL in seconds (default: 3600, 0 to disable)
     *   - maxRedirects: int Maximum redirect hops (default: 5)
     *   - verifySSL: bool Whether to verify SSL certificates (default: true)
     *
     * @return array{
     *     success: bool,
     *     content: string|null,
     *     url: string|null,
     *     finalUrl: string|null,
     *     statusCode: int|null,
     *     headers: array,
     *     timing: array{total: float},
     *     cached: bool,
     *     error: string|null,
     *     errorCode: string|null,
     *     redirectChain: array
     * }
     */
    public function fetch(?string $url, array $options = []): array
    {
        $startTime = microtime(true);

        // Default result structure
        $result = [
            'success' => false,
            'content' => null,
            'url' => $url,
            'finalUrl' => $url,
            'statusCode' => null,
            'headers' => [],
            'timing' => ['total' => 0],
            'cached' => false,
            'error' => null,
            'errorCode' => null,
            'redirectChain' => [],
        ];

        // Validate URL is provided
        if ($url === null || $url === '') {
            return $this->setErrorResult($result, 'URL is required', 'INVALID_URL', $startTime);
        }

        // Check protocol before validation
        $parsed = parse_url($url);
        $scheme = strtolower($parsed['scheme'] ?? '');

        if (!in_array($scheme, self::ALLOWED_PROTOCOLS, true)) {
            $errorCode = in_array($scheme, ['file', 'ftp', 'gopher']) ? 'BLOCKED_PROTOCOL' : 'INVALID_URL';
            return $this->setErrorResult($result, 'Invalid or blocked protocol', $errorCode, $startTime);
        }

        // Check if URL is a valid format
        if ($parsed === false || empty($parsed['host'])) {
            return $this->setErrorResult($result, 'Invalid URL format', 'INVALID_URL', $startTime);
        }

        // SSRF protection - validate URL
        try {
            $validationResult = $this->urlValidationService->validate($url);
            
            if (!$validationResult['valid']) {
                return $this->setErrorResult($result, $validationResult['error'] ?? 'URL blocked', 'BLOCKED_URL', $startTime);
            }
        } catch (\Exception $e) {
            return $this->setErrorResult($result, $e->getMessage(), 'BLOCKED_URL', $startTime);
        }

        // Merge options with defaults
        $timeout = $options['timeout'] ?? self::DEFAULT_TIMEOUT;
        $maxSize = $options['maxSize'] ?? self::DEFAULT_MAX_SIZE;
        $cacheTtl = $options['cacheTtl'] ?? self::DEFAULT_CACHE_TTL;
        $maxRedirects = $options['maxRedirects'] ?? self::MAX_REDIRECTS;
        $verifySSL = $options['verifySSL'] ?? true;

        // Check cache
        $cacheKey = $this->getCacheKey($url);
        if ($cacheTtl > 0 && Cache::has($cacheKey)) {
            $cached = Cache::get($cacheKey);
            if ($cached !== null) {
                $cached['cached'] = true;
                $cached['timing']['total'] = microtime(true) - $startTime;
                return $cached;
            }
        }

        // Track redirects
        $redirectChain = [];
        $currentUrl = $url;
        $redirectCount = 0;

        try {
            while ($redirectCount <= $maxRedirects) {
                // Build HTTP client with options - disable automatic redirect following
                // so we can validate each redirect URL for SSRF protection
                $client = Http::timeout($timeout)
                    ->withOptions([
                        'verify' => $verifySSL,
                        'allow_redirects' => false, // Handle redirects manually for SSRF protection
                    ]);

                // Make the request
                $response = $client->get($currentUrl);

                // Check for redirect
                $status = $response->status();
                if (in_array($status, [301, 302, 307, 308]) && $response->header('Location')) {
                    $redirectCount++;
                    
                    if ($redirectCount > $maxRedirects) {
                        return $this->setErrorResult(
                            $result,
                            'Maximum redirect limit exceeded',
                            'REDIRECT_LIMIT_EXCEEDED',
                            $startTime
                        );
                    }

                    $nextUrl = $response->header('Location');
                    $redirectChain[] = [
                        'from' => $currentUrl,
                        'to' => $nextUrl,
                        'status' => $status,
                    ];

                    // Validate redirect URL for SSRF
                    try {
                        $redirectValidation = $this->urlValidationService->validate($nextUrl);
                        
                        if (!$redirectValidation['valid']) {
                            return $this->setErrorResult(
                                $result,
                                'Redirect to blocked URL',
                                'BLOCKED_REDIRECT',
                                $startTime
                            );
                        }
                    } catch (\Exception $e) {
                        return $this->setErrorResult(
                            $result,
                            'Redirect to blocked URL',
                            'BLOCKED_REDIRECT',
                            $startTime
                        );
                    }

                    $currentUrl = $nextUrl;
                    $result['finalUrl'] = $currentUrl;
                    continue;
                }

                // Check response size from Content-Length header
                $contentLength = $response->header('Content-Length');
                if ($contentLength && (int) $contentLength > $maxSize) {
                    return $this->setErrorResult(
                        $result,
                        'Response exceeds maximum size limit',
                        'RESPONSE_TOO_LARGE',
                        $startTime
                    );
                }

                // Check HTTP status code
                if ($status === 404) {
                    return $this->setErrorResult($result, 'Resource not found', 'NOT_FOUND', $startTime, 404);
                }

                if ($status === 403) {
                    return $this->setErrorResult($result, 'Access forbidden', 'FORBIDDEN', $startTime, 403);
                }

                if ($status === 429) {
                    return $this->setErrorResult($result, 'Rate limited', 'RATE_LIMITED', $startTime, 429);
                }

                if ($status >= 400) {
                    $result['statusCode'] = $status;
                    return $this->setErrorResult($result, "HTTP error: {$status}", 'HTTP_ERROR', $startTime, $status);
                }

                // Validate content type
                $contentType = $response->header('Content-Type');
                if ($contentType && !$this->isValidContentType($contentType)) {
                    return $this->setErrorResult(
                        $result,
                        "Invalid content type: {$contentType}",
                        'INVALID_CONTENT_TYPE',
                        $startTime,
                        $status
                    );
                }

                // Get content and check actual size
                $content = $response->body();
                if (strlen($content) > $maxSize) {
                    return $this->setErrorResult(
                        $result,
                        'Response exceeds maximum size limit',
                        'RESPONSE_TOO_LARGE',
                        $startTime
                    );
                }

                // Success!
                $headers = [];
                foreach ($response->headers() as $name => $values) {
                    $headers[$name] = is_array($values) ? $values[0] : $values;
                }

                $result = [
                    'success' => true,
                    'content' => $content,
                    'url' => $url,
                    'finalUrl' => $currentUrl,
                    'statusCode' => $status,
                    'headers' => $headers,
                    'timing' => ['total' => microtime(true) - $startTime],
                    'cached' => false,
                    'error' => null,
                    'errorCode' => null,
                    'redirectChain' => $redirectChain,
                ];

                // Cache successful response
                if ($cacheTtl > 0) {
                    Cache::put($cacheKey, $result, $cacheTtl);
                    $this->trackCachedUrl($url);
                }

                // Log successful fetch for audit
                Log::info('HTML fetch successful', [
                    'url' => $url,
                    'finalUrl' => $currentUrl,
                    'status' => $status,
                    'size' => strlen($content),
                    'cached' => false,
                ]);

                return $result;
            }
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            $message = $e->getMessage();
            
            if (str_contains($message, 'timeout') || str_contains($message, 'timed out')) {
                return $this->setErrorResult($result, 'Request timed out', 'TIMEOUT', $startTime);
            }

            if (str_contains($message, 'SSL') || str_contains($message, 'certificate')) {
                return $this->setErrorResult($result, 'SSL certificate error', 'SSL_ERROR', $startTime);
            }

            if (str_contains($message, 'Connection refused')) {
                return $this->setErrorResult($result, 'Connection refused', 'CONNECTION_FAILED', $startTime);
            }

            return $this->setErrorResult($result, 'Connection failed: ' . $message, 'CONNECTION_FAILED', $startTime);
        } catch (\Exception $e) {
            $message = $e->getMessage();

            if (str_contains($message, 'timeout') || str_contains($message, 'timed out')) {
                return $this->setErrorResult($result, 'Request timed out', 'TIMEOUT', $startTime);
            }

            if (str_contains($message, 'SSL') || str_contains($message, 'certificate')) {
                return $this->setErrorResult($result, 'SSL certificate error', 'SSL_ERROR', $startTime);
            }

            return $this->setErrorResult($result, 'Request failed: ' . $message, 'REQUEST_FAILED', $startTime);
        }

        // Should not reach here, but handle gracefully
        return $this->setErrorResult($result, 'Unknown error', 'UNKNOWN_ERROR', $startTime);
    }

    /**
     * Check if a URL is fetchable (valid and not blocked).
     *
     * @param string|null $url
     * @return bool
     */
    public function isFetchable(?string $url): bool
    {
        if ($url === null || $url === '') {
            return false;
        }

        // Check protocol
        $parsed = parse_url($url);
        $scheme = strtolower($parsed['scheme'] ?? '');

        if (!in_array($scheme, self::ALLOWED_PROTOCOLS, true)) {
            return false;
        }

        try {
            $validationResult = $this->urlValidationService->validate($url);
            return $validationResult['valid'] === true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Clear the cached response for a URL.
     *
     * @param string $url
     * @return void
     */
    public function clearCache(string $url): void
    {
        $cacheKey = $this->getCacheKey($url);
        Cache::forget($cacheKey);
    }

    /**
     * Clear all cached responses.
     *
     * @return void
     */
    public function clearAllCache(): void
    {
        // Since we can't iterate all cache keys efficiently,
        // we use a cache tag prefix approach or flush if supported
        // For now, this is a simplified implementation
        // In production, consider using cache tags if supported by the driver
        
        // Alternative: Store a list of cached URLs
        $cachedUrls = Cache::get('html_fetcher_cached_urls', []);
        foreach ($cachedUrls as $cachedUrl) {
            $this->clearCache($cachedUrl);
        }
        Cache::forget('html_fetcher_cached_urls');
    }

    /**
     * Get the cache key for a URL.
     *
     * @param string $url
     * @return string
     */
    private function getCacheKey(string $url): string
    {
        return 'html_fetcher_' . md5($url);
    }

    /**
     * Track a cached URL for later cleanup.
     *
     * @param string $url
     * @return void
     */
    private function trackCachedUrl(string $url): void
    {
        $cachedUrls = Cache::get(self::CACHED_URLS_KEY, []);
        if (!in_array($url, $cachedUrls, true)) {
            $cachedUrls[] = $url;
            Cache::put(self::CACHED_URLS_KEY, $cachedUrls);
        }
    }

    /**
     * Check if content type is valid for HTML fetching.
     *
     * @param string|null $contentType
     * @return bool
     */
    private function isValidContentType(?string $contentType): bool
    {
        if ($contentType === null) {
            // No content-type header - accept with warning (logged)
            Log::warning('HTML fetch received response without Content-Type header');
            return true;
        }

        $contentType = strtolower($contentType);
        
        // Strip charset and other parameters
        $parts = explode(';', $contentType);
        $mimeType = trim($parts[0]);

        return in_array($mimeType, self::ALLOWED_CONTENT_TYPES, true);
    }

    /**
     * Set error result with timing.
     *
     * @param array $result
     * @param string $error
     * @param string $errorCode
     * @param float $startTime
     * @param int|null $statusCode
     * @return array
     */
    private function setErrorResult(
        array $result,
        string $error,
        string $errorCode,
        float $startTime,
        ?int $statusCode = null
    ): array {
        $result['success'] = false;
        $result['error'] = $error;
        $result['errorCode'] = $errorCode;
        $result['timing']['total'] = microtime(true) - $startTime;
        
        if ($statusCode !== null) {
            $result['statusCode'] = $statusCode;
        }

        // Log blocked requests for security audit
        if (in_array($errorCode, ['BLOCKED_URL', 'BLOCKED_REDIRECT', 'BLOCKED_PROTOCOL'])) {
            Log::channel('security')->warning('HTML fetch blocked', [
                'url' => $result['url'],
                'error' => $error,
                'errorCode' => $errorCode,
            ]);
        }

        return $result;
    }
}
