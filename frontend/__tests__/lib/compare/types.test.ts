/**
 * Tests for Comparison Types and Interfaces
 * Task: TASK-32-1 - Create Comparison Types and Interfaces
 * Spec: SPEC-32 (Competitor Comparison Landing Pages)
 *
 * These tests verify type safety and runtime validation for:
 * - CompetitorData interface
 * - PricingTier interface
 * - FeatureCategory interface
 * - Feature interface
 * - UseCase interface
 * - ComparisonData interface
 * - FAQ interface
 * - Component Props interfaces
 */

import {
  CompetitorData,
  PricingTier,
  FeatureCategory,
  Feature,
  UseCase,
  ComparisonData,
  FAQ,
  ComparisonTableProps,
  PricingComparisonProps,
  ProsConsCardProps,
  UseCaseSectionProps,
  ComparisonCTAProps,
  ComparisonSchemaProps,
  ComparisonPageProps,
  // Type guards
  isCompetitorData,
  isPricingTier,
  isFeatureCategory,
  isFeature,
  isUseCase,
  isComparisonData,
  isFAQ,
  // Constants
  AUDIENCE_TYPES,
  BUDGET_TYPES,
} from '@/lib/compare/types';

// ============================================================================
// Mock Data
// ============================================================================

const validPricingTier: PricingTier = {
  name: 'Lite',
  monthlyPrice: 99,
  annualPrice: 82,
  features: ['Backlink analysis', 'Keyword research'],
};

const validPricingTierFree: PricingTier = {
  name: 'Free',
  monthlyPrice: 'Free',
  annualPrice: 'Free',
  features: ['Basic analysis'],
};

const validFeature: Feature = {
  name: 'Backlink Analysis',
  seoindicator: true,
  competitor: true,
  tooltip: 'Comprehensive backlink tracking',
};

const validFeatureCategory: FeatureCategory = {
  name: 'Backlink Features',
  features: [validFeature],
};

const validUseCase: UseCase = {
  title: 'Solo Blogger',
  description: 'Perfect for individual content creators',
  audience: 'solo',
  budget: 'budget',
};

const validCompetitorData: CompetitorData = {
  id: 'ahrefs',
  name: 'Ahrefs',
  slug: 'ahrefs',
  website: 'https://ahrefs.com',
  logo: 'https://ahrefs.com/logo.png',
  tagline: 'All-in-one SEO toolset for backlinks and content',
  pricing: [validPricingTier],
  features: [validFeatureCategory],
  pros: ['Comprehensive data', 'User-friendly interface'],
  cons: ['Expensive pricing', 'Steeper learning curve'],
  useCases: [validUseCase],
  lastUpdated: '2026-03-05T00:00:00Z',
  source: 'Official website',
};

const validFAQ: FAQ = {
  question: 'Is SEOIndicator free?',
  answer: 'Yes, SEOIndicator offers a free tier with essential features.',
};

const validComparisonData: ComparisonData = {
  slug: 'seoindicator-vs-ahrefs',
  title: 'SEOIndicator vs Ahrefs: Complete Comparison',
  metaDescription: 'Compare SEOIndicator and Ahrefs features, pricing, and use cases.',
  competitors: ['seoindicator', 'ahrefs'],
  summary: 'Both tools offer comprehensive SEO analysis capabilities.',
  featureComparison: [validFeatureCategory],
  pricingComparison: {
    seoindicator: [validPricingTierFree],
    competitor: [validPricingTier],
  },
  prosCons: {
    seoindicator: { pros: ['Free tier', 'Easy to use'], cons: ['Limited advanced features'] },
    competitor: { pros: ['Comprehensive data', 'Industry standard'], cons: ['Expensive'] },
  },
  useCases: {
    seoindicator: [validUseCase],
    competitor: [validUseCase],
  },
  verdict: 'Choose SEOIndicator for budget-conscious users and Ahrefs for power users.',
  faqs: [validFAQ],
  relatedComparisons: ['seoindicator-vs-semrush', 'ahrefs-vs-semrush'],
};

