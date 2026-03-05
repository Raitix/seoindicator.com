<?php

namespace App\Services\AI;

use App\Services\AI\Contracts\AIProviderInterface;
use App\Services\AI\DTOs\SuggestionResult;

/**
 * Core AI Suggestion Service.
 *
 * Orchestrates AI suggestion generation for SEO indicators,
 * managing provider selection, caching, timeouts, and fallback logic.
 */
class AISuggestionService
{
    /**
     * Create a new AISuggestionService instance.
     *
     * @param AIProviderInterface $provider The AI provider to use
     * @param AISuggestionCache|null $cache The cache service (optional for backwards compatibility)
     */
    public function __construct(
        protected AIProviderInterface $provider,
        protected ?AISuggestionCache $cache = null
    ) {}

    /**
     * Get suggestions for a specific SEO indicator.
     *
     * This method generates AI-powered suggestions for improving
     * a specific SEO indicator based on the provided context.
     * Results are cached to reduce API calls.
     *
     * @param string $indicator The indicator type (e.g., 'title', 'meta_description')
     * @param array<string, mixed> $context Context data for the indicator (page data, content, etc.)
     * @param string|null $providerName Optional provider name override
     * @return SuggestionResult The structured suggestion result
     */
    public function getSuggestions(string $indicator, array $context = [], ?string $providerName = null): SuggestionResult
    {
        $providerName = $providerName ?? $this->provider->getName();
        
        // Check cache first
        if ($this->cache) {
            $hash = $this->cache->generateHash($indicator, $context, $providerName);
            $cached = $this->cache->get($hash);
            
            if ($cached !== null) {
                return $this->arrayToSuggestionResult($cached, 'cached');
            }
        }
        
        // Build the prompt with indicator and context
        $promptContext = array_merge(['indicator' => $indicator], $context);
        $prompt = json_encode($promptContext, JSON_THROW_ON_ERROR);

        // Call the AI provider with error handling
        try {
            $response = $this->provider->generate($prompt);
        } catch (\Throwable $e) {
            // Provider threw an exception - return error result without caching
            return new SuggestionResult(
                indicator: $indicator,
                score: 0,
                issues: [$e->getMessage() ?? 'AI provider error'],
                suggestions: [],
                bestPractices: [],
                provider: $this->provider->getName(),
                model: 'error',
                generatedAt: new \DateTimeImmutable()
            );
        }

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

        $result = new SuggestionResult(
            indicator: $data['indicator'] ?? $indicator,
            score: $data['score'] ?? 0,
            issues: $data['issues'] ?? [],
            suggestions: $data['suggestions'] ?? [],
            bestPractices: $data['best_practices'] ?? [],
            provider: $data['provider'] ?? $this->provider->getName(),
            model: $data['model'] ?? 'unknown',
            generatedAt: new \DateTimeImmutable($data['generated_at'] ?? 'now')
        );
        
        // Cache the successful response
        if ($this->cache && $response->success) {
            $hash = $this->cache->generateHash($indicator, $context, $providerName);
            $this->cache->set($hash, $this->suggestionResultToArray($result));
        }
        
        return $result;
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
    
    /**
     * Convert a SuggestionResult to an array for caching.
     *
     * @param SuggestionResult $result
     * @return array<string, mixed>
     */
    private function suggestionResultToArray(SuggestionResult $result): array
    {
        return [
            'indicator' => $result->indicator,
            'score' => $result->score,
            'issues' => $result->issues,
            'suggestions' => $result->suggestions,
            'best_practices' => $result->bestPractices,
            'provider' => $result->provider,
            'model' => $result->model,
            'generated_at' => $result->generatedAt->format('c'),
        ];
    }
    
    /**
     * Convert a cached array back to a SuggestionResult.
     *
     * @param array<string, mixed> $data
     * @param string $providerMarker Marker to indicate cached source
     * @return SuggestionResult
     */
    private function arrayToSuggestionResult(array $data, string $providerMarker): SuggestionResult
    {
        return new SuggestionResult(
            indicator: $data['indicator'],
            score: $data['score'],
            issues: $data['issues'] ?? [],
            suggestions: $data['suggestions'] ?? [],
            bestPractices: $data['best_practices'] ?? [],
            provider: $providerMarker,
            model: $data['model'] ?? 'cached',
            generatedAt: new \DateTimeImmutable($data['generated_at'] ?? 'now')
        );
    }
}
