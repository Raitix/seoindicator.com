/**
 * Meta Tags Generation
 * Task: TASK-16-10 - Schema Markup & SEO Optimization
 * 
 * Meta tag, Open Graph, and Twitter Card generation utilities.
 */

import type {
  PageInfo,
  MetaTags,
  OpenGraphTags,
  TwitterCardTags,
} from '@/lib/types/tools';

import { SITE_CONFIG, getToolUrl } from './schema-markup';

/**
 * Truncate text to a maximum length with ellipsis
 */
export function truncateText(text: string, maxLength: number): string {
  if (text.length <= maxLength) return text;
  return text.substring(0, maxLength - 3) + '...';
}

/**
 * Generate meta tags for a page
 */
export function generateMetaTags(page: PageInfo): MetaTags {
  return {
    title: `${page.name} | ${SITE_CONFIG.name}`,
    description: truncateText(page.description, 160),
    keywords: page.keywords?.join(', '),
    canonical: page.url,
    robots: 'index, follow',
  };
}

/**
 * Generate Open Graph tags for a page
 */
export function generateOpenGraphTags(page: PageInfo): OpenGraphTags {
  return {
    type: 'website',
    title: page.name,
    description: page.description,
    url: page.url,
    siteName: SITE_CONFIG.name,
    image: page.image,
    imageAlt: page.image ? `${page.name} preview` : undefined,
  };
}

/**
 * Generate Twitter Card tags for a page
 */
export function generateTwitterCardTags(page: PageInfo): TwitterCardTags {
  return {
    card: page.image ? 'summary_large_image' : 'summary',
    title: page.name,
    description: page.description,
    image: page.image,
    imageAlt: page.image ? `${page.name} preview` : undefined,
  };
}

/**
 * Generate canonical URL for a tool
 */
export function generateCanonicalUrl(slug: string): string {
  return getToolUrl(slug);
}

/**
 * Generate page info for tools landing page
 */
export function getToolsLandingPageInfo(): PageInfo {
  return {
    slug: '',
    name: 'Free SEO Tools',
    description: 'Free online SEO tools for meta tag analysis, keyword density checking, mobile-friendly testing, and social media previews. No registration required.',
    url: `${SITE_CONFIG.url}/tools/`,
    image: 'https://seoindicator.com/images/tools/og-image.png',
    keywords: ['SEO tools', 'free SEO', 'meta tag analyzer', 'keyword density', 'social preview'],
  };
}

/**
 * Generate page info for a specific tool
 */
export function getToolPageInfo(
  slug: string,
  name: string,
  description: string,
  image?: string,
  keywords?: string[]
): PageInfo {
  return {
    slug,
    name: `Free ${name}`,
    description,
    url: generateCanonicalUrl(slug),
    image,
    keywords,
  };
}

/**
 * Generate Next.js metadata object for a tool page
 */
export function generateToolMetadata(
  toolName: string,
  description: string,
  slug: string,
  options: {
    image?: string;
    keywords?: string[];
  } = {}
): {
  title: string;
  description: string;
  keywords?: string;
  alternates: { canonical: string };
  openGraph: {
    type: 'website';
    title: string;
    description: string;
    url: string;
    siteName: string;
    images?: Array<{ url: string; alt: string }>;
  };
  twitter: {
    card: 'summary' | 'summary_large_image';
    title: string;
    description: string;
    images?: string[];
  };
  robots: string;
} {
  const pageInfo = getToolPageInfo(slug, toolName, description, options.image, options.keywords);
  const meta = generateMetaTags(pageInfo);
  const og = generateOpenGraphTags(pageInfo);
  const twitter = generateTwitterCardTags(pageInfo);

  return {
    title: meta.title,
    description: meta.description,
    keywords: meta.keywords,
    alternates: {
      canonical: meta.canonical,
    },
    openGraph: {
      type: og.type,
      title: og.title,
      description: og.description,
      url: og.url,
      siteName: og.siteName,
      images: og.image ? [{ url: og.image, alt: og.imageAlt || '' }] : undefined,
    },
    twitter: {
      card: twitter.card,
      title: twitter.title,
      description: twitter.description,
      images: twitter.image ? [twitter.image] : undefined,
    },
    robots: meta.robots || 'index, follow',
  };
}

/**
 * Generate Next.js metadata object for tools landing page
 */
export function generateToolsLandingMetadata(): {
  title: string;
  description: string;
  keywords: string;
  alternates: { canonical: string };
  openGraph: {
    type: 'website';
    title: string;
    description: string;
    url: string;
    siteName: string;
    images?: Array<{ url: string; alt: string }>;
  };
  twitter: {
    card: 'summary_large_image';
    title: string;
    description: string;
    images?: string[];
  };
  robots: string;
} {
  const pageInfo = getToolsLandingPageInfo();
  const meta = generateMetaTags(pageInfo);
  const og = generateOpenGraphTags(pageInfo);
  const twitter = generateTwitterCardTags(pageInfo);

  return {
    title: meta.title,
    description: meta.description,
    keywords: meta.keywords || '',
    alternates: {
      canonical: meta.canonical,
    },
    openGraph: {
      type: og.type,
      title: og.title,
      description: og.description,
      url: og.url,
      siteName: og.siteName,
      images: og.image ? [{ url: og.image, alt: og.imageAlt || '' }] : undefined,
    },
    twitter: {
      card: twitter.card,
      title: twitter.title,
      description: twitter.description,
      images: twitter.image ? [twitter.image] : undefined,
    },
    robots: meta.robots || 'index, follow',
  };
}

/**
 * Tool page metadata configurations
 */
export const TOOL_METADATA: Record<string, {
  name: string;
  description: string;
  image?: string;
  keywords: string[];
}> = {
  'meta-tag-analyzer': {
    name: 'Meta Tag Analyzer',
    description: "Analyze any page's title tags, meta descriptions, and Open Graph tags. Free instant SEO tool for optimizing your website's meta tags.",
    image: 'https://seoindicator.com/images/tools/meta-tag-analyzer.png',
    keywords: ['meta tag analyzer', 'title tag checker', 'meta description tool', 'open graph validator', 'SEO tool'],
  },
  'keyword-density': {
    name: 'Keyword Density Checker',
    description: 'Check keyword frequency and density on any webpage. Free SEO tool for content optimization and keyword analysis.',
    image: 'https://seoindicator.com/images/tools/keyword-density.png',
    keywords: ['keyword density', 'keyword frequency', 'content analysis', 'keyword stuffing', 'SEO tool'],
  },
  'social-preview': {
    name: 'Social Media Preview',
    description: 'Preview how your page appears when shared on Facebook, Twitter, and LinkedIn. Analyze Open Graph and Twitter Card tags.',
    image: 'https://seoindicator.com/images/tools/social-preview.png',
    keywords: ['social media preview', 'open graph checker', 'twitter card validator', 'facebook preview', 'linkedin preview'],
  },
};

/**
 * Get metadata for a tool by slug
 */
export function getToolMetadataBySlug(slug: string): ReturnType<typeof generateToolMetadata> | null {
  const config = TOOL_METADATA[slug];
  if (!config) return null;
  
  return generateToolMetadata(
    config.name,
    config.description,
    slug,
    {
      image: config.image,
      keywords: config.keywords,
    }
  );
}
