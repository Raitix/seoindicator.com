<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * URL Validation Service for TASK-16-3.
 * 
 * Validates URLs and prevents SSRF (Server-Side Request Forgery) attacks
 * by blocking access to internal/private IP addresses and dangerous protocols.
 */
class URLValidationService
{
    /**
     * Allowed protocols for external fetching.
     */
    private const ALLOWED_PROTOCOLS = ['http', 'https'];

    /**
     * Maximum URL length.
     */
    private const MAX_URL_LENGTH = 2048;

    /**
     * Private IP ranges (RFC 1918, link-local, etc.)
     */
    private const PRIVATE_IP_RANGES = [
        // IPv4 - Loopback
        ['start' => '127.0.0.0', 'end' => '127.255.255.255'],
        // IPv4 - Private Class A
        ['start' => '10.0.0.0', 'end' => '10.255.255.255'],
        // IPv4 - Private Class B
        ['start' => '172.16.0.0', 'end' => '172.31.255.255'],
        // IPv4 - Private Class C
        ['start' => '192.168.0.0', 'end' => '192.168.255.255'],
        // IPv4 - Link-local
        ['start' => '169.254.0.0', 'end' => '169.254.255.255'],
        // IPv4 - This network
        ['start' => '0.0.0.0', 'end' => '0.255.255.255'],
    ];

    /**
     * Cloud metadata endpoints (AWS, GCP, Azure, etc.)
     */
    private const BLOCKED_HOSTS = [
        'localhost',
        'localhost.localdomain',
        'ip6-localhost',
        'ip6-loopback',
        'metadata.google.internal',
        'metadata',
        'kubernetes.default',
        'kubernetes.default.svc',
    ];

    /**
     * Blocked IPs (cloud metadata endpoints).
     */
    private const BLOCKED_IPS = [
        '169.254.169.254', // AWS/Azure/GCP metadata
    ];

    /**
     * Validate a URL for safe external fetching.
     *
     * @param string|null $url
     * @return array{valid: bool, error?: string, normalized_url?: string}
     */
    public function validate(?string $url): array
    {
        // Check for null/empty
        if ($url === null || $url === '') {
            return [
                'valid' => false,
                'error' => 'URL is required.',
            ];
        }

        // Check URL length
        if (strlen($url) > self::MAX_URL_LENGTH) {
            return [
                'valid' => false,
                'error' => 'URL is too long. Maximum length is ' . self::MAX_URL_LENGTH . ' characters.',
            ];
        }

        // Parse URL
        $parsed = parse_url($url);
        if ($parsed === false) {
            return [
                'valid' => false,
                'error' => 'Invalid URL format.',
            ];
        }

        // Check scheme/protocol
        $scheme = strtolower($parsed['scheme'] ?? '');
        if (!in_array($scheme, self::ALLOWED_PROTOCOLS, true)) {
            return [
                'valid' => false,
                'error' => 'Invalid protocol. Only HTTP and HTTPS are allowed.',
            ];
        }

        // Check host exists
        $host = $parsed['host'] ?? null;
        if (empty($host)) {
            return [
                'valid' => false,
                'error' => 'URL must contain a valid host.',
            ];
        }

        // Check for blocked hosts
        $hostLower = strtolower($host);
        if (in_array($hostLower, self::BLOCKED_HOSTS, true)) {
            return [
                'valid' => false,
                'error' => 'Access to this host is not allowed for security reasons.',
            ];
        }

        // Check for dangerous URL patterns (decimal, hex, octal IP notation)
        if ($this->isDangerousIPNotation($host)) {
            return [
                'valid' => false,
                'error' => 'Access to this URL is not allowed for security reasons.',
            ];
        }

        // Resolve host to IP and check for private/internal IPs
        $resolvedIP = $this->resolveHostToIP($host);
        if ($resolvedIP === null) {
            // DNS resolution failed - could be invalid domain or blocked
            // We'll allow the request to proceed and let the HTTP client handle the error
            Log::warning('DNS resolution failed for URL', ['host' => $host, 'url' => $url]);
        } else {
            // Check if resolved IP is private or blocked
            if ($this->isPrivateOrBlockedIP($resolvedIP)) {
                return [
                    'valid' => false,
                    'error' => 'Access to this URL is not allowed for security reasons.',
                ];
            }
        }

        return [
            'valid' => true,
            'normalized_url' => $this->normalizeUrl($parsed),
        ];
    }

