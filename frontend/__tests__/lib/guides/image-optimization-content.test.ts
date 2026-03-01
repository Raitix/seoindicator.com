/**
 * Tests for Image Optimization Guide Content
 * Task: TASK-20-10 - Write Content for Image Optimization Guide
 * 
 * These tests validate that the Image Optimization guide content in the registry
 * meets all acceptance criteria for structure, quality, and completeness.
 */

import {
  getGuideBySlug,
  getAllGuides,
} from '@/lib/guides/registry';
import {
  isSEOGuide,
  isValidPriority,
} from '@/lib/guides/types';

// ============================================================================
// Guide Existence Tests
// ============================================================================

describe('Image Optimization Guide - Existence', () => {
  describe('CO-001: Guide exists in registry', () => {
    it('should return SEOGuide object for image-optimization slug', () => {
      const guide = getGuideBySlug('image-optimization');
      expect(guide).not.toBeNull();
    });

    it('should return valid SEOGuide type', () => {
      const guide = getGuideBySlug('image-optimization');
      expect(guide).not.toBeNull();
      if (guide) {
        expect(isSEOGuide(guide)).toBe(true);
      }
    });
  });

  describe('CO-002: Guide has correct title', () => {
    it('should have title "Image Optimization for Web"', () => {
      const guide = getGuideBySlug('image-optimization');
      expect(guide).not.toBeNull();
      expect(guide?.title).toBe('Image Optimization for Web');
    });
  });

  describe('CO-003: Guide has non-empty summary', () => {
    it('should have summary with length > 20 characters', () => {
      const guide = getGuideBySlug('image-optimization');
      expect(guide).not.toBeNull();
      expect(guide?.summary.length).toBeGreaterThan(20);
    });

    it('should have meaningful summary about image optimization', () => {
      const guide = getGuideBySlug('image-optimization');
      expect(guide).not.toBeNull();
      expect(guide?.summary.toLowerCase()).toMatch(/image|optim|load|quality/);
    });
  });
});

// ============================================================================
// Core Web Vitals Section Tests
// ============================================================================

describe('Image Optimization Guide - Core Web Vitals', () => {
  const guide = getGuideBySlug('image-optimization');

  describe('CW-001: Has Core Web Vitals section', () => {
    it('should have a section about Core Web Vitals or why optimization matters', () => {
      expect(guide).not.toBeNull();
      if (!guide) return;

      const hasCwvSection = guide.sections.some(
        (section) =>
          section.title.toLowerCase().includes('why') ||
          section.title.toLowerCase().includes('matter') ||
          section.title.toLowerCase().includes('core web vital') ||
          section.title.toLowerCase().includes('performance')
      );
      expect(hasCwvSection).toBe(true);
    });
  });

  describe('CW-002: Section mentions LCP', () => {
    it('should mention Largest Contentful Paint or LCP', () => {
      expect(guide).not.toBeNull();
      if (!guide) return;

      const content = guide.sections.map((s) => s.content).join(' ').toLowerCase();
      const mentionsLCP =
        content.includes('largest contentful paint') ||
        content.includes('lcp');
      expect(mentionsLCP).toBe(true);
    });
  });

  describe('CW-003: Section mentions CLS', () => {
    it('should mention Cumulative Layout Shift or CLS', () => {
      expect(guide).not.toBeNull();
      if (!guide) return;

      const content = guide.sections.map((s) => s.content).join(' ').toLowerCase();
      const mentionsCLS =
        content.includes('cumulative layout shift') ||
        content.includes('cls');
      expect(mentionsCLS).toBe(true);
    });
  });

  describe('CW-004: Section explains impact', () => {
    it('should explain performance or SEO impact of image optimization', () => {
      expect(guide).not.toBeNull();
      if (!guide) return;

      const content = guide.sections.map((s) => s.content).join(' ').toLowerCase();
      const mentionsImpact =
        content.includes('seo') ||
        content.includes('ranking') ||
        content.includes('performance') ||
        content.includes('user experience') ||
        content.includes('page speed');
      expect(mentionsImpact).toBe(true);
    });
  });

  describe('CW-005: Section has valid content', () => {
    it('should have substantial content in the why/impact section', () => {
      expect(guide).not.toBeNull();
      if (!guide) return;

      const whySection = guide.sections.find(
        (s) =>
          s.title.toLowerCase().includes('why') ||
          s.title.toLowerCase().includes('matter')
      );
      expect(whySection).toBeDefined();
      if (whySection) {
        expect(whySection.content.length).toBeGreaterThan(200);
      }
    });
  });
});

