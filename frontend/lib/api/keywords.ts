import useSWR, { mutate as globalMutate } from "swr";

import type {
  Keyword,
  KeywordListResponse,
  KeywordListMeta,
  KeywordStatus,
  KeywordCreatePayload,
  KeywordUpdatePayload,
} from "@/lib/types/keywords";

const API_BASE = "/api/v1";

export type KeywordSortBy = "keyword" | "position" | "lastChecked" | "change";
export type KeywordSortOrder = "asc" | "desc";

export interface KeywordQueryParams {
  status?: "active" | "paused" | "all";
  page?: number;
  perPage?: number;
  sortBy?: KeywordSortBy;
  sortOrder?: KeywordSortOrder;
  serpFeature?: string;
  present?: boolean;
}

const normalizeKeyword = (item: any): Keyword => ({
  id: item.id,
  keyword: item.keyword,
  status: item.status ?? "active",
  currentPosition: item.currentPosition ?? item.current_position ?? null,
  previousPosition: item.previousPosition ?? item.previous_position ?? null,
  positionChange: item.positionChange ?? item.position_change ?? null,
  lastCheckedAt: item.lastCheckedAt ?? item.last_checked_at ?? null,
  targetUrl: item.targetUrl ?? item.target_url ?? null,
  searchEngine: item.searchEngine ?? item.search_engine ?? null,
  location: item.location ?? null,
  device: item.device ?? null,
});

const normalizeKeywordList = (payload: any): KeywordListResponse => {
  if (Array.isArray(payload)) {
    return { data: payload.map(normalizeKeyword) };
  }

  const rawData = payload?.data ?? [];
  return {
    data: Array.isArray(rawData) ? rawData.map(normalizeKeyword) : [],
    meta: payload?.meta ?? payload?.pagination ?? undefined,
  };
};

const buildQueryString = (params: KeywordQueryParams = {}) => {
  const query = new URLSearchParams();

  if (params.status && params.status !== "all") {
    query.set("status", params.status);
  }
  if (params.page) {
    query.set("page", String(params.page));
  }
  if (params.perPage) {
    query.set("perPage", String(params.perPage));
  }
  if (params.sortBy && params.sortBy !== "change") {
    query.set("sortBy", params.sortBy);
  }
  if (params.sortOrder) {
    query.set("sortOrder", params.sortOrder);
  }

  const queryString = query.toString();
  return queryString ? `?${queryString}` : "";
};

const fetcher = async (url: string): Promise<KeywordListResponse> => {
  const response = await fetch(url);
  if (!response.ok) {
    throw new Error(`Failed to fetch keywords: ${response.statusText}`);
  }

  const payload = await response.json();
  return normalizeKeywordList(payload);
};

export function useKeywords(siteId: string | number, params: KeywordQueryParams = {}) {
  const queryString = buildQueryString(params);
  const key = siteId ? `${API_BASE}/sites/${siteId}/keywords${queryString}` : null;

  const { data, error, isLoading, mutate } = useSWR<KeywordListResponse>(key, fetcher);

  return {
    keywords: data?.data ?? [],
    meta: data?.meta,
    isLoading,
    error,
    mutate,
  };
}

export function useKeyword(id?: string | number) {
  const key = id ? `${API_BASE}/keywords/${id}` : null;

  const { data, error, isLoading, mutate } = useSWR<Keyword>(key, async (url: string) => {
    const response = await fetch(url);
    if (!response.ok) {
      throw new Error(`Failed to fetch keyword: ${response.statusText}`);
    }
    const payload = await response.json();
    return normalizeKeyword(payload?.data ?? payload);
  });

  return {
    keyword: data,
    error,
    isLoading,
    mutate,
  };
}

export async function createKeyword(siteId: number, payload: KeywordCreatePayload) {
  const response = await fetch(`${API_BASE}/sites/${siteId}/keywords`, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(payload),
  });

  if (!response.ok) {
    const errorBody = await response.json().catch(() => null);
    throw new Error(errorBody?.error?.message ?? errorBody?.message ?? "Failed to create keyword");
  }

  const data = await response.json();
  await globalMutate((key) => typeof key === "string" && key.includes("/keywords"));
  return normalizeKeyword(data?.data ?? data);
}

export async function updateKeyword(id: number, payload: KeywordUpdatePayload) {
  const response = await fetch(`${API_BASE}/keywords/${id}`, {
    method: "PUT",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(payload),
  });

  if (!response.ok) {
    const errorBody = await response.json().catch(() => null);
    throw new Error(errorBody?.error?.message ?? errorBody?.message ?? "Failed to update keyword");
  }

  const data = await response.json();
  await globalMutate((key) => typeof key === "string" && key.includes("/keywords"));
  return normalizeKeyword(data?.data ?? data);
}

export async function updateKeywordStatus(id: number, status: KeywordStatus) {
  const response = await fetch(`${API_BASE}/keywords/${id}`, {
    method: "PUT",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ status }),
  });

  if (!response.ok) {
    const errorBody = await response.json().catch(() => null);
    throw new Error(errorBody?.message ?? "Failed to update keyword");
  }

  return response.json();
}

export async function deleteKeyword(id: number) {
  const response = await fetch(`${API_BASE}/keywords/${id}`, {
    method: "DELETE",
  });

  if (!response.ok) {
    const errorBody = await response.json().catch(() => null);
    throw new Error(errorBody?.message ?? "Failed to delete keyword");
  }

  await globalMutate((key) => typeof key === "string" && key.includes("/keywords"));
  return response;
}

export async function bulkUpdateKeywordStatus(ids: number[], status: KeywordStatus) {
  await Promise.all(ids.map((id) => updateKeywordStatus(id, status)));
  await globalMutate((key) => typeof key === "string" && key.includes("/keywords"));
}

export async function bulkDeleteKeywords(ids: number[]) {
  await Promise.all(ids.map((id) => deleteKeyword(id)));
  await globalMutate((key) => typeof key === "string" && key.includes("/keywords"));
}

export const defaultKeywordMeta: KeywordListMeta = {
  total: 0,
  page: 1,
  perPage: 20,
};
