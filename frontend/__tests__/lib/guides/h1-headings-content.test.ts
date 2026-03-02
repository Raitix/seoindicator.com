/**
 * Tests for H1 Headings Guide Content
 * Task: TASK-20-7 - Write Content for H1 Headings SEO Guide
 * 
 * These tests validate that the H1 Headings guide content in the registry
 * meets all acceptance criteria for structure, quality, and completeness.
 */

import {
  getGuideBySlug,
  getAllGuides,
} from '@/lib/guides/registry';
import {
  isSEOGuide,
  isValidPriority,
} from '@/lib/guides/types';

// ============================================================================
// Guide Existence Tests
// ============================================================================

describe('H1 Headings Guide - Existence', () => {
  describe('HE-001: Guide exists in registry', () => {
    it('should return SEOGuide object for h1-headings slug', () => {
      const guide = getGuideBySlug('h1-headings');
      expect(guide).not.toBeNull();
    });

    it('should return valid SEOGuide type', () => {
      const guide = getGuideBySlug('h1-headings');
      expect(guide).not.toBeNull();
      if (guide) {
        expect(isSEOGuide(guide)).toBe(true);
      }
    });
  });

  describe('HE-002: Guide has correct title', () => {
    it('should have title containing "H1" and "Heading"', () => {
      const guide = getGuideBySlug('h1-headings');
      expect(guide).not.toBeNull();
      expect(guide?.title.toLowerCase()).toMatch(/h1|heading/);
    });
  });

  describe('HE-003: Guide has non-empty summary', () => {
    it('should have summary with length > 20 characters', () => {
      const guide = getGuideBySlug('h1-headings');
      expect(guide).not.toBeNull();
      expect(guide?.summary.length).toBeGreaterThan(20);
    });

    it('should have meaningful summary about H1 headings', () => {
      const guide = getGuideBySlug('h1-headings');
      expect(guide).not.toBeNull();
      expect(guide?.summary.toLowerCase()).toMatch(/h1|heading|seo|title/);
    });
  });

  describe('HE-004: Guide has correct category', () => {
    it('should have category "on-page"', () => {
      const guide = getGuideBySlug('h1-headings');
      expect(guide).not.toBeNull();
      expect(guide?.category).toBe('on-page');
    });
  });

  describe('HE-005: Guide has icon', () => {
    it('should have an icon property', () => {
      const guide = getGuideBySlug('h1-headings');
      expect(guide).not.toBeNull();
      expect(guide?.icon).toBeDefined();
      expect(guide?.icon.length).toBeGreaterThan(0);
    });
  });
});

// ============================================================================
// "What are H1 Headings?" Section Tests
// ============================================================================

describe('H1 Headings Guide - What Are H1 Headings Section', () => {
  const guide = getGuideBySlug('h1-headings');

  describe('WH-001: Has "What are H1 Headings?" section', () => {
    it('should have a section explaining what H1 headings are', () => {
      expect(guide).not.toBeNull();
      if (!guide) return;

      const hasWhatIsSection = guide.sections.some(
        (section) =>
          section.title.toLowerCase().includes('what') ||
          section.title.toLowerCase().includes('introduction') ||
          section.title.toLowerCase().includes('h1')
      );
      expect(hasWhatIsSection).toBe(true);
    });
  });

  describe('WH-002: Explains H1 definition', () => {
    it('should explain that H1 is the main heading', () => {
      expect(guide).not.toBeNull();
      if (!guide) return;

      const content = guide.sections.map((s) => s.content).join(' ').toLowerCase();
      const definesH1 =
        content.includes('main heading') ||
        content.includes('primary heading') ||
        content.includes('top-level heading') ||
        content.includes('most important heading');
      expect(definesH1).toBe(true);
    });
  });

  describe('WH-003: Mentions HTML structure', () => {
    it('should mention HTML H1 tag', () => {
      expect(guide).not.toBeNull();
      if (!guide) return;

      const content = guide.sections.map((s) => s.content).join(' ').toLowerCase();
      const mentionsHTML =
        content.includes('<h1') ||
        content.includes('h1 tag') ||
        content.includes('html') ||
        content.includes('heading tag');
      expect(mentionsHTML).toBe(true);
    });
  });

  describe('WH-004: Explains hierarchy', () => {
    it('should explain heading hierarchy concept', () => {
      expect(guide).not.toBeNull();
      if (!guide) return;

      const content = guide.sections.map((s) => s.content).join(' ').toLowerCase();
      const explainsHierarchy =
        content.includes('hierarchy') ||
        content.includes('structure') ||
        content.includes('h2') ||
        content.includes('subheading');
      expect(explainsHierarchy).toBe(true);
    });
  });

  describe('WH-005: Section has valid content length', () => {
    it('should have substantial content in the what/definition section', () => {
      expect(guide).not.toBeNull();
      if (!guide) return;

      const whatSection = guide.sections.find(
        (s) =>
          s.title.toLowerCase().includes('what') ||
          s.title.toLowerCase().includes('introduction')
      );
      expect(whatSection).toBeDefined();
      if (whatSection) {
        expect(whatSection.content.length).toBeGreaterThan(150);
      }
    });
  });
});