// ============================================================================
// TS-COMP-001: CompetitorData Interface Tests
// ============================================================================

describe('CompetitorData Interface', () => {
  describe('Valid CompetitorData', () => {
    it('should accept complete CompetitorData with all required fields', () => {
      expect(isCompetitorData(validCompetitorData)).toBe(true);
    });

    it('should accept CompetitorData without optional logo field', () => {
      const dataWithoutLogo = { ...validCompetitorData } as Partial<CompetitorData>;
      delete dataWithoutLogo.logo;
      expect(isCompetitorData(dataWithoutLogo)).toBe(true);
    });

    it('should accept CompetitorData with empty arrays', () => {
      const dataWithEmptyArrays = {
        ...validCompetitorData,
        pricing: [],
        features: [],
        pros: [],
        cons: [],
        useCases: [],
      };
      expect(isCompetitorData(dataWithEmptyArrays)).toBe(true);
    });
  });

  describe('Missing Required Fields', () => {
    it('should reject CompetitorData without id', () => {
      const invalid = { ...validCompetitorData } as Partial<CompetitorData>;
      delete invalid.id;
      expect(isCompetitorData(invalid)).toBe(false);
    });

    it('should reject CompetitorData without name', () => {
      const invalid = { ...validCompetitorData } as Partial<CompetitorData>;
      delete invalid.name;
      expect(isCompetitorData(invalid)).toBe(false);
    });

    it('should reject CompetitorData without slug', () => {
      const invalid = { ...validCompetitorData } as Partial<CompetitorData>;
      delete invalid.slug;
      expect(isCompetitorData(invalid)).toBe(false);
    });

    it('should reject CompetitorData without website', () => {
      const invalid = { ...validCompetitorData } as Partial<CompetitorData>;
      delete invalid.website;
      expect(isCompetitorData(invalid)).toBe(false);
    });

    it('should reject CompetitorData without tagline', () => {
      const invalid = { ...validCompetitorData } as Partial<CompetitorData>;
      delete invalid.tagline;
      expect(isCompetitorData(invalid)).toBe(false);
    });

    it('should reject CompetitorData without pricing', () => {
      const invalid = { ...validCompetitorData } as Partial<CompetitorData>;
      delete invalid.pricing;
      expect(isCompetitorData(invalid)).toBe(false);
    });

    it('should reject CompetitorData without features', () => {
      const invalid = { ...validCompetitorData } as Partial<CompetitorData>;
      delete invalid.features;
      expect(isCompetitorData(invalid)).toBe(false);
    });

    it('should reject CompetitorData without pros', () => {
      const invalid = { ...validCompetitorData } as Partial<CompetitorData>;
      delete invalid.pros;
      expect(isCompetitorData(invalid)).toBe(false);
    });

    it('should reject CompetitorData without cons', () => {
      const invalid = { ...validCompetitorData } as Partial<CompetitorData>;
      delete invalid.cons;
      expect(isCompetitorData(invalid)).toBe(false);
    });

    it('should reject CompetitorData without useCases', () => {
      const invalid = { ...validCompetitorData } as Partial<CompetitorData>;
      delete invalid.useCases;
      expect(isCompetitorData(invalid)).toBe(false);
    });

    it('should reject CompetitorData without lastUpdated', () => {
      const invalid = { ...validCompetitorData } as Partial<CompetitorData>;
      delete invalid.lastUpdated;
      expect(isCompetitorData(invalid)).toBe(false);
    });

    it('should reject CompetitorData without source', () => {
      const invalid = { ...validCompetitorData } as Partial<CompetitorData>;
      delete invalid.source;
      expect(isCompetitorData(invalid)).toBe(false);
    });
  });

  describe('Invalid Types', () => {
    it('should reject CompetitorData with id as number', () => {
      const invalid = { ...validCompetitorData, id: 123 as unknown as string };
      expect(isCompetitorData(invalid)).toBe(false);
    });

    it('should reject CompetitorData with pricing as object (not array)', () => {
      const invalid = { ...validCompetitorData, pricing: {} as unknown as PricingTier[] };
      expect(isCompetitorData(invalid)).toBe(false);
    });

    it('should reject CompetitorData with features as null', () => {
      const invalid = { ...validCompetitorData, features: null as unknown as FeatureCategory[] };
      expect(isCompetitorData(invalid)).toBe(false);
    });
  });
});

