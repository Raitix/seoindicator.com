/**
 * IssueStatusBadge Component
 * 
 * Task: TASK-14-16 - Create Audit Detail Page (Frontend)
 * Spec: SPEC-14 - Technical SEO Site Audit
 * 
 * Badge component for displaying issue status or severity.
 */

"use client";

import * as React from 'react';
import { AlertTriangle, AlertCircle, Info, CheckCircle, XCircle, Clock } from 'lucide-react';
import { cn } from '@/lib/utils';
import type { IssueSeverity, IssueStatus } from '@/lib/types/audit-issues';

// ============================================================
// Props
// ============================================================

export interface IssueStatusBadgeProps {
  /** Status to display (open, fixed, ignored) */
  status?: IssueStatus;
  /** Severity to display (critical, warning, notice) */
  severity?: IssueSeverity;
  /** Badge size */
  size?: 'sm' | 'md' | 'lg';
  /** Badge variant */
  variant?: 'solid' | 'outline' | 'pill';
  /** Show icon only, no text */
  iconOnly?: boolean;
  /** Additional CSS classes */
  className?: string;
  /** Click handler */
  onClick?: () => void;
  /** Tooltip text */
  title?: string;
}

// ============================================================
// Helpers
// ============================================================

const severityConfig: Record<IssueSeverity, { label: string; color: string; bgColor: string; borderColor: string; icon: typeof AlertTriangle }> = {
  critical: {
    label: 'Critical',
    color: 'text-red-400',
    bgColor: 'bg-red-500/20',
    borderColor: 'border-red-500/50',
    icon: AlertTriangle,
  },
  warning: {
    label: 'Warning',
    color: 'text-orange-400',
    bgColor: 'bg-orange-500/20',
    borderColor: 'border-orange-500/50',
    icon: AlertCircle,
  },
  notice: {
    label: 'Notice',
    color: 'text-yellow-400',
    bgColor: 'bg-yellow-500/20',
    borderColor: 'border-yellow-500/50',
    icon: Info,
  },
};

const statusConfig: Record<IssueStatus, { label: string; color: string; bgColor: string; borderColor: string; icon: typeof CheckCircle }> = {
  open: {
    label: 'Open',
    color: 'text-blue-400',
    bgColor: 'bg-blue-500/20',
    borderColor: 'border-blue-500/50',
    icon: Clock,
  },
  fixed: {
    label: 'Fixed',
    color: 'text-green-400',
    bgColor: 'bg-green-500/20',
    borderColor: 'border-green-500/50',
    icon: CheckCircle,
  },
  ignored: {
    label: 'Ignored',
    color: 'text-slate-400',
    bgColor: 'bg-slate-500/20',
    borderColor: 'border-slate-500/50',
    icon: XCircle,
  },
};

const sizeConfig = {
  sm: 'text-xs px-1.5 py-0.5',
  md: 'text-xs px-2 py-0.5',
  lg: 'text-sm px-2.5 py-1',
};

const iconSizeConfig = {
  sm: 'h-3 w-3',
  md: 'h-3.5 w-3.5',
  lg: 'h-4 w-4',
};

// ============================================================
// Component
// ============================================================

export function IssueStatusBadge({
  status,
  severity,
  size = 'md',
  variant = 'solid',
  iconOnly = false,
  className,
  onClick,
  title,
}: IssueStatusBadgeProps) {
  // Determine which config to use (severity takes precedence if both provided)
  const config = severity ? severityConfig[severity] : status ? statusConfig[status] : null;
  
  if (!config) {
    return null;
  }

  const Icon = config.icon;
  const labelText = severity ? config.label.toUpperCase() : config.label;

  const isClickable = !!onClick;

  return (
    <span
      data-testid="issue-status-badge"
      data-severity={severity}
      data-status={status}
      role={isClickable ? 'button' : undefined}
      tabIndex={isClickable ? 0 : undefined}
      title={title || labelText}
      onClick={onClick}
      onKeyDown={(e) => {
        if (isClickable && (e.key === 'Enter' || e.key === ' ')) {
          e.preventDefault();
          onClick();
        }
      }}
      className={cn(
        // Base styles
        'inline-flex items-center justify-center gap-1 font-medium whitespace-nowrap rounded transition-colors',
        // Size
        sizeConfig[size],
        // Variant styles
        variant === 'solid' && [config.bgColor, config.color],
        variant === 'outline' && ['bg-transparent border', config.borderColor, config.color],
        variant === 'pill' && [config.bgColor, config.color, 'rounded-full'],
        // Interactive styles
        isClickable && 'cursor-pointer hover:opacity-80 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-slate-900',
        // Custom classes
        className
      )}
      aria-label={title || labelText}
    >
      <Icon className={iconSizeConfig[size]} aria-hidden="true" />
      {!iconOnly && <span>{labelText}</span>}
    </span>
  );
}

export default IssueStatusBadge;
