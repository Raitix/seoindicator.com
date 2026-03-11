/**
 * DecayReasonBadges Component
 * Displays primary and supporting decay reason badges with color coding
 * 
 * @see TASK-36-11 for implementation requirements
 */

import * as React from "react";
import {
  Tooltip,
  TooltipContent,
  TooltipProvider,
  TooltipTrigger,
} from "@/components/ui/tooltip";
import {
  DECAY_REASON_CONFIG,
  type DecayReason,
  type ContentDecayReasonCode,
} from "@/lib/types/content-decay";
import { cn } from "@/lib/utils";

export interface DecayReasonBadgesProps {
  /** Array of decay reasons with evidence */
  reasons: DecayReason[] | null | undefined;
  /** The primary reason code to highlight */
  primaryReason?: ContentDecayReasonCode | null;
  /** Maximum number of supporting badges to show */
  maxSupporting?: number;
  /** Size variant */
  size?: "sm" | "md";
  /** Additional CSS classes */
  className?: string;
}

const SIZE_CLASSES = {
  sm: "text-xs px-1.5 py-0.5",
  md: "text-xs px-2 py-1",
} as const;

function formatReasonLabel(type: ContentDecayReasonCode): string {
  const config = DECAY_REASON_CONFIG[type];
  return config?.label ?? type.replace(/_/g, " ");
}

function getReasonColorClass(type: ContentDecayReasonCode): string {
  const config = DECAY_REASON_CONFIG[type];
  return config?.colorClass ?? "bg-gray-100 text-gray-800 border-gray-200";
}

export function DecayReasonBadges({
  reasons,
  primaryReason,
  maxSupporting = 3,
  size = "md",
  className,
}: DecayReasonBadgesProps) {
  // Handle null/undefined/empty reasons
  if (!reasons || reasons.length === 0) {
    return null;
  }

  // Separate primary from supporting reasons
  const primaryReasons = primaryReason
    ? reasons.filter((r) => r.type === primaryReason)
    : reasons.slice(0, 1);
  
  const supportingReasons = primaryReason
    ? reasons.filter((r) => r.type !== primaryReason).slice(0, maxSupporting)
    : reasons.slice(1, maxSupporting + 1);

  return (
    <TooltipProvider>
      <div className={cn("flex flex-wrap gap-1.5", className)} data-testid="reason-badges-container">
        {primaryReasons.map((reason, index) => (
          <Tooltip key={`primary-${reason.type}-${index}`}>
            <TooltipTrigger asChild>
              <span
                role="status"
                aria-label={`${formatReasonLabel(reason.type)}: ${reason.evidence}`}
                className={cn(
                  "inline-flex items-center rounded-md border font-medium",
                  getReasonColorClass(reason.type),
                  SIZE_CLASSES[size],
                  "ring-1 ring-offset-1"
                )}
              >
                {formatReasonLabel(reason.type)}
              </span>
            </TooltipTrigger>
            <TooltipContent side="top" className="max-w-xs">
              <p className="text-sm">{reason.evidence}</p>
            </TooltipContent>
          </Tooltip>
        ))}
        
        {supportingReasons.map((reason, index) => (
          <Tooltip key={`supporting-${reason.type}-${index}`}>
            <TooltipTrigger asChild>
              <span
                role="status"
                aria-label={`${formatReasonLabel(reason.type)}: ${reason.evidence}`}
                className={cn(
                  "inline-flex items-center rounded-md border font-medium opacity-75",
                  getReasonColorClass(reason.type),
                  SIZE_CLASSES[size]
                )}
              >
                {formatReasonLabel(reason.type)}
              </span>
            </TooltipTrigger>
            <TooltipContent side="top" className="max-w-xs">
              <p className="text-sm">{reason.evidence}</p>
            </TooltipContent>
          </Tooltip>
        ))}
      </div>
    </TooltipProvider>
  );
}
