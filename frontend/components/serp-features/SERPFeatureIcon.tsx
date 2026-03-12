/**
 * SERP Feature Icon Component
 *
 * Displays an icon for a specific SERP feature type.
 * Uses lightweight SVG icons for visual representation.
 *
 * @see TASK-7-16
 * @see TESTS/SPEC-7-16.md
 */

import React from "react";
import { cn } from "@/lib/utils";
import type { SERPFeatureType } from "@/lib/types/keywords";

// ============================================================================
// Types
// ============================================================================

export interface SERPFeatureIconProps {
  /** The SERP feature type to display */
  feature: SERPFeatureType;
  /** Size variant */
  size?: "sm" | "md" | "lg";
  /** Optional className for custom styling */
  className?: string;
  /** Show the icon with a background circle */
  showBackground?: boolean;
}

// ============================================================================
// SVG Icon Components
// ============================================================================

/**
 * Featured Snippet Icon - Trophy/Award style
 */
const FeaturedSnippetIcon: React.FC<{ className?: string }> = ({ className }) => (
  <svg
    viewBox="0 0 24 24"
    fill="none"
    stroke="currentColor"
    strokeWidth="2"
    strokeLinecap="round"
    strokeLinejoin="round"
    className={className}
    aria-hidden="true"
  >
    <path d="M6 9H4.5a2.5 2.5 0 0 1 0-5H6" />
    <path d="M18 9h1.5a2.5 2.5 0 0 0 0-5H18" />
    <path d="M4 22h16" />
    <path d="M10 14.66V17c0 .55-.47.98-.97 1.21C7.85 18.75 7 20.24 7 22" />
    <path d="M14 14.66V17c0 .55.47.98.97 1.21C16.15 18.75 17 20.24 17 22" />
    <path d="M18 2H6v7a6 6 0 0 0 12 0V2Z" />
  </svg>
);

/**
 * Local Pack Icon - Map pin style
 */
const LocalPackIcon: React.FC<{ className?: string }> = ({ className }) => (
  <svg
    viewBox="0 0 24 24"
    fill="none"
    stroke="currentColor"
    strokeWidth="2"
    strokeLinecap="round"
    strokeLinejoin="round"
    className={className}
    aria-hidden="true"
  >
    <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z" />
    <circle cx="12" cy="10" r="3" />
  </svg>
);

/**
 * Image Pack Icon - Image grid style
 */
const ImagePackIcon: React.FC<{ className?: string }> = ({ className }) => (
  <svg
    viewBox="0 0 24 24"
    fill="none"
    stroke="currentColor"
    strokeWidth="2"
    strokeLinecap="round"
    strokeLinejoin="round"
    className={className}
    aria-hidden="true"
  >
    <rect x="3" y="3" width="18" height="18" rx="2" ry="2" />
    <circle cx="9" cy="9" r="2" />
    <path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21" />
  </svg>
);

/**
 * Video Icon - Play button style
 */
const VideoIcon: React.FC<{ className?: string }> = ({ className }) => (
  <svg
    viewBox="0 0 24 24"
    fill="none"
    stroke="currentColor"
    strokeWidth="2"
    strokeLinecap="round"
    strokeLinejoin="round"
    className={className}
    aria-hidden="true"
  >
    <rect x="2" y="4" width="20" height="16" rx="2" ry="2" />
    <polygon points="10 9 15 12 10 15 10 9" />
  </svg>
);

/**
 * People Also Ask Icon - Question mark style
 */
const PeopleAlsoAskIcon: React.FC<{ className?: string }> = ({ className }) => (
  <svg
    viewBox="0 0 24 24"
    fill="none"
    stroke="currentColor"
    strokeWidth="2"
    strokeLinecap="round"
    strokeLinejoin="round"
    className={className}
    aria-hidden="true"
  >
    <circle cx="12" cy="12" r="10" />
    <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3" />
    <path d="M12 17h.01" />
  </svg>
);

/**
 * Knowledge Panel Icon - Info card style
 */
const KnowledgePanelIcon: React.FC<{ className?: string }> = ({ className }) => (
  <svg
    viewBox="0 0 24 24"
    fill="none"
    stroke="currentColor"
    strokeWidth="2"
    strokeLinecap="round"
    strokeLinejoin="round"
    className={className}
    aria-hidden="true"
  >
    <rect x="3" y="3" width="18" height="18" rx="2" ry="2" />
    <path d="M3 9h18" />
    <path d="M9 21V9" />
  </svg>
);

/**
 * Sitelinks Icon - Link chain style
 */
const SitelinksIcon: React.FC<{ className?: string }> = ({ className }) => (
  <svg
    viewBox="0 0 24 24"
    fill="none"
    stroke="currentColor"
    strokeWidth="2"
    strokeLinecap="round"
    strokeLinejoin="round"
    className={className}
    aria-hidden="true"
  >
    <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71" />
    <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71" />
  </svg>
);

