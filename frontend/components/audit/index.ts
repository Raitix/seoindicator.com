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