// ============================================================================
// File Formats Section Tests
// ============================================================================

describe('Image Optimization Guide - File Formats', () => {
  const guide = getGuideBySlug('image-optimization');

  describe('FF-001: Has file formats section', () => {
    it('should have a section about image file formats', () => {
      expect(guide).not.toBeNull();
      if (!guide) return;

      const hasFormatSection = guide.sections.some(
        (section) =>
          section.title.toLowerCase().includes('format') ||
          section.title.toLowerCase().includes('webp') ||
          section.title.toLowerCase().includes('file type')
      );
      expect(hasFormatSection).toBe(true);
    });
  });

  describe('FF-002: Mentions WebP format', () => {
    it('should mention WebP format', () => {
      expect(guide).not.toBeNull();
      if (!guide) return;

      const content = guide.sections.map((s) => s.content).join(' ').toLowerCase();
      expect(content).toContain('webp');
    });
  });

  describe('FF-003: Mentions AVIF format', () => {
    it('should mention AVIF format', () => {
      expect(guide).not.toBeNull();
      if (!guide) return;

      const content = guide.sections.map((s) => s.content).join(' ').toLowerCase();
      expect(content).toContain('avif');
    });
  });

  describe('FF-004: Mentions JPEG format', () => {
    it('should mention JPEG or JPG format', () => {
      expect(guide).not.toBeNull();
      if (!guide) return;

      const content = guide.sections.map((s) => s.content).join(' ').toLowerCase();
      const mentionsJPEG = content.includes('jpeg') || content.includes('jpg');
      expect(mentionsJPEG).toBe(true);
    });
  });

  describe('FF-005: Mentions PNG format', () => {
    it('should mention PNG format', () => {
      expect(guide).not.toBeNull();
      if (!guide) return;

      const content = guide.sections.map((s) => s.content).join(' ').toLowerCase();
      expect(content).toContain('png');
    });
  });

  describe('FF-006: Compares format benefits', () => {
    it('should explain when to use each format', () => {
      expect(guide).not.toBeNull();
      if (!guide) return;

      const formatSection = guide.sections.find(
        (s) =>
          s.title.toLowerCase().includes('format') ||
          s.title.toLowerCase().includes('file type')
      );
      expect(formatSection).toBeDefined();
      if (formatSection) {
        const content = formatSection.content.toLowerCase();
        // Should compare formats or explain use cases
        const hasComparison =
          content.includes('use') ||
          content.includes('best for') ||
          content.includes('suitable') ||
          content.includes('compare');
        expect(hasComparison).toBe(true);
      }
    });
  });
});

// ============================================================================
// Compression Section Tests
// ============================================================================

describe('Image Optimization Guide - Compression and Resizing', () => {
  const guide = getGuideBySlug('image-optimization');

  describe('CR-001: Has compression section', () => {
    it('should have a section about compression or resizing', () => {
      expect(guide).not.toBeNull();
      if (!guide) return;

      const hasCompressionSection = guide.sections.some(
        (section) =>
          section.title.toLowerCase().includes('compress') ||
          section.title.toLowerCase().includes('resize') ||
          section.title.toLowerCase().includes('size')
      );
      expect(hasCompressionSection).toBe(true);
    });
  });

  describe('CR-002: Mentions lossy compression', () => {
    it('should mention lossy or lossless compression', () => {
      expect(guide).not.toBeNull();
      if (!guide) return;

      const content = guide.sections.map((s) => s.content).join(' ').toLowerCase();
      const mentionsCompression =
        content.includes('lossy') ||
        content.includes('lossless') ||
        content.includes('compression');
      expect(mentionsCompression).toBe(true);
    });
  });

  describe('CR-003: Mentions resizing techniques', () => {
    it('should mention resizing or image dimensions', () => {
      expect(guide).not.toBeNull();
      if (!guide) return;

      const content = guide.sections.map((s) => s.content).join(' ').toLowerCase();
      const mentionsResizing =
        content.includes('resize') ||
        content.includes('dimension') ||
        content.includes('width') ||
        content.includes('height') ||
        content.includes('scale');
      expect(mentionsResizing).toBe(true);
    });
  });

  describe('CR-004: Mentions tools/services', () => {
    it('should mention compression tools or services', () => {
      expect(guide).not.toBeNull();
      if (!guide) return;

      const content = guide.sections.map((s) => s.content).join(' ').toLowerCase();
      const mentionsTools =
        content.includes('tool') ||
        content.includes('squish') ||
        content.includes('tinypng') ||
        content.includes('imagemagick') ||
        content.includes('sharp') ||
        content.includes('plugin') ||
        content.includes('service');
      expect(mentionsTools).toBe(true);
    });
  });

  describe('CR-005: Section has valid content', () => {
    it('should have substantial content in compression section', () => {
      expect(guide).not.toBeNull();
      if (!guide) return;

      const compressionSection = guide.sections.find(
        (s) =>
          s.title.toLowerCase().includes('compress') ||
          s.title.toLowerCase().includes('resize')
      );
      expect(compressionSection).toBeDefined();
      if (compressionSection) {
        expect(compressionSection.content.length).toBeGreaterThan(150);
      }
    });
  });
});

