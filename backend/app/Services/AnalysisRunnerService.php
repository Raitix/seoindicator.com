<?php

namespace App\Services;

use App\Exceptions\AIAnalysisException;
use App\Models\AIAnalysisRequest;
use App\Models\SEOAnalysis;
use App\Services\AI\AIAnalysisServiceInterface;
use App\Services\AI\DTOs\AnalysisResult;
use App\Services\SEO\Checkers\IndicatorCheckerInterface;
use App\Services\SEO\Checkers\IndicatorResult;
use App\Services\SEO\ScoreCalculatorService;
use App\Services\SEO\ScoreResult;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Analysis Runner Service - Orchestrates SEO indicator checks.
 *
 * Runs all registered indicator checkers, aggregates results,
 * and manages the analysis lifecycle.
 *
 * Supports unified AI analysis (single call for all indicators)
 * with feature flag for backward compatibility.
 *
 * @see TASK-18-6
 * @see TASK-31-13
 * @see SPEC-018
 * @see SPEC-031
 */
class AnalysisRunnerService
{
    /**
     * Default timeout in seconds for entire analysis.
     */
    private const DEFAULT_TIMEOUT = 60;

    /**
     * Default timeout per indicator in seconds.
     */
    private const DEFAULT_INDICATOR_TIMEOUT = 5;

    /**
     * Registered indicator checkers.
     *
     * @var array<string, IndicatorCheckerInterface>
     */
    private array $checkers = [];

    /**
     * Score calculator service.
     */
    private ScoreCalculatorService $scoreCalculator;

    /**
     * AI Analysis Service for unified analysis.
     */
    private ?AIAnalysisServiceInterface $aiAnalysisService = null;

    /**
     * Create a new AnalysisRunnerService instance.
     *
     * @param iterable<IndicatorCheckerInterface>|null $indicatorCheckers
     * @param ScoreCalculatorService|null $scoreCalculator
     * @param AIAnalysisServiceInterface|null $aiAnalysisService
     */
    public function __construct(
        ?iterable $indicatorCheckers = null,
        ?ScoreCalculatorService $scoreCalculator = null,
        ?AIAnalysisServiceInterface $aiAnalysisService = null
    ) {
        // Auto-discover checkers from service container if not provided
        if ($indicatorCheckers !== null) {
            foreach ($indicatorCheckers as $checker) {
                $this->registerChecker($checker);
            }
        } else {
            $this->discoverCheckers();
        }

        $this->scoreCalculator = $scoreCalculator ?? app(ScoreCalculatorService::class);

        // Optional AI Analysis Service - may not be available in all contexts
        if ($aiAnalysisService !== null) {
            $this->aiAnalysisService = $aiAnalysisService;
        } elseif (app()->bound(AIAnalysisServiceInterface::class)) {
            $this->aiAnalysisService = app(AIAnalysisServiceInterface::class);
        }
    }

    /**
     * Run SEO analysis on a URL.
     *
     * @param string $url Validated URL
     * @param string $html HTML content
     * @param array $options Optional settings
     *   - indicators: array (specific indicators to run)
     *   - excludeIndicators: array (indicators to skip)
     *   - checkers: array (manual checker instances to use)
     *   - timeout: int (total timeout in seconds, default 60)
     *   - user_id: int (link to authenticated user)
     *   - session_id: string (link to anonymous session)
     *   - use_unified: bool (override feature flag for this analysis)
     *   - model: string (specific AI model to use)
     *   - force_refresh: bool (bypass cache)
     *   - return_model: bool (return SEOAnalysis model instead of array)
     * @return SEOAnalysis|array Analysis result (model or array based on return_model)
     * @throws \InvalidArgumentException On invalid input
     */
    public function run(string $url, string $html, array $options = []): SEOAnalysis|array
    {
        $startTime = microtime(true);

        // Validate input
        $this->validateInput($url, $html);

        // Check feature flag for unified vs legacy flow
        // Default to unified (true) unless explicitly set to false
        $configValue = Config::get('ai.unified_prompt_enabled');
        $useUnified = $options['use_unified'] ?? ($configValue !== false);

        if ($useUnified && $this->aiAnalysisService !== null) {
            return $this->runUnified($url, $html, $options, $startTime);
        }

        return $this->runLegacy($url, $html, $options, $startTime);
    }

