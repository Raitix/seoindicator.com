"use client";

import * as React from "react";
import useSWR from "swr";
import { useRouter, useSearchParams, usePathname } from "next/navigation";

import {
  fetchAnalyses,
  fetchAnalysisDetail,
  deleteAnalysis,
  buildFilterUrl,
  parseFilterUrl,
  analysisKeys,
} from "@/lib/api/analyses";
import type {
  AnalysisQueryParams,
  AnalysisFilters,
  AnalysisRecord,
  AnalysisDetail,
  AnalysisListResponse,
} from "@/lib/types/analysis";

// ==================== Types ====================

interface UseAnalysisHistoryReturn {
  /** Analysis list data */
  data: AnalysisListResponse | undefined;
  
  /** Loading state */
  isLoading: boolean;
  
  /** Error state */
  error: Error | undefined;
  
  /** Current filters */
  filters: AnalysisQueryParams;
  
  /** Update filters */
  setFilters: (filters: AnalysisFilters) => void;
  
  /** Clear all filters */
  clearFilters: () => void;
  
  /** Refresh data */
  refresh: () => void;
  
  /** Go to page */
  goToPage: (page: number) => void;
  
  /** Change per page */
  changePerPage: (perPage: number) => void;
}

// ==================== Hook ====================

export function useAnalysisHistory(): UseAnalysisHistoryReturn {
  const router = useRouter();
  const pathname = usePathname();
  const searchParams = useSearchParams();

  // Parse initial filters from URL
  const initialFilters = React.useMemo(() => {
    return parseFilterUrl(searchParams);
  }, [searchParams]);

  // Local filter state
  const [filters, setFiltersState] = React.useState<AnalysisQueryParams>(initialFilters);

  // SWR fetcher
  const fetcher = React.useCallback(async (params: AnalysisQueryParams) => {
    const result = await fetchAnalyses(params);
    return result;
  }, []);

  // SWR hook for fetching analyses
  const { data, isLoading, error, mutate } = useSWR(
    analysisKeys.list(filters),
    () => fetcher(filters),
    {
      revalidateOnFocus: false,
      dedupingInterval: 5000,
    }
  );

  // Update URL when filters change
  const updateUrl = React.useCallback(
    (newFilters: AnalysisQueryParams) => {
      const url = buildFilterUrl(newFilters);
      router.replace(`${pathname}${url}`, { scroll: false });
    },
    [router, pathname]
  );

  // Set filters (resets to page 1)
  const setFilters = React.useCallback(
    (newFilters: AnalysisFilters) => {
      const updatedFilters: AnalysisQueryParams = {
        ...newFilters,
        page: 1, // Reset to page 1 when filters change
        perPage: filters.perPage,
      };
      setFiltersState(updatedFilters);
      updateUrl(updatedFilters);
    },
    [filters.perPage, updateUrl]
  );

  // Clear all filters
  const clearFilters = React.useCallback(() => {
    const clearedFilters: AnalysisQueryParams = {
      page: 1,
      perPage: filters.perPage,
    };
    setFiltersState(clearedFilters);
    updateUrl(clearedFilters);
  }, [filters.perPage, updateUrl]);

  // Go to page
  const goToPage = React.useCallback(
    (page: number) => {
      const updatedFilters = { ...filters, page };
      setFiltersState(updatedFilters);
      updateUrl(updatedFilters);
    },
    [filters, updateUrl]
  );

  // Change per page
  const changePerPage = React.useCallback(
    (perPage: number) => {
      const updatedFilters = { ...filters, perPage, page: 1 }; // Reset to page 1
      setFiltersState(updatedFilters);
      updateUrl(updatedFilters);
    },
    [filters, updateUrl]
  );

  // Refresh data
  const refresh = React.useCallback(() => {
    mutate();
  }, [mutate]);

  return {
    data,
    isLoading,
    error,
    filters,
    setFilters,
    clearFilters,
    refresh,
    goToPage,
    changePerPage,
  };
}

// ==================== Delete Hook ====================

interface UseAnalysisDeleteReturn {
  /** Delete an analysis */
  deleteOne: (id: number) => Promise<void>;
  
  /** Is deletion in progress */
  isDeleting: boolean;
  
  /** Deletion error */
  error: Error | null;
}

export function useAnalysisDelete(
  onSuccess?: () => void
): UseAnalysisDeleteReturn {
  const [isDeleting, setIsDeleting] = React.useState(false);
  const [error, setError] = React.useState<Error | null>(null);

  const deleteOne = React.useCallback(
    async (id: number) => {
      setIsDeleting(true);
      setError(null);

      try {
        await deleteAnalysis(id);
        onSuccess?.();
      } catch (err) {
        setError(err instanceof Error ? err : new Error("Failed to delete"));
        throw err;
      } finally {
        setIsDeleting(false);
      }
    },
    [onSuccess]
  );

  return {
    deleteOne,
    isDeleting,
    error,
  };
}

// ==================== Detail Hook ====================

interface UseAnalysisDetailReturn {
  /** Analysis detail data */
  data: AnalysisDetail | undefined;
  
  /** Loading state */
  isLoading: boolean;
  
  /** Error state */
  error: Error | undefined;
  
  /** Fetch detail */
  fetch: (id: number) => void;
}

export function useAnalysisDetail(): UseAnalysisDetailReturn {
  const [id, setId] = React.useState<number | null>(null);

  const { data, isLoading, error } = useSWR(
    id ? analysisKeys.detail(id) : null,
    () => fetchAnalysisDetail(id!),
    {
      revalidateOnFocus: false,
    }
  );

  const fetch = React.useCallback((analysisId: number) => {
    setId(analysisId);
  }, []);

  return {
    data,
    isLoading,
    error,
    fetch,
  };
}

export default useAnalysisHistory;
