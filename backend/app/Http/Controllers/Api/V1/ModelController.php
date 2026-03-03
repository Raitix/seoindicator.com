<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\Billing\ModelPricingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * ModelController - API endpoint for listing available AI models.
 *
 * TASK-26-17: Models List API Endpoint
 *
 * @see TEST-26-17 for test documentation
 */
class ModelController extends Controller
{
    /**
     * Valid sort options.
     */
    protected const VALID_SORTS = ['name', 'price', 'context'];

    /**
     * Create a new controller instance.
     */
    public function __construct(
        protected ModelPricingService $modelPricingService
    ) {}

    /**
     * List available AI models with pricing information.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        // Validate query parameters
        $validated = $this->validateRequest($request);

        // Get filters
        $filters = $this->buildFilters($validated);

        // Get models from service
        $models = $this->modelPricingService->getAvailableModels();

        // Apply filters
        $models = $this->applyFilters($models, $filters);

        // Apply sorting
        $models = $this->applySorting($models, $validated['sort'] ?? 'name');

        // Get user's default model if authenticated
        $userDefaultModel = $this->getUserDefaultModel($request);

        // Transform to response format
        $data = $this->transformModels($models, $userDefaultModel);

        // Build response
        return response()->json([
            'success' => true,
            'data' => $data,
            'meta' => [
                'total' => count($data),
                'free_tier_count' => collect($data)->where('is_free_tier', true)->count(),
            ],
        ]);
    }

    /**
     * Validate request parameters.
     *
     * @param Request $request
     * @return array
     */
    protected function validateRequest(Request $request): array
    {
        $validated = $request->validate([
            'provider' => ['sometimes', 'string', 'max:255'],
            'free_only' => ['sometimes', 'nullable'],
            'capability' => ['sometimes', 'string', 'max:255'],
            'sort' => ['sometimes', 'string', 'in:' . implode(',', self::VALID_SORTS)],
        ]);

        // Convert free_only to boolean (accepts "true", "false", "1", "0", 1, 0)
        if (isset($validated['free_only'])) {
            $value = $validated['free_only'];
            $validated['free_only'] = in_array($value, [true, 'true', '1', 1], true);
        }

        return $validated;
    }

    /**
     * Build filters array from validated input.
     *
     * @param array $validated
     * @return array
     */
    protected function buildFilters(array $validated): array
    {
        $filters = [];

        if (isset($validated['provider'])) {
            $filters['provider'] = $validated['provider'];
        }

        if (isset($validated['free_only']) && $validated['free_only'] === true) {
            $filters['free_only'] = true;
        }

        if (isset($validated['capability'])) {
            $filters['capability'] = $validated['capability'];
        }

        return $filters;
    }

    /**
     * Apply filters to models collection.
     *
     * @param Collection $models
     * @param array $filters
     * @return Collection
     */
    protected function applyFilters(Collection $models, array $filters): Collection
    {
        // Filter by provider
        if (!empty($filters['provider'])) {
            $models = $models->filter(function ($model) use ($filters) {
                $provider = is_object($model) ? ($model->provider ?? null) : ($model['provider'] ?? null);
                return $provider === $filters['provider'];
            });
        }

        // Filter by free_only
        if (!empty($filters['free_only'])) {
            $models = $models->filter(function ($model) {
                $isFree = is_object($model) ? ($model->is_free_tier ?? false) : ($model['is_free_tier'] ?? false);
                return $isFree === true;
            });
        }

        // Filter by capability
        if (!empty($filters['capability'])) {
            $models = $models->filter(function ($model) use ($filters) {
                $capabilities = is_object($model)
                    ? ($model->capabilities ?? [])
                    : ($model['capabilities'] ?? []);
                
                // Handle JSON-encoded capabilities
                if (is_string($capabilities)) {
                    $capabilities = json_decode($capabilities, true) ?? [];
                }
                
                return in_array($filters['capability'], $capabilities);
            });
        }

        return $models;
    }

    /**
     * Apply sorting to models collection.
     *
     * @param Collection $models
     * @param string $sort
     * @return Collection
     */
    protected function applySorting(Collection $models, string $sort): Collection
    {
        // Default to name if invalid sort
        if (!in_array($sort, self::VALID_SORTS)) {
            $sort = 'name';
        }

        return match ($sort) {
            'name' => $models->sortBy(function ($model) {
                $name = is_object($model)
                    ? ($model->display_name ?? '')
                    : ($model['display_name'] ?? '');
                return $name;
            })->values(),
            
            'price' => $models->sortBy(function ($model) {
                return is_object($model)
                    ? ($model->cost_per_1k_input ?? PHP_FLOAT_MAX)
                    : ($model['cost_per_1k_input'] ?? PHP_FLOAT_MAX);
            })->values(),
            
            'context' => $models->sortByDesc(function ($model) {
                return is_object($model)
                    ? ($model->context_window ?? 0)
                    : ($model['context_window'] ?? 0);
            })->values(),
            
            default => $models->values(),
        };
    }

