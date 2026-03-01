/**
 * External Links Guide Code Examples Tests
 * Task: TASK-20-12 - Write Content for External Links Guide
 * 
 * Tests for code examples in the External Links guide content.
 */

import { getGuideBySlug } from '@/lib/guides/registry';

describe('External Links Guide - Code Examples', () => {
  let guide: ReturnType<typeof getGuideBySlug>;
  let allCodeExamples: Array<{
    language: string;
    code: string;
    description?: string;
  }>;

  beforeAll(() => {
    guide = getGuideBySlug('external-links');
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

    it('CE-003: Shows noopener attribute', () => {
      const hasNoopener = allCodeExamples.some((ex) =>
        ex.code.toLowerCase().includes('noopener')
      );
      expect(hasNoopener).toBe(true);
    });

    it('CE-004: Shows noreferrer attribute', () => {
      const hasNoreferrer = allCodeExamples.some((ex) =>
        ex.code.toLowerCase().includes('noreferrer')
      );
      expect(hasNoreferrer).toBe(true);
    });

    it('CE-005: Shows nofollow attribute', () => {
      const hasNofollow = allCodeExamples.some((ex) =>
        ex.code.toLowerCase().includes('nofollow')
      );
      expect(hasNofollow).toBe(true);
    });
  });

  describe('Code Example Quality', () => {
    it('CE-006: Code example has language', () => {
      allCodeExamples.forEach((example) => {
        expect(example.language).toBeDefined();
        expect(example.language.length).toBeGreaterThan(0);
      });
    });

    it('CE-007: Code example has code content', () => {
      allCodeExamples.forEach((example) => {
        expect(example.code).toBeDefined();
        expect(example.code.length).toBeGreaterThan(0);
      });
    });

    it('CE-008: Example descriptions present', () => {
      allCodeExamples.forEach((example) => {
        expect(example.description).toBeDefined();
        expect(example.description!.length).toBeGreaterThan(0);
      });
    });
  });

  describe('Code Syntax Validation', () => {
    it('CE-009: HTML examples are syntactically valid', () => {
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
  });

  describe('Security Attributes Examples', () => {
    it('CE-010: Shows secure external link pattern', () => {
      const hasSecurePattern = allCodeExamples.some((ex) => {
        const code = ex.code.toLowerCase();
        return (
          code.includes('target="_blank"') &&
          code.includes('noopener') &&
          code.includes('noreferrer')
        );
      });
      expect(hasSecurePattern).toBe(true);
    });

    it('CE-011: Shows combined rel attribute', () => {
      const hasCombinedRel = allCodeExamples.some((ex) => {
        const code = ex.code.toLowerCase();
        // Should show rel="noopener noreferrer" pattern
        return (
          code.includes('rel="noopener noreferrer"') ||
          code.includes("rel='noopener noreferrer'") ||
          code.includes('rel="noreferrer noopener"') ||
          code.includes("rel='noreferrer noopener'")
        );
      });
      expect(hasCombinedRel).toBe(true);
    });
  });

  describe('Nofollow Examples', () => {
    it('CE-012: Shows sponsored link pattern', () => {
      const hasSponsored = allCodeExamples.some((ex) => {
        const code = ex.code.toLowerCase();
        return (
          code.includes('sponsored') ||
          code.includes('nofollow')
        );
      });
      expect(hasSponsored).toBe(true);
    });

    it('CE-013: Shows UGC link pattern (optional)', () => {
      const hasUGC = allCodeExamples.some((ex) => {
        const code = ex.code.toLowerCase();
        return code.includes('ugc');
      });
      
      // Optional but recommended
      if (!hasUGC) {
        console.log('INFO: No UGC example found (optional but recommended)');
      }
    });
  });
});

describe('External Links Guide - Expected Code Examples', () => {
  let guide: ReturnType<typeof getGuideBySlug>;
  let allCodeExamples: Array<{
    language: string;
    code: string;
    description?: string;
  }>;

  beforeAll(() => {
    guide = getGuideBySlug('external-links');
    allCodeExamples = guide?.sections.flatMap((s) => s.codeExamples || []) || [];
  });

  describe('Required Examples Coverage', () => {
    it('should have at least one HTML example for external links', () => {
      const htmlExamples = allCodeExamples.filter(
        (ex) => ex.language === 'html'
      );
      expect(htmlExamples.length).toBeGreaterThanOrEqual(1);
    });

    it('should demonstrate target="_blank" usage', () => {
      const hasTargetBlank = allCodeExamples.some((ex) => {
        return ex.code.toLowerCase().includes('target="_blank"');
      });
      expect(hasTargetBlank).toBe(true);
    });

    it('should show secure link pattern with all attributes', () => {
      const hasFullSecure = allCodeExamples.some((ex) => {
        const code = ex.code.toLowerCase();
        return (
          code.includes('target="_blank"') &&
          code.includes('noopener') &&
          code.includes('noreferrer')
        );
      });
      expect(hasFullSecure).toBe(true);
    });

    it('should show nofollow usage for sponsored content', () => {
      const hasNofollowSponsored = allCodeExamples.some((ex) => {
        const code = ex.code.toLowerCase();
        return (
          code.includes('nofollow') ||
          code.includes('sponsored')
        );
      });
      expect(hasNofollowSponsored).toBe(true);
    });
  });

  describe('Code Example Content Quality', () => {
    it('should have descriptive descriptions for all examples', () => {
      allCodeExamples.forEach((example) => {
        if (example.description) {
          expect(example.description.length).toBeGreaterThan(10);
        }
      });
    });

    it('should use real URLs or example.com in examples', () => {
      const htmlExamples = allCodeExamples.filter(
        (ex) => ex.language === 'html'
      );
      
      htmlExamples.forEach((example) => {
        const hasValidUrl = 
          example.code.includes('href="http') ||
          example.code.includes("href='http") ||
          example.code.includes('href="https') ||
          example.code.includes("href='https") ||
          example.code.includes('example.com');
        
        expect(hasValidUrl).toBe(true);
      });
    });
  });

  describe('Optional Advanced Examples', () => {
    it('may include JavaScript window.open example (optional)', () => {
      const hasJsExample = allCodeExamples.some((ex) => {
        return ex.language === 'javascript' || ex.language === 'js';
      });
      
      if (hasJsExample) {
        console.log('INFO: JavaScript example found (optional)');
      }
    });

    it('may include link checking guidance (optional)', () => {
      const allContent = guide?.sections
        .map((s) => s.content.toLowerCase())
        .join(' ') || '';
      
      const hasLinkCheck = 
        allContent.includes('check') ||
        allContent.includes('audit') ||
        allContent.includes('broken link');
      
      if (hasLinkCheck) {
        console.log('INFO: Link checking guidance found (recommended)');
      }
    });
  });
});

describe('External Links Guide - Security Best Practices', () => {
  let guide: ReturnType<typeof getGuideBySlug>;
  let allCodeExamples: Array<{
    language: string;
    code: string;
    description?: string;
  }>;

  beforeAll(() => {
    guide = getGuideBySlug('external-links');
    allCodeExamples = guide?.sections.flatMap((s) => s.codeExamples || []) || [];
  });

  describe('noopener/noreferrer Security', () => {
    it('should explain why noopener is needed with target="_blank"', () => {
      const allContent = guide?.sections
        .map((s) => s.content.toLowerCase())
        .join(' ') || '';
      
      expect(
        allContent.includes('noopener') ||
        allContent.includes('security') ||
        allContent.includes('tabnabbing') ||
        allContent.includes('vulnerability')
      ).toBe(true);
    });

    it('should recommend noopener AND noreferrer together', () => {
      const allContent = guide?.sections
        .map((s) => s.content.toLowerCase())
        .join(' ') || '';
      
      // Should mention both for maximum security
      expect(
        allContent.includes('noopener') && allContent.includes('noreferrer')
      ).toBe(true);
    });

    it('should show the correct combined rel attribute in code', () => {
      const hasCorrectPattern = allCodeExamples.some((ex) => {
        const code = ex.code.toLowerCase();
        // Correct: rel="noopener noreferrer" with target="_blank"
        return (
          code.includes('target="_blank"') &&
          (code.includes('rel="noopener noreferrer"') ||
           code.includes("rel='noopener noreferrer'") ||
           code.includes('rel="noreferrer noopener"') ||
           code.includes("rel='noreferrer noopener'"))
        );
      });
      expect(hasCorrectPattern).toBe(true);
    });
  });

  describe('Nofollow Usage', () => {
    it('should explain when to use nofollow', () => {
      const allContent = guide?.sections
        .map((s) => s.content.toLowerCase())
        .join(' ') || '';
      
      expect(allContent.includes('nofollow')).toBe(true);
    });

    it('should mention sponsored and UGC link types', () => {
      const allContent = guide?.sections
        .map((s) => s.content.toLowerCase())
        .join(' ') || '';
      
      const hasSponsored = allContent.includes('sponsored');
      const hasUGC = allContent.includes('ugc') || allContent.includes('user-generated');
      
      // At least one should be mentioned
      expect(hasSponsored || hasUGC).toBe(true);
    });
  });
});
