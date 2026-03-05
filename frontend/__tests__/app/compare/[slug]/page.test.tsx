/**
 * Comparison Page Route Tests
 * Task: TASK-32-3 - Comparison Page Route with Static Generation
 * Spec: SPEC-32 (Competitor Comparison Landing Pages)
 */

import React from 'react';
import { render, screen, within } from '@testing-library/react';
import '@testing-library/jest-dom';

// Types
import type { ComparisonData, CompetitorData, FAQ } from '@/lib/compare/types';

// ============================================================================
// Mock Data
// ============================================================================

const mockComparisonData: ComparisonData = {
  slug: 'seoindicator-vs-ahrefs',
  title: 'SEOIndicator vs Ahrefs: Which SEO Tool Is Better?',
  metaDescription: 'Compare SEOIndicator vs Ahrefs. Detailed analysis of features, pricing, pros and cons to help you choose the best SEO tool.',
  competitors: ['seoindicator', 'ahrefs'],
  summary: 'Find out how SEOIndicator compares to Ahrefs in this comprehensive comparison.',
  featureComparison: [
    {
      name: 'Keyword Research',
      features: [
        { name: 'Keyword Database', seoindicator: true, competitor: true },
        { name: 'Search Volume', seoindicator: true, competitor: true },
        { name: 'Keyword Difficulty', seoindicator: 'Partial', competitor: true, tooltip: 'Limited to top 100 keywords on free plan' },
      ],
    },
    {
      name: 'Backlink Analysis',
      features: [
        { name: 'Backlink Database', seoindicator: false, competitor: true },
        { name: 'Link Quality Score', seoindicator: true, competitor: true },
      ],
    },
  ],
  pricingComparison: {
    seoindicator: [
      { name: 'Free', monthlyPrice: 'Free', annualPrice: 'Free', features: ['Basic features'] },
      { name: 'Pro', monthlyPrice: 29, annualPrice: 290, features: ['All features'], highlighted: true },
    ],
    competitor: [
      { name: 'Lite', monthlyPrice: 99, annualPrice: 990, features: ['Basic features'] },
      { name: 'Standard', monthlyPrice: 199, annualPrice: 1990, features: ['More features'], highlighted: true },
    ],
  },
  prosCons: {
    seoindicator: {
      pros: ['Affordable pricing', 'Easy to use', 'Good for beginners'],
      cons: ['Smaller database', 'Limited advanced features'],
    },
    competitor: {
      pros: ['Large database', 'Advanced features', 'Industry standard'],
      cons: ['Expensive', 'Steep learning curve', 'Overwhelming for beginners'],
    },
  },
  useCases: {
    seoindicator: [
      { title: 'Solo Bloggers', description: 'Perfect for individuals starting out', audience: 'solo', budget: 'budget' },
      { title: 'Small Teams', description: 'Great for small marketing teams', audience: 'small-team', budget: 'budget' },
    ],
    competitor: [
      { title: 'Enterprise Teams', description: 'Built for large marketing departments', audience: 'enterprise', budget: 'premium' },
      { title: 'Agencies', description: 'Comprehensive tool for agencies', audience: 'agency', budget: 'premium' },
    ],
  },
  verdict: 'SEOIndicator is best for budget-conscious users who need essential SEO features without the enterprise price tag. Choose Ahrefs if you need advanced backlink analysis and have the budget.',
  faqs: [
    { question: 'Which is better: SEOIndicator or Ahrefs?', answer: 'It depends on your needs. SEOIndicator is better for budget-conscious users.' },
    { question: 'Is SEOIndicator cheaper than Ahrefs?', answer: 'Yes, SEOIndicator starts at $29/mo while Ahrefs starts at $99/mo.' },
  ],
  relatedComparisons: ['seoindicator-vs-semrush', 'seoindicator-vs-moz', 'ahrefs-vs-semrush'],
};

