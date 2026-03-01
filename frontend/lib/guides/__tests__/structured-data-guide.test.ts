/**
 * Tests for Structured Data SEO Guide Content
 * Task: TASK-20-16 - Write Content for Structured Data Guide
 * 
 * These tests verify the content structure, coverage, and quality
 * of the Structured Data SEO guide.
 */

import {
  getGuideBySlug,
  getAllGuides,
} from '../registry';
import {
  SEOGuide,
  GuideSection,
  ChecklistItem,
  isGuideSection,
  isChecklistItem,
} from '../types';

describe('Structured Data Guide Content', () => {
  let guide: SEOGuide | null;

  beforeAll(() => {
    guide = getGuideBySlug('structured-data');
  });

  describe('Guide Structure', () => {
    it('should exist in the registry', () => {
      expect(guide).not.toBeNull();
    });

    it('should have correct slug', () => {
      expect(guide?.slug).toBe('structured-data');
    });

    it('should have a title containing "Structured Data"', () => {
      expect(guide?.title.toLowerCase()).toContain('structured data');
    });

    it('should be in technical category', () => {
      expect(guide?.category).toBe('technical');
    });

    it('should have an icon', () => {
      expect(guide?.icon).toBe('📊');
    });

    it('should have read time between 5-7 minutes', () => {
      expect(guide?.readTime).toBeGreaterThanOrEqual(5);
      expect(guide?.readTime).toBeLessThanOrEqual(7);
    });
  });

  describe('Content Sections - What is Structured Data', () => {
    it('should have a section explaining structured data', () => {
      const section = guide?.sections.find(
        (s) => s.title.toLowerCase().includes('what is') ||
               s.title.toLowerCase().includes('introduction') ||
               s.title.toLowerCase().includes('overview')
      );
      expect(section).toBeDefined();
    });

    it('should mention Schema.org', () => {
      const allContent = guide?.sections.map((s) => s.content).join(' ') || '';
      expect(allContent.toLowerCase()).toMatch(/schema\.org|schema org/);
    });

    it('should explain the purpose of structured data', () => {
      const allContent = guide?.sections.map((s) => s.content.toLowerCase()).join(' ') || '';
      expect(allContent).toMatch(/search engine.*understand|machine.readable|structured data helps/);
    });
  });

  describe('Content Sections - SEO Benefits', () => {
    it('should have a benefits section', () => {
      const section = guide?.sections.find(
        (s) => s.title.toLowerCase().includes('benefit') ||
               s.title.toLowerCase().includes('why') ||
               s.title.toLowerCase().includes('seo value')
      );
      expect(section).toBeDefined();
    });

    it('should mention rich snippets/rich results', () => {
      const allContent = guide?.sections.map((s) => s.content).join(' ') || '';
      expect(allContent.toLowerCase()).toMatch(/rich snippet|rich result/);
    });

    it('should mention knowledge panels', () => {
      const allContent = guide?.sections.map((s) => s.content).join(' ') || '';
      expect(allContent.toLowerCase()).toMatch(/knowledge panel|knowledge graph/);
    });

    it('should mention CTR improvement', () => {
      const allContent = guide?.sections.map((s) => s.content).join(' ') || '';
      expect(allContent.toLowerCase()).toMatch(/click.through|ctr/);
    });
  });

  describe('Content Sections - Schema.org Types', () => {
    it('should have a types section', () => {
      const section = guide?.sections.find(
        (s) => s.title.toLowerCase().includes('type') ||
               s.title.toLowerCase().includes('schema') ||
               s.title.toLowerCase().includes('common')
      );
      expect(section).toBeDefined();
    });

    it('should mention Article schema type', () => {
      const allContent = guide?.sections.map((s) => s.content).join(' ') || '';
      expect(allContent.toLowerCase()).toContain('article');
    });

    it('should mention Product schema type', () => {
      const allContent = guide?.sections.map((s) => s.content).join(' ') || '';
      expect(allContent.toLowerCase()).toContain('product');
    });

    it('should mention FAQ schema type', () => {
      const allContent = guide?.sections.map((s) => s.content).join(' ') || '';
      expect(allContent.toLowerCase()).toMatch(/faq|faqpage/);
    });

    it('should mention BreadcrumbList schema type', () => {
      const allContent = guide?.sections.map((s) => s.content).join(' ') || '';
      expect(allContent.toLowerCase()).toMatch(/breadcrumb|breadcrumblist/);
    });

    it('should mention Organization schema type', () => {
      const allContent = guide?.sections.map((s) => s.content).join(' ') || '';
      expect(allContent.toLowerCase()).toContain('organization');
    });
  });

  describe('Content Sections - Implementation Methods', () => {
    it('should have an implementation section', () => {
      const section = guide?.sections.find(
        (s) => s.title.toLowerCase().includes('implement') ||
               s.title.toLowerCase().includes('how to') ||
               s.title.toLowerCase().includes('add') ||
               s.title.toLowerCase().includes('method')
      );
      expect(section).toBeDefined();
    });

    it('should mention JSON-LD as preferred method', () => {
      const allContent = guide?.sections.map((s) => s.content).join(' ') || '';
      expect(allContent.toLowerCase()).toMatch(/json.ld|json\-ld/);
      expect(allContent.toLowerCase()).toMatch(/preferred|recommended|google.recommends/);
    });

    it('should mention Microdata as alternative', () => {
      const allContent = guide?.sections.map((s) => s.content).join(' ') || '';
      expect(allContent.toLowerCase()).toContain('microdata');
    });

    it('should explain JSON-LD script tag format', () => {
      const allContent = guide?.sections.map((s) => s.content).join(' ') || '';
      expect(allContent.toLowerCase()).toMatch(/script.*tag|application\/ld\+json/);
    });
  });

  describe('Content Sections - Testing Tools', () => {
    it('should have a testing/validation section', () => {
      const section = guide?.sections.find(
        (s) => s.title.toLowerCase().includes('test') ||
               s.title.toLowerCase().includes('validat') ||
               s.title.toLowerCase().includes('debug') ||
               s.title.toLowerCase().includes('check')
      );
      expect(section).toBeDefined();
    });

    it('should mention Google Rich Results Test', () => {
      const allContent = guide?.sections.map((s) => s.content).join(' ') || '';
      expect(allContent.toLowerCase()).toMatch(/rich results test|rich result test/);
    });

    it('should mention schema validation tools', () => {
      const allContent = guide?.sections.map((s) => s.content).join(' ') || '';
      expect(allContent.toLowerCase()).toMatch(/schema.*validat|validat.*schema|markup.*test/);
    });
  });

  describe('Code Examples - JSON-LD', () => {
    it('should include JSON-LD code examples', () => {
      const codeExamples = guide?.sections.flatMap((s) => s.codeExamples || []) || [];
      expect(codeExamples.length).toBeGreaterThan(0);
    });

    it('should have at least one JSON example', () => {
      const codeExamples = guide?.sections.flatMap((s) => s.codeExamples || []) || [];
      const jsonExample = codeExamples.find(
        (ex) => ex.language === 'json' || ex.language === 'javascript'
      );
      expect(jsonExample).toBeDefined();
    });

    it('should include @context with schema.org', () => {
      const codeExamples = guide?.sections.flatMap((s) => s.codeExamples || []) || [];
      const allCode = codeExamples.map((ex) => ex.code).join('\n');
      expect(allCode).toMatch(/@context.*schema\.org|schema\.org.*@context/);
    });

    it('should include @type in JSON-LD examples', () => {
      const codeExamples = guide?.sections.flatMap((s) => s.codeExamples || []) || [];
      const allCode = codeExamples.map((ex) => ex.code).join('\n');
      expect(allCode).toContain('@type');
    });

    it('should have valid JSON structure in examples', () => {
      const codeExamples = guide?.sections.flatMap((s) => s.codeExamples || []) || [];
      const jsonExample = codeExamples.find((ex) => ex.language === 'json');
      
      if (jsonExample) {
        const code = jsonExample.code;
        const jsonMatch = code.match(/\{[\s\S]*\}/);
        
        if (jsonMatch) {
          expect(() => JSON.parse(jsonMatch[0])).not.toThrow();
        }
      }
    });
  });

  describe('Checklist', () => {
    it('should have 5-10 checklist items', () => {
      expect(guide?.checklist.length).toBeGreaterThanOrEqual(5);
      expect(guide?.checklist.length).toBeLessThanOrEqual(10);
    });

    it('should have valid checklist item structure', () => {
      guide?.checklist.forEach((item) => {
        expect(isChecklistItem(item)).toBe(true);
      });
    });

    it('should have at least one critical priority item', () => {
      const hasCritical = guide?.checklist.some((item) => item.priority === 'critical');
      expect(hasCritical).toBe(true);
    });

    it('should have at least one important priority item', () => {
      const hasImportant = guide?.checklist.some((item) => item.priority === 'important');
      expect(hasImportant).toBe(true);
    });

    it('should have actionable checklist items', () => {
      guide?.checklist.forEach((item) => {
        expect(item.text.length).toBeGreaterThan(10);
      });
    });
  });

  describe('Related Guides', () => {
    it('should include open-graph as related guide', () => {
      expect(guide?.relatedGuides).toContain('open-graph');
    });

    it('should include canonical-urls as related guide', () => {
      expect(guide?.relatedGuides).toContain('canonical-urls');
    });

    it('should have valid related guide slugs', () => {
      const allGuides = getAllGuides();
      const allSlugs = allGuides.map((g) => g.slug);
      
      guide?.relatedGuides.forEach((slug) => {
        expect(allSlugs).toContain(slug);
      });
    });
  });

  describe('Content Quality', () => {
    it('should have a meaningful summary (20-200 characters)', () => {
      expect(guide?.summary.length).toBeGreaterThanOrEqual(20);
      expect(guide?.summary.length).toBeLessThanOrEqual(200);
    });

    it('should have at least 5 content sections', () => {
      expect(guide?.sections.length).toBeGreaterThanOrEqual(5);
    });

    it('should have substantial content in each section (100+ chars)', () => {
      guide?.sections.forEach((section) => {
        expect(section.content.length).toBeGreaterThan(100);
      });
    });

    it('should have valid section structure', () => {
      guide?.sections.forEach((section) => {
        expect(isGuideSection(section)).toBe(true);
      });
    });

    it('should have no duplicate section titles', () => {
      const titles = guide?.sections.map((s) => s.title) || [];
      const uniqueTitles = new Set(titles);
      expect(titles.length).toBe(uniqueTitles.size);
    });
  });
});
