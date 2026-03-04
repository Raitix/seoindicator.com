"use client";

import * as React from "react";
import Link from "next/link";
import { cn } from "@/lib/utils";
import {
  TrendingUp,
  TrendingDown,
  AlertTriangle,
  CheckCircle,
  Minus,
  Loader2,
  type LucideIcon,
} from "lucide-react";

// ============================================
// Types
// ============================================

export type IndicatorStatus = "good" | "warning" | "critical" | "neutral";

export interface IndicatorCardProps {
  /** Unique identifier for the indicator */
  id: string;
  /** Title displayed on the card */
  title: string;
  /** Main value to display */
  value: string | number;
  /** Optional description text */
  description?: string;
  /** Status indicator for visual styling */
  status: IndicatorStatus;
  /** Navigation URL when card is clicked */
  href?: string;
  /** Icon name from Lucide icons or React component */
  icon?: string | React.ComponentType<{ className?: string }>;
  /** Tooltip content for additional context */
  tooltip?: string;
  /** Click handler (alternative to href navigation) */
  onClick?: () => void;
  /** Disable the card (non-interactive) */
  disabled?: boolean;
  /** Show loading state */
  isLoading?: boolean;
  /** Show error state */
  error?: Error | null;
  /** Custom aria-label override */
  "aria-label"?: string;
  /** Additional CSS classes */
  className?: string;
  /** Badge count for notifications */
  badge?: string | number;
  /** Trend direction */
  trend?: "up" | "down" | "stable";
  /** Trend value */
  trendValue?: string;
  /** Category for filtering */
  category?: string;
}

// ============================================
// Status Configurations
// ============================================

const statusConfig: Record<
  IndicatorStatus,
  {
    color: string;
    bgColor: string;
    borderColor: string;
    icon: LucideIcon;
    label: string;
  }
> = {
  good: {
    color: "text-emerald-400",
    bgColor: "bg-emerald-500/10",
    borderColor: "border-emerald-500/20",
    icon: CheckCircle,
    label: "Good",
  },
  warning: {
    color: "text-amber-400",
    bgColor: "bg-amber-500/10",
    borderColor: "border-amber-500/20",
    icon: AlertTriangle,
    label: "Warning",
  },
  critical: {
    color: "text-red-400",
    bgColor: "bg-red-500/10",
    borderColor: "border-red-500/20",
    icon: AlertTriangle,
    label: "Critical",
  },
  neutral: {
    color: "text-slate-400",
    bgColor: "bg-slate-500/10",
    borderColor: "border-slate-500/20",
    icon: Minus,
    label: "Neutral",
  },
};

// ============================================
// Component
// ============================================

/**
 * IndicatorCard Component
 *
 * An interactive card displaying an SEO indicator with full accessibility support.
 * Supports keyboard navigation, ARIA labels, and screen reader compatibility.
 *
 * @example
 * ```tsx
 * <IndicatorCard
 *   id="rankings"
 *   title="Keyword Rankings"
 *   value={42}
 *   status="good"
 *   href="/dashboard/rankings"
 *   tooltip="Track your keyword positions in SERP"
 * />
 * ```
 */
