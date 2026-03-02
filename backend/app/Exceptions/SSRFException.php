<?php

namespace App\Exceptions;

use Exception;

/**
 * Exception thrown when an SSRF (Server-Side Request Forgery) attempt is detected.
 *
 * This exception is thrown by UrlValidatorService when a URL resolves to a
 * blocked IP address, localhost, private network, or other restricted resource.
 *
 * @see TASK-22-7
 * @see TASK-22-15
 */
class SSRFException extends Exception
{
    /**
     * The reason code for the SSRF block.
     */
    protected string $reason;

    /**
     * The original URL that was blocked.
     */
    protected string $blockedUrl;

    /**
     * Create a new SSRF exception.
     *
     * @param string $message The error message
     * @param string $reason The reason code (localhost, private_ip, metadata_endpoint, etc.)
     * @param string $blockedUrl The URL that was blocked
     * @param int $code The error code
     * @param \Throwable|null $previous The previous exception
     */
    public function __construct(
        string $message = 'URL blocked for security reasons',
        string $reason = 'ssrf_blocked',
        string $blockedUrl = '',
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);

        $this->reason = $reason;
        $this->blockedUrl = $blockedUrl;
    }

    /**
     * Get the reason code for the SSRF block.
     */
    public function getReason(): string
    {
        return $this->reason;
    }

    /**
     * Get the blocked URL.
     */
    public function getBlockedUrl(): string
    {
        return $this->blockedUrl;
    }

    /**
     * Create exception for localhost access.
     */
    public static function localhost(string $url): self
    {
        return new self(
            'URL blocked: localhost access denied',
            'localhost',
            $url
        );
    }

    /**
     * Create exception for private IP access.
     */
    public static function privateIp(string $url): self
    {
        return new self(
            'URL blocked: private IP access denied',
            'private_ip',
            $url
        );
    }

    /**
     * Create exception for cloud metadata endpoint.
     */
    public static function metadataEndpoint(string $url): self
    {
        return new self(
            'URL blocked: metadata endpoint access denied',
            'metadata_endpoint',
            $url
        );
    }

    /**
     * Create exception for blocked protocol.
     */
    public static function blockedProtocol(string $url, string $protocol): self
    {
        return new self(
            "Protocol not allowed: {$protocol}://",
            'blocked_protocol',
            $url
        );
    }

    /**
     * Create exception for internal domain.
     */
    public static function internalDomain(string $url): self
    {
        return new self(
            'URL blocked: internal domain access denied',
            'internal_domain',
            $url
        );
    }

    /**
     * Create exception for DNS rebinding.
     */
    public static function dnsRebinding(string $url): self
    {
        return new self(
            'URL blocked: DNS rebinding attempt detected',
            'dns_rebinding',
            $url
        );
    }

    /**
     * Get the exception context for logging.
     */
    public function context(): array
    {
        return [
            'reason' => $this->reason,
            'blocked_url' => $this->blockedUrl,
        ];
    }
}
