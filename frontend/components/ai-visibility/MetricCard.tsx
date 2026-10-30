/**
 * TASK-34-10: MetricCard Component
 * 
 * Individual metric card for the AI Visibility dashboard.
 * Displays metric value with trend indicator and optional label.
 * 
 * @see PROJECT-SEOINDICATOR/TASKS/TASK-34-10.md
 */

'use client';

import * as React from 'react';
import { TrendingUp, TrendingDown, Minus, Info } from 'lucide-react';
import { cn } from '@/lib/utils';
import type { MetricCardProps, TrendDirection, SentimentType } from '@/lib/types/ai-visibility';

// ============================================
// Helper Functions
// ============================================

function formatValue(value: number | string, format: 'percentage' | 'decimal' | 'number'): string {
  if (typeof value === 'string') return value;
  
  switch (format) {
    case 'percentage':
      return `${value.toFixed(1)}%`;
    case 'decimal':
      return value.toFixed(2);
    default:
      return value.toString();
  }
}

function getTrendIcon(trend: TrendDirection) {
  switch (trend) {
    case 'up':
      return <TrendingUp className="h-4 w-4" data-testid="trending-up-icon" />;
    case 'down':
      return <TrendingDown className="h-4 w-4" data-testid="trending-down-icon" />;
    default:
      return <Minus className="h-4 w-4" data-testid="minus-icon" />;
  }
}

function getTrendColor(trend: TrendDirection): string {
  switch (trend) {
    case 'up':
      return 'text-emerald-400';
    case 'down':
      return 'text-red-400';
    default:
      return 'text-slate-400';
  }
}

function getSentimentColor(sentiment?: SentimentType): string {
  switch (sentiment) {
    case 'positive':
      return 'text-emerald-400';
    case 'negative':
      return 'text-red-400';
    case 'mixed':
      return 'text-yellow-400';
    case 'neutral':
      return 'text-slate-400';
    default:
      return 'text-blue-400';
  }
}

function getSentimentBgColor(sentiment?: SentimentType): string {
  switch (sentiment) {
    case 'positive':
      return 'bg-emerald-500/20';
    case 'negative':
      return 'bg-red-500/20';
    case 'mixed':
      return 'bg-yellow-500/20';
    case 'neutral':
      return 'bg-slate-500/20';
    default:
      return 'bg-blue-500/20';
  }
}

// ============================================
// Component
// ============================================

export function MetricCard({
  title,
  value,
  previousValue,
  change,
  trend = 'stable',
  format = 'number',
  sentiment,
  label,
  tooltip,
  className,
}: MetricCardProps) {
  const formattedValue = formatValue(value, format);
  const trendColor = getTrendColor(trend);
  const sentimentColor = getSentimentColor(sentiment);
  const sentimentBgColor = getSentimentBgColor(sentiment);

  return (
    <div
      data-testid="metric-card"
      data-trend={trend}
      data-sentiment={sentiment}
      className={cn(
        'rounded-lg bg-slate-800 p-6',
        className
      )}
    >
      {/* Header with title and tooltip */}
      <div className="flex items-center justify-between">
        <div
          data-testid="metric-title"
          className="text-sm font-medium text-slate-400"
        >
          {title}
        </div>
        {tooltip && (
          <span
            data-testid="tooltip-trigger"
            title={tooltip}
            className="cursor-help text-slate-500 hover:text-slate-400 transition-colors"
          >
            <Info className="h-4 w-4" data-testid="info-icon" />
          </span>
        )}
      </div>

      {/* Main value */}
      <div
        data-testid="metric-value"
        data-format={format}
        className="mt-2 text-3xl font-bold text-white"
      >
        {formattedValue}
      </div>

      {/* Trend indicator */}
      {change !== undefined && (
        <div
          data-testid="metric-change"
          data-direction={trend}
          className={cn('mt-2 flex items-center gap-1 text-sm', trendColor)}
        >
          {getTrendIcon(trend)}
          <span data-testid="change-value">
            {change > 0 ? '+' : ''}{change.toFixed(1)}%
          </span>
        </div>
      )}

      {/* Label (e.g., sentiment label) */}
      {label && (
        <div
          data-testid="metric-label"
          data-color={sentiment}
          className={cn(
            'mt-2 inline-flex items-center rounded-full px-2 py-1 text-xs font-medium',
            sentimentBgColor,
            sentimentColor
          )}
        >
          {label}
        </div>
      )}
    </div>
  );
}

export default MetricCard;
