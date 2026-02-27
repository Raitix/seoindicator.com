<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Annotation;
use App\Models\Keyword;
use App\Models\Project;
use App\Http\Requests\StoreAnnotationRequest;
use App\Http\Requests\UpdateAnnotationRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * Annotation API Controller
 *
 * Handles CRUD operations for keyword annotations.
 *
 * @see TASK-9-4
 */
class AnnotationController extends Controller
{
    /**
     * List annotations for a keyword.
     *
     * @param int $keywordId
     * @param Request $request
     * @return ResourceCollection
     */
    public function index(int $keywordId, Request $request): ResourceCollection
    {
        $keyword = Keyword::findOrFail($keywordId);

        // Authorization: check if user can view this keyword's annotations
        $this->authorize('viewAny', [Annotation::class, $keyword]);

        $query = Annotation::where('keyword_id', $keywordId)
            ->orderBy('date', 'desc')
            ->orderBy('created_at', 'desc');

        // Filter by date range
        if ($request->has('from') && $request->has('to')) {
            $query->whereBetween('date', [$request->from, $request->to]);
        } elseif ($request->has('from')) {
            $query->where('date', '>=', $request->from);
        } elseif ($request->has('to')) {
            $query->where('date', '<=', $request->to);
        }

        // Filter by category
        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        // Pagination
        $perPage = min($request->input('perPage', 15), 100);
        $page = $request->input('page', 1);

        $annotations = $query->paginate($perPage, ['*'], 'page', $page);

        return JsonResource::collection($annotations)
            ->additional([
                'meta' => [
                    'total' => $annotations->total(),
                    'page' => $annotations->currentPage(),
                    'perPage' => $annotations->perPage(),
                    'last_page' => $annotations->lastPage(),
                ],
            ]);
    }

    /**
     * Create a new annotation.
     *
     * @param int $keywordId
     * @param StoreAnnotationRequest $request
     * @return JsonResponse
     */
    public function store(int $keywordId, StoreAnnotationRequest $request): JsonResponse
    {
        $keyword = Keyword::findOrFail($keywordId);

        // Authorization: check if user can create annotation for this keyword
        $this->authorize('create', [Annotation::class, $keyword]);

        // Get project_id from keyword's site or use provided project_id
        $projectId = $keyword->site?->project_id ?? $request->input('project_id');
        
        if (!$projectId) {
            // Fallback: get first project for the user
            $projectId = Project::where('user_id', $request->user()->id)->first()?->id;
        }

        $annotation = Annotation::create([
            'keyword_id' => $keywordId,
            'project_id' => $projectId,
            'date' => $request->date,
            'title' => $request->title,
            'description' => $request->description,
            'category' => $request->category ?? 'other',
        ]);

        return (new JsonResource($annotation))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Show a single annotation.
     *
     * @param int $id
     * @return JsonResource
     */
    public function show(int $id): JsonResource
    {
        $annotation = Annotation::findOrFail($id);

        $this->authorize('view', $annotation);

        return new JsonResource($annotation);
    }

    /**
     * Update an annotation.
     *
     * @param int $id
     * @param UpdateAnnotationRequest $request
     * @return JsonResource
     */
    public function update(int $id, UpdateAnnotationRequest $request): JsonResource
    {
        $annotation = Annotation::findOrFail($id);

        $this->authorize('update', $annotation);

        $annotation->update($request->validated());

        return new JsonResource($annotation);
    }

    /**
     * Delete an annotation.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        $annotation = Annotation::findOrFail($id);

        $this->authorize('delete', $annotation);

        $annotation->delete();

        return response()->json(null, 204);
    }
}
