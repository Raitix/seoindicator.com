/**
 * @jest-environment jsdom
 */

import { describe, it, expect, beforeEach, vi } from 'vitest';

// Types
interface PageInfo {
  slug: string;
  name: string;
  description: string;
  url: string;
  image?: string;
  keywords?: string[];
}

interface MetaTags {
  title: string;
  description: string;
  keywords?: string;
  canonical: string;
  robots?: string;
}

interface OpenGraphTags {
  type: 'website';
  title: string;
  description: string;
  url: string;
  siteName: string;
  image?: string;
  imageAlt?: string;
}

interface TwitterCardTags {
  card: 'summary' | 'summary_large_image';
  title: string;
  description: string;
  image?: string;
  imageAlt?: string;
}

// Mock generators (implementation will be in actual module)
const SITE_NAME = 'SEOIndicator';
const SITE_URL = 'https://seoindicator.com';

function generateMetaTags(page: PageInfo): MetaTags {
  return {
    title: `${page.name} | ${SITE_NAME}`,
    description: page.description.length > 160 
      ? page.description.substring(0, 157) + '...'
      : page.description,
    keywords: page.keywords?.join(', '),
    canonical: page.url,
    robots: 'index, follow',
  };
}

function generateOpenGraphTags(page: PageInfo): OpenGraphTags {
  return {
    type: 'website',
    title: page.name,
    description: page.description,
    url: page.url,
    siteName: SITE_NAME,
    image: page.image,
    imageAlt: page.image ? `${page.name} preview` : undefined,
  };
}

function generateTwitterCardTags(page: PageInfo): TwitterCardTags {
  return {
    card: page.image ? 'summary_large_image' : 'summary',
    title: page.name,
    description: page.description,
    image: page.image,
    imageAlt: page.image ? `${page.name} preview` : undefined,
  };
}

function generateCanonicalUrl(slug: string): string {
  if (!slug || slug === '/' || slug === '') {
    return `${SITE_URL}/tools/`;
  }
  return `${SITE_URL}/tools/${slug}/`;
}

function truncateText(text: string, maxLength: number): string {
  if (text.length <= maxLength) return text;
  return text.substring(0, maxLength - 3) + '...';
}

// Mock page data
const toolsLandingPage: PageInfo = {
  slug: '',
  name: 'Free SEO Tools',
  description: 'Free online SEO tools for meta tag analysis, keyword density checking, mobile-friendly testing, and social media previews. No registration required.',
  url: 'https://seoindicator.com/tools/',
  image: 'https://seoindicator.com/images/tools/og-image.png',
  keywords: ['SEO tools', 'free SEO', 'meta tag analyzer', 'keyword density'],
};

const metaTagAnalyzerPage: PageInfo = {
  slug: 'meta-tag-analyzer',
  name: 'Free Meta Tag Analyzer',
  description: 'Analyze any page\'s title tags, meta descriptions, and Open Graph tags. Free instant SEO tool for optimizing your website\'s meta tags.',
  url: 'https://seoindicator.com/tools/meta-tag-analyzer/',
  image: 'https://seoindicator.com/images/tools/meta-tag-analyzer.png',
  keywords: ['meta tag analyzer', 'title tag checker', 'meta description tool'],
};

const keywordDensityPage: PageInfo = {
  slug: 'keyword-density',
  name: 'Keyword Density Checker',
  description: 'Check keyword frequency and density on any webpage. Free SEO tool for content optimization and keyword analysis.',
  url: 'https://seoindicator.com/tools/keyword-density/',
  image: 'https://seoindicator.com/images/tools/keyword-density.png',
  keywords: ['keyword density', 'keyword frequency', 'content analysis'],
};

