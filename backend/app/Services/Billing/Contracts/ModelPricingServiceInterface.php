<?php

namespace App\Services\Billing\Contracts;

use App\Models\AIModel;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Interface for Model Pricing Service.
 *
 * Manages AI model data, pricing calculations, and user preferences.
 */
interface ModelPricingServiceInterface
{
    /**
     * Get all available (active) AI models.
     *
     * @param bool $forceRefresh Force refresh bypassing cache
     * @return Collection<int, AIModel>
     */
    public function getAvailableModels(bool $forceRefresh = false): Collection;

    /**
     * Get a specific AI model by its database ID.
     *
     * @param int $id Model database ID
     * @return AIModel|null
     */
    public function getModelById(int $id): ?AIModel;

    /**
     * Get a specific AI model by provider and model ID.
     *
     * @param string $provider Provider name (e.g., 'openrouter')
     * @param string $modelId Model identifier (e.g., 'openai/gpt-4-turbo')
     * @return AIModel|null
     */
    public function getModelByProviderId(string $provider, string $modelId): ?AIModel;

    /**
     * Get pricing information for a specific model.
     *
     * @param string $modelId Model identifier
     * @return array|null Pricing data or null if model not found
     */
    public function getModelPricing(string $modelId): ?array;

    /**
     * Calculate the cost for an analysis based on estimated tokens.
     *
     * @param string $modelId Model identifier
     * @param int $estimatedTokens Total estimated tokens
     * @param string $analysisType Analysis type: 'quick', 'standard', or 'deep'
     * @return array Cost breakdown
     */
    public function calculateAnalysisCost(string $modelId, int $estimatedTokens, string $analysisType = 'standard'): array;

    /**
     * Sync models from OpenRouter API.
     *
     * @return int Number of models synced
     */
    public function syncModelsFromOpenRouter(): int;

    /**
     * Get the user's default model preference.
     *
     * @param User $user
     * @return AIModel|null
     */
    public function getUserDefaultModel(User $user): ?AIModel;

    /**
     * Set the user's default model preference.
     *
     * @param User $user
     * @param int $modelId
     * @throws \InvalidArgumentException If model doesn't exist or is inactive
     */
    public function setUserDefaultModel(User $user, int $modelId): void;
}
