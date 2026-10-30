<?php

namespace App\Services;

use App\Models\CrawlJob;
use App\Models\CrawlPage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

/**
 * CrawlerService - Headless Crawler Service
 *
 * Service for managing crawl jobs that render JavaScript-heavy pages
 * and extract SEO-relevant content.
 *
 * @see SPEC-022
 * @see TASK-22-3
 */
class CrawlerService
{
    /**
     * Create a new crawl job.
     *
     * @param int $userId The user ID creating the job
     * @param string $url The URL to crawl
     * @param array $options Optional configuration for the crawl
     * @return CrawlJob The created crawl job
     * @throws \InvalidArgumentException If URL is invalid or blocked
     */
    public function createJob(int $userId, string $url, array $options = []): CrawlJob
    {
        // Validate URL
        $this->validateUrl($url);

        // Generate unique job ID
        $jobId = 'crawl-' . Str::uuid()->toString();

        // Merge with default options
        $defaultOptions = [
            'waitTime' => config('crawler.default_wait_time', 2000),
            'userAgent' => config('crawler.user_agent', 'SEOIndicator/1.0 Crawler'),
            'includeImages' => false,
            'followRedirects' => true,
            'maxRedirects' => config('crawler.max_redirects', 5),
        ];

        $mergedOptions = array_merge($defaultOptions, $options);

        // Calculate depth (capped to max_depth)
        $depth = isset($options['depth'])
            ? min($options['depth'], config('crawler.max_depth', 2))
            : 0;

        // Calculate expiry date
        $expiresAt = now()->addDays(config('crawler.retention_days', 7));

        // Create the job
        $job = CrawlJob::create([
            'user_id' => $userId,
            'project_id' => $options['project_id'] ?? null,
            'job_id' => $jobId,
            'url' => $url,
            'status' => CrawlJob::STATUS_QUEUED,
            'depth' => $depth,
            'options' => $mergedOptions,
            'pages_crawled' => 0,
            'total_size' => 0,
            'error' => null,
            'started_at' => null,
            'completed_at' => null,
            'expires_at' => $expiresAt,
        ]);

        Log::info('Crawl job created', [
            'job_id' => $job->job_id,
            'url' => $url,
            'user_id' => $userId,
        ]);

        // TODO: Dispatch to queue (TASK-22-16)
        // CrawlWorker::dispatch($job->job_id);

        return $job;
    }

    /**
     * Get a crawl job by its job_id.
     *
     * @param string $jobId The unique job identifier
     * @return CrawlJob|null The crawl job or null if not found
     */
    public function getJob(string $jobId): ?CrawlJob
    {
        return CrawlJob::where('job_id', $jobId)->first();
    }

