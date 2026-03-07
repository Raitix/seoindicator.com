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
