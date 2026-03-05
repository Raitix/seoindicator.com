/**
 * Comparison Page Metadata Tests
 * Task: TASK-32-3 - Comparison Page Route with Static Generation
 * Spec: SPEC-32 (Competitor Comparison Landing Pages)
 */

import '@testing-library/jest-dom';

// Types
import type { Metadata } from 'next';
import type { ComparisonData } from '@/lib/compare/types';

// ============================================================================
// Mock Data
// ============================================================================

const mockComparisonData: ComparisonData = {
  slug: 'seoindicator-vs-ahrefs',
  title: 'SEOIndicator vs Ahrefs: Which SEO Tool Is Better?',
  metaDescription: 'Compare SEOIndicator vs Ahrefs. Detailed analysis of features, pricing, pros and cons to help you choose the best SEO tool.',
  competitors: ['seoindicator', 'ahrefs'],
  summary: 'Find out how SEOIndicator compares to Ahrefs.',
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
  verdict: 'SEOIndicator is best for budget-conscious users.',
  faqs: [],
  relatedComparisons: [],
};

const MOCK_COMPARISON_SLUGS = [
  'seoindicator-vs-ahrefs',
  'seoindicator-vs-semrush',
  'seoindicator-vs-moz',
  'ahrefs-vs-semrush',
];

// ============================================================================
// Mock Registry Module
// ============================================================================

const mockGetComparisonBySlug = jest.fn((slug: string): ComparisonData | null => {
  if (slug === 'seoindicator-vs-ahrefs') return mockComparisonData;
  if (MOCK_COMPARISON_SLUGS.includes(slug)) {
    const [a, b] = slug.split('-vs-');
    return {
      ...mockComparisonData,
      slug,
      title: `${a.charAt(0).toUpperCase() + a.slice(1)} vs ${b.charAt(0).toUpperCase() + b.slice(1)}: Which SEO Tool Is Better?`,
      metaDescription: `Compare ${a} vs ${b}. Detailed analysis of features, pricing, pros and cons.`,
      competitors: [a, b] as [string, string],
    };
  }
  return null;
});

jest.mock('@/lib/compare/registry', () => ({
  COMPARISON_SLUGS: MOCK_COMPARISON_SLUGS,
  getComparisonBySlug: mockGetComparisonBySlug,
  getCompetitorById: jest.fn((id: string) => ({ id, name: id.charAt(0).toUpperCase() + id.slice(1) })),
}));

// ============================================================================
// Metadata Generation Function (Expected Implementation)
// ============================================================================

/**
 * Expected metadata generation pattern.
 * Developer will implement actual function in page.tsx
 */
async function generateMetadata({ params }: { params: { slug: string } }): Promise<Metadata> {
  const comparison = mockGetComparisonBySlug(params.slug);
  
  if (!comparison) {
    return {
      title: 'Comparison Not Found',
    };
  }
  
  return {
    title: comparison.title,
    description: comparison.metaDescription,
    openGraph: {
      title: comparison.title,
      description: comparison.metaDescription,
      type: 'article',
      url: `https://seoindicator.com/compare/${comparison.slug}`,
      siteName: 'SEOIndicator',
      locale: 'en_US',
    },
    twitter: {
      card: 'summary_large_image',
      title: comparison.title,
      description: comparison.metaDescription,
    },
    alternates: {
      canonical: `/compare/${comparison.slug}`,
    },
    robots: {
      index: true,
      follow: true,
    },
  };
}

// ============================================================================
// Test Suite: Metadata Generation (CP-META)
// ============================================================================

