<?php

namespace App\Services;

use App\Exceptions\DomainValidationException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Domain Validation Service for SPEC-003-3
 * 
 * Validates competitor domains before adding them to the system.
 * Provides SSRF protection by blocking internal IPs and sensitive patterns.
 */
class DomainValidator
{
    /**
     * Rate limit: max requests per minute per user
     */
    protected int $rateLimit = 10;

    /**
     * HTTP timeout in seconds for accessibility check
     */
    protected int $timeout = 10;

    /**
     * Maximum redirects to follow
     */
    protected int $maxRedirects = 5;

    /**
     * Blocked internal domain patterns
     */
    protected array $blockedPatterns = [
        'localhost',
        'local',
        'internal',
        'intranet',
    ];

    /**
     * Blocked TLD patterns (ends with)
     */
    protected array $blockedTlds = [
        '.local',
        '.internal',
        '.localhost',
    ];

    /**
     * Main validation method
     * Performs full validation: format, SSRF, DNS, and accessibility.
     */
    public function validate(string $domain): array
    {
        // Step 1: Format validation (includes SSRF keyword checks)
        $formatResult = $this->validateFormat($domain);
        if (!$formatResult['valid']) {
            return $formatResult;
        }

        $normalizedDomain = $formatResult['domain'];

        // Step 2: Resolve domain to IP
        $ip = $this->resolveDomain($normalizedDomain);
        if ($ip === null) {
            return [
                'valid' => false,
                'domain' => $normalizedDomain,
                'ip' => null,
                'message' => 'Could not resolve domain to IP address',
                'code' => 'DNS_NOT_FOUND',
            ];
        }

        // Step 3: SSRF protection - validate the resolved IP
        $ipResult = $this->validateIp($ip);
        if (!$ipResult['valid']) {
            return [
                'valid' => false,
                'domain' => $normalizedDomain,
                'ip' => $ip,
                'message' => $ipResult['message'],
                'code' => $ipResult['code'],
            ];
        }

        // Step 4: DNS record check
        $dnsResult = $this->validateDns($normalizedDomain);
        if (!$dnsResult['valid']) {
            return [
                'valid' => false,
                'domain' => $normalizedDomain,
                'ip' => $ip,
                'message' => $dnsResult['message'],
                'code' => $dnsResult['code'],
            ];
        }

        // Step 5: Accessibility check
        $accessResult = $this->checkAccessibility($normalizedDomain);
        if (!$accessResult['valid']) {
            return [
                'valid' => false,
                'domain' => $normalizedDomain,
                'ip' => $ip,
                'message' => $accessResult['message'],
                'code' => $accessResult['code'],
            ];
        }

        return [
            'valid' => true,
            'domain' => $normalizedDomain,
            'ip' => $ip,
            'message' => null,
            'code' => null,
        ];
    }

