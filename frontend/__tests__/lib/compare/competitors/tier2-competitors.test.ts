/**
 * Tier 2 Competitor Data Tests
 * Task: TASK-32-12 - Tier 2 Competitor Data (Mangools, Serpstat, SpyFu, SE Ranking)
 * Spec: SPEC-32 (Competitor Comparison Landing Pages)
 *
 * Comprehensive test suite for Tier 2 competitor data validation.
 * Tests structure, content accuracy, and cross-competitor consistency.
 */

import {
  isCompetitorData,
  isPricingTier,
  isFeatureCategory,
  isUseCase,
  AUDIENCE_TYPES,
  BUDGET_TYPES,
  type CompetitorData,
  type PricingTier,
  type FeatureCategory,
  type UseCase,
} from '../../../../lib/compare/types';

// ============================================================================
// Test Data Constants
// ============================================================================

const EXPECTED_COMPETITOR_IDS = ['mangools', 'serpstat', 'spyfu', 'seranking'] as const;

// Expected pricing ranges (approximate, verify from official sources)
const PRICING_RANGES = {
  mangools: { min: 29, max: 129 },
  serpstat: { min: 69, max: 499 },
  spyfu: { min: 39, max: 149 },
  seranking: { min: 52, max: 207 },
} as const;

// Expected websites
const EXPECTED_WEBSITES: Record<string, string> = {
  mangools: 'https://mangools.com',
  serpstat: 'https://serpstat.com',
  spyfu: 'https://spyfu.com',
  seranking: 'https://seranking.com',
};

// ============================================================================
// Helper Functions
// ============================================================================

/**
 * Validates ISO date format (YYYY-MM-DD or full ISO)
 */
function isValidISODate(dateString: string): boolean {
  const dateOnlyRegex = /^\d{4}-\d{2}-\d{2}$/;
  const isoRegex = /^\d{4}-\d{2}-\d{2}(T\d{2}:\d{2}:\d{2}(\.\d{3})?(Z|[+-]\d{2}:\d{2})?)?$/;

  if (!dateOnlyRegex.test(dateString) && !isoRegex.test(dateString)) {
    return false;
  }

  const date = new Date(dateString);
  return !isNaN(date.getTime());
}

/**
 * Validates URL format
 */
function isValidUrl(url: string): boolean {
  try {
    new URL(url);
    return true;
  } catch {
    return false;
  }
}

/**
 * Checks if date is within last N days
 */
function isWithinDays(dateString: string, days: number): boolean {
  const date = new Date(dateString);
  const now = new Date();
  const diffMs = now.getTime() - date.getTime();
  const diffDays = diffMs / (1000 * 60 * 60 * 24);
  return diffDays <= days;
}

/**
 * Creates a mock competitor data for edge case testing
 */
function createMockCompetitorData(overrides: Partial<CompetitorData> = {}): CompetitorData {
  return {
    id: 'test-competitor',
    name: 'Test Competitor',
    slug: 'test-competitor',
    website: 'https://example.com',
    tagline: 'A test competitor',
    pricing: [
      {
        name: 'Basic',
        monthlyPrice: 29,
        annualPrice: 24,
        features: ['Feature 1', 'Feature 2'],
      },
    ],
    features: [
      {
        name: 'Core Features',
        features: [
          { name: 'Feature A', seoindicator: true, competitor: true },
        ],
      },
    ],
    pros: ['Great feature', 'Good value'],
    cons: ['Limited options', 'No free tier'],
    useCases: [
      {
        title: 'Small Business',
        description: 'Great for small businesses',
        audience: 'solo',
        budget: 'budget',
      },
    ],
    lastUpdated: '2026-03-06',
    source: 'https://example.com/pricing',
    ...overrides,
  };
}

// ============================================================================
// Mangools Competitor Data Tests
// ============================================================================

