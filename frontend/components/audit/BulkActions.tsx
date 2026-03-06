/**
 * BulkActions Component
 * 
 * Task: TASK-14-16 - Create Audit Detail Page (Frontend)
 * Spec: SPEC-14 - Technical SEO Site Audit
 * 
 * Bulk action toolbar for selected issues.
 */

"use client";

import * as React from 'react';
import { Check, X, AlertTriangle, Loader2 } from 'lucide-react';
import { cn } from '@/lib/utils';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import type { IssueStatus } from '@/lib/types/audit-issues';

// ============================================================
// Props
// ============================================================

export interface BulkActionsProps {
  /** Selected issue IDs */
  selectedIds: number[];
  /** Total number of issues on current page */
  totalOnPage: number;
  /** Total number of issues across all pages */
  totalIssues?: number;
  /** Current page number */
  currentPage?: number;
  /** Total pages */
  totalPages?: number;
  /** Bulk action handler */
  onBulkAction: (action: 'fixed' | 'ignored', ids: number[]) => void;
  /** Clear selection handler */
  onClearSelection: () => void;
  /** Select all handler */
  onSelectAll?: () => void;
  /** Is action in progress */
  isLoading?: boolean;
  /** Additional CSS classes */
  className?: string;
}

// ============================================================
// Component
// ============================================================

export function BulkActions({
  selectedIds,
  totalOnPage,
  totalIssues,
  currentPage = 1,
  totalPages = 1,
  onBulkAction,
  onClearSelection,
  onSelectAll,
  isLoading = false,
  className,
}: BulkActionsProps) {
  const [showConfirm, setShowConfirm] = React.useState<'fixed' | 'ignored' | null>(null);

  const selectedCount = selectedIds.length;
  const allSelected = selectedIds.length === totalOnPage && totalOnPage > 0;
  const hasSelection = selectedCount > 0;

  // Handle action click
  const handleActionClick = React.useCallback((action: 'fixed' | 'ignored') => {
    if (selectedCount > 5) {
      // Show confirmation for bulk actions with many items
      setShowConfirm(action);
    } else {
      onBulkAction(action, selectedIds);
    }
  }, [selectedCount, selectedIds, onBulkAction]);

  // Confirm action
  const handleConfirmAction = React.useCallback(() => {
    if (showConfirm) {
      onBulkAction(showConfirm, selectedIds);
      setShowConfirm(null);
    }
  }, [showConfirm, selectedIds, onBulkAction]);

  // Cancel confirmation
  const handleCancelConfirm = React.useCallback(() => {
    setShowConfirm(null);
  }, []);

  if (!hasSelection) {
    return null;
  }

  return (
    <>
      <div
        data-testid="bulk-actions"
        className={cn(
          'sticky bottom-4 z-10',
          'flex items-center justify-between gap-4',
          'bg-slate-800/95 backdrop-blur-sm border border-slate-700 rounded-lg p-3',
          'shadow-lg',
          className
        )}
        role="toolbar"
        aria-label="Bulk actions for selected issues"
      >
        {/* Left: Selection info */}
        <div className="flex items-center gap-3">
          {/* Select all checkbox */}
          {onSelectAll && (
            <Checkbox
              checked={allSelected}
              onCheckedChange={(checked) => {
                if (checked) {
                  onSelectAll();
                } else {
                  onClearSelection();
                }
              }}
              aria-label="Select all issues on page"
              data-testid="select-all-checkbox"
            />
          )}

          {/* Selection count */}
          <span className="text-sm text-slate-300" data-testid="selection-count">
            {selectedCount} selected
          </span>

          {/* Clear selection button */}
          <Button
            variant="ghost"
            size="sm"
            onClick={onClearSelection}
            disabled={isLoading}
            data-testid="clear-selection-button"
            className="h-7 text-xs text-slate-400 hover:text-white"
          >
            <X className="h-3.5 w-3.5 mr-1" aria-hidden="true" />
            Clear
          </Button>
        </div>

        {/* Right: Action buttons */}
        <div className="flex items-center gap-2">
          <Button
            variant="outline"
            size="sm"
            onClick={() => handleActionClick('fixed')}
            disabled={isLoading}
            data-testid="bulk-fixed-button"
            className="h-8 text-xs border-green-500/50 text-green-400 hover:bg-green-500/10"
          >
            {isLoading ? (
              <Loader2 className="h-4 w-4 animate-spin mr-1" aria-hidden="true" />
            ) : (
              <Check className="h-4 w-4 mr-1" aria-hidden="true" />
            )}
            Mark Fixed ({selectedCount})
          </Button>

          <Button
            variant="outline"
            size="sm"
            onClick={() => handleActionClick('ignored')}
            disabled={isLoading}
            data-testid="bulk-ignore-button"
            className="h-8 text-xs border-slate-500/50 text-slate-400 hover:bg-slate-500/10"
          >
            {isLoading ? (
              <Loader2 className="h-4 w-4 animate-spin mr-1" aria-hidden="true" />
            ) : (
              <X className="h-4 w-4 mr-1" aria-hidden="true" />
            )}
            Ignore ({selectedCount})
          </Button>
        </div>
      </div>

      {/* Confirmation Dialog */}
      {showConfirm && (
        <div
          className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm"
          data-testid="bulk-confirm-dialog"
          role="dialog"
          aria-modal="true"
          aria-labelledby="confirm-dialog-title"
        >
          <div className="bg-slate-800 border border-slate-700 rounded-lg p-6 max-w-md w-full mx-4 shadow-xl">
            <div className="flex items-center gap-3 mb-4">
              <AlertTriangle className="h-5 w-5 text-yellow-400" aria-hidden="true" />
              <h2 id="confirm-dialog-title" className="text-lg font-semibold text-white">
                Confirm Bulk Action
              </h2>
            </div>
            <p className="text-sm text-slate-300 mb-6">
              Are you sure you want to mark {selectedCount} issues as{' '}
              <span className="font-medium text-white">
                {showConfirm === 'fixed' ? 'Fixed' : 'Ignored'}
              </span>
              ? This action can be undone by reopening the issues.
            </p>
            <div className="flex justify-end gap-3">
              <Button
                variant="ghost"
                onClick={handleCancelConfirm}
                disabled={isLoading}
                className="text-slate-400 hover:text-white"
              >
                Cancel
              </Button>
              <Button
                onClick={handleConfirmAction}
                disabled={isLoading}
                data-testid="confirm-action-button"
                className={cn(
                  showConfirm === 'fixed'
                    ? 'bg-green-500 hover:bg-green-600 text-white'
                    : 'bg-slate-600 hover:bg-slate-500 text-white'
                )}
              >
                {isLoading && <Loader2 className="h-4 w-4 animate-spin mr-2" aria-hidden="true" />}
                Confirm
              </Button>
            </div>
          </div>
        </div>
      )}
    </>
  );
}

export default BulkActions;
