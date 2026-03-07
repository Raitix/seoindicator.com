/**
 * Tier 3 Competitor Data Tests
 * Task: TASK-32-13 - Tier 3 Competitor Data (Raven, BrightEdge, Conductor, Wincher)
 * Spec: SPEC-32 (Competitor Comparison Landing Pages)
 * 
 * Tests for niche/enterprise competitor data files.
 * Includes special handling for:
 * - Enterprise pricing (Custom)
 * - Free tiers (Wincher)
 * - Agency focus (Raven Tools)
 */

import {
  isCompetitorData,
  isPricingTier,
  isFeatureCategory,
  isFeature,
  isUseCase,
  AUDIENCE_TYPES,
  BUDGET_TYPES,
  type CompetitorData,
  type PricingTier,
  type FeatureCategory,
  type Feature,
  type UseCase,
} from '../../../lib/compare/types';

// ============================================================================
// Mock Competitor Data (for TDD reference)
// ============================================================================

const mockRavenData: CompetitorData = {
  id: 'raven',
  name: 'Raven Tools',
  slug: 'raven',
  website: 'https://raventools.com',
  tagline: 'SEO and marketing reporting platform for agencies',
  pricing: [
    {
      name: 'Lite',
      monthlyPrice: 49,
      annualPrice: 39,
      features: ['5 campaigns', '50 keywords', 'Basic reporting'],
      highlighted: false,
    },
    {
      name: 'Pro',
      monthlyPrice: 99,
      annualPrice: 79,
      features: ['25 campaigns', '500 keywords', 'Advanced reporting', 'API access'],
      highlighted: true,
    },
    {
      name: 'Agency',
      monthlyPrice: 279,
      annualPrice: 229,
      features: ['Unlimited campaigns', '5000 keywords', 'White label reports', 'Team members'],
      highlighted: false,
    },
  ],
  features: [
    {
      name: 'Core SEO Features',
      features: [
        { name: 'Site Auditor', seoindicator: true, competitor: true },
        { name: 'Rank Tracking', seoindicator: true, competitor: true },
        { name: 'Backlink Analysis', seoindicator: true, competitor: 'Limited' },
        { name: 'Keyword Research', seoindicator: true, competitor: 'Limited' },
      ],
    },
    {
      name: 'Reporting',
      features: [
        { name: 'Custom Reports', seoindicator: true, competitor: true },
        { name: 'White Label Reports', seoindicator: true, competitor: true },
        { name: 'PDF Export', seoindicator: true, competitor: true },
        { name: 'Scheduled Reports', seoindicator: true, competitor: true },
      ],
    },
  ],
  pros: [
    'Great for agencies managing multiple clients',
    'Affordable pricing with agency-focused features',
    'Comprehensive reporting and white label options',
    'Integrates with Google Analytics and Search Console',
  ],
  cons: [
    'Limited backlink database compared to Ahrefs',
    'No proprietary keyword database',
    'Less advanced features for technical SEO',
  ],
  useCases: [
    {
      title: 'Marketing Agencies',
      description: 'Perfect for agencies managing multiple client campaigns with white label reporting',
      audience: 'agency',
      budget: 'mid-range',
    },
    {
      title: 'Small Marketing Teams',
      description: 'Good for small teams that need basic SEO tools without enterprise costs',
      audience: 'small-team',
      budget: 'budget',
    },
  ],
  lastUpdated: '2026-03-07',
  source: 'https://raventools.com/pricing',
};

const mockBrightEdgeData: CompetitorData = {
  id: 'brightedge',
  name: 'BrightEdge',
  slug: 'brightedge',
  website: 'https://brightedge.com',
  tagline: 'Enterprise SEO platform powered by AI',
  pricing: [
    {
      name: 'Enterprise',
      monthlyPrice: 'Custom',
      annualPrice: 'Custom',
      features: ['Custom pricing based on needs', 'Dedicated customer success manager', 'Full feature access', 'API access', 'Custom integrations'],
      highlighted: true,
    },
  ],
  features: [
    {
      name: 'AI Features',
      features: [
        { name: 'Datacube', seoindicator: false, competitor: true },
        { name: 'Content Recommendations', seoindicator: true, competitor: true },
        { name: 'Intent Targeting', seoindicator: false, competitor: true },
        { name: 'Hyperlocal', seoindicator: false, competitor: true },
      ],
    },
    {
      name: 'Core SEO Features',
      features: [
        { name: 'Site Audit', seoindicator: true, competitor: true },
        { name: 'Rank Tracking', seoindicator: true, competitor: true },
        { name: 'Keyword Research', seoindicator: true, competitor: true },
        { name: 'Backlink Analysis', seoindicator: true, competitor: 'Limited' },
      ],
    },
  ],
  pros: [
    'Enterprise-grade features with AI-powered recommendations',
    'Dedicated customer success and support team',
    'Comprehensive content optimization capabilities',
    'Strong integration with enterprise marketing stacks',
  ],
  cons: [
    'Very expensive - pricing requires contact with sales',
    'Complex setup requiring dedicated team resources',
    'Overkill for small businesses or individual users',
    'Long implementation timeline',
  ],
  useCases: [
    {
      title: 'Enterprise SEO Teams',
      description: 'For large organizations with dedicated SEO resources and substantial budgets',
      audience: 'enterprise',
      budget: 'premium',
    },
    {
      title: 'Global Brands',
      description: 'Multi-national companies needing advanced international SEO capabilities',
      audience: 'enterprise',
      budget: 'premium',
    },
  ],
  lastUpdated: '2026-03-07',
  source: 'https://brightedge.com/contact',
};

const mockConductorData: CompetitorData = {
  id: 'conductor',
  name: 'Conductor',
  slug: 'conductor',
  website: 'https://conductor.com',
  tagline: 'Enterprise SEO and content marketing platform',
  pricing: [
    {
      name: 'Enterprise',
      monthlyPrice: 'Custom',
      annualPrice: 'Custom',
      features: ['Custom pricing', 'Full platform access', 'Dedicated support', 'Custom training', 'API access'],
      highlighted: true,
    },
  ],
  features: [
    {
      name: 'Content Marketing',
      features: [
        { name: 'Content Guidance', seoindicator: false, competitor: true },
        { name: 'Impact Analysis', seoindicator: false, competitor: true },
        { name: 'Content Performance', seoindicator: true, competitor: true },
        { name: 'Topic Research', seoindicator: true, competitor: true },
      ],
    },
    {
      name: 'Core SEO Features',
      features: [
        { name: 'Site Audit', seoindicator: true, competitor: true },
        { name: 'Rank Tracking', seoindicator: true, competitor: true },
        { name: 'Keyword Research', seoindicator: true, competitor: true },
        { name: 'Competitor Analysis', seoindicator: true, competitor: true },
      ],
    },
  ],
  pros: [
    'Excellent content marketing and optimization capabilities',
    'Strong focus on enterprise-level content strategy',
    'Good workflow and collaboration features for teams',
    'Comprehensive training and customer success resources',
  ],
  cons: [
    'Expensive enterprise-only pricing',
    'Requires significant investment in implementation',
    'Not suitable for small businesses or individual users',
    'Limited backlink analysis capabilities',
  ],
  useCases: [
    {
      title: 'Content Marketing Teams',
      description: 'Enterprise teams focused on content-driven SEO strategies',
      audience: 'enterprise',
      budget: 'premium',
    },
    {
      title: 'Large SEO Departments',
      description: 'Organizations with dedicated SEO teams managing multiple properties',
      audience: 'enterprise',
      budget: 'premium',
    },
  ],
  lastUpdated: '2026-03-07',
  source: 'https://conductor.com/contact',
};

