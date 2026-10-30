<?php

namespace App\Exceptions\Auth;

use Exception;

/**
 * Exception thrown when OAuth configuration is missing or invalid.
 * 
 * This can occur when:
 * - Google client ID is not configured
 * - Google client secret is not configured
 * - Redirect URI is not configured
 * - Socialite is not installed
 * 
 * @see GoogleOAuthService
 * @see TEST-25-7
 */
class ConfigurationException extends Exception
{
    /**
     * Create a new configuration exception.
     */
    public function __construct(string $message = 'OAuth configuration is missing or invalid', int $code = 500)
    {
        parent::__construct($message, $code);
    }
}