// ============================================================================
// TS-COMP-002: PricingTier Interface Tests
// ============================================================================

describe('PricingTier Interface', () => {
  describe('Valid PricingTier', () => {
    it('should accept complete PricingTier with all required fields', () => {
      expect(isPricingTier(validPricingTier)).toBe(true);
    });

    it('should accept PricingTier with monthlyPrice as number', () => {
      expect(isPricingTier({ ...validPricingTier, monthlyPrice: 29 })).toBe(true);
    });

    it('should accept PricingTier with monthlyPrice as "Free"', () => {
      expect(isPricingTier({ ...validPricingTier, monthlyPrice: 'Free' })).toBe(true);
    });

    it('should accept PricingTier with annualPrice as number', () => {
      expect(isPricingTier({ ...validPricingTier, annualPrice: 199 })).toBe(true);
    });

    it('should accept PricingTier with annualPrice as "Free"', () => {
      expect(isPricingTier({ ...validPricingTier, annualPrice: 'Free' })).toBe(true);
    });

    it('should accept PricingTier with optional highlighted field', () => {
      expect(isPricingTier({ ...validPricingTier, highlighted: true })).toBe(true);
    });
  });

  describe('Missing Required Fields', () => {
    it('should reject PricingTier without name', () => {
      const invalid = { ...validPricingTier } as Partial<PricingTier>;
      delete invalid.name;
      expect(isPricingTier(invalid)).toBe(false);
    });

    it('should reject PricingTier without monthlyPrice', () => {
      const invalid = { ...validPricingTier } as Partial<PricingTier>;
      delete invalid.monthlyPrice;
      expect(isPricingTier(invalid)).toBe(false);
    });

    it('should reject PricingTier without annualPrice', () => {
      const invalid = { ...validPricingTier } as Partial<PricingTier>;
      delete invalid.annualPrice;
      expect(isPricingTier(invalid)).toBe(false);
    });

    it('should reject PricingTier without features', () => {
      const invalid = { ...validPricingTier } as Partial<PricingTier>;
      delete invalid.features;
      expect(isPricingTier(invalid)).toBe(false);
    });
  });

  describe('Invalid Types', () => {
    it('should reject PricingTier with monthlyPrice as null', () => {
      const invalid = { ...validPricingTier, monthlyPrice: null as unknown as number };
      expect(isPricingTier(invalid)).toBe(false);
    });

    it('should reject PricingTier with features as string', () => {
      const invalid = { ...validPricingTier, features: 'string' as unknown as string[] };
      expect(isPricingTier(invalid)).toBe(false);
    });
  });
});

// ============================================================================
// TS-COMP-003: FeatureCategory Interface Tests
// ============================================================================

describe('FeatureCategory Interface', () => {
  describe('Valid FeatureCategory', () => {
    it('should accept complete FeatureCategory with all required fields', () => {
      expect(isFeatureCategory(validFeatureCategory)).toBe(true);
    });

    it('should accept FeatureCategory with empty features array', () => {
      expect(isFeatureCategory({ ...validFeatureCategory, features: [] })).toBe(true);
    });

    it('should accept FeatureCategory with multiple features', () => {
      const category = {
        ...validFeatureCategory,
        features: [validFeature, { ...validFeature, name: 'Another Feature' }],
      };
      expect(isFeatureCategory(category)).toBe(true);
    });
  });

  describe('Missing Required Fields', () => {
    it('should reject FeatureCategory without name', () => {
      const invalid = { ...validFeatureCategory } as Partial<FeatureCategory>;
      delete invalid.name;
      expect(isFeatureCategory(invalid)).toBe(false);
    });

    it('should reject FeatureCategory without features', () => {
      const invalid = { ...validFeatureCategory } as Partial<FeatureCategory>;
      delete invalid.features;
      expect(isFeatureCategory(invalid)).toBe(false);
    });
  });
});

