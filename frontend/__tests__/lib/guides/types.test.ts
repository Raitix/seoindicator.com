/**
 * Tests for SEO Guide TypeScript Types
 * Task: TASK-20-1 - Create Guide Data Structure and TypeScript Types
 * 
 * These tests verify type safety and runtime validation for:
 * - SEOGuide interface
 * - GuideSection interface
 * - ChecklistItem interface
 * - CodeExample interface
 */

import {
  SEOGuide,
  GuideSection,
  ChecklistItem,
  CodeExample,
  isSEOGuide,
  isGuideSection,
  isChecklistItem,
  isCodeExample,
  isValidCategory,
  isValidPriority,
  GUIDE_CATEGORIES,
  CHECKLIST_PRIORITIES,
} from '@/lib/guides/types';

// ============================================================================
// SEOGuide Interface Tests
// ============================================================================

describe('SEOGuide Interface', () => {
  const validSEOGuide: SEOGuide = {
    slug: 'meta-title',
    title: 'Meta Title Optimization',
    summary: 'Learn how to optimize your page titles for better SEO.',
    category: 'on-page',
    toolSlug: 'meta-tag-analyzer',
    icon: '📝',
    readTime: 5,
    sections: [],
    checklist: [],
    relatedGuides: ['meta-description', 'h1-headings'],
  };

  describe('T-001: Valid SEOGuide object', () => {
    it('should accept a complete SEOGuide with all required fields', () => {
      const result = isSEOGuide(validSEOGuide);
      expect(result).toBe(true);
    });

    it('should accept SEOGuide with minimal required fields', () => {
      const minimalGuide: SEOGuide = {
        slug: 'test-guide',
        title: 'Test Guide',
        summary: 'Test summary',
        category: 'on-page',
        icon: '📄',
        readTime: 3,
        sections: [],
        checklist: [],
        relatedGuides: [],
      };
      expect(isSEOGuide(minimalGuide)).toBe(true);
    });
  });

  describe('T-002: SEOGuide missing slug', () => {
    it('should reject SEOGuide without slug property', () => {
      const invalidGuide = { ...validSEOGuide } as Partial<SEOGuide>;
      delete invalidGuide.slug;
      expect(isSEOGuide(invalidGuide)).toBe(false);
    });
  });

  describe('T-003: SEOGuide missing title', () => {
    it('should reject SEOGuide without title property', () => {
      const invalidGuide = { ...validSEOGuide } as Partial<SEOGuide>;
      delete invalidGuide.title;
      expect(isSEOGuide(invalidGuide)).toBe(false);
    });
  });

  describe('T-004: SEOGuide invalid category', () => {
    it('should reject SEOGuide with invalid category', () => {
      const invalidGuide = { ...validSEOGuide, category: 'invalid' };
      expect(isSEOGuide(invalidGuide)).toBe(false);
    });
  });

  describe('T-005: SEOGuide valid categories', () => {
    it.each(GUIDE_CATEGORIES)('should accept category: %s', (category) => {
      const guide = { ...validSEOGuide, category };
      expect(isSEOGuide(guide)).toBe(true);
    });
  });

  describe('T-006: SEOGuide optional toolSlug', () => {
    it('should accept SEOGuide without toolSlug', () => {
      const guideWithoutTool = { ...validSEOGuide } as Partial<SEOGuide>;
      delete guideWithoutTool.toolSlug;
      expect(isSEOGuide(guideWithoutTool)).toBe(true);
    });
  });

  describe('T-007: SEOGuide with toolSlug', () => {
    it('should accept SEOGuide with toolSlug', () => {
      const guideWithTool = { ...validSEOGuide, toolSlug: 'meta-tag-analyzer' };
      expect(isSEOGuide(guideWithTool)).toBe(true);
    });
  });

  describe('T-008: SEOGuide icon types', () => {
    it('should accept emoji string as icon', () => {
      const guide = { ...validSEOGuide, icon: '📝' };
      expect(isSEOGuide(guide)).toBe(true);
    });

    it('should accept icon component name as string', () => {
      const guide = { ...validSEOGuide, icon: 'FileText' };
      expect(isSEOGuide(guide)).toBe(true);
    });
  });

  describe('T-009: SEOGuide readTime type', () => {
    it('should accept readTime as number', () => {
      const guide = { ...validSEOGuide, readTime: 10 };
      expect(isSEOGuide(guide)).toBe(true);
    });
  });

  describe('T-010: SEOGuide readTime invalid', () => {
    it('should reject readTime as string', () => {
      const invalidGuide = { ...validSEOGuide, readTime: '5 min' as unknown as number };
      expect(isSEOGuide(invalidGuide)).toBe(false);
    });

    it('should reject negative readTime', () => {
      const invalidGuide = { ...validSEOGuide, readTime: -1 };
      expect(isSEOGuide(invalidGuide)).toBe(false);
    });
  });

  describe('T-021: SEOGuide empty sections', () => {
    it('should accept empty sections array', () => {
      const guide = { ...validSEOGuide, sections: [] };
      expect(isSEOGuide(guide)).toBe(true);
    });
  });

  describe('T-022: SEOGuide slug format', () => {
    it('should accept kebab-case slug', () => {
      const guide = { ...validSEOGuide, slug: 'meta-title' };
      expect(isSEOGuide(guide)).toBe(true);
    });
  });

  describe('T-023: SEOGuide invalid slug format', () => {
    it('should accept but flag slug with spaces for review', () => {
      // Note: This passes type check but should be validated at runtime
      const guide = { ...validSEOGuide, slug: 'Meta Title' };
      expect(isSEOGuide(guide)).toBe(true);
    });
  });
});

