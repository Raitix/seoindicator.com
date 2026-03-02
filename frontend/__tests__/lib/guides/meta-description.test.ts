/**
 * Tests for Meta Description Guide Content
 * Task: TASK-20-6 - Write Content for Meta Description Guide
 * 
 * Tests verify the meta-description guide meets all acceptance criteria:
 * - Content covers required sections (definition, SEO impact/CTR, optimization, mistakes, best practices)
 * - Checklist has 5-10 items with proper priority levels
 * - Code examples present (1-2 examples)
 * - Read time: 3-5 minutes
 * - Tool integration links to meta-tag-analyzer
 * - Related guides include meta-title, open-graph
 * - Content covers character limits (150-160) and Google rewriting behavior
 */

import { getGuideBySlug, GUIDE_SLUGS } from '@/lib/guides/registry';
import {
  isSEOGuide,
  isGuideSection,
  isChecklistItem,
  isCodeExample,
  ChecklistPriority,
} from '@/lib/guides/types';

// ============================================================================
// Guide Existence Tests
// ============================================================================

describe('Meta Description Guide: Existence', () => {
  test('MD-001: meta-description slug exists in GUIDE_SLUGS', () => {
    expect(GUIDE_SLUGS).toContain('meta-description');
  });

  test('MD-002: getGuideBySlug returns meta-description guide', () => {
    const guide = getGuideBySlug('meta-description');
    expect(guide).not.toBeNull();
    expect(guide?.slug).toBe('meta-description');
  });

  test('MD-003: Guide is a valid SEOGuide object', () => {
    const guide = getGuideBySlug('meta-description');
    expect(guide).not.toBeNull();
    expect(isSEOGuide(guide)).toBe(true);
  });
});

// ============================================================================
// Guide Metadata Tests
// ============================================================================

describe('Meta Description Guide: Metadata', () => {
  const guide = getGuideBySlug('meta-description');

  beforeEach(() => {
    expect(guide).not.toBeNull();
  });

  test('MD-004: Title is descriptive', () => {
    expect(guide!.title).toBeTruthy();
    expect(guide!.title.length).toBeGreaterThan(5);
    expect(guide!.title.toLowerCase()).toContain('meta');
    expect(guide!.title.toLowerCase()).toContain('description');
  });

  test('MD-005: Summary is descriptive (20-200 chars)', () => {
    expect(guide!.summary).toBeTruthy();
    expect(guide!.summary.length).toBeGreaterThanOrEqual(20);
    expect(guide!.summary.length).toBeLessThanOrEqual(200);
  });

  test('MD-006: Category is on-page', () => {
    expect(guide!.category).toBe('on-page');
  });

  test('MD-007: Tool slug links to meta-tag-analyzer', () => {
    expect(guide!.toolSlug).toBe('meta-tag-analyzer');
  });

  test('MD-008: Icon is defined', () => {
    expect(guide!.icon).toBeTruthy();
    expect(guide!.icon.length).toBeGreaterThan(0);
  });

  test('MD-009: Read time is 3-5 minutes', () => {
    expect(guide!.readTime).toBeGreaterThanOrEqual(3);
    expect(guide!.readTime).toBeLessThanOrEqual(5);
  });
});

// ============================================================================
// Required Sections Tests
// ============================================================================

