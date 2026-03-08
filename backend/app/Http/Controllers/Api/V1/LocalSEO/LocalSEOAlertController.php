<?php

namespace App\Http\Controllers\Api\V1\LocalSEO;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\LocalSEO\LocalSEOAlertRequest;
use App\Http\Resources\Api\V1\LocalSEO\LocalSEOAlertResource;
use App\Models\BusinessLocation;
use App\Models\LocalSEOAlert;
use App\Services\LocalSEO\Contracts\LocalSEOAlertServiceInterface;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @Task TASK-35-16
 * @Description Local SEO Alerts API Controller
 *
 * Provides endpoints for managing local SEO alerts:
 * - List alerts with filtering and pagination
 * - Get unread alert counts
 * - Mark alerts as read (single or bulk)
 * - Delete alerts
 * - List alerts for a specific location
 */
class LocalSEOAlertController extends Controller
{
    use AuthorizesRequests;

    protected LocalSEOAlertServiceInterface $alertService;

    public function __construct(LocalSEOAlertServiceInterface $alertService)
    {
        $this->alertService = $alertService;
    }

    /**
     * List all alerts for the authenticated user.
     *
     * GET /api/v1/local-seo/alerts
     *
     * @RateLimit 60/min
     */
    public function index(LocalSEOAlertRequest $request): AnonymousResourceCollection
    {
        $user = $request->user();
        $filters = $this->getFilters($request);

        $query = LocalSEOAlert::where('user_id', $user->id)
            ->with('location')
            ->orderBy('created_at', 'desc');

        // Apply filters
        if (isset($filters['location_id'])) {
            $query->where('location_id', $filters['location_id']);
        }

        if (isset($filters['severity'])) {
            $query->where('severity', $filters['severity']);
        }

        if (isset($filters['alert_type'])) {
            $query->where('alert_type', $filters['alert_type']);
        }

        if (isset($filters['is_read'])) {
            $query->where('is_read', $filters['is_read']);
        }

        // Pagination with capped per_page
        $perPage = min($filters['per_page'] ?? 15, 100);

        $alerts = $query->paginate($perPage);

        return LocalSEOAlertResource::collection($alerts);
    }

    /**
     * Get unread alert counts for the authenticated user.
     *
     * GET /api/v1/local-seo/alerts/unread-count
     *
     * @RateLimit 60/min
     */
    public function unreadCount(LocalSEOAlertRequest $request): JsonResponse
    {
        $user = $request->user();
        $locationId = $request->input('location_id');

        $unreadAlerts = $this->alertService->getUnreadAlerts($user->id, $locationId);

        // Count by severity
        $bySeverity = [
            'info' => 0,
            'warning' => 0,
            'critical' => 0,
        ];

        foreach ($unreadAlerts as $alert) {
            if (isset($bySeverity[$alert->severity])) {
                $bySeverity[$alert->severity]++;
            }
        }

        return response()->json([
            'total' => $unreadAlerts->count(),
            'by_severity' => $bySeverity,
        ]);
    }

    /**
     * Mark a specific alert as read.
     *
     * POST /api/v1/local-seo/alerts/{id}/read
     *
     * @RateLimit 60/min
     */
    public function markAsRead(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        $alert = LocalSEOAlert::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (!$alert) {
            return response()->json([
                'message' => 'Alert not found',
            ], 404);
        }

        // Already read - idempotent
        if ($alert->is_read) {
            return response()->json([
                'success' => true,
                'alert' => new LocalSEOAlertResource($alert),
            ]);
        }

        $this->alertService->markAsRead($id);

        $alert->refresh();

        return response()->json([
            'success' => true,
            'alert' => new LocalSEOAlertResource($alert),
        ]);
    }

    /**
     * Mark all alerts as read for the authenticated user.
     *
     * POST /api/v1/local-seo/alerts/mark-all-read
     *
     * @RateLimit 10/min
     */
    public function markAllAsRead(LocalSEOAlertRequest $request): JsonResponse
    {
        $user = $request->user();
        $locationId = $request->input('location_id');
        $severity = $request->input('severity');

        $query = LocalSEOAlert::where('user_id', $user->id)
            ->where('is_read', false);

        // Apply optional filters
        if ($locationId) {
            $query->where('location_id', $locationId);
        }

        if ($severity) {
            $query->where('severity', $severity);
        }

        $count = $query->update([
            'is_read' => true,
            'read_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'count' => $count,
        ]);
    }

    /**
     * Delete a specific alert.
     *
     * DELETE /api/v1/local-seo/alerts/{id}
     *
     * @RateLimit 60/min
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        $alert = LocalSEOAlert::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (!$alert) {
            return response()->json([
                'message' => 'Alert not found',
            ], 404);
        }

        $alert->delete();

        return response()->json(null, 204);
    }

    /**
     * List all alerts for a specific location.
     *
     * GET /api/v1/locations/{id}/alerts
     *
     * @RateLimit 60/min
     */
    public function locationAlerts(LocalSEOAlertRequest $request, int $id): AnonymousResourceCollection
    {
        $user = $request->user();

        // Verify location ownership
        $location = BusinessLocation::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (!$location) {
            abort(404, 'Location not found');
        }

        $filters = $this->getFilters($request);

        $query = LocalSEOAlert::where('location_id', $id)
            ->orderBy('created_at', 'desc');

        // Apply filters
        if (isset($filters['severity'])) {
            $query->where('severity', $filters['severity']);
        }

        if (isset($filters['alert_type'])) {
            $query->where('alert_type', $filters['alert_type']);
        }

        if (isset($filters['is_read'])) {
            $query->where('is_read', $filters['is_read']);
        }

        // Pagination with capped per_page
        $perPage = min($filters['per_page'] ?? 15, 100);

        $alerts = $query->paginate($perPage);

        return LocalSEOAlertResource::collection($alerts);
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

        // Location filter
        if ($request->filled('location_id')) {
            $filters['location_id'] = (int) $request->input('location_id');
        }

        // Severity filter (validated by request)
        if ($request->filled('severity')) {
            $filters['severity'] = $request->input('severity');
        }

        // Alert type filter (validated by request)
        if ($request->filled('alert_type')) {
            $filters['alert_type'] = $request->input('alert_type');
        }

        // Is read filter
        if ($request->has('is_read')) {
            $filters['is_read'] = filter_var(
                $request->input('is_read'),
                FILTER_VALIDATE_BOOLEAN
            );
        }

        return $filters;
    }
}
