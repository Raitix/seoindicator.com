<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AIModel;
use App\Services\AI\ModelSelectionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * AI Model Controller - API endpoint for model selection.
 *
 * TASK-31-14: API Endpoint for Model Selection
 *
 * Returns available AI models with context windows and pricing information
 * for frontend display and user model selection.
 *
 * @see TEST-31-14 for test documentation
 */
class AIModelController extends Controller
{
    /**
     * Default model ID.
     */
    private const DEFAULT_MODEL = 'google/gemini-2.0-flash-exp:free';

    /**
     * Create a new controller instance.
     */
    public function __construct(
        protected ModelSelectionService $modelService
    ) {}

    /**
     * Get available AI models.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        // Get available free-tier models
        $models = $this->modelService->getAvailableModels();

        // Get default model
        $defaultModel = $this->getDefaultModel();

        // Get user preference if authenticated
        $userPreference = $this->getUserPreference($request);

        // Transform models to response format
        $formattedModels = $models->map(fn ($model) => $this->formatModel($model))->values();

        return response()->json([
            'success' => true,
            'data' => [
                'models' => $formattedModels,
                'default_model' => $defaultModel,
                'user_preference' => $userPreference,
            ],
        ]);
    }

    /**
     * Get the default model ID.
     *
     * @return string
     */
    protected function getDefaultModel(): string
    {
        return self::DEFAULT_MODEL;
    }

    /**
     * Get the authenticated user's preferred model.
     *
     * @param Request $request
     * @return string|null
     */
    protected function getUserPreference(Request $request): ?string
    {
        if (!auth()->check()) {
            return null;
        }

        return $this->modelService->getUserPreference(auth()->user());
    }

    /**
     * Format a model for the response.
     *
     * @param AIModel $model
     * @return array
     */
    protected function formatModel(AIModel $model): array
    {
        return [
            'id' => $model->model_id,
            'name' => $model->display_name,
            'provider' => $model->provider,
            'context_window' => $model->context_window,
            'is_free' => $model->is_free_tier,
            'is_recommended' => $model->model_id === self::DEFAULT_MODEL,
            'capabilities' => $model->capabilities ?? ['text', 'json'],
            'avg_latency_ms' => $this->getAvgLatency($model),
            'best_for' => $this->getBestForText($model),
        ];
    }

    /**
     * Get average latency for a model.
     *
     * @param AIModel $model
     * @return int
     */
    protected function getAvgLatency(AIModel $model): int
    {
        return match ($model->model_id) {
            'google/gemini-2.0-flash-exp:free' => 3000,
            'qwen/qwen-2-7b-instruct:free' => 2500,
            'mistralai/mistral-7b-instruct:free' => 2000,
            'meta-llama/llama-3-8b-instruct:free' => 1500,
            default => 2000,
        };
    }

    /**
     * Get "best for" text for a model.
     *
     * @param AIModel $model
     * @return string
     */
    protected function getBestForText(AIModel $model): string
    {
        return match ($model->model_id) {
            'google/gemini-2.0-flash-exp:free' => 'Large pages (100K+ characters)',
            'qwen/qwen-2-7b-instruct:free' => 'Medium pages',
            'mistralai/mistral-7b-instruct:free' => 'Fast analysis',
            'meta-llama/llama-3-8b-instruct:free' => 'Small pages only',
            default => 'General use',
        };
    }
}
