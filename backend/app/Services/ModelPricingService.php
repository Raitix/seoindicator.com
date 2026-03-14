<?php

namespace App\Services;

use App\Models\AIModel;
use App\Models\User;
use App\Models\UserModelPreference;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Service for managing AI model data, pricing calculations, and user preferences.
 *
 * TASK-26-16: ModelPricingService
 *
 * Provides methods for:
 * - Listing and retrieving AI models
 * - Calculating analysis costs based on token usage
 * - Syncing models from OpenRouter API
 * - Managing user default model preferences
 * - Caching model data for performance
 */
class ModelPricingService
{
    /**
     * Cache key for available models list.
     */
    private const CACHE_KEY_MODELS = 'ai_models:available';

    /**
     * Cache key prefix for user default model.
     */
    private const CACHE_KEY_USER_DEFAULT = 'ai_models:user_default:';

    /**
     * Cache TTL for models list (1 hour in seconds).
     */
    private const CACHE_TTL_MODELS = 3600;

    /**
     * Cache TTL for user default model (24 hours in seconds).
     */
    private const CACHE_TTL_USER_DEFAULT = 86400;

    /**
     * Token estimates by analysis type.
     */
    private const ANALYSIS_TOKENS = [
        'quick' => [
            'input' => 400,
            'output' => 100,
            'total' => 500,
        ],
        'standard' => [
            'input' => 1600,
            'output' => 400,
            'total' => 2000,
        ],
        'deep' => [
            'input' => 4000,
            'output' => 1000,
            'total' => 5000,
        ],
    ];

    /**
     * OpenRouter sync service instance.
     */
    private OpenRouterSyncService $syncService;

    /**
     * Create a new ModelPricingService instance.
     *
     * @param OpenRouterSyncService $syncService
     */
    public function __construct(OpenRouterSyncService $syncService)
    {
        $this->syncService = $syncService;
    }

    // ==================== MODEL LISTING ====================

    /**
     * Get all available active AI models.
     *
     * @param bool $forceRefresh Force cache refresh
     * @return Collection<AIModel>
     */
    public function getAvailableModels(bool $forceRefresh = false): Collection
    {
        if ($forceRefresh) {
            Cache::forget(self::CACHE_KEY_MODELS);
        }

        return Cache::remember(
            self::CACHE_KEY_MODELS,
            self::CACHE_TTL_MODELS,
            function () {
                return AIModel::active()
                    ->ordered()
                    ->get();
            }
        );
    }

    /**
     * Get a model by its database ID.
     *
     * @param int $id
     * @return AIModel|null
     */
    public function getModelById(int $id): ?AIModel
    {
        // Validate ID is positive integer
        if ($id <= 0) {
            return null;
        }

        return AIModel::active()
            ->where('id', $id)
            ->first();
    }

    /**
     * Get a model by provider and model_id.
     *
     * @param string $provider
     * @param string $modelId
     * @return AIModel|null
     */
    public function getModelByProviderId(string $provider, string $modelId): ?AIModel
    {
        // Validate parameters
        if (empty($provider) || empty($modelId)) {
            return null;
        }

        return AIModel::active()
            ->where('provider', $provider)
            ->where('model_id', $modelId)
            ->first();
    }

    // ==================== PRICING ====================

    /**
     * Get pricing information for a model.
     *
     * @param string $modelId
     * @return array|null
     */
    public function getModelPricing(string $modelId): ?array
    {
        $model = AIModel::active()
            ->where('model_id', $modelId)
            ->first();

        if (!$model) {
            return null;
        }

        return [
            'model_id' => $model->model_id,
            'input_per_1k' => (float) $model->cost_per_1k_input,
            'output_per_1k' => (float) $model->cost_per_1k_output,
            'input_per_1m' => (float) ($model->cost_per_1k_input * 1000),
            'output_per_1m' => (float) ($model->cost_per_1k_output * 1000),
            'is_free_tier' => $model->is_free_tier,
        ];
    }

    /**
     * Calculate the cost for an analysis.
     *
     * @param string $modelId Model ID (e.g., 'openai/gpt-4-turbo')
     * @param int $estimatedTokens Total estimated tokens for the analysis
     * @param string $analysisType Analysis type: 'quick', 'standard', 'deep'
     * @return array Cost breakdown
     */
    public function calculateAnalysisCost(string $modelId, int $estimatedTokens, string $analysisType = 'standard'): array
    {
        // Handle negative tokens
        if ($estimatedTokens < 0) {
            $estimatedTokens = 0;
        }

        // Get the model
        $model = AIModel::active()
            ->where('model_id', $modelId)
            ->first();

        // Return zero cost for non-existent or free tier models
        if (!$model) {
            return $this->emptyCostResult($modelId, $analysisType);
        }

        if ($model->is_free_tier) {
            return $this->emptyCostResult($modelId, $analysisType);
        }

        // Calculate token distribution based on analysis type
        $tokens = $this->calculateTokenDistribution($estimatedTokens, $analysisType);
        $inputTokens = $tokens['input'];
        $outputTokens = $tokens['output'];

        // Calculate costs
        $inputCost = ($inputTokens / 1000) * (float) $model->cost_per_1k_input;
        $outputCost = ($outputTokens / 1000) * (float) $model->cost_per_1k_output;
        $totalCost = $inputCost + $outputCost;

        // Round to 2 decimal places
        $totalCost = round($totalCost, 2);

        return [
            'model_id' => $model->model_id,
            'analysis_type' => $analysisType,
            'input_tokens' => $inputTokens,
            'output_tokens' => $outputTokens,
            'input_cost' => round($inputCost, 6),
            'output_cost' => round($outputCost, 6),
            'total_cost' => $totalCost,
        ];
    }

