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

        // AI Visibility Service binding (TASK-34-6, SPEC-034)
        $this->app->bind(
            \App\Services\AIVisibility\Contracts\AIVisibilityServiceInterface::class,
            \App\Services\AIVisibility\AIVisibilityService::class
        );

        // Analytics Aggregator binding (TASK-34-13, SPEC-034)
        $this->app->bind(
            \App\Services\AIVisibility\Contracts\AnalyticsAggregatorInterface::class,
            \App\Services\AIVisibility\AnalyticsAggregator::class
        );

        // Alert Detection Service binding (TASK-34-19, SPEC-034)
        $this->app->bind(
            \App\Services\AIVisibility\Contracts\AlertDetectionServiceInterface::class,
            \App\Services\AIVisibility\AlertDetectionService::class
        );

        // GBP Insights Service binding (TASK-35-14, SPEC-035)
        $this->app->bind(
            \App\Services\LocalSEO\Contracts\GBPInsightsServiceInterface::class,
            \App\Services\LocalSEO\GBPInsightsService::class
        );

        // GBP API Client binding (TASK-35-8, TASK-35-10, TASK-35-9, SPEC-035)
        $this->app->bind(
            \App\Services\GBP\Contracts\GBPApiServiceInterface::class,
            \App\Services\GBP\GBPApiService::class
        );

        // GBP Insights domain service binding (TASK-35-9, SPEC-035)
        $this->app->bind(
            \App\Services\GBP\Contracts\GBPInsightsServiceInterface::class,
            \App\Services\GBP\GBPInsightsService::class
        );

        // GBP Reviews Service binding (TASK-35-10, TASK-35-12, TASK-35-14, SPEC-035)
        // Note: LocalSEO namespace for alert service compatibility
        $this->app->bind(
            \App\Services\LocalSEO\Contracts\GBPReviewsServiceInterface::class,
            \App\Services\LocalSEO\GBPReviewsService::class
        );

        // Local Rank Service binding (TASK-35-11, SPEC-035)
        $this->app->bind(
            \App\Services\LocalSEO\Contracts\LocalRankServiceInterface::class,
            \App\Services\LocalSEO\LocalRankService::class
        );

        // Local SEO Alert Service binding (TASK-35-12, SPEC-035)
        $this->app->bind(
            \App\Services\LocalSEO\Contracts\LocalSEOAlertServiceInterface::class,
            \App\Services\LocalSEO\LocalSEOAlertService::class
        );

        // Business Location Service binding (TASK-35-5, TASK-35-13, SPEC-035)
        $this->app->bind(
            \App\Services\LocalSEO\Contracts\BusinessLocationServiceInterface::class,
            \App\Services\LocalSEO\BusinessLocationService::class
        );

        // Google OAuth Service binding (TASK-35-7, TASK-35-13, SPEC-035)
        $this->app->bind(
            \App\Services\LocalSEO\Contracts\GoogleOAuthServiceInterface::class,
            \App\Services\LocalSEO\GoogleOAuthService::class
        );

        // Nominatim Geocoding Service binding (TASK-35-6, SPEC-035)
        $this->app->bind(
            \App\Services\LocalSEO\Contracts\NominatimServiceInterface::class,
            \App\Services\LocalSEO\NominatimService::class
        );

        // Content Decay Service bindings (TASK-36-10, SPEC-036)
        $this->app->bind(
            \App\Services\ContentDecay\Contracts\ContentDecayServiceInterface::class,
            \App\Services\ContentDecay\ContentDecayService::class
        );

        $this->app->bind(
            \App\Services\ContentDecay\Contracts\ContentDecayCandidateServiceInterface::class,
            \App\Services\ContentDecay\ContentDecayCandidateService::class
        );

        $this->app->bind(
            \App\Services\ContentDecay\Contracts\ContentDecayDetectionServiceInterface::class,
            \App\Services\ContentDecay\ContentDecayDetectionService::class
        );

        $this->app->bind(
            \App\Services\ContentDecay\Contracts\ContentRefreshTaskServiceInterface::class,
            \App\Services\ContentDecay\ContentRefreshTaskService::class
        );

        $this->app->bind(
            \App\Services\ContentDecay\Contracts\ContentDecayExportServiceInterface::class,
            \App\Services\ContentDecay\ContentDecayExportService::class
        );

        // Content Decay Reason Service binding (TASK-36-4, SPEC-036)
        $this->app->bind(
            \App\Services\ContentDecay\Contracts\ContentDecayReasonServiceInterface::class,
            \App\Services\ContentDecay\ContentDecayReasonService::class
        );

        // Content Decay Scoring Service binding (TASK-36-3, SPEC-036)
        $this->app->bind(
            \App\Services\ContentDecay\Contracts\ContentDecayScoringServiceInterface::class,
            \App\Services\ContentDecay\ContentDecayScoringService::class
        );

        // Content Refresh Planner Service binding (TASK-36-5, SPEC-036)
        $this->app->bind(
            \App\Services\ContentDecay\Contracts\ContentRefreshPlannerServiceInterface::class,
            \App\Services\ContentDecay\ContentRefreshPlannerService::class
        );

        // Crawler Service binding (TASK-22-11, SPEC-022)
        $this->app->bind(
            \App\Services\Crawler\CrawlerServiceInterface::class,
            \App\Services\Crawler\CrawlerService::class
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
        // 10 requests per minute per IP - prevents abuse while allowing reasonable tracking
        RateLimiter::for('badge-tracking', function (Request $request) {
            return \Illuminate\Cache\RateLimiting\Limit::perMinute(10)
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
