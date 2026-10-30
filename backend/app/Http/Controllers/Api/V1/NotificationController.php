<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Services\NotificationDispatcher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function __construct(
        private readonly NotificationDispatcher $dispatcher
    ) {}

    /**
     * List notifications for the authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $unreadOnly = $request->boolean('unread_only', false);

        $query = Notification::where('user_id', $user->id)
            ->with('keyword')
            ->orderBy('created_at', 'desc');

        if ($unreadOnly) {
            $query->unread();
        }

        $notifications = $query->paginate(20);

        $unreadCount = Notification::where('user_id', $user->id)
            ->unread()
            ->count();

        return response()->json([
            'data' => $notifications->map(fn ($n) => $n->toApiResponse()),
            'meta' => [
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
                'per_page' => $notifications->perPage(),
                'total' => $notifications->total(),
                'unread_count' => $unreadCount,
            ],
        ]);
    }

    /**
     * Get a single notification.
     */
    public function show(Request $request, Notification $notification): JsonResponse
    {
        if ($request->user()->id !== $notification->user_id) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }

        return response()->json([
            'data' => $notification->toApiResponse(),
        ]);
    }

    /**
     * Mark a notification as read.
     */
    public function markAsRead(Request $request, Notification $notification): JsonResponse
    {
        if ($request->user()->id !== $notification->user_id) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }

        $notification->markAsRead();

        return response()->json([
            'success' => true,
            'read_at' => $notification->read_at?->toIso8601String(),
        ]);
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        $count = $this->dispatcher->markAllAsRead($request->user());

        return response()->json([
            'success' => true,
            'marked_count' => $count,
        ]);
    }

    /**
     * Delete a notification.
     */
    public function destroy(Request $request, Notification $notification): JsonResponse
    {
        if ($request->user()->id !== $notification->user_id) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }

        $notification->delete();

        return response()->json([
            'success' => true,
        ]);
    }

    /**
     * Get unread notification count.
     */
    public function unreadCount(Request $request): JsonResponse
    {
        $count = $this->dispatcher->getUnreadCount($request->user());

        return response()->json([
            'unread_count' => $count,
        ]);
    }
}
