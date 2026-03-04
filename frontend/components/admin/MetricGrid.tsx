"use client";

import * as React from "react";
import { cn } from "@/lib/utils";
import { MetricCard, MetricFormat } from "./MetricCard";

// ==================== Types ====================

export interface MetricCardData {
  id: string;
  title: string;
  value: number | null;
  trend?: number | null;
  format?: MetricFormat;
  href?: string;
}

export interface MetricGridProps {
  /**
   * Array of metric card data
   */
  cards: MetricCardData[];

  /**
   * Auto-refresh interval in milliseconds (0 = disabled)
   */
  refreshInterval?: number;

  /**
   * Manual refresh callback
   */
  onRefresh?: () => void;

  /**
   * Last updated timestamp
   */
  lastUpdated?: Date | null;

  /**
   * Loading state
   */
  isLoading?: boolean;

  /**
   * Error message
   */
  error?: string | null;

  /**
   * Retry callback
   */
  onRetry?: () => void;

  /**
   * Additional CSS classes
   */
  className?: string;
}

// ==================== Helper Functions ====================

/**
 * Format relative time
 */
function formatRelativeTime(date: Date): string {
  const now = new Date();
  const diffMs = now.getTime() - date.getTime();
  const diffSec = Math.floor(diffMs / 1000);
  const diffMin = Math.floor(diffSec / 60);
  const diffHour = Math.floor(diffMin / 60);

  if (diffSec < 60) {
    return "just now";
  }
  if (diffMin < 60) {
    return `${diffMin} minute${diffMin > 1 ? "s" : ""} ago`;
  }
  if (diffHour < 24) {
    return `${diffHour} hour${diffHour > 1 ? "s" : ""} ago`;
  }
  return date.toLocaleDateString();
}

// ==================== Component ====================

export function MetricGrid({
  cards,
  refreshInterval = 30000,
  onRefresh,
  lastUpdated,
  isLoading = false,
  error = null,
  onRetry,
  className,
}: MetricGridProps) {
  const [countdown, setCountdown] = React.useState(refreshInterval / 1000);

  // Countdown timer for auto-refresh
  React.useEffect(() => {
    if (refreshInterval <= 0) return;

    const interval = setInterval(() => {
      setCountdown((prev) => {
        if (prev <= 1) {
          return refreshInterval / 1000;
        }
        return prev - 1;
      });
    }, 1000);

    return () => clearInterval(interval);
  }, [refreshInterval]);

  // Reset countdown on manual refresh
  const handleRefresh = React.useCallback(() => {
    setCountdown(refreshInterval / 1000);
    onRefresh?.();
  }, [onRefresh, refreshInterval]);

  // Empty state
  if (cards.length === 0 && !isLoading) {
    return (
      <div
        data-testid="metric-grid-empty"
        className="flex flex-col items-center justify-center rounded-lg bg-slate-800 p-8 text-center"
      >
        <div className="text-4xl mb-4">📊</div>
        <p className="text-slate-400">No data available</p>
        <p className="text-sm text-slate-500 mt-1">Check back later for metrics</p>
      </div>
    );
  }

  return (
    <div
      data-testid="metric-grid"
      role="region"
      aria-label="Dashboard metrics"
      className={className}
    >
      {/* Header with refresh controls */}
      <div className="mb-4 flex items-center justify-between">
        <div className="flex items-center gap-4">
          {lastUpdated && (
            <span className="text-sm text-slate-500">
              Last updated {formatRelativeTime(lastUpdated)}
            </span>
          )}
          {refreshInterval > 0 && !isLoading && (
            <span className="text-xs text-slate-600">
              Refreshing in {countdown}s
            </span>
          )}
        </div>

        {onRefresh && (
          <button
            data-testid="metric-grid-refresh"
            onClick={handleRefresh}
            disabled={isLoading}
            className={cn(
              "flex items-center gap-2 rounded-lg px-3 py-1.5 text-sm font-medium",
              "bg-slate-700 text-slate-300 hover:bg-slate-600",
              "disabled:opacity-50 disabled:cursor-not-allowed",
              "transition-colors"
            )}
          >
            {isLoading ? (
              <>
                <span className="h-4 w-4 animate-spin rounded-full border-2 border-slate-500 border-t-white" />
                <span>Loading...</span>
              </>
            ) : (
              <>
                <span>↻</span>
                <span>Refresh</span>
              </>
            )}
          </button>
        )}
      </div>

      {/* Error state */}
      {error && (
        <div
          data-testid="metric-grid-error"
          role="alert"
          className="mb-4 flex items-center justify-between rounded-lg bg-red-900/20 px-4 py-3"
        >
          <span className="text-sm text-red-400">{error}</span>
          {onRetry && (
            <button
              onClick={onRetry}
              className="text-sm text-red-300 hover:text-red-200 underline"
            >
              Retry
            </button>
          )}
        </div>
      )}

      {/* Grid of metric cards */}
      <div
        data-testid="metric-grid-cards"
        className={cn(
          "grid gap-4",
          "grid-cols-1",
          "sm:grid-cols-2",
          "lg:grid-cols-3",
          "xl:grid-cols-4"
        )}
      >
        {cards.map((card) => (
          <MetricCard
            key={card.id}
            title={card.title}
            value={card.value}
            trend={card.trend}
            format={card.format}
            href={card.href}
            isLoading={isLoading}
            aria-label={`${card.title} metric`}
          />
        ))}
      </div>
    </div>
  );
}

// ==================== Exports ====================

export default MetricGrid;
