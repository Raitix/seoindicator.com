<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\ConnectionException;

/**
 * Icon Validator Service for TASK-24-5.
 *
 * Validates PWA icon URLs for accessibility and correctness.
 * Checks if icons exist and are properly sized.
 *
 * Features:
 * - Icon accessibility validation via HEAD requests
 * - Relative URL resolution against base URL
 * - SSRF protection via URLValidationService
 * - Concurrent icon validation (up to 5 simultaneous)
 * - Size parsing ("192x192", "any", multiple sizes)
 * - Required/recommended size detection
 * - Timeout handling (10s per icon)
 * - Content-type and size warnings
 *
 * @see TASKS/TASK-24-5.md
 * @see TESTS/SPEC-24-5.md
 */
class IconValidatorService
{
    /**
     * Required PWA icon sizes.
     */
    private const REQUIRED_SIZES = ['192x192', '512x512'];

    /**
     * Recommended icon sizes (iOS, etc.).
     */
    private const RECOMMENDED_SIZES = ['180x180'];

    /**
     * Maximum concurrent requests.
     */
    private const MAX_CONCURRENT = 5;

    /**
     * Timeout per icon request in seconds.
     */
    private const TIMEOUT = 10;

    /**
     * Maximum icon file size warning threshold (5MB).
     */
    private const SIZE_WARNING_THRESHOLD = 5 * 1024 * 1024;

    /**
     * Valid image content types.
     */
    private const VALID_IMAGE_TYPES = [
        'image/png',
        'image/jpeg',
        'image/jpg',
        'image/gif',
        'image/webp',
        'image/svg+xml',
        'image/x-icon',
        'image/vnd.microsoft.icon',
    ];

    /**
     * URL Validation Service for SSRF protection.
     */
    private URLValidationService $urlValidationService;

    /**
     * Create a new IconValidatorService instance.
     */
    public function __construct(URLValidationService $urlValidationService)
    {
        $this->urlValidationService = $urlValidationService;
    }

