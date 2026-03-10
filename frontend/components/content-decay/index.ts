/**
 * Content Decay Detection & Refresh Planner Components
 * 
 * This module exports all components related to the Content Decay feature.
 * 
 * @see SPEC-036 for architectural details
 * @see TASK-36-11 for implementation requirements
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
} from "@/lib/types/content-decay";

export {
  SEVERITY_CONFIG,
  DECAY_REASON_CONFIG,
  PAGE_TYPE_CONFIG,
} from "@/lib/types/content-decay";
