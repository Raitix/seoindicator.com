/**
 * SERP Feature Badge Component
 *
 * Displays a collection of SERP feature icons as a badge.
 * Supports hover state to show feature names as a tooltip.
 *
 * @see TASK-7-16
 * @see TESTS/SPEC-7-16.md
 */

import React from "react";
import { cn } from "@/lib/utils";
import { SERPFeatureIcon, SERP_FEATURE_NAMES } from "./SERPFeatureIcon";
import type { SERPFeatureType, SERPFeature } from "@/lib/types/keywords";

// ============================================================================
// Types
// ============================================================================

export interface SERPFeatureBadgeProps {
  /** Array of SERP features to display */
  features: SERPFeature[];
  /** Size variant for icons */
  size?: "sm" | "md" | "lg";
  /** Maximum number of icons to show before "+N" indicator */
  maxVisible?: number;
  /** Show icons with background circles */
  showBackground?: boolean;
  /** Optional className for custom styling */
  className?: string;
  /** Compact mode - smaller spacing */
  compact?: boolean;
}

// ============================================================================
// Helper Functions
// ============================================================================

/**
 * Filters features to only those that are present
 */
function getPresentFeatures(features: SERPFeature[]): SERPFeature[] {
  return features.filter((f) => f.isPresent);
}

/**
 * Creates a comma-separated list of feature names for tooltip
 */
function createFeatureList(featureTypes: SERPFeatureType[]): string {
  return featureTypes
    .map((type) => SERP_FEATURE_NAMES[type] || type)
    .join(", ");
}

// ============================================================================
// Main Component
// ============================================================================

/**
 * SERPFeatureBadge Component
 *
 * Renders a collection of SERP feature icons in a compact badge format.
 * Shows a tooltip with all feature names on hover.
 *
 * @example
 * ```tsx
 * const features = [
 *   { type: 'featured_snippet', isPresent: true },
 *   { type: 'sitelinks', isPresent: true },
 *   { type: 'reviews', isPresent: false }
 * ];
 *
 * <SERPFeatureBadge features={features} />
 * <SERPFeatureBadge features={features} maxVisible={3} showBackground />
 * ```
 */
export const SERPFeatureBadge: React.FC<SERPFeatureBadgeProps> = ({
  features,
  size = "md",
  maxVisible = 5,
  showBackground = false,
  className,
  compact = false,
}) => {
  const presentFeatures = getPresentFeatures(features);
  const visibleFeatures = presentFeatures.slice(0, maxVisible);
  const hiddenCount = presentFeatures.length - maxVisible;
  const featureNames = createFeatureList(presentFeatures.map((f) => f.type));

  // If no features are present, render nothing
  if (presentFeatures.length === 0) {
    return null;
  }

  return (
    <div
      className={cn(
        "serp-feature-badge inline-flex items-center gap-1",
        compact ? "gap-0.5" : "gap-1.5",
        className
      )}
      title={featureNames}
      role="group"
      aria-label={`SERP Features: ${featureNames}`}
    >
      {visibleFeatures.map((feature, index) => (
        <SERPFeatureIcon
          key={`${feature.type}-${index}`}
          feature={feature.type}
          size={size}
          showBackground={showBackground}
        />
      ))}
      {hiddenCount > 0 && (
        <span
          className={cn(
            "inline-flex items-center justify-center rounded-full bg-slate-700/50 text-slate-300 font-medium",
            size === "sm" && "text-xs px-1.5 py-0.5",
            size === "md" && "text-sm px-2 py-0.5",
            size === "lg" && "text-sm px-2 py-1"
          )}
          title={`+${hiddenCount} more: ${createFeatureList(
            presentFeatures.slice(maxVisible).map((f) => f.type)
          )}`}
        >
          +{hiddenCount}
        </span>
      )}
    </div>
  );
};

export default SERPFeatureBadge;
