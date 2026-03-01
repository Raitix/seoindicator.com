<?php

namespace App\Services;

use App\Models\AnalysisJob;
use App\Models\AnalysisJobResult;
use App\Models\Site;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * AnalysisJobService - Core operations for SEO analysis jobs.
 *
 * Manages the lifecycle of analysis jobs including creation,
 * cancellation, and status transitions.
 * 
 * Rate limiting is handled by AnalysisRateLimiterService (TASK-19-14).
 */
class AnalysisJobService
{
    protected ?AnalysisRateLimiterService $rateLimiter = null;

    /**
     * Set the rate limiter service (for dependency injection).
     *
     * @param AnalysisRateLimiterService $rateLimiter
     */
    public function setRateLimiter(AnalysisRateLimiterService $rateLimiter): void
    {
        $this->rateLimiter = $rateLimiter;
    }

    /**
     * Get the rate limiter service.
     *
     * @return AnalysisRateLimiterService
     */
    protected function getRateLimiter(): AnalysisRateLimiterService
    {
        if ($this->rateLimiter === null) {
            $this->rateLimiter = app(AnalysisRateLimiterService::class);
        }
        return $this->rateLimiter;
    }

    /**
     * Available indicator definitions.
     */
    public const INDICATORS = [
        'meta_tags' => [
            'name' => 'Meta Tags',
            'max_score' => 100.00,
        ],
        'headings' => [
            'name' => 'Headings Structure',
            'max_score' => 100.00,
        ],
        'images' => [
            'name' => 'Images Optimization',
            'max_score' => 100.00,
        ],
        'links' => [
            'name' => 'Links Analysis',
            'max_score' => 100.00,
        ],
        'performance' => [
            'name' => 'Performance',
            'max_score' => 100.00,
        ],
        'mobile' => [
            'name' => 'Mobile Friendliness',
            'max_score' => 100.00,
        ],
        'security' => [
            'name' => 'Security',
            'max_score' => 100.00,
        ],
        'structured_data' => [
            'name' => 'Structured Data',
            'max_score' => 100.00,
        ],
        'content_quality' => [
            'name' => 'Content Quality',
            'max_score' => 100.00,
        ],
        'url_structure' => [
            'name' => 'URL Structure',
            'max_score' => 100.00,
        ],
        'canonicalization' => [
            'name' => 'Canonicalization',
            'max_score' => 100.00,
        ],
        'robots_txt' => [
            'name' => 'Robots.txt',
            'max_score' => 100.00,
        ],
        'sitemap' => [
            'name' => 'XML Sitemap',
            'max_score' => 100.00,
        ],
        'internal_linking' => [
            'name' => 'Internal Linking',
            'max_score' => 100.00,
        ],
        'external_links' => [
            'name' => 'External Links',
            'max_score' => 100.00,
        ],
        'social_tags' => [
            'name' => 'Social Tags',
            'max_score' => 100.00,
        ],
        'crawlability' => [
            'name' => 'Crawlability',
            'max_score' => 100.00,
        ],
    ];

    /**
     * Maximum concurrent jobs per user.
     */
    public const MAX_CONCURRENT_JOBS_PER_USER = 2;

    /**
     * Maximum jobs per user per hour.
     */
    public const MAX_JOBS_PER_USER_PER_HOUR = 10;

    /**
     * Maximum jobs per site per hour.
     */
    public const MAX_JOBS_PER_SITE_PER_HOUR = 5;

    /**
     * Create a new analysis job.
     *
     * @param int $userId The user creating the job
     * @param int $siteId The site to analyze
     * @param array $indicators List of indicator slugs to analyze
     * @return AnalysisJob
     * @throws RuntimeException If validation fails or rate limit exceeded
     */
    public function createJob(int $userId, int $siteId, array $indicators): AnalysisJob
    {
        // Verify site exists and belongs to user
        $site = Site::where('id', $siteId)
            ->where('user_id', $userId)
            ->first();

        if (!$site) {
            throw new RuntimeException('Site not found or access denied.');
        }

        // Check rate limits
        $this->checkRateLimits($userId, $siteId);

        // Validate indicators
        $validIndicators = $this->validateIndicators($indicators);

        if (empty($validIndicators)) {
            throw new RuntimeException('No valid indicators provided.');
        }

        // Create job with results atomically
        return DB::transaction(function () use ($userId, $siteId, $validIndicators) {
            // Create the job
            $job = AnalysisJob::create([
                'user_id' => $userId,
                'site_id' => $siteId,
                'status' => AnalysisJob::STATUS_PENDING,
                'total_indicators' => count($validIndicators),
                'completed_indicators' => 0,
                'failed_indicators' => 0,
                'progress_percent' => 0,
            ]);

            // Create pending results for each indicator
            foreach ($validIndicators as $slug) {
                $indicator = self::INDICATORS[$slug];
                AnalysisJobResult::create([
                    'job_id' => $job->id,
                    'indicator_slug' => $slug,
                    'indicator_name' => $indicator['name'],
                    'status' => AnalysisJobResult::STATUS_PENDING,
                ]);
            }

            Log::info('Analysis job created', [
                'job_id' => $job->id,
                'user_id' => $userId,
                'site_id' => $siteId,
                'indicators' => count($validIndicators),
            ]);

            return $job;
        });
    }