// ============================================================================
// Why H1 Headings Matter for SEO Section Tests
// ============================================================================

describe('H1 Headings Guide - Why H1 Headings Matter for SEO', () => {
  const guide = getGuideBySlug('h1-headings');

  describe('WHY-001: Has "Why H1 Headings Matter" section', () => {
    it('should have a section about why H1 headings matter', () => {
      expect(guide).not.toBeNull();
      if (!guide) return;

      const hasWhySection = guide.sections.some(
        (section) =>
          section.title.toLowerCase().includes('why') ||
          section.title.toLowerCase().includes('matter') ||
          section.title.toLowerCase().includes('important') ||
          section.title.toLowerCase().includes('seo benefit')
      );
      expect(hasWhySection).toBe(true);
    });
  });

  describe('WHY-002: Mentions SEO impact', () => {
    it('should mention SEO or search engine impact', () => {
      expect(guide).not.toBeNull();
      if (!guide) return;

      const content = guide.sections.map((s) => s.content).join(' ').toLowerCase();
      const mentionsSEO =
        content.includes('seo') ||
        content.includes('search engine') ||
        content.includes('ranking') ||
        content.includes('google');
      expect(mentionsSEO).toBe(true);
    });
  });

  describe('WHY-003: Mentions accessibility benefits', () => {
    it('should mention accessibility benefits', () => {
      expect(guide).not.toBeNull();
      if (!guide) return;

      const content = guide.sections.map((s) => s.content).join(' ').toLowerCase();
      const mentionsAccessibility =
        content.includes('accessibility') ||
        content.includes('screen reader') ||
        content.includes('a11y') ||
        content.includes('assistive');
      expect(mentionsAccessibility).toBe(true);
    });
  });

  describe('WHY-004: Mentions user experience', () => {
    it('should mention user experience or readability', () => {
      expect(guide).not.toBeNull();
      if (!guide) return;

      const content = guide.sections.map((s) => s.content).join(' ').toLowerCase();
      const mentionsUX =
        content.includes('user experience') ||
        content.includes('readability') ||
        content.includes('scan') ||
        content.includes('visitor');
      expect(mentionsUX).toBe(true);
    });
  });

  describe('WHY-005: Mentions keyword relevance', () => {
    it('should mention keywords or topical relevance', () => {
      expect(guide).not.toBeNull();
      if (!guide) return;

      const content = guide.sections.map((s) => s.content).join(' ').toLowerCase();
      const mentionsKeywords =
        content.includes('keyword') ||
        content.includes('topic') ||
        content.includes('relevance') ||
        content.includes('signal');
      expect(mentionsKeywords).toBe(true);
    });
  });

  describe('WHY-006: Section has valid content length', () => {
    it('should have substantial content in the why section', () => {
      expect(guide).not.toBeNull();
      if (!guide) return;

      const whySection = guide.sections.find(
        (s) =>
          s.title.toLowerCase().includes('why') ||
          s.title.toLowerCase().includes('matter')
      );
      expect(whySection).toBeDefined();
      if (whySection) {
        expect(whySection.content.length).toBeGreaterThan(200);
      }
    });
  });
});

// ============================================================================
// How to Optimize H1 Tags Section Tests
// ============================================================================

