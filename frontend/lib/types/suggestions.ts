// Types for Keyword Suggestions feature (SPEC-005)

export type SuggestionSource = 'suggest' | 'question' | 'related' | 'competitor';

export interface KeywordSuggestion {
  id: number;
  keyword: string;
  source: SuggestionSource;
  searchVolume: number | null;
  difficultyScore: number | null;
  opportunityScore: number | null;
  isTracking: boolean;
  isDismissed: boolean;
  discoveredAt: string;
}

export interface SuggestionListMeta {
  total: number;
  page: number;
  perPage: number;
}

export interface SuggestionListResponse {
  data: KeywordSuggestion[];
  meta?: SuggestionListMeta;
}

export interface SuggestionQueryParams {
  source?: SuggestionSource | 'all';
  minScore?: number;
  maxScore?: number;
  includeTracking?: boolean;
  includeDismissed?: boolean;
  page?: number;
  perPage?: number;
  sort?: 'opportunity_score' | 'keyword' | 'source';
  order?: 'asc' | 'desc';
}

export interface GenerateSuggestionsPayload {
  seedKeyword: string;
  sources: SuggestionSource[];
  maxResults?: number;
}

export interface GenerateSuggestionsResponse {
  jobId: string;
  status: 'queued' | 'processing' | 'completed' | 'failed';
  count?: number;
}

export interface TrackSuggestionResponse {
  keyword: {
    id: number;
    keyword: string;
    status: string;
  };
  suggestion: {
    id: number;
    isTracking: boolean;
  };
}

export interface BulkTrackResponse {
  added: Array<{ keyword: string; keywordId: number }>;
  skipped: Array<{ keyword: string; reason: string }>;
}

export interface DismissSuggestionResponse {
  id: number;
  isDismissed: boolean;
}
