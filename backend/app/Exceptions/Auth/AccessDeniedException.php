<?php

namespace App\Exceptions\Auth;

use Exception;

/**
 * Exception thrown when user denies OAuth authorization.
 * 
 * This occurs when the user clicks "Deny" or cancels
 * the Google OAuth consent screen.
 * 
 * @see GoogleOAuthService
 * @see TEST-25-7
 */
class AccessDeniedException extends Exception
{
    /**
     * Create a new access denied exception.
     */
    public function __construct(string $message = 'User denied OAuth authorization', int $code = 403)
    {
        parent::__construct($message, $code);
    }
}
