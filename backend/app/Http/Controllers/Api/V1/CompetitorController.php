<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\StoreCompetitorRequest;
use App\Http\Requests\V1\UpdateCompetitorRequest;
use App\Models\Competitor;
use App\Repositories\CompetitorRepository;
use App\Services\DomainValidator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Competitor Controller for SPEC-003-2.
 *
 * Handles REST API endpoints for competitor management.
 */
class CompetitorController extends Controller
{
    protected CompetitorRepository $repository;
    protected DomainValidator $domainValidator;

    /**
     * Maximum competitors per user (stub for subscription tier).
     */
    protected int $maxCompetitors = 3;

    public function __construct(CompetitorRepository $repository, DomainValidator $domainValidator)
    {
        $this->repository = $repository;
        $this->domainValidator = $domainValidator;
    }

    /**
     * List all competitors for the authenticated user.
     *
     * GET /api/v1/competitors
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $competitors = $this->repository->listByUser($user->id);

        return response()->json([
            'success' => true,
            'data' => $competitors->map(function ($competitor) {
                return $this->formatCompetitor($competitor);
            })->values()->toArray(),
        ]);
    }

    /**
     * Create a new competitor.
     *
     * POST /api/v1/competitors
     *
     * @param StoreCompetitorRequest $request
     * @return JsonResponse
     */
    public function store(StoreCompetitorRequest $request): JsonResponse
    {
        $user = $request->user();
        $domain = strtolower(trim($request->input('domain')));

        // Check for duplicate
        if ($this->repository->existsForUser($user->id, $domain)) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'COMPETITOR_EXISTS',
                    'message' => 'This competitor is already being tracked.',
                ],
            ], 409);
        }

        // Check competitor limit
        $currentCount = $this->repository->countByUser($user->id);
        if ($currentCount >= $this->maxCompetitors) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'COMPETITOR_LIMIT_EXCEEDED',
                    'message' => "Maximum {$this->maxCompetitors} competitors allowed.",
                ],
            ], 400);
        }

        // Validate domain (optional - can be disabled for testing)
        $validationResult = $this->domainValidator->validate($domain);
        if (!$validationResult['valid']) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => $validationResult['code'] ?? 'INVALID_DOMAIN',
                    'message' => $validationResult['message'] ?? 'Domain validation failed.',
                ],
            ], 400);
        }

        // Create competitor
        $competitor = $this->repository->create($user->id, [
            'domain' => $domain,
            'displayName' => $request->input('displayName'),
            'notes' => $request->input('notes'),
        ]);

        return response()->json([
            'success' => true,
            'data' => $this->formatCompetitor($competitor),
        ], 201);
    }

    /**
     * Get a single competitor.
     *
     * GET /api/v1/competitors/{id}
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $competitor = $this->repository->findForUser($id, $user->id);

        if (!$competitor) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Competitor not found.',
                ],
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->formatCompetitor($competitor),
        ]);
    }

    /**
     * Update a competitor.
     *
     * PUT /api/v1/competitors/{id}
     *
     * @param UpdateCompetitorRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(UpdateCompetitorRequest $request, int $id): JsonResponse
    {
        $user = $request->user();
        $competitor = $this->repository->findForUser($id, $user->id);

        if (!$competitor) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Competitor not found.',
                ],
            ], 404);
        }

        $this->repository->update($id, [
            'displayName' => $request->input('displayName'),
            'notes' => $request->input('notes'),
        ]);

        // Refresh the model
        $competitor = $this->repository->find($id);

        return response()->json([
            'success' => true,
            'data' => $this->formatCompetitor($competitor),
        ]);
    }

    /**
     * Delete a competitor.
     *
     * DELETE /api/v1/competitors/{id}
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $competitor = $this->repository->findForUser($id, $user->id);

        if (!$competitor) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Competitor not found.',
                ],
            ], 404);
        }

        $this->repository->delete($id);

        return response()->noContent();
    }

    /**
     * Format competitor for API response.
     *
     * @param Competitor $competitor
     * @return array
     */
    protected function formatCompetitor(Competitor $competitor): array
    {
        return [
            'id' => $competitor->id,
            'domain' => $competitor->domain,
            'displayName' => $competitor->display_name,
            'notes' => $competitor->notes,
            'createdAt' => $competitor->created_at?->toISOString(),
            'updatedAt' => $competitor->updated_at?->toISOString(),
        ];
    }
}
