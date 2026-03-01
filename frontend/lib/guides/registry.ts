/**
 * SEO Guide Registry and Helper Functions
 * Task: TASK-20-1 - Create Guide Data Structure and TypeScript Types
 * 
 * This module provides the central registry for all SEO guides and helper functions
 * for accessing guide data.
 */

import {
  SEOGuide,
  GuideCategory,
} from './types';

// ============================================================================
// Guide Definitions
// ============================================================================

/**
 * All 17 SEO Guide definitions
 * Content sections will be populated in Phase 2 tasks
 */
const GUIDES: SEOGuide[] = [
  // On-Page SEO Guides
  {
    slug: 'meta-title',
    title: 'Meta Title Optimization',
    summary: 'Learn how to craft compelling page titles that improve click-through rates and search rankings.',
    category: 'on-page',
    toolSlug: 'meta-tag-analyzer',
    icon: '📝',
    readTime: 5,
    sections: [],
    checklist: [],
    relatedGuides: ['meta-description', 'h1-headings', 'open-graph'],
  },
  {
    slug: 'meta-description',
    title: 'Meta Description Best Practices',
    summary: 'Write descriptions that entice users to click and provide search engines with context.',
    category: 'on-page',
    toolSlug: 'meta-tag-analyzer',
    icon: '📋',
    readTime: 4,
    sections: [],
    checklist: [],
    relatedGuides: ['meta-title', 'open-graph', 'twitter-cards'],
  },
  {
    slug: 'h1-headings',
    title: 'H1 Heading Structure',
    summary: 'Understand how to use H1 tags effectively for SEO and accessibility.',
    category: 'on-page',
    icon: '🔤',
    readTime: 4,
    sections: [],
    checklist: [],
    relatedGuides: ['heading-hierarchy', 'meta-title'],
  },
  {
    slug: 'heading-hierarchy',
    title: 'Heading Hierarchy (H1-H6)',
    summary: 'Create a logical content structure using proper heading order for better UX and SEO.',
    category: 'on-page',
    icon: '📑',
    readTime: 5,
    sections: [],
    checklist: [],
    relatedGuides: ['h1-headings', 'structured-data'],
  },
  {
    slug: 'image-alt-text',
    title: 'Image Alt Text Optimization',
    summary: 'Write descriptive alt text that improves accessibility and image search visibility.',
    category: 'on-page',
    icon: '🖼️',
    readTime: 4,
    sections: [],
    checklist: [],
    relatedGuides: ['image-optimization', 'structured-data'],
  },
  {
    slug: 'image-optimization',
    title: 'Image Optimization for Web',
    summary: 'Optimize images for faster loading without sacrificing quality.',
    category: 'on-page',
    icon: '⚡',
    readTime: 6,
    sections: [],
    checklist: [],
    relatedGuides: ['image-alt-text', 'page-speed'],
  },
  {
    slug: 'internal-links',
    title: 'Internal Linking Strategy',
    summary: 'Build a strong site architecture through strategic internal linking.',
    category: 'on-page',
    icon: '🔗',
    readTime: 5,
    sections: [],
    checklist: [],
    relatedGuides: ['external-links', 'xml-sitemap'],
  },
  {
    slug: 'external-links',
    title: 'External Link Best Practices',
    summary: 'Use outbound links strategically to build authority and provide value.',
    category: 'on-page',
    icon: '🌐',
    readTime: 4,
    sections: [],
    checklist: [],
    relatedGuides: ['internal-links', 'https-security'],
  },

  // Technical SEO Guides
  {
    slug: 'page-speed',
    title: 'Page Speed Optimization',
    summary: 'Improve Core Web Vitals and page load times for better user experience.',
    category: 'technical',
    icon: '🚀',
    readTime: 7,
    sections: [],
    checklist: [],
    relatedGuides: ['mobile-friendly', 'image-optimization'],
  },
  {
    slug: 'mobile-friendly',
    title: 'Mobile-Friendly Design',
    summary: 'Ensure your site works flawlessly on all mobile devices.',
    category: 'technical',
    toolSlug: 'mobile-friendly-checker',
    icon: '📱',
    readTime: 5,
    sections: [],
    checklist: [],
    relatedGuides: ['page-speed', 'https-security'],
  },
  {
    slug: 'https-security',
    title: 'HTTPS & Security Headers',
    summary: 'Secure your site with SSL and implement security best practices.',
    category: 'technical',
    icon: '🔒',
    readTime: 5,
    sections: [],
    checklist: [],
    relatedGuides: ['mobile-friendly', 'canonical-urls'],
  },
  {
    slug: 'structured-data',
    title: 'Structured Data (Schema.org)',
    summary: 'Implement rich snippets and structured data for enhanced search results.',
    category: 'technical',
    icon: '📊',
    readTime: 6,
    sections: [],
    checklist: [],
    relatedGuides: ['open-graph', 'canonical-urls'],
  },
  {
    slug: 'canonical-urls',
    title: 'Canonical URL Implementation',
    summary: 'Prevent duplicate content issues with proper canonical tags.',
    category: 'technical',
    icon: '🎯',
    readTime: 4,
    sections: [],
    checklist: [],
    relatedGuides: ['https-security', 'xml-sitemap'],
  },
  {
    slug: 'robots-txt',
    title: 'Robots.txt Configuration',
    summary: 'Control how search engines crawl and index your site.',
    category: 'technical',
    icon: '🤖',
    readTime: 4,
    sections: [],
    checklist: [],
    relatedGuides: ['xml-sitemap', 'canonical-urls'],
  },
  {
    slug: 'xml-sitemap',
    title: 'XML Sitemap Creation',
    summary: 'Help search engines discover and index all your important pages.',
    category: 'technical',
    icon: '🗺️',
    readTime: 4,
    sections: [],
    checklist: [],
    relatedGuides: ['robots-txt', 'internal-links'],
  },

  // Content & Social SEO Guides
  {
    slug: 'open-graph',
    title: 'Open Graph Tags',
    summary: 'Optimize how your content appears when shared on Facebook and other platforms.',
    category: 'content',
    toolSlug: 'social-media-preview',
    icon: '👍',
    readTime: 5,
    sections: [],
    checklist: [],
    relatedGuides: ['twitter-cards', 'meta-description', 'structured-data'],
  },
  {
    slug: 'twitter-cards',
    title: 'Twitter Cards',
    summary: 'Create rich Twitter card previews that drive engagement and clicks.',
    category: 'content',
    toolSlug: 'social-media-preview',
    icon: '🐦',
    readTime: 4,
    sections: [],
    checklist: [],
    relatedGuides: ['open-graph', 'meta-description'],
  },
];

