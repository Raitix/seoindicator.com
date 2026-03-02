<?php

namespace App\Services\Crawler;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * URL Validator Service for SSRF Protection
 *
 * Comprehensive URL validation and SSRF (Server-Side Request Forgery) protection
 * to prevent malicious requests to internal resources.
 *
 * Features:
 * - Protocol validation (HTTP/HTTPS only)
 * - IP address blocking (localhost, private networks, metadata endpoints)
 * - Domain blocking (*.internal, *.local, *.localhost)
 * - DNS rebinding protection
 * - URL normalization
 * - Redirect validation support
 *
 * @see TASK-22-7
 * @see SPEC-022
 */
class UrlValidatorService
{
    /**
     * Default allowed protocols.
     */
    private const DEFAULT_ALLOWED_PROTOCOLS = ['http', 'https'];

    /**
     * Maximum URL length.
     */
    private const MAX_URL_LENGTH = 2083;

    /**
     * Blocked IPv4 ranges (CIDR notation).
     */
    private const BLOCKED_IPV4_RANGES = [
        '127.0.0.0/8',      // Localhost
        '10.0.0.0/8',       // Private Class A
        '172.16.0.0/12',    // Private Class B
        '192.168.0.0/16',   // Private Class C
        '169.254.0.0/16',   // Link-local (AWS/GCP metadata)
        '0.0.0.0/8',        // Current network
        '224.0.0.0/4',      // Multicast
        '240.0.0.0/4',      // Reserved
    ];

    /**
     * Blocked IPv6 ranges.
     */
    private const BLOCKED_IPV6_RANGES = [
        '::1/128',          // Localhost
        '::/128',           // Unspecified
        'fc00::/7',         // Unique local
        'fe80::/10',        // Link-local
        'ff00::/8',         // Multicast
    ];

    /**
     * Default blocked domain patterns.
     */
    private const DEFAULT_BLOCKED_DOMAINS = [
        '*.internal',
        '*.local',
        '*.localhost',
        '*.internal.*',
    ];

    /**
     * Known cloud metadata hostnames.
     */
    private const METADATA_HOSTS = [
        'metadata.google.internal',
        'metadata',
        'kubernetes.default',
        'kubernetes.default.svc',
        'kubernetes',
    ];

    /**
     * Configuration cache.
     */
    private array $config;

    /**
     * Create a new UrlValidatorService instance.
     */
    public function __construct()
    {
        $this->loadConfig();
    }

    /**
     * Validate a URL for safe crawling.
     *
     * @param string|null $url The URL to validate
     * @return array ValidationResult with keys: valid, url, originalUrl, host, ip, error, blocked, blockReason
     */
    public function validate(?string $url): array
    {
        // Initialize result structure
        $result = [
            'valid' => false,
            'url' => null,
            'originalUrl' => $url,
            'host' => null,
            'ip' => null,
            'error' => null,
            'blocked' => false,
            'blockReason' => null,
        ];

        // Check for null/empty
        if ($url === null || $url === '') {
            $result['error'] = 'URL format invalid';
            return $result;
        }

        // Trim whitespace
        $url = trim($url);
        $result['originalUrl'] = $url;

        // Check URL length
        if (strlen($url) > self::MAX_URL_LENGTH) {
            $result['error'] = 'URL format invalid';
            return $result;
        }

        // Check for null bytes
        if (str_contains($url, "\x00")) {
            $result['error'] = 'URL format invalid';
            return $result;
        }

        // Parse URL
        $parsed = parse_url($url);
        if ($parsed === false) {
            $result['error'] = 'URL format invalid';
            return $result;
        }

        // Validate protocol
        $protocolResult = $this->validateProtocol($parsed);
        if ($protocolResult !== null) {
            $result['error'] = $protocolResult['error'];
            $result['blocked'] = $protocolResult['blocked'] ?? false;
            $result['blockReason'] = $protocolResult['blockReason'] ?? null;
            return $result;
        }

        // Extract and validate host
        $host = $parsed['host'] ?? null;
        if (empty($host)) {
            // Check for IPv6 in URL format (parse_url may not handle it correctly)
            if (preg_match('#^https?://\[([a-f0-9:\.]+)\]#i', $url, $matches)) {
                $host = $matches[1];
            } else {
                $result['error'] = 'URL format invalid';
                return $result;
            }
        }

        // Decode URL-encoded host
        $host = urldecode($host);
        $hostLower = strtolower($host);
        $result['host'] = $hostLower;

        // Check if host is in allowed domains (whitelist bypass)
        if ($this->isAllowedDomain($hostLower)) {
            $result['valid'] = true;
            $result['url'] = $this->normalizeUrl($parsed);
            return $result;
        }

        // Check domain blocking
        $domainResult = $this->validateDomain($hostLower);
        if ($domainResult !== null) {
            $result['error'] = $domainResult['error'];
            $result['blocked'] = true;
            $result['blockReason'] = $domainResult['blockReason'];
            $this->logBlockedRequest($url, 'domain_blocked', $hostLower);
            return $result;
        }

        // Check for dangerous IP notations (decimal, hex, octal)
        $notationResult = $this->checkDangerousNotation($host);
        if ($notationResult !== null) {
            $result['error'] = $notationResult['error'];
            $result['blocked'] = true;
            $result['blockReason'] = $notationResult['blockReason'];
            $this->logBlockedRequest($url, 'dangerous_notation', $host);
            return $result;
        }

        // Resolve DNS and check IP
        $ip = $this->resolveDns($host);
        $result['ip'] = $ip;

        if ($ip !== null) {
            $ipResult = $this->validateIp($ip);
            if ($ipResult !== null) {
                $result['error'] = $ipResult['error'];
                $result['blocked'] = true;
                $result['blockReason'] = $ipResult['blockReason'];
                $this->logBlockedRequest($url, 'ip_blocked', $ip);
                return $result;
            }
        }

        // URL is valid
        $result['valid'] = true;
        $result['url'] = $this->normalizeUrl($parsed);

        return $result;
    }

