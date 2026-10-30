<?php

namespace App\Http\Controllers\Api\V1\AIVisibility;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\AIVisibility\AIVisibilityAlertResource;
use App\Models\AIVisibility\AIVisibilityAlert;
use App\Models\AIVisibility\AIVisibilityProject;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * AI Visibility Alert Controller.
 *
 * Handles REST API endpoints for AI Visibility alert management.
 *
 * @see TASK-34-20
 * @see SPEC-034
 */
class AIVisibilityAlertController extends Controller
{
    /**
     * List all AI visibility alerts for the authenticated user.
     *
     * GET /api/v1/ai-visibility/alerts
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = AIVisibilityAlert::query()
            ->whereHas('project', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->with('project');

        // Filter by project_id
        if ($request->has('project_id')) {
            $projectId = $request->input('project_id');
            // Verify user owns the project
            $project = AIVisibilityProject::where('id', $projectId)
                ->where('user_id', $user->id)
                ->first();
            if ($project) {
                $query->where('project_id', $projectId);
            }
        }

        // Filter by is_read
        if ($request->has('is_read')) {
            $query->where('is_read', filter_var($request->input('is_read'), FILTER_VALIDATE_BOOLEAN));
        }

        // Filter by alert_type
        if ($request->has('alert_type')) {
            $query->where('alert_type', $request->input('alert_type'));
        }

        // Sort by triggered_at descending
        $query->orderBy('triggered_at', 'desc');

        // Paginate
        $perPage = min($request->input('per_page', 15), 100);
        $alerts = $query->paginate($perPage);

        // Get unread count
        $unreadCount = AIVisibilityAlert::query()
            ->whereHas('project', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->where('is_read', false)
            ->count();

        return response()->json([
            'success' => true,
            'data' => AIVisibilityAlertResource::collection($alerts->items()),
            'meta' => [
                'current_page' => $alerts->currentPage(),
                'last_page' => $alerts->lastPage(),
                'per_page' => $alerts->perPage(),
                'total' => $alerts->total(),
                'unread_count' => $unreadCount,
            ],
        ]);
    }

    /**
     * Get a single AI visibility alert.
     *
     * GET /api/v1/ai-visibility/alerts/{id}
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        $alert = AIVisibilityAlert::with('project')->find($id);

        if (!$alert) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Alert not found.',
                ],
            ], 404);
        }

        // Authorization: user must own the alert's project
        if ($alert->project->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'FORBIDDEN',
                    'message' => 'You do not have access to this alert.',
                ],
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => new AIVisibilityAlertResource($alert),
        ]);
    }

    /**
     * Mark an alert as read.
     *
     * POST /api/v1/ai-visibility/alerts/{id}/read
     */
    public function markAsRead(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        $alert = AIVisibilityAlert::with('project')->find($id);

        if (!$alert) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Alert not found.',
                ],
            ], 404);
        }

        // Authorization: user must own the alert's project
        if ($alert->project->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'FORBIDDEN',
                    'message' => 'You do not have access to this alert.',
                ],
            ], 403);
        }

        $alert->update(['is_read' => true]);

        return response()->json([
            'success' => true,
            'data' => new AIVisibilityAlertResource($alert),
        ]);
    }

    /**
     * Mark an alert as resolved.
     *
     * POST /api/v1/ai-visibility/alerts/{id}/resolve
     */
    public function markAsResolved(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        $alert = AIVisibilityAlert::with('project')->find($id);

        if (!$alert) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Alert not found.',
                ],
            ], 404);
        }

        // Authorization: user must own the alert's project
        if ($alert->project->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'FORBIDDEN',
                    'message' => 'You do not have access to this alert.',
                ],
            ], 403);
        }

        $alert->update([
            'is_resolved' => true,
            'resolved_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'data' => new AIVisibilityAlertResource($alert),
        ]);
    }

    /**
     * Mark all unread alerts as read.
     *
     * POST /api/v1/ai-visibility/alerts/mark-all-read
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = AIVisibilityAlert::query()
            ->whereHas('project', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->where('is_read', false);

        // Optional: scope by project_id
        if ($request->has('project_id')) {
            $projectId = $request->input('project_id');
            $project = AIVisibilityProject::where('id', $projectId)
                ->where('user_id', $user->id)
                ->first();
            if ($project) {
                $query->where('project_id', $projectId);
            }
        }

        $count = $query->update(['is_read' => true]);

        return response()->json([
            'success' => true,
            'data' => [
                'updated_count' => $count,
            ],
        ]);
    }

    /**
     * Delete an alert.
     *
     * DELETE /api/v1/ai-visibility/alerts/{id}
     */
    public function destroy(Request $request, int $id): Response
    {
        $user = $request->user();

        $alert = AIVisibilityAlert::with('project')->find($id);

        if (!$alert) {
            abort(404, 'Alert not found.');
        }

        // Authorization: user must own the alert's project
        if ($alert->project->user_id !== $user->id) {
            abort(403, 'You do not have access to this alert.');
        }

        $alert->delete();

        return response()->noContent();
    }

    /**
     * Get user's AI Visibility alert preferences.
     *
     * GET /api/v1/ai-visibility/alert-preferences
     */
    public function getPreferences(Request $request): JsonResponse
    {
        $user = $request->user();

        // Get preferences from user's settings or defaults
        $preferences = $user->ai_visibility_preferences ?? [
            'email_notifications_enabled' => true,
            'webhook_url' => null,
            'alert_types' => ['mention_spike', 'mention_drop', 'competitor_gain'],
        ];

        return response()->json([
            'success' => true,
            'data' => $preferences,
        ]);
    }

    /**
     * Update user's AI Visibility alert preferences.
     *
     * PUT /api/v1/ai-visibility/alert-preferences
     */
    public function updatePreferences(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'email_notifications_enabled' => ['sometimes', 'boolean'],
            'webhook_url' => ['sometimes', 'nullable', 'url', 'max:255'],
            'alert_types' => ['sometimes', 'array'],
            'alert_types.*' => ['string', 'in:mention_spike,mention_drop,competitor_gain'],
        ]);

        $currentPreferences = $user->ai_visibility_preferences ?? [
            'email_notifications_enabled' => true,
            'webhook_url' => null,
            'alert_types' => ['mention_spike', 'mention_drop', 'competitor_gain'],
        ];

        $newPreferences = array_merge($currentPreferences, $validated);

        // Store preferences on user model
        $user->ai_visibility_preferences = $newPreferences;
        $user->save();

        return response()->json([
            'success' => true,
            'data' => $newPreferences,
        ]);
    }
}
