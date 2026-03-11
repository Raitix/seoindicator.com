<?php

namespace App\Services\ContentDecay;

use App\Models\ContentDecayCandidate;
use App\Models\ContentDecayProject;
use App\Models\Site;
use App\Services\ContentDecay\Contracts\ContentDecayDetectionServiceInterface;
use App\Services\ContentDecay\Contracts\ContentDecayScoringServiceInterface;
use App\Services\ContentDecay\Contracts\ContentDecayReasonServiceInterface;
use App\Services\ContentDecay\Contracts\ContentRefreshPlannerServiceInterface;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Content Decay Detection Service
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
        'low_signal_threshold' => 5,
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
            // Get or create project for this site
            $project = $this->getOrCreateProject($site);

            if (!$project || !$project->isDetectionEnabled()) {
                return $result;
            }

            // Run detection for the project
            $projectResult = $this->detectForProject($project);

            return array_merge($result, $projectResult);

        } catch (\Throwable $e) {
            Log::error('Content decay detection failed for site', [
                'site_id' => $site->id,
                'error' => $e->getMessage(),
            ]);

            $result['errors'] = 1;
            $result['error'] = $e->getMessage();
            return $result;
        } finally {
            $result['duration_ms'] = (int) ((microtime(true) - $startTime) * 1000);
        }
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

        if (!$project->isDetectionEnabled()) {
            return $result;
        }

        try {
            // Update last_scanned_at
            $project->last_scanned_at = now();
            $project->save();

            // Calculate window dates
            $windowDays = $project->getWindowDays() ?: self::DEFAULT_CONFIG['window_days'];
            $windowDates = $this->calculateWindowDates($windowDays);

            // Fetch GSC data for the site
            $gscData = $this->fetchGscData($project->site, $windowDates);

            if (empty($gscData)) {
                return $result;
            }

            // Get thresholds from project
            $thresholds = $project->getThresholds();

            // Process each page
            foreach ($gscData as $pageUrl => $pageGscData) {
                $result['processed_pages']++;

                // Check eligibility
                if (!$this->isPageEligible($pageGscData, $thresholds)) {
                    continue;
                }

                $result['eligible_pages']++;

                // Check for existing candidate in cooldown
                $existingCandidate = $this->findExistingCandidate($project->id, $pageUrl);
                if ($existingCandidate && $this->isCooldownActive($existingCandidate)) {
                    continue;
                }

                // Gather signals
                $signals = $this->gatherSignals($pageUrl, $project, $pageGscData);

                // Compare windows
                $comparison = $this->compareWindows($signals);

                // Check if below low-signal threshold
                if ($this->isBelowLowSignalThreshold($comparison)) {
                    // If existing candidate, mark as recovered
                    if ($existingCandidate) {
                        $existingCandidate->markAsRecovered();
                        $result['candidates_archived']++;
                    }
                    continue;
                }

                // Prepare scoring signals
                $scoringSignals = $this->prepareScoringSignals($signals, $comparison);

                // Calculate decay score
                $scoringResult = $this->scoringService->calculateDecayScore($scoringSignals);

                // If no decay (score too low)
                if ($scoringResult['decay_score'] < ($thresholds['min_decay_score'] ?? 0)) {
                    if ($existingCandidate) {
                        $existingCandidate->markAsRecovered();
                        $result['candidates_archived']++;
                    }
                    continue;
                }

                // Assign reason codes
                $reasonResult = $this->reasonService->assignReasonCodes([
                    'clicks' => [
                        'previous' => $signals['gsc']['previous']['clicks'] ?? null,
                        'current' => $signals['gsc']['current']['clicks'] ?? null,
                    ],
                    'impressions' => [
                        'previous' => $signals['gsc']['previous']['impressions'] ?? null,
                        'current' => $signals['gsc']['current']['impressions'] ?? null,
                    ],
                    'ctr' => [
                        'previous' => $signals['gsc']['previous']['ctr'] ?? null,
                        'current' => $signals['gsc']['current']['ctr'] ?? null,
                    ],
                    'avg_position' => [
                        'previous' => $signals['gsc']['previous']['position'] ?? null,
                        'current' => $signals['gsc']['current']['position'] ?? null,
                    ],
                    'tracked_rank_visibility' => [
                        'previous' => $signals['rankings']['previous']['visibility'] ?? null,
                        'current' => $signals['rankings']['current']['visibility'] ?? null,
                    ],
                    'ai_visibility' => [
                        'previous' => $signals['ai_visibility']['previous']['mention_rate'] ?? null,
                        'current' => $signals['ai_visibility']['current']['mention_rate'] ?? null,
                    ],
                    'unresolved_audit_issues' => $signals['audit']['unresolved_issues'] ?? null,
                    'content_age_days' => $signals['audit']['content_age_days'] ?? null,
                    'internal_links' => $signals['audit']['internal_links'] ?? null,
                    'last_annotation_at' => $signals['annotations']['last_annotation_at'] ?? null,
                ]);

                // Upsert candidate
                $candidate = $this->upsertCandidate([
                    'project_id' => $project->id,
                    'page_url' => $pageUrl,
                    'decay_score' => $scoringResult['decay_score'],
                    'severity' => $scoringResult['severity'],
                    'detection_confidence' => $scoringResult['detection_confidence'],
                    'reason_codes' => $reasonResult['reason_codes'],
                    'primary_reason_code' => $reasonResult['primary_reason_code'],
                    'signal_sources' => array_keys(array_filter($signals, fn($v) => $v !== null)),
                    'evidence_json' => $reasonResult['evidence'],
                ]);

                if ($candidate->wasRecentlyCreated) {
                    $result['candidates_created']++;
                } else {
                    $result['candidates_updated']++;
                }

                $result['pages'][] = [
                    'page_url' => $pageUrl,
                    'decay_score' => $scoringResult['decay_score'],
                    'severity' => $scoringResult['severity'],
                    'status' => $candidate->status,
                    'reason_codes' => $reasonResult['reason_codes'],
                    'recommendation' => $reasonResult['primary_reason_code'],
                ];
            }

        } catch (\Throwable $e) {
            Log::error('Content decay detection failed for project', [
                'project_id' => $project->id,
                'error' => $e->getMessage(),
            ]);

            $result['errors'] = 1;
            $result['error'] = $e->getMessage();
        }

        $result['duration_ms'] = (int) ((microtime(true) - $startTime) * 1000);

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function gatherSignals(string $pageUrl, ContentDecayProject $project, ?array $preFetchedGscData = null): array
    {
        $windowDays = $project->getWindowDays() ?: self::DEFAULT_CONFIG['window_days'];
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

        // GSC data (required)
        $signals['gsc'] = $preFetchedGscData ?? $this->fetchGscDataForPage($pageUrl, $project, $windowDates);

        // Rankings (optional) - graceful degradation
        try {
            $signals['rankings'] = $this->fetchRankingsData($pageUrl, $project, $windowDates);
        } catch (\Throwable $e) {
            Log::warning('Failed to fetch rankings data', [
                'page_url' => $pageUrl,
                'error' => $e->getMessage(),
            ]);
        }

        // Audit (optional) - graceful degradation
        try {
            $signals['audit'] = $this->fetchAuditData($pageUrl, $project);
        } catch (\Throwable $e) {
            Log::warning('Failed to fetch audit data', [
                'page_url' => $pageUrl,
                'error' => $e->getMessage(),
            ]);
        }

        // Annotations (optional) - graceful degradation
        try {
            $signals['annotations'] = $this->fetchAnnotationsData($pageUrl, $project);
        } catch (\Throwable $e) {
            Log::warning('Failed to fetch annotations data', [
                'page_url' => $pageUrl,
                'error' => $e->getMessage(),
            ]);
        }

        // AI visibility (optional) - graceful degradation
        try {
            $signals['ai_visibility'] = $this->fetchAiVisibilityData($pageUrl, $project, $windowDates);
        } catch (\Throwable $e) {
            Log::warning('Failed to fetch AI visibility data', [
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

        // GSC metrics
        if (isset($signals['gsc']) && $signals['gsc'] !== null) {
            $previous = $signals['gsc']['previous'] ?? [];
            $current = $signals['gsc']['current'] ?? [];

            // Clicks
            if (isset($previous['clicks'], $current['clicks'])) {
                $comparison['deltas']['clicks'] = $current['clicks'] - $previous['clicks'];
                $comparison['percentages']['clicks'] = $previous['clicks'] > 0
                    ? round(($comparison['deltas']['clicks'] / $previous['clicks']) * 100, 2)
                    : 0;
                $comparison['direction']['clicks'] = $this->determineDirection($comparison['deltas']['clicks']);
            }

            // Impressions
            if (isset($previous['impressions'], $current['impressions'])) {
                $comparison['deltas']['impressions'] = $current['impressions'] - $previous['impressions'];
                $comparison['percentages']['impressions'] = $previous['impressions'] > 0
                    ? round(($comparison['deltas']['impressions'] / $previous['impressions']) * 100, 2)
                    : 0;
                $comparison['direction']['impressions'] = $this->determineDirection($comparison['deltas']['impressions']);
            }

            // CTR
            if (isset($previous['ctr'], $current['ctr'])) {
                $comparison['deltas']['ctr'] = round($current['ctr'] - $previous['ctr'], 6);
                $comparison['percentages']['ctr'] = $previous['ctr'] > 0
                    ? round(($comparison['deltas']['ctr'] / $previous['ctr']) * 100, 2)
                    : 0;
                $comparison['direction']['ctr'] = $this->determineDirection($comparison['deltas']['ctr']);
            }

            // Position (higher is worse)
            if (isset($previous['position'], $current['position'])) {
                $comparison['deltas']['position'] = round($current['position'] - $previous['position'], 2);
                $comparison['percentages']['position'] = $previous['position'] > 0
                    ? round(($comparison['deltas']['position'] / $previous['position']) * 100, 2)
                    : 0;
                $comparison['direction']['position'] = $this->determineDirection($comparison['deltas']['position'], true);
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

        // Sanitize the page URL to prevent XSS
        $pageUrl = $this->sanitizeUrl($pageUrl);

        // Find existing candidate
        $candidate = ContentDecayCandidate::where('project_id', $projectId)
            ->where('page_url', $pageUrl)
            ->first();

        // Sanitize evidence_json
        $evidenceJson = $detectionData['evidence_json'] ?? [];
        $evidenceJson = $this->sanitizeEvidence($evidenceJson);

        // Ensure primary_reason_code always has a value (required by schema)
        $primaryReasonCode = $detectionData['primary_reason_code'] ?? null;
        if ($primaryReasonCode === null) {
            $reasonCodes = $detectionData['reason_codes'] ?? [];
            $primaryReasonCode = !empty($reasonCodes) ? $reasonCodes[0] : 'clicks_down';
        }

        if ($candidate) {
            // Update existing candidate (preserve status)
            $candidate->update([
                'decay_score' => $detectionData['decay_score'] ?? $candidate->decay_score,
                'severity' => $detectionData['severity'] ?? $candidate->severity,
                'detection_confidence' => $detectionData['detection_confidence'] ?? $candidate->detection_confidence,
                'primary_reason_code' => $primaryReasonCode,
                'reason_codes' => $detectionData['reason_codes'] ?? $candidate->reason_codes,
                'signal_sources' => $detectionData['signal_sources'] ?? $candidate->signal_sources,
                'evidence_json' => $evidenceJson,
                'last_detected_at' => now(),
            ]);
        } else {
            // Create new candidate
            $candidate = ContentDecayCandidate::create([
                'project_id' => $projectId,
                'page_url' => $pageUrl,
                'page_url_hash' => hash('sha256', $pageUrl),
                'decay_score' => $detectionData['decay_score'] ?? 0,
                'severity' => $detectionData['severity'] ?? 'low',
                'detection_confidence' => $detectionData['detection_confidence'] ?? 0.85,
                'primary_reason_code' => $primaryReasonCode,
                'reason_codes' => $detectionData['reason_codes'] ?? [],
                'signal_sources' => $detectionData['signal_sources'] ?? ['gsc'],
                'evidence_json' => $evidenceJson,
                'status' => 'backlog',
                'first_detected_at' => now(),
                'last_detected_at' => now(),
            ]);
        }

        return $candidate;
    }

    /**
     * {@inheritDoc}
     */
    public function isPageEligible(array $gscData, array $options = []): bool
    {
        // Check both windows exist
        if (!isset($gscData['previous'], $gscData['current'])) {
            return false;
        }

        $previous = $gscData['previous'];
        $minImpressions = $options['min_impressions'] ?? self::DEFAULT_CONFIG['min_impressions_threshold'];
        $minClicks = $options['min_clicks'] ?? self::DEFAULT_CONFIG['min_clicks_threshold'];

        // Check thresholds - either impressions OR clicks must pass
        $impressionsPass = ($previous['impressions'] ?? 0) >= $minImpressions;
        $clicksPass = ($previous['clicks'] ?? 0) >= $minClicks;

        return $impressionsPass || $clicksPass;
    }

    /**
     * {@inheritDoc}
     */
    public function isCooldownActive(ContentDecayCandidate $candidate): bool
    {
        // Check if candidate was recently refreshed
        if ($candidate->status === 'refreshed' && $candidate->refreshed_at) {
            $cooldownDays = self::DEFAULT_CONFIG['refresh_cooldown_days'];
            return $candidate->refreshed_at->diffInDays(now()) < $cooldownDays;
        }

        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function isBelowLowSignalThreshold(array $comparison): bool
    {
        $threshold = self::DEFAULT_CONFIG['low_signal_threshold'];

        // Check if all percentage changes are below threshold
        $percentages = $comparison['percentages'] ?? [];

        foreach ($percentages as $metric => $percent) {
            if (abs($percent) >= $threshold) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get the scoring service instance.
     */
    public function getScoringService(): ContentDecayScoringServiceInterface
    {
        return $this->scoringService;
    }

    /**
     * Get the reason service instance.
     */
    public function getReasonService(): ContentDecayReasonServiceInterface
    {
        return $this->reasonService;
    }

    /**
     * Get the planner service instance.
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

    // ==========================================
    // Protected Helper Methods
    // ==========================================

    /**
     * Get or create a ContentDecayProject for a site.
     */
    protected function getOrCreateProject(Site $site): ?ContentDecayProject
    {
        $project = ContentDecayProject::where('site_id', $site->id)->first();

        if (!$project) {
            $project = ContentDecayProject::create([
                'user_id' => $site->user_id,
                'site_id' => $site->id,
                'detection_enabled' => true,
                'window_days' => self::DEFAULT_CONFIG['window_days'],
                'min_clicks_threshold' => self::DEFAULT_CONFIG['min_clicks_threshold'],
                'min_impressions_threshold' => self::DEFAULT_CONFIG['min_impressions_threshold'],
            ]);
        }

        return $project;
    }

    /**
     * Calculate window dates for comparison.
     *
     * Returns array with previous and current window dates.
     */
    protected function calculateWindowDates(int $windowDays): array
    {
        $now = Carbon::now();

        // Current window: last N complete days (excluding today)
        $currentEnd = $now->subDay()->endOfDay();
        $currentStart = $now->subDays($windowDays)->startOfDay();

        // Previous window: N days before current window
        $previousEnd = $currentStart->copy()->subSecond();
        $previousStart = $previousEnd->copy()->subDays($windowDays)->startOfDay();

        return [
            'previous_start' => $previousStart->toDateString(),
            'previous_end' => $previousEnd->toDateString(),
            'current_start' => $currentStart->toDateString(),
            'current_end' => $currentEnd->toDateString(),
        ];
    }

    /**
     * Fetch GSC data for a site.
     *
     * This is a placeholder method that should be overridden
     * to integrate with actual GSC API.
     *
     * @return array<string, array{previous: array, current: array}>
     */
    protected function fetchGscData(Site $site, array $windowDates): array
    {
        // Placeholder - should be implemented with actual GSC integration
        // Returns empty array by default
        return [];
    }

    /**
     * Fetch GSC data for a specific page.
     */
    protected function fetchGscDataForPage(string $pageUrl, ContentDecayProject $project, array $windowDates): ?array
    {
        // Placeholder - should be implemented with actual GSC integration
        return null;
    }

    /**
     * Fetch rankings data for a page.
     */
    protected function fetchRankingsData(string $pageUrl, ContentDecayProject $project, array $windowDates): ?array
    {
        // Placeholder - should be implemented with actual rankings integration
        return null;
    }

    /**
     * Fetch audit data for a page.
     */
    protected function fetchAuditData(string $pageUrl, ContentDecayProject $project): ?array
    {
        // Placeholder - should be implemented with actual audit integration
        return null;
    }

    /**
     * Fetch annotations data for a page.
     */
    protected function fetchAnnotationsData(string $pageUrl, ContentDecayProject $project): ?array
    {
        // Placeholder - should be implemented with actual annotations integration
        return null;
    }

    /**
     * Fetch AI visibility data for a page.
     */
    protected function fetchAiVisibilityData(string $pageUrl, ContentDecayProject $project, array $windowDates): ?array
    {
        // Placeholder - should be implemented with actual AI visibility integration
        return null;
    }

    /**
     * Find an existing candidate by project and page URL.
     */
    protected function findExistingCandidate(int $projectId, string $pageUrl): ?ContentDecayCandidate
    {
        return ContentDecayCandidate::where('project_id', $projectId)
            ->where('page_url', $pageUrl)
            ->first();
    }

    /**
     * Prepare scoring signals from gathered signals and comparison.
     */
    protected function prepareScoringSignals(array $signals, array $comparison): array
    {
        $gsc = $signals['gsc'] ?? [];

        $scoringSignals = [
            'click_loss' => $this->scoringService->normalizeClickLoss(
                $gsc['previous']['clicks'] ?? 0,
                $gsc['current']['clicks'] ?? 0
            ),
            'impression_loss' => $this->scoringService->normalizeImpressionLoss(
                $gsc['previous']['impressions'] ?? 0,
                $gsc['current']['impressions'] ?? 0
            ),
            'ctr_drop' => $this->scoringService->normalizeCtrDrop(
                $gsc['previous']['ctr'] ?? 0,
                $gsc['current']['ctr'] ?? 0
            ),
            'position_decline' => $this->scoringService->normalizePositionDecline(
                (int) ($gsc['previous']['position'] ?? 0),
                (int) ($gsc['current']['position'] ?? 0)
            ),
        ];

        // Optional signals
        if (isset($signals['rankings']) && $signals['rankings'] !== null) {
            $rankings = $signals['rankings'];
            $prevVis = $rankings['previous']['visibility'] ?? 0;
            $currVis = $rankings['current']['visibility'] ?? 0;
            if ($prevVis > 0) {
                $scoringSignals['ranking_decline'] = max(0, ($prevVis - $currVis) / $prevVis);
            }
        }

        if (isset($signals['audit']) && $signals['audit'] !== null) {
            $issues = $signals['audit']['unresolved_issues'] ?? 0;
            $scoringSignals['audit_pressure'] = min(1, $issues / 10);
        }

        if (isset($signals['audit']['content_age_days'])) {
            $age = $signals['audit']['content_age_days'];
            // Staleness: 180 days = 1.0, capped at 1.0
            $scoringSignals['staleness'] = min(1, $age / 180);
        }

        return $scoringSignals;
    }

    /**
     * Determine direction of change.
     */
    protected function determineDirection(float $delta, bool $reversed = false): string
    {
        if ($delta > 0) {
            return $reversed ? 'down' : 'up';
        } elseif ($delta < 0) {
            return $reversed ? 'up' : 'down';
        }
        return 'stable';
    }

    /**
     * Sanitize a URL to prevent XSS.
     */
    protected function sanitizeUrl(string $url): string
    {
        // Remove any script tags or dangerous content
        $url = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $url);
        $url = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
        return $url;
    }

    /**
     * Sanitize evidence array to prevent XSS.
     */
    protected function sanitizeEvidence(array $evidence): array
    {
        $sanitized = [];

        foreach ($evidence as $key => $value) {
            if (is_array($value)) {
                $sanitized[$key] = $this->sanitizeEvidence($value);
            } elseif (is_string($value)) {
                // Remove script tags
                $value = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $value);
                $sanitized[$key] = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
            } else {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }
}
