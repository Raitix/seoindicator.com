<?php

namespace App\Services;

use App\Models\AIModel;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;

/**
 * Service for syncing AI models from OpenRouter API.
 *
 * Fetches model information including pricing, context windows,
 * and capabilities from the OpenRouter API and stores them in the database.
 */
class OpenRouterSyncService
{
    /**
     * OpenRouter API base URL.
     */
    protected string $apiUrl = 'https://openrouter.ai/api/v1/models';

    /**
     * Default context window when not specified.
     */
    protected int $defaultContextWindow = 4096;

    /**
     * Sync models from OpenRouter API.
     *
     * @return int Number of models synced
     */
    public function syncModels(): int
    {
        $apiKey = Config::get('services.openrouter.api_key');

        if (empty($apiKey)) {
            Log::warning('OpenRouter API key not configured');
            return 0;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ])
            ->timeout(30)
            ->get($this->apiUrl);

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
            $seenModelIds = [];

            foreach ($data['data'] as $modelData) {
                if (!$this->isValidModelData($modelData)) {
                    continue;
                }

                $modelId = $modelData['id'];
                $seenModelIds[] = $modelId;

                $syncData = $this->parseModelData($modelData);

                // Update or create the model
                $model = AIModel::updateOrCreate(
                    [
                        'provider' => 'openrouter',
                        'model_id' => $modelId,
                    ],
                    $syncData
                );

                if ($model->wasRecentlyCreated || $model->wasChanged()) {
                    $syncedCount++;
                }
            }

            Log::info('OpenRouter sync completed', [
                'synced' => $syncedCount,
                'total' => count($seenModelIds),
            ]);

            return $syncedCount;

        } catch (\Exception $e) {
            Log::error('OpenRouter sync failed', [
                'error' => $e->getMessage(),
            ]);
            return 0;
        }
    }

    /**
     * Get pricing for a specific model.
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
            'input_per_1m' => round((float) $model->cost_per_1k_input * 1000, 2),
            'output_per_1m' => round((float) $model->cost_per_1k_output * 1000, 2),
        ];
    }

    /**
     * Validate model data from API.
     *
     * @param array $modelData
     * @return bool
     */
    protected function isValidModelData(array $modelData): bool
    {
        if (!isset($modelData['id']) || empty($modelData['id'])) {
            return false;
        }

        if (!is_string($modelData['id'])) {
            return false;
        }

        return true;
    }

    /**
     * Parse model data from API response.
     *
     * @param array $modelData
     * @return array
     */
    protected function parseModelData(array $modelData): array
    {
        $pricing = $modelData['pricing'] ?? [];
        
        // OpenRouter returns per-token pricing
        // Convert to per-1k tokens
        $promptPrice = $this->parsePrice($pricing['prompt'] ?? 0);
        $completionPrice = $this->parsePrice($pricing['completion'] ?? 0);

        // Cost per 1k tokens = per-token * 1000
        $costPer1kInput = max(0, $promptPrice * 1000);
        $costPer1kOutput = max(0, $completionPrice * 1000);

        // Determine free tier status
        $isFreeTier = $this->isFreeTier($modelData, $costPer1kInput, $costPer1kOutput);

        // Parse capabilities
        $capabilities = $this->parseCapabilities($modelData);

        // Get context window
        $contextWindow = $this->parseContextWindow($modelData);

        // Sanitize and truncate display name
        $displayName = $this->sanitizeDisplayName($modelData['name'] ?? $modelData['id']);

        return [
            'provider' => 'openrouter',
            'model_id' => $modelData['id'],
            'display_name' => $displayName,
            'cost_per_1k_input' => round($costPer1kInput, 6),
            'cost_per_1k_output' => round($costPer1kOutput, 6),
            'context_window' => $contextWindow,
            'is_free_tier' => $isFreeTier,
            'free_tier_limit' => $this->parseFreeTierLimit($modelData),
            'capabilities' => $capabilities,
            'is_active' => true,
            'last_synced_at' => now(),
        ];
    }

    /**
     * Parse price value (handles string or numeric).
     *
     * @param mixed $value
     * @return float
     */
    protected function parsePrice($value): float
    {
        if (is_numeric($value)) {
            return max(0, (float) $value);
        }

        if (is_string($value)) {
            $parsed = (float) $value;
            return max(0, $parsed);
        }

        return 0.0;
    }

    /**
     * Determine if model is free tier.
     *
     * @param array $modelData
     * @param float $costPer1kInput
     * @param float $costPer1kOutput
     * @return bool
     */
    protected function isFreeTier(array $modelData, float $costPer1kInput, float $costPer1kOutput): bool
    {
        // Check for :free suffix in model ID
        if (str_ends_with($modelData['id'], ':free')) {
            return true;
        }

        // Check if both costs are zero
        if ($costPer1kInput <= 0 && $costPer1kOutput <= 0) {
            return true;
        }

        return false;
    }

    /**
     * Parse capabilities from model data.
     *
     * @param array $modelData
     * @return array|null
     */
    protected function parseCapabilities(array $modelData): ?array
    {
        $capabilities = [];

        // Check for vision capability from modality
        $architecture = $modelData['architecture'] ?? [];
        $modality = $architecture['modality'] ?? '';

        if (str_contains($modality, 'image')) {
            $capabilities['vision'] = true;
        }

        // Check for tools capability (often indicated by model name or description)
        $name = strtolower($modelData['id'] ?? '');
        $description = strtolower($modelData['description'] ?? '');
        
        if (str_contains($name, 'tools') || str_contains($description, 'tool')) {
            $capabilities['tools'] = true;
        }

        // Check for streaming (most models support this)
        $capabilities['streaming'] = true;

        return !empty($capabilities) ? $capabilities : null;
    }

    /**
     * Parse context window from model data.
     *
     * @param array $modelData
     * @return int
     */
    protected function parseContextWindow(array $modelData): int
    {
        // Check for context_length at root level
        if (isset($modelData['context_length']) && is_numeric($modelData['context_length'])) {
            return (int) $modelData['context_length'];
        }

        // Check for context_length in top_provider
        $topProvider = $modelData['top_provider'] ?? [];
        if (isset($topProvider['context_length']) && is_numeric($topProvider['context_length'])) {
            return (int) $topProvider['context_length'];
        }

        return $this->defaultContextWindow;
    }

    /**
     * Parse free tier limit from model data.
     *
     * @param array $modelData
     * @return int|null
     */
    protected function parseFreeTierLimit(array $modelData): ?int
    {
        // OpenRouter doesn't always provide explicit free tier limits
        // Check per_request_limits for hints
        $perRequestLimits = $modelData['per_request_limits'] ?? [];
        
        if (isset($perRequestLimits['prompt_tokens'])) {
            return (int) $perRequestLimits['prompt_tokens'];
        }

        return null;
    }

    /**
     * Sanitize and truncate display name.
     *
     * @param string $name
     * @return string
     */
    protected function sanitizeDisplayName(string $name): string
    {
        // Remove any HTML/script tags for XSS prevention
        $name = strip_tags($name);
        
        // Remove any dangerous characters
        $name = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
        
        // Decode back since we just wanted to strip tags, not encode
        $name = html_entity_decode($name, ENT_QUOTES, 'UTF-8');
        
        // Truncate to max length
        if (strlen($name) > 255) {
            $name = substr($name, 0, 255);
        }

        return $name;
    }
}
