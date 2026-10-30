<?php

namespace App\Services\AIVisibility;

use App\Services\AI\Contracts\OpenRouterClientInterface;
use App\Services\AI\DTOs\AIResponse;
use App\Services\AIVisibility\DTOs\AIQueryResult;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

/**
 * AI Query Engine Service.
 *
 * Handles querying AI platforms via OpenRouter and parsing responses
 * for brand mentions. Supports multiple platforms with configurable
 * free-tier models for Generative Engine Optimization (GEO).
 *
 * @see TASK-34-3
 * @see SPEC-034
 */
class AIQueryEngine
{
    /**
     * Cache TTL in seconds (7 days).
     */
    private const CACHE_TTL = 604800;

    /**
     * Platform configurations.
     * 
     * @var array<string, array{enabled: bool, model: string, name: string}>
     */
    private const PLATFORMS = [
        'chatgpt' => [
            'enabled' => true,
            'model' => 'google/gemini-2.0-flash-exp:free',
            'name' => 'ChatGPT',
        ],
        'perplexity' => [
            'enabled' => true,
            'model' => 'google/gemini-2.0-flash-exp:free',
            'name' => 'Perplexity',
        ],
        'claude' => [
            'enabled' => false,
            'model' => 'anthropic/claude-3-haiku',
            'name' => 'Claude',
        ],
    ];

    /**
     * Query templates for common query types.
     * 
     * @var array<string, string>
     */
    private const QUERY_TEMPLATES = [
        'comparison' => 'What are the best {category} tools for {audience}?',
        'recommendation' => 'Can you recommend a good {category} tool?',
        'feature' => 'How does {brand} compare to {competitor}?',
        'use_case' => 'What tool should I use to {use_case}?',
    ];

    /**
     * Positive sentiment keywords.
     */
    private const POSITIVE_KEYWORDS = [
        'excellent', 'great', 'best', 'recommended', 'top',
        'amazing', 'outstanding', 'superb', 'fantastic', 'wonderful',
        'impressive', 'solid', 'reliable', 'powerful', 'effective',
    ];

    /**
     * Negative sentiment keywords.
     */
    private const NEGATIVE_KEYWORDS = [
        'poor', 'bad', 'worst', 'avoid', 'limited',
        'terrible', 'awful', 'horrible', 'disappointing', 'weak',
        'unreliable', 'buggy', 'frustrating', 'lacking', 'inferior',
    ];

    /**
     * The OpenRouter client instance.
     */
    private OpenRouterClientInterface $client;

    /**
     * Create a new AIQueryEngine instance.
     *
     * @param OpenRouterClientInterface|null $client The OpenRouter client
     * @throws InvalidArgumentException If client is null
     */
    public function __construct(?OpenRouterClientInterface $client)
    {
        if ($client === null) {
            throw new InvalidArgumentException('OpenRouterClient is required');
        }

        $this->client = $client;
    }

    /**
     * Get list of available (enabled) platforms.
     *
     * @return array<string> List of platform identifiers
     */
    public function getAvailablePlatforms(): array
    {
        return array_keys(array_filter(self::PLATFORMS, fn ($config) => $config['enabled']));
    }

    /**
     * Get the model for a specific platform.
     *
     * @param string $platform The platform identifier
     * @return string The model identifier
     * @throws InvalidArgumentException If platform is not found
     */
    public function getModelForPlatform(string $platform): string
    {
        if (!isset(self::PLATFORMS[$platform])) {
            throw new InvalidArgumentException("Unknown platform: {$platform}");
        }

        return self::PLATFORMS[$platform]['model'];
    }

    /**
     * Check if a platform is valid and enabled.
     *
     * @param string $platform The platform identifier
     * @return bool True if platform is valid and enabled
     */
    public function isValidPlatform(string $platform): bool
    {
        return isset(self::PLATFORMS[$platform]) && self::PLATFORMS[$platform]['enabled'];
    }

    /**
     * Get a query template by type.
     *
     * @param string $type The template type
     * @return string The template string
     * @throws InvalidArgumentException If template type is not found
     */
    public function getQueryTemplate(string $type): string
    {
        if (!isset(self::QUERY_TEMPLATES[$type])) {
            throw new InvalidArgumentException("Unknown template type: {$type}");
        }

        return self::QUERY_TEMPLATES[$type];
    }

    /**
     * Interpolate a template with provided values.
     *
     * @param string $type The template type
     * @param array<string, string> $values The values to interpolate
     * @return string The interpolated query
     */
    public function interpolateTemplate(string $type, array $values): string
    {
        $template = $this->getQueryTemplate($type);

        return str_replace(
            array_map(fn ($key) => '{' . $key . '}', array_keys($values)),
            array_values($values),
            $template
        );
    }

    /**
     * Get the cache TTL in seconds.
     *
     * @return int The TTL in seconds
     */
    public function getCacheTtl(): int
    {
        return self::CACHE_TTL;
    }

    /**
     * Query an AI platform with the given query.
     *
     * @param string|null $platform The platform to query (chatgpt, perplexity)
     * @param string|null $query The query to send
     * @param bool $useFreeModel Whether to use free-tier model (default: true)
     * @return AIQueryResult The query result
     * @throws InvalidArgumentException If platform or query is invalid
     */
    public function query(?string $platform, ?string $query, bool $useFreeModel = true): AIQueryResult
    {
        // Validate platform
        if ($platform === null || !$this->isValidPlatform($platform)) {
            throw new InvalidArgumentException("Invalid platform: {$platform}");
        }

        // Validate query
        if ($query === null || trim($query) === '') {
            throw new InvalidArgumentException('Query cannot be empty');
        }

        // Get model for platform
        $model = $this->getModelForPlatform($platform);

        // Create cache key
        $cacheKey = $this->getCacheKey($platform, $query);

        // Use cache to avoid redundant API calls
        return Cache::remember(
            $cacheKey,
            self::CACHE_TTL,
            fn () => $this->executeQuery($platform, $query, $model)
        );
    }

