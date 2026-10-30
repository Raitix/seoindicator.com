/**
 * Content Decay Detection & Refresh Planner Components
 * 
 * This module exports all components related to the Content Decay feature.
 * 
 * @see SPEC-036 for architectural details
 * @see TASK-36-11, TASK-36-12 for implementation requirements
 */

// Components
export { SeverityBadge } from "./SeverityBadge";
export type { SeverityBadgeProps } from "./SeverityBadge";

export { DecayReasonBadges } from "./DecayReasonBadges";
export type { DecayReasonBadgesProps } from "./DecayReasonBadges";

export { DecayTrendMiniChart } from "./DecayTrendMiniChart";
export type { DecayTrendMiniChartProps } from "./DecayTrendMiniChart";

export { DecaySummaryCards } from "./DecaySummaryCards";
export type { DecaySummaryCardsProps } from "./DecaySummaryCards";

export { DecayQueueTable } from "./DecayQueueTable";
export type { DecayQueueTableProps } from "./DecayQueueTable";

export { CandidateDetailDrawer } from "./CandidateDetailDrawer";
export type { CandidateDetailDrawerProps } from "./CandidateDetailDrawer";

export { RefreshTaskList } from "./RefreshTaskList";
export type { RefreshTaskListProps } from "./RefreshTaskList";

export { RecoveryComparisonCard } from "./RecoveryComparisonCard";
export type { RecoveryComparisonCardProps } from "./RecoveryComparisonCard";

export { StatusWorkflowButtons } from "./StatusWorkflowButtons";
export type { StatusWorkflowButtonsProps } from "./StatusWorkflowButtons";

export { AiRefreshBriefPanel } from "./AiRefreshBriefPanel";
export type { AiRefreshBriefPanelProps } from "./AiRefreshBriefPanel";

// Re-export types from lib/types for convenience
export type {
  DecaySummaryData,
  DecayCandidate,
  DecayReason,
  DecayTrendData,
  ContentDecaySeverity,
  ContentDecayCandidateStatus,
  ContentDecayPageType,
  ContentDecayReasonCode,
  DecayQueueFilterState,
  ContentRefreshTask,
  RecoveryComparison,
  AiRefreshBrief,
  AiRefreshSuggestion,
} from "@/lib/types/content-decay";

export {
  SEVERITY_CONFIG,
  DECAY_REASON_CONFIG,
  PAGE_TYPE_CONFIG,
  VALID_STATUS_TRANSITIONS,
} from "@/lib/types/content-decay";
