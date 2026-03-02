<?php

namespace App\Services\Billing;

use App\Models\AIModel;
use App\Models\User;
use App\Models\UserModelPreference;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Service for managing AI model data, pricing calculations, and OpenRouter sync.
 *
 * TASK-26-16: ModelPricingService
 *
 * @see TEST-26-16 for test documentation
 */
class ModelPricingService
{
    /**
     * Cache key for available models.
     */
    protected const CACHE_KEY_MODELS = 'ai_models_available';

    /**
     * Cache TTL in seconds (1 hour).
     */
    protected const CACHE_TTL = 3600;

    /**
     * Get all active AI models.
     *
     * @return Collection<AIModel>
     */
    public function getAvailableModels(): Collection
    {
        return Cache::remember(self::CACHE_KEY_MODELS, self::CACHE_TTL, function () {
            return AIModel::where('is_active', true)->get();
        });
    }

    /**
     * Get a single model by ID.
     *
     * @param int $id
     * @return AIModel|null
     */
    public function getModelById(int $id): ?AIModel
    {
        return AIModel::find($id);
    }

    /**
     * Get a model by provider and model ID.
     *
     * @param string $provider
     * @param string $modelId
     * @return AIModel|null
     */
    public function getModelByProviderId(string $provider, string $modelId): ?AIModel
    {
        return AIModel::where('provider', $provider)
            ->where('model_id', $modelId)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Get pricing breakdown for a model.
     *
     * @param string $modelId
     * @return array|null
     */
    public function getModelPricing(string $modelId): ?array
    {
        $model = AIModel::where('model_id', $modelId)
            ->where('is_active', true)
            ->first();

        if (!$model) {
            return null;
        }

        return [
            'input_per_1k' => (float) $model->cost_per_1k_input,
            'output_per_1k' => (float) $model->cost_per_1k_output,
            'input_per_1m' => (float) $model->cost_per_1k_input * 1000,
            'output_per_1m' => (float) $model->cost_per_1k_output * 1000,
        ];
    }

    /**
     * Calculate analysis cost based on tokens.
     *
     * @param string $modelId
     * @param int $estimatedTokens Total or input tokens
     * @param int|null $outputTokens Output tokens (optional, estimated if not provided)
     * @return float Cost rounded to 2 decimal places
     */
    public function calculateAnalysisCost(string $modelId, int $estimatedTokens, ?int $outputTokens = null): float
    {
        $pricing = $this->getModelPricing($modelId);

        if (!$pricing) {
            return 0.0;
        }

        // If only total tokens provided, estimate 70% input / 30% output
        if ($outputTokens === null) {
            $inputTokens = (int) ($estimatedTokens * 0.7);
            $outputTokens = (int) ($estimatedTokens * 0.3);
        } else {
            $inputTokens = $estimatedTokens;
        }

        $inputCost = ($inputTokens / 1000) * $pricing['input_per_1k'];
        $outputCost = ($outputTokens / 1000) * $pricing['output_per_1k'];

        return round($inputCost + $outputCost, 2);
    }

    /**
     * Sync models from OpenRouter API.
     *
     * @return int Number of models synced
     */
    public function syncModelsFromOpenRouter(): int
    {
        try {
            $response = Http::timeout(30)
                ->get('https://openrouter.ai/api/v1/models');

            if (!$response->successful()) {
                Log::warning('OpenRouter sync failed', [
                    'status' => $response->status(),
                ]);
                return 0;
            }

            $data = $response->json('data', []);
            $syncedCount = 0;
            $seenModelIds = [];

            foreach ($data as $modelData) {
                if (empty($modelData['id']) || empty($modelData['name'])) {
                    continue;
                }

                $seenModelIds[] = $modelData['id'];

                $pricing = $modelData['pricing'] ?? [];
                $promptPrice = max(0, (float) ($pricing['prompt'] ?? 0));
                $completionPrice = max(0, (float) ($pricing['completion'] ?? 0));

                $capabilities = $this->parseCapabilities($modelData);

                $model = AIModel::updateOrCreate(
                    [
                        'provider' => 'openrouter',
                        'model_id' => $modelData['id'],
                    ],
                    [
                        'display_name' => substr($modelData['name'], 0, 255),
                        'cost_per_1k_input' => $promptPrice * 1000,
                        'cost_per_1k_output' => $completionPrice * 1000,
                        'context_window' => (int) ($modelData['context_length'] ?? 4096),
                        'is_free_tier' => $promptPrice == 0 && $completionPrice == 0,
                        'capabilities' => !empty($capabilities) ? json_encode($capabilities) : null,
                        'is_active' => true,
                        'last_synced_at' => now(),
                    ]
                );

                $syncedCount++;
            }

            // Deactivate models not in API response
            if (!empty($seenModelIds)) {
                AIModel::where('provider', 'openrouter')
                    ->whereNotIn('model_id', $seenModelIds)
                    ->update(['is_active' => false]);
            }

            // Invalidate cache
            $this->invalidateModelCache();

            Log::info('OpenRouter sync completed', [
                'synced' => $syncedCount,
                'deactivated' => count($seenModelIds) > 0 ? AIModel::where('provider', 'openrouter')->where('is_active', false)->count() : 0,
            ]);

            return $syncedCount;
        } catch (\Exception $e) {
            Log::error('OpenRouter sync error', [
                'message' => $e->getMessage(),
            ]);
            return 0;
        }
    }

    /**
     * Parse capabilities from model data.
     *
     * @param array $modelData
     * @return array
     */
    protected function parseCapabilities(array $modelData): array
    {
        $capabilities = [];

        $modality = $modelData['architecture']['modality'] ?? '';
        if (str_contains($modality, 'image')) {
            $capabilities['vision'] = true;
        }

        return $capabilities;
    }

    /**
     * Invalidate the model cache.
     */
    public function invalidateModelCache(): void
    {
        Cache::forget(self::CACHE_KEY_MODELS);
    }

    /**
     * Get user's default model.
     *
     * @param User $user
     * @return AIModel|null
     */
    public function getUserDefaultModel(User $user): ?AIModel
    {
        $cacheKey = "user_default_model:{$user->id}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($user) {
            $preference = UserModelPreference::where('user_id', $user->id)
                ->where('is_default', true)
                ->first();

            if (!$preference) {
                return null;
            }

            $model = AIModel::where('id', $preference->model_id)
                ->where('is_active', true)
                ->first();

            return $model;
        });
    }

    /**
     * Set user's default model.
     *
     * @param User $user
     * @param int $modelId
     * @throws \InvalidArgumentException If model is not active
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException If model doesn't exist
     */
    public function setUserDefaultModel(User $user, int $modelId): void
    {
        $model = AIModel::findOrFail($modelId);

        if (!$model->is_active) {
            throw new \InvalidArgumentException('Cannot set inactive model as default');
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

        // Invalidate user default cache
        Cache::forget("user_default_model:{$user->id}");
    }
}
