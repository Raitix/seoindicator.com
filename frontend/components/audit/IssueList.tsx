/**
 * IssueList Component
 * 
 * Task: TASK-14-16 - Create Audit Detail Page (Frontend)
 * Spec: SPEC-14 - Technical SEO Site Audit
 * 
 * List component for displaying and managing audit issues.
 */

"use client";

import * as React from 'react';
import { ChevronLeft, ChevronRight, ChevronsLeft, ChevronsRight } from 'lucide-react';
import { cn } from '@/lib/utils';
import { Button } from '@/components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Skeleton } from '@/components/ui/skeleton';
import { EmptyState } from '@/components/ui/EmptyState';
import { IssueItem } from './IssueItem';
import type { Issue, IssueStatus, IssueSeverity, PaginationMeta } from '@/lib/types/audit-issues';

// ============================================================
// Props
// ============================================================

export interface IssueListProps {
  /** Issues to display */
  issues: Issue[];
  /** Loading state */
  isLoading?: boolean;
  /** Error state */
  error?: Error | null;
  /** Pagination metadata */
  meta?: PaginationMeta;
  /** Selected issue IDs */
  selectedIds: number[];
  /** Selection change handler */
  onSelectionChange: (ids: number[]) => void;
  /** Status change handler */
  onStatusChange: (issueId: number, status: IssueStatus) => void;
  /** Page change handler */
  onPageChange?: (page: number) => void;
  /** Items per page change handler */
  onPerPageChange?: (perPage: number) => void;
  /** Group by field */
  groupBy?: 'severity' | 'status' | 'type' | null;
  /** Additional CSS classes */
  className?: string;
}

// ============================================================
// Skeleton Component
// ============================================================

function IssueItemSkeleton() {
  return (
    <div className="rounded-lg border border-slate-800 bg-slate-900/60 p-4">
      <div className="flex items-start gap-3">
        <Skeleton className="h-4 w-4 rounded" />
        <div className="flex-1 space-y-2">
          <div className="flex items-center gap-2">
            <Skeleton className="h-5 w-16 rounded" />
            <Skeleton className="h-3 w-20 rounded" />
          </div>
          <Skeleton className="h-4 w-48 rounded" />
          <Skeleton className="h-3 w-64 rounded" />
          <Skeleton className="h-3 w-96 rounded" />
        </div>
      </div>
    </div>
  );
}

// ============================================================
// Pagination Component
// ============================================================

interface PaginationProps {
  meta: PaginationMeta;
  onPageChange: (page: number) => void;
  onPerPageChange?: (perPage: number) => void;
}

function Pagination({ meta, onPageChange, onPerPageChange }: PaginationProps) {
  const { current_page, per_page, total, last_page } = meta;
  const startItem = (current_page - 1) * per_page + 1;
  const endItem = Math.min(current_page * per_page, total);

  return (
    <div
      data-testid="pagination"
      className="flex items-center justify-between gap-4 py-4"
    >
      {/* Items info */}
      <div className="text-sm text-slate-400" data-testid="pagination-info">
        Showing {startItem}-{endItem} of {total} issues
      </div>

      {/* Items per page selector */}
      {onPerPageChange && (
        <div className="flex items-center gap-2">
          <span className="text-sm text-slate-400">Show:</span>
          <Select
            value={String(per_page)}
            onValueChange={(value) => onPerPageChange(parseInt(value, 10))}
          >
            <SelectTrigger className="w-[70px] h-8 text-sm" data-testid="per-page-selector">
              <SelectValue />
            </SelectTrigger>
            <SelectContent>
              {[10, 25, 50, 100].map((num) => (
                <SelectItem key={num} value={String(num)}>
                  {num}
                </SelectItem>
              ))}
            </SelectContent>
          </Select>
        </div>
      )}

      {/* Page navigation */}
      <div className="flex items-center gap-1">
        <Button
          variant="ghost"
          size="icon"
          onClick={() => onPageChange(1)}
          disabled={current_page === 1}
          aria-label="First page"
          data-testid="first-page-button"
          className="h-8 w-8"
        >
          <ChevronsLeft className="h-4 w-4" />
        </Button>
        <Button
          variant="ghost"
          size="icon"
          onClick={() => onPageChange(current_page - 1)}
          disabled={current_page === 1}
          aria-label="Previous page"
          data-testid="prev-page-button"
          className="h-8 w-8"
        >
          <ChevronLeft className="h-4 w-4" />
        </Button>

        {/* Page numbers */}
        <div className="flex items-center gap-1" data-testid="page-numbers">
          {Array.from({ length: Math.min(5, last_page) }, (_, i) => {
            let pageNum: number;
            if (last_page <= 5) {
              pageNum = i + 1;
            } else if (current_page <= 3) {
              pageNum = i + 1;
            } else if (current_page >= last_page - 2) {
              pageNum = last_page - 4 + i;
            } else {
              pageNum = current_page - 2 + i;
            }

            return (
              <Button
                key={pageNum}
                variant={pageNum === current_page ? 'default' : 'ghost'}
                size="icon"
                onClick={() => onPageChange(pageNum)}
                aria-label={`Page ${pageNum}`}
                aria-current={pageNum === current_page ? 'page' : undefined}
                data-testid={`page-${pageNum}-button`}
                className={cn(
                  'h-8 w-8',
                  pageNum === current_page && 'bg-blue-500 text-white hover:bg-blue-600'
                )}
              >
                {pageNum}
              </Button>
            );
          })}
        </div>

        <Button
          variant="ghost"
          size="icon"
          onClick={() => onPageChange(current_page + 1)}
          disabled={current_page === last_page}
          aria-label="Next page"
          data-testid="next-page-button"
          className="h-8 w-8"
        >
          <ChevronRight className="h-4 w-4" />
        </Button>
        <Button
          variant="ghost"
          size="icon"
          onClick={() => onPageChange(last_page)}
          disabled={current_page === last_page}
          aria-label="Last page"
          data-testid="last-page-button"
          className="h-8 w-8"
        >
          <ChevronsRight className="h-4 w-4" />
        </Button>
      </div>
    </div>
  );
}