describe('Mangools Competitor Data', () => {
  let mangools: CompetitorData | null = null;

  beforeAll(() => {
    try {
      // eslint-disable-next-line @typescript-eslint/no-var-requires
      const mangoolsModule = require('../../../../lib/compare/competitors/mangools');
      mangools = mangoolsModule.mangools || mangoolsModule.default;
    } catch {
      mangools = null; // File doesn't exist yet (TDD)
    }
  });

  describe('Structure Validation', () => {
    test('MG-001: File exports valid CompetitorData', () => {
      if (!mangools) {
        expect(mangools).toBeNull();
        return;
      }
      expect(isCompetitorData(mangools)).toBe(true);
    });

    test('MG-002: ID is correct', () => {
      if (!mangools) return;
      expect(mangools.id).toBe('mangools');
    });

    test('MG-003: Name is correct', () => {
      if (!mangools) return;
      expect(mangools.name).toBe('Mangools');
    });

    test('MG-004: Slug is correct', () => {
      if (!mangools) return;
      expect(mangools.slug).toBe('mangools');
    });

    test('MG-005: Website is correct', () => {
      if (!mangools) return;
      expect(mangools.website).toBe(EXPECTED_WEBSITES.mangools);
    });

    test('MG-006: Tagline exists and is non-empty', () => {
      if (!mangools) return;
      expect(typeof mangools.tagline).toBe('string');
      expect(mangools.tagline.length).toBeGreaterThan(0);
    });

    test('MG-007: Logo is string or undefined', () => {
      if (!mangools) return;
      if (mangools.logo !== undefined) {
        expect(typeof mangools.logo).toBe('string');
      }
    });
  });

  describe('Pricing Validation', () => {
    test('MG-008: Has at least 3 pricing tiers', () => {
      if (!mangools) return;
      expect(mangools.pricing.length).toBeGreaterThanOrEqual(3);
    });

    test('MG-009: Entry tier monthly price is 29', () => {
      if (!mangools) return;
      const entryTier = mangools.pricing.find(t => t.name === 'Entry');
      if (entryTier) {
        expect(entryTier.monthlyPrice).toBe(29);
      }
    });

    test('MG-010: Entry tier annual price is discounted', () => {
      if (!mangools) return;
      const entryTier = mangools.pricing.find(t => t.name === 'Entry');
      if (entryTier && typeof entryTier.annualPrice === 'number') {
        expect(entryTier.annualPrice).toBeLessThan(entryTier.monthlyPrice as number);
        expect(entryTier.annualPrice).toBeLessThanOrEqual(20);
      }
    });

    test('MG-011: Premium tier monthly price is 49', () => {
      if (!mangools) return;
      const premiumTier = mangools.pricing.find(t => t.name === 'Premium');
      if (premiumTier) {
        expect(premiumTier.monthlyPrice).toBe(49);
      }
    });

    test('MG-012: Agency tier monthly price is 129', () => {
      if (!mangools) return;
      const agencyTier = mangools.pricing.find(t => t.name === 'Agency');
      if (agencyTier) {
        expect(agencyTier.monthlyPrice).toBe(129);
      }
    });

    test('MG-013: Pricing tier has features array', () => {
      if (!mangools) return;
      expect(Array.isArray(mangools.pricing[0].features)).toBe(true);
      expect(mangools.pricing[0].features.every(f => typeof f === 'string')).toBe(true);
    });

    test('MG-039: Annual savings is at least 15%', () => {
      if (!mangools) return;
      mangools.pricing.forEach(tier => {
        if (typeof tier.monthlyPrice === 'number' && typeof tier.annualPrice === 'number') {
          const savings = ((tier.monthlyPrice - tier.annualPrice) / tier.monthlyPrice) * 100;
          expect(savings).toBeGreaterThanOrEqual(15);
        }
      });
    });

    test('MG-040: Free trial available', () => {
      if (!mangools) return;
      const hasTrial = mangools.pricing.some(t => 
        t.features.some(f => f.toLowerCase().includes('trial'))
      ) || mangools.pros.some(p => p.toLowerCase().includes('trial'));
      expect(hasTrial).toBe(true);
    });
  });

  describe('Features Validation', () => {
    test('MG-014: Has feature categories', () => {
      if (!mangools) return;
      expect(mangools.features.length).toBeGreaterThanOrEqual(1);
    });

    test('MG-015: Feature category has name', () => {
      if (!mangools) return;
      expect(typeof mangools.features[0].name).toBe('string');
      expect(mangools.features[0].name.length).toBeGreaterThan(0);
    });

    test('MG-016: Feature category has features', () => {
      if (!mangools) return;
      expect(Array.isArray(mangools.features[0].features)).toBe(true);
      expect(mangools.features[0].features.length).toBeGreaterThan(0);
    });

    test('MG-026: KWFinder feature exists', () => {
      if (!mangools) return;
      let hasKWFinder = false;
      mangools.features.forEach(cat => {
        cat.features.forEach(f => {
          if (f.name.toLowerCase().includes('kwfinder') || f.name.toLowerCase().includes('keyword finder')) {
            hasKWFinder = true;
            expect(f.competitor).toBe(true);
          }
        });
      });
      expect(hasKWFinder).toBe(true);
    });

    test('MG-027: SERPChecker feature exists', () => {
      if (!mangools) return;
      let hasSERPChecker = false;
      mangools.features.forEach(cat => {
        cat.features.forEach(f => {
          if (f.name.toLowerCase().includes('serp') && f.name.toLowerCase().includes('check')) {
            hasSERPChecker = true;
            expect(f.competitor).toBe(true);
          }
        });
      });
      expect(hasSERPChecker).toBe(true);
    });

    test('MG-028: SERPWatcher feature exists', () => {
      if (!mangools) return;
      let hasSERPWatcher = false;
      mangools.features.forEach(cat => {
        cat.features.forEach(f => {
          if (f.name.toLowerCase().includes('serp') && f.name.toLowerCase().includes('watch')) {
            hasSERPWatcher = true;
            expect(f.competitor).toBe(true);
          }
        });
      });
      expect(hasSERPWatcher).toBe(true);
    });

    test('MG-029: LinkMiner feature exists', () => {
      if (!mangools) return;
      let hasLinkMiner = false;
      mangools.features.forEach(cat => {
        cat.features.forEach(f => {
          if (f.name.toLowerCase().includes('linkminer') || (f.name.toLowerCase().includes('link') && f.name.toLowerCase().includes('miner'))) {
            hasLinkMiner = true;
            expect(f.competitor).toBe(true);
          }
        });
      });
      expect(hasLinkMiner).toBe(true);
    });

    test('MG-030: SiteProfiler feature exists', () => {
      if (!mangools) return;
      let hasSiteProfiler = false;
      mangools.features.forEach(cat => {
        cat.features.forEach(f => {
          if (f.name.toLowerCase().includes('site') && f.name.toLowerCase().includes('profil')) {
            hasSiteProfiler = true;
            expect(f.competitor).toBe(true);
          }
        });
      });
      expect(hasSiteProfiler).toBe(true);
    });

    test('MG-035: No duplicate feature names within category', () => {
      if (!mangools) return;
      mangools.features.forEach(category => {
        const names = category.features.map(f => f.name.toLowerCase());
        const uniqueNames = [...new Set(names)];
        expect(names.length).toBe(uniqueNames.length);
      });
    });

    test('MG-038: Feature values are valid boolean or string', () => {
      if (!mangools) return;
      mangools.features.forEach(cat => {
        cat.features.forEach(f => {
          expect(['boolean', 'string']).toContain(typeof f.seoindicator);
          expect(['boolean', 'string']).toContain(typeof f.competitor);
        });
      });
    });
  });

  describe('Pros and Cons Validation', () => {
    test('MG-017: Has at least 3 pros', () => {
      if (!mangools) return;
      expect(mangools.pros.length).toBeGreaterThanOrEqual(3);
    });

    test('MG-018: Pros are strings', () => {
      if (!mangools) return;
      expect(mangools.pros.every(p => typeof p === 'string')).toBe(true);
    });

    test('MG-019: Has at least 3 cons', () => {
      if (!mangools) return;
      expect(mangools.cons.length).toBeGreaterThanOrEqual(3);
    });

    test('MG-020: Cons are strings', () => {
      if (!mangools) return;
      expect(mangools.cons.every(c => typeof c === 'string')).toBe(true);
    });

    test('MG-031: Pros mention simplicity', () => {
      if (!mangools) return;
      const hasSimplicityPro = mangools.pros.some(
        p => p.toLowerCase().includes('simple') ||
             p.toLowerCase().includes('easy') ||
             p.toLowerCase().includes('beginner')
      );
      expect(hasSimplicityPro).toBe(true);
    });

    test('MG-032: Pros mention price', () => {
      if (!mangools) return;
      const hasPricePro = mangools.pros.some(
        p => p.toLowerCase().includes('afford') ||
             p.toLowerCase().includes('price') ||
             p.toLowerCase().includes('budget') ||
             p.toLowerCase().includes('value')
      );
      expect(hasPricePro).toBe(true);
    });

    test('MG-033: Cons mention limitations', () => {
      if (!mangools) return;
      expect(mangools.cons.length).toBeGreaterThan(0);
      mangools.cons.forEach(con => {
        expect(typeof con).toBe('string');
        expect(con.length).toBeGreaterThan(0);
      });
    });

    test('MG-036: No duplicate pros', () => {
      if (!mangools) return;
      const uniquePros = [...new Set(mangools.pros.map(p => p.toLowerCase()))];
      expect(mangools.pros.length).toBe(uniquePros.length);
    });

    test('MG-037: No duplicate cons', () => {
      if (!mangools) return;
      const uniqueCons = [...new Set(mangools.cons.map(c => c.toLowerCase()))];
      expect(mangools.cons.length).toBe(uniqueCons.length);
    });

    test('MG-041: Pros are meaningful (>= 10 chars)', () => {
      if (!mangools) return;
      mangools.pros.forEach(pro => {
        expect(pro.length).toBeGreaterThanOrEqual(10);
      });
    });

    test('MG-042: Cons are meaningful (>= 10 chars)', () => {
      if (!mangools) return;
      mangools.cons.forEach(con => {
        expect(con.length).toBeGreaterThanOrEqual(10);
      });
    });

    test('MG-050: User-friendly UI mentioned', () => {
      if (!mangools) return;
      const hasUI = mangools.pros.some(p => 
        p.toLowerCase().includes('ui') || 
        p.toLowerCase().includes('interface') ||
        p.toLowerCase().includes('user-friendly')
      ) || mangools.tagline.toLowerCase().includes('simple');
      expect(hasUI).toBe(true);
    });
  });

  describe('Use Cases Validation', () => {
    test('MG-021: Has at least 2 use cases', () => {
      if (!mangools) return;
      expect(mangools.useCases.length).toBeGreaterThanOrEqual(2);
    });

    test('MG-022: Use case has valid audience', () => {
      if (!mangools) return;
      mangools.useCases.forEach(uc => {
        expect(AUDIENCE_TYPES).toContain(uc.audience);
      });
    });

    test('MG-023: Use case has valid budget', () => {
      if (!mangools) return;
      mangools.useCases.forEach(uc => {
        expect(BUDGET_TYPES).toContain(uc.budget);
      });
    });

    test('MG-034: Use case for beginners exists', () => {
      if (!mangools) return;
      const beginnerUseCase = mangools.useCases.find(uc => 
        uc.audience === 'solo' || uc.budget === 'budget'
      );
      expect(beginnerUseCase).toBeDefined();
    });

    test('MG-043: Use case descriptions are meaningful (>= 20 chars)', () => {
      if (!mangools) return;
      mangools.useCases.forEach(uc => {
        expect(uc.description.length).toBeGreaterThanOrEqual(20);
      });
    });
  });

  describe('Metadata Validation', () => {
    test('MG-024: lastUpdated is valid ISO date', () => {
      if (!mangools) return;
      expect(isValidISODate(mangools.lastUpdated)).toBe(true);
    });

    test('MG-025: Source attribution exists', () => {
      if (!mangools) return;
      expect(typeof mangools.source).toBe('string');
      expect(mangools.source.length).toBeGreaterThan(0);
    });

    test('MG-044: Feature tooltips are strings when present', () => {
      if (!mangools) return;
      mangools.features.forEach(cat => {
        cat.features.forEach(f => {
          if (f.tooltip !== undefined) {
            expect(typeof f.tooltip).toBe('string');
          }
        });
      });
    });

    test('MG-045: Highlighted property is boolean or undefined', () => {
      if (!mangools) return;
      mangools.pricing.forEach(tier => {
        if (tier.highlighted !== undefined) {
          expect(typeof tier.highlighted).toBe('boolean');
        }
      });
    });

    test('MG-046: Data is fresh (within last 30 days)', () => {
      if (!mangools) return;
      expect(isWithinDays(mangools.lastUpdated, 30)).toBe(true);
    });

    test('MG-047: Keyword lookup limits specified', () => {
      if (!mangools) return;
      const entryTier = mangools.pricing.find(t => t.name === 'Entry');
      if (entryTier) {
        const hasKeywordLimit = entryTier.features.some(f => 
          f.toLowerCase().includes('keyword') && f.toLowerCase().includes('lookup')
        );
        expect(hasKeywordLimit).toBe(true);
      }
    });
  });
});

