/**
 * IssueSummary Component
 * 
 * Task: TASK-14-15 - Create Audit List Page (Frontend)
 * Spec: SPEC-14 - Technical SEO Site Audit
 * 
 * Component for displaying issue summary badges (Critical, Warning, Notice)
 */

'use client';

import * as React from 'react';
import { AlertTriangle, AlertCircle, Info } from 'lucide-react';
import { cn } from '@/lib/utils';

// ============================================================
// Props
// ============================================================

export interface IssueSummaryProps {
  /** Number of critical issues */
  critical: number;
  /** Number of warning issues */
  warning: number;
  /** Number of notice issues */
  notice: number;
  /** Size variant */
  size?: 'sm' | 'md' | 'lg';
  /** Additional CSS classes */
  className?: string;
}

// ============================================================
// Config
// ============================================================

interface IssueConfig {
  key: 'critical' | 'warning' | 'notice';
  label: string;
  icon: typeof AlertTriangle;
  className: string;
}

const issueConfigs: IssueConfig[] = [
  {
    key: 'critical',
    label: 'Critical',
    icon: AlertTriangle,
    className: 'bg-red-500/20 text-red-400 border-red-500/30',
  },
  {
    key: 'warning',
    label: 'Warning',
    icon: AlertCircle,
    className: 'bg-orange-500/20 text-orange-400 border-orange-500/30',
  },
  {
    key: 'notice',
    label: 'Notice',
    icon: Info,
    className: 'bg-yellow-500/20 text-yellow-400 border-yellow-500/30',
  },
];

const sizeConfig = {
  sm: 'text-xs px-1.5 py-0.5',
  md: 'text-xs px-2 py-0.5',
  lg: 'text-sm px-2.5 py-1',
};

const iconSizeConfig = {
  sm: 'h-3 w-3',
  md: 'h-3 w-3',
  lg: 'h-3.5 w-3.5',
};

// ============================================================
// Component
// ============================================================

export function IssueSummary({
  critical,
  warning,
  notice,
  size = 'md',
  className,
}: IssueSummaryProps) {
  const counts = { critical, warning, notice };

  return (
    <div 
      className={cn('flex flex-wrap items-center gap-2', className)}
      role="group"
      aria-label="Issue summary"
    >
      {issueConfigs.map((config) => {
        const count = counts[config.key];
        const Icon = config.icon;

        // Always show all badges even if count is 0
        return (
          <span
            key={config.key}
            data-testid={`issue-summary-${config.key}`}
            className={cn(
              'inline-flex items-center gap-1 font-medium whitespace-nowrap rounded-full border',
              sizeConfig[size],
              config.className
            )}
            aria-label={`${count} ${config.label} issues`}
          >
            <Icon 
              className={iconSizeConfig[size]} 
              aria-hidden="true" 
            />
            <span>{count} {config.label}</span>
          </span>
        );
      })}
    </div>
  );
}

export default IssueSummary;
