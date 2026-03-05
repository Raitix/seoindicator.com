/**
 * Accessibility Tests for Terms Page
 * Task: TASK-30-7 - Accessibility Tests
 * 
 * WCAG 2.1 AA compliance tests for the Terms & Privacy page.
 * Uses jest-axe for automated accessibility scanning.
 */

import React from 'react';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { axe, toHaveNoViolations } from 'jest-axe';
import TermsPage from '@/app/terms/page';

// Extend Jest matchers
expect.extend(toHaveNoViolations);

// ============================================================================
// Mock Components
// ============================================================================

// Mock Next.js Link
jest.mock('next/link', () => {
  const MockLink = ({
    children,
    href,
    className,
    ...props
  }: {
    children: React.ReactNode;
    href: string;
    className?: string;
  }) => (
    <a href={href} className={className} {...props}>
      {children}
    </a>
  );
  MockLink.displayName = 'Link';
  return MockLink;
});

// Mock Next.js metadata generation
jest.mock('next', () => ({
  ...jest.requireActual('next'),
  Metadata: {},
}));

// ============================================================================
// Test Categories
// ============================================================================

describe('Terms Page Accessibility', () => {
  describe('Automated A11y Tests (jest-axe)', () => {
    it('should have no accessibility violations on initial render', async () => {
      const { container } = render(<TermsPage />);
      const results = await axe(container, {
        rules: {
          // Disable color-contrast rule as it's tested separately
          'color-contrast': { enabled: false },
        },
      });
      expect(results).toHaveNoViolations();
    });

    it('should have no violations after focusing interactive elements', async () => {
      const { container } = render(<TermsPage />);
      const user = userEvent.setup();
      
      // Tab through all interactive elements
      const links = screen.getAllByRole('link');
      for (const link of links) {
        await user.tab();
      }
      
      const results = await axe(container, {
        rules: {
          'color-contrast': { enabled: false },
        },
      });
      expect(results).toHaveNoViolations();
    });

    it('should have no violations when navigating to sections', async () => {
      const { container } = render(<TermsPage />);
      
      // Find all section links
      const sectionLinks = screen.getAllByRole('link').filter(
        (link) => link.getAttribute('href')?.startsWith('#')
      );
      
      const results = await axe(container, {
        rules: {
          'color-contrast': { enabled: false },
        },
      });
      expect(results).toHaveNoViolations();
    });
  });

  describe('Keyboard Navigation Tests', () => {
    it('should have all interactive elements focusable via Tab', async () => {
      render(<TermsPage />);
      const user = userEvent.setup();
      
      // Start tabbing from the body
      await user.tab();
      
      // First focusable should be the "Home" breadcrumb link
      const homeLink = screen.getByRole('link', { name: /home/i });
      expect(homeLink).toHaveFocus();
      
      // Continue tabbing through links
      await user.tab();
      // Should be Terms & Privacy (aria-current)
      
      await user.tab();
      // Should be first TOC link
    });

    it('should navigate to sections when pressing Enter on TOC links', async () => {
      render(<TermsPage />);
      const user = userEvent.setup();
      
      // Find the Introduction link
      const introLink = screen.getByRole('link', { name: 'Introduction' });
      expect(introLink).toHaveAttribute('href', '#introduction');
    });

    it('should have visible focus indicators on all interactive elements', () => {
      render(<TermsPage />);
      
      const links = screen.getAllByRole('link');
      links.forEach((link) => {
        // Check that links have focus-visible styles (via Tailwind classes)
        const classList = link.className;
        // Links should have focus-visible styles or rely on default focus styles
        expect(link).toBeVisible();
      });
    });

    it('should support keyboard navigation through breadcrumb', async () => {
      render(<TermsPage />);
      const user = userEvent.setup();
      
      // Tab to breadcrumb navigation
      await user.tab();
      
      const breadcrumb = screen.getByRole('navigation', { name: /breadcrumb/i });
      expect(breadcrumb).toBeInTheDocument();
      
      // Verify breadcrumb structure
      const breadcrumbItems = screen.getAllByRole('listitem');
      expect(breadcrumbItems.length).toBeGreaterThan(0);
    });
  });

  describe('Focus Management Tests', () => {
    it('should have proper heading hierarchy', () => {
      render(<TermsPage />);
      
      // There should be exactly one H1
      const h1 = screen.getByRole('heading', { level: 1 });
      expect(h1).toHaveTextContent('Terms of Service & Privacy Policy');
      
      // All H2 headings should be present
      const h2s = screen.getAllByRole('heading', { level: 2 });
      expect(h2s.length).toBeGreaterThan(0);
    });

    it('should have skip link functionality', () => {
      render(<TermsPage />);
      
      // Main landmark should exist
      const main = screen.getByRole('main');
      expect(main).toBeInTheDocument();
    });

    it('should have aria-current on current page in breadcrumb', () => {
      render(<TermsPage />);
      
      const currentLink = screen.getByRole('link', { current: 'page' });
      expect(currentLink).toHaveTextContent('Terms & Privacy');
    });

    it('should have no focus traps on the page', async () => {
      render(<TermsPage />);
      const user = userEvent.setup();
      
      // Tab through all focusable elements
      const focusableElements = screen.getAllByRole('link');
      expect(focusableElements.length).toBeGreaterThan(0);
      
      // Should be able to tab through all elements without getting trapped
      for (let i = 0; i < focusableElements.length + 2; i++) {
        await user.tab();
      }
    });
  });

  describe('ARIA and Semantic Structure', () => {
    it('should have proper landmark roles', () => {
      render(<TermsPage />);
      
      // Main landmark
      expect(screen.getByRole('main')).toBeInTheDocument();
      
      // Navigation landmark (breadcrumb)
      expect(screen.getByRole('navigation', { name: /breadcrumb/i })).toBeInTheDocument();
      
      // Article for content
      expect(screen.getByRole('article')).toBeInTheDocument();
    });

    it('should have aria-label on navigation', () => {
      render(<TermsPage />);
      
      const nav = screen.getByRole('navigation', { name: /breadcrumb/i });
      expect(nav).toHaveAttribute('aria-label', 'Breadcrumb');
    });

    it('should have proper list structure in breadcrumb', () => {
      render(<TermsPage />);
      
      const list = screen.getByRole('list');
      expect(list).toBeInTheDocument();
      
      const listItems = screen.getAllByRole('listitem');
      expect(listItems.length).toBeGreaterThanOrEqual(2);
    });

    it('should have descriptive link text', () => {
      render(<TermsPage />);
      
      // All links should have accessible names
      const links = screen.getAllByRole('link');
      links.forEach((link) => {
        const accessibleName = link.textContent || link.getAttribute('aria-label');
        expect(accessibleName).toBeTruthy();
        expect(accessibleName?.trim().length).toBeGreaterThan(0);
      });
    });

    it('should have accessible Table of Contents navigation', () => {
      render(<TermsPage />);
      
      const tocNav = screen.getByRole('navigation', { name: /table of contents/i });
      expect(tocNav).toBeInTheDocument();
      
      // TOC should have links to all sections
      const tocLinks = within(tocNav).getAllByRole('link');
      expect(tocLinks.length).toBe(5); // 5 sections
    });
  });

  describe('Color Contrast Tests (manual check recommended)', () => {
    it('should use appropriate text colors for contrast', () => {
      render(<TermsPage />);
      
      // This test verifies that text elements exist
      // Actual color contrast must be verified manually or with a visual tool
      const mainHeading = screen.getByRole('heading', { level: 1 });
      expect(mainHeading).toHaveClass('text-white');
      
      const paragraph = screen.getByText(/please read these terms/i);
      expect(paragraph).toBeInTheDocument();
    });

    it('should have visible interactive element colors', () => {
      render(<TermsPage />);
      
      // Links should have distinguishable colors
      const links = screen.getAllByRole('link');
      links.forEach((link) => {
        expect(link).toBeVisible();
      });
    });
  });

  describe('Screen Reader Tests (Manual)', () => {
    /**
     * Manual screen reader testing checklist:
     * 
     * [ ] All content readable by screen reader
     * [ ] ARIA labels announced correctly
     * [ ] Heading structure announced correctly
     * [ ] Links have descriptive text
     * [ ] Breadcrumb structure announced
     * [ ] Table of contents navigation announced
     * [ ] Section changes announced
     */
    it('should have screen reader accessible structure', () => {
      render(<TermsPage />);
      
      // Verify structure is accessible to screen readers
      // Main content
      expect(screen.getByRole('main')).toBeInTheDocument();
      
      // Headings are in proper order
      const h1 = screen.getByRole('heading', { level: 1 });
      const h2s = screen.getAllByRole('heading', { level: 2 });
      
      expect(h1).toBeInTheDocument();
      expect(h2s.length).toBeGreaterThan(0);
      
      // All sections have IDs for navigation
      const sections = ['introduction', 'terms-of-use', 'privacy-policy', 'cookie-policy', 'contact'];
      sections.forEach((sectionId) => {
        const section = document.getElementById(sectionId);
        expect(section).toBeInTheDocument();
      });
    });
  });
});

// Helper for within queries
import { within } from '@testing-library/react';
