/**
 * HTTPS & Security Headers Guide Content Tests
 * Task: TASK-20-15 - Write Content for HTTPS/Security Guide
 * 
 * Tests for content structure and quality validation of the HTTPS & Security guide.
 */

import {
  getGuideBySlug,
  getAllGuides,
} from '@/lib/guides/registry';
import { isSEOGuide } from '@/lib/guides/types';

describe('HTTPS & Security Guide - Content Structure', () => {
  let guide: ReturnType<typeof getGuideBySlug>;

  beforeAll(() => {
    guide = getGuideBySlug('https-security');
  });

  describe('Guide Existence and Basic Properties', () => {
    it('HS-001: Guide exists in registry', () => {
      expect(guide).not.toBeNull();
    });

    it('HS-002: Guide has correct title', () => {
      expect(guide).toHaveProperty('title', 'HTTPS & Security Headers');
    });

    it('HS-003: Guide has non-empty summary', () => {
      expect(guide).toHaveProperty('summary');
      expect(guide!.summary.length).toBeGreaterThan(20);
    });

    it('HS-004: Guide category is technical', () => {
      expect(guide).toHaveProperty('category', 'technical');
    });

    it('HS-005: Guide has security icon', () => {
      expect(guide).toHaveProperty('icon', '🔒');
    });
  });

  describe('What is HTTPS Section', () => {
    let whatIsSection: typeof guide extends { sections: infer S } ? S[0] : never;

    beforeAll(() => {
      whatIsSection = guide?.sections.find(
        (s) =>
          s.title.toLowerCase().includes('what') &&
          s.title.toLowerCase().includes('https')
      ) as typeof whatIsSection;
    });

    it('WH-001: Has "What is HTTPS?" section', () => {
      expect(whatIsSection).toBeDefined();
    });

    it('WH-002: Explains SSL/TLS', () => {
      const content = whatIsSection?.content.toLowerCase() || '';
      expect(
        content.includes('ssl') || content.includes('tls')
      ).toBe(true);
    });

    it('WH-003: Explains encryption concept', () => {
      const content = whatIsSection?.content.toLowerCase() || '';
      expect(content.includes('encrypt')).toBe(true);
    });

    it('WH-004: Mentions certificate authority', () => {
      const content = whatIsSection?.content.toLowerCase() || '';
      expect(
        content.includes('certificate') ||
        content.includes('authority') ||
        content.includes('ca ')
      ).toBe(true);
    });

    it('WH-005: Section has valid content length', () => {
      expect(whatIsSection?.content.length).toBeGreaterThan(150);
    });
  });

  describe('SEO Benefits Section', () => {
    let seoSection: typeof guide extends { sections: infer S } ? S[0] : never;

    beforeAll(() => {
      seoSection = guide?.sections.find(
        (s) =>
          s.title.toLowerCase().includes('seo') ||
          (s.title.toLowerCase().includes('benefit') &&
           s.title.toLowerCase().includes('https'))
      ) as typeof seoSection;
    });

    it('SEO-001: Has SEO benefits section', () => {
      expect(seoSection).toBeDefined();
    });

    it('SEO-002: Mentions ranking signal', () => {
      const content = seoSection?.content.toLowerCase() || '';
      expect(
        content.includes('ranking') &&
        (content.includes('signal') || content.includes('factor'))
      ).toBe(true);
    });

    it('SEO-003: Mentions Google\'s HTTPS preference', () => {
      const content = seoSection?.content.toLowerCase() || '';
      expect(
        content.includes('google') && content.includes('https')
      ).toBe(true);
    });

    it('SEO-004: Mentions trust indicators', () => {
      const content = seoSection?.content.toLowerCase() || '';
      expect(
        content.includes('trust') || content.includes('security')
      ).toBe(true);
    });

    it('SEO-005: Mentions browser warnings', () => {
      const content = seoSection?.content.toLowerCase() || '';
      expect(
        content.includes('warning') ||
        content.includes('secure') ||
        content.includes('not secure')
      ).toBe(true);
    });

    it('SEO-006: Section has valid content length', () => {
      expect(seoSection?.content.length).toBeGreaterThan(200);
    });
  });

  describe('SSL Certificate Types Section', () => {
    let sslSection: typeof guide extends { sections: infer S } ? S[0] : never;

    beforeAll(() => {
      sslSection = guide?.sections.find(
        (s) =>
          s.title.toLowerCase().includes('ssl') ||
          s.title.toLowerCase().includes('certificate')
      ) as typeof sslSection;
    });

    it('SSL-001: Has SSL certificate section', () => {
      expect(sslSection).toBeDefined();
    });

    it('SSL-002: Mentions certificate types', () => {
      const content = sslSection?.content.toLowerCase() || '';
      expect(
        content.includes('dv') ||
        content.includes('ov') ||
        content.includes('ev') ||
        content.includes('domain validation') ||
        content.includes('extended validation')
      ).toBe(true);
    });

    it('SSL-003: Mentions Let\'s Encrypt (free)', () => {
      const content = sslSection?.content.toLowerCase() || '';
      expect(
        content.includes("let's encrypt") || content.includes('free ssl')
      ).toBe(true);
    });

    it('SSL-004: Explains validation levels', () => {
      const content = sslSection?.content.toLowerCase() || '';
      expect(
        content.includes('validation') || content.includes('verify')
      ).toBe(true);
    });

    it('SSL-005: Mentions certificate installation', () => {
      const content = sslSection?.content.toLowerCase() || '';
      expect(
        content.includes('install') || content.includes('setup') || content.includes('deploy')
      ).toBe(true);
    });

    it('SSL-006: Section has valid content length', () => {
      expect(sslSection?.content.length).toBeGreaterThan(200);
    });

    it('SSL-007: Free tools constraint met', () => {
      const content = sslSection?.content.toLowerCase() || '';
      expect(
        content.includes('free') ||
        content.includes("let's encrypt") ||
        content.includes('certbot') ||
        content.includes('zero cost')
      ).toBe(true);
    });
  });

  describe('Security Headers Section', () => {
    let headersSection: typeof guide extends { sections: infer S } ? S[0] : never;

    beforeAll(() => {
      headersSection = guide?.sections.find(
        (s) =>
          s.title.toLowerCase().includes('security header') ||
          s.title.toLowerCase().includes('header')
      ) as typeof headersSection;
    });

    it('SH-001: Has security headers section', () => {
      expect(headersSection).toBeDefined();
    });

    it('SH-002: Mentions HSTS', () => {
      const content = headersSection?.content.toLowerCase() || '';
      expect(
        content.includes('hsts') || content.includes('strict-transport-security')
      ).toBe(true);
    });

    it('SH-003: Mentions X-Frame-Options', () => {
      const content = headersSection?.content.toLowerCase() || '';
      expect(content.includes('x-frame-options')).toBe(true);
    });

    it('SH-004: Mentions X-Content-Type-Options', () => {
      const content = headersSection?.content.toLowerCase() || '';
      expect(content.includes('x-content-type-options')).toBe(true);
    });

    it('SH-005: Mentions Content-Security-Policy', () => {
      const content = headersSection?.content.toLowerCase() || '';
      expect(
        content.includes('content-security-policy') || content.includes('csp')
      ).toBe(true);
    });

    it('SH-006: Explains header purpose', () => {
      const content = headersSection?.content.toLowerCase() || '';
      expect(
        content.includes('protect') ||
        content.includes('prevent') ||
        content.includes('attack') ||
        content.includes('security')
      ).toBe(true);
    });

    it('SH-007: Mentions header implementation', () => {
      const content = headersSection?.content.toLowerCase() || '';
      expect(
        content.includes('header set') ||
        content.includes('add_header') ||
        content.includes('implement') ||
        content.includes('configure')
      ).toBe(true);
    });

    it('SH-008: Section has valid content length', () => {
      expect(headersSection?.content.length).toBeGreaterThan(250);
    });
  });

  describe('Mixed Content Issues Section', () => {
    let mixedSection: typeof guide extends { sections: infer S } ? S[0] : never;

    beforeAll(() => {
      mixedSection = guide?.sections.find(
        (s) =>
          s.title.toLowerCase().includes('mixed content') ||
          s.title.toLowerCase().includes('mixed')
      ) as typeof mixedSection;
    });

    it('MC-001: Has mixed content section', () => {
      expect(mixedSection).toBeDefined();
    });

    it('MC-002: Explains mixed content definition', () => {
      const content = mixedSection?.content.toLowerCase() || '';
      expect(
        content.includes('mixed content') && content.includes('http')
      ).toBe(true);
    });

    it('MC-003: Mentions browser blocking', () => {
      const content = mixedSection?.content.toLowerCase() || '';
      expect(
        content.includes('block') || content.includes('warning')
      ).toBe(true);
    });

    it('MC-004: Explains resolution steps', () => {
      const content = mixedSection?.content.toLowerCase() || '';
      expect(
        content.includes('fix') ||
        content.includes('resolve') ||
        content.includes('solution') ||
        content.includes('correct')
      ).toBe(true);
    });

    it('MC-005: Section has valid content length', () => {
      expect(mixedSection?.content.length).toBeGreaterThan(150);
    });
  });

  describe('Best Practices Checklist', () => {
    it('BC-001: Checklist has 5-10 items', () => {
      expect(guide?.checklist.length).toBeGreaterThanOrEqual(5);
      expect(guide?.checklist.length).toBeLessThanOrEqual(10);
    });

    it('BC-002: All items have valid text', () => {
      guide?.checklist.forEach((item) => {
        expect(item.text.length).toBeGreaterThan(0);
      });
    });

    it('BC-003: All items have valid priority', () => {
      const validPriorities = ['critical', 'important', 'recommended'];
      guide?.checklist.forEach((item) => {
        expect(validPriorities).toContain(item.priority);
      });
    });

    it('BC-004: At least one critical item', () => {
      const hasCritical = guide?.checklist.some(
        (item) => item.priority === 'critical'
      );
      expect(hasCritical).toBe(true);
    });

    it('BC-005: Covers key HTTPS/security items', () => {
      const checklistText = guide?.checklist
        .map((item) => item.text.toLowerCase())
        .join(' ') || '';
      
      // Should cover at least 3 of these key topics
      const keyTopics = [
        'https redirect',
        'ssl certificate',
        'hsts',
        'x-frame-options',
        'mixed content',
        'security header',
        'let\'s encrypt',
        'certificate renewal'
      ];
      const coveredTopics = keyTopics.filter((topic) =>
        checklistText.includes(topic)
      );
      expect(coveredTopics.length).toBeGreaterThanOrEqual(3);
    });
  });

  describe('Read Time', () => {
    it('RT-001: Read time is set', () => {
      expect(guide).toHaveProperty('readTime');
      expect(typeof guide!.readTime).toBe('number');
    });

    it('RT-002: Read time in valid range (4-6 minutes)', () => {
      expect(guide!.readTime).toBeGreaterThanOrEqual(4);
      expect(guide!.readTime).toBeLessThanOrEqual(6);
    });

    it('RT-003: Read time matches expected value', () => {
      // Task specifies 4-6 minutes, registry shows 5
      expect(guide!.readTime).toBe(5);
    });
  });

  describe('Related Guides', () => {
    it('RG-001: Has related guides array', () => {
      expect(Array.isArray(guide?.relatedGuides)).toBe(true);
    });

    it('RG-002: Contains mobile-friendly', () => {
      expect(guide?.relatedGuides).toContain('mobile-friendly');
    });

    it('RG-003: Contains canonical-urls', () => {
      expect(guide?.relatedGuides).toContain('canonical-urls');
    });

    it('RG-004: Related guides exist in registry', () => {
      guide?.relatedGuides.forEach((relatedSlug) => {
        const relatedGuide = getGuideBySlug(relatedSlug);
        expect(relatedGuide).not.toBeNull();
      });
    });
  });

  describe('Guide Registry Integration', () => {
    it('GR-001: Guide in registry', () => {
      const allGuides = getAllGuides();
      const found = allGuides.find((g) => g.slug === 'https-security');
      expect(found).toBeDefined();
    });

    it('GR-002: getGuideBySlug returns complete guide', () => {
      expect(guide).toHaveProperty('slug');
      expect(guide).toHaveProperty('title');
      expect(guide).toHaveProperty('summary');
      expect(guide).toHaveProperty('category');
      expect(guide).toHaveProperty('sections');
      expect(guide).toHaveProperty('checklist');
      expect(guide).toHaveProperty('relatedGuides');
    });

    it('GR-003: Guide validates with type guard', () => {
      expect(isSEOGuide(guide!)).toBe(true);
    });
  });

  describe('Free Tools Coverage (CONSTITUTION.md Constraint)', () => {
    it('FT-001: Mentions free SSL option (Let\'s Encrypt)', () => {
      const allContent = guide?.sections
        .map((s) => s.content.toLowerCase())
        .join(' ') || '';
      
      expect(
        allContent.includes("let's encrypt") ||
        allContent.includes('free ssl') ||
        allContent.includes('certbot')
      ).toBe(true);
    });

    it('FT-002: Mentions SSL Labs or similar free testing tool', () => {
      const allContent = guide?.sections
        .map((s) => s.content.toLowerCase())
        .join(' ') || '';
      
      expect(
        allContent.includes('ssl labs') ||
        allContent.includes('ssllabs') ||
        allContent.includes('security headers') ||
        allContent.includes('test') ||
        allContent.includes('check')
      ).toBe(true);
    });
  });

  describe('Content Quality', () => {
    it('CQ-001: All sections have content', () => {
      guide?.sections.forEach((section) => {
        expect(section.content.length).toBeGreaterThan(0);
      });
    });

    it('CQ-002: All sections have titles', () => {
      guide?.sections.forEach((section) => {
        expect(section.title.length).toBeGreaterThan(0);
      });
    });

    it('CQ-003: Has at least 5 sections', () => {
      expect(guide?.sections.length).toBeGreaterThanOrEqual(5);
    });

    it('CQ-004: Has at least 6 sections for comprehensive coverage', () => {
      // Recommended: What is HTTPS, SEO Benefits, SSL Types, Installation, Security Headers, Mixed Content
      expect(guide?.sections.length).toBeGreaterThanOrEqual(6);
    });
  });
});
