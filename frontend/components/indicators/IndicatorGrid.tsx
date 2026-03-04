"use client";

import * as React from "react";
import { cn } from "@/lib/utils";
import {
  IndicatorCard,
  type IndicatorCardProps,
  type IndicatorStatus,
} from "./IndicatorCard";

// ============================================
// Types
// ============================================

export interface IndicatorData {
  id: string;
  title: string;
  value: string | number;
  description?: string;
  status: IndicatorStatus;
  href?: string;
  icon?: string | React.ComponentType<{ className?: string }>;
  tooltip?: string;
  disabled?: boolean;
  isLoading?: boolean;
  error?: Error | null;
  badge?: string | number;
  trend?: "up" | "down" | "stable";
  trendValue?: string;
  category?: string;
}

export interface IndicatorGridProps {
  /** Array of indicator data to display */
  indicators: IndicatorData[];
  /** Number of columns (default: responsive grid) */
  columns?: 1 | 2 | 3 | 4 | 5 | 6;
  /** Show loading skeleton state */
  isLoading?: boolean;
  /** Show error state */
  error?: Error | null;
  /** Empty state message */
  emptyMessage?: string;
  /** Click handler for indicator cards */
  onIndicatorClick?: (indicator: IndicatorData) => void;
  /** Additional CSS classes */
  className?: string;
  /** Aria label for the grid */
  "aria-label"?: string;
  /** Show category badges */
  showCategories?: boolean;
  /** Animation on mount */
  animate?: boolean;
}

// ============================================
// Skeleton Component
// ============================================

function IndicatorCardSkeleton() {
  return (
    <div
      className="rounded-lg border border-slate-700/50 p-4 bg-slate-900/50 animate-pulse"
      aria-hidden="true"
      data-testid="indicator-card-skeleton"
    >
      <div className="flex items-start justify-between gap-2 mb-3">
        <div className="flex items-center gap-2">
          <div className="w-5 h-5 bg-slate-700 rounded" />
          <div className="h-4 w-24 bg-slate-700 rounded" />
        </div>
        <div className="w-4 h-4 bg-slate-700 rounded" />
      </div>
      <div className="flex items-end justify-between">
        <div>
          <div className="h-7 w-16 bg-slate-700 rounded mb-1" />
          <div className="h-3 w-32 bg-slate-700/50 rounded" />
        </div>
      </div>
    </div>
  );
}

// ============================================
// Empty State Component
// ============================================

function EmptyState({
  message = "No indicators available",
}: {
  message?: string;
}) {
  return (
    <div
      role="status"
      className="flex flex-col items-center justify-center py-12 text-center"
      data-testid="indicator-grid-empty"
    >
      <div className="w-12 h-12 rounded-full bg-slate-800 flex items-center justify-center mb-4">
        <svg
          className="w-6 h-6 text-slate-500"
          fill="none"
          viewBox="0 0 24 24"
          stroke="currentColor"
          aria-hidden="true"
        >
          <path
            strokeLinecap="round"
            strokeLinejoin="round"
            strokeWidth={1.5}
            d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"
          />
        </svg>
      </div>
      <p className="text-sm text-slate-400">{message}</p>
    </div>
  );
}

// ============================================
// Error State Component
// ============================================

function ErrorState({
  error,
  onRetry,
}: {
  error: Error;
  onRetry?: () => void;
}) {
  return (
    <div
      role="alert"
      className="flex flex-col items-center justify-center py-12 text-center"
      data-testid="indicator-grid-error"
    >
      <div className="w-12 h-12 rounded-full bg-red-900/50 flex items-center justify-center mb-4">
        <svg
          className="w-6 h-6 text-red-400"
          fill="none"
          viewBox="0 0 24 24"
          stroke="currentColor"
          aria-hidden="true"
        >
          <path
            strokeLinecap="round"
            strokeLinejoin="round"
            strokeWidth={1.5}
            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
          />
        </svg>
      </div>
      <p className="text-sm text-red-400 mb-4">{error.message}</p>
      {onRetry && (
        <button
          onClick={onRetry}
          className="text-sm text-blue-400 hover:text-blue-300 transition-colors"
        >
          Try again
        </button>
      )}
    </div>
  );
}

// ============================================
// Component
// ============================================

/**
 * IndicatorGrid Component
 *
 * A responsive grid layout for displaying indicator cards with proper
 * accessibility support including ARIA roles and keyboard navigation.
 *
 * @example
 * ```tsx
 * <IndicatorGrid
 *   indicators={[
 *     { id: 'rankings', title: 'Rankings', value: 42, status: 'good' },
 *     { id: 'traffic', title: 'Traffic', value: '12K', status: 'warning' },
 *   ]}
 *   onIndicatorClick={(indicator) => console.log(indicator)}
 * />
 * ```
 */
export function IndicatorGrid({
  indicators,
  columns,
  isLoading = false,
  error = null,
  emptyMessage,
  onIndicatorClick,
  className,
  "aria-label": ariaLabel = "SEO Indicators",
  showCategories = false,
  animate = true,
}: IndicatorGridProps) {
  // Generate unique ID for the grid
  const gridId = React.useId();

  // Determine grid columns class
  const gridColumnsClass = columns
    ? {
        1: "grid-cols-1",
        2: "grid-cols-1 sm:grid-cols-2",
        3: "grid-cols-1 sm:grid-cols-2 lg:grid-cols-3",
        4: "grid-cols-1 sm:grid-cols-2 lg:grid-cols-4",
        5: "grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5",
        6: "grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6",
      }[columns]
    : "grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4";

  // State priority: Loading > Error > Empty > Content
  const renderContent = () => {
    // Loading state
    if (isLoading) {
      return (
        <>
          {Array.from({ length: 8 }).map((_, i) => (
            <IndicatorCardSkeleton key={`skeleton-${i}`} />
          ))}
        </>
      );
    }

    // Error state
    if (error) {
      return <ErrorState error={error} />;
    }

    // Empty state
    if (!indicators || indicators.length === 0) {
      return <EmptyState message={emptyMessage} />;
    }

    // Content
    return indicators.map((indicator, index) => (
      <div
        key={indicator.id}
        className={cn(animate && "animate-in fade-in-0 slide-in-from-bottom-4")}
        style={{ animationDelay: animate ? `${index * 50}ms` : undefined }}
      >
        <IndicatorCard
          {...indicator}
          onClick={() => onIndicatorClick?.(indicator)}
          category={showCategories ? indicator.category : undefined}
        />
      </div>
    ));
  };

  return (
    <div
      id={gridId}
      role="list"
      aria-label={ariaLabel}
      aria-busy={isLoading ? "true" : undefined}
      data-testid="indicator-grid"
      data-loading={isLoading ? "true" : undefined}
      data-error={error ? "true" : undefined}
      data-empty={!isLoading && !error && indicators?.length === 0 ? "true" : undefined}
      className={cn(
        "grid gap-4",
        gridColumnsClass,
        className
      )}
    >
      {renderContent()}
    </div>
  );
}

export default IndicatorGrid;
