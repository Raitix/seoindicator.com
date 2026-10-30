<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\ApiKey;
use Illuminate\Support\Facades\Hash;

/**
 * API Key Authentication Middleware for TASK-6-4.
 *
 * Validates Bearer token API key authentication for REST API endpoints.
 * Attaches the authenticated API key and user to the request.
 *
 * Features:
 * - Bearer token authentication
 * - API key validation (exists, not revoked, not expired)
 * - Permission checking via hasPermission() method
 * - Sets request attributes for downstream middleware/controllers
 *
 * Response Codes:
 * - 401 Unauthorized: Missing, invalid, revoked, or expired API key
 * - 403 Forbidden: Valid API key but missing required permission
 */
class ApiKeyAuth
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @param string ...$permissions Required permissions (optional)
     * @return mixed
     */
    public function handle(Request $request, Closure $next, string ...$permissions): mixed
    {
        // Get the Bearer token from Authorization header
        $token = $this->extractToken($request);

        if ($token === null) {
            return $this->unauthorizedResponse('UNAUTHENTICATED', 'Missing API key');
        }

        // Validate the API key
        $apiKey = $this->validateToken($token);

        if ($apiKey === null) {
            return $this->unauthorizedResponse('UNAUTHENTICATED', 'Invalid or revoked API key');
        }

        // Check if API key is active (not revoked, not expired)
        if (!$apiKey->isActive()) {
            return $this->unauthorizedResponse('UNAUTHENTICATED', 'Invalid or revoked API key');
        }

        // Attach API key and user to request
        $request->attributes->set('api_key', $apiKey);
        $request->attributes->set('current_api_key', $apiKey);
        $request->setUserResolver(function () use ($apiKey) {
            return $apiKey->user;
        });

        // Mark API key as used
        $apiKey->markAsUsed();

        // Check permissions if specified
        if (!empty($permissions)) {
            foreach ($permissions as $permission) {
                if (!$this->hasPermission($apiKey, $permission)) {
                    return $this->forbiddenResponse();
                }
            }
        }

        return $next($request);
    }

    /**
     * Extract Bearer token from Authorization header.
     *
     * @param Request $request
     * @return string|null
     */
    protected function extractToken(Request $request): ?string
    {
        $authHeader = $request->header('Authorization');

        if ($authHeader === null) {
            return null;
        }

        // Check for Bearer token format
        if (!str_starts_with($authHeader, 'Bearer ')) {
            return null;
        }

        return substr($authHeader, 7); // Remove 'Bearer ' prefix
    }

    /**
     * Validate the API key token.
     *
     * @param string $token
     * @return ApiKey|null
     */
    protected function validateToken(string $token): ?ApiKey
    {
        // Validate token format (should start with sk_live_)
        if (!str_starts_with($token, 'sk_live_')) {
            return null;
        }

        // Hash the token to find it in the database
        $keyHash = hash('sha256', $token);

        // Find the API key by hash
        return ApiKey::where('key_hash', $keyHash)->first();
    }

    /**
     * Check if the API key has a specific permission.
     *
     * @param ApiKey $apiKey
     * @param string $permission
     * @return bool
     */
    protected function hasPermission(ApiKey $apiKey, string $permission): bool
    {
        $permissions = $apiKey->permissions ?? [];

        return in_array($permission, $permissions, true);
    }

    /**
     * Build an unauthorized response.
     *
     * @param string $code Error code
     * @param string $message Error message
     * @return JsonResponse
     */
    protected function unauthorizedResponse(string $code, string $message): JsonResponse
    {
        return new JsonResponse([
            'success' => false,
            'error' => [
                'code' => $code,
                'message' => $message,
            ],
        ], 401);
    }

    /**
     * Build a forbidden response.
     *
     * @return JsonResponse
     */
    protected function forbiddenResponse(): JsonResponse
    {
        return new JsonResponse([
            'success' => false,
            'error' => [
                'code' => 'FORBIDDEN',
                'message' => 'Insufficient permissions for this action.',
            ],
        ], 403);
    }
}
