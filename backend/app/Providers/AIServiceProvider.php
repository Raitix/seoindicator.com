<?php

namespace App\Providers;

use App\Services\AI\AISuggestionService;
use App\Services\AI\AIAnalysisService;
use App\Services\AI\AnalysisCacheService;
use App\Services\AI\OpenRouterClient;
use App\Services\AI\UnifiedPromptService;
use App\Services\AI\AIAnalysisResponseParser;
use App\Services\AI\ContextWindowManager;
use App\Services\AI\TruncationService;
use App\Services\AI\Contracts\AIProviderInterface;
use App\Services\AI\Contracts\AIAnalysisServiceInterface;
use App\Services\AI\Contracts\AnalysisCacheServiceInterface;
use App\Services\AI\Contracts\OpenRouterClientInterface;
use App\Services\AI\Contracts\UnifiedPromptServiceInterface;
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

        // TASK-31-7: AIAnalysisService bindings
        $this->app->singleton(UnifiedPromptServiceInterface::class, UnifiedPromptService::class);
        $this->app->singleton(OpenRouterClientInterface::class, function ($app) {
            $apiKey = config('openrouter.api_key');
            // Allow empty API key for testing - the service will handle it gracefully
            return new OpenRouterClient([
                'api_key' => $apiKey ?: 'not-configured',
                'base_url' => config('openrouter.base_url'),
                'model' => config('openrouter.default_model'),
                'timeout' => config('openrouter.timeout'),
                'max_tokens' => config('openrouter.max_tokens'),
            ]);
        });
        $this->app->singleton(AnalysisCacheServiceInterface::class, AnalysisCacheService::class);
        
        // Helper services
        $this->app->singleton(AIAnalysisResponseParser::class);
        $this->app->singleton(ContextWindowManager::class);
        $this->app->singleton(TruncationService::class);
        
        // Main AIAnalysisService
        $this->app->singleton(AIAnalysisServiceInterface::class, AIAnalysisService::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
