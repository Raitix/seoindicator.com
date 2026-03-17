<?php

namespace App\Exceptions;

use App\Services\ToolError;
use Exception;

/**
 * Exception for non-retryable errors.
 * 
 * TASK-16-12: Error Handling & User Feedback
 */
class NonRetryableException extends Exception
{
    protected ToolError $toolError;

    public function __construct(ToolError $error, ?string $message = null)
    {
        $this->toolError = $error;
        parent::__construct($message ?? $error->message);
    }

    /**
     * Get the tool error.
     */
    public function getToolError(): ToolError
    {
        return $this->toolError;
    }
}
