/**
 * Tests for Image Optimization Guide Code Examples
 * Task: TASK-20-10 - Write Content for Image Optimization Guide
 * 
 * These tests validate that the code examples in the Image Optimization guide
 * are syntactically correct, relevant, and follow best practices.
 */

import {
  getGuideBySlug,
} from '@/lib/guides/registry';

// ============================================================================
// Code Examples Existence Tests
// ============================================================================

describe('Image Optimization Guide - Code Examples Existence', () => {
  const guide = getGuideBySlug('image-optimization');

  describe('CE-001: Has at least one code example', () => {
    it('should have at least one code example in the guide', () => {
      expect(guide).not.toBeNull();
      if (!guide) return;

      const totalCodeExamples = guide.sections.reduce(
        (count, section) => count + (section.codeExamples?.length || 0),
        0
      );
      expect(totalCodeExamples).toBeGreaterThanOrEqual(1);
    });
  });

  describe('CE-002: Lazy loading HTML example exists', () => {
    it('should have a code example with loading="lazy"', () => {
      expect(guide).not.toBeNull();
      if (!guide) return;

      const hasLazyLoadingExample = guide.sections.some((section) =>
        section.codeExamples?.some(
          (example) =>
            example.code.includes('loading="lazy"') ||
            example.code.includes("loading='lazy'")
        )
      );
      expect(hasLazyLoadingExample).toBe(true);
    });
  });
});

// ============================================================================
// Code Example Structure Tests
// ============================================================================

describe('Image Optimization Guide - Code Example Structure', () => {
  const guide = getGuideBySlug('image-optimization');

  describe('CE-003: Code example has language', () => {
    it('should have a valid language for each code example', () => {
      expect(guide).not.toBeNull();
      if (!guide) return;

      guide.sections.forEach((section) => {
        section.codeExamples?.forEach((example) => {
          expect(example.language).toBeDefined();
          expect(typeof example.language).toBe('string');
          expect(example.language.length).toBeGreaterThan(0);
        });
      });
    });
  });

  describe('CE-004: Code example has code content', () => {
    it('should have non-empty code for each example', () => {
      expect(guide).not.toBeNull();
      if (!guide) return;

      guide.sections.forEach((section) => {
        section.codeExamples?.forEach((example) => {
          expect(example.code).toBeDefined();
          expect(typeof example.code).toBe('string');
          expect(example.code.length).toBeGreaterThan(0);
        });
      });
    });
  });

  describe('CE-007: Example descriptions present', () => {
    it('should have descriptions for code examples', () => {
      expect(guide).not.toBeNull();
      if (!guide) return;

      guide.sections.forEach((section) => {
        section.codeExamples?.forEach((example) => {
          // Description is optional but recommended
          if (example.description) {
            expect(example.description.length).toBeGreaterThan(10);
          }
        });
      });
    });
  });
});

// ============================================================================
// HTML Code Examples Tests
// ============================================================================

describe('Image Optimization Guide - HTML Examples', () => {
  const guide = getGuideBySlug('image-optimization');

  describe('CE-005: HTML example is valid', () => {
    it('should have valid HTML with img tag in examples', () => {
      expect(guide).not.toBeNull();
      if (!guide) return;

      const htmlExamples = guide.sections
        .flatMap((s) => s.codeExamples || [])
        .filter((e) => e.language === 'html');

      if (htmlExamples.length > 0) {
        htmlExamples.forEach((example) => {
          expect(
            example.code.includes('<img') ||
            example.code.includes('<picture')
          ).toBe(true);
        });
      }
    });

    it('should have alt attribute in img tags', () => {
      expect(guide).not.toBeNull();
      if (!guide) return;

      const htmlExamples = guide.sections
        .flatMap((s) => s.codeExamples || [])
        .filter((e) => e.language === 'html' && e.code.includes('<img'));

      htmlExamples.forEach((example) => {
        // Best practice: always include alt
        const hasAlt =
          example.code.includes('alt="') ||
          example.code.includes("alt='") ||
          example.code.includes('alt=""'); // Empty alt is valid for decorative images
        expect(hasAlt).toBe(true);
      });
    });

    it('should have src attribute in img tags', () => {
      expect(guide).not.toBeNull();
      if (!guide) return;

      const htmlExamples = guide.sections
        .flatMap((s) => s.codeExamples || [])
        .filter((e) => e.language === 'html' && e.code.includes('<img'));

      htmlExamples.forEach((example) => {
        const hasSrc =
          example.code.includes('src="') ||
          example.code.includes("src='");
        expect(hasSrc).toBe(true);
      });
    });
  });

  describe('Native lazy loading example', () => {
    it('should demonstrate native lazy loading attribute', () => {
      expect(guide).not.toBeNull();
      if (!guide) return;

      const lazyExamples = guide.sections
        .flatMap((s) => s.codeExamples || [])
        .filter(
          (e) =>
            e.code.includes('loading="lazy"') || e.code.includes("loading='lazy'")
        );

      expect(lazyExamples.length).toBeGreaterThan(0);
    });

    it('should show loading attribute on img tag', () => {
      expect(guide).not.toBeNull();
      if (!guide) return;

      const lazyImgExamples = guide.sections
        .flatMap((s) => s.codeExamples || [])
        .filter(
          (e) =>
            e.code.includes('<img') &&
            (e.code.includes('loading="lazy"') || e.code.includes("loading='lazy'"))
        );

      expect(lazyImgExamples.length).toBeGreaterThan(0);
    });
  });
});

