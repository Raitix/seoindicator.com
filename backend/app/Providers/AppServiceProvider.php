<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use App\Listeners\SerpFeatureChangeListener;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $fetcher = $this->app->environment('testing')
            ? \App\Services\SERP\HttpSERPFetcher::class
            : \App\Services\SERP\MockSERPFetcher::class;

        $this->app->bind(
            \App\Services\SERPFetcherInterface::class,
            $fetcher
        );

        $this->app->bind(
            \App\Services\Queue\JobQueueInterface::class,
            \App\Services\Queue\JobQueue::class
        );

        // Payment gateway binding (TASK-26-13)
        $this->app->bind(
            \App\Services\Billing\Contracts\PaymentGatewayInterface::class,
            \App\Services\Billing\PayPalGatewayService::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Enable foreign key constraints for SQLite
        if ($this->app->environment('testing')) {
            try {
                $pdo = $this->app->make('db')->connection()->getPdo();
                if ($pdo->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'sqlite') {
                    $pdo->exec('PRAGMA foreign_keys = ON');
                }
            } catch (\Exception $e) {
                // Ignore if connection not available yet
            }
        }

        // Register SERP feature change event listeners (TASK-7-14)
        Event::subscribe(SerpFeatureChangeListener::class);

        // Rate limiter for deploy endpoints (SPEC-SEC-2026-02-21-3)
        // 5 requests per 15 minutes per IP
        RateLimiter::for('deploy', function (Request $request) {
            return \Illuminate\Cache\RateLimiting\Limit::perMinutes(15, 5)
                ->by($request->ip())
                ->response(function () {
                    return response()->json([
                        'success' => false,
                        'error' => 'Too many requests. Please try again later.',
                    ], 429, [
                        'Retry-After' => 900, // 15 minutes in seconds
                    ]);
                });
        });
    }
}
