<?php

namespace App\Services\LocalSEO\Contracts;

/**
 * Google OAuth Service Interface
 * 
 * @Task TASK-35-13
 * @Description Interface for GoogleOAuthService mocking
 */
interface GoogleOAuthServiceInterface
{
    /**
     * Get the Google OAuth authorization URL for GBP connection
     *
     * @param int $locationId
     * @return array{authorization_url: string, state: string}
     */
    public function getAuthorizationUrl(int $locationId): array;

    /**
     * Handle the OAuth callback from Google
     *
     * @param string $code
     * @param string $state
     * @return array{success: bool, location_id: int, redirect?: string, error?: string}
     */
    public function handleCallback(string $code, string $state): array;

    /**
     * Disconnect a location from Google Business Profile
     *
     * @param int $locationId
     * @return void
     */
    public function disconnect(int $locationId): void;

    /**
     * Validate the OAuth state parameter
     *
     * @param string $state
     * @return bool
     */
    public function validateState(string $state): bool;

    /**
     * Refresh the access token for a location
     *
     * @param int $locationId
     * @return bool
     */
    public function refreshToken(int $locationId): bool;

    /**
     * Check if the OAuth token is valid
     *
     * @param int $locationId
     * @return bool
     */
    public function isTokenValid(int $locationId): bool;
}
