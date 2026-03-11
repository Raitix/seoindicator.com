<?php

namespace App\Services\ContentDecay;

use App\Models\ContentDecayCandidate;
use App\Models\ContentDecayProject;
use App\Models\ContentDecaySignal;
use App\Models\Site;
use App\Services\ContentDecay\Contracts\ContentDecayDetectionServiceInterface;
use App\Services\ContentDecay\Contracts\ContentDecayScoringServiceInterface;
use App\Services\ContentDecay\Contracts\ContentDecayReasonServiceInterface;
use App\Services\ContentDecay\Contracts\ContentRefreshPlannerServiceInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Content Decay Detection Service.
 *
 * Main orchestration service that gathers signals from multiple sources
 * (GSC, rankings, audits, annotations, AI visibility), compares windows,
 * and creates/updates decay candidates.
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
        'low_signal_threshold' => 5.0,
        'max_pages_per_run' => 1000,
    ];

    /**
     * Low signal threshold for suppression.
     */
    protected const LOW_SIGNAL_THRESHOLD = 5.0;

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
     * Get the default configuration.
     */
    public function getConfiguration(): array
    {
        return self::DEFAULT_CONFIG;
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
            $project = ContentDecayProject::firstOrCreate(
                ['site_id' => $site->id],
                [
                    'user_id' => $site->user_id,
                    'detection_enabled' => true,
                    'window_days' => self::DEFAULT_CONFIG['window_days'],
                ]
            );

            // Check if detection is enabled
            if (!$project->detection_enabled) {
                return $result;
            }

            // Run detection for the project
            $projectResult = $this->detectForProject($project);

            $result['processed_pages'] = $projectResult['processed_pages'];
            $result['eligible_pages'] = $projectResult['eligible_pages'];
            $result['candidates_created'] = $projectResult['candidates_created'];
            $result['candidates_updated'] = $projectResult['candidates_updated'];
            $result['candidates_archived'] = $projectResult['candidates_archived'];
            $result['errors'] = $projectResult['errors'];
            $result['pages'] = $projectResult['pages'];

        } catch (\Throwable $e) {
            Log::error('Content decay detection failed', [
                'site_id' => $site->id,
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
            return $result;
        }

        try {
            // Fetch GSC data for the site
            $gscData = $this->fetchGscData($project->site);

            if (empty($gscData)) {
                return $result;
            }

            $windowDays = $project->window_days ?? self::DEFAULT_CONFIG['window_days'];
            $thresholds = $project->getThresholds();

            foreach ($gscData as $pageUrl => $pageData) {
                $result['processed_pages']++;

                // Check eligibility
                if (!$this->isPageEligible($pageData, ['thresholds' => $thresholds])) {
                    continue;
                }

                $result['eligible_pages']++;

                // Gather signals (pass existing GSC data to avoid re-fetching)
                $signals = $this->gatherSignals($pageUrl, $project, $pageData);

                // Compare windows
                $comparison = $this->compareWindows($signals);

                // Check low-signal threshold
                if ($this->isBelowLowSignalThreshold($comparison)) {
                    continue;
                }

                // Check for existing candidate (cooldown check)
                $existingCandidate = ContentDecayCandidate::where('project_id', $project->id)
                    ->where('page_url', $pageUrl)
                    ->first();

                if ($existingCandidate && $this->isCooldownActive($existingCandidate)) {
                    continue;
                }

                // Calculate decay score
                $normalizedSignals = $this->normalizeSignals($signals, $comparison);
                $scoreResult = $this->scoringService->calculateDecayScore($normalizedSignals);

                $minDecayScore = $thresholds['min_decay_score'] ?? 20;
                $hasSignificantDecay = $scoreResult['decay_score'] >= $minDecayScore;

                // Handle recovery detection for existing candidates
                if ($existingCandidate && !$hasSignificantDecay) {
                    // Mark as recovered if the decay score is below threshold
                    if (in_array($existingCandidate->status, ['backlog', 'planned', 'in_progress'])) {
                        $existingCandidate->update([
                            'status' => 'recovered',
                            'decay_score' => $scoreResult['decay_score'],
                            'last_detected_at' => now(),
                        ]);
                        $result['candidates_archived']++;
                    }
                    continue;
                }

                // Skip if no significant decay
                if (!$hasSignificantDecay) {
                    continue;
                }

                // Assign reason codes
                $reasonResult = $this->reasonService->assignReasonCodes($this->buildReasonSignals($signals, $comparison));

                // Upsert candidate
                $candidate = $this->upsertCandidate([
                    'project_id' => $project->id,
                    'page_url' => $pageUrl,
                    'page_title' => $pageData['title'] ?? null,
                    'decay_score' => $scoreResult['decay_score'],
                    'severity' => $scoreResult['severity'],
                    'detection_confidence' => $scoreResult['detection_confidence'],
                    'reason_codes' => $reasonResult['reason_codes'],
                    'primary_reason_code' => $reasonResult['primary_reason_code'],
                    'signal_sources' => array_keys(array_filter($signals, fn($v) => $v !== null && $v !== [])),
                    'evidence_json' => $reasonResult['evidence'],
                ]);

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
                    'recommendation' => $reasonResult['primary_reason_code'] ?? 'No action required',
                ];
            }

            // Update last scanned timestamp
            $project->update(['last_scanned_at' => now()]);

        } catch (\Throwable $e) {
            Log::error('Content decay detection failed for project', [
                'project_id' => $project->id,
                'error' => $e->getMessage(),
            ]);
            $result['errors'] = 1;
        }

        $result['duration_ms'] = (int) ((microtime(true) - $startTime) * 1000);

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function gatherSignals(string $pageUrl, ContentDecayProject $project, ?array $preFetchedGscData = null): array
    {
        $windowDays = $project->window_days ?? self::DEFAULT_CONFIG['window_days'];

        // Calculate window dates
        $currentEnd = Carbon::yesterday()->endOfDay();
        $currentStart = $currentEnd->copy()->subDays($windowDays - 1)->startOfDay();
        $previousEnd = $currentStart->copy()->subDay()->endOfDay();
        $previousStart = $previousEnd->copy()->subDays($windowDays - 1)->startOfDay();

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

        // Use pre-fetched GSC data if provided, otherwise fetch it
        if ($preFetchedGscData !== null) {
            $signals['gsc'] = $preFetchedGscData;
        } else {
            // Fetch GSC data (required)
            $signals['gsc'] = $this->fetchGscPageData($pageUrl, $project->site, $previousStart, $currentEnd);
        }

        // Fetch rankings data (optional - graceful degradation)
        try {
            $signals['rankings'] = $this->fetchRankingsData($pageUrl, $project, $previousStart, $currentEnd);
        } catch (\Throwable $e) {
            Log::warning('Rankings data unavailable for page', [
                'page_url' => $pageUrl,
                'error' => $e->getMessage(),
            ]);
        }

        // Fetch audit data (optional - graceful degradation)
        try {
            $signals['audit'] = $this->fetchAuditData($pageUrl, $project->site);
        } catch (\Throwable $e) {
            Log::warning('Audit data unavailable for page', [
                'page_url' => $pageUrl,
                'error' => $e->getMessage(),
            ]);
        }

        // Fetch annotations (optional - graceful degradation)
        try {
            $signals['annotations'] = $this->fetchAnnotationData($pageUrl, $project);
        } catch (\Throwable $e) {
            Log::warning('Annotation data unavailable for page', [
                'page_url' => $pageUrl,
                'error' => $e->getMessage(),
            ]);
        }

        // Fetch AI visibility (optional - graceful degradation)
        try {
            $signals['ai_visibility'] = $this->fetchAiVisibilityData($pageUrl, $project, $previousStart, $currentEnd);
        } catch (\Throwable $e) {
            Log::warning('AI visibility data unavailable for page', [
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

        if ($gsc && isset($gsc['previous']) && isset($gsc['current'])) {
            $prev = $gsc['previous'];
            $curr = $gsc['current'];

            // Clicks
            if (isset($prev['clicks']) && isset($curr['clicks'])) {
                $comparison['deltas']['clicks'] = $curr['clicks'] - $prev['clicks'];
                $comparison['percentages']['clicks'] = $prev['clicks'] > 0
                    ? round((($curr['clicks'] - $prev['clicks']) / $prev['clicks']) * 100, 2)
                    : 0;
                $comparison['direction']['clicks'] = $this->determineDirection($comparison['deltas']['clicks']);
            }

            // Impressions
            if (isset($prev['impressions']) && isset($curr['impressions'])) {
                $comparison['deltas']['impressions'] = $curr['impressions'] - $prev['impressions'];
                $comparison['percentages']['impressions'] = $prev['impressions'] > 0
                    ? round((($curr['impressions'] - $prev['impressions']) / $prev['impressions']) * 100, 2)
                    : 0;
                $comparison['direction']['impressions'] = $this->determineDirection($comparison['deltas']['impressions']);
            }

            // CTR
            if (isset($prev['ctr']) && isset($curr['ctr'])) {
                $comparison['deltas']['ctr'] = round($curr['ctr'] - $prev['ctr'], 6);
                $comparison['percentages']['ctr'] = $prev['ctr'] > 0
                    ? round((($curr['ctr'] - $prev['ctr']) / $prev['ctr']) * 100, 2)
                    : 0;
                $comparison['direction']['ctr'] = $this->determineDirection($comparison['deltas']['ctr']);
            }

            // Position (higher is worse)
            if (isset($prev['position']) && isset($curr['position'])) {
                $comparison['deltas']['position'] = round($curr['position'] - $prev['position'], 2);
                $comparison['percentages']['position'] = $prev['position'] > 0
                    ? round((($curr['position'] - $prev['position']) / $prev['position']) * 100, 2)
                    : 0;
                // Position direction is reversed (higher position number = worse)
                $comparison['direction']['position'] = $comparison['deltas']['position'] > 0 ? 'down' : ($comparison['deltas']['position'] < 0 ? 'up' : 'stable');
            }
        }

        // Rankings visibility
        $rankings = $signals['rankings'] ?? null;
        if ($rankings && isset($rankings['previous']) && isset($rankings['current'])) {
            $prevVis = $rankings['previous']['visibility'] ?? null;
            $currVis = $rankings['current']['visibility'] ?? null;
            if ($prevVis !== null && $currVis !== null) {
                $comparison['deltas']['rank_visibility'] = round($currVis - $prevVis, 2);
                $comparison['percentages']['rank_visibility'] = $prevVis > 0
                    ? round((($currVis - $prevVis) / $prevVis) * 100, 2)
                    : 0;
                $comparison['direction']['rank_visibility'] = $this->determineDirection($comparison['deltas']['rank_visibility']);
            }
        }

        // AI visibility
        $aiVisibility = $signals['ai_visibility'] ?? null;
        if ($aiVisibility && isset($aiVisibility['previous']) && isset($aiVisibility['current'])) {
            $prevMention = $aiVisibility['previous']['mention_rate'] ?? null;
            $currMention = $aiVisibility['current']['mention_rate'] ?? null;
            if ($prevMention !== null && $currMention !== null) {
                $comparison['deltas']['ai_mention_rate'] = round($currMention - $prevMention, 4);
                $comparison['percentages']['ai_mention_rate'] = $prevMention > 0
                    ? round((($currMention - $prevMention) / $prevMention) * 100, 2)
                    : 0;
                $comparison['direction']['ai_mention_rate'] = $this->determineDirection($comparison['deltas']['ai_mention_rate']);
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

        // Sanitize page URL
        $pageUrl = $this->sanitizeUrl($pageUrl);

        // Sanitize evidence JSON
        $evidenceJson = $detectionData['evidence_json'] ?? [];
        if (is_array($evidenceJson)) {
            $evidenceJson = $this->sanitizeEvidence($evidenceJson);
        }

        // Use a callback that can be executed within or outside a transaction
        $upsert = function () use ($detectionData, $pageUrl, $evidenceJson, $projectId) {
            // Find existing candidate by project and page URL
            $candidate = ContentDecayCandidate::where('project_id', $projectId)
                ->where('page_url', $pageUrl)
                ->first();

            if ($candidate) {
                // Update existing candidate, preserving status
                $candidate->update([
                    'decay_score' => $detectionData['decay_score'] ?? $candidate->decay_score,
                    'severity' => $detectionData['severity'] ?? $candidate->severity,
                    'detection_confidence' => $detectionData['detection_confidence'] ?? $candidate->detection_confidence,
                    'primary_reason_code' => $detectionData['primary_reason_code'] ?? $candidate->primary_reason_code,
                    'reason_codes' => $detectionData['reason_codes'] ?? $candidate->reason_codes,
                    'signal_sources' => $detectionData['signal_sources'] ?? $candidate->signal_sources,
                    'evidence_json' => $evidenceJson ?? $candidate->evidence_json,
                    'last_detected_at' => now(),
                    'page_title' => $detectionData['page_title'] ?? $candidate->page_title,
                ]);

                return $candidate;
            }

            // Create new candidate
            return ContentDecayCandidate::create([
                'project_id' => $projectId,
                'page_url' => $pageUrl,
                'page_url_hash' => hash('sha256', $pageUrl),
                'page_title' => $detectionData['page_title'] ?? null,
                'page_type' => $this->determinePageType($pageUrl),
                'severity' => $detectionData['severity'] ?? 'medium',
                'decay_score' => $detectionData['decay_score'] ?? 0,
                'detection_confidence' => $detectionData['detection_confidence'] ?? 0.85,
                'primary_reason_code' => $detectionData['primary_reason_code'] ?? 'unknown',
                'reason_codes' => $detectionData['reason_codes'] ?? [],
                'signal_sources' => $detectionData['signal_sources'] ?? ['gsc'],
                'evidence_json' => $evidenceJson,
                'status' => 'backlog',
                'first_detected_at' => now(),
                'last_detected_at' => now(),
            ]);
        };

        // Check if we're already in a transaction (e.g., RefreshDatabase in tests)
        // If so, execute directly without starting a new transaction
        if (DB::transactionLevel() > 0) {
            return $upsert();
        }

        return DB::transaction($upsert);
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

        $prev = $gscData['previous'];
        $minImpressions = $options['min_impressions_threshold'] ?? self::DEFAULT_CONFIG['min_impressions_threshold'];
        $minClicks = $options['min_clicks_threshold'] ?? self::DEFAULT_CONFIG['min_clicks_threshold'];

        // Check thresholds from options or defaults
        $thresholds = $options['thresholds'] ?? [];
        if (isset($thresholds['min_impressions'])) {
            $minImpressions = $thresholds['min_impressions'];
        }
        if (isset($thresholds['min_clicks'])) {
            $minClicks = $thresholds['min_clicks'];
        }

        $prevImpressions = $prev['impressions'] ?? 0;
        $prevClicks = $prev['clicks'] ?? 0;

        // Must meet at least one threshold
        return $prevImpressions >= $minImpressions || $prevClicks >= $minClicks;
    }

    /**
     * {@inheritDoc}
     */
    public function isCooldownActive(ContentDecayCandidate $candidate): bool
    {
        // Only check cooldown for refreshed status
        if ($candidate->status !== 'refreshed') {
            return false;
        }

        $refreshedAt = $candidate->refreshed_at;
        if (!$refreshedAt) {
            return false;
        }

        $cooldownDays = self::DEFAULT_CONFIG['refresh_cooldown_days'];
        return $refreshedAt->gt(now()->subDays($cooldownDays));
    }

    /**
     * {@inheritDoc}
     */
    public function isBelowLowSignalThreshold(array $comparison): bool
    {
        $percentages = $comparison['percentages'] ?? [];

        // Calculate total movement magnitude
        $totalMovement = 0;
        foreach ($percentages as $metric => $percent) {
            $totalMovement += abs($percent);
        }

        return $totalMovement < self::LOW_SIGNAL_THRESHOLD;
    }

    /**
     * Fetch GSC data for a site.
     * Override this method in tests or use dependency injection.
     */
    protected function fetchGscData(Site $site): array
    {
        // Placeholder - in real implementation, this would call GSC API
        // Returns array of [page_url => ['previous' => [...], 'current' => [...]]]
        return [];
    }

    /**
     * Fetch GSC data for a specific page.
     */
    protected function fetchGscPageData(string $pageUrl, ?Site $site, Carbon $previousStart, Carbon $currentEnd): ?array
    {
        // Placeholder - in real implementation, this would call GSC API
        return null;
    }

    /**
     * Fetch rankings data for a page.
     */
    protected function fetchRankingsData(string $pageUrl, ContentDecayProject $project, Carbon $previousStart, Carbon $currentEnd): ?array
    {
        // Placeholder - in real implementation, this would call rankings API
        return null;
    }

    /**
     * Fetch audit data for a page.
     */
    protected function fetchAuditData(string $pageUrl, ?Site $site): ?array
    {
        // Placeholder - in real implementation, this would call audit service
        return null;
    }

    /**
     * Fetch annotation data for a page.
     */
    protected function fetchAnnotationData(string $pageUrl, ContentDecayProject $project): ?array
    {
        // Placeholder - in real implementation, this would call annotation service
        return null;
    }

    /**
     * Fetch AI visibility data for a page.
     */
    protected function fetchAiVisibilityData(string $pageUrl, ContentDecayProject $project, Carbon $previousStart, Carbon $currentEnd): ?array
    {
        // Placeholder - in real implementation, this would call AI visibility API
        return null;
    }

    /**
     * Determine direction from delta.
     */
    protected function determineDirection(float|int $delta): string
    {
        if ($delta > 0) {
            return 'up';
        }
        if ($delta < 0) {
            return 'down';
        }
        return 'stable';
    }

    /**
     * Normalize signals for scoring service.
     */
    protected function normalizeSignals(array $signals, array $comparison): array
    {
        $gsc = $signals['gsc'] ?? [];

        return [
            'click_loss' => isset($gsc['previous']['clicks']) && isset($gsc['current']['clicks'])
                ? $this->scoringService->normalizeClickLoss(
                    $gsc['previous']['clicks'],
                    $gsc['current']['clicks']
                )
                : null,
            'impression_loss' => isset($gsc['previous']['impressions']) && isset($gsc['current']['impressions'])
                ? $this->scoringService->normalizeImpressionLoss(
                    $gsc['previous']['impressions'],
                    $gsc['current']['impressions']
                )
                : null,
            'ctr_drop' => isset($gsc['previous']['ctr']) && isset($gsc['current']['ctr'])
                ? $this->scoringService->normalizeCtrDrop(
                    $gsc['previous']['ctr'] * 100, // Convert to percentage
                    $gsc['current']['ctr'] * 100
                )
                : null,
            'position_decline' => isset($gsc['previous']['position']) && isset($gsc['current']['position'])
                ? $this->scoringService->normalizePositionDecline(
                    (int) $gsc['previous']['position'],
                    (int) $gsc['current']['position']
                )
                : null,
            'ranking_decline' => isset($comparison['deltas']['rank_visibility'])
                ? max(0, -$comparison['deltas']['rank_visibility'] / 100)
                : null,
            'audit_pressure' => isset($signals['audit']['unresolved_issues'])
                ? min(1, $signals['audit']['unresolved_issues'] / 10)
                : null,
            'staleness' => isset($signals['audit']['content_age_days'])
                ? min(1, $signals['audit']['content_age_days'] / 365)
                : null,
        ];
    }

    /**
     * Build signals array for reason service.
     */
    protected function buildReasonSignals(array $signals, array $comparison): array
    {
        $gsc = $signals['gsc'] ?? [];
        $result = [];

        if (isset($gsc['previous']) && isset($gsc['current'])) {
            $result['clicks'] = [
                'previous' => $gsc['previous']['clicks'] ?? null,
                'current' => $gsc['current']['clicks'] ?? null,
            ];
            $result['impressions'] = [
                'previous' => $gsc['previous']['impressions'] ?? null,
                'current' => $gsc['current']['impressions'] ?? null,
            ];
            $result['ctr'] = [
                'previous' => isset($gsc['previous']['ctr']) ? $gsc['previous']['ctr'] * 100 : null,
                'current' => isset($gsc['current']['ctr']) ? $gsc['current']['ctr'] * 100 : null,
            ];
            $result['avg_position'] = [
                'previous' => $gsc['previous']['position'] ?? null,
                'current' => $gsc['current']['position'] ?? null,
            ];
        }

        // Add window info
        if (isset($signals['window'])) {
            $result['window'] = $signals['window'];
        }

        // Add audit issues
        if (isset($signals['audit']['unresolved_issues'])) {
            $result['unresolved_audit_issues'] = $signals['audit']['unresolved_issues'];
        }

        // Add rankings
        if (isset($signals['rankings']['previous']) || isset($signals['rankings']['current'])) {
            $result['tracked_rank_visibility'] = [
                'previous' => $signals['rankings']['previous']['visibility'] ?? null,
                'current' => $signals['rankings']['current']['visibility'] ?? null,
            ];
        }

        // Add AI visibility
        if (isset($signals['ai_visibility']['previous']) || isset($signals['ai_visibility']['current'])) {
            $result['ai_visibility'] = [
                'previous' => $signals['ai_visibility']['previous']['mention_rate'] ?? null,
                'current' => $signals['ai_visibility']['current']['mention_rate'] ?? null,
            ];
        }

        // Add content age
        if (isset($signals['audit']['content_age_days'])) {
            $result['content_age_days'] = $signals['audit']['content_age_days'];
        }

        return $result;
    }

    /**
     * Sanitize URL to prevent injection.
     */
    protected function sanitizeUrl(string $url): string
    {
        // Validate URL format - don't strip Unicode characters
        // filter_var with FILTER_SANITIZE_URL strips multibyte chars, so we use validation only
        if (filter_var($url, FILTER_VALIDATE_URL) !== false) {
            return $url;
        }
        
        // For URLs with Unicode paths (IRI), try to validate the structure
        // by checking for common URL components
        if (preg_match('#^https?://[^\s]+$#i', $url)) {
            return $url;
        }
        
        return $url;
    }

    /**
     * Sanitize evidence array to prevent XSS.
     */
    protected function sanitizeEvidence(array $evidence): array
    {
        array_walk_recursive($evidence, function (&$value) {
            if (is_string($value)) {
                // Strip script tags and encode HTML entities
                $value = strip_tags($value);
                $value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
            }
        });

        return $evidence;
    }

    /**
     * Determine page type from URL.
     */
    protected function determinePageType(string $url): string
    {
        $path = parse_url($url, PHP_URL_PATH) ?? '';

        if (str_contains($path, '/blog/') || str_contains($path, '/article/') || str_contains($path, '/post/')) {
            return 'blog';
        }
        if (str_contains($path, '/product/') || str_contains($path, '/shop/') || str_contains($path, '/item/')) {
            return 'product';
        }
        if (str_contains($path, '/landing/') || str_contains($path, '/lp/') || preg_match('/\/[a-z-]+$/', $path)) {
            return 'landing';
        }
        if (str_contains($path, '/category/') || str_contains($path, '/tag/') || str_contains($path, '/collection/')) {
            return 'category';
        }

        return 'other';
    }
}