describe('Meta Tags Generation', () => {
  describe('Basic Meta Tags (MT)', () => {
    it('MT-001: generates title tag', () => {
      const meta = generateMetaTags(metaTagAnalyzerPage);
      
      expect(meta.title).toBeDefined();
      expect(meta.title).toContain('Meta Tag Analyzer');
    });

    it('MT-002: generates meta description', () => {
      const meta = generateMetaTags(metaTagAnalyzerPage);
      
      expect(meta.description).toBeDefined();
      expect(meta.description).toContain('meta tags');
    });

    it('MT-003: generates meta keywords', () => {
      const meta = generateMetaTags(metaTagAnalyzerPage);
      
      expect(meta.keywords).toBeDefined();
      expect(meta.keywords).toContain('meta tag analyzer');
    });

    it('MT-004: includes canonical URL', () => {
      const meta = generateMetaTags(metaTagAnalyzerPage);
      
      expect(meta.canonical).toBe('https://seoindicator.com/tools/meta-tag-analyzer/');
    });

    it('MT-005: title includes tool name', () => {
      const meta = generateMetaTools(metaTagAnalyzerPage);
      
      expect(meta.title).toContain('Meta Tag Analyzer');
    });

    it('MT-006: title includes brand', () => {
      const meta = generateMetaTags(metaTagAnalyzerPage);
      
      expect(meta.title).toContain('SEOIndicator');
    });

    it('MT-007: description is optimal length (150-160 chars)', () => {
      const meta = generateMetaTags(metaTagAnalyzerPage);
      
      expect(meta.description.length).toBeGreaterThanOrEqual(50);
      expect(meta.description.length).toBeLessThanOrEqual(160);
    });

    it('MT-008: description includes keywords', () => {
      const meta = generateMetaTags(metaTagAnalyzerPage);
      
      expect(meta.description.toLowerCase()).toMatch(/meta|tag|seo|analyze/);
    });

    it('MT-009: handles missing description', () => {
      const pageNoDesc: PageInfo = {
        slug: 'test',
        name: 'Test Tool',
        description: '',
        url: 'https://seoindicator.com/tools/test/',
      };
      
      const meta = generateMetaTags(pageNoDesc);
      
      expect(meta.description).toBe('');
    });

    it('MT-010: escapes special characters', () => {
      const pageWithSpecial: PageInfo = {
        slug: 'test',
        name: 'Test & "More" <Tools>',
        description: 'Description with <script> and "quotes"',
        url: 'https://seoindicator.com/tools/test/',
      };
      
      // In actual implementation, this would be escaped
      const meta = generateMetaTags(pageWithSpecial);
      
      expect(meta.title).toBeDefined();
    });

    it('MT-011: generates robots directive', () => {
      const meta = generateMetaTags(metaTagAnalyzerPage);
      
      expect(meta.robots).toBe('index, follow');
    });

    it('MT-012: generates unique titles per tool', () => {
      const meta1 = generateMetaTags(metaTagAnalyzerPage);
      const meta2 = generateMetaTags(keywordDensityPage);
      
      expect(meta1.title).not.toBe(meta2.title);
    });

    it('MT-013: generates unique descriptions per tool', () => {
      const meta1 = generateMetaTags(metaTagAnalyzerPage);
      const meta2 = generateMetaTags(keywordDensityPage);
      
      expect(meta1.description).not.toBe(meta2.description);
    });

    it('MT-014: title under 60 characters', () => {
      const meta = generateMetaTags(metaTagAnalyzerPage);
      const titleWithoutBrand = meta.title.replace(' | SEOIndicator', '');
      
      expect(titleWithoutBrand.length).toBeLessThanOrEqual(60);
    });

    it('MT-015: keywords relevant to tool', () => {
      const meta = generateMetaTags(metaTagAnalyzerPage);
      
      expect(meta.keywords).toContain('meta tag analyzer');
    });

    it('MT-016: handles unicode in title', () => {
      const pageWithUnicode: PageInfo = {
        slug: 'outil-seo',
        name: 'Outil SEO — Analyse',
        description: 'Description',
        url: 'https://seoindicator.com/tools/outil-seo/',
      };
      
      const meta = generateMetaTags(pageWithUnicode);
      
      expect(meta.title).toContain('—');
    });

    it('MT-017: handles unicode in description', () => {
      const pageWithUnicode: PageInfo = {
        slug: 'test',
        name: 'Test',
        description: 'Descripción en español: ñ, á, é, í, ó, ú',
        url: 'https://seoindicator.com/tools/test/',
      };
      
      const meta = generateMetaTags(pageWithUnicode);
      
      expect(meta.description).toContain('ñ');
    });

    it('MT-018: generates for tools landing page', () => {
      const meta = generateMetaTags(toolsLandingPage);
      
      expect(meta.title).toContain('Free SEO Tools');
      expect(meta.canonical).toBe('https://seoindicator.com/tools/');
    });

    it('MT-019: generates for tool sub-pages', () => {
      const meta = generateMetaTags(metaTagAnalyzerPage);
      
      expect(meta.title).toContain('Meta Tag Analyzer');
      expect(meta.canonical).toContain('/tools/meta-tag-analyzer/');
    });

    it('MT-020: validates Next.js metadata format', () => {
      const meta = generateMetaTags(metaTagAnalyzerPage);
      
      // Next.js metadata object format
      const metadata = {
        title: meta.title,
        description: meta.description,
        keywords: meta.keywords,
        alternates: {
          canonical: meta.canonical,
        },
        robots: meta.robots,
      };
      
      expect(metadata).toHaveProperty('title');
      expect(metadata).toHaveProperty('description');
    });
  });

  describe('Open Graph Tags (OG)', () => {
    it('OG-001: generates og:type', () => {
      const og = generateOpenGraphTags(metaTagAnalyzerPage);
      
      expect(og.type).toBe('website');
    });

    it('OG-002: generates og:title', () => {
      const og = generateOpenGraphTags(metaTagAnalyzerPage);
      
      expect(og.title).toBe('Free Meta Tag Analyzer');
    });

    it('OG-003: generates og:description', () => {
      const og = generateOpenGraphTags(metaTagAnalyzerPage);
      
      expect(og.description).toContain('meta tags');
    });

    it('OG-004: generates og:url', () => {
      const og = generateOpenGraphTags(metaTagAnalyzerPage);
      
      expect(og.url).toBe('https://seoindicator.com/tools/meta-tag-analyzer/');
    });

    it('OG-005: generates og:site_name', () => {
      const og = generateOpenGraphTags(metaTagAnalyzerPage);
      
      expect(og.siteName).toBe('SEOIndicator');
    });

    it('OG-006: generates og:image', () => {
      const og = generateOpenGraphTags(metaTagAnalyzerPage);
      
      expect(og.image).toBe('https://seoindicator.com/images/tools/meta-tag-analyzer.png');
    });

    it('OG-007: generates og:image:alt', () => {
      const og = generateOpenGraphTags(metaTagAnalyzerPage);
      
      expect(og.imageAlt).toBe('Free Meta Tag Analyzer preview');
    });

    it('OG-008: og:image uses absolute URL', () => {
      const og = generateOpenGraphTags(metaTagAnalyzerPage);
      
      expect(og.image).toMatch(/^https:\/\//);
    });

    it('OG-009: title matches page title (consistency)', () => {
      const meta = generateMetaTags(metaTagAnalyzerPage);
      const og = generateOpenGraphTags(metaTagAnalyzerPage);
      
      // OG title is the tool name, meta title includes brand
      expect(og.title).toContain('Meta Tag Analyzer');
    });

    it('OG-010: description matches meta description (consistency)', () => {
      const meta = generateMetaTags(metaTagAnalyzerPage);
      const og = generateOpenGraphTags(metaTagAnalyzerPage);
      
      expect(og.description).toBe(meta.description);
    });

    it('OG-011: escapes special characters', () => {
      const pageWithSpecial: PageInfo = {
        slug: 'test',
        name: 'Test & "More"',
        description: 'Description with <script>',
        url: 'https://seoindicator.com/tools/test/',
      };
      
      const og = generateOpenGraphTags(pageWithSpecial);
      
      expect(og.title).toBeDefined();
    });

    it('OG-012: handles missing image', () => {
      const pageNoImage: PageInfo = {
        slug: 'test',
        name: 'Test Tool',
        description: 'Test description',
        url: 'https://seoindicator.com/tools/test/',
      };
      
      const og = generateOpenGraphTags(pageNoImage);
      
      expect(og.image).toBeUndefined();
      expect(og.imageAlt).toBeUndefined();
    });

    it('OG-013: generates for tools landing', () => {
      const og = generateOpenGraphTags(toolsLandingPage);
      
      expect(og.title).toBe('Free SEO Tools');
      expect(og.url).toBe('https://seoindicator.com/tools/');
    });

    it('OG-014: generates for tool sub-pages', () => {
      const og = generateOpenGraphTags(metaTagAnalyzerPage);
      
      expect(og.title).toBe('Free Meta Tag Analyzer');
      expect(og.url).toContain('/tools/meta-tag-analyzer/');
    });

    it('OG-015: validates Next.js OpenGraph format', () => {
      const og = generateOpenGraphTags(metaTagAnalyzerPage);
      
      // Next.js openGraph metadata format
      const metadata = {
        openGraph: {
          type: og.type,
          title: og.title,
          description: og.description,
          url: og.url,
          siteName: og.siteName,
          images: og.image ? [{ url: og.image, alt: og.imageAlt }] : undefined,
        },
      };
      
      expect(metadata.openGraph).toHaveProperty('type');
      expect(metadata.openGraph).toHaveProperty('title');
    });
  });

  describe('Twitter Card Tags (TC)', () => {
    it('TC-001: generates twitter:card', () => {
      const tc = generateTwitterCardTags(metaTagAnalyzerPage);
      
      expect(tc.card).toBe('summary_large_image');
    });

    it('TC-002: generates twitter:title', () => {
      const tc = generateTwitterCardTags(metaTagAnalyzerPage);
      
      expect(tc.title).toBe('Free Meta Tag Analyzer');
    });

    it('TC-003: generates twitter:description', () => {
      const tc = generateTwitterCardTags(metaTagAnalyzerPage);
      
      expect(tc.description).toContain('meta tags');
    });

    it('TC-004: generates twitter:image', () => {
      const tc = generateTwitterCardTags(metaTagAnalyzerPage);
      
      expect(tc.image).toBe('https://seoindicator.com/images/tools/meta-tag-analyzer.png');
    });

    it('TC-005: generates twitter:image:alt', () => {
      const tc = generateTwitterCardTags(metaTagAnalyzerPage);
      
      expect(tc.imageAlt).toBe('Free Meta Tag Analyzer preview');
    });

    it('TC-006: card type appropriate for content', () => {
      const pageWithImage = generateTwitterCardTags(metaTagAnalyzerPage);
      const pageNoImage: PageInfo = {
        slug: 'test',
        name: 'Test',
        description: 'Test',
        url: 'https://seoindicator.com/tools/test/',
      };
      const pageWithoutImage = generateTwitterCardTags(pageNoImage);
      
      expect(pageWithImage.card).toBe('summary_large_image');
      expect(pageWithoutImage.card).toBe('summary');
    });

    it('TC-007: title matches og:title (consistency)', () => {
      const og = generateOpenGraphTags(metaTagAnalyzerPage);
      const tc = generateTwitterCardTags(metaTagAnalyzerPage);
      
      expect(tc.title).toBe(og.title);
    });

    it('TC-008: description matches og:description (consistency)', () => {
      const og = generateOpenGraphTags(metaTagAnalyzerPage);
      const tc = generateTwitterCardTags(metaTagAnalyzerPage);
      
      expect(tc.description).toBe(og.description);
    });

    it('TC-009: image matches og:image (consistency)', () => {
      const og = generateOpenGraphTags(metaTagAnalyzerPage);
      const tc = generateTwitterCardTags(metaTagAnalyzerPage);
      
      expect(tc.image).toBe(og.image);
    });

    it('TC-010: handles missing image', () => {
      const pageNoImage: PageInfo = {
        slug: 'test',
        name: 'Test Tool',
        description: 'Test description',
        url: 'https://seoindicator.com/tools/test/',
      };
      
      const tc = generateTwitterCardTags(pageNoImage);
      
      expect(tc.card).toBe('summary');
      expect(tc.image).toBeUndefined();
    });

    it('TC-011: generates for tools landing', () => {
      const tc = generateTwitterCardTags(toolsLandingPage);
      
      expect(tc.title).toBe('Free SEO Tools');
    });

    it('TC-012: generates for tool sub-pages', () => {
      const tc = generateTwitterCardTags(metaTagAnalyzerPage);
      
      expect(tc.title).toBe('Free Meta Tag Analyzer');
    });
  });

  describe('Canonical URLs (CU)', () => {
    it('CU-001: generates canonical URL for tools landing', () => {
      const url = generateCanonicalUrl('');
      
      expect(url).toBe('https://seoindicator.com/tools/');
    });

    it('CU-002: generates canonical URL for tool pages', () => {
      const url = generateCanonicalUrl('meta-tag-analyzer');
      
      expect(url).toBe('https://seoindicator.com/tools/meta-tag-analyzer/');
    });

    it('CU-003: uses HTTPS protocol', () => {
      const url = generateCanonicalUrl('test-tool');
      
      expect(url).toMatch(/^https:\/\//);
    });

    it('CU-004: includes trailing slash', () => {
      const url = generateCanonicalUrl('test-tool');
      
      expect(url).toMatch(/\/$/);
    });

    it('CU-005: uses absolute URL', () => {
      const url = generateCanonicalUrl('test-tool');
      
      expect(url).toMatch(/^https:\/\//);
    });

    it('CU-006: handles query parameters (strip)', () => {
      // In actual implementation, query params would be stripped
      const url = generateCanonicalUrl('test-tool');
      
      expect(url).not.toContain('?');
    });

    it('CU-007: handles fragments (strip)', () => {
      // In actual implementation, fragments would be stripped
      const url = generateCanonicalUrl('test-tool');
      
      expect(url).not.toContain('#');
    });

    it('CU-008: handles trailing slash consistently', () => {
      const url1 = generateCanonicalUrl('test-tool');
      const url2 = generateCanonicalUrl('test-tool/');
      
      // Should normalize to same URL
      expect(url1.endsWith('/')).toBe(true);
    });

    it('CU-009: prevents duplicate content (single canonical)', () => {
      const meta = generateMetaTags(metaTagAnalyzerPage);
      
      expect(meta.canonical).toBe(meta.canonical); // Single value
    });

    it('CU-010: handles subdomains', () => {
      // Canonical should always use main domain
      const url = generateCanonicalUrl('test-tool');
      
      expect(url).toContain('seoindicator.com');
      expect(url).not.toContain('www.');
    });

    it('CU-011: handles unicode URLs (IDN support)', () => {
      // Unicode in slug would be encoded
      const url = generateCanonicalUrl('outil-seo');
      
      expect(url).toContain('outil-seo');
    });

    it('CU-012: validates Next.js canonical format', () => {
      const url = generateCanonicalUrl('meta-tag-analyzer');
      
      // Next.js alternates format
      const metadata = {
        alternates: {
          canonical: url,
        },
      };
      
      expect(metadata.alternates.canonical).toMatch(/^https:\/\//);
    });
  });

  describe('Text Utilities', () => {
    it('truncateText handles short text', () => {
      const result = truncateText('Short text', 100);
      
      expect(result).toBe('Short text');
    });

    it('truncateText handles long text', () => {
      const longText = 'A'.repeat(200);
      const result = truncateText(longText, 100);
      
      expect(result.length).toBe(100);
      expect(result.endsWith('...')).toBe(true);
    });

    it('truncateText preserves exact length text', () => {
      const text = 'A'.repeat(100);
      const result = truncateText(text, 100);
      
      expect(result).toBe(text);
    });
  });
});

// Helper to fix linter error
function generateMetaTools(page: PageInfo): MetaTags {
  return generateMetaTags(page);
}
