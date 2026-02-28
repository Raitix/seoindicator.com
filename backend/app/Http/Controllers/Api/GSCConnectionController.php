<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GSCConnection;
use App\Services\OAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * GSC Connection Controller.
 *
 * Manages Google Search Console OAuth connections via API endpoints.
 *
 * @see TASK-12-7
 */
class GSCConnectionController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(
        protected OAuthService $oauthService
    ) {}

    /**
     * Redirect to Google OAuth authorization.
     *
     * Initiates the OAuth flow by redirecting to Google's authorization page.
     * After authorization, Google redirects back to our callback endpoint.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function redirectToGoogle(Request $request)
    {
        $state = Str::random(40);
        
        // Store state in session for CSRF protection
        $request->session()->put('gsc_oauth_state', $state);
        
        // Determine redirect URI for callback
        $redirectUri = config('services.google.gsc_redirect_uri', 
            config('app.url') . '/api/gsc/oauth/callback');
        
        try {
            $authUrl = $this->oauthService->getAuthorizationUrl($redirectUri, $state);
            return redirect($authUrl);
        } catch (\RuntimeException $e) {
            Log::error('Failed to generate OAuth URL', [
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage(),
            ]);
            
            // Redirect back to settings with error
            $error = urlencode($e->getMessage());
            return redirect("/settings/gsc?oauth_error={$error}");
        }
    }

    /**
     * Handle OAuth callback from Google.
     *
     * Exchanges the authorization code for tokens and creates a connection.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function handleGoogleCallback(Request $request)
    {
        // Check for error from Google
        if ($request->has('error')) {
            $error = urlencode($request->get('error_description', $request->get('error')));
            return redirect("/settings/gsc?oauth_error={$error}");
        }

        // Validate state for CSRF protection
        $state = $request->get('state');
        $storedState = $request->session()->pull('gsc_oauth_state');
        
        if (!$state || $state !== $storedState) {
            $error = urlencode('Invalid state parameter - possible CSRF attack');
            return redirect("/settings/gsc?oauth_error={$error}");
        }

        $code = $request->get('code');
        if (!$code) {
            $error = urlencode('No authorization code received from Google');
            return redirect("/settings/gsc?oauth_error={$error}");
        }

        // Ensure user is authenticated
        if (!$request->user()) {
            $error = urlencode('You must be logged in to complete GSC connection');
            return redirect("/login?redirect=/settings/gsc");
        }

        try {
            // Exchange code for tokens
            $redirectUri = config('services.google.gsc_redirect_uri', 
                config('app.url') . '/api/gsc/oauth/callback');
            
            // We need to get the property URL from the user
            // For now, we'll fetch available properties from Google and let user select
            $tokenData = $this->exchangeCodeForToken($code, $redirectUri);
            
            // Store tokens temporarily in session
            $request->session()->put('gsc_tokens', $tokenData);
            
            // Fetch available properties from Google Search Console
            $properties = $this->fetchAvailableProperties($tokenData['access_token']);
            
            if (empty($properties)) {
                $error = urlencode('No Search Console properties found for your account');
                return redirect("/settings/gsc?oauth_error={$error}");
            }
            
            // If only one property, auto-connect it
            if (count($properties) === 1) {
                $propertyUrl = $properties[0]['siteUrl'];
                $connection = $this->createConnection(
                    $request->user(),
                    $propertyUrl,
                    $tokenData
                );
                
                $successUrl = urlencode($propertyUrl);
                return redirect("/settings/gsc?oauth_success=true&property_url={$successUrl}");
            }
            
            // Multiple properties - store in session and redirect to selection page
            $request->session()->put('gsc_available_properties', $properties);
            return redirect('/settings/gsc/select-property');
            
        } catch (\Exception $e) {
            Log::error('GSC OAuth callback failed', [
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage(),
            ]);
            
            $error = urlencode($e->getMessage());
            return redirect("/settings/gsc?oauth_error={$error}");
        }
    }

    /**
     * Select and connect a specific property.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse|JsonResponse
     */
    public function selectProperty(Request $request)
    {
        $request->validate([
            'property_url' => 'required|url',
        ]);

        $tokenData = $request->session()->get('gsc_tokens');
        if (!$tokenData) {
            return redirect('/settings/gsc?oauth_error=' . urlencode('OAuth session expired'));
        }

        try {
            $connection = $this->createConnection(
                $request->user(),
                $request->property_url,
                $tokenData
            );
            
            // Clear temporary session data
            $request->session()->forget(['gsc_tokens', 'gsc_available_properties']);
            
            $successUrl = urlencode($request->property_url);
            return redirect("/settings/gsc?oauth_success=true&property_url={$successUrl}");
            
        } catch (\Exception $e) {
            $error = urlencode($e->getMessage());
            return redirect("/settings/gsc?oauth_error={$error}");
        }
    }

    /**
     * Get available properties for selection.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function availableProperties(Request $request): JsonResponse
    {
        $properties = $request->session()->get('gsc_available_properties', []);
        
        return response()->json([
            'properties' => $properties,
        ]);
    }

    /**
     * Initiate OAuth flow with authorization code.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function connect(Request $request): JsonResponse
    {
        $request->validate([
            'authorization_code' => 'required|string',
            'redirect_uri' => 'required|url',
            'property_url' => 'required|url',
        ]);

        try {
            $connection = $this->oauthService->handleCallback(
                code: $request->authorization_code,
                redirectUri: $request->redirect_uri,
                user: $request->user(),
                propertyUrl: $request->property_url
            );

            return response()->json([
                'message' => 'GSC property connected successfully',
                'connection' => $this->formatConnection($connection),
            ], Response::HTTP_CREATED);

        } catch (\RuntimeException $e) {
            Log::error('GSC connection failed', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
            ]);

            throw ValidationException::withMessages([
                'authorization_code' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Remove connection and revoke tokens.
     *
     * @param int $propertyId
     * @param Request $request
     * @return JsonResponse
     */
    public function disconnect(int $propertyId, Request $request): JsonResponse
    {
        $connection = GSCConnection::find($propertyId);

        if (!$connection) {
            return response()->json([
                'message' => 'Connection not found',
            ], Response::HTTP_NOT_FOUND);
        }

        // Authorization: users can only disconnect their own connections
        if ($connection->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'You are not authorized to disconnect this property',
            ], Response::HTTP_FORBIDDEN);
        }

        try {
            // Revoke OAuth access with Google
            $this->oauthService->revokeAccess($connection);

            // Delete the connection from database
            $connection->delete();

            return response()->json([
                'success' => true,
                'message' => 'GSC property disconnected successfully',
            ]);

        } catch (\Exception $e) {
            Log::error('GSC disconnection failed', [
                'connection_id' => $propertyId,
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
            ]);

            // Still delete the connection even if revocation fails
            $connection->delete();

            return response()->json([
                'success' => true,
                'message' => 'GSC property disconnected (token revocation may have failed)',
            ]);
        }
    }

    /**
     * List all connected GSC properties for user.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function properties(Request $request): JsonResponse
    {
        $connections = GSCConnection::where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'data' => $connections->map(fn ($connection) => $this->formatConnection($connection)),
        ]);
    }

    /**
     * Format connection for API response.
     *
     * Ensures sensitive tokens are never exposed.
     *
     * @param GSCConnection $connection
     * @return array
     */
    protected function formatConnection(GSCConnection $connection): array
    {
        return [
            'id' => $connection->id,
            'site_url' => $connection->site_url,
            'property_url' => $connection->property_url,
            'status' => $connection->status,
            'connected_at' => $connection->created_at->toISOString(),
            'last_synced_at' => $connection->last_synced_at?->toISOString(),
            'token_expires_at' => $connection->token_expires_at->toISOString(),
            // Note: access_token and refresh_token are intentionally excluded
        ];
    }

    /**
     * Exchange authorization code for tokens.
     *
     * @param string $code
     * @param string $redirectUri
     * @return array
     */
    protected function exchangeCodeForToken(string $code, string $redirectUri): array
    {
        $response = \Illuminate\Support\Facades\Http::asForm()->post(
            'https://oauth2.googleapis.com/token',
            [
                'code' => $code,
                'client_id' => config('services.google.client_id'),
                'client_secret' => config('services.google.client_secret'),
                'redirect_uri' => $redirectUri,
                'grant_type' => 'authorization_code',
            ]
        );

        if (!$response->successful()) {
            throw new \RuntimeException(
                $response->json('error_description', $response->json('error', 'Token exchange failed'))
            );
        }

        return $response->json();
    }

    /**
     * Fetch available GSC properties from Google.
     *
     * @param string $accessToken
     * @return array
     */
    protected function fetchAvailableProperties(string $accessToken): array
    {
        $response = \Illuminate\Support\Facades\Http::withToken($accessToken)
            ->get('https://www.googleapis.com/webmasters/v3/sites');

        if (!$response->successful()) {
            return [];
        }

        return $response->json('siteEntry', []);
    }

    /**
     * Create a GSC connection from token data.
     *
     * @param mixed $user
     * @param string $propertyUrl
     * @param array $tokenData
     * @return GSCConnection
     */
    protected function createConnection($user, string $propertyUrl, array $tokenData): GSCConnection
    {
        $siteUrl = parse_url($propertyUrl, PHP_URL_HOST) ?? $propertyUrl;
        
        return GSCConnection::updateOrCreate(
            [
                'user_id' => $user->id,
                'property_url' => $propertyUrl,
            ],
            [
                'site_url' => $siteUrl,
                'access_token' => $tokenData['access_token'],
                'refresh_token' => $tokenData['refresh_token'] ?? null,
                'token_expires_at' => now()->addSeconds($tokenData['expires_in']),
                'status' => GSCConnection::STATUS_ACTIVE,
            ]
        );
    }
}