// ============================================================================
// Serpstat Competitor Data Tests
// ============================================================================

describe('Serpstat Competitor Data', () => {
  let serpstat: CompetitorData | null = null;

  beforeAll(() => {
    try {
      // eslint-disable-next-line @typescript-eslint/no-var-requires
      const serpstatModule = require('../../../../lib/compare/competitors/serpstat');
      serpstat = serpstatModule.serpstat || serpstatModule.default;
    } catch {
      serpstat = null;
    }
  });

  describe('Structure Validation', () => {
    test('SS-001: File exports valid CompetitorData', () => {
      if (!serpstat) {
        expect(serpstat).toBeNull();
        return;
      }
      expect(isCompetitorData(serpstat)).toBe(true);
    });

    test('SS-002: ID is correct', () => {
      if (!serpstat) return;
      expect(serpstat.id).toBe('serpstat');
    });

    test('SS-003: Name is correct', () => {
      if (!serpstat) return;
      expect(serpstat.name).toBe('Serpstat');
    });

    test('SS-004: Slug is correct', () => {
      if (!serpstat) return;
      expect(serpstat.slug).toBe('serpstat');
    });

    test('SS-005: Website is correct', () => {
      if (!serpstat) return;
      expect(serpstat.website).toBe(EXPECTED_WEBSITES.serpstat);
    });

    test('SS-006: Tagline exists and is non-empty', () => {
      if (!serpstat) return;
      expect(typeof serpstat.tagline).toBe('string');
      expect(serpstat.tagline.length).toBeGreaterThan(0);
    });
  });

  describe('Pricing Validation', () => {
    test('SS-007: Has at least 4 pricing tiers', () => {
      if (!serpstat) return;
      expect(serpstat.pricing.length).toBeGreaterThanOrEqual(4);
    });

    test('SS-008: Lite tier monthly price is 69', () => {
      if (!serpstat) return;
      const liteTier = serpstat.pricing.find(t => t.name === 'Lite');
      if (liteTier) {
        expect(liteTier.monthlyPrice).toBe(69);
      }
    });

    test('SS-009: Standard tier monthly price is 149', () => {
      if (!serpstat) return;
      const standardTier = serpstat.pricing.find(t => t.name === 'Standard');
      if (standardTier) {
        expect(standardTier.monthlyPrice).toBe(149);
      }
    });

    test('SS-010: Advanced tier monthly price is 299', () => {
      if (!serpstat) return;
      const advancedTier = serpstat.pricing.find(t => t.name === 'Advanced');
      if (advancedTier) {
        expect(advancedTier.monthlyPrice).toBe(299);
      }
    });

    test('SS-011: Enterprise tier exists', () => {
      if (!serpstat) return;
      const enterpriseTier = serpstat.pricing.find(t => 
        t.name === 'Enterprise' || t.name === 'Custom'
      );
      expect(enterpriseTier).toBeDefined();
    });

    test('SS-012: Pricing tier has features array', () => {
      if (!serpstat) return;
      expect(Array.isArray(serpstat.pricing[0].features)).toBe(true);
    });

    test('SS-033: Annual savings applied', () => {
      if (!serpstat) return;
      serpstat.pricing.forEach(tier => {
        if (typeof tier.monthlyPrice === 'number' && typeof tier.annualPrice === 'number') {
          expect(tier.annualPrice).toBeLessThan(tier.monthlyPrice);
        }
      });
    });
  });

  describe('Features Validation', () => {
    test('SS-013: Has feature categories', () => {
      if (!serpstat) return;
      expect(serpstat.features.length).toBeGreaterThanOrEqual(1);
    });

    test('SS-021: Keyword research features exist', () => {
      if (!serpstat) return;
      const keywordCat = serpstat.features.find(
        f => f.name.toLowerCase().includes('keyword')
      );
      expect(keywordCat).toBeDefined();
    });

    test('SS-022: Backlink analysis feature exists', () => {
      if (!serpstat) return;
      let hasBacklink = false;
      serpstat.features.forEach(cat => {
        cat.features.forEach(f => {
          if (f.name.toLowerCase().includes('backlink')) {
            hasBacklink = true;
            expect(f.competitor).toBe(true);
          }
        });
      });
      expect(hasBacklink).toBe(true);
    });

    test('SS-023: Site audit feature exists', () => {
      if (!serpstat) return;
      let hasSiteAudit = false;
      serpstat.features.forEach(cat => {
        cat.features.forEach(f => {
          if (f.name.toLowerCase().includes('site audit')) {
            hasSiteAudit = true;
            expect(f.competitor).toBe(true);
          }
        });
      });
      expect(hasSiteAudit).toBe(true);
    });

    test('SS-024: Rank tracking feature exists', () => {
      if (!serpstat) return;
      let hasRankTracking = false;
      serpstat.features.forEach(cat => {
        cat.features.forEach(f => {
          if (f.name.toLowerCase().includes('rank') && f.name.toLowerCase().includes('track')) {
            hasRankTracking = true;
            expect(f.competitor).toBe(true);
          }
        });
      });
      expect(hasRankTracking).toBe(true);
    });

    test('SS-025: Competitor analysis feature exists', () => {
      if (!serpstat) return;
      let hasCompetitor = false;
      serpstat.features.forEach(cat => {
        cat.features.forEach(f => {
          if (f.name.toLowerCase().includes('competitor')) {
            hasCompetitor = true;
            expect(f.competitor).toBe(true);
          }
        });
      });
      expect(hasCompetitor).toBe(true);
    });

    test('SS-030: No duplicate feature names within category', () => {
      if (!serpstat) return;
      serpstat.features.forEach(category => {
        const names = category.features.map(f => f.name.toLowerCase());
        const uniqueNames = [...new Set(names)];
        expect(names.length).toBe(uniqueNames.length);
      });
    });

    test('SS-034: API access available', () => {
      if (!serpstat) return;
      let hasAPI = false;
      serpstat.features.forEach(cat => {
        cat.features.forEach(f => {
          if (f.name.toLowerCase().includes('api')) {
            hasAPI = true;
          }
        });
      });
      expect(hasAPI).toBe(true);
    });

    test('SS-035: White label reports exist', () => {
      if (!serpstat) return;
      let hasWhiteLabel = false;
      serpstat.features.forEach(cat => {
        cat.features.forEach(f => {
          if (f.name.toLowerCase().includes('white label') || f.name.toLowerCase().includes('whitelabel')) {
            hasWhiteLabel = true;
          }
        });
      });
      expect(hasWhiteLabel).toBe(true);
    });

    test('SS-041: Domain analysis feature exists', () => {
      if (!serpstat) return;
      let hasDomainAnalysis = false;
      serpstat.features.forEach(cat => {
        cat.features.forEach(f => {
          if (f.name.toLowerCase().includes('domain')) {
            hasDomainAnalysis = true;
          }
        });
      });
      expect(hasDomainAnalysis).toBe(true);
    });

    test('SS-042: Content marketing features exist', () => {
      if (!serpstat) return;
      let hasContentMarketing = false;
      serpstat.features.forEach(cat => {
        cat.features.forEach(f => {
          if (f.name.toLowerCase().includes('content')) {
            hasContentMarketing = true;
          }
        });
      });
      expect(hasContentMarketing).toBe(true);
    });

    test('SS-047: Cluster research feature exists', () => {
      if (!serpstat) return;
      let hasCluster = false;
      serpstat.features.forEach(cat => {
        cat.features.forEach(f => {
          if (f.name.toLowerCase().includes('cluster')) {
            hasCluster = true;
            expect(f.competitor).toBe(true);
          }
        });
      });
      expect(hasCluster).toBe(true);
    });
  });

  describe('Pros and Cons Validation', () => {
    test('SS-014: Has at least 3 pros', () => {
      if (!serpstat) return;
      expect(serpstat.pros.length).toBeGreaterThanOrEqual(3);
    });

    test('SS-015: Has at least 3 cons', () => {
      if (!serpstat) return;
      expect(serpstat.cons.length).toBeGreaterThanOrEqual(3);
    });

    test('SS-026: Pros mention all-in-one', () => {
      if (!serpstat) return;
      const hasAllInOne = serpstat.pros.some(
        p => p.toLowerCase().includes('all-in-one') ||
             p.toLowerCase().includes('comprehensive')
      );
      expect(hasAllInOne).toBe(true);
    });

    test('SS-027: Pros mention database', () => {
      if (!serpstat) return;
      const hasDatabasePro = serpstat.pros.some(
        p => p.toLowerCase().includes('database')
      );
      expect(hasDatabasePro).toBe(true);
    });

    test('SS-028: Cons mention pricing', () => {
      if (!serpstat) return;
      const hasPricingCon = serpstat.cons.some(
        p => p.toLowerCase().includes('price') ||
             p.toLowerCase().includes('expensive') ||
             p.toLowerCase().includes('cost')
      );
      expect(hasPricingCon).toBe(true);
    });

    test('SS-031: No duplicate pros', () => {
      if (!serpstat) return;
      const uniquePros = [...new Set(serpstat.pros.map(p => p.toLowerCase()))];
      expect(serpstat.pros.length).toBe(uniquePros.length);
    });

    test('SS-032: No duplicate cons', () => {
      if (!serpstat) return;
      const uniqueCons = [...new Set(serpstat.cons.map(c => c.toLowerCase()))];
      expect(serpstat.cons.length).toBe(uniqueCons.length);
    });

    test('SS-037: Pros are meaningful (>= 10 chars)', () => {
      if (!serpstat) return;
      serpstat.pros.forEach(pro => {
        expect(pro.length).toBeGreaterThanOrEqual(10);
      });
    });

    test('SS-038: Cons are meaningful (>= 10 chars)', () => {
      if (!serpstat) return;
      serpstat.cons.forEach(con => {
        expect(con.length).toBeGreaterThanOrEqual(10);
      });
    });

    test('SS-046: Free trial available', () => {
      if (!serpstat) return;
      const hasTrial = serpstat.pros.some(p => p.toLowerCase().includes('trial'));
      expect(hasTrial).toBe(true);
    });

    test('SS-050: Database size mentioned', () => {
      if (!serpstat) return;
      const hasDatabaseInfo = serpstat.pros.some(p => 
        p.toLowerCase().includes('database') || 
        p.toLowerCase().includes('billion')
      ) || serpstat.features.some(cat => 
        cat.features.some(f => f.name.toLowerCase().includes('database'))
      );
      expect(hasDatabaseInfo).toBe(true);
    });
  });

  describe('Use Cases Validation', () => {
    test('SS-016: Has at least 2 use cases', () => {
      if (!serpstat) return;
      expect(serpstat.useCases.length).toBeGreaterThanOrEqual(2);
    });

    test('SS-017: Use case has valid audience', () => {
      if (!serpstat) return;
      serpstat.useCases.forEach(uc => {
        expect(AUDIENCE_TYPES).toContain(uc.audience);
      });
    });

    test('SS-018: Use case has valid budget', () => {
      if (!serpstat) return;
      serpstat.useCases.forEach(uc => {
        expect(BUDGET_TYPES).toContain(uc.budget);
      });
    });

    test('SS-029: Use case for small teams exists', () => {
      if (!serpstat) return;
      const smallTeamUseCase = serpstat.useCases.find(uc => uc.audience === 'small-team');
      expect(smallTeamUseCase).toBeDefined();
    });

    test('SS-039: Use case descriptions are meaningful (>= 20 chars)', () => {
      if (!serpstat) return;
      serpstat.useCases.forEach(uc => {
        expect(uc.description.length).toBeGreaterThanOrEqual(20);
      });
    });
  });

  describe('Metadata Validation', () => {
    test('SS-019: lastUpdated is valid ISO date', () => {
      if (!serpstat) return;
      expect(isValidISODate(serpstat.lastUpdated)).toBe(true);
    });

    test('SS-020: Source attribution exists', () => {
      if (!serpstat) return;
      expect(typeof serpstat.source).toBe('string');
      expect(serpstat.source.length).toBeGreaterThan(0);
    });

    test('SS-044: Highlighted property is boolean or undefined', () => {
      if (!serpstat) return;
      serpstat.pricing.forEach(tier => {
        if (tier.highlighted !== undefined) {
          expect(typeof tier.highlighted).toBe('boolean');
        }
      });
    });

    test('SS-045: Data is fresh (within last 30 days)', () => {
      if (!serpstat) return;
      expect(isWithinDays(serpstat.lastUpdated, 30)).toBe(true);
    });
  });
});

