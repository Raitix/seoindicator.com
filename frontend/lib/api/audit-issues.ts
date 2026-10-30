/**
 * Audit Issues API
 * 
 * Task: TASK-14-16 - Create Audit Detail Page (Frontend)
 * Spec: SPEC-14 - Technical SEO Site Audit
 * 
 * API functions for fetching and managing audit issues.
 */

import useSWR, { mutate as globalMutate } from 'swr';
import type {
  Issue,
  Audit,
  SeverityCounts,
  IssueFilters,
  IssueListResponse,
  PaginationMeta,
  UpdateIssueStatusParams,
  BulkUpdateIssuesParams,
  UseAuditIssuesOptions,
} from '@/lib/types/audit-issues';

const API_BASE = '/api/v1';

// ============================================================
// Normalizers
// ============================================================

const normalizeIssue = (item: any): Issue => ({
  id: item.id,
  url: item.url,
  issue_type: {
    id: item.issue_type?.id ?? item.issue_type_id,
    name: item.issue_type?.name ?? item.issue_type_name ?? 'Unknown',
    category: item.issue_type?.category ?? item.issue_category ?? 'Technical',
    description: item.issue_type?.description,
  },
  severity: item.severity ?? 'notice',
  description: item.description ?? '',
  recommendation: item.recommendation ?? '',
  status: item.status ?? 'open',
  created_at: item.created_at ?? item.createdAt,
  updated_at: item.updated_at ?? item.updatedAt,
  audit_id: item.audit_id ?? item.auditId,
});

const normalizeIssueList = (payload: any): IssueListResponse => {
  if (Array.isArray(payload)) {
    return {
      data: payload.map(normalizeIssue),
      meta: { current_page: 1, per_page: 25, total: payload.length, last_page: 1 },
    };
  }

  const rawData = payload?.data ?? [];
  return {
    data: Array.isArray(rawData) ? rawData.map(normalizeIssue) : [],
    meta: payload?.meta ?? payload?.pagination ?? {
      current_page: 1,
      per_page: 25,
      total: rawData.length,
      last_page: 1,
    },
  };
};

const normalizeAudit = (item: any): Audit => ({
  id: item.id,
  status: item.status ?? 'pending',
  pages_crawled: item.pages_crawled ?? item.pagesCrawled ?? 0,
  issues_found: item.issues_found ?? item.issuesFound ?? 0,
  started_at: item.started_at ?? item.startedAt,
  completed_at: item.completed_at ?? item.completedAt,
  site_id: item.site_id ?? item.siteId,
  score: item.score,
});

// ============================================================
// Query String Builder
// ============================================================

const buildQueryString = (params: IssueFilters & { page?: number; perPage?: number } = {}) => {
  const query = new URLSearchParams();

  if (params.type) {
    query.set('type', params.type);
  }
  if (params.severity) {
    query.set('severity', params.severity);
  }
  if (params.status) {
    query.set('status', params.status);
  }
  if (params.search) {
    query.set('q', params.search);
  }
  if (params.sortBy) {
    query.set('sortBy', params.sortBy);
  }
  if (params.sortOrder) {
    query.set('sortOrder', params.sortOrder);
  }
  if (params.page) {
    query.set('page', String(params.page));
  }
  if (params.perPage) {
    query.set('perPage', String(params.perPage));
  }

  const queryString = query.toString();
  return queryString ? `?${queryString}` : '';
};

// ============================================================
// Fetchers
// ============================================================

const fetcher = async <T>(url: string): Promise<T> => {
  const response = await fetch(url, {
    credentials: 'include',
    headers: {
      'Accept': 'application/json',
    },
  });

  if (!response.ok) {
    if (response.status === 401) {
      throw new Error('Unauthorized');
    }
    if (response.status === 404) {
      throw new Error('Not found');
    }
    if (response.status === 403) {
      throw new Error('Access denied');
    }
    const errorBody = await response.json().catch(() => null);
    throw new Error(errorBody?.message ?? `HTTP ${response.status}`);
  }

  const payload = await response.json();
  return payload;
};

// ============================================================
// Hooks
// ============================================================

/**
 * Hook for fetching audit issues
 */
export function useAuditIssues(
  auditId: string,
  filters: IssueFilters = {},
  options: UseAuditIssuesOptions = {}
) {
  const { page = 1, perPage = 25, enabled = true } = options;

  const queryString = buildQueryString({ ...filters, page, perPage });
  const key = enabled && auditId ? `${API_BASE}/audits/${auditId}/issues${queryString}` : null;

  const { data, error, isLoading, mutate } = useSWR<IssueListResponse>(key, fetcher);

  return {
    issues: data?.data ?? [],
    meta: data?.meta,
    isLoading,
    error,
    mutate,
    refetch: () => mutate(),
  };
}

/**
 * Hook for fetching audit summary
 */
