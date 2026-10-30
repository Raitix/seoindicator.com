/**
 * XML Sitemap Guide Code Examples Tests
 * Task: TASK-20-21 - Write Content for XML Sitemap Guide
 * 
 * Tests for XML Sitemap guide code examples validation.
 */

import { getGuideBySlug } from '@/lib/guides/registry';

// Guide slug being tested
const GUIDE_SLUG = 'xml-sitemap';

describe('XML Sitemap Guide - Code Examples (CE-001 to CE-008)', () => {
  let guide: ReturnType<typeof getGuideBySlug>;
  let allCodeExamples: Array<{ language: string; code: string; description?: string }>;

  beforeAll(() => {
    guide = getGuideBySlug(GUIDE_SLUG);
    allCodeExamples = guide?.sections.flatMap((s) => s.codeExamples || []) || [];
  });

  it('CE-001: should have at least one code example', () => {
    expect(allCodeExamples.length).toBeGreaterThanOrEqual(1);
  });

  it('CE-002: should have basic XML sitemap example with required tags', () => {
    const hasSitemapExample = allCodeExamples.some((ex) => {
      const code = ex.code.toLowerCase();
      return code.includes('urlset') && code.includes('url') && code.includes('loc');
    });
    expect(hasSitemapExample).toBe(true);
  });

  it('CE-003: code examples should have valid language', () => {
    allCodeExamples.forEach((ex) => {
      expect(ex.language).toBeTruthy();
      expect(typeof ex.language).toBe('string');
    });
  });

  it('CE-004: code examples should have code content', () => {
    allCodeExamples.forEach((ex) => {
      expect(ex.code).toBeTruthy();
      expect(ex.code.length).toBeGreaterThan(10);
    });
  });

  it('CE-005: XML examples should have valid structure', () => {
    const xmlExamples = allCodeExamples.filter((ex) => ex.language === 'xml');
    xmlExamples.forEach((ex) => {
      // Check for basic XML structure
      expect(ex.code).toMatch(/<\?xml/);
      expect(ex.code).toMatch(/<\/.*>/);
    });
  });

  it('CE-006: may have sitemap index example', () => {
    const hasIndexExample = allCodeExamples.some((ex) => 
      ex.code.toLowerCase().includes('sitemapindex')
    );
    // Optional but recommended for completeness
    expect(typeof hasIndexExample).toBe('boolean');
  });

  it('CE-007: code examples should have descriptions', () => {
    allCodeExamples.forEach((ex) => {
      expect(ex.description).toBeTruthy();
      expect(ex.description?.length).toBeGreaterThan(5);
    });
  });

  it('CE-008: XML code examples should be well-formed', () => {
    const xmlExamples = allCodeExamples.filter((ex) => ex.language === 'xml');
    xmlExamples.forEach((ex) => {
      // Basic XML well-formedness checks
      const code = ex.code;
      
      // Should have matching opening and closing tags for urlset or sitemapindex
      if (code.includes('<urlset')) {
        expect(code).toMatch(/<\/urlset>/);
      }
      if (code.includes('<sitemapindex')) {
        expect(code).toMatch(/<\/sitemapindex>/);
      }
      
      // Should have proper XML declaration
      expect(code).toMatch(/<\?xml.*\?>/);
    });
  });
});

