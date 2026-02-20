<?php

namespace App\Services;

use App\Exceptions\DomainValidationException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Domain Validator Service for SPEC-003-3.
 *
 * Validates competitor domains before adding them to the system.
 * Includes comprehensive SSRF protection to prevent security vulnerabilities.
 */
class DomainValidator
{
    /**
     * Maximum rate limit requests per minute per user.
     */
    protected int $rateLimit = 10;

    /**
     * Rate limit window in seconds (1 minute).
     */
    protected int $rateLimitWindow = 60;

    /**
     * HTTP timeout for accessibility checks (seconds).
     */
    protected int $httpTimeout = 10;

    /**
     * Maximum HTTP redirects to follow.
     */
    protected int $maxRedirects = 5;

    /**
     * Blocked domain keywords for SSRF protection.
     */
    protected array $blockedKeywords = [
        'localhost',
        'local',
        'internal',
        'intranet',
    ];

    /**
     * Blocked TLDs for SSRF protection.
     */
    protected array $blockedTlds = [
        'local',
        'internal',
        'localhost',
    ];

    /**
     * Validate a domain with all checks.
     *
     * @param string $domain
     * @param bool $skipAccessibility Skip HTTP accessibility check (useful for testing)
     * @return array
     */
    public function validate(string $domain, bool $skipAccessibility = false): array
    {
        // Normalize domain
        $domain = $this->normalizeDomain($domain);

        // Step 1: Validate format (includes SSRF keyword check)
        $formatResult = $this->validateFormat($domain);
        if (!$formatResult['valid']) {
            return $formatResult;
        }

        $domain = $formatResult['domain'] ?? $domain;

        // Step 2: Resolve domain to IP
        $ip = $this->resolveDomain($domain);
        if ($ip === null) {
            return [
                'valid' => false,
                'domain' => $domain,
                'message' => 'Domain does not resolve to an IP address',
                'code' => 'DNS_NOT_FOUND',
            ];
        }

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

        // Step 4: Check DNS record exists
        if (!$this->checkDnsRecord($domain)) {
            return [
                'valid' => false,
                'domain' => $domain,
                'ip' => $ip,
                'message' => 'Domain has no DNS records',
                'code' => 'DNS_NOT_FOUND',
            ];
        }

        // Step 5: Check accessibility (optional)
        if (!$skipAccessibility) {
            $accessibilityResult = $this->checkAccessibility($domain);
            if (!$accessibilityResult['valid']) {
                return [
                    'valid' => false,
                    'domain' => $domain,
                    'ip' => $ip,
                    'message' => $accessibilityResult['message'],
                    'code' => $accessibilityResult['code'],
                ];
            }
        }

        return [
            'valid' => true,
            'domain' => $domain,
            'ip' => $ip,
            'message' => 'Domain is valid and accessible',
        ];
    }