// ============================================================================
// GuideSection Interface Tests
// ============================================================================

describe('GuideSection Interface', () => {
  const validSection: GuideSection = {
    title: 'What is a Meta Title?',
    content: 'The meta title is the title of your webpage...',
  };

  describe('T-011: GuideSection valid', () => {
    it('should accept complete GuideSection with title and content', () => {
      expect(isGuideSection(validSection)).toBe(true);
    });
  });

  describe('T-012: GuideSection with codeExamples', () => {
    it('should accept GuideSection with codeExamples array', () => {
      const sectionWithCode: GuideSection = {
        ...validSection,
        codeExamples: [
          { language: 'html', code: '<title>Example</title>' },
        ],
      };
      expect(isGuideSection(sectionWithCode)).toBe(true);
    });
  });

  it('should reject GuideSection without title', () => {
    const invalidSection = { ...validSection } as Partial<GuideSection>;
    delete invalidSection.title;
    expect(isGuideSection(invalidSection)).toBe(false);
  });

  it('should reject GuideSection without content', () => {
    const invalidSection = { ...validSection } as Partial<GuideSection>;
    delete invalidSection.content;
    expect(isGuideSection(invalidSection)).toBe(false);
  });
});

// ============================================================================
// CodeExample Interface Tests
// ============================================================================

describe('CodeExample Interface', () => {
  const validCodeExample: CodeExample = {
    language: 'html',
    code: '<title>Your Page Title</title>',
  };

  describe('T-013: CodeExample valid', () => {
    it('should accept complete CodeExample with language and code', () => {
      expect(isCodeExample(validCodeExample)).toBe(true);
    });
  });

  describe('T-014: CodeExample missing language', () => {
    it('should reject CodeExample without language', () => {
      const invalidExample = { ...validCodeExample } as Partial<CodeExample>;
      delete invalidExample.language;
      expect(isCodeExample(invalidExample)).toBe(false);
    });
  });

  it('should reject CodeExample without code', () => {
    const invalidExample = { ...validCodeExample } as Partial<CodeExample>;
    delete invalidExample.code;
    expect(isCodeExample(invalidExample)).toBe(false);
  });

  it('should accept CodeExample with optional description', () => {
    const exampleWithDescription: CodeExample = {
      ...validCodeExample,
      description: 'Basic title tag example',
    };
    expect(isCodeExample(exampleWithDescription)).toBe(true);
  });
});

// ============================================================================
// ChecklistItem Interface Tests
// ============================================================================

describe('ChecklistItem Interface', () => {
  const validChecklistItem: ChecklistItem = {
    text: 'Keep title under 60 characters',
    priority: 'critical',
  };

  describe('T-015: ChecklistItem valid', () => {
    it('should accept complete ChecklistItem with text and priority', () => {
      expect(isChecklistItem(validChecklistItem)).toBe(true);
    });
  });

  describe('T-016: ChecklistItem priority levels', () => {
    it.each(CHECKLIST_PRIORITIES)('should accept priority: %s', (priority) => {
      const item = { ...validChecklistItem, priority };
      expect(isChecklistItem(item)).toBe(true);
    });
  });

  describe('T-017: ChecklistItem invalid priority', () => {
    it('should reject invalid priority', () => {
      const invalidItem = { ...validChecklistItem, priority: 'low' };
      expect(isChecklistItem(invalidItem)).toBe(false);
    });
  });

  it('should reject ChecklistItem without text', () => {
    const invalidItem = { ...validChecklistItem } as Partial<ChecklistItem>;
    delete invalidItem.text;
    expect(isChecklistItem(invalidItem)).toBe(false);
  });

  it('should reject ChecklistItem without priority', () => {
    const invalidItem = { ...validChecklistItem } as Partial<ChecklistItem>;
    delete invalidItem.priority;
    expect(isChecklistItem(invalidItem)).toBe(false);
  });
});

// ============================================================================
// Helper Function Tests
// ============================================================================

