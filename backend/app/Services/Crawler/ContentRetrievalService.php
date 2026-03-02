<?php

namespace App\Services\Crawler;

use App\Models\CrawlJob;
use App\Models\CrawlPage;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Service for retrieving, filtering, paginating, and exporting crawl job content.
 *
 * @see TASK-22-14
 * @see SPEC-022
 */
class ContentRetrievalService implements ContentRetrievalServiceInterface
{
    /**
     * Cache TTL in seconds (1 hour).
     */
    protected const CACHE_TTL = 3600;

    /**
     * Fields that can be included/excluded.
     */
    protected const VALID_FIELDS = [
        'url',
        'depth',
        'title',
        'renderedHtml',
        'structuredText',
        'meta',
        'links',
        'size',
        'crawledAt',
    ];

    /**
     * Fields that should always be included.
     */
    protected const REQUIRED_FIELDS = ['id', 'url'];

    /**
     * Database field mapping for sorting.
     */
    protected const SORT_FIELD_MAP = [
        'crawledAt' => 'crawled_at',
        'depth' => 'depth',
        'size' => 'size',
    ];

    /**
     * Get paginated content for a crawl job.
     *
     * @param string $jobId The job identifier
     * @param int $userId The user ID (for authorization)
     * @param array $filters Optional filters (depth, search)
     * @param array $options Optional parameters (page, perPage, sortBy, sortOrder, include, exclude)
     * @return array|null Returns null if job not found or unauthorized
     */
    public function getContent(string $jobId, int $userId, array $filters = [], array $options = []): ?array
    {
        $job = CrawlJob::where('job_id', $jobId)
            ->where('user_id', $userId)
            ->first();

        if ($job === null) {
            return null;
        }

        // Build cache key
        $cacheKey = $this->buildCacheKey($jobId, array_merge($filters, $options));

        // Try to get from cache
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            $cached['_cached'] = true;
            return $cached;
        }

        // Build query
        $query = CrawlPage::where('crawl_job_id', $job->id)
            ->whereNull('error');