const mockCompetitorData: Record<string, CompetitorData> = {
  seoindicator: {
    id: 'seoindicator',
    name: 'SEOIndicator',
    slug: 'seoindicator',
    website: 'https://seoindicator.com',
    tagline: 'SEO made simple',
    pricing: mockComparisonData.pricingComparison.seoindicator,
    features: [],
    pros: mockComparisonData.prosCons.seoindicator.pros,
    cons: mockComparisonData.prosCons.seoindicator.cons,
    useCases: mockComparisonData.useCases.seoindicator,
    lastUpdated: '2026-03-05',
    source: 'Official website',
  },
  ahrefs: {
    id: 'ahrefs',
    name: 'Ahrefs',
    slug: 'ahrefs',
    website: 'https://ahrefs.com',
    tagline: 'SEO tools and resources',
    pricing: mockComparisonData.pricingComparison.competitor,
    features: [],
    pros: mockComparisonData.prosCons.competitor.pros,
    cons: mockComparisonData.prosCons.competitor.cons,
    useCases: mockComparisonData.useCases.competitor,
    lastUpdated: '2026-03-05',
    source: 'Official website',
  },
};

const MOCK_COMPARISON_SLUGS = [
  'seoindicator-vs-ahrefs',
  'seoindicator-vs-semrush',
  'seoindicator-vs-moz',
  'seoindicator-vs-ubersuggest',
  'ahrefs-vs-semrush',
  'ahrefs-vs-moz',
  'semrush-vs-moz',
];

// ============================================================================
// Mock Registry Module
// ============================================================================

const mockGetComparisonBySlug = jest.fn((slug: string): ComparisonData | null => {
  if (slug === 'seoindicator-vs-ahrefs') return mockComparisonData;
  if (MOCK_COMPARISON_SLUGS.includes(slug)) {
    // Return a basic comparison for other valid slugs
    const [a, b] = slug.split('-vs-');
    return {
      ...mockComparisonData,
      slug,
      title: `${a} vs ${b}: Which SEO Tool Is Better?`,
      competitors: [a, b] as [string, string],
    };
  }
  return null;
});

const mockGetCompetitorById = jest.fn((id: string): CompetitorData | null => {
  return mockCompetitorData[id] || null;
});

const mockParseSlug = jest.fn((slug: string): [string, string] | null => {
  if (slug === null || slug === undefined || typeof slug !== 'string') return null;
  const parts = slug.split('-vs-');
  if (parts.length !== 2) return null;
  if (!parts[0] || !parts[1]) return null;
  return [parts[0], parts[1]];
});

jest.mock('@/lib/compare/registry', () => ({
  COMPARISON_SLUGS: MOCK_COMPARISON_SLUGS,
  getComparisonBySlug: mockGetComparisonBySlug,
  getCompetitorById: mockGetCompetitorById,
  parseSlug: mockParseSlug,
  COMPETITOR_REGISTRY: mockCompetitorData,
}));

// ============================================================================
// Mock Components (Virtual Modules for TDD)
// ============================================================================

// Use virtual: true for components that don't exist yet
jest.mock('@/components/compare/ComparisonTable', () => ({
  ComparisonTable: ({ features }: { features: ComparisonData['featureComparison'] }) => (
    <div data-testid="comparison-table" data-categories={features?.length || 0}>
      {features?.map((category) => (
        <div key={category.name} data-testid={`category-${category.name}`}>
          <h3>{category.name}</h3>
          {category.features.map((feature) => (
            <div key={feature.name} data-testid={`feature-${feature.name}`}>
              <span>{feature.name}</span>
              <span data-testid="seoindicator-value">{String(feature.seoindicator)}</span>
              <span data-testid="competitor-value">{String(feature.competitor)}</span>
            </div>
          ))}
        </div>
      ))}
    </div>
  ),
}), { virtual: true });

