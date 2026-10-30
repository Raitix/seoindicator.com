/**
 * AboutContent Component Tests - TASK-30-2
 * Tests for the AboutContent component used in /about page
 */

import React from 'react';
import { render, screen, within } from '@testing-library/react';
import { axe, toHaveNoViolations } from 'jest-axe';
import '@testing-library/jest-dom';

// Extend Jest matchers
expect.extend(toHaveNoViolations);

// Mock Next.js Image
jest.mock('next/image', () => ({
  __esModule: true,
  default: (props: any) => {
    // eslint-disable-next-line @next/next/no-img-element
    return <img {...props} alt={props.alt || ''} />;
  },
}));

// Mock Next.js Link
jest.mock('next/link', () => ({
  __esModule: true,
  default: ({ children, href, ...props }: any) => (
    <a href={href} {...props}>{children}</a>
  ),
}));

// Import the actual component after mocks
import AboutContent from '@/components/pages/AboutContent';

describe('AboutContent Component', () => {
  // ============================================
  // AC-001 to AC-004: Basic Rendering
  // ============================================
  describe('Basic Rendering', () => {
    it('AC-001: Component renders', () => {
      const { container } = render(<AboutContent />);
      expect(container.firstChild).toBeInTheDocument();
    });

    it('AC-002: Hero section renders', () => {
      render(<AboutContent />);
      expect(screen.getByRole('heading', { level: 1 })).toBeInTheDocument();
    });

    it('AC-003: Hero has mission text about SEO accessibility', () => {
      render(<AboutContent />);
      const h1 = screen.getByRole('heading', { level: 1 });
      expect(h1).toHaveTextContent(/SEO/i);
    });

    it('AC-004: Product overview section renders', () => {
      render(<AboutContent />);
      expect(screen.getByRole('heading', { name: /what we do/i })).toBeInTheDocument();
    });
  });

  // ============================================
  // AC-005 to AC-010: Features Section
  // ============================================
  describe('Features Section', () => {
    it('AC-005: Features section renders', () => {
      render(<AboutContent />);
      expect(screen.getByRole('heading', { name: /key features/i })).toBeInTheDocument();
    });

    it('AC-006: Features count is correct (3-5 features)', () => {
      render(<AboutContent />);
      // Look for feature titles from the actual component
      expect(screen.getByText(/17 SEO Indicators/i)).toBeInTheDocument();
      expect(screen.getByRole('heading', { name: /AI-Powered Suggestions/i })).toBeInTheDocument();
      expect(screen.getByRole('heading', { name: /Competitor Analysis/i })).toBeInTheDocument();
      expect(screen.getByRole('heading', { name: /Rank Tracking/i })).toBeInTheDocument();
      expect(screen.getByRole('heading', { name: /Instant Analysis/i })).toBeInTheDocument();
    });

    it('AC-007: Features have descriptions', () => {
      render(<AboutContent />);
      expect(screen.getByText(/Comprehensive analysis across technical SEO/i)).toBeInTheDocument();
    });

    it('AC-008: Stats section renders', () => {
      render(<AboutContent />);
      expect(screen.getByText('17')).toBeInTheDocument();
      expect(screen.getByText('100K+')).toBeInTheDocument();
    });
  });

  // ============================================
  // AC-011 to AC-017: Team Section & CTA
  // ============================================
  describe('Team Section & CTA', () => {
    it('AC-011: Team section renders', () => {
      render(<AboutContent />);
      expect(screen.getByRole('heading', { name: /built by seo enthusiasts/i })).toBeInTheDocument();
    });

    it('AC-012: Team values are present', () => {
      render(<AboutContent />);
      expect(screen.getByRole('heading', { name: /innovation/i })).toBeInTheDocument();
      expect(screen.getByRole('heading', { name: /accuracy/i })).toBeInTheDocument();
      expect(screen.getByRole('heading', { name: /privacy/i })).toBeInTheDocument();
    });

    it('AC-013: CTA section renders', () => {
      render(<AboutContent />);
      expect(screen.getByRole('heading', { name: /ready to improve your seo/i })).toBeInTheDocument();
    });

    it('AC-014: CTA button exists', () => {
      render(<AboutContent />);
      const ctaLink = screen.getByRole('link', { name: /get started free/i });
      expect(ctaLink).toBeInTheDocument();
    });

    it('AC-015: CTA links to register', () => {
      render(<AboutContent />);
      const ctaLink = screen.getByRole('link', { name: /get started free/i });
      expect(ctaLink).toHaveAttribute('href', '/register/');
    });

    it('AC-016: Secondary CTA (Explore Tools) exists', () => {
      render(<AboutContent />);
      const exploreLink = screen.getByRole('link', { name: /explore tools/i });
      expect(exploreLink).toBeInTheDocument();
      expect(exploreLink).toHaveAttribute('href', '/tools/');
    });
  });

  // ============================================
  // AC-018 to AC-027: Accessibility
  // ============================================
  describe('Accessibility', () => {
    it('AC-018: Each section has proper heading', () => {
      render(<AboutContent />);
      const headings = screen.getAllByRole('heading');
      expect(headings.length).toBeGreaterThan(0);
    });

    it('AC-019: H1 text exists', () => {
      render(<AboutContent />);
      const h1 = screen.getByRole('heading', { level: 1 });
      expect(h1.textContent).toBeTruthy();
    });

    it('AC-020: H2 count is correct', () => {
      render(<AboutContent />);
      const h2Elements = screen.getAllByRole('heading', { level: 2 });
      expect(h2Elements.length).toBeGreaterThanOrEqual(3);
    });

    it('AC-021: No heading level skips', () => {
      render(<AboutContent />);
      const h1 = screen.queryAllByRole('heading', { level: 1 });
      const h2 = screen.queryAllByRole('heading', { level: 2 });
      const h3 = screen.queryAllByRole('heading', { level: 3 });
      const h4 = screen.queryAllByRole('heading', { level: 4 });
      
      // If we have h3, we should have h2
      if (h3.length > 0) {
        expect(h2.length).toBeGreaterThan(0);
      }
      // If we have h4, we should have h3
      if (h4.length > 0) {
        expect(h3.length).toBeGreaterThan(0);
      }
    });

    it('AC-022: Links have accessible names', () => {
      render(<AboutContent />);
      const links = screen.getAllByRole('link');
      links.forEach(link => {
        const name = link.getAttribute('aria-label') || link.textContent;
        expect(name).toBeTruthy();
      });
    });

    it('AC-023: Sections use semantic HTML', () => {
      const { container } = render(<AboutContent />);
      const sections = container.querySelectorAll('section');
      expect(sections.length).toBeGreaterThan(0);
    });

    it('AC-024: How It Works section exists', () => {
      render(<AboutContent />);
      expect(screen.getByRole('heading', { name: /simple\. fast\. effective\./i })).toBeInTheDocument();
    });

    it('AC-025: Step indicators exist', () => {
      render(<AboutContent />);
      expect(screen.getByRole('heading', { name: /enter your url/i })).toBeInTheDocument();
      expect(screen.getByRole('heading', { name: /get your score/i })).toBeInTheDocument();
      expect(screen.getByRole('heading', { name: /take action/i })).toBeInTheDocument();
    });

    it('AC-026: No accessibility violations', async () => {
      const { container } = render(<AboutContent />);
      const results = await axe(container);
      expect(results).toHaveNoViolations();
    });
  });

  // ============================================
  // AC-031 to AC-037: Responsive Design
  // ============================================
  describe('Responsive Design', () => {
    it('AC-031: Mobile layout works at 375px', () => {
      global.innerWidth = 375;
      global.innerHeight = 667;
      
      const { container } = render(<AboutContent />);
      expect(container.firstChild).toBeInTheDocument();
    });

    it('AC-032: Tablet layout works at 768px', () => {
      global.innerWidth = 768;
      global.innerHeight = 1024;
      
      const { container } = render(<AboutContent />);
      expect(container.firstChild).toBeInTheDocument();
    });

    it('AC-033: Desktop layout works at 1024px', () => {
      global.innerWidth = 1024;
      global.innerHeight = 768;
      
      const { container } = render(<AboutContent />);
      expect(container.firstChild).toBeInTheDocument();
    });
  });

  // ============================================
  // Custom className Support
  // ============================================
  describe('Props', () => {
    it('accepts custom className', () => {
      const { container } = render(<AboutContent className="custom-class" />);
      expect(container.firstChild).toHaveClass('custom-class');
    });
  });

  // ============================================
  // Content Verification
  // ============================================
  describe('Content', () => {
    it('contains mission statement about accessibility', () => {
      render(<AboutContent />);
      // Use getAllByText since the text appears in multiple places
      const missionElements = screen.getAllByText(/accessible to everyone/i);
      expect(missionElements.length).toBeGreaterThan(0);
    });

    it('contains product description', () => {
      render(<AboutContent />);
      expect(screen.getByText(/all-in-one ai-powered seo analysis platform/i)).toBeInTheDocument();
    });

    it('contains back to home link', () => {
      render(<AboutContent />);
      const backLink = screen.getByRole('link', { name: /back to home/i });
      expect(backLink).toBeInTheDocument();
      expect(backLink).toHaveAttribute('href', '/');
    });

    it('contains learn more about how it works link', () => {
      render(<AboutContent />);
      const learnMoreLink = screen.getByRole('link', { name: /learn more about how it works/i });
      expect(learnMoreLink).toBeInTheDocument();
      expect(learnMoreLink).toHaveAttribute('href', '/how-it-works/');
    });
  });
});
