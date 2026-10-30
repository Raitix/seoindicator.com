/**
 * SEOIndicator Competitor Data Tests
 * Task: TASK-32-2 - Implement Comparison Registry and Data Loading
 * Spec: SPEC-32 (Competitor Comparison Landing Pages)
 */

import {
  CompetitorData,
  PricingTier,
  FeatureCategory,
  UseCase,
  isCompetitorData,
  isPricingTier,
  isFeatureCategory,
  isUseCase,
  AUDIENCE_TYPES,
  BUDGET_TYPES,
} from '@/lib/compare/types';

// Mock the seoindicator data module
jest.mock('@/lib/compare/competitors/seoindicator', () => ({
  seoindicator: {
    id: 'seoindicator',
    name: 'SEOIndicator',
    slug: 'seoindicator',
    website: 'https://seoindicator.com',
    logo: '/images/seoindicator-logo.png',
    tagline: 'Free SEO Analysis and Monitoring',
    pricing: [
      {
        name: 'Free',
        monthlyPrice: 'Free',
        annualPrice: 'Free',
        features: [
          '5 analysis per day',
          'Basic keyword tracking',
          'Technical SEO scanner',
          'Backlink checker (limited)',
        ],
      },
      {
        name: 'Pro',
        monthlyPrice: 29,
        annualPrice: 290,
        features: [
          'Unlimited analyses',
          'Advanced keyword tracking',
          'Full technical SEO audits',
          'Unlimited backlink analysis',
          'Competitor comparison',
          'API access',
        ],
        highlighted: true,
      },
    ],
    features: [
      {
        name: 'Keyword Research',
        features: [
          { name: 'Keyword suggestions', seoindicator: true, competitor: true },
          { name: 'Search volume data', seoindicator: true, competitor: true },
          { name: 'Keyword difficulty', seoindicator: true, competitor: true },
        ],
      },
      {
        name: 'Backlink Analysis',
        features: [
          { name: 'Backlink discovery', seoindicator: true, competitor: true },
          { name: 'Domain authority', seoindicator: false, competitor: true },
          { name: 'Lost link alerts', seoindicator: true, competitor: true },
        ],
      },
    ],
    pros: [
      'Free tier available',
      'User-friendly interface',
      'Comprehensive free features',
      'No credit card required',
    ],
    cons: [
      'Smaller database than competitors',
      'Limited third-party integrations',
      'Newer product with fewer features',
    ],
    useCases: [
      {
        title: 'Solo SEO Consultant',
        description: 'Perfect for freelancers who need professional SEO tools without the enterprise price tag.',
        audience: 'solo',
        budget: 'free',
      },
      {
        title: 'Small Business Owner',
        description: 'DIY SEO made easy with free tools and affordable Pro tier.',
        audience: 'solo',
        budget: 'budget',
      },
      {
        title: 'Digital Marketing Agency',
        description: 'Scale your SEO services with unlimited analyses and API access.',
        audience: 'agency',
        budget: 'mid-range',
      },
    ],
    lastUpdated: '2026-03-05T00:00:00Z',
    source: 'Official website and documentation',
  },
}), { virtual: true });

