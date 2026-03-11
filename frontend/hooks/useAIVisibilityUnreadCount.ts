/**
 * Hook to fetch AI Visibility alerts unread count
 * Lightweight hook for header badge
 * 
 * @see TASK-34-21
 */

import useSWR from 'swr';

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

interface AlertsMetaResponse {
  success: boolean;
  meta: {
    unread_count: number;
  };
}

/**
 * Get AI Visibility alerts unread count (lightweight hook for header badge)
 */
export function useAIVisibilityUnreadCount() {
  const { data, error, isLoading, mutate } = useSWR<AlertsMetaResponse>(
    `${API_BASE}/ai-visibility/alerts?per_page=1`,
    fetcher,
    {
      revalidateOnFocus: true,
      refreshInterval: 60000, // Refresh every minute
      dedupingInterval: 10000, // Dedupe requests within 10 seconds
    }
  );

  return {
    count: data?.meta?.unread_count ?? 0,
    isLoading,
    isError: error,
    mutate,
  };
}