    /**
     * Process a crawl job.
     *
     * This is the main crawl execution method called by the worker.
     * Currently a skeleton - full implementation in TASK-22-5 through TASK-22-10.
     *
     * @param string $jobId The unique job identifier
     * @return void
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException If job not found
     * @throws \InvalidArgumentException If job status is not 'queued'
     */
    public function processJob(string $jobId): void
    {
        $job = CrawlJob::where('job_id', $jobId)->firstOrFail();

        // Validate job status
        if ($job->status !== CrawlJob::STATUS_QUEUED) {
            throw new \InvalidArgumentException(
                "Job {$jobId} cannot be processed: status is '{$job->status}', expected 'queued'"
            );
        }

        // Set status to running
        $job->update([
            'status' => CrawlJob::STATUS_RUNNING,
            'started_at' => now(),
        ]);

        try {
            // TODO: Implement full crawl logic (TASK-22-5 through TASK-22-10)
            // 1. Validate URL (SSRF protection)
            // 2. Render page with Playwright
            // 3. Extract content (HTML + structured text)
            // 4. Extract links for depth crawling
            // 5. Process sub-pages if within depth limit
            // 6. Enforce size limits

            // For skeleton, just mark as completed
            $job->update([
                'status' => CrawlJob::STATUS_COMPLETED,
                'completed_at' => now(),
            ]);

            Log::info('Crawl job completed', [
                'job_id' => $job->job_id,
                'pages_crawled' => $job->pages_crawled,
            ]);
        } catch (\Exception $e) {
            // Catch all exceptions and mark job as failed
            $job->update([
                'status' => CrawlJob::STATUS_FAILED,
                'error' => $e->getMessage(),
                'completed_at' => now(),
            ]);

            Log::error('Crawl job failed', [
                'job_id' => $job->job_id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Cancel a crawl job.
     *
     * @param string $jobId The unique job identifier
     * @return bool True if cancelled, false if cannot be cancelled
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException If job not found
     */
    public function cancelJob(string $jobId): bool
    {
        $job = CrawlJob::where('job_id', $jobId)->firstOrFail();

        // Can only cancel queued or running jobs
        if (!in_array($job->status, [CrawlJob::STATUS_QUEUED, CrawlJob::STATUS_RUNNING])) {
            return false;
        }

        $job->update([
            'status' => CrawlJob::STATUS_CANCELLED,
            'completed_at' => now(),
        ]);

        Log::info('Crawl job cancelled', [
            'job_id' => $job->job_id,
        ]);

        return true;
    }

    /**
     * Clean up expired crawl jobs.
     *
     * Removes all jobs where expires_at < now, including their pages.
     *
     * @return int The number of jobs deleted
     */
    public function cleanup(): int
    {
        $expiredJobs = CrawlJob::where('expires_at', '<', now())->get();

        $count = $expiredJobs->count();

        foreach ($expiredJobs as $job) {
            // Cascade delete will handle pages via foreign key
            $job->delete();
        }

        if ($count > 0) {
            Log::info('Cleaned up expired crawl jobs', [
                'count' => $count,
            ]);
        }

        return $count;
    }

    /**
     * Validate a URL for crawling.
     *
     * Checks for valid format, allowed protocol, and SSRF protection.
     *
     * @param string $url The URL to validate
     * @throws \InvalidArgumentException If URL is invalid or blocked
     */
    protected function validateUrl(string $url): void
    {
        // Check for empty URL
        if (empty($url)) {
            throw new \InvalidArgumentException('URL cannot be empty');
        }

        // Parse URL
        $parsed = parse_url($url);

        // Check for valid URL format
        if ($parsed === false || !isset($parsed['scheme']) || !isset($parsed['host'])) {
            throw new \InvalidArgumentException("Invalid URL format: {$url}");
        }

        // Check for allowed protocols (only http and https)
        $scheme = strtolower($parsed['scheme']);
        $blockedProtocols = config('crawler.ssrf.blocked_protocols', []);

        if (!in_array($scheme, ['http', 'https'])) {
            throw new \InvalidArgumentException("Protocol not allowed: {$scheme}. Only HTTP and HTTPS are allowed.");
        }

        // Check for blocked protocols
        if (in_array($scheme, $blockedProtocols)) {
            throw new \InvalidArgumentException("Protocol is blocked: {$scheme}");
        }

        // SSRF protection - check blocked hosts
        $host = strtolower($parsed['host']);
        $blockedHosts = config('crawler.ssrf.blocked_hosts', []);

        foreach ($blockedHosts as $pattern) {
            if (str_starts_with($pattern, '*.')) {
                // Wildcard pattern
                $domain = substr($pattern, 2);
                if ($host === $domain || str_ends_with($host, '.' . $domain)) {
                    throw new \InvalidArgumentException("Host is blocked: {$host}");
                }
            } else {
                // Exact match
                if ($host === $pattern) {
                    throw new \InvalidArgumentException("Host is blocked: {$host}");
                }
            }
        }

        // SSRF protection - check blocked IP ranges
        $this->validateIpAddress($host);
    }

    /**
     * Validate that the host does not resolve to a blocked IP.
     *
     * @param string $host The hostname to validate
     * @throws \InvalidArgumentException If IP is blocked
     */
    protected function validateIpAddress(string $host): void
    {
        // Resolve hostname to IP
        $ip = gethostbyname($host);

        // If gethostbyname returns the same value, it might be an IP already
        if ($ip === $host) {
            // Check if it's a valid IP
            if (filter_var($host, FILTER_VALIDATE_IP)) {
                $ip = $host;
            } else {
                // Could not resolve, let it through (will fail at crawl time)
                return;
            }
        }

        // Check against blocked IP ranges
        $blockedRanges = config('crawler.ssrf.blocked_ip_ranges', []);

        foreach ($blockedRanges as $range) {
            if ($this->ipInRange($ip, $range)) {
                throw new \InvalidArgumentException("IP address is blocked: {$ip}");
            }
        }
    }

    /**
     * Check if an IP is within a CIDR range.
     *
     * @param string $ip The IP address to check
     * @param string $range The CIDR range (e.g., '192.168.0.0/16')
     * @return bool True if IP is in range
     */
    protected function ipInRange(string $ip, string $range): bool
    {
        // Handle IPv6
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            // For IPv6, we check exact matches and simple prefix matching
            if ($range === '::1/128' && $ip === '::1') {
                return true;
            }
            if (str_starts_with($range, 'fe80::') && str_starts_with($ip, 'fe80:')) {
                return true;
            }
            return false;
        }

        // Parse CIDR notation
        [$subnet, $bits] = explode('/', $range, 2) + ['', '32'];

        // Convert to long
        $ipLong = ip2long($ip);
        $subnetLong = ip2long($subnet);

        if ($ipLong === false || $subnetLong === false) {
            return false;
        }

        // Calculate mask
        $mask = -1 << (32 - (int) $bits);
        $mask = $mask & 0xFFFFFFFF;

        // Check if IP is in range
        return ($ipLong & $mask) === ($subnetLong & $mask);
    }
}
