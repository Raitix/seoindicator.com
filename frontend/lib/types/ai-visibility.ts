/**
 * TASK-34-10: AI Visibility Dashboard Types
 * 
 * Type definitions for the AI Visibility dashboard components and data.
 * 
 * @see PROJECT-SEOINDICATOR/TASKS/TASK-34-10.md
 */

// ============================================
// Metric Types
// ============================================

export type TrendDirection = 'up' | 'down' | 'stable';

export type SentimentType = 'positive' | 'negative' | 'neutral' | 'mixed';

export type MetricFormat = 'percentage' | 'decimal' | 'number';

export interface MetricValue {
  value: number;
  previousValue?: number;
  change?: number;
  trend: TrendDirection;
  label?: string;
}

export interface MentionRateMetric extends MetricValue {
  // Mention rate specific fields
}

export interface ShareOfVoiceMetric extends MetricValue {
  // Share of voice specific fields
}

export interface SentimentScoreMetric extends MetricValue {
  label: 'Positive' | 'Negative' | 'Neutral' | 'Mixed';
}

// ============================================
// Chart Data Types
// ============================================

export interface MentionRateDataPoint {
  date: string;
  overall: number;
  chatgpt: number;
  perplexity: number;
}

export interface CompetitorComparisonItem {
  name: string;
  value: number;
  isBrand: boolean;
}

export interface QueryCategoryItem {
  name: string;
  value: number;
}

// ============================================
// Mention Types
// ============================================

export type AIPlatform = 'chatgpt' | 'perplexity' | 'claude';

export interface RecentMention {
  id: string;
  brandContext: string;
  platform: AIPlatform;
  sentiment: SentimentType;
  queriedAt: string;
  queryText?: string;
}

// ============================================
// Project Types
// ============================================

export interface AIVisibilityProject {
  id: number;
  brandName: string;
  keywords: string[];
  competitors: string[];
  isActive: boolean;
  trackingFrequency: 'daily' | 'weekly' | 'monthly';
  lastQueriedAt: string | null;
}

// ============================================
// Quick Actions Types
// ============================================

export interface QuickActions {
  canRunQuery: boolean;
  nextQueryAt: string | null;
  hasExportData: boolean;
}

// ============================================
// Dashboard Data Types
// ============================================

export interface AIVisibilityMetrics {
  mentionRate: MentionRateMetric;
  shareOfVoice: ShareOfVoiceMetric;
  sentimentScore: SentimentScoreMetric;
}

export interface AIVisibilityDashboardData {
  project: AIVisibilityProject;
  metrics: AIVisibilityMetrics;
  mentionRateOverTime: MentionRateDataPoint[];
  competitorComparison: CompetitorComparisonItem[];
  recentMentions: RecentMention[];
  queryCategories: QueryCategoryItem[];
  quickActions: QuickActions;
}

// ============================================
// Component Props Types
// ============================================

export interface MetricCardProps {
  title: string;
  value: number | string;
  previousValue?: number;
  change?: number;
  trend?: TrendDirection;
  format?: MetricFormat;
  sentiment?: SentimentType;
  label?: string;
  tooltip?: string;
  className?: string;
}

export interface RecentMentionsListProps {
  mentions: RecentMention[];
  maxVisible?: number;
  viewAllHref?: string;
  onMentionClick?: (mention: RecentMention) => void;
  className?: string;
}

export interface CompetitorComparisonChartProps {
  data: CompetitorComparisonItem[];
  onBarClick?: (item: CompetitorComparisonItem) => void;
  className?: string;
}

export interface MentionRateChartProps {
  data: MentionRateDataPoint[];
  className?: string;
}

export interface QueryCategoriesChartProps {
  data: QueryCategoryItem[];
  className?: string;
}

// ============================================
// Hook Return Types
// ============================================

export interface UseAIVisibilityDashboardReturn {
  data: AIVisibilityDashboardData | null;
  isLoading: boolean;
  error: Error | null;
  refetch: () => Promise<void>;
  isStale: boolean;
}
