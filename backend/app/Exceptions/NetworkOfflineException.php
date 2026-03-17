<?php

namespace App\Exceptions;

use Exception;

/**
 * Exception for network offline errors.
 * 
 * TASK-16-12: Error Handling & User Feedback
 */
class NetworkOfflineException extends Exception
{
    public function __construct(string $message = 'Network is offline', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
