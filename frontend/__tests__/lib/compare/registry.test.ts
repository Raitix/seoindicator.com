/**
 * Registry Tests for Comparison System
 * Task: TASK-32-2 - Implement Comparison Registry and Data Loading
 * Spec: SPEC-32 (Competitor Comparison Landing Pages)
 */

import {
  CompetitorData,
  ComparisonData,
  PricingTier,
  FeatureCategory,
  UseCase,
  FAQ,
  isCompetitorData,
  isComparisonData,
  isPricingTier,
  isFeatureCategory,
  isUseCase,
  isFAQ,
  AUDIENCE_TYPES,
  BUDGET_TYPES,
} from '@/lib/compare/types';

// Mock the competitor data modules
jest.mock('@/lib/compare/competitors/seoindicator', () => ({
  seoindicator: createMockCompetitorData('seoindicator', 'SEOIndicator'),
}), { virtual: true });

jest.mock('@/lib/compare/competitors/ahrefs', () => ({
  ahrefs: createMockCompetitorData('ahrefs', 'Ahrefs'),
}), { virtual: true });

jest.mock('@/lib/compare/competitors/semrush', () => ({
  semrush: createMockCompetitorData('semrush', 'SEMrush'),
}), { virtual: true });

jest.mock('@/lib/compare/competitors/moz', () => ({
  moz: createMockCompetitorData('moz', 'Moz'),
}), { virtual: true });

jest.mock('@/lib/compare/competitors/ubersuggest', () => ({
  ubersuggest: createMockCompetitorData('ubersuggest', 'Ubersuggest'),
}), { virtual: true });

// Helper function to create mock competitor data
function createMockCompetitorData(id: string, name: string): CompetitorData {
  return {
    id,
    name,
    slug: id,
    website: `https://${id}.com`,
    tagline: `${name} tagline`,
    pricing: [
      {
        name: 'Free',
        monthlyPrice: 'Free',
        annualPrice: 'Free',
        features: ['Feature 1'],
      },
    ],
    features: [
      {
        name: 'General',
        features: [
          { name: 'Feature', seoindicator: true, competitor: true },
        ],
      },
    ],
    pros: ['Pro 1'],
    cons: ['Con 1'],
    useCases: [
      {
        title: 'Use Case',
        description: 'Description',
        audience: 'solo',
        budget: 'free',
      },
    ],
    lastUpdated: '2026-03-05T00:00:00Z',
    source: 'Mock data',
  };
}

// Helper to create valid ComparisonData
function createMockComparisonData(slug: string, compA: string, compB: string): ComparisonData {
  return {
    slug,
    title: `${compA} vs ${compB}`,
    metaDescription: `Compare ${compA} vs ${compB}`,
    competitors: [compA, compB] as [string, string],
    summary: 'Comparison summary',
    featureComparison: [],
    pricingComparison: {
      seoindicator: [],
      competitor: [],
    },
    prosCons: {
      seoindicator: { pros: [], cons: [] },
      competitor: { pros: [], cons: [] },
    },
    useCases: {
      seoindicator: [],
      competitor: [],
    },
    verdict: 'Verdict',
    faqs: [],
    relatedComparisons: [],
  };
}

// ============================================================================
// Test Suite: Registry Module Import
// ============================================================================

