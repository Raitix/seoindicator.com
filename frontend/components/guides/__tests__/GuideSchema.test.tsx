/**
 * Tests for GuideSchema Component
 * Task: TASK-20-27 - Add Schema Markup to All Guides
 *
 * These tests verify the GuideSchema component generates valid HowTo schema.org
 * structured data for SEO guide pages.
 */

import React from 'react';
import { render, screen } from '@testing-library/react';
import '@testing-library/jest-dom';
import { GuideSchema, generateHowToSchema } from '../GuideSchema';
import { SEOGuide, GuideSection, ChecklistItem } from '../../../lib/guides/types';
import { getAllGuides, getGuideBySlug } from '../../../lib/guides/registry';

// ============================================================================
// Mock Data
// ============================================================================

const mockGuide: SEOGuide = {
  slug: 'test-guide',
  title: 'Test Guide',
  summary: 'A test guide for schema validation.',
  category: 'technical',
  icon: '📝',
  readTime: 5,
  sections: [
    {
      title: 'Introduction',
      content: 'This is the introduction section with enough content for testing the schema generation.',
    },
    {
      title: 'Step 1: Setup',
      content: 'This is the first step with enough content for testing purposes.',
    },
    {
      title: 'Step 2: Implementation',
      content: 'This is the second step with detailed implementation instructions.',
    },
  ],
  checklist: [
    { text: 'First checklist item', priority: 'critical' },
    { text: 'Second checklist item', priority: 'important' },
  ],
  relatedGuides: ['meta-title', 'page-speed'],
};

const mockGuideEmptySections: SEOGuide = {
  slug: 'empty-guide',
  title: 'Empty Guide',
  summary: 'A guide with no sections.',
  category: 'technical',
  icon: '📄',
  readTime: 1,
  sections: [],
  checklist: [],
  relatedGuides: [],
};

const mockGuideLongContent: SEOGuide = {
  slug: 'long-content-guide',
  title: 'Long Content Guide',
  summary: 'A guide with very long content.',
  category: 'on-page',
  icon: '📝',
  readTime: 10,
  sections: [
    {
      title: 'Very Long Section Title That Might Need Truncation In Some Contexts',
      content: 'A'.repeat(500), // 500 characters
    },
  ],
  checklist: [],
  relatedGuides: [],
};

// ============================================================================
// generateHowToSchema Function Tests
// ============================================================================

