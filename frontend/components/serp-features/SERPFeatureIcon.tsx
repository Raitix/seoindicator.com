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

interface SVGIconProps {
  className?: string;
  role?: string;
  "aria-label"?: string;
  title?: string;
  "aria-hidden"?: "true" | "false";
}

// ============================================================================
// SVG Icon Components
// ============================================================================

/**
 * Creates an SVG icon component with proper accessibility support
 */
const createSVGIcon = (paths: React.ReactNode): React.FC<SVGIconProps> => {
  const IconComponent: React.FC<SVGIconProps> = ({
    className,
    role,
    "aria-label": ariaLabel,
    title,
    "aria-hidden": ariaHidden,
  }) => (
    <svg
      viewBox="0 0 24 24"
      fill="none"
      stroke="currentColor"
      strokeWidth="2"
      strokeLinecap="round"
      strokeLinejoin="round"
      className={className}
      role={role}
      aria-label={ariaLabel}
      aria-hidden={ariaHidden}
    >
      {title && <title>{title}</title>}
      {paths}
    </svg>
  );
  return IconComponent;
};

/**
 * Creates path elements with aria-hidden for accessibility
 */
const createPath = (d: string, key?: string) => (
  <path key={key} d={d} aria-hidden="true" />
);

const createPolygon = (points: string, key?: string) => (
  <polygon key={key} points={points} aria-hidden="true" />
);

const createCircle = (cx: string, cy: string, r: string, key?: string) => (
  <circle key={key} cx={cx} cy={cy} r={r} aria-hidden="true" />
);

const createRect = (props: { x: string; y: string; width: string; height: string; rx?: string; ry?: string }, key?: string) => (
  <rect key={key} {...props} aria-hidden="true" />
);

const createLine = (x1: string, y1: string, x2: string, y2: string, key?: string) => (
  <line key={key} x1={x1} y1={y1} x2={x2} y2={y2} aria-hidden="true" />
);

const createPolyline = (points: string, key?: string) => (
  <polyline key={key} points={points} aria-hidden="true" />
);

// Icon path definitions with aria-hidden on inner elements
const FEATURED_SNIPPET_PATHS = (
  <>
    {createPath("M6 9H4.5a2.5 2.5 0 0 1 0-5H6", "fs1")}
    {createPath("M18 9h1.5a2.5 2.5 0 0 0 0-5H18", "fs2")}
    {createPath("M4 22h16", "fs3")}
    {createPath("M10 14.66V17c0 .55-.47.98-.97 1.21C7.85 18.75 7 20.24 7 22", "fs4")}
    {createPath("M14 14.66V17c0 .55.47.98.97 1.21C16.15 18.75 17 20.24 17 22", "fs5")}
    {createPath("M18 2H6v7a6 6 0 0 0 12 0V2Z", "fs6")}
  </>
);

const LOCAL_PACK_PATHS = (
  <>
    {createPath("M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z", "lp1")}
    {createCircle("12", "10", "3", "lp2")}
  </>
);

const IMAGE_PACK_PATHS = (
  <>
    {createRect({ x: "3", y: "3", width: "18", height: "18", rx: "2", ry: "2" }, "ip1")}
    {createCircle("9", "9", "2", "ip2")}
    {createPath("m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21", "ip3")}
  </>
);

const VIDEO_PATHS = (
  <>
    {createRect({ x: "2", y: "4", width: "20", height: "16", rx: "2", ry: "2" }, "v1")}
    {createPolygon("10 9 15 12 10 15 10 9", "v2")}
  </>
);

const PEOPLE_ALSO_ASK_PATHS = (
  <>
    {createCircle("12", "12", "10", "paa1")}
    {createPath("M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3", "paa2")}
    {createPath("M12 17h.01", "paa3")}
  </>
);

const KNOWLEDGE_PANEL_PATHS = (
  <>
    {createRect({ x: "3", y: "3", width: "18", height: "18", rx: "2", ry: "2" }, "kp1")}
    {createPath("M3 9h18", "kp2")}
    {createPath("M9 21V9", "kp3")}
  </>
);

const SITELINKS_PATHS = (
  <>
    {createPath("M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71", "sl1")}
    {createPath("M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71", "sl2")}
  </>
);

const REVIEWS_PATHS = (
  <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2" aria-hidden="true" />
);

const FAQ_PATHS = (
  <>
    {createPath("M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z", "faq1")}
    {createPolyline("14 2 14 8 20 8", "faq2")}
    {createLine("16", "13", "8", "13", "faq3")}
    {createLine("16", "17", "8", "17", "faq4")}
    {createPolyline("10 9 9 9 8 9", "faq5")}
  </>
);

const PRODUCT_PATHS = (
  <>
    {createPath("M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z", "pr1")}
    {createPath("M3 6h18", "pr2")}
    {createPath("M16 10a4 4 0 0 1-8 0", "pr3")}
  </>
);

const FALLBACK_PATHS = (
  <>
    {createPath("M3 3v18h18", "fb1")}
    {createPath("M18 17V9", "fb2")}
    {createPath("M13 17V5", "fb3")}
    {createPath("M8 17v-3", "fb4")}
  </>
);

// ============================================================================
// Icon Registry
// ============================================================================

const ICON_MAP: Record<SERPFeatureType, React.FC<SVGIconProps>> = {
  featured_snippet: createSVGIcon(FEATURED_SNIPPET_PATHS),
  local_pack: createSVGIcon(LOCAL_PACK_PATHS),
  image_pack: createSVGIcon(IMAGE_PACK_PATHS),
  video: createSVGIcon(VIDEO_PATHS),
  people_also_ask: createSVGIcon(PEOPLE_ALSO_ASK_PATHS),
  knowledge_panel: createSVGIcon(KNOWLEDGE_PANEL_PATHS),
  sitelinks: createSVGIcon(SITELINKS_PATHS),
  reviews: createSVGIcon(REVIEWS_PATHS),
  faq: createSVGIcon(FAQ_PATHS),
  product: createSVGIcon(PRODUCT_PATHS),
};

const FALLBACK_ICON = createSVGIcon(FALLBACK_PATHS);

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
  const IconComponent = ICON_MAP[feature] || FALLBACK_ICON;
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
        <IconComponent
          className={sizeClass}
          aria-hidden="true"
        />
      </span>
    );
  }

  return (
    <IconComponent
      className={cn(sizeClass, className)}
      role="img"
      aria-label={featureName}
      title={featureName}
    />
  );
};

export default SERPFeatureIcon;
