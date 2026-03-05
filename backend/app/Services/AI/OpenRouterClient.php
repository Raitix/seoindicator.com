<?php

namespace App\Services\AI;

use App\Services\AI\Contracts\OpenRouterClientInterface;
use App\Services\AI\DTOs\AIResponse;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;

/**
 * OpenRouter API Client Service.
 *
 * Provides unified AI request handling with proper authentication, error handling,
 * timeouts, and response parsing. Extends the existing OpenRouter provider patterns
 * from TASK-21-2 with enhanced functionality for the unified AI analysis feature.
 *
 * @see https://openrouter.ai/docs
 * @see TASK-31-6
 */
class OpenRouterClient implements OpenRouterClientInterface
{
    /**
     * The base URL for the OpenRouter API.
     */
    private const BASE_URL = 'https://openrouter.ai/api/v1';

    /**
     * The default timeout in seconds (2 minutes for large responses).
     */
    private const DEFAULT_TIMEOUT = 120;

    /**
     * The default model for generation.
     */
    private const DEFAULT_MODEL = 'google/gemini-2.0-flash-exp:free';

    /**
     * The default maximum tokens for generation.
     */
    private const DEFAULT_MAX_TOKENS = 4000;

    /**
     * The default temperature for generation.
     */
    private const DEFAULT_TEMPERATURE = 0.3;

    /**
     * Free tier models with their metadata.
     */
    private const FREE_MODELS = [
        'google/gemini-2.0-flash-exp:free' => [
            'name' => 'Gemini 2.0 Flash',
            'context_window' => 1000000,
        ],
        'qwen/qwen-2-7b-instruct:free' => [
            'name' => 'Qwen 2 7B',
            'context_window' => 32768,
        ],
        'mistralai/mistral-7b-instruct:free' => [
            'name' => 'Mistral 7B',
            'context_window' => 32768,
        ],
        'meta-llama/llama-3-8b-instruct:free' => [
            'name' => 'Llama 3 8B',
            'context_window' => 8192,
        ],
    ];

    /**
     * The API key for authentication.
     */
    private string $apiKey;

    /**
     * The model to use for generation.
     */
    private string $model;

    /**
     * The base URL for the OpenRouter API.
     */
    private string $baseUrl;

    /**
     * The timeout in seconds for API requests.
     */
    private int $timeout;

    /**
     * The maximum tokens for generation.
     */
    private int $maxTokens;

    /**
     * The temperature for generation.
     */
    private float $temperature;

    /**
     * Create a new OpenRouterClient instance.
     *
     * @param array<string, mixed> $config Configuration array
     * @throws InvalidArgumentException If api_key is missing or invalid
     */
    public function __construct(array $config)
    {
        // Validate required api_key
        if (!isset($config['api_key'])) {
            throw new InvalidArgumentException('api_key is required');
        }

        if ($config['api_key'] === null || $config['api_key'] === '') {
            throw new InvalidArgumentException('api_key is required');
        }

        $this->apiKey = $config['api_key'];
        $this->model = $config['model'] ?? self::DEFAULT_MODEL;
        $this->baseUrl = $this->validateBaseUrl($config['base_url'] ?? self::BASE_URL);
        $this->timeout = $this->validateTimeout($config['timeout'] ?? self::DEFAULT_TIMEOUT);
        $this->maxTokens = $this->validateMaxTokens($config['max_tokens'] ?? self::DEFAULT_MAX_TOKENS);
        $this->temperature = $config['temperature'] ?? self::DEFAULT_TEMPERATURE;
    }

