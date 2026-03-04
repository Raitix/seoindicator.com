"use client";

import * as React from "react";
import Link from "next/link";
import { cn } from "@/lib/utils";
import { TrendIndicator } from "./TrendIndicator";

// ==================== Types ====================

export type MetricFormat = "number" | "percent" | "currency";

export interface MetricCardProps {
  /**
   * Card title
   */
  title: string;

  /**
   * Metric value
   */
  value: number | null;

  /**
   * Trend value (positive = up, negative = down)
   */
  trend?: number | null;

  /**
   * Value format
   */
  format?: MetricFormat;

  /**
   * Link destination (makes card clickable)
   */
  href?: string;

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
   * Number of decimal places
   */
  precision?: number;

  /**
   * Accessibility label
   */
  "aria-label"?: string;

  /**
   * Additional CSS classes
   */
  className?: string;
}

// ==================== Helper Functions ====================

/**
 * Format number with K/M suffix for large values
 */
export function formatNumber(value: number, precision: number = 2): string {
  if (value >= 1000000) {
    return (value / 1000000).toFixed(precision) + "M";
  }
  if (value >= 1000) {
    return (value / 1000).toFixed(precision) + "K";
  }
  return value.toLocaleString();
}

/**
 * Format currency value
 */
export function formatCurrency(value: number): string {
  return new Intl.NumberFormat("en-US", {
    style: "currency",
    currency: "USD",
  }).format(value);
}

/**
 * Format percentage value
 */
export function formatPercent(value: number, precision: number = 1): string {
  return value.toFixed(precision) + "%";
}

/**
 * Format value based on format type
 */
export function formatValue(
  value: number,
  format: MetricFormat = "number",
  precision: number = 2
): string {
  switch (format) {
    case "currency":
      return formatCurrency(value);
    case "percent":
      return formatPercent(value, precision);
    case "number":
    default:
      return formatNumber(value, precision);
  }
}

// ==================== Loading Skeleton ====================

function LoadingSkeleton() {
  return (
    <div
      data-testid="metric-card-skeleton"
      role="status"
      aria-live="polite"
      className="animate-pulse space-y-3"
    >
      <div className="h-4 w-24 rounded bg-slate-700" />
      <div className="h-8 w-20 rounded bg-slate-700" />
      <div className="h-4 w-16 rounded bg-slate-700" />
    </div>
  );
}

// ==================== Error Display ====================

interface ErrorDisplayProps {
  message: string;
  onRetry?: () => void;
}

function ErrorDisplay({ message, onRetry }: ErrorDisplayProps) {
  return (
    <div
      data-testid="metric-card-error"
      role="alert"
      className="flex flex-col items-start gap-2"
    >
      <span className="text-sm text-red-400">{message}</span>
      {onRetry && (
        <button
          data-testid="metric-card-retry"
          onClick={(e) => {
            e.preventDefault();
            e.stopPropagation();
            onRetry();
          }}
          className="text-xs text-blue-400 hover:text-blue-300 underline"
        >
          Retry
        </button>
      )}
    </div>
  );
}

// ==================== Component ====================

export function MetricCard({
  title,
  value,
  trend,
  format = "number",
  href,
  isLoading = false,
  error = null,
  onRetry,
  precision = 2,
  "aria-label": ariaLabel,
  className,
}: MetricCardProps) {
  const cardContent = (
    <div
      data-testid="metric-card"
      className={cn(
        "rounded-lg bg-slate-800 p-4 transition-colors",
        href && "cursor-pointer hover:bg-slate-700",
        className
      )}
      role={href ? "link" : undefined}
      aria-label={ariaLabel || `${title}: ${value !== null ? formatValue(value, format, precision) : "N/A"}`}
      tabIndex={href ? 0 : undefined}
    >
      {isLoading ? (
        <LoadingSkeleton />
      ) : error ? (
        <>
          <div className="text-sm text-slate-400">{title}</div>
          <ErrorDisplay message={error} onRetry={onRetry} />
        </>
      ) : (
        <>
          <div className="text-sm text-slate-400">{title}</div>
          <div className="mt-1 flex items-baseline gap-2">
            <span className="text-2xl font-bold text-white">
              {value !== null ? formatValue(value, format, precision) : "−"}
            </span>
            {trend !== null && trend !== undefined && (
              <TrendIndicator value={trend} format="percent" />
            )}
          </div>
        </>
      )}
    </div>
  );

  if (href && !isLoading && !error) {
    return (
      <Link href={href} className="block focus:outline-none focus:ring-2 focus:ring-blue-500 rounded-lg">
        {cardContent}
      </Link>
    );
  }

  return cardContent;
}

// ==================== Exports ====================

export default MetricCard;
