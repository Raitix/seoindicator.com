<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Exceptions\Auth\AccessDeniedException;
use App\Exceptions\Auth\ConfigurationException;
use App\Exceptions\Auth\InvalidOAuthStateException;
use App\Exceptions\Auth\OAuthFailedException;
use App\Http\Controllers\Controller;
use App\Services\Auth\GoogleOAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * GoogleAuthController handles Google OAuth 2.0 authentication endpoints.
 *
 * This controller provides:
 * - OAuth redirect URL generation for Google sign-in
 * - OAuth callback processing with state CSRF validation
 * - Rate limiting protection on callback endpoint
 *
 * @see TASK-25-8
 * @see SPEC-025
 * @see FEAT-025
 */
class GoogleAuthController extends Controller
{
    /**
     * Create a new GoogleAuthController instance.
     */
    public function __construct(
        protected GoogleOAuthService $oauthService
    ) {}

    /**
     * Generate a Google OAuth redirect URL.
     *
     * This endpoint initiates the Google OAuth flow by returning
     * a URL that the frontend should redirect the user to.
     *
     * @return JsonResponse
     */
    public function redirect(): JsonResponse
    {
        $redirectUrl = $this->oauthService->getRedirectUrl();

        return response()->json([
            'redirect_url' => $redirectUrl,
        ]);
    }

    /**
     * Handle the OAuth callback from Google.
     *
     * This endpoint processes the authorization code and state
     * returned by Google after user consent.
     *
     * Rate limited to 5 requests per minute per IP.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function callback(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|max:1000',
            'state' => 'required|string|max:100',
        ]);

        try {
            $result = $this->oauthService->handleCallback(
                $validated['code'],
                $validated['state']
            );

            $user = $result['user'];

            return response()->json([
                'success' => true,
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'email_verified' => !is_null($user->email_verified_at),
                        'avatar' => $user->avatar,
                    ],
                    'token' => $result['token'],
                    'is_new_user' => $result['is_new_user'],
                ],
            ]);
        } catch (InvalidOAuthStateException $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'invalid_state',
                    'message' => $e->getMessage(),
                ],
            ], 400);
        } catch (AccessDeniedException $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'access_denied',
                    'message' => $e->getMessage(),
                ],
            ], 400);
        } catch (ConfigurationException $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'configuration_error',
                    'message' => 'Authentication service is not properly configured.',
                ],
            ], 500);
        } catch (OAuthFailedException $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'oauth_failed',
                    'message' => $e->getMessage(),
                ],
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'oauth_failed',
                    'message' => 'An unexpected error occurred during authentication.',
                ],
            ], 500);
        }
    }
}
