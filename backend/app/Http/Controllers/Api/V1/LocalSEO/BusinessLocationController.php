<?php

namespace App\Http\Controllers\Api\V1\LocalSEO;

use App\Http\Controllers\Controller;
use App\Models\BusinessLocation;
use App\Models\Site;
use App\Http\Requests\Api\V1\LocalSEO\StoreBusinessLocationRequest;
use App\Http\Requests\Api\V1\LocalSEO\UpdateBusinessLocationRequest;
use App\Http\Resources\Api\V1\LocalSEO\BusinessLocationResource;
use App\Http\Resources\Api\V1\LocalSEO\BusinessLocationListResource;
use App\Services\LocalSEO\Contracts\BusinessLocationServiceInterface;
use App\Services\LocalSEO\Contracts\GoogleOAuthServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

/**
 * @Task TASK-35-13
 * @Description Business Location API Controller
 * 
 * @Security GBP tokens are NEVER exposed in API responses (SEC-009 to SEC-011)
 */
class BusinessLocationController extends Controller
{
    use AuthorizesRequests;

    protected BusinessLocationServiceInterface $locationService;
    protected GoogleOAuthServiceInterface $oauthService;

    public function __construct(
        BusinessLocationServiceInterface $locationService,
        GoogleOAuthServiceInterface $oauthService
    ) {
        $this->locationService = $locationService;
        $this->oauthService = $oauthService;
    }

    /**
     * List all business locations for a site.
     * 
     * GET /api/v1/sites/{siteId}/locations
     * 
     * @RateLimit 60/min
     */
    public function index(Request $request, int $siteId): AnonymousResourceCollection
    {
        $site = Site::findOrFail($siteId);
        $this->authorize('view', $site);

        $filters = $this->getFilters($request);
        $locations = $this->locationService->getLocations($siteId, $filters);

        return BusinessLocationListResource::collection($locations);
    }

