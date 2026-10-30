<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\UsageAggregationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Admin Usage API Controller.
 *
 * Provides usage metrics for the Admin Dashboard.
 *
 * Endpoints:
 * - GET /api/v1/admin/usage/overview - Aggregated usage stats
 * - GET /api/v1/admin/usage/requests - Request counts by endpoint
 * - GET /api/v1/admin/usage/tokens - Token usage by model
 * - GET /api/v1/admin/usage/trends - Usage time series
 *
 * @see TASK-27-8
 * @see SPEC-027
 */
class UsageController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @param UsageAggregationService $usageService
     */
    public function __construct(
        protected UsageAggregationService $usageService
    ) {}

    /**
     * Get aggregated usage overview.
     *
     * GET /api/v1/admin/usage/overview
     *
     * Query Parameters:
     * - start_date: Filter from date (Y-m-d format)
     * - end_date: Filter to date (Y-m-d format)
     * - granularity: hourly, daily, weekly, monthly
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function overview(Request $request): JsonResponse
    {
        try {
            $validated = $this->validateOverviewParams($request);

            $stats = $this->usageService->getStats();

            return response()->json([
                'success' => true,
                'data' => $stats,
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => $e->getMessage(),
                    'details' => $e->errors(),
                ],
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'INTERNAL_ERROR',
                    'message' => 'An error occurred while fetching usage overview.',
                ],
            ], 500);
        }
    }

    /**
     * Get request counts by endpoint.
     *
     * GET /api/v1/admin/usage/requests
     *
     * Query Parameters:
     * - start_date: Filter from date (Y-m-d format)
     * - end_date: Filter to date (Y-m-d format)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function requests(Request $request): JsonResponse
    {
        try {
            $validated = $this->validateOverviewParams($request);

            $byEndpoint = $this->usageService->getRequestsByEndpoint();

            return response()->json([
                'success' => true,
                'data' => [
                    'byEndpoint' => $byEndpoint,
                ],
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => $e->getMessage(),
                    'details' => $e->errors(),
                ],
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'INTERNAL_ERROR',
                    'message' => 'An error occurred while fetching request stats.',
                ],
            ], 500);
        }
    }

    /**
     * Get token usage statistics.
     *
     * GET /api/v1/admin/usage/tokens
     *
     * Query Parameters:
     * - start_date: Filter from date (Y-m-d format)
     * - end_date: Filter to date (Y-m-d format)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function tokens(Request $request): JsonResponse
    {
        try {
            $validated = $this->validateOverviewParams($request);

            $stats = $this->usageService->getStats();
            $tokenStats = $stats['tokens'];

            return response()->json([
                'success' => true,
                'data' => $tokenStats,
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => $e->getMessage(),
                    'details' => $e->errors(),
                ],
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'INTERNAL_ERROR',
                    'message' => 'An error occurred while fetching token stats.',
                ],
            ], 500);
        }
    }

    /**
     * Get usage trends over time.
     *
     * GET /api/v1/admin/usage/trends
     *
     * Query Parameters:
     * - days: Number of days to show (default: 7, max: 90)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function trends(Request $request): JsonResponse
    {
        try {
            $validated = $this->validateTrendsParams($request);

            $days = $validated['days'] ?? 7;
            $trends = $this->usageService->getTokenTrends($days);

            return response()->json([
                'success' => true,
                'data' => [
                    'trends' => $trends,
                ],
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => $e->getMessage(),
                    'details' => $e->errors(),
                ],
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'INTERNAL_ERROR',
                    'message' => 'An error occurred while fetching usage trends.',
                ],
            ], 500);
        }
    }

    /**
     * Validate overview endpoint query parameters.
     *
     * @param Request $request
     * @return array
     * @throws ValidationException
     */
    protected function validateOverviewParams(Request $request): array
    {
        $rules = [
            'start_date' => ['nullable', 'date', 'date_format:Y-m-d'],
            'end_date' => ['nullable', 'date', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'granularity' => ['nullable', 'string', 'in:hourly,daily,weekly,monthly'],
        ];

        return $request->validate($rules);
    }

    /**
     * Validate trends endpoint query parameters.
     * Caps days at max of 90 instead of rejecting.
     *
     * @param Request $request
     * @return array
     * @throws ValidationException
     */
    protected function validateTrendsParams(Request $request): array
    {
        $rules = [
            'days' => ['nullable', 'integer', 'min:1'],
        ];

        $validated = $request->validate($rules);

        // Cap days at max of 90
        if (isset($validated['days']) && $validated['days'] > 90) {
            $validated['days'] = 90;
        }

        return $validated;
    }
}
