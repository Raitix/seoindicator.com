<?php

namespace App\Exceptions;

use Exception;

/**
 * Exception thrown when domain validation fails
 * 
 * Task: SPEC-003-3 - Domain Validation Service
 */
class DomainValidationException extends Exception
{
    /**
     * Validation error code
     */
    protected string $errorCode;

    /**
     * The domain that failed validation
     */
    protected string $domain;

    /**
     * Create a new domain validation exception
     *
     * @param string $message Error message
     * @param string $errorCode Error code (INVALID_FORMAT, SSRF_BLOCKED, DNS_NOT_FOUND, etc.)
     * @param string $domain The domain that failed validation
     */
    public function __construct(string $message, string $errorCode, string $domain = '')
    {
        parent::__construct($message);
        $this->errorCode = $errorCode;
        $this->domain = $domain;
    }

    /**
     * Get the error code
     *
     * @return string
     */
    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    /**
     * Get the domain that failed validation
     *
     * @return string
     */
    public function getDomain(): string
    {
        return $this->domain;
    }

    /**
     * Convert to array for API responses
     *
     * @return array{valid: bool, message: string, code: string, domain: string}
     */
    public function toArray(): array
    {
        return [
            'valid' => false,
            'message' => $this->getMessage(),
            'code' => $this->errorCode,
            'domain' => $this->domain,
        ];
    }
}
