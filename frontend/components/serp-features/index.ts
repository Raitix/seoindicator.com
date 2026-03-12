/**
 * SERP Feature Components - Barrel Export
 *
 * Reusable components for displaying SERP feature icons and badges.
 *
 * @see TASK-7-16
 * @see TESTS/SPEC-7-16.md
 *
 * @example
 * ```tsx
 * import { SERPFeatureIcon, SERPFeatureBadge } from '@/components/serp-features';
 *
 * // Single icon
 * <SERPFeatureIcon feature="featured_snippet" />
 *
 * // Badge with multiple features
 * <SERPFeatureBadge features={[
 *   { type: 'featured_snippet', isPresent: true },
 *   { type: 'sitelinks', isPresent: true }
 * ]} />
 * ```
 */

export { SERPFeatureIcon, SERP_FEATURE_NAMES, type SERPFeatureIconProps } from "./SERPFeatureIcon";
export { SERPFeatureBadge, type SERPFeatureBadgeProps } from "./SERPFeatureBadge";