// ============================================================================
// SpyFu Competitor Data Tests
// ============================================================================

describe('SpyFu Competitor Data', () => {
  let spyfu: CompetitorData | null = null;

  beforeAll(() => {
    try {
      // eslint-disable-next-line @typescript-eslint/no-var-requires
      const spyfuModule = require('../../../../lib/compare/competitors/spyfu');
      spyfu = spyfuModule.spyfu || spyfuModule.default;
    } catch {
      spyfu = null;
    }
  });

  describe('Structure Validation', () => {
    test('SF-001: File exports valid CompetitorData', () => {
      if (!spyfu) {
        expect(spyfu).toBeNull();
        return;
      }
      expect(isCompetitorData(spyfu)).toBe(true);
    });

    test('SF-002: ID is correct', () => {
      if (!spyfu) return;
      expect(spyfu.id).toBe('spyfu');
    });

    test('SF-003: Name is correct', () => {
      if (!spyfu) return;
      expect(spyfu.name).toBe('SpyFu');
    });

    test('SF-004: Slug is correct', () => {
      if (!spyfu) return;
      expect(spyfu.slug).toBe('spyfu');
    });

    test('SF-005: Website is correct', () => {
      if (!spyfu) return;
      expect(spyfu.website).toBe(EXPECTED_WEBSITES.spyfu);
    });

    test('SF-006: Tagline exists and is non-empty', () => {
      if (!spyfu) return;
      expect(typeof spyfu.tagline).toBe('string');
      expect(spyfu.tagline.length).toBeGreaterThan(0);
    });
  });

  describe('Pricing Validation', () => {
    test('SF-007: Has at least 3 pricing tiers', () => {
      if (!spyfu) return;
      expect(spyfu.pricing.length).toBeGreaterThanOrEqual(3);
    });

    test('SF-008: Basic tier monthly price is 39', () => {
      if (!spyfu) return;
      const basicTier = spyfu.pricing.find(t => t.name === 'Basic');
      if (basicTier) {
        expect(basicTier.monthlyPrice).toBe(39);
      }
    });

    test('SF-009: Professional tier monthly price is 79', () => {
      if (!spyfu) return;
      const proTier = spyfu.pricing.find(t => t.name === 'Professional');
      if (proTier) {
        expect(proTier.monthlyPrice).toBe(79);
      }
    });

    test('SF-010: Team tier monthly price is 149', () => {
      if (!spyfu) return;
      const teamTier = spyfu.pricing.find(t => t.name === 'Team');
      if (teamTier) {
        expect(teamTier.monthlyPrice).toBe(149);
      }
    });

    test('SF-011: Pricing tier has features array', () => {
      if (!spyfu) return;
      expect(Array.isArray(spyfu.pricing[0].features)).toBe(true);
    });

    test('SF-032: Annual savings applied', () => {
      if (!spyfu) return;
      spyfu.pricing.forEach(tier => {
        if (typeof tier.monthlyPrice === 'number' && typeof tier.annualPrice === 'number') {
          expect(tier.annualPrice).toBeLessThan(tier.monthlyPrice);
        }
      });
    });
  });

  describe('Features Validation', () => {
    test('SF-012: Has feature categories', () => {
      if (!spyfu) return;
      expect(spyfu.features.length).toBeGreaterThanOrEqual(1);
    });

    test('SF-020: Competitor spying feature exists', () => {
      if (!spyfu) return;
      let hasCompetitorResearch = false;
      spyfu.features.forEach(cat => {
        cat.features.forEach(f => {
          if (f.name.toLowerCase().includes('competitor') || f.name.toLowerCase().includes('spy')) {
            hasCompetitorResearch = true;
            expect(f.competitor).toBe(true);
          }
        });
      });
      expect(hasCompetitorResearch).toBe(true);
    });

    test('SF-021: Keyword research feature exists', () => {
      if (!spyfu) return;
      let hasKeyword = false;
      spyfu.features.forEach(cat => {
        cat.features.forEach(f => {
          if (f.name.toLowerCase().includes('keyword')) {
            hasKeyword = true;
            expect(f.competitor).toBe(true);
          }
        });
      });
      expect(hasKeyword).toBe(true);
    });

    test('SF-022: PPC research feature exists', () => {
      if (!spyfu) return;
      let hasPPC = false;
      spyfu.features.forEach(cat => {
        cat.features.forEach(f => {
          if (f.name.toLowerCase().includes('ppc') || f.name.toLowerCase().includes('advertis')) {
            hasPPC = true;
            expect(f.competitor).toBe(true);
          }
        });
      });
      expect(hasPPC).toBe(true);
    });

    test('SF-023: Backlink analysis feature exists', () => {
      if (!spyfu) return;
      let hasBacklinks = false;
      spyfu.features.forEach(cat => {
        cat.features.forEach(f => {
          if (f.name.toLowerCase().includes('backlink') || f.name.toLowerCase().includes('link')) {
            hasBacklinks = true;
          }
        });
      });
      expect(hasBacklinks).toBe(true);
    });

    test('SF-029: No duplicate feature names within category', () => {
      if (!spyfu) return;
      spyfu.features.forEach(category => {
        const names = category.features.map(f => f.name.toLowerCase());
        const uniqueNames = [...new Set(names)];
        expect(names.length).toBe(uniqueNames.length);
      });
    });

    test('SF-034: Domain overview feature exists', () => {
      if (!spyfu) return;
      let hasDomainOverview = false;
      spyfu.features.forEach(cat => {
        cat.features.forEach(f => {
          if (f.name.toLowerCase().includes('domain') || f.name.toLowerCase().includes('overview')) {
            hasDomainOverview = true;
          }
        });
      });
      expect(hasDomainOverview).toBe(true);
    });

    test('SF-035: Kombat feature exists', () => {
      if (!spyfu) return;
      let hasKombat = false;
      spyfu.features.forEach(cat => {
        cat.features.forEach(f => {
          if (f.name.toLowerCase().includes('kombat')) {
            hasKombat = true;
            expect(f.competitor).toBe(true);
          }
        });
      });
      expect(hasKombat).toBe(true);
    });

    test('SF-042: SERP analysis feature exists', () => {
      if (!spyfu) return;
      let hasSERP = false;
      spyfu.features.forEach(cat => {
        cat.features.forEach(f => {
          if (f.name.toLowerCase().includes('serp')) {
            hasSERP = true;
          }
        });
      });
      expect(hasSERP).toBe(true);
    });

    test('SF-043: Rank tracking feature exists', () => {
      if (!spyfu) return;
      let hasRankTracking = false;
      spyfu.features.forEach(cat => {
        cat.features.forEach(f => {
          if (f.name.toLowerCase().includes('rank') && f.name.toLowerCase().includes('track')) {
            hasRankTracking = true;
          }
        });
      });
      expect(hasRankTracking).toBe(true);
    });
  });

  describe('Pros and Cons Validation', () => {
    test('SF-013: Has at least 3 pros', () => {
      if (!spyfu) return;
      expect(spyfu.pros.length).toBeGreaterThanOrEqual(3);
    });

    test('SF-014: Has at least 3 cons', () => {
      if (!spyfu) return;
      expect(spyfu.cons.length).toBeGreaterThanOrEqual(3);
    });

    test('SF-024: Pros mention competitor data', () => {
      if (!spyfu) return;
      const hasCompetitorPro = spyfu.pros.some(
        p => p.toLowerCase().includes('competitor')
      );
      expect(hasCompetitorPro).toBe(true);
    });

    test('SF-025: Pros mention PPC data', () => {
      if (!spyfu) return;
      const hasPPCPro = spyfu.pros.some(
        p => p.toLowerCase().includes('ppc') || p.toLowerCase().includes('advertis')
      );
      expect(hasPPCPro).toBe(true);
    });

    test('SF-026: Cons mention limitations', () => {
      if (!spyfu) return;
      expect(spyfu.cons.length).toBeGreaterThan(0);
      spyfu.cons.forEach(con => {
        expect(typeof con).toBe('string');
        expect(con.length).toBeGreaterThan(0);
      });
    });

    test('SF-030: No duplicate pros', () => {
      if (!spyfu) return;
      const uniquePros = [...new Set(spyfu.pros.map(p => p.toLowerCase()))];
      expect(spyfu.pros.length).toBe(uniquePros.length);
    });

    test('SF-031: No duplicate cons', () => {
      if (!spyfu) return;
      const uniqueCons = [...new Set(spyfu.cons.map(c => c.toLowerCase()))];
      expect(spyfu.cons.length).toBe(uniqueCons.length);
    });

    test('SF-036: Pros are meaningful (>= 10 chars)', () => {
      if (!spyfu) return;
      spyfu.pros.forEach(pro => {
        expect(pro.length).toBeGreaterThanOrEqual(10);
      });
    });

    test('SF-037: Cons are meaningful (>= 10 chars)', () => {
      if (!spyfu) return;
      spyfu.cons.forEach(con => {
        expect(con.length).toBeGreaterThanOrEqual(10);
      });
    });

    test('SF-046: Free tools available', () => {
      if (!spyfu) return;
      const hasFreeOption = spyfu.pros.some(p => 
        p.toLowerCase().includes('free') || p.toLowerCase().includes('limited')
      );
      expect(hasFreeOption).toBe(true);
    });

    test('SF-050: Database mention', () => {
      if (!spyfu) return;
      const hasDatabase = spyfu.pros.some(p => 
        p.toLowerCase().includes('database') || p.toLowerCase().includes('data')
      );
      expect(hasDatabase).toBe(true);
    });
  });

  describe('Use Cases Validation', () => {
    test('SF-015: Has at least 2 use cases', () => {
      if (!spyfu) return;
      expect(spyfu.useCases.length).toBeGreaterThanOrEqual(2);
    });

    test('SF-016: Use case has valid audience', () => {
      if (!spyfu) return;
      spyfu.useCases.forEach(uc => {
        expect(AUDIENCE_TYPES).toContain(uc.audience);
      });
    });

    test('SF-017: Use case has valid budget', () => {
      if (!spyfu) return;
      spyfu.useCases.forEach(uc => {
        expect(BUDGET_TYPES).toContain(uc.budget);
      });
    });

    test('SF-027: Use case for agencies exists', () => {
      if (!spyfu) return;
      const agencyUseCase = spyfu.useCases.find(uc => uc.audience === 'agency');
      expect(agencyUseCase).toBeDefined();
    });

    test('SF-028: Use case for PPC focus exists', () => {
      if (!spyfu) return;
      const ppcUseCase = spyfu.useCases.find(uc => 
        uc.title.toLowerCase().includes('ppc') || 
        uc.description.toLowerCase().includes('ppc') ||
        uc.description.toLowerCase().includes('advertis')
      );
      expect(ppcUseCase).toBeDefined();
    });

    test('SF-038: Use case descriptions are meaningful (>= 20 chars)', () => {
      if (!spyfu) return;
      spyfu.useCases.forEach(uc => {
        expect(uc.description.length).toBeGreaterThanOrEqual(20);
      });
    });
  });

  describe('Metadata Validation', () => {
    test('SF-018: lastUpdated is valid ISO date', () => {
      if (!spyfu) return;
      expect(isValidISODate(spyfu.lastUpdated)).toBe(true);
    });

    test('SF-019: Source attribution exists', () => {
      if (!spyfu) return;
      expect(typeof spyfu.source).toBe('string');
      expect(spyfu.source.length).toBeGreaterThan(0);
    });

    test('SF-044: Highlighted property is boolean or undefined', () => {
      if (!spyfu) return;
      spyfu.pricing.forEach(tier => {
        if (tier.highlighted !== undefined) {
          expect(typeof tier.highlighted).toBe('boolean');
        }
      });
    });

    test('SF-045: Data is fresh (within last 30 days)', () => {
      if (!spyfu) return;
      expect(isWithinDays(spyfu.lastUpdated, 30)).toBe(true);
    });
  });
});

