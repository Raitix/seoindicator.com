<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;

/**
 * Indicators Controller (TASK-1-22)
 *
 * Placeholder endpoint for SEO indicators.
 * Returns an empty data array for MVP.
 *
 * @group Indicators
 */
class IndicatorsController
{
    /**
     * Get indicators list (placeholder)
     *
     * Returns an empty data array as a placeholder for future
     * SEO indicator functionality.
     *
     * @response {
     *   "data": []
     * }
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => [],
        ]);
    }
}
