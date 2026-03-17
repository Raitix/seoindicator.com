<?php

namespace App\Exceptions;

use Exception;

/**
 * Exception thrown when domain validation fails
 * 
 * @see SPEC-003-3
 */
class DomainValidationException extends Exception
{
    protected string $code;

    /**
     * Create a new domain validation exception
     * 
     * @param string $message Error message
     * @param string $code Error code (INVALID_FORMAT, SSRF_BLOCKED, DNS_NOT_FOUND, etc.)
     */
    public function __construct(string $message, string $code = 'VALIDATION_ERROR')
    {
        parent::__construct($message);
        $this->code = $code;
    }

    /**
     * Get the error code
     * 
     * @return string
     */
    public function getErrorCode(): string
    {
        return $this->code;
    }
}