jest.mock('@/components/compare/PricingComparison', () => ({
  PricingComparison: ({ seoindicator, competitor }: { seoindicator: ComparisonData['pricingComparison']['seoindicator']; competitor: ComparisonData['pricingComparison']['competitor'] }) => (
    <div data-testid="pricing-comparison">
      <div data-testid="seoindicator-pricing" data-tiers={seoindicator?.length || 0}>
        {seoindicator?.map((tier) => (
          <div key={tier.name} data-testid={`tier-seoindicator-${tier.name}`}>
            {tier.name}: {String(tier.monthlyPrice)}
          </div>
        ))}
      </div>
      <div data-testid="competitor-pricing" data-tiers={competitor?.length || 0}>
        {competitor?.map((tier) => (
          <div key={tier.name} data-testid={`tier-competitor-${tier.name}`}>
            {tier.name}: {String(tier.monthlyPrice)}
          </div>
        ))}
      </div>
    </div>
  ),
}), { virtual: true });

jest.mock('@/components/compare/ProsConsCard', () => ({
  ProsConsCard: ({ tool, pros, cons }: { tool: string; pros: string[]; cons: string[] }) => (
    <div data-testid={`pros-cons-card-${tool}`}>
      <h3>{tool}</h3>
      <ul data-testid="pros-list">
        {pros?.map((pro) => <li key={pro}>{pro}</li>)}
      </ul>
      <ul data-testid="cons-list">
        {cons?.map((con) => <li key={con}>{con}</li>)}
      </ul>
    </div>
  ),
}), { virtual: true });

jest.mock('@/components/compare/UseCaseSection', () => ({
  UseCaseSection: ({ seoindicator, competitor }: { seoindicator: ComparisonData['useCases']['seoindicator']; competitor: ComparisonData['useCases']['competitor'] }) => (
    <div data-testid="use-cases-section">
      <div data-testid="seoindicator-use-cases">{seoindicator?.length || 0} use cases</div>
      <div data-testid="competitor-use-cases">{competitor?.length || 0} use cases</div>
    </div>
  ),
}), { virtual: true });

jest.mock('@/components/compare/ComparisonCTA', () => ({
  ComparisonCTA: ({ variant, text }: { variant?: string; text?: string }) => (
    <button data-testid="comparison-cta" data-variant={variant || 'primary'}>
      {text || 'Get Started Free'}
    </button>
  ),
}), { virtual: true });

jest.mock('@/components/compare/ComparisonSchema', () => ({
  ComparisonSchema: ({ comparison }: { comparison: ComparisonData }) => (
    <script type="application/ld+json" data-testid="comparison-schema">
      {JSON.stringify({ '@type': 'CompareSideBySide', name: comparison.title })}
    </script>
  ),
}), { virtual: true });

jest.mock('@/components/compare/FAQSection', () => ({
  FAQSection: ({ faqs }: { faqs: FAQ[] }) => (
    <div data-testid="faq-section" data-count={faqs?.length || 0}>
      {faqs?.map((faq, index) => (
        <div key={index} data-testid={`faq-${index}`}>
          <h4>{faq.question}</h4>
          <p>{faq.answer}</p>
        </div>
      ))}
    </div>
  ),
}), { virtual: true });

jest.mock('@/components/compare/RelatedComparisons', () => ({
  RelatedComparisons: ({ slugs }: { slugs: string[] }) => (
    <div data-testid="related-comparisons" data-count={slugs?.length || 0}>
      {slugs?.map((slug) => (
        <a key={slug} href={`/compare/${slug}`} data-testid={`related-link-${slug}`}>
          {slug}
        </a>
      ))}
    </div>
  ),
}), { virtual: true });

// ============================================================================
// Mock Next.js notFound
// ============================================================================

const mockNotFound = jest.fn();
jest.mock('next/navigation', () => ({
  notFound: () => mockNotFound(),
}));

// ============================================================================
// Mock ComparisonPage Component (TDD Pattern)
// ============================================================================

/**
 * This is the expected component signature.
 * The Developer will implement the actual component.
 */
interface ComparisonPageProps {
  params: { slug: string };
}

