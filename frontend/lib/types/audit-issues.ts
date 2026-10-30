/**
 * Audit Issues Types
 * 
 * Task: TASK-14-16 - Create Audit Detail Page (Frontend)
 * Spec: SPEC-14 - Technical SEO Site Audit
 */

// Issue severity levels
export type IssueSeverity = 'critical' | 'warning' | 'notice';

// Issue status
export type IssueStatus = 'open' | 'fixed' | 'ignored';

// Audit status
export type AuditStatus = 'pending' | 'in_progress' | 'completed' | 'failed' | 'cancelled';

// Issue type category
export type IssueCategory = 'Meta' | 'Content' | 'Technical' | 'Performance' | 'Mobile';

// Issue type
export interface IssueType {
  id: number;
  name: string;
  category: IssueCategory;
  description?: string;
}

// Issue
export interface Issue {
  id: number;
  url: string;
  issue_type: IssueType;
  severity: IssueSeverity;
  description: string;
  recommendation: string;
  status: IssueStatus;
  created_at: string;
  updated_at?: string;
  audit_id: number;
}

// Audit summary
export interface Audit {
  id: number;
  status: AuditStatus;
  pages_crawled: number;
  issues_found: number;
  started_at: string;
  completed_at: string | null;
  site_id: number;
  score?: number;
}

// Severity counts
export interface SeverityCounts {
  critical: number;
  warning: number;
  notice: number;
  clean: number;
}

// Issue filters
export interface IssueFilters {
  type?: string;
  severity?: IssueSeverity | '';
  status?: IssueStatus | '';
  search?: string;
  sortBy?: 'created_at' | 'severity' | 'url' | 'status';
  sortOrder?: 'asc' | 'desc';
}

// Pagination meta
export interface PaginationMeta {
  current_page: number;
  per_page: number;
  total: number;
  last_page: number;
}

// Issue list response
export interface IssueListResponse {
  data: Issue[];
  meta: PaginationMeta;
}

// Audit detail response
export interface AuditDetailResponse {
  audit: Audit;
  severityCounts: SeverityCounts;
  issues: IssueListResponse;
}

// Update issue status params
export interface UpdateIssueStatusParams {
  issueId: number;
  status: IssueStatus;
}

// Bulk update params
export interface BulkUpdateIssuesParams {
  auditId: string;
  issueIds: number[];
  status: IssueStatus;
}

// Hook options
export interface UseAuditIssuesOptions {
  page?: number;
  perPage?: number;
  enabled?: boolean;
}

// Sort options
export type SortField = 'created_at' | 'severity' | 'url' | 'status';
export type SortOrder = 'asc' | 'desc';

// Issue type options for filter
// Note: Radix Select doesn't accept empty string values, so we use 'all' for "All" options
export const ISSUE_TYPE_OPTIONS = [
  { value: 'all', label: 'All Issues' },
  { value: 'Meta', label: 'Meta' },
  { value: 'Content', label: 'Content' },
  { value: 'Technical', label: 'Technical' },
  { value: 'Performance', label: 'Performance' },
  { value: 'Mobile', label: 'Mobile' },
] as const;

// Severity options for filter
export const SEVERITY_OPTIONS = [
  { value: 'all', label: 'All Severities' },
  { value: 'critical', label: 'Critical' },
  { value: 'warning', label: 'Warning' },
  { value: 'notice', label: 'Notice' },
] as const;

// Status options for filter
export const STATUS_OPTIONS = [
  { value: 'all', label: 'All Statuses' },
  { value: 'open', label: 'Open' },
  { value: 'fixed', label: 'Fixed' },
  { value: 'ignored', label: 'Ignored' },
] as const;

// Sort options
export const SORT_OPTIONS = [
  { value: 'created_at', label: 'Date Found' },
  { value: 'severity', label: 'Severity' },
  { value: 'url', label: 'URL' },
  { value: 'status', label: 'Status' },
] as const;
