<?php

namespace App\Services\LocalSEO;

use App\Models\BusinessLocation;
use App\Services\LocalSEO\Contracts\GoogleOAuthServiceInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Google OAuth Service
 * 
 * @Task TASK-35-7, TASK-35-13
 * @Description Service for Google Business Profile OAuth integration
 */
class GoogleOAuthService implements GoogleOAuthServiceInterface
{
    /**
     * The Google OAuth client ID.
     */
    protected string $clientId;

    /**
     * The Google OAuth client secret.
     */
    protected string $clientSecret;

    /**
     * The OAuth redirect URI.
     */
    protected string $redirectUri;

    /**
     * The OAuth scopes required.
     */
    protected array $scopes = [
        'https://www.googleapis.com/auth/business.manage',
    ];

    /**
     * Create a new Google OAuth service instance.
     */
    public function __construct()
    {
        $this->clientId = config('services.google.client_id');
        $this->clientSecret = config('services.google.client_secret');
        $this->redirectUri = config('services.google.redirect');
    }

    /**
     * Get the Google OAuth authorization URL for GBP connection.
     *
     * @param int $locationId
     * @return array{authorization_url: string, state: string}
     */
    public function getAuthorizationUrl(int $locationId): array
    {
        // Generate a secure state parameter with location binding
        $state = $this->generateState($locationId);

        // Build the authorization URL
        $params = [
            'client_id' => $this->clientId,
            'redirect_uri' => $this->getCallbackUrl($locationId),
            'response_type' => 'code',
            'scope' => implode(' ', $this->scopes),
            'state' => $state,
            'access_type' => 'offline',
            'prompt' => 'consent',
        ];

        $authorizationUrl = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);

