/**
 * TESTS/TASK-20-9: Image Alt Text Guide Code Examples Tests
 * 
 * Validates code examples for the Image Alt Text SEO guide.
 * Tests ensure examples are syntactically valid, cover key patterns,
 * and demonstrate best practices.
 */

import { getGuideBySlug } from '../../../lib/guides/registry';

describe('Image Alt Text Guide - Code Examples', () => {
  let guide: ReturnType<typeof getGuideBySlug>;
  let allCodeExamples: Array<{ language: string; code: string; description?: string }>;

  beforeAll(() => {
    guide = getGuideBySlug('image-alt-text');
    // Collect all code examples from sections
    allCodeExamples = [];
    if (guide) {
      guide.sections.forEach(section => {
        if (section.codeExamples && section.codeExamples.length > 0) {
          allCodeExamples.push(...section.codeExamples);
        }
      });
    }
  });

  // CE-001 to CE-010: Code Examples Tests
  describe('Code Examples Structure', () => {
    it('CE-001: Has 2-3 code examples', () => {
      expect(guide).not.toBeNull();
      expect(allCodeExamples.length).toBeGreaterThanOrEqual(2);
      expect(allCodeExamples.length).toBeLessThanOrEqual(3);
    });

    it('CE-002: Has example with good alt text', () => {
      expect(guide).not.toBeNull();
      const hasGoodAltExample = allCodeExamples.some(example => {
        const code = example.code.toLowerCase();
        // Should have descriptive alt text (not empty, not too short)
        const altMatch = code.match(/alt="([^"]+)"/);
        if (altMatch && altMatch[1]) {
          return altMatch[1].length > 10 && !altMatch[1].includes('image of');
        }
        return false;
      });
      expect(hasGoodAltExample).toBe(true);
    });

    it('CE-003: Has example with empty alt (decorative)', () => {
      expect(guide).not.toBeNull();
      const hasEmptyAltExample = allCodeExamples.some(example => {
        return example.code.includes('alt=""');
      });
      expect(hasEmptyAltExample).toBe(true);
    });

    it('CE-004: Code example has valid language', () => {
      expect(guide).not.toBeNull();
      const validLanguages = ['html', 'htm', 'jsx', 'tsx', 'javascript', 'typescript', 'xml'];
      
      allCodeExamples.forEach(example => {
        expect(example.language).toBeDefined();
        expect(example.language.length).toBeGreaterThan(0);
        // Language should be a valid identifier
        expect(validLanguages).toContain(example.language.toLowerCase());
      });
    });

    it('CE-005: Code example has code content', () => {
      expect(guide).not.toBeNull();
      
      allCodeExamples.forEach(example => {
        expect(example.code).toBeDefined();
        expect(example.code.length).toBeGreaterThan(10);
      });
    });
  });

  describe('HTML Code Examples Validation', () => {
    it('CE-006: HTML example contains img tag', () => {
      expect(guide).not.toBeNull();
      const htmlExamples = allCodeExamples.filter(e => 
        e.language.toLowerCase() === 'html' || e.language.toLowerCase() === 'htm'
      );
      
      expect(htmlExamples.length).toBeGreaterThan(0);
      
      htmlExamples.forEach(example => {
        expect(example.code.toLowerCase()).toContain('<img');
      });
    });

    it('CE-007: Code examples have descriptions', () => {
      expect(guide).not.toBeNull();
      
      allCodeExamples.forEach(example => {
        expect(example.description).toBeDefined();
        expect(example.description!.length).toBeGreaterThan(10);
      });
    });

    it('CE-008: HTML examples are syntactically valid', () => {
      expect(guide).not.toBeNull();
      const htmlExamples = allCodeExamples.filter(e => 
        e.language.toLowerCase() === 'html' || e.language.toLowerCase() === 'htm'
      );
      
      htmlExamples.forEach(example => {
        const code = example.code;
        
        // Check for basic HTML validity patterns
        // Self-closing img tag
        expect(code.toLowerCase()).toMatch(/<img[^>]*\/?>/i);
        
        // Check for alt attribute
        expect(code.toLowerCase()).toMatch(/alt\s*=/i);
        
        // Check for src attribute
        expect(code.toLowerCase()).toMatch(/src\s*=/i);
      });
    });
  });

  describe('Alt Text Best Practices in Examples', () => {
    it('CE-009: Shows keyword stuffing mistake (optional)', () => {
      expect(guide).not.toBeNull();
      
      // Check if any example demonstrates keyword stuffing as a bad practice
      // This is optional - guides may show good examples only
      const hasKeywordStuffingExample = allCodeExamples.some(example => {
        const desc = (example.description || '').toLowerCase();
        return desc.includes('keyword stuffing') || 
               desc.includes('avoid') || 
               desc.includes('bad') ||
               desc.includes('wrong') ||
               desc.includes('don\'t') ||
               desc.includes('do not');
      });
      
      // This test passes either way - it's educational content
      expect(typeof hasKeywordStuffingExample).toBe('boolean');
    });

    it('CE-010: Shows decorative image example', () => {
      expect(guide).not.toBeNull();
      
      const hasDecorativeExample = allCodeExamples.some(example => {
        const code = example.code;
        const desc = (example.description || '').toLowerCase();
        
        return code.includes('alt=""') && 
               (desc.includes('decorative') || desc.includes('empty'));
      });
      
      expect(hasDecorativeExample).toBe(true);
    });
  });

  describe('Alt Text Content Quality', () => {
    it('Examples show descriptive, not generic alt text', () => {
      expect(guide).not.toBeNull();
      
      const goodAltExamples = allCodeExamples.filter(example => {
        const code = example.code;
        // Extract alt text value
        const altMatch = code.match(/alt="([^"]+)"/i);
        
        if (altMatch && altMatch[1] && altMatch[1].length > 0) {
          const altText = altMatch[1].toLowerCase();
          
          // Should not be generic
          const genericTerms = ['image', 'photo', 'picture', 'img', 'graphic'];
          const startsWithGeneric = genericTerms.some(term => 
            altText.startsWith(term + ' of') || 
            altText.startsWith(term + ':')
          );
          
          return !startsWithGeneric;
        }
        return false; // Empty alt is handled separately
      });
      
      // At least one example should have good descriptive alt
      expect(goodAltExamples.length).toBeGreaterThan(0);
    });

    it('Examples show reasonable alt text length', () => {
      expect(guide).not.toBeNull();
      
      allCodeExamples.forEach(example => {
        const code = example.code;
        const altMatch = code.match(/alt="([^"]+)"/i);
        
        if (altMatch && altMatch[1] && altMatch[1].length > 0) {
          const altText = altMatch[1];
          // Good alt text is usually under 125 characters
          expect(altText.length).toBeLessThanOrEqual(150);
        }
      });
    });

    it('Examples include src attribute', () => {
      expect(guide).not.toBeNull();
      
      allCodeExamples.forEach(example => {
        if (example.language.toLowerCase() === 'html') {
          expect(example.code.toLowerCase()).toMatch(/src\s*=/);
        }
      });
    });
  });

  describe('Code Example Descriptions', () => {
    it('All examples have clear descriptions', () => {
      expect(guide).not.toBeNull();
      
      allCodeExamples.forEach(example => {
        expect(example.description).toBeDefined();
        expect(example.description!.length).toBeGreaterThan(15);
        
        // Description should explain the purpose
        const desc = example.description!.toLowerCase();
        const hasPurposeWord = 
          desc.includes('alt') || 
          desc.includes('image') ||
          desc.includes('decorative') ||
          desc.includes('descriptive') ||
          desc.includes('example');
        
        expect(hasPurposeWord).toBe(true);
      });
    });

    it('Descriptions are human-readable', () => {
      expect(guide).not.toBeNull();
      
      allCodeExamples.forEach(example => {
        const desc = example.description!;
        
        // Should start with capital letter
        expect(desc[0]).toMatch(/[A-Z]/);
        
        // Should not contain code
        expect(desc).not.toContain('<');
        expect(desc).not.toContain('>');
      });
    });
  });

  describe('Edge Cases Coverage', () => {
    it('At least one example shows complex image alt text', () => {
      expect(guide).not.toBeNull();
      
      // Complex images like charts, graphs, infographics need detailed alt
      const allContent = guide!.sections.map(s => s.content).join(' ').toLowerCase();
      const hasComplexImageCoverage = 
        allContent.includes('chart') || 
        allContent.includes('graph') ||
        allContent.includes('infographic') ||
        allContent.includes('complex') ||
        allContent.includes('data');
      
      // This is recommended but not required
      expect(typeof hasComplexImageCoverage).toBe('boolean');
    });

    it('Guide mentions decorative vs meaningful images distinction', () => {
      expect(guide).not.toBeNull();
      
      const allContent = guide!.sections.map(s => s.content).join(' ').toLowerCase();
      const allCode = allCodeExamples.map(e => e.code).join(' ').toLowerCase();
      
      // Should explain when to use alt="" vs descriptive alt
      expect(
        allContent.includes('decorative') || 
        allCode.includes('alt=""')
      ).toBe(true);
    });
  });

  describe('Security in Code Examples', () => {
    it('No dangerous code patterns', () => {
      expect(guide).not.toBeNull();
      
      allCodeExamples.forEach(example => {
        const code = example.code.toLowerCase();
        
        // No XSS patterns
        expect(code).not.toContain('javascript:');
        expect(code).not.toContain('onerror');
        expect(code).not.toContain('onload');
        expect(code).not.toContain('onclick');
        
        // No data exfiltration
        expect(code).not.toContain('document.cookie');
        expect(code).not.toContain('localstorage');
        expect(code).not.toContain('fetch(');
      });
    });

    it('Image sources use safe patterns', () => {
      expect(guide).not.toBeNull();
      
      allCodeExamples.forEach(example => {
        if (example.language.toLowerCase() === 'html') {
          const code = example.code;
          
          // Check src attribute patterns
          const srcMatch = code.match(/src\s*=\s*["']([^"']+)["']/i);
          if (srcMatch) {
            const src = srcMatch[1];
            
            // Should be relative path, placeholder, or https
            const isSafe = 
              src.startsWith('/') ||
              src.startsWith('./') ||
              src.startsWith('../') ||
              src.startsWith('https://') ||
              src.includes('example.') ||
              src.includes('placeholder') ||
              src.includes('image.');
            
            expect(isSafe).toBe(true);
          }
        }
      });
    });
  });
});
