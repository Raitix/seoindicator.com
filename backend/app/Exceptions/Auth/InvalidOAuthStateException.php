<?php

namespace App\Exceptions\Auth;

use Exception;

/**
 * Exception thrown when OAuth state parameter is invalid or expired.
 * 
 * This is a CSRF protection measure - the state parameter must match
 * between the initial redirect and the callback.
 * 
 * @see GoogleOAuthService
 * @see TEST-25-7
 */
class InvalidOAuthStateException extends Exception
{
    /**
     * Create a new invalid OAuth state exception.
     */
    public function __construct(string $message = 'Invalid or expired OAuth state parameter', int $code = 400)
    {
        parent::__construct($message, $code);
    }
}
