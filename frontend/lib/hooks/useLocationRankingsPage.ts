"use client";

import { useCallback } from "react";
import useSWR, { mutate as globalMutate } from "swr";

import { buildApiUrl } from "@/lib/api/base-url";
import { getStoredAuthToken } from "@/lib/api/auth";
import type {
  LocationRankingRow,
  PackCompetitor,
  RankingHistorySeries,
  RankingHistorySnapshot,
  RankingQuota,
  TrackedCompetitor,
} from "@/lib/types/location-rankings";

const asRecord = (value: unknown): Record<string, unknown> => {
  if (value && typeof value === "object" && !Array.isArray(value)) {
    return value as Record<string, unknown>;
  }

  return {};
};

const asArray = <T = unknown>(value: unknown): T[] => (Array.isArray(value) ? (value as T[]) : []);

const asNumber = (value: unknown, fallback = 0): number => {
  if (typeof value === "number") {
    return Number.isFinite(value) ? value : fallback;
  }

  if (typeof value === "string") {
    const parsed = Number(value);
    return Number.isFinite(parsed) ? parsed : fallback;
  }

  return fallback;
};

const asNullableNumber = (value: unknown): number | null => {
  if (value === null || value === undefined || value === "") {
    return null;
  }

  const parsed = asNumber(value, Number.NaN);
  return Number.isFinite(parsed) ? parsed : null;
};

const asString = (value: unknown, fallback = ""): string => {
  if (typeof value !== "string") {
    return fallback;
  }

  return value.trim();
};

const asNullableString = (value: unknown): string | null => {
  const next = asString(value, "");
  return next.length ? next : null;
};

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
      throw new Error("Please sign in to view local rankings");
    }

    if (response.status === 403) {
      throw new Error("You do not have access to this location's rankings");
    }

    if (response.status === 404) {
      throw new Error("Location not found");
    }

    const body = await response.json().catch(() => null);
    throw new Error(body?.error ?? body?.message ?? "Failed to load local rankings data");
  }

  return response.json().catch(() => null);
};

export const normalizePackCompetitor = (payload: unknown): PackCompetitor => {
  const record = asRecord(payload);

  return {
    name: asNullableString(record.name ?? record.business_name ?? record.title) ?? "Competitor",
    position: asNumber(record.position),
    rating: asNullableNumber(record.rating),
    reviewCount: asNullableNumber(record.review_count ?? record.reviewCount),
  };
};

export const normalizeLocationRankingRow = (payload: unknown): LocationRankingRow => {
  const record = asRecord(payload);

  return {
    keywordId: asNumber(record.keyword_id ?? record.keywordId),
    keyword: asNullableString(record.keyword ?? record.term) ?? "Untitled keyword",
    localPackPosition: asNullableNumber(record.local_pack_position ?? record.localPackPosition),
    localOrganicPosition: asNullableNumber(record.local_organic_position ?? record.localOrganicPosition),
    lastChecked: asNullableString(record.last_checked ?? record.lastChecked),
    competitorsInPack: asArray(record.competitors_in_pack ?? record.competitorsInPack).map(normalizePackCompetitor),
  };
};

export const normalizeLocationRankings = (payload: unknown): LocationRankingRow[] => {
  const root = asRecord(payload);
  const data = asRecord(root.data ?? payload);
  return asArray(data.rankings ?? data.items ?? root.rankings ?? payload).map(normalizeLocationRankingRow);
};

const normalizeRankingHistorySnapshot = (payload: unknown): RankingHistorySnapshot => {
  const record = asRecord(payload);

  return {
    date: asNullableString(record.date ?? record.checked_at ?? record.checkedAt) ?? new Date().toISOString(),
    localPackPosition: asNullableNumber(record.local_pack_position ?? record.localPackPosition),
    localOrganicPosition: asNullableNumber(record.local_organic_position ?? record.localOrganicPosition),
    competitorsInPack: asArray(record.competitors_in_pack ?? record.competitorsInPack).map(normalizePackCompetitor),
  };
};