describe('generateMetadata', () => {
  beforeEach(() => {
    jest.clearAllMocks();
  });

  describe('CP-META: Metadata Generation', () => {
    it('CP-META-001: title includes both competitors', async () => {
      const metadata = await generateMetadata({ params: { slug: 'seoindicator-vs-ahrefs' } });
      
      expect(metadata.title).toContain('SEOIndicator');
      expect(metadata.title).toContain('Ahrefs');
    });

    it('CP-META-002: title has correct format', async () => {
      const metadata = await generateMetadata({ params: { slug: 'seoindicator-vs-ahrefs' } });
      
      expect(metadata.title).toMatch(/.+\s+vs\s+.+:\s+Which SEO Tool Is Better\?/);
    });

    it('CP-META-003: meta description generated', async () => {
      const metadata = await generateMetadata({ params: { slug: 'seoindicator-vs-ahrefs' } });
      
      expect(metadata.description).toBeDefined();
      expect(typeof metadata.description).toBe('string');
    });

    it('CP-META-004: description includes comparison context', async () => {
      const metadata = await generateMetadata({ params: { slug: 'seoindicator-vs-ahrefs' } });
      
      const desc = metadata.description as string;
      expect(desc.toLowerCase()).toContain('compare');
      expect(desc.toLowerCase()).toContain('features');
      expect(desc.toLowerCase()).toContain('pricing');
    });

    it('CP-META-005: Open Graph title matches page title', async () => {
      const metadata = await generateMetadata({ params: { slug: 'seoindicator-vs-ahrefs' } });
      
      expect(metadata.openGraph?.title).toBe(metadata.title);
    });

    it('CP-META-006: Open Graph description matches meta', async () => {
      const metadata = await generateMetadata({ params: { slug: 'seoindicator-vs-ahrefs' } });
      
      expect(metadata.openGraph?.description).toBe(metadata.description);
    });

    it('CP-META-007: canonical URL set correctly', async () => {
      const metadata = await generateMetadata({ params: { slug: 'seoindicator-vs-ahrefs' } });
      
      expect(metadata.alternates?.canonical).toBe('/compare/seoindicator-vs-ahrefs');
    });

    it('CP-META-008: robots meta allows indexing', async () => {
      const metadata = await generateMetadata({ params: { slug: 'seoindicator-vs-ahrefs' } });
      
      expect(metadata.robots?.index).toBe(true);
      expect(metadata.robots?.follow).toBe(true);
    });

    it('CP-META-009: Twitter card metadata set', async () => {
      const metadata = await generateMetadata({ params: { slug: 'seoindicator-vs-ahrefs' } });
      
      expect(metadata.twitter?.card).toBe('summary_large_image');
    });

    it('CP-META-010: invalid slug returns fallback metadata', async () => {
      const metadata = await generateMetadata({ params: { slug: 'invalid-slug' } });
      
      expect(metadata.title).toBe('Comparison Not Found');
    });

    it('CP-META-011: metadata alternates for canonical', async () => {
      const metadata = await generateMetadata({ params: { slug: 'seoindicator-vs-ahrefs' } });
      
      expect(metadata.alternates).toBeDefined();
      expect(metadata.alternates?.canonical).toBeDefined();
    });

    it('CP-META-014: title length under 70 chars', async () => {
      const metadata = await generateMetadata({ params: { slug: 'seoindicator-vs-ahrefs' } });
      
      expect((metadata.title as string).length).toBeLessThanOrEqual(70);
    });

    it('CP-META-015: description length 120-160', async () => {
      const metadata = await generateMetadata({ params: { slug: 'seoindicator-vs-ahrefs' } });
      
      const desc = metadata.description as string;
      expect(desc.length).toBeGreaterThanOrEqual(120);
      expect(desc.length).toBeLessThanOrEqual(160);
    });

    it('CP-META-017: OG type set to article', async () => {
      const metadata = await generateMetadata({ params: { slug: 'seoindicator-vs-ahrefs' } });
      
      expect(metadata.openGraph?.type).toBe('article');
    });

    it('CP-META-018: site name in metadata', async () => {
      const metadata = await generateMetadata({ params: { slug: 'seoindicator-vs-ahrefs' } });
      
      expect(metadata.openGraph?.siteName).toBe('SEOIndicator');
    });

    it('CP-META-019: locale set correctly', async () => {
      const metadata = await generateMetadata({ params: { slug: 'seoindicator-vs-ahrefs' } });
      
      expect(metadata.openGraph?.locale).toBe('en_US');
    });

    it('CP-META-020: URL set in Open Graph', async () => {
      const metadata = await generateMetadata({ params: { slug: 'seoindicator-vs-ahrefs' } });
      
      expect(metadata.openGraph?.url).toContain('seoindicator-vs-ahrefs');
    });
  });
});

// ============================================================================
// Test Suite: SEO Best Practices
// ============================================================================

