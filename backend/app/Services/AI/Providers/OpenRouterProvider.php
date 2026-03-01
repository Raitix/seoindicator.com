<?php

namespace App\Services\AI\Providers;

use App\Services\AI\Contracts\AIProviderInterface;
use App\Services\AI\DTOs\AIResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * OpenRouter AI Provider implementation.
 *
 * Integrates with the OpenRouter API to provide access to various AI models
 * including free models like Llama 3.1, Gemma 2, and Qwen 2.
 *
 * @see https://openrouter.ai/docs
 */
class OpenRouterProvider implements AIProviderInterface
{
    /**
     * The API key for OpenRouter authentication.
     */
    private string $apiKey;

    /**
     * The base URL for the OpenRouter API.
     */
    private string $baseUrl;

    /**
     * The model to use for completions.
     */
    private string $model;

    /**
     * The timeout in seconds for API requests.
     */
    private int $timeout;

    /**
     * The maximum tokens to generate in responses.
     */
    private int $maxTokens;

    /**
     * The temperature for response generation (0.0 to 2.0).
     */
    private float $temperature;

    /**
     * Optional system prompt to prepend to all requests.
     */
    private ?string $systemPrompt;

    /**
     * Create a new OpenRouterProvider instance.
     *
     * @param array<string, mixed> $config Configuration array
     * @throws \InvalidArgumentException If api_key is missing or invalid
     */
    public function __construct(array $config)
    {
        // Validate API key
        if (!isset($config['api_key']) || !is_string($config['api_key'])) {
            throw new \InvalidArgumentException('api_key is required for OpenRouter provider');
        }

        $this->apiKey = $config['api_key'];
        $this->baseUrl = $this->validateBaseUrl($config['base_url'] ?? 'https://openrouter.ai/api/v1');
        $this->model = $config['model'] ?? 'meta-llama/llama-3.1-8b-instruct:free';
        $this->timeout = $this->validateTimeout($config['timeout'] ?? 30);
        $this->maxTokens = $this->validateMaxTokens($config['max_tokens'] ?? 1000);
        $this->temperature = (float)($config['temperature'] ?? 0.7);
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
            $latencyMs = (int)((microtime(true) - $startTime) * 1000);

            return $this->parseResponse($response, $latencyMs);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            $latencyMs = (int)((microtime(true) - $startTime) * 1000);
            return AIResponse::error('Request timeout', $latencyMs);
        } catch (\Exception $e) {
            $latencyMs = (int)((microtime(true) - $startTime) * 1000);
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
        return 'openrouter';
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
     * Get the configured maximum tokens.
     *
     * @return int The maximum tokens
     */
    public function getMaxTokens(): int
    {
        return $this->maxTokens;
    }

    /**
     * Make the HTTP request to the OpenRouter API.
     *
     * @param string $prompt The prompt to send
     * @param array<string, mixed> $options Optional parameters
     * @return \Illuminate\Http\Client\Response The HTTP response
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
     * Build the messages array for the API request.
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
            'HTTP-Referer' => config('app.url', 'https://seoindicator.com'),
            'X-Title' => 'SEOIndicator',
            'Content-Type' => 'application/json',
        ];
    }

    /**
     * Parse the API response and return an AIResponse.
     *
     * @param \Illuminate\Http\Client\Response $response The HTTP response
     * @param int $latencyMs The latency in milliseconds
     * @return AIResponse The parsed response
     */
    private function parseResponse(\Illuminate\Http\Client\Response $response, int $latencyMs): AIResponse
    {
        // Handle HTTP errors
        if ($response->failed()) {
            return $this->handleError($response, $latencyMs);
        }

        // Handle empty response
        $body = $response->body();
        if (empty($body)) {
            return AIResponse::error('Empty response from API', $latencyMs);
        }

        // Parse JSON
        try {
            $data = $response->json();
        } catch (\Exception $e) {
            return AIResponse::error('Invalid response format', $latencyMs);
        }

        // Validate response structure
        if (!isset($data['choices']) || !is_array($data['choices'])) {
            return AIResponse::error('Invalid response format: missing choices', $latencyMs);
        }

        if (empty($data['choices'])) {
            return AIResponse::error('No response generated', $latencyMs);
        }

        $choice = $data['choices'][0];

        if (!isset($choice['message'])) {
            return AIResponse::error('Invalid response format: missing message', $latencyMs);
        }

        $content = $choice['message']['content'] ?? null;
        $tokensUsed = $data['usage']['total_tokens'] ?? null;

        return AIResponse::success(
            content: $content,
            tokensUsed: $tokensUsed,
            latencyMs: $latencyMs
        );
    }

    /**
     * Handle an error response from the API.
     *
     * @param \Illuminate\Http\Client\Response $response The HTTP response
     * @param int $latencyMs The latency in milliseconds
     * @return AIResponse The error response
     */
    private function handleError(\Illuminate\Http\Client\Response $response, int $latencyMs): AIResponse
    {
        $status = $response->status();
        $body = $response->json();

        // Extract error message from response
        $errorMessage = $body['error']['message'] ?? null;

        return match ($status) {
            400 => AIResponse::error($errorMessage ?? "Bad request (400)", $latencyMs),
            401 => AIResponse::error('Invalid API key (401)', $latencyMs),
            403 => AIResponse::error('Access denied (403)', $latencyMs),
            404 => AIResponse::error('Endpoint not found (404)', $latencyMs),
            429 => AIResponse::error('Rate limit exceeded. Please try again later.', $latencyMs),
            500 => AIResponse::error('Server error (500)', $latencyMs),
            502 => AIResponse::error('Bad gateway (502)', $latencyMs),
            503 => AIResponse::error('Service unavailable (503)', $latencyMs),
            default => AIResponse::error("Request failed with status {$status}", $latencyMs),
        };
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
            return 'https://openrouter.ai/api/v1';
        }

        return rtrim($url, '/');
    }

    /**
     * Validate and sanitize the timeout value.
     *
     * @param mixed $timeout The timeout to validate
     * @return int The validated timeout
     */
    private function validateTimeout(mixed $timeout): int
    {
        $timeout = (int)$timeout;
        return $timeout > 0 ? $timeout : 30;
    }

    /**
     * Validate and sanitize the max_tokens value.
     *
     * @param mixed $maxTokens The max_tokens to validate
     * @return int The validated max_tokens
     */
    private function validateMaxTokens(mixed $maxTokens): int
    {
        $maxTokens = (int)$maxTokens;
        return $maxTokens > 0 ? $maxTokens : 1000;
    }
}