describe('H1 Headings Guide - How to Optimize H1 Tags', () => {
  const guide = getGuideBySlug('h1-headings');

  describe('HO-001: Has "How to Optimize" section', () => {
    it('should have a section about optimizing H1 tags', () => {
      expect(guide).not.toBeNull();
      if (!guide) return;

      const hasOptimizeSection = guide.sections.some(
        (section) =>
          section.title.toLowerCase().includes('optimize') ||
          section.title.toLowerCase().includes('how to') ||
          section.title.toLowerCase().includes('best practice')
      );
      expect(hasOptimizeSection).toBe(true);
    });
  });

  describe('HO-002: Mentions one H1 per page', () => {
    it('should mention the one H1 per page rule', () => {
      expect(guide).not.toBeNull();
      if (!guide) return;

      const content = guide.sections.map((s) => s.content).join(' ').toLowerCase();
      const mentionsOneH1 =
        content.includes('one h1') ||
        content.includes('single h1') ||
        content.includes('only one') ||
        content.includes('per page');
      expect(mentionsOneH1).toBe(true);
    });
  });

  describe('HO-003: Mentions keyword placement', () => {
    it('should mention including keywords in H1', () => {
      expect(guide).not.toBeNull();
      if (!guide) return;

      const content = guide.sections.map((s) => s.content).join(' ').toLowerCase();
      const mentionsKeywords =
        content.includes('keyword') ||
        content.includes('include') ||
        content.includes('target');
      expect(mentionsKeywords).toBe(true);
    });
  });

  describe('HO-004: Mentions length guidelines', () => {
    it('should mention H1 length best practices', () => {
      expect(guide).not.toBeNull();
      if (!guide) return;

      const content = guide.sections.map((s) => s.content).join(' ').toLowerCase();
      const mentionsLength =
        content.includes('length') ||
        content.includes('character') ||
        content.includes('long') ||
        content.includes('short') ||
        content.includes('word');
      expect(mentionsLength).toBe(true);
    });
  });

  describe('HO-005: Mentions matching title tag', () => {
    it('should mention relationship with title tag', () => {
      expect(guide).not.toBeNull();
      if (!guide) return;

      const content = guide.sections.map((s) => s.content).join(' ').toLowerCase();
      const mentionsTitle =
        content.includes('title tag') ||
        content.includes('meta title') ||
        content.includes('match') ||
        content.includes('consistent');
      expect(mentionsTitle).toBe(true);
    });
  });

  describe('HO-006: Section has valid content length', () => {
    it('should have substantial content in optimization section', () => {
      expect(guide).not.toBeNull();
      if (!guide) return;

      const optimizeSection = guide.sections.find(
        (s) =>
          s.title.toLowerCase().includes('optimize') ||
          s.title.toLowerCase().includes('how to')
      );
      expect(optimizeSection).toBeDefined();
      if (optimizeSection) {
        expect(optimizeSection.content.length).toBeGreaterThan(200);
      }
    });
  });
});

// ============================================================================
// Common Mistakes Section Tests
// ============================================================================

describe('H1 Headings Guide - Common Mistakes', () => {
  const guide = getGuideBySlug('h1-headings');

  describe('CM-001: Has "Common Mistakes" section', () => {
    it('should have a section about common H1 mistakes', () => {
      expect(guide).not.toBeNull();
      if (!guide) return;

      const hasMistakesSection = guide.sections.some(
        (section) =>
          section.title.toLowerCase().includes('mistake') ||
          section.title.toLowerCase().includes('error') ||
          section.title.toLowerCase().includes('avoid') ||
          section.title.toLowerCase().includes('problem')
      );
      expect(hasMistakesSection).toBe(true);
    });
  });

  describe('CM-002: Mentions multiple H1s mistake', () => {
    it('should mention the mistake of having multiple H1 tags', () => {
      expect(guide).not.toBeNull();
      if (!guide) return;

      const content = guide.sections.map((s) => s.content).join(' ').toLowerCase();
      const mentionsMultipleH1 =
        content.includes('multiple h1') ||
        content.includes('more than one h1') ||
        content.includes('two h1') ||
        content.includes('several h1');
      expect(mentionsMultipleH1).toBe(true);
    });
  });

  describe('CM-003: Mentions missing H1 mistake', () => {
    it('should mention the mistake of missing H1 tag', () => {
      expect(guide).not.toBeNull();
      if (!guide) return;

      const content = guide.sections.map((s) => s.content).join(' ').toLowerCase();
      const mentionsMissingH1 =
        content.includes('missing h1') ||
        content.includes('no h1') ||
        content.includes('without h1') ||
        content.includes('lack');
      expect(mentionsMissingH1).toBe(true);
    });
  });

  describe('CM-004: Mentions empty H1 mistake', () => {
    it('should mention the mistake of empty or generic H1', () => {
      expect(guide).not.toBeNull();
      if (!guide) return;

      const content = guide.sections.map((s) => s.content).join(' ').toLowerCase();
      const mentionsEmptyH1 =
        content.includes('empty') ||
        content.includes('generic') ||
        content.includes('untitled') ||
        content.includes('placeholder');
      expect(mentionsEmptyH1).toBe(true);
    });
  });

  describe('CM-005: Provides solutions', () => {
    it('should provide solutions or fixes for mistakes', () => {
      expect(guide).not.toBeNull();
      if (!guide) return;

      const content = guide.sections.map((s) => s.content).join(' ').toLowerCase();
      const providesSolutions =
        content.includes('fix') ||
        content.includes('solution') ||
        content.includes('correct') ||
        content.includes('instead') ||
        content.includes('resolve');
      expect(providesSolutions).toBe(true);
    });
  });

  describe('CM-006: Section has valid content length', () => {
    it('should have substantial content in mistakes section', () => {
      expect(guide).not.toBeNull();
      if (!guide) return;

      const mistakesSection = guide.sections.find(
        (s) =>
          s.title.toLowerCase().includes('mistake') ||
          s.title.toLowerCase().includes('avoid')
      );
      expect(mistakesSection).toBeDefined();
      if (mistakesSection) {
        expect(mistakesSection.content.length).toBeGreaterThan(200);
      }
    });
  });
});

