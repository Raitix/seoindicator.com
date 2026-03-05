<?php

namespace App\Exceptions\AI;

use RuntimeException;

/**
 * Exception thrown when the AI service is unavailable.
 */
class ServiceUnavailableException extends RuntimeException
{
    /**
     * Create a new service unavailable exception.
     * 
     * @param string $message The exception message
     * @param int $code The exception code
     * @param \Throwable|null $previous Previous exception
     */
    public function __construct(
        string $message = 'AI service is unavailable',
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
