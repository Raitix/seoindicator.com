<?php

namespace App\Services\Crawler;

use App\Models\CrawlJob;
use App\Models\CrawlPage;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Content Retrieval Service implementation.
 *
 * Provides methods for retrieving, filtering, paginating, and exporting
 * crawl job content.
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
     * Valid fields for selection.
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
     * Field mapping from API names to model attributes.
     */
    protected const FIELD_MAPPING = [
        'renderedHtml' => 'rendered_html',
        'structuredText' => 'structured_text',
        'crawledAt' => 'crawled_at',
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
        $job = $this->getJobForUser($jobId, $userId);
        if (!$job) {
            return null;
        }

        if (!$this->isJobComplete($jobId)) {
            return null;
        }

        $page = $options['page'] ?? 1;
        $perPage = min($options['perPage'] ?? 50, 100);
        $sortBy = $options['sortBy'] ?? 'crawled_at';
        $sortOrder = $options['sortOrder'] ?? 'desc';
        $include = $options['include'] ?? null;
        $exclude = $options['exclude'] ?? null;

        $query = CrawlPage::where('crawl_job_id', $job->id);

        // Apply filters
        if (isset($filters['depth'])) {
            $query->where('depth', $filters['depth']);
        }

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('title', 'LIKE', "%{$search}%")
                  ->orWhere('structured_text', 'LIKE', "%{$search}%");
            });
        }

        // Apply sorting
        $sortColumn = $this->mapSortField($sortBy);
        $query->orderBy($sortColumn, $sortOrder);

        // Get total count for pagination
        $totalItems = $query->count();

        // Get paginated results
        $pages = $query->offset(($page - 1) * $perPage)
            ->limit($perPage)
            ->get();

        // Transform pages with field selection
        $transformedPages = $pages->map(function ($page) use ($include, $exclude) {
            return $this->transformPage($page, $include, $exclude);
        });

        // Calculate pagination metadata
        $totalPages = (int) ceil($totalItems / $perPage);

        // Get summary
        $summary = $this->getJobSummary($job);

        return [
            'jobId' => $job->job_id,
            'pages' => $transformedPages->toArray(),
            'pagination' => [
                'currentPage' => (int) $page,
                'perPage' => (int) $perPage,
                'totalPages' => $totalPages,
                'totalItems' => $totalItems,
            ],
            'summary' => $summary,
        ];
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
        $job = $this->getJobForUser($jobId, $userId);
        if (!$job) {
            return null;
        }

        $page = CrawlPage::where('crawl_job_id', $job->id)
            ->where('id', $pageId)
            ->first();

        if (!$page) {
            return null;
        }

        $include = $options['include'] ?? null;
        $exclude = $options['exclude'] ?? null;

        return $this->transformPage($page, $include, $exclude);
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
        $job = $this->getJobForUser($jobId, $userId);
        if (!$job) {
            return null;
        }

        if (!$this->isJobComplete($jobId)) {
            return null;
        }

        $pages = CrawlPage::where('crawl_job_id', $job->id)
            ->orderBy('depth', 'asc')
            ->orderBy('crawled_at', 'asc')
            ->get();

        $transformedPages = $pages->map(function ($page) {
            return $this->transformPage($page);
        });

        return [
            'jobId' => $job->job_id,
            'url' => $job->url,
            'depth' => $job->depth,
            'status' => $job->status,
            'pagesCrawled' => $job->pages_crawled,
            'totalSize' => $job->total_size,
            'startedAt' => $job->started_at?->toISOString(),
            'completedAt' => $job->completed_at?->toISOString(),
            'pages' => $transformedPages->toArray(),
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
        $job = $this->getJobForUser($jobId, $userId);
        if (!$job) {
            return null;
        }

        if (!$this->isJobComplete($jobId)) {
            return null;
        }

        $pages = CrawlPage::where('crawl_job_id', $job->id)
            ->orderBy('depth', 'asc')
            ->orderBy('crawled_at', 'asc')
            ->get();

        // CSV headers
        $headers = ['url', 'depth', 'title', 'meta_description', 'text_preview', 'size'];
        $lines = [implode(',', $headers)];

        foreach ($pages as $page) {
            $metaDescription = $page->meta['description'] ?? '';
            $textPreview = $this->truncateText($page->structured_text ?? '', 200);
            
            $row = [
                $this->csvEscape($page->url),
                $page->depth,
                $this->csvEscape($page->title ?? ''),
                $this->csvEscape($metaDescription),
                $this->csvEscape($textPreview),
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
        $job = $this->getJobForUser($jobId, $userId);
        if (!$job) {
            return null;
        }

        if (!$this->isJobComplete($jobId)) {
            return null;
        }

        $pages = CrawlPage::where('crawl_job_id', $job->id)
            ->orderBy('depth', 'asc')
            ->orderBy('crawled_at', 'asc')
            ->get();

        $html = '<!DOCTYPE html>' . "\n";
        $html .= '<html lang="en">' . "\n";
        $html .= '<head>' . "\n";
        $html .= '    <meta charset="UTF-8">' . "\n";
        $html .= '    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">' . "\n";
        $html .= '    <meta name="viewport" content="width=device-width, initial-scale=1.0">' . "\n";
        $html .= '    <title>Crawl Export - ' . $this->escapeHtml($job->url) . '</title>' . "\n";
        $html .= '    <style>' . "\n";
        $html .= '        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; max-width: 1200px; margin: 0 auto; padding: 20px; }' . "\n";
        $html .= '        h1 { border-bottom: 2px solid #333; padding-bottom: 10px; }' . "\n";
        $html .= '        nav { background: #f5f5f5; padding: 15px; border-radius: 5px; margin-bottom: 20px; }' . "\n";
        $html .= '        nav h2 { margin-top: 0; }' . "\n";
        $html .= '        nav ul { list-style: none; padding: 0; }' . "\n";
        $html .= '        nav li { margin: 5px 0; }' . "\n";
        $html .= '        nav a { color: #0066cc; text-decoration: none; }' . "\n";
        $html .= '        nav a:hover { text-decoration: underline; }' . "\n";
        $html .= '        .page { margin-bottom: 40px; padding: 20px; border: 1px solid #ddd; border-radius: 5px; }' . "\n";
        $html .= '        .page h2 { color: #333; }' . "\n";
        $html .= '        .page-meta { color: #666; font-size: 14px; margin-bottom: 15px; }' . "\n";
        $html .= '        hr { border: none; border-top: 2px solid #eee; margin: 30px 0; }' . "\n";
        $html .= '    </style>' . "\n";
        $html .= '</head>' . "\n";
        $html .= '<body>' . "\n";
        $html .= '    <h1>Crawl Export: ' . $this->escapeHtml($job->url) . '</h1>' . "\n";

        // Table of Contents
        $html .= '    <nav>' . "\n";
        $html .= '        <h2>Table of Contents</h2>' . "\n";
        $html .= '        <ul>' . "\n";
        foreach ($pages as $index => $page) {
            $anchor = 'page-' . ($index + 1);
            $html .= '            <li><a href="#' . $anchor . '">' . $this->escapeHtml($page->title ?? $page->url) . '</a> (Depth: ' . $page->depth . ')</li>' . "\n";
        }
        $html .= '        </ul>' . "\n";
        $html .= '    </nav>' . "\n";

        // Page content
        foreach ($pages as $index => $page) {
            $anchor = 'page-' . ($index + 1);
            $html .= '    <div class="page" id="' . $anchor . '">' . "\n";
            $html .= '        <h2>' . $this->escapeHtml($page->title ?? 'Untitled') . '</h2>' . "\n";
            $html .= '        <div class="page-meta">' . "\n";
            $html .= '            <strong>URL:</strong> <a href="' . $this->escapeHtml($page->url) . '" target="_blank">' . $this->escapeHtml($page->url) . '</a><br>' . "\n";
            $html .= '            <strong>Depth:</strong> ' . $page->depth . '<br>' . "\n";
            $html .= '            <strong>Size:</strong> ' . $page->size_formatted . "\n";
            $html .= '        </div>' . "\n";
            
            if ($page->rendered_html) {
                $html .= '        <div class="content">' . "\n";
                $html .= '            <iframe srcdoc="' . $this->escapeHtmlAttr($page->rendered_html) . '" style="width: 100%; height: 400px; border: 1px solid #ddd;"></iframe>' . "\n";
                $html .= '        </div>' . "\n";
            }
            
            $html .= '    </div>' . "\n";
            
            if ($index < count($pages) - 1) {
                $html .= '    <hr>' . "\n";
            }
        }

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
        if (!$job) {
            return null;
        }

        // Create ETag from job ID, updated_at, and options
        $etagBase = $job->job_id . '|' . $job->updated_at->toISOString() . '|' . json_encode($options);
        return '"' . md5($etagBase) . '"';
    }

    /**
     * Clear cache for job.
     *
     * @param string $jobId The job identifier
     */
    public function clearCache(string $jobId): void
    {
        $cacheKey = "crawler:content:{$jobId}";
        Cache::forget($cacheKey);
    }

    /**
     * Get job for user with authorization check.
     *
     * @param string $jobId
     * @param int $userId
     * @return CrawlJob|null
     */
    protected function getJobForUser(string $jobId, int $userId): ?CrawlJob
    {
        return CrawlJob::where('job_id', $jobId)
            ->where('user_id', $userId)
            ->first();
    }

    /**
     * Transform a page model to array with field selection.
     *
     * @param CrawlPage $page
     * @param array|null $include
     * @param array|null $exclude
     * @return array
     */
    protected function transformPage(CrawlPage $page, ?array $include = null, ?array $exclude = null): array
    {
        $allFields = [
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

        // URL is always included
        $result = ['url' => $page->url, 'depth' => $page->depth, 'title' => $page->title];

        // If include is specified, only include those fields
        if ($include !== null) {
            foreach ($include as $field) {
                if (isset($allFields[$field])) {
                    $result[$field] = $allFields[$field];
                }
            }
            // Always include id for single page retrieval
            if (!isset($result['id'])) {
                $result['id'] = $page->id;
            }
            return $result;
        }

        // If exclude is specified, exclude those fields
        if ($exclude !== null) {
            foreach ($allFields as $field => $value) {
                if (!in_array($field, $exclude) || $field === 'url') {
                    $result[$field] = $value;
                }
            }
            return $result;
        }

        // Include all fields
        return $allFields;
    }

    /**
     * Map sort field from API to model column.
     *
     * @param string $sortBy
     * @return string
     */
    protected function mapSortField(string $sortBy): string
    {
        return match ($sortBy) {
            'depth' => 'depth',
            'size' => 'size',
            'crawledAt' => 'crawled_at',
            default => 'crawled_at',
        };
    }

    /**
     * Get summary for a job.
     *
     * @param CrawlJob $job
     * @return array
     */
    protected function getJobSummary(CrawlJob $job): array
    {
        $depthDistribution = CrawlPage::where('crawl_job_id', $job->id)
            ->select('depth', DB::raw('count(*) as count'))
            ->groupBy('depth')
            ->pluck('count', 'depth')
            ->toArray();

        return [
            'totalPages' => $job->pages_crawled,
            'totalSize' => $job->total_size,
            'depthDistribution' => $depthDistribution,
        ];
    }

    /**
     * Escape a value for CSV.
     *
     * @param string $value
     * @return string
     */
    protected function csvEscape(string $value): string
    {
        // If contains comma, newline, or quote, wrap in quotes and escape quotes
        if (str_contains($value, ',') || str_contains($value, "\n") || str_contains($value, '"')) {
            return '"' . str_replace('"', '""', $value) . '"';
        }
        return $value;
    }

    /**
     * Truncate text to a maximum length.
     *
     * @param string $text
     * @param int $maxLength
     * @return string
     */
    protected function truncateText(string $text, int $maxLength): string
    {
        if (strlen($text) <= $maxLength) {
            return $text;
        }
        return substr($text, 0, $maxLength) . '...';
    }

    /**
     * Escape HTML entities.
     *
     * @param string $value
     * @return string
     */
    protected function escapeHtml(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * Escape HTML attribute value.
     *
     * @param string $value
     * @return string
     */
    protected function escapeHtmlAttr(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}
