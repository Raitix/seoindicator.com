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
 * Backend accepts: project_id, is_read, alert_type (singular)
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
  // Backend only accepts single alert_type filter, use the first one if array provided
  if (filters?.alert_types?.length) {
    params.append('alert_type', filters.alert_types[0]);
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
 * Endpoint: /api/v1/ai-visibility/alert-preferences
 */
export function useAlertPreferences() {
  const { data, error, isLoading, mutate: revalidate } = useSWR<AlertPreferencesResponse>(
    `${API_BASE}/ai-visibility/alert-preferences`,
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
 * Endpoint: POST /api/v1/ai-visibility/alerts/{id}/read
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
 * Endpoint: POST /api/v1/ai-visibility/alerts/{id}/resolve
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
 * Endpoint: POST /api/v1/ai-visibility/alerts/mark-all-read
 */
export function useMarkAllRead() {
  const markAllRead = async (projectId?: string): Promise<void> => {
    const params = projectId ? `?project_id=${projectId}` : '';
    const response = await fetch(`${API_BASE}/ai-visibility/alerts/mark-all-read${params}`, {
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
 * Hook to dismiss/delete an alert
 * Uses DELETE endpoint as backend has no dismiss endpoint
 * Endpoint: DELETE /api/v1/ai-visibility/alerts/{id}
 */
export function useDismissAlert() {
  const dismiss = async (alertId: string): Promise<void> => {
    const response = await fetch(`${API_BASE}/ai-visibility/alerts/${alertId}`, {
      method: 'DELETE',
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
 * Endpoint: DELETE /api/v1/ai-visibility/alerts/{id}
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
 * Endpoint: PUT /api/v1/ai-visibility/alert-preferences
 * Backend expects: email_notifications_enabled (not email_notifications)
 */
export function useUpdatePreferences() {
  const update = async (preferences: Partial<AlertPreferences>): Promise<AlertPreferences> => {
    // Map frontend field to backend field
    const payload: Record<string, unknown> = {};
    
    if ('email_notifications' in preferences) {
      payload.email_notifications_enabled = preferences.email_notifications;
    }
    if ('webhook_url' in preferences) {
      payload.webhook_url = preferences.webhook_url;
    }
    if ('alert_types' in preferences) {
      payload.alert_types = preferences.alert_types;
    }

    const response = await fetch(`${API_BASE}/ai-visibility/alert-preferences`, {
      method: 'PUT',
      credentials: 'include',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
      body: JSON.stringify(payload),
    });

    if (!response.ok) {
      const error = await response.json().catch(() => ({ message: 'Update failed' }));
      throw new Error(error.message || 'Failed to update preferences');
    }

    const result = await response.json();

    // Revalidate preferences
    mutate(`${API_BASE}/ai-visibility/alert-preferences`);

    // Map backend response to frontend format
    return {
      email_notifications: result.data?.email_notifications_enabled ?? true,
      webhook_url: result.data?.webhook_url ?? null,
      alert_types: result.data?.alert_types ?? [],
    };
  };

  return { mutate: update };
}

/**
 * Hook to test webhook URL
 * Note: Backend does not have a test-webhook endpoint.
 * This validates the URL format client-side and returns a simulated response.
 */
export function useTestWebhook() {
  const test = async (url: string): Promise<{ success: boolean; message?: string }> => {
    // Client-side validation only - backend endpoint doesn't exist
    try {
      new URL(url);
    } catch {
      return { success: false, message: 'Invalid URL format' };
    }

    // Simulate webhook test by making a simple HEAD request
    try {
      const response = await fetch(url, { method: 'HEAD', mode: 'no-cors' });
      // no-cors mode doesn't give us response details, so we assume success
      return { success: true, message: 'Webhook URL format is valid' };
    } catch (error) {
      return { 
        success: false, 
        message: error instanceof Error ? error.message : 'Failed to connect to webhook URL' 
      };
    }
  };

  return { test };
}