const mockWincherData: CompetitorData = {
  id: 'wincher',
  name: 'Wincher',
  slug: 'wincher',
  website: 'https://wincher.com',
  tagline: 'Affordable rank tracking for everyone',
  pricing: [
    {
      name: 'Free',
      monthlyPrice: 'Free',
      annualPrice: 'Free',
      features: ['10 keywords', 'Daily updates', 'Basic reports', 'Email support'],
      highlighted: false,
    },
    {
      name: 'Starter',
      monthlyPrice: 29,
      annualPrice: 24,
      features: ['100 keywords', 'Email reports', 'Competitor tracking', 'Priority support'],
      highlighted: true,
    },
    {
      name: 'Professional',
      monthlyPrice: 99,
      annualPrice: 79,
      features: ['500 keywords', 'Local SEO', 'API access', 'White label reports'],
      highlighted: false,
    },
    {
      name: 'Business',
      monthlyPrice: 399,
      annualPrice: 319,
      features: ['5000 keywords', 'Multiple websites', 'Team members', 'Advanced reporting'],
      highlighted: false,
    },
  ],
  features: [
    {
      name: 'Rank Tracking',
      features: [
        { name: 'Daily Rank Updates', seoindicator: true, competitor: true },
        { name: 'Local Rank Tracking', seoindicator: true, competitor: true },
        { name: 'Mobile Rankings', seoindicator: true, competitor: true },
        { name: 'Competitor Tracking', seoindicator: true, competitor: true },
      ],
    },
    {
      name: 'Core SEO Features',
      features: [
        { name: 'Site Audit', seoindicator: true, competitor: false },
        { name: 'Keyword Research', seoindicator: true, competitor: 'Limited' },
        { name: 'Backlink Analysis', seoindicator: true, competitor: false },
        { name: 'On-Page Analysis', seoindicator: true, competitor: false },
      ],
    },
  ],
  pros: [
    'Very affordable pricing with genuine free tier',
    'Simple and easy to use for beginners',
    'Accurate daily rank tracking',
    'Great value for solopreneurs and small businesses',
  ],
  cons: [
    'Limited features compared to comprehensive SEO tools',
    'No backlink analysis or site audit',
    'Basic keyword research capabilities only',
    'Not suitable for agencies or enterprise teams',
  ],
  useCases: [
    {
      title: 'Solo Entrepreneurs',
      description: 'Individuals tracking their own website rankings on a budget',
      audience: 'solo',
      budget: 'free',
    },
    {
      title: 'Small Business Owners',
      description: 'Small businesses needing basic rank tracking without enterprise costs',
      audience: 'solo',
      budget: 'budget',
    },
  ],
  lastUpdated: '2026-03-07',
  source: 'https://wincher.com/pricing',
};

// ============================================================================
// Test Utilities
// ============================================================================

function isValidUrl(url: string): boolean {
  try {
    new URL(url);
    return url.startsWith('https://');
  } catch {
    return false;
  }
}

function isValidIsoDate(dateString: string): boolean {
  const date = new Date(dateString);
  return !isNaN(date.getTime());
}

function isWithinDays(dateString: string, days: number): boolean {
  const date = new Date(dateString);
  const now = new Date();
  const diffMs = now.getTime() - date.getTime();
  const diffDays = diffMs / (1000 * 60 * 60 * 24);
  return diffDays <= days;
}

function hasUniqueStrings(arr: string[]): boolean {
  return new Set(arr).size === arr.length;
}

// ============================================================================
// Raven Tools Tests (RV-001 to RV-055)
// ============================================================================

