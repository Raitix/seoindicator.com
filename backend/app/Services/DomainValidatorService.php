<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * Domain Validator Service for TASK-18-4.
 *
 * Normalizes domain input, validates format, checks accessibility,
 * and detects redirects. Integrates with URLValidationService for SSRF protection.
 *
 * Features:
 * - Domain normalization (protocol, www, path/query removal)
 * - Format validation (RFC compliant)
 * - IDN/Punycode support
 * - Accessibility checking with HTTP client
 * - Redirect following with loop detection
 * - SSRF protection via URLValidationService
 */
class DomainValidatorService
{
    /**
     * Maximum number of redirects to follow.
     */
    private const MAX_REDIRECTS = 3;

    /**
     * Default timeout in seconds.
     */
    private const DEFAULT_TIMEOUT = 10;

    /**
     * Maximum domain length.
     */
    private const MAX_DOMAIN_LENGTH = 253;

    /**
     * Allowed protocols.
     */
    private const ALLOWED_PROTOCOLS = ['http', 'https'];

    /**
     * Blocked protocols (SSRF protection).
     */
    private const BLOCKED_PROTOCOLS = ['file', 'ftp', 'gopher', 'data', 'javascript', 'vbscript'];

    /**
     * URL Validation Service for SSRF protection.
     */
    private URLValidationService $urlValidationService;

    /**
     * Initialize the service.
     */
    public function __construct(URLValidationService $urlValidationService)
    {
        $this->urlValidationService = $urlValidationService;
    }

    /**
     * Validate and normalize a domain.
     *
     * @param string $domain Input domain
     * @param array $options Optional settings (timeout, follow_redirects, verify_ssl)
     * @return array{url: string, finalUrl: string, redirects: int, accessible: bool, redirectChain: array}
     * @throws ValidationException On validation failure
     */
    public function validate(string $domain, array $options = []): array
    {
        // Trim whitespace
        $domain = trim($domain);

        // Validate format first
        if (!$this->isValidFormat($domain)) {
            throw $this->createValidationException('INVALID_DOMAIN', 'The domain format is invalid.');
        }

        // Normalize the domain to a URL
        $normalizedUrl = $this->normalize($domain);

        // Check for blocked protocols in the input
        $parsedOriginal = parse_url($domain);
        if ($parsedOriginal !== false && isset($parsedOriginal['scheme'])) {
            $scheme = strtolower($parsedOriginal['scheme']);
            if (in_array($scheme, self::BLOCKED_PROTOCOLS, true)) {
                throw $this->createValidationException('BLOCKED_PROTOCOL', "The '{$scheme}' protocol is not allowed.");
            }
        }

        // Validate via SSRF protection
        $this->validateWithSSRFProtection($normalizedUrl);

        // Check accessibility
        $timeout = $options['timeout'] ?? self::DEFAULT_TIMEOUT;
        $accessibilityResult = $this->checkAccessibility($normalizedUrl, $timeout);

        if (!$accessibilityResult['accessible']) {
            $errorCode = $this->getErrorCodeFromStatus($accessibilityResult['statusCode'] ?? 0, $accessibilityResult['error'] ?? null);
            throw $this->createValidationException($errorCode, $accessibilityResult['error'] ?? 'Domain is not accessible.');
        }

        return [
            'url' => $normalizedUrl,
            'finalUrl' => $accessibilityResult['finalUrl'],
            'redirects' => $accessibilityResult['redirects'],
            'accessible' => $accessibilityResult['accessible'],
            'redirectChain' => $accessibilityResult['redirectChain'] ?? [],
        ];
    }

