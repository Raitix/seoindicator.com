/**
 * Backlinks API client
 * Handles all backlink-related API calls
 */

import type {
  BacklinkSummary,
  ReferringDomain,
  AnchorText,
  BacklinkHistoryData,
  BacklinksOverviewResponse,
  DomainsResponse,
  AnchorsResponse,
  HistoryResponse,
  SyncResponse,
  DateRange,
} from '@/components/backlinks/types';

const API_BASE = '/api/v1';

// Generic fetcher with error handling
async function fetchApi<T>(url: string, options?: RequestInit): Promise<T> {
  const response = await fetch(url, {
    headers: {
      'Content-Type': 'application/json',
      ...options?.headers,
    },
    ...options,
  });

  if (!response.ok) {
    const errorBody = await response.json().catch(() => ({ message: 'Unknown error' }));
    throw new Error(errorBody.message || `HTTP ${response.status}: ${response.statusText}`);
  }

  return response.json();
}

// ==================== History API ====================

/**
 * Fetch backlink history for a project
 * @param projectId - Project ID
 * @param dateRange - Optional date range filter
 */
export async function getBacklinkHistory(
  projectId: number,
  dateRange?: DateRange
): Promise<HistoryResponse> {
  const params = new URLSearchParams();
  
  if (dateRange?.from) {
    params.set('from', dateRange.from);
  }
  if (dateRange?.to) {
    params.set('to', dateRange.to);
  }

  const queryString = params.toString();
  const url = `${API_BASE}/projects/${projectId}/backlinks/history${queryString ? `?${queryString}` : ''}`;
  
  return fetchApi<HistoryResponse>(url);
}

// ==================== Overview API ====================

/**
 * Fetch backlinks overview (summary + recent + top domains)
 * @param projectId - Project ID
 */
export async function getBacklinksOverview(projectId: number): Promise<BacklinksOverviewResponse> {
  return fetchApi<BacklinksOverviewResponse>(`${API_BASE}/projects/${projectId}/backlinks/overview`);
}

// ==================== Domains API ====================

export interface DomainsQueryParams {
  page?: number;
  perPage?: number;
  sortField?: 'domain' | 'backlinks_count' | 'domain_trust_score' | 'first_seen_at';
  sortOrder?: 'asc' | 'desc';
}

/**
 * Fetch referring domains with pagination and sorting
 * @param projectId - Project ID
 * @param params - Query parameters
 */
export async function getReferringDomains(
  projectId: number,
  params?: DomainsQueryParams
): Promise<DomainsResponse> {
  const searchParams = new URLSearchParams();
  
  if (params?.page) searchParams.set('page', String(params.page));
  if (params?.perPage) searchParams.set('per_page', String(params.perPage));
  if (params?.sortField) searchParams.set('sort_field', params.sortField);
  if (params?.sortOrder) searchParams.set('sort_order', params.sortOrder);

  const queryString = searchParams.toString();
  const url = `${API_BASE}/projects/${projectId}/backlinks/domains${queryString ? `?${queryString}` : ''}`;
  
  return fetchApi<DomainsResponse>(url);
}

// ==================== Anchors API ====================

/**
 * Fetch anchor text distribution
 * @param projectId - Project ID
 */
export async function getAnchorTexts(projectId: number): Promise<AnchorsResponse> {
  return fetchApi<AnchorsResponse>(`${API_BASE}/projects/${projectId}/backlinks/anchors`);
}

// ==================== Sync API ====================

/**
 * Trigger backlink data sync
 * @param projectId - Project ID
 */
export async function syncBacklinks(projectId: number): Promise<SyncResponse> {
  return fetchApi<SyncResponse>(`${API_BASE}/projects/${projectId}/backlinks/sync`, {
    method: 'POST',
  });
}

// ==================== SWR Hooks ====================

import useSWR from 'swr';
import { mutate as globalMutate } from 'swr';

/**
 * Hook for fetching backlink history
 */
export function useBacklinkHistory(projectId: number, dateRange?: DateRange) {
  const shouldFetch = projectId > 0;
  const key = shouldFetch 
    ? `${API_BASE}/projects/${projectId}/backlinks/history-${dateRange?.from || ''}-${dateRange?.to || ''}`
    : null;

  const { data, error, isLoading, mutate } = useSWR<HistoryResponse>(
    key,
    () => getBacklinkHistory(projectId, dateRange),
    {
      revalidateOnFocus: false,
      dedupingInterval: 60000, // 1 minute
    }
  );

  return {
    data: data?.data ?? [],
    meta: data?.meta,
    isLoading,
    error,
    mutate,
  };
}

/**
 * Hook for fetching backlinks overview
 */
export function useBacklinksOverview(projectId: number) {
  const shouldFetch = projectId > 0;
  const key = shouldFetch ? `${API_BASE}/projects/${projectId}/backlinks/overview` : null;

  const { data, error, isLoading, mutate } = useSWR<BacklinksOverviewResponse>(
    key,
    () => getBacklinksOverview(projectId),
    {
      revalidateOnFocus: false,
      dedupingInterval: 60000,
    }
  );

  return {
    summary: data?.data?.summary ?? null,
    recentBacklinks: data?.data?.recentBacklinks ?? [],
    topReferringDomains: data?.data?.topReferringDomains ?? [],
    isLoading,
    error,
    mutate,
  };
}

/**
 * Hook for fetching referring domains
 */
export function useReferringDomains(
  projectId: number,
  params?: DomainsQueryParams
) {
  const shouldFetch = projectId > 0;
  const queryString = new URLSearchParams({
    page: String(params?.page ?? 1),
    perPage: String(params?.perPage ?? 10),
    sortField: params?.sortField ?? 'backlinks_count',
    sortOrder: params?.sortOrder ?? 'desc',
  }).toString();
  
  const key = shouldFetch 
    ? `${API_BASE}/projects/${projectId}/backlinks/domains?${queryString}`
    : null;

  const { data, error, isLoading, mutate } = useSWR<DomainsResponse>(
    key,
    () => getReferringDomains(projectId, params),
    {
      revalidateOnFocus: false,
      dedupingInterval: 60000,
    }
  );

  return {
    domains: data?.data ?? [],
    meta: data?.meta,
    isLoading,
    error,
    mutate,
  };
}

/**
 * Hook for fetching anchor text distribution
 */
export function useAnchorTexts(projectId: number) {
  const shouldFetch = projectId > 0;
  const key = shouldFetch ? `${API_BASE}/projects/${projectId}/backlinks/anchors` : null;

  const { data, error, isLoading, mutate } = useSWR<AnchorsResponse>(
    key,
    () => getAnchorTexts(projectId),
    {
      revalidateOnFocus: false,
      dedupingInterval: 60000,
    }
  );

  return {
    anchors: data?.data ?? [],
    isLoading,
    error,
    mutate,
  };
}

/**
 * Revalidate all backlink data for a project
 */
export function revalidateBacklinks(projectId: number) {
  globalMutate((key: string) => 
    key.startsWith(`${API_BASE}/projects/${projectId}/backlinks`)
  );
}