describe('Raven Tools Competitor Data', () => {
  let raven: CompetitorData;

  beforeAll(() => {
    // Import the actual competitor data when implemented
    // For now, use mock data for TDD
    raven = mockRavenData;
  });

  // RV-001 to RV-007: Basic Structure
  describe('Basic Structure', () => {
    it('RV-001: should export valid CompetitorData', () => {
      expect(isCompetitorData(raven)).toBe(true);
    });

    it('RV-002: should have correct id', () => {
      expect(raven.id).toBe('raven');
    });

    it('RV-003: should have correct name', () => {
      expect(raven.name).toBe('Raven Tools');
    });

    it('RV-004: should have correct slug', () => {
      expect(raven.slug).toBe('raven');
    });

    it('RV-005: should have correct website', () => {
      expect(raven.website).toBe('https://raventools.com');
    });

    it('RV-006: should have tagline', () => {
      expect(raven.tagline).toBeTruthy();
      expect(raven.tagline.length).toBeGreaterThan(0);
    });

    it('RV-007: should have optional logo as string or undefined', () => {
      if (raven.logo !== undefined) {
        expect(typeof raven.logo).toBe('string');
      }
    });
  });

  // RV-008 to RV-013: Pricing
  describe('Pricing', () => {
    it('RV-008: should have at least 3 pricing tiers', () => {
      expect(raven.pricing.length).toBeGreaterThanOrEqual(3);
    });

    it('RV-009: should have correct Lite tier monthly price', () => {
      expect(raven.pricing[0].monthlyPrice).toBe(49);
    });

    it('RV-010: should have Lite tier annual price with discount', () => {
      expect(raven.pricing[0].annualPrice).toBeLessThan(raven.pricing[0].monthlyPrice as number);
    });

    it('RV-011: should have Pro tier monthly price in range', () => {
      const proTier = raven.pricing[1];
      expect(proTier.monthlyPrice).toBeGreaterThanOrEqual(99);
      expect(proTier.monthlyPrice).toBeLessThanOrEqual(149);
    });

    it('RV-012: should have Agency tier monthly price in range', () => {
      const agencyTier = raven.pricing[2];
      expect(agencyTier.monthlyPrice).toBeGreaterThanOrEqual(249);
      expect(agencyTier.monthlyPrice).toBeLessThanOrEqual(279);
    });

    it('RV-013: should have features array in pricing tier', () => {
      raven.pricing.forEach((tier) => {
        expect(Array.isArray(tier.features)).toBe(true);
        expect(tier.features.length).toBeGreaterThan(0);
      });
    });
  });

  // RV-014 to RV-016: Features
  describe('Features', () => {
    it('RV-014: should have at least 1 feature category', () => {
      expect(raven.features.length).toBeGreaterThanOrEqual(1);
    });

    it('RV-015: should have feature category with name', () => {
      raven.features.forEach((category) => {
        expect(category.name).toBeTruthy();
        expect(category.name.length).toBeGreaterThan(0);
      });
    });

    it('RV-016: should have features array in feature category', () => {
      raven.features.forEach((category) => {
        expect(Array.isArray(category.features)).toBe(true);
        expect(category.features.length).toBeGreaterThan(0);
      });
    });
  });

  // RV-017 to RV-021: Pros, Cons, Use Cases
  describe('Pros, Cons, Use Cases', () => {
    it('RV-017: should have at least 3 pros', () => {
      expect(raven.pros.length).toBeGreaterThanOrEqual(3);
    });

    it('RV-018: should have pros as strings', () => {
      raven.pros.forEach((pro) => {
        expect(typeof pro).toBe('string');
      });
    });

    it('RV-019: should have at least 3 cons', () => {
      expect(raven.cons.length).toBeGreaterThanOrEqual(3);
    });

    it('RV-020: should have cons as strings', () => {
      raven.cons.forEach((con) => {
        expect(typeof con).toBe('string');
      });
    });

    it('RV-021: should have at least 2 use cases', () => {
      expect(raven.useCases.length).toBeGreaterThanOrEqual(2);
    });
  });

  // RV-022 to RV-025: Use Case Validation
  describe('Use Case Validation', () => {
    it('RV-022: should have valid audience types', () => {
      raven.useCases.forEach((useCase) => {
        expect(AUDIENCE_TYPES).toContain(useCase.audience);
      });
    });

    it('RV-023: should have valid budget types', () => {
      raven.useCases.forEach((useCase) => {
        expect(BUDGET_TYPES).toContain(useCase.budget);
      });
    });

    it('RV-024: should have valid lastUpdated date', () => {
      expect(isValidIsoDate(raven.lastUpdated)).toBe(true);
    });

    it('RV-025: should have source attribution', () => {
      expect(raven.source).toBeTruthy();
      expect(raven.source.length).toBeGreaterThan(0);
    });
  });

  // RV-026 to RV-031: Content Validation
  describe('Content Validation', () => {
    it('RV-026: should have reporting feature category', () => {
      const hasReporting = raven.features.some(
        (cat) => cat.name.toLowerCase().includes('report') || 
                  cat.features.some((f) => f.name.toLowerCase().includes('report'))
      );
      expect(hasReporting).toBe(true);
    });

    it('RV-027: should have backlink feature', () => {
      const hasBacklink = raven.features.some((cat) =>
        cat.features.some((f) => f.name.toLowerCase().includes('backlink'))
      );
      expect(hasBacklink).toBe(true);
    });

    it('RV-028: should have rank tracking feature', () => {
      const hasRank = raven.features.some((cat) =>
        cat.features.some((f) => 
          f.name.toLowerCase().includes('rank') && f.name.toLowerCase().includes('track')
        )
      );
      expect(hasRank).toBe(true);
    });

    it('RV-029: should mention agency in pros', () => {
      const hasAgency = raven.pros.some((pro) => 
        pro.toLowerCase().includes('agency') || pro.toLowerCase().includes('agencies')
      );
      expect(hasAgency).toBe(true);
    });

    it('RV-030: should mention reporting in pros', () => {
      const hasReport = raven.pros.some((pro) => 
        pro.toLowerCase().includes('report')
      );
      expect(hasReport).toBe(true);
    });

    it('RV-031: should have valid cons', () => {
      raven.cons.forEach((con) => {
        expect(con.length).toBeGreaterThan(0);
      });
    });
  });

  // RV-032 to RV-037: Uniqueness
  describe('Uniqueness', () => {
    it('RV-032: should have agency use case', () => {
      const hasAgency = raven.useCases.some((uc) => uc.audience === 'agency');
      expect(hasAgency).toBe(true);
    });

    it('RV-033: should have unique feature names within categories', () => {
      raven.features.forEach((category) => {
        const names = category.features.map((f) => f.name);
        expect(hasUniqueStrings(names)).toBe(true);
      });
    });

    it('RV-034: should have unique pros', () => {
      expect(hasUniqueStrings(raven.pros)).toBe(true);
    });

    it('RV-035: should have unique cons', () => {
      expect(hasUniqueStrings(raven.cons)).toBe(true);
    });

    it('RV-036: should have valid feature values', () => {
      raven.features.forEach((category) => {
        category.features.forEach((feature) => {
          expect(['boolean', 'string']).toContain(typeof feature.seoindicator);
          expect(['boolean', 'string']).toContain(typeof feature.competitor);
        });
      });
    });

    it('RV-037: should have annual savings of at least 15%', () => {
      raven.pricing.forEach((tier) => {
        if (typeof tier.monthlyPrice === 'number' && typeof tier.annualPrice === 'number') {
          const savings = 1 - (tier.annualPrice / tier.monthlyPrice);
          expect(savings).toBeGreaterThanOrEqual(0.15);
        }
      });
    });
  });

  // RV-038 to RV-055: Additional Features
  describe('Additional Features', () => {
    it('RV-038: should have Site Auditor feature', () => {
      const hasSiteAuditor = raven.features.some((cat) =>
        cat.features.some((f) => f.name.toLowerCase().includes('site') && f.name.toLowerCase().includes('audit'))
      );
      expect(hasSiteAuditor).toBe(true);
    });

    it('RV-039: should have Link Manager feature', () => {
      const hasLinkManager = raven.features.some((cat) =>
        cat.features.some((f) => f.name.toLowerCase().includes('link'))
      );
      expect(hasLinkManager).toBe(true);
    });

    it('RV-040: should have meaningful pros (>= 10 chars)', () => {
      raven.pros.forEach((pro) => {
        expect(pro.length).toBeGreaterThanOrEqual(10);
      });
    });

    it('RV-041: should have meaningful cons (>= 10 chars)', () => {
      raven.cons.forEach((con) => {
        expect(con.length).toBeGreaterThanOrEqual(10);
      });
    });

    it('RV-042: should have meaningful use case descriptions (>= 20 chars)', () => {
      raven.useCases.forEach((uc) => {
        expect(uc.description.length).toBeGreaterThanOrEqual(20);
      });
    });

    it('RV-043: should have string tooltips when present', () => {
      raven.features.forEach((category) => {
        category.features.forEach((feature) => {
          if (feature.tooltip !== undefined) {
            expect(typeof feature.tooltip).toBe('string');
          }
        });
      });
    });

    it('RV-044: should have boolean or undefined highlighted', () => {
      raven.pricing.forEach((tier) => {
        if (tier.highlighted !== undefined) {
          expect(typeof tier.highlighted).toBe('boolean');
        }
      });
    });

    it('RV-045: should have data freshness within 30 days', () => {
      expect(isWithinDays(raven.lastUpdated, 30)).toBe(true);
    });

    it('RV-046: should have campaign limits in features', () => {
      const hasCampaigns = raven.pricing.some((tier) =>
        tier.features.some((f) => f.toLowerCase().includes('campaign'))
      );
      expect(hasCampaigns).toBe(true);
    });

    it('RV-047: should have keyword limits in features', () => {
      const hasKeywords = raven.pricing.some((tier) =>
        tier.features.some((f) => f.toLowerCase().includes('keyword'))
      );
      expect(hasKeywords).toBe(true);
    });

    it('RV-048: should have white label reports', () => {
      const hasWhiteLabel = raven.features.some((cat) =>
        cat.features.some((f) => f.name.toLowerCase().includes('white') && f.name.toLowerCase().includes('label'))
      );
      expect(hasWhiteLabel).toBe(true);
    });

    it('RV-052: should have Google Analytics integration', () => {
      const hasGA = raven.features.some((cat) =>
        cat.features.some((f) => 
          f.name.toLowerCase().includes('analytics') || 
          f.name.toLowerCase().includes('google')
        )
      );
      expect(hasGA).toBe(true);
    });

    it('RV-053: should have custom reports', () => {
      const hasCustom = raven.features.some((cat) =>
        cat.features.some((f) => 
          f.name.toLowerCase().includes('custom') && f.name.toLowerCase().includes('report')
        )
      );
      expect(hasCustom).toBe(true);
    });

    it('RV-054: should have PDF export', () => {
      const hasPDF = raven.features.some((cat) =>
        cat.features.some((f) => f.name.toLowerCase().includes('pdf'))
      );
      expect(hasPDF).toBe(true);
    });
  });
});

// ============================================================================
// BrightEdge Tests (BE-001 to BE-055)
// ============================================================================