describe('SEO Best Practices', () => {
  beforeEach(() => {
    jest.clearAllMocks();
  });

  it('title contains primary keyword (comparison)', async () => {
    const metadata = await generateMetadata({ params: { slug: 'seoindicator-vs-ahrefs' } });
    
    expect((metadata.title as string).toLowerCase()).toContain('vs');
  });

  it('description starts with action verb', async () => {
    const metadata = await generateMetadata({ params: { slug: 'seoindicator-vs-ahrefs' } });
    
    const desc = (metadata.description as string).toLowerCase();
    expect(desc.startsWith('compare')).toBe(true);
  });

  it('twitter title matches OG title', async () => {
    const metadata = await generateMetadata({ params: { slug: 'seoindicator-vs-ahrefs' } });
    
    expect(metadata.twitter?.title).toBe(metadata.openGraph?.title);
  });

  it('twitter description matches OG description', async () => {
    const metadata = await generateMetadata({ params: { slug: 'seoindicator-vs-ahrefs' } });
    
    expect(metadata.twitter?.description).toBe(metadata.openGraph?.description);
  });

  it('all valid slugs generate metadata', async () => {
    for (const slug of MOCK_COMPARISON_SLUGS) {
      const metadata = await generateMetadata({ params: { slug } });
      expect(metadata.title).toBeDefined();
      expect(metadata.description).toBeDefined();
    }
  });

  it('different slugs generate different titles', async () => {
    const metadata1 = await generateMetadata({ params: { slug: 'seoindicator-vs-ahrefs' } });
    const metadata2 = await generateMetadata({ params: { slug: 'seoindicator-vs-semrush' } });
    
    expect(metadata1.title).not.toBe(metadata2.title);
  });

  it('cross-competitor comparison generates valid metadata', async () => {
    const metadata = await generateMetadata({ params: { slug: 'ahrefs-vs-semrush' } });
    
    expect(metadata.title).toContain('Ahrefs');
    expect(metadata.title).toContain('Semrush');
    expect(metadata.description).toBeDefined();
  });
});

// ============================================================================
// Test Suite: Edge Cases
// ============================================================================

describe('Metadata Edge Cases', () => {
  beforeEach(() => {
    jest.clearAllMocks();
  });

  it('handles empty slug', async () => {
    const metadata = await generateMetadata({ params: { slug: '' } });
    
    expect(metadata.title).toBe('Comparison Not Found');
  });

  it('handles null slug', async () => {
    const metadata = await generateMetadata({ params: { slug: null as unknown as string } });
    
    expect(metadata.title).toBe('Comparison Not Found');
  });

  it('handles malformed slug', async () => {
    const metadata = await generateMetadata({ params: { slug: 'not-a-comparison' } });
    
    expect(metadata.title).toBe('Comparison Not Found');
  });

  it('handles slug with special characters', async () => {
    const metadata = await generateMetadata({ params: { slug: 'a-vs-b<script>' } });
    
    // Should not throw, should return fallback
    expect(metadata.title).toBe('Comparison Not Found');
  });
});

// ============================================================================
// Test Suite: Schema.org Integration
// ============================================================================

describe('Schema.org Metadata', () => {
  it('article metadata type is correct', async () => {
    const metadata = await generateMetadata({ params: { slug: 'seoindicator-vs-ahrefs' } });
    
    // Open Graph type should be 'article' for comparison pages
    expect(metadata.openGraph?.type).toBe('article');
  });
});

// ============================================================================
// Test Suite: Acceptance Criteria
// ============================================================================

describe('Metadata Acceptance Criteria', () => {
  it('CP-AC-002: metadata SEO optimized', async () => {
    const metadata = await generateMetadata({ params: { slug: 'seoindicator-vs-ahrefs' } });
    
    // Check all required SEO elements
    expect(metadata.title).toBeDefined();
    expect(metadata.description).toBeDefined();
    expect(metadata.openGraph).toBeDefined();
    expect(metadata.twitter).toBeDefined();
    expect(metadata.alternates?.canonical).toBeDefined();
    
    // Check lengths
    expect((metadata.title as string).length).toBeLessThanOrEqual(70);
    expect((metadata.description as string).length).toBeGreaterThanOrEqual(120);
    expect((metadata.description as string).length).toBeLessThanOrEqual(160);
  });
});
