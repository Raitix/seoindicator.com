<?php

namespace App\Providers;

use App\Services\AI\AISuggestionService;
use App\Services\AI\Contracts\AIProviderInterface;
use App\Services\AI\Providers\StaticFallbackProvider;
use Illuminate\Support\ServiceProvider;

/**
 * Service provider for AI suggestion services.
 *
 * Binds the AI provider interface to a concrete implementation
 * and registers the AI suggestion service in the container.
 */
class AIServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Bind the AIProviderInterface to StaticFallbackProvider
        // This provides rule-based SEO suggestions without external AI dependencies
        // Real AI providers (OpenRouter, Vercel) will be added in TASK-21-2, TASK-21-4
        $this->app->bind(AIProviderInterface::class, function ($app) {
            return new StaticFallbackProvider();
        });

        // Register the AISuggestionService as a singleton
        $this->app->singleton(AISuggestionService::class, function ($app) {
            return new AISuggestionService(
                $app->make(AIProviderInterface::class)
            );
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