// ============================================================================
// TS-COMP-004: Feature Interface Tests
// ============================================================================

describe('Feature Interface', () => {
  describe('Valid Feature', () => {
    it('should accept complete Feature with all required fields', () => {
      expect(isFeature(validFeature)).toBe(true);
    });

    it('should accept Feature with seoindicator as boolean', () => {
      expect(isFeature({ ...validFeature, seoindicator: true })).toBe(true);
      expect(isFeature({ ...validFeature, seoindicator: false })).toBe(true);
    });

    it('should accept Feature with seoindicator as string', () => {
      expect(isFeature({ ...validFeature, seoindicator: 'Yes' })).toBe(true);
      expect(isFeature({ ...validFeature, seoindicator: 'Partial' })).toBe(true);
    });

    it('should accept Feature with competitor as boolean', () => {
      expect(isFeature({ ...validFeature, competitor: true })).toBe(true);
    });

    it('should accept Feature with competitor as string', () => {
      expect(isFeature({ ...validFeature, competitor: 'Yes' })).toBe(true);
    });

    it('should accept Feature with optional tooltip', () => {
      expect(isFeature({ ...validFeature, tooltip: 'Help text' })).toBe(true);
    });

    it('should accept Feature without optional tooltip', () => {
      const featureWithoutTooltip = { ...validFeature } as Partial<Feature>;
      delete featureWithoutTooltip.tooltip;
      expect(isFeature(featureWithoutTooltip)).toBe(true);
    });
  });

  describe('Missing Required Fields', () => {
    it('should reject Feature without name', () => {
      const invalid = { ...validFeature } as Partial<Feature>;
      delete invalid.name;
      expect(isFeature(invalid)).toBe(false);
    });

    it('should reject Feature without seoindicator', () => {
      const invalid = { ...validFeature } as Partial<Feature>;
      delete invalid.seoindicator;
      expect(isFeature(invalid)).toBe(false);
    });

    it('should reject Feature without competitor', () => {
      const invalid = { ...validFeature } as Partial<Feature>;
      delete invalid.competitor;
      expect(isFeature(invalid)).toBe(false);
    });
  });

  describe('Invalid Types', () => {
    it('should reject Feature with seoindicator as null', () => {
      const invalid = { ...validFeature, seoindicator: null as unknown as boolean };
      expect(isFeature(invalid)).toBe(false);
    });
  });
});

// ============================================================================
// TS-COMP-005: UseCase Interface Tests
// ============================================================================

describe('UseCase Interface', () => {
  describe('Valid UseCase', () => {
    it('should accept complete UseCase with all required fields', () => {
      expect(isUseCase(validUseCase)).toBe(true);
    });

    it.each(AUDIENCE_TYPES)('should accept audience: %s', (audience) => {
      expect(isUseCase({ ...validUseCase, audience })).toBe(true);
    });

    it.each(BUDGET_TYPES)('should accept budget: %s', (budget) => {
      expect(isUseCase({ ...validUseCase, budget })).toBe(true);
    });
  });

  describe('Missing Required Fields', () => {
    it('should reject UseCase without title', () => {
      const invalid = { ...validUseCase } as Partial<UseCase>;
      delete invalid.title;
      expect(isUseCase(invalid)).toBe(false);
    });

    it('should reject UseCase without description', () => {
      const invalid = { ...validUseCase } as Partial<UseCase>;
      delete invalid.description;
      expect(isUseCase(invalid)).toBe(false);
    });

    it('should reject UseCase without audience', () => {
      const invalid = { ...validUseCase } as Partial<UseCase>;
      delete invalid.audience;
      expect(isUseCase(invalid)).toBe(false);
    });

    it('should reject UseCase without budget', () => {
      const invalid = { ...validUseCase } as Partial<UseCase>;
      delete invalid.budget;
      expect(isUseCase(invalid)).toBe(false);
    });
  });

  describe('Invalid Values', () => {
    it('should reject UseCase with invalid audience', () => {
      const invalid = { ...validUseCase, audience: 'invalid' as unknown as UseCase['audience'] };
      expect(isUseCase(invalid)).toBe(false);
    });

    it('should reject UseCase with invalid budget', () => {
      const invalid = { ...validUseCase, budget: 'expensive' as unknown as UseCase['budget'] };
      expect(isUseCase(invalid)).toBe(false);
    });
  });
});

