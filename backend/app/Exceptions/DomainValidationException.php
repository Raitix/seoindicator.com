<?php

namespace App\Exceptions;

use Exception;

/**
 * Exception thrown when domain validation fails.
 */
class DomainValidationException extends Exception
{
    protected string $code;

    /**
     * Create a new domain validation exception.
     */
    public function __construct(string $message, string $code, int $httpStatus = 400)
    {
        parent::__construct($message, $httpStatus);
        $this->code = $code;
    }

    /**
     * Get the error code.
     */
    public function getErrorCode(): string
    {
        return $this->code;
    }

    /**
     * Convert exception to array.
     */
    public function toArray(): array
    {
        return [
            'valid' => false,
            'message' => $this->getMessage(),
            'code' => $this->code,
        ];
    }
}
