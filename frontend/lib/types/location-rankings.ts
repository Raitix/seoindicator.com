export interface PackCompetitor {
  name: string;
  position: number;
  rating: number | null;
  reviewCount: number | null;
}

export interface LocationRankingRow {
  keywordId: number;
  keyword: string;
  localPackPosition: number | null;
  localOrganicPosition: number | null;
  lastChecked: string | null;
  competitorsInPack: PackCompetitor[];
}

export interface RankingHistorySnapshot {
  date: string;
  localPackPosition: number | null;
  localOrganicPosition: number | null;
  competitorsInPack: PackCompetitor[];
}

export interface RankingHistorySeries {
  keywordId: number;
  keyword: string | null;
  snapshots: RankingHistorySnapshot[];
}

export interface RankingQuota {
  monthlyLimit: number;
  usedThisMonth: number;
  remaining: number;
  resetDate: string | null;
}

export interface TrackedCompetitor {
  id: number;
  name: string;
  googlePlaceId: string | null;
  address: string | null;
  rating: number | null;
  reviewCount: number | null;
}

export type RankingsSortKey =
  | "keyword"
  | "localPackPosition"
  | "localOrganicPosition"
  | "packChange"
  | "organicChange"
  | "lastChecked";

export type RankingsSortDirection = "asc" | "desc";

export interface RankingsTableState {
  query: string;
  sortKey: RankingsSortKey;
  sortDirection: RankingsSortDirection;
}