// ============================================================================
// TS-COMP-006: ComparisonData Interface Tests
// ============================================================================

describe('ComparisonData Interface', () => {
  describe('Valid ComparisonData', () => {
    it('should accept complete ComparisonData with all required fields', () => {
      expect(isComparisonData(validComparisonData)).toBe(true);
    });

    it('should accept ComparisonData with competitors as tuple of 2 strings', () => {
      expect(isComparisonData({ ...validComparisonData, competitors: ['seoindicator', 'ahrefs'] })).toBe(true);
    });

    it('should accept ComparisonData with empty arrays', () => {
      const dataWithEmptyArrays = {
        ...validComparisonData,
        featureComparison: [],
        faqs: [],
        relatedComparisons: [],
      };
      expect(isComparisonData(dataWithEmptyArrays)).toBe(true);
    });
  });

  describe('Missing Required Fields', () => {
    it('should reject ComparisonData without slug', () => {
      const invalid = { ...validComparisonData } as Partial<ComparisonData>;
      delete invalid.slug;
      expect(isComparisonData(invalid)).toBe(false);
    });

    it('should reject ComparisonData without title', () => {
      const invalid = { ...validComparisonData } as Partial<ComparisonData>;
      delete invalid.title;
      expect(isComparisonData(invalid)).toBe(false);
    });

    it('should reject ComparisonData without metaDescription', () => {
      const invalid = { ...validComparisonData } as Partial<ComparisonData>;
      delete invalid.metaDescription;
      expect(isComparisonData(invalid)).toBe(false);
    });

    it('should reject ComparisonData without competitors', () => {
      const invalid = { ...validComparisonData } as Partial<ComparisonData>;
      delete invalid.competitors;
      expect(isComparisonData(invalid)).toBe(false);
    });

    it('should reject ComparisonData without summary', () => {
      const invalid = { ...validComparisonData } as Partial<ComparisonData>;
      delete invalid.summary;
      expect(isComparisonData(invalid)).toBe(false);
    });

    it('should reject ComparisonData without featureComparison', () => {
      const invalid = { ...validComparisonData } as Partial<ComparisonData>;
      delete invalid.featureComparison;
      expect(isComparisonData(invalid)).toBe(false);
    });

    it('should reject ComparisonData without pricingComparison', () => {
      const invalid = { ...validComparisonData } as Partial<ComparisonData>;
      delete invalid.pricingComparison;
      expect(isComparisonData(invalid)).toBe(false);
    });

    it('should reject ComparisonData without prosCons', () => {
      const invalid = { ...validComparisonData } as Partial<ComparisonData>;
      delete invalid.prosCons;
      expect(isComparisonData(invalid)).toBe(false);
    });

    it('should reject ComparisonData without useCases', () => {
      const invalid = { ...validComparisonData } as Partial<ComparisonData>;
      delete invalid.useCases;
      expect(isComparisonData(invalid)).toBe(false);
    });

    it('should reject ComparisonData without verdict', () => {
      const invalid = { ...validComparisonData } as Partial<ComparisonData>;
      delete invalid.verdict;
      expect(isComparisonData(invalid)).toBe(false);
    });

    it('should reject ComparisonData without faqs', () => {
      const invalid = { ...validComparisonData } as Partial<ComparisonData>;
      delete invalid.faqs;
      expect(isComparisonData(invalid)).toBe(false);
    });

    it('should reject ComparisonData without relatedComparisons', () => {
      const invalid = { ...validComparisonData } as Partial<ComparisonData>;
      delete invalid.relatedComparisons;
      expect(isComparisonData(invalid)).toBe(false);
    });
  });

  describe('Invalid Types', () => {
    it('should reject ComparisonData with competitors as single item array', () => {
      const invalid = { ...validComparisonData, competitors: ['single'] as unknown as [string, string] };
      expect(isComparisonData(invalid)).toBe(false);
    });

    it('should reject ComparisonData with competitors as more than 2 items', () => {
      const invalid = { ...validComparisonData, competitors: ['a', 'b', 'c'] as unknown as [string, string] };
      expect(isComparisonData(invalid)).toBe(false);
    });
  });
});