// ============================================================
// Main Component
// ============================================================

export function IssueList({
  issues,
  isLoading = false,
  error = null,
  meta,
  selectedIds,
  onSelectionChange,
  onStatusChange,
  onPageChange,
  onPerPageChange,
  groupBy = null,
  className,
}: IssueListProps) {
  // Expand state for each issue
  const [expandedIds, setExpandedIds] = React.useState<Set<number>>(new Set());

  // Handle select all
  const handleSelectAll = React.useCallback(() => {
    if (selectedIds.length === issues.length) {
      onSelectionChange([]);
    } else {
      onSelectionChange(issues.map((issue) => issue.id));
    }
  }, [issues, selectedIds.length, onSelectionChange]);

  // Handle single issue selection
  const handleSelectIssue = React.useCallback(
    (issueId: number, selected: boolean) => {
      if (selected) {
        onSelectionChange([...selectedIds, issueId]);
      } else {
        onSelectionChange(selectedIds.filter((id) => id !== issueId));
      }
    },
    [selectedIds, onSelectionChange]
  );

  // Handle expand toggle
  const handleExpandToggle = React.useCallback((issueId: number, expanded: boolean) => {
    setExpandedIds((prev) => {
      const next = new Set(prev);
      if (expanded) {
        next.add(issueId);
      } else {
        next.delete(issueId);
      }
      return next;
    });
  }, []);

  // Loading state
  if (isLoading) {
    return (
      <div className={cn('space-y-3', className)} data-testid="issue-list-loading">
        {[1, 2, 3, 4, 5].map((i) => (
          <IssueItemSkeleton key={i} />
        ))}
      </div>
    );
  }

  // Error state
  if (error) {
    return (
      <div
        data-testid="issue-list-error"
        className={cn('text-center py-12', className)}
      >
        <div className="text-red-400 mb-2">Failed to load issues</div>
        <div className="text-sm text-slate-400">{error.message}</div>
      </div>
    );
  }

  // Empty state
  if (issues.length === 0) {
    return (
      <EmptyState
        type="custom"
        title="No issues found"
        description="No issues match your current filters. Try adjusting your search criteria."
        className={className}
      />
    );
  }

  // Default pagination meta
  const paginationMeta: PaginationMeta = meta || {
    current_page: 1,
    per_page: issues.length,
    total: issues.length,
    last_page: 1,
  };

  return (
    <div data-testid="issue-list" className={cn('space-y-4', className)}>
      {/* Select All Checkbox */}
      <div className="flex items-center gap-2">
        <input
          type="checkbox"
          checked={selectedIds.length === issues.length && issues.length > 0}
          onChange={handleSelectAll}
          className="h-4 w-4 rounded border-slate-600 bg-slate-800 text-blue-500 focus:ring-blue-500 focus:ring-offset-slate-900"
          data-testid="select-all-issues"
          aria-label="Select all issues on page"
        />
        <span className="text-sm text-slate-400">
          {selectedIds.length > 0
            ? `${selectedIds.length} selected`
            : 'Select all'}
        </span>
      </div>

      {/* Issue Items */}
      <div className="space-y-3">
        {issues.map((issue) => (
          <IssueItem
            key={issue.id}
            issue={issue}
            isSelected={selectedIds.includes(issue.id)}
            onSelect={(selected) => handleSelectIssue(issue.id, selected)}
            onStatusChange={(status) => onStatusChange(issue.id, status)}
            expanded={expandedIds.has(issue.id)}
            onExpand={(expanded) => handleExpandToggle(issue.id, expanded)}
          />
        ))}
      </div>

      {/* Pagination */}
      {onPageChange && paginationMeta.last_page > 1 && (
        <Pagination
          meta={paginationMeta}
          onPageChange={onPageChange}
          onPerPageChange={onPerPageChange}
        />
      )}
    </div>
  );
}

export default IssueList;
