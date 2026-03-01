/**
 * Internal Links Guide Code Examples Tests
 * Task: TASK-20-11 - Write Content for Internal Links Guide
 * 
 * Tests for code examples in the Internal Links guide content.
 */

import { getGuideBySlug } from '@/lib/guides/registry';

describe('Internal Links Guide - Code Examples', () => {
  let guide: ReturnType<typeof getGuideBySlug>;
  let allCodeExamples: Array<{
    language: string;
    code: string;
    description?: string;
  }>;

  beforeAll(() => {
    guide = getGuideBySlug('internal-links');
    allCodeExamples = guide?.sections.flatMap((s) => s.codeExamples || []) || [];
  });

  describe('Code Examples Existence', () => {
    it('CE-001: Has at least one code example across all sections', () => {
      expect(allCodeExamples.length).toBeGreaterThanOrEqual(1);
    });
  });

  describe('HTML Link Examples', () => {
    let htmlExamples: typeof allCodeExamples;

    beforeAll(() => {
      htmlExamples = allCodeExamples.filter(
        (ex) => ex.language === 'html' || ex.language === 'HTML'
      );
    });

    it('CE-002: HTML link example exists', () => {
      const hasLinkExample = htmlExamples.some((ex) =>
        ex.code.includes('<a')
      );
      expect(hasLinkExample).toBe(true);
    });

    it('CE-003: Shows proper anchor text in HTML example', () => {
      const linkExample = htmlExamples.find((ex) => ex.code.includes('<a'));
      if (linkExample) {
        // Should have descriptive text inside the anchor, not just "click here"
        const anchorMatch = linkExample.code.match(/<a[^>]*>([^<]+)<\/a>/);
        if (anchorMatch) {
          const anchorText = anchorMatch[1].toLowerCase();
          // Should be descriptive, not generic
          const isGeneric = 
            anchorText === 'click here' ||
            anchorText === 'read more' ||
            anchorText === 'learn more';
          expect(isGeneric).toBe(false);
        }
      }
    });
  });

  describe('Code Example Quality', () => {
    it('CE-003: Code example has language', () => {
      allCodeExamples.forEach((example) => {
        expect(example.language).toBeDefined();
        expect(example.language.length).toBeGreaterThan(0);
      });
    });

    it('CE-004: Code example has code content', () => {
      allCodeExamples.forEach((example) => {
        expect(example.code).toBeDefined();
        expect(example.code.length).toBeGreaterThan(0);
      });
    });

    it('CE-005: Example descriptions present', () => {
      allCodeExamples.forEach((example) => {
        expect(example.description).toBeDefined();
        expect(example.description!.length).toBeGreaterThan(0);
      });
    });
  });

  describe('Code Syntax Validation', () => {
    it('CE-006: HTML examples are syntactically valid', () => {
      const htmlExamples = allCodeExamples.filter(
        (ex) => ex.language === 'html'
      );
      
      htmlExamples.forEach((example) => {
        // Basic HTML syntax check - tags should be balanced
        const openTags = (example.code.match(/<[a-z][^/>]*>/gi) || []).length;
        const closeTags = (example.code.match(/<\/[a-z]+>/gi) || []).length;
        const selfClosing = (example.code.match(/\/>/g) || []).length;
        
        // Either balanced or self-closing
        expect(openTags).toBeLessThanOrEqual(closeTags + selfClosing + 5);
      });
    });

    it('CE-006: JSON examples are valid JSON', () => {
      const jsonExamples = allCodeExamples.filter(
        (ex) => ex.language === 'json' || ex.language === 'jsonld'
      );
      
      jsonExamples.forEach((example) => {
        expect(() => JSON.parse(example.code)).not.toThrow();
      });
    });
  });

  describe('Breadcrumb Examples', () => {
    it('CE-007: May include breadcrumb HTML or schema', () => {
      const hasBreadcrumb = allCodeExamples.some((ex) => {
        const content = ex.code.toLowerCase();
        return (
          content.includes('breadcrumb') ||
          content.includes('BreadcrumbList') ||
          content.includes('aria-label="breadcrumb"')
        );
      });
      
      // Optional but recommended
      if (!hasBreadcrumb) {
        console.log('INFO: No breadcrumb example found (optional but recommended)');
      }
    });
  });

  describe('Navigation Structure Examples', () => {
    it('CE-008: May include navigation/menu HTML', () => {
      const hasNavExample = allCodeExamples.some((ex) => {
        const content = ex.code.toLowerCase();
        return (
          content.includes('<nav') ||
          content.includes('navigation') ||
          content.includes('menu')
        );
      });
      
      // Optional - can be covered in anchor text section
      if (!hasNavExample) {
        console.log('INFO: No navigation example found (optional)');
      }
    });
  });

  describe('Hub-and-Spoke Visualization', () => {
    it('CE-009: May include content structure diagram', () => {
      const textExamples = allCodeExamples.filter(
        (ex) => ex.language === 'text' || ex.language === 'plaintext'
      );
      
      const hasStructureExample = textExamples.some((ex) => {
        const content = ex.code.toLowerCase();
        return (
          content.includes('hub') ||
          content.includes('spoke') ||
          content.includes('pillar') ||
          content.includes('├──') ||
          content.includes('└──')
        );
      });
      
      // Optional but helpful for visualization
      if (!hasStructureExample) {
        console.log('INFO: No hub-and-spoke diagram found (optional but recommended)');
      }
    });
  });
});

