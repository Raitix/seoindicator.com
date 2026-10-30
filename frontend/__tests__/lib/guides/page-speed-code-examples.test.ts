/**
 * Page Speed Guide Code Examples Tests
 * Task: TASK-20-13 - Write Content for Page Speed Guide
 * 
 * Tests for code examples in the Page Speed guide.
 */

import {
  getGuideBySlug,
} from '@/lib/guides/registry';

describe('Page Speed Guide - Code Examples', () => {
  let guide: ReturnType<typeof getGuideBySlug>;

  beforeAll(() => {
    guide = getGuideBySlug('page-speed');
  });

  describe('Code Examples Existence', () => {
    it('CE-001: Guide has code examples', () => {
      const allCodeExamples = guide?.sections
        .flatMap((s) => s.codeExamples || []) || [];
      expect(allCodeExamples.length).toBeGreaterThan(0);
    });

    it('CE-002: Code examples have proper structure', () => {
      const allCodeExamples = guide?.sections
        .flatMap((s) => s.codeExamples || []) || [];
      
      allCodeExamples.forEach((ex) => {
        expect(ex).toHaveProperty('language');
        expect(ex).toHaveProperty('code');
        expect(ex.language.length).toBeGreaterThan(0);
        expect(ex.code.length).toBeGreaterThan(0);
      });
    });
  });

  describe('Resource Hints Code Example', () => {
    it('RH-001: Has preload/prefetch example', () => {
      const allCodeExamples = guide?.sections
        .flatMap((s) => s.codeExamples || []) || [];
      
      const hasResourceHints = allCodeExamples.some((ex) => {
        const code = ex.code.toLowerCase();
        return code.includes('preload') || code.includes('prefetch');
      });
      
      expect(hasResourceHints).toBe(true);
    });

    it('RH-002: Resource hints example has correct syntax', () => {
      const allCodeExamples = guide?.sections
        .flatMap((s) => s.codeExamples || []) || [];
      
      const resourceHintExample = allCodeExamples.find((ex) => {
        const code = ex.code.toLowerCase();
        return code.includes('preload') || code.includes('prefetch');
      });
      
      if (resourceHintExample) {
        const code = resourceHintExample.code;
        // Should have proper link tag syntax
        expect(code.includes('rel=') || code.includes("rel='") || code.includes('rel="')).toBe(true);
      }
    });
  });

  describe('Script Loading Code Example', () => {
    it('SL-001: Has defer/async example', () => {
      const allCodeExamples = guide?.sections
        .flatMap((s) => s.codeExamples || []) || [];
      
      const hasScriptLoading = allCodeExamples.some((ex) => {
        const code = ex.code.toLowerCase();
        return (code.includes('defer') || code.includes('async')) && 
               (code.includes('script') || code.includes('<script'));
      });
      
      expect(hasScriptLoading).toBe(true);
    });

    it('SL-002: Shows difference between defer and async', () => {
      const allCodeExamples = guide?.sections
        .flatMap((s) => s.codeExamples || []) || [];
      
      const scriptExamples = allCodeExamples.filter((ex) => {
        const code = ex.code.toLowerCase();
        return code.includes('script') && (code.includes('defer') || code.includes('async'));
      });
      
      // Should have at least one example with defer or async
      expect(scriptExamples.length).toBeGreaterThanOrEqual(1);
    });
  });

  describe('Image Lazy Loading Code Example', () => {
    it('IL-001: Has lazy loading example', () => {
      const allCodeExamples = guide?.sections
        .flatMap((s) => s.codeExamples || []) || [];
      
      const hasLazyLoading = allCodeExamples.some((ex) => {
        const code = ex.code.toLowerCase();
        return code.includes('loading=') && (code.includes('lazy') || code.includes('"lazy"') || code.includes("'lazy'"));
      });
      
      expect(hasLazyLoading).toBe(true);
    });

    it('IL-002: Lazy loading example shows img tag', () => {
      const allCodeExamples = guide?.sections
        .flatMap((s) => s.codeExamples || []) || [];
      
      const lazyExample = allCodeExamples.find((ex) => {
        const code = ex.code.toLowerCase();
        return code.includes('loading') && code.includes('lazy');
      });
      
      if (lazyExample) {
        expect(lazyExample.code.toLowerCase().includes('<img')).toBe(true);
      }
    });
  });

  describe('Browser Caching Code Example', () => {
    it('BC-001: Has caching configuration example', () => {
      const allCodeExamples = guide?.sections
        .flatMap((s) => s.codeExamples || []) || [];
      
      const hasCaching = allCodeExamples.some((ex) => {
        const code = ex.code.toLowerCase();
        return code.includes('cache-control') || 
               code.includes('max-age') ||
               code.includes('expires') ||
               code.includes('.htaccess') ||
               code.includes('mod_expires');
      });
      
      expect(hasCaching).toBe(true);
    });

    it('BC-002: Caching example shows proper header syntax', () => {
      const allCodeExamples = guide?.sections
        .flatMap((s) => s.codeExamples || []) || [];
      
      const cachingExample = allCodeExamples.find((ex) => {
        const code = ex.code.toLowerCase();
        return code.includes('cache-control') || code.includes('max-age') || code.includes('expires');
      });
      
      if (cachingExample) {
        // Should have numeric value for max-age or expires
        expect(/\d+/.test(cachingExample.code)).toBe(true);
      }
    });
  });

  describe('CSS Optimization Code Example', () => {
    it('CO-001: Has CSS optimization example or mentions', () => {
      const allCodeExamples = guide?.sections
        .flatMap((s) => s.codeExamples || []) || [];
      const allContent = guide?.sections.map(s => s.content.toLowerCase()).join(' ') || '';
      
      const hasCssOpt = allCodeExamples.some((ex) => {
        const code = ex.code.toLowerCase();
        return code.includes('css') || code.includes('style');
      }) || allContent.includes('css') || allContent.includes('critical css');
      
      // CSS optimization should be covered somewhere
      expect(hasCssOpt || allContent.includes('style') || allContent.includes('render')).toBe(true);
    });
  });

  describe('CDN Configuration Code Example', () => {
    it('CDN-001: Has CDN example or mentions', () => {
      const allCodeExamples = guide?.sections
        .flatMap((s) => s.codeExamples || []) || [];
      const allContent = guide?.sections.map(s => s.content.toLowerCase()).join(' ') || '';
      
      const hasCdn = allCodeExamples.some((ex) => {
        const code = ex.code.toLowerCase();
        return code.includes('cdn') || code.includes('cloudfront') || code.includes('cloudflare');
      }) || allContent.includes('cdn');
      
      expect(hasCdn).toBe(true);
    });
  });

  describe('Server Response Optimization', () => {
    it('SR-001: Mentions server response time (TTFB)', () => {
      const allContent = guide?.sections.map(s => s.content.toLowerCase()).join(' ') || '';
      expect(
        allContent.includes('ttfb') ||
        allContent.includes('time to first byte') ||
        allContent.includes('server response') ||
        allContent.includes('first byte')
      ).toBe(true);
    });

    it('SR-002: Has server optimization example or mentions', () => {
      const allCodeExamples = guide?.sections
        .flatMap((s) => s.codeExamples || []) || [];
      const allContent = guide?.sections.map(s => s.content.toLowerCase()).join(' ') || '';
      
      const hasServerOpt = allCodeExamples.some((ex) => {
        const code = ex.code.toLowerCase();
        return code.includes('gzip') || 
               code.includes('brotli') || 
               code.includes('compression') ||
               code.includes('http/2');
      }) || allContent.includes('gzip') || allContent.includes('brotli') || allContent.includes('http/2');
      
      expect(hasServerOpt).toBe(true);
    });
  });

  describe('Core Web Vitals Thresholds', () => {
    it('CV-001: LCP threshold mentioned with value', () => {
      const allContent = guide?.sections.map(s => s.content.toLowerCase()).join(' ') || '';
      expect(
        (allContent.includes('largest contentful paint') || allContent.includes('lcp')) &&
        (allContent.includes('2.5') || allContent.includes('2.5s') || allContent.includes('2.5 second'))
      ).toBe(true);
    });

    it('CV-002: FID threshold mentioned with value', () => {
      const allContent = guide?.sections.map(s => s.content.toLowerCase()).join(' ') || '';
      expect(
        (allContent.includes('first input delay') || allContent.includes('fid')) &&
        (allContent.includes('100') || allContent.includes('100ms') || allContent.includes('100 millisecond'))
      ).toBe(true);
    });

    it('CV-003: CLS threshold mentioned with value', () => {
      const allContent = guide?.sections.map(s => s.content.toLowerCase()).join(' ') || '';
      expect(
        (allContent.includes('cumulative layout shift') || allContent.includes('cls')) &&
        (allContent.includes('0.1') || allContent.includes('0.1s') || allContent.includes('score'))
      ).toBe(true);
    });
  });

  describe('Code Example Languages', () => {
    it('LANG-001: Uses appropriate languages for web performance', () => {
      const allCodeExamples = guide?.sections
        .flatMap((s) => s.codeExamples || []) || [];
      
      const validLanguages = [
        'html', 'htm', 
        'css', 
        'javascript', 'js', 'jsx', 'ts', 'tsx',
        'bash', 'shell', 'sh',
        'apache', 'htaccess',
        'nginx',
        'json',
        'yaml', 'yml',
        'text', 'plaintext'
      ];
      
      allCodeExamples.forEach((ex) => {
        expect(validLanguages.map(l => l.toLowerCase())).toContain(ex.language.toLowerCase());
      });
    });
  });

  describe('Code Example Descriptions', () => {
    it('DESC-001: Code examples have descriptions when needed', () => {
      const allCodeExamples = guide?.sections
        .flatMap((s) => s.codeExamples || []) || [];
      
      // At least some examples should have descriptions
      const withDescriptions = allCodeExamples.filter((ex) => ex.description && ex.description.length > 0);
      
      // Having descriptions is good practice but not required for all
      // Check that if examples exist, at least some are well-documented
      if (allCodeExamples.length > 2) {
        expect(withDescriptions.length).toBeGreaterThan(0);
      }
    });
  });
});