// ============================================================================
// Best Practices Checklist Tests
// ============================================================================

describe('H1 Headings Guide - Checklist', () => {
  const guide = getGuideBySlug('h1-headings');

  describe('BC-001: Checklist has 5-10 items', () => {
    it('should have between 5 and 10 checklist items', () => {
      expect(guide).not.toBeNull();
      if (!guide) return;

      expect(guide.checklist.length).toBeGreaterThanOrEqual(5);
      expect(guide.checklist.length).toBeLessThanOrEqual(10);
    });
  });

  describe('BC-002: All items have valid text', () => {
    it('should have non-empty text for each checklist item', () => {
      expect(guide).not.toBeNull();
      if (!guide) return;

      guide.checklist.forEach((item) => {
        expect(item.text.length).toBeGreaterThan(10);
      });
    });
  });

  describe('BC-003: All items have valid priority', () => {
    it('should have valid priority for each checklist item', () => {
      expect(guide).not.toBeNull();
      if (!guide) return;

      guide.checklist.forEach((item) => {
        expect(isValidPriority(item.priority)).toBe(true);
      });
    });
  });

  describe('BC-004: At least one critical item', () => {
    it('should have at least one critical priority item', () => {
      expect(guide).not.toBeNull();
      if (!guide) return;

      const hasCritical = guide.checklist.some(
        (item) => item.priority === 'critical'
      );
      expect(hasCritical).toBe(true);
    });
  });

  describe('BC-005: Covers key H1 practices', () => {
    it('should cover one H1 per page in checklist', () => {
      expect(guide).not.toBeNull();
      if (!guide) return;

      const content = guide.checklist.map((i) => i.text.toLowerCase()).join(' ');
      const coversOneH1 =
        content.includes('one h1') ||
        content.includes('single h1') ||
        content.includes('per page');
      expect(coversOneH1).toBe(true);
    });

    it('should cover keywords in checklist', () => {
      expect(guide).not.toBeNull();
      if (!guide) return;

      const content = guide.checklist.map((i) => i.text.toLowerCase()).join(' ');
      const coversKeywords =
        content.includes('keyword') ||
        content.includes('relevant');
      expect(coversKeywords).toBe(true);
    });

    it('should cover structure/hierarchy in checklist', () => {
      expect(guide).not.toBeNull();
      if (!guide) return;

      const content = guide.checklist.map((i) => i.text.toLowerCase()).join(' ');
      const coversStructure =
        content.includes('hierarchy') ||
        content.includes('structure') ||
        content.includes('order') ||
        content.includes('h2');
      expect(coversStructure).toBe(true);
    });
  });
});

// ============================================================================
// Code Examples Tests
// ============================================================================

