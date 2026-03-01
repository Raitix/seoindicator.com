/**
 * Tests for Robots.txt SEO Guide Content
 * Task: TASK-20-20 - Write Content for Robots.txt Guide
 * 
 * These tests verify the content structure, coverage, and quality
 * of the Robots.txt SEO guide.
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

describe('Robots.txt Guide Content', () => {
  let guide: SEOGuide | null;

  beforeAll(() => {
    guide = getGuideBySlug('robots-txt');
  });

  describe('Guide Structure', () => {
    it('should exist in the registry', () => {
      expect(guide).not.toBeNull();
    });

    it('should have correct slug', () => {
      expect(guide?.slug).toBe('robots-txt');
    });

    it('should have a title containing "Robots"', () => {
      expect(guide?.title.toLowerCase()).toContain('robot');
    });

    it('should be in technical category', () => {
      expect(guide?.category).toBe('technical');
    });

    it('should have an icon', () => {
      expect(guide?.icon).toBe('🤖');
    });

    it('should have read time between 4-6 minutes', () => {
      expect(guide?.readTime).toBeGreaterThanOrEqual(4);
      expect(guide?.readTime).toBeLessThanOrEqual(6);
    });
  });

  describe('Content Sections - What is Robots.txt', () => {
    it('should have a section explaining what robots.txt is', () => {
      const section = guide?.sections.find(
        (s) => s.title.toLowerCase().includes('what is') ||
               s.title.toLowerCase().includes('introduction') ||
               s.title.toLowerCase().includes('overview')
      );
      expect(section).toBeDefined();
    });

    it('should define robots.txt as a file', () => {
      const allContent = guide?.sections.map((s) => s.content.toLowerCase()).join(' ') || '';
      expect(allContent).toMatch(/robots\.txt.*file|file.*robots\.txt|text.*file/);
    });

    it('should explain the purpose of robots.txt', () => {
      const allContent = guide?.sections.map((s) => s.content.toLowerCase()).join(' ') || '';
      expect(allContent).toMatch(/crawler|bot|spider|search.*engine/);
    });

    it('should mention the location at root', () => {
      const allContent = guide?.sections.map((s) => s.content.toLowerCase()).join(' ') || '';
      expect(allContent).toMatch(/root|domain.*root|\/robots\.txt/);
    });
  });

  describe('Content Sections - Why Robots.txt Matters for SEO', () => {
    it('should have a section about SEO importance', () => {
      const section = guide?.sections.find(
        (s) => s.title.toLowerCase().includes('why') ||
               s.title.toLowerCase().includes('seo') ||
               s.title.toLowerCase().includes('importance') ||
               s.title.toLowerCase().includes('matter')
      );
      expect(section).toBeDefined();
    });

    it('should mention crawl budget', () => {
      const allContent = guide?.sections.map((s) => s.content.toLowerCase()).join(' ') || '';
      expect(allContent).toMatch(/crawl.*budget|budget.*crawl/);
    });

    it('should mention indexation control', () => {
      const allContent = guide?.sections.map((s) => s.content.toLowerCase()).join(' ') || '';
      expect(allContent).toMatch(/index|indexation|indexed/);
    });

    it('should explain crawler instructions', () => {
      const allContent = guide?.sections.map((s) => s.content.toLowerCase()).join(' ') || '';
      expect(allContent).toMatch(/instruction|directiv|control|allow|disallow/);
    });
  });

  describe('Content Sections - Allow/Disallow Directives', () => {
    it('should have a section about directives', () => {
      const section = guide?.sections.find(
        (s) => s.title.toLowerCase().includes('directiv') ||
               s.title.toLowerCase().includes('allow') ||
               s.title.toLowerCase().includes('disallow') ||
               s.title.toLowerCase().includes('syntax')
      );
      expect(section).toBeDefined();
    });

    it('should explain User-agent directive', () => {
      const allContent = guide?.sections.map((s) => s.content.toLowerCase()).join(' ') || '';
      expect(allContent).toMatch(/user.?agent|user.?agent/i);
    });

    it('should explain Allow directive', () => {
      const allContent = guide?.sections.map((s) => s.content.toLowerCase()).join(' ') || '';
      expect(allContent.toLowerCase()).toMatch(/allow.*directive|allow:|allow\s*:/);
    });

    it('should explain Disallow directive', () => {
      const allContent = guide?.sections.map((s) => s.content.toLowerCase()).join(' ') || '';
      expect(allContent.toLowerCase()).toMatch(/disallow.*directive|disallow:|disallow\s*:/);
    });

    it('should provide syntax examples', () => {
      const allContent = guide?.sections.map((s) => s.content).join(' ') || '';
      expect(allContent).toMatch(/User-agent:|Disallow:|Allow:/i);
    });
  });

  describe('Content Sections - Sitemap Reference', () => {
    it('should mention sitemap directive', () => {
      const allContent = guide?.sections.map((s) => s.content.toLowerCase()).join(' ') || '';
      expect(allContent).toMatch(/sitemap|sitemap:/);
    });

    it('should explain how to add sitemap to robots.txt', () => {
      const allContent = guide?.sections.map((s) => s.content).join(' ') || '';
      expect(allContent).toMatch(/Sitemap:\s*https?:\/\//i);
    });

    it('should explain the benefit of sitemap in robots.txt', () => {
      const allContent = guide?.sections.map((s) => s.content.toLowerCase()).join(' ') || '';
      expect(allContent).toMatch(/discover|find|locate|crawl/);
    });
  });

  describe('Content Sections - Wildcards and Patterns', () => {
    it('should mention wildcard patterns (*)', () => {
      const allContent = guide?.sections.map((s) => s.content).join(' ') || '';
      expect(allContent).toMatch(/\*|wildcard|asterisk/);
    });

    it('should mention end-of-path pattern ($)', () => {
      const allContent = guide?.sections.map((s) => s.content).join(' ') || '';
      expect(allContent).toMatch(/\$|end.*path|dollar/);
    });

    it('should provide pattern examples', () => {
      const allContent = guide?.sections.map((s) => s.content).join(' ') || '';
      // Check for common wildcard patterns like /* or /page*
      expect(allContent).toMatch(/\/\*|\/\S+\*|\*\/|\*\//);
    });
  });

  describe('Content Sections - Common Mistakes', () => {
    it('should have a common mistakes section', () => {
      const section = guide?.sections.find(
        (s) => s.title.toLowerCase().includes('mistake') ||
               s.title.toLowerCase().includes('error') ||
               s.title.toLowerCase().includes('avoid') ||
               s.title.toLowerCase().includes('pitfall')
      );
      expect(section).toBeDefined();
    });

    it('should warn about blocking CSS/JS', () => {
      const allContent = guide?.sections.map((s) => s.content.toLowerCase()).join(' ') || '';
      expect(allContent).toMatch(/css|javascript|js|stylesheet|script/);
    });

    it('should warn about blocking important pages', () => {
      const allContent = guide?.sections.map((s) => s.content.toLowerCase()).join(' ') || '';
      expect(allContent).toMatch(/block.*important|important.*block|accidental.*block/);
    });

    it('should mention wrong path issues', () => {
      const allContent = guide?.sections.map((s) => s.content.toLowerCase()).join(' ') || '';
      expect(allContent).toMatch(/wrong.*path|path.*error|incorrect.*path|case.*sensitiv/);
    });

    it('should mention the risk of disallow: /', () => {
      const allContent = guide?.sections.map((s) => s.content).join(' ') || '';
      expect(allContent).toMatch(/Disallow:\s*\/|block.*entire.*site|block.*whole.*site/i);
    });
  });

  describe('Content Sections - User-Agent Targeting', () => {
    it('should explain targeting specific bots', () => {
      const allContent = guide?.sections.map((s) => s.content.toLowerCase()).join(' ') || '';
      expect(allContent).toMatch(/googlebot|bingbot|specific.*bot|target.*bot/);
    });

    it('should explain wildcard user-agent (*)', () => {
      const allContent = guide?.sections.map((s) => s.content).join(' ') || '';
      expect(allContent).toMatch(/User-agent:\s*\*|all.*bot|every.*bot/i);
    });
  });

  describe('Code Examples', () => {
    it('should have at least one code example', () => {
      const codeExamples = guide?.sections.flatMap((s) => s.codeExamples || []) || [];
      expect(codeExamples.length).toBeGreaterThan(0);
    });

    it('should include robots.txt syntax examples', () => {
      const codeExamples = guide?.sections.flatMap((s) => s.codeExamples || []) || [];
      const allCode = codeExamples.map((ex) => ex.code).join('\n');
      expect(allCode).toMatch(/User-agent:/i);
    });

    it('should include Disallow examples', () => {
      const codeExamples = guide?.sections.flatMap((s) => s.codeExamples || []) || [];
      const allCode = codeExamples.map((ex) => ex.code).join('\n');
      expect(allCode).toMatch(/Disallow:/i);
    });

    it('should include Allow examples', () => {
      const codeExamples = guide?.sections.flatMap((s) => s.codeExamples || []) || [];
      const allCode = codeExamples.map((ex) => ex.code).join('\n');
      expect(allCode).toMatch(/Allow:/i);
    });

    it('should include Sitemap directive example', () => {
      const codeExamples = guide?.sections.flatMap((s) => s.codeExamples || []) || [];
      const allCode = codeExamples.map((ex) => ex.code).join('\n');
      expect(allCode).toMatch(/Sitemap:/i);
    });

    it('should show wildcard pattern examples', () => {
      const codeExamples = guide?.sections.flatMap((s) => s.codeExamples || []) || [];
      const allCode = codeExamples.map((ex) => ex.code).join('\n');
      expect(allCode).toMatch(/\*/);
    });

    it('should have properly formatted code examples with language', () => {
      guide?.sections.forEach((section) => {
        section.codeExamples?.forEach((example) => {
          expect(example.language).toBeTruthy();
          expect(example.code.length).toBeGreaterThan(0);
        });
      });
    });

    it('should have code examples with descriptions', () => {
      const codeExamples = guide?.sections.flatMap((s) => s.codeExamples || []) || [];
      codeExamples.forEach((example) => {
        if (example.description) {
          expect(example.description.length).toBeGreaterThan(0);
        }
      });
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

    it('should include item about testing robots.txt', () => {
      const allChecklist = guide?.checklist.map((i) => i.text.toLowerCase()).join(' ') || '';
      expect(allChecklist).toMatch(/test|validat|check|verify/);
    });

    it('should include item about CSS/JS access', () => {
      const allChecklist = guide?.checklist.map((i) => i.text.toLowerCase()).join(' ') || '';
      expect(allChecklist).toMatch(/css|javascript|js|stylesheet/);
    });
  });

  describe('Related Guides', () => {
    it('should include xml-sitemap as related guide', () => {
      expect(guide?.relatedGuides).toContain('xml-sitemap');
    });

    it('should have 2-4 related guides', () => {
      expect(guide?.relatedGuides.length).toBeGreaterThanOrEqual(2);
      expect(guide?.relatedGuides.length).toBeLessThanOrEqual(4);
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

    it('should mention Google robots.txt specifications', () => {
      const allContent = guide?.sections.map((s) => s.content).join(' ') || '';
      expect(allContent.toLowerCase()).toMatch(/google.*specification|google.*guideline|official.*documentation|developers\.google/);
    });

    it('should mention robots.txt testing tools', () => {
      const allContent = guide?.sections.map((s) => s.content.toLowerCase()).join(' ') || '';
      expect(allContent).toMatch(/test.*tool|validat|google search console|testing tool/);
    });
  });

  describe('Security Considerations', () => {
    it('should warn that robots.txt is not a security measure', () => {
      const allContent = guide?.sections.map((s) => s.content.toLowerCase()).join(' ') || '';
      expect(allContent).toMatch(/not.*secur|security.*measure|not.*protect|does.*not.*block.*bad/);
    });

    it('should mention that disallowed pages can still be indexed', () => {
      const allContent = guide?.sections.map((s) => s.content.toLowerCase()).join(' ') || '';
      expect(allContent).toMatch(/still.*index|can.*index|might.*index|noindex/);
    });

    it('should mention noindex meta tag alternative', () => {
      const allContent = guide?.sections.map((s) => s.content.toLowerCase()).join(' ') || '';
      expect(allContent).toMatch(/noindex|meta.*robot|x-robots-tag/);
    });
  });

  describe('Technical Accuracy', () => {
    it('should explain that robots.txt is case-sensitive', () => {
      const allContent = guide?.sections.map((s) => s.content.toLowerCase()).join(' ') || '';
      expect(allContent).toMatch(/case.?sensitiv|sensitiv.*case/);
    });

    it('should explain the file must be at root', () => {
      const allContent = guide?.sections.map((s) => s.content.toLowerCase()).join(' ') || '';
      expect(allContent).toMatch(/root|domain.*root|top.?level|\/robots\.txt/);
    });

    it('should mention the file size limit', () => {
      const allContent = guide?.sections.map((s) => s.content.toLowerCase()).join(' ') || '';
      expect(allContent).toMatch(/size.*limit|limit.*size|500.*kb|file.*size/);
    });

    it('should mention crawl-delay directive (even if not standard)', () => {
      const allContent = guide?.sections.map((s) => s.content.toLowerCase()).join(' ') || '';
      expect(allContent).toMatch(/crawl.?delay|delay|crawldelay/);
    });
  });
});