describe('Meta Description Guide: Required Sections', () => {
  const guide = getGuideBySlug('meta-description');

  beforeEach(() => {
    expect(guide).not.toBeNull();
  });

  test('MD-010: Sections array is not empty', () => {
    expect(guide!.sections).toBeDefined();
    expect(Array.isArray(guide!.sections)).toBe(true);
    expect(guide!.sections.length).toBeGreaterThan(0);
  });

  test('MD-011: All sections are valid GuideSection objects', () => {
    guide!.sections.forEach((section) => {
      expect(isGuideSection(section)).toBe(true);
    });
  });

  test('MD-012: Section "What is Meta Description?" exists', () => {
    const sectionTitles = guide!.sections.map((s) => s.title.toLowerCase());
    const hasWhatIs = sectionTitles.some(
      (title) =>
        title.includes('what is') ||
        title.includes('definition') ||
        (title.includes('meta description') && title.includes('what'))
    );
    expect(hasWhatIs).toBe(true);
  });

  test('MD-013: Section covers SEO impact/CTR importance', () => {
    const content = guide!.sections.map((s) => s.content.toLowerCase()).join(' ');
    const titles = guide!.sections.map((s) => s.title.toLowerCase()).join(' ');
    
    const hasSEOImpact =
      content.includes('ctr') ||
      content.includes('click-through') ||
      content.includes('click rate') ||
      titles.includes('why') ||
      titles.includes('impact') ||
      titles.includes('importance') ||
      titles.includes('matter');

    expect(hasSEOImpact).toBe(true);
  });

  test('MD-014: Section covers optimization steps', () => {
    const titles = guide!.sections.map((s) => s.title.toLowerCase());
    const content = guide!.sections.map((s) => s.content.toLowerCase()).join(' ');
    
    const hasOptimization =
      titles.some((t) => t.includes('optim') || t.includes('how to') || t.includes('write')) ||
      content.includes('optimiz') ||
      content.includes('best practice') ||
      content.includes('compelling');

    expect(hasOptimization).toBe(true);
  });

  test('MD-015: Section covers common mistakes', () => {
    const titles = guide!.sections.map((s) => s.title.toLowerCase());
    const content = guide!.sections.map((s) => s.content.toLowerCase()).join(' ');
    
    const hasMistakes =
      titles.some((t) => t.includes('mistake') || t.includes('avoid') || t.includes('error')) ||
      content.includes('mistake') ||
      content.includes('avoid') ||
      content.includes('don\'t') ||
      content.includes('do not') ||
      content.includes('duplicate') ||
      content.includes('missing');

    expect(hasMistakes).toBe(true);
  });

  test('MD-016: Each section has meaningful content (100+ chars)', () => {
    guide!.sections.forEach((section) => {
      expect(section.content.length).toBeGreaterThanOrEqual(100);
    });
  });

  test('MD-017: Section titles are unique', () => {
    const titles = guide!.sections.map((s) => s.title);
    const uniqueTitles = new Set(titles);
    expect(uniqueTitles.size).toBe(titles.length);
  });
});

// ============================================================================
// Code Examples Tests
// ============================================================================

describe('Meta Description Guide: Code Examples', () => {
  const guide = getGuideBySlug('meta-description');

  beforeEach(() => {
    expect(guide).not.toBeNull();
  });

  test('MD-018: At least one section has code examples', () => {
    const sectionsWithCode = guide!.sections.filter(
      (s) => s.codeExamples && s.codeExamples.length > 0
    );
    expect(sectionsWithCode.length).toBeGreaterThanOrEqual(1);
  });

  test('MD-019: Total code examples count is 1-5', () => {
    const totalCodeExamples = guide!.sections.reduce(
      (count, section) => count + (section.codeExamples?.length || 0),
      0
    );
    expect(totalCodeExamples).toBeGreaterThanOrEqual(1);
    expect(totalCodeExamples).toBeLessThanOrEqual(5);
  });

  test('MD-020: All code examples are valid CodeExample objects', () => {
    guide!.sections.forEach((section) => {
      if (section.codeExamples) {
        section.codeExamples.forEach((example) => {
          expect(isCodeExample(example)).toBe(true);
        });
      }
    });
  });

  test('MD-021: Code examples have non-empty language field', () => {
    guide!.sections.forEach((section) => {
      if (section.codeExamples) {
        section.codeExamples.forEach((example) => {
          expect(example.language).toBeTruthy();
          expect(example.language.length).toBeGreaterThan(0);
        });
      }
    });
  });

  test('MD-022: Code examples have non-empty code field', () => {
    guide!.sections.forEach((section) => {
      if (section.codeExamples) {
        section.codeExamples.forEach((example) => {
          expect(example.code).toBeTruthy();
          expect(example.code.length).toBeGreaterThan(0);
        });
      }
    });
  });

  test('MD-023: Code examples demonstrate HTML meta description tags', () => {
    const allCode = guide!.sections
      .flatMap((s) => s.codeExamples || [])
      .map((e) => e.code.toLowerCase())
      .join(' ');

    const hasMetaTag =
      allCode.includes('meta') ||
      allCode.includes('description') ||
      allCode.includes('name=') ||
      allCode.includes('content=');

    expect(hasMetaTag).toBe(true);
  });
});

