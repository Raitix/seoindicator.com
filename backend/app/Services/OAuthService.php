<?php

namespace App\Services;

use App\Models\GSCConnection;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * OAuth Service for Google Search Console API Integration.
 *
 * Handles the complete OAuth 2.0 flow for Google Search Console API access:
 * - Authorization URL generation
 * - Authorization code exchange for tokens
 * - Token refresh on expiry
 * - Token revocation
 *
 * @see https://developers.google.com/identity/protocols/oauth2/web/server
 */
class OAuthService
{
    /**
     * Google OAuth 2.0 endpoints.
     */
    protected const AUTH_URL = 'https://accounts.google.com/o/oauth2/v2/auth';
    protected const TOKEN_URL = 'https://oauth2.googleapis.com/token';
    protected const REVOKE_URL = 'https://oauth2.googleapis.com/revoke';

    /**
     * GSC API scopes required for read-only access.
     */
    protected const SCOPES = [
        'https://www.googleapis.com/auth/webmasters.readonly',
    ];

    /**
     * Token expiration buffer in seconds (refresh 5 minutes before expiry).
     */
    protected const REFRESH_BUFFER_SECONDS = 300;

    /**
     * Valid redirect URI patterns for security validation.
     *
     * @var array<string>
     */
    protected array $validRedirectUris = [];

    /**
     * Create a new OAuthService instance.
     */
    public function __construct()
    {
        $this->validRedirectUris = $this->loadValidRedirectUris();
    }

    /**
     * Load valid redirect URIs from configuration.
     *
     * @return array<string>
     */
    protected function loadValidRedirectUris(): array
    {
        $uris = config('services.google.redirect_uris', []);
        
        // Add common development URIs
        if (app()->environment('local', 'testing')) {
            $uris = array_merge($uris, [
                'http://localhost:3000/gsc/callback',
                'http://localhost:8000/gsc/callback',
                'http://127.0.0.1:3000/gsc/callback',
                'http://127.0.0.1:8000/gsc/callback',
            ]);
        }

        return $uris;
    }

    /**
     * Generate the OAuth authorization URL for Google Search Console.
     *
     * @param string $redirectUri The URI to redirect to after authorization
     * @param string|null $state Optional state parameter for CSRF protection
     * @return string The full authorization URL
     * @throws RuntimeException If redirect URI is not valid
     */
    public function getAuthorizationUrl(string $redirectUri, ?string $state = null): string
    {
        $this->validateRedirectUri($redirectUri);

        $state = $state ?? Str::random(40);
        
        $params = http_build_query([
            'client_id' => $this->getClientId(),
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => implode(' ', self::SCOPES),
            'access_type' => 'offline',
            'prompt' => 'consent',
            'state' => $state,
            'include_granted_scopes' => 'true',
        ]);

        return self::AUTH_URL . '?' . $params;
    }

    /**
     * Handle OAuth callback and exchange authorization code for tokens.
     *
     * @param string $code The authorization code from Google
     * @param string $redirectUri The same redirect URI used in authorization
     * @param User $user The user to associate the connection with
     * @param string $propertyUrl The GSC property URL
     * @return GSCConnection The created or updated connection
     * @throws RuntimeException If token exchange fails
     */
    public function handleCallback(string $code, string $redirectUri, User $user, string $propertyUrl): GSCConnection
    {
        $this->validateRedirectUri($redirectUri);

        $tokenData = $this->exchangeCodeForToken($code, $redirectUri);

        if (!isset($tokenData['access_token']) || !isset($tokenData['refresh_token'])) {
            throw new RuntimeException('Invalid token response: missing required tokens');
        }

        // Extract site URL from property URL
        $siteUrl = $this->extractSiteUrl($propertyUrl);

        // Create or update connection
        return GSCConnection::updateOrCreate(
            [
                'user_id' => $user->id,
                'property_url' => $propertyUrl,
            ],
            [
                'site_url' => $siteUrl,
                'access_token' => $tokenData['access_token'],
                'refresh_token' => $tokenData['refresh_token'],
                'token_expires_at' => now()->addSeconds($tokenData['expires_in']),
                'status' => GSCConnection::STATUS_ACTIVE,
            ]
        );
    }

    /**
     * Refresh an expired access token using the refresh token.
     *
     * @param GSCConnection $connection The connection to refresh
     * @return bool True if refresh was successful
     * @throws RuntimeException If refresh fails
     */
    public function refreshAccessToken(GSCConnection $connection): bool
    {
        if (empty($connection->refresh_token)) {
            throw new RuntimeException('Cannot refresh token: no refresh token available');
        }

        if (!$connection->isActive()) {
            throw new RuntimeException('Cannot refresh token: connection is not active');
        }

        $response = Http::asForm()->post(self::TOKEN_URL, [
            'refresh_token' => $connection->refresh_token,
            'client_id' => $this->getClientId(),
            'client_secret' => $this->getClientSecret(),
            'grant_type' => 'refresh_token',
        ]);

        if (!$response->successful()) {
            $error = $response->json('error', 'Unknown error');
            Log::error('OAuth token refresh failed', [
                'connection_id' => $connection->id,
                'error' => $error,
                'status' => $response->status(),
            ]);

            if ($response->status() === 400 && $error === 'invalid_grant') {
                $connection->markAsExpired();
            }

            throw new RuntimeException("Token refresh failed: {$error}");
        }

        $tokenData = $response->json();

        $connection->update([
            'access_token' => $tokenData['access_token'],
            'token_expires_at' => now()->addSeconds($tokenData['expires_in']),
            'status' => GSCConnection::STATUS_ACTIVE,
        ]);

        return true;
    }

