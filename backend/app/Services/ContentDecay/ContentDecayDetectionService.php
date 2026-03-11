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
     * Default configuration.
     */
    protected array $config = [
        'window_days' => 28,
        'min_impressions_threshold' => 100,
        'min_clicks_threshold' => 10,
        'refresh_cooldown_days' => 14,
        'low_signal_threshold' => 5.0,
        'min_decay_score_for_candidate' => 20.0,
    ];

    /**
     * Last used window days for tracking.
     */
    protected int $lastUsedWindowDays = 28;

    /**
     * GSC data for current detection run.
     */
    protected array $gscDataCache = [];

    /**
     * Create a new ContentDecayDetectionService instance.
     */
    public function __construct(
        protected ContentDecayScoringServiceInterface $scoringService,
        protected ContentDecayReasonServiceInterface $reasonService,
        protected ContentRefreshPlannerServiceInterface $plannerService
    ) {}

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
     * Get the last used window days.
     */
    public function getLastUsedWindowDays(): int
    {
        return $this->lastUsedWindowDays;
    }

    /**
     * {@inheritDoc}
     */
    public function detectForSite(Site $site): array
    {
        $startTime = microtime(true);

        Log::info('Starting content decay detection', ['site_id' => $site->id]);

        // Initialize result
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
            // Get or create project for the site
            $project = $this->getOrCreateProject($site);

            // Check if detection is enabled
            if (!$project->detection_enabled) {
                Log::info('Detection disabled for project', ['project_id' => $project->id]);
                $result['duration_ms'] = (int) ((microtime(true) - $startTime) * 1000);
                return $result;
            }

            // Fetch GSC data
            $gscData = $this->fetchGscData($site);

            if (empty($gscData)) {
                Log::info('No GSC data found for site', ['site_id' => $site->id]);
                $result['duration_ms'] = (int) ((microtime(true) - $startTime) * 1000);
                return $result;
            }

            // Get project settings
            $settings = $project->settings_json ?? [];
            $this->lastUsedWindowDays = $settings['window_days'] ?? $this->config['window_days'];

            // Process each page
            foreach ($gscData as $pageUrl => $pageGscData) {
                $result['processed_pages']++;

                // Check eligibility
                if (!$this->isPageEligible($pageGscData, $settings)) {
                    continue;
                }

                $result['eligible_pages']++;

                // Check for existing candidate in cooldown
                $existingCandidate = ContentDecayCandidate::where('project_id', $project->id)
                    ->where('page_url', $pageUrl)
                    ->first();

                if ($existingCandidate && $existingCandidate->status === 'ignored') {
                    // Preserve ignored candidates
                    continue;
                }

                if ($existingCandidate && $this->isCooldownActive($existingCandidate)) {
                    // Skip due to cooldown
                    continue;
                }

                // Gather signals
                $signals = $this->gatherSignals($pageUrl, $project);
                $signals['gsc'] = $pageGscData;

                // Compare windows
                $comparison = $this->compareWindows($signals);

                // Check for low signal threshold
                if ($this->isBelowLowSignalThreshold($comparison)) {
                    continue;
                }

                // Calculate decay score
                $normalizedSignals = $this->normalizeSignals($signals, $comparison);
                $scoreResult = $this->scoringService->calculateDecayScore($normalizedSignals);

                // Check if decay score meets minimum threshold
                if ($scoreResult['decay_score'] < ($settings['min_decay_score'] ?? $this->config['min_decay_score_for_candidate'])) {
                    // If existing candidate with no decay, mark as recovered
                    if ($existingCandidate && $scoreResult['decay_score'] < 10) {
                        $this->archiveCandidate($existingCandidate, 'recovered');
                        $result['candidates_archived']++;
                    }
                    continue;
                }

                // Assign reason codes
                $reasonResult = $this->reasonService->assignReasonCodes($this->buildReasonSignals($signals, $comparison));

                // Create/update candidate
                $detectionData = [
                    'project_id' => $project->id,
                    'page_url' => $pageUrl,
                    'decay_score' => $scoreResult['decay_score'],
                    'severity' => $scoreResult['severity'],
                    'detection_confidence' => $scoreResult['detection_confidence'],
                    'reason_codes' => $reasonResult['reason_codes'] ?? [],
                    'primary_reason_code' => $reasonResult['primary_reason_code'] ?? null,
                    'signal_sources' => $this->getSignalSources($signals),
                    'evidence_json' => $reasonResult['evidence'] ?? [],
                ];

                $candidate = $this->upsertCandidate($detectionData);

                if ($candidate) {
                    if ($candidate->wasRecentlyCreated) {
                        $result['candidates_created']++;
                    } else {
                        $result['candidates_updated']++;
                    }

                    // Generate recommendations
                    $recommendations = $this->plannerService->generateRecommendations($candidate);

                    $result['pages'][] = [
                        'page_url' => $pageUrl,
                        'decay_score' => $candidate->decay_score,
                        'severity' => $candidate->severity,
                        'status' => $candidate->status,
                        'reason_codes' => $candidate->reason_codes ?? [],
                        'recommendation' => $recommendations['primary_recommendation'] ?? 'No recommendation',
                    ];
                }
            }

            // Update project last scanned timestamp
            $project->update(['last_scanned_at' => now()]);

        } catch (\Exception $e) {
            Log::error('Content decay detection failed', [
                'site_id' => $site->id,
                'error' => $e->getMessage(),
            ]);
            $result['error'] = $e->getMessage();
            $result['errors']++;
        }

        $result['duration_ms'] = (int) ((microtime(true) - $startTime) * 1000);

        Log::info('Content decay detection completed', [
            'site_id' => $site->id,
            'result' => $result,
        ]);

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function detectForProject(ContentDecayProject $project): array
    {
        $startTime = microtime(true);

        Log::info('Starting content decay detection for project', ['project_id' => $project->id]);

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
            Log::info('Detection disabled for project', ['project_id' => $project->id]);
            $result['duration_ms'] = (int) ((microtime(true) - $startTime) * 1000);
            return $result;
        }

        try {
            $site = $project->site;

            if (!$site) {
                throw new \RuntimeException('Project has no associated site');
            }

            // Get project settings
            $settings = $project->settings_json ?? [];
            $this->lastUsedWindowDays = $settings['window_days'] ?? $this->config['window_days'];

            // Fetch GSC data
            $gscData = $this->fetchGscData($site);

            if (empty($gscData)) {
                Log::info('No GSC data found for project', ['project_id' => $project->id]);
                $result['duration_ms'] = (int) ((microtime(true) - $startTime) * 1000);
                return $result;
            }

            // Process each page (same logic as detectForSite)
            foreach ($gscData as $pageUrl => $pageGscData) {
                $result['processed_pages']++;

                if (!$this->isPageEligible($pageGscData, $settings)) {
                    continue;
                }

                $result['eligible_pages']++;

                // ... same processing as detectForSite ...
                $existingCandidate = ContentDecayCandidate::where('project_id', $project->id)
                    ->where('page_url', $pageUrl)
                    ->first();

                if ($existingCandidate && $existingCandidate->status === 'ignored') {
                    continue;
                }

                if ($existingCandidate && $this->isCooldownActive($existingCandidate)) {
                    continue;
                }

                $signals = $this->gatherSignals($pageUrl, $project);
                $signals['gsc'] = $pageGscData;

                $comparison = $this->compareWindows($signals);

                if ($this->isBelowLowSignalThreshold($comparison)) {
                    continue;
                }

                $normalizedSignals = $this->normalizeSignals($signals, $comparison);
                $scoreResult = $this->scoringService->calculateDecayScore($normalizedSignals);

                if ($scoreResult['decay_score'] < ($settings['min_decay_score'] ?? $this->config['min_decay_score_for_candidate'])) {
                    if ($existingCandidate && $scoreResult['decay_score'] < 10) {
                        $this->archiveCandidate($existingCandidate, 'recovered');
                        $result['candidates_archived']++;
                    }
                    continue;
                }

                $reasonResult = $this->reasonService->assignReasonCodes($this->buildReasonSignals($signals, $comparison));

                $detectionData = [
                    'project_id' => $project->id,
                    'page_url' => $pageUrl,
                    'decay_score' => $scoreResult['decay_score'],
                    'severity' => $scoreResult['severity'],
                    'detection_confidence' => $scoreResult['detection_confidence'],
                    'reason_codes' => $reasonResult['reason_codes'] ?? [],
                    'primary_reason_code' => $reasonResult['primary_reason_code'] ?? null,
                    'signal_sources' => $this->getSignalSources($signals),
                    'evidence_json' => $reasonResult['evidence'] ?? [],
                ];

                $candidate = $this->upsertCandidate($detectionData);

                if ($candidate) {
                    if ($candidate->wasRecentlyCreated) {
                        $result['candidates_created']++;
                    } else {
                        $result['candidates_updated']++;
                    }

                    $recommendations = $this->plannerService->generateRecommendations($candidate);

                    $result['pages'][] = [
                        'page_url' => $pageUrl,
                        'decay_score' => $candidate->decay_score,
                        'severity' => $candidate->severity,
                        'status' => $candidate->status,
                        'reason_codes' => $candidate->reason_codes ?? [],
                        'recommendation' => $recommendations['primary_recommendation'] ?? 'No recommendation',
                    ];
                }
            }

            // Update project timestamps
            $project->update([
                'last_scanned_at' => now(),
            ]);

        } catch (\Exception $e) {
            Log::error('Content decay detection failed for project', [
                'project_id' => $project->id,
                'error' => $e->getMessage(),
            ]);
            $result['error'] = $e->getMessage();
            $result['errors']++;
        }

        $result['duration_ms'] = (int) ((microtime(true) - $startTime) * 1000);

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function gatherSignals(string $pageUrl, ContentDecayProject $project): array
    {
        $windowDays = $project->settings_json['window_days'] ?? $this->config['window_days'];

        // Calculate window dates
        $currentEnd = Carbon::now()->subDay()->endOfDay();
        $currentStart = Carbon::now()->subDays($windowDays)->startOfDay();
        $previousEnd = Carbon::now()->subDays($windowDays + 1)->endOfDay();
        $previousStart = Carbon::now()->subDays($windowDays * 2)->startOfDay();

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

        // Note: GSC data will be populated by the caller
        // Other signals would be gathered from their respective services

        return $signals;
    }

    /**
     * {@inheritDoc}
     */
    public function compareWindows(array $signals): array
    {
        $gsc = $signals['gsc'] ?? [];

        $previous = $gsc['previous'] ?? [];
        $current = $gsc['current'] ?? [];

        $deltas = [];
        $percentages = [];
        $direction = [];

        // Clicks
        $prevClicks = $previous['clicks'] ?? 0;
        $currClicks = $current['clicks'] ?? 0;
        $deltas['clicks'] = $currClicks - $prevClicks;
        $percentages['clicks'] = $prevClicks > 0 ? round((($currClicks - $prevClicks) / $prevClicks) * 100, 2) : 0;
        $direction['clicks'] = $this->determineDirection($deltas['clicks']);

        // Impressions
        $prevImpressions = $previous['impressions'] ?? 0;
        $currImpressions = $current['impressions'] ?? 0;
        $deltas['impressions'] = $currImpressions - $prevImpressions;
        $percentages['impressions'] = $prevImpressions > 0 ? round((($currImpressions - $prevImpressions) / $prevImpressions) * 100, 2) : 0;
        $direction['impressions'] = $this->determineDirection($deltas['impressions']);

        // CTR
        $prevCtr = $previous['ctr'] ?? 0;
        $currCtr = $current['ctr'] ?? 0;
        $deltas['ctr'] = $currCtr - $prevCtr;
        $percentages['ctr'] = $prevCtr > 0 ? round((($currCtr - $prevCtr) / $prevCtr) * 100, 2) : 0;
        $direction['ctr'] = $this->determineDirection($deltas['ctr']);

        // Position (higher is worse, so reverse the direction logic)
        $prevPosition = $previous['position'] ?? 0;
        $currPosition = $current['position'] ?? 0;
        $deltas['position'] = $currPosition - $prevPosition;
        $percentages['position'] = $prevPosition > 0 ? round((($currPosition - $prevPosition) / $prevPosition) * 100, 2) : 0;
        // For position, up means worse (higher number), down means better (lower number)
        $direction['position'] = $deltas['position'] > 0 ? 'down' : ($deltas['position'] < 0 ? 'up' : 'stable');

        return [
            'deltas' => $deltas,
            'percentages' => $percentages,
            'direction' => $direction,
            'compared_at' => now()->toIso8601String(),
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function upsertCandidate(array $detectionData): ?ContentDecayCandidate
    {
        $projectId = $detectionData['project_id'];
        $pageUrl = $detectionData['page_url'];

        // Sanitize page URL (prevent SQL injection)
        $pageUrl = filter_var($pageUrl, FILTER_SANITIZE_URL);

        // Calculate URL hash
        $pageUrlHash = hash('sha256', $pageUrl);

        // Sanitize evidence JSON (prevent XSS)
        if (isset($detectionData['evidence_json'])) {
            $detectionData['evidence_json'] = $this->sanitizeEvidence($detectionData['evidence_json']);
        }

        return DB::transaction(function () use ($projectId, $pageUrl, $pageUrlHash, $detectionData) {
            // Find existing candidate
            $candidate = ContentDecayCandidate::where('project_id', $projectId)
                ->where('page_url_hash', $pageUrlHash)
                ->first();

            if ($candidate) {
                // Update existing candidate
                $updateData = [
                    'decay_score' => $detectionData['decay_score'],
                    'severity' => $detectionData['severity'],
                    'detection_confidence' => $detectionData['detection_confidence'],
                    'reason_codes' => $detectionData['reason_codes'],
                    'primary_reason_code' => $detectionData['primary_reason_code'],
                    'signal_sources' => $detectionData['signal_sources'],
                    'evidence_json' => $detectionData['evidence_json'],
                    'last_detected_at' => now(),
                ];

                // Don't override status for certain states
                $preserveStatuses = ['planned', 'in_progress', 'ignored'];
                if (!in_array($candidate->status, $preserveStatuses)) {
                    $updateData['status'] = 'backlog';
                }

                $candidate->update($updateData);
                $candidate->wasRecentlyCreated = false;
            } else {
                // Create new candidate
                $candidate = ContentDecayCandidate::create([
                    'project_id' => $projectId,
                    'page_url' => $pageUrl,
                    'page_url_hash' => $pageUrlHash,
                    'page_type' => $this->determinePageType($pageUrl),
                    'severity' => $detectionData['severity'],
                    'decay_score' => $detectionData['decay_score'],
                    'detection_confidence' => $detectionData['detection_confidence'],
                    'primary_reason_code' => $detectionData['primary_reason_code'],
                    'reason_codes' => $detectionData['reason_codes'],
                    'signal_sources' => $detectionData['signal_sources'],
                    'evidence_json' => $detectionData['evidence_json'],
                    'status' => 'backlog',
                    'first_detected_at' => now(),
                    'last_detected_at' => now(),
                ]);
                $candidate->wasRecentlyCreated = true;
            }

            // Create signal record
            ContentDecaySignal::create([
                'candidate_id' => $candidate->id,
                'previous_start' => now()->subDays(56),
                'previous_end' => now()->subDays(29),
                'current_start' => now()->subDays(28),
                'current_end' => now()->subDay(),
                'previous_clicks' => $detectionData['evidence_json']['clicks']['previous'] ?? 0,
                'current_clicks' => $detectionData['evidence_json']['clicks']['current'] ?? 0,
                'created_at' => now(),
            ]);

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

        // Must have both windows
        if ($previous === null || $current === null) {
            return false;
        }

        $minImpressions = $options['min_impressions_threshold'] ?? $this->config['min_impressions_threshold'];
        $minClicks = $options['min_clicks_threshold'] ?? $this->config['min_clicks_threshold'];

        $prevImpressions = $previous['impressions'] ?? 0;
        $prevClicks = $previous['clicks'] ?? 0;

        // Must meet threshold (impressions OR clicks)
        return $prevImpressions >= $minImpressions || $prevClicks >= $minClicks;
    }

    /**
     * {@inheritDoc}
     */
    public function isCooldownActive(ContentDecayCandidate $candidate): bool
    {
        // Cooldown applies to refreshed candidates
        if ($candidate->status !== 'refreshed') {
            return false;
        }

        $refreshedAt = $candidate->refreshed_at;
        if (!$refreshedAt) {
            return false;
        }

        $cooldownDays = $this->config['refresh_cooldown_days'];

        return $refreshedAt->diffInDays(now()) < $cooldownDays;
    }

    /**
     * {@inheritDoc}
     */
    public function isBelowLowSignalThreshold(array $comparison): bool
    {
        $deltas = $comparison['deltas'] ?? [];
        $percentages = $comparison['percentages'] ?? [];

        // Calculate total signal magnitude
        $totalPercentChange = abs($percentages['clicks'] ?? 0) + abs($percentages['impressions'] ?? 0);
        $totalDelta = abs($deltas['clicks'] ?? 0) + abs($deltas['impressions'] ?? 0);

        // Below threshold if both percentage and absolute changes are very small
        return $totalPercentChange < $this->config['low_signal_threshold'] && $totalDelta < 10;
    }

    /**
     * Fetch GSC data for a site.
     * This method should be overridden/mocked in tests.
     */
    protected function fetchGscData(Site $site): array
    {
        // This is a placeholder - real implementation would call GSC API
        // Tests will mock this method
        return [];
    }

    /**
     * Get or create project for a site.
     */
    protected function getOrCreateProject(Site $site): ContentDecayProject
    {
        $project = ContentDecayProject::where('site_id', $site->id)->first();

        if (!$project) {
            $project = ContentDecayProject::create([
                'user_id' => $site->user_id,
                'site_id' => $site->id,
                'detection_enabled' => true,
                'window_days' => $this->config['window_days'],
                'min_clicks_threshold' => $this->config['min_clicks_threshold'],
                'min_impressions_threshold' => $this->config['min_impressions_threshold'],
            ]);
        }

        return $project;
    }

    /**
     * Determine direction based on delta.
     */
    protected function determineDirection(float|int $delta): string
    {
        if ($delta > 0) {
            return 'up';
        } elseif ($delta < 0) {
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
        $previous = $gsc['previous'] ?? [];
        $current = $gsc['current'] ?? [];

        return [
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
                (int) ($previous['position'] ?? 0),
                (int) ($current['position'] ?? 0)
            ),
            'ranking_decline' => null, // Optional
            'audit_pressure' => null, // Optional
            'staleness' => null, // Optional
        ];
    }

    /**
     * Build signals array for reason service.
     */
    protected function buildReasonSignals(array $signals, array $comparison): array
    {
        $gsc = $signals['gsc'] ?? [];
        $previous = $gsc['previous'] ?? [];
        $current = $gsc['current'] ?? [];

        return [
            'clicks' => [
                'previous' => $previous['clicks'] ?? null,
                'current' => $current['clicks'] ?? null,
            ],
            'impressions' => [
                'previous' => $previous['impressions'] ?? null,
                'current' => $current['impressions'] ?? null,
            ],
            'ctr' => [
                'previous' => isset($previous['ctr']) ? $previous['ctr'] * 100 : null,
                'current' => isset($current['ctr']) ? $current['ctr'] * 100 : null,
            ],
            'avg_position' => [
                'previous' => $previous['position'] ?? null,
                'current' => $current['position'] ?? null,
            ],
            'window' => $signals['window'] ?? [],
        ];
    }

    /**
     * Get list of available signal sources.
     */
    protected function getSignalSources(array $signals): array
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
     * Determine page type from URL.
     */
    protected function determinePageType(string $url): string
    {
        $path = parse_url($url, PHP_URL_PATH) ?? '';

        if (str_contains($path, '/blog/') || str_contains($path, '/article/')) {
            return 'blog';
        }
        if (str_contains($path, '/product/') || str_contains($path, '/products/')) {
            return 'product';
        }
        if (preg_match('/\/(landing|lp)\//i', $path)) {
            return 'landing';
        }
        if (str_contains($path, '/category/') || str_contains($path, '/categories/')) {
            return 'category';
        }

        return 'other';
    }

    /**
     * Sanitize evidence JSON to prevent XSS.
     */
    protected function sanitizeEvidence(array $evidence): array
    {
        array_walk_recursive($evidence, function (&$value) {
            if (is_string($value)) {
                // Strip potential XSS vectors
                $value = htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            }
        });

        return $evidence;
    }

    /**
     * Archive a candidate (mark as recovered).
     */
    protected function archiveCandidate(ContentDecayCandidate $candidate, string $reason = 'recovered'): void
    {
        $candidate->update([
            'status' => $reason,
            'recovered_at' => $reason === 'recovered' ? now() : null,
        ]);
    }
}
