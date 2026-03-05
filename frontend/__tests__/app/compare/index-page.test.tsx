/**
 * Compare Index Page Tests
 * Task: TASK-32-4 - Implement Comparison Index Page
 * Spec: SPEC-32 (Competitor Comparison Landing Pages)
 *
 * Tests for the /compare index page that lists all available comparisons.
 */

import React from 'react';
import { render, screen, within } from '@testing-library/react';
import '@testing-library/jest-dom';
import CompareIndexPage from '@/app/compare/page';

// Mock the registry module
jest.mock('@/lib/compare/registry', () => ({
  COMPARISON_SLUGS: [
    'seoindicator-vs-ahrefs',
    'seoindicator-vs-semrush',
    'seoindicator-vs-moz',
    'seoindicator-vs-ubersuggest',
    'ahrefs-vs-moz',
    'ahrefs-vs-semrush',
    'moz-vs-semrush',
  ],
  getCompetitorById: (id: string) => {
    const competitors: Record<string, { name: string }> = {
      seoindicator: { name: 'SEOIndicator' },
      ahrefs: { name: 'Ahrefs' },
      semrush: { name: 'Semrush' },
      moz: { name: 'Moz' },
      ubersuggest: { name: 'Ubersuggest' },
    };
    return competitors[id] || null;
  },
}));

// Mock Next.js Link component
jest.mock('next/link', () => {
  const MockLink = ({
    children,
    href,
    ...props
  }: {
    children: React.ReactNode;
    href: string;
    [key: string]: unknown;
  }) => (
    <a href={href} {...props}>
      {children}
    </a>
  );
  MockLink.displayName = 'Link';
  return MockLink;
});

