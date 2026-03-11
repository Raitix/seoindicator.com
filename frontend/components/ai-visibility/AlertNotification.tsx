'use client';

import React from 'react';
import { TrendingUp, TrendingDown, AlertTriangle, Meh, Check, Clock } from 'lucide-react';
import { cn } from '@/lib/utils';
import type { Alert, AlertType } from '@/lib/ai-visibility/types';
import { generateAlertMessage, generateRecommendations } from '@/lib/ai-visibility/types';

interface AlertNotificationProps {
  alert?: Alert;
  onClick?: (id: number) => void;
  onMarkRead?: (id: number) => void;
  onResolve?: (id: number) => void;
  isLoading?: boolean;
  variant?: 'default' | 'compact' | 'full';
  disabled?: boolean;
  isSelected?: boolean;
  className?: string;
}

const alertTypeConfig: Record<AlertType, {
  icon: React.ReactNode;
  borderClass: string;
  bgClass: string;
  textClass: string;
  label: string;
}> = {
  mention_spike: {
    icon: <TrendingUp className="h-4 w-4" />,
    borderClass: 'border-emerald-200',
    bgClass: 'bg-emerald-50 dark:bg-emerald-950',
    textClass: 'text-emerald-700 dark:text-emerald-300',
    label: 'Mention Spike',
  },
  mention_drop: {
    icon: <TrendingDown className="h-4 w-4" />,
    borderClass: 'border-rose-200',
    bgClass: 'bg-rose-50 dark:bg-rose-950',
    textClass: 'text-rose-700 dark:text-rose-300',
    label: 'Mention Drop',
  },
  competitor_gain: {
    icon: <AlertTriangle className="h-4 w-4" />,
    borderClass: 'border-amber-200',
    bgClass: 'bg-amber-50 dark:bg-amber-950',
    textClass: 'text-amber-700 dark:text-amber-300',
    label: 'Competitor Gain',
  },
  sentiment_change: {
    icon: <Meh className="h-4 w-4" />,
    borderClass: 'border-yellow-200',
    bgClass: 'bg-yellow-50 dark:bg-yellow-950',
    textClass: 'text-yellow-700 dark:text-yellow-300',
    label: 'Sentiment Change',
  },
};

function formatTimeAgo(dateString: string | null): string {
  if (!dateString) return 'Unknown';
  
  const date = new Date(dateString);
  const now = new Date();
  const diffMs = now.getTime() - date.getTime();
  
  // Handle future timestamps
  if (diffMs < 0) {
    return 'Just now';
  }
  
  const diffSeconds = Math.floor(diffMs / 1000);
  const diffMinutes = Math.floor(diffSeconds / 60);
  const diffHours = Math.floor(diffMinutes / 60);
  const diffDays = Math.floor(diffHours / 24);
  const diffWeeks = Math.floor(diffDays / 7);
  const diffMonths = Math.floor(diffDays / 30);
  const diffYears = Math.floor(diffDays / 365);

  if (diffYears > 0) {
    return `${diffYears} ${diffYears === 1 ? 'year' : 'years'} ago`;
  }
  if (diffMonths > 0) {
    return `${diffMonths} ${diffMonths === 1 ? 'month' : 'months'} ago`;
  }
  if (diffWeeks > 0) {
    return `${diffWeeks} ${diffWeeks === 1 ? 'week' : 'weeks'} ago`;
  }
  if (diffDays > 0) {
    return `${diffDays} ${diffDays === 1 ? 'day' : 'days'} ago`;
  }
  if (diffHours > 0) {
    return `${diffHours} ${diffHours === 1 ? 'hour' : 'hours'} ago`;
  }
  if (diffMinutes > 0) {
    return `${diffMinutes} ${diffMinutes === 1 ? 'minute' : 'minutes'} ago`;
  }
  return 'Just now';
}

function truncateText(text: string, maxLength: number): string {
  if (text.length <= maxLength) return text;
  return text.slice(0, maxLength - 3) + '...';
}