// ============================================================================
// Lazy Loading Section Tests
// ============================================================================

describe('Image Optimization Guide - Lazy Loading', () => {
  const guide = getGuideBySlug('image-optimization');

  describe('LL-001: Has lazy loading section', () => {
    it('should have a section about lazy loading', () => {
      expect(guide).not.toBeNull();
      if (!guide) return;

      const hasLazyLoadingSection = guide.sections.some(
        (section) =>
          section.title.toLowerCase().includes('lazy') ||
          section.title.toLowerCase().includes('loading')
      );
      expect(hasLazyLoadingSection).toBe(true);
    });
  });

  describe('LL-002: Explains loading attribute', () => {
    it('should mention loading="lazy" attribute', () => {
      expect(guide).not.toBeNull();
      if (!guide) return;

      const content = guide.sections.map((s) => s.content).join(' ').toLowerCase();
      expect(content).toContain('loading');
    });
  });

  describe('LL-003: Mentions native lazy loading', () => {
    it('should mention native lazy loading or the loading attribute', () => {
      expect(guide).not.toBeNull();
      if (!guide) return;

      const content = guide.sections.map((s) => s.content).join(' ').toLowerCase();
      const mentionsNative =
        content.includes('native') ||
        content.includes('loading attribute') ||
        content.includes('loading="lazy"');
      expect(mentionsNative).toBe(true);
    });
  });

  describe('LL-004: Mentions JavaScript fallback', () => {
    it('should mention Intersection Observer or JavaScript fallback', () => {
      expect(guide).not.toBeNull();
      if (!guide) return;

      const content = guide.sections.map((s) => s.content).join(' ').toLowerCase();
      const mentionsFallback =
        content.includes('intersection') ||
        content.includes('observer') ||
        content.includes('javascript') ||
        content.includes('fallback') ||
        content.includes('polyfill');
      expect(mentionsFallback).toBe(true);
    });
  });

  describe('LL-005: Section has valid content', () => {
    it('should have substantial content in lazy loading section', () => {
      expect(guide).not.toBeNull();
      if (!guide) return;

      const lazySection = guide.sections.find(
        (s) =>
          s.title.toLowerCase().includes('lazy') ||
          s.title.toLowerCase().includes('loading')
      );
      expect(lazySection).toBeDefined();
      if (lazySection) {
        expect(lazySection.content.length).toBeGreaterThan(200);
      }
    });
  });

  describe('LL-006: Has code examples', () => {
    it('should have code examples for lazy loading', () => {
      expect(guide).not.toBeNull();
      if (!guide) return;

      const lazySection = guide.sections.find(
        (s) =>
          s.title.toLowerCase().includes('lazy') ||
          s.title.toLowerCase().includes('loading')
      );
      expect(lazySection).toBeDefined();
      if (lazySection) {
        expect(lazySection.codeExamples).toBeDefined();
        expect(lazySection.codeExamples?.length).toBeGreaterThan(0);
      }
    });
  });
});

// ============================================================================
// Best Practices Checklist Tests
// ============================================================================

