<?php

namespace App\Exceptions;

use Exception;

/**
 * Exception thrown when all AI providers are unavailable.
 *
 * This exception indicates that the AI suggestion service
 * cannot fulfill requests because all configured providers
 * have failed or are unavailable.
 */
class AIServiceUnavailableException extends Exception
{
    /**
     * Create a new AIServiceUnavailableException instance.
     *
     * @param string $message The exception message
     * @param int $code The exception code
     * @param \Throwable|null $previous The previous exception
     */
    public function __construct(
        string $message = 'All AI providers are currently unavailable',
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