    /**
     * Validate domain format
     * Also checks for SSRF via internal keywords and patterns.
     */
    public function validateFormat(?string $domain): array
    {
        // Handle null or empty
        if ($domain === null || trim($domain) === '') {
            return [
                'valid' => false,
                'domain' => $domain,
                'message' => 'Domain cannot be empty',
                'code' => 'INVALID_FORMAT',
            ];
        }

        $domain = trim($domain);

        // URL decode first to catch encoded attacks
        $decodedDomain = urldecode($domain);

        // Check for protocol prefix
        if (preg_match('/^(https?|ftp):\/\//i', $domain) || Str::startsWith($domain, '//')) {
            return [
                'valid' => false,
                'domain' => $domain,
                'message' => 'Domain should not include protocol prefix',
                'code' => 'INVALID_FORMAT',
            ];
        }

        // Check for path or query string
        if (str_contains($domain, '/') || str_contains($domain, '?') || str_contains($domain, '#')) {
            return [
                'valid' => false,
                'domain' => $domain,
                'message' => 'Domain should not include path or query string',
                'code' => 'INVALID_FORMAT',
            ];
        }

        // Check for port number
        if (preg_match('/:\d+$/', $domain)) {
            return [
                'valid' => false,
                'domain' => $domain,
                'message' => 'Domain should not include port number',
                'code' => 'INVALID_FORMAT',
            ];
        }

        // Check for SSRF via blocked keywords (case insensitive)
        $lowerDomain = strtolower($domain);
        $lowerDecoded = strtolower($decodedDomain);
        
        foreach ($this->blockedPatterns as $pattern) {
            if ($lowerDomain === $pattern || $lowerDecoded === $pattern) {
                return [
                    'valid' => false,
                    'domain' => $domain,
                    'message' => 'SSRF attempt detected: internal domain keyword',
                    'code' => 'SSRF_BLOCKED',
                ];
            }
        }

        // Check for blocked TLDs
        foreach ($this->blockedTlds as $tld) {
            if (Str::endsWith($lowerDomain, $tld) || Str::endsWith($lowerDecoded, $tld)) {
                return [
                    'valid' => false,
                    'domain' => $domain,
                    'message' => 'SSRF attempt detected: internal TLD pattern',
                    'code' => 'SSRF_BLOCKED',
                ];
            }
        }

        // Check for internal domain patterns anywhere in the domain (e.g., *.internal.example.com)
        foreach ($this->blockedPatterns as $pattern) {
            if (str_contains($lowerDomain, '.' . $pattern . '.') || 
                str_contains($lowerDecoded, '.' . $pattern . '.')) {
                return [
                    'valid' => false,
                    'domain' => $domain,
                    'message' => 'SSRF attempt detected: internal domain pattern',
                    'code' => 'SSRF_BLOCKED',
                ];
            }
        }

        // Check if input looks like an IP address (potential SSRF)
        if ($this->looksLikeIpAddress($decodedDomain)) {
            // Validate the IP directly
            $ipResult = $this->validateIp($decodedDomain);
            if (!$ipResult['valid']) {
                return [
                    'valid' => false,
                    'domain' => $domain,
                    'message' => $ipResult['message'],
                    'code' => $ipResult['code'],
                ];
            }
            // If it's a valid public IP, allow it
            return [
                'valid' => true,
                'domain' => $decodedDomain,
                'message' => null,
                'code' => null,
            ];
        }

        // Check for decimal-encoded IP (e.g., 2130706433 = 127.0.0.1)
        if (preg_match('/^\d+$/', $decodedDomain)) {
            $ip = $this->decimalToIp($decodedDomain);
            if ($ip !== null) {
                return [
                    'valid' => false,
                    'domain' => $domain,
                    'message' => 'SSRF attempt detected: decimal-encoded IP',
                    'code' => 'SSRF_BLOCKED',
                ];
            }
        }

        // Check for hex-encoded IP (e.g., 0x7f000001 = 127.0.0.1)
        if (preg_match('/^0x[0-9a-f]+$/i', $decodedDomain)) {
            return [
                'valid' => false,
                'domain' => $domain,
                'message' => 'SSRF attempt detected: hex-encoded IP',
                'code' => 'SSRF_BLOCKED',
            ];
        }

        // Check for octal IP encoding (e.g., 0177.0.0.1 = 127.0.0.1)
        if (preg_match('/^0\d*\.\d+\.\d+\.\d+/', $decodedDomain)) {
            return [
                'valid' => false,
                'domain' => $domain,
                'message' => 'SSRF attempt detected: octal-encoded IP',
                'code' => 'SSRF_BLOCKED',
            ];
        }

        // Check for mixed hex notation (e.g., 0x7f.0x00.0x00.0x01)
        if (preg_match('/0x[0-9a-f]+\./i', $decodedDomain)) {
            return [
                'valid' => false,
                'domain' => $domain,
                'message' => 'SSRF attempt detected: hex-encoded IP',
                'code' => 'SSRF_BLOCKED',
            ];
        }

        // Normalize to lowercase
        $normalizedDomain = strtolower($domain);

        // Remove trailing dot if present
        $normalizedDomain = rtrim($normalizedDomain, '.');

        // Validate domain format using regex
        // Based on RFC 1034/1035 and RFC 1123
        // - Total length: max 253 characters (without trailing dot)
        // - Each label: max 63 characters
        // - Labels can contain: a-z, 0-9, hyphen (not at start or end)
        // - TLD must not start with a number
        
        if (strlen($normalizedDomain) > 253) {
            return [
                'valid' => false,
                'domain' => $domain,
                'message' => 'Domain exceeds maximum length of 253 characters',
                'code' => 'INVALID_FORMAT',
            ];
        }

        // Domain regex pattern
        $domainPattern = '/^(?!(?:https?:\/\/|ftp:\/\/))[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(?:\.[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)*\.[a-zA-Z]{2,}$/';

        // Allow punycode domains (xn--)
        $punycodePattern = '/^xn--[a-zA-Z0-9-]+(?:\.[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)*\.[a-zA-Z]{2,}$/i';

        // Allow unicode domains - convert to punycode for validation
        $hasUnicode = preg_match('/[^\x00-\x7F]/', $normalizedDomain);
        if ($hasUnicode) {
            // Convert to IDN/punycode
            $punycode = $this->convertToPunycode($normalizedDomain);
            if ($punycode === null) {
                return [
                    'valid' => false,
                    'domain' => $domain,
                    'message' => 'Invalid internationalized domain name',
                    'code' => 'INVALID_FORMAT',
                ];
            }
            $normalizedDomain = $punycode;
        }

        // Check each label length
        $labels = explode('.', $normalizedDomain);
        foreach ($labels as $label) {
            if (strlen($label) > 63) {
                return [
                    'valid' => false,
                    'domain' => $domain,
                    'message' => 'Domain label exceeds maximum length of 63 characters',
                    'code' => 'INVALID_FORMAT',
                ];
            }
        }

        // Final format check
        if (!preg_match($domainPattern, $normalizedDomain) && !preg_match($punycodePattern, $normalizedDomain)) {
            return [
                'valid' => false,
                'domain' => $domain,
                'message' => 'Invalid domain format',
                'code' => 'INVALID_FORMAT',
            ];
        }

        return [
            'valid' => true,
            'domain' => $normalizedDomain,
            'message' => null,
            'code' => null,
        ];
    }

