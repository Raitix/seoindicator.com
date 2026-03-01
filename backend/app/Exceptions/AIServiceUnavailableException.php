<?php

namespace App\Exceptions;

use Exception;

/**
 * Exception thrown when AI service is unavailable.
 *
 * This exception is thrown when all AI providers fail
 * and no fallback is available. Results in HTTP 503 response.
 */
class AIServiceUnavailableException extends Exception
{
    /**
     * Create a new AIServiceUnavailableException instance.
     *
     * @param string $message The error message
     * @param int $code The error code
     * @param Exception|null $previous The previous exception
     */
    public function __construct(
        string $message = 'AI service is temporarily unavailable. Please try again later.',
        int $code = 0,
        ?Exception $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Get the HTTP status code for this exception.
     *
     * @return int
     */
    public function getStatus(): int
    {
        return 503;
    }
}