        return [
            'authorization_url' => $authorizationUrl,
            'state' => $state,
        ];
    }

    /**
     * Handle the OAuth callback from Google.
     *
     * @param string $code
     * @param string $state
     * @return array{success: bool, location_id: int, redirect?: string, error?: string}
     */
    public function handleCallback(string $code, string $state): array
    {
        // Validate and extract location ID from state
        $locationId = $this->validateStateAndGetLocationId($state);

        if (!$locationId) {
            return [
                'success' => false,
                'location_id' => 0,
                'error' => 'Invalid state parameter',
            ];
        }

        $location = BusinessLocation::find($locationId);

        if (!$location) {
            return [
                'success' => false,
                'location_id' => $locationId,
                'error' => 'Location not found',
            ];
        }

        try {
            // Exchange authorization code for tokens
            $tokens = $this->exchangeCodeForTokens($code, $locationId);

            if (!$tokens) {
                return [
                    'success' => false,
                    'location_id' => $locationId,
                    'error' => 'Failed to exchange authorization code',
                ];
            }

            // Store tokens in the location
            $location->gbp_access_token = encrypt($tokens['access_token']);
            $location->gbp_refresh_token = encrypt($tokens['refresh_token']);
            $location->gbp_token_expires_at = now()->addSeconds($tokens['expires_in']);
            $location->gbp_connected = true;
            $location->save();

            // Fetch initial GBP data (would be done in a job in production)
            // $this->fetchInitialData($location);

            return [
                'success' => true,
                'location_id' => $locationId,
            ];
        } catch (\Exception $e) {
            \Log::error('GBP OAuth callback failed', [
                'location_id' => $locationId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'location_id' => $locationId,
                'error' => 'Connection failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Disconnect a location from Google Business Profile.
     *
     * @param int $locationId
     * @return void
     */
    public function disconnect(int $locationId): void
    {
        $location = BusinessLocation::find($locationId);

        if (!$location) {
            return;
        }

        // Attempt to revoke the token with Google
        try {
            if ($location->gbp_access_token) {
                $accessToken = decrypt($location->gbp_access_token);
                $this->revokeToken($accessToken);
            }
        } catch (\Exception $e) {
            \Log::warning('Failed to revoke Google token during disconnect', [
                'location_id' => $locationId,
                'error' => $e->getMessage(),
            ]);
        }

        // Clear stored tokens
        $location->gbp_access_token = null;
        $location->gbp_refresh_token = null;
        $location->gbp_token_expires_at = null;
        $location->gbp_connected = false;
        $location->gbp_place_id = null;
        $location->save();

        // Clear state cache
        Cache::forget("gbp_state_{$locationId}");
    }

    /**
     * Validate the OAuth state parameter.
     *
     * @param string $state
     * @return bool
     */
    public function validateState(string $state): bool
    {
        // Check if state exists in cache
        $cached = Cache::get("gbp_state_data_{$state}");

        return $cached !== null;
    }

    /**
     * Refresh the access token for a location.
     *
     * @param int $locationId
     * @return bool
     */
    public function refreshToken(int $locationId): bool
    {
        $location = BusinessLocation::find($locationId);

        if (!$location || !$location->gbp_refresh_token) {
            return false;
        }

        try {
            $refreshToken = decrypt($location->gbp_refresh_token);
            $tokens = $this->refreshAccessToken($refreshToken);

            if (!$tokens) {
                return false;
            }

            $location->gbp_access_token = encrypt($tokens['access_token']);
            $location->gbp_token_expires_at = now()->addSeconds($tokens['expires_in']);
            $location->save();

            return true;
        } catch (\Exception $e) {
            \Log::error('Failed to refresh GBP token', [
                'location_id' => $locationId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Check if the OAuth token is valid.
     *
     * @param int $locationId
     * @return bool
     */
    public function isTokenValid(int $locationId): bool
    {
        $location = BusinessLocation::find($locationId);

        if (!$location || !$location->gbp_connected || !$location->gbp_access_token) {
            return false;
        }

        // Check if token has expired
        if ($location->gbp_token_expires_at && $location->gbp_token_expires_at->isPast()) {
            // Try to refresh
            return $this->refreshToken($locationId);
        }

        return true;
    }

    /**
     * Generate a secure state parameter.
     *
     * @param int $locationId
     * @return string
     */
    protected function generateState(int $locationId): string
    {
        $state = Str::random(40);

        // Store state with location binding
        Cache::put("gbp_state_data_{$state}", [
            'location_id' => $locationId,
            'created_at' => now()->toISOString(),
        ], now()->addMinutes(10));

        // Also store by location ID for validation
        Cache::put("gbp_state_{$locationId}", $state, now()->addMinutes(10));

        return $state;
    }

    /**
     * Validate state and extract location ID.
     *
     * @param string $state
     * @return int|null
     */
    protected function validateStateAndGetLocationId(string $state): ?int
    {
        $cached = Cache::get("gbp_state_data_{$state}");

        if (!$cached) {
            return null;
        }

        // Remove the state after use
        Cache::forget("gbp_state_data_{$state}");

        return $cached['location_id'];
    }

    /**
     * Get the callback URL for a location.
     *
     * @param int $locationId
     * @return string
     */
    protected function getCallbackUrl(int $locationId): string
    {
        return config('app.url') . "/api/v1/locations/{$locationId}/gbp-callback";
    }

    /**
     * Exchange authorization code for tokens.
     *
     * @param string $code
     * @param int $locationId
     * @return array|null
     */
    protected function exchangeCodeForTokens(string $code, int $locationId): ?array
    {
        // This would make an HTTP request to Google's token endpoint
        // Placeholder implementation - actual implementation would use HTTP client
        return null;
    }

    /**
     * Refresh the access token.
     *
     * @param string $refreshToken
     * @return array{access_token: string, refresh_token: string, expires_at: string}|null
     */
    public function refreshAccessToken(string $refreshToken): ?array
    {
        // TODO TASK-35-7: implement Google token refresh flow against oauth2.googleapis.com/token
        return null;
    }

    /**
     * Encrypt and persist GBP tokens using the task-level gbp_tokens payload.
     *
     * @param BusinessLocation $location
     * @param array{access_token: string, refresh_token: string, expires_at: string} $tokens
     * @return void
     */
    public function storeTokens(BusinessLocation $location, array $tokens): void
    {
        $payload = [
            'access_token' => $tokens['access_token'] ?? null,
            'refresh_token' => $tokens['refresh_token'] ?? null,
            'expires_at' => $tokens['expires_at'] ?? null,
        ];

        $location->gbp_tokens = encrypt(json_encode($payload));
        $location->gbp_connected = true;
        $location->save();
    }

    /**
     * Return a valid access token for the location, refreshing when required.
     *
     * @param BusinessLocation $location
     * @return string|null
     */
    public function getValidAccessToken(BusinessLocation $location): ?string
    {
        $stored = $this->decodeStoredTokens($location);

        if (!$stored || empty($stored['access_token'])) {
            return null;
        }

        $expiresAt = !empty($stored['expires_at']) ? \Illuminate\Support\Carbon::parse($stored['expires_at']) : null;

        if ($expiresAt && $expiresAt->isFuture()) {
            return $stored['access_token'];
        }

        if (empty($stored['refresh_token'])) {
            return null;
        }

        $refreshed = $this->refreshAccessToken($stored['refresh_token']);

        if (!$refreshed || empty($refreshed['access_token'])) {
            return null;
        }

        $this->storeTokens($location, $refreshed);

        return $refreshed['access_token'];
    }

    /**
     * Disconnect the provided location from GBP and clear stored token payload.
     *
     * @param BusinessLocation $location
     * @return void
     */
    public function disconnectLocation(BusinessLocation $location): void
    {
        $stored = $this->decodeStoredTokens($location);

        try {
            if (!empty($stored['access_token'])) {
                $this->revokeToken($stored['access_token']);
            }
        } catch (\Throwable $e) {
            \Log::warning('Failed to revoke Google token during model disconnect', [
                'location_id' => $location->id,
                'error' => $e->getMessage(),
            ]);
        }

        $location->gbp_tokens = null;
        $location->gbp_connected = false;
        $location->save();
    }

    /**
     * Decode the encrypted gbp_tokens payload from the location.
     *
     * @param BusinessLocation $location
     * @return array<string, mixed>|null
     */
    protected function decodeStoredTokens(BusinessLocation $location): ?array
    {
        if (empty($location->gbp_tokens)) {
            return null;
        }

        try {
            $decoded = json_decode(decrypt($location->gbp_tokens), true);

            return is_array($decoded) ? $decoded : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Revoke a token with Google.
     *
     * @param string $token
     * @return bool
     */
    protected function revokeToken(string $token): bool
    {
        // TODO TASK-35-7: implement Google revoke endpoint call.
        return true;
    }
}