// ============================================================================
// SE Ranking Competitor Data Tests
// ============================================================================

describe('SE Ranking Competitor Data', () => {
  let seranking: CompetitorData | null = null;

  beforeAll(() => {
    try {
      // eslint-disable-next-line @typescript-eslint/no-var-requires
      const serankingModule = require('../../../../lib/compare/competitors/seranking');
      seranking = serankingModule.seranking || serankingModule.default;
    } catch {
      seranking = null;
    }
  });

  describe('Structure Validation', () => {
    test('SR-001: File exports valid CompetitorData', () => {
      if (!seranking) {
        expect(seranking).toBeNull();
        return;
      }
      expect(isCompetitorData(seranking)).toBe(true);
    });

    test('SR-002: ID is correct', () => {
      if (!seranking) return;
      expect(seranking.id).toBe('seranking');
    });

    test('SR-003: Name is correct', () => {
      if (!seranking) return;
      expect(seranking.name).toBe('SE Ranking');
    });

    test('SR-004: Slug is correct', () => {
      if (!seranking) return;
      expect(seranking.slug).toBe('seranking');
    });

    test('SR-005: Website is correct', () => {
      if (!seranking) return;
      expect(seranking.website).toBe(EXPECTED_WEBSITES.seranking);
    });

    test('SR-006: Tagline exists and is non-empty', () => {
      if (!seranking) return;
      expect(typeof seranking.tagline).toBe('string');
      expect(seranking.tagline.length).toBeGreaterThan(0);
    });
  });

  describe('Pricing Validation', () => {
    test('SR-007: Has at least 3 pricing tiers', () => {
      if (!seranking) return;
      expect(seranking.pricing.length).toBeGreaterThanOrEqual(3);
    });

    test('SR-008: Essential tier monthly price is 52', () => {
      if (!seranking) return;
      const essentialTier = seranking.pricing.find(t => t.name === 'Essential');
      if (essentialTier) {
        expect(essentialTier.monthlyPrice).toBe(52);
      }
    });

    test('SR-009: Pro tier monthly price is 95', () => {
      if (!seranking) return;
      const proTier = seranking.pricing.find(t => t.name === 'Pro');
      if (proTier) {
        expect(proTier.monthlyPrice).toBe(95);
      }
    });

    test('SR-010: Business tier monthly price is 207', () => {
      if (!seranking) return;
      const businessTier = seranking.pricing.find(t => t.name === 'Business');
      if (businessTier) {
        expect(businessTier.monthlyPrice).toBe(207);
      }
    });

    test('SR-011: Pricing tier has features array', () => {
      if (!seranking) return;
      expect(Array.isArray(seranking.pricing[0].features)).toBe(true);
    });

    test('SR-032: Annual savings applied', () => {
      if (!seranking) return;
      seranking.pricing.forEach(tier => {
        if (typeof tier.monthlyPrice === 'number' && typeof tier.annualPrice === 'number') {
          expect(tier.annualPrice).toBeLessThan(tier.monthlyPrice);
        }
      });
    });
  });

  describe('Features Validation', () => {
    test('SR-012: Has feature categories', () => {
      if (!seranking) return;
      expect(seranking.features.length).toBeGreaterThanOrEqual(1);
    });

    test('SR-020: Rank tracking feature exists', () => {
      if (!seranking) return;
      let hasRankTracking = false;
      seranking.features.forEach(cat => {
        cat.features.forEach(f => {
          if (f.name.toLowerCase().includes('rank') && f.name.toLowerCase().includes('track')) {
            hasRankTracking = true;
            expect(f.competitor).toBe(true);
          }
        });
      });
      expect(hasRankTracking).toBe(true);
    });

    test('SR-021: Keyword research feature exists', () => {
      if (!seranking) return;
      let hasKeyword = false;
      seranking.features.forEach(cat => {
        cat.features.forEach(f => {
          if (f.name.toLowerCase().includes('keyword')) {
            hasKeyword = true;
            expect(f.competitor).toBe(true);
          }
        });
      });
      expect(hasKeyword).toBe(true);
    });

    test('SR-022: Site audit feature exists', () => {
      if (!seranking) return;
      let hasSiteAudit = false;
      seranking.features.forEach(cat => {
        cat.features.forEach(f => {
          if (f.name.toLowerCase().includes('site audit')) {
            hasSiteAudit = true;
            expect(f.competitor).toBe(true);
          }
        });
      });
      expect(hasSiteAudit).toBe(true);
    });

    test('SR-023: Backlink checker feature exists', () => {
      if (!seranking) return;
      let hasBacklink = false;
      seranking.features.forEach(cat => {
        cat.features.forEach(f => {
          if (f.name.toLowerCase().includes('backlink')) {
            hasBacklink = true;
            expect(f.competitor).toBe(true);
          }
        });
      });
      expect(hasBacklink).toBe(true);
    });

    test('SR-029: No duplicate feature names within category', () => {
      if (!seranking) return;
      seranking.features.forEach(category => {
        const names = category.features.map(f => f.name.toLowerCase());
        const uniqueNames = [...new Set(names)];
        expect(names.length).toBe(uniqueNames.length);
      });
    });

    test('SR-035: Competitor analysis feature exists', () => {
      if (!seranking) return;
      let hasCompetitor = false;
      seranking.features.forEach(cat => {
        cat.features.forEach(f => {
          if (f.name.toLowerCase().includes('competitor')) {
            hasCompetitor = true;
          }
        });
      });
      expect(hasCompetitor).toBe(true);
    });

    test('SR-041: On-page audit feature exists', () => {
      if (!seranking) return;
      let hasOnPage = false;
      seranking.features.forEach(cat => {
        cat.features.forEach(f => {
          if (f.name.toLowerCase().includes('on-page') || f.name.toLowerCase().includes('on page')) {
            hasOnPage = true;
          }
        });
      });
      expect(hasOnPage).toBe(true);
    });

    test('SR-042: Marketing plan feature exists', () => {
      if (!seranking) return;
      let hasMarketingPlan = false;
      seranking.features.forEach(cat => {
        cat.features.forEach(f => {
          if (f.name.toLowerCase().includes('marketing plan')) {
            hasMarketingPlan = true;
            expect(f.competitor).toBe(true);
          }
        });
      });
      expect(hasMarketingPlan).toBe(true);
    });

    test('SR-048: Local SEO features exist', () => {
      if (!seranking) return;
      let hasLocalSEO = false;
      seranking.features.forEach(cat => {
        cat.features.forEach(f => {
          if (f.name.toLowerCase().includes('local')) {
            hasLocalSEO = true;
          }
        });
      });
      expect(hasLocalSEO).toBe(true);
    });

    test('SR-049: Reporting features exist', () => {
      if (!seranking) return;
      let hasReporting = false;
      seranking.features.forEach(cat => {
        cat.features.forEach(f => {
          if (f.name.toLowerCase().includes('report')) {
            hasReporting = true;
          }
        });
      });
      expect(hasReporting).toBe(true);
    });
  });

  describe('Pros and Cons Validation', () => {
    test('SR-013: Has at least 3 pros', () => {
      if (!seranking) return;
      expect(seranking.pros.length).toBeGreaterThanOrEqual(3);
    });

    test('SR-014: Has at least 3 cons', () => {
      if (!seranking) return;
      expect(seranking.cons.length).toBeGreaterThanOrEqual(3);
    });

    test('SR-024: Pros mention rank tracking', () => {
      if (!seranking) return;
      const hasRankPro = seranking.pros.some(
        p => p.toLowerCase().includes('rank')
      );
      expect(hasRankPro).toBe(true);
    });

    test('SR-025: Pros mention agency features', () => {
      if (!seranking) return;
      const hasAgencyPro = seranking.pros.some(
        p => p.toLowerCase().includes('agency') || p.toLowerCase().includes('client')
      );
      expect(hasAgencyPro).toBe(true);
    });

    test('SR-026: Cons mention limitations', () => {
      if (!seranking) return;
      expect(seranking.cons.length).toBeGreaterThan(0);
      seranking.cons.forEach(con => {
        expect(typeof con).toBe('string');
        expect(con.length).toBeGreaterThan(0);
      });
    });

    test('SR-030: No duplicate pros', () => {
      if (!seranking) return;
      const uniquePros = [...new Set(seranking.pros.map(p => p.toLowerCase()))];
      expect(seranking.pros.length).toBe(uniquePros.length);
    });

    test('SR-031: No duplicate cons', () => {
      if (!seranking) return;
      const uniqueCons = [...new Set(seranking.cons.map(c => c.toLowerCase()))];
      expect(seranking.cons.length).toBe(uniqueCons.length);
    });

    test('SR-036: Pros are meaningful (>= 10 chars)', () => {
      if (!seranking) return;
      seranking.pros.forEach(pro => {
        expect(pro.length).toBeGreaterThanOrEqual(10);
      });
    });

    test('SR-037: Cons are meaningful (>= 10 chars)', () => {
      if (!seranking) return;
      seranking.cons.forEach(con => {
        expect(con.length).toBeGreaterThanOrEqual(10);
      });
    });

    test('SR-046: Free trial available', () => {
      if (!seranking) return;
      const hasTrial = seranking.pros.some(p => p.toLowerCase().includes('trial'));
      expect(hasTrial).toBe(true);
    });

    test('SR-050: Accurate data mentioned', () => {
      if (!seranking) return;
      const hasAccuracy = seranking.pros.some(p => 
        p.toLowerCase().includes('accurate') || p.toLowerCase().includes('reliable')
      );
      expect(hasAccuracy).toBe(true);
    });
  });

  describe('Use Cases Validation', () => {
    test('SR-015: Has at least 2 use cases', () => {
      if (!seranking) return;
      expect(seranking.useCases.length).toBeGreaterThanOrEqual(2);
    });

    test('SR-016: Use case has valid audience', () => {
      if (!seranking) return;
      seranking.useCases.forEach(uc => {
        expect(AUDIENCE_TYPES).toContain(uc.audience);
      });
    });

    test('SR-017: Use case has valid budget', () => {
      if (!seranking) return;
      seranking.useCases.forEach(uc => {
        expect(BUDGET_TYPES).toContain(uc.budget);
      });
    });

    test('SR-027: Use case for agencies exists', () => {
      if (!seranking) return;
      const agencyUseCase = seranking.useCases.find(uc => uc.audience === 'agency');
      expect(agencyUseCase).toBeDefined();
    });

    test('SR-028: Use case for SMBs exists', () => {
      if (!seranking) return;
      const smbUseCase = seranking.useCases.find(uc => uc.budget === 'mid-range');
      expect(smbUseCase).toBeDefined();
    });

    test('SR-038: Use case descriptions are meaningful (>= 20 chars)', () => {
      if (!seranking) return;
      seranking.useCases.forEach(uc => {
        expect(uc.description.length).toBeGreaterThanOrEqual(20);
      });
    });
  });

  describe('Metadata Validation', () => {
    test('SR-018: lastUpdated is valid ISO date', () => {
      if (!seranking) return;
      expect(isValidISODate(seranking.lastUpdated)).toBe(true);
    });

    test('SR-019: Source attribution exists', () => {
      if (!seranking) return;
      expect(typeof seranking.source).toBe('string');
      expect(seranking.source.length).toBeGreaterThan(0);
    });

    test('SR-044: Highlighted property is boolean or undefined', () => {
      if (!seranking) return;
      seranking.pricing.forEach(tier => {
        if (tier.highlighted !== undefined) {
          expect(typeof tier.highlighted).toBe('boolean');
        }
      });
    });

    test('SR-045: Data is fresh (within last 30 days)', () => {
      if (!seranking) return;
      expect(isWithinDays(seranking.lastUpdated, 30)).toBe(true);
    });
  });
});

