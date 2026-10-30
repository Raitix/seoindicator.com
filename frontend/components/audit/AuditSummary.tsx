/**
 * AuditSummary Component
 * 
 * Task: TASK-14-16 - Create Audit Detail Page (Frontend)
 * Spec: SPEC-14 - Technical SEO Site Audit
 * 
 * Summary header component displaying audit overview and severity counts.
 */

"use client";

import * as React from 'react';
import { ArrowLeft, AlertTriangle, AlertCircle, Info, CheckCircle, RefreshCw, Loader2 } from 'lucide-react';
import Link from 'next/link';
import { cn } from '@/lib/utils';
import { Button } from '@/components/ui/button';
import { IssueStatusBadge } from './IssueStatusBadge';
import type { Audit, SeverityCounts, AuditStatus, IssueSeverity } from '@/lib/types/audit-issues';

// ============================================================
// Props
// ============================================================

export interface AuditSummaryProps {
  /** Audit data */
  audit: Audit | null;
  /** Severity counts */
  severityCounts: SeverityCounts;
  /** Callback when severity card is clicked */
  onSeverityClick?: (severity: IssueSeverity) => void;
  /** Currently active severity filter */
  activeSeverity?: IssueSeverity | '';
  /** Callback to refresh data */
  onRefresh?: () => void;
  /** Is loading state */
  isLoading?: boolean;
  /** Site ID for navigation */
  siteId: string;
  /** Additional CSS classes */
  className?: string;
}

// ============================================================
// Helpers
// ============================================================

function formatStatus(status: AuditStatus): { label: string; className: string } {
  switch (status) {
    case 'completed':
      return { label: 'Completed', className: 'bg-green-500/20 text-green-400' };
    case 'in_progress':
      return { label: 'In Progress', className: 'bg-blue-500/20 text-blue-400' };
    case 'pending':
      return { label: 'Pending', className: 'bg-yellow-500/20 text-yellow-400' };
    case 'failed':
      return { label: 'Failed', className: 'bg-red-500/20 text-red-400' };
    case 'cancelled':
      return { label: 'Cancelled', className: 'bg-slate-500/20 text-slate-400' };
    default:
      return { label: status, className: 'bg-slate-500/20 text-slate-400' };
  }
}

function formatDate(dateString: string | null): string {
  if (!dateString) return '';
  
  const date = new Date(dateString);
  return date.toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  });
}

function formatRelativeTime(dateString: string | null): string {
  if (!dateString) return '';
  
  const date = new Date(dateString);
  const now = new Date();
  const diffMs = now.getTime() - date.getTime();
  const diffMins = Math.floor(diffMs / (1000 * 60));
  const diffHours = Math.floor(diffMs / (1000 * 60 * 60));
  const diffDays = Math.floor(diffMs / (1000 * 60 * 60 * 24));
  
  if (diffMins < 1) return 'Just now';
  if (diffMins < 60) return `${diffMins} minute${diffMins > 1 ? 's' : ''} ago`;
  if (diffHours < 24) return `${diffHours} hour${diffHours > 1 ? 's' : ''} ago`;
  if (diffDays < 7) return `${diffDays} day${diffDays > 1 ? 's' : ''} ago`;
  
  return formatDate(dateString);
}

function calculateDuration(start: string, end: string | null): string {
  const startDate = new Date(start);
  const endDate = end ? new Date(end) : new Date();
  const diffMs = endDate.getTime() - startDate.getTime();
  const diffMins = Math.floor(diffMs / (1000 * 60));
  const diffSecs = Math.floor((diffMs % (1000 * 60)) / 1000);
  
  if (diffMins < 1) return `${diffSecs}s`;
  return `${diffMins}m ${diffSecs}s`;
}

// ============================================================
// Severity Card Component
// ============================================================

interface SeverityCardProps {
  type: 'critical' | 'warning' | 'notice' | 'clean';
  count: number;
  isActive?: boolean;
  onClick?: () => void;
}

const severityCardConfig = {
  critical: {
    icon: AlertTriangle,
    label: 'Critical',
    color: 'text-red-400',
    bgColor: 'bg-red-500/20',
    borderColor: 'border-red-500/30',
    activeBorder: 'border-red-500',
  },
  warning: {
    icon: AlertCircle,
    label: 'Warning',
    color: 'text-orange-400',
    bgColor: 'bg-orange-500/20',
    borderColor: 'border-orange-500/30',
    activeBorder: 'border-orange-500',
  },
  notice: {
    icon: Info,
    label: 'Notice',
    color: 'text-yellow-400',
    bgColor: 'bg-yellow-500/20',
    borderColor: 'border-yellow-500/30',
    activeBorder: 'border-yellow-500',
  },
  clean: {
    icon: CheckCircle,
    label: 'Clean',
    color: 'text-green-400',
    bgColor: 'bg-green-500/20',
    borderColor: 'border-green-500/30',
    activeBorder: 'border-green-500',
  },
};

