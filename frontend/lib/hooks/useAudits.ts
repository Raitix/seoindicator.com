/**
 * useAudits Hook
 * 
 * Task: TASK-14-15 - Create Audit List Page (Frontend)
 * Spec: SPEC-14 - Technical SEO Site Audit
 * 
 * React Query hooks for fetching and managing audits with conditional polling
 */

'use client';

import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import type { UseQueryResult } from '@tanstack/react-query';

const API_BASE = process.env.NEXT_PUBLIC_API_URL || '/api/v1';

// ============================================================
// Types
// ============================================================

export type AuditStatus = 'pending' | 'running' | 'completed' | 'failed' | 'cancelled';

export interface Audit {
  id: number;
  site_id: number;
  status: AuditStatus;
  pages_crawled: number;
  issues_found: number;
  started_at: string;
  completed_at: string | null;
  max_pages: number;
  max_depth: number;
  critical_issues?: number;
  warning_issues?: number;
  notice_issues?: number;
  score?: number;
  current_url?: string;
}

export interface AuditsResponse {
  data: Audit[];
  meta: {
    current_page: number;
    per_page: number;
    total: number;
    last_page: number;
  };
}

export interface AuditsQueryParams {
  page?: number;
  per_page?: number;
  status?: AuditStatus;
}

export interface StartAuditParams {
  siteId: string;
  max_pages: number;
  max_depth: number;
}

// ============================================================
// API Functions
// ============================================================

/**
 * Fetch audits for a site
 */
async function fetchAudits(siteId: string, params?: AuditsQueryParams): Promise<AuditsResponse> {
  const searchParams = new URLSearchParams();
  
  if (params?.page) {
    searchParams.set('page', params.page.toString());
  }
  
  if (params?.per_page) {
    searchParams.set('per_page', params.per_page.toString());
  }
  
  if (params?.status) {
    searchParams.set('status', params.status);
  }
  
  const queryString = searchParams.toString();
  const url = `${API_BASE}/sites/${siteId}/audits${queryString ? `?${queryString}` : ''}`;
  
  const response = await fetch(url, {
    method: 'GET',
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    },
    credentials: 'include',
  });
  
  if (!response.ok) {
    if (response.status === 401) {
      throw new Error('Unauthorized');
    }
    if (response.status === 404) {
      throw new Error('Site not found');
    }
    const error = await response.json().catch(() => ({ message: 'Failed to fetch audits' }));
    throw new Error(error.message || `HTTP ${response.status}`);
  }
  
  return response.json();
}

/**
 * Start a new audit
 */
async function startAudit(params: StartAuditParams): Promise<Audit> {
  const response = await fetch(`${API_BASE}/sites/${params.siteId}/audits`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    },
    credentials: 'include',
    body: JSON.stringify({
      max_pages: params.max_pages,
      max_depth: params.max_depth,
    }),
  });
  
  if (!response.ok) {
    if (response.status === 401) {
      throw new Error('Unauthorized');
    }
    if (response.status === 409) {
      throw new Error('An audit is already running for this site');
    }
    if (response.status === 422) {
      const error = await response.json();
      throw new Error(error.message || 'Validation failed');
    }
    throw new Error(`HTTP ${response.status}`);
  }
  
  return response.json();
}

/**
 * Cancel a running audit
 */
async function cancelAudit(auditId: number): Promise<Audit> {
  const response = await fetch(`${API_BASE}/audits/${auditId}`, {
    method: 'DELETE',
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    },
    credentials: 'include',
  });
  
  if (!response.ok) {
    if (response.status === 401) {
      throw new Error('Unauthorized');
    }
    if (response.status === 400) {
      const error = await response.json();
      throw new Error(error.message || 'Cannot cancel audit');
    }
    if (response.status === 404) {
      throw new Error('Audit not found');
    }
    throw new Error(`HTTP ${response.status}`);
  }
  
  return response.json();
}

// ============================================================
// Hooks
// ============================================================

/**
 * Hook for fetching audits with conditional polling
 * 
 * Polls every 5 seconds when any audit has 'running' status
 */
export function useAudits(
  siteId: string,
  params?: AuditsQueryParams
): UseQueryResult<AuditsResponse, Error> & {
  audits: Audit[];
  meta: AuditsResponse['meta'];
} {
  const query = useQuery<AuditsResponse, Error>({
    queryKey: ['audits', siteId, params],
    queryFn: () => fetchAudits(siteId, params),
    refetchInterval: (query) => {
      // Poll every 5s if any audit is running
      const data = query.state.data;
      if (data?.data?.some(audit => audit.status === 'running')) {
        return 5000;
      }
      return false;
    },
    refetchIntervalInBackground: true,
  });
  
  return {
    ...query,
    audits: query.data?.data ?? [],
    meta: query.data?.meta ?? {
      current_page: 1,
      per_page: 15,
      total: 0,
      last_page: 1,
    },
  };
}

/**
 * Hook for starting a new audit
 */
export function useStartAudit() {
  const queryClient = useQueryClient();
  
  return useMutation<Audit, Error, StartAuditParams>({
    mutationFn: startAudit,
    onSuccess: () => {
      // Invalidate audits queries to refetch
      queryClient.invalidateQueries({ queryKey: ['audits'] });
    },
  });
}

/**
 * Hook for canceling a running audit
 */
export function useCancelAudit() {
  const queryClient = useQueryClient();
  
  return useMutation<Audit, Error, number>({
    mutationFn: cancelAudit,
    onSuccess: () => {
      // Invalidate audits queries to refetch
      queryClient.invalidateQueries({ queryKey: ['audits'] });
    },
  });
}

// ============================================================
// Query Keys
// ============================================================

export const auditsKeys = {
  all: ['audits'] as const,
  list: (siteId: string, params?: AuditsQueryParams) => 
    [...auditsKeys.all, 'list', siteId, params] as const,
  detail: (auditId: number) => [...auditsKeys.all, 'detail', auditId] as const,
};

export default useAudits;
