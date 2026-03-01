<?php

namespace App\Exceptions\Auth;

use Exception;

/**
 * Exception thrown when OAuth authentication with Google fails.
 * 
 * This can occur due to:
 * - Invalid authorization code
 * - Network errors
 * - Google API errors
 * - Rate limiting
 * 
 * @see GoogleOAuthService
 * @see TEST-25-7
 */
class OAuthFailedException extends Exception
{
    /**
     * Create a new OAuth failed exception.
     */
    public function __construct(string $message = 'Google OAuth authentication failed', int $code = 400)
    {
        parent::__construct($message, $code);
    }
}
