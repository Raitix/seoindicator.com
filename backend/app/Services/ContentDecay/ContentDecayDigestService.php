<?php

namespace App\Services\ContentDecay;

use App\Mail\ContentDecayDigestEmail;
use App\Models\ContentDecayCandidate;
use App\Models\ContentDecayProject;
use App\Models\Notification;
use App\Models\User;
use App\Services\ContentDecay\Contracts\ContentDecayDigestServiceInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * Content Decay Digest Service.
 *
 * Generates weekly/monthly digest notifications summarizing:
 * - Newly detected decay candidates
 * - Recovered pages
 * - Summary statistics and recommendations
 *
 * @see TASK-36-7
 * @see SPEC-036
 */
class ContentDecayDigestService implements ContentDecayDigestServiceInterface
{
    /**
     * Default digest configuration.
     */
    private const DEFAULT_LIMIT = 5;
    private const WEEKLY_DAYS = 7;
    private const MONTHLY_DAYS = 30;

    /**
     * Generate a digest for a project.
     *
     * @param ContentDecayProject $project
     * @return array
     */
    public function generateDigest(ContentDecayProject $project): array
    {
        $startTime = microtime(true);

        Log::info('ContentDecayDigestService: Generating digest', [
            'project_id' => $project->id,
            'site_id' => $project->site_id,
        ]);

        // Check if digest is enabled
        if (!$project->digest_enabled) {
            Log::info('ContentDecayDigestService: Digest disabled for project', [
                'project_id' => $project->id,
            ]);

            return $this->formatEmptyDigest($project, 'digest_disabled');
        }

        // Get digest period
        $period = $this->getDigestPeriod($project);
        $startDate = $period['start_date'];
        $endDate = $period['end_date'];

        // Get new candidates (backlog or planned status, detected within period)
        $newCandidates = $this->getNewCandidates($project, $startDate, $endDate);

        // Get recovered candidates
        $recoveredCandidates = $this->getRecoveredCandidates($project, $startDate, $endDate);

        // Calculate summary statistics
        $summary = $this->calculateSummary($newCandidates, $recoveredCandidates);

        // Get top candidates by decay score
        $topCandidates = $this->getTopCandidates($newCandidates);

        // Generate actionable recommendations
        $recommendations = $this->generateRecommendations($newCandidates, $recoveredCandidates, $summary);

        $duration = round((microtime(true) - $startTime) * 1000, 2);

        $digest = [
            'digest_id' => 'digest-' . Str::random(12),
            'project_id' => $project->id,
            'site_id' => $project->site_id,
            'generated_at' => now()->toIso8601String(),
            'period_start' => $startDate->toDateString(),
            'period_end' => $endDate->toDateString(),
            'frequency' => $project->digest_frequency,
            'new_candidates' => $newCandidates->map(fn ($c) => $this->formatCandidate($c))->values()->toArray(),
            'recovered_candidates' => $recoveredCandidates->map(fn ($c) => $this->formatRecoveredCandidate($c))->values()->toArray(),
            'summary' => $summary,
            'top_candidates' => $topCandidates->map(fn ($c) => $this->formatCandidate($c))->values()->toArray(),
            'actionable_recommendations' => $recommendations,
            'generation_time_ms' => $duration,
        ];

        Log::info('ContentDecayDigestService: Digest generated', [
            'project_id' => $project->id,
            'new_count' => $summary['total_new'],
            'recovered_count' => $summary['total_recovered'],
            'duration_ms' => $duration,
        ]);

        return $digest;
    }

