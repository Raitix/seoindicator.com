<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Domain Validation Service
 * 
 * Validates competitor domains before adding them.
 * Includes SSRF protection and rate limiting.
 * 
 * @see SPEC-003-3
 */
class DomainValidator
{
    /**
     * Maximum number of validation requests per minute per user
     */
    protected int $rateLimit = 10;

    /**
     * Rate limit window in seconds
     */
    protected int $rateLimitWindow = 60;

    /**
     * HTTP timeout for accessibility check in seconds
     */
    protected int $httpTimeout = 10;

    /**
     * Maximum HTTP redirects to follow
     */
    protected int $maxRedirects = 5;

    /**
     * Blocked domain keywords for SSRF protection
     */
    protected array $blockedKeywords = [
        'localhost',
        'local',
        'internal',
        'intranet',
    ];

    /**
     * Validate a domain completely
     * 
     * @param string $domain Domain to validate
     * @return array Validation result with 'valid', 'domain', 'ip', 'message', 'code' keys
     */
    public function validate(string $domain): array
    {
        // Step 1: Validate format
        $formatResult = $this->validateFormat($domain);
        if (!$formatResult['valid']) {
            return [
                'valid' => false,
                'domain' => $domain,
                'ip' => null,
                'message' => $formatResult['message'],
                'code' => $formatResult['code'],
            ];
        }

        // Normalize domain
        $domain = $this->normalizeDomain($domain);

        // Step 2: Resolve domain to IP
        $ip = $this->resolveDomain($domain);
        
        if ($ip) {
            // Step 3: Validate IP for SSRF
            $ipResult = $this->validateIp($ip);
            if (!$ipResult['valid']) {
                return [
                    'valid' => false,
                    'domain' => $domain,
                    'ip' => $ip,
                    'message' => $ipResult['message'],
                    'code' => $ipResult['code'],
                ];
            }
        }

        // Step 4: Check DNS record
        $dnsResult = $this->validateDns($domain);
        if (!$dnsResult['valid']) {
            return [
                'valid' => false,
                'domain' => $domain,
                'ip' => $ip,
                'message' => $dnsResult['message'],
                'code' => $dnsResult['code'],
            ];
        }

        // Step 5: Check accessibility
        $accessResult = $this->checkAccessibility($domain);
        if (!$accessResult['valid']) {
            return [
                'valid' => false,
                'domain' => $domain,
                'ip' => $ip,
                'message' => $accessResult['message'],
                'code' => $accessResult['code'],
            ];
        }

        return [
            'valid' => true,
            'domain' => $domain,
            'ip' => $ip,
            'message' => 'Domain is valid and accessible',
            'code' => 'VALID',
        ];
    }

