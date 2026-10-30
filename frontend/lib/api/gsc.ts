// GSC API Service for TASK-12-10

import { GSCDataResponse, GSCOpportunitiesResponse, GSCTrendResponse, DateRange, GSCPropertiesResponse, GSCConnectRequest, GSCConnectResponse, GSCDisconnectResponse } from '@/lib/types/gsc';

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

// GSC Connection API for TASK-12-9

/**
 * Fetch connected GSC properties for current user
 */
export async function fetchGSCProperties(): Promise<GSCPropertiesResponse> {
  const response = await fetch(`${API_BASE}/gsc/properties`);

  if (!response.ok) {
    throw new Error(`Failed to fetch GSC properties: ${response.statusText}`);
  }

  return response.json();
}

/**
 * Connect a GSC property using OAuth authorization code
 */
export async function connectGSCProperty(data: GSCConnectRequest): Promise<GSCConnectResponse> {
  const response = await fetch(`${API_BASE}/gsc/connect`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify(data),
  });

  if (!response.ok) {
    const errorData = await response.json().catch(() => ({}));
    throw new Error(errorData?.message || `Failed to connect GSC property: ${response.statusText}`);
  }

  return response.json();
}

/**
 * Disconnect a GSC property
 */
export async function disconnectGSCProperty(propertyId: number): Promise<GSCDisconnectResponse> {
  const response = await fetch(`${API_BASE}/gsc/disconnect/${propertyId}`, {
    method: 'DELETE',
  });

  if (!response.ok) {
    const errorData = await response.json().catch(() => ({}));
    throw new Error(errorData?.message || `Failed to disconnect GSC property: ${response.statusText}`);
  }

  return response.json();
}

/**
 * Get Google OAuth authorization URL
 */
export function getGSCOAuthUrl(): string {
  // The backend will redirect to Google OAuth
  // After authorization, Google redirects back to our callback
  return `${API_BASE}/gsc/oauth/redirect`;
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
