"use client";

import * as React from "react";
import { cn } from "@/lib/utils";

// ==================== Types ====================

export type HealthStatus = "healthy" | "degraded" | "unhealthy" | "unknown";

export interface HealthBadgeProps {
  /**
   * Health status
   */
  status: HealthStatus | null;

  /**
   * Custom label (overrides default)
   */
  label?: string;

  /**
   * Show status icon
   */
  showIcon?: boolean;

  /**
   * Badge size
   */
  size?: "sm" | "md" | "lg";

  /**
   * Enable pulse animation
   */
  pulse?: boolean;

  /**
   * Click handler (makes badge clickable)
   */
  onClick?: () => void;

  /**
   * Additional CSS classes
   */
  className?: string;
}

// ==================== Helper Functions ====================

/**
 * Get status color class
 */
export function getStatusColorClass(status: HealthStatus | null): string {
  switch (status) {
    case "healthy":
      return "bg-green-100 text-green-800";
    case "degraded":
      return "bg-yellow-100 text-yellow-800";
    case "unhealthy":
      return "bg-red-100 text-red-800";
    case "unknown":
    default:
      return "bg-gray-100 text-gray-800";
  }
}

/**
 * Get default label for status
 */
export function getDefaultLabel(status: HealthStatus | null): string {
  switch (status) {
    case "healthy":
      return "Healthy";
    case "degraded":
      return "Degraded";
    case "unhealthy":
      return "Unhealthy";
    case "unknown":
    default:
      return "Unknown";
  }
}

/**
 * Get status icon
 */
export function getStatusIcon(status: HealthStatus | null): string {
  switch (status) {
    case "healthy":
      return "✓";
    case "degraded":
      return "⚠";
    case "unhealthy":
      return "✗";
    case "unknown":
    default:
      return "?";
  }
}

/**
 * Get size classes
 */
function getSizeClasses(size: "sm" | "md" | "lg"): string {
  switch (size) {
    case "sm":
      return "px-2 py-0.5 text-xs";
    case "lg":
      return "px-3 py-1.5 text-base";
    case "md":
    default:
      return "px-2.5 py-1 text-sm";
  }
}

// ==================== Component ====================

export function HealthBadge({
  status,
  label,
  showIcon = true,
  size = "md",
  pulse = false,
  onClick,
  className,
}: HealthBadgeProps) {
  const colorClass = getStatusColorClass(status);
  const sizeClass = getSizeClasses(size);
  const displayLabel = label || getDefaultLabel(status);
  const icon = getStatusIcon(status);

  const Component = onClick ? "button" : "span";

  return (
    <Component
      data-testid="health-badge"
      data-status={status || "unknown"}
      className={cn(
        "inline-flex items-center gap-1.5 rounded-full font-medium",
        colorClass,
        sizeClass,
        pulse && status === "degraded" && "animate-pulse",
        onClick && "cursor-pointer hover:opacity-80 transition-opacity",
        className
      )}
      onClick={onClick}
      role={onClick ? "button" : undefined}
      aria-label={`System status: ${displayLabel}`}
    >
      {showIcon && (
        <span aria-hidden="true" className="text-xs">
          {icon}
        </span>
      )}
      <span>{displayLabel}</span>
    </Component>
  );
}

// ==================== Exports ====================

export default HealthBadge;
