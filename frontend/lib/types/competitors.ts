export interface Competitor {
  id: number;
  domain: string;
  displayName: string;
  notes?: string | null;
  trackedKeywordsCount: number;
  createdAt?: string;
  updatedAt?: string;
}

export interface CompetitorListResponse {
  data: Competitor[];
  meta?: {
    limit?: number;
    used?: number;
  };
}

export interface CompetitorPayload {
  domain: string;
  displayName?: string;
  notes?: string | null;
}