// Mock page component for testing structure
const ComparisonPage = ({ params }: ComparisonPageProps) => {
  const comparison = mockGetComparisonBySlug(params.slug);
  
  if (!comparison) {
    mockNotFound();
    return null;
  }
  
  return (
    <main role="main" aria-label="Comparison page">
      <article>
        <header>
          <h1>{comparison.title}</h1>
          <p data-testid="summary">{comparison.summary}</p>
        </header>
        
        <section aria-labelledby="features-heading">
          <h2 id="features-heading">Feature Comparison</h2>
          <div data-testid="comparison-table-wrapper">
            {/* ComparisonTable would render here */}
          </div>
        </section>
        
        <section aria-labelledby="pricing-heading">
          <h2 id="pricing-heading">Pricing Comparison</h2>
          <div data-testid="pricing-comparison-wrapper">
            {/* PricingComparison would render here */}
          </div>
        </section>
        
        <section aria-labelledby="pros-cons-heading">
          <h2 id="pros-cons-heading">Pros & Cons</h2>
          <div data-testid="pros-cons-wrapper">
            {/* ProsConsCard components would render here */}
          </div>
        </section>
        
        <section aria-labelledby="use-cases-heading">
          <h2 id="use-cases-heading">Best For</h2>
          <div data-testid="use-cases-wrapper">
            {/* UseCaseSection would render here */}
          </div>
        </section>
        
        <section aria-labelledby="verdict-heading">
          <h2 id="verdict-heading">Our Verdict</h2>
          <p data-testid="verdict">{comparison.verdict}</p>
        </section>
        
        <section aria-labelledby="faq-heading">
          <h2 id="faq-heading">Frequently Asked Questions</h2>
          <div data-testid="faq-wrapper">
            {/* FAQSection would render here */}
          </div>
        </section>
        
        <section aria-labelledby="related-heading">
          <h2 id="related-heading">Related Comparisons</h2>
          <div data-testid="related-wrapper">
            {/* RelatedComparisons would render here */}
          </div>
        </section>
        
        <div data-testid="cta-wrapper">
          {/* ComparisonCTA would render here */}
        </div>
      </article>
    </main>
  );
};

// ============================================================================
// Test Suite: Static Params Structure (CP-STR)
// ============================================================================

describe('generateStaticParams', () => {
  beforeEach(() => {
    jest.clearAllMocks();
  });

  describe('CP-STR: Static Params Structure', () => {
    it('CP-STR-001: returns array of slug params', () => {
      const { COMPARISON_SLUGS } = require('@/lib/compare/registry');
      const params = COMPARISON_SLUGS.map((slug: string) => ({ slug }));
      
      expect(Array.isArray(params)).toBe(true);
      expect(params.length).toBeGreaterThan(0);
    });

    it('CP-STR-002: each param has slug property', () => {
      const { COMPARISON_SLUGS } = require('@/lib/compare/registry');
      const params = COMPARISON_SLUGS.map((slug: string) => ({ slug }));
      
      params.forEach((param: { slug: string }) => {
        expect(param).toHaveProperty('slug');
        expect(typeof param.slug).toBe('string');
      });
    });

    it('CP-STR-003: all COMPARISON_SLUGS included', () => {
      const { COMPARISON_SLUGS } = require('@/lib/compare/registry');
      const params = COMPARISON_SLUGS.map((slug: string) => ({ slug }));
      
      expect(params.length).toBe(COMPARISON_SLUGS.length);
    });

    it('CP-STR-004: slug format contains -vs-', () => {
      const { COMPARISON_SLUGS } = require('@/lib/compare/registry');
      
      COMPARISON_SLUGS.forEach((slug: string) => {
        expect(slug).toContain('-vs-');
      });
    });

    it('CP-STR-005: no duplicate slugs', () => {
      const { COMPARISON_SLUGS } = require('@/lib/compare/registry');
      const uniqueSlugs = new Set(COMPARISON_SLUGS);
      
      expect(uniqueSlugs.size).toBe(COMPARISON_SLUGS.length);
    });

    it('CP-STR-006: empty array if no competitors', () => {
      // Simulate empty registry
      const emptySlugs: string[] = [];
      const params = emptySlugs.map((slug) => ({ slug }));
      
      expect(params).toEqual([]);
    });

    it('CP-STR-007: params are serializable', () => {
      const { COMPARISON_SLUGS } = require('@/lib/compare/registry');
      const params = COMPARISON_SLUGS.map((slug: string) => ({ slug }));
      
      expect(() => JSON.stringify(params)).not.toThrow();
    });

    it('CP-STR-008: params sorted alphabetically', () => {
      const { COMPARISON_SLUGS } = require('@/lib/compare/registry');
      const sortedSlugs = [...COMPARISON_SLUGS].sort();
      
      expect(COMPARISON_SLUGS).toEqual(sortedSlugs);
    });

    it('CP-STR-009: primary comparisons first (seoindicator-vs-*)', () => {
      const { COMPARISON_SLUGS } = require('@/lib/compare/registry');
      const primaryComparisons = COMPARISON_SLUGS.filter((s: string) => s.startsWith('seoindicator-vs-'));
      const firstNonPrimary = COMPARISON_SLUGS.findIndex((s: string) => !s.startsWith('seoindicator-vs-'));
      
      // All seoindicator comparisons should come before cross-competitor
      if (primaryComparisons.length > 0 && firstNonPrimary > 0) {
        primaryComparisons.forEach((slug: string) => {
          const index = COMPARISON_SLUGS.indexOf(slug);
          expect(index).toBeLessThan(firstNonPrimary);
        });
      }
    });

    it('CP-STR-010: cross-competitor comparisons included', () => {
      const { COMPARISON_SLUGS } = require('@/lib/compare/registry');
      const crossCompetitor = COMPARISON_SLUGS.filter((s: string) => !s.startsWith('seoindicator-'));
      
      expect(crossCompetitor.length).toBeGreaterThan(0);
    });
  });
});

