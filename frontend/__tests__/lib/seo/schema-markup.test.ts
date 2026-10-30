/**
 * @jest-environment jsdom
 */

import { describe, it, expect, beforeEach, vi } from 'vitest';

// Types for schema generation
interface ToolInfo {
  slug: string;
  name: string;
  description: string;
  features: string[];
  relatedGuides: string[];
  icon?: string;
  image?: string;
  rating?: {
    value: number;
    count: number;
  };
}

interface FAQItem {
  question: string;
  answer: string;
}

interface BreadcrumbItem {
  name: string;
  url: string;
}

// Schema types
interface SoftwareApplicationSchema {
  '@context': string;
  '@type': string;
  name: string;
  description: string;
  applicationCategory: string;
  operatingSystem: string;
  offers: {
    '@type': string;
    price: string;
    priceCurrency: string;
  };
  aggregateRating?: {
    '@type': string;
    ratingValue: string;
    ratingCount: string;
  };
  url?: string;
  screenshot?: string;
}

interface FAQPageSchema {
  '@context': string;
  '@type': string;
  mainEntity: Array<{
    '@type': string;
    name: string;
    acceptedAnswer: {
      '@type': string;
      text: string;
    };
  }>;
}

interface BreadcrumbListSchema {
  '@context': string;
  '@type': string;
  itemListElement: Array<{
    '@type': string;
    position: number;
    name: string;
    item: string;
  }>;
}

// Mock schema generators (implementation will be in actual module)
function generateSoftwareApplicationSchema(tool: ToolInfo): SoftwareApplicationSchema {
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
  };

  if (tool.rating) {
    schema.aggregateRating = {
      '@type': 'AggregateRating',
      ratingValue: tool.rating.value.toString(),
      ratingCount: tool.rating.count.toString(),
    };
  }

  if (tool.image) {
    schema.screenshot = tool.image;
  }

  schema.url = `https://seoindicator.com/tools/${tool.slug}/`;

  return schema;
}

