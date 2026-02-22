// GSC API Service for TASK-12-10

import { GSCDataResponse, GSCOpportunitiesResponse, GSCTrendResponse, DateRange } from '@/lib/types/gsc';

const API_BASE = '/api';

/**
 * Fetch GSC metrics for a project
 */
export async function fetchGSCData(
  projectId: number,
  dateRange: DateRange
): Promise<GSCDataResponse> {
  const params = new URLSearchParams({
    from: dateRange.from,
    to: dateRange.to,
  });

  const response = await fetch(
    `${API_BASE}/projects/${projectId}/gsc?${params.toString()}`
  );

  if (!response.ok) {
    throw new Error(`Failed to fetch GSC data: ${response.statusText}`);
  }

  return response.json();
}

/**
 * Fetch GSC trend data for a project
 */
export async function fetchGSCTrend(
  projectId: number,
  dateRange: DateRange
): Promise<GSCTrendResponse> {
  const params = new URLSearchParams({
    from: dateRange.from,
    to: dateRange.to,
  });

  const response = await fetch(
    `${API_BASE}/projects/${projectId}/gsc/trend?${params.toString()}`
  );

  if (!response.ok) {
    throw new Error(`Failed to fetch GSC trend: ${response.statusText}`);
  }

  return response.json();
}

/**
 * Fetch opportunity keywords for a project
 */
export async function fetchGSCOpportunities(
  projectId: number
): Promise<GSCOpportunitiesResponse> {
  const response = await fetch(
    `${API_BASE}/projects/${projectId}/gsc/opportunities`
  );

  if (!response.ok) {
    throw new Error(`Failed to fetch opportunities: ${response.statusText}`);
  }

  return response.json();
}

/**
 * Get default date range (last 30 days)
 */
export function getDefaultDateRange(): DateRange {
  const to = new Date();
  const from = new Date();
  from.setDate(from.getDate() - 30);

  return {
    from: from.toISOString().split('T')[0],
    to: to.toISOString().split('T')[0],
  };
}

/**
 * Format date for display
 */
export function formatDate(dateString: string): string {
  const date = new Date(dateString);
  return date.toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
  });
}

/**
 * Format CTR as percentage
 */
export function formatCTR(ctr: number): string {
  return `${(ctr * 100).toFixed(2)}%`;
}

/**
 * Format number with commas
 */
export function formatNumber(num: number): string {
  return num.toLocaleString();
}

/**
 * Format position with decimal
 */
export function formatPosition(position: number): string {
  return position.toFixed(1);
}
