/**
 * Schema Markup Generation
 * Task: TASK-16-10 - Schema Markup & SEO Optimization
 * 
 * JSON-LD schema generation utilities for SEO tools.
 * Generates SoftwareApplication, FAQPage, BreadcrumbList, and ItemList schemas.
 */

import type {
  ToolInfo,
  FAQItem,
  BreadcrumbItem,
  SoftwareApplicationSchema,
  FAQPageSchema,
  BreadcrumbListSchema,
  ItemListSchema,
} from '@/lib/types/tools';

/**
 * Site configuration
 */
export const SITE_CONFIG = {
  name: 'SEOIndicator',
  url: 'https://seoindicator.com',
  defaultImage: 'https://seoindicator.com/images/og-default.png',
} as const;

/**
 * Escape HTML special characters to prevent XSS
 */
export function escapeHtml(text: string): string {
  return text
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}

/**
 * Convert schema object to JSON-LD string
 */
export function schemaToJsonLd(schema: unknown): string {
  return JSON.stringify(schema);
}

/**
 * Generate a tool URL from slug
 */
export function getToolUrl(slug: string): string {
  if (!slug || slug === '/' || slug === '') {
    return `${SITE_CONFIG.url}/tools/`;
  }
  return `${SITE_CONFIG.url}/tools/${slug}/`;
}

/**
 * Generate SoftwareApplication schema for a tool
 */
export function generateSoftwareApplicationSchema(tool: ToolInfo): SoftwareApplicationSchema {
  const schema: SoftwareApplicationSchema = {
    '@context': 'https://schema.org',
    '@type': 'SoftwareApplication',
    name: tool.name,
    description: tool.description,
    applicationCategory: 'WebApplication',
    operatingSystem: 'Web Browser',
    offers: {
      '@type': 'Offer',
      price: '0',
      priceCurrency: 'USD',
    },
    url: getToolUrl(tool.slug),
  };

  // Add aggregate rating if provided
  if (tool.rating) {
    schema.aggregateRating = {
      '@type': 'AggregateRating',
      ratingValue: tool.rating.value.toString(),
      ratingCount: tool.rating.count.toString(),
    };
  }

  // Add screenshot/image if provided
  if (tool.image) {
    schema.screenshot = tool.image;
  }

  // Add feature list if provided
  if (tool.features.length > 0) {
    schema.featureList = tool.features;
  }

  return schema;
}

/**
 * Generate FAQPage schema from FAQ items
 */
export function generateFAQSchema(faqs: FAQItem[]): FAQPageSchema {
  return {
    '@context': 'https://schema.org',
    '@type': 'FAQPage',
    mainEntity: faqs.map((faq) => ({
      '@type': 'Question',
      name: faq.question,
      acceptedAnswer: {
        '@type': 'Answer',
        text: faq.answer,
      },
    })),
  };
}

/**
 * Generate BreadcrumbList schema from breadcrumb items
 */
export function generateBreadcrumbSchema(items: BreadcrumbItem[]): BreadcrumbListSchema {
  return {
    '@context': 'https://schema.org',
    '@type': 'BreadcrumbList',
    itemListElement: items.map((item, index) => ({
      '@type': 'ListItem',
      position: index + 1,
      name: item.name,
      item: item.url,
    })),
  };
}

/**
 * Generate ItemList schema for tools listing page
 */
export function generateToolsListSchema(tools: ToolInfo[]): ItemListSchema {
  return {
    '@context': 'https://schema.org',
    '@type': 'ItemList',
    name: 'Free SEO Tools',
    description: 'Free online SEO tools for meta tag analysis, keyword density checking, mobile-friendly testing, and social media previews.',
    numberOfItems: tools.length,
    itemListElement: tools.map((tool, index) => ({
      '@type': 'ListItem',
      position: index + 1,
      item: {
        '@type': 'SoftwareApplication',
        name: tool.name,
        applicationCategory: 'WebApplication',
        operatingSystem: 'Web Browser',
        offers: {
          '@type': 'Offer',
          price: '0',
          priceCurrency: 'USD',
        },
        description: tool.description,
      },
    })),
  };
}