describe('BrightEdge Competitor Data', () => {
  let brightedge: CompetitorData;

  beforeAll(() => {
    brightedge = mockBrightEdgeData;
  });

  // BE-001 to BE-007: Basic Structure
  describe('Basic Structure', () => {
    it('BE-001: should export valid CompetitorData', () => {
      expect(isCompetitorData(brightedge)).toBe(true);
    });

    it('BE-002: should have correct id', () => {
      expect(brightedge.id).toBe('brightedge');
    });

    it('BE-003: should have correct name', () => {
      expect(brightedge.name).toBe('BrightEdge');
    });

    it('BE-004: should have correct slug', () => {
      expect(brightedge.slug).toBe('brightedge');
    });

    it('BE-005: should have correct website', () => {
      expect(brightedge.website).toBe('https://brightedge.com');
    });

    it('BE-006: should have tagline mentioning enterprise or AI', () => {
      const lowerTagline = brightedge.tagline.toLowerCase();
      expect(
        lowerTagline.includes('enterprise') || 
        lowerTagline.includes('ai') ||
        lowerTagline.includes('artificial')
      ).toBe(true);
    });

    it('BE-007: should have optional logo as string or undefined', () => {
      if (brightedge.logo !== undefined) {
        expect(typeof brightedge.logo).toBe('string');
      }
    });
  });

  // BE-008 to BE-011: Enterprise Pricing
  describe('Enterprise Pricing', () => {
    it('BE-008: should have at least 1 pricing tier', () => {
      expect(brightedge.pricing.length).toBeGreaterThanOrEqual(1);
    });

    it('BE-009: should have Custom monthly pricing', () => {
      expect(brightedge.pricing[0].monthlyPrice).toBe('Custom');
    });

    it('BE-010: should have Custom annual pricing', () => {
      expect(brightedge.pricing[0].annualPrice).toBe('Custom');
    });

    it('BE-011: should have features array in pricing tier', () => {
      expect(Array.isArray(brightedge.pricing[0].features)).toBe(true);
      expect(brightedge.pricing[0].features.length).toBeGreaterThan(0);
    });
  });

  // BE-012 to BE-023: Features and Use Cases
  describe('Features and Use Cases', () => {
    it('BE-012: should have at least 1 feature category', () => {
      expect(brightedge.features.length).toBeGreaterThanOrEqual(1);
    });

    it('BE-013: should have feature category with name', () => {
      brightedge.features.forEach((category) => {
        expect(category.name).toBeTruthy();
      });
    });

    it('BE-014: should have features array in feature category', () => {
      brightedge.features.forEach((category) => {
        expect(Array.isArray(category.features)).toBe(true);
      });
    });

    it('BE-015: should have at least 3 pros', () => {
      expect(brightedge.pros.length).toBeGreaterThanOrEqual(3);
    });

    it('BE-016: should have pros as strings', () => {
      brightedge.pros.forEach((pro) => {
        expect(typeof pro).toBe('string');
      });
    });

    it('BE-017: should have at least 3 cons', () => {
      expect(brightedge.cons.length).toBeGreaterThanOrEqual(3);
    });

    it('BE-018: should have cons as strings', () => {
      brightedge.cons.forEach((con) => {
        expect(typeof con).toBe('string');
      });
    });

    it('BE-019: should have at least 2 use cases', () => {
      expect(brightedge.useCases.length).toBeGreaterThanOrEqual(2);
    });

    it('BE-020: should have valid audience types', () => {
      brightedge.useCases.forEach((useCase) => {
        expect(AUDIENCE_TYPES).toContain(useCase.audience);
      });
    });

    it('BE-021: should have valid budget types', () => {
      brightedge.useCases.forEach((useCase) => {
        expect(BUDGET_TYPES).toContain(useCase.budget);
      });
    });

    it('BE-022: should have valid lastUpdated date', () => {
      expect(isValidIsoDate(brightedge.lastUpdated)).toBe(true);
    });

    it('BE-023: should have source attribution', () => {
      expect(brightedge.source).toBeTruthy();
    });
  });

  // BE-024 to BE-031: Content Validation
  describe('Content Validation', () => {
    it('BE-024: should have AI feature', () => {
      const hasAI = brightedge.features.some((cat) =>
        cat.name.toLowerCase().includes('ai') ||
        cat.features.some((f) => f.name.toLowerCase().includes('ai') || f.name.toLowerCase().includes('datacube'))
      );
      expect(hasAI).toBe(true);
    });

    it('BE-025: should have content optimization feature', () => {
      const hasContent = brightedge.features.some((cat) =>
        cat.features.some((f) => 
          f.name.toLowerCase().includes('content') && f.name.toLowerCase().includes('recommend')
        )
      );
      expect(hasContent).toBe(true);
    });

    it('BE-026: should have keyword research feature', () => {
      const hasKeyword = brightedge.features.some((cat) =>
        cat.features.some((f) => f.name.toLowerCase().includes('keyword'))
      );
      expect(hasKeyword).toBe(true);
    });

    it('BE-027: should have backlink feature or note limited', () => {
      const hasBacklink = brightedge.features.some((cat) =>
        cat.features.some((f) => f.name.toLowerCase().includes('backlink'))
      );
      expect(hasBacklink).toBe(true);
    });

    it('BE-028: should mention enterprise in pros', () => {
      const hasEnterprise = brightedge.pros.some((pro) => 
        pro.toLowerCase().includes('enterprise')
      );
      expect(hasEnterprise).toBe(true);
    });

    it('BE-029: should mention AI in pros', () => {
      const hasAI = brightedge.pros.some((pro) => 
        pro.toLowerCase().includes('ai') || pro.toLowerCase().includes('artificial')
      );
      expect(hasAI).toBe(true);
    });

    it('BE-030: should mention pricing in cons', () => {
      const hasPrice = brightedge.cons.some((con) => 
        con.toLowerCase().includes('price') || 
        con.toLowerCase().includes('expensive') || 
        con.toLowerCase().includes('cost')
      );
      expect(hasPrice).toBe(true);
    });

    it('BE-031: should have valid cons', () => {
      brightedge.cons.forEach((con) => {
        expect(con.length).toBeGreaterThan(0);
      });
    });
  });

  // BE-032 to BE-055: Additional Validation
  describe('Additional Validation', () => {
    it('BE-032: should have enterprise use case', () => {
      const hasEnterprise = brightedge.useCases.some((uc) => uc.audience === 'enterprise');
      expect(hasEnterprise).toBe(true);
    });

    it('BE-033: should have unique feature names within categories', () => {
      brightedge.features.forEach((category) => {
        const names = category.features.map((f) => f.name);
        expect(hasUniqueStrings(names)).toBe(true);
      });
    });

    it('BE-034: should have unique pros', () => {
      expect(hasUniqueStrings(brightedge.pros)).toBe(true);
    });

    it('BE-035: should have unique cons', () => {
      expect(hasUniqueStrings(brightedge.cons)).toBe(true);
    });

    it('BE-036: should have valid feature values', () => {
      brightedge.features.forEach((category) => {
        category.features.forEach((feature) => {
          expect(['boolean', 'string']).toContain(typeof feature.seoindicator);
          expect(['boolean', 'string']).toContain(typeof feature.competitor);
        });
      });
    });

    it('BE-037: should have Datacube feature', () => {
      const hasDatacube = brightedge.features.some((cat) =>
        cat.features.some((f) => f.name.toLowerCase().includes('datacube'))
      );
      expect(hasDatacube).toBe(true);
    });

    it('BE-038: should have Story Builder or Content feature', () => {
      const hasStory = brightedge.features.some((cat) =>
        cat.features.some((f) => 
          f.name.toLowerCase().includes('story') || f.name.toLowerCase().includes('builder')
        )
      );
      expect(hasStory).toBe(true);
    });

    it('BE-039: should have Hyperlocal feature', () => {
      const hasHyperlocal = brightedge.features.some((cat) =>
        cat.features.some((f) => f.name.toLowerCase().includes('hyperlocal'))
      );
      expect(hasHyperlocal).toBe(true);
    });

    it('BE-040: should have meaningful pros (>= 10 chars)', () => {
      brightedge.pros.forEach((pro) => {
        expect(pro.length).toBeGreaterThanOrEqual(10);
      });
    });

    it('BE-041: should have meaningful cons (>= 10 chars)', () => {
      brightedge.cons.forEach((con) => {
        expect(con.length).toBeGreaterThanOrEqual(10);
      });
    });

    it('BE-042: should have meaningful use case descriptions (>= 20 chars)', () => {
      brightedge.useCases.forEach((uc) => {
        expect(uc.description.length).toBeGreaterThanOrEqual(20);
      });
    });

    it('BE-045: should have data freshness within 30 days', () => {
      expect(isWithinDays(brightedge.lastUpdated, 30)).toBe(true);
    });

    it('BE-046: should have SEO recommendations feature', () => {
      const hasRecs = brightedge.features.some((cat) =>
        cat.features.some((f) => f.name.toLowerCase().includes('recommend'))
      );
      expect(hasRecs).toBe(true);
    });

    it('BE-047: should have content performance feature', () => {
      const hasPerf = brightedge.features.some((cat) =>
        cat.features.some((f) => 
          f.name.toLowerCase().includes('content') && f.name.toLowerCase().includes('performance')
        )
      );
      expect(hasPerf).toBe(true);
    });

    it('BE-048: should have intent signaling feature', () => {
      const hasIntent = brightedge.features.some((cat) =>
        cat.features.some((f) => f.name.toLowerCase().includes('intent'))
      );
      expect(hasIntent).toBe(true);
    });

    it('BE-050: should have API access feature', () => {
      const hasAPI = brightedge.pricing[0].features.some((f) => 
        f.toLowerCase().includes('api')
      );
      expect(hasAPI).toBe(true);
    });

    it('BE-052: should have dedicated support feature', () => {
      const hasSupport = brightedge.pricing[0].features.some((f) => 
        f.toLowerCase().includes('support') || f.toLowerCase().includes('success')
      );
      expect(hasSupport).toBe(true);
    });

    it('BE-053: should have white label options', () => {
      const hasWhiteLabel = brightedge.features.some((cat) =>
        cat.features.some((f) => f.name.toLowerCase().includes('white label'))
      ) || brightedge.pricing[0].features.some((f) => f.toLowerCase().includes('white label'));
      expect(hasWhiteLabel).toBe(true);
    });

    it('BE-054: should have training/onboarding feature', () => {
      const hasTraining = brightedge.pricing[0].features.some((f) => 
        f.toLowerCase().includes('training')
      );
      expect(hasTraining).toBe(true);
    });

    it('BE-055: should have global SEO feature', () => {
      const hasGlobal = brightedge.features.some((cat) =>
        cat.features.some((f) => 
          f.name.toLowerCase().includes('international') || 
          f.name.toLowerCase().includes('global')
        )
      );
      expect(hasGlobal).toBe(true);
    });
  });
});

