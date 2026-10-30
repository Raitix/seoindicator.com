<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\IssueType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * IssueType Controller for TASK-14-3.
 *
 * Handles REST API endpoints for SEO issue type templates.
 * Issue types are reference data used by the audit system.
 */
class IssueTypeController extends Controller
{
    /**
     * List all issue types.
     *
     * GET /api/v1/issue-types
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $query = IssueType::query();

        // Filter by category
        if ($request->has('category')) {
            $query->where('category', $request->input('category'));
        }

        // Filter by severity
        if ($request->has('severity')) {
            $query->where('severity_default', $request->input('severity'));
        }

        // Search by name
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Sort
        $sortField = $request->input('sort', 'category');
        $sortDirection = $request->input('direction', 'asc');
        $query->orderBy($sortField, $sortDirection);

        // Pagination (optional)
        if ($request->has('per_page')) {
            $perPage = min($request->input('per_page', 15), 100);
            $issueTypes = $query->paginate($perPage);
            
            return response()->json([
                'success' => true,
                'data' => $issueTypes->map(fn ($type) => $this->formatIssueType($type))->values()->toArray(),
                'meta' => [
                    'current_page' => $issueTypes->currentPage(),
                    'last_page' => $issueTypes->lastPage(),
                    'per_page' => $issueTypes->perPage(),
                    'total' => $issueTypes->total(),
                ],
            ]);
        }

        $issueTypes = $query->get();

        return response()->json([
            'success' => true,
            'data' => $issueTypes->map(fn ($type) => $this->formatIssueType($type))->values()->toArray(),
        ]);
    }

    /**
     * Get available categories.
     *
     * GET /api/v1/issue-types/categories
     *
     * @return JsonResponse
     */
    public function categories(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => IssueType::categories(),
        ]);
    }

    /**
     * Create a new issue type.
     *
     * POST /api/v1/issue-types
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'category' => ['required', 'string', 'max:50', Rule::in(array_keys(IssueType::categories()))],
            'name' => ['required', 'string', 'max:100'],
            'description' => ['required', 'string'],
            'severity_default' => ['required', 'string', Rule::in(['critical', 'warning', 'notice'])],
            'recommendation_template' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'Invalid input data.',
                    'details' => $validator->errors()->toArray(),
                ],
            ], 422);
        }

        // Check for duplicate (category + name must be unique)
        $exists = IssueType::where('category', $request->input('category'))
            ->where('name', $request->input('name'))
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'ISSUE_TYPE_EXISTS',
                    'message' => 'An issue type with this category and name already exists.',
                ],
            ], 409);
        }

        $issueType = IssueType::create([
            'category' => $request->input('category'),
            'name' => $request->input('name'),
            'description' => $request->input('description'),
            'severity_default' => $request->input('severity_default'),
            'recommendation_template' => $request->input('recommendation_template'),
        ]);

        return response()->json([
            'success' => true,
            'data' => $this->formatIssueType($issueType),
        ], 201);
    }

    /**
     * Get a single issue type.
     *
     * GET /api/v1/issue-types/{id}
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        $issueType = IssueType::find($id);

        if (!$issueType) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Issue type not found.',
                ],
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->formatIssueType($issueType),
        ]);
    }

    /**
     * Update an issue type.
     *
     * PUT /api/v1/issue-types/{id}
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $issueType = IssueType::find($id);

        if (!$issueType) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Issue type not found.',
                ],
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'category' => ['sometimes', 'string', 'max:50', Rule::in(array_keys(IssueType::categories()))],
            'name' => ['sometimes', 'string', 'max:100'],
            'description' => ['sometimes', 'string'],
            'severity_default' => ['sometimes', 'string', Rule::in(['critical', 'warning', 'notice'])],
            'recommendation_template' => ['sometimes', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'Invalid input data.',
                    'details' => $validator->errors()->toArray(),
                ],
            ], 422);
        }

        // Check for duplicate if category or name is being changed
        $category = $request->input('category', $issueType->category);
        $name = $request->input('name', $issueType->name);

        if ($category !== $issueType->category || $name !== $issueType->name) {
            $exists = IssueType::where('category', $category)
                ->where('name', $name)
                ->where('id', '!=', $id)
                ->exists();

            if ($exists) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'ISSUE_TYPE_EXISTS',
                        'message' => 'An issue type with this category and name already exists.',
                    ],
                ], 409);
            }
        }

        $issueType->update($request->only([
            'category',
            'name',
            'description',
            'severity_default',
            'recommendation_template',
        ]));

        return response()->json([
            'success' => true,
            'data' => $this->formatIssueType($issueType->fresh()),
        ]);
    }

    /**
     * Delete an issue type.
     *
     * DELETE /api/v1/issue-types/{id}
     *
     * @param int $id
     * @return Response
     */
    public function destroy(int $id): Response
    {
        $issueType = IssueType::find($id);

        if (!$issueType) {
            abort(404, 'Issue type not found.');
        }

        // Check if issue type is in use
        $inUse = $issueType->auditIssues()->exists();
        if ($inUse) {
            abort(409, 'Cannot delete issue type that is in use by audit issues.');
        }

        $issueType->delete();

        return response()->noContent();
    }

    /**
     * Format issue type for API response.
     *
     * @param IssueType $issueType
     * @return array
     */
    protected function formatIssueType(IssueType $issueType): array
    {
        return [
            'id' => $issueType->id,
            'category' => $issueType->category,
            'categoryLabel' => IssueType::categories()[$issueType->category] ?? $issueType->category,
            'name' => $issueType->name,
            'description' => $issueType->description,
            'severityDefault' => $issueType->severity_default,
            'recommendationTemplate' => $issueType->recommendation_template,
            'createdAt' => $issueType->created_at?->toISOString(),
            'updatedAt' => $issueType->updated_at?->toISOString(),
        ];
    }
}
