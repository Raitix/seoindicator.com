<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Http\Requests\ProjectRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Project CRUD API Controller.
 *
 * Handles Create, Read, Update, Delete operations for projects.
 * Each user can only manage their own projects (enforced via ProjectPolicy).
 *
 * @see TASK-8-7
 */
class ProjectController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->authorizeResource(Project::class, 'project');
    }

    /**
     * Display a listing of the user's projects.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $query = $request->user()->projects();

        // Filter by archived status
        if ($request->has('archived')) {
            $archived = filter_var($request->archived, FILTER_VALIDATE_BOOLEAN);
            if ($archived) {
                $query->archived();
            } else {
                $query->active();
            }
        }

        // Get pagination parameters
        $perPage = (int) ($request->perPage ?? 15);

        $projects = $query->orderBy('created_at', 'desc')->paginate($perPage);

        // Calculate metadata
        $total = $request->user()->projects()->count();
        $active = $request->user()->projects()->active()->count();
        $archived = $request->user()->projects()->archived()->count();

        return response()->json([
            'success' => true,
            'data' => $projects->items(),
            'meta' => [
                'total' => $total,
                'active' => $active,
                'archived' => $archived,
                'page' => $projects->currentPage(),
                'perPage' => $projects->perPage(),
            ],
        ]);
    }

    /**
     * Store a newly created project.
     *
     * @param ProjectRequest $request
     * @return JsonResponse
     */
    public function store(ProjectRequest $request): JsonResponse
    {
        $project = $request->user()->projects()->create($request->validated());

        return response()->json([
            'success' => true,
            'data' => $project,
        ], 201);
    }

    /**
     * Display the specified project.
     *
     * @param Project $project
     * @return JsonResponse
     */
    public function show(Project $project): JsonResponse
    {
        // Note: keywords_count, competitors_count, reports_count available
        // once project relationships are established (TASK-8-2)

        return response()->json([
            'success' => true,
            'data' => $project,
        ]);
    }

    /**
     * Update the specified project.
     *
     * @param ProjectRequest $request
     * @param Project $project
     * @return JsonResponse
     */
    public function update(ProjectRequest $request, Project $project): JsonResponse
    {
        $project->update($request->validated());

        return response()->json([
            'success' => true,
            'data' => $project->fresh(),
        ]);
    }

    /**
     * Remove the specified project.
     *
     * Requires confirmation to prevent accidental deletion.
     *
     * @param Request $request
     * @param Project $project
     * @return JsonResponse
     */
    public function destroy(Request $request, Project $project): JsonResponse
    {
        // Require confirmation to prevent accidental deletion
        if ($request->confirm !== true && $request->confirm !== 'true') {
            return response()->json([
                'success' => false,
                'message' => 'Confirmation required. Add ?confirm=true to delete.',
            ], 400);
        }

        $projectId = $project->id;
        $project->delete();

        return response()->json([
            'success' => true,
            'message' => 'Project deleted successfully.',
            'deleted_id' => $projectId,
        ]);
    }

    /**
     * Archive the specified project.
     *
     * @param Project $project
     * @return JsonResponse
     */
    public function archive(Project $project): JsonResponse
    {
        if (!$project->isArchived()) {
            $project->archive();
        }

        return response()->json([
            'success' => true,
            'data' => $project->fresh(),
        ]);
    }

    /**
     * Restore an archived project.
     *
     * @param Project $project
     * @return JsonResponse
     */
    public function restore(Project $project): JsonResponse
    {
        if ($project->isArchived()) {
            $project->unarchive();
        }

        return response()->json([
            'success' => true,
            'data' => $project->fresh(),
        ]);
    }
}
