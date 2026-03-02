<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Admin Authorization Middleware.
 *
 * Protects admin routes by checking if the authenticated user has admin role.
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
        // Check if user is authenticated
        if (!$request->user()) {
            return $this->unauthorizedResponse();
        }

        // Check if user is admin
        if (!$request->user()->isAdmin()) {
            return $this->forbiddenResponse();
        }

        return $next($request);
    }

    /**
     * Build an unauthorized response (401).
     *
     * @return JsonResponse
     */
    protected function unauthorizedResponse(): JsonResponse
    {
        return new JsonResponse([
            'success' => false,
            'error' => [
                'code' => 'UNAUTHORIZED',
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
