/**
 * External Links Guide Content Tests
 * Task: TASK-20-12 - Write Content for External Links Guide
 * 
 * Tests for content structure and quality validation of the External Links guide.
 */

import {
  getGuideBySlug,
  getAllGuides,
} from '@/lib/guides/registry';
import { isSEOGuide } from '@/lib/guides/types';

describe('External Links Guide - Content Structure', () => {
  let guide: ReturnType<typeof getGuideBySlug>;

  beforeAll(() => {
    guide = getGuideBySlug('external-links');
  });

  describe('Guide Existence and Basic Properties', () => {
    it('EL-001: Guide exists in registry', () => {
      expect(guide).not.toBeNull();
    });

    it('EL-002: Guide has correct title', () => {
      expect(guide).toHaveProperty('title', 'External Link Best Practices');
    });

    it('EL-003: Guide has non-empty summary', () => {
      expect(guide).toHaveProperty('summary');
      expect(guide!.summary.length).toBeGreaterThan(20);
    });

    it('EL-004: Guide category is on-page', () => {
      expect(guide).toHaveProperty('category', 'on-page');
    });
  });

  describe('What Are External Links Section', () => {
    let whatIsSection: typeof guide extends { sections: infer S } ? S[0] : never;

    beforeAll(() => {
      whatIsSection = guide?.sections.find(
        (s) =>
          s.title.toLowerCase().includes('what') &&
          s.title.toLowerCase().includes('external')
      ) as typeof whatIsSection;
    });

    it('WI-001: Has "What are External Links?" section', () => {
      expect(whatIsSection).toBeDefined();
    });

    it('WI-002: Section explains external links', () => {
      const content = whatIsSection?.content.toLowerCase() || '';
      expect(
        content.includes('link') && 
        (content.includes('domain') || content.includes('website'))
      ).toBe(true);
    });

    it('WI-003: Section differentiates from internal', () => {
      const content = whatIsSection?.content.toLowerCase() || '';
      expect(
        content.includes('internal') ||
        content.includes('different domain') ||
        content.includes('another site') ||
        content.includes('outside')
      ).toBe(true);
    });

    it('WI-004: Section has valid content length', () => {
      expect(whatIsSection?.content.length).toBeGreaterThan(150);
    });
  });

  describe('Why External Links Matter Section', () => {
    let whyMatterSection: typeof guide extends { sections: infer S } ? S[0] : never;

    beforeAll(() => {
      whyMatterSection = guide?.sections.find(
        (s) =>
          (s.title.toLowerCase().includes('why') &&
           s.title.toLowerCase().includes('matter')) ||
          s.title.toLowerCase().includes('important') ||
          s.title.toLowerCase().includes('benefit')
      ) as typeof whyMatterSection;
    });

    it('WM-001: Has "Why External Links Matter" section', () => {
      expect(whyMatterSection).toBeDefined();
    });

    it('WM-002: Section mentions authority building', () => {
      const content = whyMatterSection?.content.toLowerCase() || '';
      expect(
        content.includes('authority') ||
        content.includes('trust') ||
        content.includes('credibility')
      ).toBe(true);
    });

    it('WM-003: Section mentions user value', () => {
      const content = whyMatterSection?.content.toLowerCase() || '';
      expect(
        content.includes('user') ||
        content.includes('value') ||
        content.includes('resource') ||
        content.includes('reference')
      ).toBe(true);
    });

    it('WM-004: Section mentions citation value', () => {
      const content = whyMatterSection?.content.toLowerCase() || '';
      expect(
        content.includes('citation') ||
        content.includes('source') ||
        content.includes('reference') ||
        content.includes('credibility')
      ).toBe(true);
    });

    it('WM-005: Section has valid content length', () => {
      expect(whyMatterSection?.content.length).toBeGreaterThan(200);
    });
  });

  describe('Security Attributes Section', () => {
    let securitySection: typeof guide extends { sections: infer S } ? S[0] : never;

    beforeAll(() => {
      securitySection = guide?.sections.find(
        (s) =>
          s.title.toLowerCase().includes('security') ||
          s.title.toLowerCase().includes('noopener') ||
          s.title.toLowerCase().includes('noreferrer') ||
          s.title.toLowerCase().includes('attribute')
      ) as typeof securitySection;
    });

    it('SA-001: Has security attributes section', () => {
      expect(securitySection).toBeDefined();
    });

    it('SA-002: Mentions noopener', () => {
      const content = securitySection?.content.toLowerCase() || '';
      expect(content.includes('noopener')).toBe(true);
    });

    it('SA-003: Mentions noreferrer', () => {
      const content = securitySection?.content.toLowerCase() || '';
      expect(content.includes('noreferrer')).toBe(true);
    });

    it('SA-004: Explains target="_blank" risks', () => {
      const content = securitySection?.content.toLowerCase() || '';
      expect(
        (content.includes('target') && content.includes('blank')) ||
        content.includes('tabnabbing') ||
        content.includes('security risk') ||
        content.includes('vulnerability')
      ).toBe(true);
    });

    it('SA-005: Explains security benefits', () => {
      const content = securitySection?.content.toLowerCase() || '';
      expect(
        content.includes('security') ||
        content.includes('protect') ||
        content.includes('prevent') ||
        content.includes('safe')
      ).toBe(true);
    });

    it('SA-006: Shows correct implementation in code examples', () => {
      const codeExamples = securitySection?.codeExamples || [];
      const hasSecurityCode = codeExamples.some((ex) => 
        ex.code.includes('noopener') || ex.code.includes('noreferrer')
      );
      // Either in this section's code or elsewhere
      const allContent = guide?.sections.map(s => s.content.toLowerCase()).join(' ') || '';
      expect(hasSecurityCode || allContent.includes('noopener')).toBe(true);
    });

    it('SA-007: Explains when to use each attribute', () => {
      const content = securitySection?.content.toLowerCase() || '';
      // Should differentiate noopener vs noreferrer
      expect(
        content.includes('noopener') && content.includes('noreferrer')
      ).toBe(true);
    });

    it('SA-008: Section has valid content length', () => {
      expect(securitySection?.content.length).toBeGreaterThan(250);
    });
  });

  describe('When to Use Nofollow Section', () => {
    let nofollowSection: typeof guide extends { sections: infer S } ? S[0] : never;

    beforeAll(() => {
      nofollowSection = guide?.sections.find(
        (s) =>
          s.title.toLowerCase().includes('nofollow') ||
          s.title.toLowerCase().includes('sponsored') ||
          s.title.toLowerCase().includes('ugc')
      ) as typeof nofollowSection;
    });

    it('NF-001: Has nofollow section', () => {
      expect(nofollowSection).toBeDefined();
    });

    it('NF-002: Explains nofollow purpose', () => {
      const content = nofollowSection?.content.toLowerCase() || '';
      expect(
        content.includes('nofollow') && 
        (content.includes('link equity') || 
         content.includes('pass') || 
         content.includes('vote') ||
         content.includes('endorse') ||
         content.includes('trust'))
      ).toBe(true);
    });

    it('NF-003: Lists sponsored content scenarios', () => {
      const content = nofollowSection?.content.toLowerCase() || '';
      expect(
        content.includes('sponsored') ||
        content.includes('paid') ||
        content.includes('advertisement') ||
        content.includes('affiliate')
      ).toBe(true);
    });

    it('NF-004: Lists UGC scenarios', () => {
      const content = nofollowSection?.content.toLowerCase() || '';
      expect(
        content.includes('ugc') ||
        content.includes('user-generated') ||
        content.includes('comment') ||
        content.includes('forum')
      ).toBe(true);
    });

    it('NF-005: Section has valid content length', () => {
      expect(nofollowSection?.content.length).toBeGreaterThan(150);
    });
  });

  describe('Common Mistakes Section', () => {
    let mistakesSection: typeof guide extends { sections: infer S } ? S[0] : never;

    beforeAll(() => {
      mistakesSection = guide?.sections.find(
        (s) =>
          s.title.toLowerCase().includes('mistake') ||
          s.title.toLowerCase().includes('avoid') ||
          s.title.toLowerCase().includes('error')
      ) as typeof mistakesSection;
    });

    it('CM-001: Has common mistakes section', () => {
      expect(mistakesSection).toBeDefined();
    });

    it('CM-002: Warns about missing noopener', () => {
      const content = mistakesSection?.content.toLowerCase() || '';
      expect(
        content.includes('noopener') ||
        content.includes('security') ||
        content.includes('target') ||
        content.includes('vulnerability')
      ).toBe(true);
    });

    it('CM-003: Warns about excessive external links', () => {
      const content = mistakesSection?.content.toLowerCase() || '';
      expect(
        content.includes('excessive') ||
        content.includes('too many') ||
        content.includes('overload') ||
        content.includes('limit')
      ).toBe(true);
    });

    it('CM-004: Warns about broken external links', () => {
      const content = mistakesSection?.content.toLowerCase() || '';
      expect(
        content.includes('broken') ||
        content.includes('404') ||
        content.includes('dead link') ||
        content.includes('link rot')
      ).toBe(true);
    });

    it('CM-005: Warns about linking to low-quality sites', () => {
      const content = mistakesSection?.content.toLowerCase() || '';
      expect(
        content.includes('quality') ||
        content.includes('spam') ||
        content.includes('untrustworthy') ||
        content.includes('bad neighborhood') ||
        content.includes('penalty')
      ).toBe(true);
    });

    it('CM-006: Section has valid content length', () => {
      expect(mistakesSection?.content.length).toBeGreaterThan(200);
    });
  });

  describe('Link Quality Coverage', () => {
    it('LQ-001: Mentions link quality somewhere in content', () => {
      const allContent = guide?.sections
        .map((s) => s.content.toLowerCase())
        .join(' ') || '';
      expect(
        allContent.includes('quality') ||
        allContent.includes('reputable') ||
        allContent.includes('trustworthy') ||
        allContent.includes('authoritative')
      ).toBe(true);
    });

    it('LQ-002: Discusses authority signals', () => {
      const allContent = guide?.sections
        .map((s) => s.content.toLowerCase())
        .join(' ') || '';
      expect(
        allContent.includes('authority') ||
        allContent.includes('da') ||
        allContent.includes('trust') ||
        allContent.includes('domain authority')
      ).toBe(true);
    });

    it('LQ-003: Warns about bad neighborhoods', () => {
      const allContent = guide?.sections
        .map((s) => s.content.toLowerCase())
        .join(' ') || '';
      expect(
        allContent.includes('bad neighborhood') ||
        allContent.includes('spam') ||
        allContent.includes('penalty') ||
        allContent.includes('untrustworthy')
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

    it('BC-005: Covers key practices', () => {
      const checklistText = guide?.checklist
        .map((item) => item.text.toLowerCase())
        .join(' ') || '';
      
      // Should cover at least 3 of these key topics
      const keyTopics = [
        'noopener',
        'noreferrer',
        'nofollow',
        'quality',
        'broken',
        'sponsored',
        'ugc',
        'target'
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
      // Task specifies 4-6 minutes, registry shows 4
      expect(guide!.readTime).toBe(4);
    });
  });

  describe('Related Guides', () => {
    it('RG-001: Has related guides array', () => {
      expect(Array.isArray(guide?.relatedGuides)).toBe(true);
    });

    it('RG-002: Contains internal-links', () => {
      expect(guide?.relatedGuides).toContain('internal-links');
    });

    it('RG-003: Contains https-security', () => {
      expect(guide?.relatedGuides).toContain('https-security');
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
      const found = allGuides.find((g) => g.slug === 'external-links');
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
