<?php

namespace App\Exceptions;

use Exception;

/**
 * Exception for timeout errors.
 * 
 * TASK-16-12: Error Handling & User Feedback
 */
class TimeoutException extends Exception
{
    public function __construct(string $message = 'Request timed out', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
