"use client";

import * as React from "react";
import {
  Tooltip,
  TooltipContent,
  TooltipProvider,
  TooltipTrigger,
} from "@/components/ui/tooltip";
import { Badge } from "@/components/ui/badge";
import { cn } from "@/lib/utils";
import {
  DecayReason,
  ContentDecayReasonCode,
  DECAY_REASON_CONFIG,
} from "@/lib/types/content-decay";

/**
 * Props for the DecayReasonBadges component
 */
export interface DecayReasonBadgesProps {
  /** Array of decay reasons with evidence */
  reasons: DecayReason[];
  /** The primary reason code (will be highlighted) */
  primaryReason?: ContentDecayReasonCode;
  /** Optional additional CSS classes */
  className?: string;
  /** Maximum number of supporting badges to show */
  maxSupporting?: number;
  /** Size variant for badges */
  size?: "sm" | "md";
}

/**
 * DecayReasonBadges displays a primary reason badge with supporting reason badges,
 * each with tooltips showing evidence details.
 *
 * @example
 * ```tsx
 * const reasons = [
 *   { type: "clicks_down", evidence: "Down 45% vs last period" },
 *   { type: "impressions_down", evidence: "Impressions dropped 23%" }
 * ];
 *
 * <DecayReasonBadges
 *   reasons={reasons}
 *   primaryReason="clicks_down"
 * />
 * ```
 */
export function DecayReasonBadges({
  reasons,
  primaryReason,
  className,
  maxSupporting = 3,
  size = "md",
}: DecayReasonBadgesProps) {
  // Handle empty reasons
  if (!reasons || reasons.length === 0) {
    return null;
  }

  // Separate primary and supporting reasons
  const primaryReasons = primaryReason
    ? reasons.filter((r) => r.type === primaryReason)
    : reasons.slice(0, 1);
  const supportingReasons = primaryReason
    ? reasons.filter((r) => r.type !== primaryReason).slice(0, maxSupporting)
    : reasons.slice(1, maxSupporting + 1);

  const sizeClasses = size === "sm" ? "text-xs px-1.5 py-0.5" : "text-xs px-2 py-0.5";

  return (
    <TooltipProvider>
      <div className={cn("flex flex-wrap items-center gap-1.5", className)}>
        {/* Primary badge(s) */}
        {primaryReasons.map((reason, index) => (
          <ReasonBadge
            key={`primary-${reason.type}-${index}`}
            reason={reason}
            isPrimary
            size={size}
            sizeClasses={sizeClasses}
          />
        ))}

        {/* Supporting badges */}
        {supportingReasons.map((reason, index) => (
          <ReasonBadge
            key={`supporting-${reason.type}-${index}`}
            reason={reason}
            isPrimary={false}
            size={size}
            sizeClasses={sizeClasses}
          />
        ))}
      </div>
    </TooltipProvider>
  );
}

/**
 * Individual reason badge with tooltip
 */
interface ReasonBadgeProps {
  reason: DecayReason;
  isPrimary: boolean;
  size: "sm" | "md";
  sizeClasses: string;
}

function ReasonBadge({ reason, isPrimary, size, sizeClasses }: ReasonBadgeProps) {
  const config = DECAY_REASON_CONFIG[reason.type] || {
    label: reason.type.replace(/_/g, " "),
    colorClass: "bg-gray-100 text-gray-800 border-gray-200",
    description: reason.evidence,
  };

  const badge = (
    <Badge
      className={cn(
        config.colorClass,
        sizeClasses,
        "font-medium border transition-colors",
        isPrimary && "font-semibold ring-1 ring-offset-1 ring-offset-background",
        isPrimary && reason.type === "clicks_down" && "ring-red-300",
        isPrimary && reason.type === "impressions_down" && "ring-orange-300",
        isPrimary && reason.type === "ctr_down" && "ring-yellow-300",
        isPrimary && reason.type === "avg_position_down" && "ring-blue-300"
      )}
      role="status"
      aria-label={`${config.label}: ${reason.evidence}`}
    >
      {config.label}
    </Badge>
  );

  return (
    <Tooltip>
      <TooltipTrigger asChild>{badge}</TooltipTrigger>
      <TooltipContent
        side="top"
        className="max-w-xs text-sm"
        role="tooltip"
      >
        <div className="space-y-1">
          <p className="font-medium">{config.label}</p>
          <p className="text-muted-foreground text-xs">{reason.evidence}</p>
        </div>
      </TooltipContent>
    </Tooltip>
  );
}

export default DecayReasonBadges;
