/**
 * useBadges Hook - TASK-33-24
 *
 * Custom hook for fetching badge management data using SWR.
 *
 * @module lib/hooks/useBadges
 */

'use client';

import useSWR, { mutate } from 'swr';
import type { PublicAudit, BadgeSortField, SortDirection, BadgeSortState } from '@/lib/types/badges';
import { fetchPublicAudits, toggleAuditVisibility, badgeKeys } from '@/lib/api/badges';

// ============================================================
// Types
// ============================================================

export interface UseBadgesReturn {
  /** Public audits data */
  audits: PublicAudit[];
  /** Loading state */
  isLoading: boolean;
  /** Error state */
  error: Error | null;
  /** Refresh audits data */
  refresh: () => void;
  /** Toggle audit visibility */
  toggleVisibility: (auditId: number, isPublic: boolean) => Promise<void>;
  /** Sort state */
  sortState: BadgeSortState;
  /** Set sort field */
  setSortField: (field: BadgeSortField) => void;
  /** Sorted audits */
  sortedAudits: PublicAudit[];
}

// ============================================================
// Hook
// ============================================================

/**
 * Hook to fetch and manage public audits for badge management
 *
 * @returns Badge data, loading state, error, and actions
 */
export function useBadges(): UseBadgesReturn {
  // Fetch public audits
  const { data, error, isLoading, mutate: revalidate } = useSWR<PublicAudit[]>(
    badgeKeys.publicAudits(),
    fetchPublicAudits,
    {
      revalidateOnFocus: false,
      revalidateOnReconnect: true,
      dedupingInterval: 5000,
      errorRetryCount: 3,
    }
  );

  // Sort state (default: impressions descending)
  const [sortState, setSortState] = React.useState<BadgeSortState>({
    field: 'impressions',
    direction: 'desc',
  });

  // Set sort field (toggle direction if same field)
  const setSortField = (field: BadgeSortField) => {
    setSortState((prev) => {
      if (prev.field === field) {
        return { ...prev, direction: prev.direction === 'asc' ? 'desc' : 'asc' };
      }
      return { field, direction: 'desc' };
    });
  };

  // Sort audits
  const sortedAudits = React.useMemo(() => {
    if (!data) return [];

    return [...data].sort((a, b) => {
      const aVal = a.analytics[sortState.field];
      const bVal = b.analytics[sortState.field];
      const modifier = sortState.direction === 'asc' ? 1 : -1;

      if (typeof aVal === 'number' && typeof bVal === 'number') {
        return (aVal - bVal) * modifier;
      }
      return 0;
    });
  }, [data, sortState]);

  // Toggle visibility
  const handleToggleVisibility = async (auditId: number, isPublic: boolean) => {
    try {
      await toggleAuditVisibility(auditId, isPublic);

      // Optimistically update local data
      revalidate(
        (currentAudits) => {
          if (!currentAudits) return currentAudits;
          return currentAudits.map((audit) =>
            audit.id === auditId ? { ...audit, isPublic } : audit
          );
        },
        false // Don't revalidate immediately
      );
    } catch (err) {
      // Revert on error by revalidating
      revalidate();
      throw err;
    }
  };

  return {
    audits: data ?? [],
    isLoading,
    error: error ?? null,
    refresh: () => revalidate(),
    toggleVisibility: handleToggleVisibility,
    sortState,
    setSortField,
    sortedAudits,
  };
}

// Need React import for useState and useMemo
import * as React from 'react';
