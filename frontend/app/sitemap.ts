/**
 * Sitemap Generation
 * Task: TASK-32-15 - Sitemap Integration
 * Spec: SPEC-32 (Competitor Comparison Landing Pages)
 *
 * Next.js 14 sitemap generation for SEOIndicator.
 * Includes all comparison landing pages for search engine discovery.
 */

import { MetadataRoute } from 'next';
import { COMPARISON_SLUGS } from '@/lib/compare/registry';

/**
 * Base URL for SEOIndicator.
 * Update this when deploying to production.
 */
const BASE_URL = 'https://seoindicator.com';

// Required for static export
export const dynamic = 'force-static';

/**
 * Generate the XML sitemap for SEOIndicator.
 * Includes all pages, comparison pages, and SEO guides.
 */
export default function sitemap(): MetadataRoute.Sitemap {
  const now = new Date();

  // Static pages with their priorities and change frequencies
  const staticPages: MetadataRoute.Sitemap = [
    {
      url: BASE_URL,
      lastModified: now,
      changeFrequency: 'daily',
      priority: 1.0,
    },
    {
      url: `${BASE_URL}/about/`,
      lastModified: now,
      changeFrequency: 'monthly',
      priority: 0.5,
    },
    {
      url: `${BASE_URL}/how-it-works/`,
      lastModified: now,
      changeFrequency: 'monthly',
      priority: 0.6,
    },
    {
      url: `${BASE_URL}/pricing/`,
      lastModified: now,
      changeFrequency: 'weekly',
      priority: 0.8,
    },
    {
      url: `${BASE_URL}/terms/`,
      lastModified: now,
      changeFrequency: 'yearly',
      priority: 0.3,
    },
    {
      url: `${BASE_URL}/tools/`,
      lastModified: now,
      changeFrequency: 'weekly',
      priority: 0.7,
    },
    {
      url: `${BASE_URL}/seo-guides/`,
      lastModified: now,
      changeFrequency: 'weekly',
      priority: 0.7,
    },
  ];

  // Tools pages
  const toolsPages: MetadataRoute.Sitemap = [
    {
      url: `${BASE_URL}/tools/keyword-density/`,
      lastModified: now,
      changeFrequency: 'monthly',
      priority: 0.6,
    },
    {
      url: `${BASE_URL}/tools/meta-tag-analyzer/`,
      lastModified: now,
      changeFrequency: 'monthly',
      priority: 0.6,
    },
    {
      url: `${BASE_URL}/tools/social-preview/`,
      lastModified: now,
      changeFrequency: 'monthly',
      priority: 0.6,
    },
  ];

  // Comparison index page
  const comparisonIndex: MetadataRoute.Sitemap[0] = {
    url: `${BASE_URL}/compare/`,
    lastModified: now,
    changeFrequency: 'weekly',
    priority: 0.7,
  };

  // Comparison pages from COMPARISON_SLUGS
  // High priority (0.8) for conversion-focused comparison landing pages
  const comparisonPages: MetadataRoute.Sitemap = COMPARISON_SLUGS.map((slug) => ({
    url: `${BASE_URL}/compare/${slug}/`,
    lastModified: now,
    changeFrequency: 'monthly' as const,
    priority: 0.8,
  }));

  // Combine all entries
  return [
    ...staticPages,
    ...toolsPages,
    comparisonIndex,
    ...comparisonPages,
  ];
}
