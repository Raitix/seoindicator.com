<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\AnalysisResource;
use App\Models\AnalysisHistory;
use App\Repositories\AnalysisHistoryRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Analysis History API Controller.
 *
 * Provides CRUD endpoints for managing analysis history records.
 * Implements IDOR protection to ensure users can only access their own analyses.
 *
 * @covers TASK-25-16
 * @see TEST-25-16
 */
class AnalysisHistoryController extends Controller
{
    /**
     * @var AnalysisHistoryRepository
     */
    protected AnalysisHistoryRepository $repository;

    /**
     * Create a new controller instance.
     *
     * @param AnalysisHistoryRepository $repository
     */
    public function __construct(AnalysisHistoryRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * List analyses for the authenticated user with optional filters.
     *
     * @param Request $request
     * @return JsonResponse
     *
     * @queryParam page int Page number (default: 1)
     * @queryParam perPage int Items per page (max: 100, default: 15)
     * @queryParam tool string Filter by tool name
     * @queryParam status string Filter by status (pending, completed, failed)
     * @queryParam from date Filter from date (Y-m-d)
     * @queryParam to date Filter to date (Y-m-d)
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'page' => 'nullable|integer|min:1',
            'perPage' => 'nullable|integer|min:1|max:100',
            'tool' => 'nullable|string',
            'status' => 'nullable|string|in:pending,completed,failed',
            'from' => 'nullable|date_format:Y-m-d',
            'to' => 'nullable|date_format:Y-m-d',
        ]);

        $userId = auth()->id();

        $filters = [
            'page' => $validated['page'] ?? 1,
            'per_page' => $validated['perPage'] ?? 15,
        ];

        if (isset($validated['tool'])) {
            $filters['tool_name'] = $validated['tool'];
        }

        if (isset($validated['status'])) {
            $filters['status'] = $validated['status'];
        }

        if (isset($validated['from'])) {
            $filters['from'] = $validated['from'];
        }

        if (isset($validated['to'])) {
            $filters['to'] = $validated['to'];
        }

        $analyses = $this->repository->findByUser($userId, $filters);

        return response()->json([
            'success' => true,
            'data' => AnalysisResource::collection($analyses->getCollection()),
            'meta' => [
                'total' => $analyses->total(),
                'page' => $analyses->currentPage(),
                'perPage' => $analyses->perPage(),
                'lastPage' => $analyses->lastPage(),
            ],
            'links' => [
                'first' => $analyses->url(1),
                'last' => $analyses->url($analyses->lastPage()),
                'prev' => $analyses->previousPageUrl(),
                'next' => $analyses->nextPageUrl(),
            ],
        ]);
    }

    /**
     * Get a single analysis by ID with cost breakdown.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        $analysis = AnalysisHistory::with('costs')->find($id);

        if (!$analysis) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Analysis not found.',
                ],
            ], 404);
        }

        // IDOR protection: Only allow access to own analyses
        $userId = auth()->id();

        if ($analysis->user_id !== null && $analysis->user_id !== $userId) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'FORBIDDEN',
                    'message' => 'You do not have permission to access this analysis.',
                ],
            ], 403);
        }

        return (new AnalysisResource($analysis))->response();
    }

    /**
     * Delete an analysis by ID.
     *
     * Cascades to delete associated costs.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        $analysis = AnalysisHistory::find($id);

        if (!$analysis) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Analysis not found.',
                ],
            ], 404);
        }

        // IDOR protection: Only allow deletion of own analyses
        $userId = auth()->id();

        if ($analysis->user_id !== null && $analysis->user_id !== $userId) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'FORBIDDEN',
                    'message' => 'You do not have permission to delete this analysis.',
                ],
            ], 403);
        }

        // Delete associated costs first (cascade delete)
        $analysis->costs()->delete();

        // Delete the analysis
        $analysis->delete();

        return response()->json([
            'success' => true,
            'message' => 'Analysis deleted successfully.',
        ]);
    }

    /**
     * Get cost breakdown for an analysis.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function costs(int $id): JsonResponse
    {
        $analysis = AnalysisHistory::with('costs')->find($id);

        if (!$analysis) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Analysis not found.',
                ],
            ], 404);
        }

        // IDOR protection: Only allow access to own analyses
        $userId = auth()->id();

        if ($analysis->user_id !== null && $analysis->user_id !== $userId) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'FORBIDDEN',
                    'message' => 'You do not have permission to access this analysis.',
                ],
            ], 403);
        }

        $costs = $analysis->costs;
        $total = $costs->sum('cost_amount');
        $currency = $costs->first()?->cost_currency ?? 'USD';

        return response()->json([
            'success' => true,
            'data' => [
                'analysisId' => $analysis->id,
                'costs' => $costs->map(fn($cost) => [
                    'id' => $cost->id,
                    'costType' => $cost->cost_type,
                    'costUnits' => $cost->cost_units,
                    'costAmount' => $cost->cost_amount,
                    'costCurrency' => $cost->cost_currency,
                    'description' => $cost->description,
                ]),
                'total' => number_format((float) $total, 6, '.', ''),
                'currency' => $currency,
            ],
        ]);
    }
}
