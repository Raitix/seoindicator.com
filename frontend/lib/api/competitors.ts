import useSWR, { mutate as globalMutate } from "swr";

import type { Competitor, CompetitorListResponse, CompetitorPayload } from "@/lib/types/competitors";
import { sanitizeDomain } from "@/lib/validators/domain";

const API_BASE = "/api/v1";

const fetcher = async (url: string): Promise<CompetitorListResponse> => {
  const response = await fetch(url);
  if (!response.ok) {
    throw new Error(`Failed to fetch competitors: ${response.statusText}`);
  }

  const payload = await response.json();
  return normalizeCompetitorList(payload);
};

const normalizeCompetitor = (item: any): Competitor => ({
  id: item.id,
  domain: item.domain,
  displayName: item.display_name ?? item.displayName ?? "",
  notes: item.notes ?? "",
  trackedKeywordsCount: item.tracked_keywords_count ?? item.trackedKeywordsCount ?? 0,
  createdAt: item.created_at ?? item.createdAt,
  updatedAt: item.updated_at ?? item.updatedAt,
});

const normalizeCompetitorList = (payload: any): CompetitorListResponse => {
  if (Array.isArray(payload)) {
    return { data: payload.map(normalizeCompetitor) };
  }

  const rawData = payload?.data ?? [];
  return {
    data: Array.isArray(rawData) ? rawData.map(normalizeCompetitor) : [],
    meta: payload?.meta ?? payload?.pagination ?? undefined,
  };
};

export function useCompetitors() {
  const { data, error, isLoading, mutate } = useSWR<CompetitorListResponse>(
    `${API_BASE}/competitors`,
    fetcher
  );

  return {
    competitors: data?.data ?? [],
    meta: data?.meta,
    isLoading,
    error,
    mutate,
  };
}

export function useCreateCompetitor() {
  return async (payload: CompetitorPayload) => {
    const response = await fetch(`${API_BASE}/competitors`, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        domain: sanitizeDomain(payload.domain),
        display_name: payload.displayName ?? "",
        notes: payload.notes ?? "",
      }),
    });

    if (!response.ok) {
      const errorBody = await response.json().catch(() => null);
      throw new Error(errorBody?.message ?? "Failed to create competitor");
    }

    await globalMutate(`${API_BASE}/competitors`);
    return response.json();
  };
}

export function useUpdateCompetitor() {
  return async (id: number, payload: CompetitorPayload) => {
    const response = await fetch(`${API_BASE}/competitors/${id}`, {
      method: "PUT",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        display_name: payload.displayName ?? "",
        notes: payload.notes ?? "",
      }),
    });

    if (!response.ok) {
      const errorBody = await response.json().catch(() => null);
      throw new Error(errorBody?.message ?? "Failed to update competitor");
    }

    await globalMutate(`${API_BASE}/competitors`);
    return response.json();
  };
}

export function useDeleteCompetitor() {
  return async (id: number) => {
    const response = await fetch(`${API_BASE}/competitors/${id}`, {
      method: "DELETE",
    });

    if (!response.ok) {
      const errorBody = await response.json().catch(() => null);
      throw new Error(errorBody?.message ?? "Failed to delete competitor");
    }

    await globalMutate(`${API_BASE}/competitors`);
    return response.json();
  };
}
