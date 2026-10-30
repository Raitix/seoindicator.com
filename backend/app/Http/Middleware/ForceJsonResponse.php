<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForceJsonResponse
{
    /**
     * Force JSON responses for API routes so auth failures return 401 instead of redirect.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->is('api/v1/locations/*/gbp-callback')) {
            return $next($request);
        }

        $request->headers->set('Accept', 'application/json');

        return $next($request);
    }
}