// ============================================================================
// Constants
// ============================================================================

/**
 * All guide slugs as a readonly array
 */
export const GUIDE_SLUGS: readonly string[] = GUIDES.map((g) => g.slug);

// ============================================================================
// Helper Functions
// ============================================================================

/**
 * Get a guide by its slug
 * @param slug - The guide slug (e.g., 'meta-title')
 * @returns The guide object or null if not found
 */
export function getGuideBySlug(slug: string): SEOGuide | null {
  if (!slug || typeof slug !== 'string') {
    return null;
  }

  // Exact match only - no trimming, case-sensitive
  if (slug === '') {
    return null;
  }

  const guide = GUIDES.find((g) => g.slug === slug);
  return guide ? { ...guide } : null;
}

/**
 * Get all guides (returns copies to ensure immutability)
 * @returns Array of all guides
 */
export function getAllGuides(): SEOGuide[] {
  return GUIDES.map((guide) => ({ ...guide }));
}

/**
 * Get related guides for a given slug
 * @param slug - The guide slug
 * @returns Array of related guide objects (max 3)
 */
export function getRelatedGuides(slug: string): SEOGuide[] {
  if (!slug || typeof slug !== 'string') {
    return [];
  }

  const guide = getGuideBySlug(slug.trim());
  if (!guide) {
    return [];
  }

  // Get related guides based on the guide's relatedGuides array
  const relatedGuides = guide.relatedGuides
    .map((relatedSlug) => getGuideBySlug(relatedSlug))
    .filter((g): g is SEOGuide => g !== null)
    .slice(0, 3);

  return relatedGuides;
}

/**
 * Get guides filtered by category
 * @param category - The category to filter by
 * @returns Array of guides in the specified category
 */
export function getGuidesByCategory(category: GuideCategory): SEOGuide[] {
  return GUIDES.filter((g) => g.category === category).map((guide) => ({ ...guide }));
}

/**
 * Get guides that are linked to a specific tool
 * @param toolSlug - The tool slug to search for
 * @returns Array of guides linked to the specified tool
 */
export function getGuidesByToolSlug(toolSlug: string): SEOGuide[] {
  if (!toolSlug || typeof toolSlug !== 'string') {
    return [];
  }

  return GUIDES.filter((g) => g.toolSlug === toolSlug.trim()).map((guide) => ({ ...guide }));
}

/**
 * Get all guides that have an associated tool
 * @returns Array of guides with toolSlug defined
 */
export function getGuidesWithTools(): SEOGuide[] {
  return GUIDES.filter((g) => g.toolSlug !== undefined).map((guide) => ({ ...guide }));
}

/**
 * Get all guides that do NOT have an associated tool
 * @returns Array of guides without toolSlug
 */
export function getGuidesWithoutTools(): SEOGuide[] {
  return GUIDES.filter((g) => g.toolSlug === undefined).map((guide) => ({ ...guide }));
}
