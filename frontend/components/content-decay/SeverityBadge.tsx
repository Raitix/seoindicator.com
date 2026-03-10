"use client";

import * as React from "react";
import { AlertTriangle, AlertCircle, Info, Minus } from "lucide-react";
import { Badge } from "@/components/ui/badge";
import { cn } from "@/lib/utils";
import {
  ContentDecaySeverity,
  SEVERITY_CONFIG,
} from "@/lib/types/content-decay";

/**
 * Props for the SeverityBadge component
 */
export interface SeverityBadgeProps {
  /** The severity level to display */
  severity: ContentDecaySeverity;
  /** Optional additional CSS classes */
  className?: string;
  /** Whether to show only the icon without text */
  iconOnly?: boolean;
  /** Size variant */
  size?: "sm" | "md" | "lg";
}

/**
 * Icon mapping for severity levels
 */
const SEVERITY_ICONS: Record<ContentDecaySeverity, React.ElementType> = {
  critical: AlertTriangle,
  high: AlertCircle,
  medium: Info,
  low: Minus,
};

/**
 * Size configurations for the badge
 */
const SIZE_CONFIG = {
  sm: "text-xs px-1.5 py-0.5",
  md: "text-xs px-2 py-0.5",
  lg: "text-sm px-2.5 py-1",
};

/**
 * SeverityBadge displays a color-coded badge indicating the severity level
 * of a content decay candidate.
 *
 * @example
 * ```tsx
 * <SeverityBadge severity="critical" />
 * <SeverityBadge severity="high" size="lg" />
 * <SeverityBadge severity="medium" iconOnly />
 * ```
 */
export function SeverityBadge({
  severity,
  className,
  iconOnly = false,
  size = "md",
}: SeverityBadgeProps) {
  // Normalize severity to valid value
  const normalizedSeverity: ContentDecaySeverity = [
    "critical",
    "high",
    "medium",
    "low",
  ].includes(severity)
    ? severity
    : "low";

  const config = SEVERITY_CONFIG[normalizedSeverity];
  const Icon = SEVERITY_ICONS[normalizedSeverity];

  return (
    <Badge
      className={cn(
        config.colorClass,
        SIZE_CONFIG[size],
        "gap-1 font-medium",
        className
      )}
      role="status"
      aria-label={`Severity: ${config.label}`}
    >
      <Icon
        className={cn(
          size === "sm" ? "h-3 w-3" : size === "lg" ? "h-4 w-4" : "h-3.5 w-3.5"
        )}
        aria-hidden="true"
      />
      {!iconOnly && <span>{config.label}</span>}
    </Badge>
  );
}

export default SeverityBadge;
