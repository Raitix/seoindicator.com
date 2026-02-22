export type KeywordStatus = "active" | "paused" | "deleted";

export interface Keyword {
  id: number;
  keyword: string;
  status: KeywordStatus;
  currentPosition: number | null;
  previousPosition: number | null;
  positionChange: number | null;
  lastCheckedAt: string | null;
  targetUrl?: string | null;
  searchEngine?: string | null;
  location?: string | null;
  device?: string | null;
}

export interface KeywordListMeta {
  total: number;
  page: number;
  perPage: number;
}

export interface KeywordListResponse {
  data: Keyword[];
  meta?: KeywordListMeta;
}

export interface KeywordCreatePayload {
  keyword: string;
  targetUrl?: string;
  searchEngine?: string;
  location?: string;
  device?: string;
}

export interface KeywordUpdatePayload {
  targetUrl?: string;
  status?: KeywordStatus;
}
