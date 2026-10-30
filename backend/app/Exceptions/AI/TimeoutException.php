<?php

namespace App\Exceptions\AI;

use RuntimeException;

/**
 * Exception thrown when an AI API request times out.
 */
class TimeoutException extends RuntimeException
{
    /**
     * Create a new timeout exception.
     * 
     * @param string $message The exception message
     * @param int $code The exception code
     * @param \Throwable|null $previous Previous exception
     */
    public function __construct(
        string $message = 'AI request timed out',
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
