<?php

namespace App\Services\AI;

use App\Services\AI\Contracts\AIProviderInterface;
use App\Services\AI\DTOs\SuggestionResult;

/**
 * Core AI Suggestion Service.
 *
 * Orchestrates AI suggestion generation for SEO indicators,
 * managing provider selection, timeouts, and fallback logic.
 */
class AISuggestionService
{
    /**
     * Create a new AISuggestionService instance.
     *
     * @param AIProviderInterface $provider The AI provider to use
     */
    public function __construct(
        protected AIProviderInterface $provider
    ) {}

    /**
     * Get suggestions for a specific SEO indicator.
     *
     * This method generates AI-powered suggestions for improving
     * a specific SEO indicator based on the provided context.
     *
     * @param string $indicator The indicator type (e.g., 'title', 'meta_description')
     * @param array<string, mixed> $context Context data for the indicator (page data, content, etc.)
     * @return SuggestionResult The structured suggestion result
     */
    public function getSuggestions(string $indicator, array $context = []): SuggestionResult
    {
        // Build the prompt with indicator and context
        $promptContext = array_merge(['indicator' => $indicator], $context);
        $prompt = json_encode($promptContext, JSON_THROW_ON_ERROR);

        // Call the AI provider
        $response = $this->provider->generate($prompt);

        // Handle error response
        if (!$response->success) {
            return new SuggestionResult(
                indicator: $indicator,
                score: 0,
                issues: [$response->errorMessage ?? 'AI provider error'],
                suggestions: [],
                bestPractices: [],
                provider: $this->provider->getName(),
                model: 'error',
                generatedAt: new \DateTimeImmutable()
            );
        }

        // Parse the response content
        $data = json_decode($response->content, true, flags: JSON_THROW_ON_ERROR);

        return new SuggestionResult(
            indicator: $data['indicator'] ?? $indicator,
            score: $data['score'] ?? 0,
            issues: $data['issues'] ?? [],
            suggestions: $data['suggestions'] ?? [],
            bestPractices: $data['best_practices'] ?? [],
            provider: $data['provider'] ?? $this->provider->getName(),
            model: $data['model'] ?? 'unknown',
            generatedAt: new \DateTimeImmutable($data['generated_at'] ?? 'now')
        );
    }

    /**
     * Set the AI provider at runtime.
     *
     * Allows switching between different AI providers
     * (e.g., from OpenRouter to Vercel or fallback).
     *
     * @param string $provider The provider name to switch to
     * @return void
     */
    public function setProvider(string $provider): void
    {
        // Stub implementation - provider switching will be implemented in later tasks
        // This method is a placeholder for the provider factory implementation
    }

    /**
     * Check if debug mode is enabled.
     *
     * When debug mode is active, additional information is returned
     * with suggestions (raw prompts, responses, timing data).
     *
     * @return bool True if debug mode is enabled
     */
    public function isDebugMode(): bool
    {
        return (bool) config('ai.debug', false);
    }
}