    /**
     * Refresh access token if it's expired or about to expire.
     *
     * @param GSCConnection $connection The connection to check and refresh
     * @return bool True if token was refreshed or still valid
     */
    public function ensureValidToken(GSCConnection $connection): bool
    {
        if (!$connection->isActive()) {
            return false;
        }

        // Check if token needs refresh (expired or expiring within buffer)
        if ($connection->isTokenExpired() || 
            $connection->token_expires_at->subSeconds(self::REFRESH_BUFFER_SECONDS)->isPast()) {
            try {
                return $this->refreshAccessToken($connection);
            } catch (RuntimeException $e) {
                Log::warning('Failed to refresh token during validation', [
                    'connection_id' => $connection->id,
                    'error' => $e->getMessage(),
                ]);
                return false;
            }
        }

        return true;
    }

    /**
     * Revoke OAuth access for a connection.
     *
     * @param GSCConnection $connection The connection to revoke
     * @return bool True if revocation was successful
     */
    public function revokeAccess(GSCConnection $connection): bool
    {
        if (empty($connection->access_token)) {
            $connection->markAsRevoked();
            return true;
        }

        $response = Http::asForm()->post(self::REVOKE_URL, [
            'token' => $connection->access_token,
        ]);

        // Google returns 200 for successful revocation
        // Also accept 400 as token may already be revoked
        if ($response->successful() || $response->status() === 400) {
            $connection->markAsRevoked();
            return true;
        }

        Log::error('OAuth token revocation failed', [
            'connection_id' => $connection->id,
            'status' => $response->status(),
            'error' => $response->json('error', 'Unknown error'),
        ]);

        return false;
    }

    /**
     * Validate that a redirect URI is in the allowed list.
     *
     * @param string $redirectUri The redirect URI to validate
     * @throws RuntimeException If the redirect URI is not valid
     */
    protected function validateRedirectUri(string $redirectUri): void
    {
        // Parse the redirect URI to normalize it
        $parsed = parse_url($redirectUri);
        
        if ($parsed === false) {
            throw new RuntimeException("Invalid redirect URI format: {$redirectUri}");
        }

        // Check against allowed patterns
        foreach ($this->validRedirectUris as $allowedUri) {
            if ($this->uriMatches($redirectUri, $allowedUri)) {
                return;
            }
        }

        throw new RuntimeException("Redirect URI not allowed: {$redirectUri}");
    }

    /**
     * Check if a URI matches an allowed pattern.
     *
     * @param string $uri The URI to check
     * @param string $pattern The allowed pattern (may contain wildcards)
     * @return bool
     */
    protected function uriMatches(string $uri, string $pattern): bool
    {
        // Exact match
        if ($uri === $pattern) {
            return true;
        }

        // Pattern ends with wildcard
        if (str_ends_with($pattern, '*')) {
            return str_starts_with($uri, substr($pattern, 0, -1));
        }

        return false;
    }

    /**
     * Exchange authorization code for access and refresh tokens.
     *
     * @param string $code The authorization code
     * @param string $redirectUri The redirect URI used in authorization
     * @return array The token response data
     * @throws RuntimeException If exchange fails
     */
    protected function exchangeCodeForToken(string $code, string $redirectUri): array
    {
        $response = Http::asForm()->post(self::TOKEN_URL, [
            'code' => $code,
            'client_id' => $this->getClientId(),
            'client_secret' => $this->getClientSecret(),
            'redirect_uri' => $redirectUri,
            'grant_type' => 'authorization_code',
        ]);

        if (!$response->successful()) {
            $error = $response->json('error_description', $response->json('error', 'Unknown error'));
            
            Log::error('OAuth token exchange failed', [
                'error' => $error,
                'status' => $response->status(),
            ]);

            throw new RuntimeException("Token exchange failed: {$error}");
        }

        return $response->json();
    }

    /**
     * Extract the site URL from a GSC property URL.
     *
     * @param string $propertyUrl The GSC property URL
     * @return string The site URL (domain)
     */
    protected function extractSiteUrl(string $propertyUrl): string
    {
        $parsed = parse_url($propertyUrl);
        
        return $parsed['host'] ?? $parsed['path'] ?? $propertyUrl;
    }

    /**
     * Get the Google OAuth client ID.
     *
     * @return string
     * @throws RuntimeException If client ID is not configured
     */
    protected function getClientId(): string
    {
        $clientId = config('services.google.client_id');

        if (empty($clientId)) {
            throw new RuntimeException('Google OAuth client ID is not configured');
        }

        return $clientId;
    }

    /**
     * Get the Google OAuth client secret.
     *
     * @return string
     * @throws RuntimeException If client secret is not configured
     */
    protected function getClientSecret(): string
    {
        $clientSecret = config('services.google.client_secret');

        if (empty($clientSecret)) {
            throw new RuntimeException('Google OAuth client secret is not configured');
        }

        return $clientSecret;
    }

    /**
     * Get the list of valid redirect URIs.
     *
     * @return array<string>
     */
    public function getValidRedirectUris(): array
    {
        return $this->validRedirectUris;
    }

    /**
     * Check if a connection needs token refresh.
     *
     * @param GSCConnection $connection
     * @return bool
     */
    public function needsRefresh(GSCConnection $connection): bool
    {
        if (!$connection->isActive()) {
            return false;
        }

        return $connection->isTokenExpired() || 
               $connection->token_expires_at->subSeconds(self::REFRESH_BUFFER_SECONDS)->isPast();
    }
}
