<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\Contracts\UserStatsRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Admin User Stats Controller.
 *
 * Handles user statistics and listings for the admin dashboard.
 * All endpoints are protected by AdminAuthorizationMiddleware.
 *
 * @see TASK-27-5
 * @see SPEC-027
 */
class UserStatsController extends Controller
{
    protected UserStatsRepositoryInterface $userStatsRepository;

    public function __construct(UserStatsRepositoryInterface $userStatsRepository)
    {
        $this->userStatsRepository = $userStatsRepository;
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
            $stats = $this->userStatsRepository->getFullStats();

            return response()->json([
                'success' => true,
                'data' => $stats,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Server Error',
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
            'plan' => ['nullable', 'string', 'in:free,pro,enterprise'],
        ]);

        $perPage = $validated['per_page'] ?? 20;
        
        // Build filters
        $filters = [];
        if (isset($validated['plan'])) {
            $filters['plan'] = $validated['plan'];
        }
        // Handle is_active filter from query string (convert string "true"/"false" to boolean)
        if ($request->has('is_active')) {
            $isActiveValue = $request->query('is_active');
            $filters['is_active'] = filter_var($isActiveValue, FILTER_VALIDATE_BOOLEAN);
        }

        $users = $this->userStatsRepository->getRecentUsers($perPage, $filters);

        // Transform to exclude PII and mask names
        $data = collect($users->items())->map(function ($user) {
            return [
                'id' => $user->id,
                'masked_name' => $this->maskName($user->name),
                'created_at' => $user->created_at->toIso8601String(),
                'plan' => $user->plan ?? 'free',
                'is_active' => $user->last_login_at !== null && 
                    $user->last_login_at->gte(now()->subDays(30)),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data,
            'meta' => [
                'current_page' => $users->currentPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
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
            'days' => ['nullable', 'integer', 'min:1', 'max:365'],
            'plan' => ['nullable', 'string', 'in:free,pro,enterprise'],
        ]);

        $perPage = $validated['per_page'] ?? 20;
        $daysWithin = $validated['days'] ?? 30;
        
        // Build filters
        $filters = [];
        if (isset($validated['plan'])) {
            $filters['plan'] = $validated['plan'];
        }

        $users = $this->userStatsRepository->getActiveUsers($perPage, $daysWithin, $filters);

        // Transform to exclude PII and mask names
        $data = collect($users->items())->map(function ($user) {
            return [
                'id' => $user->id,
                'masked_name' => $this->maskName($user->name),
                'last_login_at' => $user->last_login_at?->toIso8601String(),
                'plan' => $user->plan ?? 'free',
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data,
            'meta' => [
                'current_page' => $users->currentPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
                'last_page' => $users->lastPage(),
            ],
        ]);
    }

    /**
     * Mask a user's name for privacy.
     *
     * Preserves the first character of each word, replaces the rest with asterisks.
     * Example: "John Doe" -> "J*** D**"
     *
     * @param string|null $name
     * @return string
     */
    protected function maskName(?string $name): string
    {
        if (empty($name)) {
            return 'U***';
        }

        $words = preg_split('/\s+/', $name);
        $maskedWords = array_map(function ($word) {
            if (strlen($word) <= 1) {
                return $word;
            }
            return $word[0] . str_repeat('*', strlen($word) - 1);
        }, $words);

        return implode(' ', $maskedWords);
    }
}