/**
 * Reviews Icon - Star style
 */
const ReviewsIcon: React.FC<{ className?: string }> = ({ className }) => (
  <svg
    viewBox="0 0 24 24"
    fill="none"
    stroke="currentColor"
    strokeWidth="2"
    strokeLinecap="round"
    strokeLinejoin="round"
    className={className}
    aria-hidden="true"
  >
    <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2" />
  </svg>
);

/**
 * FAQ Icon - List/document style
 */
const FAQIcon: React.FC<{ className?: string }> = ({ className }) => (
  <svg
    viewBox="0 0 24 24"
    fill="none"
    stroke="currentColor"
    strokeWidth="2"
    strokeLinecap="round"
    strokeLinejoin="round"
    className={className}
    aria-hidden="true"
  >
    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
    <polyline points="14 2 14 8 20 8" />
    <line x1="16" y1="13" x2="8" y2="13" />
    <line x1="16" y1="17" x2="8" y2="17" />
    <polyline points="10 9 9 9 8 9" />
  </svg>
);

/**
 * Product Icon - Shopping bag style
 */
const ProductIcon: React.FC<{ className?: string }> = ({ className }) => (
  <svg
    viewBox="0 0 24 24"
    fill="none"
    stroke="currentColor"
    strokeWidth="2"
    strokeLinecap="round"
    strokeLinejoin="round"
    className={className}
    aria-hidden="true"
  >
    <path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z" />
    <path d="M3 6h18" />
    <path d="M16 10a4 4 0 0 1-8 0" />
  </svg>
);

/**
 * Fallback Icon - Default chart/indicator style
 */
const FallbackIcon: React.FC<{ className?: string }> = ({ className }) => (
  <svg
    viewBox="0 0 24 24"
    fill="none"
    stroke="currentColor"
    strokeWidth="2"
    strokeLinecap="round"
    strokeLinejoin="round"
    className={className}
    aria-hidden="true"
  >
    <path d="M3 3v18h18" />
    <path d="M18 17V9" />
    <path d="M13 17V5" />
    <path d="M8 17v-3" />
  </svg>
);

// ============================================================================
// Icon Registry
// ============================================================================

const ICON_MAP: Record<SERPFeatureType, React.FC<{ className?: string }>> = {
  featured_snippet: FeaturedSnippetIcon,
  local_pack: LocalPackIcon,
  image_pack: ImagePackIcon,
  video: VideoIcon,
  people_also_ask: PeopleAlsoAskIcon,
  knowledge_panel: KnowledgePanelIcon,
  sitelinks: SitelinksIcon,
  reviews: ReviewsIcon,
  faq: FAQIcon,
  product: ProductIcon,
};

/**
 * Feature display names for accessibility
 */
export const SERP_FEATURE_NAMES: Record<SERPFeatureType, string> = {
  featured_snippet: "Featured Snippet",
  local_pack: "Local Pack",
  image_pack: "Image Pack",
  video: "Video Result",
  people_also_ask: "People Also Ask",
  knowledge_panel: "Knowledge Panel",
  sitelinks: "Sitelinks",
  reviews: "Reviews/Ratings",
  faq: "FAQ Rich Result",
  product: "Product Listing",
};

/**
 * Size classes for icons
 */
const SIZE_CLASSES: Record<"sm" | "md" | "lg", string> = {
  sm: "w-4 h-4",
  md: "w-5 h-5",
  lg: "w-6 h-6",
};

// ============================================================================
// Main Component
// ============================================================================

/**
 * SERPFeatureIcon Component
 *
 * Renders an SVG icon representing a specific SERP feature type.
 *
 * @example
 * ```tsx
 * <SERPFeatureIcon feature="featured_snippet" />
 * <SERPFeatureIcon feature="local_pack" size="lg" showBackground />
 * ```
 */
export const SERPFeatureIcon: React.FC<SERPFeatureIconProps> = ({
  feature,
  size = "md",
  className,
  showBackground = false,
}) => {
  const IconComponent = ICON_MAP[feature] || FallbackIcon;
  const featureName = SERP_FEATURE_NAMES[feature] || "SERP Feature";
  const sizeClass = SIZE_CLASSES[size];

  if (showBackground) {
    return (
      <span
        className={cn(
          "inline-flex items-center justify-center rounded-full bg-slate-800/50",
          size === "sm" && "w-6 h-6",
          size === "md" && "w-8 h-8",
          size === "lg" && "w-10 h-10",
          className
        )}
        role="img"
        aria-label={featureName}
        title={featureName}
      >
        <IconComponent className={sizeClass} />
      </span>
    );
  }

  return (
    <span
      role="img"
      aria-label={featureName}
      title={featureName}
      className={className}
    >
      <IconComponent className={sizeClass} />
    </span>
  );
};

export default SERPFeatureIcon;