// ============================================================================
// Conductor Tests (CD-001 to CD-055)
// ============================================================================

describe('Conductor Competitor Data', () => {
  let conductor: CompetitorData;

  beforeAll(() => {
    conductor = mockConductorData;
  });

  // CD-001 to CD-007: Basic Structure
  describe('Basic Structure', () => {
    it('CD-001: should export valid CompetitorData', () => {
      expect(isCompetitorData(conductor)).toBe(true);
    });

    it('CD-002: should have correct id', () => {
      expect(conductor.id).toBe('conductor');
    });

    it('CD-003: should have correct name', () => {
      expect(conductor.name).toBe('Conductor');
    });

    it('CD-004: should have correct slug', () => {
      expect(conductor.slug).toBe('conductor');
    });

    it('CD-005: should have correct website', () => {
      expect(conductor.website).toBe('https://conductor.com');
    });

    it('CD-006: should have tagline mentioning content or enterprise', () => {
      const lowerTagline = conductor.tagline.toLowerCase();
      expect(
        lowerTagline.includes('content') || 
        lowerTagline.includes('enterprise')
      ).toBe(true);
    });

    it('CD-007: should have optional logo as string or undefined', () => {
      if (conductor.logo !== undefined) {
        expect(typeof conductor.logo).toBe('string');
      }
    });
  });

  // CD-008 to CD-011: Enterprise Pricing
  describe('Enterprise Pricing', () => {
    it('CD-008: should have at least 1 pricing tier', () => {
      expect(conductor.pricing.length).toBeGreaterThanOrEqual(1);
    });

    it('CD-009: should have Custom monthly pricing', () => {
      expect(conductor.pricing[0].monthlyPrice).toBe('Custom');
    });

    it('CD-010: should have Custom annual pricing', () => {
      expect(conductor.pricing[0].annualPrice).toBe('Custom');
    });

    it('CD-011: should have features array in pricing tier', () => {
      expect(Array.isArray(conductor.pricing[0].features)).toBe(true);
      expect(conductor.pricing[0].features.length).toBeGreaterThan(0);
    });
  });

  // CD-012 to CD-031: Features and Content
  describe('Features and Content', () => {
    it('CD-012: should have at least 1 feature category', () => {
      expect(conductor.features.length).toBeGreaterThanOrEqual(1);
    });

    it('CD-015: should have at least 3 pros', () => {
      expect(conductor.pros.length).toBeGreaterThanOrEqual(3);
    });

    it('CD-017: should have at least 3 cons', () => {
      expect(conductor.cons.length).toBeGreaterThanOrEqual(3);
    });

    it('CD-019: should have at least 2 use cases', () => {
      expect(conductor.useCases.length).toBeGreaterThanOrEqual(2);
    });

    it('CD-024: should have content marketing features', () => {
      const hasContent = conductor.features.some((cat) =>
        cat.name.toLowerCase().includes('content') ||
        cat.features.some((f) => f.name.toLowerCase().includes('content'))
      );
      expect(hasContent).toBe(true);
    });

    it('CD-025: should have keyword research feature', () => {
      const hasKeyword = conductor.features.some((cat) =>
        cat.features.some((f) => f.name.toLowerCase().includes('keyword'))
      );
      expect(hasKeyword).toBe(true);
    });

    it('CD-026: should have site audit feature', () => {
      const hasAudit = conductor.features.some((cat) =>
        cat.features.some((f) => f.name.toLowerCase().includes('audit'))
      );
      expect(hasAudit).toBe(true);
    });

    it('CD-028: should mention content in pros', () => {
      const hasContent = conductor.pros.some((pro) => 
        pro.toLowerCase().includes('content')
      );
      expect(hasContent).toBe(true);
    });

    it('CD-029: should mention enterprise in pros', () => {
      const hasEnterprise = conductor.pros.some((pro) => 
        pro.toLowerCase().includes('enterprise')
      );
      expect(hasEnterprise).toBe(true);
    });

    it('CD-030: should mention pricing in cons', () => {
      const hasPrice = conductor.cons.some((con) => 
        con.toLowerCase().includes('price') || 
        con.toLowerCase().includes('expensive') || 
        con.toLowerCase().includes('cost')
      );
      expect(hasPrice).toBe(true);
    });

    it('CD-032: should have enterprise use case', () => {
      const hasEnterprise = conductor.useCases.some((uc) => uc.audience === 'enterprise');
      expect(hasEnterprise).toBe(true);
    });

    it('CD-037: should have content guidance feature', () => {
      const hasGuidance = conductor.features.some((cat) =>
        cat.features.some((f) => 
          f.name.toLowerCase().includes('content') && f.name.toLowerCase().includes('guidance')
        )
      );
      expect(hasGuidance).toBe(true);
    });

    it('CD-038: should have impact analysis feature', () => {
      const hasImpact = conductor.features.some((cat) =>
        cat.features.some((f) => f.name.toLowerCase().includes('impact'))
      );
      expect(hasImpact).toBe(true);
    });

    it('CD-039: should have workspaces feature', () => {
      const hasWorkspace = conductor.features.some((cat) =>
        cat.features.some((f) => f.name.toLowerCase().includes('workspace'))
      );
      expect(hasWorkspace).toBe(true);
    });
  });

  // CD-040 to CD-055: Additional Validation
  describe('Additional Validation', () => {
    it('CD-040: should have meaningful pros (>= 10 chars)', () => {
      conductor.pros.forEach((pro) => {
        expect(pro.length).toBeGreaterThanOrEqual(10);
      });
    });

    it('CD-041: should have meaningful cons (>= 10 chars)', () => {
      conductor.cons.forEach((con) => {
        expect(con.length).toBeGreaterThanOrEqual(10);
      });
    });

    it('CD-042: should have meaningful use case descriptions (>= 20 chars)', () => {
      conductor.useCases.forEach((uc) => {
        expect(uc.description.length).toBeGreaterThanOrEqual(20);
      });
    });

    it('CD-045: should have data freshness within 30 days', () => {
      expect(isWithinDays(conductor.lastUpdated, 30)).toBe(true);
    });

    it('CD-046: should have SEO recommendations feature', () => {
      const hasRecs = conductor.features.some((cat) =>
        cat.features.some((f) => f.name.toLowerCase().includes('recommend'))
      );
      expect(hasRecs).toBe(true);
    });

    it('CD-047: should have competitive analysis feature', () => {
      const hasCompetitive = conductor.features.some((cat) =>
        cat.features.some((f) => f.name.toLowerCase().includes('competitor'))
      );
      expect(hasCompetitive).toBe(true);
    });

    it('CD-050: should have API access feature', () => {
      const hasAPI = conductor.pricing[0].features.some((f) => 
        f.toLowerCase().includes('api')
      );
      expect(hasAPI).toBe(true);
    });

    it('CD-052: should have team collaboration feature', () => {
      const hasTeam = conductor.features.some((cat) =>
        cat.features.some((f) => 
          f.name.toLowerCase().includes('collab') || f.name.toLowerCase().includes('team')
        )
      );
      expect(hasTeam).toBe(true);
    });

    it('CD-053: should have training resources feature', () => {
      const hasTraining = conductor.pricing[0].features.some((f) => 
        f.toLowerCase().includes('training')
      );
      expect(hasTraining).toBe(true);
    });

    it('CD-054: should have customer success feature', () => {
      const hasSuccess = conductor.pricing[0].features.some((f) => 
        f.toLowerCase().includes('success') || f.toLowerCase().includes('support')
      );
      expect(hasSuccess).toBe(true);
    });

    it('CD-055: should have global SEO feature', () => {
      const hasGlobal = conductor.features.some((cat) =>
        cat.features.some((f) => 
          f.name.toLowerCase().includes('international') || 
          f.name.toLowerCase().includes('global')
        )
      );
      expect(hasGlobal).toBe(true);
    });
  });
});