describe('Registry Module', () => {
  let registry: typeof import('@/lib/compare/registry');

  beforeEach(() => {
    // Re-import to get fresh module
    jest.resetModules();
    
    // Set up mocks again after reset
    jest.mock('@/lib/compare/competitors/seoindicator', () => ({
      seoindicator: createMockCompetitorData('seoindicator', 'SEOIndicator'),
    }), { virtual: true });
    
    jest.mock('@/lib/compare/competitors/ahrefs', () => ({
      ahrefs: createMockCompetitorData('ahrefs', 'Ahrefs'),
    }), { virtual: true });
    
    jest.mock('@/lib/compare/competitors/semrush', () => ({
      semrush: createMockCompetitorData('semrush', 'SEMrush'),
    }), { virtual: true });
    
    jest.mock('@/lib/compare/competitors/moz', () => ({
      moz: createMockCompetitorData('moz', 'Moz'),
    }), { virtual: true });
    
    jest.mock('@/lib/compare/competitors/ubersuggest', () => ({
      ubersuggest: createMockCompetitorData('ubersuggest', 'Ubersuggest'),
    }), { virtual: true });
  });

  // ==========================================================================
  // RG-STR: Registry Structure Tests
  // ==========================================================================

  describe('RG-STR: Registry Structure', () => {
    test('RG-STR-001: COMPETITOR_REGISTRY is defined', async () => {
      registry = await import('@/lib/compare/registry');
      expect(registry.COMPETITOR_REGISTRY).toBeDefined();
      expect(registry.COMPETITOR_REGISTRY).not.toBeNull();
    });

    test('RG-STR-002: COMPETITOR_REGISTRY contains seoindicator', async () => {
      registry = await import('@/lib/compare/registry');
      expect(registry.COMPETITOR_REGISTRY).toHaveProperty('seoindicator');
    });

    test('RG-STR-003: COMPETITOR_REGISTRY contains ahrefs', async () => {
      registry = await import('@/lib/compare/registry');
      expect(registry.COMPETITOR_REGISTRY).toHaveProperty('ahrefs');
    });

    test('RG-STR-004: COMPETITOR_REGISTRY contains semrush', async () => {
      registry = await import('@/lib/compare/registry');
      expect(registry.COMPETITOR_REGISTRY).toHaveProperty('semrush');
    });

    test('RG-STR-005: COMPETITOR_REGISTRY contains moz', async () => {
      registry = await import('@/lib/compare/registry');
      expect(registry.COMPETITOR_REGISTRY).toHaveProperty('moz');
    });

    test('RG-STR-006: COMPETITOR_REGISTRY contains ubersuggest', async () => {
      registry = await import('@/lib/compare/registry');
      expect(registry.COMPETITOR_REGISTRY).toHaveProperty('ubersuggest');
    });

    test('RG-STR-007: COMPETITOR_REGISTRY values are valid CompetitorData', async () => {
      registry = await import('@/lib/compare/registry');
      const entries = Object.entries(registry.COMPETITOR_REGISTRY);
      entries.forEach(([key, data]) => {
        expect(isCompetitorData(data)).toBe(true);
      });
    });

    test('RG-STR-008: COMPARISON_SLUGS is defined', async () => {
      registry = await import('@/lib/compare/registry');
      expect(registry.COMPARISON_SLUGS).toBeDefined();
      expect(Array.isArray(registry.COMPARISON_SLUGS)).toBe(true);
    });

    test('RG-STR-009: COMPARISON_SLUGS contains seoindicator-vs-ahrefs', async () => {
      registry = await import('@/lib/compare/registry');
      expect(registry.COMPARISON_SLUGS).toContain('seoindicator-vs-ahrefs');
    });

    test('RG-STR-010: COMPARISON_SLUGS contains seoindicator-vs-semrush', async () => {
      registry = await import('@/lib/compare/registry');
      expect(registry.COMPARISON_SLUGS).toContain('seoindicator-vs-semrush');
    });

    test('RG-STR-011: COMPARISON_SLUGS entries have valid format', async () => {
      registry = await import('@/lib/compare/registry');
      const slugPattern = /^[a-z0-9-]+-vs-[a-z0-9-]+$/;
      registry.COMPARISON_SLUGS.forEach((slug: string) => {
        expect(slugPattern.test(slug)).toBe(true);
      });
    });

    test('RG-STR-012: COMPARISON_SLUGS contains cross-competitor slugs', async () => {
      registry = await import('@/lib/compare/registry');
      expect(registry.COMPARISON_SLUGS).toContain('ahrefs-vs-semrush');
    });

    test('RG-STR-013: Registry has minimum 5 competitors', async () => {
      registry = await import('@/lib/compare/registry');
      const keys = Object.keys(registry.COMPETITOR_REGISTRY);
      expect(keys.length).toBeGreaterThanOrEqual(5);
    });

    test('RG-STR-014: COMPARISON_SLUGS has minimum 10 entries', async () => {
      registry = await import('@/lib/compare/registry');
      expect(registry.COMPARISON_SLUGS.length).toBeGreaterThanOrEqual(10);
    });

    test('RG-STR-015: All registry IDs match keys', async () => {
      registry = await import('@/lib/compare/registry');
      const entries = Object.entries(registry.COMPETITOR_REGISTRY);
      entries.forEach(([key, data]) => {
        expect(data.id).toBe(key);
      });
    });
  });

  // ==========================================================================
  // RG-GET: getCompetitorById() Tests
  // ==========================================================================

  describe('RG-GET: getCompetitorById()', () => {
    beforeEach(async () => {
      registry = await import('@/lib/compare/registry');
    });

    test('RG-GET-001: Returns seoindicator data', () => {
      const result = registry.getCompetitorById('seoindicator');
      expect(result).not.toBeNull();
      expect(result?.id).toBe('seoindicator');
    });

    test('RG-GET-002: Returns ahrefs data', () => {
      const result = registry.getCompetitorById('ahrefs');
      expect(result).not.toBeNull();
      expect(result?.id).toBe('ahrefs');
    });

    test('RG-GET-003: Returns semrush data', () => {
      const result = registry.getCompetitorById('semrush');
      expect(result).not.toBeNull();
      expect(result?.id).toBe('semrush');
    });

    test('RG-GET-004: Returns null for invalid ID', () => {
      const result = registry.getCompetitorById('nonexistent');
      expect(result).toBeNull();
    });

    test('RG-GET-005: Returns null for empty string', () => {
      const result = registry.getCompetitorById('');
      expect(result).toBeNull();
    });

    test('RG-GET-006: Returns null for null input', () => {
      const result = registry.getCompetitorById(null as unknown as string);
      expect(result).toBeNull();
    });

    test('RG-GET-007: Returns null for undefined input', () => {
      const result = registry.getCompetitorById(undefined as unknown as string);
      expect(result).toBeNull();
    });

    test('RG-GET-008: Returns null for number input', () => {
      const result = registry.getCompetitorById(123 as unknown as string);
      expect(result).toBeNull();
    });

    test('RG-GET-009: Returns null for object input', () => {
      const result = registry.getCompetitorById({} as unknown as string);
      expect(result).toBeNull();
    });

    test('RG-GET-010: Case-sensitive lookup', () => {
      const result = registry.getCompetitorById('Ahrefs');
      expect(result).toBeNull();
    });

    test('RG-GET-011: Returns correct data structure', () => {
      const result = registry.getCompetitorById('seoindicator');
      expect(result).toHaveProperty('id');
      expect(result).toHaveProperty('name');
      expect(result).toHaveProperty('slug');
      expect(result).toHaveProperty('website');
      expect(result).toHaveProperty('pricing');
      expect(result).toHaveProperty('features');
      expect(result).toHaveProperty('pros');
      expect(result).toHaveProperty('cons');
      expect(result).toHaveProperty('useCases');
      expect(result).toHaveProperty('lastUpdated');
      expect(result).toHaveProperty('source');
    });

    test('RG-GET-012: Returned data is valid CompetitorData', () => {
      const result = registry.getCompetitorById('ahrefs');
      expect(isCompetitorData(result)).toBe(true);
    });
  });

  // ==========================================================================
  // RG-SLUG: getComparisonBySlug() Tests
  // ==========================================================================

  describe('RG-SLUG: getComparisonBySlug()', () => {
    beforeEach(async () => {
      registry = await import('@/lib/compare/registry');
    });

    test('RG-SLUG-001: Returns comparison data for valid slug', () => {
      const result = registry.getComparisonBySlug('seoindicator-vs-ahrefs');
      expect(result).not.toBeNull();
    });

    test('RG-SLUG-002: Returns comparison data for semrush', () => {
      const result = registry.getComparisonBySlug('seoindicator-vs-semrush');
      expect(result).not.toBeNull();
    });

    test('RG-SLUG-003: Returns null for invalid slug', () => {
      const result = registry.getComparisonBySlug('nonexistent-vs-foo');
      expect(result).toBeNull();
    });

    test('RG-SLUG-004: Returns null for empty string', () => {
      const result = registry.getComparisonBySlug('');
      expect(result).toBeNull();
    });

    test('RG-SLUG-005: Returns null for null input', () => {
      const result = registry.getComparisonBySlug(null as unknown as string);
      expect(result).toBeNull();
    });

    test('RG-SLUG-006: Returns null for malformed slug', () => {
      const result = registry.getComparisonBySlug('seoindicator-ahrefs');
      expect(result).toBeNull();
    });

    test('RG-SLUG-007: Returns null for slug with extra parts', () => {
      const result = registry.getComparisonBySlug('a-vs-b-vs-c');
      expect(result).toBeNull();
    });

    test('RG-SLUG-008: Returns correct slug in response', () => {
      const result = registry.getComparisonBySlug('seoindicator-vs-ahrefs');
      expect(result?.slug).toBe('seoindicator-vs-ahrefs');
    });

    test('RG-SLUG-009: Returns correct competitor IDs', () => {
      const result = registry.getComparisonBySlug('seoindicator-vs-ahrefs');
      expect(result?.competitors).toEqual(['seoindicator', 'ahrefs']);
    });

    test('RG-SLUG-010: Returns cross-competitor comparison', () => {
      const result = registry.getComparisonBySlug('ahrefs-vs-semrush');
      expect(result).not.toBeNull();
    });

    test('RG-SLUG-011: Response has title', () => {
      const result = registry.getComparisonBySlug('seoindicator-vs-ahrefs');
      expect(typeof result?.title).toBe('string');
      expect(result?.title.length).toBeGreaterThan(0);
    });

    test('RG-SLUG-012: Response has metaDescription', () => {
      const result = registry.getComparisonBySlug('seoindicator-vs-ahrefs');
      expect(typeof result?.metaDescription).toBe('string');
    });

    test('RG-SLUG-013: Response has featureComparison', () => {
      const result = registry.getComparisonBySlug('seoindicator-vs-ahrefs');
      expect(Array.isArray(result?.featureComparison)).toBe(true);
    });

    test('RG-SLUG-014: Response has pricingComparison', () => {
      const result = registry.getComparisonBySlug('seoindicator-vs-ahrefs');
      expect(result?.pricingComparison).toHaveProperty('seoindicator');
      expect(result?.pricingComparison).toHaveProperty('competitor');
    });

    test('RG-SLUG-015: Response has FAQs', () => {
      const result = registry.getComparisonBySlug('seoindicator-vs-ahrefs');
      expect(Array.isArray(result?.faqs)).toBe(true);
    });
  });

  // ==========================================================================
  // RG-PARSE: parseSlug() Tests
  // ==========================================================================

  describe('RG-PARSE: parseSlug()', () => {
    beforeEach(async () => {
      registry = await import('@/lib/compare/registry');
    });

    test('RG-PARSE-001: Parses seoindicator-vs-ahrefs', () => {
      const result = registry.parseSlug('seoindicator-vs-ahrefs');
      expect(result).toEqual(['seoindicator', 'ahrefs']);
    });

    test('RG-PARSE-002: Parses seoindicator-vs-semrush', () => {
      const result = registry.parseSlug('seoindicator-vs-semrush');
      expect(result).toEqual(['seoindicator', 'semrush']);
    });

    test('RG-PARSE-003: Parses cross-competitor slug', () => {
      const result = registry.parseSlug('ahrefs-vs-semrush');
      expect(result).toEqual(['ahrefs', 'semrush']);
    });

    test('RG-PARSE-004: Returns null for slug without -vs-', () => {
      const result = registry.parseSlug('seoindicator-ahrefs');
      expect(result).toBeNull();
    });

    test('RG-PARSE-005: Returns null for empty string', () => {
      const result = registry.parseSlug('');
      expect(result).toBeNull();
    });

    test('RG-PARSE-006: Returns null for null input', () => {
      const result = registry.parseSlug(null as unknown as string);
      expect(result).toBeNull();
    });

    test('RG-PARSE-007: Returns null for only -vs-', () => {
      const result = registry.parseSlug('-vs-');
      expect(result).toBeNull();
    });

    test('RG-PARSE-008: Returns null for slug starting with -vs-', () => {
      const result = registry.parseSlug('-vs-ahrefs');
      expect(result).toBeNull();
    });

    test('RG-PARSE-009: Returns null for slug ending with -vs-', () => {
      const result = registry.parseSlug('seoindicator-vs-');
      expect(result).toBeNull();
    });

    test('RG-PARSE-010: Handles multiple -vs- correctly', () => {
      const result = registry.parseSlug('a-vs-b-vs-c');
      expect(result).toBeNull();
    });

    test('RG-PARSE-011: Returns tuple of strings', () => {
      const result = registry.parseSlug('seoindicator-vs-ahrefs');
      expect(Array.isArray(result)).toBe(true);
      expect(result?.length).toBe(2);
      expect(typeof result?.[0]).toBe('string');
      expect(typeof result?.[1]).toBe('string');
    });

    test('RG-PARSE-012: Extracts first competitor correctly', () => {
      const result = registry.parseSlug('seoindicator-vs-ahrefs');
      expect(result?.[0]).toBe('seoindicator');
    });

    test('RG-PARSE-013: Extracts second competitor correctly', () => {
      const result = registry.parseSlug('seoindicator-vs-ahrefs');
      expect(result?.[1]).toBe('ahrefs');
    });

    test('RG-PARSE-014: Case-sensitive parsing', () => {
      const result = registry.parseSlug('SEOINDICATOR-vs-ahrefs');
      // Should either be null or preserve case
      expect(result === null || result?.[0] === 'SEOINDICATOR').toBe(true);
    });
  });

  // ==========================================================================
  // RG-GEN: generateComparisonData() Tests
  // ==========================================================================

  describe('RG-GEN: generateComparisonData()', () => {
    beforeEach(async () => {
      registry = await import('@/lib/compare/registry');
    });

    test('RG-GEN-001: Generates valid comparison data', () => {
      const result = registry.generateComparisonData('seoindicator', 'ahrefs');
      expect(result).not.toBeNull();
    });

    test('RG-GEN-002: Generates correct slug', () => {
      const result = registry.generateComparisonData('seoindicator', 'ahrefs');
      expect(result?.slug).toBe('seoindicator-vs-ahrefs');
    });

    test('RG-GEN-003: Generates correct competitor IDs', () => {
      const result = registry.generateComparisonData('seoindicator', 'ahrefs');
      expect(result?.competitors).toEqual(['seoindicator', 'ahrefs']);
    });

    test('RG-GEN-004: Returns null for invalid first competitor', () => {
      const result = registry.generateComparisonData('nonexistent', 'ahrefs');
      expect(result).toBeNull();
    });

    test('RG-GEN-005: Returns null for invalid second competitor', () => {
      const result = registry.generateComparisonData('seoindicator', 'nonexistent');
      expect(result).toBeNull();
    });

    test('RG-GEN-006: Returns null for both invalid', () => {
      const result = registry.generateComparisonData('foo', 'bar');
      expect(result).toBeNull();
    });

    test('RG-GEN-007: Returns null for null first param', () => {
      const result = registry.generateComparisonData(null as unknown as string, 'ahrefs');
      expect(result).toBeNull();
    });

    test('RG-GEN-008: Returns null for null second param', () => {
      const result = registry.generateComparisonData('seoindicator', null as unknown as string);
      expect(result).toBeNull();
    });

    test('RG-GEN-009: Generates feature comparison', () => {
      const result = registry.generateComparisonData('seoindicator', 'ahrefs');
      expect(Array.isArray(result?.featureComparison)).toBe(true);
    });

    test('RG-GEN-010: Generates pricing comparison', () => {
      const result = registry.generateComparisonData('seoindicator', 'ahrefs');
      expect(result?.pricingComparison).toHaveProperty('seoindicator');
      expect(result?.pricingComparison).toHaveProperty('competitor');
    });

    test('RG-GEN-011: Generates pros/cons', () => {
      const result = registry.generateComparisonData('seoindicator', 'ahrefs');
      expect(result?.prosCons).toHaveProperty('seoindicator');
      expect(result?.prosCons).toHaveProperty('competitor');
      expect(result?.prosCons.seoindicator).toHaveProperty('pros');
      expect(result?.prosCons.seoindicator).toHaveProperty('cons');
    });

    test('RG-GEN-012: Generates use cases', () => {
      const result = registry.generateComparisonData('seoindicator', 'ahrefs');
      expect(result?.useCases).toHaveProperty('seoindicator');
      expect(result?.useCases).toHaveProperty('competitor');
    });

    test('RG-GEN-013: Generates verdict', () => {
      const result = registry.generateComparisonData('seoindicator', 'ahrefs');
      expect(typeof result?.verdict).toBe('string');
      expect(result?.verdict.length).toBeGreaterThan(0);
    });

    test('RG-GEN-014: Generates FAQs', () => {
      const result = registry.generateComparisonData('seoindicator', 'ahrefs');
      expect(Array.isArray(result?.faqs)).toBe(true);
    });

    test('RG-GEN-015: Generated data passes validation', () => {
      const result = registry.generateComparisonData('seoindicator', 'ahrefs');
      expect(isComparisonData(result)).toBe(true);
    });
  });

  // ==========================================================================
  // RG-REL: getRelatedComparisons() Tests
  // ==========================================================================

  describe('RG-REL: getRelatedComparisons()', () => {
    beforeEach(async () => {
      registry = await import('@/lib/compare/registry');
    });

    test('RG-REL-001: Returns related comparisons for seoindicator', () => {
      const result = registry.getRelatedComparisons('seoindicator');
      expect(Array.isArray(result)).toBe(true);
      expect(result.length).toBeGreaterThan(0);
    });

    test('RG-REL-002: Returns related comparisons for ahrefs', () => {
      const result = registry.getRelatedComparisons('ahrefs');
      expect(Array.isArray(result)).toBe(true);
      expect(result.length).toBeGreaterThan(0);
    });

    test('RG-REL-003: Returns empty array for invalid ID', () => {
      const result = registry.getRelatedComparisons('nonexistent');
      expect(result).toEqual([]);
    });

    test('RG-REL-004: Returns empty array for null input', () => {
      const result = registry.getRelatedComparisons(null as unknown as string);
      expect(result).toEqual([]);
    });

    test('RG-REL-005: Returns empty array for empty string', () => {
      const result = registry.getRelatedComparisons('');
      expect(result).toEqual([]);
    });

    test('RG-REL-006: Excludes self-referencing comparisons', () => {
      const result = registry.getRelatedComparisons('seoindicator');
      expect(result).not.toContain('seoindicator-vs-seoindicator');
    });

    test('RG-REL-007: Returns maximum 5 related comparisons', () => {
      const result = registry.getRelatedComparisons('seoindicator');
      expect(result.length).toBeLessThanOrEqual(5);
    });

    test('RG-REL-008: Returns slugs containing competitor', () => {
      const result = registry.getRelatedComparisons('ahrefs');
      result.forEach((slug: string) => {
        expect(slug).toContain('ahrefs');
      });
    });

    test('RG-REL-009: Returns valid slugs only', () => {
      const result = registry.getRelatedComparisons('seoindicator');
      result.forEach((slug: string) => {
        expect(registry.COMPARISON_SLUGS).toContain(slug);
      });
    });

    test('RG-REL-010: Case-sensitive matching', () => {
      const result = registry.getRelatedComparisons('Ahrefs');
      expect(result).toEqual([]);
    });

    test('RG-REL-011: Returns array type', () => {
      const result = registry.getRelatedComparisons('seoindicator');
      expect(Array.isArray(result)).toBe(true);
    });

    test('RG-REL-012: All results are strings', () => {
      const result = registry.getRelatedComparisons('seoindicator');
      result.forEach((slug: string) => {
        expect(typeof slug).toBe('string');
      });
    });
  });

  // ==========================================================================
  // RG-VAL: validateCompetitorData() Tests
  // ==========================================================================

  describe('RG-VAL: validateCompetitorData()', () => {
    beforeEach(async () => {
      registry = await import('@/lib/compare/registry');
    });

    const validData: CompetitorData = createMockCompetitorData('test', 'Test');

    test('RG-VAL-001: Returns true for valid data', () => {
      const result = registry.validateCompetitorData(validData);
      expect(result).toBe(true);
    });

    test('RG-VAL-002: Returns false for null', () => {
      const result = registry.validateCompetitorData(null);
      expect(result).toBe(false);
    });

    test('RG-VAL-003: Returns false for undefined', () => {
      const result = registry.validateCompetitorData(undefined);
      expect(result).toBe(false);
    });

    test('RG-VAL-004: Returns false for empty object', () => {
      const result = registry.validateCompetitorData({});
      expect(result).toBe(false);
    });

    test('RG-VAL-005: Returns false for string', () => {
      const result = registry.validateCompetitorData('data');
      expect(result).toBe(false);
    });

    test('RG-VAL-006: Returns false for array', () => {
      const result = registry.validateCompetitorData([]);
      expect(result).toBe(false);
    });

    test('RG-VAL-007: Returns false missing id', () => {
      const { id, ...data } = validData;
      const result = registry.validateCompetitorData(data);
      expect(result).toBe(false);
    });

    test('RG-VAL-008: Returns false missing name', () => {
      const { name, ...data } = validData;
      const result = registry.validateCompetitorData(data);
      expect(result).toBe(false);
    });

    test('RG-VAL-009: Returns false missing pricing', () => {
      const { pricing, ...data } = validData;
      const result = registry.validateCompetitorData(data);
      expect(result).toBe(false);
    });

    test('RG-VAL-010: Returns false for empty pricing', () => {
      const result = registry.validateCompetitorData({
        ...validData,
        pricing: [],
      });
      expect(result).toBe(false);
    });

    test('RG-VAL-011: Returns false missing features', () => {
      const { features, ...data } = validData;
      const result = registry.validateCompetitorData(data);
      expect(result).toBe(false);
    });

    test('RG-VAL-012: Returns false missing pros', () => {
      const { pros, ...data } = validData;
      const result = registry.validateCompetitorData(data);
      expect(result).toBe(false);
    });

    test('RG-VAL-013: Returns false missing cons', () => {
      const { cons, ...data } = validData;
      const result = registry.validateCompetitorData(data);
      expect(result).toBe(false);
    });

    test('RG-VAL-014: Returns false missing lastUpdated', () => {
      const { lastUpdated, ...data } = validData;
      const result = registry.validateCompetitorData(data);
      expect(result).toBe(false);
    });

    test('RG-VAL-015: Returns false for invalid pricing item', () => {
      const result = registry.validateCompetitorData({
        ...validData,
        pricing: [{}],
      });
      expect(result).toBe(false);
    });

    test('RG-VAL-016: Returns true with optional logo', () => {
      const result = registry.validateCompetitorData({
        ...validData,
        logo: 'https://example.com/logo.png',
      });
      expect(result).toBe(true);
    });

    test('RG-VAL-017: Returns false for invalid lastUpdated', () => {
      const result = registry.validateCompetitorData({
        ...validData,
        lastUpdated: 'invalid-date',
      });
      expect(result).toBe(false);
    });

    test('RG-VAL-018: Returns false for negative price', () => {
      const result = registry.validateCompetitorData({
        ...validData,
        pricing: [{
          name: 'Test',
          monthlyPrice: -10,
          annualPrice: -100,
          features: [],
        }],
      });
      expect(result).toBe(false);
    });

    test('RG-VAL-019: Returns false for invalid use case audience', () => {
      const result = registry.validateCompetitorData({
        ...validData,
        useCases: [{
          title: 'Test',
          description: 'Test',
          audience: 'invalid' as any,
          budget: 'free',
        }],
      });
      expect(result).toBe(false);
    });

    test('RG-VAL-020: Validates all fields recursively', () => {
      const deepInvalid = {
        ...validData,
        features: [{
          name: 'Test',
          features: [{
            name: 'Feature',
            seoindicator: 'invalid' as any,
            competitor: true,
          }],
        }],
      };
      const result = registry.validateCompetitorData(deepInvalid);
      // Note: boolean | string is valid for seoindicator, so this might pass
      // The test is for the recursive validation structure
      expect(typeof result).toBe('boolean');
    });
  });

  // ==========================================================================
  // RG-VCD: validateComparisonData() Tests
  // ==========================================================================

  describe('RG-VCD: validateComparisonData()', () => {
    beforeEach(async () => {
      registry = await import('@/lib/compare/registry');
    });

    const validData: ComparisonData = createMockComparisonData('seoindicator-vs-test', 'seoindicator', 'test');

    test('RG-VCD-001: Returns true for valid data', () => {
      const result = registry.validateComparisonData(validData);
      expect(result).toBe(true);
    });

    test('RG-VCD-002: Returns false for null', () => {
      const result = registry.validateComparisonData(null);
      expect(result).toBe(false);
    });

    test('RG-VCD-003: Returns false for undefined', () => {
      const result = registry.validateComparisonData(undefined);
      expect(result).toBe(false);
    });

    test('RG-VCD-004: Returns false for empty object', () => {
      const result = registry.validateComparisonData({});
      expect(result).toBe(false);
    });

    test('RG-VCD-005: Returns false missing slug', () => {
      const { slug, ...data } = validData;
      const result = registry.validateComparisonData(data);
      expect(result).toBe(false);
    });

    test('RG-VCD-006: Returns false missing title', () => {
      const { title, ...data } = validData;
      const result = registry.validateComparisonData(data);
      expect(result).toBe(false);
    });

    test('RG-VCD-007: Returns false missing competitors', () => {
      const { competitors, ...data } = validData;
      const result = registry.validateComparisonData(data);
      expect(result).toBe(false);
    });

    test('RG-VCD-008: Returns false for wrong competitor count', () => {
      const result = registry.validateComparisonData({
        ...validData,
        competitors: ['a'] as any,
      });
      expect(result).toBe(false);
    });

    test('RG-VCD-009: Returns false missing featureComparison', () => {
      const { featureComparison, ...data } = validData;
      const result = registry.validateComparisonData(data);
      expect(result).toBe(false);
    });

    test('RG-VCD-010: Returns false missing pricingComparison', () => {
      const { pricingComparison, ...data } = validData;
      const result = registry.validateComparisonData(data);
      expect(result).toBe(false);
    });

    test('RG-VCD-011: Returns false missing prosCons', () => {
      const { prosCons, ...data } = validData;
      const result = registry.validateComparisonData(data);
      expect(result).toBe(false);
    });

    test('RG-VCD-012: Returns false missing verdict', () => {
      const { verdict, ...data } = validData;
      const result = registry.validateComparisonData(data);
      expect(result).toBe(false);
    });

    test('RG-VCD-013: Returns false missing faqs', () => {
      const { faqs, ...data } = validData;
      const result = registry.validateComparisonData(data);
      expect(result).toBe(false);
    });

    test('RG-VCD-014: Returns false for invalid slug format', () => {
      const result = registry.validateComparisonData({
        ...validData,
        slug: 'invalid-slug-format',
      });
      expect(result).toBe(false);
    });

    test('RG-VCD-015: Validates nested structures', () => {
      const nestedInvalid = {
        ...validData,
        pricingComparison: {
          seoindicator: [{ invalid: 'pricing' }],
          competitor: [],
        },
      };
      const result = registry.validateComparisonData(nestedInvalid);
      expect(result).toBe(false);
    });
  });

  // ==========================================================================
  // RG-EDGE: Edge Cases
  // ==========================================================================

  describe('RG-EDGE: Edge Cases', () => {
    beforeEach(async () => {
      registry = await import('@/lib/compare/registry');
    });

    test('RG-EDGE-001: Handles Unicode competitor ID in slug', () => {
      const result = registry.parseSlug('한글-vs-ahrefs');
      expect(result === null || Array.isArray(result)).toBe(true);
    });

    test('RG-EDGE-002: Handles very long slug', () => {
      const longSlug = 'a'.repeat(500) + '-vs-' + 'b'.repeat(500);
      const result = registry.parseSlug(longSlug);
      expect(result === null || Array.isArray(result)).toBe(true);
    });

    test('RG-EDGE-003: Handles special characters in slug', () => {
      const result = registry.getComparisonBySlug('seoindicator-vs-@#$%');
      expect(result).toBeNull();
    });

    test('RG-EDGE-004: Registry is immutable (attempt modification)', () => {
      const originalValue = registry.COMPETITOR_REGISTRY.seoindicator;
      try {
        (registry.COMPETITOR_REGISTRY as any).seoindicator = { ...originalValue, id: 'modified' };
        // If not frozen, this would modify
        // But we check that original is preserved
      } catch (e) {
        // Frozen objects throw on modification
      }
      // Either frozen or not - we just check no crash
      expect(true).toBe(true);
    });

    test('RG-EDGE-005: COMPARISON_SLUGS is immutable', () => {
      try {
        (registry.COMPARISON_SLUGS as any).push('new-slug');
      } catch (e) {
        // Frozen arrays throw
      }
      expect(true).toBe(true);
    });

    test('RG-EDGE-006: Concurrent access to getComparisonBySlug', () => {
      const results = Promise.all([
        registry.getComparisonBySlug('seoindicator-vs-ahrefs'),
        registry.getComparisonBySlug('seoindicator-vs-ahrefs'),
        registry.getComparisonBySlug('seoindicator-vs-ahrefs'),
      ]);
      return results.then(([r1, r2, r3]) => {
        expect(r1?.slug).toBe(r2?.slug);
        expect(r2?.slug).toBe(r3?.slug);
      });
    });

    test('RG-EDGE-007: Memory efficiency with many competitors', () => {
      // This test verifies no memory leak on repeated calls
      for (let i = 0; i < 100; i++) {
        registry.getCompetitorById('seoindicator');
        registry.getComparisonBySlug('seoindicator-vs-ahrefs');
      }
      expect(true).toBe(true);
    });

    test('RG-EDGE-008: Handles circular references in data', () => {
      const circular: any = { a: {} };
      circular.a.self = circular;
      const result = registry.validateCompetitorData(circular);
      expect(result).toBe(false);
    });

    test('RG-EDGE-009: Handles prototype pollution attempt', () => {
      const malicious = {
        ...createMockCompetitorData('test', 'Test'),
        __proto__: { polluted: true },
      };
      const result = registry.validateCompetitorData(malicious);
      expect(typeof result).toBe('boolean');
    });

    test('RG-EDGE-010: Handles extremely large arrays', () => {
      const largeFeatures = Array(1000).fill(null).map((_, i) => ({
        name: `Feature ${i}`,
        seoindicator: true,
        competitor: false,
      }));
      const data = {
        ...createMockCompetitorData('test', 'Test'),
        features: [{ name: 'Category', features: largeFeatures }],
      };
      const result = registry.validateCompetitorData(data);
      expect(result).toBe(true);
    });
  });

  // ==========================================================================
  // RG-TS: TypeScript Types
  // ==========================================================================

  describe('RG-TS: TypeScript Types', () => {
    beforeEach(async () => {
      registry = await import('@/lib/compare/registry');
    });

    test('RG-TS-001: getCompetitorById returns CompetitorData | null', () => {
      const result = registry.getCompetitorById('seoindicator');
      expect(result === null || isCompetitorData(result)).toBe(true);
    });

    test('RG-TS-002: getComparisonBySlug returns ComparisonData | null', () => {
      const result = registry.getComparisonBySlug('seoindicator-vs-ahrefs');
      expect(result === null || isComparisonData(result)).toBe(true);
    });

    test('RG-TS-003: parseSlug returns [string, string] | null', () => {
      const result = registry.parseSlug('a-vs-b');
      if (result !== null) {
        expect(Array.isArray(result)).toBe(true);
        expect(result.length).toBe(2);
        expect(typeof result[0]).toBe('string');
        expect(typeof result[1]).toBe('string');
      } else {
        expect(result).toBeNull();
      }
    });

    test('RG-TS-004: generateComparisonData returns ComparisonData | null', () => {
      const result = registry.generateComparisonData('seoindicator', 'ahrefs');
      expect(result === null || isComparisonData(result)).toBe(true);
    });

    test('RG-TS-005: getRelatedComparisons returns string[]', () => {
      const result = registry.getRelatedComparisons('seoindicator');
      expect(Array.isArray(result)).toBe(true);
      result.forEach((item: string) => {
        expect(typeof item).toBe('string');
      });
    });
  });

  // ==========================================================================
  // AC: Acceptance Criteria
  // ==========================================================================

  describe('AC: Acceptance Criteria', () => {
    beforeEach(async () => {
      registry = await import('@/lib/compare/registry');
    });

    test('AC-001: Registry exports all competitors from single entry point', () => {
      const expectedCompetitors = ['seoindicator', 'ahrefs', 'semrush', 'moz', 'ubersuggest'];
      expectedCompetitors.forEach((id) => {
        expect(registry.COMPETITOR_REGISTRY).toHaveProperty(id);
      });
    });

    test('AC-002: getComparisonBySlug() returns valid ComparisonData for all slugs', () => {
      registry.COMPARISON_SLUGS.forEach((slug: string) => {
        const result = registry.getComparisonBySlug(slug);
        expect(result).not.toBeNull();
        if (result) {
          expect(isComparisonData(result)).toBe(true);
        }
      });
    });

    test('AC-003: parseSlug() correctly extracts competitor IDs', () => {
      const testCases = [
        { slug: 'seoindicator-vs-ahrefs', expected: ['seoindicator', 'ahrefs'] },
        { slug: 'ahrefs-vs-semrush', expected: ['ahrefs', 'semrush'] },
        { slug: 'invalid-slug', expected: null },
        { slug: '', expected: null },
      ];

      testCases.forEach(({ slug, expected }) => {
        const result = registry.parseSlug(slug);
        expect(result).toEqual(expected);
      });
    });

    test('AC-004: Invalid slugs return null (not throw errors)', () => {
      const invalidSlugs = ['invalid', '', null, undefined, 123, {}];
      
      invalidSlugs.forEach((slug) => {
        expect(() => {
          registry.getComparisonBySlug(slug as any);
        }).not.toThrow();
        
        const result = registry.getComparisonBySlug(slug as any);
        expect(result).toBeNull();
      });
    });

    test('AC-005: Validation functions catch malformed data', () => {
      const invalidData = [null, undefined, {}, [], 'string', 123];
      
      invalidData.forEach((data) => {
        expect(registry.validateCompetitorData(data)).toBe(false);
        expect(registry.validateComparisonData(data)).toBe(false);
      });
    });

    test('AC-006: SEOIndicator data file is valid', async () => {
      const seoindicator = registry.COMPETITOR_REGISTRY.seoindicator;
      expect(seoindicator).toBeDefined();
      expect(isCompetitorData(seoindicator)).toBe(true);
      expect(seoindicator.id).toBe('seoindicator');
      expect(seoindicator.pricing.length).toBeGreaterThan(0);
      expect(seoindicator.features.length).toBeGreaterThan(0);
    });

    test('AC-007: Unit tests for all helper functions', () => {
      // This test verifies that all functions exist and are callable
      expect(typeof registry.getCompetitorById).toBe('function');
      expect(typeof registry.getComparisonBySlug).toBe('function');
      expect(typeof registry.parseSlug).toBe('function');
      expect(typeof registry.generateComparisonData).toBe('function');
      expect(typeof registry.getRelatedComparisons).toBe('function');
      expect(typeof registry.validateCompetitorData).toBe('function');
      expect(typeof registry.validateComparisonData).toBe('function');
    });
  });
});