    /**
     * Validate domain format
     * 
     * @param string|null $domain Domain to validate
     * @return array Validation result
     */
    public function validateFormat(?string $domain): array
    {
        // Check for empty/null
        if (empty($domain) || trim($domain) === '') {
            return [
                'valid' => false,
                'message' => 'Domain cannot be empty',
                'code' => 'INVALID_FORMAT',
            ];
        }

        $domain = strtolower(trim($domain));

        // Check for protocol prefix - reject it
        if (preg_match('/^(https?:\/\/|ftp:\/\/|\/\/)/i', $domain)) {
            return [
                'valid' => false,
                'message' => 'Domain should not include protocol prefix',
                'code' => 'INVALID_FORMAT',
            ];
        }

        // Check for path/query/fragment - reject it
        if (preg_match('/[\/\?#]/', $domain)) {
            return [
                'valid' => false,
                'message' => 'Domain should not include path, query, or fragment',
                'code' => 'INVALID_FORMAT',
            ];
        }

        // Check for port - reject it
        if (preg_match('/:\d+$/', $domain)) {
            return [
                'valid' => false,
                'message' => 'Domain should not include port number',
                'code' => 'INVALID_FORMAT',
            ];
        }

        // Check for blocked keywords (SSRF protection)
        $domainLower = strtolower($domain);
        foreach ($this->blockedKeywords as $keyword) {
            // Check exact match, ends with .keyword, or contains .keyword. (subdomain)
            if ($domainLower === $keyword || 
                preg_match('/\.' . preg_quote($keyword, '/') . '$/i', $domain) ||
                preg_match('/\.' . preg_quote($keyword, '/') . '\./i', $domain)) {
                return [
                    'valid' => false,
                    'message' => 'Internal domains are not allowed',
                    'code' => 'SSRF_BLOCKED',
                ];
            }
        }

        // Check for decimal encoded IP (SSRF bypass)
        if (preg_match('/^\d{8,10}$/', $domain)) {
            $ip = long2ip((int)$domain);
            if ($ip && filter_var($ip, FILTER_VALIDATE_IP)) {
                return [
                    'valid' => false,
                    'message' => 'Decimal encoded IPs are not allowed',
                    'code' => 'SSRF_BLOCKED',
                ];
            }
        }

        // Check for hex encoded IP (SSRF bypass)
        if (preg_match('/^0x[0-9a-f]+$/i', $domain)) {
            return [
                'valid' => false,
                'message' => 'Hex encoded IPs are not allowed',
                'code' => 'SSRF_BLOCKED',
            ];
        }

        // Check for mixed hex encoded IP (SSRF bypass) - e.g., 0x7f.0x00.0x00.0x01
        if (preg_match('/^0x[0-9a-f]+(\.0x[0-9a-f]+)+$/i', $domain)) {
            return [
                'valid' => false,
                'message' => 'Hex encoded IPs are not allowed',
                'code' => 'SSRF_BLOCKED',
            ];
        }

        // Check for octal encoded IP (SSRF bypass)
        if (preg_match('/^0[0-7]+\./', $domain)) {
            return [
                'valid' => false,
                'message' => 'Octal encoded IPs are not allowed',
                'code' => 'SSRF_BLOCKED',
            ];
        }

        // Check for URL encoded content (SSRF bypass)
        if (preg_match('/%[0-9a-f]{2}/i', $domain)) {
            return [
                'valid' => false,
                'message' => 'URL encoded content is not allowed',
                'code' => 'SSRF_BLOCKED',
            ];
        }

        // Check for valid domain format
        // Standard domain regex: allows letters, numbers, hyphens, dots
        // Does not allow starting/ending with hyphen or dot
        $domainRegex = '/^(?!-)(?!.*-\.)(?!.*\.$)([a-z0-9-]+\.)*[a-z0-9-]+$/i';
        
        // Also allow punycode domains (xn--*)
        $punycodeRegex = '/^xn--[a-z0-9-]+(\.[a-z0-9-]+)*$/i';
        
        // Check if it's a valid IP address (for direct IP input)
        if (filter_var($domain, FILTER_VALIDATE_IP)) {
            // Direct IP - validate for SSRF
            return $this->validateIp($domain);
        }

        // Check standard domain format
        if (!preg_match($domainRegex, $domain) && !preg_match($punycodeRegex, $domain)) {
            return [
                'valid' => false,
                'message' => 'Invalid domain format',
                'code' => 'INVALID_FORMAT',
            ];
        }

        // Check minimum length (at least 2 chars for TLD-like)
        $parts = explode('.', $domain);
        if (count($parts) < 2 || strlen(end($parts)) < 2) {
            return [
                'valid' => false,
                'message' => 'Invalid domain format',
                'code' => 'INVALID_FORMAT',
            ];
        }

        // Check each label length (max 63 chars)
        foreach ($parts as $part) {
            if (strlen($part) > 63) {
                return [
                    'valid' => false,
                    'message' => 'Domain label exceeds maximum length',
                    'code' => 'INVALID_FORMAT',
                ];
            }
        }

        // Check total length (max 253 chars)
        if (strlen($domain) > 253) {
            return [
                'valid' => false,
                'message' => 'Domain exceeds maximum length',
                'code' => 'INVALID_FORMAT',
            ];
        }

        return [
            'valid' => true,
            'message' => 'Domain format is valid',
            'code' => 'VALID',
            'domain' => $domain,
        ];
    }

    /**
     * Validate DNS record exists
     * 
     * @param string $domain Domain to check
     * @return array Validation result
     */
    public function validateDns(string $domain): array
    {
        $domain = $this->normalizeDomain($domain);

        if ($this->checkDnsRecord($domain)) {
            return [
                'valid' => true,
                'message' => 'DNS record found',
                'code' => 'VALID',
            ];
        }

        return [
            'valid' => false,
            'message' => 'No DNS record found for domain',
            'code' => 'DNS_NOT_FOUND',
        ];
    }

    /**
     * Check DNS record (A or CNAME)
     * 
     * @param string $domain Domain to check
     * @return bool True if DNS record exists
     */
    protected function checkDnsRecord(string $domain): bool
    {
        // Check for A record
        if (checkdnsrr($domain, 'A')) {
            return true;
        }

        // Check for CNAME record
        if (checkdnsrr($domain, 'CNAME')) {
            return true;
        }

        // Check for AAAA record (IPv6)
        if (checkdnsrr($domain, 'AAAA')) {
            return true;
        }

        return false;
    }

