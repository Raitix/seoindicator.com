/**
 * Page Speed Guide Content Tests
 * Task: TASK-20-13 - Write Content for Page Speed Guide
 * 
 * Tests for content structure and quality validation of the Page Speed guide.
 */

import {
  getGuideBySlug,
  getAllGuides,
} from '@/lib/guides/registry';
import { isSEOGuide } from '@/lib/guides/types';

describe('Page Speed Guide - Content Structure', () => {
  let guide: ReturnType<typeof getGuideBySlug>;

  beforeAll(() => {
    guide = getGuideBySlug('page-speed');
  });

  describe('Guide Existence and Basic Properties', () => {
    it('PS-001: Guide exists in registry', () => {
      expect(guide).not.toBeNull();
    });

    it('PS-002: Guide has correct title', () => {
      expect(guide).toHaveProperty('title', 'Page Speed Optimization');
    });

    it('PS-003: Guide has non-empty summary', () => {
      expect(guide).toHaveProperty('summary');
      expect(guide!.summary.length).toBeGreaterThan(20);
    });

    it('PS-004: Guide category is technical', () => {
      expect(guide).toHaveProperty('category', 'technical');
    });
  });

  describe('What is Page Speed Section', () => {
    let whatIsSection: typeof guide extends { sections: infer S } ? S[0] : never;

    beforeAll(() => {
      whatIsSection = guide?.sections.find(
        (s) =>
          s.title.toLowerCase().includes('what') &&
          s.title.toLowerCase().includes('page speed')
      ) as typeof whatIsSection;
    });

    it('WI-001: Has "What is Page Speed?" section', () => {
      expect(whatIsSection).toBeDefined();
    });

    it('WI-002: Section explains page speed concept', () => {
      const content = whatIsSection?.content.toLowerCase() || '';
      expect(
        content.includes('load') &&
        (content.includes('time') || content.includes('speed'))
      ).toBe(true);
    });

    it('WI-003: Mentions page load time metrics', () => {
      const content = whatIsSection?.content.toLowerCase() || '';
      expect(
        content.includes('first contentful paint') ||
        content.includes('fcp') ||
        content.includes('largest contentful paint') ||
        content.includes('lcp') ||
        content.includes('time to interactive') ||
        content.includes('tti') ||
        content.includes('load time')
      ).toBe(true);
    });

    it('WI-004: Section has valid content length', () => {
      expect(whatIsSection?.content.length).toBeGreaterThan(150);
    });
  });

  describe('Core Web Vitals Section', () => {
    let cwvSection: typeof guide extends { sections: infer S } ? S[0] : never;

    beforeAll(() => {
      cwvSection = guide?.sections.find(
        (s) =>
          s.title.toLowerCase().includes('core web vital') ||
          s.title.toLowerCase().includes('web vital')
      ) as typeof cwvSection;
    });

    it('CWV-001: Has Core Web Vitals section', () => {
      expect(cwvSection).toBeDefined();
    });

    it('CWV-002: Explains LCP (Largest Contentful Paint)', () => {
      const content = cwvSection?.content.toLowerCase() || '';
      expect(
        (content.includes('largest contentful paint') || content.includes('lcp')) &&
        (content.includes('2.5') || content.includes('second') || content.includes('threshold'))
      ).toBe(true);
    });

    it('CWV-003: Explains FID (First Input Delay)', () => {
      const content = cwvSection?.content.toLowerCase() || '';
      expect(
        (content.includes('first input delay') || content.includes('fid')) &&
        (content.includes('100') || content.includes('millisecond') || content.includes('ms'))
      ).toBe(true);
    });

    it('CWV-004: Explains CLS (Cumulative Layout Shift)', () => {
      const content = cwvSection?.content.toLowerCase() || '';
      expect(
        (content.includes('cumulative layout shift') || content.includes('cls')) &&
        (content.includes('0.1') || content.includes('shift') || content.includes('layout'))
      ).toBe(true);
    });

    it('CWV-005: Mentions Google ranking impact', () => {
      const content = cwvSection?.content.toLowerCase() || '';
      expect(
        content.includes('google') ||
        content.includes('ranking') ||
        content.includes('search') ||
        content.includes('algorithm')
      ).toBe(true);
    });

    it('CWV-006: Mentions INP (Interaction to Next Paint) or future metrics', () => {
      const allContent = guide?.sections
        .map((s) => s.content.toLowerCase())
        .join(' ') || '';
      expect(
        allContent.includes('inp') ||
        allContent.includes('interaction to next paint') ||
        allContent.includes('metric') ||
        allContent.includes('vital')
      ).toBe(true);
    });

    it('CWV-007: Section has valid content length', () => {
      expect(cwvSection?.content.length).toBeGreaterThan(300);
    });
  });

  describe('Why Speed Matters Section', () => {
    let whySection: typeof guide extends { sections: infer S } ? S[0] : never;

    beforeAll(() => {
      whySection = guide?.sections.find(
        (s) =>
          (s.title.toLowerCase().includes('why') &&
           (s.title.toLowerCase().includes('matter') || s.title.toLowerCase().includes('important'))) ||
          s.title.toLowerCase().includes('impact') ||
          s.title.toLowerCase().includes('benefit')
      ) as typeof whySection;
    });

    it('WM-001: Has "Why Speed Matters" section', () => {
      expect(whySection).toBeDefined();
    });

    it('WM-002: Discusses ranking impact', () => {
      const content = whySection?.content.toLowerCase() || '';
      expect(
        content.includes('ranking') ||
        content.includes('seo') ||
        content.includes('search engine') ||
        content.includes('google')
      ).toBe(true);
    });

    it('WM-003: Discusses user experience', () => {
      const content = whySection?.content.toLowerCase() || '';
      expect(
        content.includes('user experience') ||
        content.includes('ux') ||
        content.includes('bounce') ||
        content.includes('engagement')
      ).toBe(true);
    });

    it('WM-004: Discusses conversion impact', () => {
      const content = whySection?.content.toLowerCase() || '';
      expect(
        content.includes('conversion') ||
        content.includes('revenue') ||
        content.includes('sale') ||
        content.includes('business')
      ).toBe(true);
    });

    it('WM-005: Includes statistics or data', () => {
      const content = whySection?.content.toLowerCase() || '';
      // Should have some numbers/stats about impact
      expect(
        /\d+/.test(content) &&
        (content.includes('second') ||
         content.includes('percent') ||
         content.includes('%') ||
         content.includes('bounce') ||
         content.includes('abandon'))
      ).toBe(true);
    });

    it('WM-006: Section has valid content length', () => {
      expect(whySection?.content.length).toBeGreaterThan(250);
    });
  });

  describe('Optimization Techniques Section', () => {
    let optimizationSection: typeof guide extends { sections: infer S } ? S[0] : never;

    beforeAll(() => {
      optimizationSection = guide?.sections.find(
        (s) =>
          s.title.toLowerCase().includes('optimization') ||
          s.title.toLowerCase().includes('optimize') ||
          s.title.toLowerCase().includes('improve') ||
          s.title.toLowerCase().includes('technique')
      ) as typeof optimizationSection;
    });

    it('OT-001: Has optimization techniques section', () => {
      expect(optimizationSection).toBeDefined();
    });

    it('OT-002: Discusses caching', () => {
      const content = optimizationSection?.content.toLowerCase() || '';
      expect(
        content.includes('cache') ||
        content.includes('caching') ||
        content.includes('browser cache')
      ).toBe(true);
    });

    it('OT-003: Discusses CDN usage', () => {
      const content = optimizationSection?.content.toLowerCase() || '';
      expect(
        content.includes('cdn') ||
        content.includes('content delivery network') ||
        content.includes('edge') ||
        content.includes('distributed')
      ).toBe(true);
    });

    it('OT-004: Discusses minification', () => {
      const content = optimizationSection?.content.toLowerCase() || '';
      expect(
        content.includes('minif') ||
        content.includes('compress') ||
        content.includes('bundle') ||
        content.includes('css') ||
        content.includes('javascript') ||
        content.includes('js')
      ).toBe(true);
    });

    it('OT-005: Discusses image optimization', () => {
      const content = optimizationSection?.content.toLowerCase() || '';
      expect(
        content.includes('image') ||
        content.includes('webp') ||
        content.includes('avif') ||
        content.includes('compress') ||
        content.includes('lazy load')
      ).toBe(true);
    });

    it('OT-006: Discusses code optimization', () => {
      const content = optimizationSection?.content.toLowerCase() || '';
      expect(
        content.includes('javascript') ||
        content.includes('css') ||
        content.includes('render') ||
        content.includes('script') ||
        content.includes('defer') ||
        content.includes('async')
      ).toBe(true);
    });

    it('OT-007: Discusses server optimization', () => {
      const allContent = guide?.sections
        .map((s) => s.content.toLowerCase())
        .join(' ') || '';
      expect(
        allContent.includes('server') ||
        allContent.includes('hosting') ||
        allContent.includes('http/2') ||
        allContent.includes('gzip') ||
        allContent.includes('brotli')
      ).toBe(true);
    });

    it('OT-008: Section has valid content length', () => {
      expect(optimizationSection?.content.length).toBeGreaterThan(300);
    });
  });

  describe('Measurement Tools Section', () => {
    let toolsSection: typeof guide extends { sections: infer S } ? S[0] : never;

    beforeAll(() => {
      toolsSection = guide?.sections.find(
        (s) =>
          s.title.toLowerCase().includes('tool') ||
          s.title.toLowerCase().includes('measure') ||
          s.title.toLowerCase().includes('test') ||
          s.title.toLowerCase().includes('analyze')
      ) as typeof toolsSection;
    });

    it('MT-001: Has measurement tools section', () => {
      expect(toolsSection).toBeDefined();
    });

    it('MT-002: Mentions PageSpeed Insights', () => {
      const content = toolsSection?.content.toLowerCase() || '';
      expect(
        content.includes('pagespeed') ||
        content.includes('page speed insights') ||
        content.includes('pagespeed insights')
      ).toBe(true);
    });

    it('MT-003: Mentions Lighthouse', () => {
      const content = toolsSection?.content.toLowerCase() || '';
      expect(
        content.includes('lighthouse')
      ).toBe(true);
    });

    it('MT-004: Mentions GTmetrix or other free tools', () => {
      const content = toolsSection?.content.toLowerCase() || '';
      expect(
        content.includes('gtmetrix') ||
        content.includes('webpagetest') ||
        content.includes('pingdom') ||
        content.includes('web.dev')
      ).toBe(true);
    });

    it('MT-005: Explains how to interpret scores', () => {
      const content = toolsSection?.content.toLowerCase() || '';
      expect(
        content.includes('score') ||
        content.includes('metric') ||
        content.includes('report') ||
        content.includes('recommendation') ||
        content.includes('audit')
      ).toBe(true);
    });

    it('MT-006: Section has valid content length', () => {
      expect(toolsSection?.content.length).toBeGreaterThan(200);
    });
  });

  describe('Performance Testing Examples', () => {
    it('PE-001: Has code examples in at least one section', () => {
      const allCodeExamples = guide?.sections
        .flatMap((s) => s.codeExamples || []) || [];
      expect(allCodeExamples.length).toBeGreaterThan(0);
    });

    it('PE-002: Has performance-related code examples', () => {
      const allCodeExamples = guide?.sections
        .flatMap((s) => s.codeExamples || []) || [];
      
      const hasPerfCode = allCodeExamples.some((ex) => {
        const code = ex.code.toLowerCase();
        return (
          code.includes('cache') ||
          code.includes('preload') ||
          code.includes('prefetch') ||
          code.includes('defer') ||
          code.includes('async') ||
          code.includes('loading') ||
          code.includes('src') ||
          code.includes('link') ||
          code.includes('script') ||
          code.includes('img') ||
          code.includes('.htaccess') ||
          code.includes('expires') ||
          code.includes('max-age')
        );
      });
      
      expect(hasPerfCode).toBe(true);
    });

    it('PE-003: Code examples have valid language property', () => {
      const allCodeExamples = guide?.sections
        .flatMap((s) => s.codeExamples || []) || [];
      
      allCodeExamples.forEach((ex) => {
        expect(ex.language).toBeDefined();
        expect(ex.language.length).toBeGreaterThan(0);
      });
    });

    it('PE-004: Code examples have descriptive content', () => {
      const allCodeExamples = guide?.sections
        .flatMap((s) => s.codeExamples || []) || [];
      
      allCodeExamples.forEach((ex) => {
        expect(ex.code.length).toBeGreaterThan(10);
      });
    });
  });

  describe('Mobile Performance Coverage', () => {
    it('MP-001: Mentions mobile-first indexing impact', () => {
      const allContent = guide?.sections
        .map((s) => s.content.toLowerCase())
        .join(' ') || '';
      expect(
        allContent.includes('mobile') ||
        allContent.includes('mobile-first') ||
        allContent.includes('device') ||
        allContent.includes('responsive')
      ).toBe(true);
    });

    it('MP-002: Discusses mobile performance considerations', () => {
      const allContent = guide?.sections
        .map((s) => s.content.toLowerCase())
        .join(' ') || '';
      expect(
        allContent.includes('3g') ||
        allContent.includes('4g') ||
        allContent.includes('network') ||
        allContent.includes('mobile') ||
        allContent.includes('cellular')
      ).toBe(true);
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

    it('BC-005: Covers Core Web Vitals optimization', () => {
      const checklistText = guide?.checklist
        .map((item) => item.text.toLowerCase())
        .join(' ') || '';
      expect(
        checklistText.includes('core web vital') ||
        checklistText.includes('lcp') ||
        checklistText.includes('fid') ||
        checklistText.includes('cls') ||
        checklistText.includes('vital') ||
        checklistText.includes('metric')
      ).toBe(true);
    });

    it('BC-006: Covers image optimization', () => {
      const checklistText = guide?.checklist
        .map((item) => item.text.toLowerCase())
        .join(' ') || '';
      expect(
        checklistText.includes('image') ||
        checklistText.includes('compress') ||
        checklistText.includes('webp') ||
        checklistText.includes('lazy') ||
        checklistText.includes('optimize')
      ).toBe(true);
    });

    it('BC-007: Covers caching', () => {
      const checklistText = guide?.checklist
        .map((item) => item.text.toLowerCase())
        .join(' ') || '';
      expect(
        checklistText.includes('cache') ||
        checklistText.includes('browser') ||
        checklistText.includes('cdn')
      ).toBe(true);
    });
  });

  describe('Read Time', () => {
    it('RT-001: Read time is set', () => {
      expect(guide).toHaveProperty('readTime');
      expect(typeof guide!.readTime).toBe('number');
    });

    it('RT-002: Read time in valid range (5-7 minutes)', () => {
      expect(guide!.readTime).toBeGreaterThanOrEqual(5);
      expect(guide!.readTime).toBeLessThanOrEqual(7);
    });

    it('RT-003: Read time matches expected value', () => {
      // Task specifies 5-7 minutes, registry shows 7
      expect([5, 6, 7]).toContain(guide!.readTime);
    });
  });

  describe('Related Guides', () => {
    it('RG-001: Has related guides array', () => {
      expect(Array.isArray(guide?.relatedGuides)).toBe(true);
    });

    it('RG-002: Contains image-optimization', () => {
      expect(guide?.relatedGuides).toContain('image-optimization');
    });

    it('RG-003: Contains mobile-friendly', () => {
      expect(guide?.relatedGuides).toContain('mobile-friendly');
    });

    it('RG-004: Related guides exist in registry', () => {
      guide?.relatedGuides.forEach((relatedSlug) => {
        const relatedGuide = getGuideBySlug(relatedSlug);
        expect(relatedGuide).not.toBeNull();
      });
    });
  });

  describe('Content Quality', () => {
    it('CQ-001: Total content length is substantial', () => {
      const totalLength = guide?.sections
        .reduce((sum, s) => sum + s.content.length, 0) || 0;
      // Should have substantial content (at least 2000 characters)
      expect(totalLength).toBeGreaterThan(2000);
    });

    it('CQ-002: Has at least 5 sections', () => {
      expect(guide?.sections.length).toBeGreaterThanOrEqual(5);
    });

    it('CQ-003: Each section has meaningful content', () => {
      guide?.sections.forEach((section) => {
        expect(section.content.length).toBeGreaterThan(100);
      });
    });

    it('CQ-004: Section titles are descriptive', () => {
      guide?.sections.forEach((section) => {
        expect(section.title.length).toBeGreaterThan(3);
        expect(section.title).not.toMatch(/^(test|placeholder|todo|tbd)$/i);
      });
    });
  });

  describe('Guide Registry Integration', () => {
    it('GR-001: Guide in registry', () => {
      const allGuides = getAllGuides();
      const found = allGuides.find((g) => g.slug === 'page-speed');
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
});