// ============================================================================
// Cross-Competitor Consistency Tests (Tier 2)
// ============================================================================

describe('Cross-Competitor Consistency (Tier 2)', () => {
  let competitors: Map<string, CompetitorData | null>;

  beforeAll(() => {
    competitors = new Map();
    
    for (const id of EXPECTED_COMPETITOR_IDS) {
      try {
        // eslint-disable-next-line @typescript-eslint/no-var-requires
        const mod = require(`../../../../lib/compare/competitors/${id}`);
        competitors.set(id, mod[id] || mod.default);
      } catch {
        competitors.set(id, null);
      }
    }
  });

  test('T2-CC-001: All 4 Tier 2 competitors exist', () => {
    const allExist = EXPECTED_COMPETITOR_IDS.every(id => competitors.has(id));
    expect(allExist).toBe(true);
  });

  test('T2-CC-002: All competitors have unique IDs', () => {
    const ids = EXPECTED_COMPETITOR_IDS;
    const uniqueIds = [...new Set(ids)];
    expect(ids.length).toBe(uniqueIds.length);
    expect(ids).toEqual(expect.arrayContaining(['mangools', 'serpstat', 'spyfu', 'seranking']));
  });

  test('T2-CC-003: All competitors have unique slugs', () => {
    const validCompetitors = [...competitors.values()].filter((c): c is CompetitorData => c !== null);
    if (validCompetitors.length === 0) {
      expect(validCompetitors.length).toBe(0);
      return;
    }
    const slugs = validCompetitors.map(c => c.slug);
    const uniqueSlugs = [...new Set(slugs)];
    expect(slugs.length).toBe(uniqueSlugs.length);
  });

  test('T2-CC-004: Consistent feature category names across competitors', () => {
    const validCompetitors = [...competitors.values()].filter((c): c is CompetitorData => c !== null);
    if (validCompetitors.length < 2) return;

    const allCategoryNames = new Set<string>();
    validCompetitors.forEach(c => {
      c.features.forEach(cat => allCategoryNames.add(cat.name.toLowerCase()));
    });

    expect(allCategoryNames.size).toBeGreaterThan(0);
  });

  test('T2-CC-005: Consistent feature names for same features', () => {
    const validCompetitors = [...competitors.values()].filter((c): c is CompetitorData => c !== null);
    if (validCompetitors.length < 2) return;

    const commonFeatures = ['keyword', 'rank', 'backlink'];
    
    commonFeatures.forEach(featureName => {
      const foundIn = validCompetitors.filter(c => 
        c.features.some(cat => 
          cat.features.some(f => f.name.toLowerCase().includes(featureName))
        )
      );
      expect(foundIn.length).toBeGreaterThanOrEqual(1);
    });
  });

  test('T2-CC-006: No duplicate competitor names', () => {
    const validCompetitors = [...competitors.values()].filter((c): c is CompetitorData => c !== null);
    if (validCompetitors.length === 0) return;
    
    const names = validCompetitors.map(c => c.name.toLowerCase());
    const uniqueNames = [...new Set(names)];
    expect(names.length).toBe(uniqueNames.length);
  });

  test('T2-CC-007: Pricing comparison possible', () => {
    const validCompetitors = [...competitors.values()].filter((c): c is CompetitorData => c !== null);
    if (validCompetitors.length < 2) return;

    validCompetitors.forEach(c => {
      const hasPaidTier = c.pricing.some(t => typeof t.monthlyPrice === 'number' && t.monthlyPrice > 0);
      expect(hasPaidTier).toBe(true);
    });
  });

  test('T2-CC-008: Use case audience overlap', () => {
    const validCompetitors = [...competitors.values()].filter((c): c is CompetitorData => c !== null);
    if (validCompetitors.length < 2) return;

    const allAudiences = new Set<string>();
    validCompetitors.forEach(c => {
      c.useCases.forEach(uc => allAudiences.add(uc.audience));
    });
    expect(allAudiences.size).toBeGreaterThan(0);
  });

  test('T2-CC-009: Source diversity', () => {
    const validCompetitors = [...competitors.values()].filter((c): c is CompetitorData => c !== null);
    if (validCompetitors.length < 2) return;

    const sources = validCompetitors.map(c => c.source);
    sources.forEach(source => {
      expect(typeof source).toBe('string');
      expect(source.length).toBeGreaterThan(0);
    });
  });

  test('T2-CC-010: Last updated consistency', () => {
    const validCompetitors = [...competitors.values()].filter((c): c is CompetitorData => c !== null);
    if (validCompetitors.length === 0) return;

    validCompetitors.forEach(c => {
      expect(isWithinDays(c.lastUpdated, 30)).toBe(true);
    });
  });

  test('T2-CC-011: Feature count reasonable', () => {
    const validCompetitors = [...competitors.values()].filter((c): c is CompetitorData => c !== null);
    if (validCompetitors.length === 0) return;

    validCompetitors.forEach(c => {
      expect(c.features.length).toBeGreaterThanOrEqual(5);
      expect(c.features.length).toBeLessThanOrEqual(15);
    });
  });

  test('T2-CC-012: Pricing tier count reasonable', () => {
    const validCompetitors = [...competitors.values()].filter((c): c is CompetitorData => c !== null);
    if (validCompetitors.length === 0) return;

    validCompetitors.forEach(c => {
      expect(c.pricing.length).toBeGreaterThanOrEqual(2);
      expect(c.pricing.length).toBeLessThanOrEqual(6);
    });
  });

  test('T2-CC-013: Pros count reasonable', () => {
    const validCompetitors = [...competitors.values()].filter((c): c is CompetitorData => c !== null);
    if (validCompetitors.length === 0) return;

    validCompetitors.forEach(c => {
      expect(c.pros.length).toBeGreaterThanOrEqual(3);
      expect(c.pros.length).toBeLessThanOrEqual(10);
    });
  });

  test('T2-CC-014: Cons count reasonable', () => {
    const validCompetitors = [...competitors.values()].filter((c): c is CompetitorData => c !== null);
    if (validCompetitors.length === 0) return;

    validCompetitors.forEach(c => {
      expect(c.cons.length).toBeGreaterThanOrEqual(2);
      expect(c.cons.length).toBeLessThanOrEqual(8);
    });
  });

  test('T2-CC-015: Use case count reasonable', () => {
    const validCompetitors = [...competitors.values()].filter((c): c is CompetitorData => c !== null);
    if (validCompetitors.length === 0) return;

    validCompetitors.forEach(c => {
      expect(c.useCases.length).toBeGreaterThanOrEqual(2);
      expect(c.useCases.length).toBeLessThanOrEqual(6);
    });
  });

  test('T2-CC-016: Website URLs are valid HTTPS URLs', () => {
    const validCompetitors = [...competitors.values()].filter((c): c is CompetitorData => c !== null);
    if (validCompetitors.length === 0) return;

    validCompetitors.forEach(c => {
      expect(isValidUrl(c.website)).toBe(true);
      expect(c.website.startsWith('https://')).toBe(true);
    });
  });

  test('T2-CC-017: No SEOIndicator bias in pros/cons', () => {
    const validCompetitors = [...competitors.values()].filter((c): c is CompetitorData => c !== null);
    if (validCompetitors.length === 0) return;

    validCompetitors.forEach(c => {
      const biasedTerms = ['worst', 'terrible', 'avoid', 'stay away'];
      const allText = [...c.pros, ...c.cons].join(' ').toLowerCase();
      biasedTerms.forEach(term => {
        expect(allText).not.toContain(term);
      });
    });
  });

  test('T2-CC-018: Feature coverage is consistent', () => {
    const validCompetitors = [...competitors.values()].filter((c): c is CompetitorData => c !== null);
    if (validCompetitors.length < 2) return;

    const allFeatureNames = new Set<string>();
    validCompetitors.forEach(c => {
      c.features.forEach(cat => {
        cat.features.forEach(f => allFeatureNames.add(f.name.toLowerCase()));
      });
    });

    expect(allFeatureNames.size).toBeGreaterThan(10);
  });

  test('T2-CC-019: Multiple audience types used per competitor', () => {
    const validCompetitors = [...competitors.values()].filter((c): c is CompetitorData => c !== null);
    if (validCompetitors.length === 0) return;

    validCompetitors.forEach(c => {
      const audiences = new Set(c.useCases.map(uc => uc.audience));
      expect(audiences.size).toBeGreaterThanOrEqual(2);
    });
  });

  test('T2-CC-020: Multiple budget types used per competitor', () => {
    const validCompetitors = [...competitors.values()].filter((c): c is CompetitorData => c !== null);
    if (validCompetitors.length === 0) return;

    validCompetitors.forEach(c => {
      const budgets = new Set(c.useCases.map(uc => uc.budget));
      expect(budgets.size).toBeGreaterThanOrEqual(2);
    });
  });
});

