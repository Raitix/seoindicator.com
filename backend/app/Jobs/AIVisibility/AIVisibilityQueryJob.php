<?php

namespace App\Jobs\AIVisibility;

use App\Models\AIVisibility\AIQueryResult as AIQueryResultModel;
use App\Services\AIVisibility\AIQueryEngine;
use App\Services\AIVisibility\MentionParser;
use App\Services\AIVisibility\DTOs\AIQueryResult;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Job to execute a single AI visibility query.
 *
 * Queries an AI platform and stores the results for brand mention tracking.
 *
 * @see TASK-34-7
 * @see SPEC-034
 */
class AIVisibilityQueryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var array<int>
     */
    public array $backoff = [60];

    /**
     * The project ID for the query.
     *
     * @var int
     */
    public int $projectId;

    /**
     * The platform to query (chatgpt, perplexity, etc.).
     *
     * @var string
     */
    public string $platform;

    /**
     * The query text to send to the AI.
     *
     * @var string
     */
    public string $query;

    /**
     * Whether to use a free-tier model.
     *
     * @var bool
     */
    public bool $useFreeModel;

    /**
     * Create a new job instance.
     *
     * @param int $projectId
     * @param string $platform
     * @param string $query
     * @param bool $useFreeModel
     */
    public function __construct(
        int $projectId,
        string $platform,
        string $query,
        bool $useFreeModel = true
    ) {
        $this->projectId = $projectId;
        $this->platform = $platform;
        $this->query = $query;
        $this->useFreeModel = $useFreeModel;
        $this->onQueue('ai-visibility');
    }

    /**
     * Execute the job.
     *
     * @param AIQueryEngine $engine
     * @param MentionParser $parser
     * @return array
     * @throws \Exception
     */
    public function handle(AIQueryEngine $engine, MentionParser $parser): array
    {
        // Check cache first
        $cacheKey = $this->getCacheKey();
        $cachedResult = Cache::get($cacheKey);

        if ($cachedResult !== null) {
            Log::info('AIVisibilityQueryJob cache hit', [
                'project_id' => $this->projectId,
                'platform' => $this->platform,
                'query_hash' => $this->getQueryHash(),
            ]);

            // Store cached result in database
            $this->storeResult($cachedResult, true);

            return array_merge($cachedResult, ['cache_hit' => true]);
        }

        // Execute query via AIQueryEngine
        try {
            $result = $engine->query($this->platform, $this->query, $this->useFreeModel);

            if ($result === null) {
                throw new \Exception('AI query returned null result');
            }

            // Handle both DTO and array returns from the engine
            if ($result instanceof AIQueryResult) {
                $responseText = $result->responseText ?? '';
                $model = $result->model ?? '';
                $tokensUsed = $result->tokensUsed ?? 0;
            } elseif (is_object($result) && method_exists($result, 'jsonSerialize')) {
                $resultArray = $result->jsonSerialize();
                $responseText = $resultArray['responseText'] ?? $resultArray['response'] ?? '';
                $model = $resultArray['model'] ?? '';
                $tokensUsed = $resultArray['tokensUsed'] ?? $resultArray['tokens_used'] ?? 0;
            } elseif (is_array($result)) {
                $responseText = $result['response'] ?? $result['responseText'] ?? '';
                $model = $result['model'] ?? '';
                $tokensUsed = $result['tokens_used'] ?? $result['tokensUsed'] ?? 0;
            } else {
                throw new \Exception('Invalid result type from AIQueryEngine');
            }

            // Parse mentions from response
            $mentions = [];
            if (!empty($responseText)) {
                $mentions = $parser->extractMentions(
                    $responseText,
                    [$this->getBrandName()]
                );
            }

            // Prepare result data
            $resultData = [
                'query_hash' => $this->getQueryHash(),
                'platform' => $this->platform,
                'query' => $this->query,
                'response_text' => $responseText,
                'model' => $model,
                'tokens_used' => $tokensUsed,
                'mentions' => $mentions,
                'cache_hit' => false,
            ];

            // Store in database
            $this->storeResult($resultData, false);

            // Cache the result for 7 days (604800 seconds)
            Cache::put($cacheKey, $resultData, 604800);

            return $resultData;
        } catch (\Exception $e) {
            Log::error('AIVisibilityQueryJob failed', [
                'project_id' => $this->projectId,
                'platform' => $this->platform,
                'query' => $this->query,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     *
     * @param \Throwable $exception
     * @return void
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('AIVisibilityQueryJob failed permanently', [
            'project_id' => $this->projectId,
            'platform' => $this->platform,
            'query' => $this->query,
            'error' => $exception->getMessage(),
        ]);
    }

    /**
     * Get the cache key for this query.
     *
     * @return string
     */
    public function getCacheKey(): string
    {
        return 'ai_visibility:query:' . md5($this->projectId . ':' . $this->platform . ':' . $this->query);
    }

    /**
     * Get the query hash (MD5 of platform and query).
     *
     * @return string
     */
    public function getQueryHash(): string
    {
        return md5($this->projectId . ':' . $this->platform . ':' . $this->query);
    }

    /**
     * Get the lock key for preventing overlapping jobs.
     *
     * @return string
     */
    public function getLockKey(): string
    {
        return 'ai_visibility_lock:' . $this->projectId . ':' . $this->getQueryHash();
    }

    /**
     * Get the middleware the job should pass through.
     *
     * @return array
     */
    public function middleware(): array
    {
        return [
            new \Illuminate\Queue\Middleware\WithoutOverlapping($this->getLockKey(), 300),
        ];
    }

    /**
     * Store the query result in the database.
     *
     * @param array $resultData
     * @param bool $isCacheHit
     * @return void
     */
    protected function storeResult(array $resultData, bool $isCacheHit): void
    {
        // Sanitize query for storage
        $sanitizedQuery = strip_tags($this->query);

        AIQueryResultModel::create([
            'project_id' => $this->projectId,
            'platform' => $this->platform,
            'query_text' => $sanitizedQuery,
            'response_text' => $resultData['response_text'] ?? '',
            'model_used' => $resultData['model'] ?? '',
            'tokens_used' => $resultData['tokens_used'] ?? 0,
            'success' => true,
            'mentions' => $resultData['mentions'] ?? [],
            'queried_at' => now(),
        ]);
    }

    /**
     * Get the brand name for this project.
     *
     * @return string
     */
    protected function getBrandName(): string
    {
        // Get brand name from project
        $project = \App\Models\AIVisibility\AIVisibilityProject::find($this->projectId);
        return $project?->brand_name ?? '';
    }
}
