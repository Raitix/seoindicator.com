<?php

namespace App\Services\AI\Contracts;

use App\Services\AI\DTOs\AIResponse;

/**
 * Interface for OpenRouter AI client.
 *
 * Provides a simplified interface for making AI requests via OpenRouter.
 *
 * @see TASK-31-6
 * @see TASK-31-7
 */
interface OpenRouterClientInterface
{
    /**
     * Generate an AI response.
     *
     * @param string $prompt The prompt to send
     * @param array $options Optional parameters:
     *   - model: string (model identifier)
     *   - timeout: int (timeout in seconds)
     *   - temperature: float (0-1)
     *   - max_tokens: int (maximum tokens)
     * @return AIResponse The AI response
     */
    public function generate(string $prompt, array $options = []): AIResponse;

    /**
     * Check if the client is available.
     *
     * @return bool True if available
     */
    public function isAvailable(): bool;

    /**
     * Get the configured model.
     *
     * @return string The model identifier
     */
    public function getModel(): string;
}