export function IndicatorCard({
  id,
  title,
  value,
  description,
  status,
  href,
  icon: IconProp,
  tooltip,
  onClick,
  disabled = false,
  isLoading = false,
  error = null,
  "aria-label": ariaLabelOverride,
  className,
  badge,
  trend,
  trendValue,
  category,
}: IndicatorCardProps) {
  const config = statusConfig[status];
  const StatusIcon = config.icon;

  // Generate unique IDs for accessibility
  const cardId = `indicator-${id}`;
  const tooltipId = `${cardId}-tooltip`;
  const errorId = `${cardId}-error`;

  // Build aria-label with full context for screen readers
  const ariaLabel =
    ariaLabelOverride ??
    `${title}: ${value}. Status: ${config.label}.${description ? ` ${description}` : ""}${
      !disabled && (href || onClick) ? " Click to view details." : ""
    }`;

  // Determine if card is interactive
  const isInteractive = !disabled && (href || onClick);

  // Common props for the card element
  const cardProps = {
    className: cn(
      "group relative rounded-lg border p-4 transition-all duration-200",
      "bg-slate-900/50 backdrop-blur-sm",
      config.borderColor,
      disabled && "opacity-50 cursor-not-allowed",
      isInteractive && [
        "cursor-pointer",
        "hover:bg-slate-800/50",
        "focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2 focus-visible:ring-offset-slate-950",
        "active:scale-[0.98]",
      ],
      className
    ),
    "data-testid": cardId,
    "data-status": status,
    "data-loading": isLoading ? "true" : undefined,
    "data-error": error ? "true" : undefined,
    "data-disabled": disabled ? "true" : undefined,
  };

  // Handle keyboard interactions
  const handleKeyDown = (e: React.KeyboardEvent) => {
    if (!isInteractive) return;

    if (e.key === "Enter" || e.key === " ") {
      e.preventDefault();
      onClick?.();
    }
  };

  // Handle click
  const handleClick = () => {
    if (!isInteractive) return;
    onClick?.();
  };

  // Render content
  const renderContent = () => (
    <>
      {/* Header Row */}
      <div className="flex items-start justify-between gap-2 mb-3">
        <div className="flex items-center gap-2 min-w-0">
          {/* Icon */}
          {IconProp && (
            <div
              className={cn("flex-shrink-0", config.color)}
              aria-hidden="true"
            >
              {typeof IconProp === "string" ? (
                <span className="text-xl">{IconProp}</span>
              ) : (
                <IconProp className="w-5 h-5" />
              )}
            </div>
          )}
          {/* Title */}
          <h3 className="text-sm font-medium text-slate-200 truncate">
            {title}
          </h3>
        </div>

        {/* Status & Badge */}
        <div className="flex items-center gap-2 flex-shrink-0">
          {badge !== undefined && badge !== 0 && (
            <span className="inline-flex items-center justify-center min-w-[20px] h-5 px-1.5 text-xs font-bold text-white bg-blue-500 rounded-full">
              {typeof badge === "number" && badge > 99 ? "99+" : badge}
            </span>
          )}
          <StatusIcon
            className={cn("w-4 h-4", config.color)}
            aria-hidden="true"
          />
        </div>
      </div>

      {/* Value Row */}
      <div className="flex items-end justify-between gap-2">
        <div className="min-w-0">
          {/* Main Value */}
          <div className="flex items-baseline gap-2">
            <span
              className={cn(
                "text-2xl font-bold",
                isLoading ? "text-slate-500" : "text-white"
              )}
            >
              {isLoading ? "---" : value}
            </span>
            {trend && (
              <span
                className={cn(
                  "flex items-center text-xs font-medium",
                  trend === "up" && "text-emerald-400",
                  trend === "down" && "text-red-400",
                  trend === "stable" && "text-slate-400"
                )}
              >
                {trend === "up" && <TrendingUp className="w-3 h-3 mr-0.5" />}
                {trend === "down" && <TrendingDown className="w-3 h-3 mr-0.5" />}
                {trendValue}
              </span>
            )}
          </div>
          {/* Description */}
          {description && (
            <p className="text-xs text-slate-400 mt-1 truncate">{description}</p>
          )}
        </div>

        {/* Category Tag */}
        {category && (
          <span className="text-xs text-slate-500 bg-slate-800 px-2 py-0.5 rounded">
            {category}
          </span>
        )}
      </div>

      {/* Loading Overlay */}
      {isLoading && (
        <div
          className="absolute inset-0 flex items-center justify-center bg-slate-900/80 rounded-lg"
          aria-hidden="true"
        >
          <Loader2 className="w-6 h-6 text-blue-400 animate-spin" />
        </div>
      )}

      {/* Error Message */}
      {error && (
        <div
          id={errorId}
          role="alert"
          className="mt-2 text-xs text-red-400"
          data-testid={`${cardId}-error-message`}
        >
          {error.message}
        </div>
      )}

      {/* Tooltip (visually hidden, for screen readers) */}
      {tooltip && (
        <span id={tooltipId} className="sr-only">
          {tooltip}
        </span>
      )}

      {/* Interactive indicator (chevron) */}
      {isInteractive && (
        <div
          className="absolute bottom-2 right-2 opacity-0 group-hover:opacity-100 group-focus-visible:opacity-100 transition-opacity"
          aria-hidden="true"
        >
          <svg
            className="w-4 h-4 text-slate-400"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
          >
            <path
              strokeLinecap="round"
              strokeLinejoin="round"
              strokeWidth={2}
              d="M9 5l7 7-7 7"
            />
          </svg>
        </div>
      )}
    </>
  );

  // If href is provided, render as Link
  if (href && !disabled) {
    return (
      <Link
        href={href}
        {...cardProps}
        role="button"
        tabIndex={0}
        aria-label={ariaLabel}
        aria-disabled={disabled || undefined}
        aria-busy={!!isLoading}
        aria-describedby={tooltip ? tooltipId : error ? errorId : undefined}
        onClick={handleClick}
        onKeyDown={handleKeyDown}
      >
        {renderContent()}
      </Link>
    );
  }

  // Otherwise, render as interactive div
  return (
    <div
      {...cardProps}
      role={isInteractive ? "button" : undefined}
      tabIndex={isInteractive ? 0 : -1}
      aria-label={isInteractive ? ariaLabel : undefined}
      aria-disabled={disabled || undefined}
      aria-busy={!!isLoading}
      aria-describedby={tooltip ? tooltipId : error ? errorId : undefined}
      onClick={handleClick}
      onKeyDown={handleKeyDown}
    >
      {renderContent()}
    </div>
  );
}

export default IndicatorCard;