    /**
     * Validate a redirect URL.
     *
     * @param string|null $url The redirect target URL
     * @return array ValidationResult
     */
    public function validateRedirect(?string $url): array
    {
        $result = $this->validate($url);

        if (!$result['valid']) {
            Log::warning('Redirect blocked by SSRF protection', [
                'url' => $url,
                'reason' => $result['blockReason'] ?? $result['error'],
            ]);
        }

        return $result;
    }

    /**
     * Check if URL-encoded IP should be blocked.
     */
    public function checkUrlEncoding(string $host): ?array
    {
        // URL decode and check again
        $decoded = urldecode($host);
        if ($decoded !== $host) {
            // Recursively check the decoded host
            if ($this->isIpAddress($decoded)) {
                $ipResult = $this->validateIp($decoded);
                if ($ipResult !== null) {
                    return $ipResult;
                }
            }
        }

        return null;
    }

    // =========================================================================
    // Protocol Validation
    // =========================================================================

    /**
     * Validate URL protocol.
     */
    private function validateProtocol(array $parsed): ?array
    {
        $scheme = strtolower($parsed['scheme'] ?? '');

        if (empty($scheme)) {
            return [
                'error' => 'Protocol not allowed',
                'blocked' => false,
            ];
        }

        $allowedProtocols = $this->config['allowed_protocols'] ?? self::DEFAULT_ALLOWED_PROTOCOLS;

        if (!in_array($scheme, $allowedProtocols, true)) {
            return [
                'error' => 'Protocol not allowed',
                'blocked' => false,
            ];
        }

        return null;
    }

    // =========================================================================
    // Domain Validation
    // =========================================================================

    /**
     * Validate domain against blocklist.
     */
    private function validateDomain(string $host): ?array
    {
        // Check metadata hosts
        if (in_array($host, self::METADATA_HOSTS, true)) {
            return [
                'error' => 'Domain blocked',
                'blockReason' => 'Domain blocked',
            ];
        }

        // Check blocked domain patterns
        $blockedDomains = $this->config['blocked_domains'] ?? self::DEFAULT_BLOCKED_DOMAINS;

        foreach ($blockedDomains as $pattern) {
            if ($this->matchDomainPattern($host, $pattern)) {
                return [
                    'error' => 'Domain blocked',
                    'blockReason' => 'Domain blocked',
                ];
            }
        }

        return null;
    }

    /**
     * Check if host matches a domain pattern.
     */
    private function matchDomainPattern(string $host, string $pattern): bool
    {
        // Handle wildcard patterns
        if (str_starts_with($pattern, '*.')) {
            $suffix = substr($pattern, 2);
            return $host === $suffix || str_ends_with($host, '.' . $suffix);
        }

        // Handle wildcard in middle (e.g., *.internal.*)
        if (str_contains($pattern, '*')) {
            $regex = '/^' . str_replace('\*', '[^.]+', preg_quote($pattern, '/')) . '$/i';
            return preg_match($regex, $host) === 1;
        }

        return $host === $pattern;
    }