    /**
     * Generate an AI response for the given prompt.
     *
     * @param string $prompt The prompt to send to the AI model
     * @param array<string, mixed> $options Optional parameters (model, max_tokens, temperature, timeout)
     * @return AIResponse The response from the AI provider
     */
    public function generate(string $prompt, array $options = []): AIResponse
    {
        $startTime = microtime(true);
        $model = $options['model'] ?? $this->model;

        try {
            $response = $this->makeRequest($prompt, $options, $model);
            $latencyMs = (int) ((microtime(true) - $startTime) * 1000);

            if (!$response->successful()) {
                return $this->handleErrorResponse($response, $latencyMs);
            }

            return $this->handleSuccessResponse($response, $latencyMs, $model);
        } catch (ConnectionException $e) {
            $latencyMs = (int) ((microtime(true) - $startTime) * 1000);
            return AIResponse::error('Request timeout', $latencyMs);
        } catch (\Exception $e) {
            $latencyMs = (int) ((microtime(true) - $startTime) * 1000);
            return AIResponse::error($e->getMessage(), $latencyMs);
        }
    }

    /**
     * Stream response chunks.
     *
     * For MVP, this falls back to non-streaming behavior and calls the callback
     * with the complete response.
     *
     * @param string $prompt The prompt to send to the AI model
     * @param callable $onChunk Callback function to receive chunks
     * @param array<string, mixed> $options Optional parameters
     */
    public function stream(string $prompt, callable $onChunk, array $options = []): void
    {
        // For MVP, fall back to non-streaming
        $response = $this->generate($prompt, $options);

        if ($response->success && $response->content !== null) {
            $onChunk([
                'content' => $response->content,
                'done' => true,
            ]);
        } else {
            $onChunk([
                'error' => $response->errorMessage ?? 'Unknown error',
                'done' => true,
            ]);
        }
    }

    /**
     * Get available free-tier models.
     *
     * @return array<string, array{name: string, context_window: int}> List of available models
     */
    public function getAvailableModels(): array
    {
        return self::FREE_MODELS;
    }

    /**
     * Get information about a specific model.
     *
     * @param string $modelId The model identifier
     * @return array{name: string, context_window: int}|null Model information or null if not found
     */
    public function getModelInfo(string $modelId): ?array
    {
        return self::FREE_MODELS[$modelId] ?? null;
    }

    /**
     * Get the configured model.
     *
     * @return string The model identifier
     */
    public function getModel(): string
    {
        return $this->model;
    }

    /**
     * Get the configured timeout.
     *
     * @return int The timeout in seconds
     */
    public function getTimeout(): int
    {
        return $this->timeout;
    }

    /**
     * Get the configured max tokens.
     *
     * @return int The maximum tokens
     */
    public function getMaxTokens(): int
    {
        return $this->maxTokens;
    }

    /**
     * Get the configured base URL.
     *
     * @return string The base URL
     */
    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    /**
     * Check if the client is available and properly configured.
     *
     * @return bool True if the client is available
     */
    public function isAvailable(): bool
    {
        return !empty(trim($this->apiKey));
    }

    /**
     * Make the HTTP request to OpenRouter API.
     *
     * @param string $prompt The user prompt
     * @param array<string, mixed> $options Request options
     * @param string $model The model to use
     * @return \Illuminate\Http\Client\Response
     */
    private function makeRequest(string $prompt, array $options, string $model): \Illuminate\Http\Client\Response
    {
        $messages = $this->buildMessages($prompt);
        $timeout = $options['timeout'] ?? $this->timeout;

        $body = [
            'model' => $model,
            'messages' => $messages,
            'max_tokens' => $options['max_tokens'] ?? $this->maxTokens,
            'temperature' => $options['temperature'] ?? $this->temperature,
            'response_format' => ['type' => 'json_object'],
        ];

        return Http::withHeaders($this->getHeaders())
            ->timeout($timeout)
            ->post("{$this->baseUrl}/chat/completions", $body);
    }

    /**
     * Build the messages array for the request.
     *
     * @param string $prompt The user prompt
     * @return array<int, array{role: string, content: string}> The messages array
     */
    private function buildMessages(string $prompt): array
    {
        return [
            [
                'role' => 'user',
                'content' => $prompt,
            ],
        ];
    }

