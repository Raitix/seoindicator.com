"use client";

import * as React from "react";
import { cn } from "@/lib/utils";

/**
 * LinearProgress Props Interface
 * Value: 0-100 for determinate, undefined for indeterminate
 */
export interface LinearProgressProps {
  /** Progress value (0-100). Undefined for indeterminate mode. */
  value?: number;
  /** Color variant using design system */
  color?: "primary" | "secondary" | "success" | "warning" | "error";
  /** Additional CSS classes */
  className?: string;
}

/**
 * LinearProgress Component
 * A horizontal progress bar for top-of-page loading states.
 * Supports both determinate (with value) and indeterminate (no value) modes.
 *
 * @example
 * // Determinate mode
 * <LinearProgress value={75} />
 *
 * // Indeterminate mode
 * <LinearProgress />
 *
 * // With custom color
 * <LinearProgress value={50} color="success" />
 */
const LinearProgress = React.forwardRef<HTMLDivElement, LinearProgressProps>(
  ({ value, color = "primary", className }, ref) => {
    const isDeterminate = value !== undefined;
    const clampedValue = isDeterminate ? Math.min(100, Math.max(0, value)) : 0;

    // Color mapping using Tailwind CSS classes
    const colorClasses: Record<string, string> = {
      primary: "bg-primary",
      secondary: "bg-secondary",
      success: "bg-success",
      warning: "bg-warning",
      error: "bg-error",
    };

    // Track background colors (lighter versions)
    const trackColorClasses: Record<string, string> = {
      primary: "bg-primary/20",
      secondary: "bg-secondary/20",
      success: "bg-success/20",
      warning: "bg-warning/20",
      error: "bg-error/20",
    };

    return (
      <div
        ref={ref}
        data-testid="linear-progress"
        role="progressbar"
        aria-valuenow={isDeterminate ? clampedValue : undefined}
        aria-valuemin={isDeterminate ? 0 : undefined}
        aria-valuemax={isDeterminate ? 100 : undefined}
        className={cn(
          "linear-progress",
          color,
          "relative h-1 w-full overflow-hidden rounded-full",
          trackColorClasses[color],
          isDeterminate ? "determinate" : "indeterminate",
          className
        )}
      >
        {isDeterminate ? (
          <div
            data-testid="linear-progress-bar"
            className={cn(
              "linear-progress-bar",
              "h-full transition-all duration-300 ease-out rounded-full",
              colorClasses[color]
            )}
            style={{ width: `${clampedValue}%` }}
          />
        ) : (
          <div
            data-testid="linear-progress-bar"
            className={cn(
              "linear-progress-bar",
              "h-full w-1/3 animate-linear-indeterminate rounded-full",
              colorClasses[color]
            )}
          />
        )}
      </div>
    );
  }
);

LinearProgress.displayName = "LinearProgress";

export { LinearProgress };
