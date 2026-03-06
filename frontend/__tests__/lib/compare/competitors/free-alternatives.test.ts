/**
 * Free Alternatives Competitor Data Tests
 * Task: TASK-32-14 - Free Alternatives Data (GSC, GA, AnswerThePublic)
 * Spec: SPEC-32 (Competitor Comparison Landing Pages)
 * 
 * Test suite for free tool competitor data files targeting "free alternative to X" keywords.
 * These are positioned as legitimate free tools with honest pros/cons.
 */

import { describe, it, expect } from 'vitest';
import {
  isCompetitorData,
  isPricingTier,
  isFeatureCategory,
  isUseCase,
  AUDIENCE_TYPES,
  BUDGET_TYPES,
} from '@/lib/compare/types';

// ============================================================================
// Mock Competitor Data (Developer will replace with actual imports)
// ============================================================================

/**
 * Google Search Console Competitor Data
 * Expected file: frontend/lib/compare/competitors/gsc.ts
 */
const gsc = {
  id: 'gsc',
  name: 'Google Search Console',
  slug: 'google-search-console',
  website: 'https://search.google.com/search-console',
  logo: '/images/competitors/gsc.svg',
  tagline: "Google's official tool for website performance in search",
  pricing: [
    {
      name: 'Free',
      monthlyPrice: 'Free' as const,
      annualPrice: 'Free' as const,
      features: [
        'Search performance data',
        'Index coverage reports',
        'Sitemap submission',
        'Mobile usability reports',
        'Core Web Vitals',
        'URL inspection',
        'Manual actions',
        'Security issues',
      ],
    },
  ],
  features: [
    {
      name: 'On-Page SEO',
      features: [
        { name: 'Site Audit', seoindicator: true, competitor: false },
        { name: 'Meta Analyzer', seoindicator: true, competitor: false },
        { name: 'Page Experience', seoindicator: true, competitor: true },
        { name: 'URL Inspection', seoindicator: false, competitor: true },
      ],
    },
    {
      name: 'Technical SEO',
      features: [
        { name: 'Index Coverage', seoindicator: false, competitor: true },
        { name: 'Core Web Vitals', seoindicator: true, competitor: true },
        { name: 'Mobile Usability', seoindicator: true, competitor: true },
        { name: 'Page Speed Insights', seoindicator: true, competitor: false },
        { name: 'Structured Data', seoindicator: true, competitor: false },
      ],
    },
    {
      name: 'Keywords',
      features: [
        { name: 'Search Analytics', seoindicator: true, competitor: true },
        { name: 'Keyword Research', seoindicator: true, competitor: false },
        { name: 'Rank Tracking', seoindicator: true, competitor: false },
        { name: 'Keyword Difficulty', seoindicator: true, competitor: false },
        { name: 'Search Volume', seoindicator: true, competitor: 'Partial' },
      ],
    },
    {
      name: 'Backlinks',
      features: [
        { name: 'Backlink Checker', seoindicator: true, competitor: false },
        { name: 'Link Analysis', seoindicator: true, competitor: false },
        { name: 'Referring Domains', seoindicator: true, competitor: false },
        { name: 'External Links', seoindicator: false, competitor: true },
      ],
    },
    {
      name: 'Reporting',
      features: [
        { name: 'Performance Reports', seoindicator: true, competitor: true },
        { name: 'Email Alerts', seoindicator: true, competitor: true },
        { name: 'PDF Export', seoindicator: true, competitor: false },
        { name: 'API Access', seoindicator: false, competitor: true },
      ],
    },
    {
      name: 'Pricing & Access',
      features: [
        { name: 'Free Tier', seoindicator: true, competitor: true },
        { name: 'Requires Verification', seoindicator: false, competitor: true },
        { name: 'Google Account Required', seoindicator: false, competitor: true },
      ],
    },
  ],
  pros: [
    'Completely free with no usage limits',
    'Direct data from Google search',
    'Essential for indexing and crawl management',
    'Core Web Vitals integration',
    'Security and manual action alerts',
    'Mobile usability insights',
    'Official Google support',
    'API access available',
  ],
  cons: [
    'Limited SEO analysis capabilities',
    'No competitor data or analysis',
    'Basic keyword research only',
    'No backlink analysis',
    'Requires website ownership verification',
    'Limited historical data (16 months)',
    'No rank tracking across locations',
    'Learning curve for beginners',
  ],
  useCases: [
    {
      title: 'Index Management',
      description: 'Essential for ensuring Google can crawl and index your site properly',
      audience: 'solo',
      budget: 'free',
    },
    {
      title: 'Technical SEO Monitoring',
      description: 'Monitor Core Web Vitals, mobile usability, and security issues',
      audience: 'small-team',
      budget: 'free',
    },
    {
      title: 'Search Performance Tracking',
      description: 'Track impressions, clicks, and average position for your pages',
      audience: 'solo',
      budget: 'free',
    },
  ],
  lastUpdated: '2026-03-05',
  source: 'search.google.com/search-console',
};

/**
 * Google Analytics Competitor Data
 * Expected file: frontend/lib/compare/competitors/ga.ts
 */
const ga = {
  id: 'ga',
  name: 'Google Analytics',
  slug: 'google-analytics',
  website: 'https://analytics.google.com',
  logo: '/images/competitors/ga.svg',
  tagline: 'Free web analytics for tracking website traffic and user behavior',
  pricing: [
    {
      name: 'Free',
      monthlyPrice: 'Free' as const,
      annualPrice: 'Free' as const,
      features: [
        'Unlimited traffic tracking',
        'Real-time reports',
        'Audience insights',
        'Acquisition reports',
        'Behavior analysis',
        'Conversion goals',
        'Event tracking',
        'Custom dimensions',
      ],
    },
  ],
  features: [
    {
      name: 'Traffic Analytics',
      features: [
        { name: 'Real-Time Visitors', seoindicator: false, competitor: true },
        { name: 'Traffic Sources', seoindicator: false, competitor: true },
        { name: 'User Behavior', seoindicator: false, competitor: true },
        { name: 'Audience Insights', seoindicator: false, competitor: true },
      ],
    },
    {
      name: 'On-Page SEO',
      features: [
        { name: 'Site Audit', seoindicator: true, competitor: false },
        { name: 'Meta Analyzer', seoindicator: true, competitor: false },
        { name: 'Content Analysis', seoindicator: true, competitor: false },
        { name: 'Landing Page Data', seoindicator: false, competitor: true },
      ],
    },
    {
      name: 'Keywords',
      features: [
        { name: 'Keyword Research', seoindicator: true, competitor: false },
        { name: 'Rank Tracking', seoindicator: true, competitor: false },
        { name: 'Search Console Data', seoindicator: true, competitor: 'Partial' },
        { name: 'Organic Search Queries', seoindicator: true, competitor: 'Partial' },
      ],
    },
    {
      name: 'Reporting',
      features: [
        { name: 'Custom Dashboards', seoindicator: true, competitor: true },
        { name: 'Goal Tracking', seoindicator: false, competitor: true },
        { name: 'PDF Export', seoindicator: true, competitor: false },
        { name: 'API Access', seoindicator: false, competitor: true },
        { name: 'Email Reports', seoindicator: false, competitor: true },
      ],
    },
    {
      name: 'Pricing & Access',
      features: [
        { name: 'Free Tier', seoindicator: true, competitor: true },
        { name: 'Google Account Required', seoindicator: false, competitor: true },
        { name: 'Tracking Code Required', seoindicator: false, competitor: true },
      ],
    },
  ],
  pros: [
    'Completely free for most websites',
    'Comprehensive traffic analytics',
    'Real-time visitor tracking',
    'Goal and conversion tracking',
    'Audience demographics',
    'Behavior flow analysis',
    'Integration with Google Ads',
    'Powerful API access',
  ],
  cons: [
    'Not SEO-specific - traffic analytics only',
    'No rank tracking capabilities',
    'No backlink analysis',
    'No keyword research tools',
    'Requires JavaScript tracking code',
    'GDPR/privacy considerations',
    'Learning curve for advanced features',
    'No competitor analysis',
  ],
  useCases: [
    {
      title: 'Traffic Analysis',
      description: 'Understand where visitors come from and how they interact with your site',
      audience: 'solo',
      budget: 'free',
    },
    {
      title: 'Conversion Tracking',
      description: 'Set up goals and track conversions from marketing campaigns',
      audience: 'small-team',
      budget: 'free',
    },
    {
      title: 'Audience Insights',
      description: 'Learn about your visitors demographics, interests, and behavior',
      audience: 'small-team',
      budget: 'free',
    },
  ],
  lastUpdated: '2026-03-05',
  source: 'analytics.google.com',
};

