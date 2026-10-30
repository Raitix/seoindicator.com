/**
 * Tests for HTTPS/Security Guide Content
 * Task: TASK-20-15 - Write Content for HTTPS/Security Guide
 * 
 * These tests verify the content structure, coverage, and quality
 * of the HTTPS/Security SEO guide.
 */

import {
  getGuideBySlug,
} from '../registry';
import {
  SEOGuide,
  GuideSection,
  ChecklistItem,
  isGuideSection,
  isChecklistItem,
} from '../types';

describe('HTTPS/Security Guide Content', () => {
  let guide: SEOGuide | null;

  beforeAll(() => {
    guide = getGuideBySlug('https-security');
  });

  describe('Guide Structure', () => {
    it('should exist in the registry', () => {
      expect(guide).not.toBeNull();
    });

    it('should have correct slug', () => {
      expect(guide?.slug).toBe('https-security');
    });

    it('should have a title', () => {
      expect(guide?.title).toBeTruthy();
      expect(guide?.title.length).toBeGreaterThan(0);
    });

    it('should have a summary', () => {
      expect(guide?.summary).toBeTruthy();
      expect(guide?.summary.length).toBeGreaterThan(20);
    });

    it('should be in technical category', () => {
      expect(guide?.category).toBe('technical');
    });

    it('should have an icon', () => {
      expect(guide?.icon).toBe('🔒');
    });

    it('should have read time between 4-6 minutes', () => {
      expect(guide?.readTime).toBeGreaterThanOrEqual(4);
      expect(guide?.readTime).toBeLessThanOrEqual(6);
    });
  });

  describe('Content Sections', () => {
    it('should have at least 5 content sections', () => {
      expect(guide?.sections.length).toBeGreaterThanOrEqual(5);
    });

    it('should have all required topic sections', () => {
      const sectionTitles = guide?.sections.map((s) => s.title.toLowerCase()) || [];
      
      // Required topics from acceptance criteria
      const requiredTopics = [
        'what is https',
        'seo benefits',
        'ssl certificate',
        'security header',
        'mixed content',
      ];

      requiredTopics.forEach((topic) => {
        const hasTopic = sectionTitles.some((title) => 
          title.includes(topic) || title.includes(topic.replace(' ', '-'))
        );
        expect(hasTopic).toBe(true);
      });
    });

    it('should have valid section structure', () => {
      guide?.sections.forEach((section) => {
        expect(isGuideSection(section)).toBe(true);
      });
    });

    it('should have meaningful content in each section', () => {
      guide?.sections.forEach((section) => {
        expect(section.content.length).toBeGreaterThan(100);
      });
    });
  });

  describe('Code Examples', () => {
    it('should include .htaccess examples', () => {
      const hasHtaccessExample = guide?.sections.some((section) =>
        section.codeExamples?.some(
          (example) =>
            example.code.includes('RewriteEngine') ||
            example.code.includes('RewriteCond') ||
            example.code.includes('.htaccess')
        )
      );
      expect(hasHtaccessExample).toBe(true);
    });

    it('should have properly formatted code examples', () => {
      guide?.sections.forEach((section) => {
        section.codeExamples?.forEach((example) => {
          expect(example.language).toBeTruthy();
          expect(example.code.length).toBeGreaterThan(0);
        });
      });
    });
  });

  describe('Checklist', () => {
    it('should have 5-10 checklist items', () => {
      expect(guide?.checklist.length).toBeGreaterThanOrEqual(5);
      expect(guide?.checklist.length).toBeLessThanOrEqual(10);
    });

    it('should have valid checklist item structure', () => {
      guide?.checklist.forEach((item) => {
        expect(isChecklistItem(item)).toBe(true);
      });
    });

    it('should have at least one critical priority item', () => {
      const hasCritical = guide?.checklist.some((item) => item.priority === 'critical');
      expect(hasCritical).toBe(true);
    });

    it('should have actionable checklist items', () => {
      guide?.checklist.forEach((item) => {
        // Each item should start with a verb or be a clear action
        expect(item.text.length).toBeGreaterThan(10);
      });
    });
  });

  describe('Related Guides', () => {
    it('should include canonical-urls as related guide', () => {
      expect(guide?.relatedGuides).toContain('canonical-urls');
    });

    it('should include external-links as related guide', () => {
      expect(guide?.relatedGuides).toContain('external-links');
    });

    it('should have 2-4 related guides', () => {
      expect(guide?.relatedGuides.length).toBeGreaterThanOrEqual(2);
      expect(guide?.relatedGuides.length).toBeLessThanOrEqual(4);
    });
  });

  describe('Content Quality', () => {
    it('should mention Let\'s Encrypt for free SSL', () => {
      const allContent = guide?.sections.map((s) => s.content).join(' ') || '';
      expect(allContent.toLowerCase()).toContain("let's encrypt");
    });

    it('should mention HSTS security header', () => {
      const allContent = guide?.sections.map((s) => s.content).join(' ') || '';
      expect(allContent).toMatch(/HSTS|HTTP Strict Transport Security/i);
    });

    it('should mention X-Frame-Options header', () => {
      const allContent = guide?.sections.map((s) => s.content).join(' ') || '';
      expect(allContent).toMatch(/X-Frame-Options/i);
    });

    it('should mention redirect configuration', () => {
      const allContent = guide?.sections.map((s) => s.content).join(' ') || '';
      expect(allContent.toLowerCase()).toMatch(/redirect|301/);
    });

    it('should mention browser security warnings', () => {
      const allContent = guide?.sections.map((s) => s.content).join(' ') || '';
      expect(allContent.toLowerCase()).toMatch(/browser.*warning|warning.*browser|not secure/i);
    });
  });
});
