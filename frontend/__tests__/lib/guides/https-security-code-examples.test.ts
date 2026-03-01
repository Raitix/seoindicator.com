/**
 * HTTPS & Security Headers Guide Code Examples Tests
 * Task: TASK-20-15 - Write Content for HTTPS/Security Guide
 * 
 * Tests for code examples in the HTTPS & Security guide content.
 */

import { getGuideBySlug } from '@/lib/guides/registry';

describe('HTTPS & Security Guide - Code Examples', () => {
  let guide: ReturnType<typeof getGuideBySlug>;
  let allCodeExamples: Array<{
    language: string;
    code: string;
    description?: string;
  }>;

  beforeAll(() => {
    guide = getGuideBySlug('https-security');
    allCodeExamples = guide?.sections.flatMap((s) => s.codeExamples || []) || [];
  });

  describe('Code Examples Existence', () => {
    it('CE-001: Has at least one code example across all sections', () => {
      expect(allCodeExamples.length).toBeGreaterThanOrEqual(1);
    });
  });

  describe('HTTPS Redirect Examples', () => {
    it('CE-002: .htaccess redirect example exists', () => {
      const hasRedirect = allCodeExamples.some((ex) => {
        const code = ex.code.toLowerCase();
        return (
          (ex.language === 'apache' || ex.language === 'htaccess') &&
          (code.includes('rewritecond') || code.includes('rewriteengine')) &&
          code.includes('https')
        );
      });
      expect(hasRedirect).toBe(true);
    });

    it('CE-003: Redirect example uses 301', () => {
      const has301 = allCodeExamples.some((ex) => {
        const code = ex.code.toLowerCase();
        return (
          code.includes('r=301') ||
          code.includes('permanent') ||
          code.includes('301')
        );
      });
      expect(has301).toBe(true);
    });

    it('CE-004: Apache .htaccess example is valid', () => {
      const apacheExamples = allCodeExamples.filter(
        (ex) => ex.language === 'apache' || ex.language === 'htaccess'
      );
      
      const hasValidApache = apacheExamples.some((ex) => {
        const code = ex.code.toLowerCase();
        // Should have RewriteEngine and RewriteCond for redirect
        return (
          code.includes('rewriteengine') &&
          (code.includes('rewritecond') || code.includes('rewriterule'))
        );
      });
      
      // At least one valid Apache example OR no Apache examples (nginx instead)
      expect(
        hasValidApache || apacheExamples.length === 0
      ).toBe(true);
    });
  });

  describe('Security Headers Examples', () => {
    it('CE-005: HSTS header example exists', () => {
      const hasHSTS = allCodeExamples.some((ex) => {
        const code = ex.code.toLowerCase();
        return code.includes('strict-transport-security');
      });
      expect(hasHSTS).toBe(true);
    });

    it('CE-006: X-Frame-Options example exists', () => {
      const hasFrameOptions = allCodeExamples.some((ex) => {
        const code = ex.code.toLowerCase();
        return code.includes('x-frame-options');
      });
      expect(hasFrameOptions).toBe(true);
    });

    it('CE-007: X-Content-Type-Options example exists', () => {
      const hasContentType = allCodeExamples.some((ex) => {
        const code = ex.code.toLowerCase();
        return code.includes('x-content-type-options');
      });
      expect(hasContentType).toBe(true);
    });

    it('CE-008: Security headers use correct values', () => {
      const allCode = allCodeExamples.map((ex) => ex.code.toLowerCase()).join(' ');
      
      // HSTS should have max-age
      if (allCode.includes('strict-transport-security')) {
        expect(allCode.includes('max-age')).toBe(true);
      }
      
      // X-Frame-Options should be SAMEORIGIN or DENY
      if (allCode.includes('x-frame-options')) {
        expect(
          allCode.includes('sameorigin') || allCode.includes('deny')
        ).toBe(true);
      }
      
      // X-Content-Type-Options should be nosniff
      if (allCode.includes('x-content-type-options')) {
        expect(allCode.includes('nosniff')).toBe(true);
      }
    });
  });

  describe('Code Example Quality', () => {
    it('CE-009: Code example has language', () => {
      allCodeExamples.forEach((example) => {
        expect(example.language).toBeDefined();
        expect(example.language.length).toBeGreaterThan(0);
      });
    });

    it('CE-010: Code example has code content', () => {
      allCodeExamples.forEach((example) => {
        expect(example.code).toBeDefined();
        expect(example.code.length).toBeGreaterThan(0);
      });
    });

    it('CE-011: Example descriptions present', () => {
      allCodeExamples.forEach((example) => {
        expect(example.description).toBeDefined();
        expect(example.description!.length).toBeGreaterThan(0);
      });
    });
  });

  describe('Nginx Examples (Optional)', () => {
    let nginxExamples: typeof allCodeExamples;

    beforeAll(() => {
      nginxExamples = allCodeExamples.filter(
        (ex) => ex.language === 'nginx'
      );
    });

    it('CE-012: Nginx example optional but recommended', () => {
      // Optional but good to have for broader coverage
      if (nginxExamples.length === 0) {
        console.log('INFO: No Nginx examples found (optional but recommended)');
      }
    });

    it('CE-013: Nginx examples use correct syntax', () => {
      nginxExamples.forEach((example) => {
        const code = example.code.toLowerCase();
        // Nginx uses add_header for headers
        if (code.includes('header')) {
          expect(code.includes('add_header')).toBe(true);
        }
      });
    });
  });
});