    /**
     * Execute the actual query against OpenRouter.
     *
     * @param string $platform The platform identifier
     * @param string $query The query text
     * @param string $model The model to use
     * @return AIQueryResult The query result
     */
    private function executeQuery(string $platform, string $query, string $model): AIQueryResult
    {
        $startTime = microtime(true);

        try {
            $response = $this->client->generate($query, [
                'model' => $model,
            ]);

            $latencyMs = (int) ((microtime(true) - $startTime) * 1000);

            if (!$response->success) {
                return $this->handleErrorResponse($response, $platform, $query, $latencyMs);
            }

            return AIQueryResult::success(
                responseText: $response->content ?? '',
                model: $response->model ?? $model,
                tokensUsed: $response->tokensUsed ?? 0,
                latencyMs: $latencyMs,
                platform: $platform,
                queryText: $query,
            );
        } catch (\Exception $e) {
            $latencyMs = (int) ((microtime(true) - $startTime) * 1000);

            Log::error('AIQueryEngine query failed', [
                'platform' => $platform,
                'query' => $query,
                'error' => $e->getMessage(),
            ]);

            return AIQueryResult::error(
                errorMessage: $e->getMessage(),
                latencyMs: $latencyMs,
                platform: $platform,
                queryText: $query,
            );
        }
    }

    /**
     * Handle an error response from OpenRouter.
     *
     * @param AIResponse $response The error response
     * @param string $platform The platform identifier
     * @param string $query The query text
     * @param int $latencyMs The latency in milliseconds
     * @return AIQueryResult The error result
     */
    private function handleErrorResponse(
        AIResponse $response,
        string $platform,
        string $query,
        int $latencyMs
    ): AIQueryResult {
        $errorMessage = $response->errorMessage ?? 'Unknown error';

        Log::warning('AIQueryEngine received error response', [
            'platform' => $platform,
            'error' => $errorMessage,
        ]);

        return AIQueryResult::error(
            errorMessage: $errorMessage,
            latencyMs: $latencyMs,
            platform: $platform,
            queryText: $query,
        );
    }

    /**
     * Generate a cache key for the query.
     *
     * @param string $platform The platform identifier
     * @param string $query The query text
     * @return string The cache key
     */
    private function getCacheKey(string $platform, string $query): string
    {
        return 'ai_query:' . $platform . ':' . md5($query);
    }

    /**
     * Parse brand mentions from a response text.
     *
     * @param string $response The response text to parse
     * @param array<string> $brands The brands to search for
     * @return array<int, array{brand: string, position: int, context: string}> Array of mentions
     */
    public function parseMentions(string $response, array $brands): array
    {
        if (empty($response) || empty($brands)) {
            return [];
        }

        $mentions = [];
        $responseLower = mb_strtolower($response);
        $responseLength = mb_strlen($response);

        foreach ($brands as $brand) {
            if (empty($brand)) {
                continue;
            }

            $brandLower = mb_strtolower($brand);
            $brandLength = mb_strlen($brand);
            $offset = 0;

            // Find all occurrences of the brand
            while (($position = mb_strpos($responseLower, $brandLower, $offset)) !== false) {
                // Extract context (100 characters around the mention)
                $contextStart = max(0, $position - 50);
                $contextEnd = min($responseLength, $position + $brandLength + 50);
                $contextLength = $contextEnd - $contextStart;
                $context = mb_substr($response, $contextStart, $contextLength);

                $mentions[] = [
                    'brand' => $brand,
                    'position' => $position,
                    'context' => $context,
                ];

                $offset = $position + $brandLength;
            }
        }

        return $mentions;
    }

    /**
     * Analyze sentiment of a text.
     *
     * Uses keyword-based sentiment analysis for MVP. Can be enhanced
     * later with ML models for more accurate analysis.
     *
     * @param string $context The text to analyze
     * @return string The sentiment: 'positive', 'negative', 'neutral', or 'mixed'
     */
    public function analyzeSentiment(string $context): string
    {
        if (empty(trim($context))) {
            return 'neutral';
        }

        $contextLower = mb_strtolower($context);

        // Count positive and negative keywords
        $positiveCount = $this->countKeywordMatches($contextLower, self::POSITIVE_KEYWORDS);
        $negativeCount = $this->countKeywordMatches($contextLower, self::NEGATIVE_KEYWORDS);

        // Determine sentiment based on keyword counts
        if ($positiveCount > 0 && $negativeCount > 0) {
            return 'mixed';
        }

        if ($positiveCount > 0) {
            return 'positive';
        }

        if ($negativeCount > 0) {
            return 'negative';
        }

        return 'neutral';
    }

    /**
     * Count keyword matches in text with word boundary checking.
     *
     * @param string $text The text to search
     * @param array<string> $keywords The keywords to match
     * @return int The number of keyword matches
     */
    private function countKeywordMatches(string $text, array $keywords): int
    {
        $count = 0;

        foreach ($keywords as $keyword) {
            // Use word boundary regex for accurate matching
            $pattern = '/\b' . preg_quote($keyword, '/') . '\b/i';
            if (preg_match($pattern, $text)) {
                $count++;
            }
        }

        return $count;
    }
}
