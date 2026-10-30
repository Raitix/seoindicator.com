/**
 * Dashboard API Helper - TASK-25-19
 * 
 * API functions for fetching dashboard data.
 * 
 * @see SPEC-025
 * @see TASK-25-19
 */

import { DashboardData, DashboardApiResponse, DashboardQueryParams } from '@/lib/types/dashboard';

const API_BASE = process.env.NEXT_PUBLIC_API_URL || '/api/v1';

/**
 * Fetch dashboard summary data
 * 
 * @param params - Optional query parameters
 * @returns Dashboard data including stats, costs, analyses, and insights
 */
export async function fetchDashboard(params?: DashboardQueryParams): Promise<DashboardData> {
  const searchParams = new URLSearchParams();
  
  if (params?.recentLimit) {
    searchParams.set('recentLimit', params.recentLimit.toString());
  }
  
  if (params?.useCache === false) {
    searchParams.set('useCache', 'false');
  }
  
  const queryString = searchParams.toString();
  const url = `${API_BASE}/dashboard${queryString ? `?${queryString}` : ''}`;
  
  const response = await fetch(url, {
    method: 'GET',
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    },
    credentials: 'include',
  });
  
  if (!response.ok) {
    const error = await response.json().catch(() => ({ message: 'Failed to fetch dashboard' }));
    throw new Error(error.message || `HTTP ${response.status}`);
  }
  
  const json: DashboardApiResponse = await response.json();
  
  if (!json.success) {
    throw new Error('Failed to fetch dashboard data');
  }
  
  return json.data;
}

/**
 * Fetch dashboard stats only
 * 
 * @returns Stats data (total, month, week, today counts)
 */
export async function fetchDashboardStats(): Promise<DashboardData['stats']> {
  const response = await fetch(`${API_BASE}/dashboard/stats`, {
    method: 'GET',
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    },
    credentials: 'include',
  });
  
  if (!response.ok) {
    throw new Error('Failed to fetch dashboard stats');
  }
  
  const json = await response.json();
  return json.data;
}

/**
 * Fetch cost summary only
 * 
 * @returns Cost summary data
 */
export async function fetchCostSummary(): Promise<DashboardData['costSummary']> {
  const response = await fetch(`${API_BASE}/dashboard/cost-summary`, {
    method: 'GET',
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    },
    credentials: 'include',
  });
  
  if (!response.ok) {
    throw new Error('Failed to fetch cost summary');
  }
  
  const json = await response.json();
  return json.data;
}

/**
 * Fetch recent analyses only
 * 
 * @param limit - Number of analyses to fetch (default: 10)
 * @returns Recent analyses list
 */
export async function fetchRecentAnalyses(limit: number = 10): Promise<DashboardData['recentAnalyses']> {
  const response = await fetch(`${API_BASE}/dashboard/recent-analyses?limit=${limit}`, {
    method: 'GET',
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    },
    credentials: 'include',
  });
  
  if (!response.ok) {
    throw new Error('Failed to fetch recent analyses');
  }
  
  const json = await response.json();
  return json.data;
}

/**
 * Invalidate dashboard cache
 * 
 * Forces a refresh of cached dashboard data.
 */
export async function invalidateDashboardCache(): Promise<void> {
  const response = await fetch(`${API_BASE}/dashboard/refresh`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    },
    credentials: 'include',
  });
  
  if (!response.ok) {
    throw new Error('Failed to invalidate dashboard cache');
  }
}

/**
 * React Query keys for dashboard data
 */
export const dashboardKeys = {
  all: ['dashboard'] as const,
  summary: () => [...dashboardKeys.all, 'summary'] as const,
  stats: () => [...dashboardKeys.all, 'stats'] as const,
  costSummary: () => [...dashboardKeys.all, 'costSummary'] as const,
  recentAnalyses: (limit?: number) => [...dashboardKeys.all, 'recentAnalyses', limit] as const,
  insights: () => [...dashboardKeys.all, 'insights'] as const,
};