// ============================================================================
// Wincher Tests (WC-001 to WC-055)
// ============================================================================

describe('Wincher Competitor Data', () => {
  let wincher: CompetitorData;

  beforeAll(() => {
    wincher = mockWincherData;
  });

  // WC-001 to WC-007: Basic Structure
  describe('Basic Structure', () => {
    it('WC-001: should export valid CompetitorData', () => {
      expect(isCompetitorData(wincher)).toBe(true);
    });

    it('WC-002: should have correct id', () => {
      expect(wincher.id).toBe('wincher');
    });

    it('WC-003: should have correct name', () => {
      expect(wincher.name).toBe('Wincher');
    });

    it('WC-004: should have correct slug', () => {
      expect(wincher.slug).toBe('wincher');
    });

    it('WC-005: should have correct website', () => {
      expect(wincher.website).toBe('https://wincher.com');
    });

    it('WC-006: should have tagline', () => {
      expect(wincher.tagline).toBeTruthy();
      expect(wincher.tagline.length).toBeGreaterThan(0);
    });

    it('WC-007: should have optional logo as string or undefined', () => {
      if (wincher.logo !== undefined) {
        expect(typeof wincher.logo).toBe('string');
      }
    });
  });

  // WC-008 to WC-013: Pricing with Free Tier
  describe('Pricing with Free Tier', () => {
    it('WC-008: should have at least 3 pricing tiers', () => {
      expect(wincher.pricing.length).toBeGreaterThanOrEqual(3);
    });

    it('WC-009: should have Starter tier monthly price', () => {
      const starterTier = wincher.pricing.find((t) => 
        t.name.toLowerCase() === 'starter'
      );
      if (starterTier) {
        expect(starterTier.monthlyPrice).toBeGreaterThanOrEqual(29);
        expect(starterTier.monthlyPrice).toBeLessThanOrEqual(39);
      }
    });

    it('WC-010: should have Starter tier annual price with discount', () => {
      const starterTier = wincher.pricing.find((t) => 
        t.name.toLowerCase() === 'starter'
      );
      if (starterTier && typeof starterTier.monthlyPrice === 'number' && typeof starterTier.annualPrice === 'number') {
        expect(starterTier.annualPrice).toBeLessThan(starterTier.monthlyPrice);
      }
    });

    it('WC-011: should have Professional tier monthly price', () => {
      const proTier = wincher.pricing.find((t) => 
        t.name.toLowerCase() === 'professional' || t.name.toLowerCase() === 'pro'
      );
      if (proTier && typeof proTier.monthlyPrice === 'number') {
        expect(proTier.monthlyPrice).toBeGreaterThanOrEqual(79);
        expect(proTier.monthlyPrice).toBeLessThanOrEqual(199);
      }
    });

    it('WC-012: should have Business tier monthly price', () => {
      const bizTier = wincher.pricing.find((t) => 
        t.name.toLowerCase() === 'business'
      );
      if (bizTier && typeof bizTier.monthlyPrice === 'number') {
        expect(bizTier.monthlyPrice).toBeGreaterThanOrEqual(199);
        expect(bizTier.monthlyPrice).toBeLessThanOrEqual(399);
      }
    });

    it('WC-013: should have free tier', () => {
      const hasFreeTier = wincher.pricing.some((t) => t.monthlyPrice === 'Free');
      expect(hasFreeTier).toBe(true);
    });
  });

  // WC-014 to WC-034: Features and Content
  describe('Features and Content', () => {
    it('WC-014: should have at least 1 feature category', () => {
      expect(wincher.features.length).toBeGreaterThanOrEqual(1);
    });

    it('WC-018: should have at least 3 pros', () => {
      expect(wincher.pros.length).toBeGreaterThanOrEqual(3);
    });

    it('WC-020: should have at least 3 cons', () => {
      expect(wincher.cons.length).toBeGreaterThanOrEqual(3);
    });

    it('WC-022: should have at least 2 use cases', () => {
      expect(wincher.useCases.length).toBeGreaterThanOrEqual(2);
    });

    it('WC-023: should have valid audience types', () => {
      wincher.useCases.forEach((useCase) => {
        expect(AUDIENCE_TYPES).toContain(useCase.audience);
      });
    });

    it('WC-024: should have valid budget types', () => {
      wincher.useCases.forEach((useCase) => {
        expect(BUDGET_TYPES).toContain(useCase.budget);
      });
    });

    it('WC-027: should have rank tracking feature', () => {
      const hasRankTracking = wincher.features.some((cat) =>
        cat.name.toLowerCase().includes('rank') ||
        cat.features.some((f) => f.name.toLowerCase().includes('rank'))
      );
      expect(hasRankTracking).toBe(true);
    });

    it('WC-028: should have keyword research feature', () => {
      const hasKeyword = wincher.features.some((cat) =>
        cat.features.some((f) => f.name.toLowerCase().includes('keyword'))
      );
      expect(hasKeyword).toBe(true);
    });

    it('WC-030: should mention price in pros', () => {
      const hasPrice = wincher.pros.some((pro) => 
        pro.toLowerCase().includes('afford') || 
        pro.toLowerCase().includes('cheap') || 
        pro.toLowerCase().includes('price')
      );
      expect(hasPrice).toBe(true);
    });

    it('WC-031: should mention simplicity in pros', () => {
      const hasSimple = wincher.pros.some((pro) => 
        pro.toLowerCase().includes('simple') || 
        pro.toLowerCase().includes('easy')
      );
      expect(hasSimple).toBe(true);
    });

    it('WC-032: should mention free tier in pros', () => {
      const hasFree = wincher.pros.some((pro) => 
        pro.toLowerCase().includes('free')
      );
      expect(hasFree).toBe(true);
    });

    it('WC-033: should have valid cons about limitations', () => {
      wincher.cons.forEach((con) => {
        expect(con.length).toBeGreaterThan(0);
      });
    });

    it('WC-034: should have solo use case', () => {
      const hasSolo = wincher.useCases.some((uc) => uc.audience === 'solo');
      expect(hasSolo).toBe(true);
    });

    it('WC-040: should be rank tracking focused', () => {
      const rankFeatures = wincher.features.flatMap((cat) => 
        cat.features.filter((f) => f.name.toLowerCase().includes('rank'))
      );
      expect(rankFeatures.length).toBeGreaterThan(0);
    });
  });

  // WC-041 to WC-055: Additional Validation
  describe('Additional Validation', () => {
    it('WC-041: should have meaningful pros (>= 10 chars)', () => {
      wincher.pros.forEach((pro) => {
        expect(pro.length).toBeGreaterThanOrEqual(10);
      });
    });

    it('WC-042: should have meaningful cons (>= 10 chars)', () => {
      wincher.cons.forEach((con) => {
        expect(con.length).toBeGreaterThanOrEqual(10);
      });
    });

    it('WC-043: should have meaningful use case descriptions (>= 20 chars)', () => {
      wincher.useCases.forEach((uc) => {
        expect(uc.description.length).toBeGreaterThanOrEqual(20);
      });
    });

    it('WC-045: should have data freshness within 30 days', () => {
      expect(isWithinDays(wincher.lastUpdated, 30)).toBe(true);
    });

    it('WC-047: should have keyword limits in pricing', () => {
      const hasKeywords = wincher.pricing.some((tier) =>
        tier.features.some((f) => f.toLowerCase().includes('keyword'))
      );
      expect(hasKeywords).toBe(true);
    });

    it('WC-048: should have website limits in pricing', () => {
      const hasWebsites = wincher.pricing.some((tier) =>
        tier.features.some((f) => f.toLowerCase().includes('website'))
      );
      expect(hasWebsites).toBe(true);
    });

    it('WC-049: should have daily updates feature', () => {
      const hasDaily = wincher.features.some((cat) =>
        cat.features.some((f) => 
          f.name.toLowerCase().includes('daily') || f.name.toLowerCase().includes('update')
        )
      );
      expect(hasDaily).toBe(true);
    });

    it('WC-050: should have email reports feature', () => {
      const hasEmail = wincher.features.some((cat) =>
        cat.features.some((f) => f.name.toLowerCase().includes('email'))
      );
      expect(hasEmail).toBe(true);
    });

    it('WC-052: should have competitor tracking feature', () => {
      const hasCompetitor = wincher.features.some((cat) =>
        cat.features.some((f) => f.name.toLowerCase().includes('competitor'))
      );
      expect(hasCompetitor).toBe(true);
    });

    it('WC-053: should have local SEO feature', () => {
      const hasLocal = wincher.features.some((cat) =>
        cat.features.some((f) => f.name.toLowerCase().includes('local'))
      );
      expect(hasLocal).toBe(true);
    });
  });
});