    /**
     * Normalize a domain string to a URL.
     *
     * @param string $domain
     * @return string Normalized URL
     */
    public function normalize(string $domain): string
    {
        // Trim whitespace
        $domain = trim($domain);

        // Handle empty input
        if ($domain === '') {
            return '';
        }

        // Check if domain already has a protocol
        $hasProtocol = preg_match('/^https?:\/\//i', $domain);

        // Extract protocol if present
        $protocol = 'https';
        if ($hasProtocol) {
            if (stripos($domain, 'http://') === 0) {
                $protocol = 'http';
                $domain = substr($domain, 7);
            } elseif (stripos($domain, 'https://') === 0) {
                $protocol = 'https';
                $domain = substr($domain, 8);
            }
        }

        // Remove path, query, and fragment for domain-only normalization
        // But preserve them if explicitly provided with protocol
        $path = '';
        $query = '';
        $fragment = '';

        // Find the first / ? or #
        $firstSlash = strpos($domain, '/');
        $firstQuestion = strpos($domain, '?');
        $firstHash = strpos($domain, '#');

        // Find the earliest delimiter
        $cutPosition = null;
        foreach ([$firstSlash, $firstQuestion, $firstHash] as $pos) {
            if ($pos !== false && ($cutPosition === null || $pos < $cutPosition)) {
                $cutPosition = $pos;
            }
        }

        if ($cutPosition !== null) {
            $domain = substr($domain, 0, $cutPosition);
        }

        // Remove port from domain for processing but remember it
        $port = '';
        if (preg_match('/:(\d+)$/', $domain, $matches)) {
            $port = ':' . $matches[1];
            $domain = preg_replace('/:\d+$/', '', $domain);
        }

        // Convert to lowercase
        $domain = strtolower($domain);

        // Handle IDN domains - convert to punycode
        $domain = $this->convertIdnToPunycode($domain);

        // Remove www. prefix only if no protocol was specified (bare domain)
        if (!$hasProtocol && strpos($domain, 'www.') === 0) {
            $domain = substr($domain, 4);
        }

        // Build the URL (no path for domain normalization)
        $url = $protocol . '://' . $domain . $port;

        return $url;
    }

    /**
     * Check if domain format is valid.
     *
     * @param string $domain
     * @return bool
     */
    public function isValidFormat(string $domain): bool
    {
        // Trim whitespace
        $domain = trim($domain);

        // Empty check
        if ($domain === '') {
            return false;
        }

        // Whitespace-only check
        if (preg_match('/^\s+$/', $domain)) {
            return false;
        }

        // Length check
        if (strlen($domain) > self::MAX_DOMAIN_LENGTH) {
            return false;
        }

        // Check for control characters
        if (preg_match('/[\x00-\x1F\x7F]/', $domain)) {
            return false;
        }

        // Check for blocked protocols
        $parsedOriginal = parse_url($domain);
        if ($parsedOriginal !== false && isset($parsedOriginal['scheme'])) {
            $scheme = strtolower($parsedOriginal['scheme']);
            if (in_array($scheme, self::BLOCKED_PROTOCOLS, true)) {
                return false; // Blocked protocols are invalid
            }
        }

        // Extract domain from URL if protocol present
        $domainToCheck = $domain;
        if (preg_match('/^https?:\/\/(.+)$/i', $domain, $matches)) {
            $domainToCheck = $matches[1];
        }

        // Remove path, query, fragment for validation
        $domainToCheck = preg_replace('/[\/\?#].*$/', '', $domainToCheck);

        // Remove port
        $domainToCheck = preg_replace('/:\d+$/', '', $domainToCheck);

        // Convert IDN to punycode for validation
        $domainToCheck = $this->convertIdnToPunycode($domainToCheck);

        // Check for IP addresses (valid format but will be blocked by SSRF)
        if (filter_var($domainToCheck, FILTER_VALIDATE_IP)) {
            return true;
        }

        // Check for IPv6 in brackets
        if (preg_match('/^\[([a-f0-9:]+)\]$/i', $domainToCheck)) {
            return true;
        }

        // Domain regex pattern
        // Allows: letters, numbers, hyphens (not at start/end of labels)
        // Requires at least one dot for full domain, or single label for things like 'localhost'
        $domainPattern = '/^
            (?:[a-zA-Z0-9]            # Start with alphanumeric
            (?:[a-zA-Z0-9\-]*         # Allow alphanumerics and hyphens
            [a-zA-Z0-9])?             # End with alphanumeric
            \.)*                      # Repeat for subdomains
            [a-zA-Z0-9]               # TLD starts with alphanumeric
            (?:[a-zA-Z0-9\-]*         # Allow alphanumerics and hyphens
            [a-zA-Z0-9])?             # End with alphanumeric
            (?:\.[a-zA-Z]{2,})?       # Optional additional TLD parts (e.g., .co.uk)
        $/x';

        // Single label (like 'localhost') is valid
        $singleLabelPattern = '/^[a-zA-Z0-9](?:[a-zA-Z0-9\-]*[a-zA-Z0-9])?$/';

        if (preg_match($domainPattern, $domainToCheck)) {
            // Check TLD is not all numeric
            $parts = explode('.', $domainToCheck);
            $tld = end($parts);
            if (preg_match('/^\d+$/', $tld)) {
                return false;
            }
            return true;
        }

        // Check for single label (localhost, etc.)
        if (preg_match($singleLabelPattern, $domainToCheck)) {
            return true;
        }

        return false;
    }

