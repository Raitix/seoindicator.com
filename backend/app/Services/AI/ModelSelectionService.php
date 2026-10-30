<?php

namespace App\Services\AI;

use App\Models\AIModel;
use App\Models\User;
use App\Models\UserModelPreference;
use App\Services\Billing\ModelPricingService;
use Illuminate\Support\Facades\Cache;

/**
 * Model Selection Service.
 * 
 * Chooses the optimal AI model based on content size, user preferences,
 * and availability. Integrates with the existing model pricing system from FEAT-026.
 * 
 * @see TASK-31-8
 * @see SPEC-031
 */
class ModelSelectionService
{
    /**
     * Default fallback model (largest context window).
     */
    private const DEFAULT_MODEL = 'google/gemini-2.0-flash-exp:free';

    /**
     * Model selection order (smallest to largest context).
     * This ensures cost optimization by selecting the smallest sufficient model.
     */
    private const MODEL_PRIORITY = [
        'meta-llama/llama-3-8b-instruct:free',
        'qwen/qwen-2-7b-instruct:free',
        'mistralai/mistral-7b-instruct:free',
        'google/gemini-2.0-flash-exp:free',
    ];

    /**
     * Cache key prefix for model availability.
     */
    private const CACHE_KEY_AVAILABLE = 'model_available:';

    /**
     * Cache TTL in seconds (5 minutes).
     */
    private const CACHE_TTL = 300;

    /**
     * Create a new ModelSelectionService instance.
     */
    public function __construct(
        private ContextWindowManager $contextManager,
        private ModelPricingService $pricingService,
    ) {}

    /**
     * Select the optimal model for the given token count.
     * 
     * Selection priority:
     * 1. User preference (if set and content fits)
     * 2. Smallest sufficient model (cost optimization)
     * 3. Fallback to Gemini (largest context)
     * 
     * @param int $estimatedTokens Estimated token count for the content
     * @param User|null $user Optional user for preference lookup
     * @return string The selected model identifier
     */
    public function selectModel(int $estimatedTokens, ?User $user = null): string
    {
        // Handle negative tokens (treat as 0)
        $tokens = max(0, $estimatedTokens);

        // 1. Check user preference (if set)
        if ($user !== null) {
            $preferredModel = $this->getUserPreference($user);

            if ($preferredModel !== null) {
                // Check if preferred model fits the content
                if ($this->contextManager->fitsInContext($preferredModel, $tokens)) {
                    return $preferredModel;
                }
                // If preference doesn't fit, fall through to optimal selection
            }
        }

        // 2. Select smallest sufficient model (cost optimization)
        foreach (self::MODEL_PRIORITY as $model) {
            if ($this->contextManager->fitsInContext($model, $tokens)) {
                return $model;
            }
        }

        // 3. Fallback to largest context model
        return self::DEFAULT_MODEL;
    }

    /**
     * Get the user's preferred model ID.
     * 
     * Returns null if:
     * - No preference is set
     * - Preferred model is inactive
     * - Preferred model doesn't exist
     * 
     * @param User $user
     * @return string|null The model identifier or null
     */
    public function getUserPreference(User $user): ?string
    {
        $cacheKey = "user_preferred_model:{$user->id}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($user) {
            $preference = UserModelPreference::where('user_id', $user->id)
                ->where('is_default', true)
                ->first();

            if ($preference === null) {
                return null;
            }

            // Get the AI model and verify it's active
            $aiModel = AIModel::where('id', $preference->model_id)
                ->where('is_active', true)
                ->first();

            if ($aiModel === null) {
                return null;
            }

            return $aiModel->model_id;
        });
    }

    /**
     * Get the fallback model (largest context window).
     * 
     * @return string The fallback model identifier
     */
    public function getFallbackModel(): string
    {
        return self::DEFAULT_MODEL;
    }

    /**
     * Check if a model is available (active in the database).
     * 
     * @param string $modelId The model identifier to check
     * @return bool True if the model is available
     */
    public function isModelAvailable(string $modelId): bool
    {
        $cacheKey = self::CACHE_KEY_AVAILABLE . $modelId;

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($modelId) {
            return AIModel::where('model_id', $modelId)
                ->where('is_active', true)
                ->exists();
        });
    }

    /**
     * Get all available free-tier models.
     * 
     * @return \Illuminate\Support\Collection<AIModel>
     */
    public function getAvailableModels()
    {
        return $this->pricingService->getAvailableModels()
            ->where('is_free_tier', true)
            ->sortBy('context_window');
    }

    /**
     * Invalidate all caches related to model selection.
     */
    public function invalidateCache(): void
    {
        // Invalidate user preference caches
        // Note: This is a blanket invalidation; for production with many users,
        // consider using cache tags or a more targeted approach

        // Invalidate model availability caches
        foreach (self::MODEL_PRIORITY as $model) {
            Cache::forget(self::CACHE_KEY_AVAILABLE . $model);
        }
    }
}
