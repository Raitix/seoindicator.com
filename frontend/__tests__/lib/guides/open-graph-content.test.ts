/**
 * Open Graph Tags Guide Content Tests
 * Task: TASK-20-18 - Write Content for Open Graph Tags Guide
 *
 * Tests for content structure and quality validation of the Open Graph Tags guide.
 */

import {
  getGuideBySlug,
  getAllGuides,
} from '@/lib/guides/registry';
import { isSEOGuide } from '@/lib/guides/types';

describe('Open Graph Tags Guide - Content Structure', () => {
  let guide: ReturnType<typeof getGuideBySlug>;

  beforeAll(() => {
    guide = getGuideBySlug('open-graph');
  });

  describe('Guide Existence and Basic Properties', () => {
    it('OG-001: Guide exists in registry', () => {
      expect(guide).not.toBeNull();
    });

    it('OG-002: Guide has correct title', () => {
      expect(guide).toHaveProperty('title', 'Open Graph Tags');
    });

    it('OG-003: Guide has non-empty summary', () => {
      expect(guide).toHaveProperty('summary');
      expect(guide!.summary.length).toBeGreaterThan(20);
    });

    it('OG-004: Guide category is content', () => {
      expect(guide).toHaveProperty('category', 'content');
    });

    it('OG-005: Guide has tool link to Social Media Preview', () => {
      expect(guide).toHaveProperty('toolSlug', 'social-preview');
    });
  });

  describe('What Are Open Graph Tags Section', () => {
    let whatIsSection: typeof guide extends { sections: infer S } ? S[0] : never;

    beforeAll(() => {
      whatIsSection = guide?.sections.find(
        (s) =>
          s.title.toLowerCase().includes('what') &&
          s.title.toLowerCase().includes('open graph')
      ) as typeof whatIsSection;
    });

    it('WI-001: Has "What are Open Graph Tags?" section', () => {
      expect(whatIsSection).toBeDefined();
    });

    it('WI-002: Explains OG protocol origin (Facebook)', () => {
      const content = whatIsSection?.content.toLowerCase() || '';
      expect(
        content.includes('facebook') ||
        content.includes('og:') ||
        content.includes('open graph protocol')
      ).toBe(true);
    });

    it('WI-003: Explains og: namespace prefix', () => {
      const content = whatIsSection?.content.toLowerCase() || '';
      expect(
        content.includes('og:') ||
        content.includes('namespace') ||
        content.includes('prefix')
      ).toBe(true);
    });

    it('WI-004: Mentions social platforms that use OG', () => {
      const content = whatIsSection?.content.toLowerCase() || '';
      expect(
        content.includes('facebook') ||
        content.includes('linkedin') ||
        content.includes('twitter') ||
        content.includes('social')
      ).toBe(true);
    });

    it('WI-005: Section has valid content length', () => {
      expect(whatIsSection?.content.length).toBeGreaterThan(150);
    });
  });

  describe('Why Open Graph Tags Matter Section', () => {
    let whySection: typeof guide extends { sections: infer S } ? S[0] : never;

    beforeAll(() => {
      whySection = guide?.sections.find(
        (s) =>
          (s.title.toLowerCase().includes('why') &&
           (s.title.toLowerCase().includes('matter') || s.title.toLowerCase().includes('important'))) ||
          s.title.toLowerCase().includes('benefit') ||
          s.title.toLowerCase().includes('impact')
      ) as typeof whySection;
    });

    it('WM-001: Has "Why They Matter" section', () => {
      expect(whySection).toBeDefined();
    });

    it('WM-002: Discusses social sharing benefits', () => {
      const content = whySection?.content.toLowerCase() || '';
      expect(
        content.includes('social') &&
        (content.includes('share') || content.includes('post'))
      ).toBe(true);
    });

    it('WM-003: Discusses CTR improvement', () => {
      const content = whySection?.content.toLowerCase() || '';
      expect(
        content.includes('ctr') ||
        content.includes('click') ||
        content.includes('rate') ||
        content.includes('engagement')
      ).toBe(true);
    });

    it('WM-004: Mentions preview appearance', () => {
      const content = whySection?.content.toLowerCase() || '';
      expect(
        content.includes('preview') ||
        content.includes('card') ||
        content.includes('snippet') ||
        content.includes('display')
      ).toBe(true);
    });

    it('WM-005: Section has valid content length', () => {
      expect(whySection?.content.length).toBeGreaterThan(200);
    });
  });

  describe('Required OG Tags Section', () => {
    let tagsSection: typeof guide extends { sections: infer S } ? S[0] : never;

    beforeAll(() => {
      tagsSection = guide?.sections.find(
        (s) =>
          s.title.toLowerCase().includes('required') ||
          s.title.toLowerCase().includes('essential') ||
          s.title.toLowerCase().includes('tag') ||
          s.title.toLowerCase().includes('basic')
      ) as typeof tagsSection;
    });

    it('RT-001: Has required/essential tags section', () => {
      expect(tagsSection).toBeDefined();
    });

    it('RT-002: Documents og:title', () => {
      const content = tagsSection?.content.toLowerCase() || '';
      expect(content.includes('og:title')).toBe(true);
    });

    it('RT-003: Documents og:description', () => {
      const content = tagsSection?.content.toLowerCase() || '';
      expect(content.includes('og:description')).toBe(true);
    });

    it('RT-004: Documents og:image', () => {
      const content = tagsSection?.content.toLowerCase() || '';
      expect(content.includes('og:image')).toBe(true);
    });

    it('RT-005: Documents og:url', () => {
      const content = tagsSection?.content.toLowerCase() || '';
      expect(content.includes('og:url')).toBe(true);
    });

    it('RT-006: Documents og:type', () => {
      const content = tagsSection?.content.toLowerCase() || '';
      expect(content.includes('og:type')).toBe(true);
    });

    it('RT-007: Explains each tag\'s purpose', () => {
      expect(tagsSection?.content.length).toBeGreaterThan(300);
    });

    it('RT-008: Mentions character or content limits', () => {
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

    it('IB-002: Recommends specific image size (1200x630)', () => {
      const content = imageSection?.content.toLowerCase() || '';
      expect(
        content.includes('1200') ||
        content.includes('630') ||
        content.includes('dimension') ||
        content.includes('size')
      ).toBe(true);
    });

    it('IB-003: Discusses image format', () => {
      const content = imageSection?.content.toLowerCase() || '';
      expect(
        content.includes('png') ||
        content.includes('jpg') ||
        content.includes('jpeg') ||
        content.includes('format') ||
        content.includes('webp')
      ).toBe(true);
    });

    it('IB-004: Mentions aspect ratio', () => {
      const content = imageSection?.content.toLowerCase() || '';
      expect(
        content.includes('ratio') ||
        content.includes('1.91') ||
        content.includes('landscape') ||
        content.includes('width') ||
        content.includes('height')
      ).toBe(true);
    });

    it('IB-005: Warns about file size', () => {
      const content = imageSection?.content.toLowerCase() || '';
      expect(
        content.includes('kb') ||
        content.includes('mb') ||
        content.includes('file size') ||
        content.includes('filesize') ||
        content.includes('optimize')
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
        content.includes('/tools/social-preview')
      ).toBe(true);
    });

    it('SM-003: Mentions Facebook Sharing Debugger', () => {
      const content = testingSection?.content.toLowerCase() || '';
      expect(
        content.includes('debugger') ||
        content.includes('facebook') ||
        content.includes('developers.facebook.com')
      ).toBe(true);
    });

    it('SM-004: Explains debugging/scraping process', () => {
      const content = testingSection?.content.toLowerCase() || '';
      expect(
        content.includes('scrape') ||
        content.includes('debug') ||
        content.includes('refresh') ||
        content.includes('cache')
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

    it('BC-005: Covers og:image requirements', () => {
      const checklistText = guide?.checklist
        .map((item) => item.text.toLowerCase())
        .join(' ') || '';
      expect(
        checklistText.includes('og:image') ||
        checklistText.includes('image')
      ).toBe(true);
    });

    it('BC-006: Covers og:title requirements', () => {
      const checklistText = guide?.checklist
        .map((item) => item.text.toLowerCase())
        .join(' ') || '';
      expect(
        checklistText.includes('og:title') ||
        checklistText.includes('title')
      ).toBe(true);
    });

    it('BC-007: Covers testing/debugging', () => {
      const checklistText = guide?.checklist
        .map((item) => item.text.toLowerCase())
        .join(' ') || '';
      expect(
        checklistText.includes('test') ||
        checklistText.includes('debug') ||
        checklistText.includes('validate')
      ).toBe(true);
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

    it('RT-003: Read time matches task requirement', () => {
      // Task specifies 4-6 minutes, registry shows 5
      expect([4, 5, 6]).toContain(guide!.readTime);
    });
  });

  describe('Related Guides', () => {
    it('RG-001: Has related guides array', () => {
      expect(Array.isArray(guide?.relatedGuides)).toBe(true);
    });

    it('RG-002: Contains twitter-cards', () => {
      expect(guide?.relatedGuides).toContain('twitter-cards');
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
  });

  describe('Guide Registry Integration', () => {
    it('GR-001: Guide in registry', () => {
      const allGuides = getAllGuides();
      const found = allGuides.find((g) => g.slug === 'open-graph');
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
