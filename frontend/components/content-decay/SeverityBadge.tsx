/**
 * SeverityBadge Component
 * Displays severity level with color-coded styling and icon indicator
 * 
 * @see TASK-36-11 for implementation requirements
 */

import * as React from "react";
import { AlertTriangle, AlertCircle, Info, Minus } from "lucide-react";
import { SEVERITY_CONFIG, type ContentDecaySeverity } from "@/lib/types/content-decay";
import { cn } from "@/lib/utils";

export interface SeverityBadgeProps {
  /** Severity level */
  severity: ContentDecaySeverity;
  /** Show icon only without text label */
  iconOnly?: boolean;
  /** Size variant */
  size?: "sm" | "md" | "lg";
  /** Additional CSS classes */
  className?: string;
}

const ICON_MAP = {
  critical: AlertTriangle,
  high: AlertCircle,
  medium: Info,
  low: Minus,
} as const;

const SIZE_CLASSES = {
  sm: "text-xs px-1.5 py-0.5 gap-1",
  md: "text-xs px-2 py-1 gap-1.5",
  lg: "text-sm px-2.5 py-1.5 gap-2",
} as const;

const ICON_SIZES = {
  sm: "h-3 w-3",
  md: "h-3.5 w-3.5",
  lg: "h-4 w-4",
} as const;

export function SeverityBadge({
  severity,
  iconOnly = false,
  size = "md",
  className,
}: SeverityBadgeProps) {
  // Fallback to low for invalid severity values
  const validSeverity: ContentDecaySeverity = 
    ["critical", "high", "medium", "low"].includes(severity) ? severity : "low";
  
  const config = SEVERITY_CONFIG[validSeverity];
  const Icon = ICON_MAP[validSeverity];
  
  return (
    <span
      role="status"
      aria-label={`Severity: ${config.label}`}
      className={cn(
        "inline-flex items-center rounded-md font-medium",
        config.colorClass,
        SIZE_CLASSES[size],
        className
      )}
    >
      <Icon className={ICON_SIZES[size]} aria-hidden="true" />
      {!iconOnly && <span>{config.label}</span>}
    </span>
  );
}