/**
 * AnswerThePublic Competitor Data
 * Expected file: frontend/lib/compare/competitors/answerthepublic.ts
 */
const atp = {
  id: 'answerthepublic',
  name: 'AnswerThePublic',
  slug: 'answerthepublic',
  website: 'https://answerthepublic.com',
  logo: '/images/competitors/answerthepublic.svg',
  tagline: 'Visual keyword research tool for discovering search questions',
  pricing: [
    {
      name: 'Free',
      monthlyPrice: 'Free' as const,
      annualPrice: 'Free' as const,
      features: [
        '3 searches per day',
        'Basic visualizations',
        'Question wheel',
        'Preposition maps',
        'Alphabetical lists',
        'Related topics',
      ],
    },
    {
      name: 'Pro',
      monthlyPrice: 99,
      annualPrice: 79,
      features: [
        'Unlimited searches',
        'High-resolution images',
        'CSV export',
        'Priority data',
        'Compare searches',
        'Folder organization',
      ],
    },
  ],
  features: [
    {
      name: 'Keyword Research',
      features: [
        { name: 'Question Keywords', seoindicator: true, competitor: true },
        { name: 'Visual Keyword Map', seoindicator: false, competitor: true },
        { name: 'Search Volume', seoindicator: true, competitor: false },
        { name: 'Keyword Difficulty', seoindicator: true, competitor: false },
        { name: 'SERP Analysis', seoindicator: true, competitor: false },
      ],
    },
    {
      name: 'Content Ideas',
      features: [
        { name: 'Content Suggestions', seoindicator: true, competitor: true },
        { name: 'Question Analysis', seoindicator: true, competitor: true },
        { name: 'Preposition Ideas', seoindicator: false, competitor: true },
        { name: 'Comparison Keywords', seoindicator: false, competitor: true },
      ],
    },
    {
      name: 'SEO Analysis',
      features: [
        { name: 'Site Audit', seoindicator: true, competitor: false },
        { name: 'On-Page Analysis', seoindicator: true, competitor: false },
        { name: 'Rank Tracking', seoindicator: true, competitor: false },
        { name: 'Backlink Analysis', seoindicator: true, competitor: false },
      ],
    },
    {
      name: 'Reporting',
      features: [
        { name: 'CSV Export', seoindicator: true, competitor: 'Pro' },
        { name: 'PDF Reports', seoindicator: true, competitor: false },
        { name: 'White Label', seoindicator: true, competitor: false },
      ],
    },
    {
      name: 'Pricing & Access',
      features: [
        { name: 'Free Tier', seoindicator: true, competitor: true },
        { name: 'Daily Search Limit', seoindicator: false, competitor: '3/day' },
        { name: 'Starting Price', seoindicator: 'Free', competitor: '$99/mo' },
      ],
    },
  ],
  pros: [
    'Unique visual keyword presentations',
    'Great for content brainstorming',
    'Discovers question-based keywords',
    'Free tier available (3 searches/day)',
    'Intuitive and easy to use',
    'Good for finding content gaps',
    'Multiple visualization types',
    'Useful for FAQ content creation',
  ],
  cons: [
    'Very limited free tier (3 searches/day)',
    'No SEO analysis tools',
    'No rank tracking',
    'No backlink data',
    'No search volume in free tier',
    'Expensive Pro tier ($99/month)',
    'No competitor analysis',
    'No site audit capabilities',
  ],
  useCases: [
    {
      title: 'Content Ideation',
      description: 'Discover what questions people ask about your topic for content creation',
      audience: 'solo',
      budget: 'free',
    },
    {
      title: 'FAQ Content',
      description: 'Find questions to answer in your FAQ pages and blog posts',
      audience: 'solo',
      budget: 'free',
    },
    {
      title: 'Keyword Discovery',
      description: 'Uncover long-tail keywords and question-based search queries',
      audience: 'small-team',
      budget: 'budget',
    },
  ],
  lastUpdated: '2026-03-05',
  source: 'answerthepublic.com',
};

// ============================================================================
// Google Search Console Tests (GSC-001 to GSC-050)
// ============================================================================