describe('generateHowToSchema', () => {
  describe('GS-002: Schema Structure', () => {
    it('GS-002a: should contain @context with schema.org', () => {
      const schema = generateHowToSchema(mockGuide);
      expect(schema['@context']).toBe('https://schema.org');
    });

    it('GS-002b: should contain @type as HowTo', () => {
      const schema = generateHowToSchema(mockGuide);
      expect(schema['@type']).toBe('HowTo');
    });

    it('GS-002c: should contain name from guide title', () => {
      const schema = generateHowToSchema(mockGuide);
      expect(schema.name).toBe('Test Guide');
    });

    it('GS-002d: should contain description from guide summary', () => {
      const schema = generateHowToSchema(mockGuide);
      expect(schema.description).toBe('A test guide for schema validation.');
    });

    it('GS-002e: should contain step array', () => {
      const schema = generateHowToSchema(mockGuide);
      expect(Array.isArray(schema.step)).toBe(true);
    });

    it('GS-002f: should contain estimatedCost with value 0', () => {
      const schema = generateHowToSchema(mockGuide);
      expect(schema.estimatedCost).toEqual({
        '@type': 'MonetaryAmount',
        currency: 'USD',
        value: '0',
      });
    });

    it('GS-002g: should contain totalTime in ISO 8601 format', () => {
      const schema = generateHowToSchema(mockGuide);
      expect(schema.totalTime).toBe('PT5M');
    });
  });

  describe('GS-003: HowToStep Generation', () => {
    it('GS-003a: should create step for each section', () => {
      const schema = generateHowToSchema(mockGuide);
      expect(schema.step).toHaveLength(3);
    });

    it('GS-003b: each step should have @type HowToStep', () => {
      const schema = generateHowToSchema(mockGuide);
      schema.step.forEach((step: any) => {
        expect(step['@type']).toBe('HowToStep');
      });
    });

    it('GS-003c: step should have name from section title', () => {
      const schema = generateHowToSchema(mockGuide);
      expect(schema.step[0].name).toBe('Introduction');
      expect(schema.step[1].name).toBe('Step 1: Setup');
    });

    it('GS-003d: step should have text from section content', () => {
      const schema = generateHowToSchema(mockGuide);
      expect(schema.step[0].text).toContain('introduction section');
    });

    it('GS-003e: should handle empty sections array', () => {
      const schema = generateHowToSchema(mockGuideEmptySections);
      expect(schema.step).toEqual([]);
    });

    it('GS-003f: should truncate long content to 200 characters', () => {
      const schema = generateHowToSchema(mockGuideLongContent);
      expect(schema.step[0].text.length).toBeLessThanOrEqual(200);
    });
  });

  describe('GS-004: totalTime Calculation', () => {
    it('GS-004a: should format readTime 5 as PT5M', () => {
      const schema = generateHowToSchema(mockGuide);
      expect(schema.totalTime).toBe('PT5M');
    });

    it('GS-004b: should handle readTime 0', () => {
      const guide = { ...mockGuide, readTime: 0 };
      const schema = generateHowToSchema(guide);
      expect(schema.totalTime).toBe('PT0M');
    });

    it('GS-004c: should handle readTime 1', () => {
      const guide = { ...mockGuide, readTime: 1 };
      const schema = generateHowToSchema(guide);
      expect(schema.totalTime).toBe('PT1M');
    });

    it('GS-004d: should handle large readTime', () => {
      const guide = { ...mockGuide, readTime: 60 };
      const schema = generateHowToSchema(guide);
      expect(schema.totalTime).toBe('PT60M');
    });
  });

  describe('GS-005: estimatedCost Structure', () => {
    it('GS-005a: should have @type MonetaryAmount', () => {
      const schema = generateHowToSchema(mockGuide);
      expect(schema.estimatedCost['@type']).toBe('MonetaryAmount');
    });

    it('GS-005b: should have currency USD', () => {
      const schema = generateHowToSchema(mockGuide);
      expect(schema.estimatedCost.currency).toBe('USD');
    });

    it('GS-005c: should have value 0 (free)', () => {
      const schema = generateHowToSchema(mockGuide);
      expect(schema.estimatedCost.value).toBe('0');
    });
  });

  describe('GS-008: Edge Cases', () => {
    it('GS-008a: should handle guide with no sections', () => {
      const schema = generateHowToSchema(mockGuideEmptySections);
      expect(schema.step).toEqual([]);
      expect(schema.name).toBe('Empty Guide');
    });

    it('GS-008b: should handle section with no code examples', () => {
      const guideWithNoCodeExamples: SEOGuide = {
        ...mockGuide,
        sections: [
          {
            title: 'No Code Examples',
            content: 'Content without code examples.',
            codeExamples: [],
          },
        ],
      };
      const schema = generateHowToSchema(guideWithNoCodeExamples);
      expect(schema.step).toHaveLength(1);
      expect(schema.step[0].name).toBe('No Code Examples');
    });

    it('GS-008c: should handle guide with empty checklist', () => {
      const schema = generateHowToSchema(mockGuideEmptySections);
      expect(schema.name).toBe('Empty Guide');
    });

    it('GS-008d: should handle guide with no related guides', () => {
      const schema = generateHowToSchema(mockGuideEmptySections);
      expect(schema.name).toBe('Empty Guide');
    });

    it('GS-008e: should handle section with very long title', () => {
      const guide = {
        ...mockGuide,
        sections: [
          {
            title: 'A'.repeat(500),
            content: 'Content',
          },
        ],
      };
      const schema = generateHowToSchema(guide);
      expect(schema.step[0].name).toHaveLength(500);
    });
  });

  describe('GS-009: Security', () => {
    it('GS-009a: should handle XSS in title safely', () => {
      const xssGuide = {
        ...mockGuide,
        title: '<script>alert("XSS")</script>Guide',
      };
      const schema = generateHowToSchema(xssGuide);
      // JSON.stringify escapes HTML entities
      const json = JSON.stringify(schema);
      expect(json).toContain('\\u003Cscript\\u003E');
    });

    it('GS-009b: should handle XSS in content safely', () => {
      const xssGuide = {
        ...mockGuide,
        sections: [
          {
            title: 'Section',
            content: '<script>alert("XSS")</script>Content',
          },
        ],
      };
      const schema = generateHowToSchema(xssGuide);
      const json = JSON.stringify(schema);
      expect(json).toContain('\\u003Cscript\\u003E');
    });

    it('GS-009c: should preserve HTML entities', () => {
      const entityGuide = {
        ...mockGuide,
        sections: [
          {
            title: 'Section',
            content: 'Use &amp; for ampersand, &lt; for less than',
          },
        ],
      };
      const schema = generateHowToSchema(entityGuide);
      expect(schema.step[0].text).toContain('&amp;');
      expect(schema.step[0].text).toContain('&lt;');
    });
  });
});