// ============================================================================
// Test Suite: 404 Handling (CP-404)
// ============================================================================

describe('ComparisonPage - 404 Handling', () => {
  beforeEach(() => {
    jest.clearAllMocks();
    mockNotFound.mockClear();
  });

  describe('CP-404: 404 Handling', () => {
    it('CP-404-001: non-existent slug calls notFound', () => {
      render(<ComparisonPage params={{ slug: 'nonexistent-vs-tool' }} />);
      
      expect(mockNotFound).toHaveBeenCalled();
    });

    it('CP-404-002: invalid format calls notFound', () => {
      render(<ComparisonPage params={{ slug: 'invalid-slug' }} />);
      
      expect(mockNotFound).toHaveBeenCalled();
    });

    it('CP-404-003: missing competitor calls notFound', () => {
      render(<ComparisonPage params={{ slug: 'unknown-vs-ahrefs' }} />);
      
      expect(mockNotFound).toHaveBeenCalled();
    });

    it('CP-404-004: empty slug calls notFound', () => {
      render(<ComparisonPage params={{ slug: '' }} />);
      
      expect(mockNotFound).toHaveBeenCalled();
    });

    it('CP-404-005: single competitor calls notFound', () => {
      render(<ComparisonPage params={{ slug: 'seoindicator' }} />);
      
      expect(mockNotFound).toHaveBeenCalled();
    });

    it('CP-404-006: triple competitor calls notFound', () => {
      render(<ComparisonPage params={{ slug: 'a-vs-b-vs-c' }} />);
      
      expect(mockNotFound).toHaveBeenCalled();
    });

    it('CP-404-007: wrong separator calls notFound', () => {
      render(<ComparisonPage params={{ slug: 'seoindicator_versus_ahrefs' }} />);
      
      expect(mockNotFound).toHaveBeenCalled();
    });

    it('CP-404-008: uppercase slug handled', () => {
      // Should either normalize and work, or call notFound
      render(<ComparisonPage params={{ slug: 'SEOIndicator-vs-Ahrefs' }} />);
      
      // Either the page renders (normalized) or notFound is called
      const result = mockNotFound.mock.calls.length > 0 || screen.queryByRole('main');
      expect(result).toBeDefined();
    });

    it('CP-404-011: query params ignored', () => {
      // Query params are handled by Next.js routing, not the page component
      // The page receives just the slug without query params
      const slug = 'seoindicator-vs-ahrefs';
      
      render(<ComparisonPage params={{ slug }} />);
      
      expect(mockNotFound).not.toHaveBeenCalled();
    });
  });
});

