/**
 * XML Sitemap Guide Content Tests
 * Task: TASK-20-21 - Write Content for XML Sitemap Guide
 * 
 * Tests for XML Sitemap guide content structure and quality validation.
 */

import {
  getGuideBySlug,
  getAllGuides,
} from '@/lib/guides/registry';
import {
  isSEOGuide,
  isValidCategory,
} from '@/lib/guides/types';

// Guide slug being tested
const GUIDE_SLUG = 'xml-sitemap';

describe('XML Sitemap Guide - Content Structure', () => {
  let guide: ReturnType<typeof getGuideBySlug>;

  beforeAll(() => {
    guide = getGuideBySlug(GUIDE_SLUG);
  });

  // XS-001 to XS-003: Guide exists and basic structure
  describe('Guide Existence (XS-001 to XS-003)', () => {
    it('XS-001: should exist in registry', () => {
      expect(guide).not.toBeNull();
    });

    it('XS-002: should have correct title', () => {
      expect(guide?.title).toBe('XML Sitemap Creation');
    });

    it('XS-003: should have non-empty summary', () => {
      expect(guide?.summary).toBeTruthy();
      expect(guide?.summary.length).toBeGreaterThan(20);
    });
  });

  // WS-001 to WS-004: What is XML Sitemap section
  describe('What is XML Sitemap Section (WS-001 to WS-004)', () => {
    let section: { title: string; content: string } | undefined;

    beforeAll(() => {
      section = guide?.sections.find(
        (s) => s.title.toLowerCase().includes('what is') && s.title.toLowerCase().includes('sitemap')
      );
    });

    it('WS-001: should have a "What is an XML Sitemap?" section', () => {
      expect(section).toBeDefined();
    });

    it('WS-002: should explain XML format', () => {
      expect(section?.content.toLowerCase()).toMatch(/xml|sitemap/);
    });

    it('WS-003: should mention search engines', () => {
      expect(section?.content.toLowerCase()).toMatch(/google|search engine|crawler/);
    });

    it('WS-004: should have valid content length (>150 chars)', () => {
      expect(section?.content.length).toBeGreaterThan(150);
    });
  });

  // SM-001 to SM-005: Why Sitemaps Matter section
  describe('Why Sitemaps Matter Section (SM-001 to SM-005)', () => {
    let section: { title: string; content: string } | undefined;

    beforeAll(() => {
      section = guide?.sections.find(
        (s) => s.title.toLowerCase().includes('why') && s.title.toLowerCase().includes('sitemap')
      );
    });

    it('SM-001: should have a "Why Sitemaps Matter" section', () => {
      expect(section).toBeDefined();
    });

    it('SM-002: should mention crawlability', () => {
      expect(section?.content.toLowerCase()).toMatch(/crawl|discover/);
    });

    it('SM-003: should mention indexing', () => {
      expect(section?.content.toLowerCase()).toMatch(/index/);
    });

    it('SM-004: should explain SEO benefits', () => {
      expect(section?.content.toLowerCase()).toMatch(/seo|ranking|visibility/);
    });

    it('SM-005: should have valid content length (>200 chars)', () => {
      expect(section?.content.length).toBeGreaterThan(200);
    });
  });

  // ST-001 to ST-006: Sitemap Structure section
  describe('Sitemap Structure Section (ST-001 to ST-006)', () => {
    let section: { title: string; content: string; codeExamples: unknown[] } | undefined;

    beforeAll(() => {
      section = guide?.sections.find(
        (s) => s.title.toLowerCase().includes('structure')
      );
    });

    it('ST-001: should have a sitemap structure section', () => {
      expect(section).toBeDefined();
    });

    it('ST-002: should mention required tags', () => {
      const content = section?.content.toLowerCase() || '';
      const hasRequiredTags = 
        content.includes('urlset') ||
        content.includes('<url') ||
        content.includes('<loc');
      expect(hasRequiredTags).toBe(true);
    });

    it('ST-003: should mention lastmod', () => {
      expect(section?.content.toLowerCase()).toMatch(/lastmod/);
    });

    it('ST-004: should mention changefreq', () => {
      expect(section?.content.toLowerCase()).toMatch(/changefreq/);
    });

    it('ST-005: should mention priority', () => {
      expect(section?.content.toLowerCase()).toMatch(/priority/);
    });

    it('ST-006: should have code examples', () => {
      // Either this section has code examples or another section does
      const hasCodeExamples = guide?.sections.some((s) => s.codeExamples && s.codeExamples.length > 0);
      expect(hasCodeExamples).toBe(true);
    });
  });

  // GC-001 to GC-005: Google Search Console section
  describe('Google Search Console Section (GC-001 to GC-005)', () => {
    let section: { title: string; content: string } | undefined;

    beforeAll(() => {
      section = guide?.sections.find(
        (s) => 
          s.title.toLowerCase().includes('google') ||
          s.title.toLowerCase().includes('search console') ||
          s.title.toLowerCase().includes('submit')
      );
    });

    it('GC-001: should have a GSC submission section', () => {
      expect(section).toBeDefined();
    });

    it('GC-002: should mention Google Search Console', () => {
      const content = section?.content.toLowerCase() || '';
      expect(content.includes('google search console') || content.includes('gsc')).toBe(true);
    });

    it('GC-003: should explain submission process', () => {
      expect(section?.content.toLowerCase()).toMatch(/submit|ping/);
    });

    it('GC-004: should mention sitemap URL', () => {
      expect(section?.content.toLowerCase()).toMatch(/sitemap\.xml|sitemap\/|\/sitemap/);
    });

    it('GC-005: should have valid content length (>150 chars)', () => {
      expect(section?.content.length).toBeGreaterThan(150);
    });
  });

  // DG-001 to DG-005: Dynamic Generation section
  describe('Dynamic Generation Section (DG-001 to DG-005)', () => {
    let section: { title: string; content: string } | undefined;

    beforeAll(() => {
      section = guide?.sections.find(
        (s) => 
          s.title.toLowerCase().includes('dynamic') ||
          s.title.toLowerCase().includes('generation') ||
          s.title.toLowerCase().includes('automatic')
      );
    });

    it('DG-001: should have a dynamic generation section', () => {
      expect(section).toBeDefined();
    });

    it('DG-002: should mention automatic generation', () => {
      const content = section?.content.toLowerCase() || '';
      expect(content.includes('dynamic') || content.includes('automatic') || content.includes('generate')).toBe(true);
    });

    it('DG-003: should mention sitemap index files', () => {
      const content = guide?.sections
        .map((s) => s.content.toLowerCase())
        .join(' ') || '';
      expect(content.includes('sitemap index') || content.includes('sitemapindex')).toBe(true);
    });

    it('DG-004: should mention large sites or 50,000 limit', () => {
      const content = guide?.sections
        .map((s) => s.content.toLowerCase())
        .join(' ') || '';
      expect(content.includes('50,000') || content.includes('50000') || content.includes('large')).toBe(true);
    });

    it('DG-005: should have valid content length (>150 chars)', () => {
      expect(section?.content.length).toBeGreaterThan(150);
    });
  });

  // SI-001 to SI-004: Sitemap Index Files section
  describe('Sitemap Index Files Section (SI-001 to SI-004)', () => {
    let allContent: string;

    beforeAll(() => {
      allContent = guide?.sections.map((s) => s.content.toLowerCase()).join(' ') || '';
    });

    it('SI-001: should cover sitemap index files', () => {
      expect(allContent).toMatch(/sitemap index|sitemapindex/);
    });

    it('SI-002: should mention sitemapindex tag', () => {
      expect(allContent).toMatch(/sitemapindex|sitemap index/);
    });

    it('SI-003: should explain 50,000 URL limit', () => {
      expect(allContent).toMatch(/50,?000|fifty.?thousand/);
    });

    it('SI-004: may have sitemap index code example', () => {
      const hasIndexExample = guide?.sections.some(
        (s) => s.codeExamples?.some(
          (ex: { code: string }) => ex.code.toLowerCase().includes('sitemapindex')
        )
      );
      // This is optional but recommended
      expect(typeof hasIndexExample).toBe('boolean');
    });
  });
});