// ============================================================================
// GuideSchema Component Tests
// ============================================================================

describe('GuideSchema Component', () => {
  describe('GS-001: Component Rendering', () => {
    it('GS-001a: should render without crashing', () => {
      const { container } = render(<GuideSchema guide={mockGuide} />);
      expect(container).toBeInTheDocument();
    });

    it('GS-001b: should return null for null guide', () => {
      const { container } = render(<GuideSchema guide={null} />);
      expect(container.firstChild).toBeNull();
    });

    it('GS-001c: should return null for undefined guide', () => {
      const { container } = render(<GuideSchema guide={undefined as any} />);
      expect(container.firstChild).toBeNull();
    });

    it('GS-001d: should return null for empty object', () => {
      const { container } = render(<GuideSchema guide={{} as any} />);
      expect(container.firstChild).toBeNull();
    });
  });

  describe('GS-006: JSON-LD Script Tag', () => {
    it('GS-006a: should render script tag with type application/ld+json', () => {
      const { container } = render(<GuideSchema guide={mockGuide} />);
      const script = container.querySelector('script[type="application/ld+json"]');
      expect(script).toBeInTheDocument();
    });

    it('GS-006b: should contain valid JSON', () => {
      const { container } = render(<GuideSchema guide={mockGuide} />);
      const script = container.querySelector('script[type="application/ld+json"]');
      expect(() => JSON.parse(script?.textContent || '')).not.toThrow();
    });

    it('GS-006c: JSON should match schema object', () => {
      const { container } = render(<GuideSchema guide={mockGuide} />);
      const script = container.querySelector('script[type="application/ld+json"]');
      const parsed = JSON.parse(script?.textContent || '');
      
      expect(parsed['@context']).toBe('https://schema.org');
      expect(parsed['@type']).toBe('HowTo');
      expect(parsed.name).toBe('Test Guide');
    });
  });
});

// ============================================================================
// All 17 Guides Validation
// ============================================================================

describe('GS-007: All 17 Guides Validation', () => {
  const allGuides = getAllGuides();
  const expectedSlugs = [
    'meta-title',
    'meta-description',
    'h1-headings',
    'heading-hierarchy',
    'image-alt-text',
    'image-optimization',
    'internal-links',
    'external-links',
    'page-speed',
    'mobile-friendly',
    'https-security',
    'structured-data',
    'canonical-urls',
    'robots-txt',
    'xml-sitemap',
    'open-graph',
    'twitter-cards',
  ];

  it('should have exactly 17 guides in registry', () => {
    expect(allGuides.length).toBe(17);
  });

  expectedSlugs.forEach((slug, index) => {
    const testId = String.fromCharCode(97 + index); // a, b, c, ...
    
    it(`GS-007${testId}: ${slug} should generate valid HowTo schema`, () => {
      const guide = getGuideBySlug(slug);
      expect(guide).not.toBeNull();
      
      const schema = generateHowToSchema(guide!);
      
      // Validate schema structure
      expect(schema['@context']).toBe('https://schema.org');
      expect(schema['@type']).toBe('HowTo');
      expect(schema.name).toBe(guide!.title);
      expect(schema.description).toBe(guide!.summary);
      expect(schema.totalTime).toMatch(/^PT\d+M$/);
      expect(schema.estimatedCost.value).toBe('0');
      expect(Array.isArray(schema.step)).toBe(true);
    });
  });

  it('should render all 17 guides without errors', () => {
    allGuides.forEach((guide) => {
      const { container } = render(<GuideSchema guide={guide} />);
      const script = container.querySelector('script[type="application/ld+json"]');
      expect(script).toBeInTheDocument();
      
      // Verify JSON is parseable
      expect(() => JSON.parse(script?.textContent || '')).not.toThrow();
    });
  });
});

