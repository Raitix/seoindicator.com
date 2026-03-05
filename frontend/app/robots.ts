/**
 * Robots.txt Generation
 * Task: TASK-32-15 - Sitemap Integration
 * Spec: SPEC-32 (Competitor Comparison Landing Pages)
 *
 * Next.js 14 robots.txt generation for SEOIndicator.
 * Allows all crawlers and specifies sitemap location.
 */

import { MetadataRoute } from 'next';

/**
 * Base URL for SEOIndicator.
 */
const BASE_URL = 'https://seoindicator.com';

/**
 * Generate the robots.txt file.
 * Allows all paths and specifies sitemap location.
 */
export default function robots(): MetadataRoute.Robots {
  return {
    rules: {
      userAgent: '*',
      allow: '/',
      allow: '/compare/',
    },
    sitemap: `${BASE_URL}/sitemap.xml`,
  };
}
