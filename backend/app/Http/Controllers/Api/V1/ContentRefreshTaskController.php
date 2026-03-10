<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ContentDecay\CreateTaskRequest;
use App\Services\ContentDecay\Contracts\ContentDecayCandidateServiceInterface;
use App\Services\ContentDecay\Contracts\ContentRefreshTaskServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Content Refresh Task Controller for SPEC-036.
 *
 * Endpoints for managing refresh tasks within decay candidates.
 */
class ContentRefreshTaskController extends Controller
{
    /**
     * GET /api/v1/content-decay/candidates/{candidateId}/tasks
     *
     * List tasks for a candidate.
     */
    public function index(
        int $candidateId,
        Request $request,
        ContentDecayCandidateServiceInterface $candidateService,
        ContentRefreshTaskServiceInterface $taskService
    ): JsonResponse {
        $candidate = $candidateService->getCandidate($candidateId);

        if (!$candidate) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Candidate not found.',
                ],
            ], 404);
        }

        $filters = $this->buildFilters($request);

        $tasks = $taskService->listTasks($candidateId, $filters);

        return response()->json([
            'success' => true,
            'data' => $tasks,
        ]);
    }

    /**
     * POST /api/v1/content-decay/candidates/{candidateId}/tasks
     *
     * Create a new task for a candidate.
     */
    public function store(
        int $candidateId,
        CreateTaskRequest $request,
        ContentDecayCandidateServiceInterface $candidateService,
        ContentRefreshTaskServiceInterface $taskService
    ): JsonResponse {
        $candidate = $candidateService->getCandidate($candidateId);

        if (!$candidate) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Candidate not found.',
                ],
            ], 404);
        }

        // Check if candidate is ignored - cannot create tasks
        if (isset($candidate->status) && $candidate->status === 'ignored') {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'CANDIDATE_IGNORED',
                    'message' => 'Cannot create tasks for an ignored candidate.',
                ],
            ], 400);
        }

        try {
            $task = $taskService->createTask($candidateId, $request->validated());

            return response()->json([
                'success' => true,
                'data' => $task,
            ], 201);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => $e->getMessage(),
                ],
            ], 400);
        }
    }

    /**
     * PATCH /api/v1/content-refresh-tasks/{id}
     *
     * Update a task.
     */
    public function update(
        int $id,
        Request $request,
        ContentRefreshTaskServiceInterface $taskService
    ): JsonResponse {
        $task = $taskService->getTask($id);

        if (!$task) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Task not found.',
                ],
            ], 404);
        }

        $validated = $this->validateUpdate($request);

        try {
            $updatedTask = $taskService->updateTask($id, $validated);

            return response()->json([
                'success' => true,
                'data' => $updatedTask,
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => $e->getMessage(),
                ],
            ], 400);
        }
    }

    /**
     * Build filters array from request query parameters.
     */
    private function buildFilters(Request $request): array
    {
        $filters = [];

        if ($request->has('status')) {
            $filters['status'] = $request->query('status');
        }

        if ($request->has('owner_user_id')) {
            $filters['owner_user_id'] = (int) $request->query('owner_user_id');
        }

        if ($request->has('due_date_from')) {
            $filters['due_date_from'] = $request->query('due_date_from');
        }

        if ($request->has('due_date_to')) {
            $filters['due_date_to'] = $request->query('due_date_to');
        }

        if ($request->has('sort')) {
            $filters['sort'] = $request->query('sort');
        }

        if ($request->has('order')) {
            $filters['order'] = $request->query('order');
        }

        return $filters;
    }

    /**
     * Validate task update request.
     */
    private function validateUpdate(Request $request): array
    {
        $rules = [
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string', 'max:65535'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:65535'],
            'owner_user_id' => ['sometimes', 'nullable', 'integer', 'exists:users,id'],
            'due_date' => ['sometimes', 'nullable', 'date'],
            'status' => ['sometimes', 'string', 'in:todo,in_progress,done,skipped'],
        ];

        return $request->validate($rules);
    }
}
