<?php

namespace App\Services;

use App\Models\AIModel;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Service for syncing AI models from OpenRouter API.
 *
 * Fetches model list, pricing, and capabilities from OpenRouter
 * and updates the local ai_models table.
 */
class OpenRouterSyncService
{
    /**
     * OpenRouter API base URL.
     */
    private const API_BASE_URL = 'https://openrouter.ai/api/v1';

    /**
     * Default context window when not specified.
     */
    private const DEFAULT_CONTEXT_WINDOW = 4096;

    /**
     * Default input tokens for cost estimation.
     */
    private const DEFAULT_INPUT_TOKENS = 2000;

    /**
     * Default output tokens for cost estimation.
     */
    private const DEFAULT_OUTPUT_TOKENS = 500;

    /**
     * Sync models from OpenRouter API.
     *
     * @param bool $deactivateMissing Whether to deactivate models not in API response
     * @return int Number of models synced
     */
    public function syncModels(bool $deactivateMissing = true): int
    {
        $apiKey = config('services.openrouter.api_key');

        // In testing environment, allow sync to proceed without API key
        // (HTTP fake will intercept the request)
        if (app()->environment() !== 'testing' && empty($apiKey)) {
            Log::warning('OpenRouter sync skipped: API key not configured');
            return 0;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$apiKey}",
                'Content-Type' => 'application/json',
            ])
            ->timeout(30)
            ->get(self::API_BASE_URL . '/models');

            if (!$response->successful()) {
                Log::error('OpenRouter API error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return 0;
            }

            $data = $response->json();

            if (!isset($data['data']) || !is_array($data['data'])) {
                Log::warning('OpenRouter API returned invalid data structure');
                return 0;
            }

            $syncedCount = 0;
            $syncedModelIds = [];

            foreach ($data['data'] as $modelData) {
                if ($this->isValidModelData($modelData)) {
                    $this->syncModel($modelData);
                    $syncedModelIds[] = $modelData['id'];
                    $syncedCount++;
                }
            }

            // Update last_synced_at for synced models
            if (!empty($syncedModelIds)) {
                AIModel::whereIn('model_id', $syncedModelIds)
                    ->update(['last_synced_at' => now()]);
            }

            // Deactivate models that were not in the API response
            if ($deactivateMissing) {
                $this->deactivateMissingModels($syncedModelIds);
            }

            Log::info('OpenRouter sync completed', [
                'synced' => $syncedCount,
                'total_in_response' => count($data['data']),
            ]);

            return $syncedCount;

        } catch (\Exception $e) {
            Log::error('OpenRouter sync failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return 0;
        }
    }

    /**
     * Get pricing information for a specific model.
     *
     * @param string $modelId
     * @return array|null
     */
    public function getModelPricing(string $modelId): ?array
    {
        $model = AIModel::where('model_id', $modelId)->first();

        if (!$model) {
            return null;
        }

        return [
            'input_per_1k' => (float) $model->cost_per_1k_input,
            'output_per_1k' => (float) $model->cost_per_1k_output,
            'input_per_1m' => (float) ($model->cost_per_1k_input * 1000),
            'output_per_1m' => (float) ($model->cost_per_1k_output * 1000),
        ];
    }

    /**
     * Validate model data from API.
     *
     * @param array $modelData
     * @return bool
     */
    private function isValidModelData(array $modelData): bool
    {
        return !empty($modelData['id']) && is_string($modelData['id']);
    }

    /**
     * Sync a single model from API data.
     *
     * @param array $modelData
     * @return AIModel
     */
    private function syncModel(array $modelData): AIModel
    {
        $pricing = $this->parsePricing($modelData['pricing'] ?? []);
        $contextWindow = $this->parseContextWindow($modelData);
        $capabilities = $this->parseCapabilities($modelData);
        $isFreeTier = $this->isFreeTier($modelData, $pricing);
        $displayName = $this->sanitizeDisplayName($modelData['name'] ?? $modelData['id']);

        return AIModel::updateOrCreate(
            [
                'provider' => 'openrouter',
                'model_id' => $modelData['id'],
            ],
            [
                'display_name' => $displayName,
                'cost_per_1k_input' => $pricing['input'],
                'cost_per_1k_output' => $pricing['output'],
                'context_window' => $contextWindow,
                'is_free_tier' => $isFreeTier,
                'free_tier_limit' => $this->parseFreeTierLimit($modelData),
                'capabilities' => $capabilities,
                'is_active' => true,
            ]
        );
    }

    /**
     * Parse pricing from API response.
     *
     * @param array|string|null $pricing
     * @return array
     */
    private function parsePricing($pricing): array
    {
        if (!is_array($pricing)) {
            return ['input' => 0, 'output' => 0];
        }

        $promptPrice = $this->parsePrice($pricing['prompt'] ?? null);
        $completionPrice = $this->parsePrice($pricing['completion'] ?? null);

        // OpenRouter returns per-token pricing, convert to per-1k tokens
        return [
            'input' => max(0, $promptPrice * 1000),
            'output' => max(0, $completionPrice * 1000),
        ];
    }

    /**
     * Parse a single price value.
     *
     * @param mixed $value
     * @return float
     */
    private function parsePrice($value): float
    {
        if ($value === null) {
            return 0.0;
        }

        $price = is_string($value) ? (float) $value : (float) $value;

        // Handle negative values (treat as 0)
        return max(0.0, $price);
    }

    /**
     * Parse context window from API data.
     *
     * @param array $modelData
     * @return int
     */
    private function parseContextWindow(array $modelData): int
    {
        // Try root level context_length first
        if (!empty($modelData['context_length'])) {
            return (int) $modelData['context_length'];
        }

        // Fallback to top_provider.context_length
        if (!empty($modelData['top_provider']['context_length'])) {
            return (int) $modelData['top_provider']['context_length'];
        }

        return self::DEFAULT_CONTEXT_WINDOW;
    }

    /**
     * Parse capabilities from API data.
     *
     * @param array $modelData
     * @return array|null
     */
    private function parseCapabilities(array $modelData): ?array
    {
        if (!isset($modelData['architecture'])) {
            return null;
        }

        $capabilities = [];
        $architecture = $modelData['architecture'];
        $modality = $architecture['modality'] ?? '';

        // Detect vision capability from modality
        if (Str::contains($modality, ['image', 'vision', 'multimodal'])) {
            $capabilities['vision'] = true;
        }

        // Detect tools capability (often indicated in description or name)
        $description = strtolower($modelData['description'] ?? '');
        $name = strtolower($modelData['name'] ?? '');
        
        if (Str::contains($description . ' ' . $name, ['tool', 'function call'])) {
            $capabilities['tools'] = true;
        }

        // Add streaming capability (most models support this)
        $capabilities['streaming'] = true;

        return !empty($capabilities) ? $capabilities : null;
    }

    /**
     * Determine if a model is free tier.
     *
     * @param array $modelData
     * @param array $pricing
     * @return bool
     */
    private function isFreeTier(array $modelData, array $pricing): bool
    {
        // Check for :free suffix in model ID
        if (Str::endsWith($modelData['id'], ':free')) {
            return true;
        }

        // Check if both input and output prices are 0
        if ($pricing['input'] == 0 && $pricing['output'] == 0) {
            return true;
        }

        return false;
    }

    /**
     * Parse free tier limit from API data.
     *
     * @param array $modelData
     * @return int|null
     */
    private function parseFreeTierLimit(array $modelData): ?int
    {
        // Check for explicit free tier limit
        if (!empty($modelData['per_request_limits']['prompt_tokens'])) {
            return (int) $modelData['per_request_limits']['prompt_tokens'];
        }

        return null;
    }

    /**
     * Sanitize and truncate display name.
     *
     * @param string $name
     * @return string
     */
    private function sanitizeDisplayName(string $name): string
    {
        // Remove any script tags or dangerous content
        $name = strip_tags($name);
        $name = htmlspecialchars($name, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Truncate to max length
        return Str::limit($name, 255, '');
    }

    /**
     * Deactivate models that are no longer in the API.
     *
     * @param array $activeModelIds
     * @return int Number of deactivated models
     */
    public function deactivateMissingModels(array $activeModelIds): int
    {
        return AIModel::where('provider', 'openrouter')
            ->whereNotIn('model_id', $activeModelIds)
            ->where('is_active', true)
            ->update(['is_active' => false]);
    }
}