// ============================================================================
// Integration Tests
// ============================================================================

describe('GuideSchema Integration', () => {
  it('should generate valid schema for guide with code examples', () => {
    const guideWithCode: SEOGuide = {
      ...mockGuide,
      sections: [
        {
          title: 'Code Example Section',
          content: 'This section has code examples.',
          codeExamples: [
            {
              language: 'html',
              code: '<meta name="viewport" content="width=device-width">',
              description: 'Viewport meta tag',
            },
          ],
        },
      ],
    };

    const schema = generateHowToSchema(guideWithCode);
    
    expect(schema.step).toHaveLength(1);
    expect(schema.step[0].name).toBe('Code Example Section');
  });

  it('should generate valid schema for guide with multiple sections', () => {
    const multiSectionGuide: SEOGuide = {
      ...mockGuide,
      sections: [
        { title: 'Section 1', content: 'Content 1' },
        { title: 'Section 2', content: 'Content 2' },
        { title: 'Section 3', content: 'Content 3' },
        { title: 'Section 4', content: 'Content 4' },
        { title: 'Section 5', content: 'Content 5' },
      ],
    };

    const schema = generateHowToSchema(multiSectionGuide);
    
    expect(schema.step).toHaveLength(5);
    schema.step.forEach((step: any, index: number) => {
      expect(step['@type']).toBe('HowToStep');
      expect(step.name).toBe(`Section ${index + 1}`);
    });
  });

  it('should preserve guide metadata in schema', () => {
    const schema = generateHowToSchema(mockGuide);
    
    expect(schema.name).toBe(mockGuide.title);
    expect(schema.description).toBe(mockGuide.summary);
    expect(schema.totalTime).toBe(`PT${mockGuide.readTime}M`);
  });
});

// ============================================================================
// Schema.org Validation Helper
// ============================================================================

describe('Schema.org Compliance', () => {
  it('should pass basic HowTo schema requirements', () => {
    const schema = generateHowToSchema(mockGuide);
    
    // Required properties for HowTo
    expect(schema['@context']).toBe('https://schema.org');
    expect(schema['@type']).toBe('HowTo');
    expect(typeof schema.name).toBe('string');
    expect(schema.name.length).toBeGreaterThan(0);
    
    // Step requirements
    if (schema.step.length > 0) {
      schema.step.forEach((step: any) => {
        expect(step['@type']).toBe('HowToStep');
        expect(typeof step.name).toBe('string');
        expect(typeof step.text).toBe('string');
      });
    }
  });

  it('should have valid JSON-LD structure', () => {
    const schema = generateHowToSchema(mockGuide);
    
    // Should be serializable
    const json = JSON.stringify(schema);
    const parsed = JSON.parse(json);
    
    expect(parsed['@context']).toBe('https://schema.org');
    expect(parsed['@type']).toBe('HowTo');
  });

  it('should match Google Rich Results requirements for HowTo', () => {
    const schema = generateHowToSchema(mockGuide);
    
    // Google required properties for HowTo rich result
    // @context, @type, name, step[]
    expect(schema['@context']).toBeDefined();
    expect(schema['@type']).toBe('HowTo');
    expect(schema.name).toBeDefined();
    expect(Array.isArray(schema.step)).toBe(true);
    
    // Each step must have @type, name (or text)
    schema.step.forEach((step: any) => {
      expect(step['@type']).toBe('HowToStep');
      expect(step.name || step.text).toBeDefined();
    });
  });
});
