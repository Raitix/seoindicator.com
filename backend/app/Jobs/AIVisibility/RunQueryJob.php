<?php

namespace App\Jobs\AIVisibility;

use App\Services\AIVisibility\AIQueryEngine;
use App\Services\AIVisibility\MentionParser;
use App\Models\AIVisibility\AIVisibilityProject;
use App\Models\AIVisibility\AIQueryResult;
use App\Models\AIVisibility\AIMention;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Job to run a query against an AI platform and track mentions.
 * 
 * This is an alias/wrapper for the test suite that provides a simpler interface
 * matching the expected test API.
 *
 * @see TASK-34-18
 * @see SPEC-034
 */
class RunQueryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds the job can run.
     */
    public int $timeout = 120;

    /**
     * The project ID for the query.
     */
    public int $projectId;

    /**
     * The platform to query.
     */
    public string $platform;

    /**
     * The query text.
     */
    public string $queryText;

    /**
     * Create a new job instance.
     */
    public function __construct(int $projectId, string $platform, string $queryText)
    {
        $this->projectId = $projectId;
        $this->platform = $platform;
        $this->queryText = $queryText;
    }

    /**
     * Execute the job.
     */
    public function handle(AIQueryEngine $engine, MentionParser $parser): void
    {
        $startTime = microtime(true);
        $project = AIVisibilityProject::find($this->projectId);

        if (!$project) {
            Log::error('RunQueryJob: Project not found', ['project_id' => $this->projectId]);
            return;
        }

        try {
            // Call OpenRouter API (or mock in tests)
            $response = $this->callAI($engine);

            $latencyMs = (int) ((microtime(true) - $startTime) * 1000);

            // Parse mentions from response
            $mentions = [];
            $brandMentioned = false;
            $competitorMentions = [];

            if (!empty($response)) {
                // Extract brand mentions
                $brandMentions = $parser->extractMentions($response, [$project->brand_name]);
                
                // Extract competitor mentions
                $competitorNames = $project->getCompetitorNames();
                $competitorMentionData = !empty($competitorNames) 
                    ? $parser->extractCompetitorMentions($response, $competitorNames)
                    : [];

                $brandMentioned = !empty($brandMentions);
                $competitorMentions = array_column($competitorMentionData, 'brand');
            }

            // Detect mention type
            $mentionType = $this->detectMentionType($response, $project->brand_name);

            // Analyze sentiment
            $sentiment = $this->analyzeSentiment($response, $project->brand_name);
            $sentimentScore = $this->calculateSentimentScore($response, $project->brand_name);

            // Extract context
            $context = $this->extractContext($response, $project->brand_name);

            // Store the result
            $queryResult = AIQueryResult::create([
                'project_id' => $this->projectId,
                'platform' => $this->platform,
                'query_text' => $this->queryText,
                'response_text' => $response,
                'success' => true,
                'brand_mentioned' => $brandMentioned,
                'mention_type' => $brandMentioned ? $mentionType : null,
                'sentiment' => $sentiment,
                'sentiment_score' => $sentimentScore,
                'context' => $context,
                'competitor_mentions' => $competitorMentions,
                'latency_ms' => $latencyMs,
                'queried_at' => now(),
            ]);

            // Create AIMention records for each brand mention
            if (!empty($brandMentions)) {
                foreach ($brandMentions as $mention) {
                    AIMention::create([
                        'project_id' => $this->projectId,
                        'query_result_id' => $queryResult->id,
                        'entity_type' => AIMention::ENTITY_TYPE_BRAND,
                        'entity_name' => $project->brand_name,
                        'mention_type' => $mention['type'] ?? 'mention',
                        'sentiment' => $sentiment,
                        'sentiment_score' => $sentimentScore,
                        'context' => $mention['context'] ?? $context,
                        'platform' => $this->platform,
                        'mentioned_at' => now(),
                    ]);
                }
            }

            // Create AIMention records for each competitor mention
            if (!empty($competitorMentionData)) {
                foreach ($competitorMentionData as $compMention) {
                    AIMention::create([
                        'project_id' => $this->projectId,
                        'query_result_id' => $queryResult->id,
                        'entity_type' => AIMention::ENTITY_TYPE_COMPETITOR,
                        'entity_name' => $compMention['brand'],
                        'mention_type' => $parser->detectMentionType($compMention['context'] ?? ''),
                        'sentiment' => 'neutral',
                        'sentiment_score' => null,
                        'context' => $compMention['context'] ?? null,
                        'platform' => $this->platform,
                        'mentioned_at' => now(),
                    ]);
                }
            }

            // Update project last_run_at if column exists
            if (in_array('last_run_at', \Schema::getColumnListing('ai_visibility_projects'))) {
                $project->update(['last_run_at' => now()]);
            }

            Log::info('RunQueryJob completed', [
                'project_id' => $this->projectId,
                'platform' => $this->platform,
                'brand_mentioned' => $brandMentioned,
            ]);

        } catch (\Exception $e) {
            Log::error('RunQueryJob failed', [
                'project_id' => $this->projectId,
                'platform' => $this->platform,
                'error' => $e->getMessage(),
            ]);

            // Store failed result
            AIQueryResult::create([
                'project_id' => $this->projectId,
                'platform' => $this->platform,
                'query_text' => $this->queryText,
                'response_text' => null,
                'success' => false,
                'error_message' => $e->getMessage(),
                'queried_at' => now(),
            ]);
        }
    }

    /**
     * Call the AI API.
     */
    protected function callAI(AIQueryEngine $engine): string
    {
        try {
            // Try using the engine first
            $result = $engine->query($this->platform, $this->queryText);

            // Check if the result was successful
            if ($result && is_object($result) && isset($result->success) && !$result->success) {
                $errorMessage = $result->errorMessage ?? 'Unknown error';
                throw new \RuntimeException("AI query failed: {$errorMessage}");
            }

            if ($result && is_object($result) && method_exists($result, 'getResponseText')) {
                return $result->getResponseText();
            }

            if ($result && is_object($result) && isset($result->responseText)) {
                return $result->responseText;
            }

            if ($result && is_array($result) && isset($result['response'])) {
                return $result['response'];
            }

            if ($result && is_array($result) && isset($result['responseText'])) {
                return $result['responseText'];
            }

            // Fallback to direct HTTP call
            return $this->callOpenRouterDirectly();

        } catch (\Exception $e) {
            Log::warning('RunQueryJob engine call failed, trying direct HTTP', [
                'error' => $e->getMessage(),
            ]);
            return $this->callOpenRouterDirectly();
        }
    }

    /**
     * Call OpenRouter API directly.
     */
    protected function callOpenRouterDirectly(): string
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . config('services.openrouter.api_key'),
            'Content-Type' => 'application/json',
        ])->post('https://openrouter.ai/api/v1/chat/completions', [
            'model' => config('ai-visibility.models.chatgpt', 'openai/gpt-3.5-turbo'),
            'messages' => [
                ['role' => 'user', 'content' => $this->queryText],
            ],
        ]);

        if ($response->successful()) {
            $data = $response->json();
            return $data['choices'][0]['message']['content'] ?? '';
        }

        // Throw exception for error responses
        $statusCode = $response->status();
        $errorData = $response->json();
        $errorMessage = $errorData['error'] ?? "HTTP error {$statusCode}";
        throw new \RuntimeException("OpenRouter API error ({$statusCode}): {$errorMessage}");
    }

    /**
     * Detect the type of mention.
     */
    protected function detectMentionType(string $response, string $brandName): string
    {
        $responseLower = strtolower($response);
        $brandLower = strtolower($brandName);

        // Check for recommendation
        if (preg_match('/(recommend|suggest|try|should use|best choice|go with)\s+.*' . preg_quote($brandLower, '/') . '/i', $responseLower)) {
            return 'recommendation';
        }

        // Check for comparison
        if (preg_match('/(compare|versus|vs|difference|better than|worse than)\s+.*' . preg_quote($brandLower, '/') . '/i', $responseLower)) {
            return 'comparison';
        }

        // Default to mention
        return 'mention';
    }

    /**
     * Analyze sentiment around brand mention.
     */
    protected function analyzeSentiment(string $response, string $brandName): string
    {
        $responseLower = strtolower($response);
        $brandLower = strtolower($brandName);

        // Find sentences containing brand name
        $sentences = preg_split('/[.!?]+/', $response);
        $brandSentences = array_filter($sentences, function ($sentence) use ($brandLower) {
            return stripos($sentence, $brandLower) !== false;
        });

        if (empty($brandSentences)) {
            return 'neutral';
        }

        $brandText = implode(' ', $brandSentences);

        // Simple sentiment analysis
        $positiveWords = ['great', 'excellent', 'amazing', 'best', 'good', 'recommend', 'love', 'powerful', 'useful', 'helpful'];
        $negativeWords = ['bad', 'poor', 'terrible', 'worst', 'hate', 'awful', 'disappointing', 'lacking', 'missing'];

        $positiveCount = 0;
        $negativeCount = 0;

        foreach ($positiveWords as $word) {
            $positiveCount += substr_count($brandText, $word);
        }

        foreach ($negativeWords as $word) {
            $negativeCount += substr_count($brandText, $word);
        }

        if ($positiveCount > $negativeCount + 1) {
            return 'positive';
        } elseif ($negativeCount > $positiveCount + 1) {
            return 'negative';
        } elseif ($positiveCount > 0 && $negativeCount > 0) {
            return 'mixed';
        }

        return 'neutral';
    }

    /**
     * Calculate sentiment score (-1 to 1).
     */
    protected function calculateSentimentScore(string $response, string $brandName): float
    {
        $sentiment = $this->analyzeSentiment($response, $brandName);

        return match ($sentiment) {
            'positive' => 0.6,
            'negative' => -0.6,
            'mixed' => 0.0,
            default => 0.0,
        };
    }

    /**
     * Extract context around brand mention.
     */
    protected function extractContext(string $response, string $brandName): ?string
    {
        $brandLower = strtolower($brandName);
        $responseLower = strtolower($response);

        $pos = stripos($response, $brandName);
        if ($pos === false) {
            $pos = stripos($response, $brandLower);
        }

        if ($pos === false) {
            return null;
        }

        // Get 50 chars before and after
        $start = max(0, $pos - 50);
        $end = min(strlen($response), $pos + strlen($brandName) + 50);

        return trim(substr($response, $start, $end - $start));
    }
}