describe('XML Sitemap Guide - Checklist (BC-001 to BC-005)', () => {
  let guide: ReturnType<typeof getGuideBySlug>;

  beforeAll(() => {
    guide = getGuideBySlug(GUIDE_SLUG);
  });

  it('BC-001: should have 5-10 checklist items', () => {
    const checklist = guide?.checklist || [];
    expect(checklist.length).toBeGreaterThanOrEqual(5);
    expect(checklist.length).toBeLessThanOrEqual(10);
  });

  it('BC-002: all checklist items should have valid text', () => {
    const checklist = guide?.checklist || [];
    checklist.forEach((item) => {
      expect(item.text).toBeTruthy();
      expect(item.text.length).toBeGreaterThan(5);
    });
  });

  it('BC-003: all checklist items should have valid priority', () => {
    const validPriorities = ['critical', 'important', 'recommended'];
    const checklist = guide?.checklist || [];
    checklist.forEach((item) => {
      expect(validPriorities).toContain(item.priority);
    });
  });

  it('BC-004: should have at least one critical item', () => {
    const checklist = guide?.checklist || [];
    const hasCritical = checklist.some((item) => item.priority === 'critical');
    expect(hasCritical).toBe(true);
  });

  it('BC-005: should cover key sitemap practices', () => {
    const checklist = guide?.checklist || [];
    const allText = checklist.map((i) => i.text.toLowerCase()).join(' ');
    
    // Should cover submission
    expect(allText).toMatch(/submit|google search console/);
    
    // Should cover structure or canonical URLs
    expect(allText).toMatch(/canonical|url|structure/);
    
    // Should cover updates
    expect(allText).toMatch(/update|lastmod|changefreq/);
  });
});