    /**
     * Get a job by ID with results loaded.
     *
     * @param int $jobId
     * @param int|null $userId Optional: verify job belongs to user
     * @return AnalysisJob|null
     */
    public function getJob(int $jobId, ?int $userId = null): ?AnalysisJob
    {
        $query = AnalysisJob::with('results');

        if ($userId !== null) {
            $query->where('user_id', $userId);
        }

        return $query->find($jobId);
    }

    /**
     * Get job progress data for API responses.
     *
     * @param int $jobId
     * @param int|null $userId Optional: verify job belongs to user
     * @return array|null
     */
    public function getJobProgress(int $jobId, ?int $userId = null): ?array
    {
        $job = $this->getJob($jobId, $userId);

        if (!$job) {
            return null;
        }

        return [
            'id' => $job->id,
            'status' => $job->status,
            'total_indicators' => $job->total_indicators,
            'completed_indicators' => $job->completed_indicators,
            'failed_indicators' => $job->failed_indicators,
            'progress_percent' => $job->calculateProgress(),
            'started_at' => $job->started_at?->toIso8601String(),
            'completed_at' => $job->completed_at?->toIso8601String(),
            'cancelled_at' => $job->cancelled_at?->toIso8601String(),
            'error_message' => $job->error_message,
            'duration_seconds' => $job->duration,
            'duration_human' => $job->duration_human,
            'results' => $job->results->map(function ($result) {
                return [
                    'indicator_slug' => $result->indicator_slug,
                    'indicator_name' => $result->indicator_name,
                    'status' => $result->status,
                    'score' => $result->score,
                    'max_score' => $result->max_score,
                    'percentage' => $result->percentage,
                    'processing_time_ms' => $result->processing_time_ms,
                ];
            })->toArray(),
        ];
    }

    /**
     * Cancel a running or pending job.
     *
     * @param int $jobId
     * @param int|null $userId Optional: verify job belongs to user
     * @return bool
     * @throws RuntimeException If job cannot be cancelled
     */
    public function cancelJob(int $jobId, ?int $userId = null): bool
    {
        $job = $this->getJob($jobId, $userId);

        if (!$job) {
            throw new RuntimeException('Job not found.');
        }

        if (!$job->isPending() && !$job->isRunning()) {
            throw new RuntimeException("Cannot cancel job with status: {$job->status}");
        }

        $cancelled = $job->markAsCancelled();

        if ($cancelled) {
            // Mark all pending/running results as skipped
            $job->results()
                ->whereIn('status', [
                    AnalysisJobResult::STATUS_PENDING,
                    AnalysisJobResult::STATUS_RUNNING,
                ])
                ->update(['status' => AnalysisJobResult::STATUS_SKIPPED]);

            Log::info('Analysis job cancelled', [
                'job_id' => $jobId,
                'user_id' => $userId,
            ]);
        }

        return $cancelled;
    }

    /**
     * Start a pending job.
     *
     * @param int $jobId
     * @return bool
     * @throws RuntimeException If job cannot be started
     */
    public function startJob(int $jobId): bool
    {
        $job = AnalysisJob::find($jobId);

        if (!$job) {
            throw new RuntimeException('Job not found.');
        }

        if (!$job->isPending()) {
            throw new RuntimeException("Cannot start job with status: {$job->status}");
        }

        return $job->markAsRunning();
    }