    /**
     * Validate DNS record exists
     */
    public function validateDns(string $domain): array
    {
        if ($this->checkDnsRecord($domain)) {
            return [
                'valid' => true,
                'domain' => $domain,
                'message' => null,
                'code' => null,
            ];
        }

        return [
            'valid' => false,
            'domain' => $domain,
            'message' => 'No DNS record found for domain',
            'code' => 'DNS_NOT_FOUND',
        ];
    }

    /**
     * Validate IP address for SSRF protection
     */
    public function validateIp(string $ip): array
    {
        // Normalize IPv6 addresses
        $normalizedIp = $this->normalizeIp($ip);

        // Check if it's a valid IP
        if (!filter_var($normalizedIp, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6)) {
            return [
                'valid' => false,
                'ip' => $ip,
                'message' => 'Invalid IP address format',
                'code' => 'INVALID_FORMAT',
            ];
        }

        // Check for IPv6 mapped IPv4 addresses (::ffff:127.0.0.1)
        $mappedIpv4 = $this->extractMappedIpv4($normalizedIp);
        if ($mappedIpv4 !== null) {
            return $this->validateIp($mappedIpv4);
        }

        // IPv4 SSRF checks
        if (filter_var($normalizedIp, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return $this->validateIpv4($normalizedIp);
        }

        // IPv6 SSRF checks
        return $this->validateIpv6($normalizedIp);
    }

    /**
     * Check domain accessibility via HTTP
     */
    public function checkAccessibility(string $domain): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->maxRedirects($this->maxRedirects)
                ->get("https://{$domain}");

            if ($response->successful()) {
                return [
                    'valid' => true,
                    'domain' => $domain,
                    'message' => null,
                    'code' => null,
                ];
            }

            // Try HTTP if HTTPS fails
            if ($response->status() >= 400 && $response->status() < 500) {
                $httpResponse = Http::timeout($this->timeout)
                    ->maxRedirects($this->maxRedirects)
                    ->get("http://{$domain}");

                if ($httpResponse->successful()) {
                    return [
                        'valid' => true,
                        'domain' => $domain,
                        'message' => null,
                        'code' => null,
                    ];
                }
            }

            return [
                'valid' => false,
                'domain' => $domain,
                'message' => "Domain returned HTTP {$response->status()}",
                'code' => 'CONNECTION_ERROR',
            ];
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            return [
                'valid' => false,
                'domain' => $domain,
                'message' => 'Connection timed out',
                'code' => 'CONNECTION_TIMEOUT',
            ];
        } catch (\Exception $e) {
            return [
                'valid' => false,
                'domain' => $domain,
                'message' => 'Failed to connect to domain',
                'code' => 'CONNECTION_ERROR',
            ];
        }
    }

    /**
     * Validate with rate limiting
     */
    public function validateWithRateLimit(string $domain, string $userId): array
    {
        $cacheKey = "rate_limit:{$userId}";
        $count = Cache::get($cacheKey, 0);

        if ($count >= $this->rateLimit) {
            return [
                'valid' => false,
                'domain' => $domain,
                'ip' => null,
                'message' => 'Rate limit exceeded. Please try again later.',
                'code' => 'RATE_LIMITED',
            ];
        }

        // Increment counter
        Cache::put($cacheKey, $count + 1, 60); // 60 seconds = 1 minute window

        return $this->validate($domain);
    }

    /**
     * Check DNS record exists (A or CNAME)
     */
    protected function checkDnsRecord(string $domain): bool
    {
        return checkdnsrr($domain, 'A') || checkdnsrr($domain, 'CNAME') || checkdnsrr($domain, 'AAAA');
    }

    /**
     * Resolve domain to IP address
     */
    protected function resolveDomain(string $domain): ?string
    {
        // Check if domain is already an IP
        if (filter_var($domain, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6)) {
            return $domain;
        }

        $records = dns_get_record($domain, DNS_A | DNS_AAAA);
        
        if (!empty($records)) {
            foreach ($records as $record) {
                if (isset($record['ip'])) {
                    return $record['ip'];
                }
                if (isset($record['ipv6'])) {
                    return $record['ipv6'];
                }
            }
        }

        return null;
    }

    /**
     * Validate IPv4 address for SSRF
     */
    protected function validateIpv4(string $ip): array
    {
        $longIp = ip2long($ip);

        if ($longIp === false) {
            return [
                'valid' => false,
                'ip' => $ip,
                'message' => 'Invalid IPv4 address',
                'code' => 'INVALID_FORMAT',
            ];
        }

        // Block loopback: 127.0.0.0/8
        if (($longIp & 0xFF000000) === 0x7F000000) {
            return [
                'valid' => false,
                'ip' => $ip,
                'message' => 'SSRF attempt detected: loopback IP address',
                'code' => 'SSRF_BLOCKED',
            ];
        }

        // Block private: 10.0.0.0/8
        if (($longIp & 0xFF000000) === 0x0A000000) {
            return [
                'valid' => false,
                'ip' => $ip,
                'message' => 'SSRF attempt detected: private IP address (10.x.x.x)',
                'code' => 'SSRF_BLOCKED',
            ];
        }

        // Block private: 172.16.0.0/12 (172.16.0.0 - 172.31.255.255)
        if (($longIp & 0xFFF00000) === 0xAC100000) {
            return [
                'valid' => false,
                'ip' => $ip,
                'message' => 'SSRF attempt detected: private IP address (172.16-31.x.x)',
                'code' => 'SSRF_BLOCKED',
            ];
        }

        // Block private: 192.168.0.0/16
        if (($longIp & 0xFFFF0000) === 0xC0A80000) {
            return [
                'valid' => false,
                'ip' => $ip,
                'message' => 'SSRF attempt detected: private IP address (192.168.x.x)',
                'code' => 'SSRF_BLOCKED',
            ];
        }

        // Block link-local: 169.254.0.0/16
        if (($longIp & 0xFFFF0000) === 0xA9FE0000) {
            return [
                'valid' => false,
                'ip' => $ip,
                'message' => 'SSRF attempt detected: link-local IP address',
                'code' => 'SSRF_BLOCKED',
            ];
        }

        return [
            'valid' => true,
            'ip' => $ip,
            'message' => null,
            'code' => null,
        ];
    }

    /**
     * Validate IPv6 address for SSRF
     */
    protected function validateIpv6(string $ip): array
    {
        // Normalize the IPv6 address
        $packed = inet_pton($ip);
        if ($packed === false) {
            return [
                'valid' => false,
                'ip' => $ip,
                'message' => 'Invalid IPv6 address',
                'code' => 'INVALID_FORMAT',
            ];
        }

        $unpacked = unpack('H*', $packed);
        $hex = $unpacked[1];

        // Block loopback: ::1
        if ($ip === '::1' || $hex === '00000000000000000000000000000001') {
            return [
                'valid' => false,
                'ip' => $ip,
                'message' => 'SSRF attempt detected: IPv6 loopback address',
                'code' => 'SSRF_BLOCKED',
            ];
        }

        // Block unspecified: ::
        if ($ip === '::' || $hex === '00000000000000000000000000000000') {
            return [
                'valid' => false,
                'ip' => $ip,
                'message' => 'SSRF attempt detected: unspecified IPv6 address',
                'code' => 'SSRF_BLOCKED',
            ];
        }

        // Block link-local: fe80::/10
        if (Str::startsWith($hex, 'fe8') || Str::startsWith($hex, 'fe9') || 
            Str::startsWith($hex, 'fea') || Str::startsWith($hex, 'feb')) {
            return [
                'valid' => false,
                'ip' => $ip,
                'message' => 'SSRF attempt detected: IPv6 link-local address',
                'code' => 'SSRF_BLOCKED',
            ];
        }

        // Block unique local: fc00::/7 (fc00::/8 and fd00::/8)
        if (Str::startsWith($hex, 'fc') || Str::startsWith($hex, 'fd')) {
            return [
                'valid' => false,
                'ip' => $ip,
                'message' => 'SSRF attempt detected: IPv6 unique local address',
                'code' => 'SSRF_BLOCKED',
            ];
        }

        // Check for IPv6 mapped IPv4 (::ffff:x.x.x.x)
        $mappedIpv4 = $this->extractMappedIpv4($ip);
        if ($mappedIpv4 !== null) {
            return $this->validateIpv4($mappedIpv4);
        }

        return [
            'valid' => true,
            'ip' => $ip,
            'message' => null,
            'code' => null,
        ];
    }

    /**
     * Normalize IP address (handle short-hand IPv6)
     */
    protected function normalizeIp(string $ip): string
    {
        // Handle IPv6 addresses
        if (str_contains($ip, ':')) {
            $packed = @inet_pton($ip);
            if ($packed !== false) {
                return inet_ntop($packed);
            }
        }
        return $ip;
    }

    /**
     * Extract IPv4 from IPv6 mapped address (::ffff:x.x.x.x)
     */
    protected function extractMappedIpv4(string $ip): ?string
    {
        if (!str_contains($ip, ':')) {
            return null;
        }

        // Check for ::ffff:x.x.x.x format
        if (preg_match('/^::ffff:(\d+\.\d+\.\d+\.\d+)$/i', $ip, $matches)) {
            return $matches[1];
        }

        // Also check for full IPv6 mapped format
        $packed = @inet_pton($ip);
        if ($packed !== false && strlen($packed) === 16) {
            // Check if first 10 bytes are 0 and next 2 are 0xFFFF
            if (substr($packed, 0, 10) === "\0\0\0\0\0\0\0\0\0\0" && 
                substr($packed, 10, 2) === "\xff\xff") {
                return inet_ntop(substr($packed, 12, 4));
            }
        }

        return null;
    }

    /**
     * Convert decimal IP to dotted decimal
     */
    protected function decimalToIp(string $decimal): ?string
    {
        if (!is_numeric($decimal)) {
            return null;
        }

        $long = (int)$decimal;
        if ($long < 0 || $long > 4294967295) {
            return null;
        }

        return long2ip($long);
    }

    /**
     * Convert unicode domain to punycode
     */
    protected function convertToPunycode(string $domain): ?string
    {
        if (function_exists('idn_to_ascii')) {
            $result = @idn_to_ascii($domain, IDNA_NONTRANSITIONAL_TO_ASCII, INTL_IDNA_VARIANT_UTS46);
            return $result !== false ? $result : null;
        }

        // Fallback: just return null if idn functions not available
        return null;
    }

    /**
     * Check if input looks like an IP address (IPv4 or IPv6)
     */
    protected function looksLikeIpAddress(string $input): bool
    {
        // IPv4 pattern
        if (preg_match('/^(\d{1,3}\.){3}\d{1,3}$/', $input)) {
            return true;
        }

        // IPv6 patterns
        if (str_contains($input, ':')) {
            return true;
        }

        // IPv6 with brackets
        if (preg_match('/^\[.+\]$/', $input)) {
            return true;
        }

        return false;
    }
}
