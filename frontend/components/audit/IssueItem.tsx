/**
 * IssueItem Component
 * 
 * Task: TASK-14-16 - Create Audit Detail Page (Frontend)
 * Spec: SPEC-14 - Technical SEO Site Audit
 * 
 * Individual issue card component.
 */

"use client";

import * as React from 'react';
import { ExternalLink, ChevronDown, ChevronUp, Copy, Check, Lightbulb } from 'lucide-react';
import { cn } from '@/lib/utils';
import { Checkbox } from '@/components/ui/checkbox';
import { Button } from '@/components/ui/button';
import { IssueStatusBadge } from './IssueStatusBadge';
import type { Issue, IssueStatus, IssueSeverity } from '@/lib/types/audit-issues';

// ============================================================
// Props
// ============================================================

export interface IssueItemProps {
  /** Issue data */
  issue: Issue;
  /** Is the issue selected */
  isSelected?: boolean;
  /** Selection change handler */
  onSelect?: (selected: boolean) => void;
  /** Status change handler */
  onStatusChange?: (status: IssueStatus) => void;
  /** Is status update in progress */
  isUpdating?: boolean;
  /** Is expanded state */
  expanded?: boolean;
  /** Expand change handler */
  onExpand?: (expanded: boolean) => void;
  /** Show select checkbox */
  isSelectable?: boolean;
  /** Additional CSS classes */
  className?: string;
}

// ============================================================
// Helpers
// ============================================================

function truncateUrl(url: string, maxLength: number = 60): string {
  try {
    const urlObj = new URL(url);
    const display = urlObj.hostname + urlObj.pathname;
    if (display.length <= maxLength) {
      return display;
    }
    return display.substring(0, maxLength - 3) + '...';
  } catch {
    return url.length > maxLength ? url.substring(0, maxLength - 3) + '...' : url;
  }
}

function formatDate(dateString: string): string {
  const date = new Date(dateString);
  return date.toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  });
}

// ============================================================
// Component
// ============================================================

