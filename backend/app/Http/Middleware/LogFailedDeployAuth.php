<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to log failed authentication attempts on deploy endpoints.
 *
 * Part of SPEC-SEC-2026-02-21-3: Deploy Endpoint Rate Limiting
 */
class LogFailedDeployAuth
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
        $response = $next($request);

        // Log failed authentication attempts (403 response from DeployController)
        if ($response->status() === 403) {
            $this->logFailedAuth($request);
        }

        return $response;
    }

    /**
     * Log a failed authentication attempt.
     *
     * @param Request $request
     */
    protected function logFailedAuth(Request $request): void
    {
        Log::channel('security')->warning('Failed deploy auth attempt', [
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'endpoint' => $request->path(),
            'method' => $request->method(),
            'timestamp' => now()->toIso8601String(),
            'error' => 'invalid_deploy_key',
        ]);
    }
}
