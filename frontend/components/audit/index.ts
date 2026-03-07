/**
 * Audit Components Index
 * 
 * Task: TASK-14-16 - Create Audit Detail Page (Frontend)
 * Spec: SPEC-14 - Technical SEO Site Audit
 * 
 * Exports all audit-related components.
 */

// Existing components
export { default as ScoreOverview } from './ScoreOverview';
export type { ScoreOverviewProps } from './ScoreOverview';

export { default as AuditCard } from './AuditCard';
export type { AuditCardProps } from './AuditCard';

export { default as CategoryBreakdown } from './CategoryBreakdown';
export { default as PublicAuditCTA } from './PublicAuditCTA';
export { default as PublicAuditPage } from './PublicAuditPage';
export { default as ShareAuditModal } from './ShareAuditModal';
export { default as TopIssues } from './TopIssues';

// Badge Preview Component (TASK-33-13)
export { BadgePreview } from './BadgePreview';
export type { BadgePreviewProps, BadgeSize, BadgeTheme, EmbedType } from './BadgePreview';

// New TASK-14-15 components
export { AuditStatusBadge } from './AuditStatusBadge';
export type { AuditStatusBadgeProps } from './AuditStatusBadge';

export { IssueSummary } from './IssueSummary';
export type { IssueSummaryProps } from './IssueSummary';

export { StartAuditModal } from './StartAuditModal';
export type { StartAuditModalProps } from './StartAuditModal';

export { AuditListCard } from './AuditListCard';
export type { AuditListCardProps } from './AuditListCard';

export { AuditList } from './AuditList';
export type { AuditListProps } from './AuditList';

// New TASK-14-16 components
export { AuditSummary } from './AuditSummary';
export type { AuditSummaryProps } from './AuditSummary';

export { IssueStatusBadge } from './IssueStatusBadge';
export type { IssueStatusBadgeProps } from './IssueStatusBadge';

export { IssueFilters, countActiveFilters } from './IssueFilters';
export type { IssueFiltersProps } from './IssueFilters';

export { IssueItem } from './IssueItem';
export type { IssueItemProps } from './IssueItem';

export { IssueList } from './IssueList';
export type { IssueListProps } from './IssueList';

export { BulkActions } from './BulkActions';
export type { BulkActionsProps } from './BulkActions';

export { AuditDetailPage } from './AuditDetailPage';
export type { AuditDetailPageProps } from './AuditDetailPage';

// Error Boundary
export { ErrorBoundary, withErrorBoundary } from './ErrorBoundary';