/**
 * Generate breadcrumbs for a tool page
 */
export function generateToolBreadcrumbs(toolSlug: string, toolName: string): BreadcrumbItem[] {
  return [
    { name: 'Home', url: `${SITE_CONFIG.url}/` },
    { name: 'Tools', url: `${SITE_CONFIG.url}/tools/` },
    { name: toolName, url: getToolUrl(toolSlug) },
  ];
}

/**
 * Generate breadcrumbs for tools landing page
 */
export function generateToolsLandingBreadcrumbs(): BreadcrumbItem[] {
  return [
    { name: 'Home', url: `${SITE_CONFIG.url}/` },
    { name: 'Tools', url: `${SITE_CONFIG.url}/tools/` },
  ];
}

/**
 * Default FAQ items for tools pages
 */
export const DEFAULT_TOOL_FAQS: FAQItem[] = [
  {
    question: 'Are these SEO tools free?',
    answer: 'Yes, all SEO tools on SEOIndicator are completely free to use with no registration required.',
  },
  {
    question: 'How accurate are the tool results?',
    answer: 'Our tools use industry-standard algorithms and provide accurate results based on current SEO best practices.',
  },
  {
    question: 'Do I need to create an account?',
    answer: 'No account is needed for basic tool usage. Creating a free account unlocks additional features like history and reports.',
  },
  {
    question: 'Can I use these tools for client websites?',
    answer: 'Yes, you can use all our free tools to analyze any publicly accessible website, including client projects.',
  },
];

/**
 * Tool definitions for schema generation
 */
export const TOOLS: ToolInfo[] = [
  {
    slug: 'meta-tag-analyzer',
    name: 'Meta Tag Analyzer',
    description: 'Free online tool to analyze meta tags, title tags, and Open Graph tags for SEO optimization.',
    features: [
      'Title tag analysis',
      'Meta description check',
      'SERP preview',
      'Open Graph tags',
      'Twitter Card tags',
    ],
    relatedGuides: ['meta-title', 'meta-description', 'open-graph', 'twitter-cards'],
    image: 'https://seoindicator.com/images/tools/meta-tag-analyzer.png',
    rating: { value: 4.8, count: 1250 },
    status: 'available',
  },
  {
    slug: 'keyword-density',
    name: 'Keyword Density Checker',
    description: 'Analyze keyword frequency and density on any webpage. Free SEO tool for content optimization.',
    features: [
      'Density calculation',
      'Heading analysis',
      'Placement check',
      'Over-optimization warnings',
      'Export results',
    ],
    relatedGuides: ['keyword-optimization', 'content-optimization'],
    image: 'https://seoindicator.com/images/tools/keyword-density.png',
    rating: { value: 4.6, count: 980 },
    status: 'available',
  },
  {
    slug: 'social-preview',
    name: 'Social Media Preview',
    description: 'Preview how your pages appear on Facebook, Twitter, and LinkedIn. Free social card preview tool.',
    features: [
      'Facebook preview',
      'Twitter card',
      'LinkedIn preview',
      'Open Graph analysis',
    ],
    relatedGuides: ['open-graph', 'twitter-cards'],
    image: 'https://seoindicator.com/images/tools/social-preview.png',
    rating: { value: 4.5, count: 875 },
    status: 'available',
  },
];

/**
 * Get a tool by slug
 */
export function getToolBySlug(slug: string): ToolInfo | undefined {
  return TOOLS.find(tool => tool.slug === slug);
}

/**
 * Generate complete schema markup for a tool page
 * Returns both SoftwareApplication and BreadcrumbList schemas
 */
export function generateToolPageSchemas(tool: ToolInfo, includeFAQ = true): {
  softwareApplication: SoftwareApplicationSchema;
  breadcrumbs: BreadcrumbListSchema;
  faq?: FAQPageSchema;
} {
  const result = {
    softwareApplication: generateSoftwareApplicationSchema(tool),
    breadcrumbs: generateBreadcrumbSchema(generateToolBreadcrumbs(tool.slug, tool.name)),
    faq: includeFAQ ? generateFAQSchema(DEFAULT_TOOL_FAQS) : undefined,
  };

  return result;
}
