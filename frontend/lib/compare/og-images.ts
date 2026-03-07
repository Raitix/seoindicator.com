/**
 * OG Image Utility for Comparison Pages
 * Task: TASK-32-17 - OG Image Generation for Comparisons
 * 
 * Provides static OG image URLs for comparison pages.
 * Images are pre-generated at build time via scripts/generate-og-images.mjs
 */

import { COMPARISON_SLUGS } from './registry';

// Base URL for OG images (static files in /public/og/compare/)
const OG_IMAGE_BASE = '/og/compare';

// OG image dimensions (standard OG size)
export const OG_IMAGE_WIDTH = 1200;
export const OG_IMAGE_HEIGHT = 630;

/**
 * Get the OG image URL for a comparison slug.
 * 
 * @param slug - Comparison slug (e.g., 'seoindicator-vs-ahrefs')
 * @returns URL to the static OG image
 * 
 * @example
 * const ogUrl = getComparisonOGImage('seoindicator-vs-ahrefs');
 * // Returns: '/og/compare/seoindicator-vs-ahrefs.png'
 */
export function getComparisonOGImage(slug: string): string {
  // Validate slug exists in registry
  if (!COMPARISON_SLUGS.includes(slug)) {
    // Return default image for unknown slugs
    return `${OG_IMAGE_BASE}/default.png`;
  }
  
  return `${OG_IMAGE_BASE}/${slug}.png`;
}

/**
 * Get OG image metadata for a comparison.
 * 
 * @param slug - Comparison slug
 * @param baseUrl - Optional base URL for absolute URLs
 * @returns OG image metadata object
 */
export function getComparisonOGMetadata(
  slug: string,
  baseUrl?: string
): {
  url: string;
  width: number;
  height: number;
  type: 'image/png';
  alt: string;
} {
  const relativeUrl = getComparisonOGImage(slug);
  const url = baseUrl ? `${baseUrl}${relativeUrl}` : relativeUrl;
  
  return {
    url,
    width: OG_IMAGE_WIDTH,
    height: OG_IMAGE_HEIGHT,
    type: 'image/png',
    alt: `Comparison preview image`,
  };
}

/**
 * Check if an OG image exists for a slug.
 * Note: This is a client-side check; actual file existence is guaranteed by build script.
 * 
 * @param slug - Comparison slug
 * @returns true if slug is valid (has pre-generated image)
 */
export function hasOGImage(slug: string): boolean {
  return COMPARISON_SLUGS.includes(slug);
}

/**
 * Get all OG image paths for sitemap generation.
 * 
 * @returns Array of all OG image paths
 */
export function getAllOGImagePaths(): string[] {
  return COMPARISON_SLUGS.map((slug) => `${OG_IMAGE_BASE}/${slug}.png`);
}
