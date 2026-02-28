<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AnalysisJob;
use App\Services\AnalysisJobService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\ValidationException;

/**
 * AnalysisJobController - API endpoints for analysis job management.
 *
 * Handles CRUD operations for SEO analysis jobs with real-time progress tracking.
 */
class AnalysisJobController extends Controller
{
    protected AnalysisJobService $service;

    public function __construct(AnalysisJobService $service)
    {
        $this->service = $service;
    }

    /**
     * List jobs for a site.
     *
     * @param Request $request
     * @param int $siteId
     * @return AnonymousResourceCollection
     */
    public function index(Request $request, int $siteId): AnonymousResourceCollection
    {
        $request->validate([
            'status' => 'nullable|string|in:pending,running,completed,cancelled,failed',
            'page' => 'nullable|integer|min:1',
            'perPage' => 'nullable|integer|min:1|max:100',
        ]);

        $userId = $request->user()->id;
        $perPage = $request->input('perPage', 15);
        $status = $request->input('status');

        $query = AnalysisJob::where('user_id', $userId)
            ->where('site_id', $siteId)
            ->with('results');

        if ($status) {
            $query->where('status', $status);
        }

        $jobs = $query->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return AnalysisJobResource::collection($jobs);
    }

    /**
     * Create a new analysis job.
     *
     * @param Request $request
     * @param int $siteId
     * @return JsonResponse
     */
    public function store(Request $request, int $siteId): JsonResponse
    {
        $request->validate([
            'indicators' => 'required|array|min:1',
            'indicators.*' => 'required|string|distinct',
        ]);

        $userId = $request->user()->id;
        $indicators = $request->input('indicators');

        try {
            $job = $this->service->createJob($userId, $siteId, $indicators);

            return (new AnalysisJobResource($job))
                ->response()
                ->setStatusCode(201);

        } catch (\RuntimeException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Get job progress with results.
     *
     * @param Request $request
     * @param int $jobId
     * @return JsonResponse
     */
    public function show(Request $request, int $jobId): JsonResponse
    {
        $userId = $request->user()->id;

        $progress = $this->service->getJobProgress($jobId, $userId);

        if (!$progress) {
            return response()->json([
                'message' => 'Job not found.',
            ], 404);
        }

        return response()->json($progress);
    }

    /**
     * Cancel a job.
     *
     * @param Request $request
     * @param int $jobId
     * @return JsonResponse
     */
    public function destroy(Request $request, int $jobId): JsonResponse
    {
        $userId = $request->user()->id;

        try {
            $cancelled = $this->service->cancelJob($jobId, $userId);

            if (!$cancelled) {
                return response()->json([
                    'message' => 'Failed to cancel job.',
                ], 500);
            }

            $progress = $this->service->getJobProgress($jobId, $userId);

            return response()->json($progress);

        } catch (\RuntimeException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Get available indicators.
     *
     * @return JsonResponse
     */
    public function indicators(): JsonResponse
    {
        $indicators = $this->service->getAvailableIndicators();

        $formatted = [];
        foreach ($indicators as $slug => $data) {
            $formatted[] = [
                'slug' => $slug,
                'name' => $data['name'],
                'max_score' => $data['max_score'],
            ];
        }

        return response()->json([
            'indicators' => $formatted,
        ]);
    }
}