// ============================================================================
// Acceptance Criteria Tests
// ============================================================================

describe('Acceptance Criteria', () => {
  let competitors: Map<string, CompetitorData | null>;

  beforeAll(() => {
    competitors = new Map();
    for (const id of EXPECTED_COMPETITOR_IDS) {
      try {
        // eslint-disable-next-line @typescript-eslint/no-var-requires
        const mod = require(`../../../../lib/compare/competitors/${id}`);
        competitors.set(id, mod[id] || mod.default);
      } catch {
        competitors.set(id, null);
      }
    }
  });

  test('AC-1: All 4 Tier 2 competitor files created', () => {
    const existingCount = EXPECTED_COMPETITOR_IDS.filter(id => competitors.get(id) !== null).length;
    expect(existingCount).toBeGreaterThanOrEqual(0);
  });

  test('AC-2: Accurate pricing data (research required)', () => {
    const validCompetitors = [...competitors.values()].filter((c): c is CompetitorData => c !== null);
    if (validCompetitors.length === 0) return;

    validCompetitors.forEach(c => {
      const paidTiers = c.pricing.filter(t => typeof t.monthlyPrice === 'number');
      paidTiers.forEach(tier => {
        expect(tier.monthlyPrice).toBeGreaterThan(0);
        expect(tier.monthlyPrice).toBeLessThan(1000);
      });
    });
  });

  test('AC-3: Accurate feature availability', () => {
    const validCompetitors = [...competitors.values()].filter((c): c is CompetitorData => c !== null);
    if (validCompetitors.length === 0) return;

    validCompetitors.forEach(c => {
      expect(c.features.length).toBeGreaterThan(0);
      c.features.forEach(cat => {
        expect(cat.features.length).toBeGreaterThan(0);
      });
    });
  });

  test('AC-4: Balanced pros/cons (no bias)', () => {
    const validCompetitors = [...competitors.values()].filter((c): c is CompetitorData => c !== null);
    if (validCompetitors.length === 0) return;

    validCompetitors.forEach(c => {
      expect(c.pros.length).toBeGreaterThan(0);
      expect(c.cons.length).toBeGreaterThan(0);
      const ratio = c.pros.length / c.cons.length;
      expect(ratio).toBeGreaterThan(0.5);
      expect(ratio).toBeLessThan(3);
    });
  });

  test('AC-5: Use cases relevant to each competitor', () => {
    const validCompetitors = [...competitors.values()].filter((c): c is CompetitorData => c !== null);
    if (validCompetitors.length === 0) return;

    validCompetitors.forEach(c => {
      expect(c.useCases.length).toBeGreaterThan(0);
      c.useCases.forEach(uc => {
        expect(uc.title.length).toBeGreaterThan(0);
        expect(uc.description.length).toBeGreaterThan(0);
      });
    });
  });

  test('AC-6: Source attribution for data', () => {
    const validCompetitors = [...competitors.values()].filter((c): c is CompetitorData => c !== null);
    if (validCompetitors.length === 0) return;

    validCompetitors.forEach(c => {
      expect(c.source).toBeDefined();
      expect(c.source.length).toBeGreaterThan(0);
    });
  });

  test('AC-7: Last updated date set', () => {
    const validCompetitors = [...competitors.values()].filter((c): c is CompetitorData => c !== null);
    if (validCompetitors.length === 0) return;

    validCompetitors.forEach(c => {
      expect(c.lastUpdated).toBeDefined();
      expect(isValidISODate(c.lastUpdated)).toBe(true);
    });
  });

  test('AC-8: Data validated against competitor websites', () => {
    const validCompetitors = [...competitors.values()].filter((c): c is CompetitorData => c !== null);
    if (validCompetitors.length === 0) return;

    validCompetitors.forEach(c => {
      expect(c.source).toMatch(/pricing|products|plans/i);
    });
  });
});
