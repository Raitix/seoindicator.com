<?php

namespace App\Services\LocalSEO;

use App\Models\BusinessLocation;
use App\Services\LocalSEO\Contracts\GoogleOAuthServiceInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;
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
    protected ?string $clientId = null;

    /**
     * The Google OAuth client secret.
     */
    protected ?string $clientSecret = null;

    /**
     * The OAuth redirect URI.
     */
    protected ?string $redirectUri = null;

    /**
     * The OAuth scopes required.
     */
    protected array $scopes = [
        'https://www.googleapis.com/auth/business.manage',
    ];

    /**
     * Google's OAuth token endpoint.
     */
    protected string $tokenEndpoint = 'https://oauth2.googleapis.com/token';

    /**
     * Google's token revocation endpoint.
     */
    protected string $revokeEndpoint = 'https://oauth2.googleapis.com/revoke';

    /**
     * Create a new Google OAuth service instance.
     */
    public function __construct()
    {
        $this->clientId = $this->readConfigString('services.google.client_id');
        $this->clientSecret = $this->readConfigString('services.google.client_secret');
        $this->redirectUri = $this->readConfigString('services.google.redirect');
    }

    /**
     * Get the Google OAuth authorization URL for GBP connection.
     *
     * @param int $locationId
     * @return array{authorization_url: string, state: string}
     */
    public function getAuthorizationUrl(int $locationId): array
    {
        $this->ensureOAuthConfigured();

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

            // Store tokens using the new gbp_tokens column
            $this->storeTokens($location, $tokens);

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

        $this->disconnectLocation($location);
    }

    /**
     * Validate the OAuth state parameter.
     *
     * @param string $state
     * @return bool
     */
    public function validateState(string $state): bool
    {
        return Session::get('gbp_oauth_state') === $state;
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

        if (!$location) {
            return false;
        }

        $stored = $this->decodeStoredTokens($location);

        if (!$stored || empty($stored['refresh_token'])) {
            return false;
        }

        try {
            $tokens = $this->refreshAccessToken($stored['refresh_token']);

            if (!$tokens) {
                return false;
            }

            $this->storeTokens($location, $tokens);

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

        if (!$location || !$location->gbp_connected) {
            return false;
        }

        $token = $this->getValidAccessToken($location);

        return $token !== null;
    }

    /**
     * Generate a secure state parameter and store in session.
     *
     * @param int $locationId
     * @return string
     */
    protected function generateState(int $locationId): string
    {
        // Generate high-entropy random state (40+ characters)
        $state = Str::random(40);

        // Store state and location ID in session for CSRF validation
        Session::put('gbp_oauth_state', $state);
        Session::put('gbp_oauth_location_id', $locationId);

        return $state;
    }

    /**
     * Validate state and extract location ID from session.
     *
     * @param string $state
     * @return int|null
     */
    protected function validateStateAndGetLocationId(string $state): ?int
    {
        // Validate the state matches what we stored in session
        $storedState = Session::get('gbp_oauth_state');

        if ($storedState !== $state) {
            return null;
        }

        // Get the location ID from session
        $locationId = Session::get('gbp_oauth_location_id');

        // Clear the session state after use (single-use)
        Session::forget(['gbp_oauth_state', 'gbp_oauth_location_id']);

        return $locationId;
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
     * Read a trimmed string config value.
     */
    protected function readConfigString(string $key): ?string
    {
        $value = config($key);

        if (!is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value !== '' ? $value : null;
    }

    /**
     * Determine whether OAuth credentials are available.
     */
    protected function hasOAuthCredentials(): bool
    {
        return $this->clientId !== null && $this->clientSecret !== null;
    }

    /**
     * Fail gracefully when OAuth credentials are unavailable.
     */
    protected function ensureOAuthConfigured(): void
    {
        if ($this->hasOAuthCredentials()) {
            return;
        }

        throw new \RuntimeException('Google Business Profile OAuth is not configured.');
    }

    /**
     * Exchange authorization code for tokens.
     *
     * @param string $code
     * @param int $locationId
     * @return array{access_token: string, refresh_token: string, expires_at: string}|null
     */
    protected function exchangeCodeForTokens(string $code, int $locationId): ?array
    {
        $this->ensureOAuthConfigured();

        try {
            $response = Http::asForm()->post($this->tokenEndpoint, [
                'grant_type' => 'authorization_code',
                'code' => $code,
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'redirect_uri' => $this->getCallbackUrl($locationId),
            ]);

            if (!$response->successful()) {
                \Log::error('Google OAuth token exchange failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return null;
            }

            $data = $response->json();

            return $this->normalizeTokenResponse($data);
        } catch (\Exception $e) {
            \Log::error('Google OAuth token exchange exception', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Refresh the access token.
     *
     * @param string $refreshToken
     * @return array{access_token: string, refresh_token: string, expires_at: string}|null
     */
    public function refreshAccessToken(string $refreshToken): ?array
    {
        if (!$this->hasOAuthCredentials()) {
            return null;
        }

        try {
            $response = Http::asForm()->post($this->tokenEndpoint, [
                'grant_type' => 'refresh_token',
                'refresh_token' => $refreshToken,
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
            ]);

            if (!$response->successful()) {
                \Log::error('Google OAuth token refresh failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return null;
            }

            $data = $response->json();

            // Google may not return a new refresh token, so keep the original
            $tokens = $this->normalizeTokenResponse($data);
            
            // Preserve the original refresh token if Google didn't return a new one
            if (empty($tokens['refresh_token'])) {
                $tokens['refresh_token'] = $refreshToken;
            }

            return $tokens;
        } catch (\Exception $e) {
            \Log::error('Google OAuth token refresh exception', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Normalize Google's token response to our internal format.
     *
     * @param array $data
     * @return array{access_token: string, refresh_token: string, expires_at: string}
     */
    protected function normalizeTokenResponse(array $data): array
    {
        $expiresIn = (int) ($data['expires_in'] ?? 3600);
        $expiresAt = now()->addSeconds($expiresIn)->toISOString();

        return [
            'access_token' => $data['access_token'] ?? '',
            'refresh_token' => $data['refresh_token'] ?? '',
            'expires_at' => $expiresAt,
        ];
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
        try {
            $response = Http::asForm()->post($this->revokeEndpoint, [
                'token' => $token,
            ]);

            // Google returns 200 for successful revocation
            // Even if token was already revoked, we consider it a success
            return $response->status() === 200;
        } catch (\Exception $e) {
            \Log::warning('Failed to revoke Google token', [
                'error' => $e->getMessage(),
            ]);

            // Return true anyway to allow local cleanup to proceed
            return true;
        }
    }
}