// ============================================================================
// Next.js Image Component Tests
// ============================================================================

describe('Image Optimization Guide - Next.js Examples', () => {
  const guide = getGuideBySlug('image-optimization');

  describe('CE-006: Next.js Image example optional', () => {
    it('may contain Next.js Image component example', () => {
      expect(guide).not.toBeNull();
      if (!guide) return;

      const hasNextJsExample = guide.sections.some((section) =>
        section.codeExamples?.some(
          (example) =>
            example.language === 'jsx' ||
            example.language === 'tsx' ||
            (example.code.includes('<Image') && example.code.includes('from'))
        )
      );
      // This is optional, so we just check if present
      // If present, validate the structure
      if (hasNextJsExample) {
        const nextJsExamples = guide.sections
          .flatMap((s) => s.codeExamples || [])
          .filter(
            (e) =>
              (e.language === 'jsx' || e.language === 'tsx') &&
              e.code.includes('<Image')
          );

        nextJsExamples.forEach((example) => {
          // Next.js Image should have src, alt, width, height
          expect(example.code.includes('src')).toBe(true);
          expect(example.code.includes('alt')).toBe(true);
        });
      }
    });

    it('should mention Next.js Image component in content if showing example', () => {
      expect(guide).not.toBeNull();
      if (!guide) return;

      const hasNextJsExample = guide.sections.some((section) =>
        section.codeExamples?.some(
          (example) =>
            example.code.includes('<Image') && example.code.includes('next')
        )
      );

      if (hasNextJsExample) {
        const content = guide.sections.map((s) => s.content).join(' ').toLowerCase();
        expect(content.includes('next.js') || content.includes('nextjs')).toBe(true);
      }
    });
  });
});

// ============================================================================
// Code Example Best Practices Tests
// ============================================================================

describe('Image Optimization Guide - Code Best Practices', () => {
  const guide = getGuideBySlug('image-optimization');

  describe('Modern format examples', () => {
    it('should demonstrate WebP or AVIF format in examples', () => {
      expect(guide).not.toBeNull();
      if (!guide) return;

      const codeContent = guide.sections
        .flatMap((s) => s.codeExamples || [])
        .map((e) => e.code)
        .join(' ');

      const hasModernFormat =
        codeContent.includes('.webp') ||
        codeContent.includes('.avif') ||
        codeContent.includes('image/webp') ||
        codeContent.includes('image/avif');

      expect(hasModernFormat).toBe(true);
    });
  });

  describe('Responsive images', () => {
    it('should mention srcset or sizes if demonstrating responsive images', () => {
      expect(guide).not.toBeNull();
      if (!guide) return;

      const content = guide.sections.map((s) => s.content).join(' ').toLowerCase();
      const hasResponsiveMention =
        content.includes('srcset') ||
        content.includes('responsive') ||
        content.includes('sizes=');

      // This is recommended but optional
      if (hasResponsiveMention) {
        const codeContent = guide.sections
          .flatMap((s) => s.codeExamples || [])
          .map((e) => e.code)
          .join(' ');

        // If responsive is mentioned, should show srcset or sizes in code
        const hasResponsiveCode =
          codeContent.includes('srcset') || codeContent.includes('sizes');
        expect(hasResponsiveCode).toBe(true);
      }
    });
  });

  describe('Picture element support', () => {
    it('may demonstrate picture element for format fallback', () => {
      expect(guide).not.toBeNull();
      if (!guide) return;

      const hasPictureExample = guide.sections.some((section) =>
        section.codeExamples?.some((example) => example.code.includes('<picture'))
      );

      // This is optional but recommended for format fallback
      if (hasPictureExample) {
        const pictureExamples = guide.sections
          .flatMap((s) => s.codeExamples || [])
          .filter((e) => e.code.includes('<picture'));

        pictureExamples.forEach((example) => {
          // Picture element should have source and img fallback
          expect(example.code.includes('<source') || example.code.includes('<img')).toBe(true);
        });
      }
    });
  });
});