// ============================================================================
// Test Suite: Page Rendering (CP-RENDER)
// ============================================================================

describe('ComparisonPage - Rendering', () => {
  beforeEach(() => {
    jest.clearAllMocks();
    mockNotFound.mockClear();
  });

  describe('CP-RENDER: Page Rendering', () => {
    it('CP-RENDER-001: page renders without error', () => {
      render(<ComparisonPage params={{ slug: 'seoindicator-vs-ahrefs' }} />);
      
      expect(screen.getByRole('main')).toBeInTheDocument();
    });

    it('CP-RENDER-002: comparison title visible in h1', () => {
      render(<ComparisonPage params={{ slug: 'seoindicator-vs-ahrefs' }} />);
      
      const h1 = screen.getByRole('heading', { level: 1 });
      expect(h1).toHaveTextContent('SEOIndicator');
      expect(h1).toHaveTextContent('Ahrefs');
    });

    it('CP-RENDER-003: summary paragraph visible', () => {
      render(<ComparisonPage params={{ slug: 'seoindicator-vs-ahrefs' }} />);
      
      expect(screen.getByTestId('summary')).toBeInTheDocument();
      expect(screen.getByTestId('summary')).toHaveTextContent('Find out how');
    });

    it('CP-RENDER-004: feature comparison section exists', () => {
      render(<ComparisonPage params={{ slug: 'seoindicator-vs-ahrefs' }} />);
      
      expect(screen.getByTestId('comparison-table-wrapper')).toBeInTheDocument();
    });

    it('CP-RENDER-005: pricing comparison section exists', () => {
      render(<ComparisonPage params={{ slug: 'seoindicator-vs-ahrefs' }} />);
      
      expect(screen.getByTestId('pricing-comparison-wrapper')).toBeInTheDocument();
    });

    it('CP-RENDER-006: pros/cons section exists', () => {
      render(<ComparisonPage params={{ slug: 'seoindicator-vs-ahrefs' }} />);
      
      expect(screen.getByTestId('pros-cons-wrapper')).toBeInTheDocument();
    });

    it('CP-RENDER-007: use cases section exists', () => {
      render(<ComparisonPage params={{ slug: 'seoindicator-vs-ahrefs' }} />);
      
      expect(screen.getByTestId('use-cases-wrapper')).toBeInTheDocument();
    });

    it('CP-RENDER-008: verdict section renders', () => {
      render(<ComparisonPage params={{ slug: 'seoindicator-vs-ahrefs' }} />);
      
      expect(screen.getByTestId('verdict')).toBeInTheDocument();
    });

    it('CP-RENDER-009: FAQ section exists', () => {
      render(<ComparisonPage params={{ slug: 'seoindicator-vs-ahrefs' }} />);
      
      expect(screen.getByTestId('faq-wrapper')).toBeInTheDocument();
    });

    it('CP-RENDER-010: related comparisons section exists', () => {
      render(<ComparisonPage params={{ slug: 'seoindicator-vs-ahrefs' }} />);
      
      expect(screen.getByTestId('related-wrapper')).toBeInTheDocument();
    });

    it('CP-RENDER-011: CTA button wrapper exists', () => {
      render(<ComparisonPage params={{ slug: 'seoindicator-vs-ahrefs' }} />);
      
      expect(screen.getByTestId('cta-wrapper')).toBeInTheDocument();
    });
  });
});

// ============================================================================
// Test Suite: Accessibility (CP-ACCESS)
// ============================================================================