    /**
     * Run unified AI analysis (single AI call).
     *
     * @param string $url
     * @param string $html
     * @param array $options
     * @param float $startTime
     * @return SEOAnalysis
     */
    private function runUnified(string $url, string $html, array $options, float $startTime): SEOAnalysis
    {
        // Create SEOAnalysis record
        $analysis = $this->createAnalysisRecord($url, $options);

        try {
            // Update status to running
            $analysis->markAsRunning();

            // Build AI options
            $aiOptions = [];
            if (isset($options['model'])) {
                $aiOptions['model'] = $options['model'];
            }
            if (isset($options['timeout'])) {
                $aiOptions['timeout'] = $options['timeout'];
            }
            if (isset($options['force_refresh'])) {
                $aiOptions['force_refresh'] = $options['force_refresh'];
            }

            // Execute unified AI analysis
            $result = $this->aiAnalysisService->analyze($url, $html, $aiOptions);

            // Store analysis with AI metadata
            $this->storeUnifiedAnalysis($analysis, $result);

            // Log success
            Log::info('Unified AI analysis completed', [
                'url' => $url,
                'analysis_id' => $analysis->id,
                'model' => $result->model,
                'tokens' => $result->getTotalTokens(),
                'latency_ms' => $result->latencyMs,
            ]);

            // Refresh the model to get all updated data
            $analysis->refresh();
            return $analysis;

        } catch (AIAnalysisException $e) {
            Log::error('AI analysis failed', [
                'url' => $url,
                'analysis_id' => $analysis->id,
                'error' => $e->getMessage(),
                'context' => $e->getContext(),
            ]);

            // Store failed analysis
            $this->storeFailedAnalysis($analysis, $e);

            // Refresh and return the failed analysis
            $analysis->refresh();
            return $analysis;
        } catch (\Throwable $e) {
            Log::error('Unified analysis failed', [
                'url' => $url,
                'analysis_id' => $analysis->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $analysis->markAsFailed($e->getMessage());
            $analysis->refresh();
            return $analysis;
        }
    }

    /**
     * Run legacy indicator-based analysis (multiple calls).
     *
     * @param string $url
     * @param string $html
     * @param array $options
     * @param float $startTime
     * @return SEOAnalysis
     */
    private function runLegacy(string $url, string $html, array $options, float $startTime): SEOAnalysis
    {
        // Get checkers to run
        $checkers = $this->getCheckersToRun($options);

        // Create SEOAnalysis record
        $analysis = $this->createAnalysisRecord($url, $options);

        try {
            // Update status to running
            $analysis->markAsRunning();

            // Create crawler from HTML
            $crawler = new Crawler($html);

            // Run indicators
            $indicatorResults = $this->runIndicators($checkers, $crawler, $url, $options);

            // Calculate scores
            $scoreResult = $this->scoreCalculator->calculate($indicatorResults);

            // Build result array for storage
            $resultArray = $this->buildLegacyResult($url, $indicatorResults, $scoreResult, $startTime);

            // Update analysis record
            $analysis->markAsCompleted($scoreResult->overallScore, $resultArray);

            // Refresh and return the model
            $analysis->refresh();
            return $analysis;

        } catch (\Throwable $e) {
            Log::error('Legacy analysis failed', [
                'url' => $url,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $analysis->markAsFailed($e->getMessage());
            $analysis->refresh();
            return $analysis;
        }
    }

    /**
     * Store unified analysis with AI metadata.
     *
     * @param SEOAnalysis $analysis
     * @param AnalysisResult $result
     */
    private function storeUnifiedAnalysis(SEOAnalysis $analysis, AnalysisResult $result): void
    {
        // Format indicator data for storage
        $indicatorData = $this->formatIndicatorData($result->indicators);

        // Create AI analysis request record
        $aiRequest = AIAnalysisRequest::create([
            'analysis_id' => $analysis->id,
            'model' => $result->model,
            'prompt_tokens' => $result->promptTokens,
            'response_tokens' => $result->responseTokens,
            'total_tokens' => $result->getTotalTokens(),
            'latency_ms' => $result->latencyMs,
            'truncation_strategies' => $result->truncationStrategies ?: null,
            'status' => AIAnalysisRequest::STATUS_COMPLETED,
        ]);

        // Update SEO analysis with unified data
        $analysis->markAsCompletedUnified([
            'overall_score' => $result->overallScore,
            'overall_summary' => $result->overallSummary,
            'category_scores' => $result->categoryScores,
            'analysis_data' => $indicatorData,
            'ai_model' => $result->model,
            'prompt_tokens' => $result->promptTokens,
            'response_tokens' => $result->responseTokens,
            'total_tokens' => $result->getTotalTokens(),
            'truncation_applied' => $result->truncationStrategies ?: null,
            'ai_request_id' => $aiRequest->id,
        ]);
    }

    /**
     * Store failed analysis for retry capability.
     *
     * @param SEOAnalysis $analysis
     * @param AIAnalysisException $exception
     */
    private function storeFailedAnalysis(SEOAnalysis $analysis, AIAnalysisException $exception): void
    {
        // Create AI analysis request record for failed attempt
        AIAnalysisRequest::create([
            'analysis_id' => $analysis->id,
            'model' => $exception->getContext()['model'] ?? 'unknown',
            'status' => AIAnalysisRequest::STATUS_FAILED,
            'error_message' => $exception->getMessage(),
        ]);

        // Mark analysis as failed
        $analysis->markAsFailed($exception->getMessage());
    }

    /**
     * Format indicator data for storage.
     *
     * @param array $indicators
     * @return array
     */
    private function formatIndicatorData(array $indicators): array
    {
        $data = [];

        foreach ($indicators as $indicator) {
            $id = $indicator['id'] ?? $indicator['indicator'] ?? null;
            if ($id) {
                $data[$id] = [
                    'score' => $indicator['score'] ?? 0,
                    'passed' => $indicator['passed'] ?? false,
                    'details' => $indicator['details'] ?? [],
                    'suggestions' => $indicator['suggestions'] ?? [],
                ];
            }
        }

        return $data;
    }

    /**
     * Build the unified result array.
     *
     * @param string $url
     * @param AnalysisResult $result
     * @param float $startTime
     * @return array
     */
    private function buildUnifiedResult(string $url, AnalysisResult $result, float $startTime): array
    {
        $indicators = [];
        foreach ($result->indicators as $indicator) {
            $indicators[] = [
                'id' => $indicator['id'] ?? $indicator['indicator'] ?? '',
                'name' => $indicator['name'] ?? '',
                'category' => $indicator['category'] ?? 'general',
                'score' => $indicator['score'] ?? 0,
                'weight' => $indicator['weight'] ?? 1.0,
                'passed' => $indicator['passed'] ?? false,
                'details' => $indicator['details'] ?? [],
                'suggestions' => $indicator['suggestions'] ?? [],
            ];
        }

        return [
            'url' => $url,
            'overallScore' => $result->overallScore,
            'status' => 'completed',
            'indicators' => $indicators,
            'summary' => [
                'overall' => $result->overallSummary,
                'passed' => count(array_filter($indicators, fn($i) => $i['passed'])),
                'warnings' => count(array_filter($indicators, fn($i) => !$i['passed'] && $i['score'] > 0)),
                'failed' => count(array_filter($indicators, fn($i) => !$i['passed'] && $i['score'] === 0)),
            ],
            'categoryScores' => $result->categoryScores,
            'timing' => [
                'total' => round((microtime(true) - $startTime) * 1000, 2),
                'ai_latency_ms' => $result->latencyMs,
            ],
            'ai_metadata' => [
                'model' => $result->model,
                'prompt_tokens' => $result->promptTokens,
                'response_tokens' => $result->responseTokens,
                'total_tokens' => $result->getTotalTokens(),
                'truncated' => $result->wasTruncated(),
                'truncation_strategies' => $result->truncationStrategies,
            ],
            'top_priorities' => $result->topPriorities,
            'errors' => [],
        ];
    }

    /**
     * Get list of available indicators.
     *
     * @return array<array{id: string, name: string, category: string, weight: float}>
     */
    public function getAvailableIndicators(): array
    {
        $indicators = [];

        foreach ($this->checkers as $checker) {
            $indicators[] = [
                'id' => $checker->id(),
                'name' => $checker->name(),
                'category' => $checker->category(),
                'weight' => $checker->weight(),
            ];
        }

        return $indicators;
    }

    /**
     * Run a single indicator.
     *
     * @param string $indicatorId
     * @param string $url
     * @param string $html
     * @return array Indicator result
     * @throws \InvalidArgumentException If indicator not found
     */
    public function runIndicator(string $indicatorId, string $url, string $html): array
    {
        if (!isset($this->checkers[$indicatorId])) {
            throw new \InvalidArgumentException("Indicator '{$indicatorId}' not found");
        }

        $checker = $this->checkers[$indicatorId];
        $crawler = new Crawler($html);

        $startTime = microtime(true);
        $result = $checker->check($crawler, $url);
        $duration = (microtime(true) - $startTime) * 1000;

        $resultArray = $result->toArray();
        $resultArray['timing_ms'] = round($duration, 2);

        return $resultArray;
    }

    /**
     * Register a checker instance.
     *
     * @param IndicatorCheckerInterface $checker
     */
    public function registerChecker(IndicatorCheckerInterface $checker): void
    {
        $this->checkers[$checker->id()] = $checker;
    }

    /**
     * Validate input parameters.
     *
     * @param string $url
     * @param string $html
     * @throws \InvalidArgumentException
     */
    private function validateInput(string $url, string $html): void
    {
        if (empty($url)) {
            throw new \InvalidArgumentException('URL is required');
        }

        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new \InvalidArgumentException('Invalid URL format');
        }

        // Allow empty HTML - checkers will handle gracefully
        // Empty HTML is valid for testing or when only URL metadata is needed
    }

    /**
     * Get checkers to run based on options.
     *
     * @param array $options
     * @return array<IndicatorCheckerInterface>
     */
    private function getCheckersToRun(array $options): array
    {
        // If manual checkers provided, use those
        if (isset($options['checkers']) && is_array($options['checkers'])) {
            $checkers = [];
            foreach ($options['checkers'] as $checker) {
                if ($checker instanceof IndicatorCheckerInterface) {
                    $checkers[$checker->id()] = $checker;
                }
            }
            return $checkers;
        }

        // Filter by specific indicators
        if (isset($options['indicators']) && is_array($options['indicators'])) {
            return array_filter(
                $this->checkers,
                fn($id) => in_array($id, $options['indicators'], true),
                ARRAY_FILTER_USE_KEY
            );
        }

        // Exclude specific indicators
        if (isset($options['excludeIndicators']) && is_array($options['excludeIndicators'])) {
            return array_filter(
                $this->checkers,
                fn($id) => !in_array($id, $options['excludeIndicators'], true),
                ARRAY_FILTER_USE_KEY
            );
        }

        return $this->checkers;
    }

    /**
     * Create SEOAnalysis record.
     *
     * @param string $url
     * @param array $options
     * @return SEOAnalysis
     */
    private function createAnalysisRecord(string $url, array $options): SEOAnalysis
    {
        $parsedUrl = parse_url($url);
        $domain = $parsedUrl['host'] ?? '';

        // Check for existing analysis (update mode)
        $normalizedUrl = $this->normalizeUrl($url);
        $existingAnalysis = SEOAnalysis::where('normalized_url', $normalizedUrl)->first();

        if ($existingAnalysis) {
            // Update existing record
            $existingAnalysis->update([
                'status' => SEOAnalysis::STATUS_PENDING,
                'user_id' => $options['user_id'] ?? $existingAnalysis->user_id,
                'session_id' => $options['session_id'] ?? $existingAnalysis->session_id,
            ]);
            return $existingAnalysis;
        }

        return SEOAnalysis::create([
            'domain' => $domain,
            'normalized_url' => $normalizedUrl,
            'status' => SEOAnalysis::STATUS_PENDING,
            'overall_score' => 0,
            'analysis_data' => [],
            'user_id' => $options['user_id'] ?? null,
            'session_id' => $options['session_id'] ?? null,
        ]);
    }

    /**
     * Run all indicator checkers.
     *
     * @param array<IndicatorCheckerInterface> $checkers
     * @param Crawler $crawler
     * @param string $url
     * @param array $options
     * @return array<IndicatorResult>
     */
    private function runIndicators(
        array $checkers,
        Crawler $crawler,
        string $url,
        array $options
    ): array {
        $results = [];
        $timeout = $options['timeout'] ?? self::DEFAULT_TIMEOUT;
        $startTime = microtime(true);

        foreach ($checkers as $checker) {
            // Check total timeout
            if ((microtime(true) - $startTime) >= $timeout) {
                Log::warning('Analysis timeout reached, skipping remaining indicators', [
                    'url' => $url,
                    'completed' => count($results),
                    'remaining' => count($checkers) - count($results),
                ]);
                break;
            }

            $indicatorStart = microtime(true);

            try {
                $result = $checker->check($crawler, $url);
                $results[$checker->id()] = $result;

            } catch (\Throwable $e) {
                Log::error('Indicator check failed', [
                    'indicator' => $checker->id(),
                    'url' => $url,
                    'error' => $e->getMessage(),
                ]);

                // Create a failed result
                $results[$checker->id()] = new IndicatorResult(
                    id: $checker->id(),
                    name: $checker->name(),
                    category: $checker->category(),
                    score: 0,
                    weight: $checker->weight(),
                    passed: false,
                    details: [
                        'error' => $e->getMessage(),
                    ]
                );
            }
        }

        return array_values($results);
    }

    /**
     * Build the final result array for legacy storage.
     *
     * @param string $url
     * @param array<IndicatorResult> $indicatorResults
     * @param ScoreResult $scoreResult
     * @param float $startTime
     * @return array
     */
    private function buildLegacyResult(
        string $url,
        array $indicatorResults,
        ScoreResult $scoreResult,
        float $startTime
    ): array {
        $indicators = [];
        $errors = [];
        $timing = ['indicators' => []];

        foreach ($indicatorResults as $result) {
            $indicators[] = $result->toArray();

            if (!$result->passed && $result->score === 0) {
                $errors[] = [
                    'indicator' => $result->id,
                    'error' => $result->details['error'] ?? 'Indicator check failed',
                ];
            }
        }

        return [
            'url' => $url,
            'overallScore' => $scoreResult->overallScore,
            'status' => 'completed',
            'indicators' => $indicators,
            'summary' => $scoreResult->summary,
            'categoryScores' => $scoreResult->categoryScores,
            'timing' => [
                'total' => round((microtime(true) - $startTime) * 1000, 2),
            ],
            'errors' => $errors,
        ];
    }

    /**
     * Build a failed result.
     *
     * @param string $url
     * @param string $errorMessage
     * @param float $startTime
     * @return array
     */
    private function buildFailedResult(string $url, string $errorMessage, float $startTime): array
    {
        return [
            'url' => $url,
            'overallScore' => 0,
            'status' => 'failed',
            'indicators' => [],
            'summary' => ['passed' => 0, 'warnings' => 0, 'failed' => 0],
            'categoryScores' => [],
            'timing' => [
                'total' => round((microtime(true) - $startTime) * 1000, 2),
            ],
            'errors' => [
                ['error' => $errorMessage],
            ],
        ];
    }

    /**
     * Normalize URL for deduplication.
     *
     * @param string $url
     * @return string
     */
    private function normalizeUrl(string $url): string
    {
        $parsed = parse_url($url);
        
        $scheme = strtolower($parsed['scheme'] ?? 'https');
        $host = strtolower($parsed['host'] ?? '');
        $path = $parsed['path'] ?? '/';
        $query = $parsed['query'] ?? '';

        // Normalize path
        $path = rtrim($path, '/');
        if (empty($path)) {
            $path = '/';
        }

        // Build normalized URL
        $normalized = "{$scheme}://{$host}{$path}";
        
        if (!empty($query)) {
            // Sort query parameters for consistency
            parse_str($query, $params);
            ksort($params);
            $normalized .= '?' . http_build_query($params);
        }

        return $normalized;
    }

    /**
     * Discover and register checkers from the service container.
     */
    private function discoverCheckers(): void
    {
        try {
            // Try to get tagged checkers from container
            $checkers = app()->tagged('seo.indicator.checkers');
            
            foreach ($checkers as $checker) {
                if ($checker instanceof IndicatorCheckerInterface) {
                    $this->registerChecker($checker);
                }
            }
        } catch (\Throwable $e) {
            // No tagged checkers found, that's okay
            Log::debug('No tagged indicator checkers found', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