    /**
     * Check if domain is in allowed list.
     */
    private function isAllowedDomain(string $host): bool
    {
        $allowedDomains = $this->config['allowed_domains'] ?? [];

        if (empty($allowedDomains)) {
            return false;
        }

        foreach ($allowedDomains as $pattern) {
            if ($this->matchDomainPattern($host, $pattern)) {
                return true;
            }
        }

        return false;
    }

    // =========================================================================
    // IP Address Validation
    // =========================================================================

    /**
     * Validate IP address against blocklist.
     */
    private function validateIp(string $ip): ?array
    {
        // Handle IPv6
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return $this->validateIpv6($ip);
        }

        // Handle IPv4
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return $this->validateIpv4($ip);
        }

        return null;
    }

    /**
     * Validate IPv4 address.
     */
    private function validateIpv4(string $ip): ?array
    {
        $ipLong = ip2long($ip);
        if ($ipLong === false) {
            return null;
        }

        // Check localhost range (127.0.0.0/8)
        if ($this->ipInRange($ipLong, '127.0.0.0', '127.255.255.255')) {
            return [
                'error' => 'IP address blocked: localhost',
                'blockReason' => 'IP address blocked: localhost',
            ];
        }

        // Check private networks
        if ($this->ipInRange($ipLong, '10.0.0.0', '10.255.255.255')) {
            return [
                'error' => 'IP address blocked: private network',
                'blockReason' => 'IP address blocked: private network',
            ];
        }

        if ($this->ipInRange($ipLong, '172.16.0.0', '172.31.255.255')) {
            return [
                'error' => 'IP address blocked: private network',
                'blockReason' => 'IP address blocked: private network',
            ];
        }

        if ($this->ipInRange($ipLong, '192.168.0.0', '192.168.255.255')) {
            return [
                'error' => 'IP address blocked: private network',
                'blockReason' => 'IP address blocked: private network',
            ];
        }

        // Check link-local / metadata endpoint
        if ($this->ipInRange($ipLong, '169.254.0.0', '169.254.255.255')) {
            // Specific check for cloud metadata
            if ($ip === '169.254.169.254') {
                return [
                    'error' => 'IP address blocked: metadata endpoint',
                    'blockReason' => 'IP address blocked: metadata endpoint',
                ];
            }
            return [
                'error' => 'IP address blocked: link-local',
                'blockReason' => 'IP address blocked: link-local',
            ];
        }

        // Check current network (0.0.0.0/8)
        if ($this->ipInRange($ipLong, '0.0.0.0', '0.255.255.255')) {
            return [
                'error' => 'IP address blocked: unspecified',
                'blockReason' => 'IP address blocked: unspecified',
            ];
        }

        // Check multicast (224.0.0.0/4)
        if ($this->ipInRange($ipLong, '224.0.0.0', '239.255.255.255')) {
            return [
                'error' => 'IP address blocked: multicast',
                'blockReason' => 'IP address blocked: multicast',
            ];
        }

        // Check reserved (240.0.0.0/4)
        if ($this->ipInRange($ipLong, '240.0.0.0', '255.255.255.254')) {
            return [
                'error' => 'IP address blocked: reserved',
                'blockReason' => 'IP address blocked: reserved',
            ];
        }

        // Check broadcast
        if ($ip === '255.255.255.255') {
            return [
                'error' => 'IP address blocked: broadcast',
                'blockReason' => 'IP address blocked: broadcast',
            ];
        }

        return null;
    }

    /**
     * Validate IPv6 address.
     */
    private function validateIpv6(string $ip): ?array
    {
        // Expand IPv6 to full notation
        $expanded = inet_pton($ip);
        if ($expanded === false) {
            return null;
        }

        $hex = bin2hex($expanded);

        // ::1 - Loopback
        if ($ip === '::1' || $hex === '00000000000000000000000000000001') {
            return [
                'error' => 'IP address blocked: localhost',
                'blockReason' => 'IP address blocked: localhost',
            ];
        }

        // :: - Unspecified
        if ($ip === '::' || $hex === '00000000000000000000000000000000') {
            return [
                'error' => 'IP address blocked: unspecified',
                'blockReason' => 'IP address blocked: unspecified',
            ];
        }

        // fe80::/10 - Link-local
        if (preg_match('/^fe[8-9][0-9a-f]/i', $hex) || preg_match('/^fea[0-9a-f]/i', $hex) || preg_match('/^feb[0-9a-f]/i', $hex)) {
            return [
                'error' => 'IP address blocked: link-local',
                'blockReason' => 'IP address blocked: link-local',
            ];
        }

        // fc00::/7 - Unique local
        if (preg_match('/^f[cd][0-9a-f]/i', $hex)) {
            return [
                'error' => 'IP address blocked: unique local',
                'blockReason' => 'IP address blocked: unique local',
            ];
        }

        // ff00::/8 - Multicast
        if (str_starts_with($hex, 'ff')) {
            return [
                'error' => 'IP address blocked: multicast',
                'blockReason' => 'IP address blocked: multicast',
            ];
        }

        // IPv4-mapped IPv6 addresses (::ffff:x.x.x.x)
        // Handle both compressed (::ffff:127.0.0.1) and full notation
        if (preg_match('/^::ffff:(\d+\.\d+\.\d+\.\d+)$/i', $ip, $matches)) {
            return $this->validateIpv4($matches[1]);
        }
        
        // Also check hex representation for IPv4-mapped
        if (str_starts_with($hex, '00000000000000000000ffff')) {
            $ipv4hex = substr($hex, -8);
            $ipv4 = implode('.', [
                hexdec(substr($ipv4hex, 0, 2)),
                hexdec(substr($ipv4hex, 2, 2)),
                hexdec(substr($ipv4hex, 4, 2)),
                hexdec(substr($ipv4hex, 6, 2)),
            ]);
            return $this->validateIpv4($ipv4);
        }

        // IPv4-compatible IPv6 addresses (::x.x.x.x)
        if (str_starts_with($hex, '000000000000000000000000') && substr($hex, -8) !== '00000000') {
            $ipv4hex = substr($hex, -8);
            $ipv4 = implode('.', [
                hexdec(substr($ipv4hex, 0, 2)),
                hexdec(substr($ipv4hex, 2, 2)),
                hexdec(substr($ipv4hex, 4, 2)),
                hexdec(substr($ipv4hex, 6, 2)),
            ]);
            return $this->validateIpv4($ipv4);
        }

        return null;
    }

    /**
     * Check if IP is in range.
     */
    private function ipInRange(int $ipLong, string $start, string $end): bool
    {
        $startLong = ip2long($start);
        $endLong = ip2long($end);

        return $ipLong >= $startLong && $ipLong <= $endLong;
    }

    // =========================================================================
    // Dangerous IP Notation Detection
    // =========================================================================

    /**
     * Check for dangerous IP notations (decimal, hex, octal).
     */
    private function checkDangerousNotation(string $host): ?array
    {
        // Decimal notation (e.g., 2130706433 = 127.0.0.1)
        if (preg_match('/^\d+$/', $host)) {
            $ip = long2ip((int)$host);
            if ($ip !== false) {
                $ipResult = $this->validateIpv4($ip);
                if ($ipResult !== null) {
                    return $ipResult;
                }
            }
        }

        // Hex notation (e.g., 0x7f000001 = 127.0.0.1)
        if (preg_match('/^0x[0-9a-f]+$/i', $host)) {
            $ip = long2ip(hexdec(substr($host, 2)));
            if ($ip !== false) {
                $ipResult = $this->validateIpv4($ip);
                if ($ipResult !== null) {
                    return $ipResult;
                }
            }
        }

        // Octal notation (e.g., 0177.0.0.1 = 127.0.0.1)
        if (preg_match('/^0[0-7]+(\.[0-7]+)*$/', $host)) {
            $ip = $this->parseOctalIp($host);
            if ($ip !== null) {
                $ipResult = $this->validateIpv4($ip);
                if ($ipResult !== null) {
                    return $ipResult;
                }
            }
        }

        // Mixed notation (e.g., 0x7f.0.0.1)
        if (preg_match('/^(0x[0-9a-f]+|0[0-7]*|\d+)(\.(0x[0-9a-f]+|0[0-7]*|\d+)){3}$/i', $host)) {
            $ip = $this->parseMixedNotation($host);
            if ($ip !== null) {
                $ipResult = $this->validateIpv4($ip);
                if ($ipResult !== null) {
                    return $ipResult;
                }
            }
        }

        return null;
    }

    /**
     * Parse octal IP notation.
     */
    private function parseOctalIp(string $host): ?string
    {
        $parts = explode('.', $host);
        if (count($parts) !== 4) {
            return null;
        }

        $decimalParts = [];
        foreach ($parts as $part) {
            $decimal = octdec($part);
            if ($decimal < 0 || $decimal > 255) {
                return null;
            }
            $decimalParts[] = $decimal;
        }

        return implode('.', $decimalParts);
    }

    /**
     * Parse mixed IP notation.
     */
    private function parseMixedNotation(string $host): ?string
    {
        $parts = explode('.', $host);
        if (count($parts) !== 4) {
            return null;
        }

        $decimalParts = [];
        foreach ($parts as $part) {
            if (preg_match('/^0x([0-9a-f]+)$/i', $part, $matches)) {
                $decimal = hexdec($matches[1]);
            } elseif (preg_match('/^0([0-7]*)$/', $part, $matches)) {
                $decimal = octdec($matches[1] ?? '0');
            } elseif (is_numeric($part)) {
                $decimal = (int)$part;
            } else {
                return null;
            }

            if ($decimal < 0 || $decimal > 255) {
                return null;
            }
            $decimalParts[] = $decimal;
        }

        return implode('.', $decimalParts);
    }

    // =========================================================================
    // DNS Resolution
    // =========================================================================

    /**
     * Resolve hostname to IP address.
     */
    private function resolveDns(string $host): ?string
    {
        // Handle IPv6 in brackets
        if (preg_match('/^\[([a-f0-9:]+)\]$/i', $host, $matches)) {
            return $matches[1];
        }

        // Check if already an IP address
        if (filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) ||
            filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return $host;
        }

        // Check cache
        $cacheKey = 'dns_resolution_' . md5($host);
        $cacheTtl = $this->config['dns_cache_ttl'] ?? 60;

        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        // Perform DNS lookup
        $records = @dns_get_record($host, DNS_A);
        if ($records === false || empty($records)) {
            // Try AAAA record for IPv6
            $records = @dns_get_record($host, DNS_AAAA);
            if ($records === false || empty($records)) {
                return null;
            }
            $ip = $records[0]['ipv6'] ?? null;
        } else {
            $ip = $records[0]['ip'] ?? null;
        }

        if ($ip !== null) {
            Cache::put($cacheKey, $ip, $cacheTtl);
        }

        return $ip;
    }

    // =========================================================================
    // URL Normalization
    // =========================================================================

    /**
     * Normalize a parsed URL.
     */
    private function normalizeUrl(array $parsed): string
    {
        $scheme = strtolower($parsed['scheme'] ?? 'https');
        $host = strtolower($parsed['host'] ?? '');

        // Handle port
        $port = null;
        if (isset($parsed['port'])) {
            // Remove default ports
            if (!(($scheme === 'http' && $parsed['port'] === 80) ||
                  ($scheme === 'https' && $parsed['port'] === 443))) {
                $port = $parsed['port'];
            }
        }

        // Only add trailing slash if path is empty
        $path = $parsed['path'] ?? '';
        if (empty($path)) {
            $path = '/';
        }
        $query = isset($parsed['query']) ? '?' . $parsed['query'] : '';
        $fragment = isset($parsed['fragment']) ? '#' . $parsed['fragment'] : '';

        // Build URL
        $url = "{$scheme}://{$host}";

        if ($port !== null) {
            $url .= ":{$port}";
        }

        $url .= $path . $query . $fragment;

        return $url;
    }

    // =========================================================================
    // Helper Methods
    // =========================================================================

    /**
     * Check if string is an IP address.
     */
    private function isIpAddress(string $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false ||
               filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false;
    }

    /**
     * Load configuration from config/crawler.php.
     */
    private function loadConfig(): void
    {
        $this->config = [
            'allowed_protocols' => config('crawler.allowed_protocols', self::DEFAULT_ALLOWED_PROTOCOLS),
            'blocked_ips' => config('crawler.blocked_ips', []),
            'blocked_domains' => config('crawler.blocked_domains', self::DEFAULT_BLOCKED_DOMAINS),
            'allowed_domains' => config('crawler.allowed_domains', []),
            'max_redirects' => config('crawler.max_redirects', 5),
            'dns_cache_ttl' => config('crawler.dns_cache_ttl', 60),
        ];
    }

    /**
     * Log a blocked request for security monitoring.
     */
    private function logBlockedRequest(string $url, string $reason, ?string $detail = null): void
    {
        Log::channel('security')->warning('SSRF request blocked', [
            'url' => $url,
            'reason' => $reason,
            'detail' => $detail,
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * Get blocked IPv4 ranges for testing.
     */
    public static function getBlockedIpv4Ranges(): array
    {
        return self::BLOCKED_IPV4_RANGES;
    }

    /**
     * Get blocked IPv6 ranges for testing.
     */
    public static function getBlockedIpv6Ranges(): array
    {
        return self::BLOCKED_IPV6_RANGES;
    }

    /**
     * Get default blocked domains for testing.
     */
    public static function getDefaultBlockedDomains(): array
    {
        return self::DEFAULT_BLOCKED_DOMAINS;
    }
}
