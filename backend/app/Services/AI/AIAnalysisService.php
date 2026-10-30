<?php

namespace App\Services\AI;

use App\Exceptions\AIAnalysisException;
use App\Services\AI\Contracts\AIAnalysisServiceInterface;
use App\Services\AI\Contracts\AnalysisCacheServiceInterface;
use App\Services\AI\Contracts\OpenRouterClientInterface;
use App\Services\AI\Contracts\UnifiedPromptServiceInterface;
use App\Services\AI\DTOs\AIResponse;
use App\Services\AI\DTOs\AnalysisResult;
use App\Services\AI\DTOs\TruncationResult;
use Illuminate\Support\Facades\Log;

/**
 * AI Analysis Service (Unified Caller).
 *
 * Main orchestrator service that coordinates the unified AI analysis flow:
 * building prompts, managing context windows, calling the AI, and parsing responses.
 *
 * @see TASK-31-7
 * @see SPEC-31
 */
class AIAnalysisService implements AIAnalysisServiceInterface
{
    /**
     * Maximum number of retries for transient errors.
     */
    private const MAX_RETRIES = 3;

    /**
     * Backoff delays in milliseconds for retries.
     */
    private const BACKOFF_MS = [1000, 2000, 4000];

    /**
     * HTTP status codes that should trigger a retry.
     */
    private const RETRY_STATUS_CODES = [429, 500, 502, 503, 504];

    /**
     * Token overhead for system prompt and response schema.
     */
    private const TOKEN_OVERHEAD = 3400;

    /**
     * Available models with their context windows.
     */
    private const AVAILABLE_MODELS = [
        'meta-llama/llama-3-8b-instruct:free' => [
            'id' => 'meta-llama/llama-3-8b-instruct:free',
            'name' => 'Llama 3 8B',
            'context_window' => 8192,
            'is_free' => true,
        ],
        'qwen/qwen-2-7b-instruct:free' => [
            'id' => 'qwen/qwen-2-7b-instruct:free',
            'name' => 'Qwen 2 7B',
            'context_window' => 32768,
            'is_free' => true,
        ],
        'mistralai/mistral-7b-instruct:free' => [
            'id' => 'mistralai/mistral-7b-instruct:free',
            'name' => 'Mistral 7B',
            'context_window' => 32768,
            'is_free' => true,
        ],
        'google/gemini-2.0-flash-exp:free' => [
            'id' => 'google/gemini-2.0-flash-exp:free',
            'name' => 'Gemini 2.0 Flash',
            'context_window' => 1000000,
            'is_free' => true,
        ],
    ];

    /**
     * Create a new AIAnalysisService instance.
     *
     * @param UnifiedPromptServiceInterface $promptService Prompt building service
     * @param ContextWindowManager $contextManager Token estimation and model selection
     * @param TruncationService $truncationService Content truncation service
     * @param OpenRouterClientInterface $aiClient AI client for making API calls
     * @param AIAnalysisResponseParser $parser Response parser
     * @param AnalysisCacheServiceInterface $cache Cache service
     */
    public function __construct(
        private UnifiedPromptServiceInterface $promptService,
        private ContextWindowManager $contextManager,
        private TruncationService $truncationService,
        private OpenRouterClientInterface $aiClient,
        private AIAnalysisResponseParser $parser,
        private AnalysisCacheServiceInterface $cache,
    ) {}

    /**
     * Execute unified AI analysis.
     *
     * Performs a single AI call that analyzes all SEO indicators
     * and returns a complete analysis result.
     *
     * @param string $url The URL being analyzed
     * @param string $html The HTML content to analyze
     * @param array $options Optional parameters:
     *   - model: string (specific model to use)
     *   - timeout: int (timeout in seconds)
     *   - force_refresh: bool (bypass cache)
     * @return AnalysisResult The analysis result with all indicators
     * @throws AIAnalysisException On analysis failure
     */
    public function analyze(string $url, string $html, array $options = []): AnalysisResult
    {
        // Validate inputs
        $this->validateInputs($url, $html);

        // Check cache first (unless force_refresh)
        $forceRefresh = $options['force_refresh'] ?? false;
        if (!$forceRefresh) {
            $cacheKey = $this->cache->generateHash($url, $html);
            $cachedResult = $this->cache->get($cacheKey);
            
            if ($cachedResult !== null) {
                Log::debug('AIAnalysisService: Returning cached result', ['url' => $url]);
                return $cachedResult;
            }
        }

        // Estimate tokens
        $estimatedTokens = $this->contextManager->estimateTokens($html);
        $totalEstimated = $estimatedTokens + self::TOKEN_OVERHEAD;

        // Select model (use provided or auto-select)
        $model = $options['model'] ?? $this->contextManager->selectOptimalModel($totalEstimated);

        // Check if truncation needed
        $effectiveLimit = $this->contextManager->getEffectiveLimit($model);
        $truncationStrategies = [];

        if ($totalEstimated > $effectiveLimit) {
            $targetTokens = $effectiveLimit - self::TOKEN_OVERHEAD;
            $truncationResult = $this->truncationService->truncate($html, $targetTokens);
            $html = $truncationResult->html;
            $truncationStrategies = $truncationResult->strategiesApplied;
            
            Log::info('AIAnalysisService: Content truncated', [
                'original_tokens' => $truncationResult->originalTokens,
                'final_tokens' => $truncationResult->finalTokens,
                'strategies' => $truncationStrategies,
            ]);
        }

        // Build prompt
        $prompt = $this->promptService->buildPrompt($url, $html, $options);

        // Make AI call with retry logic
        $response = $this->callWithRetry($prompt, $model, $options);

        // Parse response
        $result = $this->parser->parse($response->content);

        // Enrich with metadata
        $result = $result->withMetadata(
            model: $model,
            promptTokens: $response->tokensUsed ? (int)($response->tokensUsed * 0.6) : 0,
            responseTokens: $response->tokensUsed ? (int)($response->tokensUsed * 0.4) : 0,
            latencyMs: $response->latencyMs ?? 0,
            truncationStrategies: $truncationStrategies,
        );

        // Cache result
        if (!isset($cacheKey)) {
            $cacheKey = $this->cache->generateHash($url, $html);
        }
        $this->cache->put($cacheKey, $result, 86400); // 24 hours

        Log::info('AIAnalysisService: Analysis complete', [
            'url' => $url,
            'model' => $model,
            'score' => $result->overallScore,
        ]);

        return $result;
    }

