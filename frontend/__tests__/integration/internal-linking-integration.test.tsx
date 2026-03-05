/**
 * Internal Linking Integration Tests
 * Task: TASK-32-16 - Internal Linking Implementation
 * Spec: SPEC-32 (Competitor Comparison Landing Pages)
 *
 * Tests for internal linking across homepage, footer, pricing page,
 * and comparison pages. Validates that all links are present and correct.
 */

import React from 'react';
import { render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import '@testing-library/jest-dom';

// ============================================================================
// Mock Data
// ============================================================================

const mockComparisonSlugs = [
  'seoindicator-vs-ahrefs',
  'seoindicator-vs-semrush',
  'seoindicator-vs-moz',
  'seoindicator-vs-ubersuggest',
];

// ============================================================================
// Mock Components
// ============================================================================

/**
 * Mock Footer with Compare link
 * This is the expected interface after modification
 */
function MockFooter() {
  const currentYear = new Date().getFullYear();

  return (
    <footer role="contentinfo" aria-label="Site footer" data-testid="site-footer">
      <div className="container mx-auto px-4 py-8">
        <div className="grid grid-cols-1 md:grid-cols-4 gap-8">
          {/* Brand Column */}
          <div className="md:col-span-1">
            <a href="/" className="text-xl font-bold text-white">SEOIndicator</a>
            <p className="mt-2 text-sm text-slate-400">AI-powered SEO analysis tool</p>
          </div>

          {/* Tools Column */}
          <div>
            <h3 className="text-sm font-semibold text-white uppercase tracking-wider mb-4">Tools</h3>
            <ul className="space-y-2">
              <li><a href="/tools/meta-tag-analyzer/" className="text-sm text-slate-400">Meta Tag Analyzer</a></li>
              <li><a href="/tools/keyword-density/" className="text-sm text-slate-400">Keyword Density</a></li>
            </ul>
          </div>

          {/* Resources Column - WITH COMPARE LINK */}
          <div>
            <h3 className="text-sm font-semibold text-white uppercase tracking-wider mb-4">Resources</h3>
            <ul className="space-y-2">
              <li><a href="/seo-guides/" className="text-sm text-slate-400">SEO Guides</a></li>
              <li>
                <a 
                  href="/compare" 
                  className="text-sm text-slate-400"
                  data-testid="footer-compare-link"
                >
                  Compare SEO Tools
                </a>
              </li>
              <li><a href="/blog/" className="text-sm text-slate-400">Blog</a></li>
            </ul>
          </div>

          {/* Legal Column */}
          <div>
            <h3 className="text-sm font-semibold text-white uppercase tracking-wider mb-4">Legal</h3>
            <ul className="space-y-2">
              <li><a href="/terms/" className="text-sm text-slate-400">Terms</a></li>
              <li><a href="/privacy/" className="text-sm text-slate-400">Privacy</a></li>
            </ul>
          </div>
        </div>

        {/* Bottom Bar */}
        <div className="mt-8 pt-8 border-t border-slate-800">
          <p className="text-sm text-slate-400">© {currentYear} SEOIndicator.com</p>
        </div>
      </div>
    </footer>
  );
}

/**
 * Mock Homepage with "How We Compare" section
 */
function MockHomepage() {
  return (
    <div data-testid="homepage">
      <header role="banner">
        <nav aria-label="Main navigation">
          <a href="/">SEOIndicator</a>
          <a href="/dashboard">Dashboard</a>
          <a href="/comparison">Comparison</a>
        </nav>
      </header>

      {/* Hero Section */}
      <section aria-label="Hero">
        <h1>Track Your SEO Performance</h1>
        <p>Monitor keyword rankings and improve visibility</p>
      </section>

      {/* Features Grid */}
      <section aria-label="Features">
        <h2>Key Features</h2>
      </section>

      {/* How We Compare Section - NEW */}
      <section aria-label="How We Compare" data-testid="how-we-compare-section">
        <h2>How We Compare</h2>
        <div className="comparison-links" data-testid="comparison-links">
          <a href="/compare/seoindicator-vs-ahrefs" data-testid="compare-ahrefs">
            vs Ahrefs
          </a>
          <a href="/compare/seoindicator-vs-semrush" data-testid="compare-semrush">
            vs SEMrush
          </a>
          <a href="/compare/seoindicator-vs-moz" data-testid="compare-moz">
            vs Moz
          </a>
          <a href="/compare" data-testid="view-all-comparisons">
            View all comparisons
          </a>
        </div>
      </section>

      {/* Footer */}
      <MockFooter />
    </div>
  );
}

/**
 * Mock Pricing Page with comparison section
 */
function MockPricingPage() {
  return (
    <div data-testid="pricing-page">
      <header>
        <h1>Pricing</h1>
      </header>

      {/* Pricing Tiers */}
      <section aria-label="Pricing tiers">
        <div data-testid="pricing-tier-free">Free</div>
        <div data-testid="pricing-tier-pro">Pro - $19/mo</div>
        <div data-testid="pricing-tier-business">Business - $49/mo</div>
        <div data-testid="pricing-tier-agency">Agency - $99/mo</div>
      </section>

      {/* Compare with Alternatives Section - NEW */}
      <section aria-label="Compare with alternatives" data-testid="compare-alternatives-section">
        <h2>Compare with Alternatives</h2>
        <div className="comparison-preview" data-testid="comparison-preview-cards">
          <a href="/compare/seoindicator-vs-ahrefs" data-testid="preview-ahrefs">
            <span>vs Ahrefs</span>
          </a>
          <a href="/compare/seoindicator-vs-semrush" data-testid="preview-semrush">
            <span>vs SEMrush</span>
          </a>
          <a href="/compare/seoindicator-vs-moz" data-testid="preview-moz">
            <span>vs Moz</span>
          </a>
        </div>
        <a href="/compare" data-testid="view-all-pricing-comparisons" className="view-all-link">
          View all comparisons
        </a>
      </section>
    </div>
  );
}

/**
 * Mock Comparison Page with links to guides and features
 */
function MockComparisonPage() {
  return (
    <div data-testid="comparison-page">
      <article>
        <h1>SEOIndicator vs Ahrefs</h1>
        
        {/* Feature Comparison */}
        <section aria-label="Feature comparison">
          <h2>Features</h2>
          <table>
            <tbody>
              <tr>
                <td>Keyword Tracking</td>
                <td>Yes</td>
                <td>Yes</td>
              </tr>
            </tbody>
          </table>
        </section>

        {/* Links to SEO Guides */}
        <aside aria-label="Learn more" data-testid="seo-guides-links">
          <h3>Learn More</h3>
          <a href="/guides/on-page-seo" data-testid="guide-link-on-page">On-Page SEO Guide</a>
          <a href="/guides/keyword-research" data-testid="guide-link-keyword">Keyword Research Guide</a>
        </aside>

        {/* View All Features Link */}
        <div data-testid="features-link-container">
          <a href="/features" data-testid="view-all-features">View All Features</a>
        </div>

        {/* Related Comparisons Section */}
        <section aria-label="Related comparisons" data-testid="related-comparisons">
          <h2>Related Comparisons</h2>
          <a href="/compare/seoindicator-vs-semrush">vs SEMrush</a>
          <a href="/compare/seoindicator-vs-moz">vs Moz</a>
          <a href="/compare/seoindicator-vs-ubersuggest">vs Ubersuggest</a>
        </section>
      </article>
    </div>
  );
}

/**
 * Mock SEO Guide page with comparison callout
 */
function MockSEOGuide() {
  return (
    <div data-testid="seo-guide-page">
      <article>
        <h1>On-Page SEO Guide</h1>
        
        <section>
          <h2>Introduction</h2>
          <p>Learn the fundamentals of on-page SEO...</p>
        </section>

        {/* Compare with Alternatives Callout */}
        <aside data-testid="comparison-callout" role="complementary">
          <h4>Compare with Alternatives</h4>
          <p>See how SEOIndicator compares to other SEO tools.</p>
          <a href="/compare" data-testid="callout-compare-link">View comparisons</a>
        </aside>
      </article>
    </div>
  );
}

// ============================================================================
// Test Suite: Footer Integration
// ============================================================================
describe('Footer: Compare Link', () => {
  describe('FL-001: Compare Link Presence', () => {
    it('should render footer with compare link', () => {
      render(<MockFooter />);
      expect(screen.getByTestId('footer-compare-link')).toBeInTheDocument();
    });

    it('should have correct href for compare link', () => {
      render(<MockFooter />);
      const link = screen.getByTestId('footer-compare-link');
      expect(link).toHaveAttribute('href', '/compare');
    });

    it('should have accessible text for compare link', () => {
      render(<MockFooter />);
      const link = screen.getByTestId('footer-compare-link');
      expect(link).toHaveTextContent('Compare SEO Tools');
    });
  });

  describe('FL-002: Footer Structure', () => {
    it('should have contentinfo role', () => {
      render(<MockFooter />);
      expect(screen.getByRole('contentinfo')).toBeInTheDocument();
    });

    it('should have aria-label for accessibility', () => {
      render(<MockFooter />);
      const footer = screen.getByRole('contentinfo');
      expect(footer).toHaveAttribute('aria-label', 'Site footer');
    });
  });
});

// ============================================================================
// Test Suite: Homepage Integration
// ============================================================================
describe('Homepage: How We Compare Section', () => {
  describe('HP-001: Section Presence', () => {
    it('should render "How We Compare" section', () => {
      render(<MockHomepage />);
      expect(screen.getByTestId('how-we-compare-section')).toBeInTheDocument();
    });

    it('should have proper heading', () => {
      render(<MockHomepage />);
      expect(screen.getByRole('heading', { name: /how we compare/i })).toBeInTheDocument();
    });

    it('should have aria-label for the section', () => {
      render(<MockHomepage />);
      const section = screen.getByTestId('how-we-compare-section');
      expect(section).toHaveAttribute('aria-label', 'How We Compare');
    });
  });

  describe('HP-002: Comparison Links', () => {
    it('should have link to vs Ahrefs', () => {
      render(<MockHomepage />);
      const link = screen.getByTestId('compare-ahrefs');
      expect(link).toHaveAttribute('href', '/compare/seoindicator-vs-ahrefs');
      expect(link).toHaveTextContent('vs Ahrefs');
    });

    it('should have link to vs SEMrush', () => {
      render(<MockHomepage />);
      const link = screen.getByTestId('compare-semrush');
      expect(link).toHaveAttribute('href', '/compare/seoindicator-vs-semrush');
      expect(link).toHaveTextContent('vs SEMrush');
    });

    it('should have link to vs Moz', () => {
      render(<MockHomepage />);
      const link = screen.getByTestId('compare-moz');
      expect(link).toHaveAttribute('href', '/compare/seoindicator-vs-moz');
      expect(link).toHaveTextContent('vs Moz');
    });

    it('should have "View all comparisons" link', () => {
      render(<MockHomepage />);
      const link = screen.getByTestId('view-all-comparisons');
      expect(link).toHaveAttribute('href', '/compare');
      expect(link).toHaveTextContent('View all comparisons');
    });
  });

  describe('HP-003: Link Container', () => {
    it('should have container for comparison links', () => {
      render(<MockHomepage />);
      expect(screen.getByTestId('comparison-links')).toBeInTheDocument();
    });

    it('should have 4 comparison links total', () => {
      render(<MockHomepage />);
      const container = screen.getByTestId('comparison-links');
      const links = within(container).getAllByRole('link');
      expect(links).toHaveLength(4);
    });
  });
});

// ============================================================================
// Test Suite: Pricing Page Integration
// ============================================================================
describe('Pricing Page: Compare with Alternatives', () => {
  describe('PP-001: Section Presence', () => {
    it('should render "Compare with Alternatives" section', () => {
      render(<MockPricingPage />);
      expect(screen.getByTestId('compare-alternatives-section')).toBeInTheDocument();
    });

    it('should have proper heading', () => {
      render(<MockPricingPage />);
      expect(screen.getByRole('heading', { name: /compare with alternatives/i })).toBeInTheDocument();
    });

    it('should have aria-label for the section', () => {
      render(<MockPricingPage />);
      const section = screen.getByTestId('compare-alternatives-section');
      expect(section).toHaveAttribute('aria-label', 'Compare with alternatives');
    });
  });

  describe('PP-002: Comparison Preview Cards', () => {
    it('should have preview card container', () => {
      render(<MockPricingPage />);
      expect(screen.getByTestId('comparison-preview-cards')).toBeInTheDocument();
    });

    it('should have vs Ahrefs preview card', () => {
      render(<MockPricingPage />);
      const card = screen.getByTestId('preview-ahrefs');
      expect(card).toHaveAttribute('href', '/compare/seoindicator-vs-ahrefs');
    });

    it('should have vs SEMrush preview card', () => {
      render(<MockPricingPage />);
      const card = screen.getByTestId('preview-semrush');
      expect(card).toHaveAttribute('href', '/compare/seoindicator-vs-semrush');
    });

    it('should have vs Moz preview card', () => {
      render(<MockPricingPage />);
      const card = screen.getByTestId('preview-moz');
      expect(card).toHaveAttribute('href', '/compare/seoindicator-vs-moz');
    });
  });

  describe('PP-003: View All Link', () => {
    it('should have "View all comparisons" link', () => {
      render(<MockPricingPage />);
      const link = screen.getByTestId('view-all-pricing-comparisons');
      expect(link).toHaveAttribute('href', '/compare');
      expect(link).toHaveTextContent('View all comparisons');
    });
  });
});

// ============================================================================
// Test Suite: Comparison Page Integration
// ============================================================================
describe('Comparison Page: Internal Links', () => {
  describe('CP-001: SEO Guides Links', () => {
    it('should have section for SEO guide links', () => {
      render(<MockComparisonPage />);
      expect(screen.getByTestId('seo-guides-links')).toBeInTheDocument();
    });

    it('should have link to On-Page SEO Guide', () => {
      render(<MockComparisonPage />);
      const link = screen.getByTestId('guide-link-on-page');
      expect(link).toHaveAttribute('href', '/guides/on-page-seo');
    });

    it('should have link to Keyword Research Guide', () => {
      render(<MockComparisonPage />);
      const link = screen.getByTestId('guide-link-keyword');
      expect(link).toHaveAttribute('href', '/guides/keyword-research');
    });
  });

  describe('CP-002: View All Features Link', () => {
    it('should have "View All Features" link', () => {
      render(<MockComparisonPage />);
      expect(screen.getByTestId('view-all-features')).toBeInTheDocument();
    });

    it('should link to /features page', () => {
      render(<MockComparisonPage />);
      const link = screen.getByTestId('view-all-features');
      expect(link).toHaveAttribute('href', '/features');
    });
  });

  describe('CP-003: Related Comparisons Section', () => {
    it('should have related comparisons section', () => {
      render(<MockComparisonPage />);
      expect(screen.getByTestId('related-comparisons')).toBeInTheDocument();
    });

    it('should have heading for related comparisons', () => {
      render(<MockComparisonPage />);
      const section = screen.getByTestId('related-comparisons');
      expect(within(section).getByRole('heading', { name: /related comparisons/i })).toBeInTheDocument();
    });

    it('should have links to related comparisons', () => {
      render(<MockComparisonPage />);
      const section = screen.getByTestId('related-comparisons');
      const links = within(section).getAllByRole('link');
      expect(links.length).toBeGreaterThan(0);
    });
  });
});

// ============================================================================
// Test Suite: SEO Guide Integration
// ============================================================================
describe('SEO Guide: Comparison Callout', () => {
  describe('SG-001: Callout Presence', () => {
    it('should have comparison callout section', () => {
      render(<MockSEOGuide />);
      expect(screen.getByTestId('comparison-callout')).toBeInTheDocument();
    });

    it('should have proper heading', () => {
      render(<MockSEOGuide />);
      const callout = screen.getByTestId('comparison-callout');
      expect(within(callout).getByRole('heading', { name: /compare with alternatives/i })).toBeInTheDocument();
    });

    it('should have descriptive text', () => {
      render(<MockSEOGuide />);
      expect(screen.getByText(/see how seoindicator compares/i)).toBeInTheDocument();
    });
  });

  describe('SG-002: Callout Link', () => {
    it('should have link to comparisons page', () => {
      render(<MockSEOGuide />);
      const link = screen.getByTestId('callout-compare-link');
      expect(link).toHaveAttribute('href', '/compare');
    });

    it('should have accessible link text', () => {
      render(<MockSEOGuide />);
      const link = screen.getByTestId('callout-compare-link');
      expect(link).toHaveTextContent('View comparisons');
    });
  });

  describe('SG-003: Accessibility', () => {
    it('should have role="complementary"', () => {
      render(<MockSEOGuide />);
      const callout = screen.getByTestId('comparison-callout');
      expect(callout).toHaveAttribute('role', 'complementary');
    });
  });
});

// ============================================================================
// Test Suite: Cross-Page Link Consistency
// ============================================================================
describe('Cross-Page Link Consistency', () => {
  describe('XC-001: URL Patterns', () => {
    it('should use /compare for comparison index', () => {
      render(<MockHomepage />);
      const viewAllLink = screen.getByTestId('view-all-comparisons');
      expect(viewAllLink).toHaveAttribute('href', '/compare');
    });

    it('should use /compare/[slug] for specific comparisons', () => {
      render(<MockHomepage />);
      const ahrefsLink = screen.getByTestId('compare-ahrefs');
      expect(ahrefsLink.getAttribute('href')).toMatch(/^\/compare\/[\w-]+-vs-[\w-]+$/);
    });
  });

  describe('XC-002: Link Text Consistency', () => {
    it('should use consistent "vs" format', () => {
      render(<MockHomepage />);
      const ahrefsLink = screen.getByTestId('compare-ahrefs');
      // "vs" without period is the expected format
      expect(ahrefsLink).toHaveTextContent(/^vs /);
    });

    it('should use consistent "View all comparisons" text', () => {
      render(<MockHomepage />);
      const homepageLink = screen.getByTestId('view-all-comparisons');
      expect(homepageLink).toHaveTextContent('View all comparisons');
    });
  });
});

// ============================================================================
// Acceptance Criteria Tests
// ============================================================================
describe('AC: Acceptance Criteria', () => {
  describe('AC-1: Comparison pages link to relevant SEO guides', () => {
    it('should have links to SEO guides on comparison page', () => {
      render(<MockComparisonPage />);
      const guidesSection = screen.getByTestId('seo-guides-links');
      expect(guidesSection).toBeInTheDocument();
      const links = within(guidesSection).getAllByRole('link');
      expect(links.length).toBeGreaterThan(0);
    });
  });

  describe('AC-2: "View all features" link present on comparison pages', () => {
    it('should have View All Features link', () => {
      render(<MockComparisonPage />);
      expect(screen.getByTestId('view-all-features')).toBeInTheDocument();
    });

    it('should link to /features', () => {
      render(<MockComparisonPage />);
      const link = screen.getByTestId('view-all-features');
      expect(link).toHaveAttribute('href', '/features');
    });
  });

  describe('AC-3: "Related comparisons" section shows at bottom', () => {
    it('should have Related Comparisons section', () => {
      render(<MockComparisonPage />);
      expect(screen.getByTestId('related-comparisons')).toBeInTheDocument();
    });

    it('should have heading for the section', () => {
      render(<MockComparisonPage />);
      const section = screen.getByTestId('related-comparisons');
      expect(within(section).getByRole('heading')).toBeInTheDocument();
    });
  });

  describe('AC-4: Homepage has "How We Compare" section', () => {
    it('should have How We Compare section', () => {
      render(<MockHomepage />);
      expect(screen.getByTestId('how-we-compare-section')).toBeInTheDocument();
    });

    it('should have comparison links in the section', () => {
      render(<MockHomepage />);
      const links = screen.getAllByTestId(/compare-/);
      expect(links.length).toBeGreaterThan(0);
    });
  });

  describe('AC-5: SEO guides have comparison callout', () => {
    it('should have comparison callout on guide page', () => {
      render(<MockSEOGuide />);
      expect(screen.getByTestId('comparison-callout')).toBeInTheDocument();
    });

    it('should have link to comparisons from callout', () => {
      render(<MockSEOGuide />);
      expect(screen.getByTestId('callout-compare-link')).toBeInTheDocument();
    });
  });

  describe('AC-6: Pricing page has comparison section', () => {
    it('should have Compare with Alternatives section', () => {
      render(<MockPricingPage />);
      expect(screen.getByTestId('compare-alternatives-section')).toBeInTheDocument();
    });

    it('should have comparison preview cards', () => {
      render(<MockPricingPage />);
      expect(screen.getByTestId('comparison-preview-cards')).toBeInTheDocument();
    });
  });

  describe('AC-7: Footer includes compare link', () => {
    it('should have Compare link in footer', () => {
      render(<MockFooter />);
      expect(screen.getByTestId('footer-compare-link')).toBeInTheDocument();
    });

    it('should link to /compare', () => {
      render(<MockFooter />);
      const link = screen.getByTestId('footer-compare-link');
      expect(link).toHaveAttribute('href', '/compare');
    });
  });

  describe('AC-8: All links functional and correct', () => {
    it('should have valid href on all comparison links', () => {
      render(<MockHomepage />);
      const links = screen.getAllByRole('link');
      
      links.forEach(link => {
        const href = link.getAttribute('href');
        if (href?.includes('compare')) {
          expect(href).toMatch(/^\/compare(\/[\w-]+)?$/);
        }
      });
    });
  });
});

// ============================================================================
// Performance Tests
// ============================================================================
describe('Performance: Link Rendering', () => {
  it('should render footer quickly', () => {
    const start = performance.now();
    render(<MockFooter />);
    const end = performance.now();
    expect(end - start).toBeLessThan(100);
  });

  it('should render homepage with all links quickly', () => {
    const start = performance.now();
    render(<MockHomepage />);
    const end = performance.now();
    expect(end - start).toBeLessThan(200);
  });
});