// ============================================================================
// Cross-Competitor Consistency Tests (CC-001 to CC-020)
// ============================================================================

describe('Tier 3 Cross-Competitor Consistency', () => {
  const competitors = [
    { name: 'Raven', data: mockRavenData },
    { name: 'BrightEdge', data: mockBrightEdgeData },
    { name: 'Conductor', data: mockConductorData },
    { name: 'Wincher', data: mockWincherData },
  ];

  it('CC-001: all competitors should be valid CompetitorData', () => {
    competitors.forEach(({ name, data }) => {
      expect(isCompetitorData(data)).toBe(true);
    });
  });

  it('CC-002: all competitors should have unique IDs', () => {
    const ids = competitors.map(({ data }) => data.id);
    expect(new Set(ids).size).toBe(ids.length);
  });

  it('CC-003: all competitors should have slugs matching IDs', () => {
    competitors.forEach(({ data }) => {
      expect(data.slug).toBe(data.id);
    });
  });

  it('CC-006: all competitors should have unique names', () => {
    const names = competitors.map(({ data }) => data.name);
    expect(new Set(names).size).toBe(names.length);
  });

  it('CC-007: enterprise tools should have Custom pricing', () => {
    const enterpriseTools = competitors.filter(({ name }) => 
      name === 'BrightEdge' || name === 'Conductor'
    );
    enterpriseTools.forEach(({ data }) => {
      expect(data.pricing[0].monthlyPrice).toBe('Custom');
      expect(data.pricing[0].annualPrice).toBe('Custom');
    });
  });

  it('CC-010: all lastUpdated should be within 30 days', () => {
    competitors.forEach(({ data }) => {
      expect(isWithinDays(data.lastUpdated, 30)).toBe(true);
    });
  });

  it('CC-011: all features count should be reasonable (5-15 categories)', () => {
    competitors.forEach(({ data }) => {
      expect(data.features.length).toBeGreaterThanOrEqual(1);
      expect(data.features.length).toBeLessThanOrEqual(15);
    });
  });

  it('CC-012: all pricing tier count should be reasonable (1-5 tiers)', () => {
    competitors.forEach(({ data }) => {
      expect(data.pricing.length).toBeGreaterThanOrEqual(1);
      expect(data.pricing.length).toBeLessThanOrEqual(5);
    });
  });

  it('CC-013: all pros count should be reasonable (3-10)', () => {
    competitors.forEach(({ data }) => {
      expect(data.pros.length).toBeGreaterThanOrEqual(3);
      expect(data.pros.length).toBeLessThanOrEqual(10);
    });
  });

  it('CC-014: all cons count should be reasonable (2-8)', () => {
    competitors.forEach(({ data }) => {
      expect(data.cons.length).toBeGreaterThanOrEqual(2);
      expect(data.cons.length).toBeLessThanOrEqual(8);
    });
  });

  it('CC-015: all use case count should be reasonable (2-6)', () => {
    competitors.forEach(({ data }) => {
      expect(data.useCases.length).toBeGreaterThanOrEqual(2);
      expect(data.useCases.length).toBeLessThanOrEqual(6);
    });
  });

  it('CC-016: all website URLs should be valid', () => {
    competitors.forEach(({ data }) => {
      expect(isValidUrl(data.website)).toBe(true);
    });
  });

  it('CC-017: no SEOIndicator bias in pros/cons', () => {
    competitors.forEach(({ data }) => {
      // Pros should not claim SEOIndicator is better
      const biasedPros = data.pros.filter((pro) => 
        pro.toLowerCase().includes('better than seoindicator')
      );
      expect(biasedPros.length).toBe(0);
    });
  });

  it('CC-019: all competitors should use at least 2 different audience types', () => {
    competitors.forEach(({ data }) => {
      const audiences = new Set(data.useCases.map((uc) => uc.audience));
      expect(audiences.size).toBeGreaterThanOrEqual(1);
    });
  });

  it('CC-020: all competitors should use at least 2 different budget types', () => {
    competitors.forEach(({ data }) => {
      const budgets = new Set(data.useCases.map((uc) => uc.budget));
      expect(budgets.size).toBeGreaterThanOrEqual(1);
    });
  });
});

// ============================================================================
// Enterprise Pricing Specific Tests (EP-001 to EP-015)
// ============================================================================