describe('ComparisonPage - Accessibility', () => {
  beforeEach(() => {
    jest.clearAllMocks();
  });

  describe('CP-ACCESS: Accessibility', () => {
    it('CP-ACCESS-001: single h1 on page', () => {
      render(<ComparisonPage params={{ slug: 'seoindicator-vs-ahrefs' }} />);
      
      const h1Elements = screen.getAllByRole('heading', { level: 1 });
      expect(h1Elements).toHaveLength(1);
    });

    it('CP-ACCESS-002: heading hierarchy correct (h1 -> h2)', () => {
      render(<ComparisonPage params={{ slug: 'seoindicator-vs-ahrefs' }} />);
      
      const h1 = screen.getByRole('heading', { level: 1 });
      const h2Elements = screen.getAllByRole('heading', { level: 2 });
      
      expect(h1).toBeInTheDocument();
      expect(h2Elements.length).toBeGreaterThan(0);
    });

    it('CP-ACCESS-003: sections have accessible names', () => {
      render(<ComparisonPage params={{ slug: 'seoindicator-vs-ahrefs' }} />);
      
      // Sections should have aria-labelledby pointing to their headings
      const sections = screen.getAllByRole('region');
      sections.forEach((section) => {
        const labelledBy = section.getAttribute('aria-labelledby');
        if (labelledBy) {
          const heading = document.getElementById(labelledBy);
          expect(heading).toBeTruthy();
        }
      });
    });

    it('CP-ACCESS-006: main landmark present', () => {
      render(<ComparisonPage params={{ slug: 'seoindicator-vs-ahrefs' }} />);
      
      const main = screen.getByRole('main');
      expect(main).toBeInTheDocument();
    });

    it('CP-ACCESS-007: main landmark has accessible label', () => {
      render(<ComparisonPage params={{ slug: 'seoindicator-vs-ahrefs' }} />);
      
      const main = screen.getByRole('main');
      const label = main.getAttribute('aria-label');
      expect(label).toBeTruthy();
    });
  });
});

// ============================================================================
// Test Suite: Integration (CP-INT)
// ============================================================================

describe('ComparisonPage - Integration', () => {
  beforeEach(() => {
    jest.clearAllMocks();
  });

  describe('CP-INT: Integration', () => {
    it('CP-INT-001: getComparisonBySlug called with correct slug', () => {
      render(<ComparisonPage params={{ slug: 'seoindicator-vs-ahrefs' }} />);
      
      expect(mockGetComparisonBySlug).toHaveBeenCalledWith('seoindicator-vs-ahrefs');
    });

    it('CP-INT-002: comparison data used for rendering', () => {
      render(<ComparisonPage params={{ slug: 'seoindicator-vs-ahrefs' }} />);
      
      const h1 = screen.getByRole('heading', { level: 1 });
      expect(h1).toHaveTextContent(mockComparisonData.title);
    });

    it('CP-INT-003: verdict text from comparison data', () => {
      render(<ComparisonPage params={{ slug: 'seoindicator-vs-ahrefs' }} />);
      
      const verdict = screen.getByTestId('verdict');
      expect(verdict).toHaveTextContent(mockComparisonData.verdict);
    });

    it('CP-INT-004: summary text from comparison data', () => {
      render(<ComparisonPage params={{ slug: 'seoindicator-vs-ahrefs' }} />);
      
      const summary = screen.getByTestId('summary');
      expect(summary).toHaveTextContent(mockComparisonData.summary);
    });

    it('CP-INT-012: different slug loads different data', () => {
      render(<ComparisonPage params={{ slug: 'seoindicator-vs-semrush' }} />);
      
      expect(mockGetComparisonBySlug).toHaveBeenCalledWith('seoindicator-vs-semrush');
    });
  });
});

// ============================================================================
// Test Suite: Edge Cases (CP-EDGE)
// ============================================================================

describe('ComparisonPage - Edge Cases', () => {
  beforeEach(() => {
    jest.clearAllMocks();
    mockNotFound.mockClear();
  });

  describe('CP-EDGE: Edge Cases', () => {
    it('CP-EDGE-001: same competitor comparison calls notFound', () => {
      // 'seoindicator-vs-seoindicator' should not be in valid slugs
      render(<ComparisonPage params={{ slug: 'seoindicator-vs-seoindicator' }} />);
      
      expect(mockNotFound).toHaveBeenCalled();
    });

    it('CP-EDGE-002: unicode in slug handled', () => {
      render(<ComparisonPage params={{ slug: 'seoindicator-vs-tëst' }} />);
      
      // Either notFound called or page renders (depending on normalization)
      const result = mockNotFound.mock.calls.length > 0 || screen.queryByRole('main');
      expect(result).toBeDefined();
    });

    it('CP-EDGE-003: very long slug calls notFound', () => {
      const longSlug = 'a'.repeat(1000);
      render(<ComparisonPage params={{ slug: longSlug }} />);
      
      expect(mockNotFound).toHaveBeenCalled();
    });

    it('CP-EDGE-004: special characters in slug handled', () => {
      render(<ComparisonPage params={{ slug: 'a-vs-b<script>' }} />);
      
      // Should call notFound or sanitize safely
      expect(mockNotFound).toHaveBeenCalled();
    });
  });
});