export const normalizeRankingHistorySeries = (payload: unknown): RankingHistorySeries => {
  const record = asRecord(payload);
  const snapshots = asArray(record.snapshots ?? record.history)
    .map(normalizeRankingHistorySnapshot)
    .sort((left, right) => new Date(left.date).getTime() - new Date(right.date).getTime());

  return {
    keywordId: asNumber(record.keyword_id ?? record.keywordId),
    keyword: asNullableString(record.keyword ?? record.term),
    snapshots,
  };
};

export const normalizeLocationRankingHistory = (payload: unknown): RankingHistorySeries[] => {
  const root = asRecord(payload);
  const data = asRecord(root.data ?? payload);
  return asArray(data.history ?? root.history ?? payload).map(normalizeRankingHistorySeries);
};

export const normalizeRankingQuota = (payload: unknown): RankingQuota => {
  const root = asRecord(payload);
  const data = asRecord(root.data ?? payload);

  return {
    monthlyLimit: asNumber(data.monthly_limit ?? data.monthlyLimit),
    usedThisMonth: asNumber(data.used_this_month ?? data.usedThisMonth),
    remaining: asNumber(data.remaining),
    resetDate: asNullableString(data.reset_date ?? data.resetDate),
  };
};

export const normalizeTrackedCompetitor = (payload: unknown): TrackedCompetitor => {
  const record = asRecord(payload);

  return {
    id: asNumber(record.id),
    name: asNullableString(record.name ?? record.competitor_name) ?? "Competitor",
    googlePlaceId: asNullableString(record.google_place_id ?? record.googlePlaceId),
    address: asNullableString(record.address ?? record.competitor_address),
    rating: asNullableNumber(record.rating),
    reviewCount: asNullableNumber(record.review_count ?? record.reviewCount),
  };
};

export const normalizeTrackedCompetitors = (payload: unknown): TrackedCompetitor[] => {
  const root = asRecord(payload);
  const data = asRecord(root.data ?? payload);
  return asArray(data.competitors ?? root.competitors ?? payload).map(normalizeTrackedCompetitor);
};

const getRankingsKey = (locationId: string | number) => buildApiUrl(`/locations/${locationId}/rankings`);
const getHistoryKey = (locationId: string | number) => buildApiUrl(`/locations/${locationId}/rankings/history`);
const getQuotaKey = (locationId: string | number) => buildApiUrl(`/locations/${locationId}/rankings/quota`);
const getCompetitorsKey = (locationId: string | number) => buildApiUrl(`/locations/${locationId}/competitors`);

export async function revalidateLocationRankingsData(locationId?: string | number) {
  if (!locationId) {
    return;
  }

  const prefixes = [
    buildApiUrl(`/locations/${locationId}/rankings`),
    buildApiUrl(`/locations/${locationId}/competitors`),
  ];

  await globalMutate(
    (key) => typeof key === "string" && prefixes.some((prefix) => key.startsWith(prefix)),
    undefined,
    { revalidate: true }
  );

  await globalMutate(getQuotaKey(locationId), undefined, { revalidate: true });
  await globalMutate(getHistoryKey(locationId), undefined, { revalidate: true });
}

export function useLocationRankingsData(locationId?: string | number) {
  const key = locationId ? getRankingsKey(locationId) : null;
  const { data, error, isLoading, mutate } = useSWR<LocationRankingRow[]>(
    key,
    async (url: string) => normalizeLocationRankings(await fetchJson(url))
  );

  return {
    rankings: data ?? [],
    error,
    isLoading,
    mutate,
  };
}

export function useLocationRankingHistory(locationId?: string | number) {
  const key = locationId ? getHistoryKey(locationId) : null;
  const { data, error, isLoading, mutate } = useSWR<RankingHistorySeries[]>(
    key,
    async (url: string) => normalizeLocationRankingHistory(await fetchJson(url))
  );

  return {
    history: data ?? [],
    error,
    isLoading,
    mutate,
  };
}