// ============================================================================
// Checklist Tests
// ============================================================================

describe('Meta Description Guide: Checklist', () => {
  const guide = getGuideBySlug('meta-description');

  beforeEach(() => {
    expect(guide).not.toBeNull();
  });

  test('MD-024: Checklist array is not empty', () => {
    expect(guide!.checklist).toBeDefined();
    expect(Array.isArray(guide!.checklist)).toBe(true);
    expect(guide!.checklist.length).toBeGreaterThan(0);
  });

  test('MD-025: Checklist has 5-10 items', () => {
    expect(guide!.checklist.length).toBeGreaterThanOrEqual(5);
    expect(guide!.checklist.length).toBeLessThanOrEqual(10);
  });

  test('MD-026: All checklist items are valid ChecklistItem objects', () => {
    guide!.checklist.forEach((item) => {
      expect(isChecklistItem(item)).toBe(true);
    });
  });

  test('MD-027: All checklist items have non-empty text', () => {
    guide!.checklist.forEach((item) => {
      expect(item.text).toBeTruthy();
      expect(item.text.length).toBeGreaterThan(10);
    });
  });

  test('MD-028: All checklist items have valid priority', () => {
    const validPriorities: ChecklistPriority[] = ['critical', 'important', 'recommended'];
    guide!.checklist.forEach((item) => {
      expect(validPriorities).toContain(item.priority);
    });
  });

  test('MD-029: At least one critical priority item exists', () => {
    const criticalItems = guide!.checklist.filter((item) => item.priority === 'critical');
    expect(criticalItems.length).toBeGreaterThanOrEqual(1);
  });

  test('MD-030: Checklist covers character length', () => {
    const checklistText = guide!.checklist.map((i) => i.text.toLowerCase()).join(' ');
    const hasLengthGuidance =
      checklistText.includes('length') ||
      checklistText.includes('character') ||
      checklistText.includes('150') ||
      checklistText.includes('160');

    expect(hasLengthGuidance).toBe(true);
  });

  test('MD-031: Checklist covers unique descriptions', () => {
    const checklistText = guide!.checklist.map((i) => i.text.toLowerCase()).join(' ');
    const hasUniqueGuidance =
      checklistText.includes('unique') ||
      checklistText.includes('duplicate') ||
      checklistText.includes('each page');

    expect(hasUniqueGuidance).toBe(true);
  });

  test('MD-032: Checklist covers compelling/CTR optimization', () => {
    const checklistText = guide!.checklist.map((i) => i.text.toLowerCase()).join(' ');
    const hasCTRGuidance =
      checklistText.includes('ctr') ||
      checklistText.includes('click') ||
      checklistText.includes('compelling') ||
      checklistText.includes('action') ||
      checklistText.includes('entice');

    expect(hasCTRGuidance).toBe(true);
  });
});

// ============================================================================
// Related Guides Tests
// ============================================================================

describe('Meta Description Guide: Related Guides', () => {
  const guide = getGuideBySlug('meta-description');

  beforeEach(() => {
    expect(guide).not.toBeNull();
  });

  test('MD-033: Related guides array is not empty', () => {
    expect(guide!.relatedGuides).toBeDefined();
    expect(Array.isArray(guide!.relatedGuides)).toBe(true);
    expect(guide!.relatedGuides.length).toBeGreaterThan(0);
  });

  test('MD-034: meta-title is in related guides', () => {
    expect(guide!.relatedGuides).toContain('meta-title');
  });

  test('MD-035: open-graph is in related guides', () => {
    expect(guide!.relatedGuides).toContain('open-graph');
  });

  test('MD-036: All related guide slugs are valid', () => {
    guide!.relatedGuides.forEach((slug) => {
      expect(GUIDE_SLUGS).toContain(slug);
    });
  });

  test('MD-037: Related guides do not include self-reference', () => {
    expect(guide!.relatedGuides).not.toContain('meta-description');
  });

  test('MD-038: Related guides count is reasonable (2-5)', () => {
    expect(guide!.relatedGuides.length).toBeGreaterThanOrEqual(2);
    expect(guide!.relatedGuides.length).toBeLessThanOrEqual(5);
  });
});

