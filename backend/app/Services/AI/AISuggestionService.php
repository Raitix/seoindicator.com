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
        // Stub implementation - actual logic will be implemented in later tasks
        // For now, return a basic result structure
        return new SuggestionResult(
            indicator: $indicator,
            score: 0,
            issues: [],
            suggestions: [],
            bestPractices: [],
            provider: $this->provider->getName(),
            model: 'stub',
            generatedAt: new \DateTimeImmutable()
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