describe('Google Search Console Competitor Data', () => {
  describe('Structure Validation (GSC-001 to GSC-015)', () => {
    it('GSC-001: exports valid CompetitorData', () => {
      expect(isCompetitorData(gsc)).toBe(true);
    });

    it('GSC-002: ID is correct', () => {
      expect(gsc.id).toBe('gsc');
    });

    it('GSC-003: Name is correct', () => {
      expect(gsc.name).toBe('Google Search Console');
    });

    it('GSC-004: Slug is correct', () => {
      expect(gsc.slug).toBe('google-search-console');
    });

    it('GSC-005: Website is correct', () => {
      expect(gsc.website).toBe('https://search.google.com/search-console');
    });

    it('GSC-006: Tagline exists and is meaningful', () => {
      expect(typeof gsc.tagline).toBe('string');
      expect(gsc.tagline.length).toBeGreaterThan(20);
    });

    it('GSC-007: Logo is optional string', () => {
      expect(gsc.logo === undefined || typeof gsc.logo === 'string').toBe(true);
    });

    it('GSC-008: Has pricing tiers', () => {
      expect(gsc.pricing.length).toBeGreaterThanOrEqual(1);
    });

    it('GSC-009: First pricing tier is Free', () => {
      expect(gsc.pricing[0].monthlyPrice).toBe('Free');
      expect(gsc.pricing[0].annualPrice).toBe('Free');
    });

    it('GSC-010: Free tier has features', () => {
      expect(gsc.pricing[0].features.length).toBeGreaterThanOrEqual(5);
    });

    it('GSC-011: Has feature categories', () => {
      expect(gsc.features.length).toBeGreaterThanOrEqual(5);
    });

    it('GSC-012: Has pros', () => {
      expect(gsc.pros.length).toBeGreaterThanOrEqual(5);
    });

    it('GSC-013: Has cons', () => {
      expect(gsc.cons.length).toBeGreaterThanOrEqual(5);
    });

    it('GSC-014: Has use cases', () => {
      expect(gsc.useCases.length).toBeGreaterThanOrEqual(2);
    });

    it('GSC-015: Last updated is valid date', () => {
      expect(() => new Date(gsc.lastUpdated)).not.toThrow();
    });
  });

  describe('Free Tool Characteristics (GSC-016 to GSC-025)', () => {
    it('GSC-016: All pricing is Free', () => {
      const allFree = gsc.pricing.every(
        (p) => p.monthlyPrice === 'Free' && p.annualPrice === 'Free'
      );
      expect(allFree).toBe(true);
    });

    it('GSC-017: Positioning as essential tool', () => {
      const essentialTerms = ['essential', 'index', 'google'];
      const hasEssentialPositioning = gsc.pros.some((pro) =>
        essentialTerms.some((term) => pro.toLowerCase().includes(term))
      );
      expect(hasEssentialPositioning).toBe(true);
    });

    it('GSC-018: Honest pros mention free', () => {
      const mentionsFree = gsc.pros.some((pro) =>
        pro.toLowerCase().includes('free')
      );
      expect(mentionsFree).toBe(true);
    });

    it('GSC-019: Honest pros mention Google', () => {
      const mentionsGoogle = gsc.pros.some((pro) =>
        pro.toLowerCase().includes('google')
      );
      expect(mentionsGoogle).toBe(true);
    });

    it('GSC-020: Cons mention limitations honestly', () => {
      const limitationTerms = ['limited', 'no', 'basic'];
      const hasLimitations = gsc.cons.some((con) =>
        limitationTerms.some((term) => con.toLowerCase().includes(term))
      );
      expect(hasLimitations).toBe(true);
    });

    it('GSC-021: Cons mention lack of competitor analysis', () => {
      const mentionsCompetitor = gsc.cons.some(
        (con) =>
          con.toLowerCase().includes('competitor') ||
          con.toLowerCase().includes('backlink')
      );
      expect(mentionsCompetitor).toBe(true);
    });

    it('GSC-022: Cons mention basic keyword research', () => {
      const mentionsKeywords = gsc.cons.some((con) =>
        con.toLowerCase().includes('keyword')
      );
      expect(mentionsKeywords).toBe(true);
    });

    it('GSC-023: Use cases target free budget', () => {
      const hasFreeBudget = gsc.useCases.some((uc) => uc.budget === 'free');
      expect(hasFreeBudget).toBe(true);
    });

    it('GSC-024: Use cases target solo/small-team', () => {
      const validAudiences = gsc.useCases.every(
        (uc) => AUDIENCE_TYPES.includes(uc.audience)
      );
      expect(validAudiences).toBe(true);
    });

    it('GSC-025: Positioning as complement (not replacement)', () => {
      // GSC is essential - pros should reflect this
      const hasEssentialPro = gsc.pros.some(
        (pro) =>
          pro.toLowerCase().includes('essential') ||
          pro.toLowerCase().includes('official') ||
          pro.toLowerCase().includes('direct')
      );
      expect(hasEssentialPro).toBe(true);
    });
  });

  describe('Feature Accuracy (GSC-026 to GSC-035)', () => {
    it('GSC-026: Has Index Coverage feature', () => {
      const hasIndexCoverage = gsc.features.some((cat) =>
        cat.features.some((f) => f.name.toLowerCase().includes('index'))
      );
      expect(hasIndexCoverage).toBe(true);
    });

    it('GSC-027: Has Core Web Vitals feature', () => {
      const hasCWV = gsc.features.some((cat) =>
        cat.features.some((f) =>
          f.name.toLowerCase().includes('core web vitals')
        )
      );
      expect(hasCWV).toBe(true);
    });

    it('GSC-028: Has Mobile Usability feature', () => {
      const hasMobile = gsc.features.some((cat) =>
        cat.features.some((f) => f.name.toLowerCase().includes('mobile'))
      );
      expect(hasMobile).toBe(true);
    });

    it('GSC-029: Shows SEOIndicator advantages in features', () => {
      const seoindicatorAdvantages = gsc.features.flatMap((cat) =>
        cat.features.filter(
          (f) => f.seoindicator === true && f.competitor === false
        )
      );
      expect(seoindicatorAdvantages.length).toBeGreaterThan(5);
    });

    it('GSC-030: Shows GSC advantages in features', () => {
      const gscAdvantages = gsc.features.flatMap((cat) =>
        cat.features.filter(
          (f) => f.seoindicator === false && f.competitor === true
        )
      );
      expect(gscAdvantages.length).toBeGreaterThan(2);
    });

    it('GSC-031: Features are correctly marked', () => {
      gsc.features.forEach((cat) => {
        cat.features.forEach((f) => {
          expect([true, false, 'Partial', 'Pro', 'Add-on', 'Enterprise']).toContain(
            f.seoindicator
          );
          expect([true, false, 'Partial', 'Pro', 'Add-on', 'Enterprise']).toContain(
            f.competitor
          );
        });
      });
    });

    it('GSC-032: Has search analytics/performance feature', () => {
      const hasSearch = gsc.features.some((cat) =>
        cat.features.some(
          (f) =>
            f.name.toLowerCase().includes('search') ||
            f.name.toLowerCase().includes('performance')
        )
      );
      expect(hasSearch).toBe(true);
    });

    it('GSC-033: No duplicate features within categories', () => {
      gsc.features.forEach((cat) => {
        const names = cat.features.map((f) => f.name);
        expect(new Set(names).size).toBe(names.length);
      });
    });

    it('GSC-034: Feature categories are meaningful', () => {
      const categories = gsc.features.map((f) => f.name);
      expect(categories.length).toBeGreaterThan(4);
    });

    it('GSC-035: On-Page SEO category exists', () => {
      const hasOnPage = gsc.features.some(
        (cat) =>
          cat.name.toLowerCase().includes('on-page') ||
          cat.name.toLowerCase().includes('seo')
      );
      expect(hasOnPage).toBe(true);
    });
  });

  describe('Pros and Cons Balance (GSC-036 to GSC-045)', () => {
    it('GSC-036: Pros are meaningful length', () => {
      gsc.pros.forEach((pro) => {
        expect(pro.length).toBeGreaterThan(15);
      });
    });

    it('GSC-037: Cons are meaningful length', () => {
      gsc.cons.forEach((con) => {
        expect(con.length).toBeGreaterThan(15);
      });
    });

    it('GSC-038: No duplicate pros', () => {
      expect(new Set(gsc.pros).size).toBe(gsc.pros.length);
    });

    it('GSC-039: No duplicate cons', () => {
      expect(new Set(gsc.cons).size).toBe(gsc.cons.length);
    });

    it('GSC-040: Balanced number of pros and cons', () => {
      const ratio = gsc.pros.length / gsc.cons.length;
      expect(ratio).toBeGreaterThan(0.5);
      expect(ratio).toBeLessThan(2);
    });

    it('GSC-041: Pros mention official/Google data', () => {
      const hasOfficial = gsc.pros.some(
        (pro) =>
          pro.toLowerCase().includes('official') ||
          pro.toLowerCase().includes('google') ||
          pro.toLowerCase().includes('direct')
      );
      expect(hasOfficial).toBe(true);
    });

    it('GSC-042: Cons mention learning curve or limitations', () => {
      const hasLimitation = gsc.cons.some(
        (con) =>
          con.toLowerCase().includes('limited') ||
          con.toLowerCase().includes('no') ||
          con.toLowerCase().includes('learning')
      );
      expect(hasLimitation).toBe(true);
    });

    it('GSC-043: Honest about requirements', () => {
      const hasRequirement = gsc.cons.some(
        (con) =>
          con.toLowerCase().includes('requires') ||
          con.toLowerCase().includes('verification')
      );
      expect(hasRequirement).toBe(true);
    });

    it('GSC-044: Pros mention Core Web Vitals', () => {
      const hasCWV = gsc.pros.some((pro) =>
        pro.toLowerCase().includes('core web vitals')
      );
      expect(hasCWV).toBe(true);
    });

    it('GSC-045: Cons mention no backlink analysis', () => {
      const hasNoBacklinks = gsc.cons.some(
        (con) =>
          con.toLowerCase().includes('backlink') ||
          con.toLowerCase().includes('link analysis')
      );
      expect(hasNoBacklinks).toBe(true);
    });
  });

  describe('Use Cases (GSC-046 to GSC-050)', () => {
    it('GSC-046: Use cases have valid audience types', () => {
      gsc.useCases.forEach((uc) => {
        expect(AUDIENCE_TYPES).toContain(uc.audience);
      });
    });

    it('GSC-047: Use cases have valid budget types', () => {
      gsc.useCases.forEach((uc) => {
        expect(BUDGET_TYPES).toContain(uc.budget);
      });
    });

    it('GSC-048: Use case descriptions are meaningful', () => {
      gsc.useCases.forEach((uc) => {
        expect(uc.description.length).toBeGreaterThan(30);
      });
    });

    it('GSC-049: Has index management use case', () => {
      const hasIndexUseCase = gsc.useCases.some(
        (uc) =>
          uc.title.toLowerCase().includes('index') ||
          uc.description.toLowerCase().includes('index')
      );
      expect(hasIndexUseCase).toBe(true);
    });

    it('GSC-050: Source attribution exists', () => {
      expect(gsc.source.length).toBeGreaterThan(5);
    });
  });
});

// ============================================================================
// Google Analytics Tests (GA-001 to GA-050)
// ============================================================================

