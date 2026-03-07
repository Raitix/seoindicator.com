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

        // SiteCrawler service binding (TASK-14-5)
        $this->app->bind(
            \App\Services\Audit\Contracts\SiteCrawlerInterface::class,
            \App\Services\Audit\SiteCrawler::class
        );

        // RobotsParser singleton binding (TASK-14-6)
        $this->app->singleton(
            \App\Services\Audit\RobotsParser::class,
            \App\Services\Audit\RobotsParser::class
        );

        // IssueDetector service binding (TASK-14-8)
        $this->app->bind(
            \App\Services\Audit\Contracts\IssueDetectorInterface::class,
            \App\Services\Audit\IssueDetector::class
        );

        // Repository bindings (TASK-14-8)
        $this->app->bind(
            \App\Repositories\IssueTypeRepository::class,
            \App\Repositories\IssueTypeRepository::class
        );

        $this->app->bind(
            \App\Repositories\AuditIssueRepository::class,
            \App\Repositories\AuditIssueRepository::class
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

        // Rate limiter for public audit routes (TASK-33-7, SPEC-33)
        // 100 requests per hour per IP - allows reasonable sharing/embedding
        RateLimiter::for('public-audit', function (Request $request) {
            return \Illuminate\Cache\RateLimiting\Limit::perHour(100)
                ->by($request->ip())
                ->response(function () {
                    return response()->json([
                        'success' => false,
                        'error' => 'Too many requests. Please try again later.',
                    ], 429, [
                        'Retry-After' => 3600, // 1 hour in seconds
                    ]);
                });
        });

        // Rate limiter for badge tracking routes (TASK-33-7, TASK-33-17, TASK-33-18, SPEC-33)
        // 60 requests per minute per IP - supports high-volume widget impressions
        RateLimiter::for('badge-tracking', function (Request $request) {
            return \Illuminate\Cache\RateLimiting\Limit::perMinute(60)
                ->by($request->ip())
                ->response(function () {
                    return response()->json([
                        'success' => false,
                        'error' => 'Too many requests. Please try again later.',
                    ], 429, [
                        'Retry-After' => 60, // 1 minute in seconds
                    ]);
                });
        });

        // Rate limiter for audit trigger endpoint (TASK-14-13, SPEC-014)
        // 5 requests per hour per user
        RateLimiter::for('audit-trigger', function (Request $request) {
            return \Illuminate\Cache\RateLimiting\Limit::perHour(config('audit.rate_limits.trigger_per_hour', 5))
                ->by($request->user()?->id ?: $request->ip())
                ->response(function () {
                    return response()->json([
                        'error' => 'Too many audit requests. Please try again later.',
                    ], 429, [
                        'Retry-After' => 3600, // 1 hour in seconds
                    ]);
                });
        });
    }
}
