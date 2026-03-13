<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ContentRetrievalRequest;
use App\Services\Crawler\ContentRetrievalServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

/**
 * Content Retrieval Controller.
 *
 * Handles content retrieval, filtering, pagination, and export endpoints
 * for the Headless Crawler Service.
 *
 * @see TASK-22-14
 * @see SPEC-022
 */
class ContentRetrievalController extends Controller
{
    /**
     * Cache TTL in seconds (1 hour).
     */
    protected const CACHE_TTL = 3600;

    /**
     * Size threshold for streaming (1MB).
     */
    protected const STREAM_THRESHOLD = 1048576;

    /**
     * Create a new controller instance.
     */
    public function __construct(
        protected ContentRetrievalServiceInterface $service
    ) {}

    /**
     * Get paginated content for a crawl job.
     *
     * @param string $jobId
     * @param ContentRetrievalRequest $request
     * @return JsonResponse
     */
    public function content(string $jobId, ContentRetrievalRequest $request): JsonResponse
    {
        $userId = $request->user()->id;

        // Check if job exists and belongs to user
        if (!$this->service->jobBelongsToUser($jobId, $userId)) {
            return response()->json(['message' => 'Job not found'], 404);
        }

        // Check if job is complete
        if (!$this->service->isJobComplete($jobId)) {
            $status = $this->service->getJobStatus($jobId);
            $message = match ($status) {
                'running' => 'Job is still running',
                'queued' => 'Job is queued',
                'failed' => 'Job has failed',
                'cancelled' => 'Job was cancelled',
                default => 'Job not complete',
            };
            return response()->json(['error' => 'Job not complete', 'message' => $message], 409);
        }

        $filters = $request->getFilters();
        $options = [
            'page' => (int) $request->page,
            'perPage' => (int) $request->perPage,
            'sortBy' => $request->sortBy,
            'sortOrder' => $request->sortOrder,
            'include' => $request->getIncludeFields(),
            'exclude' => $request->getExcludeFields(),
        ];

        // Generate cache key
        $cacheKey = $this->getCacheKey($jobId, $filters, $options);
        $etagKey = $cacheKey . ':etag';

        // Check ETag for caching
        $etag = Cache::get($etagKey);
        if ($etag && $request->header('If-None-Match') === $etag) {
            return response()->json(null, 304);
        }

        // Try to get from cache
        $isHit = false;
        $content = Cache::get($cacheKey);
        
        if ($content !== null) {
            $isHit = true;
        } else {
            $content = $this->service->getContent($jobId, $userId, $filters, $options);
            
            if ($content !== null) {
                // Cache the result
                Cache::put($cacheKey, $content, self::CACHE_TTL);
                
                // Generate and cache ETag
                $etag = $this->generateEtag($content);
                Cache::put($etagKey, $etag, self::CACHE_TTL);
            }
        }

        if (!$content) {
            return response()->json(['message' => 'Job not found'], 404);
        }

        $response = response()->json($content);

        // Add caching headers
        if ($etag) {
            $response->header('ETag', $etag);
        }
        $response->header('X-Cache', $isHit ? 'HIT' : 'MISS');
        $response->header('Cache-Control', 'max-age=3600');
        $response->header('X-Content-Type-Options', 'nosniff');

        return $response;
    }

    /**
     * Get a single page from a crawl job.
     *
     * @param string $jobId
     * @param int $pageId
     * @param ContentRetrievalRequest $request
     * @return JsonResponse
     */
    public function page(string $jobId, int $pageId, ContentRetrievalRequest $request): JsonResponse
    {
        $userId = $request->user()->id;

        // Check if job exists and belongs to user
        if (!$this->service->jobBelongsToUser($jobId, $userId)) {
            return response()->json(['message' => 'Job not found'], 404);
        }

        // Check if job is complete
        if (!$this->service->isJobComplete($jobId)) {
            return response()->json(['error' => 'Job not complete'], 409);
        }

        $options = [
            'include' => $request->getIncludeFields(),
            'exclude' => $request->getExcludeFields(),
        ];

        $page = $this->service->getPage($jobId, $pageId, $userId, $options);

        if (!$page) {
            return response()->json(['message' => 'Page not found'], 404);
        }

        return response()->json($page)
            ->header('X-Content-Type-Options', 'nosniff');
    }

    /**
     * Export job content as JSON.
     *
     * @param string $jobId
     * @param Request $request
     * @return JsonResponse|Response
     */
    public function exportJson(string $jobId, Request $request): JsonResponse|Response
    {
        $userId = $request->user()->id;

        // Check if job exists and belongs to user
        if (!$this->service->jobBelongsToUser($jobId, $userId)) {
            return response()->json(['message' => 'Job not found'], 404);
        }

        // Check if job is complete
        if (!$this->service->isJobComplete($jobId)) {
            return response()->json(['error' => 'Job not complete'], 409);
        }

        $data = $this->service->exportAsJson($jobId, $userId);

        if (!$data) {
            return response()->json(['message' => 'Job not found'], 404);
        }

        $filename = "crawl-{$jobId}.json";
        $content = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        return response($content, 200, [
            'Content-Type' => 'application/json',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /**
     * Export job content as CSV.
     *
     * @param string $jobId
     * @param Request $request
     * @return JsonResponse|Response
     */
    public function exportCsv(string $jobId, Request $request): JsonResponse|Response
    {
        $userId = $request->user()->id;

        // Check if job exists and belongs to user
        if (!$this->service->jobBelongsToUser($jobId, $userId)) {
            return response()->json(['message' => 'Job not found'], 404);
        }

        // Check if job is complete
        if (!$this->service->isJobComplete($jobId)) {
            return response()->json(['error' => 'Job not complete'], 409);
        }

        $csv = $this->service->exportAsCsv($jobId, $userId);

        if (!$csv) {
            return response()->json(['message' => 'Job not found'], 404);
        }

        $filename = "crawl-{$jobId}.csv";

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /**
     * Export job content as HTML.
     *
     * @param string $jobId
     * @param Request $request
     * @return JsonResponse|Response
     */
    public function exportHtml(string $jobId, Request $request): JsonResponse|Response
    {
        $userId = $request->user()->id;

        // Check if job exists and belongs to user
        if (!$this->service->jobBelongsToUser($jobId, $userId)) {
            return response()->json(['message' => 'Job not found'], 404);
        }

        // Check if job is complete
        if (!$this->service->isJobComplete($jobId)) {
            return response()->json(['error' => 'Job not complete'], 409);
        }

        $html = $this->service->exportAsHtml($jobId, $userId);

        if (!$html) {
            return response()->json(['message' => 'Job not found'], 404);
        }

        $filename = "crawl-{$jobId}.html";

        return response($html, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /**
     * Generate cache key for a request.
     *
     * @param string $jobId
     * @param array $filters
     * @param array $options
     * @return string
     */
    protected function getCacheKey(string $jobId, array $filters, array $options): string
    {
        return 'crawler:content:' . $jobId . ':' . md5(json_encode([$filters, $options]));
    }

    /**
     * Generate ETag for content.
     *
     * @param array $content
     * @return string
     */
    protected function generateEtag(array $content): string
    {
        return '"' . md5(json_encode($content)) . '"';
    }
}
