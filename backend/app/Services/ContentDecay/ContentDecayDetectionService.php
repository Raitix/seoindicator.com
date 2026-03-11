<?php

namespace App\Services\ContentDecay;

use App\Models\ContentDecayCandidate;
use App\Models\ContentDecayProject;
use App\Models\ContentDecaySignal;
use App\Models\GSCData;
use App\Models\Site;
use App\Services\ContentDecay\Contracts\ContentDecayDetectionServiceInterface;
use App\Services\ContentDecay\Contracts\ContentDecayReasonServiceInterface;
use App\Services\ContentDecay\Contracts\ContentDecayScoringServiceInterface;
use App\Services\ContentDecay\Contracts\ContentRefreshPlannerServiceInterface;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Content Decay Detection Service.
 *
 * Main orchestration service that gathers signals from multiple sources,
 * compares windows, and creates/updates decay candidates.
 *
 * @see TASK-36-6
 * @see SPEC-036
 */
class ContentDecayDetectionService implements ContentDecayDetectionServiceInterface
{
    /**
     * Default configuration values.
     */
    protected const DEFAULT_CONFIG = [
        'window_days' => 28,
        'min_impressions_threshold' => 100,
        'min_clicks_threshold' => 10,
        'refresh_cooldown_days' => 14,
        'low_signal_threshold_percent' => 5,
        'max_pages_per_run' => 1000,
    ];

    /**
     * Scoring service for calculating decay scores.
     */
    protected ContentDecayScoringServiceInterface $scoringService;

    /**
     * Reason service for assigning reason codes.
     */
    protected ContentDecayReasonServiceInterface $reasonService;

    /**
     * Planner service for generating recommendations.
     */
    protected ContentRefreshPlannerServiceInterface $plannerService;

    /**
     * Configuration values.
     */
    protected array $config;

    /**
     * Create a new ContentDecayDetectionService instance.
     */
    public function __construct(
        ContentDecayScoringServiceInterface $scoringService,
        ContentDecayReasonServiceInterface $reasonService,
        ContentRefreshPlannerServiceInterface $plannerService,
        array $config = []
    ) {
        $this->scoringService = $scoringService;
        $this->reasonService = $reasonService;
        $this->plannerService = $plannerService;
        $this->config = array_merge(self::DEFAULT_CONFIG, $config);
    }

    /**
     * Get the scoring service.
     */
    public function getScoringService(): ContentDecayScoringServiceInterface
    {
        return $this->scoringService;
    }

    /**
     * Get the reason service.
     */
    public function getReasonService(): ContentDecayReasonServiceInterface
    {
        return $this->reasonService;
    }

    /**
     * Get the planner service.
     */
    public function getPlannerService(): ContentRefreshPlannerServiceInterface
    {
        return $this->plannerService;
    }

    /**
     * Get the configuration.
     */
    public function getConfiguration(): array
    {
        return $this->config;
    }

