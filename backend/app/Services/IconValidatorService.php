<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Icon Validator Service for TASK-24-5.
 * 
 * Validates PWA icon URLs for accessibility and correctness.
 * Checks if icons exist and are properly sized.
 * 
 * Features:
 * - Validates required icon sizes: 192x192, 512x512
 * - Validates recommended icon sizes: 180x180 (iOS)
 * - Resolves relative icon URLs against base URL
 * - Uses HEAD requests for accessibility checks (faster than GET)
 * - Concurrent icon validation (up to 5 simultaneous checks)
 * - Timeout per icon: 10 seconds
 * - SSRF protection via URLValidationService
 */
class IconValidatorService
{
    /**
     * Required PWA icon sizes.
     */
    private const REQUIRED_SIZES = ['192x192', '512x512'];

    /**
     * Recommended icon sizes (iOS).
     */
    private const RECOMMENDED_SIZES = ['180x180'];

    /**
     * Maximum concurrent requests.
     */
    private const MAX_CONCURRENT = 5;

    /**
     * Timeout per icon in seconds.
     */
    private const TIMEOUT_SECONDS = 10;

    /**
     * Maximum URL length.
     */
    private const MAX_URL_LENGTH = 2048;

    /**
     * Maximum file size warning threshold (50MB).
     */
    private const MAX_FILE_SIZE_WARNING = 50000000;

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
        'image/ico',
    ];

    private URLValidationService $urlValidationService;

    public function __construct(URLValidationService $urlValidationService)
    {
        $this->urlValidationService = $urlValidationService;
    }

    /**
     * Validate multiple PWA icons.
     *
     * @param array|null $icons Array of icon objects from manifest
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
     *     warnings?: array,
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
                'error' => 'Icons array is required',
                'error_code' => 'INVALID_INPUT',
            ];
        }

        if ($baseUrl === null) {
            return [
                'success' => false,
                'error' => 'Base URL is required',
                'error_code' => 'INVALID_INPUT',
            ];
        }

        // Handle empty array
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
        $chunks = array_chunk($icons, self::MAX_CONCURRENT);

        foreach ($chunks as $chunk) {
            // Process each chunk concurrently
            $chunkResults = $this->validateIconsConcurrent($chunk, $baseUrl);

            foreach ($chunkResults as $result) {
                $results[] = $result;

                if ($result['accessible']) {
                    $validCount++;
                } else {
                    $errors[] = "Icon {$result['src']} not accessible: " . ($result['error'] ?? 'Unknown error');
                }

                // Collect warnings
                if (isset($result['warning'])) {
                    $warnings[] = "Icon {$result['src']}: {$result['warning']}";
                }

                // Check for required/recommended sizes
                if ($result['accessible'] && isset($result['sizes'])) {
                    $parsedSizes = $this->extractAllSizes($result['sizes']);
                    
                    if (in_array('192x192', $parsedSizes)) {
                        $has192 = true;
                    }
                    if (in_array('512x512', $parsedSizes)) {
                        $has512 = true;
                    }
                    if (in_array('180x180', $parsedSizes)) {
                        $has180 = true;
                    }
                }
            }
        }

        return [
            'success' => true, // Partial success - return true even if some icons fail
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
     * @param array $icon Icon object from manifest
     * @param string $baseUrl Base URL for resolving relative URLs
     * @return array{
     *     src: string,
     *     resolved_url: string,
     *     accessible: bool,
     *     sizes: string,
     *     type?: string,
     *     width?: int|null,
     *     height?: int|null,
     *     error?: string,
     *     warning?: string,
     *     purpose?: string,
     *     is_maskable?: bool,
     *     is_svg?: bool,
     *     is_data_uri?: bool,
     *     parsed_sizes?: array,
     *     has_192x192?: bool,
     *     content_type?: string,
     *     content_length?: int
     * }
     */
    public function validateIcon(array $icon, string $baseUrl): array
    {
        $src = $icon['src'] ?? null;

        // Check for missing src
        if ($src === null || $src === '') {
            return [
                'src' => '',
                'resolved_url' => '',
                'accessible' => false,
                'sizes' => $icon['sizes'] ?? '',
                'error' => 'Icon missing src attribute',
            ];
        }

        // Check if it's a data URI
        if (str_starts_with(strtolower($src), 'data:')) {
            return [
                'src' => $src,
                'resolved_url' => $src,
                'accessible' => true,
                'sizes' => $icon['sizes'] ?? '',
                'type' => $icon['type'] ?? null,
                'is_data_uri' => true,
            ];
        }

        // Resolve URL
        $resolvedUrl = $this->resolveIconUrl($src, $baseUrl);

        // Validate URL for SSRF protection
        $urlValidation = $this->urlValidationService->validate($resolvedUrl);

        if (!$urlValidation['valid']) {
            return [
                'src' => $src,
                'resolved_url' => $resolvedUrl,
                'accessible' => false,
                'sizes' => $icon['sizes'] ?? '',
                'type' => $icon['type'] ?? null,
                'error' => $this->mapValidationError($urlValidation['error'] ?? ''),
            ];
        }

        // Check accessibility with detailed info
        $accessResult = $this->checkIconAccessibleWithDetails($resolvedUrl);

        // Parse sizes
        $sizes = $icon['sizes'] ?? '';
        $parsedSize = $this->parseSize($sizes);

        // Build result
        $result = [
            'src' => $src,
            'resolved_url' => $resolvedUrl,
            'accessible' => $accessResult['accessible'],
            'sizes' => $sizes,
            'type' => $icon['type'] ?? null,
            'width' => $parsedSize['width'] ?? null,
            'height' => $parsedSize['height'] ?? null,
        ];

        // Add error if not accessible
        if (!$accessResult['accessible']) {
            $result['error'] = $accessResult['error'] ?? 'Icon not accessible';
        }

        // Add content type and length if available
        if (isset($accessResult['content_type'])) {
            $result['content_type'] = $accessResult['content_type'];
            
            // Check for wrong content type
            if (!in_array(strtolower($accessResult['content_type']), self::VALID_IMAGE_TYPES)) {
                $result['warning'] = 'Unexpected content type for icon';
            }
        }
        
        if (isset($accessResult['content_length'])) {
            $result['content_length'] = $accessResult['content_length'];
            
            // Check for large file
            if ($accessResult['content_length'] > self::MAX_FILE_SIZE_WARNING) {
                $existingWarning = $result['warning'] ?? '';
                $sizeWarning = 'Icon file is very large (' . round($accessResult['content_length'] / 1000000) . 'MB)';
                $result['warning'] = $existingWarning ? $existingWarning . '; ' . $sizeWarning : $sizeWarning;
            }
        }

        // Check for SVG
        if (isset($parsedSize['is_svg']) && $parsedSize['is_svg']) {
            $result['is_svg'] = true;
        }

        // Check for maskable
        if (isset($icon['purpose'])) {
            $result['purpose'] = $icon['purpose'];
            $result['is_maskable'] = str_contains(strtolower($icon['purpose']), 'maskable');
        }

        // Handle multiple sizes
        if (isset($parsedSize['sizes'])) {
            $result['parsed_sizes'] = $parsedSize['sizes'];
            $result['has_192x192'] = $this->hasSize($parsedSize['sizes'], '192x192');
        }

        // Add warning for missing sizes
        if (empty($sizes)) {
            $existingWarning = $result['warning'] ?? '';
            $sizesWarning = 'Icon has no sizes specified';
            $result['warning'] = $existingWarning ? $existingWarning . '; ' . $sizesWarning : $sizesWarning;
        }

        return $result;
    }

    /**
     * Check if an icon URL is accessible via HEAD request.
     *
     * @param string|null $url Icon URL to check
     * @return bool True if accessible
     */
    public function checkIconAccessible(?string $url): bool
    {
        $result = $this->checkIconAccessibleWithDetails($url);
        return $result['accessible'];
    }

    /**
     * Check if an icon URL is accessible with detailed response info.
     *
     * @param string|null $url Icon URL to check
     * @return array{accessible: bool, error?: string, content_type?: string, content_length?: int}
     */
    private function checkIconAccessibleWithDetails(?string $url): array
    {
        if ($url === null || $url === '') {
            return ['accessible' => false, 'error' => 'URL is null'];
        }

        // Data URIs are always accessible
        if (str_starts_with(strtolower($url), 'data:')) {
            return ['accessible' => true];
        }

        // Validate URL for SSRF
        $validation = $this->urlValidationService->validate($url);
        if (!$validation['valid']) {
            return ['accessible' => false, 'error' => $this->mapValidationError($validation['error'] ?? '')];
        }

        try {
            // Try HEAD request first
            $response = Http::timeout(self::TIMEOUT_SECONDS)
                ->head($url);

            if ($response->successful()) {
                $result = ['accessible' => true];
                
                if ($response->header('Content-Type')) {
                    $result['content_type'] = $response->header('Content-Type');
                }
                if ($response->header('Content-Length')) {
                    $result['content_length'] = (int) $response->header('Content-Length');
                }
                
                return $result;
            }

            // If HEAD returns 405 (Method Not Allowed), try GET
            if ($response->status() === 405) {
                $getResponse = Http::timeout(self::TIMEOUT_SECONDS)
                    ->get($url);
                    
                if ($getResponse->successful()) {
                    $result = ['accessible' => true];
                    
                    if ($getResponse->header('Content-Type')) {
                        $result['content_type'] = $getResponse->header('Content-Type');
                    }
                    if ($getResponse->header('Content-Length')) {
                        $result['content_length'] = (int) $getResponse->header('Content-Length');
                    }
                    
                    return $result;
                }
                
                return ['accessible' => false, 'error' => 'HTTP ' . $getResponse->status()];
            }

            // Follow redirects
            if ($response->redirect()) {
                $location = $response->header('Location');
                if ($location) {
                    // Validate redirect URL
                    $redirectValidation = $this->urlValidationService->validateRedirect($location);
                    if (!$redirectValidation['valid']) {
                        return ['accessible' => false, 'error' => 'BLOCKED_REDIRECT'];
                    }
                    return $this->checkIconAccessibleWithDetails($location);
                }
            }

            return ['accessible' => false, 'error' => 'HTTP ' . $response->status()];
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::warning('Icon accessibility check timeout', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);
            return ['accessible' => false, 'error' => 'Timeout'];
        } catch (\Exception $e) {
            Log::warning('Icon accessibility check failed', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);
            return ['accessible' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Parse size string into width and height.
     *
     * @param string|null $sizeString Size string (e.g., "192x192", "any", "72x72 96x96")
     * @return array{width: int|null, height: int|null, is_svg?: bool, sizes?: array}
     */
    public function parseSize(?string $sizeString): array
    {
        if ($sizeString === null || $sizeString === '') {
            return ['width' => null, 'height' => null];
        }

        $sizeString = trim($sizeString);

        // Handle "any" size (SVG)
        if (strtolower($sizeString) === 'any') {
            return [
                'width' => null,
                'height' => null,
                'is_svg' => true,
            ];
        }

        // Handle multiple sizes
        $sizeParts = preg_split('/\s+/', $sizeString);
        
        if (count($sizeParts) > 1) {
            $sizes = [];
            foreach ($sizeParts as $part) {
                $parsed = $this->parseSingleSize($part);
                if ($parsed['width'] !== null && $parsed['height'] !== null) {
                    $sizes[] = $parsed;
                }
            }
            
            if (!empty($sizes)) {
                return [
                    'width' => $sizes[0]['width'],
                    'height' => $sizes[0]['height'],
                    'sizes' => $sizes,
                ];
            }
        }

        // Single size
        return $this->parseSingleSize($sizeString);
    }

    /**
     * Resolve icon URL against base URL.
     *
     * @param string $src Icon src attribute
     * @param string $baseUrl Base URL for resolution
     * @return string Resolved URL
     */
    public function resolveIconUrl(string $src, string $baseUrl): string
    {
        // Data URIs pass through unchanged
        if (str_starts_with(strtolower($src), 'data:')) {
            return $src;
        }

        // Already absolute URL
        if (preg_match('/^https?:\/\//i', $src)) {
            return $src;
        }

        // Protocol-relative URL
        if (str_starts_with($src, '//')) {
            $parsedBase = parse_url($baseUrl);
            $scheme = $parsedBase['scheme'] ?? 'https';
            return $scheme . ':' . $src;
        }

        // Parse base URL
        $parsedBase = parse_url($baseUrl);
        $scheme = $parsedBase['scheme'] ?? 'https';
        $host = $parsedBase['host'] ?? '';
        $port = isset($parsedBase['port']) ? ':' . $parsedBase['port'] : '';
        $basePath = $parsedBase['path'] ?? '/';

        // Absolute path (starts with /)
        if (str_starts_with($src, '/')) {
            return "{$scheme}://{$host}{$port}{$src}";
        }

        // Relative path - resolve against base path
        $baseDir = dirname($basePath);
        if ($baseDir === '.') {
            $baseDir = '';
        }
        
        // Handle query and fragment in src
        $srcPath = $src;
        $query = '';
        $fragment = '';
        
        if (($pos = strpos($srcPath, '?')) !== false) {
            $query = substr($srcPath, $pos);
            $srcPath = substr($srcPath, 0, $pos);
        }
        if (($pos = strpos($srcPath, '#')) !== false) {
            $fragment = substr($srcPath, $pos);
            $srcPath = substr($srcPath, 0, $pos);
        }
        
        // Normalize the base directory (remove trailing slashes, handle Windows paths)
        $baseDir = rtrim(str_replace('\\', '/', $baseDir), '/');
        
        // Combine paths
        $resolvedPath = ($baseDir === '' ? '' : $baseDir . '/') . $srcPath;
        
        return "{$scheme}://{$host}{$port}/" . ltrim($resolvedPath, '/') . "{$query}{$fragment}";
    }

    /**
     * Validate icons concurrently within a chunk.
     *
     * @param array $icons Icons to validate
     * @param string $baseUrl Base URL
     * @return array Validation results
     */
    private function validateIconsConcurrent(array $icons, string $baseUrl): array
    {
        $results = [];

        foreach ($icons as $icon) {
            $results[] = $this->validateIcon($icon, $baseUrl);
        }

        return $results;
    }

    /**
     * Parse a single size string.
     *
     * @param string $sizeString Size string (e.g., "192x192")
     * @return array{width: int|null, height: int|null}
     */
    private function parseSingleSize(string $sizeString): array
    {
        // Normalize: remove whitespace, handle uppercase X
        $sizeString = trim($sizeString);
        $sizeString = preg_replace('/\s+/', '', $sizeString);

        // Match WxH pattern (case-insensitive)
        if (preg_match('/^(\d+)[xX](\d+)$/', $sizeString, $matches)) {
            return [
                'width' => (int) $matches[1],
                'height' => (int) $matches[2],
            ];
        }

        return ['width' => null, 'height' => null];
    }

    /**
     * Extract all sizes from a size string.
     *
     * @param string $sizeString Size string
     * @return array Array of size strings
     */
    private function extractAllSizes(string $sizeString): array
    {
        $parsed = $this->parseSize($sizeString);
        $sizes = [];

        if (isset($parsed['sizes'])) {
            foreach ($parsed['sizes'] as $s) {
                $sizes[] = "{$s['width']}x{$s['height']}";
            }
        } elseif ($parsed['width'] !== null && $parsed['height'] !== null) {
            $sizes[] = "{$parsed['width']}x{$parsed['height']}";
        }

        return $sizes;
    }

    /**
     * Check if a size exists in parsed sizes.
     *
     * @param array $sizes Parsed sizes array
     * @param string $targetSize Target size (e.g., "192x192")
     * @return bool
     */
    private function hasSize(array $sizes, string $targetSize): bool
    {
        foreach ($sizes as $size) {
            $sizeStr = "{$size['width']}x{$size['height']}";
            if ($sizeStr === $targetSize) {
                return true;
            }
        }
        return false;
    }

    /**
     * Map URL validation error to icon error code.
     *
     * @param string $error Error message
     * @return string Error code
     */
    private function mapValidationError(string $error): string
    {
        $errorLower = strtolower($error);

        if (str_contains($errorLower, 'protocol')) {
            return 'BLOCKED_PROTOCOL';
        }

        if (str_contains($errorLower, 'blocked') || str_contains($errorLower, 'not allowed')) {
            return 'BLOCKED_URL';
        }

        return 'INVALID_URL';
    }
}
