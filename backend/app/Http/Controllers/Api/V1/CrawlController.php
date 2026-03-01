<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\CrawlRequest;
use App\Services\Crawler\CrawlerServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * CrawlController - REST API endpoints for the Headless Crawler Service.
 *
 * Provides endpoints for initiating crawl jobs, checking status,
 * retrieving content, and managing crawl jobs.
 *
 * @see TASK-22-11
 * @see SPEC-022
 */
class CrawlController extends Controller
{
    /**
     * Create a new CrawlController instance.
     */
    public function __construct(
        protected CrawlerServiceInterface $crawlerService
    ) {}

    /**
     * Initiate a new crawl job.
     *
     * POST /api/v1/crawler/crawl
     *
     * @param CrawlRequest $request
     * @return JsonResponse
     */
    public function store(CrawlRequest $request): JsonResponse
    {
        $userId = $request->user()->id;
        $url = $request->input('url');
        $depth = $request->input('depth', 0);
        $options = $request->input('options', []);

        // Merge depth into options for the service
        $options['depth'] = $depth;

        $job = $this->crawlerService->createJob($userId, $url, $options);

        return response()->json($job, 202);
    }

    /**
     * Get a crawl job's status.
     *
     * GET /api/v1/crawler/jobs/{jobId}
     *
     * @param string $jobId
     * @return JsonResponse
     */
    public function show(string $jobId): JsonResponse
    {
        $job = $this->crawlerService->getJob($jobId);

        if ($job === null) {
            return response()->json([
                'message' => 'Job not found',
            ], 404);
        }

        return response()->json($job);
    }

    /**
     * Get the crawled content for a completed job.
     *
     * GET /api/v1/crawler/jobs/{jobId}/content
     *
     * @param string $jobId
     * @return JsonResponse
     */
    public function content(string $jobId): JsonResponse
    {
        // First check job status
        $job = $this->crawlerService->getJob($jobId);

        if ($job === null) {
            return response()->json([
                'message' => 'Job not found',
            ], 404);
        }

        // Check if job is in a state that allows content retrieval
        $status = $job['status'] ?? null;
        if (!in_array($status, ['completed'])) {
            return response()->json([
                'message' => 'Job content not available',
                'status' => $status,
            ], 409);
        }

        $content = $this->crawlerService->getJobContent($jobId);

        if ($content === null) {
            return response()->json([
                'message' => 'Content not found',
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
     * @return JsonResponse
     */
    public function destroy(string $jobId): JsonResponse
    {
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
        ]);
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
        $userId = $request->user()->id;

        $filters = [
            'status' => $request->input('status'),
            'page' => (int) $request->input('page', 1),
            'perPage' => (int) $request->input('perPage', 15),
            'sort' => $request->input('sort', 'created_at'),
            'order' => $request->input('order', 'desc'),
        ];

        // Validate pagination limits
        $filters['perPage'] = min($filters['perPage'], 100);
        $filters['page'] = max($filters['page'], 1);

        $paginator = $this->crawlerService->listJobs($userId, $filters);

        return response()->json([
            'data' => $paginator->items(),
            'meta' => [
                'total' => $paginator->total(),
                'page' => $paginator->currentPage(),
                'perPage' => $paginator->perPage(),
                'totalPages' => $paginator->lastPage(),
            ],
        ]);
    }
}
