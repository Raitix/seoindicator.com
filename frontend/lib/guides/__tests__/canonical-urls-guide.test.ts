/**
 * Tests for Canonical URLs SEO Guide Content
 * Task: TASK-20-17 - Write Content for Canonical URLs Guide
 * 
 * These tests verify the content structure, coverage, and quality
 * of the Canonical URLs SEO guide.
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

describe('Canonical URLs Guide Content', () => {
  let guide: SEOGuide | null;

  beforeAll(() => {
    guide = getGuideBySlug('canonical-urls');
  });

  describe('Guide Structure', () => {
    it('should exist in the registry', () => {
      expect(guide).not.toBeNull();
    });

    it('should have correct slug', () => {
      expect(guide?.slug).toBe('canonical-urls');
    });

    it('should have a title containing "Canonical"', () => {
      expect(guide?.title.toLowerCase()).toContain('canonical');
    });

    it('should be in technical category', () => {
      expect(guide?.category).toBe('technical');
    });

    it('should have an icon', () => {
      expect(guide?.icon).toBe('🎯');
    });

    it('should have read time between 4-6 minutes', () => {
      expect(guide?.readTime).toBeGreaterThanOrEqual(4);
      expect(guide?.readTime).toBeLessThanOrEqual(6);
    });
  });

  describe('Content Sections - What are Canonical URLs', () => {
    it('should have a section explaining canonical URLs', () => {
      const section = guide?.sections.find(
        (s) => s.title.toLowerCase().includes('what is') ||
               s.title.toLowerCase().includes('what are') ||
               s.title.toLowerCase().includes('introduction') ||
               s.title.toLowerCase().includes('overview')
      );
      expect(section).toBeDefined();
    });

    it('should define canonical URL concept', () => {
      const allContent = guide?.sections.map((s) => s.content.toLowerCase()).join(' ') || '';
      expect(allContent).toMatch(/canonical.*url|canonical.*tag|rel=.canonical/);
    });

    it('should explain the purpose of canonical tags', () => {
      const allContent = guide?.sections.map((s) => s.content.toLowerCase()).join(' ') || '';
      expect(allContent).toMatch(/preferred.*version|master.*copy|original.*page|duplicate.*content/);
    });
  });

  describe('Content Sections - Why Canonicals Matter (Duplicate Content)', () => {
    it('should have a section about why canonicals matter', () => {
      const section = guide?.sections.find(
        (s) => s.title.toLowerCase().includes('why') ||
               s.title.toLowerCase().includes('matter') ||
               s.title.toLowerCase().includes('importance') ||
               s.title.toLowerCase().includes('duplicate')
      );
      expect(section).toBeDefined();
    });

    it('should mention duplicate content issues', () => {
      const allContent = guide?.sections.map((s) => s.content).join(' ') || '';
      expect(allContent.toLowerCase()).toMatch(/duplicate.*content|content.*duplicate/);
    });

    it('should explain SEO impact of duplicate content', () => {
      const allContent = guide?.sections.map((s) => s.content.toLowerCase()).join(' ') || '';
      expect(allContent).toMatch(/seo.*impact|ranking.*issue|crawl.*budget|index.*problem/);
    });

    it('should mention search engine consolidation', () => {
      const allContent = guide?.sections.map((s) => s.content).join(' ') || '';
      expect(allContent.toLowerCase()).toMatch(/consolidat|search.*engine|google.*understand/);
    });
  });

  describe('Content Sections - When to Use Canonical Tags', () => {
    it('should have a section about when to use canonicals', () => {
      const section = guide?.sections.find(
        (s) => s.title.toLowerCase().includes('when') ||
               s.title.toLowerCase().includes('use case') ||
               s.title.toLowerCase().includes('scenario')
      );
      expect(section).toBeDefined();
    });

    it('should mention URL parameters scenario', () => {
      const allContent = guide?.sections.map((s) => s.content).join(' ') || '';
      expect(allContent.toLowerCase()).toMatch(/parameter|query.*string|\?.*=|filter|sort/);
    });

    it('should mention www vs non-www scenario', () => {
      const allContent = guide?.sections.map((s) => s.content).join(' ') || '';
      expect(allContent.toLowerCase()).toMatch(/www\.|non.www|subdomain/);
    });

    it('should mention http vs https scenario', () => {
      const allContent = guide?.sections.map((s) => s.content).join(' ') || '';
      expect(allContent.toLowerCase()).toMatch(/http.*https|https.*http|protocol/);
    });

    it('should mention trailing slash scenario', () => {
      const allContent = guide?.sections.map((s) => s.content).join(' ') || '';
      expect(allContent.toLowerCase()).toMatch(/trailing.*slash|slash.*trailing|\/.*url/);
    });

    it('should mention syndication/cross-domain scenario', () => {
      const allContent = guide?.sections.map((s) => s.content).join(' ') || '';
      expect(allContent.toLowerCase()).toMatch(/syndicat|cross.*domain|republish/);
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

    it('should explain HTML link tag method', () => {
      const allContent = guide?.sections.map((s) => s.content).join(' ') || '';
      expect(allContent.toLowerCase()).toMatch(/<link|link.*rel|head.*section/);
    });

    it('should mention HTTP header method', () => {
      const allContent = guide?.sections.map((s) => s.content).join(' ') || '';
      expect(allContent.toLowerCase()).toMatch(/http.*header|link.*header|response.*header/);
    });

    it('should mention sitemap method for canonicals', () => {
      const allContent = guide?.sections.map((s) => s.content).join(' ') || '';
      expect(allContent.toLowerCase()).toMatch(/sitemap|xml.*sitemap/);
    });
  });

  describe('Content Sections - Common Mistakes', () => {
    it('should have a common mistakes section', () => {
      const section = guide?.sections.find(
        (s) => s.title.toLowerCase().includes('mistake') ||
               s.title.toLowerCase().includes('error') ||
               s.title.toLowerCase().includes('pitfall') ||
               s.title.toLowerCase().includes('avoid')
      );
      expect(section).toBeDefined();
    });

    it('should mention canonical chains issue', () => {
      const allContent = guide?.sections.map((s) => s.content).join(' ') || '';
      expect(allContent.toLowerCase()).toMatch(/chain|circular|loop/);
    });

    it('should mention self-referencing canonicals', () => {
      const allContent = guide?.sections.map((s) => s.content).join(' ') || '';
      expect(allContent.toLowerCase()).toMatch(/self.?referenc|pointing.*itself|self.*canonical/);
    });

    it('should mention cross-domain canonical mistakes', () => {
      const allContent = guide?.sections.map((s) => s.content).join(' ') || '';
      expect(allContent.toLowerCase()).toMatch(/cross.*domain|different.*domain|external.*canonical/);
    });

    it('should mention conflicting signals issue', () => {
      const allContent = guide?.sections.map((s) => s.content).join(' ') || '';
      expect(allContent.toLowerCase()).toMatch(/conflict|redirect.*canonical|mixed.*signal/);
    });
  });

  describe('Content Sections - Pagination Canonicals', () => {
    it('should have content about pagination canonicals', () => {
      const allContent = guide?.sections.map((s) => s.content).join(' ') || '';
      expect(allContent.toLowerCase()).toMatch(/paginat|page.*2|rel=.prev|rel=.next/);
    });
  });

  describe('Code Examples - HTML', () => {
    it('should include HTML code examples', () => {
      const codeExamples = guide?.sections.flatMap((s) => s.codeExamples || []) || [];
      expect(codeExamples.length).toBeGreaterThan(0);
    });

    it('should have at least one HTML example', () => {
      const codeExamples = guide?.sections.flatMap((s) => s.codeExamples || []) || [];
      const htmlExample = codeExamples.find(
        (ex) => ex.language === 'html' || ex.language === 'xml'
      );
      expect(htmlExample).toBeDefined();
    });

    it('should include rel="canonical" in code examples', () => {
      const codeExamples = guide?.sections.flatMap((s) => s.codeExamples || []) || [];
      const allCode = codeExamples.map((ex) => ex.code).join('\n');
      expect(allCode.toLowerCase()).toMatch(/rel=.canonical|rel=.canonical/);
    });

    it('should include href attribute in canonical examples', () => {
      const codeExamples = guide?.sections.flatMap((s) => s.codeExamples || []) || [];
      const allCode = codeExamples.map((ex) => ex.code).join('\n');
      expect(allCode.toLowerCase()).toMatch(/href=.*http|href=.*url/);
    });

    it('should show canonical tag in <head> section', () => {
      const codeExamples = guide?.sections.flatMap((s) => s.codeExamples || []) || [];
      const allCode = codeExamples.map((ex) => ex.code).join('\n');
      expect(allCode.toLowerCase()).toMatch(/<head|head>|link.*rel/);
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

    it('should have valid priority values only', () => {
      const validPriorities = ['critical', 'important', 'recommended'];
      guide?.checklist.forEach((item) => {
        expect(validPriorities).toContain(item.priority);
      });
    });
  });

  describe('Related Guides', () => {
    it('should include https-security as related guide', () => {
      expect(guide?.relatedGuides).toContain('https-security');
    });

    it('should include xml-sitemap as related guide', () => {
      expect(guide?.relatedGuides).toContain('xml-sitemap');
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

    it('should mention Google guidelines reference', () => {
      const allContent = guide?.sections.map((s) => s.content).join(' ') || '';
      expect(allContent.toLowerCase()).toMatch(/google.*guideline|google.*recommend|official.*documentation/);
    });
  });
});
