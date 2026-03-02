<?php

namespace App\Services;

use App\Exceptions\SSRFException;

/**
 * URL Validator Service for the Crawler.
 *
 * Wrapper around URLValidationService that throws SSRFException
 * when a URL is blocked, allowing for clean exception handling in jobs.
 *
 * @see TASK-22-7
 * @see TASK-22-15
 */
class UrlValidatorService
{
    /**
     * Create a new URL validator service.
     */
    public function __construct(
        protected URLValidationService $validationService
    ) {}

    /**
     * Validate a URL for safe external fetching.
     *
     * @param string $url The URL to validate
     * @return bool True if the URL is valid
     * @throws SSRFException If the URL is blocked for security reasons
     */
    public function validate(string $url): bool
    {
        $result = $this->validationService->validate($url);

        if (!$result['valid']) {
            $error = $result['error'] ?? 'URL blocked for security reasons';
            
            // Map error message to appropriate exception
            throw $this->createExceptionFromError($url, $error);
        }

        return true;
    }

    /**
     * Validate a redirect URL (DNS rebinding protection).
     *
     * @param string $url The redirect URL to validate
     * @return bool True if the URL is valid
     * @throws SSRFException If the URL is blocked for security reasons
     */
    public function validateRedirect(string $url): bool
    {
        $result = $this->validationService->validateRedirect($url);

        if (!$result['valid']) {
            $error = $result['error'] ?? 'Redirect URL blocked for security reasons';
            
            throw SSRFException::dnsRebinding($url);
        }

        return true;
    }

    /**
     * Add a host to the whitelist.
     *
     * @param string $host Host to whitelist
     */
    public function addToWhitelist(string $host): void
    {
        $this->validationService->addToWhitelist($host);
    }

    /**
     * Remove a host from the whitelist.
     *
     * @param string $host Host to remove
     */
    public function removeFromWhitelist(string $host): void
    {
        $this->validationService->removeFromWhitelist($host);
    }

    /**
     * Add a custom blocked host.
     *
     * @param string $host Host to block
     */
    public function addBlockedHost(string $host): void
    {
        $this->validationService->addBlockedHost($host);
    }

    /**
     * Add a custom blocked IP.
     *
     * @param string $ip IP address to block
     */
    public function addBlockedIP(string $ip): void
    {
        $this->validationService->addBlockedIP($ip);
    }

    /**
     * Create an SSRFException from an error message.
     */
    protected function createExceptionFromError(string $url, string $error): SSRFException
    {
        $errorLower = strtolower($error);

        // Match specific error types
        if (str_contains($errorLower, 'localhost') || str_contains($errorLower, '127.0.0.1')) {
            return SSRFException::localhost($url);
        }

        if (str_contains($errorLower, 'private') || str_contains($errorLower, 'internal')) {
            return SSRFException::privateIp($url);
        }

        if (str_contains($errorLower, 'metadata') || str_contains($errorLower, '169.254')) {
            return SSRFException::metadataEndpoint($url);
        }

        if (str_contains($errorLower, 'protocol')) {
            // Extract protocol from URL
            $protocol = parse_url($url, PHP_URL_SCHEME) ?? 'unknown';
            return SSRFException::blockedProtocol($url, $protocol);
        }

        // Generic SSRF exception
        return new SSRFException($error, 'ssrf_blocked', $url);
    }
}