    /**
     * Complete a running job.
     *
     * @param int $jobId
     * @return bool
     * @throws RuntimeException If job cannot be completed
     */
    public function completeJob(int $jobId): bool
    {
        $job = AnalysisJob::find($jobId);

        if (!$job) {
            throw new RuntimeException('Job not found.');
        }

        if (!$job->isRunning()) {
            throw new RuntimeException("Cannot complete job with status: {$job->status}");
        }

        $completed = $job->markAsCompleted();

        if ($completed) {
            Log::info('Analysis job completed', [
                'job_id' => $jobId,
                'duration' => $job->duration_human,
            ]);
        }

        return $completed;
    }

    /**
     * Fail a running job.
     *
     * @param int $jobId
     * @param string $errorMessage
     * @return bool
     * @throws RuntimeException If job cannot be failed
     */
    public function failJob(int $jobId, string $errorMessage): bool
    {
        $job = AnalysisJob::find($jobId);

        if (!$job) {
            throw new RuntimeException('Job not found.');
        }

        if (!$job->isRunning()) {
            throw new RuntimeException("Cannot fail job with status: {$job->status}");
        }

        $failed = $job->markAsFailed($errorMessage);

        if ($failed) {
            Log::error('Analysis job failed', [
                'job_id' => $jobId,
                'error' => $errorMessage,
            ]);
        }

        return $failed;
    }

    /**
     * Update job progress based on completed results.
     *
     * @param int $jobId
     * @return void
     */
    public function updateProgress(int $jobId): void
    {
        $job = AnalysisJob::with('results')->find($jobId);

        if (!$job || !$job->isRunning()) {
            return;
        }

        $completed = $job->results()->where('status', AnalysisJobResult::STATUS_COMPLETED)->count();
        $failed = $job->results()->where('status', AnalysisJobResult::STATUS_FAILED)->count();

        $job->completed_indicators = $completed;
        $job->failed_indicators = $failed;
        $job->progress_percent = $job->calculateProgress();
        $job->save();

        // Check if all indicators are done
        $totalDone = $completed + $failed;
        if ($totalDone >= $job->total_indicators) {
            $this->completeJob($jobId);
        }
    }

    /**
     * Get pending jobs ready for processing.
     *
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getPendingJobs(int $limit = 10)
    {
        return AnalysisJob::pending()
            ->with('site')
            ->orderBy('created_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Get running jobs with their pending results.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getRunningJobs()
    {
        return AnalysisJob::running()
            ->with(['results' => function ($query) {
                $query->where('status', AnalysisJobResult::STATUS_PENDING);
            }])
            ->get();
    }

    /**
     * Validate indicators against available indicators.
     *
     * @param array $indicators
     * @return array Valid indicator slugs
     */
    protected function validateIndicators(array $indicators): array
    {
        $valid = [];
        $available = array_keys(self::INDICATORS);

        foreach ($indicators as $indicator) {
            $slug = is_string($indicator) ? $indicator : ($indicator['slug'] ?? null);

            if ($slug && in_array($slug, $available)) {
                $valid[] = $slug;
            }
        }

        return array_unique($valid);
    }

    /**
     * Check rate limits for job creation.
     *
     * @param int $userId
     * @param int $siteId
     * @throws RuntimeException If rate limit exceeded
     */
    protected function checkRateLimits(int $userId, int $siteId): void
    {
        $result = $this->getRateLimiter()->canCreateJob($userId, $siteId);

        if (!$result['allowed']) {
            $message = $result['reason'] ?? 'Rate limit exceeded.';
            
            // Include retry_after in the error for proper 429 response
            $retryAfter = $result['retry_after'] ?? 60;
            
            throw new RuntimeException("RATE_LIMIT:{$retryAfter}:{$message}");
        }
    }

    /**
     * Get rate limit headers for the last job creation check.
     *
     * @param int $userId
     * @param int $siteId
     * @return array<string, string>
     */
    public function getRateLimitHeaders(int $userId, int $siteId): array
    {
        return $this->getRateLimiter()->getJobCreationHeaders($userId, $siteId);
    }

    /**
     * Check if a user can access a job.
     *
     * @param int $userId
     * @param int $jobId
     * @return bool
     */
    public function canUserAccessJob(int $userId, int $jobId): bool
    {
        return AnalysisJob::where('id', $jobId)
            ->where('user_id', $userId)
            ->exists();
    }

    /**
     * Get available indicators.
     *
     * @return array
     */
    public function getAvailableIndicators(): array
    {
        return self::INDICATORS;
    }
}