    /**
     * {@inheritDoc}
     */
    public function detectForSite(Site $site): array
    {
        $startTime = microtime(true);

        $result = [
            'processed_pages' => 0,
            'eligible_pages' => 0,
            'candidates_created' => 0,
            'candidates_updated' => 0,
            'candidates_archived' => 0,
            'errors' => 0,
            'duration_ms' => 0,
            'pages' => [],
        ];

        try {
            // Load or create project for site
            $project = $this->getOrCreateProject($site);

            // Check if detection is enabled
            if (!$project->detection_enabled) {
                Log::info('Content decay detection disabled for site', [
                    'site_id' => $site->id,
                    'project_id' => $project->id,
                ]);

                $result['duration_ms'] = $this->calculateDuration($startTime);
                return $result;
            }

            // Run detection for the project
            $projectResult = $this->detectForProject($project);

            // Merge results
            $result['processed_pages'] = $projectResult['processed_pages'];
            $result['eligible_pages'] = $projectResult['eligible_pages'];
            $result['candidates_created'] = $projectResult['candidates_created'];
            $result['candidates_updated'] = $projectResult['candidates_updated'];
            $result['candidates_archived'] = $projectResult['candidates_archived'];
            $result['errors'] = $projectResult['errors'];
            $result['pages'] = $projectResult['pages'];

        } catch (\Throwable $e) {
            Log::error('Content decay detection failed for site', [
                'site_id' => $site->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $result['errors'] = 1;
            $result['error'] = $e->getMessage();
        }

        $result['duration_ms'] = $this->calculateDuration($startTime);

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function detectForProject(ContentDecayProject $project): array
    {
        $startTime = microtime(true);

        $result = [
            'project_id' => $project->id,
            'processed_pages' => 0,
            'eligible_pages' => 0,
            'candidates_created' => 0,
            'candidates_updated' => 0,
            'candidates_archived' => 0,
            'errors' => 0,
            'duration_ms' => 0,
            'pages' => [],
        ];

        // Check if detection is enabled
        if (!$project->detection_enabled) {
            Log::info('Content decay detection disabled for project', [
                'project_id' => $project->id,
            ]);

            $result['duration_ms'] = $this->calculateDuration($startTime);
            return $result;
        }

        try {
            // Get window configuration
            $windowDays = $project->window_days ?? $this->config['window_days'];

            // Calculate window dates
            $windowDates = $this->calculateWindowDates($windowDays);

            // Fetch GSC data aggregated by page
            $gscPages = $this->fetchGscDataByPage($project, $windowDates);

            $result['processed_pages'] = count($gscPages);

            // Process each page
            foreach ($gscPages as $pageUrl => $pageData) {
                try {
                    // Check eligibility
                    if (!$this->isPageEligible($pageData)) {
                        continue;
                    }

                    $result['eligible_pages']++;

                    // Check cooldown for existing candidate
                    $existingCandidate = $this->findExistingCandidate($project, $pageUrl);
                    if ($existingCandidate && $this->isCooldownActive($existingCandidate)) {
                        Log::info('Skipping page due to cooldown', [
                            'page_url' => $pageUrl,
                            'candidate_id' => $existingCandidate->id,
                        ]);
                        continue;
                    }

                    // Gather signals (use pre-fetched GSC data)
                    $signals = $this->gatherSignals($pageUrl, $project, $pageData);

                    // Compare windows
                    $comparison = $this->compareWindows($signals);

                    // Check low signal threshold
                    if ($this->isBelowLowSignalThreshold($comparison)) {
                        continue;
                    }

                    // Calculate decay score
                    $scoringInput = $this->prepareScoringInput($signals, $comparison);
                    $scoreResult = $this->scoringService->calculateDecayScore($scoringInput);

                    // Skip if decay score is zero
                    if ($scoreResult['decay_score'] <= 0) {
                        continue;
                    }

                    // Assign reason codes
                    $reasonInput = $this->prepareReasonInput($signals, $comparison);
                    $reasonResult = $this->reasonService->assignReasonCodes($reasonInput);

                    // Create/update candidate
                    $detectionData = [
                        'project_id' => $project->id,
                        'page_url' => $pageUrl,
                        'decay_score' => $scoreResult['decay_score'],
                        'severity' => $scoreResult['severity'],
                        'detection_confidence' => $scoreResult['detection_confidence'],
                        'reason_codes' => $reasonResult['reason_codes'],
                        'signal_sources' => $this->extractSignalSources($signals),
                        'evidence_json' => $reasonResult['evidence'],
                    ];

                    $candidate = $this->upsertCandidate($detectionData);

                    if ($candidate->wasRecentlyCreated) {
                        $result['candidates_created']++;
                    } else {
                        $result['candidates_updated']++;
                    }

                    // Generate recommendation
                    $recommendations = $this->plannerService->generateRecommendations($candidate);

                    $result['pages'][] = [
                        'page_url' => $pageUrl,
                        'decay_score' => $scoreResult['decay_score'],
                        'severity' => $scoreResult['severity'],
                        'status' => $candidate->status,
                        'reason_codes' => $reasonResult['reason_codes'],
                        'recommendation' => $recommendations['primary_recommendation'],
                    ];

                } catch (\Throwable $e) {
                    Log::warning('Failed to process page for decay detection', [
                        'page_url' => $pageUrl,
                        'error' => $e->getMessage(),
                    ]);

                    $result['errors']++;
                }

                // Enforce max pages limit
                if ($result['eligible_pages'] >= $this->config['max_pages_per_run']) {
                    break;
                }
            }

            // Archive candidates that no longer meet thresholds
            $archived = $this->archiveRecoveredCandidates($project, array_keys($gscPages));
            $result['candidates_archived'] = $archived;

            // Update last scanned timestamp
            $project->update(['last_scanned_at' => now()]);

        } catch (\Throwable $e) {
            Log::error('Content decay detection failed for project', [
                'project_id' => $project->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $result['errors'] = 1;
            $result['error'] = $e->getMessage();
        }

        $result['duration_ms'] = $this->calculateDuration($startTime);

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function gatherSignals(string $pageUrl, ContentDecayProject $project, ?array $preFetchedGscData = null): array
    {
        $windowDays = $project->window_days ?? $this->config['window_days'];
        $windowDates = $this->calculateWindowDates($windowDays);

        $signals = [
            'page_url' => $pageUrl,
            'gathered_at' => now()->toIso8601String(),
            'window' => $windowDates,
            'gsc' => null,
            'rankings' => null,
            'audit' => null,
            'annotations' => null,
            'ai_visibility' => null,
        ];

        // GSC signals (required)
        $signals['gsc'] = $preFetchedGscData ?? $this->fetchGscDataForPage($pageUrl, $project, $windowDates);

        // Rankings signals (optional - graceful degradation)
        try {
            $signals['rankings'] = $this->fetchRankingsData($pageUrl, $project, $windowDates);
        } catch (\Throwable $e) {
            Log::debug('Rankings data not available for page', [
                'page_url' => $pageUrl,
                'error' => $e->getMessage(),
            ]);
        }

        // Audit signals (optional - graceful degradation)
        try {
            $signals['audit'] = $this->fetchAuditData($pageUrl, $project);
        } catch (\Throwable $e) {
            Log::debug('Audit data not available for page', [
                'page_url' => $pageUrl,
                'error' => $e->getMessage(),
            ]);
        }

        // Annotations (optional - graceful degradation)
        try {
            $signals['annotations'] = $this->fetchAnnotations($pageUrl, $project);
        } catch (\Throwable $e) {
            Log::debug('Annotations not available for page', [
                'page_url' => $pageUrl,
                'error' => $e->getMessage(),
            ]);
        }

        // AI visibility (optional - graceful degradation)
        try {
            $signals['ai_visibility'] = $this->fetchAiVisibility($pageUrl, $project, $windowDates);
        } catch (\Throwable $e) {
            Log::debug('AI visibility data not available for page', [
                'page_url' => $pageUrl,
                'error' => $e->getMessage(),
            ]);
        }

        return $signals;
    }

    /**
     * {@inheritDoc}
     */
    public function compareWindows(array $signals): array
    {
        $comparison = [
            'deltas' => [],
            'percentages' => [],
            'direction' => [],
            'compared_at' => now()->toIso8601String(),
        ];

        $gsc = $signals['gsc'] ?? null;

        if (!$gsc) {
            return $comparison;
        }

        $previous = $gsc['previous'] ?? [];
        $current = $gsc['current'] ?? [];

        // Calculate deltas and percentages for each metric
        $metrics = [
            'clicks' => ['previous' => $previous['clicks'] ?? 0, 'current' => $current['clicks'] ?? 0],
            'impressions' => ['previous' => $previous['impressions'] ?? 0, 'current' => $current['impressions'] ?? 0],
            'ctr' => ['previous' => $previous['ctr'] ?? 0, 'current' => $current['ctr'] ?? 0],
            'position' => ['previous' => $previous['position'] ?? 0, 'current' => $current['position'] ?? 0],
        ];

        foreach ($metrics as $metric => $values) {
            $prev = $values['previous'];
            $curr = $values['current'];

            // Delta: current - previous
            $delta = $curr - $prev;

            // Position is reversed: higher number = worse
            if ($metric === 'position') {
                // Already correct: positive delta means worse position
            }

            $comparison['deltas'][$metric] = round($delta, 4);

            // Percentage change
            if ($prev > 0) {
                $percentChange = ($delta / $prev) * 100;
                $comparison['percentages'][$metric] = round($percentChange, 2);
            } else {
                // Handle zero previous - if current > 0, it's growth
                $comparison['percentages'][$metric] = $curr > 0 ? 100.0 : 0.0;
            }

            // Direction
            $threshold = 0.001; // Small threshold for "stable"
            if (abs($delta) < $threshold) {
                $comparison['direction'][$metric] = 'stable';
            } elseif ($metric === 'position') {
                // Position: positive delta = worse = down
                $comparison['direction'][$metric] = $delta > 0 ? 'down' : 'up';
            } else {
                // Other metrics: positive delta = growth = up
                $comparison['direction'][$metric] = $delta > 0 ? 'up' : 'down';
            }
        }

        // Add rankings comparison if available
        $rankings = $signals['rankings'] ?? null;
        if ($rankings && isset($rankings['previous'], $rankings['current'])) {
            $prevVisibility = $rankings['previous']['visibility'] ?? $rankings['previous']['visibility_score'] ?? null;
            $currVisibility = $rankings['current']['visibility'] ?? $rankings['current']['visibility_score'] ?? null;

            if ($prevVisibility !== null && $currVisibility !== null) {
                $comparison['deltas']['visibility'] = round($currVisibility - $prevVisibility, 2);
                $comparison['percentages']['visibility'] = $prevVisibility > 0
                    ? round((($currVisibility - $prevVisibility) / $prevVisibility) * 100, 2)
                    : ($currVisibility > 0 ? 100.0 : 0.0);
                $comparison['direction']['visibility'] = $currVisibility > $prevVisibility ? 'up' : ($currVisibility < $prevVisibility ? 'down' : 'stable');
            }
        }

        // Add AI visibility comparison if available
        $aiVisibility = $signals['ai_visibility'] ?? null;
        if ($aiVisibility && isset($aiVisibility['previous'], $aiVisibility['current'])) {
            $prevRate = $aiVisibility['previous']['mention_rate'] ?? $aiVisibility['previous']['rate'] ?? null;
            $currRate = $aiVisibility['current']['mention_rate'] ?? $aiVisibility['current']['rate'] ?? null;

            if ($prevRate !== null && $currRate !== null) {
                $comparison['deltas']['ai_mention_rate'] = round($currRate - $prevRate, 4);
                $comparison['percentages']['ai_mention_rate'] = $prevRate > 0
                    ? round((($currRate - $prevRate) / $prevRate) * 100, 2)
                    : ($currRate > 0 ? 100.0 : 0.0);
                $comparison['direction']['ai_mention_rate'] = $currRate > $prevRate ? 'up' : ($currRate < $prevRate ? 'down' : 'stable');
            }
        }

        return $comparison;
    }

    /**
     * {@inheritDoc}
     */
    public function upsertCandidate(array $detectionData): ?ContentDecayCandidate
    {
        $projectId = $detectionData['project_id'] ?? null;
        $pageUrl = $detectionData['page_url'] ?? null;

        if (!$projectId || !$pageUrl) {
            return null;
        }

        $pageUrlHash = hash('sha256', $pageUrl);

        return DB::transaction(function () use ($projectId, $pageUrl, $pageUrlHash, $detectionData) {
            // Find existing candidate
            $candidate = ContentDecayCandidate::where('project_id', $projectId)
                ->where('page_url_hash', $pageUrlHash)
                ->first();

            $wasRecentlyCreated = false;

            if ($candidate) {
                // Update existing candidate - preserve status
                $candidate->update([
                    'decay_score' => $detectionData['decay_score'] ?? $candidate->decay_score,
                    'severity' => $detectionData['severity'] ?? $candidate->severity,
                    'detection_confidence' => $detectionData['detection_confidence'] ?? $candidate->detection_confidence,
                    'primary_reason_code' => Arr::first($detectionData['reason_codes'] ?? []),
                    'reason_codes' => $detectionData['reason_codes'] ?? $candidate->reason_codes,
                    'signal_sources' => $detectionData['signal_sources'] ?? $candidate->signal_sources,
                    'evidence_json' => $detectionData['evidence_json'] ?? $candidate->evidence_json,
                    'last_detected_at' => now(),
                ]);
            } else {
                // Create new candidate
                $candidate = ContentDecayCandidate::create([
                    'project_id' => $projectId,
                    'page_url' => $pageUrl,
                    'page_url_hash' => $pageUrlHash,
                    'page_type' => $this->derivePageType($pageUrl),
                    'decay_score' => $detectionData['decay_score'] ?? 0,
                    'severity' => $detectionData['severity'] ?? 'low',
                    'detection_confidence' => $detectionData['detection_confidence'] ?? 0.85,
                    'primary_reason_code' => Arr::first($detectionData['reason_codes'] ?? []) ?? 'unknown',
                    'reason_codes' => $detectionData['reason_codes'] ?? [],
                    'signal_sources' => $detectionData['signal_sources'] ?? [],
                    'evidence_json' => $detectionData['evidence_json'] ?? [],
                    'status' => 'backlog',
                    'first_detected_at' => now(),
                    'last_detected_at' => now(),
                ]);

                $wasRecentlyCreated = true;
            }

            // Mark the candidate as recently created for tracking
            $candidate->wasRecentlyCreated = $wasRecentlyCreated;

            return $candidate;
        });
    }

    /**
     * {@inheritDoc}
     */
    public function isPageEligible(array $gscData, array $options = []): bool
    {
        $previous = $gscData['previous'] ?? null;
        $current = $gscData['current'] ?? null;

        // Must have data for both windows
        if (!$previous || !$current) {
            return false;
        }

        // Get thresholds from options or use defaults
        $minImpressions = $options['min_impressions'] ?? $this->config['min_impressions_threshold'];
        $minClicks = $options['min_clicks'] ?? $this->config['min_clicks_threshold'];

        $previousImpressions = $previous['impressions'] ?? 0;
        $previousClicks = $previous['clicks'] ?? 0;

        // Must meet at least one threshold
        if ($previousImpressions >= $minImpressions || $previousClicks >= $minClicks) {
            return true;
        }

        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function isCooldownActive(ContentDecayCandidate $candidate): bool
    {
        // Check if recently refreshed
        if ($candidate->status === 'refreshed' && $candidate->refreshed_at) {
            $cooldownDays = $this->config['refresh_cooldown_days'];
            $cooldownEnds = $candidate->refreshed_at->addDays($cooldownDays);

            if (now()->lt($cooldownEnds)) {
                return true;
            }
        }

        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function isBelowLowSignalThreshold(array $comparison): bool
    {
        $threshold = $this->config['low_signal_threshold_percent'];

        $clicksPercent = abs($comparison['percentages']['clicks'] ?? 0);
        $impressionsPercent = abs($comparison['percentages']['impressions'] ?? 0);

        // If both clicks and impressions changes are below threshold, it's low signal
        if ($clicksPercent < $threshold && $impressionsPercent < $threshold) {
            return true;
        }

        return false;
    }

    // =========================================================================
    // Protected Helper Methods
    // =========================================================================

    /**
     * Get or create a content decay project for a site.
     */
    protected function getOrCreateProject(Site $site): ContentDecayProject
    {
        return ContentDecayProject::firstOrCreate(
            ['site_id' => $site->id],
            [
                'user_id' => $site->user_id,
                'detection_enabled' => true,
                'window_days' => $this->config['window_days'],
            ]
        );
    }

    /**
     * Calculate window dates for comparison.
     */
    protected function calculateWindowDates(int $windowDays): array
    {
        // Current window: last N complete days (ending yesterday)
        $currentEnd = Carbon::yesterday()->endOfDay();
        $currentStart = Carbon::yesterday()->subDays($windowDays - 1)->startOfDay();

        // Previous window: N days before current window
        $previousEnd = $currentStart->copy()->subDay()->endOfDay();
        $previousStart = $previousEnd->copy()->subDays($windowDays - 1)->startOfDay();

        return [
            'previous_start' => $previousStart->toDateString(),
            'previous_end' => $previousEnd->toDateString(),
            'current_start' => $currentStart->toDateString(),
            'current_end' => $currentEnd->toDateString(),
        ];
    }

    /**
     * Fetch GSC data aggregated by page for all pages in the project.
     */
    protected function fetchGscDataByPage(ContentDecayProject $project, array $windowDates): array
    {
        $siteId = $project->site_id;

        $previousStart = Carbon::parse($windowDates['previous_start']);
        $previousEnd = Carbon::parse($windowDates['previous_end']);
        $currentStart = Carbon::parse($windowDates['current_start']);
        $currentEnd = Carbon::parse($windowDates['current_end']);

        // Query previous window data
        $previousData = GSCData::where('site_id', $siteId)
            ->whereBetween('data_date', [$previousStart, $previousEnd])
            ->selectRaw('page, SUM(clicks) as clicks, SUM(impressions) as impressions, 
                         AVG(ctr) as ctr, AVG(avg_position) as position')
            ->whereNotNull('page')
            ->groupBy('page')
            ->get()
            ->keyBy('page');

        // Query current window data
        $currentData = GSCData::where('site_id', $siteId)
            ->whereBetween('data_date', [$currentStart, $currentEnd])
            ->selectRaw('page, SUM(clicks) as clicks, SUM(impressions) as impressions, 
                         AVG(ctr) as ctr, AVG(avg_position) as position')
            ->whereNotNull('page')
            ->groupBy('page')
            ->get()
            ->keyBy('page');

        // Merge into page-level data
        $pages = [];

        foreach ($previousData as $page => $prev) {
            $curr = $currentData->get($page);

            $pages[$page] = [
                'previous' => [
                    'clicks' => (int) $prev->clicks,
                    'impressions' => (int) $prev->impressions,
                    'ctr' => (float) $prev->ctr,
                    'position' => (float) $prev->position,
                ],
                'current' => $curr ? [
                    'clicks' => (int) $curr->clicks,
                    'impressions' => (int) $curr->impressions,
                    'ctr' => (float) $curr->ctr,
                    'position' => (float) $curr->position,
                ] : null,
            ];
        }

        // Add pages that only have current data (new pages)
        foreach ($currentData as $page => $curr) {
            if (!isset($pages[$page])) {
                $pages[$page] = [
                    'previous' => null,
                    'current' => [
                        'clicks' => (int) $curr->clicks,
                        'impressions' => (int) $curr->impressions,
                        'ctr' => (float) $curr->ctr,
                        'position' => (float) $curr->position,
                    ],
                ];
            }
        }

        return $pages;
    }

    /**
     * Fetch GSC data for a single page.
     */
    protected function fetchGscDataForPage(string $pageUrl, ContentDecayProject $project, array $windowDates): ?array
    {
        $siteId = $project->site_id;

        $previousStart = Carbon::parse($windowDates['previous_start']);
        $previousEnd = Carbon::parse($windowDates['previous_end']);
        $currentStart = Carbon::parse($windowDates['current_start']);
        $currentEnd = Carbon::parse($windowDates['current_end']);

        $previous = GSCData::where('site_id', $siteId)
            ->where('page', $pageUrl)
            ->whereBetween('data_date', [$previousStart, $previousEnd])
            ->selectRaw('SUM(clicks) as clicks, SUM(impressions) as impressions, 
                         AVG(ctr) as ctr, AVG(avg_position) as position')
            ->first();

        $current = GSCData::where('site_id', $siteId)
            ->where('page', $pageUrl)
            ->whereBetween('data_date', [$currentStart, $currentEnd])
            ->selectRaw('SUM(clicks) as clicks, SUM(impressions) as impressions, 
                         AVG(ctr) as ctr, AVG(avg_position) as position')
            ->first();

        if (!$previous && !$current) {
            return null;
        }

        return [
            'previous' => $previous ? [
                'clicks' => (int) $previous->clicks,
                'impressions' => (int) $previous->impressions,
                'ctr' => (float) $previous->ctr,
                'position' => (float) $previous->position,
            ] : null,
            'current' => $current ? [
                'clicks' => (int) $current->clicks,
                'impressions' => (int) $current->impressions,
                'ctr' => (float) $current->ctr,
                'position' => (float) $current->position,
            ] : null,
        ];
    }

    /**
     * Fetch rankings data for a page (optional).
     */
    protected function fetchRankingsData(string $pageUrl, ContentDecayProject $project, array $windowDates): ?array
    {
        // TODO: Integrate with rank tracking service when available
        // For now, return null to allow graceful degradation
        return null;
    }

    /**
     * Fetch audit data for a page (optional).
     */
    protected function fetchAuditData(string $pageUrl, ContentDecayProject $project): ?array
    {
        // TODO: Integrate with site audit service when available
        // For now, return null to allow graceful degradation
        return null;
    }

    /**
     * Fetch annotations for a page (optional).
     */
    protected function fetchAnnotations(string $pageUrl, ContentDecayProject $project): ?array
    {
        // TODO: Integrate with annotations when available
        return null;
    }

    /**
     * Fetch AI visibility data for a page (optional).
     */
    protected function fetchAiVisibility(string $pageUrl, ContentDecayProject $project, array $windowDates): ?array
    {
        // TODO: Integrate with AI visibility service when available
        return null;
    }

    /**
     * Find an existing candidate for a project and page.
     */
    protected function findExistingCandidate(ContentDecayProject $project, string $pageUrl): ?ContentDecayCandidate
    {
        $pageUrlHash = hash('sha256', $pageUrl);

        return ContentDecayCandidate::where('project_id', $project->id)
            ->where('page_url_hash', $pageUrlHash)
            ->first();
    }

    /**
     * Prepare scoring service input from signals and comparison.
     */
    protected function prepareScoringInput(array $signals, array $comparison): array
    {
        $gsc = $signals['gsc'] ?? [];

        return [
            'click_loss' => $this->scoringService->normalizeClickLoss(
                $gsc['previous']['clicks'] ?? 0,
                $gsc['current']['clicks'] ?? 0
            ),
            'impression_loss' => $this->scoringService->normalizeImpressionLoss(
                $gsc['previous']['impressions'] ?? 0,
                $gsc['current']['impressions'] ?? 0
            ),
            'ctr_drop' => $this->scoringService->normalizeCtrDrop(
                ($gsc['previous']['ctr'] ?? 0) * 100, // Convert to percentage
                ($gsc['current']['ctr'] ?? 0) * 100
            ),
            'position_decline' => $this->scoringService->normalizePositionDecline(
                (int) ($gsc['previous']['position'] ?? 1),
                (int) ($gsc['current']['position'] ?? 1)
            ),
            'ranking_decline' => null, // Optional
            'audit_pressure' => null, // Optional
            'staleness' => null, // Optional
        ];
    }

    /**
     * Prepare reason service input from signals and comparison.
     */
    protected function prepareReasonInput(array $signals, array $comparison): array
    {
        $gsc = $signals['gsc'] ?? [];

        return [
            'clicks' => [
                'previous' => $gsc['previous']['clicks'] ?? null,
                'current' => $gsc['current']['clicks'] ?? null,
            ],
            'impressions' => [
                'previous' => $gsc['previous']['impressions'] ?? null,
                'current' => $gsc['current']['impressions'] ?? null,
            ],
            'ctr' => [
                'previous' => isset($gsc['previous']['ctr']) ? $gsc['previous']['ctr'] * 100 : null,
                'current' => isset($gsc['current']['ctr']) ? $gsc['current']['ctr'] * 100 : null,
            ],
            'avg_position' => [
                'previous' => $gsc['previous']['position'] ?? null,
                'current' => $gsc['current']['position'] ?? null,
            ],
            'tracked_rank_visibility' => $signals['rankings'] ?? null,
            'ai_visibility' => $signals['ai_visibility'] ?? null,
            'unresolved_audit_issues' => $signals['audit']['unresolved_issues'] ?? null,
            'content_age_days' => $signals['audit']['content_age_days'] ?? null,
            'last_annotation_at' => $signals['annotations']['last_updated_at'] ?? null,
            'internal_links' => $signals['audit']['internal_links'] ?? null,
            'window' => $signals['window'] ?? null,
            'page_title' => $signals['gsc']['page_title'] ?? null,
            'page_type' => $this->derivePageType($signals['page_url'] ?? ''),
        ];
    }

    /**
     * Extract signal sources from signals array.
     */
    protected function extractSignalSources(array $signals): array
    {
        $sources = [];

        if (!empty($signals['gsc'])) {
            $sources[] = 'gsc';
        }
        if (!empty($signals['rankings'])) {
            $sources[] = 'rankings';
        }
        if (!empty($signals['audit'])) {
            $sources[] = 'audit';
        }
        if (!empty($signals['annotations'])) {
            $sources[] = 'annotations';
        }
        if (!empty($signals['ai_visibility'])) {
            $sources[] = 'ai_visibility';
        }

        return $sources;
    }

    /**
     * Derive page type from URL.
     */
    protected function derivePageType(string $pageUrl): string
    {
        $path = parse_url($pageUrl, PHP_URL_PATH) ?? '';

        if (preg_match('#/blog/#i', $path) || preg_match('#/article/#i', $path)) {
            return 'blog';
        }
        if (preg_match('#/product#i', $path) || preg_match('#/shop/#i', $path)) {
            return 'product';
        }
        if (preg_match('#/tool#i', $path) || preg_match('#/calculator#i', $path)) {
            return 'tool';
        }
        if (preg_match('#^/$#', $path) || $path === '') {
            return 'landing';
        }

        return 'page';
    }

    /**
     * Archive candidates that no longer meet decay thresholds.
     */
    protected function archiveRecoveredCandidates(ContentDecayProject $project, array $processedUrls): int
    {
        $archived = 0;

        // Find candidates that were not in this detection run
        $candidatesToCheck = ContentDecayCandidate::where('project_id', $project->id)
            ->whereNotIn('page_url', $processedUrls)
            ->whereNotIn('status', ['ignored', 'recovered'])
            ->get();

        foreach ($candidatesToCheck as $candidate) {
            // Mark as recovered if not detected in this run
            $candidate->update([
                'status' => 'recovered',
                'recovered_at' => now(),
            ]);

            $archived++;
        }

        return $archived;
    }

    /**
     * Calculate duration in milliseconds.
     */
    protected function calculateDuration(float $startTime): int
    {
        return (int) ((microtime(true) - $startTime) * 1000);
    }
}
