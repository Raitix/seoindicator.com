<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\UserAggregationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Admin User Stats Controller.
 *
 * Handles user statistics and listings for the admin dashboard.
 *
 * @see TASK-27-18
 * @see SPEC-027
 */
class UserStatsController extends Controller
{
    protected UserAggregationService $userService;

    public function __construct(UserAggregationService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * Get user statistics.
     *
     * GET /api/v1/admin/users/stats
     *
     * @return JsonResponse
     */
    public function stats(): JsonResponse
    {
        try {
            $stats = $this->userService->getStats();

            return response()->json([
                'success' => true,
                'data' => $stats,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Server Error',
            ], 500);
        }
    }

    /**
     * Get recent users (paginated).
     *
     * GET /api/v1/admin/users/recent
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function recent(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $perPage = $validated['per_page'] ?? 15;
        $users = $this->userService->getRecentUsers($perPage);

        // Transform to exclude sensitive fields
        $data = collect($users->items())->map(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'plan' => $user->plan,
                'created_at' => $user->created_at->toIso8601String(),
                'last_login_at' => $user->last_login_at?->toIso8601String(),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data,
            'meta' => [
                'total' => $users->total(),
                'per_page' => $users->perPage(),
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
            ],
        ]);
    }

    /**
     * Get active users (paginated).
     *
     * GET /api/v1/admin/users/active
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function active(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $perPage = $validated['per_page'] ?? 15;
        $users = $this->userService->getActiveUsers($perPage);

        // Transform to exclude sensitive fields
        $data = collect($users->items())->map(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'plan' => $user->plan,
                'created_at' => $user->created_at->toIso8601String(),
                'last_login_at' => $user->last_login_at?->toIso8601String(),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data,
            'meta' => [
                'total' => $users->total(),
                'per_page' => $users->perPage(),
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
            ],
        ]);
    }
}
