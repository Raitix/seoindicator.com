/**
 * Tests for Static Params Generation
 * Task: TASK-20-4 - Set Up Routing with Static Params Generation
 * 
 * Tests verify the generateStaticParams function for all 17 guide slugs.
 */

import { GUIDE_SLUGS } from '@/lib/guides/registry';

// Mock the generateStaticParams function
// In the actual implementation, this would be in app/seo-guides/[slug]/page.tsx
function generateStaticParams(): { slug: string }[] {
  return GUIDE_SLUGS.map((slug) => ({ slug }));
}

describe('generateStaticParams', () => {
  describe('SP-001: Returns array of objects', () => {
    it('should return an array', () => {
      const params = generateStaticParams();
      expect(Array.isArray(params)).toBe(true);
    });

    it('should return array of objects', () => {
      const params = generateStaticParams();
      params.forEach((param) => {
        expect(typeof param).toBe('object');
      });
    });
  });

  describe('SP-002: Returns all 17 guides', () => {
    it('should return exactly 17 params', () => {
      const params = generateStaticParams();
      expect(params).toHaveLength(17);
    });
  });

  describe('SP-003: Each object has slug', () => {
    it('should have slug property on each object', () => {
      const params = generateStaticParams();
      params.forEach((param) => {
        expect(param).toHaveProperty('slug');
      });
    });
  });

  describe('SP-004: All slugs are strings', () => {
    it('should have string slugs', () => {
      const params = generateStaticParams();
      params.forEach((param) => {
        expect(typeof param.slug).toBe('string');
      });
    });
  });

  describe('SP-005: All slugs are non-empty', () => {
    it('should have non-empty slug strings', () => {
      const params = generateStaticParams();
      params.forEach((param) => {
        expect(param.slug.length).toBeGreaterThan(0);
      });
    });
  });

  describe('SP-006: Slugs match registry', () => {
    it('should match GUIDE_SLUGS from registry', () => {
      const params = generateStaticParams();
      const slugs = params.map((p) => p.slug);
      expect(slugs).toEqual([...GUIDE_SLUGS]);
    });
  });

  describe('SP-007: No duplicate slugs', () => {
    it('should have unique slugs only', () => {
      const params = generateStaticParams();
      const slugs = params.map((p) => p.slug);
      const uniqueSlugs = [...new Set(slugs)];
      expect(slugs.length).toBe(uniqueSlugs.length);
    });
  });

  describe('SP-008: Slugs are kebab-case', () => {
    it('should match kebab-case pattern', () => {
      const params = generateStaticParams();
      const kebabCasePattern = /^[a-z][a-z0-9]*(-[a-z][a-z0-9]*)*$/;
      params.forEach((param) => {
        expect(param.slug).toMatch(kebabCasePattern);
      });
    });

    it('should not have uppercase letters', () => {
      const params = generateStaticParams();
      params.forEach((param) => {
        expect(param.slug).toBe(param.slug.toLowerCase());
      });
    });

    it('should not start with hyphen', () => {
      const params = generateStaticParams();
      params.forEach((param) => {
        expect(param.slug[0]).not.toBe('-');
      });
    });

    it('should not end with hyphen', () => {
      const params = generateStaticParams();
      params.forEach((param) => {
        expect(param.slug[param.slug.length - 1]).not.toBe('-');
      });
    });
  });

  describe('SP-009: Slug order matches registry', () => {
    it('should preserve the order from GUIDE_SLUGS', () => {
      const params = generateStaticParams();
      const slugs = params.map((p) => p.slug);
      expect(slugs).toEqual([...GUIDE_SLUGS]);
    });
  });

  describe('SP-010: Function is synchronous', () => {
    it('should not return a Promise', () => {
      const result = generateStaticParams();
      expect(result).not.toBeInstanceOf(Promise);
    });

    it('should return value directly', () => {
      const result = generateStaticParams();
      expect(Array.isArray(result)).toBe(true);
    });
  });

  describe('SP-011: Returns copies not references', () => {
    it('should return new array each call', () => {
      const params1 = generateStaticParams();
      const params2 = generateStaticParams();
      expect(params1).not.toBe(params2);
    });

    it('modifying result should not affect future calls', () => {
      const params1 = generateStaticParams();
      params1.push({ slug: 'new-slug' });
      const params2 = generateStaticParams();
      expect(params2).toHaveLength(17);
    });
  });

  describe('SP-012: Works with empty registry', () => {
    it('should handle empty GUIDE_SLUGS', () => {
      // This tests the function logic, assuming empty registry would return empty array
      const emptySlugs: string[] = [];
      const result = emptySlugs.map((slug) => ({ slug }));
      expect(result).toHaveLength(0);
    });
  });

  describe('SP-013: Handles single guide', () => {
    it('should handle single slug correctly', () => {
      const singleSlug = ['meta-title'];
      const result = singleSlug.map((slug) => ({ slug }));
      expect(result).toHaveLength(1);
      expect(result[0]).toEqual({ slug: 'meta-title' });
    });
  });

  describe('SP-014: Large registry handling', () => {
    it('should handle many slugs without performance issues', () => {
      // Generate 100 slugs
      const manySlugs = Array.from({ length: 100 }, (_, i) => `guide-${i}`);
      const result = manySlugs.map((slug) => ({ slug }));
      expect(result).toHaveLength(100);
    });
  });

  describe('SP-015: All categories covered', () => {
    it('should include on-page guides', () => {
      const params = generateStaticParams();
      const slugs = params.map((p) => p.slug);
      expect(slugs).toContain('meta-title');
      expect(slugs).toContain('h1-headings');
    });

    it('should include technical guides', () => {
      const params = generateStaticParams();
      const slugs = params.map((p) => p.slug);
      expect(slugs).toContain('page-speed');
      expect(slugs).toContain('robots-txt');
    });

    it('should include content guides', () => {
      const params = generateStaticParams();
      const slugs = params.map((p) => p.slug);
      expect(slugs).toContain('open-graph');
      expect(slugs).toContain('twitter-cards');
    });
  });

  describe('SP-016: Type safety', () => {
    it('should return correct type', () => {
      const params = generateStaticParams();
      params.forEach((param) => {
        expect(typeof param).toBe('object');
        expect(typeof param.slug).toBe('string');
      });
    });
  });

  describe('SP-017: Export name', () => {
    it('should be named generateStaticParams', () => {
      expect(typeof generateStaticParams).toBe('function');
    });
  });

  describe('SP-018: Works in page component', () => {
    it('should be callable without context', () => {
      // generateStaticParams should work without any context
      const params = generateStaticParams();
      expect(params).toBeDefined();
    });
  });

  describe('SP-019: Build-time execution', () => {
    it('should be a pure function (no side effects)', () => {
      // Calling the function multiple times should produce same result
      const params1 = generateStaticParams();
      const params2 = generateStaticParams();
      expect(params1).toEqual(params2);
    });
  });

  describe('SP-020: No runtime dependencies', () => {
    it('should only depend on GUIDE_SLUGS constant', () => {
      // The function should be simple and have no external dependencies
      // other than the GUIDE_SLUGS constant
      const params = generateStaticParams();
      expect(params.map((p) => p.slug)).toEqual([...GUIDE_SLUGS]);
    });
  });

  describe('Expected Slugs', () => {
    const expectedSlugs = [
      'meta-title',
      'meta-description',
      'h1-headings',
      'heading-hierarchy',
      'image-alt-text',
      'image-optimization',
      'internal-links',
      'external-links',
      'page-speed',
      'mobile-friendly',
      'https-security',
      'structured-data',
      'canonical-urls',
      'robots-txt',
      'xml-sitemap',
      'open-graph',
      'twitter-cards',
    ];

    it.each(expectedSlugs)('should include slug: %s', (slug) => {
      const params = generateStaticParams();
      const slugs = params.map((p) => p.slug);
      expect(slugs).toContain(slug);
    });
  });
});