    /**
     * Validate IP address for SSRF protection
     * 
     * @param string $ip IP address to validate
     * @return array Validation result
     */
    public function validateIp(string $ip): array
    {
        // Normalize IP
        $ip = trim($ip);

        // Check if it's a valid IP
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            return [
                'valid' => false,
                'message' => 'Invalid IP address format',
                'code' => 'INVALID_FORMAT',
            ];
        }

        // Check IPv4 private/reserved ranges
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            // Loopback (127.0.0.0/8)
            if ($this->ipInRange($ip, '127.0.0.0', '255.0.0.0')) {
                return [
                    'valid' => false,
                    'message' => 'Loopback IP addresses are not allowed',
                    'code' => 'SSRF_BLOCKED',
                ];
            }

            // Private 10.0.0.0/8
            if ($this->ipInRange($ip, '10.0.0.0', '255.0.0.0')) {
                return [
                    'valid' => false,
                    'message' => 'Private IP addresses are not allowed',
                    'code' => 'SSRF_BLOCKED',
                ];
            }

            // Private 172.16.0.0/12
            if ($this->ipInRange($ip, '172.16.0.0', '255.240.0.0')) {
                return [
                    'valid' => false,
                    'message' => 'Private IP addresses are not allowed',
                    'code' => 'SSRF_BLOCKED',
                ];
            }

            // Private 192.168.0.0/16
            if ($this->ipInRange($ip, '192.168.0.0', '255.255.0.0')) {
                return [
                    'valid' => false,
                    'message' => 'Private IP addresses are not allowed',
                    'code' => 'SSRF_BLOCKED',
                ];
            }

            // Link-local 169.254.0.0/16
            if ($this->ipInRange($ip, '169.254.0.0', '255.255.0.0')) {
                return [
                    'valid' => false,
                    'message' => 'Link-local IP addresses are not allowed',
                    'code' => 'SSRF_BLOCKED',
                ];
            }

            // Multicast 224.0.0.0/4
            if ($this->ipInRange($ip, '224.0.0.0', '240.0.0.0')) {
                return [
                    'valid' => false,
                    'message' => 'Multicast IP addresses are not allowed',
                    'code' => 'SSRF_BLOCKED',
                ];
            }

            // Reserved 240.0.0.0/4
            if ($this->ipInRange($ip, '240.0.0.0', '240.0.0.0')) {
                return [
                    'valid' => false,
                    'message' => 'Reserved IP addresses are not allowed',
                    'code' => 'SSRF_BLOCKED',
                ];
            }

