/**
 * Badges API Helper - TASK-33-24
 *
 * API functions for fetching public audits and badge analytics.
 *
 * @module lib/api/badges
 */

import type {
  PublicAudit,
  PublicAuditsApiResponse,
  ToggleVisibilityApiResponse,
  BadgeAnalyticsApiResponse,
} from '@/lib/types/badges';

const API_BASE = process.env.NEXT_PUBLIC_API_URL || '/api/v1';

// ============================================================
// Fetch Public Audits
// ============================================================

/**
 * Fetch all public audits for the current user
 *
 * @returns List of public audits with analytics
 */
export async function fetchPublicAudits(): Promise<PublicAudit[]> {
  const response = await fetch(`${API_BASE}/audits/public`, {
    method: 'GET',
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    },
    credentials: 'include',
  });

  if (!response.ok) {
    const error = await response.json().catch(() => ({ message: 'Failed to fetch public audits' }));
    throw new Error(error.message || `HTTP ${response.status}`);
  }

  const json: PublicAuditsApiResponse = await response.json();

  if (!json.success) {
    throw new Error('Failed to fetch public audits data');
  }

  return json.data;
}

// ============================================================
// Toggle Visibility
// ============================================================

/**
 * Toggle audit visibility (public/private)
 *
 * @param auditId - Audit ID to toggle
 * @param isPublic - New visibility state
 * @returns Updated audit data
 */
export async function toggleAuditVisibility(
  auditId: number,
  isPublic: boolean
): Promise<{ id: number; isPublic: boolean }> {
  const response = await fetch(`${API_BASE}/audits/${auditId}/visibility`, {
    method: 'PATCH',
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    },
    credentials: 'include',
    body: JSON.stringify({ isPublic }),
  });

  if (!response.ok) {
    const error = await response.json().catch(() => ({ message: 'Failed to update visibility' }));
    throw new Error(error.message || `HTTP ${response.status}`);
  }

  const json: ToggleVisibilityApiResponse = await response.json();

  if (!json.success) {
    throw new Error('Failed to update visibility');
  }

  return json.data;
}

// ============================================================
// Fetch Badge Analytics Summary
// ============================================================

/**
 * Fetch aggregate badge analytics
 *
 * @returns Summary of all badge analytics
 */
export async function fetchBadgeAnalytics(): Promise<BadgeAnalyticsApiResponse['data']> {
  const response = await fetch(`${API_BASE}/badge/analytics`, {
    method: 'GET',
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    },
    credentials: 'include',
  });

  if (!response.ok) {
    const error = await response.json().catch(() => ({ message: 'Failed to fetch badge analytics' }));
    throw new Error(error.message || `HTTP ${response.status}`);
  }

  const json: BadgeAnalyticsApiResponse = await response.json();

  if (!json.success) {
    throw new Error('Failed to fetch badge analytics');
  }

  return json.data;
}

// ============================================================
// SWR Keys
// ============================================================

/**
 * SWR keys for badge data
 */
export const badgeKeys = {
  all: ['badges'] as const,
  publicAudits: () => [...badgeKeys.all, 'publicAudits'] as const,
  analytics: () => [...badgeKeys.all, 'analytics'] as const,
};
