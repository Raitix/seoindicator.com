<?php

namespace App\Services;

use App\Models\SEOAnalysis;
use App\Services\SEO\Checkers\IndicatorCheckerInterface;
use App\Services\SEO\Checkers\IndicatorResult;
use App\Services\SEO\ScoreCalculatorService;
use App\Services\SEO\ScoreResult;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Analysis Runner Service - Orchestrates SEO indicator checks.
 *
 * Runs all registered indicator checkers, aggregates results,
 * and manages the analysis lifecycle.
 *
 * @see TASK-18-6
 * @see SPEC-018
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
     * Create a new AnalysisRunnerService instance.
     *
     * @param iterable<IndicatorCheckerInterface>|null $indicatorCheckers
     * @param ScoreCalculatorService|null $scoreCalculator
     */
    public function __construct(
        ?iterable $indicatorCheckers = null,
        ?ScoreCalculatorService $scoreCalculator = null
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
     * @return array Analysis result
     * @throws \InvalidArgumentException On invalid input
     */
    public function run(string $url, string $html, array $options = []): array
    {
        $startTime = microtime(true);

        // Validate input
        $this->validateInput($url, $html);

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

            // Build result
            $result = $this->buildResult($url, $indicatorResults, $scoreResult, $startTime);

            // Update analysis record
            $analysis->markAsCompleted($result['overallScore'], $result);

            return $result;

        } catch (\Throwable $e) {
            Log::error('Analysis failed', [
                'url' => $url,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $analysis->markAsFailed($e->getMessage());

            return $this->buildFailedResult($url, $e->getMessage(), $startTime);
        }
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

        if (empty($html)) {
            throw new \InvalidArgumentException('HTML content is required');
        }
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
            'url' => $url,
            'domain' => $domain,
            'normalized_url' => $normalizedUrl,
            'status' => SEOAnalysis::STATUS_PENDING,
            'overall_score' => 0,
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
     * Build the final result array.
     *
     * @param string $url
     * @param array<IndicatorResult> $indicatorResults
     * @param ScoreResult $scoreResult
     * @param float $startTime
     * @return array
     */
    private function buildResult(
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
