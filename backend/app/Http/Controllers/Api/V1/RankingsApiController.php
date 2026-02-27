<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ApiKey;
use App\Models\Keyword;
use App\Models\Ranking;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;

/**
 * Rankings API Controller for TASK-6-9.
 *
 * REST API endpoint for reading keyword ranking history.
 * Uses API key authentication (not session).
 *
 * Authentication: Bearer token (API Key)
 * Rate Limiting: Per-key rate limits
 * Permission: rankings:read
 *
 * @see \App\Http\Middleware\ApiKeyAuth
 * @see \App\Http\Middleware\ApiKeyRateLimiter
 */
class RankingsApiController extends Controller
{
    /**
     * Default limit for ranking history results.
     */
    protected int $defaultLimit = 100;

    /**
     * Maximum allowed limit for ranking history results.
     */
    protected int $maxLimit = 1000;

    /**
     * Get ranking history for a keyword.
     *
     * GET /api/v1/keywords/{id}/rankings
     *
     * Query Parameters:
     * - from: ISO 8601 date (optional) - filter from this date
     * - to: ISO 8601 date (optional) - filter to this date
     * - limit: integer 1-1000 (optional, default 100)
     *
     * @param Request $request
     * @param int $id Keyword ID
     * @return JsonResponse
     */
    public function index(Request $request, int $id): JsonResponse
    {
        $apiKey = $this->getApiKey($request);
        $user = $apiKey->user;

        // Find the keyword
        $keyword = Keyword::find($id);

        if (!$keyword) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Keyword not found.',
                ],
            ], 404);
        }

        // Check authorization - user must own the keyword
        if ($keyword->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'FORBIDDEN',
                    'message' => 'You do not have access to this keyword.',
                ],
            ], 403);
        }

        // Validate query parameters
        $validator = Validator::make($request->query(), [
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:' . $this->maxLimit],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'Invalid query parameters.',
                ],
                'errors' => $validator->errors(),
            ], 422);
        }

        // Parse parameters
        $from = $request->query('from') ? $this->parseDate($request->query('from')) : null;
        $to = $request->query('to') ? $this->parseDate($request->query('to')) : null;
        $limit = min($this->maxLimit, max(1, (int) $request->query('limit', $this->defaultLimit)));

        // Validate date range
        if ($from && $to && $from->gt($to)) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'The from date must be before or equal to the to date.',
                ],
            ], 422);
        }

        // Build query
        $query = Ranking::where('keyword_id', $keyword->id);

        // Apply date filters
        if ($from) {
            $query->where('checked_at', '>=', $from);
        }

        if ($to) {
            // Include the entire day for the 'to' date
            $query->where('checked_at', '<=', $to->endOfDay());
        }

        // Order by checked_at DESC (most recent first) and limit
        $rankings = $query->orderBy('checked_at', 'desc')
            ->take($limit)
            ->get();

        // Format response
        $history = $rankings->map(function (Ranking $ranking) {
            return [
                'position' => $ranking->position,
                'checked_at' => $ranking->checked_at?->toISOString(),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'keyword_id' => $keyword->id,
                'history' => $history,
            ],
        ]);
    }

    /**
     * Get the API key from the request.
     *
     * @param Request $request
     * @return ApiKey
     */
    protected function getApiKey(Request $request): ApiKey
    {
        $apiKey = $request->attributes->get('api_key')
            ?? $request->attributes->get('current_api_key');

        if (!$apiKey instanceof ApiKey) {
            abort(401, 'API key not found in request.');
        }

        return $apiKey;
    }

    /**
     * Parse a date string into a Carbon instance.
     *
     * Handles ISO 8601 dates with timezone and milliseconds.
     *
     * @param string $dateString
     * @return Carbon|null
     */
    protected function parseDate(string $dateString): ?Carbon
    {
        try {
            return Carbon::parse($dateString);
        } catch (\Exception $e) {
            return null;
        }
    }
}