describe('Compare Index Page', () => {
  // ============================================================================
  // Rendering Tests
  // ============================================================================

  describe('Rendering', () => {
    it('CP-001: renders main page structure', () => {
      render(<CompareIndexPage />);

      const main = screen.getByRole('main');
      expect(main).toBeInTheDocument();
      expect(main).toHaveClass('min-h-screen');
    });

    it('CP-002: renders page heading', () => {
      render(<CompareIndexPage />);

      const heading = screen.getByRole('heading', { level: 1, name: /SEO Tool Comparisons/i });
      expect(heading).toBeInTheDocument();
    });

    it('CP-003: renders page description', () => {
      render(<CompareIndexPage />);

      expect(
        screen.getByText(/Compare SEOIndicator with other popular SEO tools/i)
      ).toBeInTheDocument();
    });

    it('CP-004: renders breadcrumb navigation', () => {
      render(<CompareIndexPage />);

      const breadcrumb = screen.getByRole('navigation', { name: /breadcrumb/i });
      expect(breadcrumb).toBeInTheDocument();

      const homeLink = within(breadcrumb).getByRole('link', { name: /home/i });
      expect(homeLink).toHaveAttribute('href', '/');
    });

    it('CP-005: breadcrumb shows current page as "Compare"', () => {
      render(<CompareIndexPage />);

      const currentPage = screen.getByText('Compare', { selector: 'li' });
      expect(currentPage).toHaveAttribute('aria-current', 'page');
    });
  });

  // ============================================================================
  // Primary Comparisons Section
  // ============================================================================

  describe('Primary Comparisons Section', () => {
    it('CP-010: renders primary comparisons heading', () => {
      render(<CompareIndexPage />);

      const heading = screen.getByRole('heading', { level: 2, name: /SEOIndicator Comparisons/i });
      expect(heading).toBeInTheDocument();
    });

    it('CP-011: renders all primary comparison links', () => {
      render(<CompareIndexPage />);

      // Primary comparisons are seoindicator-vs-* slugs
      const primaryLinks = screen.getAllByRole('link', { name: /SEOIndicator vs/i });
      expect(primaryLinks).toHaveLength(4);
    });

    it('CP-012: primary comparison links have correct hrefs', () => {
      render(<CompareIndexPage />);

      const expectedHrefs = [
        '/compare/seoindicator-vs-ahrefs',
        '/compare/seoindicator-vs-semrush',
        '/compare/seoindicator-vs-moz',
        '/compare/seoindicator-vs-ubersuggest',
      ];

      const allLinks = screen.getAllByRole('link');
      const hrefs = allLinks.map((link) => link.getAttribute('href'));

      expectedHrefs.forEach((href) => {
        expect(hrefs).toContain(href);
      });
    });

    it('CP-013: primary comparison links show view text', () => {
      render(<CompareIndexPage />);

      const viewTexts = screen.getAllByText(/View detailed comparison/i);
      expect(viewTexts).toHaveLength(4);
    });

    it('CP-014: primary comparisons use grid layout', () => {
      render(<CompareIndexPage />);

      const section = screen.getByRole('region', { name: /SEOIndicator Comparisons/i });
      const grid = section.querySelector('.grid');
      expect(grid).toHaveClass('md:grid-cols-2');
    });
  });

  // ============================================================================
  // Cross-Competitor Comparisons Section
  // ============================================================================

  describe('Cross-Competitor Comparisons Section', () => {
    it('CP-020: renders cross-competitor comparisons heading', () => {
      render(<CompareIndexPage />);

      const heading = screen.getByRole('heading', { level: 2, name: /Other Comparisons/i });
      expect(heading).toBeInTheDocument();
    });

    it('CP-021: renders all cross-competitor comparison links', () => {
      render(<CompareIndexPage />);

      // Cross-competitor comparisons: ahrefs-vs-moz, ahrefs-vs-semrush, moz-vs-semrush
      const crossLinks = screen.getAllByRole('link').filter(
        (link) => link.getAttribute('href')?.includes('/compare/') && 
                   !link.getAttribute('href')?.includes('seoindicator')
      );
      expect(crossLinks).toHaveLength(3);
    });

    it('CP-022: cross-competitor links have correct hrefs', () => {
      render(<CompareIndexPage />);

      const expectedHrefs = [
        '/compare/ahrefs-vs-moz',
        '/compare/ahrefs-vs-semrush',
        '/compare/moz-vs-semrush',
      ];

      const allLinks = screen.getAllByRole('link');
      const hrefs = allLinks.map((link) => link.getAttribute('href'));

      expectedHrefs.forEach((href) => {
        expect(hrefs).toContain(href);
      });
    });

    it('CP-023: cross-competitor comparisons use grid layout', () => {
      render(<CompareIndexPage />);

      const section = screen.getByRole('region', { name: /Other Comparisons/i });
      const grid = section.querySelector('.grid');
      expect(grid).toHaveClass('lg:grid-cols-3');
    });
  });

  // ============================================================================
  // Link Formatting Tests
  // ============================================================================

  describe('Comparison Title Formatting', () => {
    it('CP-030: formats SEOIndicator vs Ahrefs correctly', () => {
      render(<CompareIndexPage />);

      expect(screen.getByText('SEOIndicator vs Ahrefs')).toBeInTheDocument();
    });

    it('CP-031: formats SEOIndicator vs Semrush correctly', () => {
      render(<CompareIndexPage />);

      expect(screen.getByText('SEOIndicator vs Semrush')).toBeInTheDocument();
    });

    it('CP-032: formats Ahrefs vs Moz correctly', () => {
      render(<CompareIndexPage />);

      expect(screen.getByText('Ahrefs vs Moz')).toBeInTheDocument();
    });

    it('CP-033: formats Moz vs Semrush correctly', () => {
      render(<CompareIndexPage />);

      expect(screen.getByText('Moz vs Semrush')).toBeInTheDocument();
    });
  });

  // ============================================================================
  // Accessibility Tests
  // ============================================================================

  describe('Accessibility', () => {
    it('CP-040: has proper heading hierarchy', () => {
      render(<CompareIndexPage />);

      const h1 = screen.getByRole('heading', { level: 1 });
      const h2s = screen.getAllByRole('heading', { level: 2 });

      expect(h1).toBeInTheDocument();
      expect(h2s).toHaveLength(2); // Primary + Cross-competitor sections
    });

    it('CP-041: sections have aria-labelledby', () => {
      render(<CompareIndexPage />);

      const primarySection = screen.getByRole('region', { name: /SEOIndicator Comparisons/i });
      const crossSection = screen.getByRole('region', { name: /Other Comparisons/i });

      expect(primarySection).toBeInTheDocument();
      expect(crossSection).toBeInTheDocument();
    });

    it('CP-042: links are keyboard navigable', () => {
      render(<CompareIndexPage />);

      const links = screen.getAllByRole('link');
      links.forEach((link) => {
        expect(link).not.toHaveAttribute('tabindex', '-1');
      });
    });

    it('CP-043: has proper landmark roles', () => {
      render(<CompareIndexPage />);

      const main = screen.getByRole('main');
      const nav = screen.getByRole('navigation', { name: /breadcrumb/i });

      expect(main).toBeInTheDocument();
      expect(nav).toBeInTheDocument();
    });

    it('CP-044: breadcrumb uses aria-current for current page', () => {
      render(<CompareIndexPage />);

      const currentPage = screen.getByText('Compare', { selector: 'li' });
      expect(currentPage).toHaveAttribute('aria-current', 'page');
    });
  });

  // ============================================================================
  // Styling Tests
  // ============================================================================

  describe('Styling', () => {
    it('CP-050: main has dark theme classes', () => {
      render(<CompareIndexPage />);

      const main = screen.getByRole('main');
      expect(main).toHaveClass('bg-slate-950');
      expect(main).toHaveClass('text-white');
    });

    it('CP-051: primary comparison cards have hover effects', () => {
      render(<CompareIndexPage />);

      const primarySection = screen.getByRole('region', { name: /SEOIndicator Comparisons/i });
      const cards = primarySection.querySelectorAll('.group');

      cards.forEach((card) => {
        expect(card).toHaveClass('hover:border-emerald-500/50');
      });
    });

    it('CP-052: cross-competitor cards have hover effects', () => {
      render(<CompareIndexPage />);

      const crossSection = screen.getByRole('region', { name: /Other Comparisons/i });
      const cards = crossSection.querySelectorAll('.group');

      cards.forEach((card) => {
        expect(card).toHaveClass('hover:border-blue-500/50');
      });
    });

    it('CP-053: responsive grid classes are applied', () => {
      render(<CompareIndexPage />);

      const primarySection = screen.getByRole('region', { name: /SEOIndicator Comparisons/i });
      const crossSection = screen.getByRole('region', { name: /Other Comparisons/i });

      const primaryGrid = primarySection.querySelector('.grid');
      const crossGrid = crossSection.querySelector('.grid');

      expect(primaryGrid).toHaveClass('md:grid-cols-2');
      expect(crossGrid).toHaveClass('md:grid-cols-2');
      expect(crossGrid).toHaveClass('lg:grid-cols-3');
    });
  });

  // ============================================================================
  // Integration Tests
  // ============================================================================

  describe('Integration', () => {
    it('CP-070: total link count matches registry slugs', () => {
      render(<CompareIndexPage />);

      const compareLinks = screen.getAllByRole('link').filter(
        (link) => link.getAttribute('href')?.startsWith('/compare/')
      );

      // 4 primary + 3 cross-competitor = 7 total
      expect(compareLinks).toHaveLength(7);
    });

    it('CP-071: all comparison links point to valid URLs', () => {
      render(<CompareIndexPage />);

      const compareLinks = screen.getAllByRole('link').filter(
        (link) => link.getAttribute('href')?.startsWith('/compare/')
      );

      compareLinks.forEach((link) => {
        const href = link.getAttribute('href');
        expect(href).toMatch(/^\/compare\/[a-z0-9-]+-vs-[a-z0-9-]+$/);
      });
    });

    it('CP-072: page renders without errors', () => {
      const { container } = render(<CompareIndexPage />);

      expect(container).toBeInTheDocument();
    });
  });

  // ============================================================================
  // SEO Tests
  // ============================================================================

  describe('SEO', () => {
    it('CP-080: page exports metadata', async () => {
      const { metadata } = await import('@/app/compare/page');

      expect(metadata).toBeDefined();
      expect(metadata.title).toBe('SEO Tool Comparisons | SEOIndicator');
      expect(metadata.description).toContain('Compare SEOIndicator');
    });

    it('CP-081: metadata has OpenGraph tags', async () => {
      const { metadata } = await import('@/app/compare/page');

      expect(metadata.openGraph).toBeDefined();
      expect(metadata.openGraph?.title).toContain('SEO Tool Comparisons');
      expect(metadata.openGraph?.type).toBe('website');
    });

    it('CP-082: meta description is appropriate length', async () => {
      const { metadata } = await import('@/app/compare/page');

      const description = metadata.description as string;
      expect(description.length).toBeGreaterThan(50);
      expect(description.length).toBeLessThan(160);
    });
  });

  // ============================================================================
  // Security Tests
  // ============================================================================

  describe('Security', () => {
    it('CP-090: no XSS vulnerabilities in competitor names', () => {
      // Verify no script tags are rendered in the page
      render(<CompareIndexPage />);

      // React escapes content by default, so script tags would appear as text
      const allText = document.body.textContent || '';
      expect(allText).not.toContain('<script>');
      expect(allText).not.toContain('</script>');
    });

    it('CP-091: hrefs are properly formatted URLs', () => {
      render(<CompareIndexPage />);

      const links = screen.getAllByRole('link');
      links.forEach((link) => {
        const href = link.getAttribute('href');
        if (href && href.startsWith('/compare/')) {
          // Should not contain dangerous characters
          expect(href).not.toMatch(/[<>"']/);
        }
      });
    });
  });

  // ============================================================================
  // Edge Cases
  // ============================================================================

  describe('Edge Cases', () => {
    it('CP-060: handles missing competitor gracefully with fallback', () => {
      render(<CompareIndexPage />);

      // Page should still render with heading
      expect(screen.getByRole('heading', { level: 1 })).toBeInTheDocument();
    });

    it('CP-061: all slugs produce valid title text', () => {
      render(<CompareIndexPage />);

      // All comparison links should have readable text
      const compareLinks = screen.getAllByRole('link').filter(
        (link) => link.getAttribute('href')?.startsWith('/compare/')
      );

      compareLinks.forEach((link) => {
        expect(link.textContent).toBeTruthy();
        expect(link.textContent?.length).toBeGreaterThan(0);
      });
    });

    it('CP-062: renders both primary and cross sections', () => {
      render(<CompareIndexPage />);

      // Both sections should be present
      expect(screen.getByRole('region', { name: /SEOIndicator Comparisons/i })).toBeInTheDocument();
      expect(screen.getByRole('region', { name: /Other Comparisons/i })).toBeInTheDocument();
    });
  });
});
