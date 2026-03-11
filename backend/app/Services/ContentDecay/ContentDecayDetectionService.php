<?php

namespace App\Services\ContentDecay;

use App\Models\ContentDecayCandidate;
use App\Models\ContentDecayProject;
use App\Models\ContentDecaySignal;
use App\Models\Site;
use App\Models\GSCData;
use App\Services\ContentDecay\Contracts\ContentDecayDetectionServiceInterface;
use App\Services\ContentDecay\Contracts\ContentDecayScoringServiceInterface;
use App\Services\ContentDecay\Contracts\ContentDecayReasonServiceInterface;
use App\Services\ContentDecay\Contracts\ContentRefreshPlannerServiceInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

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
    ];

    /**
     * Decay score threshold for creating candidates.
     */
    protected const MIN_DECAY_SCORE_FOR_CANDIDATE = 10.0;

    /**
     * Score threshold for "recovered" status.
     */
    protected const RECOVERY_SCORE_THRESHOLD = 15.0;

    /**
     * Scoring service instance.
     */
    protected ContentDecayScoringServiceInterface $scoringService;

    /**
     * Reason service instance.
     */
    protected ContentDecayReasonServiceInterface $reasonService;

    /**
     * Planner service instance.
     */
    protected ContentRefreshPlannerServiceInterface $plannerService;

    /**
     * Configuration cache.
     */
    protected array $config;

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
        $this->config = self::DEFAULT_CONFIG;
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
     * Get the current configuration.
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
            // Get or create project for this site
            $project = $this->getOrCreateProject($site);

            if (!$project || !$project->detection_enabled) {
                $result['duration_ms'] = $this->getDurationMs($startTime);
                return $result;
            }

            // Fetch GSC data for the site
            $gscData = $this->fetchGscData($site);

            if (empty($gscData)) {
                $result['duration_ms'] = $this->getDurationMs($startTime);
                return $result;
            }

            // Merge project thresholds with defaults
            $thresholds = $this->getProjectThresholds($project);

            // Process each page
            foreach ($gscData as $pageUrl => $pageGscData) {
                $result['processed_pages']++;

                // Check eligibility
                if (!$this->isPageEligible($pageGscData, $thresholds)) {
                    continue;
                }

                $result['eligible_pages']++;

                // Check for existing candidate to apply cooldown
                $existingCandidate = ContentDecayCandidate::where('project_id', $project->id)
                    ->where('page_url', $pageUrl)
                    ->first();

                if ($existingCandidate && $this->isCooldownActive($existingCandidate)) {
                    continue;
                }

                // Gather signals
                $signals = $this->gatherSignals($pageUrl, $project, $pageGscData);

                // Compare windows
                $comparison = $this->compareWindows($signals);

                // Check low-signal threshold
                if ($this->isBelowLowSignalThreshold($comparison)) {
                    continue;
                }

                // Calculate decay score
                $scoreResult = $this->calculateDecayScore($signals, $comparison);

                // Check if decay is significant
                if ($scoreResult['decay_score'] < self::MIN_DECAY_SCORE_FOR_CANDIDATE) {
                    // If there's an existing candidate and decay is low, mark as recovered
                    if ($existingCandidate) {
                        $existingCandidate->update(['status' => 'recovered', 'recovered_at' => now()]);
                        $result['candidates_archived']++;
                    }
                    continue;
                }

                // Assign reason codes
                $reasonResult = $this->assignReasonCodes($signals, $comparison);

                // Upsert candidate
                $candidate = $this->upsertCandidate([
                    'project_id' => $project->id,
                    'page_url' => $pageUrl,
                    'decay_score' => $scoreResult['decay_score'],
                    'severity' => $scoreResult['severity'],
                    'detection_confidence' => $scoreResult['detection_confidence'],
                    'reason_codes' => $reasonResult['reason_codes'],
                    'primary_reason_code' => $reasonResult['primary_reason_code'],
                    'signal_sources' => $this->extractSignalSources($signals),
                    'evidence_json' => $reasonResult['evidence'],
                ]);

                if ($candidate) {
                    if ($candidate->wasRecentlyCreated) {
                        $result['candidates_created']++;
                    } else {
                        $result['candidates_updated']++;
                    }

                    $result['pages'][] = [
                        'page_url' => $pageUrl,
                        'decay_score' => $scoreResult['decay_score'],
                        'severity' => $scoreResult['severity'],
                        'status' => $candidate->status,
                        'reason_codes' => $reasonResult['reason_codes'],
                        'recommendation' => $reasonResult['primary_reason_code'],
                    ];

                    // Create signal record
                    $this->createSignalRecord($candidate, $signals, $comparison);
                }
            }

            // Update project's last scanned timestamp
            $project->update(['last_scanned_at' => now()]);
        } catch (\Throwable $e) {
            Log::error('Content decay detection failed', [
                'site_id' => $site->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $result['errors']++;
            $result['error'] = $e->getMessage();
        }

        $result['duration_ms'] = $this->getDurationMs($startTime);

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

        try {
            if (!$project->detection_enabled) {
                $result['duration_ms'] = $this->getDurationMs($startTime);
                return $result;
            }

            $site = $project->site;

            if (!$site) {
                $result['duration_ms'] = $this->getDurationMs($startTime);
                return $result;
            }

            // Fetch GSC data
            $gscData = $this->fetchGscData($site);

            if (empty($gscData)) {
                $result['duration_ms'] = $this->getDurationMs($startTime);
                return $result;
            }

            $thresholds = $this->getProjectThresholds($project);

            foreach ($gscData as $pageUrl => $pageGscData) {
                $result['processed_pages']++;

                if (!$this->isPageEligible($pageGscData, $thresholds)) {
                    continue;
                }

                $result['eligible_pages']++;

                $existingCandidate = ContentDecayCandidate::where('project_id', $project->id)
                    ->where('page_url', $pageUrl)
                    ->first();

                if ($existingCandidate && $this->isCooldownActive($existingCandidate)) {
                    continue;
                }

                $signals = $this->gatherSignals($pageUrl, $project, $pageGscData);
                $comparison = $this->compareWindows($signals);

                if ($this->isBelowLowSignalThreshold($comparison)) {
                    continue;
                }

                $scoreResult = $this->calculateDecayScore($signals, $comparison);

                if ($scoreResult['decay_score'] < self::MIN_DECAY_SCORE_FOR_CANDIDATE) {
                    if ($existingCandidate) {
                        $existingCandidate->update(['status' => 'recovered', 'recovered_at' => now()]);
                        $result['candidates_archived']++;
                    }
                    continue;
                }

                $reasonResult = $this->assignReasonCodes($signals, $comparison);

                $candidate = $this->upsertCandidate([
                    'project_id' => $project->id,
                    'page_url' => $pageUrl,
                    'decay_score' => $scoreResult['decay_score'],
                    'severity' => $scoreResult['severity'],
                    'detection_confidence' => $scoreResult['detection_confidence'],
                    'reason_codes' => $reasonResult['reason_codes'],
                    'primary_reason_code' => $reasonResult['primary_reason_code'],
                    'signal_sources' => $this->extractSignalSources($signals),
                    'evidence_json' => $reasonResult['evidence'],
                ]);

                if ($candidate) {
                    if ($candidate->wasRecentlyCreated) {
                        $result['candidates_created']++;
                    } else {
                        $result['candidates_updated']++;
                    }

                    $result['pages'][] = [
                        'page_url' => $pageUrl,
                        'decay_score' => $scoreResult['decay_score'],
                        'severity' => $scoreResult['severity'],
                        'status' => $candidate->status,
                        'reason_codes' => $reasonResult['reason_codes'],
                        'recommendation' => $reasonResult['primary_reason_code'],
                    ];

                    $this->createSignalRecord($candidate, $signals, $comparison);
                }
            }

            $project->update(['last_scanned_at' => now()]);
        } catch (\Throwable $e) {
            Log::error('Content decay detection for project failed', [
                'project_id' => $project->id,
                'error' => $e->getMessage(),
            ]);
            $result['errors']++;
            $result['error'] = $e->getMessage();
        }

        $result['duration_ms'] = $this->getDurationMs($startTime);

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function gatherSignals(string $pageUrl, ContentDecayProject $project, ?array $preFetchedGscData = null): array
    {
        $windowDays = $project->window_days ?? $this->config['window_days'];
        
        // Calculate window dates
        $currentEnd = Carbon::yesterday();
        $currentStart = $currentEnd->copy()->subDays($windowDays - 1);
        $previousEnd = $currentStart->copy()->subDay();
        $previousStart = $previousEnd->copy()->subDays($windowDays - 1);

        $signals = [
            'page_url' => $pageUrl,
            'gathered_at' => now()->toIso8601String(),
            'window' => [
                'previous_start' => $previousStart->toDateString(),
                'previous_end' => $previousEnd->toDateString(),
                'current_start' => $currentStart->toDateString(),
                'current_end' => $currentEnd->toDateString(),
            ],
            'gsc' => null,
            'rankings' => null,
            'audit' => null,
            'annotations' => null,
            'ai_visibility' => null,
        ];

        // GSC data (required) - use pre-fetched if available
        if ($preFetchedGscData !== null) {
            $signals['gsc'] = $preFetchedGscData;
        } else {
            $signals['gsc'] = $this->fetchGscDataForPage($pageUrl, $project, $previousStart, $currentEnd);
        }

        // Rankings (optional) - graceful skip if unavailable
        $rankingsData = $this->fetchRankingsDataForPage($pageUrl, $project, $previousStart, $currentEnd);
        if ($rankingsData !== null) {
            $signals['rankings'] = $rankingsData;
        }

        // Audit (optional) - graceful skip if unavailable
        $auditData = $this->fetchAuditDataForPage($pageUrl, $project);
        if ($auditData !== null) {
            $signals['audit'] = $auditData;
        }

        // Annotations (optional) - graceful skip if unavailable
        $annotationsData = $this->fetchAnnotationsForPage($pageUrl, $project);
        if ($annotationsData !== null) {
            $signals['annotations'] = $annotationsData;
        }

        // AI visibility (optional) - graceful skip if unavailable
        $aiVisibilityData = $this->fetchAiVisibilityForPage($pageUrl, $project, $previousStart, $currentEnd);
        if ($aiVisibilityData !== null) {
            $signals['ai_visibility'] = $aiVisibilityData;
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

        // Clicks
        $prevClicks = $previous['clicks'] ?? 0;
        $currClicks = $current['clicks'] ?? 0;
        $comparison['deltas']['clicks'] = $currClicks - $prevClicks;
        $comparison['percentages']['clicks'] = $prevClicks > 0 
            ? round((($currClicks - $prevClicks) / $prevClicks) * 100, 2) 
            : 0;
        $comparison['direction']['clicks'] = $this->determineDirection($prevClicks, $currClicks);

        // Impressions
        $prevImpressions = $previous['impressions'] ?? 0;
        $currImpressions = $current['impressions'] ?? 0;
        $comparison['deltas']['impressions'] = $currImpressions - $prevImpressions;
        $comparison['percentages']['impressions'] = $prevImpressions > 0 
            ? round((($currImpressions - $prevImpressions) / $prevImpressions) * 100, 2) 
            : 0;
        $comparison['direction']['impressions'] = $this->determineDirection($prevImpressions, $currImpressions);

        // CTR
        $prevCtr = $previous['ctr'] ?? 0;
        $currCtr = $current['ctr'] ?? 0;
        $comparison['deltas']['ctr'] = round($currCtr - $prevCtr, 6);
        $comparison['percentages']['ctr'] = $prevCtr > 0 
            ? round((($currCtr - $prevCtr) / $prevCtr) * 100, 2) 
            : 0;
        $comparison['direction']['ctr'] = $this->determineDirection($prevCtr, $currCtr);

        // Position (higher is worse)
        $prevPosition = $previous['position'] ?? 0;
        $currPosition = $current['position'] ?? 0;
        $comparison['deltas']['position'] = round($currPosition - $prevPosition, 2);
        $comparison['percentages']['position'] = $prevPosition > 0 
            ? round((($currPosition - $prevPosition) / $prevPosition) * 100, 2) 
            : 0;
        // Position direction is reversed (higher number = worse = "down")
        $comparison['direction']['position'] = $currPosition > $prevPosition ? 'down' 
            : ($currPosition < $prevPosition ? 'up' : 'stable');

        // Rankings visibility (optional)
        $rankings = $signals['rankings'] ?? null;
        if ($rankings) {
            $prevVisibility = $rankings['previous']['visibility'] ?? null;
            $currVisibility = $rankings['current']['visibility'] ?? null;
            if ($prevVisibility !== null && $currVisibility !== null) {
                $comparison['deltas']['visibility'] = round($currVisibility - $prevVisibility, 2);
                $comparison['percentages']['visibility'] = $prevVisibility > 0 
                    ? round((($currVisibility - $prevVisibility) / $prevVisibility) * 100, 2) 
                    : 0;
                $comparison['direction']['visibility'] = $this->determineDirection($prevVisibility, $currVisibility);
            }
        }

        // AI visibility (optional)
        $aiVisibility = $signals['ai_visibility'] ?? null;
        if ($aiVisibility) {
            $prevAi = $aiVisibility['previous']['mention_rate'] ?? null;
            $currAi = $aiVisibility['current']['mention_rate'] ?? null;
            if ($prevAi !== null && $currAi !== null) {
                $comparison['deltas']['ai_mention_rate'] = round($currAi - $prevAi, 4);
                $comparison['percentages']['ai_mention_rate'] = $prevAi > 0 
                    ? round((($currAi - $prevAi) / $prevAi) * 100, 2) 
                    : 0;
                $comparison['direction']['ai_mention_rate'] = $this->determineDirection($prevAi, $currAi);
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

        // Sanitize URL to prevent SQL injection
        $pageUrl = filter_var($pageUrl, FILTER_SANITIZE_URL);

        // Generate hash for page URL
        $pageUrlHash = hash('sha256', $pageUrl);

        // Look for existing candidate
        $candidate = ContentDecayCandidate::where('project_id', $projectId)
            ->where('page_url_hash', $pageUrlHash)
            ->first();

        $isNew = false;

        DB::beginTransaction();
        try {
            if ($candidate) {
                // Update existing candidate (preserve status)
                $updateData = [
                    'decay_score' => $detectionData['decay_score'] ?? 0,
                    'severity' => $detectionData['severity'] ?? 'low',
                    'detection_confidence' => $detectionData['detection_confidence'] ?? 0.85,
                    'reason_codes' => $detectionData['reason_codes'] ?? [],
                    'primary_reason_code' => $detectionData['primary_reason_code'] ?? null,
                    'signal_sources' => $detectionData['signal_sources'] ?? [],
                    'evidence_json' => $this->sanitizeEvidence($detectionData['evidence_json'] ?? []),
                    'last_detected_at' => now(),
                ];

                // Don't overwrite status if it's not 'backlog'
                // Also don't overwrite if it's 'ignored' or 'recovered'
                if (!in_array($candidate->status, ['ignored', 'recovered'])) {
                    // Keep existing status unless it's recovered
                    if ($candidate->status !== 'recovered') {
                        // Keep the status as is
                        unset($updateData['status']);
                    }
                }

                $candidate->update($updateData);
            } else {
                // Create new candidate
                $isNew = true;
                $candidate = ContentDecayCandidate::create([
                    'project_id' => $projectId,
                    'page_url' => $pageUrl,
                    'page_url_hash' => $pageUrlHash,
                    'decay_score' => $detectionData['decay_score'] ?? 0,
                    'severity' => $detectionData['severity'] ?? 'low',
                    'detection_confidence' => $detectionData['detection_confidence'] ?? 0.85,
                    'reason_codes' => $detectionData['reason_codes'] ?? [],
                    'primary_reason_code' => $detectionData['primary_reason_code'] ?? null,
                    'signal_sources' => $detectionData['signal_sources'] ?? [],
                    'evidence_json' => $this->sanitizeEvidence($detectionData['evidence_json'] ?? []),
                    'status' => 'backlog',
                    'first_detected_at' => now(),
                    'last_detected_at' => now(),
                ]);
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Failed to upsert decay candidate', [
                'project_id' => $projectId,
                'page_url' => $pageUrl,
                'error' => $e->getMessage(),
            ]);
            return null;
        }

        // Mark as recently created for the caller
        $candidate->wasRecentlyCreated = $isNew;

        return $candidate;
    }

    /**
     * {@inheritDoc}
     */
    public function isPageEligible(array $gscData, array $options = []): bool
    {
        // Must have both windows
        if (!isset($gscData['previous']) || !isset($gscData['current'])) {
            return false;
        }

        $previous = $gscData['previous'];
        $minImpressions = $options['min_impressions'] ?? $this->config['min_impressions_threshold'];
        $minClicks = $options['min_clicks'] ?? $this->config['min_clicks_threshold'];

        $prevImpressions = $previous['impressions'] ?? 0;
        $prevClicks = $previous['clicks'] ?? 0;

        // Must meet threshold (impressions OR clicks)
        if ($prevImpressions < $minImpressions && $prevClicks < $minClicks) {
            return false;
        }

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function isCooldownActive(ContentDecayCandidate $candidate): bool
    {
        // Only apply cooldown to refreshed candidates
        if ($candidate->status !== 'refreshed') {
            return false;
        }

        $refreshedAt = $candidate->refreshed_at;

        if (!$refreshedAt) {
            return false;
        }

        $cooldownDays = $this->config['refresh_cooldown_days'];
        $cooldownEnds = $refreshedAt->copy()->addDays($cooldownDays);

        return now()->lt($cooldownEnds);
    }

    /**
     * {@inheritDoc}
     */
    public function isBelowLowSignalThreshold(array $comparison): bool
    {
        $threshold = $this->config['low_signal_threshold'];

        // Check if all percentage changes are below threshold
        $percentages = $comparison['percentages'] ?? [];

        $maxChange = 0;
        foreach ($percentages as $metric => $value) {
            $absValue = abs($value);
            if ($absValue > $maxChange) {
                $maxChange = $absValue;
            }
        }

        return $maxChange < $threshold;
    }

    // =========================================================================
    // Protected Helper Methods
    // =========================================================================

    /**
     * Get or create a project for a site.
     */
    protected function getOrCreateProject(Site $site): ?ContentDecayProject
    {
        $project = ContentDecayProject::where('site_id', $site->id)->first();

        if (!$project) {
            $project = ContentDecayProject::create([
                'user_id' => $site->user_id,
                'site_id' => $site->id,
                'detection_enabled' => true,
                'window_days' => $this->config['window_days'],
            ]);
        }

        return $project;
    }

    /**
     * Fetch GSC data for a site.
     * Override this method in tests to provide mock data.
     */
    protected function fetchGscData(Site $site): array
    {
        // In production, this would fetch from GSCData model or API
        // For now, return empty array (to be overridden in tests)
        return [];
    }

    /**
     * Fetch GSC data for a specific page.
     */
    protected function fetchGscDataForPage(string $pageUrl, ContentDecayProject $project, Carbon $previousStart, Carbon $currentEnd): ?array
    {
        // In production, query GSCData model
        // This is a placeholder that returns null
        return null;
    }

    /**
     * Fetch rankings data for a page.
     */
    protected function fetchRankingsDataForPage(string $pageUrl, ContentDecayProject $project, Carbon $previousStart, Carbon $currentEnd): ?array
    {
        // Optional data source - return null if unavailable
        return null;
    }

    /**
     * Fetch audit data for a page.
     */
    protected function fetchAuditDataForPage(string $pageUrl, ContentDecayProject $project): ?array
    {
        // Optional data source - return null if unavailable
        return null;
    }

    /**
     * Fetch annotations for a page.
     */
    protected function fetchAnnotationsForPage(string $pageUrl, ContentDecayProject $project): ?array
    {
        // Optional data source - return null if unavailable
        return null;
    }

    /**
     * Fetch AI visibility data for a page.
     */
    protected function fetchAiVisibilityForPage(string $pageUrl, ContentDecayProject $project, Carbon $previousStart, Carbon $currentEnd): ?array
    {
        // Optional data source - return null if unavailable
        return null;
    }

    /**
     * Get project thresholds merged with defaults.
     */
    protected function getProjectThresholds(ContentDecayProject $project): array
    {
        $projectThresholds = $project->getThresholds();

        return [
            'min_impressions' => $projectThresholds['min_impressions'] ?? $this->config['min_impressions_threshold'],
            'min_clicks' => $projectThresholds['min_clicks'] ?? $this->config['min_clicks_threshold'],
        ];
    }

    /**
     * Calculate decay score using scoring service.
     */
    protected function calculateDecayScore(array $signals, array $comparison): array
    {
        $gsc = $signals['gsc'] ?? [];
        $previous = $gsc['previous'] ?? [];
        $current = $gsc['current'] ?? [];

        // Normalize signals for scoring service
        $normalizedSignals = [
            'click_loss' => $this->scoringService->normalizeClickLoss(
                $previous['clicks'] ?? 0,
                $current['clicks'] ?? 0
            ),
            'impression_loss' => $this->scoringService->normalizeImpressionLoss(
                $previous['impressions'] ?? 0,
                $current['impressions'] ?? 0
            ),
            'ctr_drop' => $this->scoringService->normalizeCtrDrop(
                ($previous['ctr'] ?? 0) * 100, // Convert to percentage
                ($current['ctr'] ?? 0) * 100
            ),
            'position_decline' => $this->scoringService->normalizePositionDecline(
                (int) round($previous['position'] ?? 0),
                (int) round($current['position'] ?? 0)
            ),
        ];

        // Add optional signals if available
        $rankings = $signals['rankings'] ?? null;
        if ($rankings) {
            $prevVis = $rankings['previous']['visibility'] ?? null;
            $currVis = $rankings['current']['visibility'] ?? null;
            if ($prevVis !== null && $currVis !== null && $prevVis > 0) {
                $normalizedSignals['ranking_decline'] = max(0, ($prevVis - $currVis) / $prevVis);
            }
        }

        $audit = $signals['audit'] ?? null;
        if ($audit && isset($audit['unresolved_issues'])) {
            $normalizedSignals['audit_pressure'] = min(1, $audit['unresolved_issues'] / 10);
        }

        return $this->scoringService->calculateDecayScore($normalizedSignals);
    }

    /**
     * Assign reason codes using reason service.
     */
    protected function assignReasonCodes(array $signals, array $comparison): array
    {
        // Prepare signals for reason service
        $reasonSignals = [
            'clicks' => [
                'previous' => $signals['gsc']['previous']['clicks'] ?? null,
                'current' => $signals['gsc']['current']['clicks'] ?? null,
            ],
            'impressions' => [
                'previous' => $signals['gsc']['previous']['impressions'] ?? null,
                'current' => $signals['gsc']['current']['impressions'] ?? null,
            ],
            'ctr' => [
                'previous' => ($signals['gsc']['previous']['ctr'] ?? 0) * 100,
                'current' => ($signals['gsc']['current']['ctr'] ?? 0) * 100,
            ],
            'avg_position' => [
                'previous' => $signals['gsc']['previous']['position'] ?? null,
                'current' => $signals['gsc']['current']['position'] ?? null,
            ],
            'window' => $signals['window'] ?? [],
        ];

        // Add optional signals
        if (isset($signals['rankings'])) {
            $reasonSignals['tracked_rank_visibility'] = [
                'previous' => $signals['rankings']['previous']['visibility'] ?? null,
                'current' => $signals['rankings']['current']['visibility'] ?? null,
            ];
        }

        if (isset($signals['audit'])) {
            $reasonSignals['unresolved_audit_issues'] = $signals['audit']['unresolved_issues'] ?? 0;
        }

        if (isset($signals['ai_visibility'])) {
            $reasonSignals['ai_visibility'] = [
                'previous' => $signals['ai_visibility']['previous']['mention_rate'] ?? null,
                'current' => $signals['ai_visibility']['current']['mention_rate'] ?? null,
            ];
        }

        $result = $this->reasonService->assignReasonCodes($reasonSignals);

        return [
            'primary_reason_code' => $result['primary_reason_code'] ?? null,
            'reason_codes' => $result['reason_codes'] ?? [],
            'evidence' => $result['evidence'] ?? [],
        ];
    }

    /**
     * Extract signal sources from gathered signals.
     */
    protected function extractSignalSources(array $signals): array
    {
        $sources = ['gsc']; // GSC is always required

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
     * Create a signal record for a candidate.
     */
    protected function createSignalRecord(ContentDecayCandidate $candidate, array $signals, array $comparison): void
    {
        $window = $signals['window'] ?? [];
        $gsc = $signals['gsc'] ?? [];

        ContentDecaySignal::create([
            'candidate_id' => $candidate->id,
            'previous_start' => $window['previous_start'] ?? null,
            'previous_end' => $window['previous_end'] ?? null,
            'current_start' => $window['current_start'] ?? null,
            'current_end' => $window['current_end'] ?? null,
            'previous_clicks' => $gsc['previous']['clicks'] ?? 0,
            'current_clicks' => $gsc['current']['clicks'] ?? 0,
            'previous_impressions' => $gsc['previous']['impressions'] ?? 0,
            'current_impressions' => $gsc['current']['impressions'] ?? 0,
            'previous_ctr' => $gsc['previous']['ctr'] ?? 0,
            'current_ctr' => $gsc['current']['ctr'] ?? 0,
            'previous_avg_position' => $gsc['previous']['position'] ?? null,
            'current_avg_position' => $gsc['current']['position'] ?? null,
            'created_at' => now(),
        ]);
    }

    /**
     * Determine direction based on previous and current values.
     */
    protected function determineDirection(float $previous, float $current): string
    {
        $epsilon = 0.0001; // Small threshold for "stable"

        if (abs($current - $previous) < $epsilon) {
            return 'stable';
        }

        return $current > $previous ? 'up' : 'down';
    }

    /**
     * Sanitize evidence to prevent XSS.
     */
    protected function sanitizeEvidence(array $evidence): array
    {
        $sanitized = [];

        foreach ($evidence as $key => $value) {
            if (is_array($value)) {
                $sanitized[$key] = $this->sanitizeEvidence($value);
            } elseif (is_string($value)) {
                // Strip script tags and other dangerous content
                $sanitized[$key] = htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            } else {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }

    /**
     * Get duration in milliseconds.
     */
    protected function getDurationMs(float $startTime): int
    {
        return (int) round((microtime(true) - $startTime) * 1000);
    }
}
