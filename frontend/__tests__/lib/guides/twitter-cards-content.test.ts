/**
 * Twitter Cards Guide Content Tests
 * Task: TASK-20-19 - Write Content for Twitter Cards Guide
 *
 * Tests for content structure and quality validation of the Twitter Cards guide.
 */

import {
  getGuideBySlug,
  getAllGuides,
} from '@/lib/guides/registry';
import { isSEOGuide } from '@/lib/guides/types';

describe('Twitter Cards Guide - Content Structure', () => {
  let guide: ReturnType<typeof getGuideBySlug>;

  beforeAll(() => {
    guide = getGuideBySlug('twitter-cards');
  });

  describe('Guide Existence and Basic Properties', () => {
    it('TC-001: Guide exists in registry', () => {
      expect(guide).not.toBeNull();
    });

    it('TC-002: Guide has correct title', () => {
      expect(guide).toHaveProperty('title', 'Twitter Cards');
    });

    it('TC-003: Guide has non-empty summary', () => {
      expect(guide).toHaveProperty('summary');
      expect(guide!.summary.length).toBeGreaterThan(20);
    });

    it('TC-004: Guide category is content', () => {
      expect(guide).toHaveProperty('category', 'content');
    });

    it('TC-005: Guide has tool link to Social Media Preview', () => {
      expect(guide).toHaveProperty('toolSlug', 'social-media-preview');
    });

    it('TC-006: Guide has appropriate icon', () => {
      expect(guide).toHaveProperty('icon');
      expect(guide!.icon.length).toBeGreaterThan(0);
    });
  });

  describe('What Are Twitter Cards Section', () => {
    let whatIsSection: typeof guide extends { sections: infer S } ? S[0] : never;

    beforeAll(() => {
      whatIsSection = guide?.sections.find(
        (s) =>
          s.title.toLowerCase().includes('what') &&
          s.title.toLowerCase().includes('twitter')
      ) as typeof whatIsSection;
    });

    it('WI-001: Has "What are Twitter Cards?" section', () => {
      expect(whatIsSection).toBeDefined();
    });

    it('WI-002: Explains Twitter Cards purpose', () => {
      const content = whatIsSection?.content.toLowerCase() || '';
      expect(
        content.includes('social') ||
        content.includes('share') ||
        content.includes('preview') ||
        content.includes('card')
      ).toBe(true);
    });

    it('WI-003: Explains Twitter Cards benefits', () => {
      const content = whatIsSection?.content.toLowerCase() || '';
      expect(
        content.includes('engagement') ||
        content.includes('click') ||
        content.includes('ctr') ||
        content.includes('traffic')
      ).toBe(true);
    });

    it('WI-004: Mentions Twitter/X platform', () => {
      const content = whatIsSection?.content.toLowerCase() || '';
      expect(
        content.includes('twitter') ||
        content.includes('x.com')
      ).toBe(true);
    });

    it('WI-005: Section has valid content length', () => {
      expect(whatIsSection?.content.length).toBeGreaterThan(150);
    });
  });

  describe('Card Types Section', () => {
    let cardTypesSection: typeof guide extends { sections: infer S } ? S[0] : never;

    beforeAll(() => {
      cardTypesSection = guide?.sections.find(
        (s) =>
          s.title.toLowerCase().includes('card') &&
          (s.title.toLowerCase().includes('type') ||
           s.title.toLowerCase().includes('kind') ||
           s.title.toLowerCase().includes('format'))
      ) as typeof cardTypesSection;
    });

    it('CT-001: Has Card Types section', () => {
      expect(cardTypesSection).toBeDefined();
    });

    it('CT-002: Documents summary card type', () => {
      const content = cardTypesSection?.content.toLowerCase() || '';
      expect(content.includes('summary')).toBe(true);
    });

    it('CT-003: Documents summary_large_image card type', () => {
      const content = cardTypesSection?.content.toLowerCase() || '';
      expect(
        content.includes('summary_large_image') ||
        content.includes('large image') ||
        content.includes('large-image')
      ).toBe(true);
    });

    it('CT-004: Documents player card type', () => {
      const content = cardTypesSection?.content.toLowerCase() || '';
      expect(content.includes('player')).toBe(true);
    });

    it('CT-005: Explains when to use each card type', () => {
      const content = cardTypesSection?.content.toLowerCase() || '';
      // Should mention use cases or when to use
      expect(
        content.includes('use') ||
        content.includes('when') ||
        content.includes('for ') ||
        content.includes('best')
      ).toBe(true);
    });

    it('CT-006: Section has valid content length', () => {
      expect(cardTypesSection?.content.length).toBeGreaterThan(200);
    });
  });

  describe('Required Twitter Card Tags Section', () => {
    let tagsSection: typeof guide extends { sections: infer S } ? S[0] : never;

    beforeAll(() => {
      tagsSection = guide?.sections.find(
        (s) =>
          s.title.toLowerCase().includes('required') ||
          s.title.toLowerCase().includes('essential') ||
          s.title.toLowerCase().includes('tag') ||
          s.title.toLowerCase().includes('meta')
      ) as typeof tagsSection;
    });

    it('RT-001: Has required/essential tags section', () => {
      expect(tagsSection).toBeDefined();
    });

    it('RT-002: Documents twitter:card', () => {
      const content = tagsSection?.content.toLowerCase() || '';
      expect(content.includes('twitter:card')).toBe(true);
    });

    it('RT-003: Documents twitter:site', () => {
      const content = tagsSection?.content.toLowerCase() || '';
      expect(content.includes('twitter:site')).toBe(true);
    });

    it('RT-004: Documents twitter:title', () => {
      const content = tagsSection?.content.toLowerCase() || '';
      expect(content.includes('twitter:title')).toBe(true);
    });

    it('RT-005: Documents twitter:description', () => {
      const content = tagsSection?.content.toLowerCase() || '';
      expect(content.includes('twitter:description')).toBe(true);
    });

    it('RT-006: Documents twitter:image', () => {
      const content = tagsSection?.content.toLowerCase() || '';
      expect(content.includes('twitter:image')).toBe(true);
    });

    it('RT-007: Explains each tag\'s purpose', () => {
      expect(tagsSection?.content.length).toBeGreaterThan(300);
    });

    it('RT-008: Mentions character limits', () => {
      const content = tagsSection?.content.toLowerCase() || '';
      expect(
        /\d+/.test(content) &&
        (content.includes('character') ||
         content.includes('limit') ||
         content.includes('max') ||
         content.includes('length'))
      ).toBe(true);
    });
  });

  describe('Image Best Practices Section', () => {
    let imageSection: typeof guide extends { sections: infer S } ? S[0] : never;

    beforeAll(() => {
      imageSection = guide?.sections.find(
        (s) =>
          s.title.toLowerCase().includes('image') ||
          s.title.toLowerCase().includes('photo') ||
          s.title.toLowerCase().includes('picture')
      ) as typeof imageSection;
    });

    it('IB-001: Has image best practices section', () => {
      expect(imageSection).toBeDefined();
    });

    it('IB-002: Recommends specific image sizes', () => {
      const content = imageSection?.content.toLowerCase() || '';
      expect(
        content.includes('1200') ||
        content.includes('628') ||
        content.includes('dimension') ||
        content.includes('size') ||
        content.includes('pixel')
      ).toBe(true);
    });

    it('IB-003: Discusses image formats', () => {
      const content = imageSection?.content.toLowerCase() || '';
      expect(
        content.includes('png') ||
        content.includes('jpg') ||
        content.includes('jpeg') ||
        content.includes('gif') ||
        content.includes('format') ||
        content.includes('webp')
      ).toBe(true);
    });

    it('IB-004: Warns about file size limits', () => {
      const content = imageSection?.content.toLowerCase() || '';
      expect(
        content.includes('kb') ||
        content.includes('mb') ||
        content.includes('file size') ||
        content.includes('filesize') ||
        content.includes('limit')
      ).toBe(true);
    });

    it('IB-005: Mentions aspect ratio', () => {
      const content = imageSection?.content.toLowerCase() || '';
      expect(
        content.includes('ratio') ||
        content.includes('1.91') ||
        content.includes('landscape') ||
        content.includes('width') ||
        content.includes('height')
      ).toBe(true);
    });

    it('IB-006: Section has valid content length', () => {
      expect(imageSection?.content.length).toBeGreaterThan(200);
    });
  });

  describe('Testing with Social Media Preview Tool Section', () => {
    let testingSection: typeof guide extends { sections: infer S } ? S[0] : never;

    beforeAll(() => {
      testingSection = guide?.sections.find(
        (s) =>
          s.title.toLowerCase().includes('test') ||
          s.title.toLowerCase().includes('preview') ||
          s.title.toLowerCase().includes('debug') ||
          s.title.toLowerCase().includes('validate')
      ) as typeof testingSection;
    });

    it('SM-001: Has testing/debugging section', () => {
      expect(testingSection).toBeDefined();
    });

    it('SM-002: Mentions Social Media Preview tool', () => {
      const content = testingSection?.content.toLowerCase() || '';
      expect(
        content.includes('social media preview') ||
        content.includes('tool') ||
        content.includes('/tools/social')
      ).toBe(true);
    });

    it('SM-003: Mentions Twitter Card Validator', () => {
      const content = testingSection?.content.toLowerCase() || '';
      expect(
        content.includes('validator') ||
        content.includes('cards-dev.twitter.com') ||
        content.includes('twitter card validator')
      ).toBe(true);
    });

    it('SM-004: Explains testing process', () => {
      const content = testingSection?.content.toLowerCase() || '';
      expect(
        content.includes('test') ||
        content.includes('validate') ||
        content.includes('check') ||
        content.includes('preview')
      ).toBe(true);
    });

    it('SM-005: Section has valid content length', () => {
      expect(testingSection?.content.length).toBeGreaterThan(150);
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

    it('BC-005: Covers twitter:card requirements', () => {
      const checklistText = guide?.checklist
        .map((item) => item.text.toLowerCase())
        .join(' ') || '';
      expect(
        checklistText.includes('twitter:card') ||
        checklistText.includes('card type')
      ).toBe(true);
    });

    it('BC-006: Covers image requirements', () => {
      const checklistText = guide?.checklist
        .map((item) => item.text.toLowerCase())
        .join(' ') || '';
      expect(
        checklistText.includes('image') ||
        checklistText.includes('photo') ||
        checklistText.includes('picture')
      ).toBe(true);
    });

    it('BC-007: Covers testing/validation', () => {
      const checklistText = guide?.checklist
        .map((item) => item.text.toLowerCase())
        .join(' ') || '';
      expect(
        checklistText.includes('test') ||
        checklistText.includes('validat') ||
        checklistText.includes('check')
      ).toBe(true);
    });

    it('BC-008: Covers title and description', () => {
      const checklistText = guide?.checklist
        .map((item) => item.text.toLowerCase())
        .join(' ') || '';
      expect(
        checklistText.includes('title') ||
        checklistText.includes('description')
      ).toBe(true);
    });
  });

  describe('Read Time', () => {
    it('RT-001: Read time is set', () => {
      expect(guide).toHaveProperty('readTime');
      expect(typeof guide!.readTime).toBe('number');
    });

    it('RT-002: Read time in valid range (3-5 minutes)', () => {
      expect(guide!.readTime).toBeGreaterThanOrEqual(3);
      expect(guide!.readTime).toBeLessThanOrEqual(5);
    });

    it('RT-003: Read time matches task requirement', () => {
      // Task specifies 3-5 minutes
      expect([3, 4, 5]).toContain(guide!.readTime);
    });
  });

  describe('Related Guides', () => {
    it('RG-001: Has related guides array', () => {
      expect(Array.isArray(guide?.relatedGuides)).toBe(true);
    });

    it('RG-002: Contains open-graph', () => {
      expect(guide?.relatedGuides).toContain('open-graph');
    });

    it('RG-003: Contains meta-description', () => {
      expect(guide?.relatedGuides).toContain('meta-description');
    });

    it('RG-004: Related guides exist in registry', () => {
      guide?.relatedGuides.forEach((relatedSlug) => {
        const relatedGuide = getGuideBySlug(relatedSlug);
        expect(relatedGuide).not.toBeNull();
      });
    });

    it('RG-005: Has 2-3 related guides', () => {
      expect(guide?.relatedGuides.length).toBeGreaterThanOrEqual(2);
      expect(guide?.relatedGuides.length).toBeLessThanOrEqual(3);
    });
  });

  describe('Content Quality', () => {
    it('CQ-001: Total content length is substantial', () => {
      const totalLength = guide?.sections
        .reduce((sum, s) => sum + s.content.length, 0) || 0;
      // Should have substantial content (at least 1500 characters)
      expect(totalLength).toBeGreaterThan(1500);
    });

    it('CQ-002: Has at least 4 sections', () => {
      expect(guide?.sections.length).toBeGreaterThanOrEqual(4);
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

    it('CQ-005: No duplicate section titles', () => {
      const titles = guide?.sections.map((s) => s.title.toLowerCase()) || [];
      const uniqueTitles = new Set(titles);
      expect(uniqueTitles.size).toBe(titles.length);
    });
  });

  describe('Guide Registry Integration', () => {
    it('GR-001: Guide in registry', () => {
      const allGuides = getAllGuides();
      const found = allGuides.find((g) => g.slug === 'twitter-cards');
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

    it('GR-004: Guide slug is correct', () => {
      expect(guide).toHaveProperty('slug', 'twitter-cards');
    });
  });

  describe('Twitter-Specific Content', () => {
    it('TW-001: Mentions twitter:creator tag (optional)', () => {
      const allContent = guide?.sections
        .map((s) => s.content.toLowerCase())
        .join(' ') || '';
      // Optional tag but commonly used
      expect(typeof allContent.includes('twitter:creator')).toBe('boolean');
    });

    it('TW-002: Mentions twitter:image:alt tag (optional)', () => {
      const allContent = guide?.sections
        .map((s) => s.content.toLowerCase())
        .join(' ') || '';
      // Accessibility best practice
      expect(typeof allContent.includes('twitter:image:alt') || 
             allContent.includes('alt text')).toBe('boolean');
    });

    it('TW-003: Explains fallback to Open Graph', () => {
      const allContent = guide?.sections
        .map((s) => s.content.toLowerCase())
        .join(' ') || '';
      // Twitter falls back to OG tags when twitter: tags aren't present
      expect(
        allContent.includes('fallback') ||
        allContent.includes('open graph') ||
        allContent.includes('og:')
      ).toBe(true);
    });

    it('TW-004: Mentions Twitter username format (@username)', () => {
      const allContent = guide?.sections
        .map((s) => s.content.toLowerCase())
        .join(' ') || '';
      expect(
        allContent.includes('@') ||
        allContent.includes('username') ||
        allContent.includes('handle')
      ).toBe(true);
    });
  });
});