describe('Google Analytics Competitor Data', () => {
  describe('Structure Validation (GA-001 to GA-015)', () => {
    it('GA-001: exports valid CompetitorData', () => {
      expect(isCompetitorData(ga)).toBe(true);
    });

    it('GA-002: ID is correct', () => {
      expect(ga.id).toBe('ga');
    });

    it('GA-003: Name is correct', () => {
      expect(ga.name).toBe('Google Analytics');
    });

    it('GA-004: Slug is correct', () => {
      expect(ga.slug).toBe('google-analytics');
    });

    it('GA-005: Website is correct', () => {
      expect(ga.website).toBe('https://analytics.google.com');
    });

    it('GA-006: Tagline exists and is meaningful', () => {
      expect(typeof ga.tagline).toBe('string');
      expect(ga.tagline.length).toBeGreaterThan(20);
    });

    it('GA-007: Logo is optional string', () => {
      expect(ga.logo === undefined || typeof ga.logo === 'string').toBe(true);
    });

    it('GA-008: Has pricing tiers', () => {
      expect(ga.pricing.length).toBeGreaterThanOrEqual(1);
    });

    it('GA-009: First pricing tier is Free', () => {
      expect(ga.pricing[0].monthlyPrice).toBe('Free');
      expect(ga.pricing[0].annualPrice).toBe('Free');
    });

    it('GA-010: Free tier has features', () => {
      expect(ga.pricing[0].features.length).toBeGreaterThanOrEqual(5);
    });

    it('GA-011: Has feature categories', () => {
      expect(ga.features.length).toBeGreaterThanOrEqual(4);
    });

    it('GA-012: Has pros', () => {
      expect(ga.pros.length).toBeGreaterThanOrEqual(5);
    });

    it('GA-013: Has cons', () => {
      expect(ga.cons.length).toBeGreaterThanOrEqual(5);
    });

    it('GA-014: Has use cases', () => {
      expect(ga.useCases.length).toBeGreaterThanOrEqual(2);
    });

    it('GA-015: Last updated is valid date', () => {
      expect(() => new Date(ga.lastUpdated)).not.toThrow();
    });
  });

  describe('Traffic Analytics Focus (GA-016 to GA-025)', () => {
    it('GA-016: All pricing is Free', () => {
      const allFree = ga.pricing.every(
        (p) => p.monthlyPrice === 'Free' && p.annualPrice === 'Free'
      );
      expect(allFree).toBe(true);
    });

    it('GA-017: Positioned as traffic analytics (not SEO)', () => {
      const trafficTerms = ['traffic', 'analytics', 'behavior'];
      const hasTrafficFocus = ga.tagline.toLowerCase().includes('traffic');
      expect(hasTrafficFocus).toBe(true);
    });

    it('GA-018: Pros mention traffic/analytics', () => {
      const mentionsTraffic = ga.pros.some(
        (pro) =>
          pro.toLowerCase().includes('traffic') ||
          pro.toLowerCase().includes('analytics')
      );
      expect(mentionsTraffic).toBe(true);
    });

    it('GA-019: Pros mention free', () => {
      const mentionsFree = ga.pros.some((pro) =>
        pro.toLowerCase().includes('free')
      );
      expect(mentionsFree).toBe(true);
    });

    it('GA-020: Cons clearly state not SEO-specific', () => {
      const notSEO = ga.cons.some(
        (con) =>
          con.toLowerCase().includes('not seo') ||
          con.toLowerCase().includes('traffic analytics only')
      );
      expect(notSEO).toBe(true);
    });

    it('GA-021: Cons mention no rank tracking', () => {
      const noRank = ga.cons.some((con) =>
        con.toLowerCase().includes('rank tracking')
      );
      expect(noRank).toBe(true);
    });

    it('GA-022: Cons mention no backlink analysis', () => {
      const noBacklinks = ga.cons.some((con) =>
        con.toLowerCase().includes('backlink')
      );
      expect(noBacklinks).toBe(true);
    });

    it('GA-023: Cons mention no keyword research', () => {
      const noKeywords = ga.cons.some((con) =>
        con.toLowerCase().includes('keyword research')
      );
      expect(noKeywords).toBe(true);
    });

    it('GA-024: Has real-time tracking feature', () => {
      const hasRealTime = ga.features.some((cat) =>
        cat.features.some((f) => f.name.toLowerCase().includes('real-time'))
      );
      expect(hasRealTime).toBe(true);
    });

    it('GA-025: Has Traffic Analytics category', () => {
      const hasTrafficCategory = ga.features.some((cat) =>
        cat.name.toLowerCase().includes('traffic')
      );
      expect(hasTrafficCategory).toBe(true);
    });
  });

  describe('Feature Accuracy (GA-026 to GA-035)', () => {
    it('GA-026: Shows SEOIndicator advantages in features', () => {
      const seoindicatorAdvantages = ga.features.flatMap((cat) =>
        cat.features.filter(
          (f) => f.seoindicator === true && f.competitor === false
        )
      );
      expect(seoindicatorAdvantages.length).toBeGreaterThan(3);
    });

    it('GA-027: Shows GA advantages in features', () => {
      const gaAdvantages = ga.features.flatMap((cat) =>
        cat.features.filter(
          (f) => f.seoindicator === false && f.competitor === true
        )
      );
      expect(gaAdvantages.length).toBeGreaterThan(3);
    });

    it('GA-028: Features are correctly marked', () => {
      ga.features.forEach((cat) => {
        cat.features.forEach((f) => {
          expect([true, false, 'Partial', 'Pro', 'Add-on', 'Enterprise']).toContain(
            f.seoindicator
          );
          expect([true, false, 'Partial', 'Pro', 'Add-on', 'Enterprise']).toContain(
            f.competitor
          );
        });
      });
    });

    it('GA-029: Has goal tracking feature', () => {
      const hasGoals = ga.features.some((cat) =>
        cat.features.some((f) => f.name.toLowerCase().includes('goal'))
      );
      expect(hasGoals).toBe(true);
    });

    it('GA-030: Has audience insights feature', () => {
      const hasAudience = ga.features.some((cat) =>
        cat.features.some((f) => f.name.toLowerCase().includes('audience'))
      );
      expect(hasAudience).toBe(true);
    });

    it('GA-031: No duplicate features within categories', () => {
      ga.features.forEach((cat) => {
        const names = cat.features.map((f) => f.name);
        expect(new Set(names).size).toBe(names.length);
      });
    });

    it('GA-032: Feature categories are meaningful', () => {
      const categories = ga.features.map((f) => f.name);
      expect(categories.length).toBeGreaterThan(3);
    });

    it('GA-033: Shows API access as GA advantage', () => {
      const hasAPI = ga.features.some((cat) =>
        cat.features.some(
          (f) =>
            f.name.toLowerCase().includes('api') &&
            f.competitor === true &&
            f.seoindicator === false
        )
      );
      expect(hasAPI).toBe(true);
    });

    it('GA-034: Free tier features are substantial', () => {
      expect(ga.pricing[0].features.length).toBeGreaterThanOrEqual(7);
    });

    it('GA-035: Has conversion tracking mentioned', () => {
      const hasConversion = ga.pros.some((pro) =>
        pro.toLowerCase().includes('conversion')
      );
      expect(hasConversion).toBe(true);
    });
  });

  describe('Pros and Cons Balance (GA-036 to GA-045)', () => {
    it('GA-036: Pros are meaningful length', () => {
      ga.pros.forEach((pro) => {
        expect(pro.length).toBeGreaterThan(15);
      });
    });

    it('GA-037: Cons are meaningful length', () => {
      ga.cons.forEach((con) => {
        expect(con.length).toBeGreaterThan(15);
      });
    });

    it('GA-038: No duplicate pros', () => {
      expect(new Set(ga.pros).size).toBe(ga.pros.length);
    });

    it('GA-039: No duplicate cons', () => {
      expect(new Set(ga.cons).size).toBe(ga.cons.length);
    });

    it('GA-040: Balanced number of pros and cons', () => {
      const ratio = ga.pros.length / ga.cons.length;
      expect(ratio).toBeGreaterThan(0.5);
      expect(ratio).toBeLessThan(2);
    });

    it('GA-041: Pros mention comprehensive', () => {
      const hasComprehensive = ga.pros.some((pro) =>
        pro.toLowerCase().includes('comprehensive')
      );
      expect(hasComprehensive).toBe(true);
    });

    it('GA-042: Pros mention integration', () => {
      const hasIntegration = ga.pros.some((pro) =>
        pro.toLowerCase().includes('integration')
      );
      expect(hasIntegration).toBe(true);
    });

    it('GA-043: Cons mention privacy considerations', () => {
      const hasPrivacy = ga.cons.some(
        (con) =>
          con.toLowerCase().includes('privacy') ||
          con.toLowerCase().includes('gdpr')
      );
      expect(hasPrivacy).toBe(true);
    });

    it('GA-044: Cons mention tracking code requirement', () => {
      const hasTracking = ga.cons.some(
        (con) =>
          con.toLowerCase().includes('tracking code') ||
          con.toLowerCase().includes('javascript')
      );
      expect(hasTracking).toBe(true);
    });

    it('GA-045: Honest about learning curve', () => {
      const hasLearningCurve = ga.cons.some((con) =>
        con.toLowerCase().includes('learning curve')
      );
      expect(hasLearningCurve).toBe(true);
    });
  });

  describe('Use Cases (GA-046 to GA-050)', () => {
    it('GA-046: Use cases have valid audience types', () => {
      ga.useCases.forEach((uc) => {
        expect(AUDIENCE_TYPES).toContain(uc.audience);
      });
    });

    it('GA-047: Use cases have valid budget types', () => {
      ga.useCases.forEach((uc) => {
        expect(BUDGET_TYPES).toContain(uc.budget);
      });
    });

    it('GA-048: Use case descriptions are meaningful', () => {
      ga.useCases.forEach((uc) => {
        expect(uc.description.length).toBeGreaterThan(30);
      });
    });

    it('GA-049: Has traffic analysis use case', () => {
      const hasTrafficUseCase = ga.useCases.some(
        (uc) =>
          uc.title.toLowerCase().includes('traffic') ||
          uc.description.toLowerCase().includes('traffic')
      );
      expect(hasTrafficUseCase).toBe(true);
    });

    it('GA-050: Source attribution exists', () => {
      expect(ga.source.length).toBeGreaterThan(5);
    });
  });
});

