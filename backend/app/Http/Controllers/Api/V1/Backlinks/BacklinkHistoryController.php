<?php

namespace App\Http\Controllers\Api\V1\Backlinks;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Services\Backlinks\BacklinkSnapshotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controller for backlink history API endpoint.
 * 
 * TASK-23-11: Backlinks API Endpoints - History & Sync
 * 
 * Endpoint: GET /api/v1/projects/{projectId}/backlinks/history
 * 
 * Authentication: Bearer token (Sanctum session)
 * Authorization: ProjectPolicy view permission
 */
class BacklinkHistoryController extends Controller
{
    /**
     * Get historical backlink snapshots for a project.
     *
     * @param Request $request
     * @param mixed $projectId
     * @return JsonResponse
     */
    public function index(Request $request, mixed $projectId): JsonResponse
    {
        // Validate projectId is numeric
        if (!is_numeric($projectId)) {
            return response()->json([
                'message' => 'Invalid project ID.',
            ], 404);
        }

        $project = Project::findOrFail((int) $projectId);

        // Authorization via ProjectPolicy
        $this->authorize('view', $project);

        // Validate query parameters
        $validated = $request->validate([
            'from' => [
                'nullable',
                'date',
                'before_or_equal:today',
            ],
            'to' => [
                'nullable',
                'date',
                'before_or_equal:today',
            ],
        ]);

        $from = $validated['from'] ?? null;
        $to = $validated['to'] ?? null;

        // Validate that from <= to if both are provided
        if ($from && $to && $from > $to) {
            return response()->json([
                'message' => 'The from date must be before or equal to the to date.',
                'errors' => [
                    'from' => ['The from date must be before or equal to the to date.'],
                ],
            ], 422);
        }

        // Get history using the service
        $snapshotService = app(BacklinkSnapshotService::class);
        $history = $snapshotService->getHistory($project->id, $from, $to);

        return response()->json([
            'data' => $history,
        ]);
    }
}