function SeverityCard({ type, count, isActive, onClick }: SeverityCardProps) {
  const config = severityCardConfig[type];
  const Icon = config.icon;

  return (
    <button
      data-testid={`severity-card-${type}`}
      onClick={onClick}
      className={cn(
        'flex flex-col items-center justify-center p-3 rounded-lg border transition-all min-w-[80px]',
        config.bgColor,
        config.borderColor,
        isActive && [config.activeBorder, 'ring-2 ring-offset-2 ring-offset-slate-900'],
        onClick && 'cursor-pointer hover:opacity-80',
        !onClick && 'cursor-default'
      )}
      aria-label={`${config.label}: ${count}`}
      aria-pressed={isActive}
    >
      <Icon className={cn('h-5 w-5 mb-1', config.color)} aria-hidden="true" />
      <span className={cn('text-lg font-semibold', config.color)}>{count}</span>
      <span className="text-xs text-slate-400">{config.label}</span>
    </button>
  );
}

// ============================================================
// Main Component
// ============================================================

export function AuditSummary({
  audit,
  severityCounts,
  onSeverityClick,
  activeSeverity = '',
  onRefresh,
  isLoading = false,
  siteId,
  className,
}: AuditSummaryProps) {
  if (!audit) {
    return (
      <div className={cn('animate-pulse space-y-4', className)}>
        <div className="h-8 w-48 bg-slate-700 rounded" />
        <div className="h-4 w-64 bg-slate-700 rounded" />
        <div className="flex gap-2">
          {[1, 2, 3, 4].map((i) => (
            <div key={i} className="h-20 w-20 bg-slate-700 rounded-lg" />
          ))}
        </div>
      </div>
    );
  }

  const statusConfig = formatStatus(audit.status);
  const isInProgress = audit.status === 'in_progress';
  const displayDate = audit.completed_at || audit.started_at;

  return (
    <div data-testid="audit-summary" className={cn('space-y-4', className)}>
      {/* Header Row */}
      <div className="flex items-start justify-between gap-4">
        <div className="flex-1">
          {/* Back Link */}
          <Link
            href={`/sites/${siteId}/audits`}
            className="inline-flex items-center gap-1 text-sm text-slate-400 hover:text-white mb-2 transition-colors"
            data-testid="back-to-audits-link"
          >
            <ArrowLeft className="h-4 w-4" aria-hidden="true" />
            Back to Audits
          </Link>

          {/* Audit Info */}
          <div className="flex items-center gap-3 flex-wrap">
            <h1 className="text-xl font-semibold text-white">
              Audit #{audit.id}
            </h1>
            <span
              className={cn(
                'inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-xs font-medium',
                statusConfig.className
              )}
              data-testid="audit-status-badge"
            >
              {isInProgress && <Loader2 className="h-3 w-3 animate-spin" aria-hidden="true" />}
              {statusConfig.label}
            </span>
          </div>

          {/* Date and Stats */}
          <div className="flex items-center gap-4 mt-2 text-sm text-slate-400 flex-wrap">
            {displayDate && (
              <span data-testid="audit-date">
                {audit.completed_at ? 'Completed' : 'Started'} {formatRelativeTime(displayDate)}
              </span>
            )}
            <span data-testid="audit-pages">
              {audit.pages_crawled.toLocaleString()} pages crawled
            </span>
            <span data-testid="audit-issues-count">
              {audit.issues_found.toLocaleString()} total issues
            </span>
            {audit.started_at && (
              <span className="text-slate-500">
                Duration: {calculateDuration(audit.started_at, audit.completed_at)}
              </span>
            )}
          </div>
        </div>

        {/* Actions */}
        {onRefresh && (
          <Button
            variant="ghost"
            size="sm"
            onClick={onRefresh}
            disabled={isLoading}
            data-testid="refresh-audit-button"
            aria-label="Refresh audit data"
            className="text-slate-400 hover:text-white"
          >
            <RefreshCw className={cn('h-4 w-4', isLoading && 'animate-spin')} aria-hidden="true" />
          </Button>
        )}
      </div>

      {/* Severity Cards */}
      <div className="flex gap-3 overflow-x-auto pb-2">
        <SeverityCard
          type="critical"
          count={severityCounts.critical}
          isActive={activeSeverity === 'critical'}
          onClick={onSeverityClick ? () => onSeverityClick('critical') : undefined}
        />
        <SeverityCard
          type="warning"
          count={severityCounts.warning}
          isActive={activeSeverity === 'warning'}
          onClick={onSeverityClick ? () => onSeverityClick('warning') : undefined}
        />
        <SeverityCard
          type="notice"
          count={severityCounts.notice}
          isActive={activeSeverity === 'notice'}
          onClick={onSeverityClick ? () => onSeverityClick('notice') : undefined}
        />
        <SeverityCard
          type="clean"
          count={severityCounts.clean}
          isActive={false}
          onClick={undefined}
        />
      </div>
    </div>
  );
}

export default AuditSummary;