// ============================================================================
// TS-COMP-007: FAQ Interface Tests
// ============================================================================

describe('FAQ Interface', () => {
  describe('Valid FAQ', () => {
    it('should accept complete FAQ with all required fields', () => {
      expect(isFAQ(validFAQ)).toBe(true);
    });

    it('should accept FAQ with long question', () => {
      const longQuestion = 'a'.repeat(500);
      expect(isFAQ({ ...validFAQ, question: longQuestion })).toBe(true);
    });

    it('should accept FAQ with long answer', () => {
      const longAnswer = 'a'.repeat(2000);
      expect(isFAQ({ ...validFAQ, answer: longAnswer })).toBe(true);
    });
  });

  describe('Missing Required Fields', () => {
    it('should reject FAQ without question', () => {
      const invalid = { ...validFAQ } as Partial<FAQ>;
      delete invalid.question;
      expect(isFAQ(invalid)).toBe(false);
    });

    it('should reject FAQ without answer', () => {
      const invalid = { ...validFAQ } as Partial<FAQ>;
      delete invalid.answer;
      expect(isFAQ(invalid)).toBe(false);
    });
  });

  describe('Invalid Types', () => {
    it('should reject FAQ with question as number', () => {
      const invalid = { ...validFAQ, question: 123 as unknown as string };
      expect(isFAQ(invalid)).toBe(false);
    });

    it('should reject FAQ with answer as null', () => {
      const invalid = { ...validFAQ, answer: null as unknown as string };
      expect(isFAQ(invalid)).toBe(false);
    });
  });
});

// ============================================================================
// TS-COMP-008: Component Props Interface Tests (TypeScript Compilation)
// ============================================================================

describe('Component Props Interfaces', () => {
  describe('ComparisonTableProps', () => {
    it('should accept valid ComparisonTableProps', () => {
      const props: ComparisonTableProps = {
        features: [validFeatureCategory],
      };
      expect(props).toBeDefined();
    });

    it('should accept ComparisonTableProps with optional fields', () => {
      const props: ComparisonTableProps = {
        features: [validFeatureCategory],
        highlightWinner: true,
        showTooltips: true,
      };
      expect(props).toBeDefined();
    });
  });

  describe('PricingComparisonProps', () => {
    it('should accept valid PricingComparisonProps', () => {
      const props: PricingComparisonProps = {
        seoindicator: [validPricingTierFree],
        competitor: [validPricingTier],
      };
      expect(props).toBeDefined();
    });

    it('should accept PricingComparisonProps with optional showFreeTier', () => {
      const props: PricingComparisonProps = {
        seoindicator: [validPricingTierFree],
        competitor: [validPricingTier],
        showFreeTier: true,
      };
      expect(props).toBeDefined();
    });
  });

  describe('ProsConsCardProps', () => {
    it('should accept valid ProsConsCardProps', () => {
      const props: ProsConsCardProps = {
        tool: 'SEOIndicator',
        pros: ['Free', 'Easy'],
        cons: ['Limited'],
      };
      expect(props).toBeDefined();
    });

    it('should accept ProsConsCardProps with optional overallRating', () => {
      const props: ProsConsCardProps = {
        tool: 'SEOIndicator',
        pros: ['Free', 'Easy'],
        cons: ['Limited'],
        overallRating: 4.5,
      };
      expect(props).toBeDefined();
    });
  });

  describe('UseCaseSectionProps', () => {
    it('should accept valid UseCaseSectionProps', () => {
      const props: UseCaseSectionProps = {
        seoindicator: [validUseCase],
        competitor: [validUseCase],
      };
      expect(props).toBeDefined();
    });
  });

  describe('ComparisonCTAProps', () => {
    it('should accept valid ComparisonCTAProps with all optional fields', () => {
      const props: ComparisonCTAProps = {
        variant: 'primary',
        text: 'Get Started',
        href: '/signup',
        abTestGroup: 'control',
      };
      expect(props).toBeDefined();
    });

    it('should accept empty ComparisonCTAProps', () => {
      const props: ComparisonCTAProps = {};
      expect(props).toBeDefined();
    });
  });

  describe('ComparisonSchemaProps', () => {
    it('should accept valid ComparisonSchemaProps', () => {
      const props: ComparisonSchemaProps = {
        comparison: validComparisonData,
        faqs: [validFAQ],
      };
      expect(props).toBeDefined();
    });
  });

  describe('ComparisonPageProps', () => {
    it('should accept valid ComparisonPageProps', () => {
      const props: ComparisonPageProps = {
        params: { slug: 'seoindicator-vs-ahrefs' },
      };
      expect(props).toBeDefined();
    });
  });
});

