<?php

namespace App\Services\AI\Providers;

use App\Services\AI\Contracts\AIProviderInterface;
use App\Services\AI\DTOs\AIResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\ConnectionException;

/**
 * Vercel AI Provider implementation.
 *
 * Provides access to Vercel's AI models including free tier models like
 * gpt-4o-mini and claude-3-haiku. Implements the AIProviderInterface for
 * consistent integration with the AI suggestion system.
 *
 * Free Tier Models:
 * - gpt-4o-mini (default)
 * - claude-3-haiku-20240307
 *
 * @see https://vercel.com/docs/ai
 */
class VercelAIProvider implements AIProviderInterface
{
    /**
     * The API key for authentication.
     */
    private string $apiKey;

    /**
     * The model to use for generation.
     */
    private string $model;

    /**
     * The base URL for the Vercel AI API.
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
     * The default temperature for generation.
     */
    private float $temperature;

    /**
     * Optional system prompt for all requests.
     */
    private ?string $systemPrompt;

    /**
     * Create a new VercelAIProvider instance.
     *
     * @param array<string, mixed> $config Configuration array
     * @throws \InvalidArgumentException If api_key is missing or invalid
     */
    public function __construct(array $config)
    {
        // Validate required api_key
        if (!isset($config['api_key'])) {
            throw new \InvalidArgumentException('api_key is required');
        }

        // Validate api_key is not null
        if ($config['api_key'] === null) {
            throw new \InvalidArgumentException('api_key is required');
        }

        $this->apiKey = $config['api_key'];
        $this->model = $config['model'] ?? 'gpt-4o-mini';
        $this->baseUrl = $this->validateBaseUrl($config['base_url'] ?? 'https://api.vercel.ai/v1');
        $this->timeout = $this->validateTimeout($config['timeout'] ?? 30);
        $this->maxTokens = $this->validateMaxTokens($config['max_tokens'] ?? 1000);
        $this->temperature = $config['temperature'] ?? 0.7;
        $this->systemPrompt = $config['system_prompt'] ?? null;
    }

    /**
     * Generate an AI response for the given prompt.
     *
     * @param string $prompt The prompt to send to the AI model
     * @param array<string, mixed> $options Optional parameters (temperature, max_tokens, etc.)
     * @return AIResponse The response from the AI provider
     */
    public function generate(string $prompt, array $options = []): AIResponse
    {
        $startTime = microtime(true);

        try {
            $response = $this->makeRequest($prompt, $options);
            $latencyMs = (int) ((microtime(true) - $startTime) * 1000);

            if (!$response->successful()) {
                return $this->handleErrorResponse($response, $latencyMs);
            }

            return $this->handleSuccessResponse($response, $latencyMs);
        } catch (ConnectionException $e) {
            $latencyMs = (int) ((microtime(true) - $startTime) * 1000);
            return AIResponse::error('Request timeout', $latencyMs);
        } catch (\Exception $e) {
            $latencyMs = (int) ((microtime(true) - $startTime) * 1000);
            return AIResponse::error($e->getMessage(), $latencyMs);
        }
    }

    /**
     * Check if the provider is available and properly configured.
     *
     * @return bool True if the provider is available, false otherwise
     */
    public function isAvailable(): bool
    {
        return !empty(trim($this->apiKey));
    }

    /**
     * Get the name of this provider.
     *
     * @return string The provider name
     */
    public function getName(): string
    {
        return 'vercel';
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
     * Make the HTTP request to Vercel AI API.
     *
     * @param string $prompt The user prompt
     * @param array<string, mixed> $options Request options
     * @return \Illuminate\Http\Client\Response
     */
    private function makeRequest(string $prompt, array $options): \Illuminate\Http\Client\Response
    {
        $messages = $this->buildMessages($prompt);

        $body = [
            'model' => $this->model,
            'messages' => $messages,
            'max_tokens' => $options['max_tokens'] ?? $this->maxTokens,
            'temperature' => $options['temperature'] ?? $this->temperature,
        ];

        return Http::withHeaders($this->getHeaders())
            ->timeout($this->timeout)
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
        $messages = [];

        if ($this->systemPrompt) {
            $messages[] = [
                'role' => 'system',
                'content' => $this->systemPrompt,
            ];
        }

        $messages[] = [
            'role' => 'user',
            'content' => $prompt,
        ];

        return $messages;
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
            'Content-Type' => 'application/json',
        ];
    }

    /**
     * Handle a successful API response.
     *
     * @param \Illuminate\Http\Client\Response $response The HTTP response
     * @param int $latencyMs The request latency in milliseconds
     * @return AIResponse The parsed response
     */
    private function handleSuccessResponse(\Illuminate\Http\Client\Response $response, int $latencyMs): AIResponse
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
        $tokensUsed = $data['usage']['total_tokens'] ?? null;

        // Use constructor directly to allow null content
        return new AIResponse(
            success: true,
            content: $content,
            tokensUsed: $tokensUsed,
            latencyMs: $latencyMs,
            errorMessage: null
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
            return 'https://api.vercel.ai/v1';
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
        return $timeout > 0 ? $timeout : 30;
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
        return $maxTokens > 0 ? $maxTokens : 1000;
    }
}
