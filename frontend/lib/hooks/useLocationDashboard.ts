"use client";

import { useCallback } from "react";
import useSWR, { mutate as globalMutate } from "swr";

import { buildApiUrl } from "@/lib/api/base-url";
import { getStoredAuthToken } from "@/lib/api/auth";
import {
  normalizeAlertSummary,
  normalizeCompetitors,
  normalizeDashboardLocation,
  normalizeGBPInsights,
  normalizeRankings,
  normalizeReviewList,
  normalizeReviewStats,
} from "@/lib/location-dashboard";
import type {
  AlertSummary,
  CompetitorPreview,
  GBPInsights,
  LocalRankings,
  LocationDashboardLocation,
  ReviewPreview,
  ReviewStats,
} from "@/lib/types/location-dashboard";

const buildRequestHeaders = (headers?: HeadersInit, includeJsonContentType = false) => {
  const nextHeaders: Record<string, string> = Array.isArray(headers)
    ? Object.fromEntries(headers)
    : typeof Headers !== "undefined" && headers instanceof Headers
      ? Object.fromEntries(headers.entries())
      : { ...((headers as Record<string, string> | undefined) ?? {}) };

  if (!nextHeaders.Accept) {
    nextHeaders.Accept = "application/json";
  }

  if (includeJsonContentType && !nextHeaders["Content-Type"]) {
    nextHeaders["Content-Type"] = "application/json";
  }

  const token = getStoredAuthToken();
  if (token && !nextHeaders.Authorization) {
    nextHeaders.Authorization = `Bearer ${token}`;
  }

  return nextHeaders;
};

const buildRequestInit = (init: RequestInit = {}, includeJsonContentType = false): RequestInit => ({
  ...init,
  credentials: "include",
  headers: buildRequestHeaders(init.headers, includeJsonContentType),
});

const fetchJson = async (url: string) => {
  const response = await fetch(url, buildRequestInit());

  if (!response.ok) {
    if (response.status === 401) {
      throw new Error("Please sign in to view this location dashboard");
    }

    if (response.status === 403) {
      throw new Error("You do not have access to this location");
    }

    if (response.status === 404) {
      throw new Error("Location not found");
    }

    const body = await response.json().catch(() => null);
    throw new Error(body?.message ?? "Failed to load location dashboard data");
  }

  return response.json().catch(() => null);
};

const useDashboardResource = <T>(
  key: string | null,
  normalizer: (payload: unknown) => T
) => {
  const { data, error, isLoading, mutate } = useSWR<T>(
    key,
    async (url: string) => normalizer(await fetchJson(url))
  );

  return {
    data,
    error,
    isLoading,
    mutate,
  };
};

export function useDashboardLocation(locationId?: string | number) {
  const key = locationId ? buildApiUrl(`/locations/${locationId}`) : null;
  const state = useDashboardResource<LocationDashboardLocation>(key, normalizeDashboardLocation);

  return {
    location: state.data,
    error: state.error,
    isLoading: state.isLoading,
    mutate: state.mutate,
  };
}

export function useLocationInsights(locationId?: string | number) {
  const key = locationId ? buildApiUrl(`/locations/${locationId}/insights`) : null;
  const state = useDashboardResource<GBPInsights>(key, normalizeGBPInsights);

  return {
    insights: state.data,
    error: state.error,
    isLoading: state.isLoading,
    mutate: state.mutate,
  };
}

export function useLocationReviewStats(locationId?: string | number) {
  const key = locationId ? buildApiUrl(`/locations/${locationId}/reviews/stats`) : null;
  const state = useDashboardResource<ReviewStats>(key, normalizeReviewStats);

  return {
    stats: state.data,
    error: state.error,
    isLoading: state.isLoading,
    mutate: state.mutate,
  };
}

export function useLocationReviews(locationId?: string | number) {
  const key = locationId ? buildApiUrl(`/locations/${locationId}/reviews?per_page=3`) : null;
  const state = useDashboardResource<ReviewPreview[]>(key, normalizeReviewList);

  return {
    reviews: state.data,
    error: state.error,
    isLoading: state.isLoading,
    mutate: state.mutate,
  };
}

export function useLocationRankings(locationId?: string | number) {
  const key = locationId ? buildApiUrl(`/locations/${locationId}/rankings`) : null;
  const state = useDashboardResource<LocalRankings>(key, normalizeRankings);

  return {
    rankings: state.data,
    error: state.error,
    isLoading: state.isLoading,
    mutate: state.mutate,
  };
}

export function useLocationAlerts(locationId?: string | number) {
  const key = locationId ? buildApiUrl(`/locations/${locationId}/alerts?limit=3`) : null;
  const state = useDashboardResource<AlertSummary>(key, normalizeAlertSummary);

  return {
    alerts: state.data,
    error: state.error,
    isLoading: state.isLoading,
    mutate: state.mutate,
  };
}

export function useLocationCompetitors(locationId?: string | number) {
  const key = locationId ? buildApiUrl(`/locations/${locationId}/competitors?limit=3`) : null;
  const state = useDashboardResource<CompetitorPreview[]>(key, normalizeCompetitors);

  return {
    competitors: state.data,
    error: state.error,
    isLoading: state.isLoading,
    mutate: state.mutate,
  };
}

export async function revalidateLocationDashboard(locationId?: string | number) {
  if (!locationId) {
    return;
  }

  await globalMutate(
    (key) => typeof key === "string" && key.includes(`/locations/${locationId}`),
    undefined,
    { revalidate: true }
  );
}

export function useLocationSync(locationId?: string | number) {
  const triggerSync = useCallback(async () => {
    if (!locationId) {
      throw new Error("Location ID is required to refresh dashboard data");
    }

    const response = await fetch(
      buildApiUrl(`/locations/${locationId}/sync`),
      buildRequestInit(
        {
          method: "POST",
          body: JSON.stringify({}),
        },
        true
      )
    );

    if (!response.ok) {
      const body = await response.json().catch(() => null);
      throw new Error(body?.message ?? "Failed to refresh location data");
    }

    const payload = await response.json().catch(() => null);
    await revalidateLocationDashboard(locationId);
    return payload;
  }, [locationId]);

  return { triggerSync };
}
