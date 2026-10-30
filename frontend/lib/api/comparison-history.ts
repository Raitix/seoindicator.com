import useSWR from "swr";

const API_BASE = "/api/v1";

export interface ComparisonHistoryPoint {
  position: number | null;
  checkedAt: string;
}

export interface CompetitorHistoryPoint {
  competitorId: number;
  position: number | null;
  checkedAt: string;
}

export interface ComparisonHistoryData {
  keyword: {
    id: number;
    keyword: string;
  } | null;
  history: {
    user: ComparisonHistoryPoint[];
    competitors: CompetitorHistoryPoint[];
  };
}

export interface ComparisonHistoryParams {
  keywordId?: number | null;
  days?: number;
  competitorId?: number | null;
}

const fetcher = async (url: string): Promise<ComparisonHistoryData> => {
  const response = await fetch(url);
  if (!response.ok) {
    throw new Error(`Failed to fetch comparison history: ${response.statusText}`);
  }
  const payload = await response.json();
  return payload.data ?? payload;
};

export function useComparisonHistory(params: ComparisonHistoryParams) {
  const { keywordId, days = 30, competitorId } = params;

  const queryParams = new URLSearchParams();
  if (keywordId) {
    queryParams.set("keywordId", String(keywordId));
  }
  queryParams.set("days", String(days));
  if (competitorId) {
    queryParams.set("competitorId", String(competitorId));
  }

  const key = keywordId ? `${API_BASE}/rankings/comparison/history?${queryParams.toString()}` : null;

  const { data, error, isLoading, mutate } = useSWR<ComparisonHistoryData>(key, fetcher);

  return {
    data,
    isLoading,
    error,
    mutate,
  };
}

export type DateRangeOption = "7d" | "30d" | "90d" | "custom";

export function parseDateRangeOption(option: DateRangeOption): number {
  switch (option) {
    case "7d":
      return 7;
    case "30d":
      return 30;
    case "90d":
      return 90;
    default:
      return 30;
  }
}