    /**
     * Validate domain format with SSRF protection.
     */
    public function validateFormat(?string $domain): array
    {
        // Check for null or empty
        if ($domain === null || trim($domain) === '') {
            return [
                'valid' => false,
                'domain' => $domain,
                'message' => 'Domain is required',
                'code' => 'INVALID_FORMAT',
            ];
        }

        // Normalize
        $domain = $this->normalizeDomain($domain);
        $domainLower = strtolower($domain);

        // Check for protocol prefix
        if (preg_match('/^(https?|ftp):\/\//i', $domain) || Str::startsWith($domain, '//')) {
            return [
                'valid' => false,
                'domain' => $domain,
                'message' => 'Domain should not include protocol prefix',
                'code' => 'INVALID_FORMAT',
            ];
        }

        // Check for path, query, or fragment
        if (preg_match('/[\/\?#]/', $domain)) {
            return [
                'valid' => false,
                'domain' => $domain,
                'message' => 'Domain should not include path, query, or fragment',
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

        // Check for URL encoding
        if (preg_match('/%[0-9a-fA-F]{2}/', $domain)) {
            return [
                'valid' => false,
                'domain' => $domain,
                'message' => 'URL encoded domains are not allowed',
                'code' => 'INVALID_FORMAT',
            ];
        }

        // SSRF: Check for blocked keywords
        foreach ($this->blockedKeywords as $keyword) {
            if ($domainLower === $keyword || Str::startsWith($domainLower . '.', $keyword . '.')) {
                return [
                    'valid' => false,
                    'domain' => $domain,
                    'message' => 'Domain blacklisted',
                    'code' => 'SSRF_BLOCKED',
                ];
            }
        }

        // SSRF: Check for blocked TLDs
        $domainParts = explode('.', $domainLower);
        $tld = end($domainParts);
        if (in_array($tld, $this->blockedTlds)) {
            return [
                'valid' => false,
                'domain' => $domain,
                'message' => 'Domain TLD is blocked for security reasons',
                'code' => 'SSRF_BLOCKED',
            ];
        }

        // SSRF: Check for internal domain patterns
        foreach ($domainParts as $part) {
            if (in_array($part, $this->blockedTlds)) {
                return [
                    'valid' => false,
                    'domain' => $domain,
                    'message' => 'Internal domain pattern is blocked',
                    'code' => 'SSRF_BLOCKED',
                ];
            }
        }

        // SSRF: Block decimal IP encoding
        if (preg_match('/^\d+$/', $domain) && (int)$domain > 0) {
            return [
                'valid' => false,
                'domain' => $domain,
                'message' => 'Decimal IP encoding is not allowed',
                'code' => 'INVALID_FORMAT',
            ];
        }

        // SSRF: Block hex IP encoding
        if (preg_match('/^0x[0-9a-fA-F]+$/i', $domain)) {
            return [
                'valid' => false,
                'domain' => $domain,
                'message' => 'Hex IP encoding is not allowed',
                'code' => 'INVALID_FORMAT',
            ];
        }

        // SSRF: Block octal IP encoding
        if (preg_match('/^0\d+\.\d+\.\d+\.\d+$/', $domain)) {
            return [
                'valid' => false,
                'domain' => $domain,
                'message' => 'Octal IP encoding is not allowed',
                'code' => 'INVALID_FORMAT',
            ];
        }

        // SSRF: Block mixed hex IP encoding
        if (preg_match('/0x[0-9a-fA-F]+\./i', $domain)) {
            return [
                'valid' => false,
                'domain' => $domain,
                'message' => 'Mixed hex IP encoding is not allowed',
                'code' => 'INVALID_FORMAT',
            ];
        }

        // Check domain length (max 253 chars)
        if (strlen($domain) > 253) {
            return [
                'valid' => false,
                'domain' => $domain,
                'message' => 'Domain is too long',
                'code' => 'INVALID_FORMAT',
            ];
        }

        // Check for valid domain format
        $domainRegex = '/^(?=.{1,253}$)(?!-)([a-zA-Z0-9-]{1,63}(?<!-)\.)+[a-zA-Z]{2,}$/';

        // Also allow punycode domains
        $punycodeRegex = '/^(?=.{1,253}$)(?!-)([a-zA-Z0-9-]{1,63}(?<!-)\.)+[a-zA-Z0-9-]{2,}$/';

        if (!preg_match($domainRegex, $domain) && !preg_match($punycodeRegex, $domain)) {
            // Check if it's a Unicode domain - convert to punycode
            if (preg_match('/[^\x20-\x7E]/', $domain)) {
                $punycode = idn_to_ascii($domain, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46);
                if ($punycode !== false) {
                    return [
                        'valid' => true,
                        'domain' => $punycode,
                        'normalized' => $punycode,
                        'message' => 'Unicode domain converted to punycode',
                    ];
                }
            }

            return [
                'valid' => false,
                'domain' => $domain,
                'message' => 'Invalid domain format',
                'code' => 'INVALID_FORMAT',
            ];
        }

        // Check for very long labels (max 63 chars per label)
        foreach ($domainParts as $part) {
            if (strlen($part) > 63) {
                return [
                    'valid' => false,
                    'domain' => $domain,
                    'message' => 'Domain label is too long',
                    'code' => 'INVALID_FORMAT',
                ];
            }
        }

        return [
            'valid' => true,
            'domain' => $domain,
            'message' => 'Domain format is valid',
        ];
    }

    /**
     * Validate DNS record exists.
     */
    public function validateDns(string $domain): array
    {
        if ($this->checkDnsRecord($domain)) {
            return [
                'valid' => true,
                'domain' => $domain,
                'message' => 'DNS record found',
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
     * Check if DNS record exists for domain.
     */
    protected function checkDnsRecord(string $domain): bool
    {
        // Check for A or CNAME record
        return checkdnsrr($domain, 'A') || checkdnsrr($domain, 'CNAME');
    }

    /**
     * Resolve domain to IP address.
     */
    protected function resolveDomain(string $domain): ?string
    {
        $ip = gethostbyname($domain);

        // gethostbyname returns the domain if it can't resolve
        if ($ip === $domain) {
            return null;
        }

        // Validate DNS rebinding cases explicitly
        $cachedIp = Cache::get("resolved:{$domain}");

        if (!is_null($cachedIp) && $cachedIp !== $ip) {
            info("Rebinding flagged. Cached '{$cachedIp}' diverged from latest '{$ip}' for domain '{$domain}'.");
            return [
                'valid' => false,
                'domain' => $domain,
                'message' => 'DNS rebinding detected',
                'code' => 'SSRF_BLOCKED',
            ];
        }

        Cache::put("resolved:{$domain}", $ip, 300);

        // Validate the resolved IP for SSRF rules
        $ipValidation = $this->validateIp($ip);
        if (!$ipValidation['valid']) {
            throw new DomainValidationException('Resolved IP violates SSRF rules', 'SSRF_BLOCKED');
        }

        return $ip;
    }

    /**
     * Validate IP address for SSRF protection.
     */
    public function validateIp(string $ip): array
    {
        // Handle IPv6 mapped IPv4 addresses
        $ip = $this->normalizeIpv6($ip);

        // Check for valid IP format
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            return [
                'valid' => false,
                'message' => 'Invalid IP address format',
                'code' => 'INVALID_FORMAT',
            ];
        }

        // SSRF: Check for private IP ranges
        $blockedRanges = [
            // IPv4 Loopback
            '127.0.0.0/8',
            // IPv4 Private networks
            '10.0.0.0/8',
            '172.16.0.0/12',
            '192.168.0.0/16',
            // IPv4 Link-local
            '169.254.0.0/16',
        ];

        foreach ($blockedRanges as $range) {
            if ($this->ipInRange($ip, $range)) {
                return [
                    'valid' => false,
                    'message' => 'IP address is blocked for security reasons',
                    'code' => 'SSRF_BLOCKED',
                ];
            }
        }

        // SSRF: Check for IPv6 special addresses
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $ipLower = strtolower($ip);

            // IPv6 loopback
            if ($ipLower === '::1' || $ipLower === '0:0:0:0:0:0:0:1') {
                return [
                    'valid' => false,
                    'message' => 'IPv6 loopback address is blocked',
                    'code' => 'SSRF_BLOCKED',
                ];
            }

            // IPv6 unspecified address
            if ($ipLower === '::' || $ipLower === '0:0:0:0:0:0:0:0') {
                return [
                    'valid' => false,
                    'message' => 'IPv6 unspecified address is blocked',
                    'code' => 'SSRF_BLOCKED',
                ];
            }

            // IPv6 link-local (fe80::/10)
            if (Str::startsWith($ipLower, 'fe8') || Str::startsWith($ipLower, 'fe9') ||
                Str::startsWith($ipLower, 'fea') || Str::startsWith($ipLower, 'feb')) {
                return [
                    'valid' => false,
                    'message' => 'IPv6 link-local address is blocked',
                    'code' => 'SSRF_BLOCKED',
                ];
            }

            // IPv6 unique local (fc00::/7)
            if (Str::startsWith($ipLower, 'fc') || Str::startsWith($ipLower, 'fd')) {
                return [
                    'valid' => false,
                    'message' => 'IPv6 unique local address is blocked',
                    'code' => 'SSRF_BLOCKED',
                ];
            }
        }

        return [
            'valid' => true,
            'message' => 'IP address is valid',
        ];
    }

    /**
     * Normalize IPv6 mapped IPv4 addresses.
     */
    protected function normalizeIpv6(string $ip): string
    {
        // Check for IPv6 mapped IPv4 (::ffff:127.0.0.1)
        if (preg_match('/^::ffff:(\d+\.\d+\.\d+\.\d+)$/i', $ip, $matches)) {
            return $matches[1];
        }

        return $ip;
    }

    /**
     * Check if IP is in CIDR range.
     */
    protected function ipInRange(string $ip, string $range): bool
    {
        // Only handle IPv4 for CIDR ranges
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return false;
        }

        list($subnet, $bits) = explode('/', $range);

        $ipLong = ip2long($ip);
        $subnetLong = ip2long($subnet);

        if ($ipLong === false || $subnetLong === false) {
            return false;
        }

        $mask = -1 << (32 - (int)$bits);
        $subnetLong &= $mask;

        return ($ipLong & $mask) === $subnetLong;
    }

    /**
     * Check domain accessibility via HTTP.
     */
    public function checkAccessibility(string $domain): array
    {
        try {
            $response = Http::withOptions(['timeout' => $this->httpTimeout, 'allow_redirects' => ['max' => $this->maxRedirects]])
                ->get("https://{$domain}");

            if ($response->successful()) {
                return [
                    'valid' => true,
                    'message' => 'Domain is accessible',
                ];
            }

            // Try HTTP if HTTPS fails
            $response = Http::timeout($this->httpTimeout)->get("http://{$domain}");

            if ($response->successful()) {
                return [
                    'valid' => true,
                    'message' => 'Domain is accessible (HTTP)',
                ];
            }

            return [
                'valid' => false,
                'message' => 'Domain returned HTTP ' . $response->status(),
                'code' => 'CONNECTION_ERROR',
            ];
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            return [
                'valid' => false,
                'message' => 'Connection timeout or refused',
                'code' => 'CONNECTION_TIMEOUT',
            ];
        } catch (\Exception $e) {
            return [
                'valid' => false,
                'message' => 'Failed to connect to domain: ' . $e->getMessage(),
                'code' => 'CONNECTION_ERROR',
            ];
        }
    }

    /**
     * Validate with rate limiting.
     */
    public function validateWithRateLimit(string $domain, string $userId): array
    {
        $cacheKey = "rate_limit:{$userId}";

        // Get current count
        $count = Cache::get($cacheKey, 0);

        if ($count >= $this->rateLimit) {
            return [
                'valid' => false,
                'domain' => $domain,
                'message' => 'Rate limit exceeded. Please wait before making more requests.',
                'code' => 'RATE_LIMITED',
            ];
        }

        // Increment count
        Cache::put($cacheKey, $count + 1, $this->rateLimitWindow);

        // Perform validation
        return $this->validate($domain);
    }

    /**
     * Normalize domain string.
     */
    protected function normalizeDomain(string $domain): string
    {
        // Trim whitespace and trailing dot
        $domain = trim($domain);
        $domain = rtrim($domain, '.');

        // Convert to lowercase
        return strtolower($domain);
    }
}
