<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\AnalysisJobResource;
use App\Models\AnalysisJob;
use App\Models\Site;
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
     * @param mixed $siteId
     * @return AnonymousResourceCollection|JsonResponse
     */
    public function index(Request $request, $siteId): AnonymousResourceCollection|JsonResponse
    {
        // Validate siteId is numeric
        if (!is_numeric($siteId)) {
            return response()->json(['error' => 'Site not found.'], 404);
        }

        $siteId = (int) $siteId;
        $userId = $request->user()->id;

        // Check site exists and belongs to user
        $site = Site::find($siteId);
        if (!$site) {
            return response()->json(['error' => 'Site not found.'], 404);
        }
        if ($site->user_id !== $userId) {
            return response()->json(['error' => 'You do not have access to this site.'], 403);
        }

        $request->validate([
            'status' => 'nullable|string|in:pending,running,completed,cancelled,failed',
            'page' => 'nullable|integer|min:1',
            'perPage' => 'nullable|integer|min:1|max:100',
        ]);

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
     * @param mixed $siteId
     * @return JsonResponse
     */
    public function store(Request $request, $siteId): JsonResponse
    {
        // Validate siteId is numeric
        if (!is_numeric($siteId)) {
            return response()->json(['error' => 'Site not found.'], 404);
        }

        $siteId = (int) $siteId;
        $userId = $request->user()->id;

        // Check site exists and belongs to user
        $site = Site::find($siteId);
        if (!$site) {
            return response()->json(['error' => 'Site not found.'], 404);
        }
        if ($site->user_id !== $userId) {
            return response()->json(['error' => 'You do not have access to this site.'], 403);
        }

        $request->validate([
            'indicators' => 'required|array|min:1',
            'indicators.*' => 'required|string|distinct',
        ]);

        $indicators = $request->input('indicators');

        try {
            $job = $this->service->createJob($userId, $siteId, $indicators);
            $job->load('results');

            return response()->json([
                'data' => new AnalysisJobResource($job),
            ], 201);

        } catch (\RuntimeException $e) {
            $message = $e->getMessage();
            
            // Check if this is a rate limit error
            if (str_contains($message, 'limit') || str_contains($message, 'rate')) {
                return response()->json([
                    'error' => $message,
                ], 429)->header('Retry-After', 60);
            }
            
            return response()->json([
                'error' => $message,
            ], 422);
        }
    }

    /**
     * Get job progress with results.
     *
     * @param Request $request
     * @param mixed $jobId
     * @return JsonResponse
     */
    public function show(Request $request, $jobId): JsonResponse
    {
        // Validate jobId is numeric
        if (!is_numeric($jobId)) {
            return response()->json(['error' => 'Job not found.'], 404);
        }

        $jobId = (int) $jobId;
        $userId = $request->user()->id;

        $job = AnalysisJob::with('results')->find($jobId);

        if (!$job) {
            return response()->json(['error' => 'Job not found.'], 404);
        }

        // Check ownership
        if ($job->user_id !== $userId) {
            return response()->json(['error' => 'You do not have access to this job.'], 403);
        }

        return response()->json([
            'data' => new AnalysisJobResource($job),
        ]);
    }

    /**
     * Cancel a job.
     *
     * @param Request $request
     * @param mixed $jobId
     * @return JsonResponse
     */
    public function destroy(Request $request, $jobId): JsonResponse
    {
        // Validate jobId is numeric
        if (!is_numeric($jobId)) {
            return response()->json(['error' => 'Job not found.'], 404);
        }

        $jobId = (int) $jobId;
        $userId = $request->user()->id;

        $job = AnalysisJob::with('results')->find($jobId);

        if (!$job) {
            return response()->json(['error' => 'Job not found.'], 404);
        }

        // Check ownership
        if ($job->user_id !== $userId) {
            return response()->json(['error' => 'You do not have access to this job.'], 403);
        }

        // Check if job can be cancelled
        if (in_array($job->status, ['completed', 'cancelled', 'failed'])) {
            return response()->json([
                'error' => "Cannot cancel a job with status: {$job->status}",
            ], 400);
        }

        // Cancel the job
        $job->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);

        return response()->json([
            'data' => new AnalysisJobResource($job->fresh(['results'])),
        ]);
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

    /**
     * SSE stream for real-time job progress updates.
     *
     * @param Request $request
     * @param mixed $jobId
     * @return \Illuminate\Http\Response
     */
    public function stream(Request $request, $jobId)
    {
        if (!is_numeric($jobId)) {
            return response()->json(['error' => 'Job not found.'], 404);
        }

        $jobId = (int) $jobId;
        $userId = $request->user()->id;

        // Verify user owns the job
        if (!$this->service->canUserAccessJob($userId, $jobId)) {
            return response()->json([
                'error' => 'Job not found.',
            ], 404);
        }

        return response()->stream(function () use ($jobId) {
            $lastEventId = 0;
            $heartbeatInterval = 15;
            $lastHeartbeat = time();

            while (true) {
                // Check connection
                if (connection_aborted()) {
                    break;
                }

                // Get current job state
                $progress = $this->service->getJobProgress($jobId);

                if (!$progress) {
                    $this->sendEvent('error', ['message' => 'Job not found'], $lastEventId);
                    break;
                }

                // Send progress update
                $this->sendEvent('progress', [
                    'job_id' => $progress['id'],
                    'completed_indicators' => $progress['completed_indicators'],
                    'total_indicators' => $progress['total_indicators'],
                    'progress_percent' => $progress['progress_percent'],
                    'status' => $progress['status'],
                ], ++$lastEventId);

                // Check if job is complete
                if (in_array($progress['status'], ['completed', 'cancelled', 'failed'])) {
                    $this->sendEvent('complete', [
                        'status' => $progress['status'],
                        'total_indicators' => $progress['total_indicators'],
                        'completed_indicators' => $progress['completed_indicators'],
                        'failed_indicators' => $progress['failed_indicators'],
                    ], ++$lastEventId);
                    break;
                }

                // Heartbeat to keep connection alive
                if (time() - $lastHeartbeat >= $heartbeatInterval) {
                    $this->sendEvent('heartbeat', ['time' => time()], ++$lastEventId);
                    $lastHeartbeat = time();
                }

                // Flush output
                if (ob_get_level() > 0) {
                    ob_flush();
                }
                flush();

                // Wait before next update
                sleep(1);
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'X-Accel-Buffering' => 'no',
            'Connection' => 'keep-alive',
        ]);
    }

    /**
     * Polling endpoint for job progress (fallback for SSE).
     *
     * @param Request $request
     * @param mixed $jobId
     * @return JsonResponse
     */
    public function progress(Request $request, $jobId): JsonResponse
    {
        if (!is_numeric($jobId)) {
            return response()->json(['error' => 'Job not found.'], 404);
        }

        $jobId = (int) $jobId;
        $userId = $request->user()->id;

        $progress = $this->service->getJobProgress($jobId, $userId);

        if (!$progress) {
            return response()->json([
                'error' => 'Job not found.',
            ], 404);
        }

        return response()->json($progress)
            ->header('Cache-Control', 'no-store')
            ->header('X-Polling-Interval', '2');
    }

    /**
     * Start processing a job.
     *
     * @param Request $request
     * @param mixed $jobId
     * @return JsonResponse
     */
    public function start(Request $request, $jobId): JsonResponse
    {
        if (!is_numeric($jobId)) {
            return response()->json(['error' => 'Job not found.'], 404);
        }

        $jobId = (int) $jobId;
        $userId = $request->user()->id;

        // Verify user owns the job
        if (!$this->service->canUserAccessJob($userId, $jobId)) {
            return response()->json([
                'error' => 'Job not found.',
            ], 404);
        }

        try {
            $job = $this->service->getJob($jobId);

            if (!$job->isPending()) {
                return response()->json([
                    'error' => 'Job is not in pending state.',
                ], 422);
            }

            // Dispatch the start job
            \App\Jobs\StartAnalysisJob::dispatch($jobId);

            return response()->json([
                'message' => 'Job started.',
                'job_id' => $jobId,
            ]);

        } catch (\RuntimeException $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Send an SSE event.
     *
     * @param string $event
     * @param array $data
     * @param int $id
     */
    protected function sendEvent(string $event, array $data, int $id): void
    {
        echo "id: {$id}\n";
        echo "event: {$event}\n";
        echo "data: " . json_encode($data) . "\n\n";
    }
}
