<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

/**
 * Admin Stats Controller.
 *
 * Provides general admin dashboard statistics.
 *
 * @see TASK-27-2
 * @see SPEC-027
 */
class AdminStatsController extends Controller
{
    /**
     * Get general admin statistics.
     *
     * GET /api/v1/admin/stats
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        try {
            $stats = [
                'users' => [
                    'total' => DB::table('users')->count(),
                    'active' => DB::table('users')->whereNotNull('last_login_at')->count(),
                ],
                'projects' => [
                    'total' => DB::table('projects')->count(),
                ],
                'analyses' => [
                    'total' => DB::table('seo_analyses')->count(),
                ],
            ];

            return response()->json([
                'success' => true,
                'data' => $stats,
            ]);
        } catch (\Exception $e) {
            // Return empty stats if tables don't exist yet
            return response()->json([
                'success' => true,
                'data' => [
                    'users' => ['total' => 0, 'active' => 0],
                    'projects' => ['total' => 0],
                    'analyses' => ['total' => 0],
                ],
            ]);
        }
    }
}
