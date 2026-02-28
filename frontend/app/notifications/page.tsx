"use client";

import { useState } from "react";
import { useRouter } from "next/navigation";
import { useNotifications, markAsRead, markAllAsRead, deleteNotification } from "@/lib/api/notifications";
import { NOTIFICATION_TYPE_LABELS } from "@/lib/types/notifications";
import type { Notification } from "@/lib/types/notifications";

export default function NotificationsPage() {
  const router = useRouter();
  const [page, setPage] = useState(1);
  const [unreadOnly, setUnreadOnly] = useState(false);
  const [actionLoading, setActionLoading] = useState<number | null>(null);

  const {
    notifications,
    unreadCount,
    pagination,
    isLoading,
    isError,
    mutate,
  } = useNotifications({
    page,
    per_page: 20,
    unread_only: unreadOnly,
  });

  const handleMarkAsRead = async (id: number) => {
    setActionLoading(id);
    try {
      await markAsRead(id);
      mutate();
    } catch (e) {
      console.error("Failed to mark as read:", e);
    } finally {
      setActionLoading(null);
    }
  };

  const handleMarkAllAsRead = async () => {
    setActionLoading(-1); // -1 means "all"
    try {
      await markAllAsRead();
      mutate();
    } catch (e) {
      console.error("Failed to mark all as read:", e);
    } finally {
      setActionLoading(null);
    }
  };

  const handleDelete = async (id: number) => {
    setActionLoading(id);
    try {
      await deleteNotification(id);
      mutate();
    } catch (e) {
      console.error("Failed to delete notification:", e);
    } finally {
      setActionLoading(null);
    }
  };

  const handleNotificationClick = async (notification: Notification) => {
    // Mark as read
    if (!notification.read_at) {
      await markAsRead(notification.id);
      mutate();
    }

    // Navigate to keyword detail if available
    if (notification.keyword_id) {
      router.push(`/keywords/${notification.keyword_id}`);
    }
  };

  const formatTime = (dateString: string) => {
    const date = new Date(dateString);
    const now = new Date();
    const diffMs = now.getTime() - date.getTime();
    const diffMins = Math.floor(diffMs / 60000);
    const diffHours = Math.floor(diffMs / 3600000);
    const diffDays = Math.floor(diffMs / 86400000);

    if (diffMins < 1) return "Just now";
    if (diffMins < 60) return `${diffMins}m ago`;
    if (diffHours < 24) return `${diffHours}h ago`;
    if (diffDays < 7) return `${diffDays}d ago`;
    return date.toLocaleDateString();
  };

  const getPositionChangeText = (notification: Notification) => {
    const { old_position, new_position, change, feature_type } = notification.data;

    if (feature_type) {
      return feature_type.replace(/_/g, " ");
    }

    if (old_position !== undefined && new_position !== undefined) {
      const direction = (notification.type.includes("drop") || notification.type.includes("left"))
        ? "↓"
        : "↑";
      return `${old_position} → ${new_position} ${direction}`;
    }

    if (change !== undefined) {
      return `${change} positions`;
    }

    return "";
  };

  if (isError) {
    return (
      <div className="flex min-h-screen items-center justify-center bg-gradient-to-b from-slate-950 to-slate-900">
        <div className="text-red-400">Failed to load notifications</div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gradient-to-b from-slate-950 to-slate-900 px-4 py-10 text-slate-300">
      <div className="mx-auto w-full max-w-3xl">
        {/* Header */}
        <div className="flex items-center justify-between mb-6">
          <div>
            <h1 className="text-2xl font-semibold text-white">Notifications</h1>
            {unreadCount > 0 && (
              <p className="text-sm text-slate-400 mt-1">
                {unreadCount} unread
              </p>
            )}
          </div>
          <div className="flex items-center gap-3">
            <button
              onClick={() => setUnreadOnly(!unreadOnly)}
              className={`px-4 py-2 rounded-lg text-sm font-medium transition-colors ${
                unreadOnly
                  ? "bg-blue-600 text-white"
                  : "bg-slate-800 text-slate-300 hover:bg-slate-700"
              }`}
            >
              Unread Only
            </button>
            {unreadCount > 0 && (
              <button
                onClick={handleMarkAllAsRead}
                disabled={actionLoading === -1}
                className="px-4 py-2 rounded-lg text-sm font-medium bg-slate-800 text-slate-300 hover:bg-slate-700 transition-colors disabled:opacity-50"
              >
                Mark All Read
              </button>
            )}
          </div>
        </div>

        {/* Notifications List */}
        <div className="rounded-xl border border-slate-800 bg-slate-900/60 overflow-hidden">
          {isLoading ? (
            <div className="p-8 text-center text-slate-400">Loading...</div>
          ) : notifications.length === 0 ? (
            <div className="p-8 text-center">
              <div className="text-4xl mb-3">🔔</div>
              <p className="text-slate-400">
                {unreadOnly ? "No unread notifications" : "No notifications yet"}
              </p>
            </div>
          ) : (
            <ul className="divide-y divide-slate-800">
              {notifications.map((notification) => (
                <li
                  key={notification.id}
                  className={`relative p-4 hover:bg-slate-800/50 transition-colors cursor-pointer ${
                    !notification.read_at ? "bg-slate-800/30" : ""
                  }`}
                  onClick={() => handleNotificationClick(notification)}
                >
                  <div className="flex items-start gap-3">
                    {/* Unread Indicator */}
                    {!notification.read_at && (
                      <div className="absolute left-2 top-1/2 -translate-y-1/2 w-2 h-2 bg-blue-500 rounded-full" />
                    )}

                    <div className="flex-1 ml-4">
                      <div className="flex items-center gap-2 mb-1">
                        <span className="font-medium text-white">
                          {NOTIFICATION_TYPE_LABELS[notification.type]}
                        </span>
                        <span className="text-xs text-slate-500">
                          {formatTime(notification.created_at)}
                        </span>
                      </div>

                      <div className="text-sm text-slate-400 mb-1">
                        {notification.keyword?.keyword && (
                          <span className="font-medium text-slate-300">
                            "{notification.keyword.keyword}"
                          </span>
                        )}
                      </div>

                      <div className="text-sm text-slate-400">
                        {getPositionChangeText(notification)}
                      </div>
                    </div>

                    {/* Actions */}
                    <div className="flex items-center gap-2">
                      {!notification.read_at && (
                        <button
                          onClick={(e) => {
                            e.stopPropagation();
                            handleMarkAsRead(notification.id);
                          }}
                          disabled={actionLoading === notification.id}
                          className="p-1.5 rounded hover:bg-slate-700 text-slate-400 hover:text-white transition-colors disabled:opacity-50"
                          title="Mark as read"
                        >
                          ✓
                        </button>
                      )}
                      <button
                        onClick={(e) => {
                          e.stopPropagation();
                          handleDelete(notification.id);
                        }}
                        disabled={actionLoading === notification.id}
                        className="p-1.5 rounded hover:bg-slate-700 text-slate-400 hover:text-red-400 transition-colors disabled:opacity-50"
                        title="Delete"
                      >
                        ×
                      </button>
                    </div>
                  </div>
                </li>
              ))}
            </ul>
          )}
        </div>

        {/* Pagination */}
        {pagination && pagination.last_page > 1 && (
          <div className="flex items-center justify-center gap-2 mt-6">
            <button
              onClick={() => setPage(page - 1)}
              disabled={page === 1}
              className="px-4 py-2 rounded-lg bg-slate-800 text-slate-300 hover:bg-slate-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
            >
              Previous
            </button>
            <span className="text-slate-400">
              Page {pagination.current_page} of {pagination.last_page}
            </span>
            <button
              onClick={() => setPage(page + 1)}
              disabled={page === pagination.last_page}
              className="px-4 py-2 rounded-lg bg-slate-800 text-slate-300 hover:bg-slate-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
            >
              Next
            </button>
          </div>
        )}
      </div>
    </div>
  );
}
