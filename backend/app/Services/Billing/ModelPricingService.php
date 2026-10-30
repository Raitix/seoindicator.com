<?php

namespace App\Services\Billing;

use App\Models\AIModel;
use App\Models\User;
use App\Models\UserModelPreference;
use App\Services\Billing\Contracts\ModelPricingServiceInterface;
use App\Services\OpenRouterSyncService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Service for managing AI model pricing and user preferences.
 *
 * TASK-26-16: ModelPricingService
 *
 * Provides methods for:
 * - Listing available AI models
 * - Calculating analysis costs
 * - Managing user default model preferences
 * - Syncing models from OpenRouter
 */
class ModelPricingService implements ModelPricingServiceInterface
{
    /**
     * Cache key for available models.
     */
    private const CACHE_KEY_MODELS = 'ai_models:available';

    /**
     * Cache key prefix for user default model.
     */
    private const CACHE_KEY_USER_DEFAULT = 'ai_models:user_default:';

    /**
     * Cache TTL for models (1 hour).
     */
    private const CACHE_TTL_MODELS = 3600;

    /**
     * Cache TTL for user preferences (24 hours).
     */
    private const CACHE_TTL_USER_PREF = 86400;

    /**
     * Token estimates by analysis type.
     */
    private const ANALYSIS_TOKENS = [
        'quick' => ['input' => 400, 'output' => 100, 'total' => 500],
        'standard' => ['input' => 1600, 'output' => 400, 'total' => 2000],
        'deep' => ['input' => 4000, 'output' => 1000, 'total' => 5000],
    ];

    /**
     * Input/output token ratio (4:1).
     */
    private const INPUT_OUTPUT_RATIO = 4;

    /**
     * Create a new service instance.
     */
    public function __construct(
        protected OpenRouterSyncService $syncService
    ) {}

    /**
     * {@inheritdoc}
     */
    public function getAvailableModels(bool $forceRefresh = false): Collection
    {
        if ($forceRefresh) {
            Cache::forget(self::CACHE_KEY_MODELS);
        }

        return Cache::remember(
            self::CACHE_KEY_MODELS,
            self::CACHE_TTL_MODELS,
            fn() => AIModel::active()
                ->ordered()
                ->get()
        );
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
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

    /**
     * {@inheritdoc}
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
            'is_free_tier' => (bool) $model->is_free_tier,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function calculateAnalysisCost(string $modelId, int $estimatedTokens, string $analysisType = 'standard'): array
    {
        // Handle non-positive token counts
        if ($estimatedTokens <= 0) {
            return $this->emptyCostResult($modelId, $analysisType);
        }

        // Get the model
        $model = AIModel::active()
            ->where('model_id', $modelId)
            ->first();

        if (!$model) {
            return $this->emptyCostResult($modelId, $analysisType);
        }

        // Free tier models have no cost
        if ($model->is_free_tier) {
            $tokenData = $this->calculateTokenSplit($estimatedTokens, $analysisType);
            return [
                'model_id' => $modelId,
                'analysis_type' => $analysisType,
                'input_tokens' => $tokenData['input_tokens'],
                'output_tokens' => $tokenData['output_tokens'],
                'input_cost' => 0.0,
                'output_cost' => 0.0,
                'total_cost' => 0,
            ];
        }

        // Calculate token split based on analysis type
        $tokenData = $this->calculateTokenSplit($estimatedTokens, $analysisType);

        // Calculate costs
        $inputCost = ($tokenData['input_tokens'] / 1000) * (float) $model->cost_per_1k_input;
        $outputCost = ($tokenData['output_tokens'] / 1000) * (float) $model->cost_per_1k_output;
        $totalCost = $inputCost + $outputCost;

        return [
            'model_id' => $modelId,
            'analysis_type' => $analysisType,
            'input_tokens' => $tokenData['input_tokens'],
            'output_tokens' => $tokenData['output_tokens'],
            'input_cost' => round($inputCost, 6),
            'output_cost' => round($outputCost, 6),
            'total_cost' => round($totalCost, 2), // Rounded to 2 decimal places
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function syncModelsFromOpenRouter(): int
    {
        try {
            $count = $this->syncService->syncModels();

            // Invalidate cache after sync
            Cache::forget(self::CACHE_KEY_MODELS);

            Log::info('OpenRouter model sync completed', [
                'synced_count' => $count,
            ]);

            return $count;
        } catch (\Exception $e) {
            Log::error('OpenRouter model sync failed', [
                'error' => $e->getMessage(),
            ]);

            // Still invalidate cache to ensure fresh data on next request
            Cache::forget(self::CACHE_KEY_MODELS);

            return 0;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getUserDefaultModel(User $user): ?AIModel
    {
        $cacheKey = self::CACHE_KEY_USER_DEFAULT . $user->id;

        // Try to get model ID from cache
        $modelId = Cache::remember(
            $cacheKey,
            self::CACHE_TTL_USER_PREF,
            function () use ($user) {
                $preference = UserModelPreference::where('user_id', $user->id)
                    ->where('is_default', true)
                    ->first();

                return $preference?->model_id;
            }
        );

        if (!$modelId) {
            return null;
        }

        // Look up the model and ensure it's still active
        return AIModel::active()->find($modelId);
    }

    /**
     * {@inheritdoc}
     */
    public function setUserDefaultModel(User $user, int $modelId): void
    {
        // Validate model exists and is active
        $model = AIModel::active()->find($modelId);

        if (!$model) {
            throw new \InvalidArgumentException(
                "Model with ID {$modelId} does not exist or is not active."
            );
        }

        // Clear any existing default for this user
        UserModelPreference::where('user_id', $user->id)
            ->where('is_default', true)
            ->update(['is_default' => false]);

        // Update existing preference or create new one
        UserModelPreference::updateOrCreate(
            [
                'user_id' => $user->id,
                'model_id' => $modelId,
            ],
            [
                'is_default' => true,
            ]
        );

        // Invalidate user's default model cache
        Cache::forget(self::CACHE_KEY_USER_DEFAULT . $user->id);
    }

    /**
     * Calculate token split based on estimated tokens and analysis type.
     *
     * @param int $estimatedTokens
     * @param string $analysisType
     * @return array
     */
    private function calculateTokenSplit(int $estimatedTokens, string $analysisType): array
    {
        // Use predefined token counts for standard analysis types
        if (isset(self::ANALYSIS_TOKENS[$analysisType])) {
            $ratios = self::ANALYSIS_TOKENS[$analysisType];
            
            // Use the predefined input/output split for this analysis type
            return [
                'input_tokens' => $ratios['input'],
                'output_tokens' => $ratios['output'],
            ];
        }

        // For custom analysis types, calculate based on 4:1 input/output ratio
        $inputTokens = (int) ($estimatedTokens * self::INPUT_OUTPUT_RATIO / (self::INPUT_OUTPUT_RATIO + 1));
        $outputTokens = $estimatedTokens - $inputTokens;

        return [
            'input_tokens' => $inputTokens,
            'output_tokens' => $outputTokens,
        ];
    }

    /**
     * Return an empty cost result for invalid scenarios.
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
            'input_cost' => 0.0,
            'output_cost' => 0.0,
            'total_cost' => 0,
        ];
    }
}
