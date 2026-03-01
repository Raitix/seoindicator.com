<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->api(prepend: [
            \App\Http\Middleware\ForceJsonResponse::class,
        ]);

        // Register alias for custom middleware
        $middleware->alias([
            'log-deploy-auth' => \App\Http\Middleware\LogFailedDeployAuth::class,
            'project.context' => \App\Http\Middleware\SetProjectContext::class,
            'api-key.auth' => \App\Http\Middleware\ApiKeyAuth::class,
            'api-key.ratelimit' => \App\Http\Middleware\ApiKeyRateLimiter::class,
        ]);

        // Add SetProjectContext to web middleware group (session-based)
        $middleware->web(append: [
            \App\Http\Middleware\SetProjectContext::class,
        ]);

        // Add SetProjectContext to API middleware group (header-based)
        $middleware->api(append: [
            \App\Http\Middleware\SetProjectContext::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Illuminate\Http\Exceptions\ThrottleRequestsException $e, $request) {
            if ($request->expectsJson()) {
                $response = response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'rate_limit_exceeded',
                        'message' => 'Too many requests. Please try again later.',
                    ],
                ], 429);

                // Add Retry-After header if available
                if ($e->getHeaders() && isset($e->getHeaders()['Retry-After'])) {
                    $response->header('Retry-After', $e->getHeaders()['Retry-After']);
                }

                return $response;
            }
        });
    })->create();
