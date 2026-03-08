'use client';

import useSWR, { mutate } from 'swr';
import type {
  Alert,
  AlertPreferences,
  AlertsListResponse,
  AlertDetailResponse,
  AlertPreferencesResponse,
} from '@/lib/ai-visibility/types';

const API_BASE = '/api/v1';

const fetcher = async (url: string) => {
  const response = await fetch(url, {
    credentials: 'include',
    headers: {
      'Accept': 'application/json',
    },
  });
  if (!response.ok) {
    const error = await response.json().catch(() => ({ message: 'Request failed' }));
    throw new Error(error.message || `API Error: ${response.status}`);
  }
  return response.json();
};

/**
 * Hook to fetch alerts list
 */
export function useAlerts(page = 1, perPage = 15, filters?: {
  project_id?: string | null;
  alert_types?: string[];
  is_read?: boolean | null;
}) {
  const params = new URLSearchParams({
    page: String(page),
    per_page: String(perPage),
  });

  if (filters?.project_id) {
    params.append('project_id', filters.project_id);
  }
  if (filters?.alert_types?.length) {
    params.append('alert_types', filters.alert_types.join(','));
  }
  if (filters?.is_read !== null && filters?.is_read !== undefined) {
    params.append('is_read', String(filters.is_read));
  }

  const { data, error, isLoading, mutate: revalidate } = useSWR<AlertsListResponse>(
    `${API_BASE}/ai-visibility/alerts?${params.toString()}`,
    fetcher,
    {
      revalidateOnFocus: false,
      dedupingInterval: 5000,
    }
  );

  return {
    data: data?.data || [],
    meta: data?.meta,
    isLoading,
    isError: !!error,
    error: error?.message,
    refetch: revalidate,
  };
}

/**
 * Hook to fetch single alert details
 */
export function useAlert(alertId: string | null) {
  const { data, error, isLoading, mutate: revalidate } = useSWR<AlertDetailResponse>(
    alertId ? `${API_BASE}/ai-visibility/alerts/${alertId}` : null,
    fetcher
  );

  return {
    alert: data?.data || null,
    isLoading,
    isError: !!error,
    error: error?.message,
    refetch: revalidate,
  };
}

/**
 * Hook to fetch alert preferences
 */
export function useAlertPreferences() {
  const { data, error, isLoading, mutate: revalidate } = useSWR<AlertPreferencesResponse>(
    `${API_BASE}/ai-visibility/alerts/preferences`,
    fetcher
  );

  return {
    preferences: data?.data || null,
    isLoading,
    isError: !!error,
    error: error?.message,
    refetch: revalidate,
  };
}

/**
 * Hook to mark an alert as read
 */
export function useMarkAlertRead() {
  const markRead = async (alertId: string): Promise<void> => {
    const response = await fetch(`${API_BASE}/ai-visibility/alerts/${alertId}/read`, {
      method: 'POST',
      credentials: 'include',
      headers: {
        'Accept': 'application/json',
      },
    });

    if (!response.ok) {
      throw new Error('Failed to mark alert as read');
    }

    // Revalidate alerts list
    mutate(
      (key: string) => key.startsWith(`${API_BASE}/ai-visibility/alerts`),
      undefined,
      { revalidate: true }
    );
  };

  return { mutate: markRead };
}

/**
 * Hook to resolve an alert
 */
export function useResolveAlert() {
  const resolve = async (alertId: string): Promise<void> => {
    const response = await fetch(`${API_BASE}/ai-visibility/alerts/${alertId}/resolve`, {
      method: 'POST',
      credentials: 'include',
      headers: {
        'Accept': 'application/json',
      },
    });

    if (!response.ok) {
      throw new Error('Failed to resolve alert');
    }

    // Revalidate alerts list
    mutate(
      (key: string) => key.startsWith(`${API_BASE}/ai-visibility/alerts`),
      undefined,
      { revalidate: true }
    );
  };

  return { mutate: resolve };
}

/**
 * Hook to mark all alerts as read
 */
export function useMarkAllRead() {
  const markAllRead = async (): Promise<void> => {
    const response = await fetch(`${API_BASE}/ai-visibility/alerts/read-all`, {
      method: 'POST',
      credentials: 'include',
      headers: {
        'Accept': 'application/json',
      },
    });

    if (!response.ok) {
      throw new Error('Failed to mark all alerts as read');
    }

    // Revalidate alerts list
    mutate(
      (key: string) => key.startsWith(`${API_BASE}/ai-visibility/alerts`),
      undefined,
      { revalidate: true }
    );
  };

  return { mutate: markAllRead };
}

/**
 * Hook to dismiss an alert
 */
export function useDismissAlert() {
  const dismiss = async (alertId: string): Promise<void> => {
    const response = await fetch(`${API_BASE}/ai-visibility/alerts/${alertId}/dismiss`, {
      method: 'POST',
      credentials: 'include',
      headers: {
        'Accept': 'application/json',
      },
    });

    if (!response.ok) {
      throw new Error('Failed to dismiss alert');
    }

    // Revalidate alerts list
    mutate(
      (key: string) => key.startsWith(`${API_BASE}/ai-visibility/alerts`),
      undefined,
      { revalidate: true }
    );
  };

  return { mutate: dismiss };
}

/**
 * Hook to delete an alert
 */
export function useDeleteAlert() {
  const deleteAlert = async (alertId: string): Promise<void> => {
    const response = await fetch(`${API_BASE}/ai-visibility/alerts/${alertId}`, {
      method: 'DELETE',
      credentials: 'include',
      headers: {
        'Accept': 'application/json',
      },
    });

    if (!response.ok) {
      throw new Error('Failed to delete alert');
    }

    // Revalidate alerts list
    mutate(
      (key: string) => key.startsWith(`${API_BASE}/ai-visibility/alerts`),
      undefined,
      { revalidate: true }
    );
  };

  return { mutate: deleteAlert };
}

/**
 * Hook to update alert preferences
 */
export function useUpdatePreferences() {
  const update = async (preferences: AlertPreferences): Promise<AlertPreferences> => {
    const response = await fetch(`${API_BASE}/ai-visibility/alerts/preferences`, {
      method: 'PUT',
      credentials: 'include',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
      body: JSON.stringify(preferences),
    });

    if (!response.ok) {
      const error = await response.json().catch(() => ({ message: 'Update failed' }));
      throw new Error(error.message || 'Failed to update preferences');
    }

    const result = await response.json();

    // Revalidate preferences
    mutate(`${API_BASE}/ai-visibility/alerts/preferences`);

    return result.data;
  };

  return { mutate: update };
}

/**
 * Hook to test webhook URL
 */
export function useTestWebhook() {
  const test = async (url: string): Promise<{ success: boolean }> => {
    const response = await fetch(`${API_BASE}/ai-visibility/alerts/test-webhook`, {
      method: 'POST',
      credentials: 'include',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
      body: JSON.stringify({ url }),
    });

    if (!response.ok) {
      const error = await response.json().catch(() => ({ message: 'Test failed' }));
      throw new Error(error.message || 'Webhook test failed');
    }

    return response.json();
  };

  return { test };
}
