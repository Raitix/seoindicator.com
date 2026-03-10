<?php

namespace App\Services\LocalSEO\Contracts;

use App\Models\BusinessLocation;

/**
 * Google OAuth Service Interface
 *
 * @Task TASK-35-7, TASK-35-13
 * @Description Controller-facing OAuth flow helpers + token lifecycle contract for GBP integration
 */
interface GoogleOAuthServiceInterface
{
    /**
     * Get the Google OAuth authorization URL for GBP connection.
     *
     * @param int $locationId
     * @return array{authorization_url: string, state: string}
     */
    public function getAuthorizationUrl(int $locationId): array;

    /**
     * Handle the OAuth callback from Google.
     *
     * @param string $code
     * @param string $state
     * @return array{success: bool, location_id: int, redirect?: string, error?: string}
     */
    public function handleCallback(string $code, string $state): array;

    /**
     * Disconnect a location from Google Business Profile by identifier.
     *
     * @param int $locationId
     * @return void
     */
    public function disconnect(int $locationId): void;

    /**
     * Disconnect a location from Google Business Profile using the loaded model.
     *
     * @param BusinessLocation $location
     * @return void
     */
    public function disconnectLocation(BusinessLocation $location): void;

    /**
     * Validate the OAuth state parameter.
     *
     * @param string $state
     * @return bool
     */
    public function validateState(string $state): bool;

    /**
     * Refresh the access token for a location by identifier.
     *
     * @param int $locationId
     * @return bool
     */
    public function refreshToken(int $locationId): bool;

    /**
     * Refresh an access token using a refresh token.
     *
     * @param string $refreshToken
     * @return array{access_token: string, refresh_token: string, expires_at: string}|null
     */
    public function refreshAccessToken(string $refreshToken): ?array;

    /**
     * Encrypt and persist GBP OAuth tokens on the provided location.
     *
     * @param BusinessLocation $location
     * @param array{access_token: string, refresh_token: string, expires_at: string} $tokens
     * @return void
     */
    public function storeTokens(BusinessLocation $location, array $tokens): void;

    /**
     * Return a valid access token for the provided location.
     *
     * @param BusinessLocation $location
     * @return string|null
     */
    public function getValidAccessToken(BusinessLocation $location): ?string;

    /**
     * Check if the OAuth token is valid.
     *
     * @param int $locationId
     * @return bool
     */
    public function isTokenValid(int $locationId): bool;
}
