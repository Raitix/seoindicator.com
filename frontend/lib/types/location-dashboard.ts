import type { BusinessLocation } from "@/lib/types/locations";

export interface TrendPoint {
  label: string;
  value: number;
}

export interface MetricSeries {
  total: number;
  trend: TrendPoint[];
  breakdown: Record<string, number>;
}

export interface GBPInsights {
  views: MetricSeries;
  searches: MetricSeries;
  actions: MetricSeries;
  period: string;
}

export interface ReviewSentiment {
  positive: number;
  neutral: number;
  negative: number;
}

export interface ReviewStats {
  average_rating: number;
  total_count: number;
  rating_distribution: Record<number, number>;
  sentiment: ReviewSentiment;
}

export interface ReviewPreview {
  id: string;
  author: string;
  rating: number;
  text: string;
  date: string;
  sentiment: "positive" | "neutral" | "negative";
}

export interface RankingKeyword {
  keyword: string;
  position: number;
  change: number;
}

export interface DroppedKeyword {
  keyword: string;
  previous_position: number;
  current_position: number;
}

export interface LocalRankings {
  keywords_in_pack: number;
  average_position: number;
  top_keywords: RankingKeyword[];
  dropped_keywords: DroppedKeyword[];
}

export interface AlertItem {
  id: string;
  type: string;
  message: string;
  severity: "critical" | "warning" | "info";
  timestamp: string;
}

export interface AlertSummary {
  unread_count: number;
  latest: AlertItem[];
}

export interface CompetitorPreview {
  name: string;
  rating: number;
  review_count: number;
  position: number;
}

export interface LocationDashboardLocation extends BusinessLocation {
  lastSyncAt: string | null;
}

export interface DashboardLinks {
  dashboard: string;
  insights: string;
  reviews: string;
  rankings: string;
  alerts: string;
}

export interface QuickStatsSnapshot {
  views: number | null;
  searches: number | null;
  actions: number | null;
  rating: number | null;
  reviewCount: number | null;
  keywordsInPack: number | null;
  viewTrend?: number | null;
  searchTrend?: number | null;
  actionsTrend?: number | null;
}