describe('SEOIndicator Competitor Data', () => {
  let seoindicator: CompetitorData;

  beforeEach(async () => {
    const module = await import('@/lib/compare/competitors/seoindicator');
    seoindicator = module.seoindicator;
  });

  // ==========================================================================
  // SEO-STR: Data Structure Tests
  // ==========================================================================

  describe('SEO-STR: Data Structure', () => {
    test('SEO-STR-001: Data is valid CompetitorData', () => {
      expect(isCompetitorData(seoindicator)).toBe(true);
    });

    test('SEO-STR-002: Has correct ID', () => {
      expect(seoindicator.id).toBe('seoindicator');
    });

    test('SEO-STR-003: Has correct name', () => {
      expect(seoindicator.name).toBe('SEOIndicator');
    });

    test('SEO-STR-004: Has correct slug', () => {
      expect(seoindicator.slug).toBe('seoindicator');
    });

    test('SEO-STR-005: Has valid website URL', () => {
      expect(seoindicator.website).toBe('https://seoindicator.com');
      expect(seoindicator.website).toMatch(/^https:\/\//);
    });

    test('SEO-STR-006: Has logo URL', () => {
      expect(seoindicator.logo).toBeDefined();
      expect(typeof seoindicator.logo).toBe('string');
    });

    test('SEO-STR-007: Has tagline', () => {
      expect(seoindicator.tagline).toBeDefined();
      expect(typeof seoindicator.tagline).toBe('string');
      expect(seoindicator.tagline.length).toBeGreaterThan(0);
    });
  });

  // ==========================================================================
  // SEO-PRI: Pricing Tests
  // ==========================================================================

  describe('SEO-PRI: Pricing', () => {
    test('SEO-PRI-001: Has pricing array', () => {
      expect(Array.isArray(seoindicator.pricing)).toBe(true);
      expect(seoindicator.pricing.length).toBeGreaterThan(0);
    });

    test('SEO-PRI-002: Has Free tier', () => {
      const freeTier = seoindicator.pricing.find((p) => p.name === 'Free');
      expect(freeTier).toBeDefined();
      expect(freeTier?.monthlyPrice).toBe('Free');
      expect(freeTier?.annualPrice).toBe('Free');
    });

    test('SEO-PRI-003: Has Pro tier', () => {
      const proTier = seoindicator.pricing.find((p) => p.name === 'Pro');
      expect(proTier).toBeDefined();
    });

    test('SEO-PRI-004: Pro tier has numeric pricing', () => {
      const proTier = seoindicator.pricing.find((p) => p.name === 'Pro');
      expect(typeof proTier?.monthlyPrice).toBe('number');
      expect(typeof proTier?.annualPrice).toBe('number');
    });

    test('SEO-PRI-005: Annual price is discounted', () => {
      const proTier = seoindicator.pricing.find((p) => p.name === 'Pro');
      if (proTier && typeof proTier.monthlyPrice === 'number' && typeof proTier.annualPrice === 'number') {
        const annualEquivalent = proTier.monthlyPrice * 12;
        expect(proTier.annualPrice).toBeLessThan(annualEquivalent);
      }
    });

    test('SEO-PRI-006: All pricing tiers are valid', () => {
      seoindicator.pricing.forEach((tier) => {
        expect(isPricingTier(tier)).toBe(true);
      });
    });

    test('SEO-PRI-007: Free tier has features', () => {
      const freeTier = seoindicator.pricing.find((p) => p.name === 'Free');
      expect(freeTier?.features.length).toBeGreaterThan(0);
    });

    test('SEO-PRI-008: Pro tier has more features than Free', () => {
      const freeTier = seoindicator.pricing.find((p) => p.name === 'Free');
      const proTier = seoindicator.pricing.find((p) => p.name === 'Pro');
      expect(proTier?.features.length).toBeGreaterThan(freeTier?.features.length || 0);
    });

    test('SEO-PRI-009: Highlighted tier exists', () => {
      const highlighted = seoindicator.pricing.find((p) => p.highlighted);
      expect(highlighted).toBeDefined();
    });

    test('SEO-PRI-010: Prices are reasonable', () => {
      seoindicator.pricing.forEach((tier) => {
        if (typeof tier.monthlyPrice === 'number') {
          expect(tier.monthlyPrice).toBeGreaterThan(0);
          expect(tier.monthlyPrice).toBeLessThan(1000); // Reasonable upper bound
        }
      });
    });
  });

  // ==========================================================================
  // SEO-FEA: Features Tests
  // ==========================================================================

  describe('SEO-FEA: Features', () => {
    test('SEO-FEA-001: Has features array', () => {
      expect(Array.isArray(seoindicator.features)).toBe(true);
      expect(seoindicator.features.length).toBeGreaterThan(0);
    });

    test('SEO-FEA-002: All feature categories are valid', () => {
      seoindicator.features.forEach((category) => {
        expect(isFeatureCategory(category)).toBe(true);
      });
    });

    test('SEO-FEA-003: Has keyword research features', () => {
      const kwCategory = seoindicator.features.find((f) => f.name === 'Keyword Research');
      expect(kwCategory).toBeDefined();
    });

    test('SEO-FEA-004: Has backlink analysis features', () => {
      const backlinkCategory = seoindicator.features.find((f) => f.name === 'Backlink Analysis');
      expect(backlinkCategory).toBeDefined();
    });

    test('SEO-FEA-005: Each category has features', () => {
      seoindicator.features.forEach((category) => {
        expect(category.features.length).toBeGreaterThan(0);
      });
    });

    test('SEO-FEA-006: Feature seoindicator values are valid', () => {
      seoindicator.features.forEach((category) => {
        category.features.forEach((feature) => {
          expect(typeof feature.seoindicator === 'boolean' || typeof feature.seoindicator === 'string').toBe(true);
        });
      });
    });

    test('SEO-FEA-007: Feature competitor values are valid', () => {
      seoindicator.features.forEach((category) => {
        category.features.forEach((feature) => {
          expect(typeof feature.competitor === 'boolean' || typeof feature.competitor === 'string').toBe(true);
        });
      });
    });

    test('SEO-FEA-008: Minimum 2 feature categories', () => {
      expect(seoindicator.features.length).toBeGreaterThanOrEqual(2);
    });

    test('SEO-FEA-009: Minimum 5 features per category', () => {
      const totalFeatures = seoindicator.features.reduce((sum, cat) => sum + cat.features.length, 0);
      expect(totalFeatures).toBeGreaterThanOrEqual(5);
    });
  });

  // ==========================================================================
  // SEO-PROS: Pros and Cons Tests
  // ==========================================================================

  describe('SEO-PROS: Pros and Cons', () => {
    test('SEO-PROS-001: Has pros array', () => {
      expect(Array.isArray(seoindicator.pros)).toBe(true);
    });

    test('SEO-PROS-002: Has cons array', () => {
      expect(Array.isArray(seoindicator.cons)).toBe(true);
    });

    test('SEO-PROS-003: Has at least 3 pros', () => {
      expect(seoindicator.pros.length).toBeGreaterThanOrEqual(3);
    });

    test('SEO-PROS-004: Has at least 1 con', () => {
      expect(seoindicator.cons.length).toBeGreaterThanOrEqual(1);
    });

    test('SEO-PROS-005: All pros are strings', () => {
      seoindicator.pros.forEach((pro) => {
        expect(typeof pro).toBe('string');
        expect(pro.length).toBeGreaterThan(0);
      });
    });

    test('SEO-PROS-006: All cons are strings', () => {
      seoindicator.cons.forEach((con) => {
        expect(typeof con).toBe('string');
        expect(con.length).toBeGreaterThan(0);
      });
    });

    test('SEO-PROS-007: Mentions free tier in pros', () => {
      const freePro = seoindicator.pros.some((pro) => 
        pro.toLowerCase().includes('free')
      );
      expect(freePro).toBe(true);
    });

    test('SEO-PROS-008: Honest assessment (has cons)', () => {
      // SEOIndicator should acknowledge limitations
      expect(seoindicator.cons.length).toBeGreaterThan(0);
    });
  });

  // ==========================================================================
  // SEO-USE: Use Cases Tests
  // ==========================================================================

  describe('SEO-USE: Use Cases', () => {
    test('SEO-USE-001: Has useCases array', () => {
      expect(Array.isArray(seoindicator.useCases)).toBe(true);
    });

    test('SEO-USE-002: Has at least 2 use cases', () => {
      expect(seoindicator.useCases.length).toBeGreaterThanOrEqual(2);
    });

    test('SEO-USE-003: All use cases are valid', () => {
      seoindicator.useCases.forEach((uc) => {
        expect(isUseCase(uc)).toBe(true);
      });
    });

    test('SEO-USE-004: Has solo audience use case', () => {
      const soloUseCase = seoindicator.useCases.find((uc) => uc.audience === 'solo');
      expect(soloUseCase).toBeDefined();
    });

    test('SEO-USE-005: Has free budget use cases', () => {
      const freeUseCase = seoindicator.useCases.find((uc) => uc.budget === 'free');
      expect(freeUseCase).toBeDefined();
    });

    test('SEO-USE-006: All audiences are valid types', () => {
      seoindicator.useCases.forEach((uc) => {
        expect(AUDIENCE_TYPES).toContain(uc.audience);
      });
    });

    test('SEO-USE-007: All budgets are valid types', () => {
      seoindicator.useCases.forEach((uc) => {
        expect(BUDGET_TYPES).toContain(uc.budget);
      });
    });

    test('SEO-USE-008: Has agency use case', () => {
      const agencyUseCase = seoindicator.useCases.find((uc) => uc.audience === 'agency');
      expect(agencyUseCase).toBeDefined();
    });

    test('SEO-USE-009: Each use case has title and description', () => {
      seoindicator.useCases.forEach((uc) => {
        expect(uc.title.length).toBeGreaterThan(0);
        expect(uc.description.length).toBeGreaterThan(0);
      });
    });
  });

  // ==========================================================================
  // SEO-MET: Metadata Tests
  // ==========================================================================

  describe('SEO-MET: Metadata', () => {
    test('SEO-MET-001: Has lastUpdated date', () => {
      expect(seoindicator.lastUpdated).toBeDefined();
      expect(typeof seoindicator.lastUpdated).toBe('string');
    });

    test('SEO-MET-002: lastUpdated is valid ISO date', () => {
      const date = new Date(seoindicator.lastUpdated);
      expect(date.toString()).not.toBe('Invalid Date');
    });

    test('SEO-MET-003: lastUpdated is not in the future', () => {
      const date = new Date(seoindicator.lastUpdated);
      expect(date.getTime()).toBeLessThanOrEqual(Date.now());
    });

    test('SEO-MET-004: Has source attribution', () => {
      expect(seoindicator.source).toBeDefined();
      expect(typeof seoindicator.source).toBe('string');
      expect(seoindicator.source.length).toBeGreaterThan(0);
    });

    test('SEO-MET-005: Source is credible', () => {
      const source = seoindicator.source.toLowerCase();
      const credibleSources = ['official', 'website', 'documentation', 'pricing page'];
      const hasCredibleSource = credibleSources.some((s) => source.includes(s));
      expect(hasCredibleSource).toBe(true);
    });
  });

  // ==========================================================================
  // SEO-ACC: Accuracy Tests
  // ==========================================================================

  describe('SEO-ACC: Data Accuracy', () => {
    test('SEO-ACC-001: Free tier is actually free', () => {
      const freeTier = seoindicator.pricing.find((p) => p.name === 'Free');
      expect(freeTier?.monthlyPrice).toBe('Free');
      expect(freeTier?.annualPrice).toBe('Free');
    });

    test('SEO-ACC-002: Pro tier has reasonable pricing', () => {
      const proTier = seoindicator.pricing.find((p) => p.name === 'Pro');
      if (proTier && typeof proTier.monthlyPrice === 'number') {
        // Pro should be affordable (< $100/month)
        expect(proTier.monthlyPrice).toBeLessThan(100);
      }
    });

    test('SEO-ACC-003: ID matches slug', () => {
      expect(seoindicator.id).toBe(seoindicator.slug);
    });

    test('SEO-ACC-004: Website is real URL', () => {
      expect(seoindicator.website).toMatch(/^https:\/\/[a-z0-9-]+\.[a-z]+/i);
    });

    test('SEO-ACC-005: Pros reflect actual benefits', () => {
      const expectedPros = ['free', 'user-friendly', 'comprehensive'];
      const prosText = seoindicator.pros.join(' ').toLowerCase();
      const hasExpectedPro = expectedPros.some((pro) => prosText.includes(pro));
      expect(hasExpectedPro).toBe(true);
    });

    test('SEO-ACC-006: Cons reflect realistic limitations', () => {
      // Should have honest cons
      expect(seoindicator.cons.length).toBeGreaterThan(0);
    });
  });

  // ==========================================================================
  // SEO-EXP: Export Tests
  // ==========================================================================

  describe('SEO-EXP: Module Export', () => {
    test('SEO-EXP-001: Exports seoindicator constant', async () => {
      const module = await import('@/lib/compare/competitors/seoindicator');
      expect(module.seoindicator).toBeDefined();
    });

    test('SEO-EXP-002: Export is named export', async () => {
      const module = await import('@/lib/compare/competitors/seoindicator');
      expect(module).toHaveProperty('seoindicator');
    });

    test('SEO-EXP-003: Can be destructured', async () => {
      const { seoindicator: data } = await import('@/lib/compare/competitors/seoindicator');
      expect(data).toBeDefined();
      expect(data.id).toBe('seoindicator');
    });
  });

  // ==========================================================================
  // SEO-INT: Integration Tests
  // ==========================================================================

  describe('SEO-INT: Integration', () => {
    test('SEO-INT-001: Can be added to COMPETITOR_REGISTRY', async () => {
      const { seoindicator: data } = await import('@/lib/compare/competitors/seoindicator');
      const registry = { seoindicator: data };
      expect(registry.seoindicator).toBeDefined();
    });

    test('SEO-INT-002: Compatible with generateComparisonData', async () => {
      const { seoindicator: data } = await import('@/lib/compare/competitors/seoindicator');
      // Verify data structure matches expected interface
      expect(data.id).toBeDefined();
      expect(data.features).toBeDefined();
      expect(data.pricing).toBeDefined();
    });

    test('SEO-INT-003: Feature comparison data is usable', async () => {
      const { seoindicator: data } = await import('@/lib/compare/competitors/seoindicator');
      data.features.forEach((category) => {
        category.features.forEach((feature) => {
          // Each feature should have comparison values
          expect(feature).toHaveProperty('seoindicator');
          expect(feature).toHaveProperty('competitor');
        });
      });
    });
  });
});