export function useAuditSummary(auditId: string, enabled = true) {
  const key = enabled && auditId ? `${API_BASE}/audits/${auditId}` : null;

  const { data, error, isLoading, mutate } = useSWR<{ data: Audit; severityCounts: SeverityCounts }>(key, fetcher);

  return {
    audit: data?.data ? normalizeAudit(data.data) : null,
    severityCounts: data?.severityCounts ?? { critical: 0, warning: 0, notice: 0, clean: 0 },
    isLoading,
    error,
    mutate,
    refetch: () => mutate(),
  };
}

/**
 * Hook for updating issue status
 */
export function useUpdateIssueStatus() {
  const updateStatus = async (params: UpdateIssueStatusParams): Promise<Issue> => {
    const response = await fetch(`${API_BASE}/issues/${params.issueId}`, {
      method: 'PATCH',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
      credentials: 'include',
      body: JSON.stringify({ status: params.status }),
    });

    if (!response.ok) {
      if (response.status === 401) {
        throw new Error('Unauthorized');
      }
      if (response.status === 403) {
        throw new Error('Access denied');
      }
      const errorBody = await response.json().catch(() => null);
      throw new Error(errorBody?.message ?? 'Failed to update issue');
    }

    const payload = await response.json();
    
    // Invalidate all audit-issues queries
    await globalMutate((key) => typeof key === 'string' && key.includes('/audits/') && key.includes('/issues'));
    
    return normalizeIssue(payload?.data ?? payload);
  };

  return { updateStatus };
}

/**
 * Hook for bulk updating issues
 */
export function useBulkUpdateIssues() {
  const bulkUpdate = async (params: BulkUpdateIssuesParams): Promise<{ updated: number }> => {
    const response = await fetch(`${API_BASE}/audits/${params.auditId}/issues/bulk`, {
      method: 'PATCH',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
      credentials: 'include',
      body: JSON.stringify({
        issue_ids: params.issueIds,
        status: params.status,
      }),
    });

    if (!response.ok) {
      if (response.status === 401) {
        throw new Error('Unauthorized');
      }
      if (response.status === 403) {
        throw new Error('Access denied');
      }
      const errorBody = await response.json().catch(() => null);
      throw new Error(errorBody?.message ?? 'Failed to bulk update issues');
    }

    const payload = await response.json();
    
    // Invalidate all audit-issues queries
    await globalMutate((key) => typeof key === 'string' && key.includes('/audits/') && key.includes('/issues'));
    
    return { updated: payload?.updated ?? params.issueIds.length };
  };

  return { bulkUpdate };
}

// ============================================================
// Direct API Functions (for use outside of hooks)
// ============================================================

/**
 * Fetch audit issues directly
 */
export async function fetchAuditIssues(
  auditId: string,
  filters: IssueFilters = {},
  page = 1,
  perPage = 25
): Promise<IssueListResponse> {
  const queryString = buildQueryString({ ...filters, page, perPage });
  const response = await fetch(`${API_BASE}/audits/${auditId}/issues${queryString}`, {
    credentials: 'include',
    headers: {
      'Accept': 'application/json',
    },
  });

  if (!response.ok) {
    throw new Error(`Failed to fetch audit issues: ${response.statusText}`);
  }

  const payload = await response.json();
  return normalizeIssueList(payload);
}

/**
 * Update issue status directly
 */
export async function updateIssueStatus(params: UpdateIssueStatusParams): Promise<Issue> {
  const response = await fetch(`${API_BASE}/issues/${params.issueId}`, {
    method: 'PATCH',
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    },
    credentials: 'include',
    body: JSON.stringify({ status: params.status }),
  });

  if (!response.ok) {
    const errorBody = await response.json().catch(() => null);
    throw new Error(errorBody?.message ?? 'Failed to update issue');
  }

  const payload = await response.json();
  
  // Invalidate cache
  await globalMutate((key) => typeof key === 'string' && key.includes('/audits/') && key.includes('/issues'));
  
  return normalizeIssue(payload?.data ?? payload);
}

/**
 * Bulk update issues directly
 */
export async function bulkUpdateIssues(params: BulkUpdateIssuesParams): Promise<{ updated: number }> {
  const response = await fetch(`${API_BASE}/audits/${params.auditId}/issues/bulk`, {
    method: 'PATCH',
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    },
    credentials: 'include',
    body: JSON.stringify({
      issue_ids: params.issueIds,
      status: params.status,
    }),
  });

  if (!response.ok) {
    const errorBody = await response.json().catch(() => null);
    throw new Error(errorBody?.message ?? 'Failed to bulk update issues');
  }

  const payload = await response.json();
  
  // Invalidate cache
  await globalMutate((key) => typeof key === 'string' && key.includes('/audits/') && key.includes('/issues'));
  
  return { updated: payload?.updated ?? params.issueIds.length };
}

/**
 * Default pagination meta
 */
export const defaultPaginationMeta: PaginationMeta = {
  current_page: 1,
  per_page: 25,
  total: 0,
  last_page: 1,
};