export function IssueItem({
  issue,
  isSelected = false,
  onSelect,
  onStatusChange,
  isUpdating = false,
  expanded = false,
  onExpand,
  isSelectable = true,
  className,
}: IssueItemProps) {
  const [copied, setCopied] = React.useState(false);

  // Copy URL to clipboard
  const handleCopyUrl = React.useCallback(async (e: React.MouseEvent) => {
    e.stopPropagation();
    try {
      await navigator.clipboard.writeText(issue.url);
      setCopied(true);
      setTimeout(() => setCopied(false), 2000);
    } catch (err) {
      console.error('Failed to copy URL:', err);
    }
  }, [issue.url]);

  // Open URL in new tab
  const handleViewPage = React.useCallback((e: React.MouseEvent) => {
    e.stopPropagation();
    window.open(issue.url, '_blank', 'noopener,noreferrer');
  }, [issue.url]);

  // Toggle expand
  const handleToggleExpand = React.useCallback(() => {
    onExpand?.(!expanded);
  }, [expanded, onExpand]);

  // Get action buttons based on status
  const getActionButtons = () => {
    const buttons: React.ReactNode[] = [];

    if (issue.status === 'open') {
      buttons.push(
        <Button
          key="fixed"
          variant="ghost"
          size="sm"
          onClick={(e) => {
            e.stopPropagation();
            onStatusChange?.('fixed');
          }}
          disabled={isUpdating}
          data-testid="mark-fixed-button"
          className="h-7 text-xs text-green-400 hover:text-green-300 hover:bg-green-500/10"
        >
          Mark Fixed
        </Button>,
        <Button
          key="ignore"
          variant="ghost"
          size="sm"
          onClick={(e) => {
            e.stopPropagation();
            onStatusChange?.('ignored');
          }}
          disabled={isUpdating}
          data-testid="ignore-button"
          className="h-7 text-xs text-slate-400 hover:text-slate-300 hover:bg-slate-500/10"
        >
          Ignore
        </Button>
      );
    } else {
      buttons.push(
        <Button
          key="reopen"
          variant="ghost"
          size="sm"
          onClick={(e) => {
            e.stopPropagation();
            onStatusChange?.('open');
          }}
          disabled={isUpdating}
          data-testid="reopen-button"
          className="h-7 text-xs text-blue-400 hover:text-blue-300 hover:bg-blue-500/10"
        >
          Reopen
        </Button>
      );
    }

    buttons.push(
      <Button
        key="view"
        variant="ghost"
        size="sm"
        onClick={handleViewPage}
        data-testid="view-page-button"
        className="h-7 text-xs text-slate-400 hover:text-white hover:bg-slate-800"
      >
        <ExternalLink className="h-3.5 w-3.5 mr-1" aria-hidden="true" />
        View Page
      </Button>
    );

    return buttons;
  };

  return (
    <div
      data-testid="issue-item"
      data-issue-id={issue.id}
      className={cn(
        'rounded-lg border bg-slate-900/60 transition-colors',
        isSelected ? 'border-blue-500/50 bg-blue-500/5' : 'border-slate-800 hover:border-slate-700',
        className
      )}
      role="article"
      aria-label={`Issue: ${issue.issue_type.name}`}
    >
      {/* Main Row */}
      <div className="p-4">
        {/* Header */}
        <div className="flex items-start gap-3">
          {/* Select Checkbox */}
          {isSelectable && (
            <Checkbox
              checked={isSelected}
              onCheckedChange={(checked) => onSelect?.(checked === true)}
              disabled={isUpdating}
              data-testid="issue-checkbox"
              aria-label={`Select issue ${issue.id}`}
              className="mt-1"
            />
          )}

          {/* Content */}
          <div className="flex-1 min-w-0">
            {/* Badges */}
            <div className="flex items-center gap-2 flex-wrap mb-1.5">
              <IssueStatusBadge
                severity={issue.severity}
                size="sm"
                data-testid="severity-badge"
              />
              <span className="text-xs text-slate-400" data-testid="issue-category">
                {issue.issue_type.category}
              </span>
              <IssueStatusBadge
                status={issue.status}
                size="sm"
                data-testid="status-badge"
              />
            </div>

            {/* Title */}
            <h3 className="text-sm font-medium text-white mb-1" data-testid="issue-title">
              {issue.issue_type.name}
            </h3>

            {/* URL */}
            <div className="flex items-center gap-2 mb-2">
              <a
                href={issue.url}
                target="_blank"
                rel="noopener noreferrer"
                onClick={(e) => e.stopPropagation()}
                className="text-sm text-blue-400 hover:text-blue-300 transition-colors truncate max-w-[400px]"
                data-testid="issue-url"
                title={issue.url}
              >
                {truncateUrl(issue.url)}
              </a>
              <button
                onClick={handleCopyUrl}
                className="p-1 text-slate-400 hover:text-white transition-colors opacity-0 group-hover:opacity-100"
                aria-label="Copy URL"
                data-testid="copy-url-button"
              >
                {copied ? (
                  <Check className="h-3.5 w-3.5 text-green-400" aria-hidden="true" />
                ) : (
                  <Copy className="h-3.5 w-3.5" aria-hidden="true" />
                )}
              </button>
            </div>

            {/* Description */}
            <p className="text-sm text-slate-400 mb-2" data-testid="issue-description">
              {issue.description}
            </p>

            {/* Date */}
            <span className="text-xs text-slate-500" data-testid="issue-date">
              Found {formatDate(issue.created_at)}
            </span>
          </div>

          {/* Expand Button */}
          {issue.recommendation && (
            <button
              onClick={handleToggleExpand}
              className="p-1 text-slate-400 hover:text-white transition-colors"
              aria-label={expanded ? 'Collapse details' : 'Expand details'}
              data-testid="expand-button"
            >
              {expanded ? (
                <ChevronUp className="h-5 w-5" aria-hidden="true" />
              ) : (
                <ChevronDown className="h-5 w-5" aria-hidden="true" />
              )}
            </button>
          )}
        </div>

        {/* Action Buttons */}
        <div className="flex items-center gap-2 mt-3 pl-7">
          {getActionButtons()}
        </div>
      </div>

      {/* Expanded Content - Recommendation */}
      {expanded && issue.recommendation && (
        <div
          data-testid="issue-recommendation"
          className="px-4 pb-4 pl-10 border-t border-slate-800 pt-3"
        >
          <div className="flex items-start gap-2">
            <Lightbulb className="h-4 w-4 text-yellow-400 mt-0.5 flex-shrink-0" aria-hidden="true" />
            <div>
              <span className="text-xs font-medium text-yellow-400">Recommendation</span>
              <p className="text-sm text-slate-300 mt-1">{issue.recommendation}</p>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}

export default IssueItem;
