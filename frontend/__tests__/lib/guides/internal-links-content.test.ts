/**
 * Internal Links Guide Content Tests
 * Task: TASK-20-11 - Write Content for Internal Links Guide
 * 
 * Tests for content structure and quality validation of the Internal Links guide.
 */

import {
  getGuideBySlug,
  getAllGuides,
} from '@/lib/guides/registry';
import { isSEOGuide } from '@/lib/guides/types';

describe('Internal Links Guide - Content Structure', () => {
  let guide: ReturnType<typeof getGuideBySlug>;

  beforeAll(() => {
    guide = getGuideBySlug('internal-links');
  });

  describe('Guide Existence and Basic Properties', () => {
    it('IL-001: Guide exists in registry', () => {
      expect(guide).not.toBeNull();
    });

    it('IL-002: Guide has correct title', () => {
      expect(guide).toHaveProperty('title', 'Internal Linking Strategy');
    });

    it('IL-003: Guide has non-empty summary', () => {
      expect(guide).toHaveProperty('summary');
      expect(guide!.summary.length).toBeGreaterThan(20);
    });

    it('IL-004: Guide category is on-page', () => {
      expect(guide).toHaveProperty('category', 'on-page');
    });
  });

  describe('What Are Internal Links Section', () => {
    let whatIsSection: typeof guide extends { sections: infer S } ? S[0] : never;

    beforeAll(() => {
      whatIsSection = guide?.sections.find(
        (s) =>
          s.title.toLowerCase().includes('what') &&
          s.title.toLowerCase().includes('internal')
      ) as typeof whatIsSection;
    });

    it('WI-001: Has "What are Internal Links?" section', () => {
      expect(whatIsSection).toBeDefined();
    });

    it('WI-002: Section explains internal links', () => {
      const content = whatIsSection?.content.toLowerCase() || '';
      expect(
        content.includes('link') && content.includes('page')
      ).toBe(true);
    });

    it('WI-003: Section differentiates from external', () => {
      const content = whatIsSection?.content.toLowerCase() || '';
      expect(
        content.includes('external') ||
        content.includes('same domain') ||
        content.includes('within your') ||
        content.includes('within the')
      ).toBe(true);
    });

    it('WI-004: Section has valid content length', () => {
      expect(whatIsSection?.content.length).toBeGreaterThan(150);
    });
  });

  describe('Why Internal Links Matter Section', () => {
    let whyMatterSection: typeof guide extends { sections: infer S } ? S[0] : never;

    beforeAll(() => {
      whyMatterSection = guide?.sections.find(
        (s) =>
          (s.title.toLowerCase().includes('why') &&
           s.title.toLowerCase().includes('matter')) ||
          s.title.toLowerCase().includes('important') ||
          s.title.toLowerCase().includes('benefit')
      ) as typeof whyMatterSection;
    });

    it('WM-001: Has "Why Internal Links Matter" section', () => {
      expect(whyMatterSection).toBeDefined();
    });

    it('WM-002: Section mentions crawlability', () => {
      const content = whyMatterSection?.content.toLowerCase() || '';
      expect(
        content.includes('crawl') ||
        content.includes('index') ||
        content.includes('discover')
      ).toBe(true);
    });

    it('WM-003: Section mentions user navigation', () => {
      const content = whyMatterSection?.content.toLowerCase() || '';
      expect(
        content.includes('navigation') ||
        content.includes('user') ||
        content.includes('browse')
      ).toBe(true);
    });

    it('WM-004: Section mentions page authority', () => {
      const content = whyMatterSection?.content.toLowerCase() || '';
      expect(
        content.includes('authority') ||
        content.includes('equity') ||
        content.includes('rank') ||
        content.includes('value')
      ).toBe(true);
    });

    it('WM-005: Section has valid content length', () => {
      expect(whyMatterSection?.content.length).toBeGreaterThan(200);
    });
  });

  describe('Site Architecture and Link Equity Section', () => {
    let architectureSection: typeof guide extends { sections: infer S } ? S[0] : never;

    beforeAll(() => {
      architectureSection = guide?.sections.find(
        (s) =>
          s.title.toLowerCase().includes('architecture') ||
          s.title.toLowerCase().includes('structure') ||
          s.title.toLowerCase().includes('equity')
      ) as typeof architectureSection;
    });

    it('SA-001: Has site architecture section', () => {
      expect(architectureSection).toBeDefined();
    });

    it('SA-002: Mentions hub-and-spoke model', () => {
      const content = architectureSection?.content.toLowerCase() || '';
      expect(
        content.includes('hub') ||
        content.includes('spoke') ||
        content.includes('pillar') ||
        content.includes('cluster')
      ).toBe(true);
    });

    it('SA-003: Explains link equity distribution', () => {
      const content = architectureSection?.content.toLowerCase() || '';
      expect(
        content.includes('equity') ||
        content.includes('juice') ||
        content.includes('value') ||
        content.includes('pass') ||
        content.includes('distribute')
      ).toBe(true);
    });

    it('SA-004: Mentions site structure', () => {
      const content = architectureSection?.content.toLowerCase() || '';
      expect(
        content.includes('structure') ||
        content.includes('hierarchy') ||
        content.includes('depth')
      ).toBe(true);
    });

    it('SA-005: Mentions URL structure', () => {
      const content = architectureSection?.content.toLowerCase() || '';
      expect(
        content.includes('url') ||
        content.includes('path') ||
        content.includes('slug')
      ).toBe(true);
    });

    it('SA-006: Section has valid content length', () => {
      expect(architectureSection?.content.length).toBeGreaterThan(250);
    });
  });

  describe('Anchor Text Best Practices Section', () => {
    let anchorSection: typeof guide extends { sections: infer S } ? S[0] : never;

    beforeAll(() => {
      anchorSection = guide?.sections.find(
        (s) =>
          s.title.toLowerCase().includes('anchor')
      ) as typeof anchorSection;
    });

    it('AT-001: Has anchor text section', () => {
      expect(anchorSection).toBeDefined();
    });

    it('AT-002: Explains descriptive anchors', () => {
      const content = anchorSection?.content.toLowerCase() || '';
      expect(
        content.includes('descriptive') ||
        content.includes('relevant') ||
        content.includes('meaningful') ||
        content.includes('specific')
      ).toBe(true);
    });

    it('AT-003: Warns against generic text', () => {
      const content = anchorSection?.content.toLowerCase() || '';
      expect(
        content.includes('click here') ||
        content.includes('avoid generic') ||
        content.includes('generic') ||
        content.includes('read more')
      ).toBe(true);
    });

    it('AT-004: Mentions keyword anchors', () => {
      const content = anchorSection?.content.toLowerCase() || '';
      expect(
        content.includes('keyword') ||
        content.includes('exact match') ||
        content.includes('branded') ||
        content.includes('partial')
      ).toBe(true);
    });

    it('AT-005: Section has valid content length', () => {
      expect(anchorSection?.content.length).toBeGreaterThan(150);
    });
  });

  describe('Common Mistakes Section', () => {
    let mistakesSection: typeof guide extends { sections: infer S } ? S[0] : never;

    beforeAll(() => {
      mistakesSection = guide?.sections.find(
        (s) =>
          s.title.toLowerCase().includes('mistake') ||
          s.title.toLowerCase().includes('avoid') ||
          s.title.toLowerCase().includes('error')
      ) as typeof mistakesSection;
    });

    it('CM-001: Has common mistakes section', () => {
      expect(mistakesSection).toBeDefined();
    });

    it('CM-002: Mentions orphan pages', () => {
      const content = mistakesSection?.content.toLowerCase() || '';
      expect(
        content.includes('orphan') ||
        content.includes('isolated')
      ).toBe(true);
    });

    it('CM-003: Warns about over-optimization', () => {
      const content = mistakesSection?.content.toLowerCase() || '';
      expect(
        content.includes('over-optimize') ||
        content.includes('excessive') ||
        content.includes('spam') ||
        content.includes('too many')
      ).toBe(true);
    });

    it('CM-004: Mentions broken links', () => {
      const content = mistakesSection?.content.toLowerCase() || '';
      expect(
        content.includes('broken') ||
        content.includes('404')
      ).toBe(true);
    });

    it('CM-005: Warns about excessive links', () => {
      const content = mistakesSection?.content.toLowerCase() || '';
      expect(
        content.includes('too many') ||
        content.includes('limit') ||
        content.includes('excessive') ||
        content.includes('overload')
      ).toBe(true);
    });

    it('CM-006: Section has valid content length', () => {
      expect(mistakesSection?.content.length).toBeGreaterThan(200);
    });
  });

  describe('Breadcrumbs Coverage', () => {
    it('BR-001: Mentions breadcrumbs somewhere in content', () => {
      const allContent = guide?.sections
        .map((s) => s.content.toLowerCase())
        .join(' ') || '';
      expect(allContent.includes('breadcrumb')).toBe(true);
    });
  });

  describe('Best Practices Checklist', () => {
    it('BC-001: Checklist has 5-10 items', () => {
      expect(guide?.checklist.length).toBeGreaterThanOrEqual(5);
      expect(guide?.checklist.length).toBeLessThanOrEqual(10);
    });

    it('BC-002: All items have valid text', () => {
      guide?.checklist.forEach((item) => {
        expect(item.text.length).toBeGreaterThan(0);
      });
    });

    it('BC-003: All items have valid priority', () => {
      const validPriorities = ['critical', 'important', 'recommended'];
      guide?.checklist.forEach((item) => {
        expect(validPriorities).toContain(item.priority);
      });
    });

    it('BC-004: At least one critical item', () => {
      const hasCritical = guide?.checklist.some(
        (item) => item.priority === 'critical'
      );
      expect(hasCritical).toBe(true);
    });

    it('BC-005: Covers key practices', () => {
      const checklistText = guide?.checklist
        .map((item) => item.text.toLowerCase())
        .join(' ') || '';
      
      // Should cover at least 3 of these key topics
      const keyTopics = [
        'anchor text',
        'hub',
        'orphan',
        'depth',
        'broken',
        'breadcrumb',
        'navigation'
      ];
      const coveredTopics = keyTopics.filter((topic) =>
        checklistText.includes(topic)
      );
      expect(coveredTopics.length).toBeGreaterThanOrEqual(3);
    });
  });

  describe('Read Time', () => {
    it('RT-001: Read time is set', () => {
      expect(guide).toHaveProperty('readTime');
      expect(typeof guide!.readTime).toBe('number');
    });

    it('RT-002: Read time in valid range (4-6 minutes)', () => {
      expect(guide!.readTime).toBeGreaterThanOrEqual(4);
      expect(guide!.readTime).toBeLessThanOrEqual(6);
    });

    it('RT-003: Read time matches expected value', () => {
      // Task specifies 4-6 minutes, registry shows 5
      expect(guide!.readTime).toBe(5);
    });
  });

  describe('Related Guides', () => {
    it('RG-001: Has related guides array', () => {
      expect(Array.isArray(guide?.relatedGuides)).toBe(true);
    });

    it('RG-002: Contains external-links', () => {
      expect(guide?.relatedGuides).toContain('external-links');
    });

    it('RG-003: Contains xml-sitemap', () => {
      expect(guide?.relatedGuides).toContain('xml-sitemap');
    });

    it('RG-004: Related guides exist in registry', () => {
      guide?.relatedGuides.forEach((relatedSlug) => {
        const relatedGuide = getGuideBySlug(relatedSlug);
        expect(relatedGuide).not.toBeNull();
      });
    });
  });

  describe('Guide Registry Integration', () => {
    it('GR-001: Guide in registry', () => {
      const allGuides = getAllGuides();
      const found = allGuides.find((g) => g.slug === 'internal-links');
      expect(found).toBeDefined();
    });

    it('GR-002: getGuideBySlug returns complete guide', () => {
      expect(guide).toHaveProperty('slug');
      expect(guide).toHaveProperty('title');
      expect(guide).toHaveProperty('summary');
      expect(guide).toHaveProperty('category');
      expect(guide).toHaveProperty('sections');
      expect(guide).toHaveProperty('checklist');
      expect(guide).toHaveProperty('relatedGuides');
    });

    it('GR-003: Guide validates with type guard', () => {
      expect(isSEOGuide(guide!)).toBe(true);
    });
  });
});
