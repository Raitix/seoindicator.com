/**
 * Dashboard Components Index - TASK-25-19
 * 
 * Export all dashboard components for easy importing.
 */

export { StatsCard, StatsCardSkeleton } from "./StatsCard";
export type { StatsCardProps } from "./StatsCard";

export { CostSummaryCard, CostSummaryCardSkeleton } from "./CostSummaryCard";
export type { CostCardProps } from "./CostSummaryCard";

export { RecentAnalysesTable, RecentAnalysesTableSkeleton } from "./RecentAnalysesTable";
export type { RecentAnalysesProps } from "./RecentAnalysesTable";

export { QuickInsightsCard, QuickInsightsCardSkeleton } from "./QuickInsightsCard";
export type { QuickInsightsProps } from "./QuickInsightsCard";

export { QuickActionsCard } from "./QuickActionsCard";
export type { QuickActionsProps } from "./QuickActionsCard";

export { WelcomeCard } from "./WelcomeCard";
export type { WelcomeCardProps } from "./WelcomeCard";

export { DashboardSkeleton, AuthLoadingState } from "./DashboardSkeleton";

// AuditWidget - TASK-14-17
export { AuditWidget } from "./AuditWidget";
export type { AuditWidgetProps, AuditIssue, LastAudit, RunningAudit } from "./AuditWidget";

// Re-export types
export type { AnalysisStatus, RecentAnalysis, DashboardData, CostSummary, QuickInsights, DashboardStats } from "@/lib/types/dashboard";

// Badge Management - TASK-33-24
export { BadgeManagementRow } from "./BadgeManagementRow";
export type { BadgeManagementRowProps } from "./BadgeManagementRow";

export { EmbedCodeModal } from "./EmbedCodeModal";

// Badge Analytics Dashboard - TASK-33-29
export { BadgeAnalyticsDashboard } from "./BadgeAnalyticsDashboard";
export type {
  BadgeAnalyticsDashboardProps,
  BadgeStats,
  TimeSeriesPoint,
  TopReferrer,
  TopAudit,
  DateRange,
  PresetRange,
} from "./BadgeAnalyticsDashboard";

export { BadgeStatsCard, BadgeStatsCardSkeleton, formatNumber } from "./BadgeStatsCard";
export type { BadgeStatsCardProps } from "./BadgeStatsCard";

// Content Decay Widget - TASK-36-14
export { ContentDecayWidget } from "./ContentDecayWidget";
export type { ContentDecayWidgetProps } from "./ContentDecayWidget";
