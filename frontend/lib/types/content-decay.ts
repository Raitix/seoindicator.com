/**
 * TypeScript interfaces for Content Decay Detection & Refresh Planner
 * Reference: SPEC-036, TASK-36-11, TASK-36-12
 */

// ============== Enums ==============

export type ContentDecaySeverity = "critical" | "high" | "medium" | "low";

export type ContentDecayCandidateStatus =
  | "backlog"
  | "planned"
  | "in_progress"
  | "refreshed"
  | "recovered"
  | "ignored";

export type ContentDecayReasonCode =
  | "clicks_down"
  | "impressions_down"
  | "ctr_down"
  | "avg_position_down"
  | "tracked_rankings_down"
  | "audit_issues_present"
  | "stale_content"
  | "annotation_recently_changed"
  | "ai_visibility_down"
  | "competitor_gain_suspected"
  | "internal_link_weakness";

export type ContentDecayPageType =
  | "home"
  | "blog"
  | "landing"
  | "product"
  | "tool"
  | "compare"
  | "docs"
  | "other";

export type ContentRefreshTaskStatus = "todo" | "in_progress" | "done" | "skipped";

export type ContentRefreshTaskSource = "system" | "manual" | "ai";

// ============== Reason & Evidence ==============

export interface DecayReason {
  type: ContentDecayReasonCode;
  evidence: string;
  isPrimary?: boolean;
}

export interface DecayReasonConfig {
  label: string;
  colorClass: string;
  iconLabel: string;
  description: string;
}

export const DECAY_REASON_CONFIG: Record<ContentDecayReasonCode, DecayReasonConfig> = {
  clicks_down: {
    label: "Clicks Down",
    colorClass: "bg-red-100 text-red-800 border-red-200",
    iconLabel: "Mouse pointer click",
    description: "Significant decline in organic clicks",
  },
  impressions_down: {
    label: "Impressions Down",
    colorClass: "bg-orange-100 text-orange-800 border-orange-200",
    iconLabel: "Eye",
    description: "Reduced visibility in search results",
  },
  ctr_down: {
    label: "CTR Down",
    colorClass: "bg-yellow-100 text-yellow-800 border-yellow-200",
    iconLabel: "Trending down",
    description: "Click-through rate has decreased",
  },
  avg_position_down: {
    label: "Position Down",
    colorClass: "bg-blue-100 text-blue-800 border-blue-200",
    iconLabel: "Arrow down",
    description: "Average position has worsened",
  },
  tracked_rankings_down: {
    label: "Rankings Down",
    colorClass: "bg-indigo-100 text-indigo-800 border-indigo-200",
    iconLabel: "Bar chart down",
    description: "Tracked keyword rankings have declined",
  },
  audit_issues_present: {
    label: "Audit Issues",
    colorClass: "bg-purple-100 text-purple-800 border-purple-200",
    iconLabel: "Alert circle",
    description: "Technical issues detected on this page",
  },
  stale_content: {
    label: "Stale Content",
    colorClass: "bg-gray-100 text-gray-800 border-gray-200",
    iconLabel: "Clock",
    description: "Content has not been updated recently",
  },
  annotation_recently_changed: {
    label: "Recent Change",
    colorClass: "bg-teal-100 text-teal-800 border-teal-200",
    iconLabel: "Edit",
    description: "Content was recently modified",
  },
  ai_visibility_down: {
    label: "AI Visibility Down",
    colorClass: "bg-violet-100 text-violet-800 border-violet-200",
    iconLabel: "Sparkles",
    description: "Reduced appearance in AI answers",
  },
  competitor_gain_suspected: {
    label: "Competitor Gain",
    colorClass: "bg-amber-100 text-amber-800 border-amber-200",
    iconLabel: "Users",
    description: "Competitors may have improved",
  },
  internal_link_weakness: {
    label: "Weak Internal Links",
    colorClass: "bg-slate-100 text-slate-800 border-slate-200",
    iconLabel: "Link",
    description: "Insufficient internal link support",
  },
};

// ============== Trend Data ==============

export interface DecayTrendData {
  previousWindow: {
    clicks: number[];
    impressions: number[];
    ctr?: number[];
    avgPosition?: number[];
  };
  currentWindow: {
    clicks: number[];
    impressions: number[];
    ctr?: number[];
    avgPosition?: number[];
  };
}

export interface DecaySignalSnapshot {
  previousStart: string;
  previousEnd: string;
  currentStart: string;
  currentEnd: string;
  previousClicks: number;
  currentClicks: number;
  previousImpressions: number;
  currentImpressions: number;
  previousCtr: number;
  currentCtr: number;
  previousAvgPosition: number | null;
  currentAvgPosition: number | null;
  previousRankVisibility: number | null;
  currentRankVisibility: number | null;
  previousAiVisibility: number | null;
  currentAiVisibility: number | null;
  unresolvedAuditIssues: number;
  contentAgeDays: number | null;
}

// ============== Candidate ==============

export interface DecayCandidate {
  id: number;
  projectId: number;
  pageUrl: string;
  pageTitle: string | null;
  pageType: ContentDecayPageType;
  severity: ContentDecaySeverity;
  decayScore: number;
  detectionConfidence: number;
  lostClickEstimate: number;
  recoveryUpsideEstimate: number;
  primaryReasonCode: ContentDecayReasonCode;
  reasonCodes: DecayReason[];
  status: ContentDecayCandidateStatus;
  firstDetectedAt: string;
  lastDetectedAt: string;
  plannedAt: string | null;
  startedAt: string | null;
  refreshedAt: string | null;
  recoveredAt: string | null;
  ignoredAt: string | null;
  signals: DecaySignalSnapshot | null;
  trend: DecayTrendData | null;
}

