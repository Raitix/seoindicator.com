<?php

namespace App\Http\Controllers\Api\V1\AIVisibility;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\AIVisibility\StoreAIVisibilityProjectRequest;
use App\Http\Requests\V1\AIVisibility\UpdateAIVisibilityProjectRequest;
use App\Http\Resources\V1\AIVisibility\AIVisibilityProjectResource;
use App\Http\Resources\V1\AIVisibility\AIVisibilityProjectDetailResource;
use App\Models\AIVisibility\AIVisibilityProject;
use App\Services\AIVisibility\Contracts\AIVisibilityServiceInterface;
use App\Services\AIVisibility\Exceptions\QueryLimitExceededException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

/**
 * AI Visibility Project Controller.
 *
 * Handles REST API endpoints for AI Visibility project management.
 *
 * @see TASK-34-6
 * @see SPEC-034
 */
class AIVisibilityProjectController extends Controller
{
    protected AIVisibilityServiceInterface $service;

    /**
     * Create a new controller instance.
     */
    public function __construct(AIVisibilityServiceInterface $service)
    {
        $this->service = $service;
    }

    /**
     * List all AI visibility projects for the authenticated user.
     *
     * GET /api/v1/ai-visibility/projects
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $filters = [
            'is_active' => $request->input('is_active'),
            'sort_by' => $request->input('sort_by', 'created_at'),
            'sort_order' => $request->input('sort_order', 'desc'),
            'per_page' => min($request->input('per_page', 15), 100),
        ];

        $projects = $this->service->getUserProjects($user->id, $filters);

        // Add mention_rate from latest analytics
        $projectsWithMentionRate = $projects->getCollection()->map(function ($project) {
            $latestAnalytics = $project->analytics()->latest()->first();
            $mentionRate = null;

            if ($latestAnalytics) {
                $analyticsData = $latestAnalytics->analytics_data ?? [];
                $mentionRate = $analyticsData['mention_rate'] ?? null;
            }

            return array_merge(
                (new AIVisibilityProjectResource($project))->resolve(request()),
                ['mention_rate' => $mentionRate]
            );
        });

        return response()->json([
            'success' => true,
            'data' => $projectsWithMentionRate,
            'meta' => [
                'current_page' => $projects->currentPage(),
                'last_page' => $projects->lastPage(),
                'per_page' => $projects->perPage(),
                'total' => $projects->total(),
            ],
        ]);
    }

    /**
     * Create a new AI visibility project.
     *
     * POST /api/v1/ai-visibility/projects
     *
     * @param StoreAIVisibilityProjectRequest $request
     * @return JsonResponse
     */
    public function store(StoreAIVisibilityProjectRequest $request): JsonResponse
    {
        $user = $request->user();

        // Transform competitors from array of strings to expected format
        $data = $request->validated();
        $data['competitors'] = collect($data['competitors'] ?? [])
            ->map(fn($name) => ['name' => $name])
            ->toArray();

        try {
            $project = $this->service->createProject($user->id, $data);

            return response()->json([
                'success' => true,
                'data' => new AIVisibilityProjectResource($project),
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'The given data was invalid.',
                    'details' => $e->errors(),
                ],
            ], 422);
        }
    }

    /**
     * Get a single AI visibility project with analytics summary.
     *
     * GET /api/v1/ai-visibility/projects/{id}
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $project = $this->service->getProject($id, $request->user()->id);

        if (!$project) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Project not found.',
                ],
            ], 404);
        }

        // Get latest analytics summary
        $analyticsSummary = null;
        try {
            $analytics = $this->service->getAnalytics($project->id, 'week');
            $analyticsSummary = [
                'mention_rate_by_platform' => $analytics['mention_rate_by_platform'] ?? [],
                'share_of_voice' => $analytics['share_of_voice'] ?? 0,
                'sentiment_breakdown' => $analytics['sentiment_breakdown'] ?? [],
                'trend_direction' => $analytics['trend_direction'] ?? 'stable',
                'total_queries' => $analytics['total_queries'] ?? 0,
                'brand_mentions' => $analytics['brand_mentions'] ?? 0,
            ];
        } catch (\Exception $e) {
            // Analytics not available yet
        }

        $project->analytics_summary = $analyticsSummary;

        return response()->json([
            'success' => true,
            'data' => new AIVisibilityProjectDetailResource($project),
        ]);
    }

    /**
     * Update an AI visibility project.
     *
     * PUT /api/v1/ai-visibility/projects/{id}
     *
     * @param UpdateAIVisibilityProjectRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(UpdateAIVisibilityProjectRequest $request, int $id): JsonResponse
    {
        $project = AIVisibilityProject::find($id);

        if (!$project) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Project not found.',
                ],
            ], 404);
        }

        // Authorization check
        if (Gate::denies('update', $project)) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'FORBIDDEN',
                    'message' => 'You do not have permission to update this project.',
                ],
            ], 403);
        }

        // Transform competitors from array of strings to expected format
        $data = $request->validated();
        if (isset($data['competitors'])) {
            $data['competitors'] = collect($data['competitors'])
                ->map(fn($name) => ['name' => $name])
                ->toArray();
        }

        try {
            $project = $this->service->updateProject($project->id, $data);

            return response()->json([
                'success' => true,
                'data' => new AIVisibilityProjectResource($project),
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'The given data was invalid.',
                    'details' => $e->errors(),
                ],
            ], 422);
        }
    }

    /**
     * Delete an AI visibility project.
     *
     * DELETE /api/v1/ai-visibility/projects/{id}
     *
     * @param Request $request
     * @param int $id
     * @return Response
     */
    public function destroy(Request $request, int $id): Response
    {
        $project = AIVisibilityProject::find($id);

        if (!$project) {
            abort(404, 'Project not found.');
        }

        // Authorization check
        if (Gate::denies('delete', $project)) {
            abort(403, 'You do not have permission to delete this project.');
        }

        $userId = $request->user()->id;
        $this->service->deleteProject($project->id, $userId);

        return response()->noContent();
    }

    /**
     * Manually trigger query run for a project.
     *
     * POST /api/v1/ai-visibility/projects/{id}/run
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function run(Request $request, int $id): JsonResponse
    {
        $project = AIVisibilityProject::find($id);

        if (!$project) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Project not found.',
                ],
            ], 404);
        }

        // Authorization check
        if (Gate::denies('runQueries', $project)) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'FORBIDDEN',
                    'message' => 'You do not have permission to run queries for this project.',
                ],
            ], 403);
        }

        try {
            $jobIds = $this->service->runQueries($project->id);

            return response()->json([
                'success' => true,
                'data' => [
                    'job_ids' => $jobIds,
                    'message' => 'Queries dispatched successfully.',
                ],
            ], 202);
        } catch (QueryLimitExceededException $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'QUERY_LIMIT_EXCEEDED',
                    'message' => $e->getMessage(),
                    'details' => [
                        'limit' => $e->limit,
                        'used' => $e->used,
                        'tier' => $e->tier,
                        'remaining' => $e->remaining,
                    ],
                ],
            ], 429);
        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'PROJECT_INACTIVE',
                    'message' => $e->getMessage(),
                ],
            ], 400);
        }
    }
}
