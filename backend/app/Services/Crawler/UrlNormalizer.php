<?php

namespace App\Services\Crawler;

use Illuminate\Support\Str;

/**
 * URL Normalizer for the Depth Controller Service.
 *
 * Normalizes URLs for consistent comparison and duplicate detection.
 *
 * @see TASK-22-8
 * @see SPEC-022
 */
class UrlNormalizer
{
    /**
     * Default tracking parameters to remove.
     */
    protected const DEFAULT_TRACKING_PARAMS = [
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'utm_term',
        'utm_content',
        'fbclid',
        'gclid',
        'msclkid',
    ];

    /**
     * Configuration options.
     */
    protected array $options;

    /**
     * Create a new UrlNormalizer instance.
     */
    public function __construct(array $options = [])
    {
        $this->options = array_merge([
            'keepTrailingSlash' => false,
            'removeTrackingParams' => true,
            'sortQueryParams' => true,
            'keepFragment' => false,
            'trackingParams' => self::DEFAULT_TRACKING_PARAMS,
        ], $options);
    }

    /**
     * Normalize a URL.
     *
     * @param string|null $url The URL to normalize
     * @return string The normalized URL
     */
    public function normalize(?string $url): string
    {
        if (empty($url)) {
            return '';
        }

        // Track if original URL has trailing slash
        $hadTrailingSlash = preg_match('#/+$#', parse_url($url, PHP_URL_PATH) ?? '');

        // Parse URL
        $parsed = parse_url($url);
        if ($parsed === false) {
            return '';
        }

        // Build normalized URL
        return $this->buildNormalizedUrl($parsed, $hadTrailingSlash);
    }

    /**
     * Build normalized URL from parsed components.
     */
    protected function buildNormalizedUrl(array $parsed, bool $hadTrailingSlash = false): string
    {
        $scheme = $this->normalizeScheme($parsed['scheme'] ?? 'https');
        $host = $this->normalizeHost($parsed['host'] ?? '');
        $port = $this->normalizePort($parsed['port'] ?? null, $scheme);
        $path = $this->normalizePath($parsed['path'] ?? '', $hadTrailingSlash);
        $query = $this->normalizeQuery($parsed['query'] ?? '');
        $fragment = $this->normalizeFragment($parsed['fragment'] ?? null);

        // Build URL
        $url = $scheme . '://';

        // Add credentials if present
        if (isset($parsed['user'])) {
            $url .= $parsed['user'];
            if (isset($parsed['pass'])) {
                $url .= ':' . $parsed['pass'];
            }
            $url .= '@';
        }

        $url .= $host;

        if ($port) {
            $url .= ':' . $port;
        }

        $url .= $path;

        if ($query) {
            $url .= '?' . $query;
        }

        if ($fragment) {
            $url .= '#' . $fragment;
        }

        return $url;
    }

    /**
     * Normalize scheme to lowercase.
     */
    protected function normalizeScheme(string $scheme): string
    {
        return strtolower($scheme);
    }

    /**
     * Normalize host to lowercase (with IDN conversion).
     */
    protected function normalizeHost(string $host): string
    {
        $host = strtolower($host);

        // Convert IDN to punycode
        if (preg_match('/[^\x20-\x7E]/', $host)) {
            $punycode = idn_to_ascii($host, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46);
            if ($punycode !== false) {
                $host = $punycode;
            }
        }

        return $host;
    }

    /**
     * Normalize port (remove default ports).
     */
    protected function normalizePort(?int $port, string $scheme): ?int
    {
        if ($port === null) {
            return null;
        }

        // Remove default ports
        if ($scheme === 'http' && $port === 80) {
            return null;
        }

        if ($scheme === 'https' && $port === 443) {
            return null;
        }

        return $port;
    }

    /**
     * Normalize path (handle trailing slashes).
     */
    protected function normalizePath(string $path, bool $hadTrailingSlash = false): string
    {
        // Remove multiple trailing slashes
        $path = rtrim($path, '/');

        // Keep at least a root path
        if (empty($path)) {
            return '';
        }

        // Add back trailing slash if configured and original had it
        if ($this->options['keepTrailingSlash'] && $hadTrailingSlash && !empty($path)) {
            $path .= '/';
        }

        return $path;
    }

    /**
     * Normalize query string (remove tracking params, sort).
     */
    protected function normalizeQuery(?string $query): string
    {
        if (empty($query)) {
            return '';
        }

        // Parse query string
        parse_str($query, $params);

        if (empty($params)) {
            return '';
        }

        // Remove tracking parameters
        if ($this->options['removeTrackingParams']) {
            $params = $this->removeTrackingParams($params);
        }

        if (empty($params)) {
            return '';
        }

        // Sort parameters
        if ($this->options['sortQueryParams']) {
            ksort($params);
        }

        // Build query string
        return http_build_query($params);
    }

    /**
     * Remove tracking parameters from query.
     */
    protected function removeTrackingParams(array $params): array
    {
        $trackingParams = $this->options['trackingParams'];

        foreach ($trackingParams as $trackingParam) {
            // Support wildcard matching (e.g., utm_*)
            if (Str::endsWith($trackingParam, '*')) {
                $prefix = substr($trackingParam, 0, -1);
                foreach (array_keys($params) as $key) {
                    if (Str::startsWith($key, $prefix)) {
                        unset($params[$key]);
                    }
                }
            } else {
                unset($params[$trackingParam]);
            }
        }

        return $params;
    }

    /**
     * Normalize fragment.
     */
    protected function normalizeFragment(?string $fragment): ?string
    {
        if ($this->options['keepFragment']) {
            return $fragment;
        }

        return null;
    }

    // =========================================================================
    // Helper Methods
    // =========================================================================

    /**
     * Get the host from a URL.
     */
    public function getHost(string $url): string
    {
        $parsed = parse_url($url);
        return strtolower($parsed['host'] ?? '');
    }

    /**
     * Get the path from a URL.
     */
    public function getPath(string $url): string
    {
        $parsed = parse_url($url);
        return $parsed['path'] ?? '';
    }

    /**
     * Get query parameters from a URL.
     */
    public function getQueryParams(string $url): array
    {
        $parsed = parse_url($url);
        $query = $parsed['query'] ?? '';

        if (empty($query)) {
            return [];
        }

        parse_str($query, $params);
        return $params;
    }

    /**
     * Check if two URLs have the same origin.
     */
    public function isSameOrigin(string $url1, string $url2): bool
    {
        $parsed1 = parse_url($url1);
        $parsed2 = parse_url($url2);

        $scheme1 = strtolower($parsed1['scheme'] ?? '');
        $scheme2 = strtolower($parsed2['scheme'] ?? '');

        $host1 = strtolower($parsed1['host'] ?? '');
        $host2 = strtolower($parsed2['host'] ?? '');

        $port1 = $this->normalizePort($parsed1['port'] ?? null, $scheme1) ?? ($scheme1 === 'https' ? 443 : 80);
        $port2 = $this->normalizePort($parsed2['port'] ?? null, $scheme2) ?? ($scheme2 === 'https' ? 443 : 80);

        return $scheme1 === $scheme2 && $host1 === $host2 && $port1 === $port2;
    }
}