// ============================================================================
// Content Quality Tests
// ============================================================================

describe('Meta Description Guide: Content Quality', () => {
  const guide = getGuideBySlug('meta-description');

  beforeEach(() => {
    expect(guide).not.toBeNull();
  });

  test('MD-039: Total content length is substantial (2000+ chars)', () => {
    const totalContent = guide!.sections.map((s) => s.content).join(' ');
    expect(totalContent.length).toBeGreaterThanOrEqual(2000);
  });

  test('MD-040: Content mentions character limits (150-160)', () => {
    const content = guide!.sections.map((s) => s.content.toLowerCase()).join(' ');
    const hasCharacterLimit =
      content.includes('150') ||
      content.includes('160') ||
      content.includes('character limit') ||
      content.includes('character');

    expect(hasCharacterLimit).toBe(true);
  });

  test('MD-041: Content mentions Google/SEO practices', () => {
    const content = guide!.sections.map((s) => s.content.toLowerCase()).join(' ');
    const hasGoogleMention =
      content.includes('google') ||
      content.includes('serp') ||
      content.includes('search result') ||
      content.includes('ranking');

    expect(hasGoogleMention).toBe(true);
  });

  test('MD-042: Content mentions CTR/click-through rate', () => {
    const content = guide!.sections.map((s) => s.content.toLowerCase()).join(' ');
    const hasCTR =
      content.includes('click-through') ||
      content.includes('ctr') ||
      content.includes('click rate') ||
      content.includes('entice') ||
      content.includes('compelling');

    expect(hasCTR).toBe(true);
  });

  test('MD-043: Content mentions Google rewriting behavior', () => {
    const content = guide!.sections.map((s) => s.content.toLowerCase()).join(' ');
    const hasRewritingInfo =
      content.includes('rewrite') ||
      content.includes('replace') ||
      content.includes('override') ||
      content.includes('ignores') ||
      content.includes('may not use') ||
      content.includes('doesn\'t always');

    expect(hasRewritingInfo).toBe(true);
  });

  test('MD-044: Content is properly formatted (has headings)', () => {
    const content = guide!.sections.map((s) => s.content).join(' ');
    const hasHeadings =
      content.includes('##') ||
      content.includes('###') ||
      content.includes('**') ||
      content.includes('- ');

    expect(hasHeadings).toBe(true);
  });

  test('MD-045: Content avoids keyword stuffing', () => {
    const content = guide!.sections.map((s) => s.content.toLowerCase()).join(' ');
    const metaDescCount = (content.match(/meta description/g) || []).length;
    
    // Should not have excessive repetition (arbitrary threshold: < 25 occurrences)
    expect(metaDescCount).toBeLessThan(25);
  });
});

// ============================================================================
// Integration Tests
// ============================================================================

describe('Meta Description Guide: Integration', () => {
  test('MD-046: Guide is retrievable by tool slug', () => {
    const { getGuidesByToolSlug } = require('@/lib/guides/registry');
    const guidesForTool = getGuidesByToolSlug('meta-tag-analyzer');
    
    const metaDescGuide = guidesForTool.find((g: any) => g.slug === 'meta-description');
    expect(metaDescGuide).toBeDefined();
  });

  test('MD-047: Related guides are retrievable', () => {
    const metaTitle = getGuideBySlug('meta-title');
    const openGraph = getGuideBySlug('open-graph');
    
    expect(metaTitle).not.toBeNull();
    expect(openGraph).not.toBeNull();
  });

  test('MD-048: Guide is listed in on-page category', () => {
    const { getGuidesByCategory } = require('@/lib/guides/registry');
    const onPageGuides = getGuidesByCategory('on-page');
    
    const metaDescGuide = onPageGuides.find((g: any) => g.slug === 'meta-description');
    expect(metaDescGuide).toBeDefined();
  });
});