export function useLocationRankingQuota(locationId?: string | number) {
  const key = locationId ? getQuotaKey(locationId) : null;
  const { data, error, isLoading, mutate } = useSWR<RankingQuota>(
    key,
    async (url: string) => normalizeRankingQuota(await fetchJson(url))
  );

  return {
    quota: data ?? null,
    error,
    isLoading,
    mutate,
  };
}

export function useLocationTrackedCompetitors(locationId?: string | number) {
  const key = locationId ? getCompetitorsKey(locationId) : null;
  const { data, error, isLoading, mutate } = useSWR<TrackedCompetitor[]>(
    key,
    async (url: string) => normalizeTrackedCompetitors(await fetchJson(url))
  );

  return {
    competitors: data ?? [],
    error,
    isLoading,
    mutate,
  };
}

export function useCheckAllLocationRankings(locationId?: string | number) {
  const checkAll = useCallback(async () => {
    if (!locationId) {
      throw new Error("Location ID is required to check rankings");
    }

    const response = await fetch(
      buildApiUrl(`/locations/${locationId}/rankings/check`),
      buildRequestInit({ method: "POST" })
    );

    const payload = await response.json().catch(() => null);

    if (!response.ok) {
      const message = payload?.error ?? payload?.message ?? "Failed to start ranking check";
      const error = new Error(message) as Error & { status?: number; payload?: unknown };
      error.status = response.status;
      error.payload = payload;
      throw error;
    }

    await revalidateLocationRankingsData(locationId);
    return payload;
  }, [locationId]);

  return { checkAll };
}

export function useCheckSingleLocationRanking(locationId?: string | number) {
  const checkKeyword = useCallback(
    async (keywordId: string | number) => {
      if (!locationId) {
        throw new Error("Location ID is required to check rankings");
      }

      const response = await fetch(
        buildApiUrl(`/locations/${locationId}/rankings/check/${keywordId}`),
        buildRequestInit({ method: "POST" })
      );

      const payload = await response.json().catch(() => null);

      if (!response.ok) {
        const message = payload?.error ?? payload?.message ?? "Failed to check keyword ranking";
        const error = new Error(message) as Error & { status?: number; payload?: unknown };
        error.status = response.status;
        error.payload = payload;
        throw error;
      }

      await revalidateLocationRankingsData(locationId);
      return payload;
    },
    [locationId]
  );

  return { checkKeyword };
}

export function useCreateTrackedCompetitor(locationId?: string | number) {
  const createCompetitor = useCallback(
    async (input: { name: string; googlePlaceId: string; notes?: string }) => {
      if (!locationId) {
        throw new Error("Location ID is required to add competitors");
      }

      const response = await fetch(
        buildApiUrl(`/locations/${locationId}/competitors`),
        buildRequestInit(
          {
            method: "POST",
            body: JSON.stringify({
              name: input.name,
              google_place_id: input.googlePlaceId,
              notes: input.notes ?? "",
            }),
          },
          true
        )
      );

      const payload = await response.json().catch(() => null);

      if (!response.ok) {
        throw new Error(payload?.error ?? payload?.message ?? "Failed to add competitor");
      }

      await revalidateLocationRankingsData(locationId);
      return payload;
    },
    [locationId]
  );

  return { createCompetitor };
}

export function useDeleteTrackedCompetitor(locationId?: string | number) {
  const deleteCompetitor = useCallback(
    async (competitorId: string | number) => {
      if (!locationId) {
        throw new Error("Location ID is required to remove competitors");
      }

      const response = await fetch(
        buildApiUrl(`/locations/${locationId}/competitors/${competitorId}`),
        buildRequestInit({ method: "DELETE" })
      );

      if (!response.ok && response.status !== 204) {
        const payload = await response.json().catch(() => null);
        throw new Error(payload?.error ?? payload?.message ?? "Failed to remove competitor");
      }

      await revalidateLocationRankingsData(locationId);
      return true;
    },
    [locationId]
  );

  return { deleteCompetitor };
}