    /**
     * Check if domain is accessible.
     *
     * @param string $url Normalized URL
     * @param int $timeout Timeout in seconds
     * @return array{accessible: bool, statusCode: int, finalUrl: string, redirects: int, redirectChain: array, error?: string}
     */
    public function checkAccessibility(string $url, int $timeout = self::DEFAULT_TIMEOUT): array
    {
        $visitedUrls = [];
        $redirectChain = [];
        $currentUrl = $url;
        $redirectCount = 0;

        try {
            while (true) {
                // Check for redirect loop
                if (in_array($currentUrl, $visitedUrls, true)) {
                    return [
                        'accessible' => false,
                        'statusCode' => 0,
                        'finalUrl' => $currentUrl,
                        'redirects' => $redirectCount,
                        'redirectChain' => $redirectChain,
                        'error' => 'Redirect loop detected.',
                    ];
                }

                $visitedUrls[] = $currentUrl;
                $redirectChain[] = $currentUrl;

                // Validate each URL in the redirect chain for SSRF
                $this->validateWithSSRFProtection($currentUrl);

                // Make the HTTP request
                $response = Http::timeout($timeout)
                    ->withoutVerifying() // For development/testing
                    ->get($currentUrl);

                $statusCode = $response->status();

                // Check for redirect
                if (in_array($statusCode, [301, 302, 303, 307, 308], true)) {
                    $redirectCount++;

                    // Check redirect limit
                    if ($redirectCount > self::MAX_REDIRECTS) {
                        return [
                            'accessible' => false,
                            'statusCode' => $statusCode,
                            'finalUrl' => $currentUrl,
                            'redirects' => $redirectCount,
                            'redirectChain' => $redirectChain,
                            'error' => 'Maximum redirect limit exceeded.',
                        ];
                    }

                    // Get redirect location
                    $location = $response->header('Location');
                    if (empty($location)) {
                        return [
                            'accessible' => false,
                            'statusCode' => $statusCode,
                            'finalUrl' => $currentUrl,
                            'redirects' => $redirectCount,
                            'redirectChain' => $redirectChain,
                            'error' => 'Redirect without location header.',
                        ];
                    }

                    // Handle relative URLs
                    if (strpos($location, 'http') !== 0) {
                        $parsedUrl = parse_url($currentUrl);
                        $baseUrl = ($parsedUrl['scheme'] ?? 'https') . '://' . ($parsedUrl['host'] ?? '');
                        $location = $baseUrl . ($location[0] === '/' ? $location : '/' . $location);
                    }

                    $currentUrl = $location;
                    continue;
                }

                // Success (2xx)
                if ($statusCode >= 200 && $statusCode < 300) {
                    return [
                        'accessible' => true,
                        'statusCode' => $statusCode,
                        'finalUrl' => $currentUrl,
                        'redirects' => $redirectCount,
                        'redirectChain' => $redirectChain,
                    ];
                }

                // Client errors (4xx)
                if ($statusCode >= 400 && $statusCode < 500) {
                    return [
                        'accessible' => false,
                        'statusCode' => $statusCode,
                        'finalUrl' => $currentUrl,
                        'redirects' => $redirectCount,
                        'redirectChain' => $redirectChain,
                        'error' => $this->getErrorMessageForStatus($statusCode),
                    ];
                }

                // Server errors (5xx)
                if ($statusCode >= 500) {
                    return [
                        'accessible' => false,
                        'statusCode' => $statusCode,
                        'finalUrl' => $currentUrl,
                        'redirects' => $redirectCount,
                        'redirectChain' => $redirectChain,
                        'error' => 'Server error.',
                    ];
                }

                // Other status codes
                return [
                    'accessible' => false,
                    'statusCode' => $statusCode,
                    'finalUrl' => $currentUrl,
                    'redirects' => $redirectCount,
                    'redirectChain' => $redirectChain,
                    'error' => 'Unexpected response status.',
                ];
            }
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            return [
                'accessible' => false,
                'statusCode' => 0,
                'finalUrl' => $currentUrl,
                'redirects' => $redirectCount,
                'redirectChain' => $redirectChain,
                'error' => 'Connection failed or timeout.',
            ];
        } catch (\Exception $e) {
            return [
                'accessible' => false,
                'statusCode' => 0,
                'finalUrl' => $currentUrl,
                'redirects' => $redirectCount,
                'redirectChain' => $redirectChain,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Validate URL with SSRF protection.
     *
     * @param string $url
     * @throws ValidationException
     */
    private function validateWithSSRFProtection(string $url): void
    {
        $result = $this->urlValidationService->validate($url);

        if (!$result['valid']) {
            throw $this->createValidationException(
                'BLOCKED_URL',
                $result['error'] ?? 'Access to this URL is not allowed.'
            );
        }
    }

    /**
     * Convert IDN domain to punycode.
     *
     * @param string $domain
     * @return string
     */
    private function convertIdnToPunycode(string $domain): string
    {
        // Check if domain contains non-ASCII characters
        if (!preg_match('/[^\x20-\x7E]/', $domain)) {
            return $domain;
        }

        // Use intl extension if available
        if (function_exists('idn_to_ascii')) {
            // IDNA 2003 variant for better compatibility
            $result = idn_to_ascii($domain, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46);
            if ($result !== false) {
                return $result;
            }
        }

        // Fallback: return as-is (tests may need to handle this)
        return $domain;
    }

    /**
     * Create a ValidationException with error code.
     *
     * @param string $code
     * @param string $message
     * @return ValidationException
     */
    private function createValidationException(string $code, string $message): ValidationException
    {
        return ValidationException::withMessages([
            'domain' => [
                'code' => $code,
                'message' => $message,
            ],
        ]);
    }

    /**
     * Get error code from HTTP status.
     *
     * @param int $statusCode
     * @param string|null $error
     * @return string
     */
    private function getErrorCodeFromStatus(int $statusCode, ?string $error): string
    {
        // Check for specific error messages
        if ($error !== null) {
            if (stripos($error, 'timeout') !== false) {
                return 'TIMEOUT';
            }
            if (stripos($error, 'ssl') !== false || stripos($error, 'certificate') !== false) {
                return 'SSL_ERROR';
            }
            if (stripos($error, 'dns') !== false || stripos($error, 'resolve') !== false) {
                return 'DOMAIN_UNREACHABLE';
            }
            if (stripos($error, 'redirect loop') !== false) {
                return 'REDIRECT_LOOP';
            }
            if (stripos($error, 'redirect limit') !== false) {
                return 'REDIRECT_LIMIT_EXCEEDED';
            }
        }

        // Status code mapping
        return match (true) {
            $statusCode === 0 => 'DOMAIN_UNREACHABLE',
            $statusCode === 403 => 'DOMAIN_FORBIDDEN',
            $statusCode === 404 => 'DOMAIN_NOT_FOUND',
            $statusCode === 429 => 'RATE_LIMITED',
            $statusCode >= 500 => 'DOMAIN_ERROR',
            default => 'DOMAIN_UNREACHABLE',
        };
    }

    /**
     * Get human-readable error message for HTTP status.
     *
     * @param int $statusCode
     * @return string
     */
    private function getErrorMessageForStatus(int $statusCode): string
    {
        return match ($statusCode) {
            400 => 'Bad request.',
            401 => 'Authentication required.',
            403 => 'Access forbidden.',
            404 => 'Resource not found.',
            429 => 'Too many requests. Please try again later.',
            default => "Request failed with status {$statusCode}.",
        };
    }
}