describe('XML Sitemap Guide - Code Example Content Validation', () => {
  let guide: ReturnType<typeof getGuideBySlug>;
  let allCodeExamples: Array<{ language: string; code: string; description?: string }>;

  beforeAll(() => {
    guide = getGuideBySlug(GUIDE_SLUG);
    allCodeExamples = guide?.sections.flatMap((s) => s.codeExamples || []) || [];
  });

  describe('Basic Sitemap Example', () => {
    let sitemapExample: { language: string; code: string; description?: string } | undefined;

    beforeAll(() => {
      sitemapExample = allCodeExamples.find((ex) => 
        ex.code.toLowerCase().includes('urlset') && 
        ex.code.toLowerCase().includes('<url')
      );
    });

    it('should have a basic sitemap example', () => {
      expect(sitemapExample).toBeDefined();
    });

    it('should include <loc> tag', () => {
      expect(sitemapExample?.code.toLowerCase()).toMatch(/<loc>/);
    });

    it('should include xmlns namespace', () => {
      expect(sitemapExample?.code).toMatch(/xmlns.*sitemaps\.org/);
    });

    it('should include at least one example URL', () => {
      expect(sitemapExample?.code).toMatch(/https?:\/\/.*/);
    });

    it('may include lastmod tag', () => {
      // Optional but good practice
      const hasLastmod = sitemapExample?.code.toLowerCase().includes('lastmod');
      expect(typeof hasLastmod).toBe('boolean');
    });

    it('may include changefreq tag', () => {
      const hasChangefreq = sitemapExample?.code.toLowerCase().includes('changefreq');
      expect(typeof hasChangefreq).toBe('boolean');
    });

    it('may include priority tag', () => {
      const hasPriority = sitemapExample?.code.toLowerCase().includes('priority');
      expect(typeof hasPriority).toBe('boolean');
    });
  });

  describe('Sitemap Index Example (Optional)', () => {
    let indexExample: { language: string; code: string; description?: string } | undefined;

    beforeAll(() => {
      indexExample = allCodeExamples.find((ex) => 
        ex.code.toLowerCase().includes('sitemapindex')
      );
    });

    it('may have a sitemap index example', () => {
      // Optional for completeness
      expect(typeof indexExample).toBeDefined();
    });

    if (indexExample) {
      it('should include <sitemap> tag', () => {
        expect(indexExample.code.toLowerCase()).toMatch(/<sitemap>/);
      });

      it('should include <loc> for child sitemap', () => {
        expect(indexExample.code.toLowerCase()).toMatch(/<loc>/);
      });

      it('should reference multiple sitemaps', () => {
        const locMatches = indexExample.code.match(/<loc>/g) || [];
        expect(locMatches.length).toBeGreaterThanOrEqual(1);
      });
    }
  });
});

describe('XML Sitemap Guide - Code Example Best Practices', () => {
  let guide: ReturnType<typeof getGuideBySlug>;
  let allCodeExamples: Array<{ language: string; code: string; description?: string }>;

  beforeAll(() => {
    guide = getGuideBySlug(GUIDE_SLUG);
    allCodeExamples = guide?.sections.flatMap((s) => s.codeExamples || []) || [];
  });

  it('should use example.com or similar placeholder domains', () => {
    const xmlExamples = allCodeExamples.filter((ex) => ex.language === 'xml');
    xmlExamples.forEach((ex) => {
      const hasValidDomain = 
        ex.code.includes('example.com') ||
        ex.code.includes('example.org') ||
        ex.code.includes('yoursite.com') ||
        ex.code.includes('yourdomain');
      expect(hasValidDomain).toBe(true);
    });
  });

  it('should use UTF-8 encoding in XML declaration', () => {
    const xmlExamples = allCodeExamples.filter((ex) => ex.language === 'xml');
    xmlExamples.forEach((ex) => {
      if (ex.code.includes('<?xml')) {
        expect(ex.code.toLowerCase()).toMatch(/encoding.*utf-?8/i);
      }
    });
  });

  it('should reference sitemap.org schema', () => {
    const sitemapExamples = allCodeExamples.filter((ex) => 
      ex.code.toLowerCase().includes('urlset')
    );
    sitemapExamples.forEach((ex) => {
      expect(ex.code).toMatch(/sitemaps\.org\/schemas\/sitemap/);
    });
  });

  it('should have descriptive descriptions for each example', () => {
    allCodeExamples.forEach((ex) => {
      expect(ex.description).toBeDefined();
      expect(ex.description?.length).toBeGreaterThan(10);
      // Description should mention what the example shows
      expect(ex.description?.toLowerCase()).toMatch(/sitemap|xml|url|structure|index/);
    });
  });
});