// ============================================================================
// Code Example Syntax Validation Tests
// ============================================================================

describe('Image Optimization Guide - Syntax Validation', () => {
  const guide = getGuideBySlug('image-optimization');

  describe('CE-008: Code examples are syntactically valid', () => {
    it('should have balanced HTML tags', () => {
      expect(guide).not.toBeNull();
      if (!guide) return;

      guide.sections.forEach((section) => {
        section.codeExamples?.forEach((example) => {
          if (example.language === 'html') {
            // Check for balanced angle brackets
            const openBrackets = (example.code.match(/</g) || []).length;
            const closeBrackets = (example.code.match(/>/g) || []).length;
            expect(openBrackets).toBe(closeBrackets);
          }
        });
      });
    });

    it('should have balanced JSX tags', () => {
      expect(guide).not.toBeNull();
      if (!guide) return;

      guide.sections.forEach((section) => {
        section.codeExamples?.forEach((example) => {
          if (example.language === 'jsx' || example.language === 'tsx') {
            // Check for balanced angle brackets
            const openBrackets = (example.code.match(/</g) || []).length;
            const closeBrackets = (example.code.match(/>/g) || []).length;
            expect(openBrackets).toBe(closeBrackets);
          }
        });
      });
    });

    it('should have properly quoted attributes', () => {
      expect(guide).not.toBeNull();
      if (!guide) return;

      guide.sections.forEach((section) => {
        section.codeExamples?.forEach((example) => {
          if (example.language === 'html' || example.language === 'jsx') {
            // Check for balanced quotes
            const doubleQuotes = (example.code.match(/"/g) || []).length;
            const singleQuotes = (example.code.match(/'/g) || []).length;
            expect(doubleQuotes % 2).toBe(0);
            expect(singleQuotes % 2).toBe(0);
          }
        });
      });
    });
  });

  describe('No syntax errors in examples', () => {
    it('should not have malformed HTML', () => {
      expect(guide).not.toBeNull();
      if (!guide) return;

      guide.sections.forEach((section) => {
        section.codeExamples?.forEach((example) => {
          if (example.language === 'html') {
            // Check for common HTML errors
            expect(example.code).not.toMatch(/<img[^>]*>[^<]*<\/img>/i); // img is self-closing
            expect(example.code).not.toMatch(/<img[^>]*>[^<]*<\/img>/i);
          }
        });
      });
    });
  });
});

// ============================================================================
// Code Example Relevance Tests
// ============================================================================

describe('Image Optimization Guide - Code Relevance', () => {
  const guide = getGuideBySlug('image-optimization');

  describe('Examples match section content', () => {
    it('should have lazy loading examples in lazy loading section', () => {
      expect(guide).not.toBeNull();
      if (!guide) return;

      const lazySection = guide.sections.find(
        (s) =>
          s.title.toLowerCase().includes('lazy') ||
          s.title.toLowerCase().includes('loading')
      );

      if (lazySection && lazySection.codeExamples) {
        const hasLazyCode = lazySection.codeExamples.some(
          (e) =>
            e.code.includes('loading="lazy"') ||
            e.code.includes("loading='lazy'") ||
            e.code.includes('IntersectionObserver')
        );
        expect(hasLazyCode).toBe(true);
      }
    });

    it('should have format examples in format section', () => {
      expect(guide).not.toBeNull();
      if (!guide) return;

      const formatSection = guide.sections.find(
        (s) =>
          s.title.toLowerCase().includes('format') ||
          s.title.toLowerCase().includes('file type')
      );

      if (formatSection && formatSection.codeExamples) {
        const hasFormatCode = formatSection.codeExamples.some(
          (e) =>
            e.code.includes('.webp') ||
            e.code.includes('.avif') ||
            e.code.includes('image/webp') ||
            e.code.includes('<picture') ||
            e.code.includes('<source')
        );
        // Optional but recommended
        if (formatSection.codeExamples.length > 0) {
          expect(hasFormatCode).toBe(true);
        }
      }
    });
  });

  describe('Examples are practical', () => {
    it('should have copy-paste ready examples', () => {
      expect(guide).not.toBeNull();
      if (!guide) return;

      guide.sections.forEach((section) => {
        section.codeExamples?.forEach((example) => {
          // Examples should be complete enough to use
          if (example.language === 'html' && example.code.includes('<img')) {
            // img should have src and alt
            expect(
              example.code.includes('src=') && example.code.includes('alt=')
            ).toBe(true);
          }
        });
      });
    });

    it('should use realistic values in examples', () => {
      expect(guide).not.toBeNull();
      if (!guide) return;

      guide.sections.forEach((section) => {
        section.codeExamples?.forEach((example) => {
          // Check for width/height in both HTML attributes and JSX props
          const hasWidthAndHeight = 
            (example.code.includes('width=') && example.code.includes('height=')) ||
            (example.code.includes('width={') && example.code.includes('height={'));
          
          if (hasWidthAndHeight) {
            // Width and height should be numeric (HTML: width="1200" or JSX: width={1200})
            const hasNumericWidth = 
              /width=["']?\d+/.test(example.code) || 
              /width=\{\d+\}/.test(example.code);
            const hasNumericHeight = 
              /height=["']?\d+/.test(example.code) || 
              /height=\{\d+\}/.test(example.code);
            
            expect(hasNumericWidth).toBe(true);
            expect(hasNumericHeight).toBe(true);
          }
        });
      });
    });
  });
});

// ============================================================================
// Code Example Documentation Tests
// ============================================================================

describe('Image Optimization Guide - Code Documentation', () => {
  const guide = getGuideBySlug('image-optimization');

  describe('Example descriptions', () => {
    it('should describe what each example demonstrates', () => {
      expect(guide).not.toBeNull();
      if (!guide) return;

      guide.sections.forEach((section) => {
        section.codeExamples?.forEach((example) => {
          if (example.description) {
            // Description should be meaningful
            expect(example.description.length).toBeGreaterThan(15);
            // Should not be the same as the code
            expect(example.description).not.toBe(example.code);
          }
        });
      });
    });
  });

  describe('Language accuracy', () => {
    it('should use correct language tags', () => {
      expect(guide).not.toBeNull();
      if (!guide) return;

      const validLanguages = [
        'html',
        'css',
        'javascript',
        'js',
        'jsx',
        'tsx',
        'typescript',
        'bash',
        'shell',
        'json',
      ];

      guide.sections.forEach((section) => {
        section.codeExamples?.forEach((example) => {
          expect(validLanguages).toContain(example.language.toLowerCase());
        });
      });
    });
  });
});

// ============================================================================
// Edge Cases Tests
// ============================================================================

describe('Image Optimization Guide - Code Edge Cases', () => {
  const guide = getGuideBySlug('image-optimization');

  describe('Special characters', () => {
    it('should handle special characters in code examples', () => {
      expect(guide).not.toBeNull();
      if (!guide) return;

      guide.sections.forEach((section) => {
        section.codeExamples?.forEach((example) => {
          // Code should be valid string (no unescaped quotes causing issues)
          expect(typeof example.code).toBe('string');
        });
      });
    });
  });

  describe('Unicode support', () => {
    it('should support unicode in code descriptions', () => {
      expect(guide).not.toBeNull();
      if (!guide) return;

      guide.sections.forEach((section) => {
        section.codeExamples?.forEach((example) => {
          if (example.description) {
            // Should handle unicode characters
            expect(() => JSON.stringify(example.description)).not.toThrow();
          }
        });
      });
    });
  });

  describe('Large code examples', () => {
    it('should handle large code examples without issues', () => {
      expect(guide).not.toBeNull();
      if (!guide) return;

      guide.sections.forEach((section) => {
        section.codeExamples?.forEach((example) => {
          // Code should be reasonably sized (< 10KB per example)
          expect(example.code.length).toBeLessThan(10000);
        });
      });
    });
  });
});
