// Alert Settings API

import useSWR, { mutate as globalMutate } from 'swr';
import type {
  AlertSettings,
  AlertSettingsUpdateRequest,
  KeywordAlertSettings,
  KeywordAlertSettingsUpdateRequest,
} from '@/lib/types/alerts';

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
 * Get user's global alert settings
 */
export function useAlertSettings() {
  const { data, error, isLoading, mutate } = useSWR<AlertSettings>(
    `${API_BASE}/user/alert-settings`,
    fetcher
  );

  return {
    settings: data,
    isLoading,
    isError: error,
    mutate,
  };
}

/**
 * Update user's global alert settings
 */
export async function updateAlertSettings(
  data: AlertSettingsUpdateRequest
): Promise<AlertSettings> {
  const response = await fetch(`${API_BASE}/user/alert-settings`, {
    method: 'PUT',
    credentials: 'include',
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    },
    body: JSON.stringify(data),
  });

  if (!response.ok) {
    const error = await response.json().catch(() => ({ message: 'Update failed' }));
    throw new Error(error.message || 'Failed to update alert settings');
  }

  const result = await response.json();
  await globalMutate(`${API_BASE}/user/alert-settings`);
  return result;
}

/**
 * Get keyword-specific alert settings
 */
export function useKeywordAlertSettings(keywordId: number | null) {
  const { data, error, isLoading, mutate } = useSWR<KeywordAlertSettings>(
    keywordId ? `${API_BASE}/keywords/${keywordId}/alert-settings` : null,
    fetcher
  );

  return {
    settings: data,
    isLoading,
    isError: error,
    mutate,
  };
}

/**
 * Update keyword-specific alert settings
 */
export async function updateKeywordAlertSettings(
  keywordId: number,
  data: KeywordAlertSettingsUpdateRequest
): Promise<KeywordAlertSettings> {
  const response = await fetch(`${API_BASE}/keywords/${keywordId}/alert-settings`, {
    method: 'PUT',
    credentials: 'include',
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    },
    body: JSON.stringify(data),
  });

  if (!response.ok) {
    const error = await response.json().catch(() => ({ message: 'Update failed' }));
    throw new Error(error.message || 'Failed to update keyword alert settings');
  }

  const result = await response.json();
  await globalMutate(`${API_BASE}/keywords/${keywordId}/alert-settings`);
  return result;
}

/**
 * Reset keyword alert settings to defaults
 */
export async function deleteKeywordAlertSettings(
  keywordId: number
): Promise<{ success: boolean }> {
  const response = await fetch(`${API_BASE}/keywords/${keywordId}/alert-settings`, {
    method: 'DELETE',
    credentials: 'include',
    headers: {
      'Accept': 'application/json',
    },
  });

  if (!response.ok) {
    throw new Error('Failed to reset keyword alert settings');
  }

  await globalMutate(`${API_BASE}/keywords/${keywordId}/alert-settings`);
  return response.json();
}

/**
 * Resubscribe to alerts after unsubscribing
 */
export async function resubscribeToAlerts(): Promise<{ success: boolean }> {
  const response = await fetch(`${API_BASE}/alerts/resubscribe`, {
    method: 'POST',
    credentials: 'include',
    headers: {
      'Accept': 'application/json',
    },
  });

  if (!response.ok) {
    throw new Error('Failed to resubscribe to alerts');
  }

  return response.json();
}
