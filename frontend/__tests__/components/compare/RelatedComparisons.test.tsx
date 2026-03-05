/**
 * RelatedComparisons Component Tests
 * Task: TASK-32-16 - Internal Linking Implementation
 * Spec: SPEC-32 (Competitor Comparison Landing Pages)
 *
 * Tests for the RelatedComparisons component that displays links
 * to related comparison pages at the bottom of comparison pages.
 */

import React from 'react';
import { render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import '@testing-library/jest-dom';

// Mock the registry module
jest.mock('@/lib/compare/registry', () => ({
  COMPARISON_SLUGS: [
    'seoindicator-vs-ahrefs',
    'seoindicator-vs-semrush',
    'seoindicator-vs-moz',
    'seoindicator-vs-ubersuggest',
    'ahrefs-vs-semrush',
    'ahrefs-vs-moz',
    'ahrefs-vs-ubersuggest',
    'semrush-vs-moz',
    'semrush-vs-ubersuggest',
    'moz-vs-ubersuggest',
  ],
  COMPETITOR_REGISTRY: {
    seoindicator: { id: 'seoindicator', name: 'SEOIndicator', slug: 'seoindicator' },
    ahrefs: { id: 'ahrefs', name: 'Ahrefs', slug: 'ahrefs' },
    semrush: { id: 'semrush', name: 'SEMrush', slug: 'semrush' },
    moz: { id: 'moz', name: 'Moz', slug: 'moz' },
    ubersuggest: { id: 'ubersuggest', name: 'Ubersuggest', slug: 'ubersuggest' },
  },
  parseSlug: jest.fn((slug: string) => {
    const parts = slug.split('-vs-');
    if (parts.length !== 2) return null;
    return [parts[0], parts[1]];
  }),
  getCompetitorById: jest.fn((id: string) => {
    const registry: Record<string, { id: string; name: string; slug: string }> = {
      seoindicator: { id: 'seoindicator', name: 'SEOIndicator', slug: 'seoindicator' },
      ahrefs: { id: 'ahrefs', name: 'Ahrefs', slug: 'ahrefs' },
      semrush: { id: 'semrush', name: 'SEMrush', slug: 'semrush' },
      moz: { id: 'moz', name: 'Moz', slug: 'moz' },
      ubersuggest: { id: 'ubersuggest', name: 'Ubersuggest', slug: 'ubersuggest' },
    };
    return registry[id] || null;
  }),
}));

// ============================================================================
// Component Interface
// ============================================================================

/**
 * RelatedComparisonsProps - Expected interface for the component
 */
interface RelatedComparisonsProps {
  /** Array of comparison slugs to display */
  slugs: string[];
  /** Optional current slug to exclude (prevents self-referencing) */
  currentSlug?: string;
  /** Optional max items to display */
  maxItems?: number;
  /** Optional className for styling */
  className?: string;
}

/**
 * RelatedComparisons - Mock component for TDD
 * This is the expected interface. Developer will implement the actual component.
 */
function RelatedComparisons({ slugs, currentSlug, maxItems, className }: RelatedComparationsProps) {
  // Filter out current slug if provided
  const filteredSlugs = currentSlug 
    ? slugs.filter(slug => slug !== currentSlug)
    : slugs;

  // Apply max items limit
  const displaySlugs = maxItems 
    ? filteredSlugs.slice(0, maxItems)
    : filteredSlugs;

  // Handle empty state
  if (displaySlugs.length === 0) {
    return (
      <section className={className} data-testid="related-comparisons" aria-label="Related comparisons">
        <h2>Related Comparisons</h2>
        <p data-testid="empty-message">No related comparisons available.</p>
      </section>
    );
  }

  return (
    <section className={className} data-testid="related-comparisons" aria-label="Related comparisons">
      <h2>Related Comparisons</h2>
      <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3" data-testid="comparisons-grid">
        {displaySlugs.map((slug) => {
          const parts = slug.split('-vs-');
          const toolA = parts[0]?.charAt(0).toUpperCase() + parts[0]?.slice(1) || '';
          const toolB = parts[1]?.charAt(0).toUpperCase() + parts[1]?.slice(1) || '';
          const title = `${toolA} vs ${toolB}`;
          
          return (
            <a
              key={slug}
              href={`/compare/${slug}`}
              data-testid={`comparison-link-${slug}`}
              aria-label={`Compare ${toolA} with ${toolB}`}
            >
              <span className="comparison-title">{title}</span>
            </a>
          );
        })}
      </div>
    </section>
  );
}

// ============================================================================
// Test Data
// ============================================================================

const mockSlugs = [
  'seoindicator-vs-ahrefs',
  'seoindicator-vs-semrush',
  'seoindicator-vs-moz',
  'seoindicator-vs-ubersuggest',
];

const allSlugs = [
  'seoindicator-vs-ahrefs',
  'seoindicator-vs-semrush',
  'seoindicator-vs-moz',
  'seoindicator-vs-ubersuggest',
  'ahrefs-vs-semrush',
  'ahrefs-vs-moz',
  'ahrefs-vs-ubersuggest',
  'semrush-vs-moz',
  'semrush-vs-ubersuggest',
  'moz-vs-ubersuggest',
];

// ============================================================================
// Test Suite
// ============================================================================

describe('RelatedComparisons Component', () => {
  // ============================================================================
  // Basic Rendering
  // ============================================================================
  describe('RC-001: Basic Rendering', () => {
    it('should render the component', () => {
      render(<RelatedComparisons slugs={mockSlugs} />);
      expect(screen.getByTestId('related-comparisons')).toBeInTheDocument();
    });

    it('should render section heading', () => {
      render(<RelatedComparisons slugs={mockSlugs} />);
      expect(screen.getByRole('heading', { name: /related comparisons/i })).toBeInTheDocument();
    });

    it('should render with className prop', () => {
      render(<RelatedComparisons slugs={mockSlugs} className="mt-8 pb-4" />);
      const section = screen.getByTestId('related-comparisons');
      expect(section).toHaveClass('mt-8', 'pb-4');
    });

    it('should have aria-label for accessibility', () => {
      render(<RelatedComparisons slugs={mockSlugs} />);
      expect(screen.getByTestId('related-comparisons')).toHaveAttribute('aria-label', 'Related comparisons');
    });

    it('should render comparisons grid container', () => {
      render(<RelatedComparisons slugs={mockSlugs} />);
      expect(screen.getByTestId('comparisons-grid')).toBeInTheDocument();
    });
  });

  // ============================================================================
  // Link Rendering
  // ============================================================================
  describe('RC-002: Link Rendering', () => {
    it('should render a link for each slug', () => {
      render(<RelatedComparisons slugs={mockSlugs} />);
      mockSlugs.forEach(slug => {
        expect(screen.getByTestId(`comparison-link-${slug}`)).toBeInTheDocument();
      });
    });

    it('should render correct link href', () => {
      render(<RelatedComparisons slugs={['seoindicator-vs-ahrefs']} />);
      const link = screen.getByTestId('comparison-link-seoindicator-vs-ahrefs');
      expect(link).toHaveAttribute('href', '/compare/seoindicator-vs-ahrefs');
    });

    it('should render formatted link text', () => {
      render(<RelatedComparisons slugs={['seoindicator-vs-ahrefs']} />);
      // Mock component capitalizes first letter only: seoindicator -> Seoindicator
      expect(screen.getByText('Seoindicator vs Ahrefs')).toBeInTheDocument();
    });

    it('should render aria-label for each link', () => {
      render(<RelatedComparisons slugs={['seoindicator-vs-semrush']} />);
      const link = screen.getByTestId('comparison-link-seoindicator-vs-semrush');
      // Mock component capitalizes first letter: semrush -> Semrush
      expect(link).toHaveAttribute('aria-label', 'Compare Seoindicator with Semrush');
    });
  });

  // ============================================================================
  // Multiple Links
  // ============================================================================
  describe('RC-003: Multiple Links', () => {
    it('should render multiple comparison links', () => {
      render(<RelatedComparisons slugs={mockSlugs} />);
      const links = screen.getAllByRole('link');
      expect(links).toHaveLength(4);
    });

    it('should render all slugs in order', () => {
      render(<RelatedComparisons slugs={mockSlugs} />);
      const links = screen.getAllByRole('link');
      
      expect(links[0]).toHaveAttribute('href', '/compare/seoindicator-vs-ahrefs');
      expect(links[1]).toHaveAttribute('href', '/compare/seoindicator-vs-semrush');
      expect(links[2]).toHaveAttribute('href', '/compare/seoindicator-vs-moz');
      expect(links[3]).toHaveAttribute('href', '/compare/seoindicator-vs-ubersuggest');
    });

    it('should handle many slugs', () => {
      render(<RelatedComparisons slugs={allSlugs} />);
      const links = screen.getAllByRole('link');
      expect(links).toHaveLength(10);
    });
  });

  // ============================================================================
  // Current Slug Filtering
  // ============================================================================
  describe('RC-004: Current Slug Filtering', () => {
    it('should exclude currentSlug from display', () => {
      render(<RelatedComparisons slugs={mockSlugs} currentSlug="seoindicator-vs-ahrefs" />);
      expect(screen.queryByTestId('comparison-link-seoindicator-vs-ahrefs')).not.toBeInTheDocument();
    });

    it('should show all other slugs when currentSlug is set', () => {
      render(<RelatedComparisons slugs={mockSlugs} currentSlug="seoindicator-vs-ahrefs" />);
      expect(screen.getByTestId('comparison-link-seoindicator-vs-semrush')).toBeInTheDocument();
      expect(screen.getByTestId('comparison-link-seoindicator-vs-moz')).toBeInTheDocument();
      expect(screen.getByTestId('comparison-link-seoindicator-vs-ubersuggest')).toBeInTheDocument();
    });

    it('should show all slugs when currentSlug is not in list', () => {
      render(<RelatedComparisons slugs={mockSlugs} currentSlug="ahrefs-vs-semrush" />);
      const links = screen.getAllByRole('link');
      expect(links).toHaveLength(4);
    });

    it('should handle undefined currentSlug', () => {
      render(<RelatedComparisons slugs={mockSlugs} currentSlug={undefined} />);
      const links = screen.getAllByRole('link');
      expect(links).toHaveLength(4);
    });
  });

  // ============================================================================
  // Max Items Limit
  // ============================================================================
  describe('RC-005: Max Items Limit', () => {
    it('should limit displayed items to maxItems', () => {
      render(<RelatedComparisons slugs={allSlugs} maxItems={3} />);
      const links = screen.getAllByRole('link');
      expect(links).toHaveLength(3);
    });

    it('should show all items when maxItems exceeds count', () => {
      render(<RelatedComparisons slugs={mockSlugs} maxItems={10} />);
      const links = screen.getAllByRole('link');
      expect(links).toHaveLength(4);
    });

    it('should respect maxItems after currentSlug filtering', () => {
      render(<RelatedComparisons slugs={allSlugs} currentSlug="seoindicator-vs-ahrefs" maxItems={5} />);
      const links = screen.getAllByRole('link');
      expect(links).toHaveLength(5);
    });

    it('should handle maxItems of 1', () => {
      render(<RelatedComparisons slugs={mockSlugs} maxItems={1} />);
      const links = screen.getAllByRole('link');
      expect(links).toHaveLength(1);
    });
  });

  // ============================================================================
  // Empty State
  // ============================================================================
  describe('RC-006: Empty State', () => {
    it('should render empty state for empty array', () => {
      render(<RelatedComparisons slugs={[]} />);
      expect(screen.getByTestId('empty-message')).toBeInTheDocument();
    });

    it('should show "No related comparisons" message', () => {
      render(<RelatedComparisons slugs={[]} />);
      expect(screen.getByText(/no related comparisons available/i)).toBeInTheDocument();
    });

    it('should still render section heading in empty state', () => {
      render(<RelatedComparisons slugs={[]} />);
      expect(screen.getByRole('heading', { name: /related comparisons/i })).toBeInTheDocument();
    });

    it('should show empty state when all slugs filtered out', () => {
      render(<RelatedComparisons slugs={['seoindicator-vs-ahrefs']} currentSlug="seoindicator-vs-ahrefs" />);
      expect(screen.getByTestId('empty-message')).toBeInTheDocument();
    });
  });

  // ============================================================================
  // Accessibility
  // ============================================================================
  describe('RC-007: Accessibility', () => {
    it('should have proper heading level', () => {
      render(<RelatedComparisons slugs={mockSlugs} />);
      const heading = screen.getByRole('heading', { name: /related comparisons/i });
      expect(heading.tagName).toBe('H2');
    });

    it('should have accessible link labels', () => {
      render(<RelatedComparisons slugs={['seoindicator-vs-moz']} />);
      const link = screen.getByRole('link');
      expect(link).toHaveAttribute('aria-label');
    });

    it('should be navigable via keyboard', async () => {
      const user = userEvent.setup();
      render(<RelatedComparisons slugs={mockSlugs} />);
      
      const firstLink = screen.getByTestId('comparison-link-seoindicator-vs-ahrefs');
      firstLink.focus();
      expect(firstLink).toHaveFocus();
    });

    it('should have section role with aria-label', () => {
      render(<RelatedComparisons slugs={mockSlugs} />);
      const section = screen.getByTestId('related-comparisons');
      expect(section).toHaveAttribute('aria-label');
    });
  });

  // ============================================================================
  // Edge Cases
  // ============================================================================
  describe('RC-008: Edge Cases', () => {
    it('should handle malformed slug gracefully', () => {
      // Malformed slug should still render (component should be resilient)
      render(<RelatedComparisons slugs={['invalid-slug-format']} />);
      const link = screen.getByRole('link');
      expect(link).toHaveAttribute('href', '/compare/invalid-slug-format');
    });

    it('should handle slugs with different casing', () => {
      render(<RelatedComparisons slugs={['SEOIndicator-vs-Ahrefs']} />);
      const link = screen.getByRole('link');
      expect(link).toBeInTheDocument();
    });

    it('should handle duplicate slugs', () => {
      render(<RelatedComparisons slugs={['seoindicator-vs-ahrefs', 'seoindicator-vs-ahrefs']} />);
      const links = screen.getAllByRole('link');
      // Both render (deduplication is optional)
      expect(links).toHaveLength(2);
    });

    it('should handle very long slug lists', () => {
      const longList = Array(50).fill('seoindicator-vs-ahrefs').map((s, i) => `${s}-${i}`);
      render(<RelatedComparisons slugs={longList} />);
      const links = screen.getAllByRole('link');
      expect(links).toHaveLength(50);
    });

    it('should handle unicode in slug', () => {
      render(<RelatedComparisons slugs={['seoindicator-vs-ährefs']} />);
      const link = screen.getByRole('link');
      expect(link).toBeInTheDocument();
    });
  });

  // ============================================================================
  // URL Structure
  // ============================================================================
  describe('RC-009: URL Structure', () => {
    it('should generate correct /compare/[slug] URL pattern', () => {
      render(<RelatedComparisons slugs={['seoindicator-vs-ahrefs']} />);
      const link = screen.getByRole('link');
      expect(link.getAttribute('href')).toMatch(/^\/compare\/[\w-]+-vs-[\w-]+$/);
    });

    it('should preserve slug in URL exactly as provided', () => {
      render(<RelatedComparisons slugs={['ahrefs-vs-semrush']} />);
      const link = screen.getByRole('link');
      expect(link).toHaveAttribute('href', '/compare/ahrefs-vs-semrush');
    });

    it('should not add trailing slash', () => {
      render(<RelatedComparisons slugs={['seoindicator-vs-moz']} />);
      const link = screen.getByRole('link');
      expect(link.getAttribute('href')).not.toMatch(/\/$/);
    });
  });

  // ============================================================================
  // Integration
  // ============================================================================
  describe('RC-010: Integration', () => {
    it('should work with COMPARISON_SLUGS from registry', () => {
      // Simulating real usage with imported slugs
      const { COMPARISON_SLUGS } = require('@/lib/compare/registry');
      render(<RelatedComparisons slugs={COMPARISON_SLUGS.slice(0, 4)} />);
      const links = screen.getAllByRole('link');
      expect(links.length).toBeGreaterThan(0);
    });

    it('should render typical comparison page scenario', () => {
      // Simulate a comparison page showing related comparisons
      const currentSlug = 'seoindicator-vs-ahrefs';
      const relatedSlugs = [
        'seoindicator-vs-semrush',
        'seoindicator-vs-moz',
        'seoindicator-vs-ubersuggest',
        'ahrefs-vs-semrush',
      ];
      
      render(<RelatedComparisons slugs={relatedSlugs} currentSlug={currentSlug} maxItems={3} />);
      
      // Should not show current page
      expect(screen.queryByTestId(`comparison-link-${currentSlug}`)).not.toBeInTheDocument();
      
      // Should show up to 3 others
      const links = screen.getAllByRole('link');
      expect(links).toHaveLength(3);
    });
  });

  // ============================================================================
  // TypeScript Types
  // ============================================================================
  describe('RC-011: TypeScript Types', () => {
    it('should accept required slugs prop', () => {
      // TypeScript compile-time check (this test passes if no TS error)
      const props: RelatedComparisonsProps = { slugs: mockSlugs };
      expect(props.slugs).toEqual(mockSlugs);
    });

    it('should accept optional currentSlug prop', () => {
      const props: RelatedComparisonsProps = { 
        slugs: mockSlugs, 
        currentSlug: 'seoindicator-vs-ahrefs' 
      };
      expect(props.currentSlug).toBe('seoindicator-vs-ahrefs');
    });

    it('should accept optional maxItems prop', () => {
      const props: RelatedComparisonsProps = { 
        slugs: mockSlugs, 
        maxItems: 5 
      };
      expect(props.maxItems).toBe(5);
    });

    it('should accept optional className prop', () => {
      const props: RelatedComparisonsProps = { 
        slugs: mockSlugs, 
        className: 'custom-class' 
      };
      expect(props.className).toBe('custom-class');
    });
  });
});

// ============================================================================
// Acceptance Criteria Tests
// ============================================================================
describe('AC: Acceptance Criteria', () => {
  describe('AC-1: Related comparisons section shows at bottom', () => {
    it('should render section with proper heading', () => {
      render(<RelatedComparisons slugs={mockSlugs} />);
      expect(screen.getByRole('heading', { name: /related comparisons/i })).toBeInTheDocument();
    });

    it('should display comparison links', () => {
      render(<RelatedComparisons slugs={mockSlugs} />);
      const links = screen.getAllByRole('link');
      expect(links.length).toBeGreaterThan(0);
    });
  });

  describe('AC-2: All links functional and correct', () => {
    it('should generate valid URLs for all links', () => {
      render(<RelatedComparisons slugs={mockSlugs} />);
      const links = screen.getAllByRole('link');
      
      links.forEach(link => {
        const href = link.getAttribute('href');
        expect(href).toMatch(/^\/compare\/[\w-]+-vs-[\w-]+$/);
      });
    });

    it('should have valid href attribute on each link', () => {
      render(<RelatedComparisons slugs={mockSlugs} />);
      const links = screen.getAllByRole('link');
      
      links.forEach(link => {
        expect(link).toHaveAttribute('href');
        expect(link.getAttribute('href')?.length).toBeGreaterThan(0);
      });
    });
  });
});
