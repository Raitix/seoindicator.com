<?php

namespace App\Exceptions\Auth;

use Exception;

/**
 * Exception thrown when required OAuth scopes are not granted.
 * 
 * Google OAuth requires at least 'email' and 'profile' scopes
 * for user authentication.
 * 
 * @see GoogleOAuthService
 * @see TEST-25-7
 */
class InsufficientScopeException extends Exception
{
    /**
     * Create a new insufficient scope exception.
     */
    public function __construct(string $message = 'Required OAuth scopes not granted', int $code = 400)
    {
        parent::__construct($message, $code);
    }
}