    /**
     * Check if host uses dangerous IP notation (decimal, hex, octal).
     */
    private function isDangerousIPNotation(string $host): bool
    {
        // Check for decimal IP notation (e.g., 2130706433 = 127.0.0.1)
        if (preg_match('/^\d+$/', $host)) {
            $ip = long2ip((int)$host);
            if ($ip !== false && $this->isPrivateOrBlockedIP($ip)) {
                return true;
            }
        }

        // Check for hex IP notation (e.g., 0x7f000001 = 127.0.0.1)
        if (preg_match('/^0x[0-9a-f]+$/i', $host)) {
            $ip = long2ip(hexdec($host));
            if ($ip !== false && $this->isPrivateOrBlockedIP($ip)) {
                return true;
            }
        }

        // Check for octal IP notation (e.g., 0177.0.0.1 = 127.0.0.1)
        if (preg_match('/^0[0-7]+(\.[0-7]+)*$/', $host)) {
            $parts = explode('.', $host);
            $decimalParts = array_map(function ($part) {
                return octdec($part);
            }, $parts);
            if (count($decimalParts) === 4) {
                $ip = implode('.', $decimalParts);
                if ($this->isPrivateOrBlockedIP($ip)) {
                    return true;
                }
            }
        }

        // Check for mixed notation with hex/octal parts
        if (preg_match('/[0x][0-9a-f]+/i', $host) || preg_match('/^0[0-7]/', $host)) {
            // More aggressive check for mixed notations
            $ip = $this->tryParseMixedNotation($host);
            if ($ip !== null && $this->isPrivateOrBlockedIP($ip)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Try to parse mixed notation IP addresses.
     */
    private function tryParseMixedNotation(string $host): ?string
    {
        $parts = explode('.', $host);
        if (count($parts) !== 4) {
            return null;
        }

        $decimalParts = [];
        foreach ($parts as $part) {
            if (preg_match('/^0x([0-9a-f]+)$/i', $part, $matches)) {
                $decimalParts[] = hexdec($matches[1]);
            } elseif (preg_match('/^0([0-7]+)$/', $part, $matches)) {
                $decimalParts[] = octdec($matches[1]);
            } elseif (is_numeric($part)) {
                $decimalParts[] = (int)$part;
            } else {
                return null;
            }
        }

        foreach ($decimalParts as $value) {
            if ($value < 0 || $value > 255) {
                return null;
            }
        }

        return implode('.', $decimalParts);
    }

    /**
     * Resolve hostname to IP address.
     */
    private function resolveHostToIP(string $host): ?string
    {
        // Handle IPv6 addresses in brackets
        if (preg_match('/^\[([a-f0-9:]+)\]$/i', $host, $matches)) {
            $ipv6 = $matches[1];
            if ($this->isIPv6Private($ipv6)) {
                return $ipv6; // Will be blocked by isPrivateOrBlockedIP
            }
            return $ipv6;
        }

        // Check if already an IP address
        if (filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return $host;
        }

        if (filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return $host;
        }

        // Perform DNS lookup
        $records = @dns_get_record($host, DNS_A);
        if ($records === false || empty($records)) {
            // Try AAAA record for IPv6
            $records = @dns_get_record($host, DNS_AAAA);
            if ($records === false || empty($records)) {
                return null;
            }
        }

        return $records[0]['ip'] ?? $records[0]['ipv6'] ?? null;
    }

    /**
     * Check if an IP is private or blocked.
     */
    private function isPrivateOrBlockedIP(string $ip): bool
    {
        // Check against blocked IPs list
        if (in_array($ip, self::BLOCKED_IPS, true)) {
            return true;
        }

        // Check for IPv6 private addresses
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return $this->isIPv6Private($ip);
        }

        // Check for IPv4 private addresses
        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return false; // Invalid IP format
        }

        // Convert IP to long integer
        $ipLong = ip2long($ip);
        if ($ipLong === false) {
            return false;
        }

        // Check against private IP ranges
        foreach (self::PRIVATE_IP_RANGES as $range) {
            $start = ip2long($range['start']);
            $end = ip2long($range['end']);
            if ($ipLong >= $start && $ipLong <= $end) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if an IPv6 address is private/link-local.
     */
    private function isIPv6Private(string $ip): bool
    {
        // Expand abbreviated IPv6
        $expanded = inet_pton($ip);
        if ($expanded === false) {
            return false;
        }

        $hex = bin2hex($expanded);

        // ::1 - Loopback
        if ($ip === '::1' || $hex === '00000000000000000000000000000001') {
            return true;
        }

        // fe80::/10 - Link-local
        if (str_starts_with($hex, 'fe8') || str_starts_with($hex, 'fe9') || 
            str_starts_with($hex, 'fea') || str_starts_with($hex, 'feb')) {
            return true;
        }

        // fc00::/7 - Unique local addresses (ULA)
        if (str_starts_with($hex, 'fc') || str_starts_with($hex, 'fd')) {
            return true;
        }

        // :: - Unspecified
        if ($hex === '00000000000000000000000000000000') {
            return true;
        }

        return false;
    }

    /**
     * Normalize a parsed URL.
     */
    private function normalizeUrl(array $parsed): string
    {
        $scheme = strtolower($parsed['scheme'] ?? 'https');
        $host = strtolower($parsed['host'] ?? '');
        $port = isset($parsed['port']) ? ':' . $parsed['port'] : '';
        $path = $parsed['path'] ?? '/';
        $query = isset($parsed['query']) ? '?' . $parsed['query'] : '';
        $fragment = isset($parsed['fragment']) ? '#' . $parsed['fragment'] : '';

        return "{$scheme}://{$host}{$port}{$path}{$query}{$fragment}";
    }

    /**
     * Get list of blocked IP ranges for testing/documentation.
     */
    public static function getPrivateIPRanges(): array
    {
        return self::PRIVATE_IP_RANGES;
    }

    /**
     * Get list of blocked hosts for testing/documentation.
     */
    public static function getBlockedHosts(): array
    {
        return self::BLOCKED_HOSTS;
    }

    /**
     * Get list of blocked IPs for testing/documentation.
     */
    public static function getBlockedIPs(): array
    {
        return self::BLOCKED_IPS;
    }
}
