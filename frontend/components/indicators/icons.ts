/**
 * Indicator Icon Registry
 *
 * Centralized registry mapping SEO indicator types to their corresponding
 * Lucide icons. This provides consistent iconography across the application
 * and supports tree-shaking through direct imports.
 *
 * @example
 * ```tsx
 * import { INDICATOR_ICONS, IndicatorIconName } from '@/components/indicators';
 *
 * // Get icon for an indicator
 * const Icon = INDICATOR_ICONS['keyword-rankings'];
 *
 * // Type-safe indicator names
 * const indicatorName: IndicatorIconName = 'visibility-score';
 * ```
 */

import {
  TrendingUp,
  Eye,
  Users,
  Wrench,
  Link,
  FileText,
  Star,
  Zap,
} from 'lucide-react';

// ============================================================================
// Type Exports
// ============================================================================

/**
 * Type representing valid indicator icon names.
 * This is the canonical list of all indicator types that have icons.
 */
export type IndicatorIconName =
  | 'keyword-rankings'
  | 'visibility-score'
  | 'competitor-analysis'
  | 'technical-health'
  | 'backlink-profile'
  | 'content-opportunities'
  | 'serp-features'
  | 'page-speed';

// ============================================================================
// Indicator Icon Registry
// ============================================================================

/**
 * Maps SEO indicator types to their corresponding Lucide icons.
 *
 * Each indicator has an icon that semantically represents its purpose:
 * - keyword-rankings → TrendingUp: Growth/position tracking
 * - visibility-score → Eye: Visibility/visibility
 * - competitor-analysis → Users: Multiple people/competitors
 * - technical-health → Wrench: Tools/fixing
 * - backlink-profile → Link: Links
 * - content-opportunities → FileText: Content/documents
 * - serp-features → Star: Featured/highlighted
 * - page-speed → Zap: Performance/fast
 *
 * The registry is frozen to prevent runtime mutations.
 */
export const INDICATOR_ICONS: Readonly<Record<IndicatorIconName, typeof TrendingUp>> = Object.freeze({
  'keyword-rankings': TrendingUp,
  'visibility-score': Eye,
  'competitor-analysis': Users,
  'technical-health': Wrench,
  'backlink-profile': Link,
  'content-opportunities': FileText,
  'serp-features': Star,
  'page-speed': Zap,
});

/**
 * Array of all valid indicator icon names.
 * Useful for validation and iteration.
 */
export const IndicatorIconName = Object.freeze(
  Object.keys(INDICATOR_ICONS) as IndicatorIconName[]
);