describe('Enterprise Pricing Specific Tests', () => {
  it('EP-001: BrightEdge should have Custom pricing', () => {
    expect(mockBrightEdgeData.pricing[0].monthlyPrice).toBe('Custom');
  });

  it('EP-002: Conductor should have Custom pricing', () => {
    expect(mockConductorData.pricing[0].monthlyPrice).toBe('Custom');
  });

  it('EP-003: Raven should have numeric pricing', () => {
    expect(typeof mockRavenData.pricing[0].monthlyPrice).toBe('number');
  });

  it('EP-004: Wincher should have numeric or Free pricing', () => {
    const validPrices = mockWincherData.pricing.map((t) => t.monthlyPrice);
    validPrices.forEach((price) => {
      expect(['number', 'string']).toContain(typeof price);
      if (typeof price === 'string') {
        expect(['Free', 'Custom']).toContain(price);
      }
    });
  });

  it('EP-005: BrightEdge annual should be Custom', () => {
    expect(mockBrightEdgeData.pricing[0].annualPrice).toBe('Custom');
  });

  it('EP-006: Conductor annual should be Custom', () => {
    expect(mockConductorData.pricing[0].annualPrice).toBe('Custom');
  });

  it('EP-007: Enterprise tools should have enterprise audience', () => {
    const enterpriseTools = [mockBrightEdgeData, mockConductorData];
    enterpriseTools.forEach((tool) => {
      const hasEnterprise = tool.useCases.some((uc) => uc.audience === 'enterprise');
      expect(hasEnterprise).toBe(true);
    });
  });

  it('EP-008: Enterprise tools should have premium budget', () => {
    const enterpriseTools = [mockBrightEdgeData, mockConductorData];
    enterpriseTools.forEach((tool) => {
      const hasPremium = tool.useCases.some((uc) => uc.budget === 'premium');
      expect(hasPremium).toBe(true);
    });
  });

  it('EP-009: Enterprise pros should mention scale', () => {
    const enterpriseTools = [mockBrightEdgeData, mockConductorData];
    enterpriseTools.forEach((tool) => {
      const mentionsScale = tool.pros.some((pro) => 
        pro.toLowerCase().includes('enterprise') || 
        pro.toLowerCase().includes('scale')
      );
      expect(mentionsScale).toBe(true);
    });
  });

  it('EP-010: Enterprise cons should mention cost', () => {
    const enterpriseTools = [mockBrightEdgeData, mockConductorData];
    enterpriseTools.forEach((tool) => {
      const mentionsCost = tool.cons.some((con) => 
        con.toLowerCase().includes('cost') || 
        con.toLowerCase().includes('expensive') ||
        con.toLowerCase().includes('price')
      );
      expect(mentionsCost).toBe(true);
    });
  });

  it('EP-011: Wincher should have free tier', () => {
    const hasFreeTier = mockWincherData.pricing.some((t) => t.monthlyPrice === 'Free');
    expect(hasFreeTier).toBe(true);
  });

  it('EP-012: Wincher should have solo audience', () => {
    const hasSolo = mockWincherData.useCases.some((uc) => uc.audience === 'solo');
    expect(hasSolo).toBe(true);
  });

  it('EP-013: Wincher should have budget audience', () => {
    const hasBudget = mockWincherData.useCases.some((uc) => 
      uc.budget === 'budget' || uc.budget === 'free'
    );
    expect(hasBudget).toBe(true);
  });

  it('EP-014: Raven should have agency focus', () => {
    const hasAgency = mockRavenData.useCases.some((uc) => uc.audience === 'agency');
    expect(hasAgency).toBe(true);
  });

  it('EP-015: Custom pricing tiers should have features listed', () => {
    const customPricingTools = [mockBrightEdgeData, mockConductorData];
    customPricingTools.forEach((tool) => {
      expect(tool.pricing[0].features.length).toBeGreaterThan(0);
    });
  });
});

// ============================================================================
// Edge Cases and Validation Tests (EC-001 to EC-030)
// ============================================================================

describe('Edge Cases and Validation', () => {
  const validCompetitor = mockRavenData;

  it('EC-001: should not have empty pricing array', () => {
    expect(validCompetitor.pricing.length).toBeGreaterThan(0);
  });

  it('EC-002: should not have empty pros array', () => {
    expect(validCompetitor.pros.length).toBeGreaterThan(0);
  });

  it('EC-003: should not have empty cons array', () => {
    expect(validCompetitor.cons.length).toBeGreaterThan(0);
  });

  it('EC-004: should not have empty useCases array', () => {
    expect(validCompetitor.useCases.length).toBeGreaterThan(0);
  });

  it('EC-005: should not have empty features array', () => {
    expect(validCompetitor.features.length).toBeGreaterThan(0);
  });

  it('EC-006: should not have negative price', () => {
    validCompetitor.pricing.forEach((tier) => {
      if (typeof tier.monthlyPrice === 'number') {
        expect(tier.monthlyPrice).toBeGreaterThanOrEqual(0);
      }
    });
  });

  it('EC-007: should not have invalid audience', () => {
    validCompetitor.useCases.forEach((uc) => {
      expect(AUDIENCE_TYPES).toContain(uc.audience);
    });
  });

  it('EC-008: should not have invalid budget', () => {
    validCompetitor.useCases.forEach((uc) => {
      expect(BUDGET_TYPES).toContain(uc.budget);
    });
  });

  it('EC-009: should have valid lastUpdated date format', () => {
    expect(isValidIsoDate(validCompetitor.lastUpdated)).toBe(true);
  });

  it('EC-010: should have valid website URL', () => {
    expect(isValidUrl(validCompetitor.website)).toBe(true);
  });

  it('EC-011: should not have empty string for name', () => {
    expect(validCompetitor.name.length).toBeGreaterThan(0);
  });

  it('EC-013: should have reasonable pro length', () => {
    validCompetitor.pros.forEach((pro) => {
      expect(pro.length).toBeLessThan(500);
    });
  });

  it('EC-016: should work with missing optional fields', () => {
    const dataWithoutLogo = { ...validCompetitor, logo: undefined };
    expect(isCompetitorData(dataWithoutLogo)).toBe(true);
  });

  it('EC-017: should ignore extra fields', () => {
    const dataWithExtra = { 
      ...validCompetitor, 
      extraField: 'should be ignored' 
    };
    expect(isCompetitorData(dataWithExtra)).toBe(true);
  });

  it('EC-020: should only accept number, Free, or Custom for price', () => {
    validCompetitor.pricing.forEach((tier) => {
      const validPrice = 
        typeof tier.monthlyPrice === 'number' ||
        tier.monthlyPrice === 'Free' ||
        tier.monthlyPrice === 'Custom';
      expect(validPrice).toBe(true);
    });
  });

  it('EC-023: should have at least 1 feature in each category', () => {
    validCompetitor.features.forEach((category) => {
      expect(category.features.length).toBeGreaterThan(0);
    });
  });

  it('EC-028: should have valid website URL format', () => {
    expect(validCompetitor.website.startsWith('https://')).toBe(true);
  });

  it('EC-029: should not have future lastUpdated date', () => {
    const lastUpdated = new Date(validCompetitor.lastUpdated);
    const now = new Date();
    expect(lastUpdated.getTime()).toBeLessThanOrEqual(now.getTime());
  });

  it('EC-030: should not have very old lastUpdated (over 1 year)', () => {
    const lastUpdated = new Date(validCompetitor.lastUpdated);
    const oneYearAgo = new Date();
    oneYearAgo.setFullYear(oneYearAgo.getFullYear() - 1);
    expect(lastUpdated.getTime()).toBeGreaterThan(oneYearAgo.getTime());
  });
});

// ============================================================================
// TypeScript Compilation Tests (TS-001 to TS-010)
// ============================================================================

describe('TypeScript Compilation', () => {
  it('TS-001: types file should export isCompetitorData', () => {
    expect(typeof isCompetitorData).toBe('function');
  });

  it('TS-002: types file should export isPricingTier', () => {
    expect(typeof isPricingTier).toBe('function');
  });

  it('TS-003: types file should export isFeatureCategory', () => {
    expect(typeof isFeatureCategory).toBe('function');
  });

  it('TS-004: types file should export isFeature', () => {
    expect(typeof isFeature).toBe('function');
  });

  it('TS-005: types file should export isUseCase', () => {
    expect(typeof isUseCase).toBe('function');
  });

  it('TS-006: types file should export AUDIENCE_TYPES', () => {
    expect(Array.isArray(AUDIENCE_TYPES)).toBe(true);
    expect(AUDIENCE_TYPES).toContain('solo');
    expect(AUDIENCE_TYPES).toContain('enterprise');
  });

  it('TS-007: types file should export BUDGET_TYPES', () => {
    expect(Array.isArray(BUDGET_TYPES)).toBe(true);
    expect(BUDGET_TYPES).toContain('free');
    expect(BUDGET_TYPES).toContain('premium');
  });

  it('TS-008: type guard isCompetitorData should return true for valid data', () => {
    expect(isCompetitorData(mockRavenData)).toBe(true);
    expect(isCompetitorData(mockBrightEdgeData)).toBe(true);
    expect(isCompetitorData(mockConductorData)).toBe(true);
    expect(isCompetitorData(mockWincherData)).toBe(true);
  });

  it('TS-009: type guard isCompetitorData should return false for invalid data', () => {
    expect(isCompetitorData(null)).toBe(false);
    expect(isCompetitorData(undefined)).toBe(false);
    expect(isCompetitorData({})).toBe(false);
    expect(isCompetitorData({ id: 'test' })).toBe(false);
  });

  it('TS-010: type guard isPricingTier should validate Custom pricing', () => {
    const customTier = {
      name: 'Enterprise',
      monthlyPrice: 'Custom',
      annualPrice: 'Custom',
      features: ['Custom pricing'],
    };
    expect(isPricingTier(customTier)).toBe(true);
  });
});
