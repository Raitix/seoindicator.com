<?php

namespace App\Services;

use App\Models\Competitor;
use App\Models\CompetitorRanking;
use App\Models\Keyword;
use App\Models\Ranking;
use App\Models\SerpFeature;
use App\Repositories\SerpFeatureRepository;
use App\Services\SERP\SERPEntry;
use App\Services\SERP\SERPOptionsData;
use App\Services\SERP\SERPResult;
use App\Services\Traits\EmitsWebhookEvents;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class RankCheckerService
{
    use EmitsWebhookEvents;

    public function __construct(
        private readonly SERPFetcherInterface $fetcher,
        private readonly ?AlertEvaluator $alertEvaluator = null,
        private readonly ?NotificationDispatcher $notificationDispatcher = null,
        private readonly ?SERPFeatureDetector $featureDetector = null,
        private readonly ?SerpFeatureRepository $featureRepository = null,
        private readonly ?SerpFeatureChangeTracker $changeTracker = null,
    ) {
    }

    /**
     * Check a single keyword against user + competitor domains.
     *
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    public function checkKeyword(string $keyword, string $userDomain, array $competitorDomains = [], array $options = []): array
    {
        $start = microtime(true);
        $sanitizedUser = $this->sanitizeDomain($userDomain);
        $sanitizedCompetitors = array_values(array_filter(array_map([$this, 'sanitizeDomain'], $competitorDomains)));

        $targetUrl = $sanitizedUser ? $this->normalizeUrl($sanitizedUser) : null;
        $serpOptions = new SERPOptionsData(targetUrl: $targetUrl);

        $result = $this->fetcher->fetch($keyword, $serpOptions);
        $payload = $this->normalizeResult($result);

        $results = $payload['results'] ?? [];
        $userMatch = $this->findDomainMatch($results, $sanitizedUser);

        $competitorResults = [];
        foreach ($sanitizedCompetitors as $domain) {
            $match = $this->findDomainMatch($results, $domain);
            $competitorEntry = [
                'domain' => $domain,
                'position' => $match['position'],
                'url' => $match['url'],
            ];

            if (($options['throwOnCompetitorFailure'] ?? true) === false && $match['position'] === null) {
                $message = sprintf('Competitor ranking not found for domain: %s', $domain);
                Log::error($message, ['domain' => $domain, 'keyword' => $keyword]);
                $competitorEntry['error'] = $message;
            }

            $competitorResults[] = $competitorEntry;
        }

        $durationMs = $payload['duration_ms'] ?? (int) round((microtime(true) - $start) * 1000);

        $response = [
            'keyword' => $keyword,
            'user' => [
                'domain' => $sanitizedUser,
                'position' => $userMatch['position'],
                'url' => $userMatch['url'],
            ],
            'competitors' => $competitorResults,
            'duration_ms' => (int) $durationMs,
        ];

        if (isset($payload['serp_features'])) {
            $response['serp_features'] = $payload['serp_features'];
        }

        return $response;
    }

    /**
     * @param array<int, string> $keywords
     * @param array<int, string> $competitorDomains
     * @param array<string, mixed> $options
     * @return array<int, array<string, mixed>>
     */
    public function checkKeywords(array $keywords, string $userDomain, array $competitorDomains = [], array $options = []): array
    {
        $results = [];
        foreach ($keywords as $keyword) {
            $results[] = $this->checkKeyword((string) $keyword, $userDomain, $competitorDomains, $options);
        }

        return $results;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Competitor>
     */
    public function getCompetitorsForSite(int $userId)
    {
        return Competitor::where('user_id', $userId)->orderBy('id')->get();
    }

    /**
     * Run a keyword check and store ranking + competitor rankings.
     *
     * Emits ranking.changed webhook event if position changed.
     *
     * @param array<int, string>|null $competitorDomains
     */
    public function checkAndStore(int $keywordId, ?array $competitorDomains = null): bool
    {
        $keyword = Keyword::find($keywordId);
        if (!$keyword) {
            throw new \RuntimeException('Keyword not found.');
        }

        $userDomain = '';
        if (!empty($keyword->target_url)) {
            $userDomain = $this->sanitizeDomain($keyword->target_url);
        } elseif ($keyword->site_id && $keyword->site) {
            $userDomain = $this->sanitizeDomain($keyword->site->url);
        }

        if ($competitorDomains === null) {
            $competitorDomains = $this->getCompetitorsForSite($keyword->user_id)
                ->pluck('domain')
                ->all();
        }

        // Get previous position before check
        $previousPosition = $this->getLatestPosition($keyword->id);

        $result = $this->checkKeyword($keyword->keyword, $userDomain, $competitorDomains);

        $checkedAt = now();
        $newPosition = $result['user']['position'];
        $serpFeatures = $result['serp_features'] ?? null;
        $serpFeaturesArray = is_array($serpFeatures) ? $serpFeatures : null;
        $serpFeaturesJson = is_array($serpFeatures) ? json_encode($serpFeatures) : $serpFeatures;

        Ranking::create([
            'keyword_id' => $keyword->id,
            'position' => $newPosition,
            'url_found' => $result['user']['url'],
            'serp_features' => $serpFeaturesJson,
            'checked_at' => $checkedAt,
            'check_duration_ms' => $result['duration_ms'] ?? null,
            'error' => null,
        ]);

        $keyword->last_checked_at = $checkedAt;
        $keyword->save();

        if (!empty($competitorDomains)) {
            $competitors = Competitor::where('user_id', $keyword->user_id)
                ->whereIn('domain', $competitorDomains)
                ->get()
                ->keyBy('domain');

            foreach ($result['competitors'] as $competitorResult) {
                $domain = $competitorResult['domain'] ?? null;
                if (!$domain || !$competitors->has($domain)) {
                    continue;
                }

                CompetitorRanking::create([
                    'competitor_id' => $competitors[$domain]->id,
                    'keyword_id' => $keyword->id,
                    'position' => $competitorResult['position'],
                    'url' => $competitorResult['url'],
                    'checked_at' => $checkedAt,
                ]);
            }
        }

        // Detect and store SERP features if enabled for the site
        $this->detectAndStoreSerpFeatures($keyword, $userDomain, $result);

        // Emit webhook event if position changed
        if ($this->hasPositionChanged($previousPosition, $newPosition)) {
            $this->emitRankingChangedEvent(
                userId: $keyword->user_id,
                keywordId: $keyword->id,
                keyword: $keyword->keyword,
                siteId: $keyword->site_id ?? 0,
                oldPosition: $previousPosition,
                newPosition: $newPosition,
                urlFound: $result['user']['url'],
                checkedAt: $checkedAt->toIso8601ZuluString(),
                serpFeatures: $serpFeaturesArray
            );
        }

        // Evaluate and dispatch alerts
        $this->evaluateAndDispatchAlerts(
            $keyword,
            $previousPosition,
            $newPosition,
            $serpFeaturesArray
        );

        return true;
    }

    /**
     * Evaluate alerts and dispatch notifications.
     *
     * @param Keyword $keyword
     * @param int|null $oldPosition
     * @param int|null $newPosition
     * @param array|null $serpFeatures
     */
    protected function evaluateAndDispatchAlerts(
        Keyword $keyword,
        ?int $oldPosition,
        ?int $newPosition,
        ?array $serpFeatures
    ): void {
        // Skip if services not available (for backward compatibility)
        if (!$this->alertEvaluator || !$this->notificationDispatcher) {
            return;
        }

        try {
            // Evaluate position changes
            $events = $this->alertEvaluator->evaluate(
                $keyword,
                $oldPosition,
                $newPosition,
                ['serp_features' => $this->detectSerpFeatureChanges($keyword, $serpFeatures)]
            );

            if (empty($events)) {
                return;
            }

            // Dispatch notifications
            if ($keyword->user) {
                $this->notificationDispatcher->dispatch(
                    $keyword->user,
                    $keyword,
                    $events
                );
            }
        } catch (\Throwable $e) {
            // Don't fail the ranking check if alerts fail
            Log::error('Alert evaluation failed', [
                'keyword_id' => $keyword->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Detect SERP feature changes for alert evaluation.
     *
     * @param Keyword $keyword
     * @param array|null $currentFeatures
     * @return array
     */
    protected function detectSerpFeatureChanges(Keyword $keyword, ?array $currentFeatures): array
    {
        // Get previous SERP features
        $previousRanking = Ranking::where('keyword_id', $keyword->id)
            ->whereNotNull('serp_features')
            ->orderBy('checked_at', 'desc')
            ->skip(1) // Skip the current one we just saved
            ->first();

        $previousFeatures = [];
        if ($previousRanking && is_string($previousRanking->serp_features)) {
            $decoded = json_decode($previousRanking->serp_features, true);
            if (is_array($decoded)) {
                $previousFeatures = $decoded;
            }
        } elseif ($previousRanking && is_array($previousRanking->serp_features)) {
            $previousFeatures = $previousRanking->serp_features;
        }

        // Normalize feature arrays
        $currentTypes = $this->extractSerpFeatureTypes($currentFeatures);
        $previousTypes = $this->extractSerpFeatureTypes($previousFeatures);

        return [
            'gained' => array_values(array_diff($currentTypes, $previousTypes)),
            'lost' => array_values(array_diff($previousTypes, $currentTypes)),
        ];
    }

    /**
     * Extract SERP feature types from features array.
     *
     * @param array|null $features
     * @return array
     */
    protected function extractSerpFeatureTypes(?array $features): array
    {
        if (empty($features)) {
            return [];
        }

        // Handle different formats
        if (isset($features['types'])) {
            return (array) $features['types'];
        }

        // Assume it's a flat array of types
        return array_keys(array_filter($features, fn($v) => $v === true || (is_array($v) && !empty($v))));
    }

    /**
     * Detect and store SERP features for a keyword.
     *
     * This is the main integration point for TASK-7-13.
     * Feature detection is only run if:
     * 1. Site has enable_serp_features = true
     * 2. Required services are available
     *
     * @param Keyword $keyword
     * @param string $userDomain
     * @param array $result The SERP result from checkKeyword
     * @return void
     */
    protected function detectAndStoreSerpFeatures(Keyword $keyword, string $userDomain, array $result): void
    {
        // Check if feature detection is enabled for the site
        if (!$this->isFeatureDetectionEnabled($keyword)) {
            Log::debug('SERP feature detection skipped - not enabled for site', [
                'keyword_id' => $keyword->id,
                'site_id' => $keyword->site_id,
            ]);
            return;
        }

        // Check if required services are available
        if (!$this->featureDetector || !$this->featureRepository) {
            Log::debug('SERP feature detection skipped - services not available', [
                'keyword_id' => $keyword->id,
            ]);
            return;
        }

        try {
            $startTime = microtime(true);
            
            // Detect features from SERP result
            $features = $this->featureDetector->detectFeatures(
                $keyword->keyword,
                $userDomain ? $this->normalizeUrl($userDomain) : null,
                $result['serp_result'] ?? $result
            );

            // Store features in serp_features table
            $featureRecords = [];
            foreach ($features as $feature) {
                $featureRecords[] = [
                    'keyword_id' => $keyword->id,
                    'feature_type' => $feature['type'],
                    'is_present' => $feature['is_present'] ?? false,
                    'position' => $feature['position'] ?? null,
                    'url' => $feature['url'] ?? null,
                    'title' => $feature['title'] ?? null,
                    'snippet' => $feature['snippet'] ?? null,
                    'checked_at' => $feature['checked_at'] ?? now(),
                ];
            }

            $this->featureRepository->insertBatch($featureRecords);

            // Track feature changes
            $this->trackFeatureChanges($keyword, $features);

            $duration = round((microtime(true) - $startTime) * 1000, 2);
            
            Log::info('SERP feature detection completed', [
                'keyword_id' => $keyword->id,
                'features_detected' => count(array_filter($features, fn($f) => $f['is_present'] ?? false)),
                'duration_ms' => $duration,
            ]);

        } catch (\Throwable $e) {
            // Feature detection failure should NOT break ranking check
            Log::error('SERP feature detection failed', [
                'keyword_id' => $keyword->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Check if SERP feature detection is enabled for a keyword's site.
     *
     * @param Keyword $keyword
     * @return bool
     */
    protected function isFeatureDetectionEnabled(Keyword $keyword): bool
    {
        // No site = no feature detection
        if (!$keyword->site_id || !$keyword->site) {
            return false;
        }

        return $keyword->site->enable_serp_features ?? false;
    }

    /**
     * Track feature changes using the change tracker.
     *
     * @param Keyword $keyword
     * @param array $currentFeatures
     * @return void
     */
    protected function trackFeatureChanges(Keyword $keyword, array $currentFeatures): void
    {
        if (!$this->changeTracker) {
            return;
        }

        try {
            // Get previous features from serp_features table
            $previousFeatures = $this->getPreviousFeatures($keyword->id);

            // Track changes (only if not first check)
            if (!empty($previousFeatures)) {
                $changes = $this->changeTracker->trackChanges(
                    $keyword->id,
                    $currentFeatures,
                    $previousFeatures
                );

                if (!empty($changes)) {
                    Log::info('SERP feature changes detected', [
                        'keyword_id' => $keyword->id,
                        'changes' => count($changes),
                    ]);
                }
            }
        } catch (\Throwable $e) {
            Log::error('SERP feature change tracking failed', [
                'keyword_id' => $keyword->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get previous SERP features for a keyword.
     *
     * @param int $keywordId
     * @return array
     */
    protected function getPreviousFeatures(int $keywordId): array
    {
        $features = SerpFeature::where('keyword_id', $keywordId)
            ->where('checked_at', '<', now())
            ->orderBy('checked_at', 'desc')
            ->limit(count(SerpFeature::FEATURE_TYPES))
            ->get();

        if ($features->isEmpty()) {
            return [];
        }

        return $features->map(fn($f) => [
            'type' => $f->feature_type,
            'is_present' => $f->is_present,
            'url' => $f->url,
        ])->toArray();
    }

    /**
     * Get the latest position for a keyword.
     *
     * @param int $keywordId
     * @return int|null Position or null if no previous ranking
     */
    protected function getLatestPosition(int $keywordId): ?int
    {
        $latestRanking = Ranking::where('keyword_id', $keywordId)
            ->orderBy('checked_at', 'desc')
            ->first();

        return $latestRanking?->position;
    }

    /**
     * @param SERPResult|array<string, mixed> $result
     * @return array<string, mixed>
     */
    private function normalizeResult($result): array
    {
        if ($result instanceof SERPResult) {
            $results = array_map(function (SERPEntry $entry) {
                return [
                    'position' => $entry->getPosition(),
                    'url' => $entry->getUrl(),
                ];
            }, $result->getResults());

            $payload = [
                'keyword' => $result->getKeyword(),
                'results' => $results,
                'duration_ms' => $result->getDurationMs(),
            ];

            if (method_exists($result, 'getSerpFeatures')) {
                $serpFeatures = $result->getSerpFeatures();
                if ($serpFeatures !== null) {
                    $payload['serp_features'] = $serpFeatures;
                }
            }

            return $payload;
        }

        return $result;
    }

    /**
     * @param array<int, array<string, mixed>> $results
     * @return array{position: int|null, url: string|null}
     */
    private function findDomainMatch(array $results, ?string $domain): array
    {
        if (!$domain) {
            return ['position' => null, 'url' => null];
        }

        foreach ($results as $entry) {
            $url = $entry['url'] ?? null;
            $position = $entry['position'] ?? null;

            if (!$url || !$position) {
                continue;
            }

            $host = $this->sanitizeDomain($url);
            if ($this->domainMatches($domain, $host)) {
                return ['position' => (int) $position, 'url' => $url];
            }
        }

        return ['position' => null, 'url' => null];
    }

    private function sanitizeDomain(?string $domain): ?string
    {
        if (!$domain) {
            return null;
        }

        $domain = trim(strtolower($domain));
        if (str_contains($domain, '://')) {
            $domain = parse_url($domain, PHP_URL_HOST) ?: $domain;
        } elseif (str_contains($domain, '/')) {
            $domain = parse_url('https://' . $domain, PHP_URL_HOST) ?: $domain;
        }

        $domain = preg_replace('/[^a-z0-9\.-]/', '', $domain) ?? '';
        $domain = ltrim($domain, '.');
        $domain = rtrim($domain, '.');
        $domain = preg_replace('/^www\./', '', $domain) ?? $domain;

        return $domain ?: null;
    }

    private function normalizeUrl(string $domain): string
    {
        if (Str::startsWith($domain, ['http://', 'https://'])) {
            return $domain;
        }

        return 'https://' . $domain;
    }

    private function domainMatches(string $expected, ?string $actual): bool
    {
        if (!$actual) {
            return false;
        }

        if ($actual === $expected) {
            return true;
        }

        return str_ends_with($actual, '.' . $expected);
    }
}
