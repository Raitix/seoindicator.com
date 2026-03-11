<?php

namespace App\Services\ContentDecay;

use App\Models\ContentDecayCandidate;
use App\Models\ContentDecayProject;
use App\Models\ContentDecaySignal;
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
 * Main orchestration service that:
 * - Gathers signals from multiple sources (GSC, rankings, audit, annotations, AI visibility)
 * - Compares windows (current vs previous)
 * - Creates/updates decay candidates
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
        'low_signal_threshold' => 5, // Percentage change threshold for low signal
        'max_pages_per_run' => 1000,
    ];

    /**
     * @var ContentDecayScoringServiceInterface
     */
    protected ContentDecayScoringServiceInterface $scoringService;

    /**
     * @var ContentDecayReasonServiceInterface
     */
    protected ContentDecayReasonServiceInterface $reasonService;

    /**
     * @var ContentRefreshPlannerServiceInterface
     */
    protected ContentRefreshPlannerServiceInterface $plannerService;

    /**
     * Create a new ContentDecayDetectionService instance.
     */
    public function __construct(
        ContentDecayScoringServiceInterface $scoringService,
        ContentDecayReasonServiceInterface $reasonService,
        ContentRefreshPlannerServiceInterface $plannerService
    ) {
        $this->scoringService = $scoringService;
        $this->reasonService = $reasonService;
        $this->plannerService = $plannerService;
    }

    // =========================================================================
    // Public API Methods
    // =========================================================================

    /**
     * {@inheritDoc}
     */
    public function detectForSite(Site $site): array
    {
        $startTime = microtime(true);

        try {
            // Load or create project for the site
            $project = $this->getOrCreateProject($site);

            // Check if detection is enabled
            if (!$project->isDetectionEnabled()) {
                return $this->emptyResult(0, 'Detection disabled for project');
            }

            return $this->detectForProject($project);

        } catch (\Throwable $e) {
            Log::error('Content decay detection failed for site', [
                'site_id' => $site->id,
                'error' => $e->getMessage(),
            ]);

            return $this->emptyResult(0, $e->getMessage());
        }
    }

    /**
     * {@inheritDoc}
     */
    public function detectForProject(ContentDecayProject $project): array
    {
        $startTime = microtime(true);

        try {
            // Check if detection is enabled
            if (!$project->isDetectionEnabled()) {
                return $this->emptyResult($project->id, 'Detection disabled for project');
            }

            // Get window configuration
            $windowDays = $project->getWindowDays();
            $windowDates = $this->calculateWindowDates($windowDays);

            // Fetch GSC data for the site
            $gscData = $this->fetchGscData($project->site, $windowDates);

            if (empty($gscData)) {
                return $this->emptyResult($project->id, 'No GSC data available');
            }

            // Process each page
            $results = $this->processPages($gscData, $project, $windowDates);

            // Update project last scanned timestamp
            $project->update(['last_scanned_at' => now()]);

            // Calculate duration
            $durationMs = (int) ((microtime(true) - $startTime) * 1000);

            return [
                'project_id' => $project->id,
                'processed_pages' => $results['processed'],
                'eligible_pages' => $results['eligible'],
                'candidates_created' => $results['created'],
                'candidates_updated' => $results['updated'],
                'candidates_archived' => $results['archived'],
                'errors' => $results['errors'],
                'duration_ms' => $durationMs,
                'pages' => $results['pages'],
            ];

        } catch (\Throwable $e) {
            Log::error('Content decay detection failed for project', [
                'project_id' => $project->id,
                'error' => $e->getMessage(),
            ]);

            $durationMs = (int) ((microtime(true) - $startTime) * 1000);

            return [
                'project_id' => $project->id,
                'processed_pages' => 0,
                'eligible_pages' => 0,
                'candidates_created' => 0,
                'candidates_updated' => 0,
                'candidates_archived' => 0,
                'errors' => 1,
                'duration_ms' => $durationMs,
                'pages' => [],
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * {@inheritDoc}
     */
    public function gatherSignals(string $pageUrl, ContentDecayProject $project, ?array $preFetchedGscData = null): array
    {
        $windowDays = $project->getWindowDays();
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
        if ($preFetchedGscData !== null) {
            $signals['gsc'] = $preFetchedGscData;
        } else {
            $signals['gsc'] = $this->fetchGscSignalsForPage($pageUrl, $project->site, $windowDates);
        }

        // Rankings signals (optional - graceful degradation)
        $signals['rankings'] = $this->fetchRankingsSignalsForPage($pageUrl, $project);

        // Audit signals (optional - graceful degradation)
        $signals['audit'] = $this->fetchAuditSignalsForPage($pageUrl, $project);

        // Annotations (optional - graceful degradation)
        $signals['annotations'] = $this->fetchAnnotationSignalsForPage($pageUrl, $project);

        // AI visibility (optional - graceful degradation)
        $signals['ai_visibility'] = $this->fetchAiVisibilitySignalsForPage($pageUrl, $project, $windowDates);

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

        if ($gsc === null) {
            return $comparison;
        }

        $previous = $gsc['previous'] ?? [];
        $current = $gsc['current'] ?? [];

        // Clicks
        $this->compareMetric($comparison, 'clicks', $previous['clicks'] ?? 0, $current['clicks'] ?? 0);

        // Impressions
        $this->compareMetric($comparison, 'impressions', $previous['impressions'] ?? 0, $current['impressions'] ?? 0);

        // CTR
        $this->compareMetric($comparison, 'ctr', $previous['ctr'] ?? 0, $current['ctr'] ?? 0);

        // Position (reversed - higher number = worse)
        $this->comparePosition($comparison, $previous['position'] ?? null, $current['position'] ?? null);

        // Rankings visibility (optional)
        $rankings = $signals['rankings'] ?? null;
        if ($rankings !== null) {
            $this->compareMetric($comparison, 'rank_visibility', 
                $rankings['previous']['visibility'] ?? null, 
                $rankings['current']['visibility'] ?? null
            );
        }

        // AI visibility (optional)
        $aiVisibility = $signals['ai_visibility'] ?? null;
        if ($aiVisibility !== null) {
            $this->compareMetric($comparison, 'ai_visibility',
                $aiVisibility['previous']['mention_rate'] ?? null,
                $aiVisibility['current']['mention_rate'] ?? null
            );
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

        if ($projectId === null || $pageUrl === null) {
            return null;
        }

        $pageUrlHash = hash('sha256', $pageUrl);

        // Sanitize evidence_json to prevent XSS
        $evidenceJson = $detectionData['evidence_json'] ?? null;
        if ($evidenceJson !== null) {
            $evidenceJson = $this->sanitizeEvidence($evidenceJson);
        }

        // Find existing candidate by project + URL hash
        $candidate = ContentDecayCandidate::where('project_id', $projectId)
            ->where('page_url_hash', $pageUrlHash)
            ->first();

        try {
            return DB::transaction(function () use ($candidate, $detectionData, $pageUrl, $pageUrlHash, $evidenceJson, $projectId) {
                if ($candidate === null) {
                    // Create new candidate
                    $candidate = ContentDecayCandidate::create([
                        'project_id' => $projectId,
                        'page_url' => $pageUrl,
                        'page_url_hash' => $pageUrlHash,
                        'page_title' => $detectionData['page_title'] ?? null,
                        'page_type' => $detectionData['page_type'] ?? $this->derivePageType($pageUrl),
                        'severity' => $detectionData['severity'] ?? 'medium',
                        'decay_score' => $detectionData['decay_score'] ?? 0,
                        'detection_confidence' => $detectionData['detection_confidence'] ?? 0.85,
                        'lost_click_estimate' => $detectionData['lost_click_estimate'] ?? null,
                        'recovery_upside_estimate' => $detectionData['recovery_upside_estimate'] ?? null,
                        'primary_reason_code' => $detectionData['primary_reason_code'] ?? null,
                        'reason_codes' => $detectionData['reason_codes'] ?? [],
                        'signal_sources' => $detectionData['signal_sources'] ?? ['gsc'],
                        'evidence_json' => $evidenceJson,
                        'status' => 'backlog',
                        'first_detected_at' => now(),
                        'last_detected_at' => now(),
                    ]);
                } else {
                    // Update existing candidate (preserve status)
                    $updateData = [
                        'page_title' => $detectionData['page_title'] ?? $candidate->page_title,
                        'severity' => $detectionData['severity'] ?? $candidate->severity,
                        'decay_score' => $detectionData['decay_score'] ?? $candidate->decay_score,
                        'detection_confidence' => $detectionData['detection_confidence'] ?? $candidate->detection_confidence,
                        'lost_click_estimate' => $detectionData['lost_click_estimate'] ?? $candidate->lost_click_estimate,
                        'recovery_upside_estimate' => $detectionData['recovery_upside_estimate'] ?? $candidate->recovery_upside_estimate,
                        'primary_reason_code' => $detectionData['primary_reason_code'] ?? $candidate->primary_reason_code,
                        'reason_codes' => $detectionData['reason_codes'] ?? $candidate->reason_codes,
                        'signal_sources' => $detectionData['signal_sources'] ?? $candidate->signal_sources,
                        'evidence_json' => $evidenceJson ?? $candidate->evidence_json,
                        'last_detected_at' => now(),
                    ];

                    // Reset refreshed_at if decay is detected again
                    if ($candidate->status === 'refreshed') {
                        $updateData['refreshed_at'] = null;
                    }

                    $candidate->update($updateData);
                }

                return $candidate;
            });
        } catch (\Throwable $e) {
            Log::error('Failed to upsert decay candidate', [
                'page_url' => $pageUrl,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function isPageEligible(array $gscData, array $options = []): bool
    {
        // Both windows must have GSC data
        if (!isset($gscData['previous']) || !isset($gscData['current'])) {
            return false;
        }

        $previous = $gscData['previous'];
        $minImpressions = $options['min_impressions_threshold'] ?? self::DEFAULT_CONFIG['min_impressions_threshold'];
        $minClicks = $options['min_clicks_threshold'] ?? self::DEFAULT_CONFIG['min_clicks_threshold'];

        // Check if previous window meets threshold (impressions OR clicks)
        $previousImpressions = $previous['impressions'] ?? 0;
        $previousClicks = $previous['clicks'] ?? 0;

        $meetsImpressionsThreshold = $previousImpressions >= $minImpressions;
        $meetsClicksThreshold = $previousClicks >= $minClicks;

        return $meetsImpressionsThreshold || $meetsClicksThreshold;
    }

    /**
     * {@inheritDoc}
     */
    public function isCooldownActive(ContentDecayCandidate $candidate): bool
    {
        // Check if candidate was recently refreshed
        if ($candidate->status === 'refreshed' && $candidate->refreshed_at !== null) {
            $cooldownDays = self::DEFAULT_CONFIG['refresh_cooldown_days'];
            return $candidate->refreshed_at->gt(now()->subDays($cooldownDays));
        }

        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function isBelowLowSignalThreshold(array $comparison): bool
    {
        $deltas = $comparison['deltas'] ?? [];
        $percentages = $comparison['percentages'] ?? [];
        $threshold = self::DEFAULT_CONFIG['low_signal_threshold'];

        // Check if all percentage changes are below threshold
        $clickPercent = abs($percentages['clicks'] ?? 0);
        $impressionPercent = abs($percentages['impressions'] ?? 0);

        return $clickPercent < $threshold && $impressionPercent < $threshold;
    }

    // =========================================================================
    // Accessor Methods for Testing
    // =========================================================================

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
        return self::DEFAULT_CONFIG;
    }

    // =========================================================================
    // Protected Helper Methods
    // =========================================================================

    /**
     * Get or create a project for a site.
     */
    protected function getOrCreateProject(Site $site): ContentDecayProject
    {
        $project = ContentDecayProject::where('site_id', $site->id)->first();

        if ($project === null) {
            $project = ContentDecayProject::create([
                'user_id' => $site->user_id,
                'site_id' => $site->id,
                'detection_enabled' => true,
                'window_days' => self::DEFAULT_CONFIG['window_days'],
            ]);
        }

        return $project;
    }

    /**
     * Calculate window dates for comparison.
     */
    protected function calculateWindowDates(int $windowDays): array
    {
        $currentEnd = Carbon::yesterday();
        $currentStart = $currentEnd->copy()->subDays($windowDays - 1);
        $previousEnd = $currentStart->copy()->subDay();
        $previousStart = $previousEnd->copy()->subDays($windowDays - 1);

        return [
            'previous_start' => $previousStart->toDateString(),
            'previous_end' => $previousEnd->toDateString(),
            'current_start' => $currentStart->toDateString(),
            'current_end' => $currentEnd->toDateString(),
        ];
    }

    /**
     * Fetch GSC data for a site.
     * Override in tests to mock GSC data.
     */
    protected function fetchGscData(Site $site, array $windowDates): array
    {
        // This is a placeholder - in production this would call the GSC API
        // For testing, this method can be overridden or mocked
        return [];
    }

    /**
     * Fetch GSC signals for a specific page.
     */
    protected function fetchGscSignalsForPage(string $pageUrl, Site $site, array $windowDates): ?array
    {
        // Placeholder - override in tests or implement GSC API integration
        return null;
    }

    /**
     * Fetch rankings signals for a page (optional source).
     */
    protected function fetchRankingsSignalsForPage(string $pageUrl, ContentDecayProject $project): ?array
    {
        // Optional source - return null if not available
        // In production, this would check rank tracking data
        return null;
    }

    /**
     * Fetch audit signals for a page (optional source).
     */
    protected function fetchAuditSignalsForPage(string $pageUrl, ContentDecayProject $project): ?array
    {
        // Optional source - return null if not available
        // In production, this would check audit issues for the page
        return null;
    }

    /**
     * Fetch annotation signals for a page (optional source).
     */
    protected function fetchAnnotationSignalsForPage(string $pageUrl, ContentDecayProject $project): ?array
    {
        // Optional source - return null if not available
        // In production, this would check recent annotations for the page
        return null;
    }

    /**
     * Fetch AI visibility signals for a page (optional source).
     */
    protected function fetchAiVisibilitySignalsForPage(string $pageUrl, ContentDecayProject $project, array $windowDates): ?array
    {
        // Optional source - return null if not available
        // In production, this would check AI visibility data
        return null;
    }

    /**
     * Process pages and detect decay.
     */
    protected function processPages(array $gscData, ContentDecayProject $project, array $windowDates): array
    {
        $results = [
            'processed' => 0,
            'eligible' => 0,
            'created' => 0,
            'updated' => 0,
            'archived' => 0,
            'errors' => 0,
            'pages' => [],
        ];

        foreach ($gscData as $pageUrl => $pageGscData) {
            $results['processed']++;

            // Check eligibility
            if (!$this->isPageEligible($pageGscData, $project->getThresholds())) {
                continue;
            }

            $results['eligible']++;

            try {
                // Gather signals
                $signals = $this->gatherSignals($pageUrl, $project, $pageGscData);

                // Compare windows
                $comparison = $this->compareWindows($signals);

                // Check low signal threshold
                if ($this->isBelowLowSignalThreshold($comparison)) {
                    continue;
                }

                // Check for existing candidate (for cooldown check)
                $existingCandidate = ContentDecayCandidate::where('project_id', $project->id)
                    ->where('page_url_hash', hash('sha256', $pageUrl))
                    ->first();

                if ($existingCandidate && $this->isCooldownActive($existingCandidate)) {
                    continue;
                }

                // Calculate decay score
                $normalizedSignals = $this->normalizeSignals($signals, $comparison);
                $scoreResult = $this->scoringService->calculateDecayScore($normalizedSignals);

                // Skip if no significant decay
                if ($scoreResult['decay_score'] < 1) {
                    continue;
                }

                // Assign reason codes
                $reasonResult = $this->reasonService->assignReasonCodes($this->buildReasonSignals($signals, $comparison));

                // Upsert candidate
                $candidate = $this->upsertCandidate([
                    'project_id' => $project->id,
                    'page_url' => $pageUrl,
                    'decay_score' => $scoreResult['decay_score'],
                    'severity' => $scoreResult['severity'],
                    'detection_confidence' => $scoreResult['detection_confidence'],
                    'primary_reason_code' => $reasonResult['primary_reason_code'],
                    'reason_codes' => $reasonResult['reason_codes'],
                    'signal_sources' => array_keys(array_filter($signals, fn($v) => $v !== null && $v !== [])),
                    'evidence_json' => $reasonResult['evidence'],
                ]);

                if ($candidate) {
                    if ($candidate->wasRecentlyCreated) {
                        $results['created']++;
                    } else {
                        $results['updated']++;
                    }

                    // Generate recommendations
                    $recommendations = $this->plannerService->generateRecommendations($candidate);

                    $results['pages'][] = [
                        'page_url' => $pageUrl,
                        'decay_score' => $scoreResult['decay_score'],
                        'severity' => $scoreResult['severity'],
                        'status' => $candidate->status,
                        'reason_codes' => $reasonResult['reason_codes'],
                        'recommendation' => $recommendations['primary_recommendation'],
                    ];
                }

            } catch (\Throwable $e) {
                $results['errors']++;
                Log::warning('Failed to process page for decay detection', [
                    'page_url' => $pageUrl,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $results;
    }

    /**
     * Compare a single metric.
     */
    protected function compareMetric(array &$comparison, string $metric, $previous, $current): void
    {
        if ($previous === null || $current === null) {
            $comparison['deltas'][$metric] = null;
            $comparison['percentages'][$metric] = null;
            $comparison['direction'][$metric] = 'unknown';
            return;
        }

        $delta = $current - $previous;
        $comparison['deltas'][$metric] = $delta;

        // Calculate percentage
        if ($previous == 0) {
            $comparison['percentages'][$metric] = $current > 0 ? 100 : 0;
        } else {
            $comparison['percentages'][$metric] = ($delta / $previous) * 100;
        }

        // Determine direction
        if ($delta > 0) {
            $comparison['direction'][$metric] = 'up';
        } elseif ($delta < 0) {
            $comparison['direction'][$metric] = 'down';
        } else {
            $comparison['direction'][$metric] = 'stable';
        }
    }

    /**
     * Compare position metric (reversed logic).
     */
    protected function comparePosition(array &$comparison, $previous, $current): void
    {
        if ($previous === null || $current === null) {
            $comparison['deltas']['position'] = null;
            $comparison['percentages']['position'] = null;
            $comparison['direction']['position'] = 'unknown';
            return;
        }

        // Position is reversed: higher number = worse
        $delta = $current - $previous;
        $comparison['deltas']['position'] = $delta;

        if ($previous == 0) {
            $comparison['percentages']['position'] = 0;
        } else {
            $comparison['percentages']['position'] = ($delta / $previous) * 100;
        }

        // Direction: positive delta = worse position = down
        if ($delta > 0) {
            $comparison['direction']['position'] = 'down';
        } elseif ($delta < 0) {
            $comparison['direction']['position'] = 'up';
        } else {
            $comparison['direction']['position'] = 'stable';
        }
    }

    /**
     * Normalize signals for scoring.
     */
    protected function normalizeSignals(array $signals, array $comparison): array
    {
        $normalized = [];

        // Click loss
        if (isset($signals['gsc']['previous']['clicks'], $signals['gsc']['current']['clicks'])) {
            $normalized['click_loss'] = $this->scoringService->normalizeClickLoss(
                $signals['gsc']['previous']['clicks'],
                $signals['gsc']['current']['clicks']
            );
        }

        // Impression loss
        if (isset($signals['gsc']['previous']['impressions'], $signals['gsc']['current']['impressions'])) {
            $normalized['impression_loss'] = $this->scoringService->normalizeImpressionLoss(
                $signals['gsc']['previous']['impressions'],
                $signals['gsc']['current']['impressions']
            );
        }

        // CTR drop
        if (isset($signals['gsc']['previous']['ctr'], $signals['gsc']['current']['ctr'])) {
            // Convert decimal to percentage for scoring
            $previousCtr = $signals['gsc']['previous']['ctr'] * 100;
            $currentCtr = $signals['gsc']['current']['ctr'] * 100;
            $normalized['ctr_drop'] = $this->scoringService->normalizeCtrDrop($previousCtr, $currentCtr);
        }

        // Position decline
        if (isset($signals['gsc']['previous']['position'], $signals['gsc']['current']['position'])) {
            $normalized['position_decline'] = $this->scoringService->normalizePositionDecline(
                (int) round($signals['gsc']['previous']['position']),
                (int) round($signals['gsc']['current']['position'])
            );
        }

        // Ranking decline (optional)
        if (isset($signals['rankings']['previous']['visibility'], $signals['rankings']['current']['visibility'])) {
            $prevRank = $signals['rankings']['previous']['visibility'];
            $currRank = $signals['rankings']['current']['visibility'];
            $normalized['ranking_decline'] = $prevRank > 0 ? max(0, ($prevRank - $currRank) / $prevRank) : 0;
        }

        // Audit pressure (optional)
        if (isset($signals['audit']['unresolved_issues'])) {
            $normalized['audit_pressure'] = min(1, $signals['audit']['unresolved_issues'] / 10);
        }

        // Staleness (optional)
        if (isset($signals['audit']['content_age_days'])) {
            $normalized['staleness'] = min(1, $signals['audit']['content_age_days'] / 365);
        }

        return $normalized;
    }

    /**
     * Build signals array for reason assignment.
     */
    protected function buildReasonSignals(array $signals, array $comparison): array
    {
        $reasonSignals = [];

        // GSC data
        if (isset($signals['gsc'])) {
            $reasonSignals['clicks'] = [
                'previous' => $signals['gsc']['previous']['clicks'] ?? null,
                'current' => $signals['gsc']['current']['clicks'] ?? null,
            ];
            $reasonSignals['impressions'] = [
                'previous' => $signals['gsc']['previous']['impressions'] ?? null,
                'current' => $signals['gsc']['current']['impressions'] ?? null,
            ];
            $reasonSignals['ctr'] = [
                'previous' => $signals['gsc']['previous']['ctr'] ?? null,
                'current' => $signals['gsc']['current']['ctr'] ?? null,
            ];
            $reasonSignals['avg_position'] = [
                'previous' => $signals['gsc']['previous']['position'] ?? null,
                'current' => $signals['gsc']['current']['position'] ?? null,
            ];
        }

        // Rankings
        if (isset($signals['rankings'])) {
            $reasonSignals['tracked_rank_visibility'] = [
                'previous' => $signals['rankings']['previous']['visibility'] ?? null,
                'current' => $signals['rankings']['current']['visibility'] ?? null,
            ];
        }

        // AI visibility
        if (isset($signals['ai_visibility'])) {
            $reasonSignals['ai_visibility'] = [
                'previous' => $signals['ai_visibility']['previous']['mention_rate'] ?? null,
                'current' => $signals['ai_visibility']['current']['mention_rate'] ?? null,
            ];
        }

        // Audit issues
        if (isset($signals['audit']['unresolved_issues'])) {
            $reasonSignals['unresolved_audit_issues'] = $signals['audit']['unresolved_issues'];
        }

        // Window info
        $reasonSignals['window'] = $signals['window'] ?? [];

        return $reasonSignals;
    }

    /**
     * Derive page type from URL.
     */
    protected function derivePageType(string $url): string
    {
        $path = parse_url($url, PHP_URL_PATH) ?? '';

        if (preg_match('/\/blog\//i', $path) || preg_match('/\/article\//i', $path)) {
            return 'blog';
        }
        if (preg_match('/\/product\//i', $path) || preg_match('/\/products\//i', $path)) {
            return 'product';
        }
        if (preg_match('/\/tool\//i', $path) || preg_match('/\/tools\//i', $path)) {
            return 'tool';
        }
        if (preg_match('/\/landing\//i', $path)) {
            return 'landing';
        }

        return 'other';
    }

    /**
     * Sanitize evidence to prevent XSS.
     */
    protected function sanitizeEvidence(array $evidence): array
    {
        array_walk_recursive($evidence, function (&$value) {
            if (is_string($value)) {
                // Strip script tags and other dangerous HTML
                $value = strip_tags($value);
            }
        });

        return $evidence;
    }

    /**
     * Create an empty result array.
     */
    protected function emptyResult(int $projectId, string $error = null): array
    {
        return [
            'project_id' => $projectId,
            'processed_pages' => 0,
            'eligible_pages' => 0,
            'candidates_created' => 0,
            'candidates_updated' => 0,
            'candidates_archived' => 0,
            'errors' => $error ? 1 : 0,
            'duration_ms' => 0,
            'pages' => [],
            'error' => $error,
        ];
    }
}