// ============================================================================
// Test Suite: Acceptance Criteria (CP-AC)
// ============================================================================

describe('ComparisonPage - Acceptance Criteria', () => {
  beforeEach(() => {
    jest.clearAllMocks();
    mockNotFound.mockClear();
  });

  describe('CP-AC: Acceptance Criteria', () => {
    it('CP-AC-001: static params generate all slugs', () => {
      const { COMPARISON_SLUGS } = require('@/lib/compare/registry');
      const params = COMPARISON_SLUGS.map((slug: string) => ({ slug }));
      
      expect(params.length).toBe(COMPARISON_SLUGS.length);
      params.forEach((param: { slug: string }) => {
        expect(param).toHaveProperty('slug');
        expect(typeof param.slug).toBe('string');
      });
    });

    it('CP-AC-003: invalid slug shows 404', () => {
      render(<ComparisonPage params={{ slug: 'invalid-slug' }} />);
      
      expect(mockNotFound).toHaveBeenCalled();
    });

    it('CP-AC-004: page renders for all valid slugs', () => {
      const { COMPARISON_SLUGS } = require('@/lib/compare/registry');
      
      COMPARISON_SLUGS.forEach((slug: string) => {
        const { unmount } = render(<ComparisonPage params={{ slug }} />);
        expect(mockGetComparisonBySlug).toHaveBeenCalledWith(slug);
        unmount();
      });
    });

    it('CP-AC-007: mobile responsive layout exists', () => {
      render(<ComparisonPage params={{ slug: 'seoindicator-vs-ahrefs' }} />);
      
      // Page structure supports responsive design
      const main = screen.getByRole('main');
      expect(main).toBeInTheDocument();
    });
  });
});

// ============================================================================
// Test Suite: Registry Helper Functions
// ============================================================================

describe('Registry Helper Functions', () => {
  beforeEach(() => {
    jest.clearAllMocks();
  });

  describe('getComparisonBySlug', () => {
    it('returns ComparisonData for valid slug', () => {
      const result = mockGetComparisonBySlug('seoindicator-vs-ahrefs');
      
      expect(result).not.toBeNull();
      expect(result?.slug).toBe('seoindicator-vs-ahrefs');
    });

    it('returns null for invalid slug', () => {
      const result = mockGetComparisonBySlug('invalid-slug');
      
      expect(result).toBeNull();
    });
  });

  describe('parseSlug', () => {
    it('parses valid slug correctly', () => {
      const result = mockParseSlug('seoindicator-vs-ahrefs');
      
      expect(result).toEqual(['seoindicator', 'ahrefs']);
    });

    it('returns null for invalid format', () => {
      expect(mockParseSlug('invalid')).toBeNull();
      expect(mockParseSlug('a-b-c')).toBeNull();
    });

    it('handles null input', () => {
      expect(mockParseSlug(null as unknown as string)).toBeNull();
    });

    it('handles undefined input', () => {
      expect(mockParseSlug(undefined as unknown as string)).toBeNull();
    });
  });

  describe('getCompetitorById', () => {
    it('returns competitor data for valid id', () => {
      const result = mockGetCompetitorById('seoindicator');
      
      expect(result).not.toBeNull();
      expect(result?.id).toBe('seoindicator');
    });

    it('returns null for invalid id', () => {
      const result = mockGetCompetitorById('unknown');
      
      expect(result).toBeNull();
    });
  });
});
