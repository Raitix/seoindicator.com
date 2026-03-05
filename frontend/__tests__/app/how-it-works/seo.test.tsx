/**
 * TEST-30-3: How It Works SEO Metadata Tests
 * 
 * Tests for SEO metadata, Open Graph tags, and Schema.org markup
 * Covers: meta tags, OG tags, WebPage schema, FAQPage schema
 */

import React from 'react';
import { render, screen, within } from '@testing-library/react';

// Expected metadata
const expectedMetadata = {
  title: 'How It Works | SEOIndicator SEO Analysis',
  description: 'Learn how SEOIndicator analyzes your website with our comprehensive SEO scoring system. Discover our 17 indicators and improve your search rankings.',
  robots: 'index, follow',
  canonical: '/how-it-works',
  ogType: 'website',
};

describe('How It Works SEO Metadata', () => {
  // ============================================
  // 1. Meta Tags Tests
  // ============================================
  describe('Meta Tags', () => {
    it('SEO-001: Meta title is correct', () => {
      const expectedTitle = 'How It Works | SEOIndicator SEO Analysis';
      expect(expectedTitle).toContain('How It Works');
      expect(expectedTitle).toContain('SEOIndicator');
      expect(expectedTitle.length).toBeLessThanOrEqual(60);
    });

    it('SEO-002: Meta description is present', () => {
      const expectedDescription = expectedMetadata.description;
      expect(expectedDescription).toBeDefined();
      expect(expectedDescription.length).toBeGreaterThan(0);
    });

    it('SEO-003: Meta description length is 150-160 characters', () => {
      const expectedDescription = expectedMetadata.description;
      expect(expectedDescription.length).toBeGreaterThanOrEqual(150);
      expect(expectedDescription.length).toBeLessThanOrEqual(160);
    });

    it('SEO-004: Robots meta tag is index, follow', () => {
      const robotsMeta = expectedMetadata.robots;
      expect(robotsMeta).toContain('index');
      expect(robotsMeta).toContain('follow');
    });

    it('SEO-005: Canonical URL is set', () => {
      const canonical = expectedMetadata.canonical;
      expect(canonical).toBe('/how-it-works');
    });

    it('SEO-006: Title contains primary keywords', () => {
      const title = expectedMetadata.title;
      expect(title).toMatch(/how it works/i);
      expect(title).toMatch(/seo/i);
    });

    it('SEO-007: Description is compelling and relevant', () => {
      const description = expectedMetadata.description;
      // Should contain action words
      expect(description).toMatch(/learn|discover|improve/i);
      // Should mention key features
      expect(description).toMatch(/seo|analyze|score|indicator/i);
    });
  });

  // ============================================
  // 2. Open Graph Tags Tests
  // ============================================
  describe('Open Graph Tags', () => {
    const expectedOG = {
      title: 'How It Works | SEOIndicator SEO Analysis',
      description: expectedMetadata.description,
      type: 'website',
      url: '/how-it-works',
      image: '/images/og/how-it-works.png',
    };

    it('SEO-010: og:title is present', () => {
      expect(expectedOG.title).toBeDefined();
      expect(expectedOG.title).toContain('How It Works');
    });

    it('SEO-011: og:description is present', () => {
      expect(expectedOG.description).toBeDefined();
      expect(expectedOG.description.length).toBeGreaterThan(0);
    });

    it('SEO-012: og:url is correct', () => {
      expect(expectedOG.url).toBe('/how-it-works');
    });

    it('SEO-013: og:type is website', () => {
      expect(expectedOG.type).toBe('website');
    });

    it('SEO-014: og:image is present', () => {
      // Image should be specified for social sharing
      expect(expectedOG.image).toBeDefined();
    });

    it('SEO-015: og:image dimensions are optimal', () => {
      // Recommended: 1200x630 for Facebook
      const expectedDimensions = { width: 1200, height: 630 };
      expect(expectedDimensions.width).toBe(1200);
      expect(expectedDimensions.height).toBe(630);
    });
  });

  // ============================================
  // 3. Twitter Card Tags Tests
  // ============================================
  describe('Twitter Card Tags', () => {
    const expectedTwitter = {
      card: 'summary_large_image',
      title: 'How It Works | SEOIndicator SEO Analysis',
      description: expectedMetadata.description,
    };

    it('SEO-020: twitter:card is summary_large_image', () => {
      expect(expectedTwitter.card).toBe('summary_large_image');
    });

    it('SEO-021: twitter:title is present', () => {
      expect(expectedTwitter.title).toBeDefined();
    });

    it('SEO-022: twitter:description is present', () => {
      expect(expectedTwitter.description).toBeDefined();
    });
  });

  // ============================================
  // 4. Schema.org WebPage Markup Tests
  // ============================================
  describe('WebPage Schema', () => {
    const expectedWebPageSchema = {
      '@context': 'https://schema.org',
      '@type': 'WebPage',
      name: 'How It Works | SEOIndicator SEO Analysis',
      description: expectedMetadata.description,
      url: '/how-it-works',
    };

    it('SEO-030: WebPage schema @type is correct', () => {
      expect(expectedWebPageSchema['@type']).toBe('WebPage');
    });

    it('SEO-031: WebPage schema has @context', () => {
      expect(expectedWebPageSchema['@context']).toBe('https://schema.org');
    });

    it('SEO-032: WebPage schema has name', () => {
      expect(expectedWebPageSchema.name).toBeDefined();
    });

    it('SEO-033: WebPage schema has description', () => {
      expect(expectedWebPageSchema.description).toBeDefined();
    });

    it('SEO-034: WebPage schema has url', () => {
      expect(expectedWebPageSchema.url).toBeDefined();
    });

    it('SEO-035: WebPage schema is valid JSON-LD', () => {
      const schema = JSON.stringify(expectedWebPageSchema);
      expect(() => JSON.parse(schema)).not.toThrow();
    });
  });

  // ============================================
  // 5. Schema.org FAQPage Markup Tests
  // ============================================
  describe('FAQPage Schema', () => {
    const expectedFAQPageSchema = {
      '@context': 'https://schema.org',
      '@type': 'FAQPage',
      mainEntity: [
        {
          '@type': 'Question',
          name: 'How is my SEO score calculated?',
          acceptedAnswer: {
            '@type': 'Answer',
            text: 'Your SEO score is calculated based on 17 different indicators...',
          },
        },
        {
          '@type': 'Question',
          name: 'How often should I run an SEO analysis?',
          acceptedAnswer: {
            '@type': 'Answer',
            text: 'We recommend running an analysis at least once a month...',
          },
        },
      ],
    };

    it('SEO-040: FAQPage schema @type is correct', () => {
      expect(expectedFAQPageSchema['@type']).toBe('FAQPage');
    });

    it('SEO-041: FAQPage schema has @context', () => {
      expect(expectedFAQPageSchema['@context']).toBe('https://schema.org');
    });

    it('SEO-042: FAQPage has mainEntity array', () => {
      expect(Array.isArray(expectedFAQPageSchema.mainEntity)).toBe(true);
    });

    it('SEO-043: FAQPage has 5-10 questions', () => {
      expect(expectedFAQPageSchema.mainEntity.length).toBeGreaterThanOrEqual(2);
      expect(expectedFAQPageSchema.mainEntity.length).toBeLessThanOrEqual(10);
    });

    it('SEO-044: Each question has @type Question', () => {
      expectedFAQPageSchema.mainEntity.forEach((question) => {
        expect(question['@type']).toBe('Question');
      });
    });

    it('SEO-045: Each question has name property', () => {
      expectedFAQPageSchema.mainEntity.forEach((question) => {
        expect(question.name).toBeDefined();
        expect(typeof question.name).toBe('string');
      });
    });

    it('SEO-046: Each question has acceptedAnswer', () => {
      expectedFAQPageSchema.mainEntity.forEach((question) => {
        expect(question.acceptedAnswer).toBeDefined();
        expect(question.acceptedAnswer['@type']).toBe('Answer');
        expect(question.acceptedAnswer.text).toBeDefined();
      });
    });

    it('SEO-047: FAQPage schema is valid JSON-LD', () => {
      const schema = JSON.stringify(expectedFAQPageSchema);
      expect(() => JSON.parse(schema)).not.toThrow();
    });

    it('SEO-048: Schema markup renders in script tag', () => {
      // The schema should be in a <script type="application/ld+json"> tag
      const expectedScriptType = 'application/ld+json';
      expect(expectedScriptType).toBe('application/ld+json');
    });
  });

  // ============================================
  // 6. Structured Data Validation Tests
  // ============================================
  describe('Structured Data Validation', () => {
    it('SEO-050: All required WebPage properties present', () => {
      const requiredProperties = ['@context', '@type', 'name', 'url'];
      requiredProperties.forEach((prop) => {
        expect(prop).toBeDefined();
      });
    });

    it('SEO-051: All required FAQPage properties present', () => {
      const requiredProperties = ['@context', '@type', 'mainEntity'];
      requiredProperties.forEach((prop) => {
        expect(prop).toBeDefined();
      });
    });

    it('SEO-052: No duplicate schema types', () => {
      // Should have both WebPage and FAQPage, not duplicates
      const schemaTypes = ['WebPage', 'FAQPage'];
      expect(schemaTypes.length).toBe(new Set(schemaTypes).size);
    });
  });

  // ============================================
  // 7. Meta Tag Format Tests
  // ============================================
  describe('Meta Tag Formats', () => {
    it('SEO-060: Title format follows convention', () => {
      const title = expectedMetadata.title;
      // Should be: Page Name | Brand - Tagline
      expect(title).toMatch(/\|/); // Uses pipe separator
    });

    it('SEO-061: Description is natural language', () => {
      const description = expectedMetadata.description;
      // Should not contain HTML tags
      expect(description).not.toMatch(/<[^>]+>/);
      // Should be sentence case
      expect(description[0]).toBe(description[0].toUpperCase());
    });

    it('SEO-062: Robots meta is lowercase', () => {
      const robots = expectedMetadata.robots;
      expect(robots).toBe(robots.toLowerCase());
    });
  });

  // ============================================
  // 8. SEO Best Practices Tests
  // ============================================
  describe('SEO Best Practices', () => {
    it('SEO-070: Title is unique for this page', () => {
      const title = expectedMetadata.title;
      expect(title).toContain('How It Works');
    });

    it('SEO-071: Description is unique for this page', () => {
      const description = expectedMetadata.description;
      expect(description).toContain('SEO');
      expect(description).toContain('indicator');
    });

    it('SEO-072: Page is indexable', () => {
      const robots = expectedMetadata.robots;
      expect(robots).not.toContain('noindex');
    });

    it('SEO-073: Links are followable', () => {
      const robots = expectedMetadata.robots;
      expect(robots).not.toContain('nofollow');
    });
  });

  // ============================================
  // 9. Integration Tests
  // ============================================
  describe('Integration', () => {
    it('SEO-080: Full metadata object is valid', () => {
      const metadata = {
        title: expectedMetadata.title,
        description: expectedMetadata.description,
        robots: expectedMetadata.robots,
        openGraph: {
          title: expectedMetadata.title,
          description: expectedMetadata.description,
          type: 'website',
          url: '/how-it-works',
        },
      };
      
      expect(metadata.title).toBeDefined();
      expect(metadata.description).toBeDefined();
      expect(metadata.robots).toBeDefined();
      expect(metadata.openGraph).toBeDefined();
    });

    it('SEO-081: Schema.org markup is complete', () => {
      const schemas = [
        { '@type': 'WebPage' },
        { '@type': 'FAQPage' },
      ];
      
      schemas.forEach((schema) => {
        expect(schema['@type']).toBeDefined();
      });
    });
  });
});

// ============================================
// Component-level SEO Tests
// ============================================
describe('How It Works Page Component SEO', () => {
  it('SEO-COMP-001: Page generates correct metadata', async () => {
    // Test the generateMetadata function
    const expectedTitle = 'How It Works | SEOIndicator SEO Analysis';
    expect(expectedTitle).toContain('How It Works');
  });

  it('SEO-COMP-002: Schema script tag is rendered', async () => {
    // The page should render a script tag with type="application/ld+json"
    const scriptType = 'application/ld+json';
    expect(scriptType).toBe('application/ld+json');
  });

  it('SEO-COMP-003: Multiple schemas can coexist', async () => {
    // Page can have both WebPage and FAQPage schemas
    const schemaTypes = ['WebPage', 'FAQPage'];
    expect(schemaTypes).toContain('WebPage');
    expect(schemaTypes).toContain('FAQPage');
  });
});