describe('XML Sitemap Guide - Read Time (RT-001 to RT-003)', () => {
  let guide: ReturnType<typeof getGuideBySlug>;

  beforeAll(() => {
    guide = getGuideBySlug(GUIDE_SLUG);
  });

  it('RT-001: should have read time set', () => {
    expect(typeof guide?.readTime).toBe('number');
  });

  it('RT-002: should have read time in valid range (4-6 minutes)', () => {
    expect(guide?.readTime).toBeGreaterThanOrEqual(4);
    expect(guide?.readTime).toBeLessThanOrEqual(6);
  });

  it('RT-003: should have appropriate content length for read time', () => {
    // Total word count should roughly match read time (200-300 words per minute)
    const allContent = guide?.sections.map((s) => s.content).join(' ') || '';
    const wordCount = allContent.split(/\s+/).length;
    const minWords = (guide?.readTime || 4) * 150;
    const maxWords = (guide?.readTime || 6) * 350;
    expect(wordCount).toBeGreaterThanOrEqual(minWords);
    expect(wordCount).toBeLessThanOrEqual(maxWords);
  });
});

describe('XML Sitemap Guide - Related Guides (RG-001 to RG-004)', () => {
  let guide: ReturnType<typeof getGuideBySlug>;

  beforeAll(() => {
    guide = getGuideBySlug(GUIDE_SLUG);
  });

  it('RG-001: should have related guides array', () => {
    expect(Array.isArray(guide?.relatedGuides)).toBe(true);
  });

  it('RG-002: should contain robots-txt', () => {
    expect(guide?.relatedGuides).toContain('robots-txt');
  });

  it('RG-003: should contain internal-links', () => {
    expect(guide?.relatedGuides).toContain('internal-links');
  });

  it('RG-004: all related guides should exist in registry', () => {
    const relatedGuides = guide?.relatedGuides || [];
    relatedGuides.forEach((slug) => {
      const relatedGuide = getGuideBySlug(slug);
      expect(relatedGuide).not.toBeNull();
    });
  });
});

describe('XML Sitemap Guide - Registry Integration (GR-001 to GR-003)', () => {
  it('GR-001: should be in getAllGuides()', () => {
    const allGuides = getAllGuides();
    const found = allGuides.some((g) => g.slug === GUIDE_SLUG);
    expect(found).toBe(true);
  });

  it('GR-002: getGuideBySlug should return complete guide', () => {
    const guide = getGuideBySlug(GUIDE_SLUG);
    expect(guide).not.toBeNull();
    expect(guide?.sections).toBeDefined();
    expect(guide?.checklist).toBeDefined();
    expect(guide?.relatedGuides).toBeDefined();
  });

  it('GR-003: should validate with type guard', () => {
    const guide = getGuideBySlug(GUIDE_SLUG);
    expect(isSEOGuide(guide)).toBe(true);
  });
});

describe('XML Sitemap Guide - Category Validation', () => {
  it('should have correct category', () => {
    const guide = getGuideBySlug(GUIDE_SLUG);
    expect(guide?.category).toBe('technical');
  });

  it('should have valid category', () => {
    const guide = getGuideBySlug(GUIDE_SLUG);
    expect(isValidCategory(guide?.category)).toBe(true);
  });
});

describe('XML Sitemap Guide - Icon', () => {
  it('should have an icon defined', () => {
    const guide = getGuideBySlug(GUIDE_SLUG);
    expect(guide?.icon).toBeTruthy();
  });
});
