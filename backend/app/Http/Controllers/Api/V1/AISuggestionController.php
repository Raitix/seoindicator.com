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
 * TASK-21-5: Create AI Suggestion API Endpoint
 *
 * Provides REST API endpoint for generating AI-powered SEO suggestions.
 * Supports multiple AI providers with automatic fallback.
 *
 * @see https://seoindicator.com/docs/api/ai-suggestions
 */
class AISuggestionController extends Controller
{
    /**
     * Create a new AISuggestionController instance.
     *
     * @param AISuggestionService $service The AI suggestion service
     */
    public function __construct(
        protected AISuggestionService $service
    ) {}

    /**
     * Generate AI suggestions for a specific SEO indicator.
     *
     * @param AISuggestionRequest $request The validated request
     * @return JsonResponse
     */
    public function generate(AISuggestionRequest $request): JsonResponse
    {
        try {
            // Get validated input
            $indicator = $request->getIndicator();
            $context = $request->getContext();
            $provider = $request->getProvider();
            $debug = $request->isDebugEnabled();

            // Switch provider if specified
            if ($provider !== null) {
                $this->service->setProvider($provider);
            }

            // Generate suggestions
            $result = $this->service->getSuggestions($indicator, $context);

            // Build response
            $response = $this->buildSuccessResponse($result);

            // Add debug info if enabled and user is authenticated
            if ($debug && $this->shouldIncludeDebugInfo()) {
                $response['debug'] = $this->buildDebugInfo($result);
            }

            return response()->json($response, 200);

        } catch (AIServiceUnavailableException $e) {
            // All providers failed, no fallback available
            Log::error('AI suggestion service unavailable', [
                'indicator' => $request->getIndicator(),
                'exception' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'AI service is temporarily unavailable. Please try again later.',
            ], 503);

        } catch (\Exception $e) {
            // Unexpected error
            Log::error('AI suggestion error', [
                'indicator' => $request->getIndicator(),
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred. Please try again later.',
            ], 500);
        }
    }

    /**
     * Build a successful response from the suggestion result.
     *
     * @param SuggestionResult $result
     * @return array<string, mixed>
     */
    protected function buildSuccessResponse(SuggestionResult $result): array
    {
        $data = [
            'indicator' => $result->indicator,
            'score' => $result->score,
            'issues' => $result->issues,
            'suggestions' => $result->suggestions,
            'best_practices' => $result->bestPractices,
            'provider' => $result->provider,
            'model' => $result->model,
            'generated_at' => $result->generatedAt->format('c'),
        ];

        // Add fallback_reason if static fallback was used
        if ($result->provider === 'static_fallback') {
            $data['fallback_reason'] = 'AI provider unavailable';
        }

        return [
            'success' => true,
            'data' => $data,
        ];
    }

    /**
     * Check if debug info should be included in response.
     *
     * Debug info is only shown for authenticated users (especially admins).
     *
     * @return bool
     */
    protected function shouldIncludeDebugInfo(): bool
    {
        $user = request()->user();

        // Only authenticated users can see debug info
        if (!$user) {
            return false;
        }

        // Check if debug mode is enabled in config
        return $this->service->isDebugMode();
    }

    /**
     * Build debug information for the response.
     *
     * @param SuggestionResult $result
     * @return array<string, mixed>
     */
    protected function buildDebugInfo(SuggestionResult $result): array
    {
        return [
            'prompt' => 'Debug prompt would be logged here when fully implemented',
            'raw_response' => 'Debug raw response would be logged here when fully implemented',
            'tokens_used' => null, // Will be populated when AI provider integration is complete
            'latency_ms' => null, // Will be populated when AI provider integration is complete
            'provider' => $result->provider,
            'model' => $result->model,
        ];
    }
}