describe('isValidCategory', () => {
  it.each(GUIDE_CATEGORIES)('should return true for valid category: %s', (category) => {
    expect(isValidCategory(category)).toBe(true);
  });

  it('should return false for invalid category', () => {
    expect(isValidCategory('invalid')).toBe(false);
  });

  it('should return false for empty string', () => {
    expect(isValidCategory('')).toBe(false);
  });
});

describe('isValidPriority', () => {
  it.each(CHECKLIST_PRIORITIES)('should return true for valid priority: %s', (priority) => {
    expect(isValidPriority(priority)).toBe(true);
  });

  it('should return false for invalid priority', () => {
    expect(isValidPriority('low')).toBe(false);
  });

  it('should return false for empty string', () => {
    expect(isValidPriority('')).toBe(false);
  });
});

// ============================================================================
// Type Guard Integration Tests
// ============================================================================

describe('Type Guards Integration', () => {
  describe('T-024: Type guard isSEOGuide() with valid object', () => {
    it('should return true for valid SEOGuide', () => {
      const guide: SEOGuide = {
        slug: 'test',
        title: 'Test',
        summary: 'Test summary',
        category: 'on-page',
        icon: '📄',
        readTime: 5,
        sections: [
          {
            title: 'Section',
            content: 'Content',
            codeExamples: [
              { language: 'html', code: '<code/>' },
            ],
          },
        ],
        checklist: [
          { text: 'Item', priority: 'critical' },
        ],
        relatedGuides: ['other-guide'],
      };
      expect(isSEOGuide(guide)).toBe(true);
    });
  });

  describe('T-025: Type guard isSEOGuide() with invalid object', () => {
    it('should return false for null', () => {
      expect(isSEOGuide(null)).toBe(false);
    });

    it('should return false for undefined', () => {
      expect(isSEOGuide(undefined)).toBe(false);
    });

    it('should return false for empty object', () => {
      expect(isSEOGuide({})).toBe(false);
    });

    it('should return false for string', () => {
      expect(isSEOGuide('not a guide')).toBe(false);
    });

    it('should return false for array', () => {
      expect(isSEOGuide([])).toBe(false);
    });
  });
});

// ============================================================================
// Edge Cases
// ============================================================================

describe('Edge Cases', () => {
  it('should handle very long strings', () => {
    const longString = 'a'.repeat(10000);
    const guide: SEOGuide = {
      slug: 'test',
      title: longString,
      summary: longString,
      category: 'on-page',
      icon: '📄',
      readTime: 5,
      sections: [],
      checklist: [],
      relatedGuides: [],
    };
    expect(isSEOGuide(guide)).toBe(true);
  });

  it('should handle unicode characters in text', () => {
    const guide: SEOGuide = {
      slug: 'test',
      title: '测试标题 🎉 Тест',
      summary: '日本語の要約',
      category: 'on-page',
      icon: '🌐',
      readTime: 5,
      sections: [],
      checklist: [],
      relatedGuides: [],
    };
    expect(isSEOGuide(guide)).toBe(true);
  });

  it('should handle zero readTime', () => {
    const guide: SEOGuide = {
      slug: 'test',
      title: 'Test',
      summary: 'Test',
      category: 'on-page',
      icon: '📄',
      readTime: 0,
      sections: [],
      checklist: [],
      relatedGuides: [],
    };
    // Zero readTime should be valid (quick reference guides)
    expect(isSEOGuide(guide)).toBe(true);
  });

  it('should handle very large readTime', () => {
    const guide: SEOGuide = {
      slug: 'test',
      title: 'Test',
      summary: 'Test',
      category: 'on-page',
      icon: '📄',
      readTime: 120,
      sections: [],
      checklist: [],
      relatedGuides: [],
    };
    expect(isSEOGuide(guide)).toBe(true);
  });

  it('should handle empty strings in required fields', () => {
    const guide = {
      slug: '',
      title: '',
      summary: '',
      category: 'on-page',
      icon: '📄',
      readTime: 5,
      sections: [],
      checklist: [],
      relatedGuides: [],
    };
    // Empty strings are valid types but should be validated at business logic level
    expect(isSEOGuide(guide)).toBe(true);
  });

  it('should handle sections with invalid codeExamples', () => {
    const section = {
      title: 'Test',
      content: 'Content',
      codeExamples: [
        { language: 'html', code: '<code/>' },
        { language: 'invalid', code: '' }, // Empty code
      ],
    };
    expect(isGuideSection(section)).toBe(true);
  });

  it('should handle checklist with many items', () => {
    const checklist: ChecklistItem[] = Array(100).fill(null).map((_, i) => ({
      text: `Item ${i}`,
      priority: 'recommended' as const,
    }));
    const guide: SEOGuide = {
      slug: 'test',
      title: 'Test',
      summary: 'Test',
      category: 'on-page',
      icon: '📄',
      readTime: 5,
      sections: [],
      checklist,
      relatedGuides: [],
    };
    expect(isSEOGuide(guide)).toBe(true);
  });
});