// ============================================================================
// AnswerThePublic Tests (ATP-001 to ATP-050)
// ============================================================================

describe('AnswerThePublic Competitor Data', () => {
  describe('Structure Validation (ATP-001 to ATP-015)', () => {
    it('ATP-001: exports valid CompetitorData', () => {
      expect(isCompetitorData(atp)).toBe(true);
    });

    it('ATP-002: ID is correct', () => {
      expect(atp.id).toBe('answerthepublic');
    });

    it('ATP-003: Name is correct', () => {
      expect(atp.name).toBe('AnswerThePublic');
    });

    it('ATP-004: Slug is correct', () => {
      expect(atp.slug).toBe('answerthepublic');
    });

    it('ATP-005: Website is correct', () => {
      expect(atp.website).toBe('https://answerthepublic.com');
    });

    it('ATP-006: Tagline exists and is meaningful', () => {
      expect(typeof atp.tagline).toBe('string');
      expect(atp.tagline.length).toBeGreaterThan(20);
    });

    it('ATP-007: Logo is optional string', () => {
      expect(atp.logo === undefined || typeof atp.logo === 'string').toBe(true);
    });

    it('ATP-008: Has pricing tiers', () => {
      expect(atp.pricing.length).toBeGreaterThanOrEqual(2);
    });

    it('ATP-009: First pricing tier is Free', () => {
      expect(atp.pricing[0].monthlyPrice).toBe('Free');
      expect(atp.pricing[0].annualPrice).toBe('Free');
    });

    it('ATP-010: Has Pro tier with numeric price', () => {
      const proTier = atp.pricing.find((p) => p.name === 'Pro');
      expect(proTier).toBeDefined();
      expect(typeof proTier!.monthlyPrice).toBe('number');
    });

    it('ATP-011: Pro tier price is around $99/month', () => {
      const proTier = atp.pricing.find((p) => p.name === 'Pro');
      expect(proTier!.monthlyPrice).toBeGreaterThanOrEqual(79);
      expect(proTier!.monthlyPrice).toBeLessThanOrEqual(129);
    });

    it('ATP-012: Has feature categories', () => {
      expect(atp.features.length).toBeGreaterThanOrEqual(4);
    });

    it('ATP-013: Has pros', () => {
      expect(atp.pros.length).toBeGreaterThanOrEqual(5);
    });

    it('ATP-014: Has cons', () => {
      expect(atp.cons.length).toBeGreaterThanOrEqual(5);
    });

    it('ATP-015: Last updated is valid date', () => {
      expect(() => new Date(atp.lastUpdated)).not.toThrow();
    });
  });

  describe('Free Tier Limitations (ATP-016 to ATP-025)', () => {
    it('ATP-016: Free tier mentions search limit', () => {
      const freeTierFeatures = atp.pricing[0].features;
      const hasLimit = freeTierFeatures.some(
        (f) => f.toLowerCase().includes('3') || f.toLowerCase().includes('search')
      );
      expect(hasLimit).toBe(true);
    });

    it('ATP-017: Free tier search limit is 3/day', () => {
      const has3Searches = atp.pricing[0].features.some(
        (f) => f.toLowerCase().includes('3 search')
      );
      expect(has3Searches).toBe(true);
    });

    it('ATP-018: Pros mention free tier', () => {
      const mentionsFree = atp.pros.some((pro) =>
        pro.toLowerCase().includes('free')
      );
      expect(mentionsFree).toBe(true);
    });

    it('ATP-019: Pros mention visual/visualization', () => {
      const mentionsVisual = atp.pros.some(
        (pro) =>
          pro.toLowerCase().includes('visual') ||
          pro.toLowerCase().includes('visualization')
      );
      expect(mentionsVisual).toBe(true);
    });

    it('ATP-020: Pros mention content brainstorming', () => {
      const mentionsBrainstorm = atp.pros.some(
        (pro) =>
          pro.toLowerCase().includes('brainstorm') ||
          pro.toLowerCase().includes('content')
      );
      expect(mentionsBrainstorm).toBe(true);
    });

    it('ATP-021: Cons mention limited free tier', () => {
      const mentionsLimit = atp.cons.some(
        (con) =>
          con.toLowerCase().includes('limited') ||
          con.toLowerCase().includes('3 search')
      );
      expect(mentionsLimit).toBe(true);
    });

    it('ATP-022: Cons mention no SEO analysis', () => {
      const noSEO = atp.cons.some(
        (con) =>
          con.toLowerCase().includes('no seo') ||
          con.toLowerCase().includes('seo analysis')
      );
      expect(noSEO).toBe(true);
    });

    it('ATP-023: Cons mention no rank tracking', () => {
      const noRank = atp.cons.some((con) =>
        con.toLowerCase().includes('rank tracking')
      );
      expect(noRank).toBe(true);
    });

    it('ATP-024: Cons mention expensive Pro tier', () => {
      const mentionsPrice = atp.cons.some(
        (con) =>
          con.toLowerCase().includes('expensive') ||
          con.toLowerCase().includes('$99')
      );
      expect(mentionsPrice).toBe(true);
    });

    it('ATP-025: Cons mention no backlink data', () => {
      const noBacklinks = atp.cons.some((con) =>
        con.toLowerCase().includes('backlink')
      );
      expect(noBacklinks).toBe(true);
    });
  });

  describe('Feature Accuracy (ATP-026 to ATP-035)', () => {
    it('ATP-026: Has keyword research category', () => {
      const hasKeywordCategory = atp.features.some((cat) =>
        cat.name.toLowerCase().includes('keyword')
      );
      expect(hasKeywordCategory).toBe(true);
    });

    it('ATP-027: Has question keywords feature', () => {
      const hasQuestions = atp.features.some((cat) =>
        cat.features.some((f) => f.name.toLowerCase().includes('question'))
      );
      expect(hasQuestions).toBe(true);
    });

    it('ATP-028: Shows SEOIndicator advantages in features', () => {
      const seoindicatorAdvantages = atp.features.flatMap((cat) =>
        cat.features.filter(
          (f) => f.seoindicator === true && f.competitor === false
        )
      );
      expect(seoindicatorAdvantages.length).toBeGreaterThan(5);
    });

    it('ATP-029: Shows ATP advantages in features', () => {
      const atpAdvantages = atp.features.flatMap((cat) =>
        cat.features.filter(
          (f) => f.seoindicator === false && f.competitor === true
        )
      );
      expect(atpAdvantages.length).toBeGreaterThan(2);
    });

    it('ATP-030: Visual keyword map is ATP advantage', () => {
      const hasVisualMap = atp.features.some((cat) =>
        cat.features.some(
          (f) =>
            f.name.toLowerCase().includes('visual') &&
            f.competitor === true &&
            f.seoindicator === false
        )
      );
      expect(hasVisualMap).toBe(true);
    });

    it('ATP-031: Features are correctly marked', () => {
      atp.features.forEach((cat) => {
        cat.features.forEach((f) => {
          expect([true, false, 'Partial', 'Pro', 'Add-on', 'Enterprise']).toContain(
            f.seoindicator
          );
          expect([true, false, 'Partial', 'Pro', 'Add-on', 'Enterprise']).toContain(
            f.competitor
          );
        });
      });
    });

    it('ATP-032: Has content ideas category', () => {
      const hasContentCategory = atp.features.some((cat) =>
        cat.name.toLowerCase().includes('content')
      );
      expect(hasContentCategory).toBe(true);
    });

    it('ATP-033: No duplicate features within categories', () => {
      atp.features.forEach((cat) => {
        const names = cat.features.map((f) => f.name);
        expect(new Set(names).size).toBe(names.length);
      });
    });

    it('ATP-034: SEO Analysis category shows SEOIndicator dominance', () => {
      const seoCategory = atp.features.find((cat) =>
        cat.name.toLowerCase().includes('seo analysis')
      );
      expect(seoCategory).toBeDefined();
      const seoIndicatorWins = seoCategory!.features.filter(
        (f) => f.seoindicator === true && f.competitor === false
      );
      expect(seoIndicatorWins.length).toBe(4);
    });

    it('ATP-035: CSV Export marked as Pro feature', () => {
      const csvExport = atp.features
        .flatMap((cat) => cat.features)
        .find((f) => f.name.toLowerCase().includes('csv'));
      expect(csvExport).toBeDefined();
      expect(csvExport!.competitor).toBe('Pro');
    });
  });

  describe('Pros and Cons Balance (ATP-036 to ATP-045)', () => {
    it('ATP-036: Pros are meaningful length', () => {
      atp.pros.forEach((pro) => {
        expect(pro.length).toBeGreaterThan(15);
      });
    });

    it('ATP-037: Cons are meaningful length', () => {
      atp.cons.forEach((con) => {
        expect(con.length).toBeGreaterThan(15);
      });
    });

    it('ATP-038: No duplicate pros', () => {
      expect(new Set(atp.pros).size).toBe(atp.pros.length);
    });

    it('ATP-039: No duplicate cons', () => {
      expect(new Set(atp.cons).size).toBe(atp.cons.length);
    });

    it('ATP-040: Balanced number of pros and cons', () => {
      const ratio = atp.pros.length / atp.cons.length;
      expect(ratio).toBeGreaterThan(0.5);
      expect(ratio).toBeLessThan(2);
    });

    it('ATP-041: Pros mention question keywords', () => {
      const hasQuestions = atp.pros.some(
        (pro) =>
          pro.toLowerCase().includes('question') ||
          pro.toLowerCase().includes('faq')
      );
      expect(hasQuestions).toBe(true);
    });

    it('ATP-042: Pros mention ease of use', () => {
      const hasEasy = atp.pros.some(
        (pro) =>
          pro.toLowerCase().includes('easy') ||
          pro.toLowerCase().includes('intuitive')
      );
      expect(hasEasy).toBe(true);
    });

    it('ATP-043: Cons mention no search volume', () => {
      const noVolume = atp.cons.some(
        (con) =>
          con.toLowerCase().includes('search volume') ||
          con.toLowerCase().includes('volume')
      );
      expect(noVolume).toBe(true);
    });

    it('ATP-044: Cons mention no competitor analysis', () => {
      const noCompetitor = atp.cons.some((con) =>
        con.toLowerCase().includes('competitor')
      );
      expect(noCompetitor).toBe(true);
    });

    it('ATP-045: Cons mention no site audit', () => {
      const noAudit = atp.cons.some(
        (con) =>
          con.toLowerCase().includes('site audit') ||
          con.toLowerCase().includes('audit')
      );
      expect(noAudit).toBe(true);
    });
  });

  describe('Use Cases (ATP-046 to ATP-050)', () => {
    it('ATP-046: Use cases have valid audience types', () => {
      atp.useCases.forEach((uc) => {
        expect(AUDIENCE_TYPES).toContain(uc.audience);
      });
    });

    it('ATP-047: Use cases have valid budget types', () => {
      atp.useCases.forEach((uc) => {
        expect(BUDGET_TYPES).toContain(uc.budget);
      });
    });

    it('ATP-048: Use case descriptions are meaningful', () => {
      atp.useCases.forEach((uc) => {
        expect(uc.description.length).toBeGreaterThan(30);
      });
    });

    it('ATP-049: Has content ideation use case', () => {
      const hasContentUseCase = atp.useCases.some(
        (uc) =>
          uc.title.toLowerCase().includes('content') ||
          uc.description.toLowerCase().includes('content')
      );
      expect(hasContentUseCase).toBe(true);
    });

    it('ATP-050: Source attribution exists', () => {
      expect(atp.source.length).toBeGreaterThan(5);
    });
  });
});