            // 0.0.0.0/8 - Current network
            if ($this->ipInRange($ip, '0.0.0.0', '255.0.0.0')) {
                return [
                    'valid' => false,
                    'message' => 'Current network addresses are not allowed',
                    'code' => 'SSRF_BLOCKED',
                ];
            }
        }

        // Check IPv6 private/reserved ranges
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $ipExpanded = $this->expandIPv6($ip);

            // Loopback ::1
            if ($ipExpanded === '0000:0000:0000:0000:0000:0000:0000:0001') {
                return [
                    'valid' => false,
                    'message' => 'IPv6 loopback address is not allowed',
                    'code' => 'SSRF_BLOCKED',
                ];
            }

            // Unspecified ::
            if ($ipExpanded === '0000:0000:0000:0000:0000:0000:0000:0000') {
                return [
                    'valid' => false,
                    'message' => 'Unspecified IPv6 address is not allowed',
                    'code' => 'SSRF_BLOCKED',
                ];
            }

            // Link-local fe80::/10
            if (preg_match('/^fe[8-9a-f][0-9a-f]:/i', $ipExpanded)) {
                return [
                    'valid' => false,
                    'message' => 'IPv6 link-local addresses are not allowed',
                    'code' => 'SSRF_BLOCKED',
                ];
            }

            // Unique local fc00::/7 (fc00::/8 and fd00::/8)
            // First two hex groups must be fcxx or fdxx
            if (preg_match('/^f[cd][0-9a-f]{2}:/i', $ipExpanded)) {
                return [
                    'valid' => false,
                    'message' => 'IPv6 unique local addresses are not allowed',
                    'code' => 'SSRF_BLOCKED',
                ];
            }

            // IPv6 mapped IPv4 addresses (::ffff:192.168.0.1)
            if (preg_match('/^::ffff:(\d+\.\d+\.\d+\.\d+)$/i', $ip, $matches)) {
                return $this->validateIp($matches[1]);
            }

            // IPv4-compatible IPv6 addresses (::192.168.0.1)
            if (preg_match('/^::(\d+\.\d+\.\d+\.\d+)$/i', $ip, $matches)) {
                return $this->validateIp($matches[1]);
            }
        }

        return [
            'valid' => true,
            'message' => 'IP address is allowed',
            'code' => 'VALID',
        ];
    }

    /**
     * Check domain accessibility via HTTP
     * 
     * @param string $domain Domain to check
     * @return array Validation result
     */
    public function checkAccessibility(string $domain): array
    {
        $domain = $this->normalizeDomain($domain);

        try {
            $response = Http::timeout($this->httpTimeout)
                ->maxRedirects($this->maxRedirects)
                ->head("https://{$domain}");

            if ($response->successful()) {
                return [
                    'valid' => true,
                    'message' => 'Domain is accessible',
                    'code' => 'VALID',
                ];
            }

            // Try HTTP if HTTPS fails
            $response = Http::timeout($this->httpTimeout)
                ->maxRedirects($this->maxRedirects)
                ->head("http://{$domain}");

            if ($response->successful()) {
                return [
                    'valid' => true,
                    'message' => 'Domain is accessible',
                    'code' => 'VALID',
                ];
            }

            // Check for redirect (3xx is acceptable)
            if ($response->redirect()) {
                return [
                    'valid' => true,
                    'message' => 'Domain redirects',
                    'code' => 'VALID',
                ];
            }

            return [
                'valid' => false,
                'message' => 'Domain returned HTTP ' . $response->status(),
                'code' => 'CONNECTION_REFUSED',
            ];
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            return [
                'valid' => false,
                'message' => 'Connection failed: ' . $e->getMessage(),
                'code' => 'CONNECTION_TIMEOUT',
            ];
        } catch (\Exception $e) {
            return [
                'valid' => false,
                'message' => 'Accessibility check failed: ' . $e->getMessage(),
                'code' => 'CONNECTION_ERROR',
            ];
        }
    }

    /**
     * Validate domain with rate limiting
     * 
     * @param string $domain Domain to validate
     * @param string $userId User identifier for rate limiting
     * @return array Validation result
     */
    public function validateWithRateLimit(string $domain, string $userId): array
    {
        $cacheKey = "rate_limit:{$userId}";
        $currentCount = (int) Cache::get($cacheKey, 0);

        if ($currentCount >= $this->rateLimit) {
            return [
                'valid' => false,
                'domain' => $domain,
                'ip' => null,
                'message' => 'Rate limit exceeded. Please try again later.',
                'code' => 'RATE_LIMITED',
            ];
        }

        // Increment counter
        Cache::put($cacheKey, $currentCount + 1, $this->rateLimitWindow);

        return $this->validate($domain);
    }

    /**
     * Resolve domain to IP address
     * 
     * @param string $domain Domain to resolve
     * @return string|null IP address or null if resolution fails
     */
    protected function resolveDomain(string $domain): ?string
    {
        $domain = $this->normalizeDomain($domain);

        // Get A record (IPv4)
        $records = dns_get_record($domain, DNS_A);
        if (!empty($records) && isset($records[0]['ip'])) {
            return $records[0]['ip'];
        }

        // Get AAAA record (IPv6)
        $records = dns_get_record($domain, DNS_AAAA);
        if (!empty($records) && isset($records[0]['ipv6'])) {
            return $records[0]['ipv6'];
        }

        return null;
    }

    /**
     * Normalize domain for consistent processing
     * 
     * @param string $domain Domain to normalize
     * @return string Normalized domain
     */
    protected function normalizeDomain(string $domain): string
    {
        $domain = strtolower(trim($domain));
        
        // Remove trailing dot if present
        $domain = rtrim($domain, '.');
        
        return $domain;
    }

    /**
     * Check if IP is in a given network range
     * 
     * @param string $ip IP address to check
     * @param string $network Network address
     * @param string $mask Subnet mask
     * @return bool True if IP is in range
     */
    protected function ipInRange(string $ip, string $network, string $mask): bool
    {
        $ipLong = ip2long($ip);
        $networkLong = ip2long($network);
        $maskLong = ip2long($mask);

        if ($ipLong === false || $networkLong === false || $maskLong === false) {
            return false;
        }

        return ($ipLong & $maskLong) === ($networkLong & $maskLong);
    }

    /**
     * Expand IPv6 address to full notation
     * 
     * @param string $ip IPv6 address
     * @return string Expanded IPv6 address
     */
    protected function expandIPv6(string $ip): string
    {
        $ip = inet_pton($ip);
        if ($ip === false) {
            return $ip;
        }

        $hex = unpack('H*hex', $ip);
        return substr(preg_replace('/([a-f0-9]{4})/i', '$1:', $hex['hex']), 0, -1);
    }
}
