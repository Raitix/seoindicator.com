/**
 * Indicator-to-Guide Mapping
 * Task: TASK-20-25 - Add Links from Results Table to Guides
 *
 * Maps analysis indicator IDs to corresponding SEO guide slugs.
 */

import { GUIDE_SLUGS } from './registry';

/**
 * Mapping from indicator IDs to guide slugs.
 * Each indicator maps to its primary or closest matching guide.
 */
export const INDICATOR_TO_GUIDE_MAP: Record<string, string> = {
  meta_tags: 'meta-title',
  headings: 'h1-headings',
  images: 'image-alt-text',
  links: 'internal-links',
  url_structure: 'canonical-urls',
  content_quality: 'h1-headings',
  mobile_friendly: 'mobile-friendly',
  page_speed: 'page-speed',
  structured_data: 'structured-data',
  security: 'https-security',
  accessibility: 'image-alt-text',
  social_tags: 'open-graph',
  canonical: 'canonical-urls',
  robots: 'robots-txt',
  sitemap: 'xml-sitemap',
  internal_links: 'internal-links',
  external_links: 'external-links',
};

/**
 * Get the SEO guide slug for a given indicator ID.
 *
 * @param indicator - The indicator ID (e.g., 'meta_tags', 'page_speed')
 * @returns The corresponding guide slug (e.g., 'meta-title', 'page-speed') or null if not found
 *
 * @example
 * getGuideSlugForIndicator('meta_tags') // Returns 'meta-title'
 * getGuideSlugForIndicator('page_speed') // Returns 'page-speed'
 * getGuideSlugForIndicator('unknown') // Returns null
 * getGuideSlugForIndicator(null) // Returns null
 */
export function getGuideSlugForIndicator(indicator: string | null | undefined): string | null {
  // Handle null, undefined, or non-string inputs
  if (typeof indicator !== 'string') {
    return null;
  }

  // Empty string check
  if (indicator === '') {
    return null;
  }

  // Exact match only - case-sensitive, no trimming
  const slug = INDICATOR_TO_GUIDE_MAP[indicator];

  // Validate that the mapped slug exists in the guide registry
  if (!slug || !GUIDE_SLUGS.includes(slug)) {
    return null;
  }

  return slug;
}

/**
 * Check if an indicator has a corresponding guide.
 *
 * @param indicator - The indicator ID
 * @returns true if the indicator has a guide mapping, false otherwise
 */
export function hasGuideForIndicator(indicator: string | null | undefined): boolean {
  return getGuideSlugForIndicator(indicator) !== null;
}

/**
 * Get all indicators that have guide mappings.
 *
 * @returns Array of indicator IDs that have guide mappings
 */
export function getIndicatorsWithGuides(): string[] {
  return Object.keys(INDICATOR_TO_GUIDE_MAP);
}
