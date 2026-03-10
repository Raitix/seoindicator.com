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
use App\Services\LocalSEO\Contracts\GBPInsightsServiceInterface;
use App\Services\LocalSEO\Contracts\GBPReviewsServiceInterface;
use App\Services\LocalSEO\Contracts\GoogleOAuthServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

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

        $location = $this->locationService->createLocation($data);

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
     * Trigger a dashboard refresh for a business location.
     *
     * POST /api/v1/locations/{id}/sync
     *
     * @RateLimit 3/hour
     */
    public function sync(
        int $id,
        GBPInsightsServiceInterface $insightsService,
        GBPReviewsServiceInterface $reviewsService
    ): JsonResponse {
        $location = $this->locationService->getLocation($id);

        if (!$location) {
            abort(404, 'Location not found');
        }

        $this->authorize('syncGbp', $location);

        if (!$location->gbp_connected) {
            return response()->json([
                'message' => 'Google Business Profile is not connected for this location.',
            ], 409);
        }

        try {
            $insightsJobId = $insightsService->syncInsights($id);
            $reviewsJobId = $reviewsService->syncReviews($id);

            return response()->json([
                'message' => 'Location dashboard refresh initiated.',
                'data' => [
                    'insights_job_id' => $insightsJobId,
                    'reviews_job_id' => $reviewsJobId,
                ],
            ], 202);
        } catch (RuntimeException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 409);
        } catch (Throwable $exception) {
            Log::error('Failed to trigger business location dashboard refresh', [
                'location_id' => $id,
                'error' => $exception->getMessage(),
            ]);

            return response()->json([
                'message' => 'Failed to refresh location data.',
            ], 500);
        }
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

            if ($request->expectsJson() || $request->header('Accept') === 'application/json') {
                return redirect()->to("{$frontendUrl}/locations/{$id}?connected=true");
            }

            return $this->callbackBridgeResponse(
                [
                    'success' => true,
                    'connected' => true,
                    'state' => $state,
                    'locationId' => $id,
                ],
                'Google Business Profile connected',
                'Connection complete. You can close this window.'
            );
        }

        $error = $result['error'] ?? 'connection_failed';
        return $this->callbackError($error, $error, $request, 400);
    }

    /**
     * Return error response for callback - JSON or popup bridge based on Accept header.
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

        return $this->callbackBridgeResponse(
            [
                'success' => false,
                'error' => $error,
                'message' => $description,
                'state' => $request->input('state'),
                'locationId' => $request->route('id'),
            ],
            'Google Business Profile connection failed',
            $description,
            $status === 302 ? 400 : $status
        );
    }

    /**
     * Render a small popup bridge page that notifies the opener via postMessage and closes itself.
     */
    protected function callbackBridgeResponse(array $payload, string $title, string $message, int $status = 200)
    {
        $fullPayload = array_merge([
            'type' => 'gbp_oauth_callback',
        ], $payload);

        $payloadJson = json_encode($fullPayload, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
        $safeTitle = e($title);
        $safeMessage = e($message);

        $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{$safeTitle}</title>
    <style>
        body { font-family: Inter, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; background: #020617; color: #e2e8f0; display: grid; place-items: center; min-height: 100vh; margin: 0; padding: 24px; }
        .card { max-width: 420px; width: 100%; border: 1px solid rgba(148, 163, 184, 0.2); background: rgba(15, 23, 42, 0.92); border-radius: 20px; padding: 24px; box-shadow: 0 24px 80px rgba(2, 6, 23, 0.45); }
        h1 { margin: 0 0 12px; font-size: 20px; }
        p { margin: 0; color: #cbd5e1; line-height: 1.6; }
    </style>
</head>
<body>
    <div class="card">
        <h1>{$safeTitle}</h1>
        <p id="message">{$safeMessage}</p>
    </div>
    <script>
        (function () {
            var payload = {$payloadJson};

            try {
                if (window.opener && !window.opener.closed) {
                    window.opener.postMessage(payload, window.location.origin);
                    window.close();
                    return;
                }
            } catch (error) {
                // Ignore opener access errors and fall back to visible message.
            }
        })();
    </script>
</body>
</html>
HTML;

        return response($html, $status)->header('Content-Type', 'text/html; charset=UTF-8');
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

        // Page
        if ($request->filled('page')) {
            $filters['page'] = max((int) $request->input('page'), 1);
        }

        // Sorting
        if ($request->filled('sort')) {
            $sort = (string) $request->input('sort');
            $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';
            $field = ltrim($sort, '-');

            // Map public API sort aliases to database columns
            $sortFieldMap = [
                'name' => 'name',
                'created_at' => 'created_at',
                'updated_at' => 'updated_at',
                'is_gbp_connected' => 'gbp_connected',
                'gbp_connected' => 'gbp_connected',
            ];

            if (array_key_exists($field, $sortFieldMap)) {
                $filters['sort_field'] = $sortFieldMap[$field];
                $filters['sort_direction'] = $direction;
                $filters['sort'] = $sort;
            }
        }

        return $filters;
    }
}
