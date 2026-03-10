<?php

namespace App\Http\Controllers\Api\V1\LocalSEO;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\LocalSEO\LocalSEOAlertIndexRequest;
use App\Http\Requests\Api\V1\LocalSEO\LocalSEOAlertMarkAllReadRequest;
use App\Http\Resources\Api\V1\LocalSEO\LocalSEOAlertResource;
use App\Models\BusinessLocation;
use App\Models\LocalSEOAlert;
use App\Models\User;
use App\Services\LocalSEO\Contracts\LocalSEOAlertServiceInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @Task TASK-35-16
 * @Description Local SEO Alerts API Controller
 */
class LocalSEOAlertController extends Controller
{
    public function __construct(
        protected LocalSEOAlertServiceInterface $alertService
    ) {
    }

    /**
     * List alerts for the authenticated user.
     */
    public function index(LocalSEOAlertIndexRequest $request): AnonymousResourceCollection
    {
        $filters = $request->validated();
        $query = LocalSEOAlert::query()
            ->with('location')
            ->orderByDesc('created_at');

        $this->applyUserScope($query, $request->user());
        $this->applyFilters($query, $filters);

        return LocalSEOAlertResource::collection(
            $query->paginate($this->resolvePerPage($filters))
        );
    }

    /**
     * Get unread alert counts for the authenticated user.
     */
    public function unreadCount(LocalSEOAlertIndexRequest $request): JsonResponse
    {
        $filters = $request->validated();
        $query = LocalSEOAlert::query()->where('is_read', false);

        $this->applyUserScope($query, $request->user());

        if (array_key_exists('location_id', $filters)) {
            $query->where('location_id', $filters['location_id']);
        }

        $alerts = $query->get(['severity']);
        $bySeverity = [
            'info' => 0,
            'warning' => 0,
            'critical' => 0,
        ];

        foreach ($alerts as $alert) {
            if (array_key_exists($alert->severity, $bySeverity)) {
                $bySeverity[$alert->severity]++;
            }
        }

        return response()->json([
            'total' => $alerts->count(),
            'by_severity' => $bySeverity,
        ]);
    }

    /**
     * Mark a specific alert as read.
     */
    public function markAsRead(Request $request, int $id): JsonResponse
    {
        $alert = $this->findAccessibleAlert($request->user(), $id);

        if (!$alert) {
            return response()->json([
                'message' => 'Alert not found',
            ], 404);
        }

        $this->alertService->markAsRead($alert->id);
        $alert->refresh()->loadMissing('location');

        return response()->json([
            'success' => true,
            'alert' => (new LocalSEOAlertResource($alert))->resolve($request),
        ]);
    }

    /**
     * Mark all alerts as read for the authenticated user.
     */
    public function markAllAsRead(LocalSEOAlertMarkAllReadRequest $request): JsonResponse
    {
        $filters = $request->validated();

        $count = $this->alertService->markAllAsRead(
            $request->user()->id,
            $filters['location_id'] ?? null,
            $filters['severity'] ?? null,
        );

        return response()->json([
            'success' => true,
            'count' => $count,
        ]);
    }

    /**
     * Delete a specific alert.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $alert = $this->findAccessibleAlert($request->user(), $id);

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
     */
    public function locationAlerts(LocalSEOAlertIndexRequest $request, int $id): AnonymousResourceCollection
    {
        $location = $this->findAccessibleLocation($request->user(), $id);

        if (!$location) {
            abort(404, 'Location not found');
        }

        $filters = $request->validated();
        unset($filters['location_id']);

        $query = LocalSEOAlert::query()
            ->where('location_id', $id)
            ->orderByDesc('created_at');

        $this->applyUserScope($query, $request->user());
        $this->applyFilters($query, $filters);

        return LocalSEOAlertResource::collection(
            $query->paginate($this->resolvePerPage($filters))
        );
    }

    protected function applyUserScope(Builder $query, User $user): void
    {
        if (!$this->isAdmin($user)) {
            $query->where('user_id', $user->id);
        }
    }

    protected function applyFilters(Builder $query, array $filters): void
    {
        if (array_key_exists('location_id', $filters)) {
            $query->where('location_id', $filters['location_id']);
        }

        if (array_key_exists('severity', $filters)) {
            $query->where('severity', $filters['severity']);
        }

        if (array_key_exists('type', $filters)) {
            $query->where('alert_type', $filters['type']);
        }

        if (array_key_exists('is_read', $filters)) {
            $query->where('is_read', $this->normalizeBoolean($filters['is_read']));
        }
    }

    protected function resolvePerPage(array $filters): int
    {
        return min((int) ($filters['per_page'] ?? 15), 100);
    }

    protected function normalizeBoolean(mixed $value): bool
    {
        return in_array($value, [true, 1, '1', 'true'], true);
    }

    protected function findAccessibleAlert(User $user, int $id): ?LocalSEOAlert
    {
        $query = LocalSEOAlert::query()
            ->with('location')
            ->whereKey($id);

        $this->applyUserScope($query, $user);

        return $query->first();
    }

    protected function findAccessibleLocation(User $user, int $id): ?BusinessLocation
    {
        $query = BusinessLocation::query()->whereKey($id);

        if (!$this->isAdmin($user)) {
            $query->where('user_id', $user->id);
        }

        return $query->first();
    }

    protected function isAdmin(User $user): bool
    {
        return method_exists($user, 'isAdmin')
            ? $user->isAdmin()
            : in_array($user->role ?? null, ['admin', 'super_admin'], true);
    }
}
