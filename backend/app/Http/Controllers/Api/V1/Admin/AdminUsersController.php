<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Admin Users Controller.
 *
 * Handles admin user management operations.
 * Used for testing admin authorization middleware.
 *
 * @see TASK-27-2
 * @see SPEC-027
 */
class AdminUsersController extends Controller
{
    /**
     * Get list of users (admin only).
     *
     * GET /api/v1/admin/users
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [],
            'message' => 'User list endpoint - admin access verified',
        ]);
    }

    /**
     * Get a specific user (admin only).
     *
     * GET /api/v1/admin/users/{id}
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => ['id' => $id],
            'message' => 'User details endpoint - admin access verified',
        ]);
    }

    /**
     * Create a new user (admin only).
     *
     * POST /api/v1/admin/users
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'User creation endpoint - admin access verified',
        ], 201);
    }

    /**
     * Update a user (admin only).
     *
     * PUT /api/v1/admin/users/{id}
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'User update endpoint - admin access verified',
        ]);
    }

    /**
     * Delete a user (admin only).
     *
     * DELETE /api/v1/admin/users/{id}
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'User deletion endpoint - admin access verified',
        ]);
    }

    /**
     * Impersonate a user (super admin only).
     *
     * POST /api/v1/admin/users/{id}/impersonate
     *
     * @param int $id
     * @return JsonResponse
     */
    public function impersonate(int $id): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Impersonation endpoint - admin access verified',
        ]);
    }
}