describe('H1 Headings Guide - Code Examples', () => {
  const guide = getGuideBySlug('h1-headings');

  describe('CE-001: Has code examples', () => {
    it('should have at least 1 code example', () => {
      expect(guide).not.toBeNull();
      if (!guide) return;

      const totalCodeExamples = guide.sections.reduce(
        (count, section) => count + (section.codeExamples?.length || 0),
        0
      );
      expect(totalCodeExamples).toBeGreaterThanOrEqual(1);
    });
  });

  describe('CE-002: Has at most 2 code examples', () => {
    it('should have at most 2 code examples', () => {
      expect(guide).not.toBeNull();
      if (!guide) return;

      const totalCodeExamples = guide.sections.reduce(
        (count, section) => count + (section.codeExamples?.length || 0),
        0
      );
      expect(totalCodeExamples).toBeLessThanOrEqual(2);
    });
  });

  describe('CE-003: Code examples are valid', () => {
    it('should have valid code example structure', () => {
      expect(guide).not.toBeNull();
      if (!guide) return;

      guide.sections.forEach((section) => {
        section.codeExamples?.forEach((example) => {
          expect(example.language).toBeDefined();
          expect(example.code).toBeDefined();
          expect(example.code.length).toBeGreaterThan(0);
        });
      });
    });
  });

  describe('CE-004: Code examples show H1 usage', () => {
    it('should demonstrate H1 tag usage in code examples', () => {
      expect(guide).not.toBeNull();
      if (!guide) return;

      const allCode = guide.sections
        .flatMap((s) => s.codeExamples?.map((e) => e.code.toLowerCase()) || [])
        .join(' ');

      const showsH1 =
        allCode.includes('<h1') ||
        allCode.includes('&lt;h1') ||
        allCode.includes('h1>');
      expect(showsH1).toBe(true);
    });
  });
});

// ============================================================================
// Read Time Tests
// ============================================================================

describe('H1 Headings Guide - Read Time', () => {
  const guide = getGuideBySlug('h1-headings');

  describe('RT-001: Read time is set', () => {
    it('should have a readTime property that is a number', () => {
      expect(guide).not.toBeNull();
      if (!guide) return;

      expect(typeof guide.readTime).toBe('number');
    });
  });

  describe('RT-002: Read time in valid range', () => {
    it('should have readTime between 3 and 5 minutes', () => {
      expect(guide).not.toBeNull();
      if (!guide) return;

      expect(guide.readTime).toBeGreaterThanOrEqual(3);
      expect(guide.readTime).toBeLessThanOrEqual(5);
    });
  });

  describe('RT-003: Read time matches content', () => {
    it('should have readTime that roughly matches content length', () => {
      expect(guide).not.toBeNull();
      if (!guide) return;

      // Calculate approximate word count
      const allContent = guide.sections
        .map((s) => s.title + ' ' + s.content)
        .join(' ');
      const wordCount = allContent.split(/\s+/).length;

      // Average reading speed: 200-250 words per minute
      const expectedReadTime = Math.ceil(wordCount / 225);

      // Allow for ±1 minute variance
      expect(Math.abs(guide.readTime - expectedReadTime)).toBeLessThanOrEqual(1);
    });
  });
});

// ============================================================================
// Related Guides Tests
// ============================================================================

describe('H1 Headings Guide - Related Guides', () => {
  const guide = getGuideBySlug('h1-headings');

  describe('RG-001: Has related guides array', () => {
    it('should have relatedGuides array', () => {
      expect(guide).not.toBeNull();
      if (!guide) return;

      expect(Array.isArray(guide.relatedGuides)).toBe(true);
    });
  });

  describe('RG-002: Contains heading-hierarchy', () => {
    it('should include heading-hierarchy in related guides', () => {
      expect(guide).not.toBeNull();
      if (!guide) return;

      expect(guide.relatedGuides).toContain('heading-hierarchy');
    });
  });

  describe('RG-003: Contains meta-title', () => {
    it('should include meta-title in related guides', () => {
      expect(guide).not.toBeNull();
      if (!guide) return;

      expect(guide.relatedGuides).toContain('meta-title');
    });
  });

  describe('RG-004: Related guides exist', () => {
    it('should have all related guides exist in registry', () => {
      expect(guide).not.toBeNull();
      if (!guide) return;

      guide.relatedGuides.forEach((slug) => {
        const relatedGuide = getGuideBySlug(slug);
        expect(relatedGuide).not.toBeNull();
      });
    });
  });
});