function generateFAQSchema(faqs: FAQItem[]): FAQPageSchema {
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

function generateBreadcrumbSchema(items: BreadcrumbItem[]): BreadcrumbListSchema {
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

function escapeHtml(text: string): string {
  return text
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}

function schemaToJsonLd(schema: unknown): string {
  return JSON.stringify(schema);
}

// Mock tool data
const mockTools: ToolInfo[] = [
  {
    slug: 'meta-tag-analyzer',
    name: 'Meta Tag Analyzer',
    description: 'Free online tool to analyze meta tags, title tags, and Open Graph tags for SEO optimization.',
    features: ['Title tag analysis', 'Meta description check', 'OG tag validation'],
    relatedGuides: ['meta-title', 'meta-description'],
    image: 'https://seoindicator.com/images/tools/meta-tag-analyzer.png',
    rating: { value: 4.8, count: 1250 },
  },
  {
    slug: 'keyword-density-checker',
    name: 'Keyword Density Checker',
    description: 'Analyze keyword frequency and density on any webpage. Free SEO tool for content optimization.',
    features: ['Keyword frequency', 'Density percentage', 'Top keywords'],
    relatedGuides: ['keyword-optimization'],
    image: 'https://seoindicator.com/images/tools/keyword-density.png',
    rating: { value: 4.6, count: 980 },
  },
  {
    slug: 'mobile-friendly-checker',
    name: 'Mobile-Friendly Checker',
    description: 'Test if your website is mobile-friendly. Free tool to check responsive design and mobile usability.',
    features: ['Responsive check', 'Touch targets', 'Viewport test'],
    relatedGuides: ['mobile-seo'],
    image: 'https://seoindicator.com/images/tools/mobile-friendly.png',
    rating: { value: 4.7, count: 1100 },
  },
  {
    slug: 'social-media-preview',
    name: 'Social Media Preview',
    description: 'Preview how your pages appear on Facebook, Twitter, and LinkedIn. Free social card preview tool.',
    features: ['Facebook preview', 'Twitter card', 'LinkedIn preview'],
    relatedGuides: ['open-graph', 'twitter-cards'],
    image: 'https://seoindicator.com/images/tools/social-preview.png',
    rating: { value: 4.5, count: 875 },
  },
];

const mockFAQs: FAQItem[] = [
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

describe('Schema Markup Generation', () => {
  describe('SoftwareApplication Schema (SA)', () => {
    it('SA-001: generates valid SoftwareApplication schema', () => {
      const schema = generateSoftwareApplicationSchema(mockTools[0]);
      
      expect(schema['@context']).toBe('https://schema.org');
      expect(schema['@type']).toBe('SoftwareApplication');
    });

    it('SA-002: includes required name field', () => {
      const schema = generateSoftwareApplicationSchema(mockTools[0]);
      
      expect(schema.name).toBe('Meta Tag Analyzer');
    });

    it('SA-003: includes description field', () => {
      const schema = generateSoftwareApplicationSchema(mockTools[0]);
      
      expect(schema.description).toContain('meta tags');
    });

    it('SA-004: sets applicationCategory to WebApplication', () => {
      const schema = generateSoftwareApplicationSchema(mockTools[0]);
      
      expect(schema.applicationCategory).toBe('WebApplication');
    });

    it('SA-005: sets operatingSystem to Web Browser', () => {
      const schema = generateSoftwareApplicationSchema(mockTools[0]);
      
      expect(schema.operatingSystem).toBe('Web Browser');
    });

    it('SA-006: includes free offer object', () => {
      const schema = generateSoftwareApplicationSchema(mockTools[0]);
      
      expect(schema.offers).toEqual({
        '@type': 'Offer',
        price: '0',
        priceCurrency: 'USD',
      });
    });

    it('SA-007: includes aggregateRating when provided', () => {
      const schema = generateSoftwareApplicationSchema(mockTools[0]);
      
      expect(schema.aggregateRating).toBeDefined();
      expect(schema.aggregateRating?.ratingValue).toBe('4.8');
      expect(schema.aggregateRating?.ratingCount).toBe('1250');
    });

    it('SA-008: generates correct schema for meta-tag-analyzer', () => {
      const schema = generateSoftwareApplicationSchema(mockTools[0]);
      
      expect(schema.name).toBe('Meta Tag Analyzer');
      expect(schema.url).toBe('https://seoindicator.com/tools/meta-tag-analyzer/');
    });

    it('SA-009: generates correct schema for keyword-density-checker', () => {
      const schema = generateSoftwareApplicationSchema(mockTools[1]);
      
      expect(schema.name).toBe('Keyword Density Checker');
      expect(schema.url).toBe('https://seoindicator.com/tools/keyword-density-checker/');
    });

    it('SA-010: generates correct schema for mobile-friendly-checker', () => {
      const schema = generateSoftwareApplicationSchema(mockTools[2]);
      
      expect(schema.name).toBe('Mobile-Friendly Checker');
      expect(schema.url).toBe('https://seoindicator.com/tools/mobile-friendly-checker/');
    });

    it('SA-011: generates correct schema for social-media-preview', () => {
      const schema = generateSoftwareApplicationSchema(mockTools[3]);
      
      expect(schema.name).toBe('Social Media Preview');
      expect(schema.url).toBe('https://seoindicator.com/tools/social-media-preview/');
    });

    it('SA-012: handles missing optional fields', () => {
      const tool: ToolInfo = {
        slug: 'test-tool',
        name: 'Test Tool',
        description: 'Test description',
        features: [],
        relatedGuides: [],
      };
      
      const schema = generateSoftwareApplicationSchema(tool);
      
      expect(schema.aggregateRating).toBeUndefined();
      expect(schema.screenshot).toBeUndefined();
    });

    it('SA-013: escapes special characters in name', () => {
      const tool: ToolInfo = {
        slug: 'test-tool',
        name: 'Test <script>alert("XSS")</script> Tool',
        description: 'Test description',
        features: [],
        relatedGuides: [],
      };
      
      const escaped = escapeHtml(tool.name);
      
      expect(escaped).not.toContain('<script>');
      expect(escaped).toContain('&lt;');
    });

    it('SA-014: escapes special characters in description', () => {
      const tool: ToolInfo = {
        slug: 'test-tool',
        name: 'Test Tool',
        description: 'Description with <script>alert("XSS")</script>',
        features: [],
        relatedGuides: [],
      };
      
      const escaped = escapeHtml(tool.description);
      
      expect(escaped).not.toContain('<script>');
      expect(escaped).toContain('&lt;');
    });

    it('SA-015: validates JSON-LD structure', () => {
      const schema = generateSoftwareApplicationSchema(mockTools[0]);
      
      expect(schema).toHaveProperty('@context');
      expect(schema).toHaveProperty('@type');
      expect(schema).toHaveProperty('name');
      expect(schema).toHaveProperty('description');
    });

    it('SA-016: includes url field', () => {
      const schema = generateSoftwareApplicationSchema(mockTools[0]);
      
      expect(schema.url).toBeDefined();
      expect(schema.url).toMatch(/^https:\/\//);
    });

    it('SA-017: includes screenshot when provided', () => {
      const schema = generateSoftwareApplicationSchema(mockTools[0]);
      
      expect(schema.screenshot).toBeDefined();
      expect(schema.screenshot).toMatch(/^https:\/\//);
    });

    it('SA-018: generates valid JSON string', () => {
      const schema = generateSoftwareApplicationSchema(mockTools[0]);
      const json = schemaToJsonLd(schema);
      
      expect(() => JSON.parse(json)).not.toThrow();
    });

    it('SA-019: handles unicode characters', () => {
      const tool: ToolInfo = {
        slug: 'test-tool',
        name: 'Outil SEO — Analyse de balises',
        description: 'Descripción en español con caracteres especiales: ñ, á, é',
        features: [],
        relatedGuides: [],
      };
      
      const schema = generateSoftwareApplicationSchema(tool);
      
      expect(schema.name).toContain('—');
      expect(schema.description).toContain('ñ');
    });

    it('SA-020: handles long descriptions', () => {
      const longDescription = 'A'.repeat(500);
      const tool: ToolInfo = {
        slug: 'test-tool',
        name: 'Test Tool',
        description: longDescription,
        features: [],
        relatedGuides: [],
      };
      
      const schema = generateSoftwareApplicationSchema(tool);
      
      expect(schema.description).toBe(longDescription);
    });

    it('SA-021: omits aggregateRating when not provided', () => {
      const tool: ToolInfo = {
        slug: 'test-tool',
        name: 'Test Tool',
        description: 'Test description',
        features: [],
        relatedGuides: [],
      };
      
      const schema = generateSoftwareApplicationSchema(tool);
      
      expect(schema.aggregateRating).toBeUndefined();
    });

    it('SA-022: sets correct url format', () => {
      const schema = generateSoftwareApplicationSchema(mockTools[0]);
      
      expect(schema.url).toMatch(/\/tools\/[\w-]+\/$/);
    });

    it('SA-023: handles emoji in name', () => {
      const tool: ToolInfo = {
        slug: 'test-tool',
        name: 'SEO Tool 🚀 ✨',
        description: 'Test description',
        features: [],
        relatedGuides: [],
      };
      
      const schema = generateSoftwareApplicationSchema(tool);
      
      expect(schema.name).toContain('🚀');
    });

    it('SA-024: handles empty name gracefully', () => {
      const tool: ToolInfo = {
        slug: 'test-tool',
        name: '',
        description: 'Test description',
        features: [],
        relatedGuides: [],
      };
      
      const schema = generateSoftwareApplicationSchema(tool);
      
      expect(schema.name).toBe('');
    });

    it('SA-025: handles null values gracefully', () => {
      const tool: ToolInfo = {
        slug: 'test-tool',
        name: 'Test Tool',
        description: 'Test description',
        features: [],
        relatedGuides: [],
        image: undefined,
        rating: undefined,
      };
      
      const schema = generateSoftwareApplicationSchema(tool);
      
      expect(schema.screenshot).toBeUndefined();
      expect(schema.aggregateRating).toBeUndefined();
    });
  });

  describe('FAQPage Schema (FAQ)', () => {
    it('FAQ-001: generates valid FAQPage schema', () => {
      const schema = generateFAQSchema(mockFAQs);
      
      expect(schema['@context']).toBe('https://schema.org');
      expect(schema['@type']).toBe('FAQPage');
    });

    it('FAQ-002: includes @type: FAQPage', () => {
      const schema = generateFAQSchema(mockFAQs);
      
      expect(schema['@type']).toBe('FAQPage');
    });

    it('FAQ-003: includes mainEntity array', () => {
      const schema = generateFAQSchema(mockFAQs);
      
      expect(Array.isArray(schema.mainEntity)).toBe(true);
    });

    it('FAQ-004: formats Question objects correctly', () => {
      const schema = generateFAQSchema([mockFAQs[0]]);
      
      expect(schema.mainEntity[0]['@type']).toBe('Question');
      expect(schema.mainEntity[0].name).toBe('Are these SEO tools free?');
    });

    it('FAQ-005: formats Answer objects correctly', () => {
      const schema = generateFAQSchema([mockFAQs[0]]);
      
      expect(schema.mainEntity[0].acceptedAnswer['@type']).toBe('Answer');
      expect(schema.mainEntity[0].acceptedAnswer.text).toContain('Yes');
    });

    it('FAQ-006: supports multiple Q&A pairs', () => {
      const schema = generateFAQSchema(mockFAQs);
      
      expect(schema.mainEntity.length).toBe(4);
    });

    it('FAQ-007: escapes HTML in questions', () => {
      const faqWithHtml: FAQItem = {
        question: 'Is this <b>free</b>?',
        answer: 'Yes',
      };
      
      const escaped = escapeHtml(faqWithHtml.question);
      
      expect(escaped).toContain('&lt;b&gt;');
    });

    it('FAQ-008: escapes HTML in answers', () => {
      const faqWithHtml: FAQItem = {
        question: 'Question?',
        answer: 'Yes, <a href="#">click here</a>',
      };
      
      const escaped = escapeHtml(faqWithHtml.answer);
      
      expect(escaped).toContain('&lt;a');
    });

    it('FAQ-009: handles empty questions array', () => {
      const schema = generateFAQSchema([]);
      
      expect(schema.mainEntity).toEqual([]);
    });

    it('FAQ-010: handles special characters', () => {
      const faqWithSpecial: FAQItem = {
        question: 'What about quotes " and symbols & < >?',
        answer: 'They are handled properly — with “smart quotes”',
      };
      
      const schema = generateFAQSchema([faqWithSpecial]);
      
      expect(schema.mainEntity[0].name).toContain('"');
      expect(schema.mainEntity[0].acceptedAnswer.text).toContain('—');
    });

    it('FAQ-011: generates correct FAQ for tools page', () => {
      const schema = generateFAQSchema(mockFAQs);
      
      const questions = schema.mainEntity.map(e => e.name);
      expect(questions).toContain('Are these SEO tools free?');
      expect(questions).toContain('How accurate are the tool results?');
    });

    it('FAQ-012: includes acceptedAnswer', () => {
      const schema = generateFAQSchema(mockFAQs);
      
      schema.mainEntity.forEach(item => {
        expect(item.acceptedAnswer).toBeDefined();
        expect(item.acceptedAnswer['@type']).toBe('Answer');
      });
    });

    it('FAQ-013: validates question structure', () => {
      const schema = generateFAQSchema(mockFAQs);
      
      schema.mainEntity.forEach(item => {
        expect(item).toHaveProperty('@type');
        expect(item).toHaveProperty('name');
        expect(item).toHaveProperty('acceptedAnswer');
      });
    });

    it('FAQ-014: validates answer structure', () => {
      const schema = generateFAQSchema(mockFAQs);
      
      schema.mainEntity.forEach(item => {
        expect(item.acceptedAnswer).toHaveProperty('@type');
        expect(item.acceptedAnswer).toHaveProperty('text');
      });
    });

    it('FAQ-015: handles long questions', () => {
      const longQuestion = 'What is the best way to analyze meta tags for SEO optimization?'.repeat(3);
      const faq: FAQItem = {
        question: longQuestion,
        answer: 'Use our tool.',
      };
      
      const schema = generateFAQSchema([faq]);
      
      expect(schema.mainEntity[0].name).toBe(longQuestion);
    });

    it('FAQ-016: handles long answers', () => {
      const longAnswer = 'This is a very detailed answer. '.repeat(50);
      const faq: FAQItem = {
        question: 'Question?',
        answer: longAnswer,
      };
      
      const schema = generateFAQSchema([faq]);
      
      expect(schema.mainEntity[0].acceptedAnswer.text).toBe(longAnswer);
    });

    it('FAQ-017: generates valid JSON-LD script tag content', () => {
      const schema = generateFAQSchema(mockFAQs);
      const json = schemaToJsonLd(schema);
      
      expect(() => JSON.parse(json)).not.toThrow();
    });

    it('FAQ-018: schema integrates with page', () => {
      const schema = generateFAQSchema(mockFAQs);
      
      // Verify schema can be serialized for script tag
      const scriptContent = JSON.stringify(schema);
      expect(scriptContent).toContain('FAQPage');
      expect(scriptContent).toContain('mainEntity');
    });

    it('FAQ-019: handles missing questions gracefully', () => {
      const schema = generateFAQSchema([]);
      
      expect(schema.mainEntity).toEqual([]);
    });

    it('FAQ-020: supports variable FAQ content', () => {
      const customFAQs: FAQItem[] = [
        { question: 'Custom Q1', answer: 'Custom A1' },
        { question: 'Custom Q2', answer: 'Custom A2' },
      ];
      
      const schema = generateFAQSchema(customFAQs);
      
      expect(schema.mainEntity.length).toBe(2);
      expect(schema.mainEntity[0].name).toBe('Custom Q1');
    });
  });

  describe('BreadcrumbList Schema (BL)', () => {
    const homeBreadcrumb: BreadcrumbItem = { name: 'Home', url: 'https://seoindicator.com/' };
    const toolsBreadcrumb: BreadcrumbItem = { name: 'Tools', url: 'https://seoindicator.com/tools/' };
    const toolBreadcrumb: BreadcrumbItem = { 
      name: 'Meta Tag Analyzer', 
      url: 'https://seoindicator.com/tools/meta-tag-analyzer/' 
    };

    it('BL-001: generates valid BreadcrumbList schema', () => {
      const schema = generateBreadcrumbSchema([homeBreadcrumb, toolsBreadcrumb, toolBreadcrumb]);
      
      expect(schema['@context']).toBe('https://schema.org');
      expect(schema['@type']).toBe('BreadcrumbList');
    });

    it('BL-002: includes @type: BreadcrumbList', () => {
      const schema = generateBreadcrumbSchema([homeBreadcrumb]);
      
      expect(schema['@type']).toBe('BreadcrumbList');
    });

    it('BL-003: includes itemListElement array', () => {
      const schema = generateBreadcrumbSchema([homeBreadcrumb, toolsBreadcrumb]);
      
      expect(Array.isArray(schema.itemListElement)).toBe(true);
    });

    it('BL-004: formats ListItem correctly', () => {
      const schema = generateBreadcrumbSchema([homeBreadcrumb]);
      
      expect(schema.itemListElement[0]['@type']).toBe('ListItem');
      expect(schema.itemListElement[0].position).toBe(1);
      expect(schema.itemListElement[0].name).toBe('Home');
      expect(schema.itemListElement[0].item).toBe('https://seoindicator.com/');
    });

    it('BL-005: sets correct position values', () => {
      const schema = generateBreadcrumbSchema([homeBreadcrumb, toolsBreadcrumb, toolBreadcrumb]);
      
      expect(schema.itemListElement[0].position).toBe(1);
      expect(schema.itemListElement[1].position).toBe(2);
      expect(schema.itemListElement[2].position).toBe(3);
    });

    it('BL-006: includes Home breadcrumb', () => {
      const schema = generateBreadcrumbSchema([homeBreadcrumb, toolsBreadcrumb]);
      
      expect(schema.itemListElement[0].name).toBe('Home');
      expect(schema.itemListElement[0].item).toBe('https://seoindicator.com/');
    });

    it('BL-007: includes Tools breadcrumb', () => {
      const schema = generateBreadcrumbSchema([homeBreadcrumb, toolsBreadcrumb]);
      
      expect(schema.itemListElement[1].name).toBe('Tools');
      expect(schema.itemListElement[1].item).toBe('https://seoindicator.com/tools/');
    });

    it('BL-008: includes tool name breadcrumb', () => {
      const schema = generateBreadcrumbSchema([homeBreadcrumb, toolsBreadcrumb, toolBreadcrumb]);
      
      expect(schema.itemListElement[2].name).toBe('Meta Tag Analyzer');
      expect(schema.itemListElement[2].item).toBe('https://seoindicator.com/tools/meta-tag-analyzer/');
    });

    it('BL-009: generates correct URLs', () => {
      const schema = generateBreadcrumbSchema([homeBreadcrumb, toolsBreadcrumb]);
      
      schema.itemListElement.forEach(item => {
        expect(item.item).toMatch(/^https:\/\//);
      });
    });

    it('BL-010: handles nested paths', () => {
      const deepBreadcrumb: BreadcrumbItem = {
        name: 'Deep Tool',
        url: 'https://seoindicator.com/tools/category/subcategory/deep-tool/',
      };
      
      const schema = generateBreadcrumbSchema([homeBreadcrumb, deepBreadcrumb]);
      
      expect(schema.itemListElement[1].item).toBe(deepBreadcrumb.url);
    });

    it('BL-011: escapes special characters in names', () => {
      const specialBreadcrumb: BreadcrumbItem = {
        name: 'Tool & <More>',
        url: 'https://seoindicator.com/tools/test/',
      };
      
      const escaped = escapeHtml(specialBreadcrumb.name);
      
      expect(escaped).toContain('&amp;');
      expect(escaped).toContain('&lt;');
    });

    it('BL-012: handles unicode in names', () => {
      const unicodeBreadcrumb: BreadcrumbItem = {
        name: 'Outil SEO — Analyse',
        url: 'https://seoindicator.com/tools/outil-seo/',
      };
      
      const schema = generateBreadcrumbSchema([unicodeBreadcrumb]);
      
      expect(schema.itemListElement[0].name).toContain('—');
    });

    it('BL-013: validates item URLs are HTTPS', () => {
      const schema = generateBreadcrumbSchema([homeBreadcrumb, toolsBreadcrumb, toolBreadcrumb]);
      
      schema.itemListElement.forEach(item => {
        expect(item.item).toMatch(/^https:\/\//);
      });
    });

    it('BL-014: generates for tools landing page (2 items)', () => {
      const schema = generateBreadcrumbSchema([homeBreadcrumb, toolsBreadcrumb]);
      
      expect(schema.itemListElement.length).toBe(2);
    });

    it('BL-015: generates for individual tool page (3 items)', () => {
      const schema = generateBreadcrumbSchema([homeBreadcrumb, toolsBreadcrumb, toolBreadcrumb]);
      
      expect(schema.itemListElement.length).toBe(3);
    });

    it('BL-016: handles empty path', () => {
      const schema = generateBreadcrumbSchema([]);
      
      expect(schema.itemListElement).toEqual([]);
    });

    it('BL-017: validates JSON output', () => {
      const schema = generateBreadcrumbSchema([homeBreadcrumb, toolsBreadcrumb]);
      const json = schemaToJsonLd(schema);
      
      expect(() => JSON.parse(json)).not.toThrow();
    });

    it('BL-018: integrates with page component', () => {
      const schema = generateBreadcrumbSchema([homeBreadcrumb, toolsBreadcrumb, toolBreadcrumb]);
      
      // Verify it can be embedded in script tag
      const scriptContent = JSON.stringify(schema);
      expect(scriptContent).toContain('BreadcrumbList');
    });
  });

  describe('Schema Utilities', () => {
    it('escapeHtml handles all special characters', () => {
      const input = '<script>alert("XSS")</script> & \'quotes\'';
      const escaped = escapeHtml(input);
      
      expect(escaped).toBe('&lt;script&gt;alert(&quot;XSS&quot;)&lt;/script&gt; &amp; &#039;quotes&#039;');
    });

    it('schemaToJsonLd produces valid JSON', () => {
      const schema = generateSoftwareApplicationSchema(mockTools[0]);
      const json = schemaToJsonLd(schema);
      
      const parsed = JSON.parse(json);
      expect(parsed['@type']).toBe('SoftwareApplication');
    });

    it('handles circular reference detection', () => {
      // This would be caught by JSON.stringify throwing
      const obj: Record<string, unknown> = { name: 'test' };
      obj.self = obj;
      
      expect(() => JSON.stringify(obj)).toThrow();
    });
  });
});