export function AlertNotification({
  alert,
  onClick,
  onMarkRead,
  onResolve,
  isLoading = false,
  variant = 'default',
  disabled = false,
  isSelected = false,
  className,
}: AlertNotificationProps) {
  // Loading skeleton
  if (isLoading || !alert) {
    return (
      <div
        data-testid="alert-skeleton"
        className={cn(
          'animate-pulse rounded-lg border border-slate-200 bg-slate-100 p-4',
          variant === 'compact' ? 'p-2' : 'p-4',
          className
        )}
      >
        <div className="flex items-start gap-3">
          <div className="h-8 w-8 rounded-full bg-slate-200" />
          <div className="flex-1 space-y-2">
            <div className="h-4 w-1/3 rounded bg-slate-200" />
            <div className="h-4 w-2/3 rounded bg-slate-200" />
          </div>
        </div>
      </div>
    );
  }

  const config = alertTypeConfig[alert.alert_type];
  const isMobile = typeof window !== 'undefined' && window.innerWidth < 640;

  // Generate message and recommendations from backend data
  const message = generateAlertMessage(alert);
  const recommendations = generateRecommendations(alert);

  const handleClick = (e: React.MouseEvent) => {
    if (disabled) return;
    onClick?.(alert.id);
  };

  const handleKeyDown = (e: React.KeyboardEvent) => {
    if (disabled) return;
    if (e.key === 'Enter' || e.key === ' ') {
      e.preventDefault();
      onClick?.(alert.id);
    }
  };

  const handleMarkRead = (e: React.MouseEvent) => {
    e.stopPropagation();
    if (!disabled) {
      onMarkRead?.(alert.id);
    }
  };

  const handleResolve = (e: React.MouseEvent) => {
    e.stopPropagation();
    if (!disabled) {
      onResolve?.(alert.id);
    }
  };

  // Use change_percent from backend (not percentage_change)
  const percentageChangeClass = 
    alert.change_percent > 0 
      ? 'text-emerald-600' 
      : alert.change_percent < 0 
        ? 'text-rose-600' 
        : 'text-slate-500';

  const percentagePrefix = alert.change_percent > 0 ? '+' : '';

  // Use triggered_at for time display, fallback to created_at
  const timeAgo = formatTimeAgo(alert.triggered_at || alert.created_at);

  return (
    <div
      data-testid="alert-notification"
      data-alert-id={alert.id}
      data-alert-type={alert.alert_type}
      role="button"
      tabIndex={disabled ? -1 : 0}
      aria-label={`${alert.project_name} - ${config.label} - ${alert.is_read ? 'read' : 'unread'}`}
      aria-disabled={disabled}
      onClick={handleClick}
      onKeyDown={handleKeyDown}
      className={cn(
        'relative rounded-lg border-2 transition-all',
        config.borderClass,
        config.bgClass,
        variant === 'compact' ? 'p-2' : 'p-4',
        isMobile && 'flex-col',
        disabled && 'opacity-50 cursor-not-allowed',
        isSelected && 'ring-2 ring-blue-500',
        !disabled && 'cursor-pointer hover:shadow-md',
        className
      )}
    >
      <div className={cn('flex items-start gap-3', isMobile && 'flex-col')}>
        {/* Icon */}
        <div className={cn('flex-shrink-0', config.textClass)}>
          {config.icon}
        </div>

        {/* Content */}
        <div className="flex-1 min-w-0">
          <div className="flex items-start justify-between gap-2">
            <div className="min-w-0">
              {/* Project Name */}
              <p
                data-testid="project-name"
                className={cn(
                  'text-sm font-medium text-slate-900 dark:text-slate-100 truncate',
                  variant === 'compact' ? 'text-xs' : 'text-sm'
                )}
              >
                {truncateText(alert.project_name, 30)}
              </p>

              {/* Message */}
              <p
                data-testid="alert-message"
                className={cn(
                  'text-slate-600 dark:text-slate-400 mt-0.5',
                  variant === 'compact' ? 'text-xs truncate' : 'text-sm'
                )}
              >
                {variant === 'compact' 
                  ? truncateText(message, 60) 
                  : truncateText(message, 150)}
              </p>
            </div>

            {/* Unread Indicator */}
            {!alert.is_read && (
              <div
                data-testid="unread-indicator"
                className="flex-shrink-0 h-2 w-2 rounded-full bg-blue-500"
              />
            )}
          </div>

          {/* Metadata Row */}
          <div className="flex items-center gap-3 mt-2 flex-wrap">
            {/* Time Ago */}
            <span className="flex items-center gap-1 text-xs text-slate-500">
              <Clock className="h-3 w-3" />
              {timeAgo}
            </span>

            {/* Percentage Change */}
            <span
              data-testid="percentage-change"
              className={cn('text-xs font-medium', percentageChangeClass)}
            >
              {percentagePrefix}{alert.change_percent.toFixed(1)}%
            </span>

            {/* Resolved Badge */}
            {alert.is_resolved && (
              <span className="inline-flex items-center gap-1 text-xs bg-slate-200 dark:bg-slate-700 text-slate-600 dark:text-slate-300 px-2 py-0.5 rounded">
                <Check className="h-3 w-3" />
                Resolved
              </span>
            )}
          </div>

          {/* Recommendations Preview (full variant only) */}
          {variant === 'full' && recommendations.length > 0 && (
            <div data-testid="recommendations-preview" className="mt-3 space-y-1">
              {recommendations.map((rec, i) => (
                <p key={i} className="text-xs text-slate-500 dark:text-slate-400">
                  • {rec}
                </p>
              ))}
            </div>
          )}

          {/* Actions */}
          {(onMarkRead || onResolve) && !alert.is_resolved && (
            <div className="flex items-center gap-2 mt-3">
              {onMarkRead && !alert.is_read && (
                <button
                  onClick={handleMarkRead}
                  className="text-xs text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-slate-200"
                >
                  Mark as Read
                </button>
              )}
              {onResolve && (
                <button
                  onClick={handleResolve}
                  className="text-xs text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-slate-200"
                >
                  Resolve
                </button>
              )}
            </div>
          )}
        </div>
      </div>
    </div>
  );
}