    /**
     * Validate an array of icons from a manifest.
     *
     * @param array|null $icons Array of icon definitions from manifest
     * @param string|null $baseUrl Base URL for resolving relative URLs
     * @return array{
     *     success: bool,
     *     total: int,
     *     valid: int,
     *     has_192x192: bool,
     *     has_512x512: bool,
     *     has_180x180: bool,
     *     icons: array,
     *     errors: array,
     *     warnings: array,
     *     error?: string,
     *     error_code?: string
     * }
     */
    public function validateIcons(?array $icons, ?string $baseUrl): array
    {
        // Validate inputs
        if ($icons === null) {
            return [
                'success' => false,
                'total' => 0,
                'valid' => 0,
                'has_192x192' => false,
                'has_512x512' => false,
                'has_180x180' => false,
                'icons' => [],
                'errors' => [],
                'warnings' => [],
                'error' => 'Icons array is required',
                'error_code' => 'INVALID_INPUT',
            ];
        }

        if ($baseUrl === null || $baseUrl === '') {
            return [
                'success' => false,
                'total' => 0,
                'valid' => 0,
                'has_192x192' => false,
                'has_512x512' => false,
                'has_180x180' => false,
                'icons' => [],
                'errors' => [],
                'warnings' => [],
                'error' => 'Base URL is required',
                'error_code' => 'INVALID_INPUT',
            ];
        }

        // Empty array is valid
        if (empty($icons)) {
            return [
                'success' => true,
                'total' => 0,
                'valid' => 0,
                'has_192x192' => false,
                'has_512x512' => false,
                'has_180x180' => false,
                'icons' => [],
                'errors' => [],
                'warnings' => [],
            ];
        }

        $results = [];
        $errors = [];
        $warnings = [];
        $validCount = 0;
        $has192 = false;
        $has512 = false;
        $has180 = false;

        // Process icons in batches for concurrent validation
        $batches = array_chunk($icons, self::MAX_CONCURRENT, true);

        foreach ($batches as $batch) {
            $batchResults = $this->validateIconsConcurrent($batch, $baseUrl);
            
            foreach ($batchResults as $index => $result) {
                $results[$index] = $result;

                if ($result['accessible']) {
                    $validCount++;
                } else {
                    $errors[] = [
                        'index' => $index,
                        'src' => $result['src'] ?? null,
                        'error' => $result['error'] ?? 'Unknown error',
                    ];
                }

                if (!empty($result['warning'])) {
                    $warnings[] = [
                        'index' => $index,
                        'src' => $result['src'] ?? null,
                        'warning' => $result['warning'],
                    ];
                }

                // Track size presence
                if ($result['accessible'] && !empty($result['parsed_sizes'])) {
                    foreach ($result['parsed_sizes'] as $size) {
                        $sizeStr = ($size['width'] ?? 0) . 'x' . ($size['height'] ?? 0);
                        if ($sizeStr === '192x192') $has192 = true;
                        if ($sizeStr === '512x512') $has512 = true;
                        if ($sizeStr === '180x180') $has180 = true;
                    }
                }

                // Also check for has_192x192 flag on individual result
                if (!empty($result['has_192x192'])) {
                    $has192 = true;
                }
            }
        }

        // Sort results by original index
        ksort($results);
        $results = array_values($results);

        return [
            'success' => true, // Partial success - even if some icons fail
            'total' => count($icons),
            'valid' => $validCount,
            'has_192x192' => $has192,
            'has_512x512' => $has512,
            'has_180x180' => $has180,
            'icons' => $results,
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }

    /**
     * Validate a single icon.
     *
     * @param array $icon Icon definition from manifest
     * @param string $baseUrl Base URL for resolving relative URLs
     * @return array{
     *     src: string,
     *     resolved_url: string|null,
     *     accessible: bool,
     *     sizes: string|null,
     *     width: int|null,
     *     height: int|null,
     *     parsed_sizes: array,
     *     has_192x192: bool,
     *     purpose: string|null,
     *     is_maskable: bool,
     *     is_svg: bool,
     *     is_data_uri: bool,
     *     content_type: string|null,
     *     content_length: int|null,
     *     warning: string|null,
     *     error: string|null
     * }
     */
    public function validateIcon(array $icon, string $baseUrl): array
    {
        $src = $icon['src'] ?? null;

        // Check for missing/empty src
        if ($src === null || $src === '') {
            return [
                'src' => $src ?? '',
                'resolved_url' => null,
                'accessible' => false,
                'sizes' => $icon['sizes'] ?? null,
                'width' => null,
                'height' => null,
                'parsed_sizes' => [],
                'has_192x192' => false,
                'purpose' => $icon['purpose'] ?? null,
                'is_maskable' => str_contains($icon['purpose'] ?? '', 'maskable'),
                'is_svg' => false,
                'is_data_uri' => false,
                'content_type' => null,
                'content_length' => null,
                'warning' => null,
                'error' => 'Icon is missing src attribute',
            ];
        }

        // Check for data URI
        if (str_starts_with(strtolower($src), 'data:')) {
            $parsedSizes = $this->parseSize($icon['sizes'] ?? null);
            
            return [
                'src' => $src,
                'resolved_url' => $src,
                'accessible' => true, // Data URIs are inherently accessible
                'sizes' => $icon['sizes'] ?? null,
                'width' => $parsedSizes['width'] ?? null,
                'height' => $parsedSizes['height'] ?? null,
                'parsed_sizes' => $parsedSizes['sizes'] ?? [],
                'has_192x192' => $parsedSizes['has_192x192'] ?? false,
                'purpose' => $icon['purpose'] ?? null,
                'is_maskable' => str_contains($icon['purpose'] ?? '', 'maskable'),
                'is_svg' => false,
                'is_data_uri' => true,
                'content_type' => null,
                'content_length' => null,
                'warning' => null,
                'error' => null,
            ];
        }

        // Resolve URL
        $resolvedUrl = $this->resolveIconUrl($src, $baseUrl);

        // Parse sizes
        $parsedSizes = $this->parseSize($icon['sizes'] ?? null);

        // Build base result
        $result = [
            'src' => $src,
            'resolved_url' => $resolvedUrl,
            'accessible' => false,
            'sizes' => $icon['sizes'] ?? null,
            'width' => $parsedSizes['width'] ?? null,
            'height' => $parsedSizes['height'] ?? null,
            'parsed_sizes' => $parsedSizes['sizes'] ?? [],
            'has_192x192' => $parsedSizes['has_192x192'] ?? false,
            'purpose' => $icon['purpose'] ?? null,
            'is_maskable' => str_contains($icon['purpose'] ?? '', 'maskable'),
            'is_svg' => $parsedSizes['is_svg'] ?? false,
            'is_data_uri' => false,
            'content_type' => null,
            'content_length' => null,
            'warning' => null,
            'error' => null,
        ];

        // Validate URL for SSRF protection
        $validationResult = $this->urlValidationService->validate($resolvedUrl);
        
        if (!$validationResult['valid']) {
            $result['error'] = 'BLOCKED_URL';
            $result['accessible'] = false;
            
            Log::warning('Icon URL blocked by SSRF protection', [
                'src' => $src,
                'resolved_url' => $resolvedUrl,
                'error' => $validationResult['error'] ?? 'Unknown',
            ]);
            
            return $result;
        }

        // Check accessibility
        $accessible = $this->checkIconAccessible($resolvedUrl);

        if (!$accessible) {
            $result['accessible'] = false;
            $result['error'] = 'ICON_NOT_ACCESSIBLE';
            return $result;
        }

        // Get additional info via HEAD request
        $info = $this->getIconInfo($resolvedUrl);

        $result['accessible'] = true;
        $result['content_type'] = $info['content_type'] ?? null;
        $result['content_length'] = $info['content_length'] ?? null;

        // Add warnings for non-image content types
        if ($info['content_type'] && !$this->isValidImageType($info['content_type'])) {
            $result['warning'] = "Unexpected content type: {$info['content_type']}";
        }

        // Add warning for large files
        if ($info['content_length'] && $info['content_length'] > self::SIZE_WARNING_THRESHOLD) {
            $sizeMB = round($info['content_length'] / 1024 / 1024, 2);
            $result['warning'] = ($result['warning'] ?? '') . " Large file size: {$sizeMB}MB";
            $result['warning'] = trim($result['warning']);
        }

        // Warning for missing sizes
        if (empty($icon['sizes'])) {
            $result['warning'] = ($result['warning'] ? $result['warning'] . ' ' : '') . 'Missing sizes attribute';
            $result['warning'] = trim($result['warning']);
        }

        return $result;
    }

    /**
     * Check if an icon URL is accessible via HEAD request.
     *
     * @param string|null $url The icon URL to check
     * @return bool True if accessible, false otherwise
     */
    public function checkIconAccessible(?string $url): bool
    {
        if ($url === null || $url === '') {
            return false;
        }

        // Validate URL for SSRF protection
        $validationResult = $this->urlValidationService->validate($url);
        
        if (!$validationResult['valid']) {
            return false;
        }

        try {
            // Try HEAD request first
            $response = Http::timeout(self::TIMEOUT)
                ->withOptions(['allow_redirects' => true])
                ->head($url);

            if ($response->successful()) {
                return true;
            }

            // If HEAD not allowed (405), try GET
            if ($response->status() === 405) {
                $getResponse = Http::timeout(self::TIMEOUT)
                    ->withOptions(['allow_redirects' => true])
                    ->get($url);
                
                return $getResponse->successful();
            }

            return false;
        } catch (ConnectionException $e) {
            $message = $e->getMessage();
            
            Log::warning('Icon accessibility check failed', [
                'url' => $url,
                'error' => $message,
            ]);
            
            return false;
        } catch (\Exception $e) {
            Log::warning('Icon accessibility check failed', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);
            
            return false;
        }
    }

    /**
     * Parse an icon size string.
     *
     * Handles formats:
     * - "192x192" - single size
     * - "any" - SVG any size
     * - "72x72 96x96 128x128" - multiple sizes
     *
     * @param string|null $sizeString The size string to parse
     * @return array{
     *     width: int|null,
     *     height: int|null,
     *     sizes: array,
     *     is_svg: bool,
     *     has_192x192: bool
     * }
     */
    public function parseSize(?string $sizeString): array
    {
        $result = [
            'width' => null,
            'height' => null,
            'sizes' => [],
            'is_svg' => false,
            'has_192x192' => false,
        ];

        if ($sizeString === null || $sizeString === '') {
            return $result;
        }

        // Normalize whitespace
        $sizeString = trim($sizeString);

        if ($sizeString === '') {
            return $result;
        }

        // Handle "any" for SVG
        if (strtolower($sizeString) === 'any') {
            $result['is_svg'] = true;
            return $result;
        }

        // Split by whitespace for multiple sizes
        $parts = preg_split('/\s+/', $sizeString);

        foreach ($parts as $part) {
            $part = trim($part);
            
            // Handle case-insensitive "X"
            $partNormalized = strtoupper($part);
            
            // Match WxH format (case-insensitive)
            if (preg_match('/^(\d+)\s*X\s*(\d+)$/i', $part, $matches)) {
                $width = (int) $matches[1];
                $height = (int) $matches[2];
                
                $result['sizes'][] = [
                    'width' => $width,
                    'height' => $height,
                ];

                // First size becomes the primary width/height
                if ($result['width'] === null) {
                    $result['width'] = $width;
                    $result['height'] = $height;
                }

                // Check for 192x192
                if ($width === 192 && $height === 192) {
                    $result['has_192x192'] = true;
                }
            }
        }

        return $result;
    }

    /**
     * Resolve an icon URL to an absolute URL.
     *
     * @param string|null $iconUrl The icon URL (may be relative)
     * @param string $baseUrl The base URL for resolution
     * @return string|null The resolved absolute URL
     */
    public function resolveIconUrl(?string $iconUrl, string $baseUrl): ?string
    {
        if ($iconUrl === null || $iconUrl === '') {
            return null;
        }

        // Data URIs are returned as-is
        if (str_starts_with(strtolower($iconUrl), 'data:')) {
            return $iconUrl;
        }

        // Already an absolute URL
        if (preg_match('/^[a-zA-Z][a-zA-Z0-9+.-]*:\/\//i', $iconUrl)) {
            return $iconUrl;
        }

        // Protocol-relative URL
        if (str_starts_with($iconUrl, '//')) {
            $parsedBase = parse_url($baseUrl);
            $scheme = $parsedBase['scheme'] ?? 'https';
            return $scheme . ':' . $iconUrl;
        }

        // Parse base URL
        $parsedBase = parse_url($baseUrl);
        
        if ($parsedBase === false || !isset($parsedBase['host'])) {
            return $baseUrl . $iconUrl;
        }

        $scheme = $parsedBase['scheme'] ?? 'https';
        $host = $parsedBase['host'];
        $port = isset($parsedBase['port']) ? ':' . $parsedBase['port'] : '';
        $origin = "{$scheme}://{$host}{$port}";

        // Absolute path
        if (str_starts_with($iconUrl, '/')) {
            return $origin . $iconUrl;
        }

        // Relative path
        $basePath = $parsedBase['path'] ?? '/';
        
        if (!str_ends_with($basePath, '/')) {
            $basePath = dirname($basePath) . '/';
        }

        return $origin . $basePath . $iconUrl;
    }

    /**
     * Validate icons concurrently using HTTP pool.
     *
     * @param array $icons Icons to validate (with original indices as keys)
     * @param string $baseUrl Base URL for resolution
     * @return array Validation results (indexed by original position)
     */
    private function validateIconsConcurrent(array $icons, string $baseUrl): array
    {
        $results = [];
        
        // Build URL list for concurrent validation
        $urls = [];
        foreach ($icons as $index => $icon) {
            $src = $icon['src'] ?? null;
            
            if ($src === null || $src === '') {
                $results[$index] = $this->validateIcon($icon, $baseUrl);
                continue;
            }

            // Data URIs
            if (str_starts_with(strtolower($src), 'data:')) {
                $results[$index] = $this->validateIcon($icon, $baseUrl);
                continue;
            }

            $resolvedUrl = $this->resolveIconUrl($src, $baseUrl);
            
            if ($resolvedUrl === null) {
                $results[$index] = $this->validateIcon($icon, $baseUrl);
                continue;
            }

            // Validate URL for SSRF
            $validationResult = $this->urlValidationService->validate($resolvedUrl);
            
            if (!$validationResult['valid']) {
                $results[$index] = $this->validateIcon($icon, $baseUrl);
                continue;
            }

            $urls[$index] = [
                'icon' => $icon,
                'resolved_url' => $resolvedUrl,
            ];
        }

        // If no URLs to check, return results
        if (empty($urls)) {
            return $results;
        }

        // Perform concurrent HEAD requests
        try {
            $responses = Http::pool(function (Pool $pool) use ($urls) {
                $promises = [];
                
                foreach ($urls as $index => $data) {
                    $promises[$index] = $pool->timeout(self::TIMEOUT)
                        ->withOptions(['allow_redirects' => true])
                        ->head($data['resolved_url']);
                }
                
                return $promises;
            });

            // Process responses
            foreach ($urls as $index => $data) {
                $icon = $data['icon'];
                $resolvedUrl = $data['resolved_url'];
                $response = $responses[$index] ?? null;

                $parsedSizes = $this->parseSize($icon['sizes'] ?? null);

                $result = [
                    'src' => $icon['src'] ?? '',
                    'resolved_url' => $resolvedUrl,
                    'accessible' => false,
                    'sizes' => $icon['sizes'] ?? null,
                    'width' => $parsedSizes['width'] ?? null,
                    'height' => $parsedSizes['height'] ?? null,
                    'parsed_sizes' => $parsedSizes['sizes'] ?? [],
                    'has_192x192' => $parsedSizes['has_192x192'] ?? false,
                    'purpose' => $icon['purpose'] ?? null,
                    'is_maskable' => str_contains($icon['purpose'] ?? '', 'maskable'),
                    'is_svg' => $parsedSizes['is_svg'] ?? false,
                    'is_data_uri' => false,
                    'content_type' => null,
                    'content_length' => null,
                    'warning' => null,
                    'error' => null,
                ];

                if ($response instanceof \Illuminate\Http\Client\Response) {
                    if ($response->successful()) {
                        $result['accessible'] = true;
                        $result['content_type'] = $response->header('Content-Type');
                        
                        $contentLength = $response->header('Content-Length');
                        if ($contentLength) {
                            $result['content_length'] = (int) $contentLength;
                        }

                        // Check for warnings
                        if ($result['content_type'] && !$this->isValidImageType($result['content_type'])) {
                            $result['warning'] = "Unexpected content type: {$result['content_type']}";
                        }

                        if ($result['content_length'] && $result['content_length'] > self::SIZE_WARNING_THRESHOLD) {
                            $sizeMB = round($result['content_length'] / 1024 / 1024, 2);
                            $result['warning'] = ($result['warning'] ?? '') . " Large file size: {$sizeMB}MB";
                            $result['warning'] = trim($result['warning']);
                        }
                    } elseif ($response->status() === 405) {
                        // HEAD not allowed, try GET for this specific icon
                        $getResult = $this->getIconInfo($resolvedUrl);
                        
                        if ($getResult['accessible'] ?? false) {
                            $result['accessible'] = true;
                            $result['content_type'] = $getResult['content_type'] ?? null;
                            $result['content_length'] = $getResult['content_length'] ?? null;
                        } else {
                            $result['error'] = 'ICON_NOT_ACCESSIBLE';
                        }
                    } else {
                        $result['error'] = 'ICON_NOT_ACCESSIBLE';
                    }
                } elseif ($response instanceof \Exception) {
                    $message = $response->getMessage();
                    
                    if (str_contains(strtolower($message), 'timeout')) {
                        $result['error'] = 'TIMEOUT';
                    } else {
                        $result['error'] = 'ICON_NOT_ACCESSIBLE';
                    }
                } else {
                    $result['error'] = 'ICON_NOT_ACCESSIBLE';
                }

                $results[$index] = $result;
            }
        } catch (\Exception $e) {
            // Fallback to sequential validation
            foreach ($urls as $index => $data) {
                if (!isset($results[$index])) {
                    $results[$index] = $this->validateIcon($data['icon'], $baseUrl);
                }
            }
        }

        return $results;
    }

    /**
     * Get icon info via HEAD request (or GET if HEAD fails).
     *
     * @param string $url The icon URL
     * @return array{
     *     accessible: bool,
     *     content_type: string|null,
     *     content_length: int|null
     * }
     */
    private function getIconInfo(string $url): array
    {
        $info = [
            'accessible' => false,
            'content_type' => null,
            'content_length' => null,
        ];

        try {
            $response = Http::timeout(self::TIMEOUT)
                ->withOptions(['allow_redirects' => true])
                ->head($url);

            if ($response->successful()) {
                $info['accessible'] = true;
                $info['content_type'] = $response->header('Content-Type');
                
                $contentLength = $response->header('Content-Length');
                if ($contentLength) {
                    $info['content_length'] = (int) $contentLength;
                }
            }
        } catch (\Exception $e) {
            // Ignore errors
        }

        return $info;
    }

    /**
     * Check if a content type is a valid image type.
     *
     * @param string|null $contentType
     * @return bool
     */
    private function isValidImageType(?string $contentType): bool
    {
        if ($contentType === null) {
            return false;
        }

        $contentType = strtolower($contentType);
        $parts = explode(';', $contentType);
        $mimeType = trim($parts[0]);

        return in_array($mimeType, self::VALID_IMAGE_TYPES, true);
    }
}
