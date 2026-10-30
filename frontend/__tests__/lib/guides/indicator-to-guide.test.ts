/**
 * Indicator-to-Guide Mapping Tests
 * Task: TASK-20-25 - Add Links from Results Table to Guides
 * Tests for getGuideSlugForIndicator() mapping function
 */

import { describe, it, expect } from 'vitest';

// Import the function to test
import {
  getGuideSlugForIndicator,
  hasGuideForIndicator,
  getIndicatorsWithGuides,
  INDICATOR_TO_GUIDE_MAP
} from '@/lib/guides/indicator-to-guide';

// Expected mapping for reference
const EXPECTED_MAPPING: Record<string, string> = {
  meta_tags: 'meta-title',
  headings: 'h1-headings',
  images: 'image-alt-text',
  links: 'internal-links',
  url_structure: 'canonical-urls',
  content_quality: 'h1-headings',
  mobile_friendly: 'mobile-friendly',
  page_speed: 'page-speed',
  structured_data: 'structured-data',
  security: 'https-security',
  accessibility: 'image-alt-text',
  social_tags: 'open-graph',
  canonical: 'canonical-urls',
  robots: 'robots-txt',
  sitemap: 'xml-sitemap',
  internal_links: 'internal-links',
  external_links: 'external-links',
};

// Valid guide slugs from registry
const VALID_GUIDE_SLUGS = [
  'meta-title',
  'meta-description',
  'h1-headings',
  'heading-hierarchy',
  'image-alt-text',
  'image-optimization',
  'internal-links',
  'external-links',
  'page-speed',
  'mobile-friendly',
  'https-security',
  'structured-data',
  'canonical-urls',
  'robots-txt',
  'xml-sitemap',
  'open-graph',
  'twitter-cards',
];

// ============================================
// M-VALID: Valid Indicator Mappings
// ============================================

describe('M-VALID: Valid Indicator Mappings', () => {
  it('M-VALID-001: maps meta_tags to meta-title', () => {
    const result = getGuideSlugForIndicator('meta_tags');
    expect(result).toBe('meta-title');
  });

  it('M-VALID-002: maps headings to h1-headings', () => {
    const result = getGuideSlugForIndicator('headings');
    expect(result).toBe('h1-headings');
  });

  it('M-VALID-003: maps images to image-alt-text', () => {
    const result = getGuideSlugForIndicator('images');
    expect(result).toBe('image-alt-text');
  });

  it('M-VALID-004: maps links to internal-links', () => {
    const result = getGuideSlugForIndicator('links');
    expect(result).toBe('internal-links');
  });

  it('M-VALID-005: maps url_structure to canonical-urls', () => {
    const result = getGuideSlugForIndicator('url_structure');
    expect(result).toBe('canonical-urls');
  });

  it('M-VALID-006: maps content_quality to h1-headings', () => {
    const result = getGuideSlugForIndicator('content_quality');
    expect(result).toBe('h1-headings');
  });

  it('M-VALID-007: maps mobile_friendly to mobile-friendly', () => {
    const result = getGuideSlugForIndicator('mobile_friendly');
    expect(result).toBe('mobile-friendly');
  });

  it('M-VALID-008: maps page_speed to page-speed', () => {
    const result = getGuideSlugForIndicator('page_speed');
    expect(result).toBe('page-speed');
  });

  it('M-VALID-009: maps structured_data to structured-data', () => {
    const result = getGuideSlugForIndicator('structured_data');
    expect(result).toBe('structured-data');
  });

  it('M-VALID-010: maps security to https-security', () => {
    const result = getGuideSlugForIndicator('security');
    expect(result).toBe('https-security');
  });

  it('M-VALID-011: maps accessibility to image-alt-text', () => {
    const result = getGuideSlugForIndicator('accessibility');
    expect(result).toBe('image-alt-text');
  });

  it('M-VALID-012: maps social_tags to open-graph', () => {
    const result = getGuideSlugForIndicator('social_tags');
    expect(result).toBe('open-graph');
  });

  it('M-VALID-013: maps canonical to canonical-urls', () => {
    const result = getGuideSlugForIndicator('canonical');
    expect(result).toBe('canonical-urls');
  });

  it('M-VALID-014: maps robots to robots-txt', () => {
    const result = getGuideSlugForIndicator('robots');
    expect(result).toBe('robots-txt');
  });

  it('M-VALID-015: maps sitemap to xml-sitemap', () => {
    const result = getGuideSlugForIndicator('sitemap');
    expect(result).toBe('xml-sitemap');
  });

  it('M-VALID-016: maps internal_links to internal-links', () => {
    const result = getGuideSlugForIndicator('internal_links');
    expect(result).toBe('internal-links');
  });

  it('M-VALID-017: maps external_links to external-links', () => {
    const result = getGuideSlugForIndicator('external_links');
    expect(result).toBe('external-links');
  });
});

// ============================================
// M-INVALID: Invalid Input Handling
// ============================================

