<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\AISuggestionRequest;
use App\Services\AI\AISuggestionService;
use App\Services\AI\DTOs\SuggestionResult;
use App\Exceptions\AIServiceUnavailableException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * AI Suggestion API Controller.
 *
 * Handles requests for AI-powered SEO suggestions.
 * Provides endpoints for generating suggestions for various
 * SEO indicators (title, meta_description, robots, etc.).
 *
 * @see https://seoindicator.com/docs/api/ai-suggestions
 */
class AISuggestionController extends Controller
{
    /**
     * Create a new AISuggestionController instance.
     *
     * @param AISuggestionService $service
     */
    public function __construct(
        protected AISuggestionService $service
    ) {}

    /**
     * Generate AI suggestions for an SEO indicator.
     *
     * @param AISuggestionRequest $request
     * @return JsonResponse
     */
    public function generate(AISuggestionRequest $request): JsonResponse
    {
        $startTime = microtime(true);
        $validated = $request->validatedWithDefaults();
        $debugMode = $validated['debug'] && $request->user()?->exists;
        
        try {
            // Set provider if specified
            if ($validated['provider'] !== config('ai.default')) {
                $this->service->setProvider($validated['provider']);
            }
            
            // Get suggestions from service
            $result = $this->service->getSuggestions(
                $validated['indicator'],
                $validated['context']
            );
            
            $response = $this->buildSuccessResponse($result, $debugMode, $startTime);
            
            return response()->json($response, 200);
            
        } catch (AIServiceUnavailableException $e) {
            Log::error('AI suggestion service unavailable', [
                'indicator' => $validated['indicator'],
                'exception' => $e->getMessage(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'AI suggestion service is currently unavailable. Please try again later.',
            ], 503);
            
        } catch (\Exception $e) {
            Log::error('AI suggestion error', [
                'indicator' => $validated['indicator'],
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while generating suggestions.',
            ], 500);
        }
    }

    /**
     * Build the success response structure.
     *
     * @param SuggestionResult $result
     * @param bool $debugMode
     * @param float $startTime
     * @return array<string, mixed>
     */
    protected function buildSuccessResponse(
        SuggestionResult $result,
        bool $debugMode,
        float $startTime
    ): array {
        $response = [
            'success' => true,
            'data' => [
                'indicator' => $result->indicator,
                'score' => $result->score,
                'issues' => $result->issues,
                'suggestions' => $result->suggestions,
                'best_practices' => $result->bestPractices,
                'provider' => $result->provider,
                'model' => $result->model,
                'generated_at' => $result->generatedAt->format('c'),
            ],
        ];
        
        // Add fallback reason if static fallback was used
        if ($result->provider === 'static_fallback') {
            $response['data']['fallback_reason'] = 'AI provider unavailable';
        }
        
        // Add debug information if enabled
        if ($debugMode && $this->service->isDebugMode()) {
            $latencyMs = (int) ((microtime(true) - $startTime) * 1000);
            
            $response['debug'] = [
                'prompt' => 'Debug prompt would be shown here',
                'raw_response' => 'Raw AI response would be shown here',
                'tokens_used' => 0,
                'latency_ms' => $latencyMs,
                'provider' => $result->provider,
                'model' => $result->model,
            ];
        }
        
        return $response;
    }
}
