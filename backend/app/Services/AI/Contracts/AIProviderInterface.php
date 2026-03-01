<?php

namespace App\Services\AI\Contracts;

use App\Services\AI\DTOs\AIResponse;

/**
 * Interface for AI provider implementations.
 *
 * All AI providers (OpenRouter, Vercel, Static Fallback) must implement this interface
 * to ensure consistent behavior across different AI backends.
 */
interface AIProviderInterface
{
    /**
     * Generate an AI response for the given prompt.
     *
     * @param string $prompt The prompt to send to the AI model
     * @param array<string, mixed> $options Optional parameters (temperature, max_tokens, etc.)
     * @return AIResponse The response from the AI provider
     * @throws \Exception When the provider fails to generate a response
     */
    public function generate(string $prompt, array $options = []): AIResponse;

    /**
     * Check if the provider is available and properly configured.
     *
     * This method should verify that the provider can make requests
     * (e.g., API key is set, endpoint is reachable).
     *
     * @return bool True if the provider is available, false otherwise
     */
    public function isAvailable(): bool;

    /**
     * Get the name of this provider.
     *
     * Used for logging, debugging, and provider selection.
     *
     * @return string The provider name (e.g., 'openrouter', 'vercel', 'static_fallback')
     */
    public function getName(): string;
}
