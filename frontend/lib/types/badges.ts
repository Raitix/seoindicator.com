/**
 * Badge Types - TASK-33-24
 *
 * Types for badge management and public audit analytics.
 *
 * @module lib/types/badges
 */

// ============================================================
// Badge Analytics
// ============================================================

/**
 * Analytics data for a badge
 */
export interface BadgeAnalytics {
  /** Number of times the badge was viewed/impressed */
  impressions: number;
  /** Number of clicks on the badge */
  clicks: number;
  /** Click-through rate (percentage) */
  ctr: number;
}

// ============================================================
// Public Audit
// ============================================================

/**
 * Public audit with badge information
 */
export interface PublicAudit {
  /** Unique audit ID */
  id: number;
  /** Hash for public URL */
  hash: string;
  /** Site URL that was audited */
  siteUrl: string;
  /** SEO score (0-100) */
  score: number;
  /** Whether the audit is publicly visible */
  isPublic: boolean;
  /** When the audit was published publicly */
  publishedAt: string | null;
  /** Analytics for the badge */
  analytics: BadgeAnalytics;
}

// ============================================================
// API Response Types
// ============================================================

/**
 * API response for public audits list
 */
export interface PublicAuditsApiResponse {
  success: boolean;
  data: PublicAudit[];
  message?: string;
}

/**
 * API response for visibility toggle
 */
export interface ToggleVisibilityApiResponse {
  success: boolean;
  data: {
    id: number;
    isPublic: boolean;
  };
  message?: string;
}

/**
 * API response for badge analytics
 */
export interface BadgeAnalyticsApiResponse {
  success: boolean;
  data: {
    totalBadges: number;
    totalImpressions: number;
    totalClicks: number;
    averageCtr: number;
  };
  message?: string;
}

// ============================================================
// Component Props
// ============================================================

/**
 * Props for BadgeManagementPage component
 */
export interface BadgeManagementPageProps {
  /** Initial audits data (for SSR) */
  audits?: PublicAudit[];
  /** Loading state override */
  isLoading?: boolean;
  /** Error state override */
  error?: string | null;
}

/**
 * Props for BadgeManagementRow component
 */
export interface BadgeManagementRowProps {
  /** Audit data */
  audit: PublicAudit;
  /** Callback when visibility is toggled */
  onToggleVisibility: (auditId: number, isPublic: boolean) => Promise<void>;
  /** Callback when embed code is requested */
  onGetEmbedCode: (hash: string) => void;
}

/**
 * Props for EmbedCodeModal component
 */
export interface EmbedCodeModalProps {
  /** Whether the modal is open */
  isOpen: boolean;
  /** Audit hash for embed code */
  auditHash: string | null;
  /** Callback when modal is closed */
  onClose: () => void;
}

// ============================================================
// Sort Types
// ============================================================

/**
 * Sortable fields for badges table
 */
export type BadgeSortField = keyof BadgeAnalytics;

/**
 * Sort direction
 */
export type SortDirection = 'asc' | 'desc';

/**
 * Sort state
 */
export interface BadgeSortState {
  field: BadgeSortField;
  direction: SortDirection;
}