    /**
     * Get the authenticated user's default model.
     *
     * @param Request $request
     * @return mixed
     */
    protected function getUserDefaultModel(Request $request): mixed
    {
        if (!$request->user()) {
            return null;
        }

        try {
            return $this->modelPricingService->getUserDefaultModel($request->user());
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get detailed pricing information for a specific model.
     *
     * TASK-26-18: Model Pricing API Endpoint
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function pricing(Request $request, int $id): JsonResponse
    {
        // Validate query parameters
        $validated = $request->validate([
            'analysis_type' => ['sometimes', 'string', 'in:quick,standard,deep'],
            'estimated_tokens' => ['sometimes', 'integer', 'min:1'],
        ]);

        // Get model from service
        $model = $this->modelPricingService->getModelById($id);

        if (!$model) {
            return response()->json([
                'success' => false,
                'message' => 'Model not found',
            ], 404);
        }

        // Build pricing response
        $data = $this->buildPricingResponse($model, $validated);

        return response()->json([
            'success' => true,
            'data' => $data,
        ], 200, [], JSON_PRESERVE_ZERO_FRACTION);
    }

    /**
     * Build the pricing response data.
     *
     * @param mixed $model
     * @param array $params
     * @return array
     */
    protected function buildPricingResponse($model, array $params): array
    {
        // Model info
        $modelInfo = [
            'id' => $model->id,
            'display_name' => $model->display_name,
            'provider' => $model->provider,
            'model_id' => $model->model_id,
        ];

        // Pricing breakdown
        $pricing = [
            'cost_per_1m_input' => round((float) $model->cost_per_1k_input * 1000, 2),
            'cost_per_1m_output' => round((float) $model->cost_per_1k_output * 1000, 2),
            'cost_per_1k_input' => round((float) $model->cost_per_1k_input, 4),
            'cost_per_1k_output' => round((float) $model->cost_per_1k_output, 4),
        ];

        // Token estimates by analysis type
        $estimates = [
            'quick_analysis' => $this->calculateEstimate($model, 500),
            'standard_analysis' => $this->calculateEstimate($model, 2000),
            'deep_analysis' => $this->calculateEstimate($model, 5000),
        ];

        // Free tier info
        $freeTier = [
            'available' => (bool) $model->is_free_tier,
            'limit' => null,
        ];

        $data = [
            'model' => $modelInfo,
            'pricing' => $pricing,
            'estimates' => $estimates,
            'free_tier' => $freeTier,
        ];

        // Add specific estimate if analysis_type is provided
        if (isset($params['analysis_type'])) {
            $tokens = match ($params['analysis_type']) {
                'quick' => 500,
                'standard' => 2000,
                'deep' => 5000,
                default => 2000,
            };

            $data['estimate'] = [
                'type' => $params['analysis_type'],
                'tokens' => $tokens,
                'cost' => $this->calculateCost($model, $tokens),
            ];
        }

        // Add custom estimate if estimated_tokens is provided
        if (isset($params['estimated_tokens'])) {
            $data['custom_estimate'] = [
                'tokens' => $params['estimated_tokens'],
                'cost' => $this->calculateCost($model, $params['estimated_tokens']),
            ];
        }

        return $data;
    }

    /**
     * Calculate estimate for a given token count.
     *
     * @param mixed $model
     * @param int $tokens
     * @return array
     */
    protected function calculateEstimate($model, int $tokens): array
    {
        return [
            'tokens' => $tokens,
            'cost' => $this->calculateCost($model, $tokens),
        ];
    }

    /**
     * Calculate cost for a given token count.
     *
     * @param mixed $model
     * @param int $tokens
     * @return float
     */
    protected function calculateCost($model, int $tokens): float
    {
        // Use 70% input / 30% output split for estimates
        $inputTokens = (int) ($tokens * 0.7);
        $outputTokens = (int) ($tokens * 0.3);

        $inputCost = ($inputTokens / 1000) * (float) $model->cost_per_1k_input;
        $outputCost = ($outputTokens / 1000) * (float) $model->cost_per_1k_output;

        return round($inputCost + $outputCost, 2);
    }

    /**
     * Transform models to response format.
     *
     * @param Collection $models
     * @param mixed $userDefaultModel
     * @return array
     */
    protected function transformModels(Collection $models, mixed $userDefaultModel): array
    {
        $defaultModelId = null;
        if ($userDefaultModel) {
            $defaultModelId = is_object($userDefaultModel)
                ? $userDefaultModel->id
                : ($userDefaultModel['id'] ?? null);
        }

        return $models->map(function ($model) use ($defaultModelId) {
            // Handle both object and array representations
            if (is_object($model)) {
                $capabilities = $model->capabilities ?? [];
                if (is_string($capabilities)) {
                    $capabilities = json_decode($capabilities, true) ?? [];
                }
                
                return [
                    'id' => $model->id,
                    'provider' => $model->provider,
                    'model_id' => $model->model_id,
                    'display_name' => $model->display_name,
                    'cost_per_1k_input' => (float) $model->cost_per_1k_input,
                    'cost_per_1k_output' => (float) $model->cost_per_1k_output,
                    'estimated_cost_per_analysis' => (float) ($model->estimated_cost_per_analysis ?? 0),
                    'context_window' => (int) $model->context_window,
                    'is_free_tier' => (bool) $model->is_free_tier,
                    'capabilities' => $capabilities,
                    'is_user_default' => $defaultModelId === $model->id,
                ];
            }

            // Array representation
            $capabilities = $model['capabilities'] ?? [];
            if (is_string($capabilities)) {
                $capabilities = json_decode($capabilities, true) ?? [];
            }

            return [
                'id' => $model['id'],
                'provider' => $model['provider'],
                'model_id' => $model['model_id'],
                'display_name' => $model['display_name'],
                'cost_per_1k_input' => (float) $model['cost_per_1k_input'],
                'cost_per_1k_output' => (float) $model['cost_per_1k_output'],
                'estimated_cost_per_analysis' => (float) ($model['estimated_cost_per_analysis'] ?? 0),
                'context_window' => (int) $model['context_window'],
                'is_free_tier' => (bool) $model['is_free_tier'],
                'capabilities' => $capabilities,
                'is_user_default' => $defaultModelId === $model['id'],
            ];
        })->values()->toArray();
    }
}