// ============================================================================
// Guide Registry Integration Tests
// ============================================================================

describe('H1 Headings Guide - Registry Integration', () => {
  describe('GR-001: Guide in registry', () => {
    it('should be included in getAllGuides()', () => {
      const allGuides = getAllGuides();
      const h1Guide = allGuides.find(
        (g) => g.slug === 'h1-headings'
      );
      expect(h1Guide).toBeDefined();
    });
  });

  describe('GR-002: getGuideBySlug returns complete guide', () => {
    it('should return guide with all required properties', () => {
      const guide = getGuideBySlug('h1-headings');
      expect(guide).not.toBeNull();
      if (!guide) return;

      expect(guide.slug).toBeDefined();
      expect(guide.title).toBeDefined();
      expect(guide.summary).toBeDefined();
      expect(guide.category).toBeDefined();
      expect(guide.icon).toBeDefined();
      expect(guide.readTime).toBeDefined();
      expect(guide.sections).toBeDefined();
      expect(guide.checklist).toBeDefined();
      expect(guide.relatedGuides).toBeDefined();
    });
  });

  describe('GR-003: Guide validates with type guard', () => {
    it('should pass isSEOGuide validation', () => {
      const guide = getGuideBySlug('h1-headings');
      expect(guide).not.toBeNull();
      if (guide) {
        expect(isSEOGuide(guide)).toBe(true);
      }
    });
  });
});

// ============================================================================
// Content Quality Tests
// ============================================================================

describe('H1 Headings Guide - Content Quality', () => {
  const guide = getGuideBySlug('h1-headings');

  describe('Content completeness', () => {
    it('should have at least 4 sections', () => {
      expect(guide).not.toBeNull();
      if (!guide) return;

      expect(guide.sections.length).toBeGreaterThanOrEqual(4);
    });

    it('should have "What is/are H1" section', () => {
      expect(guide).not.toBeNull();
      if (!guide) return;

      const hasWhatIsSection = guide.sections.some(
        (s) =>
          s.title.toLowerCase().includes('what') ||
          s.title.toLowerCase().includes('introduction')
      );
      expect(hasWhatIsSection).toBe(true);
    });
  });

  describe('Section content quality', () => {
    it('should have substantial content in each section', () => {
      expect(guide).not.toBeNull();
      if (!guide) return;

      guide.sections.forEach((section) => {
        expect(section.content.length).toBeGreaterThan(100);
      });
    });

    it('should have unique section titles', () => {
      expect(guide).not.toBeNull();
      if (!guide) return;

      const titles = guide.sections.map((s) => s.title);
      const uniqueTitles = new Set(titles);
      expect(uniqueTitles.size).toBe(titles.length);
    });
  });

  describe('Content formatting', () => {
    it('should use markdown formatting in content', () => {
      expect(guide).not.toBeNull();
      if (!guide) return;

      const hasMarkdown = guide.sections.some(
        (s) =>
          s.content.includes('**') ||
          s.content.includes('- ') ||
          s.content.includes('1.') ||
          s.content.includes('`')
      );
      expect(hasMarkdown).toBe(true);
    });
  });
});

// ============================================================================
// Security Tests
// ============================================================================

describe('H1 Headings Guide - Security', () => {
  const guide = getGuideBySlug('h1-headings');

  describe('XSS prevention', () => {
    it('should not contain script tags in content', () => {
      expect(guide).not.toBeNull();
      if (!guide) return;

      guide.sections.forEach((section) => {
        expect(
          section.content.toLowerCase().includes('<script')
        ).toBe(false);
      });
    });

    it('should not contain event handlers in content', () => {
      expect(guide).not.toBeNull();
      if (!guide) return;

      guide.sections.forEach((section) => {
        expect(
          section.content.toLowerCase().includes('onclick')
        ).toBe(false);
        expect(
          section.content.toLowerCase().includes('onerror')
        ).toBe(false);
      });
    });
  });

  describe('Code example safety', () => {
    it('should not have executable code outside code blocks', () => {
      expect(guide).not.toBeNull();
      if (!guide) return;

      // Code examples should be in the codeExamples array, not inline scripts
      guide.sections.forEach((section) => {
        const content = section.content;
        // Content can have code blocks (```), but not raw scripts
        expect(content.toLowerCase()).not.toContain('<script>');
      });
    });
  });
});
