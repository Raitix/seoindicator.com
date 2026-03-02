<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

/**
 * Admin Authorization Middleware.
 *
 * Protects admin routes by checking if the authenticated user has admin role.
 * Handles both session-based and Sanctum token-based authentication.
 * Logs unauthorized access attempts for security auditing.
 *
 * @see TASK-27-2
 * @see SPEC-027
 */
class AdminAuthorizationMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Try to get authenticated user from session or Sanctum token
        $user = $this->getAuthenticatedUser($request);

        // Check if user is authenticated
        if (!$user) {
            $this->logUnauthenticatedAccess($request);
            return $this->unauthenticatedResponse();
        }

        // Check if user is admin
        if (!$user->isAdmin()) {
            $this->logUnauthorizedAccess($request, $user);
            return $this->forbiddenResponse();
        }

        return $next($request);
    }

    /**
     * Get authenticated user from session or Sanctum token.
     *
     * @param Request $request
     * @return \App\Models\User|null
     */
    protected function getAuthenticatedUser(Request $request): ?\App\Models\User
    {
        // First check session-based auth (web guard)
        if (Auth::guard('web')->check()) {
            return Auth::guard('web')->user();
        }

        // Check for Sanctum token in Authorization header
        $token = $this->getTokenFromRequest($request);
        if ($token) {
            $user = $this->getUserFromToken($token);
            if ($user) {
                return $user;
            }
        }

        // Fallback to request user (may work if other auth middleware ran)
        if ($request->user()) {
            return $request->user();
        }

        return null;
    }

    /**
     * Extract token from request.
     *
     * @param Request $request
     * @return string|null
     */
    protected function getTokenFromRequest(Request $request): ?string
    {
        $authorization = $request->header('Authorization');
        
        if (!$authorization) {
            return null;
        }

        $parts = explode(' ', $authorization);
        
        if (count($parts) !== 2 || strtolower($parts[0]) !== 'bearer') {
            return null;
        }

        return $parts[1];
    }

    /**
     * Get user from Sanctum token.
     *
     * @param string $token
     * @return \App\Models\User|null
     */
    protected function getUserFromToken(string $token): ?\App\Models\User
    {
        try {
            $accessToken = PersonalAccessToken::findToken($token);
            
            if (!$accessToken) {
                return null;
            }

            // Check if token has the 'access' ability or no specific ability
            $abilities = $accessToken->abilities;
            if (!empty($abilities) && !in_array('*', $abilities) && !in_array('access', $abilities)) {
                return null;
            }

            $user = $accessToken->tokenable;
            
            // Update last used timestamp
            $accessToken->forceFill(['last_used_at' => now()])->save();

            return $user;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Log unauthenticated access attempt.
     *
     * @param Request $request
     * @return void
     */
    protected function logUnauthenticatedAccess(Request $request): void
    {
        $context = [
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'url' => $request->fullUrl(),
            'method' => $request->method(),
        ];

        Log::info('Unauthenticated admin access attempt', $context);

        // Log to database if table exists
        $this->logToDatabase(null, $request, 'unauthenticated_access_attempt');
    }

    /**
     * Log unauthorized access attempt.
     *
     * @param Request $request
     * @param \App\Models\User $user
     * @return void
     */
    protected function logUnauthorizedAccess(Request $request, \App\Models\User $user): void
    {
        $context = [
            'user_id' => $user->id,
            'user_role' => $user->role,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'url' => $request->fullUrl(),
            'method' => $request->method(),
        ];

        Log::info('Unauthorized admin access attempt', $context);

        // Log to database if table exists
        $this->logToDatabase($user->id, $request, 'unauthorized_access_attempt');
    }

    /**
     * Log access attempt to admin_activity_logs table if it exists.
     *
     * @param int|null $userId
     * @param Request $request
     * @param string $action
     * @return void
     */
    protected function logToDatabase(?int $userId, Request $request, string $action): void
    {
        try {
            // Check if table exists
            if (!$this->adminActivityLogsTableExists()) {
                return;
            }

            DB::table('admin_activity_logs')->insert([
                'admin_user_id' => $userId,
                'action' => $action,
                'target_type' => 'admin_route',
                'target_id' => null,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'details' => json_encode([
                    'url' => $request->fullUrl(),
                    'method' => $request->method(),
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Exception $e) {
            // Silently fail if logging to database fails
            // File logging already captured the attempt
        }
    }

    /**
     * Check if admin_activity_logs table exists.
     *
     * @return bool
     */
    protected function adminActivityLogsTableExists(): bool
    {
        static $exists = null;

        if ($exists === null) {
            try {
                $exists = DB::getSchemaBuilder()->hasTable('admin_activity_logs');
            } catch (\Exception $e) {
                $exists = false;
            }
        }

        return $exists;
    }

    /**
     * Build an unauthenticated response (401).
     *
     * @return JsonResponse
     */
    protected function unauthenticatedResponse(): JsonResponse
    {
        return new JsonResponse([
            'success' => false,
            'error' => [
                'code' => 'UNAUTHENTICATED',
                'message' => 'Authentication required.',
            ],
        ], 401);
    }

    /**
     * Build a forbidden response (403).
     *
     * @return JsonResponse
     */
    protected function forbiddenResponse(): JsonResponse
    {
        return new JsonResponse([
            'success' => false,
            'error' => [
                'code' => 'FORBIDDEN',
                'message' => 'Admin access required.',
            ],
        ], 403);
    }
}