    /**
     * Send the digest notification to a user.
     *
     * @param User $user
     * @param array $digest
     * @return void
     */
    public function sendDigest(User $user, array $digest): void
    {
        Log::info('ContentDecayDigestService: Sending digest', [
            'user_id' => $user->id,
            'digest_id' => $digest['digest_id'] ?? 'unknown',
        ]);

        try {
            // Send email notification
            Mail::to($user->email)
                ->queue(new ContentDecayDigestEmail($user, $digest));

            // Create in-app notification
            $this->createInAppNotification($user, $digest);

            Log::info('ContentDecayDigestService: Digest sent successfully', [
                'user_id' => $user->id,
                'digest_id' => $digest['digest_id'] ?? 'unknown',
            ]);
        } catch (\Exception $e) {
            Log::error('ContentDecayDigestService: Failed to send digest', [
                'user_id' => $user->id,
                'digest_id' => $digest['digest_id'] ?? 'unknown',
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Check if a digest should be sent for a project.
     *
     * @param ContentDecayProject $project
     * @return bool
     */
    public function shouldSendDigest(ContentDecayProject $project): bool
    {
        // Check if digest is enabled
        if (!$project->digest_enabled) {
            Log::debug('ContentDecayDigestService: Digest disabled', [
                'project_id' => $project->id,
            ]);
            return false;
        }

        // Check if detection is enabled
        if (!$project->detection_enabled) {
            Log::debug('ContentDecayDigestService: Detection disabled', [
                'project_id' => $project->id,
            ]);
            return false;
        }

        // Check valid digest frequency
        if (!in_array($project->digest_frequency, ['weekly', 'monthly'])) {
            Log::debug('ContentDecayDigestService: Invalid digest frequency', [
                'project_id' => $project->id,
                'frequency' => $project->digest_frequency,
            ]);
            return false;
        }

        // Check if digest is due
        $lastSent = $project->last_digest_sent_at;
        $thresholdDays = $project->digest_frequency === 'weekly' ? self::WEEKLY_DAYS : self::MONTHLY_DAYS;

        if ($lastSent === null) {
            Log::debug('ContentDecayDigestService: First digest, should send', [
                'project_id' => $project->id,
            ]);
            return true;
        }

        $daysSinceLastDigest = now()->diffInDays($lastSent);
        $shouldSend = $daysSinceLastDigest >= $thresholdDays;

        Log::debug('ContentDecayDigestService: Digest due check', [
            'project_id' => $project->id,
            'days_since_last' => $daysSinceLastDigest,
            'threshold' => $thresholdDays,
            'should_send' => $shouldSend,
        ]);

        return $shouldSend;
    }

    /**
     * Get the date range for the digest period.
     *
     * @param ContentDecayProject $project
     * @return array
     */
    public function getDigestPeriod(ContentDecayProject $project): array
    {
        $days = $project->digest_frequency === 'monthly' ? self::MONTHLY_DAYS : self::WEEKLY_DAYS;

        return [
            'start_date' => now()->subDays($days)->startOfDay(),
            'end_date' => now()->endOfDay(),
        ];
    }

    /**
     * Get new decay candidates for the period.
     *
     * @param ContentDecayProject $project
     * @param mixed $startDate
     * @param mixed $endDate
     * @param array $options
     * @return Collection
     */
    public function getNewCandidates(ContentDecayProject $project, $startDate, $endDate, array $options = []): Collection
    {
        $minScore = $options['min_decay_score'] ?? $project->min_decay_score ?? 0;
        $limit = $options['limit'] ?? 100;

        return ContentDecayCandidate::query()
            ->where('project_id', $project->id)
            ->whereIn('status', ['backlog', 'planned', 'in_progress'])
            ->where('decay_score', '>=', $minScore)
            ->whereBetween('first_detected_at', [$startDate, $endDate])
            ->orderByDesc('decay_score')
            ->limit($limit)
            ->get();
    }

    /**
     * Get recovered candidates for the period.
     *
     * @param ContentDecayProject $project
     * @param mixed $startDate
     * @param mixed $endDate
     * @param array $options
     * @return Collection
     */
    public function getRecoveredCandidates(ContentDecayProject $project, $startDate, $endDate, array $options = []): Collection
    {
        $limit = $options['limit'] ?? 50;

        return ContentDecayCandidate::query()
            ->where('project_id', $project->id)
            ->where('status', 'recovered')
            ->whereBetween('recovered_at', [$startDate, $endDate])
            ->orderByDesc('recovered_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Format the digest payload.
     *
     * @param ContentDecayProject $project
     * @param Collection $newCandidates
     * @param Collection $recoveredCandidates
     * @param array $options
     * @return array
     */
    public function formatDigestPayload(ContentDecayProject $project, $newCandidates, $recoveredCandidates, array $options = []): array
    {
        $summary = $this->calculateSummary($newCandidates, $recoveredCandidates);
        $topCandidates = $this->getTopCandidates($newCandidates);
        $recommendations = $this->generateRecommendations($newCandidates, $recoveredCandidates, $summary);
        $period = $this->getDigestPeriod($project);

        return [
            'digest_id' => 'digest-' . Str::random(12),
            'project_id' => $project->id,
            'site_id' => $project->site_id,
            'generated_at' => now()->toIso8601String(),
            'period_start' => $period['start_date']->toDateString(),
            'period_end' => $period['end_date']->toDateString(),
            'frequency' => $project->digest_frequency,
            'new_candidates' => $newCandidates->map(fn ($c) => $this->formatCandidate($c))->values()->toArray(),
            'recovered_candidates' => $recoveredCandidates->map(fn ($c) => $this->formatRecoveredCandidate($c))->values()->toArray(),
            'summary' => $summary,
            'top_candidates' => $topCandidates->map(fn ($c) => $this->formatCandidate($c))->values()->toArray(),
            'actionable_recommendations' => $recommendations,
        ];
    }

    // ==========================================
    // Protected Helper Methods
    // ==========================================

    /**
     * Calculate summary statistics.
     *
     * @param Collection $newCandidates
     * @param Collection $recoveredCandidates
     * @return array
     */
    protected function calculateSummary(Collection $newCandidates, Collection $recoveredCandidates): array
    {
        $severityBreakdown = [
            'critical' => 0,
            'high' => 0,
            'medium' => 0,
            'low' => 0,
        ];

        $pageTypeBreakdown = [];

        foreach ($newCandidates as $candidate) {
            $severity = $candidate->severity ?? 'medium';
            if (isset($severityBreakdown[$severity])) {
                $severityBreakdown[$severity]++;
            }

            $pageType = $candidate->page_type ?? 'other';
            $pageTypeBreakdown[$pageType] = ($pageTypeBreakdown[$pageType] ?? 0) + 1;
        }

        $totalLostClicks = (int) $newCandidates->sum('lost_click_estimate');
        $totalUpside = (int) $newCandidates->sum('recovery_upside_estimate');

        return [
            'total_new' => $newCandidates->count(),
            'total_recovered' => $recoveredCandidates->count(),
            'critical_count' => $severityBreakdown['critical'],
            'high_count' => $severityBreakdown['high'],
            'medium_count' => $severityBreakdown['medium'],
            'low_count' => $severityBreakdown['low'],
            'total_lost_clicks' => $totalLostClicks,
            'estimated_upside_clicks' => $totalUpside,
            'severity_breakdown' => $severityBreakdown,
            'page_type_breakdown' => $pageTypeBreakdown,
        ];
    }

    /**
     * Get top candidates by decay score.
     *
     * @param Collection $candidates
     * @param int $limit
     * @return Collection
     */
    protected function getTopCandidates(Collection $candidates, int $limit = self::DEFAULT_LIMIT): Collection
    {
        return $candidates->sortByDesc('decay_score')->take($limit);
    }

    /**
     * Generate actionable recommendations.
     *
     * @param Collection $newCandidates
     * @param Collection $recoveredCandidates
     * @param array $summary
     * @return array
     */
    protected function generateRecommendations(Collection $newCandidates, Collection $recoveredCandidates, array $summary): array
    {
        $recommendations = [];

        // Critical pages recommendation
        if ($summary['critical_count'] > 0) {
            $recommendations[] = sprintf(
                'Focus on %d critical page(s) with highest recovery potential',
                $summary['critical_count']
            );
        }

        // High severity recommendation
        if ($summary['high_count'] > 0) {
            $recommendations[] = sprintf(
                'Refresh content on %d high-severity page(s)',
                $summary['high_count']
            );
        }

        // Blog pages recommendation
        $blogCount = $summary['page_type_breakdown']['blog'] ?? 0;
        if ($blogCount > 0) {
            $recommendations[] = sprintf(
                'Update %d blog post(s) with fresh content and keywords',
                $blogCount
            );
        }

        // Recovery success recommendation
        if ($summary['total_recovered'] > 0) {
            $recommendations[] = sprintf(
                '%d page(s) recovered this period - apply similar strategies to decaying pages',
                $summary['total_recovered']
            );
        }

        // Lost clicks recommendation
        if ($summary['total_lost_clicks'] > 100) {
            $recommendations[] = sprintf(
                'Estimated %d clicks lost - prioritize high-upside pages first',
                number_format($summary['total_lost_clicks'])
            );
        }

        // Default recommendation if none generated
        if (empty($recommendations)) {
            $recommendations[] = 'Review decay candidates and plan content refresh strategy';
        }

        return $recommendations;
    }

    /**
     * Format a candidate for digest output.
     *
     * @param ContentDecayCandidate $candidate
     * @return array
     */
    protected function formatCandidate(ContentDecayCandidate $candidate): array
    {
        return [
            'id' => $candidate->id,
            'page_url' => $this->truncateUrl($candidate->page_url),
            'page_title' => $candidate->page_title ?? 'Untitled',
            'page_type' => $candidate->page_type ?? 'other',
            'decay_score' => round((float) $candidate->decay_score, 2),
            'severity' => $candidate->severity ?? 'medium',
            'reason_codes' => $candidate->reason_codes ?? [],
            'primary_reason' => $candidate->primary_reason_code ?? 'unknown',
            'lost_clicks' => (int) ($candidate->lost_click_estimate ?? 0),
            'recovery_upside' => (int) ($candidate->recovery_upside_estimate ?? 0),
            'first_detected_at' => $candidate->first_detected_at?->toIso8601String(),
            'status' => $candidate->status,
        ];
    }

    /**
     * Format a recovered candidate for digest output.
     *
     * @param ContentDecayCandidate $candidate
     * @return array
     */
    protected function formatRecoveredCandidate(ContentDecayCandidate $candidate): array
    {
        return [
            'id' => $candidate->id,
            'page_url' => $this->truncateUrl($candidate->page_url),
            'page_title' => $candidate->page_title ?? 'Untitled',
            'recovery_upside' => (int) ($candidate->recovery_upside_estimate ?? 0),
            'recovered_at' => $candidate->recovered_at?->toIso8601String(),
            'previous_severity' => $candidate->severity ?? 'medium',
        ];
    }

    /**
     * Truncate a URL for display.
     *
     * @param string $url
     * @param int $maxLength
     * @return string
     */
    protected function truncateUrl(string $url, int $maxLength = 80): string
    {
        if (strlen($url) <= $maxLength) {
            return $url;
        }

        return substr($url, 0, $maxLength - 3) . '...';
    }

    /**
     * Create an in-app notification for the digest.
     *
     * @param User $user
     * @param array $digest
     * @return Notification
     */
    protected function createInAppNotification(User $user, array $digest): Notification
    {
        return Notification::create([
            'user_id' => $user->id,
            'type' => 'content_decay_digest',
            'keyword_id' => null,
            'data' => [
                'digest_id' => $digest['digest_id'] ?? null,
                'project_id' => $digest['project_id'] ?? null,
                'new_count' => $digest['summary']['total_new'] ?? 0,
                'recovered_count' => $digest['summary']['total_recovered'] ?? 0,
                'critical_count' => $digest['summary']['critical_count'] ?? 0,
            ],
        ]);
    }

    /**
     * Format an empty digest response.
     *
     * @param ContentDecayProject $project
     * @param string $reason
     * @return array
     */
    protected function formatEmptyDigest(ContentDecayProject $project, string $reason): array
    {
        $period = $this->getDigestPeriod($project);

        return [
            'digest_id' => null,
            'project_id' => $project->id,
            'site_id' => $project->site_id,
            'generated_at' => now()->toIso8601String(),
            'period_start' => $period['start_date']->toDateString(),
            'period_end' => $period['end_date']->toDateString(),
            'frequency' => $project->digest_frequency,
            'new_candidates' => [],
            'recovered_candidates' => [],
            'summary' => [
                'total_new' => 0,
                'total_recovered' => 0,
                'critical_count' => 0,
                'high_count' => 0,
                'medium_count' => 0,
                'low_count' => 0,
                'total_lost_clicks' => 0,
                'estimated_upside_clicks' => 0,
                'severity_breakdown' => [
                    'critical' => 0,
                    'high' => 0,
                    'medium' => 0,
                    'low' => 0,
                ],
                'page_type_breakdown' => [],
            ],
            'top_candidates' => [],
            'actionable_recommendations' => [],
            'reason' => $reason,
        ];
    }
}
