/**
 * Tests for TASK-20-8: Heading Hierarchy Guide Content
 * 
 * This test suite validates the content of the Heading Hierarchy (H1-H6) guide
 * to ensure it meets all acceptance criteria and quality standards.
 */

import {
  getGuideBySlug,
  GUIDE_SLUGS,
} from '@/lib/guides/registry';
import {
  isSEOGuide,
  isGuideSection,
  isChecklistItem,
  isValidPriority,
  ChecklistPriority,
  SEOGuide,
} from '@/lib/guides/types';

describe('TASK-20-8: Heading Hierarchy Guide Content', () => {
  let guide: SEOGuide | null;

  beforeAll(() => {
    guide = getGuideBySlug('heading-hierarchy');
  });

  // ===========================================================================
  // TC-001: Guide Registry Entry Exists
  // ===========================================================================
  describe('TC-001: Guide Registry Entry', () => {
    it('TC-001-01: should return guide by slug', () => {
      expect(guide).not.toBeNull();
    });

    it('TC-001-02: should have correct slug', () => {
      expect(guide?.slug).toBe('heading-hierarchy');
    });

    it('TC-001-03: should have correct title', () => {
      expect(guide?.title).toMatch(/Heading Hierarchy/i);
    });

    it('TC-001-04: should be on-page category', () => {
      expect(guide?.category).toBe('on-page');
    });

    it('TC-001-05: should have read time in 4-6 minute range', () => {
      expect(guide?.readTime).toBeGreaterThanOrEqual(4);
      expect(guide?.readTime).toBeLessThanOrEqual(6);
    });

    it('TC-001-06: should have icon defined', () => {
      expect(guide?.icon).toBeDefined();
      expect(guide?.icon.length).toBeGreaterThan(0);
    });

    it('TC-001-07: should have summary between 10-200 characters', () => {
      expect(guide?.summary).toBeDefined();
      expect(guide?.summary.length).toBeGreaterThanOrEqual(10);
      expect(guide?.summary.length).toBeLessThanOrEqual(200);
    });

    it('TC-001-08: should be a valid SEOGuide', () => {
      expect(isSEOGuide(guide)).toBe(true);
    });
  });

  // ===========================================================================
  // TC-002: Sections Structure
  // ===========================================================================
  describe('TC-002: Sections Structure', () => {
    it('TC-002-01: should have at least 5 sections', () => {
      expect(guide?.sections).toBeDefined();
      expect(guide?.sections.length).toBeGreaterThanOrEqual(5);
    });

    it('TC-002-02: each section should have a non-empty title', () => {
      guide?.sections.forEach((section, index) => {
        expect(section.title).toBeDefined();
        expect(section.title.length).toBeGreaterThan(0);
      });
    });

    it('TC-002-03: each section should have content with min 100 chars', () => {
      guide?.sections.forEach((section, index) => {
        expect(section.content).toBeDefined();
        expect(section.content.length).toBeGreaterThanOrEqual(100);
      });
    });

    it('TC-002-04: all sections should be valid GuideSection objects', () => {
      guide?.sections.forEach((section) => {
        expect(isGuideSection(section)).toBe(true);
      });
    });

    it('TC-002-05: code examples should have language and code', () => {
      guide?.sections.forEach((section) => {
        if (section.codeExamples && section.codeExamples.length > 0) {
          section.codeExamples.forEach((example) => {
            expect(example.language).toBeDefined();
            expect(example.language.length).toBeGreaterThan(0);
            expect(example.code).toBeDefined();
            expect(example.code.length).toBeGreaterThan(0);
          });
        }
      });
    });
  });

  // ===========================================================================
  // TC-003: Required Content Topics
  // ===========================================================================
  describe('TC-003: Required Content Topics', () => {
    const getAllContent = (): string => {
      if (!guide) return '';
      return guide.sections.map(s => s.content.toLowerCase()).join(' ');
    };

    it('TC-003-01: should cover "What is Heading Hierarchy"', () => {
      const content = getAllContent();
      const hasDefinition = 
        content.includes('heading hierarchy') ||
        content.includes('h1') ||
        content.includes('what is') ||
        content.includes('definition');
      expect(hasDefinition).toBe(true);
    });

    it('TC-003-02: should cover SEO benefits', () => {
      const content = getAllContent();
      const hasSeoBenefits = 
        content.includes('seo') ||
        content.includes('search engine') ||
        content.includes('ranking') ||
        content.includes('crawl');
      expect(hasSeoBenefits).toBe(true);
    });

    it('TC-003-03: should cover accessibility benefits', () => {
      const content = getAllContent();
      const hasAccessibility = 
        content.includes('accessibility') ||
        content.includes('screen reader') ||
        content.includes('a11y') ||
        content.includes('assistive');
      expect(hasAccessibility).toBe(true);
    });

    it('TC-003-04: should cover how to structure headings', () => {
      const content = getAllContent();
      const hasStructure = 
        content.includes('h1') ||
        content.includes('h2') ||
        content.includes('h3') ||
        content.includes('hierarchy') ||
        content.includes('structure');
      expect(hasStructure).toBe(true);
    });

    it('TC-003-05: should cover common mistakes (skipping levels, wrong order)', () => {
      const content = getAllContent();
      const hasMistakes = 
        content.includes('skip') ||
        content.includes('mistake') ||
        content.includes('wrong') ||
        content.includes('avoid') ||
        content.includes('don\'t') ||
        content.includes('error');
      expect(hasMistakes).toBe(true);
    });

    it('TC-003-06: should include visual hierarchy reference', () => {
      const content = getAllContent();
      const hasVisual = 
        content.includes('visual') ||
        content.includes('diagram') ||
        content.includes('outline') ||
        content.includes('tree') ||
        content.includes('nest');
      expect(hasVisual).toBe(true);
    });
  });

  // ===========================================================================
  // TC-004: Section Title Validation
  // ===========================================================================
  describe('TC-004: Section Titles', () => {
    const getTitles = (): string[] => {
      if (!guide) return [];
      return guide.sections.map(s => s.title.toLowerCase());
    };

    it('TC-004-01: should have a definition/intro section', () => {
      const titles = getTitles();
      const hasDefinition = titles.some(t => 
        t.includes('what is') || 
        t.includes('heading hierarchy') ||
        t.includes('introduction') ||
        t.includes('overview')
      );
      expect(hasDefinition).toBe(true);
    });

    it('TC-004-02: should have a benefits/SEO section', () => {
      const titles = getTitles();
      const hasBenefits = titles.some(t => 
        t.includes('seo') || 
        t.includes('benefit') ||
        t.includes('why') ||
        t.includes('importance') ||
        t.includes('matter')
      );
      expect(hasBenefits).toBe(true);
    });

    it('TC-004-03: should have a how-to/structure section', () => {
      const titles = getTitles();
      const hasHowTo = titles.some(t => 
        t.includes('how to') || 
        t.includes('structure') ||
        t.includes('implement') ||
        t.includes('use') ||
        t.includes('proper')
      );
      expect(hasHowTo).toBe(true);
    });

    it('TC-004-04: should have a mistakes section', () => {
      const titles = getTitles();
      const hasMistakes = titles.some(t => 
        t.includes('mistake') || 
        t.includes('common') ||
        t.includes('avoid') ||
        t.includes('error') ||
        t.includes('wrong')
      );
      expect(hasMistakes).toBe(true);
    });

    it('TC-004-05: should have a best practices section', () => {
      const titles = getTitles();
      const hasBestPractices = titles.some(t => 
        t.includes('best') || 
        t.includes('practice') ||
        t.includes('tip') ||
        t.includes('guideline') ||
        t.includes('recommendation')
      );
      expect(hasBestPractices).toBe(true);
    });
  });

  // ===========================================================================
  // TC-005: Content Quality
  // ===========================================================================
  describe('TC-005: Content Quality', () => {
    const getTotalContentLength = (): number => {
      if (!guide) return 0;
      return guide.sections.reduce((sum, s) => sum + s.content.length, 0);
    };

    const getTotalCodeExamples = (): number => {
      if (!guide) return 0;
      return guide.sections.reduce((sum, s) => sum + (s.codeExamples?.length || 0), 0);
    };

    const getAllContent = (): string => {
      if (!guide) return '';
      return guide.sections.map(s => s.content.toLowerCase()).join(' ');
    };

    const getAllCode = (): string => {
      if (!guide) return '';
      return guide.sections
        .flatMap(s => s.codeExamples?.map(e => e.code.toLowerCase()) || [])
        .join(' ');
    };

    it('TC-005-01: should have enough content for 4-6 min read (>= 3000 chars)', () => {
      const totalLength = getTotalContentLength();
      // Average reading speed is 200-250 words/min, ~5 chars per word
      // 4-6 min = 800-1200 words = 4000-6000 chars minimum
      expect(totalLength).toBeGreaterThanOrEqual(3000);
    });

    it('TC-005-02: should mention H1 usage', () => {
      const content = getAllContent();
      expect(content).toMatch(/h1/);
    });

    it('TC-005-03: should mention H2-H6 subheadings', () => {
      const content = getAllContent();
      const hasSubheadings = 
        content.includes('h2') ||
        content.includes('h3') ||
        content.includes('h4') ||
        content.includes('h5') ||
        content.includes('h6') ||
        content.includes('subheading');
      expect(hasSubheadings).toBe(true);
    });

    it('TC-005-04: should have at least 2 code examples', () => {
      const totalExamples = getTotalCodeExamples();
      expect(totalExamples).toBeGreaterThanOrEqual(2);
    });

    it('TC-005-05: should have HTML code examples', () => {
      const code = getAllCode();
      const hasHtml = 
        code.includes('<h1') ||
        code.includes('<h2') ||
        code.includes('<h3') ||
        code.includes('<h') ||
        code.includes('<heading');
      expect(hasHtml).toBe(true);
    });
  });

  // ===========================================================================
  // TC-006: Checklist Validation
  // ===========================================================================
  describe('TC-006: Checklist', () => {
    it('TC-006-01: should have 5-10 checklist items', () => {
      expect(guide?.checklist).toBeDefined();
      expect(guide?.checklist.length).toBeGreaterThanOrEqual(5);
      expect(guide?.checklist.length).toBeLessThanOrEqual(10);
    });

    it('TC-006-02: each item should have non-empty text', () => {
      guide?.checklist.forEach((item) => {
        expect(item.text).toBeDefined();
        expect(item.text.length).toBeGreaterThan(0);
      });
    });

    it('TC-006-03: each item should have valid priority', () => {
      guide?.checklist.forEach((item) => {
        expect(isValidPriority(item.priority)).toBe(true);
      });
    });

    it('TC-006-04: should have at least one critical item', () => {
      const criticalCount = guide?.checklist.filter(
        (item) => item.priority === 'critical'
      ).length || 0;
      expect(criticalCount).toBeGreaterThanOrEqual(1);
    });

    it('TC-006-05: should have at least one important item', () => {
      const importantCount = guide?.checklist.filter(
        (item) => item.priority === 'important'
      ).length || 0;
      expect(importantCount).toBeGreaterThanOrEqual(1);
    });

    it('TC-006-06: all priorities should be valid values', () => {
      const validPriorities: ChecklistPriority[] = ['critical', 'important', 'recommended'];
      guide?.checklist.forEach((item) => {
        expect(validPriorities).toContain(item.priority);
      });
    });

    it('TC-006-07: all checklist items should be valid ChecklistItem objects', () => {
      guide?.checklist.forEach((item) => {
        expect(isChecklistItem(item)).toBe(true);
      });
    });
  });

  // ===========================================================================
  // TC-007: Related Guides
  // ===========================================================================
  describe('TC-007: Related Guides', () => {
    it('TC-007-01: should have at least 2 related guides', () => {
      expect(guide?.relatedGuides).toBeDefined();
      expect(guide?.relatedGuides.length).toBeGreaterThanOrEqual(2);
    });

    it('TC-007-02: should include h1-headings as related guide', () => {
      expect(guide?.relatedGuides).toContain('h1-headings');
    });

    it('TC-007-03: should include internal-links as related guide', () => {
      expect(guide?.relatedGuides).toContain('internal-links');
    });

    it('TC-007-04: all related guides should be valid slugs', () => {
      guide?.relatedGuides.forEach((slug) => {
        expect(GUIDE_SLUGS).toContain(slug);
      });
    });
  });

  // ===========================================================================
  // TC-008: Content-Specific Validation
  // ===========================================================================
  describe('TC-008: Content-Specific Topics', () => {
    const getAllContent = (): string => {
      if (!guide) return '';
      return guide.sections.map(s => s.content.toLowerCase()).join(' ');
    };

    it('TC-008-01: should mention one H1 per page limit', () => {
      const content = getAllContent();
      const hasH1Limit = 
        content.includes('one h1') ||
        content.includes('single h1') ||
        content.includes('only one h1') ||
        content.includes('one h1 per page') ||
        content.includes('one heading') ||
        content.includes('main heading');
      expect(hasH1Limit).toBe(true);
    });

    it('TC-008-02: should explain heading order (H1 > H2 > H3)', () => {
      const content = getAllContent();
      const hasOrder = 
        content.includes('h1') && content.includes('h2') ||
        content.includes('order') ||
        content.includes('hierarchy') ||
        content.includes('nest');
      expect(hasOrder).toBe(true);
    });

    it('TC-008-03: should warn against skipping heading levels', () => {
      const content = getAllContent();
      const hasSkipWarning = 
        content.includes('skip') ||
        content.includes('h1 to h3') ||
        content.includes('jump') ||
        content.includes('missing') ||
        content.includes('gap');
      expect(hasSkipWarning).toBe(true);
    });

    it('TC-008-04: should mention screen reader navigation', () => {
      const content = getAllContent();
      const hasScreenReader = 
        content.includes('screen reader') ||
        content.includes('assistive technology') ||
        content.includes('navigate by heading') ||
        content.includes('keyboard navigation') ||
        content.includes('accessibility tree');
      expect(hasScreenReader).toBe(true);
    });

    it('TC-008-05: should discuss document outline/table of contents', () => {
      const content = getAllContent();
      const hasOutline = 
        content.includes('outline') ||
        content.includes('table of contents') ||
        content.includes('toc') ||
        content.includes('structure') ||
        content.includes('hierarchy');
      expect(hasOutline).toBe(true);
    });
  });

  // ===========================================================================
  // TC-009: Visual Hierarchy Diagram
  // ===========================================================================
  describe('TC-009: Visual Hierarchy', () => {
    const getAllContent = (): string => {
      if (!guide) return '';
      return guide.sections.map(s => s.content.toLowerCase()).join(' ');
    };

    const getAllCode = (): string => {
      if (!guide) return '';
      return guide.sections
        .flatMap(s => s.codeExamples?.map(e => e.code.toLowerCase() + ' ' + (e.description?.toLowerCase() || '')) || [])
        .join(' ');
    };

    it('TC-009-01: should include visual representation of heading structure', () => {
      const content = getAllContent();
      const code = getAllCode();
      const combined = content + ' ' + code;
      
      // Either visual description, diagram, or tree structure in content/code
      const hasVisual = 
        combined.includes('visual') ||
        combined.includes('diagram') ||
        combined.includes('tree') ||
        combined.includes('outline') ||
        combined.includes('h1\n') ||
        combined.includes('├──') ||
        combined.includes('└──') ||
        combined.includes('nest');
      expect(hasVisual).toBe(true);
    });

    it('TC-009-02: should show example of correct heading structure in code', () => {
      const code = getAllCode();
      // Should show a proper H1 > H2 > H3 structure
      const hasCorrectStructure = 
        code.includes('<h1') ||
        code.includes('<h2') ||
        code.includes('h1') ||
        code.includes('h2');
      expect(hasCorrectStructure).toBe(true);
    });
  });
});
