import useSWR, { mutate as globalMutate } from "swr";

import type {
  KeywordSuggestion,
  SuggestionListResponse,
  SuggestionListMeta,
  SuggestionQueryParams,
  GenerateSuggestionsPayload,
  GenerateSuggestionsResponse,
  TrackSuggestionResponse,
  BulkTrackResponse,
  DismissSuggestionResponse,
} from "@/lib/types/suggestions";

const API_BASE = "/api/v1";

const normalizeSuggestion = (item: any): KeywordSuggestion => ({
  id: item.id,
  keyword: item.keyword,
  source: item.source ?? 'suggest',
  searchVolume: item.searchVolume ?? item.search_volume ?? null,
  difficultyScore: item.difficultyScore ?? item.difficulty_score ?? null,
  opportunityScore: item.opportunityScore ?? item.opportunity_score ?? null,
  isTracking: item.isTracking ?? item.is_tracking ?? false,
  isDismissed: item.isDismissed ?? item.is_dismissed ?? false,
  discoveredAt: item.discoveredAt ?? item.discovered_at ?? new Date().toISOString(),
});

const normalizeSuggestionList = (payload: any): SuggestionListResponse => {
  if (Array.isArray(payload)) {
    return { data: payload.map(normalizeSuggestion) };
  }

  const rawData = payload?.data ?? [];
  return {
    data: Array.isArray(rawData) ? rawData.map(normalizeSuggestion) : [],
    meta: payload?.meta ?? undefined,
  };
};

const buildQueryString = (params: SuggestionQueryParams = {}) => {
  const query = new URLSearchParams();

  if (params.source && params.source !== 'all') {
    query.set('source', params.source);
  }
  if (params.minScore !== undefined) {
    query.set('minScore', String(params.minScore));
  }
  if (params.maxScore !== undefined) {
    query.set('maxScore', String(params.maxScore));
  }
  if (params.includeTracking) {
    query.set('includeTracking', '1');
  }
  if (params.includeDismissed) {
    query.set('includeDismissed', '1');
  }
  if (params.page) {
    query.set('page', String(params.page));
  }
  if (params.perPage) {
    query.set('perPage', String(params.perPage));
  }
  if (params.sort) {
    query.set('sort', params.sort);
  }
  if (params.order) {
    query.set('order', params.order);
  }

  const queryString = query.toString();
  return queryString ? `?${queryString}` : '';
};

const fetcher = async (url: string): Promise<SuggestionListResponse> => {
  const response = await fetch(url);
  if (!response.ok) {
    throw new Error(`Failed to fetch suggestions: ${response.statusText}`);
  }

  const payload = await response.json();
  return normalizeSuggestionList(payload);
};

export function useSuggestions(siteId: string | number, params: SuggestionQueryParams = {}) {
  const queryString = buildQueryString(params);
  const key = siteId ? `${API_BASE}/sites/${siteId}/suggestions${queryString}` : null;

  const { data, error, isLoading, mutate } = useSWR<SuggestionListResponse>(key, fetcher);

  return {
    suggestions: data?.data ?? [],
    meta: data?.meta,
    isLoading,
    error,
    mutate,
  };
}

export async function generateSuggestions(
  siteId: number,
  payload: GenerateSuggestionsPayload
): Promise<GenerateSuggestionsResponse> {
  const response = await fetch(`${API_BASE}/sites/${siteId}/suggestions/generate`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload),
  });

  if (!response.ok) {
    const errorBody = await response.json().catch(() => null);
    throw new Error(errorBody?.error?.message ?? errorBody?.message ?? 'Failed to generate suggestions');
  }

  const data = await response.json();
  await globalMutate((key) => typeof key === 'string' && key.includes('/suggestions'));
  return data?.data ?? data;
}

export async function trackSuggestion(
  siteId: number,
  suggestionId: number
): Promise<TrackSuggestionResponse> {
  const response = await fetch(
    `${API_BASE}/sites/${siteId}/suggestions/${suggestionId}/track`,
    { method: 'POST' }
  );

  if (!response.ok) {
    const errorBody = await response.json().catch(() => null);
    throw new Error(errorBody?.error?.message ?? errorBody?.message ?? 'Failed to track suggestion');
  }

  const data = await response.json();
  await globalMutate((key) => typeof key === 'string' && key.includes('/suggestions'));
  await globalMutate((key) => typeof key === 'string' && key.includes('/keywords'));
  return data?.data ?? data;
}

export async function bulkTrackSuggestions(
  siteId: number,
  suggestionIds: number[]
): Promise<BulkTrackResponse> {
  const response = await fetch(`${API_BASE}/sites/${siteId}/suggestions/bulk-track`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ suggestionIds }),
  });

  if (!response.ok) {
    const errorBody = await response.json().catch(() => null);
    throw new Error(errorBody?.error?.message ?? errorBody?.message ?? 'Failed to bulk track suggestions');
  }

  const data = await response.json();
  await globalMutate((key) => typeof key === 'string' && key.includes('/suggestions'));
  await globalMutate((key) => typeof key === 'string' && key.includes('/keywords'));
  return data?.data ?? data;
}

export async function dismissSuggestion(
  siteId: number,
  suggestionId: number
): Promise<DismissSuggestionResponse> {
  const response = await fetch(
    `${API_BASE}/sites/${siteId}/suggestions/${suggestionId}/dismiss`,
    { method: 'POST' }
  );

  if (!response.ok) {
    const errorBody = await response.json().catch(() => null);
    throw new Error(errorBody?.error?.message ?? errorBody?.message ?? 'Failed to dismiss suggestion');
  }

  const data = await response.json();
  await globalMutate((key) => typeof key === 'string' && key.includes('/suggestions'));
  return data?.data ?? data;
}

export async function exportSuggestions(siteId: number, params: SuggestionQueryParams = {}): Promise<string> {
  const queryString = buildQueryString(params);
  const response = await fetch(`${API_BASE}/sites/${siteId}/suggestions/export${queryString}`);

  if (!response.ok) {
    throw new Error('Failed to export suggestions');
  }

  return response.text();
}

export const defaultSuggestionMeta: SuggestionListMeta = {
  total: 0,
  page: 1,
  perPage: 20,
};
