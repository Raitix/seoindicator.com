/**
 * Analysis API Helper - TASK-25-20
 * 
 * API functions for fetching and managing analysis history.
 * 
 * @see SPEC-025
 * @see TASK-25-20
 */

import {
  AnalysisRecord,
  AnalysisDetail,
  AnalysisListResponse,
  AnalysisDetailResponse,
  AnalysisQueryParams,
  ToolOption,
} from '@/lib/types/analysis';

const API_BASE = process.env.NEXT_PUBLIC_API_URL || '/api/v1';

/**
 * Fetch paginated list of analyses
 * 
 * @param params - Query parameters for filtering and pagination
 * @returns Paginated list of analyses
 */
export async function fetchAnalyses(params?: AnalysisQueryParams): Promise<AnalysisListResponse> {
  const searchParams = new URLSearchParams();
  
  if (params?.page) {
    searchParams.set('page', params.page.toString());
  }
  
  if (params?.perPage) {
    searchParams.set('perPage', params.perPage.toString());
  }
  
  if (params?.tool) {
    searchParams.set('tool', params.tool);
  }
  
  if (params?.status) {
    searchParams.set('status', params.status);
  }
  
  if (params?.from) {
    searchParams.set('from', params.from);
  }
  
  if (params?.to) {
    searchParams.set('to', params.to);
  }
  
  const queryString = searchParams.toString();
  const url = `${API_BASE}/analyses${queryString ? `?${queryString}` : ''}`;
  
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
    const error = await response.json().catch(() => ({ message: 'Failed to fetch analyses' }));
    throw new Error(error.message || `HTTP ${response.status}`);
  }
  
  const json: AnalysisListResponse = await response.json();
  return json;
}

/**
 * Fetch single analysis details
 * 
 * @param id - Analysis ID
 * @returns Analysis detail with full results
 */
export async function fetchAnalysisDetail(id: number): Promise<AnalysisDetail> {
  const response = await fetch(`${API_BASE}/analyses/${id}`, {
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
      throw new Error('Analysis not found');
    }
    if (response.status === 403) {
      throw new Error('Access denied');
    }
    throw new Error(`HTTP ${response.status}`);
  }
  
  const json: AnalysisDetailResponse = await response.json();
  return json.data;
}

/**
 * Delete an analysis
 * 
 * @param id - Analysis ID to delete
 * @returns True if successful
 */
export async function deleteAnalysis(id: number): Promise<boolean> {
  const response = await fetch(`${API_BASE}/analyses/${id}`, {
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
    if (response.status === 404) {
      throw new Error('Analysis not found');
    }
    if (response.status === 403) {
      throw new Error('Access denied');
    }
    throw new Error(`HTTP ${response.status}`);
  }
  
  return true;
}

/**
 * Fetch cost breakdown for an analysis
 * 
 * @param id - Analysis ID
 * @returns Cost breakdown data
 */
export async function fetchAnalysisCosts(id: number): Promise<{
  total: number;
  breakdown: Array<{ type: string; amount: number }>;
  currency: string;
}> {
  const response = await fetch(`${API_BASE}/analyses/${id}/costs`, {
    method: 'GET',
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    },
    credentials: 'include',
  });
  
  if (!response.ok) {
    throw new Error(`HTTP ${response.status}`);
  }
  
  const json = await response.json();
  return json.data;
}

/**
 * Fetch available tools for filter dropdown
 * 
 * @returns List of tool options
 */
export async function fetchTools(): Promise<ToolOption[]> {
  // For now, return static tools
  // In future, this could be fetched from an API endpoint
  return [
    { value: '', label: 'All Tools' },
    { value: 'meta-tag-analyzer', label: 'Meta Tag Analyzer' },
    { value: 'keyword-density', label: 'Keyword Density Checker' },
    { value: 'mobile-friendly', label: 'Mobile-Friendly Checker' },
    { value: 'social-preview', label: 'Social Media Preview' },
  ];
}

/**
 * React Query/SWR keys for analysis data
 */
export const analysisKeys = {
  all: ['analyses'] as const,
  list: (params?: AnalysisQueryParams) => [...analysisKeys.all, 'list', params] as const,
  detail: (id: number) => [...analysisKeys.all, 'detail', id] as const,
  costs: (id: number) => [...analysisKeys.all, 'costs', id] as const,
};

/**
 * Build URL search params from filters
 */
export function buildFilterUrl(params: AnalysisQueryParams): string {
  const searchParams = new URLSearchParams();
  
  if (params.page && params.page > 1) {
    searchParams.set('page', params.page.toString());
  }
  
  if (params.perPage && params.perPage !== 15) {
    searchParams.set('perPage', params.perPage.toString());
  }
  
  if (params.tool) {
    searchParams.set('tool', params.tool);
  }
  
  if (params.status) {
    searchParams.set('status', params.status);
  }
  
  if (params.from) {
    searchParams.set('from', params.from);
  }
  
  if (params.to) {
    searchParams.set('to', params.to);
  }
  
  const queryString = searchParams.toString();
  return queryString ? `?${queryString}` : '';
}

/**
 * Parse filters from URL search params
 */
export function parseFilterUrl(searchParams: URLSearchParams): AnalysisQueryParams {
  const page = searchParams.get('page');
  const perPage = searchParams.get('perPage');
  const tool = searchParams.get('tool');
  const status = searchParams.get('status') as AnalysisQueryParams['status'];
  const from = searchParams.get('from');
  const to = searchParams.get('to');
  
  return {
    page: page ? parseInt(page, 10) : 1,
    perPage: perPage ? parseInt(perPage, 10) : 15,
    tool: tool || undefined,
    status: status || undefined,
    from: from || undefined,
    to: to || undefined,
  };
}
