<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\CrawlRequest;
use App\Services\Crawler\CrawlerService;
use App\Services\Crawler\CrawlerServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * API Controller for the Headless Crawler Service.
 *
 * Provides REST API endpoints for initiating crawl jobs,
 * checking status, retrieving content, and managing jobs.
 *
 * @see TASK-22-11
 * @see SPEC-022
 */
class CrawlController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(
        protected CrawlerServiceInterface $crawlerService
    ) {}

    /**
     * Create a new crawl job.
     *
     * POST /api/v1/crawler/crawl
     *
     * @param CrawlRequest $request
     * @return JsonResponse
     */
    public function store(CrawlRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $user = $request->user();

        $options = array_merge(
            [
                'depth' => $validated['depth'] ?? 0,
            ],
            $validated['options'] ?? []
        );

        $job = $this->crawlerService->createJob(
            $user->id,
            $validated['url'],
            $options
        );

        return response()->json([
            'jobId' => $job['jobId'],
            'status' => $job['status'],
            'url' => $job['url'],
            'depth' => $job['depth'],
            'estimatedTime' => $job['estimatedTime'],
            'message' => $job['message'],
        ], 202);
    }

    /**
     * List user's crawl jobs with optional filters.
     *
     * GET /api/v1/crawler/jobs
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['nullable', 'string', 'in:queued,running,completed,failed,cancelled'],
            'page' => ['nullable', 'integer', 'min:1'],
            'perPage' => ['nullable', 'integer', 'min:1', 'max:100'],
            'sort' => ['nullable', 'string', 'in:created_at,url,status'],
            'order' => ['nullable', 'string', 'in:asc,desc'],
        ]);

        $user = $request->user();

        // Set current user for authorization if service supports it
        if ($this->crawlerService instanceof CrawlerService) {
            $this->crawlerService->forUser($user->id);
        }

        $paginator = $this->crawlerService->listJobs($user->id, [
            'status' => $validated['status'] ?? null,
            'page' => $validated['page'] ?? 1,
            'perPage' => $validated['perPage'] ?? 15,
            'sort' => $validated['sort'] ?? 'created_at',
            'order' => $validated['order'] ?? 'desc',
        ]);

        return response()->json([
            'data' => $paginator->items(),
            'meta' => [
                'total' => $paginator->total(),
                'page' => $paginator->currentPage(),
                'perPage' => $paginator->perPage(),
                'lastPage' => $paginator->lastPage(),
            ],
        ]);
    }

    /**
     * Get crawl job status.
     *
     * GET /api/v1/crawler/jobs/{jobId}
     *
     * @param string $jobId
     * @param Request $request
     * @return JsonResponse
     */
    public function show(string $jobId, Request $request): JsonResponse
    {
        // Validate jobId format to prevent injection
        if (!$this->isValidJobId($jobId)) {
            return response()->json([
                'message' => 'Job not found',
            ], 404);
        }

        $user = $request->user();

        // Set current user for authorization if service supports it
        if ($this->crawlerService instanceof CrawlerService) {
            $this->crawlerService->forUser($user->id);
        }

        $job = $this->crawlerService->getJob($jobId);

        if (!$job) {
            return response()->json([
                'message' => 'Job not found',
            ], 404);
        }

        // Remove sensitive data before returning
        unset($job['user_id']);
        unset($job['pages']);
        unset($job['summary']);

        return response()->json($job);
    }

    /**
     * Get crawled content for a completed job.
     *
     * GET /api/v1/crawler/jobs/{jobId}/content
     *
     * @param string $jobId
     * @param Request $request
     * @return JsonResponse
     */
    public function content(string $jobId, Request $request): JsonResponse
    {
        // Validate jobId format
        if (!$this->isValidJobId($jobId)) {
            return response()->json([
                'message' => 'Job not found',
            ], 404);
        }

        $user = $request->user();

        // Set current user for authorization if service supports it
        if ($this->crawlerService instanceof CrawlerService) {
            $this->crawlerService->forUser($user->id);
        }

        $job = $this->crawlerService->getJob($jobId);

        if (!$job) {
            return response()->json([
                'message' => 'Job not found',
            ], 404);
        }

        // Only completed jobs have content
        if ($job['status'] !== 'completed') {
            return response()->json([
                'message' => 'Job content not available',
                'status' => $job['status'],
            ], 409);
        }

        $content = $this->crawlerService->getJobContent($jobId);

        if (!$content) {
            return response()->json([
                'message' => 'Job content not found',
            ], 404);
        }

        return response()->json($content);
    }

    /**
     * Cancel a crawl job.
     *
     * DELETE /api/v1/crawler/jobs/{jobId}
     *
     * @param string $jobId
     * @param Request $request
     * @return JsonResponse
     */
    public function destroy(string $jobId, Request $request): JsonResponse
    {
        // Validate jobId format
        if (!$this->isValidJobId($jobId)) {
            return response()->json([
                'message' => 'Job not found',
            ], 404);
        }

        $user = $request->user();

        // Set current user for authorization if service supports it
        if ($this->crawlerService instanceof CrawlerService) {
            $this->crawlerService->forUser($user->id);
        }

        $result = $this->crawlerService->cancelJob($jobId);

        if ($result === null) {
            return response()->json([
                'message' => 'Job not found',
            ], 404);
        }

        if ($result === false) {
            return response()->json([
                'message' => 'Job cannot be cancelled',
            ], 409);
        }

        return response()->json([
            'jobId' => $jobId,
            'status' => 'cancelled',
            'message' => 'Job cancelled successfully',
        ], 202);
    }

    /**
     * Validate job ID format.
     *
     * Job IDs should be in format: crawl-{uuid}
     * This prevents injection attacks and ensures proper format.
     *
     * @param string $jobId
     * @return bool
     */
    protected function isValidJobId(string $jobId): bool
    {
        // Job ID format: crawl-{uuid}
        // UUID format: 8-4-4-4-12 hexadecimal characters
        return (bool) preg_match('/^crawl-[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $jobId);
    }
}
