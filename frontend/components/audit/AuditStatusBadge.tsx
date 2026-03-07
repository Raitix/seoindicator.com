/**
 * AuditStatusBadge Component
 * 
 * Task: TASK-14-15 - Create Audit List Page (Frontend)
 * Spec: SPEC-14 - Technical SEO Site Audit
 * 
 * Badge component for displaying audit status with icons and animations
 */

'use client';

import * as React from 'react';
import { Clock, Loader2, CheckCircle, XCircle } from 'lucide-react';
import { cn } from '@/lib/utils';
import type { AuditStatus } from '@/lib/hooks/useAudits';

// ============================================================
// Props
// ============================================================

export interface AuditStatusBadgeProps {
  /** Status to display */
  status: AuditStatus;
  /** Badge size */
  size?: 'sm' | 'md' | 'lg';
  /** Additional CSS classes */
  className?: string;
}

// ============================================================
// Config
// ============================================================

interface StatusConfig {
  label: string;
  icon: typeof Clock;
  testId: string;
  className: string;
  animation?: string;
}

const statusConfig: Record<AuditStatus, StatusConfig> = {
  pending: {
    label: 'Pending',
    icon: Clock,
    testId: 'clock-icon',
    className: 'bg-gray-500/20 text-gray-300 border-gray-500/30',
    animation: 'animate-pulse',
  },
  running: {
    label: 'Running',
    icon: Loader2,
    testId: 'loader-icon',
    className: 'bg-blue-500/20 text-blue-400 border-blue-500/30',
    animation: 'animate-spin',
  },
  completed: {
    label: 'Completed',
    icon: CheckCircle,
    testId: 'check-circle-icon',
    className: 'bg-green-500/20 text-green-400 border-green-500/30',
  },
  failed: {
    label: 'Failed',
    icon: XCircle,
    testId: 'x-circle-icon',
    className: 'bg-red-500/20 text-red-400 border-red-500/30',
  },
  cancelled: {
    label: 'Cancelled',
    icon: XCircle,
    testId: 'x-circle-icon',
    className: 'bg-gray-500/20 text-gray-500 border-gray-500/20',
  },
};

const sizeConfig = {
  sm: 'text-xs px-2 py-0.5',
  md: 'text-sm px-2.5 py-1',
  lg: 'text-base px-3 py-1.5',
};

const iconSizeConfig = {
  sm: 'h-3 w-3',
  md: 'h-3.5 w-3.5',
  lg: 'h-4 w-4',
};

// ============================================================
// Component
// ============================================================

export function AuditStatusBadge({
  status,
  size = 'md',
  className,
}: AuditStatusBadgeProps) {
  const config = statusConfig[status] || statusConfig.pending;
  const Icon = config.icon;
  const labelText = config.label;

  return (
    <span
      data-testid={`status-badge-${status}`}
      role="status"
      aria-label={`Audit status: ${labelText}`}
      className={cn(
        // Base styles
        'inline-flex items-center justify-center gap-1.5 font-medium whitespace-nowrap rounded-full border',
        // Size
        sizeConfig[size],
        // Status styles
        config.className,
        // Animation
        config.animation,
        // Custom classes
        className
      )}
    >
      <Icon 
        className={cn(iconSizeConfig[size])} 
        aria-hidden="true" 
        data-testid={config.testId}
      />
      <span>{labelText}</span>
    </span>
  );
}

export default AuditStatusBadge;