    /**
     * Get the headers for the API request.
     *
     * @return array<string, string> The headers array
     */
    private function getHeaders(): array
    {
        return [
            'Authorization' => "Bearer {$this->apiKey}",
            'HTTP-Referer' => config('app.url', 'https://seoindicator.com'),
            'X-Title' => 'SEOIndicator',
            'Content-Type' => 'application/json',
        ];
    }

    /**
     * Handle a successful API response.
     *
     * @param \Illuminate\Http\Client\Response $response The HTTP response
     * @param int $latencyMs The request latency in milliseconds
     * @param string $model The model used
     * @return AIResponse The parsed response
     */
    private function handleSuccessResponse(\Illuminate\Http\Client\Response $response, int $latencyMs, string $model): AIResponse
    {
        $body = $response->body();

        // Handle empty response
        if (empty($body)) {
            return AIResponse::error('Empty response from provider', $latencyMs);
        }

        // Parse JSON
        try {
            $data = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            return AIResponse::error('Invalid JSON response from provider', $latencyMs);
        }

        // Validate choices array exists
        if (!isset($data['choices']) || !is_array($data['choices'])) {
            return AIResponse::error('Invalid response format: missing choices', $latencyMs);
        }

        // Validate choices is not empty
        if (empty($data['choices'])) {
            return AIResponse::error('No response generated', $latencyMs);
        }

        // Extract content from first choice
        $firstChoice = $data['choices'][0];

        if (!isset($firstChoice['message'])) {
            return AIResponse::error('Invalid response format: missing message', $latencyMs);
        }

        $content = $firstChoice['message']['content'] ?? null;

        // Extract token usage
        $tokensUsed = $data['usage']['total_tokens'] ?? 0;
        $promptTokens = $data['usage']['prompt_tokens'] ?? 0;
        $responseTokens = $data['usage']['completion_tokens'] ?? 0;

        return new AIResponse(
            success: true,
            content: $content,
            tokensUsed: $tokensUsed,
            latencyMs: $latencyMs,
            errorMessage: null,
            model: $model,
            promptTokens: $promptTokens,
            responseTokens: $responseTokens
        );
    }

    /**
     * Handle an error API response.
     *
     * @param \Illuminate\Http\Client\Response $response The HTTP response
     * @param int $latencyMs The request latency in milliseconds
     * @return AIResponse The error response
     */
    private function handleErrorResponse(\Illuminate\Http\Client\Response $response, int $latencyMs): AIResponse
    {
        $statusCode = $response->status();

        // Try to extract error message from response body
        $errorData = $response->json();
        $errorMessage = $errorData['error']['message'] ?? null;

        $message = match ($statusCode) {
            400 => $errorMessage ?? 'Bad request',
            401 => 'Invalid API key',
            403 => 'Access denied',
            404 => 'Endpoint not found',
            429 => 'Rate limit exceeded',
            500 => 'Server error',
            502 => 'Bad gateway',
            503 => 'Service unavailable',
            default => $errorMessage ?? "HTTP error {$statusCode}",
        };

        // Include status code in error message for better debugging
        $message = "{$statusCode}: {$message}";

        return AIResponse::error($message, $latencyMs);
    }

    /**
     * Validate and sanitize the base URL.
     *
     * @param string $url The URL to validate
     * @return string The validated URL or default
     */
    private function validateBaseUrl(string $url): string
    {
        if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
            return self::BASE_URL;
        }

        return rtrim($url, '/');
    }

    /**
     * Validate and sanitize the timeout value.
     *
     * @param mixed $timeout The timeout value
     * @return int The validated timeout
     */
    private function validateTimeout(mixed $timeout): int
    {
        $timeout = (int) $timeout;
        return $timeout > 0 ? $timeout : self::DEFAULT_TIMEOUT;
    }

    /**
     * Validate and sanitize the max_tokens value.
     *
     * @param mixed $maxTokens The max_tokens value
     * @return int The validated max_tokens
     */
    private function validateMaxTokens(mixed $maxTokens): int
    {
        $maxTokens = (int) $maxTokens;
        return $maxTokens > 0 ? $maxTokens : self::DEFAULT_MAX_TOKENS;
    }
}
