<?php

namespace App\Providers;

use App\Services\AI\AISuggestionService;
use App\Services\AI\Contracts\AIProviderInterface;
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
        // Bind the AIProviderInterface to a closure that returns a mock/null provider
        // This allows the service to be resolved from the container for testing
        // Real providers (OpenRouter, Vercel) will be implemented in TASK-21-2, TASK-21-3
        $this->app->bind(AIProviderInterface::class, function ($app) {
            // Return a simple mock provider that satisfies the interface
            return new class implements AIProviderInterface {
                public function generate(string $prompt, array $options = []): \App\Services\AI\DTOs\AIResponse
                {
                    return \App\Services\AI\DTOs\AIResponse::error('Provider not configured');
                }

                public function isAvailable(): bool
                {
                    return false;
                }

                public function getName(): string
                {
                    return 'null_provider';
                }
            };
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
