<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ContentDecay\UpdateCandidateRequest;
use App\Models\Site;
use App\Services\ContentDecay\Contracts\ContentDecayCandidateServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Content Decay Candidate Controller for SPEC-036.
 *
 * Endpoints for managing the decay candidate queue.
 */
class ContentDecayCandidateController extends Controller
{
    /**
     * GET /api/v1/sites/{siteId}/content-decay/candidates
     *
     * List candidates with filtering and pagination.
     */
    public function index(int $siteId, Request $request, ContentDecayCandidateServiceInterface $service): JsonResponse
    {
        $site = Site::find($siteId);

        if (!$site) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Site not found.',
                ],
            ], 404);
        }

        // Authorization: User must own the site
        if ($site->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'FORBIDDEN',
                    'message' => 'You do not have access to this site.',
                ],
            ], 403);
        }

        // Build filters from request
        $filters = $this->buildFilters($request);

        $perPage = (int) $request->query('per_page', 15);
        $perPage = max(1, min(100, $perPage));

        $paginator = $service->listCandidates($siteId, $filters, $perPage);

        return response()->json([
            'success' => true,
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
            'filters' => [
                'status' => $filters['status'] ?? null,
                'severity' => $filters['severity'] ?? null,
                'min_score' => $filters['min_score'] ?? null,
            ],
        ]);
    }

    /**
     * GET /api/v1/content-decay/candidates/{id}
     *
     * Get a single candidate with full details.
     */
    public function show(int $id, Request $request, ContentDecayCandidateServiceInterface $service): JsonResponse
    {
        $candidate = $service->getCandidate($id);

        if (!$candidate) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Candidate not found.',
                ],
            ], 404);
        }

        // Authorization: User must own the site (via project)
        // The service should return candidate with project/site info for authorization
        // For now, we check if the service returns null for unauthorized access

        return response()->json([
            'success' => true,
            'data' => $candidate,
        ]);
    }

    /**
     * PATCH /api/v1/content-decay/candidates/{id}
     *
     * Update a candidate's status and other fields.
     */
    public function update(
        int $id,
        UpdateCandidateRequest $request,
        ContentDecayCandidateServiceInterface $service
    ): JsonResponse {
        try {
            $updatedCandidate = $service->updateCandidate($id, $request->validated());

            if (!$updatedCandidate) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'NOT_FOUND',
                        'message' => 'Candidate not found.',
                    ],
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $updatedCandidate,
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
     * POST /api/v1/content-decay/candidates/{id}/restore
     *
     * Restore an ignored candidate back to backlog.
     */
    public function restore(int $id, Request $request, ContentDecayCandidateServiceInterface $service): JsonResponse
    {
        try {
            $restoredCandidate = $service->restoreCandidate($id);

            if (!$restoredCandidate) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'NOT_FOUND',
                        'message' => 'Candidate not found.',
                    ],
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $restoredCandidate,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'RESTORE_FAILED',
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

        if ($request->has('severity')) {
            $filters['severity'] = $request->query('severity');
        }

        if ($request->has('page_type')) {
            $filters['page_type'] = $request->query('page_type');
        }

        if ($request->has('reason_code')) {
            $filters['reason_code'] = $request->query('reason_code');
        }

        if ($request->has('min_lost_clicks')) {
            $filters['min_lost_clicks'] = (int) $request->query('min_lost_clicks');
        }

        if ($request->has('search')) {
            $filters['search'] = $request->query('search');
        }

        if ($request->has('sort')) {
            $filters['sort'] = $request->query('sort');
        }

        if ($request->has('order')) {
            $filters['order'] = $request->query('order');
        }

        return $filters;
    }
}
