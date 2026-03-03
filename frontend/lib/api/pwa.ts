/**
 * PWA API Helper - TASK-24-10
 * 
 * API functions for fetching and managing PWA analysis data.
 * 
 * @see TASK-24-8 (PWA Analysis API Endpoints)
 * @see TASK-24-10 (PWA Dashboard Components)
 */

"use client";

import useSWR, { mutate } from "swr";
import type {
  PWAAnalysisData,
  PWAAnalysisResponse,
  PWAAnalysisErrorResponse,
  PWAAnalyzeResponse,
} from "@/lib/types/pwa";

const API_BASE = process.env.NEXT_PUBLIC_API_URL || '/api/v1';

/**
 * Fetch PWA analysis for a site
 * 
 * @param siteId - Site ID
 * @returns PWA analysis data
 * @throws Error if fetch fails
 */
export async function fetchPWAAnalysis(siteId: number): Promise<PWAAnalysisData> {
  const response = await fetch(`${API_BASE}/sites/${siteId}/pwa`, {
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
      const error: PWAAnalysisErrorResponse = await response.json();
      throw new Error(error.error?.message || 'No PWA analysis found');
    }
    if (response.status === 403) {
      throw new Error('Access denied');
    }
    if (response.status === 429) {
      const error: PWAAnalysisErrorResponse = await response.json();
      const retryAfter = error.error?.retryAfter || 60;
      throw new Error(`Rate limited. Try again in ${retryAfter} seconds.`);
    }
    throw new Error(`HTTP ${response.status}`);
  }

  const json: PWAAnalysisResponse = await response.json();
  return json.data;
}

/**
 * Trigger a new PWA analysis
 * 
 * @param siteId - Site ID
 * @returns Job details for the queued analysis
 * @throws Error if trigger fails
 */
export async function triggerPWAAnalysis(siteId: number): Promise<PWAAnalyzeResponse> {
  const response = await fetch(`${API_BASE}/sites/${siteId}/pwa/analyze`, {
    method: 'POST',
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
    if (response.status === 403) {
      throw new Error('Access denied');
    }
    if (response.status === 429) {
      const error: PWAAnalysisErrorResponse = await response.json();
      const retryAfter = error.error?.retryAfter || 60;
      throw new Error(`Rate limited. Try again in ${retryAfter} seconds.`);
    }
    throw new Error(`HTTP ${response.status}`);
  }

  const json: PWAAnalyzeResponse = await response.json();
  return json;
}

/**
 * Refresh PWA analysis (invalidate cache and re-analyze)
 * 
 * @param siteId - Site ID
 * @returns Job details for the queued refresh
 * @throws Error if refresh fails
 */
export async function refreshPWAAnalysis(siteId: number): Promise<PWAAnalyzeResponse> {
  const response = await fetch(`${API_BASE}/sites/${siteId}/pwa/refresh`, {
    method: 'POST',
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
    if (response.status === 403) {
      throw new Error('Access denied');
    }
    if (response.status === 429) {
      const error: PWAAnalysisErrorResponse = await response.json();
      const retryAfter = error.error?.retryAfter || 60;
      throw new Error(`Rate limited. Try again in ${retryAfter} seconds.`);
    }
    throw new Error(`HTTP ${response.status}`);
  }

  const json: PWAAnalyzeResponse = await response.json();
  return json;
}

/**
 * SWR key for PWA analysis
 */
export const pwaKeys = {
  analysis: (siteId: number) => ['pwa', 'analysis', siteId] as const,
};

/**
 * Hook to fetch PWA analysis data using SWR
 * 
 * @param siteId - Site ID
 * @returns PWA analysis data, loading state, error, and actions
 */
export function usePWAAnalysis(siteId: number) {
  const { data, error, isLoading, mutate: revalidate } = useSWR<PWAAnalysisData>(
    pwaKeys.analysis(siteId),
    () => fetchPWAAnalysis(siteId),
    {
      revalidateOnFocus: false,
      revalidateOnReconnect: true,
      dedupingInterval: 5000, // 5 seconds
      errorRetryCount: 1,
      shouldRetryOnError: false,
    }
  );

  // Trigger new analysis
  const triggerAnalysis = async () => {
    const result = await triggerPWAAnalysis(siteId);
    // Revalidate after a short delay to allow the job to process
    setTimeout(() => revalidate(), 2000);
    return result;
  };

  // Refresh analysis
  const refreshAnalysis = async () => {
    const result = await refreshPWAAnalysis(siteId);
    // Revalidate after a short delay
    setTimeout(() => revalidate(), 2000);
    return result;
  };

  return {
    data,
    error,
    isLoading,
    revalidate,
    triggerAnalysis,
    refreshAnalysis,
  };
}

/**
 * Hook return type for type inference
 */
export type UsePWAAnalysisReturn = ReturnType<typeof usePWAAnalysis>;