    /**
     * Create a new business location.
     * 
     * POST /api/v1/sites/{siteId}/locations
     * 
     * @RateLimit 10/hour
     */
    public function store(StoreBusinessLocationRequest $request, int $siteId): JsonResponse
    {
        $site = Site::findOrFail($siteId);
        $this->authorize('create', [BusinessLocation::class, $site]);

        $data = $request->validated();
        $data['user_id'] = $request->user()->id;
        $data['site_id'] = $siteId;

        $location = $this->locationService->createLocation($siteId, $data);

        return (new BusinessLocationResource($location))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Get a specific business location.
     * 
     * GET /api/v1/locations/{id}
     * 
     * @RateLimit 60/min
     */
    public function show(int $id): BusinessLocationResource
    {
        $location = $this->locationService->getLocation($id);

        if (!$location) {
            abort(404, 'Location not found');
        }

        $this->authorize('view', $location);

        // Attach insights summary and review stats
        $location->insights_summary = $this->locationService->getInsightsSummary($id);
        $location->review_stats = $this->locationService->getReviewStats($id);

        return new BusinessLocationResource($location);
    }

    /**
     * Update a business location.
     * 
     * PUT /api/v1/locations/{id}
     * 
     * @RateLimit 60/min
     */
    public function update(UpdateBusinessLocationRequest $request, int $id): BusinessLocationResource
    {
        $location = $this->locationService->getLocation($id);

        if (!$location) {
            abort(404, 'Location not found');
        }

        $this->authorize('update', $location);

        $data = $request->validated();
        $location = $this->locationService->updateLocation($id, $data);

        return new BusinessLocationResource($location);
    }

    /**
     * Soft delete a business location.
     * 
     * DELETE /api/v1/locations/{id}
     * 
     * @RateLimit 60/min
     */
    public function destroy(int $id): JsonResponse
    {
        $location = $this->locationService->getLocation($id);

        if (!$location) {
            abort(404, 'Location not found');
        }

        $this->authorize('delete', $location);

        // Disconnect GBP if connected
        if ($location->gbp_connected) {
            try {
                $this->oauthService->disconnect($id);
            } catch (\Exception $e) {
                // Log but continue with deletion
                \Log::warning('Failed to disconnect GBP during location deletion', [
                    'location_id' => $id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->locationService->deleteLocation($id);

        return response()->json(null, 204);
    }

    /**
     * Initiate GBP OAuth connection flow.
     * 
     * POST /api/v1/locations/{id}/connect-gbp
     * 
     * @RateLimit 5/hour
     */
    public function connectGbp(int $id): JsonResponse
    {
        $location = $this->locationService->getLocation($id);

        if (!$location) {
            abort(404, 'Location not found');
        }

        $this->authorize('connectGbp', $location);

        $result = $this->oauthService->getAuthorizationUrl($id);

        return response()->json([
            'authorization_url' => $result['authorization_url'],
            'state' => $result['state'],
        ]);
    }

    /**
     * Handle GBP OAuth callback.
     * 
     * GET /api/v1/locations/{id}/gbp-callback
     * 
     * @RateLimit 20/hour
     */
    public function gbpCallback(Request $request, int $id)
    {
        $location = $this->locationService->getLocation($id);

        if (!$location) {
            return $this->callbackError('location_not_found', 'Location not found', $request);
        }

        $this->authorize('connectGbp', $location);

        // Check for error from Google
        if ($request->has('error')) {
            $error = $request->input('error');
            $errorDescription = $request->input('error_description', $error);
            
            return $this->callbackError($error, $errorDescription, $request);
        }

        // Validate required parameters
        $code = $request->input('code');
        $state = $request->input('state');

        if (!$code || !$state) {
            return $this->callbackError('missing_parameters', 'Missing code or state parameter', $request);
        }

        // Validate state
        if (!$this->oauthService->validateState($state)) {
            return $this->callbackError('invalid_state', 'Invalid state parameter', $request, 400);
        }

        // Handle callback
        $result = $this->oauthService->handleCallback($code, $state);

        if ($result['success']) {
            $frontendUrl = config('app.frontend_url', config('app.url'));
            return redirect()->to("{$frontendUrl}/locations/{$id}?connected=true");
        }

        $error = $result['error'] ?? 'connection_failed';
        return $this->callbackError($error, $error, $request);
    }

    /**
     * Return error response for callback - JSON or redirect based on Accept header.
     */
    protected function callbackError(string $error, string $description, Request $request, int $status = 302)
    {
        // For JSON requests, return JSON error
        if ($request->expectsJson() || $request->header('Accept') === 'application/json') {
            return response()->json([
                'success' => false,
                'error' => $error,
                'message' => $description,
            ], $status === 302 ? 400 : $status);
        }

        // For browser requests, redirect with error
        $frontendUrl = config('app.frontend_url', config('app.url'));
        $id = $request->route('id');
        return redirect()->to("{$frontendUrl}/locations/{$id}?error=" . urlencode($description));
    }

    /**
     * Disconnect GBP from a location.
     * 
     * DELETE /api/v1/locations/{id}/disconnect-gbp
     * 
     * @RateLimit 10/hour
     */
    public function disconnectGbp(int $id): JsonResponse
    {
        $location = $this->locationService->getLocation($id);

        if (!$location) {
            abort(404, 'Location not found');
        }

        $this->authorize('disconnectGbp', $location);

        // Idempotent - if not connected, still return success
        if ($location->gbp_connected) {
            $this->oauthService->disconnect($id);
        }

        return response()->json(null, 204);
    }

    /**
     * Extract filters from request.
     */
    protected function getFilters(Request $request): array
    {
        $filters = [];

        // Pagination
        $perPage = (int) $request->input('per_page', 15);
        $filters['per_page'] = min(max($perPage, 1), 100);

        // GBP connection status filter
        if ($request->has('is_gbp_connected')) {
            $filters['is_gbp_connected'] = filter_var(
                $request->input('is_gbp_connected'),
                FILTER_VALIDATE_BOOLEAN
            );
        }

        // Search filter
        if ($request->filled('search')) {
            $filters['search'] = $request->input('search');
        }

        // Sorting
        if ($request->filled('sort')) {
            $sort = $request->input('sort');
            $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';
            $field = ltrim($sort, '-');
            
            // Whitelist allowed sort fields
            $allowedFields = ['name', 'created_at', 'updated_at', 'is_gbp_connected'];
            if (in_array($field, $allowedFields)) {
                $filters['sort_field'] = $field;
                $filters['sort_direction'] = $direction;
            }
        }

        return $filters;
    }
}
