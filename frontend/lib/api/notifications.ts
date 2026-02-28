// Notifications API

import useSWR, { mutate as globalMutate } from 'swr';
import type {
  Notification,
  NotificationsListResponse,
  UnreadCountResponse,
  NotificationsQueryParams,
} from '@/lib/types/notifications';

const API_BASE = '/api/v1';

const fetcher = async (url: string) => {
  const response = await fetch(url, {
    credentials: 'include',
    headers: {
      'Accept': 'application/json',
    },
  });
  if (!response.ok) {
    throw new Error(`API Error: ${response.status}`);
  }
  return response.json();
};

/**
 * Get paginated list of notifications
 */
export function useNotifications(params: NotificationsQueryParams = {}) {
  const searchParams = new URLSearchParams();
  if (params.page) searchParams.set('page', String(params.page));
  if (params.per_page) searchParams.set('per_page', String(params.per_page));
  if (params.unread_only) searchParams.set('unread_only', '1');

  const url = `${API_BASE}/notifications${searchParams.toString() ? `?${searchParams}` : ''}`;

  const { data, error, isLoading, mutate } = useSWR<NotificationsListResponse>(
    url,
    fetcher
  );

  return {
    notifications: data?.data ?? [],
    unreadCount: data?.unread_count ?? 0,
    pagination: data ? {
      total: data.total,
      per_page: data.per_page,
      current_page: data.current_page,
      last_page: data.last_page,
    } : null,
    isLoading,
    isError: error,
    mutate,
  };
}

/**
 * Get unread notification count (lightweight hook for header badge)
 */
export function useUnreadCount() {
  const { data, error, isLoading, mutate } = useSWR<UnreadCountResponse>(
    `${API_BASE}/notifications/unread-count`,
    fetcher,
    {
      revalidateOnFocus: true,
      refreshInterval: 60000, // Refresh every minute
    }
  );

  return {
    count: data?.count ?? 0,
    isLoading,
    isError: error,
    mutate,
  };
}

/**
 * Get a single notification
 */
export async function getNotification(id: number): Promise<Notification> {
  const response = await fetch(`${API_BASE}/notifications/${id}`, {
    credentials: 'include',
    headers: {
      'Accept': 'application/json',
    },
  });

  if (!response.ok) {
    throw new Error('Failed to fetch notification');
  }

  return response.json();
}

/**
 * Mark a notification as read
 */
export async function markAsRead(id: number): Promise<Notification> {
  const response = await fetch(`${API_BASE}/notifications/${id}/read`, {
    method: 'PATCH',
    credentials: 'include',
    headers: {
      'Accept': 'application/json',
    },
  });

  if (!response.ok) {
    throw new Error('Failed to mark notification as read');
  }

  const result = await response.json();
  await globalMutate(`${API_BASE}/notifications/unread-count`);
  return result;
}

/**
 * Mark all notifications as read
 */
export async function markAllAsRead(): Promise<{ success: boolean; count: number }> {
  const response = await fetch(`${API_BASE}/notifications/read-all`, {
    method: 'POST',
    credentials: 'include',
    headers: {
      'Accept': 'application/json',
    },
  });

  if (!response.ok) {
    throw new Error('Failed to mark all notifications as read');
  }

  const result = await response.json();
  await globalMutate(`${API_BASE}/notifications/unread-count`);
  await globalMutate((key: string) => key.startsWith(`${API_BASE}/notifications`));
  return result;
}

/**
 * Delete a notification
 */
export async function deleteNotification(id: number): Promise<{ success: boolean }> {
  const response = await fetch(`${API_BASE}/notifications/${id}`, {
    method: 'DELETE',
    credentials: 'include',
    headers: {
      'Accept': 'application/json',
    },
  });

  if (!response.ok) {
    throw new Error('Failed to delete notification');
  }

  await globalMutate(`${API_BASE}/notifications/unread-count`);
  await globalMutate((key: string) => key.startsWith(`${API_BASE}/notifications`));
  return response.json();
}
