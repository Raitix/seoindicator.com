<?php

namespace App\Rules;

use App\Services\URLValidationService;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * SSRF protection rule for crawl URLs.
 *
 * Validates that a URL is safe for crawling by checking:
 * - Protocol is HTTP or HTTPS only
 * - Host is not localhost, loopback, or private IP
 * - Host is not a metadata endpoint (169.254.169.254)
 * - Domain is not internal (.internal, .local, .localhost)
 * - Various alternate notations (decimal, hex, octal IP)
 *
 * @see TASK-22-12
 */
class ValidCrawlUrl implements ValidationRule
{
    protected URLValidationService $urlValidator;

    /**
     * Maximum URL length (2083 chars is common browser limit).
     */
    protected const MAX_URL_LENGTH = 2083;

    /**
     * Allowed protocols.
     */
    protected const ALLOWED_PROTOCOLS = ['http', 'https'];

    /**
     * Blocked TLDs that indicate internal networks.
     */
    protected const BLOCKED_TLDS = ['internal', 'local', 'localhost'];

    /**
     * Blocked hostnames (case-insensitive).
     */
    protected const BLOCKED_HOSTNAMES = [
        'localhost',
        'localhost.localdomain',
        'ip6-localhost',
        'ip6-loopback',
        'ip6-localnet',
        'broadcasthost',
    ];

    /**
     * Create a new rule instance.
     */
    public function __construct(?URLValidationService $urlValidator = null)
    {
        $this->urlValidator = $urlValidator ?? app(URLValidationService::class);
    }

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Check for null or empty
        if ($value === null || $value === '') {
            $fail('The :attribute is required.');
            return;
        }

        // Must be a string
        if (!is_string($value)) {
            $fail('The :attribute must be a valid URL.');
            return;
        }

        // Check URL length
        if (strlen($value) > self::MAX_URL_LENGTH) {
            $fail('The :attribute exceeds the maximum length of ' . self::MAX_URL_LENGTH . ' characters.');
            return;
        }

        // Parse URL
        $parsed = parse_url($value);
        
        if ($parsed === false) {
            $fail('The :attribute must be a valid URL.');
            return;
        }

        // Check protocol
        $scheme = strtolower($parsed['scheme'] ?? '');
        if (!in_array($scheme, self::ALLOWED_PROTOCOLS)) {
            $fail('The :attribute Protocol must be HTTP or HTTPS.');
            return;
        }

        // Check host exists
        $host = $parsed['host'] ?? '';
        if (empty($host)) {
            $fail('The :attribute must contain a valid host.');
            return;
        }

        // Normalize host
        $hostLower = strtolower($host);

        // Check blocked hostnames
        if (in_array($hostLower, self::BLOCKED_HOSTNAMES)) {
            $fail('The :attribute hostname is not allowed.');
            return;
        }

        // Check blocked TLDs
        $hostParts = explode('.', $hostLower);
        $tld = end($hostParts);
        if (in_array($tld, self::BLOCKED_TLDS)) {
            $fail('The :attribute contains a blocked domain extension.');
            return;
        }

        // Check for internal subdomain patterns
        if ($this->isInternalDomain($hostLower)) {
            $fail('The :attribute points to an internal domain.');
            return;
        }

        // Use URLValidationService for comprehensive SSRF checks
        // This handles IP checks, alternate notations, etc.
        $validationResult = $this->urlValidator->validate($value);
        
        if (!$validationResult['valid']) {
            $fail($validationResult['message'] ?? 'The :attribute is not allowed.');
            return;
        }
    }

    /**
     * Check if domain indicates internal network.
     */
    protected function isInternalDomain(string $host): bool
    {
        // Check for internal subdomain patterns
        $internalPatterns = [
            '/\.internal$/i',
            '/\.internal\./i',
            '/\.local$/i',
            '/\.local\./i',
            '/\.localhost$/i',
            '/\.localhost\./i',
            '/^internal\./i',
            '/^local\./i',
            '/^localhost\./i',
        ];

        foreach ($internalPatterns as $pattern) {
            if (preg_match($pattern, $host)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if the host is an IP address (including alternate notations).
     */
    protected function isIpAddress(string $host): bool
    {
        // Standard IPv4
        if (filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return true;
        }

        // IPv6
        if (filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return true;
        }

        // Decimal notation (e.g., 2130706433 for 127.0.0.1)
        if (preg_match('/^\d+$/', $host)) {
            return true;
        }

        // Hex notation (e.g., 0x7f000001 for 127.0.0.1)
        if (preg_match('/^0x[0-9a-f]+$/i', $host)) {
            return true;
        }

        // Octal notation (e.g., 017700000001 for 127.0.0.1)
        if (preg_match('/^0[0-7]+$/', $host)) {
            return true;
        }

        return false;
    }
}