// ============================================================================
// TS-COMP-009: TypeScript Compilation Tests
// ============================================================================

describe('TypeScript Compilation', () => {
  it('should export all interfaces', () => {
    expect(typeof isCompetitorData).toBe('function');
    expect(typeof isPricingTier).toBe('function');
    expect(typeof isFeatureCategory).toBe('function');
    expect(typeof isFeature).toBe('function');
    expect(typeof isUseCase).toBe('function');
    expect(typeof isComparisonData).toBe('function');
    expect(typeof isFAQ).toBe('function');
  });

  it('should export AUDIENCE_TYPES constant', () => {
    expect(Array.isArray(AUDIENCE_TYPES)).toBe(true);
    expect(AUDIENCE_TYPES).toContain('solo');
    expect(AUDIENCE_TYPES).toContain('small-team');
    expect(AUDIENCE_TYPES).toContain('enterprise');
    expect(AUDIENCE_TYPES).toContain('agency');
  });

  it('should export BUDGET_TYPES constant', () => {
    expect(Array.isArray(BUDGET_TYPES)).toBe(true);
    expect(BUDGET_TYPES).toContain('free');
    expect(BUDGET_TYPES).toContain('budget');
    expect(BUDGET_TYPES).toContain('mid-range');
    expect(BUDGET_TYPES).toContain('premium');
  });
});

// ============================================================================
// TS-COMP-010: Edge Cases
// ============================================================================

describe('Edge Cases', () => {
  describe('Null and Undefined Handling', () => {
    it('should reject null for isCompetitorData', () => {
      expect(isCompetitorData(null)).toBe(false);
    });

    it('should reject undefined for isCompetitorData', () => {
      expect(isCompetitorData(undefined)).toBe(false);
    });

    it('should reject null for isComparisonData', () => {
      expect(isComparisonData(null)).toBe(false);
    });

    it('should reject null for isFAQ', () => {
      expect(isFAQ(null)).toBe(false);
    });
  });

  describe('Empty Object Handling', () => {
    it('should reject empty object for isCompetitorData', () => {
      expect(isCompetitorData({})).toBe(false);
    });

    it('should reject empty object for isComparisonData', () => {
      expect(isComparisonData({})).toBe(false);
    });
  });

  describe('Wrong Type Handling', () => {
    it('should reject array for isCompetitorData', () => {
      expect(isCompetitorData([])).toBe(false);
    });

    it('should reject string for isCompetitorData', () => {
      expect(isCompetitorData('string')).toBe(false);
    });

    it('should reject number for isCompetitorData', () => {
      expect(isCompetitorData(123)).toBe(false);
    });
  });

  describe('Unicode and Special Characters', () => {
    it('should accept unicode characters in CompetitorData name', () => {
      const data = { ...validCompetitorData, name: '测试工具 🚀 Ahrefs' };
      expect(isCompetitorData(data)).toBe(true);
    });

    it('should accept unicode characters in FAQ question', () => {
      const faq = { ...validFAQ, question: '这是什么？What is this?' };
      expect(isFAQ(faq)).toBe(true);
    });
  });

  describe('Long String Values', () => {
    it('should accept very long string in name', () => {
      const longName = 'a'.repeat(10000);
      const data = { ...validCompetitorData, name: longName };
      expect(isCompetitorData(data)).toBe(true);
    });

    it('should accept very long string in verdict', () => {
      const longVerdict = 'a'.repeat(10000);
      const data = { ...validComparisonData, verdict: longVerdict };
      expect(isComparisonData(data)).toBe(true);
    });
  });

  describe('Extra Properties', () => {
    it('should accept CompetitorData with extra properties', () => {
      const data = { ...validCompetitorData, extraProperty: 'extra' };
      expect(isCompetitorData(data)).toBe(true);
    });

    it('should accept ComparisonData with extra properties', () => {
      const data = { ...validComparisonData, customField: 'value' };
      expect(isComparisonData(data)).toBe(true);
    });
  });
});

