<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\SitesApiIndexRequest;
use App\Models\ApiKey;
use App\Models\Site;
use App\Models\Keyword;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Sites API Controller for TASK-6-10.
 *
 * REST API endpoint for listing sites.
 * Uses API key authentication (not session).
 *
 * Authentication: Bearer token (API Key)
 * Rate Limiting: Per-key rate limits
 * Permission: sites:read
 *
 * @see \App\Http\Middleware\ApiKeyAuth
 * @see \App\Http\Middleware\ApiKeyRateLimiter
 */
class SitesApiController extends Controller
{
    /**
     * List sites for the authenticated user.
     *
     * GET /api/v1/sites
     */
    public function index(SitesApiIndexRequest $request): JsonResponse
    {
        $apiKey = $this->getApiKey($request);
        $user = $apiKey->user;

        // Get validated pagination parameters
        $page = $request->getPage();
        $perPage = $request->getPerPage();

        // Build query - only user's sites
        $query = Site::where('user_id', $user->id);

        // Get total count before pagination
        $total = $query->count();
        $totalPages = (int) ceil($total / $perPage);

        // Paginate
        $sites = $query->orderBy('created_at', 'desc')
            ->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get();

        // Format response with keyword count
        $data = $sites->map(function (Site $site) {
            return $this->formatSite($site);
        });

        return response()->json([
            'success' => true,
            'data' => $data,
            'meta' => [
                'total' => $total,
                'page' => $page,
                'per_page' => $perPage,
                'total_pages' => $totalPages,
            ],
        ]);
    }

    /**
     * Get the API key from the request.
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
     * Format site for API response.
     */
    protected function formatSite(Site $site): array
    {
        // Count keywords excluding deleted status
        $keywordCount = Keyword::where('site_id', $site->id)
            ->where('status', '!=', 'deleted')
            ->count();

        return [
            'id' => $site->id,
            'url' => $site->url,
            'name' => $site->name,
            'keyword_count' => $keywordCount,
            'created_at' => $site->created_at?->toISOString(),
        ];
    }
}