// ============================================================================
// Cross-Competitor Consistency Tests (CC-001 to CC-025)
// ============================================================================

describe('Free Alternatives Cross-Competitor Consistency', () => {
  const competitors = [gsc, ga, atp];

  describe('Structure Consistency (CC-001 to CC-010)', () => {
    it('CC-001: All 3 competitors are valid', () => {
      competitors.forEach((c) => {
        expect(isCompetitorData(c)).toBe(true);
      });
    });

    it('CC-002: Unique IDs', () => {
      const ids = competitors.map((c) => c.id);
      expect(new Set(ids).size).toBe(3);
    });

    it('CC-003: Unique slugs', () => {
      const slugs = competitors.map((c) => c.slug);
      expect(new Set(slugs).size).toBe(3);
    });

    it('CC-004: Unique names', () => {
      const names = competitors.map((c) => c.name);
      expect(new Set(names).size).toBe(3);
    });

    it('CC-005: All have free pricing', () => {
      competitors.forEach((c) => {
        expect(c.pricing[0].monthlyPrice).toBe('Free');
        expect(c.pricing[0].annualPrice).toBe('Free');
      });
    });

    it('CC-006: All have valid lastUpdated dates', () => {
      competitors.forEach((c) => {
        expect(() => new Date(c.lastUpdated)).not.toThrow();
      });
    });

    it('CC-007: All have source attribution', () => {
      competitors.forEach((c) => {
        expect(c.source.length).toBeGreaterThan(5);
      });
    });

    it('CC-008: All have pros and cons', () => {
      competitors.forEach((c) => {
        expect(c.pros.length).toBeGreaterThanOrEqual(5);
        expect(c.cons.length).toBeGreaterThanOrEqual(5);
      });
    });

    it('CC-009: All have use cases', () => {
      competitors.forEach((c) => {
        expect(c.useCases.length).toBeGreaterThanOrEqual(2);
      });
    });

    it('CC-010: All have feature categories', () => {
      competitors.forEach((c) => {
        expect(c.features.length).toBeGreaterThanOrEqual(4);
      });
    });
  });

  describe('Honest Positioning (CC-011 to CC-018)', () => {
    it('CC-011: All mention "free" in pros', () => {
      competitors.forEach((c) => {
        const mentionsFree = c.pros.some((pro) =>
          pro.toLowerCase().includes('free')
        );
        expect(mentionsFree).toBe(true);
      });
    });

    it('CC-012: All have honest limitations in cons', () => {
      competitors.forEach((c) => {
        const hasLimitations = c.cons.some(
          (con) =>
            con.toLowerCase().includes('no') ||
            con.toLowerCase().includes('limited') ||
            con.toLowerCase().includes('not')
        );
        expect(hasLimitations).toBe(true);
      });
    });

    it('CC-013: All use cases include free budget option', () => {
      const hasFreeBudget = competitors.some((c) =>
        c.useCases.some((uc) => uc.budget === 'free')
      );
      expect(hasFreeBudget).toBe(true);
    });

    it('CC-014: SEOIndicator advantages clearly shown', () => {
      competitors.forEach((c) => {
        const seoindicatorAdvantages = c.features.flatMap((cat) =>
          cat.features.filter(
            (f) => f.seoindicator === true && f.competitor === false
          )
        );
        expect(seoindicatorAdvantages.length).toBeGreaterThan(3);
      });
    });

    it('CC-015: Competitor advantages acknowledged', () => {
      competitors.forEach((c) => {
        const competitorAdvantages = c.features.flatMap((cat) =>
          cat.features.filter(
            (f) => f.seoindicator === false && f.competitor === true
          )
        );
        expect(competitorAdvantages.length).toBeGreaterThan(2);
      });
    });

    it('CC-016: GSC positioned as essential (complement)', () => {
      const hasEssential = gsc.pros.some(
        (pro) =>
          pro.toLowerCase().includes('essential') ||
          pro.toLowerCase().includes('official')
      );
      expect(hasEssential).toBe(true);
    });

    it('CC-017: GA positioned as traffic analytics (not SEO)', () => {
      const hasTrafficFocus = ga.cons.some(
        (con) =>
          con.toLowerCase().includes('not seo') ||
          con.toLowerCase().includes('traffic analytics')
      );
      expect(hasTrafficFocus).toBe(true);
    });

    it('CC-018: ATP positioned for keyword research only', () => {
      const hasKeywordFocus = atp.cons.some(
        (con) =>
          con.toLowerCase().includes('no seo') ||
          con.toLowerCase().includes('seo analysis')
      );
      expect(hasKeywordFocus).toBe(true);
    });
  });

  describe('SEOIndicator Value Proposition (CC-019 to CC-025)', () => {
    it('CC-019: All show SEOIndicator has site audit', () => {
      competitors.forEach((c) => {
        const hasSiteAudit = c.features.some((cat) =>
          cat.features.some(
            (f) =>
              f.name.toLowerCase().includes('site audit') &&
              f.seoindicator === true
          )
        );
        expect(hasSiteAudit).toBe(true);
      });
    });

    it('CC-020: All show SEOIndicator has backlink analysis', () => {
      competitors.forEach((c) => {
        const hasBacklinks = c.features.some((cat) =>
          cat.features.some(
            (f) =>
              (f.name.toLowerCase().includes('backlink') ||
                f.name.toLowerCase().includes('link')) &&
              f.seoindicator === true
          )
        );
        expect(hasBacklinks).toBe(true);
      });
    });

    it('CC-021: All show SEOIndicator has keyword research', () => {
      competitors.forEach((c) => {
        const hasKeywords = c.features.some((cat) =>
          cat.features.some(
            (f) =>
              f.name.toLowerCase().includes('keyword') &&
              f.seoindicator === true
          )
        );
        expect(hasKeywords).toBe(true);
      });
    });

    it('CC-022: All show SEOIndicator has rank tracking', () => {
      competitors.forEach((c) => {
        const hasRankTracking = c.features.some((cat) =>
          cat.features.some(
            (f) =>
              f.name.toLowerCase().includes('rank') &&
              f.seoindicator === true
          )
        );
        expect(hasRankTracking).toBe(true);
      });
    });

    it('CC-023: GSC shows SEOIndicator complementarity', () => {
      // GSC has unique advantages, SEOIndicator fills gaps
      const gscUnique = gsc.features.flatMap((cat) =>
        cat.features.filter(
          (f) => f.seoindicator === false && f.competitor === true
        )
      );
      const seoindicatorFills = gsc.features.flatMap((cat) =>
        cat.features.filter(
          (f) => f.seoindicator === true && f.competitor === false
        )
      );
      expect(gscUnique.length).toBeGreaterThan(2);
      expect(seoindicatorFills.length).toBeGreaterThan(5);
    });

    it('CC-024: Consistent feature naming across competitors', () => {
      const allFeatureNames = competitors.flatMap((c) =>
        c.features.flatMap((cat) => cat.features.map((f) => f.name.toLowerCase()))
      );
      // Check for common feature names
      expect(
        allFeatureNames.filter((n) => n.includes('keyword')).length
      ).toBeGreaterThan(2);
    });

    it('CC-025: All have Pricing & Access category', () => {
      competitors.forEach((c) => {
        const hasPricingCategory = c.features.some((cat) =>
          cat.name.toLowerCase().includes('pricing')
        );
        expect(hasPricingCategory).toBe(true);
      });
    });
  });
});

