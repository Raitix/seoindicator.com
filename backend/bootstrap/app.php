<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Schedule;

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
            'ai.ratelimit' => \App\Http\Middleware\AIRateLimiter::class,
            'polling.ratelimit' => \App\Http\Middleware\PollingRateLimiter::class,
            'admin' => \App\Http\Middleware\AdminAuthorizationMiddleware::class,
        ]);

        // Add SetProjectContext to web middleware group (session-based)
        $middleware->web(append: [
            \App\Http\Middleware\SetProjectContext::class,
        ]);

        // Add SetProjectContext to API middleware group (header-based)
        $middleware->api(append: [
            \App\Http\Middleware\SetProjectContext::class,
            \App\Http\Middleware\RequestTrackingMiddleware::class,
            \App\Http\Middleware\RequestLoggingMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Handle malformed JSON requests (TASK-6-11)
        $exceptions->render(function (\Illuminate\Http\Exceptions\PostTooLargeException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'PAYLOAD_TOO_LARGE',
                        'message' => 'The request payload is too large.',
                    ],
                ], 413);
            }
        });

        // Handle validation errors for API routes
        $exceptions->render(function (\Illuminate\Validation\ValidationException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'The given data was invalid.',
                    'errors' => $e->errors(),
                ], 422);
            }
        });

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
    })
    ->withSchedule(function () {
        // TASK-23-5: Scheduled Daily Backlink Sync
        // Runs daily at configured time (default 03:00) to sync backlink data
        // for all projects with active GSC connections
        $syncTime = config('backlinks.sync_time', '03:00');
        [$hour, $minute] = array_map('intval', explode(':', $syncTime) + [0, 0]);

        Schedule::command('backlinks:sync --all')
            ->dailyAt("$hour:$minute")
            ->withoutOverlapping()
            ->onOneServer()
            ->when(fn () => config('backlinks.sync_enabled', true))
            ->appendOutputTo(storage_path('logs/backlinks-sync.log'));
    })
    ->create();
