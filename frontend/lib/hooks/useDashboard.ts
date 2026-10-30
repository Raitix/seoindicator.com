/**
 * useDashboard Hook - TASK-25-19
 * 
 * Custom hook for fetching dashboard data using SWR.
 * 
 * @see SPEC-025
 * @see TASK-25-19
 */

"use client";

import useSWR from "swr";
import type { DashboardData, DashboardQueryParams } from "@/lib/types/dashboard";
import { fetchDashboard } from "@/lib/api/dashboard";

/**
 * Hook to fetch dashboard summary data
 * 
 * @param params - Optional query parameters
 * @returns Dashboard data, loading state, error, and refresh function
 */
export function useDashboard(params?: DashboardQueryParams) {
  const { data, error, isLoading, mutate } = useSWR<DashboardData>(
    ["dashboard", params],
    () => fetchDashboard(params),
    {
      revalidateOnFocus: false,
      revalidateOnReconnect: true,
      dedupingInterval: 5000, // 5 seconds
      errorRetryCount: 3,
    }
  );

  const refresh = () => mutate();

  return {
    data,
    error,
    isLoading,
    refresh,
  };
}

/**
 * Hook return type for type inference
 */
export type UseDashboardReturn = ReturnType<typeof useDashboard>;