// ============================================================================
// Edge Cases and Validation Tests (EC-001 to EC-025)
// ============================================================================

describe('Free Alternatives Edge Cases', () => {
  describe('Data Integrity (EC-001 to EC-010)', () => {
    it('EC-001: No empty pros arrays', () => {
      expect(gsc.pros.length).toBeGreaterThan(0);
      expect(ga.pros.length).toBeGreaterThan(0);
      expect(atp.pros.length).toBeGreaterThan(0);
    });

    it('EC-002: No empty cons arrays', () => {
      expect(gsc.cons.length).toBeGreaterThan(0);
      expect(ga.cons.length).toBeGreaterThan(0);
      expect(atp.cons.length).toBeGreaterThan(0);
    });

    it('EC-003: No empty pricing features', () => {
      [gsc, ga, atp].forEach((c) => {
        c.pricing.forEach((p) => {
          expect(p.features.length).toBeGreaterThan(0);
        });
      });
    });

    it('EC-004: No empty feature categories', () => {
      [gsc, ga, atp].forEach((c) => {
        c.features.forEach((cat) => {
          expect(cat.features.length).toBeGreaterThan(0);
        });
      });
    });

    it('EC-005: No whitespace-only fields', () => {
      [gsc, ga, atp].forEach((c) => {
        expect(c.name.trim()).toBe(c.name);
        expect(c.slug.trim()).toBe(c.slug);
        expect(c.tagline.trim()).toBe(c.tagline);
      });
    });

    it('EC-006: Valid website URLs', () => {
      [gsc, ga, atp].forEach((c) => {
        expect(c.website).toMatch(/^https:\/\//);
      });
    });

    it('EC-007: No future dates in lastUpdated', () => {
      const now = new Date();
      [gsc, ga, atp].forEach((c) => {
        expect(new Date(c.lastUpdated) <= now).toBe(true);
      });
    });

    it('EC-008: No negative prices', () => {
      [gsc, ga, atp].forEach((c) => {
        c.pricing.forEach((p) => {
          if (typeof p.monthlyPrice === 'number') {
            expect(p.monthlyPrice).toBeGreaterThanOrEqual(0);
          }
          if (typeof p.annualPrice === 'number') {
            expect(p.annualPrice).toBeGreaterThanOrEqual(0);
          }
        });
      });
    });

    it('EC-009: Pro tier annual price less than monthly', () => {
      const atpProTier = atp.pricing.find((p) => p.name === 'Pro');
      if (atpProTier && typeof atpProTier.monthlyPrice === 'number') {
        expect(atpProTier.annualPrice).toBeLessThan(atpProTier.monthlyPrice);
      }
    });

    it('EC-010: All required fields present', () => {
      const requiredFields = [
        'id',
        'name',
        'slug',
        'website',
        'tagline',
        'pricing',
        'features',
        'pros',
        'cons',
        'useCases',
        'lastUpdated',
        'source',
      ];
      [gsc, ga, atp].forEach((c) => {
        requiredFields.forEach((field) => {
          expect(c).toHaveProperty(field);
        });
      });
    });
  });

  describe('Feature Validation (EC-011 to EC-018)', () => {
    it('EC-011: No duplicate feature names across categories', () => {
      [gsc, ga, atp].forEach((c) => {
        const allNames = c.features.flatMap((cat) =>
          cat.features.map((f) => f.name)
        );
        // Allow some duplicates across categories (e.g., "Free Tier")
        const uniqueCount = new Set(allNames).size;
        const tolerance = Math.floor(allNames.length * 0.2); // 20% tolerance
        expect(uniqueCount).toBeGreaterThanOrEqual(allNames.length - tolerance);
      });
    });

    it('EC-012: Feature values are valid types', () => {
      [gsc, ga, atp].forEach((c) => {
        c.features.forEach((cat) => {
          cat.features.forEach((f) => {
            expect([
              true,
              false,
              'Partial',
              'Pro',
              'Add-on',
              'Enterprise',
              'Limited',
              '3/day',
              '17%',
            ]).toContainEqual(expect.objectContaining({}).constructor(f.seoindicator)
              ? f.seoindicator
              : f.seoindicator);
          });
        });
      });
    });

    it('EC-013: Use case audience types are valid', () => {
      [gsc, ga, atp].forEach((c) => {
        c.useCases.forEach((uc) => {
          expect(AUDIENCE_TYPES).toContain(uc.audience);
        });
      });
    });

    it('EC-014: Use case budget types are valid', () => {
      [gsc, ga, atp].forEach((c) => {
        c.useCases.forEach((uc) => {
          expect(BUDGET_TYPES).toContain(uc.budget);
        });
      });
    });

    it('EC-015: Feature categories have unique names', () => {
      [gsc, ga, atp].forEach((c) => {
        const categoryNames = c.features.map((cat) => cat.name);
        expect(new Set(categoryNames).size).toBe(categoryNames.length);
      });
    });

    it('EC-016: Pro tier exists for AnswerThePublic', () => {
      const hasProTier = atp.pricing.some((p) => p.name === 'Pro');
      expect(hasProTier).toBe(true);
    });

    it('EC-017: Free tier features are substantive', () => {
      [gsc, ga, atp].forEach((c) => {
        expect(c.pricing[0].features.length).toBeGreaterThanOrEqual(5);
      });
    });

    it('EC-018: Source URLs are domain-based', () => {
      [gsc, ga, atp].forEach((c) => {
        // Source should be a domain, not a full URL typically
        expect(c.source).not.toContain('://');
      });
    });
  });

  describe('Special Cases (EC-019 to EC-025)', () => {
    it('EC-019: GSC has only Free tier (no paid tiers)', () => {
      expect(gsc.pricing.length).toBe(1);
      expect(gsc.pricing[0].name).toBe('Free');
    });

    it('EC-020: GA has only Free tier (no paid tiers)', () => {
      expect(ga.pricing.length).toBe(1);
      expect(ga.pricing[0].name).toBe('Free');
    });

    it('EC-021: ATP has both Free and Pro tiers', () => {
      expect(atp.pricing.length).toBe(2);
    });

    it('EC-022: GSC has API access mentioned in pros', () => {
      const hasAPI = gsc.pros.some((pro) =>
        pro.toLowerCase().includes('api')
      );
      expect(hasAPI).toBe(true);
    });

    it('EC-023: GA has API access mentioned in pros', () => {
      const hasAPI = ga.pros.some((pro) =>
        pro.toLowerCase().includes('api')
      );
      expect(hasAPI).toBe(true);
    });

    it('EC-024: ATP mentions free tier limitations in cons', () => {
      const hasLimitMention = atp.cons.some(
        (con) =>
          con.toLowerCase().includes('limited') &&
          con.toLowerCase().includes('free')
      );
      expect(hasLimitMention).toBe(true);
    });

    it('EC-025: All competitors reference Google where applicable', () => {
      const gscMentionsGoogle = gsc.pros.some((pro) =>
        pro.toLowerCase().includes('google')
      );
      const gaMentionsGoogle = ga.pros.some((pro) =>
        pro.toLowerCase().includes('google')
      );
      expect(gscMentionsGoogle).toBe(true);
      expect(gaMentionsGoogle).toBe(true);
    });
  });
});

// ============================================================================
// Acceptance Criteria Tests (AC-001 to AC-010)
// ============================================================================

describe('TASK-32-14 Acceptance Criteria', () => {
  it('AC-1: All 3 free alternative files are valid CompetitorData', () => {
    expect(isCompetitorData(gsc)).toBe(true);
    expect(isCompetitorData(ga)).toBe(true);
    expect(isCompetitorData(atp)).toBe(true);
  });

  it('AC-2: Accurate feature comparison exists', () => {
    // Each competitor has feature categories with comparisons
    [gsc, ga, atp].forEach((c) => {
      expect(c.features.length).toBeGreaterThanOrEqual(4);
      c.features.forEach((cat) => {
        expect(cat.features.length).toBeGreaterThan(0);
        cat.features.forEach((f) => {
          expect(f).toHaveProperty('name');
          expect(f).toHaveProperty('seoindicator');
          expect(f).toHaveProperty('competitor');
        });
      });
    });
  });

  it('AC-3: Honest pros/cons for free tools', () => {
    // All competitors have pros acknowledging free nature
    [gsc, ga, atp].forEach((c) => {
      const mentionsFree = c.pros.some((pro) =>
        pro.toLowerCase().includes('free')
      );
      expect(mentionsFree).toBe(true);
    });

    // All competitors have cons acknowledging limitations
    [gsc, ga, atp].forEach((c) => {
      const hasLimitations = c.cons.some(
        (con) =>
          con.toLowerCase().includes('no') ||
          con.toLowerCase().includes('limited') ||
          con.toLowerCase().includes('not')
      );
      expect(hasLimitations).toBe(true);
    });
  });

  it('AC-4: Clear positioning of SEOIndicator advantage', () => {
    // Each competitor shows SEOIndicator has more SEO capabilities
    [gsc, ga, atp].forEach((c) => {
      const seoindicatorAdvantages = c.features.flatMap((cat) =>
        cat.features.filter(
          (f) => f.seoindicator === true && f.competitor === false
        )
      );
      expect(seoindicatorAdvantages.length).toBeGreaterThan(5);
    });
  });

  it('AC-5: Use cases show when to use free vs SEOIndicator', () => {
    // All competitors have use cases targeting free budget
    const hasFreeBudgetUseCase = [gsc, ga, atp].every((c) =>
      c.useCases.some((uc) => uc.budget === 'free')
    );
    expect(hasFreeBudgetUseCase).toBe(true);

    // Use cases have meaningful descriptions
    [gsc, ga, atp].forEach((c) => {
      c.useCases.forEach((uc) => {
        expect(uc.description.length).toBeGreaterThan(30);
      });
    });
  });

  it('AC-6: GSC positioned as complement (not replacement)', () => {
    // GSC pros mention essential/official nature
    const hasEssential = gsc.pros.some(
      (pro) =>
        pro.toLowerCase().includes('essential') ||
        pro.toLowerCase().includes('official') ||
        pro.toLowerCase().includes('direct')
    );
    expect(hasEssential).toBe(true);

    // GSC cons acknowledge it's not a full SEO tool
    const hasSEOLimitation = gsc.cons.some(
      (con) =>
        con.toLowerCase().includes('limited seo') ||
        con.toLowerCase().includes('no competitor') ||
        con.toLowerCase().includes('basic keyword')
    );
    expect(hasSEOLimitation).toBe(true);
  });

  it('AC-7: GA positioned as traffic analytics (not SEO-specific)', () => {
    // GA tagline mentions traffic analytics
    expect(
      ga.tagline.toLowerCase().includes('traffic') ||
        ga.tagline.toLowerCase().includes('analytics')
    ).toBe(true);

    // GA cons clearly state not SEO-specific
    const notSEO = ga.cons.some(
      (con) =>
        con.toLowerCase().includes('not seo') ||
        con.toLowerCase().includes('traffic analytics only')
    );
    expect(notSEO).toBe(true);
  });

  it('AC-8: ATP positioned with limited free tier and expensive Pro', () => {
    // Free tier has 3 searches limit
    const has3SearchLimit = atp.pricing[0].features.some((f) =>
      f.toLowerCase().includes('3 search')
    );
    expect(has3SearchLimit).toBe(true);

    // Cons mention limited free tier
    const mentionsLimit = atp.cons.some(
      (con) =>
        con.toLowerCase().includes('limited') &&
        con.toLowerCase().includes('free')
    );
    expect(mentionsLimit).toBe(true);

    // Cons mention expensive Pro tier
    const mentionsExpensive = atp.cons.some(
      (con) =>
        con.toLowerCase().includes('expensive') || con.toLowerCase().includes('$99')
    );
    expect(mentionsExpensive).toBe(true);
  });

  it('AC-9: All files have correct data structure', () => {
    const requiredFields = [
      'id',
      'name',
      'slug',
      'website',
      'tagline',
      'pricing',
      'features',
      'pros',
      'cons',
      'useCases',
      'lastUpdated',
      'source',
    ];

    [gsc, ga, atp].forEach((c) => {
      requiredFields.forEach((field) => {
        expect(c).toHaveProperty(field);
      });
    });
  });

  it('AC-10: Data is fresh and source attributed', () => {
    [gsc, ga, atp].forEach((c) => {
      // lastUpdated should be a valid date
      expect(() => new Date(c.lastUpdated)).not.toThrow();

      // source should exist
      expect(c.source.length).toBeGreaterThan(5);
    });
  });
});