describe('Image Optimization Guide - Checklist', () => {
  const guide = getGuideBySlug('image-optimization');

  describe('BC-001: Checklist has 5-10 items', () => {
    it('should have between 5 and 10 checklist items', () => {
      expect(guide).not.toBeNull();
      if (!guide) return;

      expect(guide.checklist.length).toBeGreaterThanOrEqual(5);
      expect(guide.checklist.length).toBeLessThanOrEqual(10);
    });
  });

  describe('BC-002: All items have valid text', () => {
    it('should have non-empty text for each checklist item', () => {
      expect(guide).not.toBeNull();
      if (!guide) return;

      guide.checklist.forEach((item) => {
        expect(item.text.length).toBeGreaterThan(10);
      });
    });
  });

  describe('BC-003: All items have valid priority', () => {
    it('should have valid priority for each checklist item', () => {
      expect(guide).not.toBeNull();
      if (!guide) return;

      guide.checklist.forEach((item) => {
        expect(isValidPriority(item.priority)).toBe(true);
      });
    });
  });

  describe('BC-004: At least one critical item', () => {
    it('should have at least one critical priority item', () => {
      expect(guide).not.toBeNull();
      if (!guide) return;

      const hasCritical = guide.checklist.some(
        (item) => item.priority === 'critical'
      );
      expect(hasCritical).toBe(true);
    });
  });

  describe('BC-005: Covers key optimizations', () => {
    it('should cover format optimization in checklist', () => {
      expect(guide).not.toBeNull();
      if (!guide) return;

      const content = guide.checklist.map((i) => i.text.toLowerCase()).join(' ');
      const coversFormats =
        content.includes('webp') ||
        content.includes('avif') ||
        content.includes('format');
      expect(coversFormats).toBe(true);
    });

    it('should cover lazy loading in checklist', () => {
      expect(guide).not.toBeNull();
      if (!guide) return;

      const content = guide.checklist.map((i) => i.text.toLowerCase()).join(' ');
      const coversLazyLoading =
        content.includes('lazy') ||
        content.includes('loading');
      expect(coversLazyLoading).toBe(true);
    });

    it('should cover compression in checklist', () => {
      expect(guide).not.toBeNull();
      if (!guide) return;

      const content = guide.checklist.map((i) => i.text.toLowerCase()).join(' ');
      const coversCompression =
        content.includes('compress') ||
        content.includes('size') ||
        content.includes('reduce');
      expect(coversCompression).toBe(true);
    });
  });
});

// ============================================================================
// Read Time Tests
// ============================================================================

describe('Image Optimization Guide - Read Time', () => {
  const guide = getGuideBySlug('image-optimization');

  describe('RT-001: Read time is set', () => {
    it('should have a readTime property that is a number', () => {
      expect(guide).not.toBeNull();
      if (!guide) return;

      expect(typeof guide.readTime).toBe('number');
    });
  });

  describe('RT-002: Read time in valid range', () => {
    it('should have readTime between 4 and 6 minutes', () => {
      expect(guide).not.toBeNull();
      if (!guide) return;

      expect(guide.readTime).toBeGreaterThanOrEqual(4);
      expect(guide.readTime).toBeLessThanOrEqual(6);
    });
  });

  describe('RT-003: Read time matches content', () => {
    it('should have readTime that roughly matches content length', () => {
      expect(guide).not.toBeNull();
      if (!guide) return;

      // Calculate approximate word count
      const allContent = guide.sections
        .map((s) => s.title + ' ' + s.content)
        .join(' ');
      const wordCount = allContent.split(/\s+/).length;

      // Average reading speed: 200-250 words per minute
      const expectedReadTime = Math.ceil(wordCount / 225);

      // Allow for ±1 minute variance
      expect(Math.abs(guide.readTime - expectedReadTime)).toBeLessThanOrEqual(1);
    });
  });
});

// ============================================================================
// Related Guides Tests
// ============================================================================

describe('Image Optimization Guide - Related Guides', () => {
  const guide = getGuideBySlug('image-optimization');

  describe('RG-001: Has related guides array', () => {
    it('should have relatedGuides array', () => {
      expect(guide).not.toBeNull();
      if (!guide) return;

      expect(Array.isArray(guide.relatedGuides)).toBe(true);
    });
  });

  describe('RG-002: Contains page-speed', () => {
    it('should include page-speed in related guides', () => {
      expect(guide).not.toBeNull();
      if (!guide) return;

      expect(guide.relatedGuides).toContain('page-speed');
    });
  });

  describe('RG-003: Contains image-alt-text', () => {
    it('should include image-alt-text in related guides', () => {
      expect(guide).not.toBeNull();
      if (!guide) return;

      expect(guide.relatedGuides).toContain('image-alt-text');
    });
  });

  describe('RG-004: Related guides exist', () => {
    it('should have all related guides exist in registry', () => {
      expect(guide).not.toBeNull();
      if (!guide) return;

      guide.relatedGuides.forEach((slug) => {
        const relatedGuide = getGuideBySlug(slug);
        expect(relatedGuide).not.toBeNull();
      });
    });
  });
});

