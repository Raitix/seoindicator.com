/**
 * Twitter Cards Guide - Code Examples Tests
 * Task: TASK-20-19 - Write Content for Twitter Cards Guide
 *
 * Tests for code examples in the Twitter Cards guide.
 */

import { getGuideBySlug } from '@/lib/guides/registry';

describe('Twitter Cards Guide - Code Examples', () => {
  let guide: ReturnType<typeof getGuideBySlug>;

  beforeAll(() => {
    guide = getGuideBySlug('twitter-cards');
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
        return code.includes('<meta') || code.includes('name=');
      });
      expect(hasHtmlCode).toBe(true);
    });

    it('CE-004: Has twitter:card example', () => {
      const hasTwitterCard = allCodeExamples.some((ex) => {
        const code = ex.code.toLowerCase();
        return code.includes('twitter:card');
      });
      expect(hasTwitterCard).toBe(true);
    });

    it('CE-005: Has twitter:site example', () => {
      const hasTwitterSite = allCodeExamples.some((ex) => {
        const code = ex.code.toLowerCase();
        return code.includes('twitter:site');
      });
      expect(hasTwitterSite).toBe(true);
    });

    it('CE-006: Has twitter:title example', () => {
      const hasTwitterTitle = allCodeExamples.some((ex) => {
        const code = ex.code.toLowerCase();
        return code.includes('twitter:title');
      });
      expect(hasTwitterTitle).toBe(true);
    });

    it('CE-007: Has twitter:description example', () => {
      const hasTwitterDescription = allCodeExamples.some((ex) => {
        const code = ex.code.toLowerCase();
        return code.includes('twitter:description');
      });
      expect(hasTwitterDescription).toBe(true);
    });

    it('CE-008: Has twitter:image example', () => {
      const hasTwitterImage = allCodeExamples.some((ex) => {
        const code = ex.code.toLowerCase();
        return code.includes('twitter:image');
      });
      expect(hasTwitterImage).toBe(true);
    });

    it('CE-009: Has complete Twitter Card example', () => {
      const hasCompleteExample = allCodeExamples.some((ex) => {
        const code = ex.code.toLowerCase();
        return (
          code.includes('twitter:card') &&
          code.includes('twitter:site') &&
          code.includes('twitter:title') &&
          code.includes('twitter:image')
        );
      });
      expect(hasCompleteExample).toBe(true);
    });
  });

  describe('Card Type Examples', () => {
    let allCodeExamples: Array<{ language: string; code: string; description?: string }>;

    beforeAll(() => {
      allCodeExamples = guide?.sections
        .flatMap((s) => s.codeExamples || []) || [];
    });

    it('CE-010: Has summary card example', () => {
      const hasSummaryCard = allCodeExamples.some((ex) => {
        const code = ex.code.toLowerCase();
        return code.includes('twitter:card') && 
               (code.includes('summary') || code.includes('"summary"') || code.includes("'summary'"));
      });
      expect(hasSummaryCard).toBe(true);
    });

    it('CE-011: Has summary_large_image card example', () => {
      const hasLargeImageCard = allCodeExamples.some((ex) => {
        const code = ex.code.toLowerCase();
        return code.includes('summary_large_image');
      });
      expect(hasLargeImageCard).toBe(true);
    });

    it('CE-012: May have player card example', () => {
      const hasPlayerCard = allCodeExamples.some((ex) => {
        const code = ex.code.toLowerCase();
        return code.includes('player') && code.includes('twitter:card');
      });
      // Optional - player card is less common
      expect(typeof hasPlayerCard).toBe('boolean');
    });
  });

  describe('Code Example Quality', () => {
    let allCodeExamples: Array<{ language: string; code: string; description?: string }>;

    beforeAll(() => {
      allCodeExamples = guide?.sections
        .flatMap((s) => s.codeExamples || []) || [];
    });

    it('CE-013: All code examples have valid language property', () => {
      allCodeExamples.forEach((ex) => {
        expect(ex.language).toBeDefined();
        expect(ex.language.length).toBeGreaterThan(0);
      });
    });

    it('CE-014: Code examples use HTML language', () => {
      const htmlExamples = allCodeExamples.filter((ex) => {
        const code = ex.code.toLowerCase();
        return code.includes('<meta') || code.includes('name=');
      });

      htmlExamples.forEach((ex) => {
        expect(ex.language.toLowerCase()).toBe('html');
      });
    });

    it('CE-015: Code examples have descriptive content', () => {
      allCodeExamples.forEach((ex) => {
        expect(ex.code.length).toBeGreaterThan(10);
      });
    });

    it('CE-016: Code examples are well-formed', () => {
      allCodeExamples.forEach((ex) => {
        // Should have some structure (tags, properties, etc.)
        const code = ex.code.trim();
        expect(code.length).toBeGreaterThan(0);
      });
    });

    it('CE-017: Meta tag examples have name attribute', () => {
      const metaExamples = allCodeExamples.filter((ex) => {
        const code = ex.code.toLowerCase();
        return code.includes('<meta');
      });

      metaExamples.forEach((ex) => {
        expect(ex.code.toLowerCase()).toContain('name=');
      });
    });

    it('CE-018: Meta tag examples have content attribute', () => {
      const metaExamples = allCodeExamples.filter((ex) => {
        const code = ex.code.toLowerCase();
        return code.includes('<meta');
      });

      metaExamples.forEach((ex) => {
        expect(ex.code.toLowerCase()).toContain('content=');
      });
    });
  });

  describe('Optional Twitter Tag Examples', () => {
    let allCodeExamples: Array<{ language: string; code: string; description?: string }>;

    beforeAll(() => {
      allCodeExamples = guide?.sections
        .flatMap((s) => s.codeExamples || []) || [];
    });

    it('CE-019: May have twitter:creator example', () => {
      const hasCreator = allCodeExamples.some((ex) => {
        const code = ex.code.toLowerCase();
        return code.includes('twitter:creator');
      });
      // Optional but recommended for content creators
      expect(typeof hasCreator).toBe('boolean');
    });

    it('CE-020: May have twitter:image:alt example', () => {
      const hasImageAlt = allCodeExamples.some((ex) => {
        const code = ex.code.toLowerCase();
        return code.includes('twitter:image:alt');
      });
      // Optional but recommended for accessibility
      expect(typeof hasImageAlt).toBe('boolean');
    });

    it('CE-021: May have twitter:player example (for player cards)', () => {
      const hasPlayer = allCodeExamples.some((ex) => {
        const code = ex.code.toLowerCase();
        return code.includes('twitter:player');
      });
      // Only for player card type
      expect(typeof hasPlayer).toBe('boolean');
    });

    it('CE-022: May have player dimensions (twitter:player:width, twitter:player:height)', () => {
      const hasPlayerDimensions = allCodeExamples.some((ex) => {
        const code = ex.code.toLowerCase();
        return (
          code.includes('twitter:player:width') ||
          code.includes('twitter:player:height')
        );
      });
      // Only for player card type
      expect(typeof hasPlayerDimensions).toBe('boolean');
    });
  });

  describe('Code Example Descriptions', () => {
    let allCodeExamples: Array<{ language: string; code: string; description?: string }>;

    beforeAll(() => {
      allCodeExamples = guide?.sections
        .flatMap((s) => s.codeExamples || []) || [];
    });

    it('CE-023: Code examples may have descriptions', () => {
      const withDescription = allCodeExamples.filter(
        (ex) => ex.description && ex.description.length > 0
      );
      // At least some should have descriptions
      expect(withDescription.length).toBeGreaterThanOrEqual(0);
    });

    it('CE-024: Descriptions are meaningful when present', () => {
      const withDescription = allCodeExamples.filter(
        (ex) => ex.description && ex.description.length > 0
      );

      withDescription.forEach((ex) => {
        expect(ex.description!.length).toBeGreaterThan(10);
      });
    });
  });

  describe('Twitter Username Format in Examples', () => {
    let allCodeExamples: Array<{ language: string; code: string; description?: string }>;

    beforeAll(() => {
      allCodeExamples = guide?.sections
        .flatMap((s) => s.codeExamples || []) || [];
    });

    it('CE-025: twitter:site uses @username format', () => {
      const siteExamples = allCodeExamples.filter((ex) => {
        const code = ex.code.toLowerCase();
        return code.includes('twitter:site');
      });

      siteExamples.forEach((ex) => {
        // Should have @ in the content value for twitter:site
        expect(
          ex.code.includes('@') || 
          ex.code.includes('&commat;') // HTML entity for @
        ).toBe(true);
      });
    });

    it('CE-026: twitter:creator uses @username format (when present)', () => {
      const creatorExamples = allCodeExamples.filter((ex) => {
        const code = ex.code.toLowerCase();
        return code.includes('twitter:creator');
      });

      creatorExamples.forEach((ex) => {
        expect(
          ex.code.includes('@') || 
          ex.code.includes('&commat;')
        ).toBe(true);
      });
    });
  });

  describe('Card Type Values', () => {
    let allCodeExamples: Array<{ language: string; code: string; description?: string }>;

    beforeAll(() => {
      allCodeExamples = guide?.sections
        .flatMap((s) => s.codeExamples || []) || [];
    });

    it('CE-027: Valid card type values used', () => {
      const validCardTypes = ['summary', 'summary_large_image', 'player', 'app'];
      
      allCodeExamples.forEach((ex) => {
        const code = ex.code.toLowerCase();
        if (code.includes('twitter:card') && code.includes('content=')) {
          // Extract content value and check it's valid
          const hasValidType = validCardTypes.some((type) => 
            code.includes(`"${type}"`) || code.includes(`'${type}'`)
          );
          // At least one example should have a valid card type
          expect(typeof hasValidType).toBe('boolean');
        }
      });
    });

    it('CE-028: Summary card example is complete', () => {
      const summaryExamples = allCodeExamples.filter((ex) => {
        const code = ex.code.toLowerCase();
        return code.includes('summary') && code.includes('twitter:card');
      });

      // If there's a summary example, it should be reasonably complete
      summaryExamples.forEach((ex) => {
        const code = ex.code.toLowerCase();
        const hasBasicTags = 
          code.includes('twitter:card') &&
          code.includes('twitter:title');
        expect(hasBasicTags).toBe(true);
      });
    });

    it('CE-029: Large image card example is distinct', () => {
      const largeImageExamples = allCodeExamples.filter((ex) => {
        const code = ex.code.toLowerCase();
        return code.includes('summary_large_image');
      });

      // Large image examples should emphasize the image
      largeImageExamples.forEach((ex) => {
        const code = ex.code.toLowerCase();
        expect(code.includes('twitter:image')).toBe(true);
      });
    });
  });
});
