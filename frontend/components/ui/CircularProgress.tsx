"use client";

import * as React from "react";
import { cn } from "@/lib/utils";

/**
 * CircularProgress Props Interface
 * Value: 0-100 for determinate, undefined for indeterminate
 * Size: sm (24px), md (40px), lg (56px)
 */
export interface CircularProgressProps {
  /** Progress value (0-100). Undefined for indeterminate mode. */
  value?: number;
  /** Size variant */
  size?: "sm" | "md" | "lg";
  /** Color variant using design system */
  color?: "primary" | "secondary" | "white";
  /** Additional CSS classes */
  className?: string;
}

/**
 * CircularProgress Component
 * A circular spinner for button loading states and inline loading indicators.
 * Supports both determinate (with value) and indeterminate (no value) modes.
 *
 * @example
 * // Determinate mode
 * <CircularProgress value={75} />
 *
 * // Small spinner for buttons
 * <CircularProgress size="sm" color="white" />
 *
 * // Large spinner
 * <CircularProgress size="lg" />
 */
const CircularProgress = React.forwardRef<HTMLDivElement, CircularProgressProps>(
  ({ value, size = "md", color = "primary", className }, ref) => {
    const isDeterminate = value !== undefined;
    const clampedValue = isDeterminate ? Math.min(100, Math.max(0, value)) : 0;

    // Size mapping in pixels
    const sizeMap: Record<string, number> = {
      sm: 24,
      md: 40,
      lg: 56,
    };

    const px = sizeMap[size];
    const strokeWidth = 4;
    const radius = (px - strokeWidth) / 2;
    const circumference = 2 * Math.PI * radius;
    const offset = isDeterminate
      ? circumference - (clampedValue / 100) * circumference
      : circumference * 0.25; // For indeterminate, show partial arc

    // Color mapping using Tailwind CSS classes
    const colorClasses: Record<string, string> = {
      primary: "text-primary",
      secondary: "text-secondary",
      white: "text-white",
    };

    // Track color (lighter/muted version)
    const trackColorClasses: Record<string, string> = {
      primary: "text-primary/20",
      secondary: "text-secondary/20",
      white: "text-white/20",
    };

    return (
      <div
        ref={ref}
        data-testid="circular-progress"
        role="progressbar"
        aria-valuenow={isDeterminate ? clampedValue : undefined}
        aria-valuemin={isDeterminate ? 0 : undefined}
        aria-valuemax={isDeterminate ? 100 : undefined}
        className={cn(
          "circular-progress",
          size,
          color,
          "inline-flex items-center justify-center",
          isDeterminate ? "determinate" : "indeterminate",
          className
        )}
      >
        <svg
          width={px}
          height={px}
          viewBox={`0 0 ${px} ${px}`}
          className={cn(
            isDeterminate ? "" : "animate-spin",
            colorClasses[color]
          )}
        >
          {/* Track circle (background) */}
          <circle
            data-testid="circular-progress-track"
            className={cn("circular-progress-track", trackColorClasses[color])}
            cx={px / 2}
            cy={px / 2}
            r={radius}
            fill="none"
            strokeWidth={strokeWidth}
            stroke="currentColor"
          />
          {/* Progress circle (foreground) */}
          <circle
            data-testid="circular-progress-bar"
            className="circular-progress-bar"
            cx={px / 2}
            cy={px / 2}
            r={radius}
            fill="none"
            strokeWidth={strokeWidth}
            stroke="currentColor"
            strokeDasharray={circumference}
            strokeDashoffset={offset}
            strokeLinecap="round"
            transform={`rotate(-90 ${px / 2} ${px / 2})`}
          />
        </svg>
      </div>
    );
  }
);

CircularProgress.displayName = "CircularProgress";

export { CircularProgress };