        // Apply filters
        if (isset($filters['depth']) && $filters['depth'] !== null) {
            $query->where('depth', $filters['depth']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('title', 'LIKE', "%{$search}%")
                  ->orWhere('structured_text', 'LIKE', "%{$search}%");
            });
        }

        // Get pagination options
        $page = $options['page'] ?? 1;
        $perPage = min($options['perPage'] ?? 50, 100);

        // Apply sorting
        $sortBy = $options['sortBy'] ?? 'crawledAt';
        $sortOrder = $options['sortOrder'] ?? 'desc';
        $sortField = self::SORT_FIELD_MAP[$sortBy] ?? 'crawled_at';
        $query->orderBy($sortField, $sortOrder);

        // Get total count for pagination
        $totalItems = $query->count();

        // Get paginated results
        $pages = $query->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get();

        // Transform pages
        $transformedPages = $pages->map(function ($page) use ($options) {
            return $this->transformPage($page, $options);
        })->toArray();

        // Build response
        $response = [
            'jobId' => $jobId,
            'pages' => $transformedPages,
            'pagination' => [
                'currentPage' => $page,
                'perPage' => $perPage,
                'totalPages' => ceil($totalItems / $perPage),
                'totalItems' => $totalItems,
            ],
            'summary' => $this->buildSummary($job),
        ];

        // Cache the response
        Cache::put($cacheKey, $response, self::CACHE_TTL);

        return $response;
    }

    /**
     * Get a single page from a crawl job.
     *
     * @param string $jobId The job identifier
     * @param int $pageId The page ID
     * @param int $userId The user ID (for authorization)
     * @param array $options Optional parameters (include, exclude)
     * @return array|null Returns null if page/job not found or unauthorized
     */
    public function getPage(string $jobId, int $pageId, int $userId, array $options = []): ?array
    {
        $job = CrawlJob::where('job_id', $jobId)
            ->where('user_id', $userId)
            ->first();

        if ($job === null) {
            return null;
        }

        $page = CrawlPage::where('crawl_job_id', $job->id)
            ->where('id', $pageId)
            ->first();

        if ($page === null) {
            return null;
        }

        return $this->transformPage($page, $options);
    }

    /**
     * Export job content as JSON.
     *
     * @param string $jobId The job identifier
     * @param int $userId The user ID (for authorization)
     * @return array|null Returns null if job not found or unauthorized
     */
    public function exportAsJson(string $jobId, int $userId): ?array
    {
        $job = CrawlJob::where('job_id', $jobId)
            ->where('user_id', $userId)
            ->first();

        if ($job === null) {
            return null;
        }

        $pages = CrawlPage::where('crawl_job_id', $job->id)
            ->whereNull('error')
            ->orderBy('depth')
            ->orderBy('url')
            ->get();

        return [
            'jobId' => $jobId,
            'url' => $job->url,
            'depth' => $job->depth,
            'pagesCrawled' => $job->pages_crawled,
            'totalSize' => $job->total_size,
            'startedAt' => $job->started_at?->toISOString(),
            'completedAt' => $job->completed_at?->toISOString(),
            'pages' => $pages->map(fn ($page) => $this->transformPage($page))->toArray(),
        ];
    }

    /**
     * Export job content as CSV.
     *
     * @param string $jobId The job identifier
     * @param int $userId The user ID (for authorization)
     * @return string|null CSV content or null if job not found/unauthorized
     */
    public function exportAsCsv(string $jobId, int $userId): ?string
    {
        $job = CrawlJob::where('job_id', $jobId)
            ->where('user_id', $userId)
            ->first();

        if ($job === null) {
            return null;
        }

        $pages = CrawlPage::where('crawl_job_id', $job->id)
            ->whereNull('error')
            ->orderBy('depth')
            ->orderBy('url')
            ->get();

        // CSV headers
        $headers = ['url', 'depth', 'title', 'meta_description', 'text_preview', 'size'];
        $lines = [implode(',', $headers)];

        foreach ($pages as $page) {
            $metaDescription = $page->meta['description'] ?? '';
            $textPreview = Str::limit($page->structured_text ?? '', 200);
            
            $row = [
                $this->escapeCsvField($page->url),
                $page->depth,
                $this->escapeCsvField($page->title ?? ''),
                $this->escapeCsvField($metaDescription),
                $this->escapeCsvField($textPreview),
                $page->size,
            ];
            
            $lines[] = implode(',', $row);
        }

        return implode("\n", $lines);
    }

    /**
     * Export job content as combined HTML.
     *
     * @param string $jobId The job identifier
     * @param int $userId The user ID (for authorization)
     * @return string|null HTML content or null if job not found/unauthorized
     */
    public function exportAsHtml(string $jobId, int $userId): ?string
    {
        $job = CrawlJob::where('job_id', $jobId)
            ->where('user_id', $userId)
            ->first();

        if ($job === null) {
            return null;
        }

        $pages = CrawlPage::where('crawl_job_id', $job->id)
            ->whereNull('error')
            ->orderBy('depth')
            ->orderBy('url')
            ->get();

        // Build HTML document
        $html = '<!DOCTYPE html>' . "\n";
        $html .= '<html lang="en">' . "\n";
        $html .= '<head>' . "\n";
        $html .= '  <meta charset="UTF-8">' . "\n";
        $html .= '  <meta name="viewport" content="width=device-width, initial-scale=1.0">' . "\n";
        $html .= '  <title>Crawl Export: ' . htmlspecialchars($job->url) . '</title>' . "\n";
        $html .= '  <style>' . "\n";
        $html .= '    body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; margin: 0; padding: 20px; background: #f5f5f5; }' . "\n";
        $html .= '    .container { max-width: 1200px; margin: 0 auto; }' . "\n";
        $html .= '    h1 { color: #333; border-bottom: 2px solid #007bff; padding-bottom: 10px; }' . "\n";
        $html .= '    nav { background: #fff; padding: 20px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }' . "\n";
        $html .= '    nav h2 { margin-top: 0; color: #555; }' . "\n";
        $html .= '    nav ul { list-style: none; padding: 0; }' . "\n";
        $html .= '    nav li { margin: 8px 0; }' . "\n";
        $html .= '    nav a { color: #007bff; text-decoration: none; }' . "\n";
        $html .= '    nav a:hover { text-decoration: underline; }' . "\n";
        $html .= '    .page { background: #fff; padding: 20px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }' . "\n";
        $html .= '    .page-header { border-bottom: 1px solid #eee; padding-bottom: 10px; margin-bottom: 15px; }' . "\n";
        $html .= '    .page-url { color: #666; font-size: 0.9em; word-break: break-all; }' . "\n";
        $html .= '    .page-depth { background: #007bff; color: white; padding: 2px 8px; border-radius: 4px; font-size: 0.8em; }' . "\n";
        $html .= '    .content { white-space: pre-wrap; font-size: 0.9em; line-height: 1.6; }' . "\n";
        $html .= '    hr { border: none; border-top: 2px solid #eee; margin: 30px 0; }' . "\n";
        $html .= '  </style>' . "\n";
        $html .= '</head>' . "\n";
        $html .= '<body>' . "\n";
        $html .= '  <div class="container">' . "\n";
        $html .= '    <h1>Crawl Export</h1>' . "\n";
        $html .= '    <p><strong>URL:</strong> ' . htmlspecialchars($job->url) . '</p>' . "\n";
        $html .= '    <p><strong>Pages:</strong> ' . $pages->count() . '</p>' . "\n";

        // Table of Contents
        $html .= '    <nav>' . "\n";
        $html .= '      <h2>Table of Contents</h2>' . "\n";
        $html .= '      <ul>' . "\n";
        foreach ($pages as $i => $page) {
            $anchor = 'page-' . $page->id;
            $title = $page->title ?? $page->url;
            $html .= '        <li><a href="#' . $anchor . '">' . htmlspecialchars($title) . '</a> <span class="page-depth">Depth ' . $page->depth . '</span></li>' . "\n";
        }
        $html .= '      </ul>' . "\n";
        $html .= '    </nav>' . "\n";

        // Page content
        foreach ($pages as $page) {
            $anchor = 'page-' . $page->id;
            $html .= '    <div class="page" id="' . $anchor . '">' . "\n";
            $html .= '      <div class="page-header">' . "\n";
            $html .= '        <h3>' . htmlspecialchars($page->title ?? 'Untitled') . '</h3>' . "\n";
            $html .= '        <p class="page-url">' . htmlspecialchars($page->url) . '</p>' . "\n";
            $html .= '        <span class="page-depth">Depth ' . $page->depth . '</span>' . "\n";
            $html .= '      </div>' . "\n";
            $html .= '      <div class="content">' . htmlspecialchars($page->structured_text ?? '') . '</div>' . "\n";
            $html .= '    </div>' . "\n";
            $html .= '    <hr>' . "\n";
        }

        $html .= '  </div>' . "\n";
        $html .= '</body>' . "\n";
        $html .= '</html>';

        return $html;
    }

    /**
     * Check if job belongs to user.
     *
     * @param string $jobId The job identifier
     * @param int $userId The user ID
     * @return bool
     */
    public function jobBelongsToUser(string $jobId, int $userId): bool
    {
        return CrawlJob::where('job_id', $jobId)
            ->where('user_id', $userId)
            ->exists();
    }

    /**
     * Get job status.
     *
     * @param string $jobId The job identifier
     * @return string|null Status or null if not found
     */
    public function getJobStatus(string $jobId): ?string
    {
        $job = CrawlJob::where('job_id', $jobId)->first();
        return $job?->status;
    }

    /**
     * Check if job is complete (can retrieve content).
     *
     * @param string $jobId The job identifier
     * @return bool
     */
    public function isJobComplete(string $jobId): bool
    {
        $status = $this->getJobStatus($jobId);
        return $status === CrawlJob::STATUS_COMPLETED;
    }

    /**
     * Get ETag for job content.
     *
     * @param string $jobId The job identifier
     * @param array $options Optional parameters that affect content
     * @return string|null ETag or null if job not found
     */
    public function getEtag(string $jobId, array $options = []): ?string
    {
        $job = CrawlJob::where('job_id', $jobId)->first();
        
        if ($job === null) {
            return null;
        }

        // Build ETag from job completed_at and options hash
        $etagBase = $job->completed_at?->toISOString() . json_encode($options);
        return '"' . md5($etagBase) . '"';
    }

    /**
     * Clear cache for job.
     *
     * @param string $jobId The job identifier
     */
    public function clearCache(string $jobId): void
    {
        // Clear all cached content for this job
        $pattern = "crawl_content_{$jobId}_*";
        
        // Get all keys matching pattern
        $keys = Cache::getRedis()->keys($pattern);
        
        foreach ($keys as $key) {
            Cache::forget($key);
        }
    }

    /**
     * Transform a page model to array representation.
     *
     * @param CrawlPage $page
     * @param array $options
     * @return array
     */
    protected function transformPage(CrawlPage $page, array $options = []): array
    {
        $data = [
            'id' => $page->id,
            'url' => $page->url,
            'depth' => $page->depth,
            'title' => $page->title,
            'renderedHtml' => $page->rendered_html,
            'structuredText' => $page->structured_text,
            'meta' => $page->meta,
            'links' => $page->links,
            'size' => $page->size,
            'sizeFormatted' => $page->size_formatted,
            'crawledAt' => $page->crawled_at?->toISOString(),
        ];

        // Apply field selection
        $include = $options['include'] ?? null;
        $exclude = $options['exclude'] ?? null;

        if ($include !== null) {
            $includeFields = is_array($include) ? $include : explode(',', $include);
            $includeFields = array_merge(self::REQUIRED_FIELDS, $includeFields);
            $data = array_intersect_key($data, array_flip($includeFields));
        } elseif ($exclude !== null) {
            $excludeFields = is_array($exclude) ? $exclude : explode(',', $exclude);
            // Never exclude required fields
            $excludeFields = array_diff($excludeFields, self::REQUIRED_FIELDS);
            foreach ($excludeFields as $field) {
                unset($data[$field]);
            }
        }

        return $data;
    }

    /**
     * Build summary for a crawl job.
     *
     * @param CrawlJob $job
     * @return array
     */
    protected function buildSummary(CrawlJob $job): array
    {
        $pages = CrawlPage::where('crawl_job_id', $job->id)
            ->whereNull('error')
            ->get();

        $depthDistribution = $pages->groupBy('depth')
            ->map(fn ($group) => $group->count())
            ->toArray();

        return [
            'totalPages' => $pages->count(),
            'totalSize' => $pages->sum('size'),
            'depthDistribution' => $depthDistribution,
        ];
    }

    /**
     * Build cache key for content retrieval.
     *
     * @param string $jobId
     * @param array $params
     * @return string
     */
    protected function buildCacheKey(string $jobId, array $params): string
    {
        ksort($params);
        $hash = md5(json_encode($params));
        return "crawl_content_{$jobId}_{$hash}";
    }

    /**
     * Escape a field for CSV output.
     *
     * @param string $value
     * @return string
     */
    protected function escapeCsvField(string $value): string
    {
        // If the value contains comma, newline, or double quote, wrap in quotes
        if (preg_match('/[,"\n\r]/', $value)) {
            // Escape double quotes by doubling them
            $value = str_replace('"', '""', $value);
            return '"' . $value . '"';
        }
        
        return $value;
    }
}
