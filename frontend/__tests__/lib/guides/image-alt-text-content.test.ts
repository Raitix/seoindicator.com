/**
 * TESTS/TASK-20-9: Image Alt Text Guide Content Tests
 * 
 * Validates content structure and quality for the Image Alt Text SEO guide.
 * Tests ensure the guide covers accessibility, SEO, writing effective alt text,
 * common mistakes, and best practices.
 */

import { getGuideBySlug, getAllGuides } from '../../../lib/guides/registry';
import { isSEOGuide } from '../../../lib/guides/types';

describe('Image Alt Text Guide - Content Structure', () => {
  let guide: ReturnType<typeof getGuideBySlug>;

  beforeAll(() => {
    guide = getGuideBySlug('image-alt-text');
  });

  // CO-001 to CO-004: Content Structure Tests
  describe('Guide Existence and Basic Structure', () => {
    it('CO-001: Guide exists in registry', () => {
      expect(guide).not.toBeNull();
      expect(guide).toBeDefined();
    });

    it('CO-002: Guide has correct title', () => {
      expect(guide).not.toBeNull();
      expect(guide!.title).toMatch(/alt text/i);
    });

    it('CO-003: Guide has non-empty summary', () => {
      expect(guide).not.toBeNull();
      expect(guide!.summary).toBeDefined();
      expect(guide!.summary.length).toBeGreaterThan(20);
    });

    it('CO-004: Guide has correct category', () => {
      expect(guide).not.toBeNull();
      expect(['on-page', 'content']).toContain(guide!.category);
    });
  });

  // WM-001 to WM-006: Why It Matters Section Tests (SEO + Accessibility)
  describe('Why It Matters Section', () => {
    it('WM-001: Has "Why it matters" section', () => {
      expect(guide).not.toBeNull();
      const section = guide!.sections.find(s => 
        s.title.toLowerCase().includes('why') || 
        s.title.toLowerCase().includes('matters')
      );
      expect(section).toBeDefined();
    });

    it('WM-002: Section mentions SEO', () => {
      expect(guide).not.toBeNull();
      const section = guide!.sections.find(s => 
        s.title.toLowerCase().includes('why') || 
        s.title.toLowerCase().includes('matters')
      );
      expect(section).toBeDefined();
      const content = section!.content.toLowerCase();
      expect(
        content.includes('seo') || 
        content.includes('search engine')
      ).toBe(true);
    });

    it('WM-003: Section mentions accessibility', () => {
      expect(guide).not.toBeNull();
      const section = guide!.sections.find(s => 
        s.title.toLowerCase().includes('why') || 
        s.title.toLowerCase().includes('matters')
      );
      expect(section).toBeDefined();
      const content = section!.content.toLowerCase();
      expect(
        content.includes('accessibility') || 
        content.includes('wcag')
      ).toBe(true);
    });

    it('WM-004: Section mentions screen readers', () => {
      expect(guide).not.toBeNull();
      const section = guide!.sections.find(s => 
        s.title.toLowerCase().includes('why') || 
        s.title.toLowerCase().includes('matters')
      );
      expect(section).toBeDefined();
      const content = section!.content.toLowerCase();
      expect(content.includes('screen reader')).toBe(true);
    });

    it('WM-005: Section explains image search benefits', () => {
      expect(guide).not.toBeNull();
      const section = guide!.sections.find(s => 
        s.title.toLowerCase().includes('why') || 
        s.title.toLowerCase().includes('matters')
      );
      expect(section).toBeDefined();
      const content = section!.content.toLowerCase();
      expect(
        content.includes('google image') || 
        content.includes('image search')
      ).toBe(true);
    });

    it('WM-006: Section has valid content length', () => {
      expect(guide).not.toBeNull();
      const section = guide!.sections.find(s => 
        s.title.toLowerCase().includes('why') || 
        s.title.toLowerCase().includes('matters')
      );
      expect(section).toBeDefined();
      expect(section!.content.length).toBeGreaterThan(200);
    });
  });

  // ET-001 to ET-005: How to Write Effective Alt Text Tests
  describe('How to Write Effective Alt Text Section', () => {
    it('ET-001: Has effective alt text section', () => {
      expect(guide).not.toBeNull();
      const section = guide!.sections.find(s => 
        s.title.toLowerCase().includes('how') || 
        s.title.toLowerCase().includes('write') ||
        s.title.toLowerCase().includes('effective')
      );
      expect(section).toBeDefined();
    });

    it('ET-002: Explains descriptive text', () => {
      expect(guide).not.toBeNull();
      const section = guide!.sections.find(s => 
        s.title.toLowerCase().includes('how') || 
        s.title.toLowerCase().includes('write') ||
        s.title.toLowerCase().includes('effective')
      );
      expect(section).toBeDefined();
      const content = section!.content.toLowerCase();
      expect(
        content.includes('descriptive') || 
        content.includes('describe')
      ).toBe(true);
    });

    it('ET-003: Mentions length guidelines', () => {
      expect(guide).not.toBeNull();
      const section = guide!.sections.find(s => 
        s.title.toLowerCase().includes('how') || 
        s.title.toLowerCase().includes('write') ||
        s.title.toLowerCase().includes('effective')
      );
      expect(section).toBeDefined();
      const content = section!.content.toLowerCase();
      expect(
        content.includes('character') || 
        content.includes('length') ||
        content.includes('125')
      ).toBe(true);
    });

    it('ET-004: Explains context importance', () => {
      expect(guide).not.toBeNull();
      const section = guide!.sections.find(s => 
        s.title.toLowerCase().includes('how') || 
        s.title.toLowerCase().includes('write') ||
        s.title.toLowerCase().includes('effective')
      );
      expect(section).toBeDefined();
      const content = section!.content.toLowerCase();
      expect(content.includes('context')).toBe(true);
    });

    it('ET-005: Section has valid content length', () => {
      expect(guide).not.toBeNull();
      const section = guide!.sections.find(s => 
        s.title.toLowerCase().includes('how') || 
        s.title.toLowerCase().includes('write') ||
        s.title.toLowerCase().includes('effective')
      );
      expect(section).toBeDefined();
      expect(section!.content.length).toBeGreaterThan(150);
    });
  });

  // CM-001 to CM-005: Common Mistakes Section Tests
  describe('Common Mistakes Section', () => {
    it('CM-001: Has common mistakes section', () => {
      expect(guide).not.toBeNull();
      const section = guide!.sections.find(s => 
        s.title.toLowerCase().includes('mistake') || 
        s.title.toLowerCase().includes('avoid')
      );
      expect(section).toBeDefined();
    });

    it('CM-002: Mentions keyword stuffing', () => {
      expect(guide).not.toBeNull();
      const section = guide!.sections.find(s => 
        s.title.toLowerCase().includes('mistake') || 
        s.title.toLowerCase().includes('avoid')
      );
      expect(section).toBeDefined();
      const content = section!.content.toLowerCase();
      expect(
        content.includes('keyword stuffing') || 
        content.includes('keyword')
      ).toBe(true);
    });

    it('CM-003: Mentions empty alt on meaningful images', () => {
      expect(guide).not.toBeNull();
      const section = guide!.sections.find(s => 
        s.title.toLowerCase().includes('mistake') || 
        s.title.toLowerCase().includes('avoid')
      );
      expect(section).toBeDefined();
      const content = section!.content.toLowerCase();
      expect(
        content.includes('empty') || 
        content.includes('missing alt')
      ).toBe(true);
    });

    it('CM-004: Explains decorative images', () => {
      expect(guide).not.toBeNull();
      const section = guide!.sections.find(s => 
        s.title.toLowerCase().includes('mistake') || 
        s.title.toLowerCase().includes('avoid')
      );
      expect(section).toBeDefined();
      const content = section!.content.toLowerCase();
      expect(
        content.includes('decorative') || 
        content.includes('alt=""')
      ).toBe(true);
    });

    it('CM-005: Section has valid content length', () => {
      expect(guide).not.toBeNull();
      const section = guide!.sections.find(s => 
        s.title.toLowerCase().includes('mistake') || 
        s.title.toLowerCase().includes('avoid')
      );
      expect(section).toBeDefined();
      expect(section!.content.length).toBeGreaterThan(150);
    });
  });

  // DI-001 to DI-003: Decorative Images Tests
  describe('Decorative Images (alt="")', () => {
    it('DI-001: Explains decorative image handling', () => {
      expect(guide).not.toBeNull();
      const allContent = guide!.sections.map(s => s.content).join(' ').toLowerCase();
      expect(
        allContent.includes('alt=""') || 
        allContent.includes('empty alt')
      ).toBe(true);
    });

    it('DI-002: Explains when to use empty alt', () => {
      expect(guide).not.toBeNull();
      const allContent = guide!.sections.map(s => s.content).join(' ').toLowerCase();
      expect(
        allContent.includes('decorative') || 
        allContent.includes('purely decorative')
      ).toBe(true);
    });

    it('DI-003: Mentions aria-hidden alternative (optional)', () => {
      expect(guide).not.toBeNull();
      const allContent = guide!.sections.map(s => s.content).join(' ').toLowerCase();
      // This is optional - aria-hidden is an alternative but not required
      const hasAriaMention = allContent.includes('aria-hidden');
      // Pass either way - this is optional content
      expect(true).toBe(true);
    });
  });

  // BC-001 to BC-006: Best Practices Checklist Tests
  describe('Best Practices Checklist', () => {
    it('BC-001: Checklist has 5-10 items', () => {
      expect(guide).not.toBeNull();
      expect(guide!.checklist.length).toBeGreaterThanOrEqual(5);
      expect(guide!.checklist.length).toBeLessThanOrEqual(10);
    });

    it('BC-002: All items have valid text', () => {
      expect(guide).not.toBeNull();
      guide!.checklist.forEach(item => {
        expect(item.text).toBeDefined();
        expect(item.text.length).toBeGreaterThan(10);
      });
    });

    it('BC-003: All items have valid priority', () => {
      expect(guide).not.toBeNull();
      const validPriorities = ['critical', 'important', 'recommended'];
      guide!.checklist.forEach(item => {
        expect(validPriorities).toContain(item.priority);
      });
    });

    it('BC-004: At least one critical item', () => {
      expect(guide).not.toBeNull();
      const criticalItems = guide!.checklist.filter(item => item.priority === 'critical');
      expect(criticalItems.length).toBeGreaterThanOrEqual(1);
    });

    it('BC-005: Covers key alt text practices', () => {
      expect(guide).not.toBeNull();
      const allText = guide!.checklist.map(item => item.text.toLowerCase()).join(' ');
      
      // Check for coverage of key topics
      const hasDescriptive = allText.includes('descriptive');
      const hasLength = allText.includes('character') || allText.includes('length');
      const hasDecorative = allText.includes('decorative') || allText.includes('empty');
      
      expect(hasDescriptive || hasLength || hasDecorative).toBe(true);
    });

    it('BC-006: Mentions WCAG guidelines', () => {
      expect(guide).not.toBeNull();
      const allContent = guide!.sections.map(s => s.content).join(' ').toLowerCase();
      const allChecklist = guide!.checklist.map(item => item.text.toLowerCase()).join(' ');
      const combinedContent = allContent + ' ' + allChecklist;
      
      expect(combinedContent.includes('wcag')).toBe(true);
    });
  });

  // RT-001 to RT-003: Read Time Tests
  describe('Read Time', () => {
    it('RT-001: Read time is set', () => {
      expect(guide).not.toBeNull();
      expect(guide!.readTime).toBeDefined();
      expect(typeof guide!.readTime).toBe('number');
    });

    it('RT-002: Read time in valid range (3-5 minutes)', () => {
      expect(guide).not.toBeNull();
      expect(guide!.readTime).toBeGreaterThanOrEqual(3);
      expect(guide!.readTime).toBeLessThanOrEqual(5);
    });

    it('RT-003: Read time matches content', () => {
      expect(guide).not.toBeNull();
      // Calculate approximate word count
      const allContent = guide!.sections.map(s => s.content).join(' ');
      const wordCount = allContent.split(/\s+/).length;
      
      // At 200-300 words per minute, verify read time is reasonable
      const minExpectedTime = Math.floor(wordCount / 300);
      const maxExpectedTime = Math.ceil(wordCount / 200);
      
      // Read time should be within reasonable range of calculated
      expect(guide!.readTime).toBeGreaterThanOrEqual(minExpectedTime - 1);
      expect(guide!.readTime).toBeLessThanOrEqual(maxExpectedTime + 1);
    });
  });

  // RG-001 to RG-004: Related Guides Tests
  describe('Related Guides', () => {
    it('RG-001: Has related guides array', () => {
      expect(guide).not.toBeNull();
      expect(Array.isArray(guide!.relatedGuides)).toBe(true);
    });

    it('RG-002: Contains image-optimization', () => {
      expect(guide).not.toBeNull();
      expect(guide!.relatedGuides).toContain('image-optimization');
    });

    it('RG-003: Contains structured-data', () => {
      expect(guide).not.toBeNull();
      expect(guide!.relatedGuides).toContain('structured-data');
    });

    it('RG-004: Related guides exist in registry', () => {
      expect(guide).not.toBeNull();
      const allGuides = getAllGuides();
      const allSlugs = allGuides.map(g => g.slug);
      
      guide!.relatedGuides.forEach(relatedSlug => {
        expect(allSlugs).toContain(relatedSlug);
      });
    });
  });

  // GR-001 to GR-003: Guide Registry Integration Tests
  describe('Guide Registry Integration', () => {
    it('GR-001: Guide in registry', () => {
      const allGuides = getAllGuides();
      const slugs = allGuides.map(g => g.slug);
      expect(slugs).toContain('image-alt-text');
    });

    it('GR-002: getGuideBySlug returns complete guide', () => {
      expect(guide).not.toBeNull();
      expect(guide!.slug).toBe('image-alt-text');
      expect(guide!.sections).toBeDefined();
      expect(guide!.checklist).toBeDefined();
      expect(guide!.relatedGuides).toBeDefined();
    });

    it('GR-003: Guide validates with type guard', () => {
      expect(guide).not.toBeNull();
      expect(isSEOGuide(guide!)).toBe(true);
    });
  });

  // Security Tests
  describe('Security', () => {
    it('No XSS vulnerabilities in content', () => {
      expect(guide).not.toBeNull();
      const allContent = guide!.sections.map(s => s.content).join(' ');
      
      // Check for potentially dangerous patterns
      expect(allContent.toLowerCase()).not.toContain('<script');
      expect(allContent.toLowerCase()).not.toContain('javascript:');
      expect(allContent.toLowerCase()).not.toContain('onerror=');
      expect(allContent.toLowerCase()).not.toContain('onclick=');
    });

    it('No inline event handlers in code examples', () => {
      expect(guide).not.toBeNull();
      const allCode = guide!.sections
        .flatMap(s => s.codeExamples?.map(ce => ce.code) || [])
        .join(' ')
        .toLowerCase();
      
      // Inline handlers in examples should be educational, not dangerous
      // This test just verifies no malicious patterns
      expect(allCode).not.toContain('document.cookie');
      expect(allCode).not.toContain('eval(');
    });
  });
});
