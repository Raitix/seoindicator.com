"use client";

import * as React from "react";
import { cn } from "@/lib/utils";

// ==================== Types ====================

export interface TrendIndicatorProps {
  /**
   * Trend value (positive = up, negative = down, 0 = neutral)
   */
  value: number | null | undefined;

  /**
   * Format for display
   * - 'percent': Shows value with % suffix (default)
   * - 'number': Shows value without %
   */
  format?: "percent" | "number";

  /**
   * Custom color for positive trend (Tailwind class or hex)
   */
  positiveColor?: string;

  /**
   * Custom color for negative trend (Tailwind class or hex)
   */
  negativeColor?: string;

  /**
   * Show arrow icon
   */
  showIcon?: boolean;

  /**
   * Additional CSS classes
   */
  className?: string;

  /**
   * Accessibility description
   */
  "aria-describedby"?: string;
}

// ==================== Helper Functions ====================

/**
 * Get trend direction from value
 */
export function getTrendDirection(
  value: number | null | undefined
): "up" | "down" | "neutral" | null {
  if (value === null || value === undefined || isNaN(value)) return null;
  if (value > 0) return "up";
  if (value < 0) return "down";
  return "neutral";
}

/**
 * Get trend color class
 */
export function getTrendColorClass(
  direction: "up" | "down" | "neutral" | null,
  positiveColor?: string,
  negativeColor?: string
): string {
  switch (direction) {
    case "up":
      return positiveColor || "text-green-600";
    case "down":
      return negativeColor || "text-red-600";
    case "neutral":
      return "text-gray-500";
    default:
      return "";
  }
}

/**
 * Format trend value for display
 */
export function formatTrendValue(
  value: number,
  format: "percent" | "number" = "percent"
): string {
  const prefix = value >= 0 ? "+" : "";
  const suffix = format === "percent" ? "%" : "";
  return `${prefix}${value}${suffix}`;
}

// ==================== Component ====================

export function TrendIndicator({
  value,
  format = "percent",
  positiveColor,
  negativeColor,
  showIcon = true,
  className,
  "aria-describedby": ariaDescribedBy,
}: TrendIndicatorProps) {
  const direction = getTrendDirection(value);
  const colorClass = getTrendColorClass(direction, positiveColor, negativeColor);

  // Don't render if no value
  if (direction === null) {
    return null;
  }

  const displayValue = formatTrendValue(value!, format);

  return (
    <span
      data-testid="trend-indicator"
      data-direction={direction}
      className={cn(
        "inline-flex items-center gap-1 text-sm font-medium",
        colorClass,
        className
      )}
      aria-describedby={ariaDescribedBy}
    >
      {showIcon && (
        <span aria-hidden="true" className="transition-transform duration-200">
          {direction === "up" && "↑"}
          {direction === "down" && "↓"}
          {direction === "neutral" && "−"}
        </span>
      )}
      <span>{displayValue}</span>
    </span>
  );
}

// ==================== Exports ====================

export default TrendIndicator;
