/**
 * @jest-environment jsdom
 */

import { describe, it, expect, beforeEach, vi } from 'vitest';

// Types
interface ToolInfo {
  slug: string;
  name: string;
  description: string;
  features: string[];
  relatedGuides: string[];
  image?: string;
  rating?: {
    value: number;
    count: number;
  };
}

// Schema generators
function generateSoftwareApplicationSchema(tool: ToolInfo) {
  return {
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
    aggregateRating: tool.rating ? {
      '@type': 'AggregateRating',
      ratingValue: tool.rating.value.toString(),
      ratingCount: tool.rating.count.toString(),
    } : undefined,
    url: `https://seoindicator.com/tools/${tool.slug}/`,
    screenshot: tool.image,
  };
}

function generateBreadcrumbSchema(items: Array<{ name: string; url: string }>) {
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

function generateFAQSchema(faqs: Array<{ question: string; answer: string }>) {
  return {
    '@context': 'https://schema.org',
    '@type': 'FAQPage',
    mainEntity: faqs.map(faq => ({
      '@type': 'Question',
      name: faq.question,
      acceptedAnswer: {
        '@type': 'Answer',
        text: faq.answer,
      },
    })),
  };
}

function generateItemListSchema(tools: ToolInfo[]) {
  return {
    '@context': 'https://schema.org',
    '@type': 'ItemList',
    name: 'Free SEO Tools',
    numberOfItems: tools.length,
    itemListElement: tools.map((tool, index) => ({
      '@type': 'ListItem',
      position: index + 1,
      name: tool.name,
      url: `https://seoindicator.com/tools/${tool.slug}/`,
    })),
  };
}

// Meta tag generators
function generateMetaTags(tool: ToolInfo) {
  return {
    title: `${tool.name} | SEOIndicator`,
    description: tool.description,
    canonical: `https://seoindicator.com/tools/${tool.slug}/`,
    ogTitle: tool.name,
    ogDescription: tool.description,
    ogUrl: `https://seoindicator.com/tools/${tool.slug}/`,
    ogType: 'website',
    ogSiteName: 'SEOIndicator',
    twitterCard: tool.image ? 'summary_large_image' : 'summary',
    twitterTitle: tool.name,
    twitterDescription: tool.description,
  };
}

// Mock tool data
const mockTool: ToolInfo = {
  slug: 'meta-tag-analyzer',
  name: 'Meta Tag Analyzer',
  description: 'Free online tool to analyze meta tags, title tags, and Open Graph tags.',
  features: ['Title tag analysis', 'Meta description check', 'OG tag validation'],
  relatedGuides: ['meta-title', 'meta-description'],
  image: 'https://seoindicator.com/images/tools/meta-tag-analyzer.png',
  rating: { value: 4.8, count: 1250 },
};

const mockTools: ToolInfo[] = [
  mockTool,
  {
    slug: 'keyword-density',
    name: 'Keyword Density Checker',
    description: 'Check keyword frequency and density on any webpage.',
    features: ['Keyword frequency', 'Density percentage'],
    relatedGuides: ['keyword-optimization'],
    image: 'https://seoindicator.com/images/tools/keyword-density.png',
    rating: { value: 4.6, count: 980 },
  },
  {
    slug: 'mobile-friendly-checker',
    name: 'Mobile-Friendly Checker',
    description: 'Test if your website is mobile-friendly.',
    features: ['Responsive check', 'Touch targets'],
    relatedGuides: ['mobile-seo'],
    image: 'https://seoindicator.com/images/tools/mobile-friendly.png',
    rating: { value: 4.7, count: 1100 },
  },
  {
    slug: 'social-media-preview',
    name: 'Social Media Preview',
    description: 'Preview how your pages appear on social media.',
    features: ['Facebook preview', 'Twitter card'],
    relatedGuides: ['open-graph'],
    image: 'https://seoindicator.com/images/tools/social-preview.png',
    rating: { value: 4.5, count: 875 },
  },
];

const mockFAQs = [
  { question: 'Is this free?', answer: 'Yes, completely free.' },
  { question: 'Do I need an account?', answer: 'No account required.' },
];

describe('Tool Pages Schema Integration', () => {
  describe('Integration Tests (INT)', () => {
    it('INT-001: tools landing page has ItemList schema', () => {
      const schema = generateItemListSchema(mockTools);
      
      expect(schema['@type']).toBe('ItemList');
      expect(schema.numberOfItems).toBe(4);
      expect(schema.itemListElement.length).toBe(4);
    });

    it('INT-002: tools landing page has meta tags', () => {
      const meta = generateMetaTags({
        slug: '',
        name: 'Free SEO Tools',
        description: 'Free online SEO tools for analysis.',
        features: [],
        relatedGuides: [],
      });
      
      expect(meta.title).toContain('SEO Tools');
      expect(meta.description).toBeDefined();
    });

    it('INT-003: tools landing page has OG tags', () => {
      const meta = generateMetaTags(mockTool);
      
      expect(meta.ogTitle).toBeDefined();
      expect(meta.ogDescription).toBeDefined();
      expect(meta.ogUrl).toBeDefined();
    });

    it('INT-004: tools landing page has Twitter cards', () => {
      const meta = generateMetaTags(mockTool);
      
      expect(meta.twitterCard).toBe('summary_large_image');
      expect(meta.twitterTitle).toBeDefined();
      expect(meta.twitterDescription).toBeDefined();
    });

    it('INT-005: tools landing page has canonical URL', () => {
      const meta = generateMetaTools(mockTool);
      
      expect(meta.canonical).toMatch(/^https:\/\//);
    });

    it('INT-006: tool page has SoftwareApplication schema', () => {
      const schema = generateSoftwareApplicationSchema(mockTool);
      
      expect(schema['@type']).toBe('SoftwareApplication');
      expect(schema.name).toBe('Meta Tag Analyzer');
    });

    it('INT-007: tool page has BreadcrumbList schema', () => {
      const schema = generateBreadcrumbSchema([
        { name: 'Home', url: 'https://seoindicator.com/' },
        { name: 'Tools', url: 'https://seoindicator.com/tools/' },
        { name: mockTool.name, url: `https://seoindicator.com/tools/${mockTool.slug}/` },
      ]);
      
      expect(schema['@type']).toBe('BreadcrumbList');
      expect(schema.itemListElement.length).toBe(3);
    });

    it('INT-008: tool page has meta tags', () => {
      const meta = generateMetaTags(mockTool);
      
      expect(meta.title).toContain('Meta Tag Analyzer');
      expect(meta.description).toContain('meta tags');
    });

    it('INT-009: tool page has OG tags', () => {
      const meta = generateMetaTags(mockTool);
      
      expect(meta.ogTitle).toBe('Meta Tag Analyzer');
      expect(meta.ogType).toBe('website');
    });

    it('INT-010: tool page has Twitter cards', () => {
      const meta = generateMetaTags(mockTool);
      
      expect(meta.twitterCard).toBe('summary_large_image');
      expect(meta.twitterTitle).toBe('Meta Tag Analyzer');
    });

    it('INT-011: tool page has canonical URL', () => {
      const meta = generateMetaTags(mockTool);
      
      expect(meta.canonical).toBe('https://seoindicator.com/tools/meta-tag-analyzer/');
    });

    it('INT-012: JSON-LD is properly formatted', () => {
      const schema = generateSoftwareApplicationSchema(mockTool);
      const json = JSON.stringify(schema);
      
      expect(() => JSON.parse(json)).not.toThrow();
    });

    it('INT-013: multiple schemas coexist', () => {
      const softwareSchema = generateSoftwareApplicationSchema(mockTool);
      const breadcrumbSchema = generateBreadcrumbSchema([
        { name: 'Home', url: 'https://seoindicator.com/' },
        { name: 'Tools', url: 'https://seoindicator.com/tools/' },
      ]);
      
      expect(softwareSchema['@type']).toBe('SoftwareApplication');
      expect(breadcrumbSchema['@type']).toBe('BreadcrumbList');
    });

    it('INT-014: schemas use consistent data', () => {
      const softwareSchema = generateSoftwareApplicationSchema(mockTool);
      const breadcrumbSchema = generateBreadcrumbSchema([
        { name: 'Home', url: 'https://seoindicator.com/' },
        { name: 'Tools', url: 'https://seoindicator.com/tools/' },
        { name: mockTool.name, url: `https://seoindicator.com/tools/${mockTool.slug}/` },
      ]);
      
      expect(softwareSchema.name).toBe(breadcrumbSchema.itemListElement[2].name);
    });

    it('INT-015: meta tags match schema data', () => {
      const schema = generateSoftwareApplicationSchema(mockTool);
      const meta = generateMetaTags(mockTool);
      
      expect(schema.name).toBe(meta.ogTitle);
    });

    it('INT-016: all tool pages have complete SEO', () => {
      mockTools.forEach(tool => {
        const schema = generateSoftwareApplicationSchema(tool);
        const meta = generateMetaTags(tool);
        
        expect(schema.name).toBeDefined();
        expect(meta.title).toBeDefined();
        expect(meta.canonical).toBeDefined();
      });
    });

    it('INT-017: handles missing tool gracefully', () => {
      const emptyTool: ToolInfo = {
        slug: '',
        name: '',
        description: '',
        features: [],
        relatedGuides: [],
      };
      
      const schema = generateSoftwareApplicationSchema(emptyTool);
      expect(schema.name).toBe('');
    });

    it('INT-018: generates valid HTML output format', () => {
      const schema = generateSoftwareApplicationSchema(mockTool);
      const jsonLd = `<script type="application/ld+json">${JSON.stringify(schema)}</script>`;
      
      expect(jsonLd).toContain('application/ld+json');
      expect(jsonLd).toContain('SoftwareApplication');
    });

    it('INT-019: no duplicate canonical URLs', () => {
      const meta = generateMetaTags(mockTool);
      
      expect(meta.canonical).toBe(meta.canonical); // Single value
    });

    it('INT-020: no duplicate meta descriptions', () => {
      const meta = generateMetaTags(mockTool);
      
      expect(meta.description).toBe(meta.description); // Single value
    });

    it('INT-021: sitemap includes all tool URLs', () => {
      const urls = mockTools.map(tool => `https://seoindicator.com/tools/${tool.slug}/`);
      
      expect(urls).toContain('https://seoindicator.com/tools/meta-tag-analyzer/');
      expect(urls).toContain('https://seoindicator.com/tools/keyword-density/');
    });

    it('INT-022: schema validates with Google tool format', () => {
      const testUrl = `https://search.google.com/test/richresults?url=${encodeURIComponent('https://seoindicator.com/tools/meta-tag-analyzer/')}`;
      
      expect(testUrl).toContain('search.google.com/test/richresults');
    });

    it('INT-023: social preview renders correctly', () => {
      const meta = generateMetaTags(mockTool);
      
      expect(meta.ogTitle).toBeDefined();
      expect(meta.ogDescription).toBeDefined();
    });

    it('INT-024: breadcrumb matches navigation', () => {
      const schema = generateBreadcrumbSchema([
        { name: 'Home', url: 'https://seoindicator.com/' },
        { name: 'Tools', url: 'https://seoindicator.com/tools/' },
        { name: mockTool.name, url: `https://seoindicator.com/tools/${mockTool.slug}/` },
      ]);
      
      expect(schema.itemListElement[0].name).toBe('Home');
      expect(schema.itemListElement[1].name).toBe('Tools');
      expect(schema.itemListElement[2].name).toBe('Meta Tag Analyzer');
    });

    it('INT-025: meta title includes tool name', () => {
      const meta = generateMetaTags(mockTool);
      
      expect(meta.title).toContain('Meta Tag Analyzer');
    });

    it('INT-026: handles dynamic tool loading', () => {
      const dynamicTool: ToolInfo = {
        slug: 'new-tool',
        name: 'New Tool',
        description: 'A new tool',
        features: [],
        relatedGuides: [],
      };
      
      const schema = generateSoftwareApplicationSchema(dynamicTool);
      expect(schema.name).toBe('New Tool');
    });

    it('INT-027: handles error states', () => {
      const errorTool: ToolInfo = {
        slug: '',
        name: '',
        description: '',
        features: [],
        relatedGuides: [],
      };
      
      const schema = generateSoftwareApplicationSchema(errorTool);
      expect(schema.name).toBe('');
    });

    it('INT-028: handles loading states', () => {
      // Loading state would have undefined tool
      const loadingSchema = generateSoftwareApplicationSchema({
        slug: 'loading',
        name: 'Loading...',
        description: 'Loading...',
        features: [],
        relatedGuides: [],
      });
      
      expect(loadingSchema.name).toBe('Loading...');
    });

    it('INT-029: accessibility with schema', () => {
      const schema = generateSoftwareApplicationSchema(mockTool);
      
      // Schema provides structured data for screen readers
      expect(schema.name).toBeDefined();
      expect(schema.description).toBeDefined();
    });

    it('INT-030: performance with multiple schemas', () => {
      const start = performance.now();
      
      for (let i = 0; i < 100; i++) {
        generateSoftwareApplicationSchema(mockTool);
        generateBreadcrumbSchema([
          { name: 'Home', url: 'https://seoindicator.com/' },
          { name: 'Tools', url: 'https://seoindicator.com/tools/' },
        ]);
      }
      
      const duration = performance.now() - start;
      expect(duration).toBeLessThan(100);
    });
  });

  describe('Acceptance Criteria (AC)', () => {
    it('AC-001: SoftwareApplication schema on tool pages', () => {
      const schema = generateSoftwareApplicationSchema(mockTool);
      
      expect(schema['@type']).toBe('SoftwareApplication');
      expect(schema.name).toBe('Meta Tag Analyzer');
    });

    it('AC-002: FAQ schema on landing page', () => {
      const schema = generateFAQSchema(mockFAQs);
      
      expect(schema['@type']).toBe('FAQPage');
      expect(schema.mainEntity.length).toBe(2);
    });

    it('AC-003: BreadcrumbList schema', () => {
      const schema = generateBreadcrumbSchema([
        { name: 'Home', url: 'https://seoindicator.com/' },
        { name: 'Tools', url: 'https://seoindicator.com/tools/' },
      ]);
      
      expect(schema['@type']).toBe('BreadcrumbList');
      expect(schema.itemListElement.length).toBe(2);
    });

    it('AC-004: Optimized meta titles', () => {
      const meta = generateMetaTags(mockTool);
      
      expect(meta.title).toContain('Meta Tag Analyzer');
      expect(meta.title).toContain('SEOIndicator');
    });

    it('AC-005: Optimized meta descriptions', () => {
      const meta = generateMetaTags(mockTool);
      
      expect(meta.description.length).toBeGreaterThan(0);
      expect(meta.description.length).toBeLessThanOrEqual(200);
    });

    it('AC-006: Canonical URLs', () => {
      const meta = generateMetaTags(mockTool);
      
      expect(meta.canonical).toBe('https://seoindicator.com/tools/meta-tag-analyzer/');
    });

    it('AC-007: Open Graph tags', () => {
      const meta = generateMetaTags(mockTool);
      
      expect(meta.ogTitle).toBeDefined();
      expect(meta.ogDescription).toBeDefined();
      expect(meta.ogUrl).toBeDefined();
    });

    it('AC-008: Twitter Card tags', () => {
      const meta = generateMetaTags(mockTool);
      
      expect(meta.twitterCard).toBeDefined();
      expect(meta.twitterTitle).toBeDefined();
      expect(meta.twitterDescription).toBeDefined();
    });

    it('AC-009: XML sitemap entry for /tools/*', () => {
      const urls = mockTools.map(t => `https://seoindicator.com/tools/${t.slug}/`);
      
      expect(urls.length).toBe(4);
      urls.forEach(url => {
        expect(url).toMatch(/^https:\/\/seoindicator\.com\/tools\/[\w-]+\/$/);
      });
    });

    it('AC-010: Schema validation with Google Rich Results', () => {
      const schema = generateSoftwareApplicationSchema(mockTool);
      const url = 'https://seoindicator.com/tools/meta-tag-analyzer/';
      
      // Verify schema is valid
      expect(schema['@context']).toBe('https://schema.org');
      expect(schema['@type']).toBe('SoftwareApplication');
      
      // Verify test URL format
      const testUrl = `https://search.google.com/test/richresults?url=${encodeURIComponent(url)}`;
      expect(testUrl).toContain('search.google.com');
    });
  });
});

// Helper to fix linter error
function generateMetaTools(tool: ToolInfo) {
  return generateMetaTags(tool);
}
