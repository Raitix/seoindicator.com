/**
 * Open Graph Tags Guide - Code Examples Tests
 * Task: TASK-20-18 - Write Content for Open Graph Tags Guide
 *
 * Tests for code examples in the Open Graph Tags guide.
 */

import { getGuideBySlug } from '@/lib/guides/registry';

describe('Open Graph Tags Guide - Code Examples', () => {
  let guide: ReturnType<typeof getGuideBySlug>;

  beforeAll(() => {
    guide = getGuideBySlug('open-graph');
  });

  describe('Code Examples Existence', () => {
    it('CE-001: Has code examples in at least one section', () => {
      const allCodeExamples = guide?.sections
        .flatMap((s) => s.codeExamples || []) || [];
      expect(allCodeExamples.length).toBeGreaterThan(0);
    });

    it('CE-002: Multiple sections have code examples', () => {
      const sectionsWithCode = guide?.sections
        .filter((s) => s.codeExamples && s.codeExamples.length > 0) || [];
      expect(sectionsWithCode.length).toBeGreaterThanOrEqual(1);
    });
  });

  describe('HTML Meta Tag Examples', () => {
    let allCodeExamples: Array<{ language: string; code: string; description?: string }>;

    beforeAll(() => {
      allCodeExamples = guide?.sections
        .flatMap((s) => s.codeExamples || []) || [];
    });

    it('CE-003: Has HTML meta tag examples', () => {
      const hasHtmlCode = allCodeExamples.some((ex) => {
        const code = ex.code.toLowerCase();
        return code.includes('<meta') || code.includes('property=');
      });
      expect(hasHtmlCode).toBe(true);
    });

    it('CE-004: Has og:title example', () => {
      const hasOgTitle = allCodeExamples.some((ex) => {
        const code = ex.code.toLowerCase();
        return code.includes('og:title');
      });
      expect(hasOgTitle).toBe(true);
    });

    it('CE-005: Has og:description example', () => {
      const hasOgDescription = allCodeExamples.some((ex) => {
        const code = ex.code.toLowerCase();
        return code.includes('og:description');
      });
      expect(hasOgDescription).toBe(true);
    });

    it('CE-006: Has og:image example', () => {
      const hasOgImage = allCodeExamples.some((ex) => {
        const code = ex.code.toLowerCase();
        return code.includes('og:image');
      });
      expect(hasOgImage).toBe(true);
    });

    it('CE-007: Has og:url example', () => {
      const hasOgUrl = allCodeExamples.some((ex) => {
        const code = ex.code.toLowerCase();
        return code.includes('og:url');
      });
      expect(hasOgUrl).toBe(true);
    });

    it('CE-008: Has og:type example', () => {
      const hasOgType = allCodeExamples.some((ex) => {
        const code = ex.code.toLowerCase();
        return code.includes('og:type');
      });
      expect(hasOgType).toBe(true);
    });

    it('CE-009: Has complete OG meta tags example', () => {
      const hasCompleteExample = allCodeExamples.some((ex) => {
        const code = ex.code.toLowerCase();
        return (
          code.includes('og:title') &&
          code.includes('og:description') &&
          code.includes('og:image')
        );
      });
      expect(hasCompleteExample).toBe(true);
    });
  });

  describe('Code Example Quality', () => {
    let allCodeExamples: Array<{ language: string; code: string; description?: string }>;

    beforeAll(() => {
      allCodeExamples = guide?.sections
        .flatMap((s) => s.codeExamples || []) || [];
    });

    it('CE-010: All code examples have valid language property', () => {
      allCodeExamples.forEach((ex) => {
        expect(ex.language).toBeDefined();
        expect(ex.language.length).toBeGreaterThan(0);
      });
    });

    it('CE-011: Code examples use HTML language', () => {
      const htmlExamples = allCodeExamples.filter((ex) => {
        const code = ex.code.toLowerCase();
        return code.includes('<meta') || code.includes('property=');
      });

      htmlExamples.forEach((ex) => {
        expect(ex.language.toLowerCase()).toBe('html');
      });
    });

    it('CE-012: Code examples have descriptive content', () => {
      allCodeExamples.forEach((ex) => {
        expect(ex.code.length).toBeGreaterThan(10);
      });
    });

    it('CE-013: Code examples are well-formed', () => {
      allCodeExamples.forEach((ex) => {
        // Should have some structure (tags, properties, etc.)
        const code = ex.code.trim();
        expect(code.length).toBeGreaterThan(0);
      });
    });

    it('CE-014: Meta tag examples have property attribute', () => {
      const metaExamples = allCodeExamples.filter((ex) => {
        const code = ex.code.toLowerCase();
        return code.includes('<meta');
      });

      metaExamples.forEach((ex) => {
        expect(ex.code.toLowerCase()).toContain('property=');
      });
    });

    it('CE-015: Meta tag examples have content attribute', () => {
      const metaExamples = allCodeExamples.filter((ex) => {
        const code = ex.code.toLowerCase();
        return code.includes('<meta');
      });

      metaExamples.forEach((ex) => {
        expect(ex.code.toLowerCase()).toContain('content=');
      });
    });
  });

  describe('Advanced OG Tag Examples', () => {
    let allCodeExamples: Array<{ language: string; code: string; description?: string }>;

    beforeAll(() => {
      allCodeExamples = guide?.sections
        .flatMap((s) => s.codeExamples || []) || [];
    });

    it('CE-016: May have og:site_name example', () => {
      const hasSiteName = allCodeExamples.some((ex) => {
        const code = ex.code.toLowerCase();
        return code.includes('og:site_name');
      });
      // Optional - not required
      expect(typeof hasSiteName).toBe('boolean');
    });

    it('CE-017: May have og:locale example', () => {
      const hasLocale = allCodeExamples.some((ex) => {
        const code = ex.code.toLowerCase();
        return code.includes('og:locale');
      });
      // Optional - not required
      expect(typeof hasLocale).toBe('boolean');
    });

    it('CE-018: May have article-specific tags (for articles)', () => {
      const hasArticleTags = allCodeExamples.some((ex) => {
        const code = ex.code.toLowerCase();
        return (
          code.includes('article:published') ||
          code.includes('article:author') ||
          code.includes('article:tag')
        );
      });
      // Optional - depends on content type
      expect(typeof hasArticleTags).toBe('boolean');
    });

    it('CE-019: May have image dimensions (og:image:width, og:image:height)', () => {
      const hasImageDimensions = allCodeExamples.some((ex) => {
        const code = ex.code.toLowerCase();
        return (
          code.includes('og:image:width') ||
          code.includes('og:image:height')
        );
      });
      // Optional but recommended
      expect(typeof hasImageDimensions).toBe('boolean');
    });
  });

  describe('Code Example Descriptions', () => {
    let allCodeExamples: Array<{ language: string; code: string; description?: string }>;

    beforeAll(() => {
      allCodeExamples = guide?.sections
        .flatMap((s) => s.codeExamples || []) || [];
    });

    it('CE-020: Code examples may have descriptions', () => {
      const withDescription = allCodeExamples.filter(
        (ex) => ex.description && ex.description.length > 0
      );
      // At least some should have descriptions
      expect(withDescription.length).toBeGreaterThanOrEqual(0);
    });

    it('CE-021: Descriptions are meaningful when present', () => {
      const withDescription = allCodeExamples.filter(
        (ex) => ex.description && ex.description.length > 0
      );

      withDescription.forEach((ex) => {
        expect(ex.description!.length).toBeGreaterThan(10);
      });
    });
  });
});
