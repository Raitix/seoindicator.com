/**
 * AuditListCard Component
 * 
 * Task: TASK-14-15 - Create Audit List Page (Frontend)
 * Spec: SPEC-14 - Technical SEO Site Audit
 * 
 * Card component for displaying an individual audit in the list
 */

'use client';

import * as React from 'react';
import { format } from 'date-fns';
import { ExternalLink, X } from 'lucide-react';
import Link from 'next/link';
import { cn } from '@/lib/utils';
import { Button } from '@/components/ui/button';
import { Progress } from '@/components/ui/progress';
import { AuditStatusBadge } from './AuditStatusBadge';
import { IssueSummary } from './IssueSummary';
import type { Audit } from '@/lib/hooks/useAudits';

// ============================================================
// Props
// ============================================================

export interface AuditListCardProps {
  /** Audit data */
  audit: Audit;
  /** Site ID for navigation */
  siteId: string;
  /** Callback to cancel a running audit */
  onCancel?: (auditId: number) => void;
  /** Whether cancel is in progress */
  isCancelling?: boolean;
  /** Additional CSS classes */
  className?: string;
}

// ============================================================
// Helpers
// ============================================================

/**
 * Format relative time
 */
function formatRelativeTime(dateString: string): string {
  const date = new Date(dateString);
  const now = new Date();
  const diffMs = now.getTime() - date.getTime();
  const diffMins = Math.floor(diffMs / 60000);
  const diffHours = Math.floor(diffMins / 60);
  const diffDays = Math.floor(diffHours / 24);

  if (diffMins < 1) return 'Just now';
  if (diffMins < 60) return `${diffMins} minute${diffMins > 1 ? 's' : ''} ago`;
  if (diffHours < 24) return `${diffHours} hour${diffHours > 1 ? 's' : ''} ago`;
  if (diffDays < 7) return `${diffDays} day${diffDays > 1 ? 's' : ''} ago`;
  
  return format(date, 'MMM d, yyyy');
}

/**
 * Calculate progress percentage for running audits
 */
function calculateProgress(audit: Audit): number {
  if (audit.status !== 'running' || !audit.max_pages) return 0;
  return Math.round((audit.pages_crawled / audit.max_pages) * 100);
}

// ============================================================
// Component
// ============================================================

export function AuditListCard({
  audit,
  siteId,
  onCancel,
  isCancelling = false,
  className,
}: AuditListCardProps) {
  const isRunning = audit.status === 'running';
  const isCompleted = audit.status === 'completed';
  const progress = calculateProgress(audit);

  return (
    <div
      data-testid="audit-card"
      data-audit-id={audit.id}
      className={cn(
        'rounded-lg border border-slate-800 bg-slate-900/60 p-4 transition-colors',
        className
      )}
    >
      {/* Header: Status and Date */}
      <div className="flex items-center justify-between gap-4 mb-3">
        <AuditStatusBadge status={audit.status} />
        
        <span className="text-sm text-slate-400">
          {isRunning ? (
            <>Started {formatRelativeTime(audit.started_at)}</>
          ) : (
            <>
              {isCompleted && audit.completed_at
                ? format(new Date(audit.completed_at), 'MMM d, yyyy \'at\' h:mm a')
                : format(new Date(audit.started_at), 'MMM d, yyyy \'at\' h:mm a')}
            </>
          )}
        </span>
      </div>

      {/* Stats Row */}
      <div className="flex items-center gap-4 mb-3 text-sm text-slate-300">
        <span>
          {isRunning ? (
            <>{audit.pages_crawled}/{audit.max_pages} pages</>
          ) : (
            <>{audit.pages_crawled} pages crawled</>
          )}
        </span>
        <span>•</span>
        <span>{audit.issues_found} issues found</span>
      </div>

      {/* Progress Bar for Running Audits */}
      {isRunning && (
        <div className="mb-3">
          <Progress 
            value={progress} 
            className="h-2"
            aria-label={`Crawling progress: ${progress}%`}
          />
          {audit.current_url && (
            <p className="mt-1 text-xs text-slate-400 truncate" title={audit.current_url}>
              Currently: {audit.current_url}
            </p>
          )}
        </div>
      )}

      {/* Issue Summary for Completed Audits */}
      {isCompleted && (
        <div className="mb-3">
          <IssueSummary
            critical={audit.critical_issues ?? 0}
            warning={audit.warning_issues ?? 0}
            notice={audit.notice_issues ?? 0}
            size="sm"
          />
        </div>
      )}

      {/* Actions */}
      <div className="flex items-center justify-end gap-2 pt-2 border-t border-slate-800">
        {isRunning && onCancel && (
          <Button
            variant="outline"
            size="sm"
            onClick={() => onCancel(audit.id)}
            disabled={isCancelling}
            className="text-red-400 hover:text-red-300 hover:bg-red-500/10"
          >
            {isCancelling ? (
              <>Cancelling...</>
            ) : (
              <>
                <X className="h-4 w-4 mr-1" />
                Cancel
              </>
            )}
          </Button>
        )}
        
        {isCompleted && (
          <Link href={`/sites/${siteId}/audits/${audit.id}`}>
            <Button variant="outline" size="sm">
              View Details
              <ExternalLink className="h-4 w-4 ml-1" />
            </Button>
          </Link>
        )}
      </div>
    </div>
  );
}

export default AuditListCard;