describe('HTTPS & Security Guide - Expected Code Examples', () => {
  let guide: ReturnType<typeof getGuideBySlug>;
  let allCodeExamples: Array<{
    language: string;
    code: string;
    description?: string;
  }>;

  beforeAll(() => {
    guide = getGuideBySlug('https-security');
    allCodeExamples = guide?.sections.flatMap((s) => s.codeExamples || []) || [];
  });

  describe('Required Examples Coverage', () => {
    it('should have at least one Apache/htaccess example', () => {
      const apacheExamples = allCodeExamples.filter(
        (ex) => ex.language === 'apache' || ex.language === 'htaccess'
      );
      expect(apacheExamples.length).toBeGreaterThanOrEqual(1);
    });

    it('should demonstrate HTTPS redirect', () => {
      const hasHttpsRedirect = allCodeExamples.some((ex) => {
        const code = ex.code.toLowerCase();
        return (
          code.includes('https') &&
          (code.includes('rewrite') || code.includes('redirect') || code.includes('return 301'))
        );
      });
      expect(hasHttpsRedirect).toBe(true);
    });

    it('should show security headers configuration', () => {
      const hasSecurityHeaders = allCodeExamples.some((ex) => {
        const code = ex.code.toLowerCase();
        return (
          code.includes('strict-transport-security') ||
          code.includes('x-frame-options') ||
          code.includes('x-content-type-options')
        );
      });
      expect(hasSecurityHeaders).toBe(true);
    });

    it('should show HSTS with proper max-age', () => {
      const hasHSTS = allCodeExamples.some((ex) => {
        const code = ex.code.toLowerCase();
        return (
          code.includes('strict-transport-security') &&
          code.includes('max-age') &&
          code.includes('31536000') // 1 year
        );
      });
      expect(hasHSTS).toBe(true);
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

    it('should use example.com or placeholder domains', () => {
      const hasValidDomain = allCodeExamples.some((ex) => {
        const code = ex.code.toLowerCase();
        return (
          code.includes('example.com') ||
          code.includes('yourdomain.com') ||
          code.includes('example.org') ||
          code.includes('%{http_host}') ||
          code.includes('$server_name') ||
          code.includes('$host')
        );
      });
      
      // Should use placeholders, not real domains
      expect(hasValidDomain || allCodeExamples.length === 0).toBe(true);
    });
  });

  describe('Apache .htaccess Specific Tests', () => {
    let apacheExamples: typeof allCodeExamples;

    beforeAll(() => {
      apacheExamples = allCodeExamples.filter(
        (ex) => ex.language === 'apache' || ex.language === 'htaccess'
      );
    });

    it('should include IfModule wrapper for headers', () => {
      const hasIfModule = apacheExamples.some((ex) => {
        return ex.code.toLowerCase().includes('ifmodule');
      });
      
      // Best practice to wrap in IfModule
      if (!hasIfModule && apacheExamples.length > 0) {
        console.log('INFO: Apache examples should ideally use IfModule wrapper');
      }
    });

    it('should use Header set directive', () => {
      const hasHeaderSet = apacheExamples.some((ex) => {
        const code = ex.code.toLowerCase();
        return code.includes('header set') || code.includes('header always set');
      });
      
      expect(hasHeaderSet || apacheExamples.length === 0).toBe(true);
    });
  });
});

describe('HTTPS & Security Guide - Security Headers Validation', () => {
  let guide: ReturnType<typeof getGuideBySlug>;
  let allCodeExamples: Array<{
    language: string;
    code: string;
    description?: string;
  }>;

  beforeAll(() => {
    guide = getGuideBySlug('https-security');
    allCodeExamples = guide?.sections.flatMap((s) => s.codeExamples || []) || [];
  });

  describe('HSTS Configuration', () => {
    it('should show HSTS with includeSubDomains', () => {
      const hasIncludeSubDomains = allCodeExamples.some((ex) => {
        const code = ex.code.toLowerCase();
        return (
          code.includes('strict-transport-security') &&
          code.includes('includesubdomains')
        );
      });
      expect(hasIncludeSubDomains).toBe(true);
    });

    it('should show HSTS with preload directive', () => {
      const hasPreload = allCodeExamples.some((ex) => {
        const code = ex.code.toLowerCase();
        return (
          code.includes('strict-transport-security') &&
          code.includes('preload')
        );
      });
      
      // Optional but recommended for HSTS preloading
      if (!hasPreload) {
        console.log('INFO: HSTS preload directive recommended for production');
      }
    });

    it('should use at least 1 year max-age for HSTS', () => {
      const hasMaxAge = allCodeExamples.some((ex) => {
        const code = ex.code.toLowerCase();
        return (
          code.includes('strict-transport-security') &&
          (code.includes('31536000') || code.includes('63072000')) // 1 or 2 years
        );
      });
      expect(hasMaxAge).toBe(true);
    });
  });

  describe('X-Frame-Options Configuration', () => {
    it('should recommend SAMEORIGIN or DENY', () => {
      const hasValidFrameOption = allCodeExamples.some((ex) => {
        const code = ex.code.toLowerCase();
        return (
          code.includes('x-frame-options') &&
          (code.includes('sameorigin') || code.includes('deny'))
        );
      });
      expect(hasValidFrameOption).toBe(true);
    });

    it('should not use ALLOW-FROM (deprecated)', () => {
      const hasAllowFrom = allCodeExamples.some((ex) => {
        const code = ex.code.toLowerCase();
        return code.includes('allow-from');
      });
      
      // ALLOW-FROM is deprecated, should not be recommended
      expect(hasAllowFrom).toBe(false);
    });
  });

  describe('X-Content-Type-Options Configuration', () => {
    it('should use nosniff value', () => {
      const hasNosniff = allCodeExamples.some((ex) => {
        const code = ex.code.toLowerCase();
        return (
          code.includes('x-content-type-options') &&
          code.includes('nosniff')
        );
      });
      expect(hasNosniff).toBe(true);
    });
  });

  describe('Additional Security Headers (Recommended)', () => {
    it('may include Referrer-Policy', () => {
      const hasReferrerPolicy = allCodeExamples.some((ex) => {
        const code = ex.code.toLowerCase();
        return code.includes('referrer-policy');
      });
      
      if (!hasReferrerPolicy) {
        console.log('INFO: Referrer-Policy header recommended for privacy');
      }
    });

    it('may include X-XSS-Protection', () => {
      const hasXSSProtection = allCodeExamples.some((ex) => {
        const code = ex.code.toLowerCase();
        return code.includes('x-xss-protection');
      });
      
      if (!hasXSSProtection) {
        console.log('INFO: X-XSS-Protection header optional (legacy browser support)');
      }
    });

    it('may include Content-Security-Policy', () => {
      const hasCSP = allCodeExamples.some((ex) => {
        const code = ex.code.toLowerCase();
        return code.includes('content-security-policy');
      });
      
      if (!hasCSP) {
        console.log('INFO: Content-Security-Policy header recommended for XSS prevention');
      }
    });
  });
});

describe('HTTPS & Security Guide - Mixed Content Examples', () => {
  let guide: ReturnType<typeof getGuideBySlug>;
  let allCodeExamples: Array<{
    language: string;
    code: string;
    description?: string;
  }>;

  beforeAll(() => {
    guide = getGuideBySlug('https-security');
    allCodeExamples = guide?.sections.flatMap((s) => s.codeExamples || []) || [];
  });

  describe('Mixed Content Resolution', () => {
    it('should explain how to fix mixed content', () => {
      const mixedContentSection = guide?.sections.find(
        (s) => s.title.toLowerCase().includes('mixed content')
      );
      
      const content = mixedContentSection?.content.toLowerCase() || '';
      expect(
        content.includes('http://') &&
        (content.includes('https://') || content.includes('https'))
      ).toBe(true);
    });

    it('may include example of mixed content error', () => {
      const hasMixedContentExample = allCodeExamples.some((ex) => {
        const code = ex.code.toLowerCase();
        return code.includes('http://') && code.includes('src');
      });
      
      // Optional - could be in content instead of code example
      if (!hasMixedContentExample) {
        console.log('INFO: Mixed content example could be shown in code or content');
      }
    });
  });
});