// ============================================================================
// Integration Tests
// ============================================================================

describe('Integration Tests', () => {
  describe('Nested Object Validation', () => {
    it('should validate CompetitorData with nested PricingTier', () => {
      const data: CompetitorData = {
        ...validCompetitorData,
        pricing: [
          { name: 'Free', monthlyPrice: 'Free', annualPrice: 'Free', features: [] },
          { name: 'Pro', monthlyPrice: 99, annualPrice: 82, features: ['Advanced'], highlighted: true },
        ],
      };
      expect(isCompetitorData(data)).toBe(true);
    });

    it('should validate ComparisonData with nested objects', () => {
      const data: ComparisonData = {
        ...validComparisonData,
        pricingComparison: {
          seoindicator: [
            { name: 'Free', monthlyPrice: 'Free', annualPrice: 'Free', features: ['Basic'] },
          ],
          competitor: [
            { name: 'Lite', monthlyPrice: 99, annualPrice: 82, features: ['Full'] },
          ],
        },
      };
      expect(isComparisonData(data)).toBe(true);
    });
  });

  describe('Real-world Scenario', () => {
    it('should validate complete SEOIndicator vs Ahrefs comparison', () => {
      const seoindicatorData: CompetitorData = {
        id: 'seoindicator',
        name: 'SEOIndicator',
        slug: 'seoindicator',
        website: 'https://seoindicator.com',
        tagline: 'Free SEO analysis tool',
        pricing: [{ name: 'Free', monthlyPrice: 'Free', annualPrice: 'Free', features: ['17 SEO Indicators'] }],
        features: [{ name: 'Analysis', features: [{ name: 'SEO Check', seoindicator: true, competitor: true }] }],
        pros: ['Free', 'Easy to use'],
        cons: ['Limited advanced features'],
        useCases: [{ title: 'Bloggers', description: 'For bloggers', audience: 'solo', budget: 'free' }],
        lastUpdated: '2026-03-05T00:00:00Z',
        source: 'Official website',
      };

      const ahrefsData: CompetitorData = {
        id: 'ahrefs',
        name: 'Ahrefs',
        slug: 'ahrefs',
        website: 'https://ahrefs.com',
        tagline: 'All-in-one SEO toolset',
        pricing: [{ name: 'Lite', monthlyPrice: 99, annualPrice: 82, features: ['Full toolkit'] }],
        features: [{ name: 'Analysis', features: [{ name: 'SEO Check', seoindicator: true, competitor: true }] }],
        pros: ['Comprehensive', 'Industry standard'],
        cons: ['Expensive', 'Learning curve'],
        useCases: [{ title: 'Agencies', description: 'For agencies', audience: 'agency', budget: 'premium' }],
        lastUpdated: '2026-03-05T00:00:00Z',
        source: 'Official website',
      };

      expect(isCompetitorData(seoindicatorData)).toBe(true);
      expect(isCompetitorData(ahrefsData)).toBe(true);
    });
  });
});