// ============================================================================
// Guide Registry Integration Tests
// ============================================================================

describe('Image Optimization Guide - Registry Integration', () => {
  describe('GR-001: Guide in registry', () => {
    it('should be included in getAllGuides()', () => {
      const allGuides = getAllGuides();
      const imageOptimizationGuide = allGuides.find(
        (g) => g.slug === 'image-optimization'
      );
      expect(imageOptimizationGuide).toBeDefined();
    });
  });

  describe('GR-002: getGuideBySlug returns complete guide', () => {
    it('should return guide with all required properties', () => {
      const guide = getGuideBySlug('image-optimization');
      expect(guide).not.toBeNull();
      if (!guide) return;

      expect(guide.slug).toBeDefined();
      expect(guide.title).toBeDefined();
      expect(guide.summary).toBeDefined();
      expect(guide.category).toBeDefined();
      expect(guide.icon).toBeDefined();
      expect(guide.readTime).toBeDefined();
      expect(guide.sections).toBeDefined();
      expect(guide.checklist).toBeDefined();
      expect(guide.relatedGuides).toBeDefined();
    });
  });

  describe('GR-003: Guide validates with type guard', () => {
    it('should pass isSEOGuide validation', () => {
      const guide = getGuideBySlug('image-optimization');
      expect(guide).not.toBeNull();
      if (guide) {
        expect(isSEOGuide(guide)).toBe(true);
      }
    });
  });
});

// ============================================================================
// Content Quality Tests
// ============================================================================

describe('Image Optimization Guide - Content Quality', () => {
  const guide = getGuideBySlug('image-optimization');

  describe('Content completeness', () => {
    it('should have at least 5 sections', () => {
      expect(guide).not.toBeNull();
      if (!guide) return;

      expect(guide.sections.length).toBeGreaterThanOrEqual(5);
    });

    it('should have "What is Image Optimization" section', () => {
      expect(guide).not.toBeNull();
      if (!guide) return;

      const hasWhatIsSection = guide.sections.some(
        (s) =>
          s.title.toLowerCase().includes('what is') ||
          s.title.toLowerCase().includes('introduction')
      );
      expect(hasWhatIsSection).toBe(true);
    });
  });

  describe('Section content quality', () => {
    it('should have substantial content in each section', () => {
      expect(guide).not.toBeNull();
      if (!guide) return;

      guide.sections.forEach((section) => {
        expect(section.content.length).toBeGreaterThan(100);
      });
    });

    it('should have unique section titles', () => {
      expect(guide).not.toBeNull();
      if (!guide) return;

      const titles = guide.sections.map((s) => s.title);
      const uniqueTitles = new Set(titles);
      expect(uniqueTitles.size).toBe(titles.length);
    });
  });

  describe('Content formatting', () => {
    it('should use markdown formatting in content', () => {
      expect(guide).not.toBeNull();
      if (!guide) return;

      const hasMarkdown = guide.sections.some(
        (s) =>
          s.content.includes('**') ||
          s.content.includes('- ') ||
          s.content.includes('1.') ||
          s.content.includes('`')
      );
      expect(hasMarkdown).toBe(true);
    });
  });
});

// ============================================================================
// Security Tests
// ============================================================================

describe('Image Optimization Guide - Security', () => {
  const guide = getGuideBySlug('image-optimization');

  describe('XSS prevention', () => {
    it('should not contain script tags in content', () => {
      expect(guide).not.toBeNull();
      if (!guide) return;

      guide.sections.forEach((section) => {
        expect(
          section.content.toLowerCase().includes('<script')
        ).toBe(false);
      });
    });

    it('should not contain event handlers in content', () => {
      expect(guide).not.toBeNull();
      if (!guide) return;

      guide.sections.forEach((section) => {
        expect(
          section.content.toLowerCase().includes('onclick')
        ).toBe(false);
        expect(
          section.content.toLowerCase().includes('onerror')
        ).toBe(false);
      });
    });
  });

  describe('Code example safety', () => {
    it('should not have executable code outside code blocks', () => {
      expect(guide).not.toBeNull();
      if (!guide) return;

      // Code examples should be in the codeExamples array, not inline scripts
      guide.sections.forEach((section) => {
        const content = section.content;
        // Content can have code blocks (```), but not raw scripts
        expect(content.toLowerCase()).not.toContain('<script>');
      });
    });
  });
});