// ============== Refresh Task ==============

export interface ContentRefreshTask {
  id: number;
  candidateId: number;
  title: string;
  recommendationType: string;
  description: string | null;
  notes: string | null;
  ownerUserId: number | null;
  ownerName: string | null;
  dueDate: string | null;
  status: ContentRefreshTaskStatus;
  source: ContentRefreshTaskSource;
  createdAt: string;
  updatedAt: string;
}

export interface ContentRefreshTaskCreateInput {
  title: string;
  description?: string;
  dueDate?: string;
  ownerUserId?: number;
}

export interface ContentRefreshTaskUpdateInput {
  title?: string;
  description?: string;
  notes?: string;
  ownerUserId?: number | null;
  dueDate?: string | null;
  status?: ContentRefreshTaskStatus;
}

// ============== Recovery Comparison ==============

export interface RecoveryMetric {
  before: number;
  after: number;
  delta: number;
  deltaPercent: number | null;
}

export interface RecoveryComparison {
  clicks: RecoveryMetric;
  impressions: RecoveryMetric;
  ctr: RecoveryMetric;
  position: RecoveryMetric;
  aiVisibility?: RecoveryMetric;
}

// ============== AI Brief ==============

export interface AiRefreshBrief {
  id: string;
  candidateId: number;
  generatedAt: string;
  suggestions: AiRefreshSuggestion[];
  disclaimer: string;
}

export interface AiRefreshSuggestion {
  id: string;
  title: string;
  description: string;
  priority: "high" | "medium" | "low";
  category: string;
  details?: string[];
}

// ============== Summary ==============

export interface DecaySummaryData {
  totalCandidates: number;
  criticalCount: number;
  highCount: number;
  mediumCount: number;
  lowCount: number;
  estimatedLostClicks: number;
  recoveryUpside: number;
  recoveredCount: number;
  lastScannedAt: string | null;
}

// ============== API Responses ==============

export interface DecayCandidateListResponse {
  data: DecayCandidate[];
  meta: {
    currentPage: number;
    perPage: number;
    total: number;
    lastPage: number;
  };
  filters: {
    status: ContentDecayCandidateStatus | null;
    severity: ContentDecaySeverity | null;
    pageType: ContentDecayPageType | null;
    reasonCode: ContentDecayReasonCode | null;
    minLostClicks: number | null;
    search: string | null;
    sort: string;
    order: "asc" | "desc";
  };
}

export interface DecayCandidateDetailResponse {
  candidate: DecayCandidate;
  tasks: ContentRefreshTask[];
  comparison: RecoveryComparison | null;
  aiBrief: AiRefreshBrief | null;
}

// ============== Filter & Sort ==============

export interface DecayQueueFilterState {
  status: ContentDecayCandidateStatus | "all";
  severity: ContentDecaySeverity | "all";
  pageType: ContentDecayPageType | "all";
  reasonCode: ContentDecayReasonCode | "all";
  minLostClicks: number | null;
  search: string;
  sort: "decay_score" | "recovery_upside_estimate" | "lost_click_estimate" | "last_detected_at";
  order: "asc" | "desc";
}

export const DEFAULT_DECAY_QUEUE_FILTERS: DecayQueueFilterState = {
  status: "all",
  severity: "all",
  pageType: "all",
  reasonCode: "all",
  minLostClicks: null,
  search: "",
  sort: "decay_score",
  order: "desc",
};

// ============== Status Workflow ==============

export interface StatusTransition {
  from: ContentDecayCandidateStatus;
  to: ContentDecayCandidateStatus;
  label: string;
  icon?: string;
  requiresConfirmation?: boolean;
  confirmationMessage?: string;
}

export const VALID_STATUS_TRANSITIONS: StatusTransition[] = [
  { from: "backlog", to: "planned", label: "Plan Refresh" },
  { from: "planned", to: "in_progress", label: "Start Work" },
  { from: "in_progress", to: "refreshed", label: "Mark Refreshed" },
  { from: "refreshed", to: "recovered", label: "Confirm Recovery" },
  { from: "backlog", to: "ignored", label: "Ignore", requiresConfirmation: true, confirmationMessage: "Are you sure you want to ignore this candidate?" },
  { from: "planned", to: "ignored", label: "Ignore", requiresConfirmation: true, confirmationMessage: "Are you sure you want to ignore this candidate?" },
  { from: "ignored", to: "backlog", label: "Restore" },
];

// ============== Severity Helpers ==============

export const SEVERITY_CONFIG: Record<ContentDecaySeverity, { label: string; colorClass: string; icon: string }> = {
  critical: {
    label: "Critical",
    colorClass: "bg-red-500 text-white",
    icon: "AlertTriangle",
  },
  high: {
    label: "High",
    colorClass: "bg-orange-500 text-white",
    icon: "AlertCircle",
  },
  medium: {
    label: "Medium",
    colorClass: "bg-yellow-500 text-black",
    icon: "Info",
  },
  low: {
    label: "Low",
    colorClass: "bg-gray-300 text-black",
    icon: "Minus",
  },
};

// ============== Page Type Helpers ==============

export const PAGE_TYPE_CONFIG: Record<ContentDecayPageType, { label: string; icon: string }> = {
  home: { label: "Homepage", icon: "Home" },
  blog: { label: "Blog", icon: "FileText" },
  landing: { label: "Landing Page", icon: "Layout" },
  product: { label: "Product", icon: "Package" },
  tool: { label: "Tool", icon: "Wrench" },
  compare: { label: "Comparison", icon: "GitCompare" },
  docs: { label: "Documentation", icon: "BookOpen" },
  other: { label: "Other", icon: "File" },
};
