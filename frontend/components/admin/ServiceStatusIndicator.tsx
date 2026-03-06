"use client";

import * as React from "react";
import { cn } from "@/lib/utils";
import { HealthBadge, type HealthStatus } from "./HealthBadge";

// ==================== Types ====================

export interface ServiceStatus {
  name: string;
  status: HealthStatus;
  latency?: number;
  message?: string;
  lastCheck: string;
}

export interface ServiceStatusIndicatorProps {
  service: ServiceStatus;
  showLatency?: boolean;
  showLastCheck?: boolean;
  pulse?: boolean;
  onClick?: () => void;
  isLoading?: boolean;
  error?: string | null;
  onRetry?: () => void;
  "aria-label"?: string;
  className?: string;
}

// ==================== Helper Functions ====================

/**
 * Get status icon character
 */
export function getStatusIcon(status: HealthStatus): string {
  switch (status) {
    case "healthy":
      return "●";
    case "degraded":
      return "◐";
    case "unhealthy":
      return "○";
    default:
      return "?";
  }
}

/**
 * Get status label
 */
export function getStatusLabel(status: HealthStatus): string {
  switch (status) {
    case "healthy":
      return "Operational";
    case "degraded":
      return "Degraded";
    case "unhealthy":
      return "Down";
    default:
      return "Unknown";
  }
}

/**
 * Format latency display
 */
export function formatLatency(latency: number): string {
  if (latency < 1000) return `${latency}ms`;
  return `${(latency / 1000).toFixed(1)}s`;
}

/**
 * Get relative time
 */
export function getRelativeTime(isoString: string): string {
  const now = new Date();
  const date = new Date(isoString);
  const diffMs = now.getTime() - date.getTime();
  const diffSec = Math.floor(diffMs / 1000);

  if (diffSec < 60) return "Just now";
  if (diffSec < 3600) return `${Math.floor(diffSec / 60)}m ago`;
  if (diffSec < 86400) return `${Math.floor(diffSec / 3600)}h ago`;
  return `${Math.floor(diffSec / 86400)}d ago`;
}

/**
 * Get status color classes
 */
export function getStatusClasses(status: HealthStatus): {
  bg: string;
  text: string;
  icon: string;
} {
  switch (status) {
    case "healthy":
      return {
        bg: "bg-green-900/20",
        text: "text-green-400",
        icon: "text-green-500",
      };
    case "degraded":
      return {
        bg: "bg-yellow-900/20",
        text: "text-yellow-400",
        icon: "text-yellow-500",
      };
    case "unhealthy":
      return {
        bg: "bg-red-900/20",
        text: "text-red-400",
        icon: "text-red-500",
      };
    default:
      return {
        bg: "bg-gray-900/20",
        text: "text-gray-400",
        icon: "text-gray-500",
      };
  }
}

// ==================== Loading Skeleton ====================

function LoadingSkeleton() {
  return (
    <div
      data-testid="service-status-skeleton"
      role="status"
      aria-live="polite"
      className="animate-pulse space-y-2 p-4"
    >
      <div className="h-4 w-32 rounded bg-slate-700" />
      <div className="h-3 w-24 rounded bg-slate-700" />
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
      data-testid="service-status-error"
      role="alert"
      className="flex flex-col items-start gap-2 p-4"
    >
      <span className="text-sm text-red-400">{message}</span>
      {onRetry && (
        <button
          data-testid="service-status-retry"
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

export function ServiceStatusIndicator({
  service,
  showLatency = true,
  showLastCheck = true,
  pulse = false,
  onClick,
  isLoading = false,
  error = null,
  onRetry,
  "aria-label": ariaLabel,
  className,
}: ServiceStatusIndicatorProps) {
  const classes = getStatusClasses(service.status);
  const statusLabel = getStatusLabel(service.status);
  const icon = getStatusIcon(service.status);

  const handleKeyDown = React.useCallback(
    (e: React.KeyboardEvent) => {
      if (onClick && (e.key === "Enter" || e.key === " ")) {
        e.preventDefault();
        onClick();
      }
    },
    [onClick]
  );

  if (isLoading) {
    return (
      <div
        className={cn(
          "rounded-lg bg-slate-800 border border-slate-700",
          className
        )}
      >
        <LoadingSkeleton />
      </div>
    );
  }

  if (error) {
    return (
      <div
        className={cn(
          "rounded-lg bg-slate-800 border border-slate-700",
          className
        )}
      >
        <div className="p-4">
          <div className="text-sm text-slate-400">{service.name}</div>
        </div>
        <ErrorDisplay message={error} onRetry={onRetry} />
      </div>
    );
  }

  const Component = onClick ? "button" : "div";

  return (
    <Component
      data-testid="service-status-indicator"
      data-status={service.status}
      className={cn(
        "rounded-lg bg-slate-800 border border-slate-700 p-4 transition-colors",
        onClick && "cursor-pointer hover:bg-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500",
        pulse && service.status === "unhealthy" && "animate-pulse",
        className
      )}
      onClick={onClick}
      onKeyDown={handleKeyDown}
      role={onClick ? "button" : undefined}
      tabIndex={onClick ? 0 : undefined}
      aria-label={ariaLabel || `${service.name}: ${statusLabel}`}
    >
      <div className="flex items-center justify-between gap-4">
        {/* Service Name */}
        <div className="flex-1 min-w-0">
          <div className="text-sm font-medium text-white truncate">
            {service.name}
          </div>
          {service.message && (
            <div className="text-xs text-slate-400 mt-0.5 truncate">
              {service.message}
            </div>
          )}
        </div>

        {/* Status & Latency */}
        <div className="flex items-center gap-3 flex-shrink-0">
          {showLatency && service.latency !== undefined && (
            <span
              data-testid="service-latency"
              className="text-xs text-slate-400"
            >
              {formatLatency(service.latency)}
            </span>
          )}
          <div className="flex items-center gap-2">
            <span
              aria-hidden="true"
              className={cn("text-sm", classes.icon)}
            >
              {icon}
            </span>
            <span className={cn("text-sm font-medium", classes.text)}>
              {statusLabel}
            </span>
          </div>
        </div>
      </div>

      {/* Last Check */}
      {showLastCheck && (
        <div className="mt-2 text-xs text-slate-500">
          Last check: {getRelativeTime(service.lastCheck)}
        </div>
      )}
    </Component>
  );
}

// ==================== Exports ====================

export default ServiceStatusIndicator;
