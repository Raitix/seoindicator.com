/**
 * Tests for Meta Title Guide Content
 * Task: TASK-20-5 - Write Content for Meta Title Guide
 * 
 * Tests verify the meta-title guide meets all acceptance criteria:
 * - Content covers required sections (definition, SEO impact, optimization, mistakes, best practices)
 * - Checklist has 5-10 items with proper priority levels
 * - Code examples present (1-2 examples)
 * - Read time: 3-5 minutes
 * - Tool integration links to meta-tag-analyzer
 * - Related guides include meta-description, h1-headings
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

describe('Meta Title Guide: Existence', () => {
  test('MT-001: meta-title slug exists in GUIDE_SLUGS', () => {
    expect(GUIDE_SLUGS).toContain('meta-title');
  });

  test('MT-002: getGuideBySlug returns meta-title guide', () => {
    const guide = getGuideBySlug('meta-title');
    expect(guide).not.toBeNull();
    expect(guide?.slug).toBe('meta-title');
  });

  test('MT-003: Guide is a valid SEOGuide object', () => {
    const guide = getGuideBySlug('meta-title');
    expect(guide).not.toBeNull();
    expect(isSEOGuide(guide)).toBe(true);
  });
});

// ============================================================================
// Guide Metadata Tests
// ============================================================================

describe('Meta Title Guide: Metadata', () => {
  const guide = getGuideBySlug('meta-title');

  beforeEach(() => {
    expect(guide).not.toBeNull();
  });

  test('MT-004: Title is descriptive', () => {
    expect(guide!.title).toBeTruthy();
    expect(guide!.title.length).toBeGreaterThan(5);
    expect(guide!.title.toLowerCase()).toContain('meta');
    expect(guide!.title.toLowerCase()).toContain('title');
  });

  test('MT-005: Summary is descriptive (20-200 chars)', () => {
    expect(guide!.summary).toBeTruthy();
    expect(guide!.summary.length).toBeGreaterThanOrEqual(20);
    expect(guide!.summary.length).toBeLessThanOrEqual(200);
  });

  test('MT-006: Category is on-page', () => {
    expect(guide!.category).toBe('on-page');
  });

  test('MT-007: Tool slug links to meta-tag-analyzer', () => {
    expect(guide!.toolSlug).toBe('meta-tag-analyzer');
  });

  test('MT-008: Icon is defined', () => {
    expect(guide!.icon).toBeTruthy();
    expect(guide!.icon.length).toBeGreaterThan(0);
  });

  test('MT-009: Read time is 3-5 minutes', () => {
    expect(guide!.readTime).toBeGreaterThanOrEqual(3);
    expect(guide!.readTime).toBeLessThanOrEqual(5);
  });
});

// ============================================================================
// Required Sections Tests
// ============================================================================

describe('Meta Title Guide: Required Sections', () => {
  const guide = getGuideBySlug('meta-title');

  beforeEach(() => {
    expect(guide).not.toBeNull();
  });

  test('MT-010: Sections array is not empty', () => {
    expect(guide!.sections).toBeDefined();
    expect(Array.isArray(guide!.sections)).toBe(true);
    expect(guide!.sections.length).toBeGreaterThan(0);
  });

  test('MT-011: All sections are valid GuideSection objects', () => {
    guide!.sections.forEach((section) => {
      expect(isGuideSection(section)).toBe(true);
    });
  });

  test('MT-012: Section "What is Meta Title?" exists', () => {
    const sectionTitles = guide!.sections.map((s) => s.title.toLowerCase());
    const hasWhatIs = sectionTitles.some(
      (title) =>
        title.includes('what is') ||
        title.includes('definition') ||
        title.includes('meta title') && title.includes('what')
    );
    expect(hasWhatIs).toBe(true);
  });

  test('MT-013: Section covers SEO impact/importance', () => {
    const content = guide!.sections.map((s) => s.content.toLowerCase()).join(' ');
    const titles = guide!.sections.map((s) => s.title.toLowerCase()).join(' ');
    
    const hasSEOImpact =
      content.includes('seo') ||
      content.includes('ranking') ||
      content.includes('click-through') ||
      content.includes('ctr') ||
      titles.includes('why') ||
      titles.includes('impact') ||
      titles.includes('importance') ||
      titles.includes('matter');

    expect(hasSEOImpact).toBe(true);
  });

  test('MT-014: Section covers optimization steps', () => {
    const titles = guide!.sections.map((s) => s.title.toLowerCase());
    const content = guide!.sections.map((s) => s.content.toLowerCase()).join(' ');
    
    const hasOptimization =
      titles.some((t) => t.includes('optim') || t.includes('how to')) ||
      content.includes('optimiz') ||
      content.includes('best practice') ||
      content.includes('step-by-step') ||
      content.includes('guide');

    expect(hasOptimization).toBe(true);
  });

  test('MT-015: Section covers common mistakes', () => {
    const titles = guide!.sections.map((s) => s.title.toLowerCase());
    const content = guide!.sections.map((s) => s.content.toLowerCase()).join(' ');
    
    const hasMistakes =
      titles.some((t) => t.includes('mistake') || t.includes('avoid') || t.includes('error')) ||
      content.includes('mistake') ||
      content.includes('avoid') ||
      content.includes('don\'t') ||
      content.includes('do not') ||
      content.includes('common error');

    expect(hasMistakes).toBe(true);
  });

  test('MT-016: Each section has meaningful content (100+ chars)', () => {
    guide!.sections.forEach((section) => {
      expect(section.content.length).toBeGreaterThanOrEqual(100);
    });
  });

  test('MT-017: Section titles are unique', () => {
    const titles = guide!.sections.map((s) => s.title);
    const uniqueTitles = new Set(titles);
    expect(uniqueTitles.size).toBe(titles.length);
  });
});

// ============================================================================
// Code Examples Tests
// ============================================================================

describe('Meta Title Guide: Code Examples', () => {
  const guide = getGuideBySlug('meta-title');

  beforeEach(() => {
    expect(guide).not.toBeNull();
  });

  test('MT-018: At least one section has code examples', () => {
    const sectionsWithCode = guide!.sections.filter(
      (s) => s.codeExamples && s.codeExamples.length > 0
    );
    expect(sectionsWithCode.length).toBeGreaterThanOrEqual(1);
  });

  test('MT-019: Total code examples count is 1-5', () => {
    const totalCodeExamples = guide!.sections.reduce(
      (count, section) => count + (section.codeExamples?.length || 0),
      0
    );
    expect(totalCodeExamples).toBeGreaterThanOrEqual(1);
    expect(totalCodeExamples).toBeLessThanOrEqual(5);
  });

  test('MT-020: All code examples are valid CodeExample objects', () => {
    guide!.sections.forEach((section) => {
      if (section.codeExamples) {
        section.codeExamples.forEach((example) => {
          expect(isCodeExample(example)).toBe(true);
        });
      }
    });
  });

  test('MT-021: Code examples have non-empty language field', () => {
    guide!.sections.forEach((section) => {
      if (section.codeExamples) {
        section.codeExamples.forEach((example) => {
          expect(example.language).toBeTruthy();
          expect(example.language.length).toBeGreaterThan(0);
        });
      }
    });
  });

  test('MT-022: Code examples have non-empty code field', () => {
    guide!.sections.forEach((section) => {
      if (section.codeExamples) {
        section.codeExamples.forEach((example) => {
          expect(example.code).toBeTruthy();
          expect(example.code.length).toBeGreaterThan(0);
        });
      }
    });
  });

  test('MT-023: Code examples demonstrate HTML title tags', () => {
    const allCode = guide!.sections
      .flatMap((s) => s.codeExamples || [])
      .map((e) => e.code.toLowerCase())
      .join(' ');

    const hasTitleTag =
      allCode.includes('<title') ||
      allCode.includes('meta') ||
      allCode.includes('html');

    expect(hasTitleTag).toBe(true);
  });
});

// ============================================================================
// Checklist Tests
// ============================================================================

describe('Meta Title Guide: Checklist', () => {
  const guide = getGuideBySlug('meta-title');

  beforeEach(() => {
    expect(guide).not.toBeNull();
  });

  test('MT-024: Checklist array is not empty', () => {
    expect(guide!.checklist).toBeDefined();
    expect(Array.isArray(guide!.checklist)).toBe(true);
    expect(guide!.checklist.length).toBeGreaterThan(0);
  });

  test('MT-025: Checklist has 5-10 items', () => {
    expect(guide!.checklist.length).toBeGreaterThanOrEqual(5);
    expect(guide!.checklist.length).toBeLessThanOrEqual(10);
  });

  test('MT-026: All checklist items are valid ChecklistItem objects', () => {
    guide!.checklist.forEach((item) => {
      expect(isChecklistItem(item)).toBe(true);
    });
  });

  test('MT-027: All checklist items have non-empty text', () => {
    guide!.checklist.forEach((item) => {
      expect(item.text).toBeTruthy();
      expect(item.text.length).toBeGreaterThan(10);
    });
  });

  test('MT-028: All checklist items have valid priority', () => {
    const validPriorities: ChecklistPriority[] = ['critical', 'important', 'recommended'];
    guide!.checklist.forEach((item) => {
      expect(validPriorities).toContain(item.priority);
    });
  });

  test('MT-029: At least one critical priority item exists', () => {
    const criticalItems = guide!.checklist.filter((item) => item.priority === 'critical');
    expect(criticalItems.length).toBeGreaterThanOrEqual(1);
  });

  test('MT-030: Checklist covers title length optimization', () => {
    const checklistText = guide!.checklist.map((i) => i.text.toLowerCase()).join(' ');
    const hasLengthGuidance =
      checklistText.includes('length') ||
      checklistText.includes('character') ||
      checklistText.includes('50-60') ||
      checklistText.includes('pixel');

    expect(hasLengthGuidance).toBe(true);
  });

  test('MT-031: Checklist covers keyword placement', () => {
    const checklistText = guide!.checklist.map((i) => i.text.toLowerCase()).join(' ');
    const hasKeywordGuidance =
      checklistText.includes('keyword') ||
      checklistText.includes('phrase') ||
      checklistText.includes('primary');

    expect(hasKeywordGuidance).toBe(true);
  });

  test('MT-032: Checklist covers unique titles', () => {
    const checklistText = guide!.checklist.map((i) => i.text.toLowerCase()).join(' ');
    const hasUniqueGuidance =
      checklistText.includes('unique') ||
      checklistText.includes('duplicate') ||
      checklistText.includes('each page');

    expect(hasUniqueGuidance).toBe(true);
  });
});

// ============================================================================
// Related Guides Tests
// ============================================================================

describe('Meta Title Guide: Related Guides', () => {
  const guide = getGuideBySlug('meta-title');

  beforeEach(() => {
    expect(guide).not.toBeNull();
  });

  test('MT-033: Related guides array is not empty', () => {
    expect(guide!.relatedGuides).toBeDefined();
    expect(Array.isArray(guide!.relatedGuides)).toBe(true);
    expect(guide!.relatedGuides.length).toBeGreaterThan(0);
  });

  test('MT-034: meta-description is in related guides', () => {
    expect(guide!.relatedGuides).toContain('meta-description');
  });

  test('MT-035: h1-headings is in related guides', () => {
    expect(guide!.relatedGuides).toContain('h1-headings');
  });

  test('MT-036: All related guide slugs are valid', () => {
    guide!.relatedGuides.forEach((slug) => {
      expect(GUIDE_SLUGS).toContain(slug);
    });
  });

  test('MT-037: Related guides do not include self-reference', () => {
    expect(guide!.relatedGuides).not.toContain('meta-title');
  });

  test('MT-038: Related guides count is reasonable (2-5)', () => {
    expect(guide!.relatedGuides.length).toBeGreaterThanOrEqual(2);
    expect(guide!.relatedGuides.length).toBeLessThanOrEqual(5);
  });
});

// ============================================================================
// Content Quality Tests
// ============================================================================

describe('Meta Title Guide: Content Quality', () => {
  const guide = getGuideBySlug('meta-title');

  beforeEach(() => {
    expect(guide).not.toBeNull();
  });

  test('MT-039: Total content length is substantial (2000+ chars)', () => {
    const totalContent = guide!.sections.map((s) => s.content).join(' ');
    expect(totalContent.length).toBeGreaterThanOrEqual(2000);
  });

  test('MT-040: Content mentions title length guidelines', () => {
    const content = guide!.sections.map((s) => s.content.toLowerCase()).join(' ');
    const hasLengthInfo =
      content.includes('50-60') ||
      content.includes('60 character') ||
      content.includes('pixel') ||
      content.includes('length') ||
      content.includes('truncat');

    expect(hasLengthInfo).toBe(true);
  });

  test('MT-041: Content mentions Google/SEO best practices', () => {
    const content = guide!.sections.map((s) => s.content.toLowerCase()).join(' ');
    const hasGoogleMention =
      content.includes('google') ||
      content.includes('serp') ||
      content.includes('search result') ||
      content.includes('ranking');

    expect(hasGoogleMention).toBe(true);
  });

  test('MT-042: Content mentions click-through rate (CTR)', () => {
    const content = guide!.sections.map((s) => s.content.toLowerCase()).join(' ');
    const hasCTR =
      content.includes('click-through') ||
      content.includes('ctr') ||
      content.includes('click rate') ||
      content.includes('entice') ||
      content.includes('compelling');

    expect(hasCTR).toBe(true);
  });

  test('MT-043: Content is properly formatted (has headings)', () => {
    const content = guide!.sections.map((s) => s.content).join(' ');
    const hasHeadings =
      content.includes('##') ||
      content.includes('###') ||
      content.includes('**') ||
      content.includes('- ');

    expect(hasHeadings).toBe(true);
  });

  test('MT-044: Content avoids keyword stuffing', () => {
    const content = guide!.sections.map((s) => s.content.toLowerCase()).join(' ');
    const metaTitleCount = (content.match(/meta title/g) || []).length;
    
    // Should not have excessive repetition (arbitrary threshold: < 20 occurrences)
    expect(metaTitleCount).toBeLessThan(20);
  });
});

// ============================================================================
// Integration Tests
// ============================================================================

describe('Meta Title Guide: Integration', () => {
  test('MT-045: Guide is retrievable by tool slug', () => {
    const { getGuidesByToolSlug } = require('@/lib/guides/registry');
    const guidesForTool = getGuidesByToolSlug('meta-tag-analyzer');
    
    const metaTitleGuide = guidesForTool.find((g: any) => g.slug === 'meta-title');
    expect(metaTitleGuide).toBeDefined();
  });

  test('MT-046: Related guides are retrievable', () => {
    const metaDescription = getGuideBySlug('meta-description');
    const h1Headings = getGuideBySlug('h1-headings');
    
    expect(metaDescription).not.toBeNull();
    expect(h1Headings).not.toBeNull();
  });

  test('MT-047: Guide is listed in on-page category', () => {
    const { getGuidesByCategory } = require('@/lib/guides/registry');
    const onPageGuides = getGuidesByCategory('on-page');
    
    const metaTitleGuide = onPageGuides.find((g: any) => g.slug === 'meta-title');
    expect(metaTitleGuide).toBeDefined();
  });
});