    /**
     * Execute analysis with a specific model.
     *
     * @param string $url The URL being analyzed
     * @param string $html The HTML content to analyze
     * @param string $model The model identifier to use
     * @param array $options Additional options
     * @return AnalysisResult The analysis result
     * @throws AIAnalysisException On analysis failure
     */
    public function analyzeWithModel(string $url, string $html, string $model, array $options = []): AnalysisResult
    {
        $options['model'] = $model;
        return $this->analyze($url, $html, $options);
    }

    /**
     * Get available AI models.
     *
     * @return array<string, array{id: string, name: string, context_window: int, is_free: bool}>
     */
    public function getAvailableModels(): array
    {
        return self::AVAILABLE_MODELS;
    }

    /**
     * Get the default model.
     *
     * @return string The default model identifier
     */
    public function getDefaultModel(): string
    {
        return 'google/gemini-2.0-flash-exp:free';
    }

    /**
     * Get analysis metadata for the last analysis.
     *
     * @return array<string, mixed> Analysis metadata
     */
    public function getAnalysisMetadata(): array
    {
        return [
            'default_model' => $this->getDefaultModel(),
            'available_models' => array_keys(self::AVAILABLE_MODELS),
            'token_overhead' => self::TOKEN_OVERHEAD,
            'max_retries' => self::MAX_RETRIES,
            'cache_ttl' => 86400,
        ];
    }

    /**
     * Validate input parameters.
     *
     * @param string $url The URL
     * @param string $html The HTML content
     * @throws \InvalidArgumentException If inputs are invalid
     */
    private function validateInputs(string $url, string $html): void
    {
        if (empty(trim($url))) {
            throw new \InvalidArgumentException('URL cannot be empty');
        }
    }

    /**
     * Make AI call with retry logic.
     *
     * @param string $prompt The prompt to send
     * @param string $model The model to use
     * @param array $options Request options
     * @return AIResponse The AI response
     * @throws AIAnalysisException If all retries fail
     */
    private function callWithRetry(string $prompt, string $model, array $options): AIResponse
    {
        $lastError = null;

        for ($attempt = 1; $attempt <= self::MAX_RETRIES + 1; $attempt++) {
            $response = $this->aiClient->generate($prompt, array_merge($options, [
                'model' => $model,
            ]));

            if ($response->success) {
                return $response;
            }

            $lastError = $response->errorMessage;

            // Check if we should retry (not on last attempt, and retryable error)
            if ($attempt <= self::MAX_RETRIES && $this->isRetryableError($response)) {
                // Wait before retry
                $delayMs = self::BACKOFF_MS[$attempt - 1] ?? 4000;
                usleep($delayMs * 1000);
                
                Log::warning('AIAnalysisService: Retrying after error', [
                    'attempt' => $attempt,
                    'error' => $lastError,
                    'delay_ms' => $delayMs,
                ]);
                continue;
            }

            // Non-retryable error or max retries reached
            break;
        }

        throw new AIAnalysisException(
            "AI analysis failed: " . ($lastError ?? 'Unknown error'),
            0,
            null,
            ['model' => $model, 'attempts' => $attempt - 1]
        );
    }

    /**
     * Check if an error is retryable.
     *
     * @param AIResponse $response The AI response
     * @return bool True if retryable
     */
    private function isRetryableError(AIResponse $response): bool
    {
        // Check for retryable status codes in the error message
        foreach (self::RETRY_STATUS_CODES as $code) {
            if (str_contains((string)$response->errorMessage, (string)$code)) {
                return true;
            }
        }

        // Check for rate limit or server error indicators
        $errorMessage = strtolower($response->errorMessage ?? '');
        if (str_contains($errorMessage, 'rate limit') ||
            str_contains($errorMessage, 'rate limited') ||
            str_contains($errorMessage, 'timeout') ||
            str_contains($errorMessage, 'unavailable') ||
            str_contains($errorMessage, 'server error')) {
            return true;
        }

        return false;
    }
}
