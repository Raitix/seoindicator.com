<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\AISuggestionRequest;
use App\Services\AI\AISuggestionService;
use App\Services\AI\DTOs\SuggestionResult;
use App\Services\AI\Contracts\AIProviderInterface;
use App\Services\AI\DTOs\AIResponse;
use App\Services\AI\AILogger;
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
     * @param AIProviderInterface $provider
     * @param AILogger $logger
     */
    public function __construct(
        protected AISuggestionService $service,
        protected AIProviderInterface $provider,
        protected AILogger $logger
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
        $debugMode = $this->isDebugEnabled() && $request->user()?->exists;

        try {
            // Set provider if specified
            if ($validated['provider'] !== config('ai.default')) {
                $this->service->setProvider($validated['provider']);
            }

            // Log the request
            $this->logger->logRequest(
                $validated['indicator'],
                $validated['context'],
                $validated['provider']
            );

            // Get suggestions from service
            $result = $this->service->getSuggestions(
                $validated['indicator'],
                $validated['context']
            );

            $latencyMs = (int) ((microtime(true) - $startTime) * 1000);

            // Log the response
            $this->logger->logResponse(
                $validated['indicator'],
                [
                    'success' => true,
                    'score' => $result->score,
                    'provider' => $result->provider,
                    'model' => $result->model,
                ],
                $latencyMs
            );

            $response = $this->buildSuccessResponse($result, $debugMode, $startTime, $validated);

            return response()->json($response, 200);

        } catch (AIServiceUnavailableException $e) {
            $latencyMs = (int) ((microtime(true) - $startTime) * 1000);

            $this->logger->logError(
                $validated['indicator'],
                $e->getMessage(),
                ['latency_ms' => $latencyMs]
            );

            Log::error('AI suggestion service unavailable', [
                'indicator' => $validated['indicator'],
                'exception' => $e->getMessage(),
            ]);

            $response = [
                'success' => false,
                'error' => $e->getMessage(),
            ];

            // Include debug info on error if debug mode enabled
            if ($debugMode) {
                $response['debug'] = [
                    'error' => $e->getMessage(),
                    'latency_ms' => $latencyMs,
                    'provider' => $validated['provider'],
                ];
            }

            return response()->json($response, 503);

        } catch (\Exception $e) {
            $latencyMs = (int) ((microtime(true) - $startTime) * 1000);

            $this->logger->logError(
                $validated['indicator'],
                $e->getMessage(),
                [
                    'latency_ms' => $latencyMs,
                    'stack_trace' => $e->getTraceAsString(),
                ]
            );

            Log::error('AI suggestion error', [
                'indicator' => $validated['indicator'],
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $response = [
                'success' => false,
                'error' => $e->getMessage(),
            ];
            
            // Include debug info on error if debug mode enabled
            if ($debugMode) {
                $response['debug'] = [
                    'error' => $e->getMessage(),
                    'latency_ms' => $latencyMs,
                    'provider' => $validated['provider'],
                ];
            }
            
            return response()->json($response, 500);
        }
    }

    /**
     * Build the success response structure.
     *
     * @param SuggestionResult $result
     * @param bool $debugMode
     * @param float $startTime
     * @param array<string, mixed> $validated
     * @return array<string, mixed>
     */
    protected function buildSuccessResponse(
        SuggestionResult $result,
        bool $debugMode,
        float $startTime,
        array $validated
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
        if ($debugMode) {
            $latencyMs = (int) ((microtime(true) - $startTime) * 1000);

            // Build the prompt (same logic as in AISuggestionService)
            $promptContext = array_merge(['indicator' => $validated['indicator']], $validated['context']);
            $prompt = json_encode($promptContext, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

            // Get raw response from provider for debug
            $rawResponse = $this->getRawResponseForDebug($result);

            $response['debug'] = [
                'prompt' => $prompt,
                'raw_response' => $rawResponse,
                'tokens_used' => $this->estimateTokens($prompt, $rawResponse),
                'latency_ms' => $latencyMs,
                'provider' => $result->provider,
                'model' => $result->model,
            ];
        }

        return $response;
    }

    /**
     * Get raw response content for debug mode.
     *
     * @param SuggestionResult $result
     * @return string
     */
    protected function getRawResponseForDebug(SuggestionResult $result): string
    {
        return json_encode([
            'indicator' => $result->indicator,
            'score' => $result->score,
            'issues' => $result->issues,
            'suggestions' => $result->suggestions,
            'best_practices' => $result->bestPractices,
            'provider' => $result->provider,
            'model' => $result->model,
            'generated_at' => $result->generatedAt->format('c'),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Estimate token usage for debug mode.
     *
     * @param string $prompt
     * @param string $response
     * @return int
     */
    protected function estimateTokens(string $prompt, string $response): int
    {
        // Static fallback has no actual AI tokens
        if (str_contains($response, '"provider": "static_fallback"')) {
            return 0;
        }

        // Rough estimation: ~4 characters per token for English text
        $promptTokens = (int) (strlen($prompt) / 4);
        $responseTokens = (int) (strlen($response) / 4);

        return $promptTokens + $responseTokens;
    }

    /**
     * Check if debug mode is enabled.
     *
     * @return bool
     */
    protected function isDebugEnabled(): bool
    {
        return (bool) config('ai.debug', false);
    }
}
