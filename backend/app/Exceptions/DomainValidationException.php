<?php

namespace App\Exceptions;

use Exception;

/**
 * Exception for domain validation failures
 */
class DomainValidationException extends Exception
{
    protected string $code;

    protected ?string $domain;

    protected ?string $ip;

    /**
     * @param string $message Error message
     * @param string $code Error code (INVALID_FORMAT, SSRF_BLOCKED, DNS_NOT_FOUND, etc.)
     * @param string|null $domain The domain that failed validation
     * @param string|null $ip The resolved IP (if any)
     */
    public function __construct(
        string $message,
        string $code,
        ?string $domain = null,
        ?string $ip = null
    ) {
        parent::__construct($message);
        $this->code = $code;
        $this->domain = $domain;
        $this->ip = $ip;
    }

    /**
     * Get the error code
     */
    public function getErrorCode(): string
    {
        return $this->code;
    }

    /**
     * Get the domain that failed validation
     */
    public function getDomain(): ?string
    {
        return $this->domain;
    }

    /**
     * Get the resolved IP
     */
    public function getIp(): ?string
    {
        return $this->ip;
    }

    /**
     * Convert to array for API responses
     */
    public function toArray(): array
    {
        return [
            'valid' => false,
            'domain' => $this->domain,
            'ip' => $this->ip,
            'message' => $this->getMessage(),
            'code' => $this->code,
        ];
    }
}