    /**
     * Calculate token distribution based on analysis type.
     *
     * @param int $totalTokens
     * @param string $analysisType
     * @return array
     */
    private function calculateTokenDistribution(int $totalTokens, string $analysisType): array
    {
        // If using standard predefined analysis types
        if (isset(self::ANALYSIS_TOKENS[$analysisType])) {
            return [
                'input' => self::ANALYSIS_TOKENS[$analysisType]['input'],
                'output' => self::ANALYSIS_TOKENS[$analysisType]['output'],
            ];
        }

        // For custom token counts, use 4:1 ratio
        $inputTokens = (int) ($totalTokens * 0.8);
        $outputTokens = $totalTokens - $inputTokens;

        return [
            'input' => $inputTokens,
            'output' => $outputTokens,
        ];
    }

    /**
     * Return an empty cost result for non-existent or free tier models.
     *
     * @param string $modelId
     * @param string $analysisType
     * @return array
     */
    private function emptyCostResult(string $modelId, string $analysisType): array
    {
        return [
            'model_id' => $modelId,
            'analysis_type' => $analysisType,
            'input_tokens' => 0,
            'output_tokens' => 0,
            'input_cost' => 0,
            'output_cost' => 0,
            'total_cost' => 0,
        ];
    }

    // ==================== SYNC ====================

    /**
     * Sync models from OpenRouter API.
     *
     * @return int Number of models synced
     */
    public function syncModelsFromOpenRouter(): int
    {
        try {
            Log::info('Starting OpenRouter model sync');

            $count = $this->syncService->syncModels();

            // Invalidate cache after sync
            Cache::forget(self::CACHE_KEY_MODELS);

            Log::info('OpenRouter model sync completed', [
                'models_synced' => $count,
            ]);

            return $count;
        } catch (\Exception $e) {
            Log::error('OpenRouter model sync failed', [
                'error' => $e->getMessage(),
            ]);

            // Still invalidate cache on error
            Cache::forget(self::CACHE_KEY_MODELS);

            return 0;
        }
    }

    // ==================== USER PREFERENCES ====================

    /**
     * Get the user's default AI model.
     *
     * @param User $user
     * @return AIModel|null
     */
    public function getUserDefaultModel(User $user): ?AIModel
    {
        $cacheKey = self::CACHE_KEY_USER_DEFAULT . $user->id;

        // Try to get cached model ID
        $cachedModelId = Cache::get($cacheKey);

        if ($cachedModelId) {
            $model = AIModel::active()
                ->where('id', $cachedModelId)
                ->first();

            if ($model) {
                return $model;
            }
        }

        // Get preference from database
        $preference = UserModelPreference::where('user_id', $user->id)
            ->where('is_default', true)
            ->first();

        if (!$preference) {
            return null;
        }

        // Get the model and verify it's active
        $model = AIModel::active()
            ->where('id', $preference->model_id)
            ->first();

        if (!$model) {
            return null;
        }

        // Cache the model ID
        Cache::put($cacheKey, $model->id, self::CACHE_TTL_USER_DEFAULT);

        return $model;
    }

    /**
     * Set the user's default AI model.
     *
     * @param User $user
     * @param int $modelId
     * @return void
     * @throws \InvalidArgumentException If model doesn't exist or is inactive
     */
    public function setUserDefaultModel(User $user, int $modelId): void
    {
        // Validate model exists and is active
        $model = AIModel::where('id', $modelId)->first();

        if (!$model) {
            throw new \InvalidArgumentException('Model not found');
        }

        if (!$model->is_active) {
            throw new \InvalidArgumentException('Model is not active');
        }

        // Clear any existing default
        UserModelPreference::where('user_id', $user->id)
            ->where('is_default', true)
            ->update(['is_default' => false]);

        // Create or update preference
        UserModelPreference::updateOrCreate(
            [
                'user_id' => $user->id,
                'model_id' => $modelId,
            ],
            [
                'is_default' => true,
            ]
        );

        // Invalidate user cache
        Cache::forget(self::CACHE_KEY_USER_DEFAULT . $user->id);
    }
}
