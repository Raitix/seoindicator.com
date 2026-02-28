<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * URL Validation Service for TASK-16-3, TASK-19-15.
 * 
 * Validates URLs and prevents SSRF (Server-Side Request Forgery) attacks
 * by blocking access to internal/private IP addresses and dangerous protocols.
 * 
 * Features:
 * - Private IP range blocking (RFC 1918, link-local, etc.)
 * - Cloud metadata endpoint protection
 * - DNS rebinding protection via redirect validation
 * - Configurable whitelist
 * - Security audit logging
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
    private const DEFAULT_BLOCKED_HOSTS = [
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
    private const DEFAULT_BLOCKED_IPS = [
        '169.254.169.254', // AWS/Azure/GCP metadata
    ];

    /**
     * Runtime blocked hosts (configurable).
     */
    private array $blockedHosts = [];

    /**
     * Runtime blocked IPs (configurable).
     */
    private array $blockedIPs = [];

    /**
     * Whitelisted hosts (can bypass blocks).
     */
    private array $whitelist = [];

    /**
     * Initialize the service with default blocked lists.
     */
    public function __construct()
    {
        $this->blockedHosts = self::DEFAULT_BLOCKED_HOSTS;
        $this->blockedIPs = self::DEFAULT_BLOCKED_IPS;
    }

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

        // Decode URL-encoded host to catch encoded blocked hosts
        $decodedHost = urldecode($host);
        $hostLower = strtolower($decodedHost);

        // Check whitelist first (allows bypass of blocked lists)
        if ($this->isWhitelisted($hostLower)) {
            Log::info('URL allowed via whitelist', [
                'host' => $hostLower,
                'url' => $url,
            ]);

            return [
                'valid' => true,
                'normalized_url' => $this->normalizeUrl($parsed),
            ];
        }

        // Check for blocked hosts
        if (in_array($hostLower, $this->blockedHosts, true)) {
            $this->logBlockedRequest($url, 'host_blocked', $hostLower);
            return [
                'valid' => false,
                'error' => 'Access to this host is not allowed for security reasons.',
            ];
        }

        // Check for dangerous URL patterns (decimal, hex, octal IP notation)
        // Check both original and decoded host
        if ($this->isDangerousIPNotation($host) || $this->isDangerousIPNotation($decodedHost)) {
            $this->logBlockedRequest($url, 'dangerous_notation', $host);
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
                $this->logBlockedRequest($url, 'private_ip', $resolvedIP);
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
     * Validate a redirect URL (DNS rebinding protection).
     * 
     * This method should be called for each redirect during HTTP requests
     * to prevent DNS rebinding attacks where the initial URL resolves to a
     * valid public IP but redirects to a private/internal IP.
     *
     * @param string|null $url The redirect target URL
     * @return array{valid: bool, error?: string, normalized_url?: string}
     */
    public function validateRedirect(?string $url): array
    {
        // Use the same validation logic as initial URL
        // This ensures redirects are also checked for SSRF
        $result = $this->validate($url);

        // Log redirect validation for audit purposes
        if (!$result['valid']) {
            Log::warning('Redirect blocked by SSRF protection', [
                'url' => $url,
                'reason' => $result['error'] ?? 'unknown',
            ]);
        }

        return $result;
    }

    /**
     * Get structured log entry for a blocked request.
     *
     * @param string $url The blocked URL
     * @param string $reason The reason code
     * @param string|null $detail Additional detail
     * @return array{url: string, reason: string, detail: string|null, timestamp: string}
     */
    public function getBlockedRequestLog(string $url, string $reason, ?string $detail = null): array
    {
        return [
            'url' => $url,
            'reason' => $reason,
            'detail' => $detail,
            'timestamp' => now()->toISOString(),
        ];
    }

    /**
     * Get the current whitelist.
     *
     * @return array
     */
    public function getWhitelist(): array
    {
        return $this->whitelist;
    }

    /**
     * Add a host to the whitelist.
     * 
     * Whitelisted hosts can bypass the blocked host list.
     * Use with caution - only add trusted internal services.
     *
     * @param string $host Host to whitelist (e.g., 'trusted.internal')
     */
    public function addToWhitelist(string $host): void
    {
        $hostLower = strtolower($host);
        if (!in_array($hostLower, $this->whitelist, true)) {
            $this->whitelist[] = $hostLower;
            
            Log::info('Host added to SSRF whitelist', [
                'host' => $hostLower,
            ]);
        }
    }

    /**
     * Remove a host from the whitelist.
     *
     * @param string $host Host to remove from whitelist
     */
    public function removeFromWhitelist(string $host): void
    {
        $hostLower = strtolower($host);
        $this->whitelist = array_filter(
            $this->whitelist,
            fn($h) => $h !== $hostLower
        );
    }

    /**
     * Add a custom blocked host.
     *
     * @param string $host Host to block
     */
    public function addBlockedHost(string $host): void
    {
        $hostLower = strtolower($host);
        if (!in_array($hostLower, $this->blockedHosts, true)) {
            $this->blockedHosts[] = $hostLower;
            
            Log::info('Host added to SSRF blocked list', [
                'host' => $hostLower,
            ]);
        }
    }

    /**
     * Add a custom blocked IP.
     *
     * @param string $ip IP address to block
     */
    public function addBlockedIP(string $ip): void
    {
        if (!in_array($ip, $this->blockedIPs, true)) {
            $this->blockedIPs[] = $ip;
            
            Log::info('IP added to SSRF blocked list', [
                'ip' => $ip,
            ]);
        }
    }

    /**
     * Check if host is whitelisted.
     *
     * @param string $host
     * @return bool
     */
    private function isWhitelisted(string $host): bool
    {
        foreach ($this->whitelist as $whitelisted) {
            // Support wildcard matching (e.g., *.internal.trusted)
            if (str_starts_with($whitelisted, '*.')) {
                $domain = substr($whitelisted, 2);
                if ($host === $domain || str_ends_with($host, '.' . $domain)) {
                    return true;
                }
            } elseif ($host === $whitelisted) {
                return true;
            }
        }

        return false;
    }

    /**
     * Log a blocked request for security auditing.
     *
     * @param string $url
     * @param string $reason
     * @param string|null $detail
     */
    private function logBlockedRequest(string $url, string $reason, ?string $detail = null): void
    {
        $logEntry = $this->getBlockedRequestLog($url, $reason, $detail);

        Log::channel('security')->warning('SSRF request blocked', $logEntry);
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
        // Handle IPv6 addresses in brackets (including IPv4-mapped IPv6 like ::ffff:127.0.0.1)
        if (preg_match('/^\[([a-f0-9:\.]+)\]$/i', $host, $matches)) {
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
        if (in_array($ip, $this->blockedIPs, true)) {
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

        // IPv4-mapped IPv6 addresses (::ffff:x.x.x.x)
        if (str_starts_with($hex, '00000000000000000000ffff')) {
            // Extract the IPv4 part (last 4 bytes)
            $ipv4hex = substr($hex, -8);
            $ipv4 = implode('.', [
                hexdec(substr($ipv4hex, 0, 2)),
                hexdec(substr($ipv4hex, 2, 2)),
                hexdec(substr($ipv4hex, 4, 2)),
                hexdec(substr($ipv4hex, 6, 2)),
            ]);
            return $this->isPrivateOrBlockedIP($ipv4);
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
        return self::DEFAULT_BLOCKED_HOSTS;
    }

    /**
     * Get list of blocked IPs for testing/documentation.
     */
    public static function getBlockedIPs(): array
    {
        return self::DEFAULT_BLOCKED_IPS;
    }
}
