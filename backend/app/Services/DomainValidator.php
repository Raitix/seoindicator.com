<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Domain Validation Service
 * 
 * Task: SPEC-003-3 - Domain Validation Service
 * 
 * Validates competitor domains before adding them.
 * Includes SSRF protection to prevent access to internal resources.
 */
class DomainValidator
{
    /**
     * Maximum requests per minute per user
     */
    protected int $rateLimit = 10;

    /**
     * Rate limit window in seconds
     */
    protected int $rateLimitWindow = 60;

    /**
     * HTTP timeout for accessibility check
     */
    protected int $httpTimeout = 5;

    /**
     * Maximum redirects to follow
     */
    protected int $maxRedirects = 3;

    /**
     * Blocked internal domain keywords
     */
    protected array $blockedKeywords = [
        'localhost',
        'local',
        'internal',
        'intranet',
        'test',
    ];

    /**
     * Validate a domain completely
     *
     * @param string $domain Domain to validate
     * @return array{valid: bool, domain: string, ip?: string, message?: string, code?: string}
     */
    public function validate(string $domain): array
    {
        // Step 1: Validate format
        $formatResult = $this->validateFormat($domain);
        if (!$formatResult['valid']) {
            return [
                'valid' => false,
                'domain' => $domain,
                'message' => $formatResult['message'],
                'code' => $formatResult['code'],
            ];
        }

        // Clean domain
        $domain = $formatResult['domain'] ?? $domain;

        // Step 2: Resolve domain to IP
        $ip = $this->resolveDomain($domain);
        if (!$ip) {
            return [
                'valid' => false,
                'domain' => $domain,
                'message' => 'Could not resolve domain to IP address',
                'code' => 'DNS_NOT_FOUND',
            ];
        }

        // Step 3: Check SSRF protection
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
                'message' => 'No DNS record found for domain',
                'code' => 'DNS_NOT_FOUND',
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
        ];
    }

    /**
     * Validate domain format
     *
     * @param string|null $domain
     * @return array{valid: bool, domain?: string, message?: string, code?: string}
     */
    public function validateFormat(?string $domain): array
    {
        // Check for null or empty
        if (empty($domain) || trim($domain) === '') {
            return [
                'valid' => false,
                'message' => 'Domain is required',
                'code' => 'INVALID_FORMAT',
            ];
        }

        $domain = strtolower(trim($domain));

        // Check for protocol prefix
        if (preg_match('/^(https?|ftp):\/\//i', $domain)) {
            return [
                'valid' => false,
                'message' => 'Domain should not include protocol prefix',
                'code' => 'INVALID_FORMAT',
            ];
        }

        // Check for path/query
        if (preg_match('/[\/?#]/', $domain)) {
            return [
                'valid' => false,
                'message' => 'Domain should not include path or query parameters',
                'code' => 'INVALID_FORMAT',
            ];
        }

        // Check for blocked keywords (SSRF protection)
        $domainParts = explode('.', $domain);
        foreach ($domainParts as $part) {
            if (in_array($part, $this->blockedKeywords)) {
                return [
                    'valid' => false,
                    'message' => 'Internal domains are not allowed',
                    'code' => 'SSRF_BLOCKED',
                ];
            }
        }

        // Check for decimal IP encoding (SSRF bypass)
        if (preg_match('/^\d+$/', $domain) && $this->isDecimalIp($domain)) {
            return [
                'valid' => false,
                'message' => 'Decimal encoded IP addresses are not allowed',
                'code' => 'SSRF_BLOCKED',
            ];
        }

        // Check for hex IP encoding (SSRF bypass)
        if (preg_match('/^0x[0-9a-f]+$/i', $domain) || preg_match('/^0x[0-9a-f]+\./i', $domain)) {
            return [
                'valid' => false,
                'message' => 'Hex encoded IP addresses are not allowed',
                'code' => 'SSRF_BLOCKED',
            ];
        }

        // Check for octal IP encoding (SSRF bypass)
        if (preg_match('/^0[0-7]+\./', $domain)) {
            return [
                'valid' => false,
                'message' => 'Octal encoded IP addresses are not allowed',
                'code' => 'SSRF_BLOCKED',
            ];
        }

        // Check for URL encoded IP
        if (preg_match('/%[0-9a-f]{2}/i', $domain)) {
            return [
                'valid' => false,
                'message' => 'URL encoded addresses are not allowed',
                'code' => 'SSRF_BLOCKED',
            ];
        }

        // Validate domain format with regex
        $domainRegex = '/^([a-z0-9]([a-z0-9-]*[a-z0-9])?\.)*[a-z0-9]([a-z0-9-]*[a-z0-9])?$/i';
        if (!preg_match($domainRegex, $domain)) {
            return [
                'valid' => false,
                'message' => 'Invalid domain format',
                'code' => 'INVALID_FORMAT',
            ];
        }

        // Validate TLD length
        $tld = end($domainParts);
        if (strlen($tld) < 2 || strlen($tld) > 63) {
            return [
                'valid' => false,
                'message' => 'Invalid TLD length',
                'code' => 'INVALID_FORMAT',
            ];
        }

        // Validate total domain length
        if (strlen($domain) > 253) {
            return [
                'valid' => false,
                'message' => 'Domain is too long (max 253 characters)',
                'code' => 'INVALID_FORMAT',
            ];
        }

        return [
            'valid' => true,
            'domain' => $domain,
        ];
    }

    /**
     * Check DNS record exists
     *
     * @param string $domain
     * @return array{valid: bool, message?: string, code?: string}
     */
    public function validateDns(string $domain): array
    {
        if ($this->checkDnsRecord($domain)) {
            return [
                'valid' => true,
            ];
        }

        return [
            'valid' => false,
            'message' => 'No DNS record found for domain',
            'code' => 'DNS_NOT_FOUND',
        ];
    }

    /**
     * Check DNS record exists (protected for mocking)
     *
     * @param string $domain
     * @return bool
     */
    protected function checkDnsRecord(string $domain): bool
    {
        // Check for A or CNAME record
        return checkdnsrr($domain, 'A') || checkdnsrr($domain, 'CNAME');
    }

    /**
     * Validate IP address for SSRF protection
     *
     * @param string $ip
     * @return array{valid: bool, message?: string, code?: string}
     */
    public function validateIp(string $ip): array
    {
        // Validate it's a valid IP
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            return [
                'valid' => false,
                'message' => 'Invalid IP address',
                'code' => 'INVALID_FORMAT',
            ];
        }

        // Check for IPv6 loopback
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            // ::1 is IPv6 loopback
            if ($ip === '::1' || $ip === '0:0:0:0:0:0:0:1' || $ip === '::') {
                return [
                    'valid' => false,
                    'message' => 'IPv6 loopback addresses are not allowed',
                    'code' => 'SSRF_BLOCKED',
                ];
            }

            // IPv6 link-local (fe80::/10)
            if (Str::startsWith(strtolower($ip), 'fe80')) {
                return [
                    'valid' => false,
                    'message' => 'IPv6 link-local addresses are not allowed',
                    'code' => 'SSRF_BLOCKED',
                ];
            }

            // IPv6 unique local (fc00::/7)
            if (Str::startsWith(strtolower($ip), 'fc') || Str::startsWith(strtolower($ip), 'fd')) {
                return [
                    'valid' => false,
                    'message' => 'IPv6 unique local addresses are not allowed',
                    'code' => 'SSRF_BLOCKED',
                ];
            }

            // IPv6 mapped IPv4 addresses (::ffff:x.x.x.x)
            if (Str::startsWith(strtolower($ip), '::ffff:')) {
                // Extract the IPv4 part
                $ipv4Part = substr($ip, 7);
                return $this->validateIp($ipv4Part);
            }
        }

        // Check for IPv4 private ranges
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            // Loopback (127.0.0.0/8)
            if (Str::startsWith($ip, '127.')) {
                return [
                    'valid' => false,
                    'message' => 'Loopback addresses are not allowed',
                    'code' => 'SSRF_BLOCKED',
                ];
            }

            // Private 10.0.0.0/8
            if (Str::startsWith($ip, '10.')) {
                return [
                    'valid' => false,
                    'message' => 'Private IP addresses are not allowed',
                    'code' => 'SSRF_BLOCKED',
                ];
            }

            // Private 172.16.0.0/12
            if (preg_match('/^172\.(1[6-9]|2[0-9]|3[0-1])\./', $ip)) {
                return [
                    'valid' => false,
                    'message' => 'Private IP addresses are not allowed',
                    'code' => 'SSRF_BLOCKED',
                ];
            }

            // Private 192.168.0.0/16
            if (Str::startsWith($ip, '192.168.')) {
                return [
                    'valid' => false,
                    'message' => 'Private IP addresses are not allowed',
                    'code' => 'SSRF_BLOCKED',
                ];
            }

            // Link-local 169.254.0.0/16
            if (Str::startsWith($ip, '169.254.')) {
                return [
                    'valid' => false,
                    'message' => 'Link-local addresses are not allowed',
                    'code' => 'SSRF_BLOCKED',
                ];
            }
        }

        return [
            'valid' => true,
        ];
    }

    /**
     * Check domain accessibility via HTTP
     *
     * @param string $domain
     * @return array{valid: bool, message?: string, code?: string}
     */
    public function checkAccessibility(string $domain): array
    {
        try {
            $response = Http::timeout($this->httpTimeout)
                ->maxRedirects($this->maxRedirects)
                ->head("https://{$domain}");

            // 2xx responses are valid
            if ($response->successful()) {
                return [
                    'valid' => true,
                ];
            }

            // Try HTTP if HTTPS failed
            $response = Http::timeout($this->httpTimeout)
                ->maxRedirects($this->maxRedirects)
                ->head("http://{$domain}");

            if ($response->successful()) {
                return [
                    'valid' => true,
                ];
            }

            // 3xx redirects should be followed by the client
            if ($response->redirect()) {
                return [
                    'valid' => true,
                ];
            }

            // 4xx/5xx errors
            return [
                'valid' => false,
                'message' => "Domain returned HTTP {$response->status()}",
                'code' => 'CONNECTION_FAILED',
            ];
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            return [
                'valid' => false,
                'message' => 'Connection timed out or failed',
                'code' => 'CONNECTION_TIMEOUT',
            ];
        } catch (\Exception $e) {
            return [
                'valid' => false,
                'message' => 'Failed to connect to domain: ' . $e->getMessage(),
                'code' => 'CONNECTION_FAILED',
            ];
        }
    }

    /**
     * Validate with rate limiting
     *
     * @param string $domain
     * @param string $userId
     * @return array{valid: bool, domain?: string, ip?: string, message?: string, code?: string}
     */
    public function validateWithRateLimit(string $domain, string $userId): array
    {
        $cacheKey = "rate_limit:{$userId}";

        // Get current count
        $count = Cache::get($cacheKey, 0);

        // Check if rate limited
        if ($count >= $this->rateLimit) {
            return [
                'valid' => false,
                'domain' => $domain,
                'message' => 'Rate limit exceeded. Please wait before trying again.',
                'code' => 'RATE_LIMITED',
            ];
        }

        // Increment count
        Cache::put($cacheKey, $count + 1, $this->rateLimitWindow);

        // Perform validation
        return $this->validate($domain);
    }

    /**
     * Resolve domain to IP address (protected for mocking)
     *
     * @param string $domain
     * @return string|null
     */
    protected function resolveDomain(string $domain): ?string
    {
        $records = dns_get_record($domain, DNS_A | DNS_CNAME);

        if (empty($records)) {
            return null;
        }

        // Look for A record first
        foreach ($records as $record) {
            if ($record['type'] === 'A') {
                return $record['ip'];
            }
        }

        // Follow CNAME if present
        foreach ($records as $record) {
            if ($record['type'] === 'CNAME') {
                return $this->resolveDomain($record['target']);
            }
        }

        return null;
    }

    /**
     * Check if a numeric string is a decimal encoded IP
     *
     * @param string $input
     * @return bool
     */
    protected function isDecimalIp(string $input): bool
    {
        $decimal = (int) $input;
        $ip = long2ip($decimal);

        if ($ip === false) {
            return false;
        }

        // Check if the resulting IP is a private/internal IP
        $result = $this->validateIp($ip);
        return !$result['valid'];
    }
}