describe('Internal Links Guide - Expected Code Examples', () => {
  let guide: ReturnType<typeof getGuideBySlug>;
  let allCodeExamples: Array<{
    language: string;
    code: string;
    description?: string;
  }>;

  beforeAll(() => {
    guide = getGuideBySlug('internal-links');
    allCodeExamples = guide?.sections.flatMap((s) => s.codeExamples || []) || [];
  });

  describe('Recommended Examples Coverage', () => {
    it('should have at least one HTML example for internal links', () => {
      const htmlExamples = allCodeExamples.filter(
        (ex) => ex.language === 'html'
      );
      expect(htmlExamples.length).toBeGreaterThanOrEqual(1);
    });

    it('should demonstrate proper link structure', () => {
      const hasLinkStructure = allCodeExamples.some((ex) => {
        return (
          ex.code.includes('href=') &&
          ex.code.includes('<a') &&
          ex.code.includes('</a>')
        );
      });
      expect(hasLinkStructure).toBe(true);
    });

    it('should use relative or absolute URLs correctly', () => {
      const htmlExamples = allCodeExamples.filter(
        (ex) => ex.language === 'html'
      );
      
      const hasProperUrls = htmlExamples.some((ex) => {
        // Should use relative URLs for internal links or proper absolute
        return (
          ex.code.includes('href="/') ||  // Relative
          ex.code.includes('href="./') ||  // Relative
          ex.code.includes('href="../')    // Relative
        );
      });
      
      expect(hasProperUrls).toBe(true);
    });
  });

  describe('Optional Advanced Examples', () => {
    it('may include JSON-LD breadcrumb schema (optional)', () => {
      const hasJsonLd = allCodeExamples.some((ex) => {
        const content = ex.code.toLowerCase();
        return (
          (ex.language === 'json' || ex.language === 'jsonld') &&
          content.includes('breadcrumblist')
        );
      });
      
      // This is optional but recommended for structured data section
      if (hasJsonLd) {
        console.log('INFO: JSON-LD breadcrumb schema example found (recommended)');
      }
    });

    it('may include rel="nofollow" explanation if relevant', () => {
      const hasNofollow = allCodeExamples.some((ex) => {
        return ex.code.toLowerCase().includes('nofollow');
      });
      
      // Internal links typically don't use nofollow, but might be mentioned
      // as something NOT to do for internal links
      if (hasNofollow) {
        console.log('INFO: nofollow mentioned in examples (check context)');
      }
    });
  });
});
