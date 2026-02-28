<?php

namespace App\Http\Controllers\Api\V1\Backlinks;

use App\Http\Controllers\Controller;
use App\Jobs\SyncBacklinksJob;
use App\Models\GSCConnection;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Controller for backlink sync API endpoint.
 * 
 * TASK-23-11: Backlinks API Endpoints - History & Sync
 * 
 * Endpoint: POST /api/v1/projects/{projectId}/backlinks/sync
 * 
 * Authentication: Bearer token (Sanctum session)
 * Authorization: ProjectPolicy update permission
 * Rate Limiting: 1 request per project per hour
 */
class BacklinkSyncController extends Controller
{
    /**
     * Rate limit duration in seconds (1 hour).
     */
    protected const RATE_LIMIT_SECONDS = 3600;

    /**
     * Trigger a backlink sync for a project.
     *
     * @param mixed $projectId
     * @return JsonResponse
     */
    public function store(mixed $projectId): JsonResponse
    {
        // Validate projectId is numeric
        if (!is_numeric($projectId)) {
            return response()->json([
                'message' => 'Invalid project ID.',
            ], 404);
        }

        $project = Project::findOrFail((int) $projectId);

        // Authorization via ProjectPolicy (requires update permission)
        $this->authorize('update', $project);

        // Check if project is archived
        if ($project->isArchived()) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'PROJECT_ARCHIVED',
                    'message' => 'Cannot sync backlinks for an archived project.',
                ],
            ], 403);
        }

        // Check if project has a domain
        if (!$project->domain) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'PRECONDITION_FAILED',
                    'message' => 'Project must have a domain configured before syncing backlinks.',
                ],
            ], 422);
        }

        // Check for active GSC connection
        $hasGscConnection = GSCConnection::where('user_id', $project->user_id)
            ->active()
            ->where(function ($query) use ($project) {
                $domain = parse_url($project->domain, PHP_URL_HOST) ?? $project->domain;
                $query->where('site_url', $domain)
                    ->orWhere('property_url', $project->domain)
                    ->orWhere('property_url', 'like', '%' . $domain . '%');
            })
            ->exists();

        if (!$hasGscConnection) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'PRECONDITION_FAILED',
                    'message' => 'Project must have an active Google Search Console connection.',
                ],
            ], 422);
        }

        // Check rate limit
        $rateLimitKey = $this->getRateLimitKey($project->id);
        $lastSync = Cache::get($rateLimitKey);

        if ($lastSync) {
            $retryAfter = now()->addSeconds(self::RATE_LIMIT_SECONDS)->toIso8601String();
            
            // If the cached value is a timestamp, calculate retry_after
            if (is_string($lastSync) && strtotime($lastSync)) {
                $lastSyncTime = strtotime($lastSync);
                $retryAfterTime = $lastSyncTime + self::RATE_LIMIT_SECONDS;
                $retryAfter = now()->setTimestamp($retryAfterTime)->toIso8601String();
            }

            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'RATE_LIMIT_EXCEEDED',
                    'message' => 'Sync already in progress or recently completed. Please wait before requesting another sync.',
                    'retry_after' => $retryAfter,
                ],
            ], 429);
        }

        // Generate a unique job ID
        $jobId = 'backlink-sync-' . Str::uuid()->toString();

        // Dispatch the sync job
        SyncBacklinksJob::dispatch($project->id);

        // Set rate limit cache
        Cache::put($rateLimitKey, now()->toIso8601String(), self::RATE_LIMIT_SECONDS);

        return response()->json([
            'success' => true,
            'data' => [
                'job_id' => $jobId,
                'status' => 'queued',
                'estimated_duration' => '2-5 minutes',
            ],
        ]);
    }

    /**
     * Get the rate limit cache key for a project.
     *
     * @param int $projectId
     * @return string
     */
    protected function getRateLimitKey(int $projectId): string
    {
        return "backlink-sync:{$projectId}";
    }
}