describe('M-INVALID: Invalid Input Handling', () => {
  it('M-INVALID-001: returns null for unknown indicator', () => {
    const result = getGuideSlugForIndicator('unknown_indicator');
    expect(result).toBeNull();
  });

  it('M-INVALID-002: returns null for empty string', () => {
    const result = getGuideSlugForIndicator('');
    expect(result).toBeNull();
  });

  it('M-INVALID-003: returns null for null input', () => {
    const result = getGuideSlugForIndicator(null);
    expect(result).toBeNull();
  });

  it('M-INVALID-004: returns null for undefined input', () => {
    const result = getGuideSlugForIndicator(undefined);
    expect(result).toBeNull();
  });
});

// ============================================
// M-CASE: Case Sensitivity
// ============================================

describe('M-CASE: Case Sensitivity', () => {
  it('M-CASE-001: uppercase input returns null', () => {
    const result = getGuideSlugForIndicator('META_TAGS');
    expect(result).toBeNull();
  });

  it('M-CASE-002: mixed case input returns null', () => {
    const result = getGuideSlugForIndicator('Meta_Tags');
    expect(result).toBeNull();
  });

  it('M-CASE-003: exact match works', () => {
    const result = getGuideSlugForIndicator('meta_tags');
    expect(result).toBe('meta-title');
  });
});

// ============================================
// M-TRIM: Whitespace Handling
// ============================================

describe('M-TRIM: Whitespace Handling', () => {
  it('M-TRIM-001: leading whitespace returns null', () => {
    const result = getGuideSlugForIndicator(' meta_tags');
    expect(result).toBeNull();
  });

  it('M-TRIM-002: trailing whitespace returns null', () => {
    const result = getGuideSlugForIndicator('meta_tags ');
    expect(result).toBeNull();
  });

  it('M-TRIM-003: both whitespace returns null', () => {
    const result = getGuideSlugForIndicator(' meta_tags ');
    expect(result).toBeNull();
  });
});

// ============================================
// M-SLUG: Guide Slug Validation
// ============================================

describe('M-SLUG: Guide Slug Validation', () => {
  it('M-SLUG-001: all mapped slugs are valid', () => {
    Object.values(INDICATOR_TO_GUIDE_MAP).forEach((slug) => {
      expect(VALID_GUIDE_SLUGS).toContain(slug);
    });
  });

  it('M-SLUG-002: all slugs are kebab-case', () => {
    Object.values(INDICATOR_TO_GUIDE_MAP).forEach((slug) => {
      expect(slug).toMatch(/^[a-z0-9-]+$/);
    });
  });
});

// ============================================
// M-RETURN: Return Type Validation
// ============================================

describe('M-RETURN: Return Type Validation', () => {
  it('M-RETURN-001: returns string for valid input', () => {
    const result = getGuideSlugForIndicator('meta_tags');
    expect(typeof result).toBe('string');
  });

  it('M-RETURN-002: returns null for invalid input', () => {
    const result = getGuideSlugForIndicator('invalid');
    expect(result).toBeNull();
  });
});

// ============================================
// M-EDGE: Edge Cases
// ============================================

describe('M-EDGE: Edge Cases', () => {
  it('M-EDGE-001: very long indicator name', () => {
    const result = getGuideSlugForIndicator('a'.repeat(1000));
    expect(result).toBeNull();
  });

  it('M-EDGE-002: special characters in indicator', () => {
    const result = getGuideSlugForIndicator('meta_tags!@#$%');
    expect(result).toBeNull();
  });

  it('M-EDGE-003: spaces instead of underscores', () => {
    const result = getGuideSlugForIndicator('meta tags');
    expect(result).toBeNull();
  });

  it('M-EDGE-004: hyphens instead of underscores', () => {
    const result = getGuideSlugForIndicator('meta-tags');
    expect(result).toBeNull();
  });
});

// ============================================
// M-COMPLETE: Complete Mapping Coverage
// ============================================

describe('M-COMPLETE: Complete Mapping Coverage', () => {
  it('M-COMPLETE-001: all known indicators have mappings', () => {
    const knownIndicators = [
      'meta_tags',
      'headings',
      'images',
      'links',
      'url_structure',
      'content_quality',
      'mobile_friendly',
      'page_speed',
      'structured_data',
      'security',
      'accessibility',
      'social_tags',
      'canonical',
      'robots',
      'sitemap',
      'internal_links',
      'external_links',
    ];

    knownIndicators.forEach((indicator) => {
      expect(getGuideSlugForIndicator(indicator)).not.toBeNull();
    });
  });

  it('M-COMPLETE-002: mapping count matches indicator count', () => {
    const expectedIndicatorCount = 17;
    expect(Object.keys(INDICATOR_TO_GUIDE_MAP).length).toBe(expectedIndicatorCount);
  });
});

// ============================================
// M-HELPERS: Helper Functions
// ============================================

describe('M-HELPERS: Helper Functions', () => {
  it('hasGuideForIndicator returns true for valid indicator', () => {
    expect(hasGuideForIndicator('meta_tags')).toBe(true);
  });

  it('hasGuideForIndicator returns false for invalid indicator', () => {
    expect(hasGuideForIndicator('unknown')).toBe(false);
  });

  it('getIndicatorsWithGuides returns all indicator keys', () => {
    const indicators = getIndicatorsWithGuides();
    expect(indicators.length).toBe(17);
    expect(indicators).toContain('meta_tags');
    expect(indicators).toContain('page_speed');
  });
});
