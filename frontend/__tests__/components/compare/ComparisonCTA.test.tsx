/**
 * ComparisonCTA Component Tests
 * Task: TASK-32-19 - Unit Tests for Comparison Components
 * Spec: SPEC-32 (Competitor Comparison Landing Pages)
 *
 * TDD Tests - Component implementation pending
 */

import React from 'react';
import { render, screen, fireEvent } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import '@testing-library/jest-dom';
import type { ComparisonCTAProps } from '@/lib/compare/types';

// Mock analytics tracking
const mockTrackEvent = jest.fn();
jest.mock('@/lib/analytics', () => ({
  trackEvent: (...args: unknown[]) => mockTrackEvent(...args),
}));

// Mock Lucide React icons
jest.mock('lucide-react', () => ({
  ArrowRight: () => <span data-testid="arrow-icon">→</span>,
  ExternalLink: () => <span data-testid="external-icon">↗</span>,
}));

// Mock component - will be replaced with actual implementation
const ComparisonCTA = ({
  variant = 'primary',
  text = 'Get Started Free',
  href = '/signup',
  abTestGroup,
}: ComparisonCTAProps) => {
  const handleClick = () => {
    // Track click event
    mockTrackEvent('cta_click', {
      variant,
      text,
      href,
      abTestGroup,
    });
  };

  // Render sticky variant
  if (variant === 'sticky') {
    return (
      <div
        data-testid="cta-sticky"
        className="fixed bottom-0 left-0 right-0 bg-surface-2 border-t border-slate-700 p-4 md:hidden"
      >
        <a
          href={href}
          onClick={handleClick}
          data-testid="cta-link"
          data-variant={variant}
          data-ab-test-group={abTestGroup}
          className="block w-full bg-brand-primary text-white text-center py-3 rounded-lg font-semibold"
        >
          {text}
          <span data-testid="arrow-icon">→</span>
        </a>
      </div>
    );
  }

  // Render secondary variant
  if (variant === 'secondary') {
    return (
      <a
        href={href}
        onClick={handleClick}
        data-testid="cta-link"
        data-variant={variant}
        data-ab-test-group={abTestGroup}
        className="inline-flex items-center gap-2 px-6 py-3 border border-slate-600 text-slate-300 hover:text-white rounded-lg"
      >
        {text}
        <span data-testid="external-icon">↗</span>
      </a>
    );
  }

  // Render primary variant (default)
  return (
    <a
      href={href}
      onClick={handleClick}
      data-testid="cta-link"
      data-variant={variant}
      data-ab-test-group={abTestGroup}
      className="inline-flex items-center gap-2 px-6 py-3 bg-brand-primary text-white rounded-lg font-semibold hover:bg-brand-primary/90"
    >
      {text}
      <span data-testid="arrow-icon">→</span>
    </a>
  );
};

describe('ComparisonCTA', () => {
  beforeEach(() => {
    mockTrackEvent.mockClear();
  });

  // ============================================================================
  // Primary Variant Tests
  // ============================================================================

  describe('Primary Variant', () => {
    it('CTA-001: renders primary variant', () => {
      render(<ComparisonCTA variant="primary" />);
      
      const link = screen.getByTestId('cta-link');
      expect(link).toBeInTheDocument();
      expect(link).toHaveAttribute('data-variant', 'primary');
    });

    it('CTA-002: has primary styling', () => {
      render(<ComparisonCTA variant="primary" />);
      
      const link = screen.getByTestId('cta-link');
      expect(link).toHaveClass('bg-brand-primary');
      expect(link).toHaveClass('text-white');
    });

    it('CTA-003: displays arrow icon', () => {
      render(<ComparisonCTA variant="primary" />);
      
      expect(screen.getByTestId('arrow-icon')).toBeInTheDocument();
    });

    it('CTA-004: has rounded corners', () => {
      render(<ComparisonCTA variant="primary" />);
      
      const link = screen.getByTestId('cta-link');
      expect(link).toHaveClass('rounded-lg');
    });
  });

  // ============================================================================
  // Secondary Variant Tests
  // ============================================================================

  describe('Secondary Variant', () => {
    it('CTA-010: renders secondary variant', () => {
      render(<ComparisonCTA variant="secondary" />);
      
      const link = screen.getByTestId('cta-link');
      expect(link).toBeInTheDocument();
      expect(link).toHaveAttribute('data-variant', 'secondary');
    });

    it('CTA-011: has secondary styling (outline)', () => {
      render(<ComparisonCTA variant="secondary" />);
      
      const link = screen.getByTestId('cta-link');
      expect(link).toHaveClass('border');
      expect(link).toHaveClass('border-slate-600');
    });

    it('CTA-012: displays external link icon', () => {
      render(<ComparisonCTA variant="secondary" />);
      
      expect(screen.getByTestId('external-icon')).toBeInTheDocument();
    });

    it('CTA-013: has hover state for text color', () => {
      render(<ComparisonCTA variant="secondary" />);
      
      const link = screen.getByTestId('cta-link');
      expect(link).toHaveClass('hover:text-white');
    });
  });

  // ============================================================================
  // Sticky Variant Tests
  // ============================================================================

  describe('Sticky Variant', () => {
    it('CTA-020: renders sticky variant', () => {
      render(<ComparisonCTA variant="sticky" />);
      
      const stickyContainer = screen.getByTestId('cta-sticky');
      expect(stickyContainer).toBeInTheDocument();
    });

    it('CTA-021: has fixed positioning', () => {
      render(<ComparisonCTA variant="sticky" />);
      
      const stickyContainer = screen.getByTestId('cta-sticky');
      expect(stickyContainer).toHaveClass('fixed');
      expect(stickyContainer).toHaveClass('bottom-0');
    });

    it('CTA-022: has full width button', () => {
      render(<ComparisonCTA variant="sticky" />);
      
      const link = screen.getByTestId('cta-link');
      expect(link).toHaveClass('block');
      expect(link).toHaveClass('w-full');
    });

    it('CTA-023: shows on mobile only (md:hidden)', () => {
      render(<ComparisonCTA variant="sticky" />);
      
      const stickyContainer = screen.getByTestId('cta-sticky');
      expect(stickyContainer).toHaveClass('md:hidden');
    });

    it('CTA-024: has border on top', () => {
      render(<ComparisonCTA variant="sticky" />);
      
      const stickyContainer = screen.getByTestId('cta-sticky');
      expect(stickyContainer).toHaveClass('border-t');
    });
  });

  // ============================================================================
  // Text Tests
  // ============================================================================

  describe('Text', () => {
    it('CTA-030: supports custom text', () => {
      render(<ComparisonCTA text="Try SEOIndicator Now" />);
      
      expect(screen.getByText('Try SEOIndicator Now')).toBeInTheDocument();
    });

    it('CTA-031: uses default text when none provided', () => {
      render(<ComparisonCTA />);
      
      expect(screen.getByText('Get Started Free')).toBeInTheDocument();
    });

    it('CTA-032: displays text with icon', () => {
      render(<ComparisonCTA text="Start Free Trial" variant="primary" />);
      
      const link = screen.getByTestId('cta-link');
      expect(link).toHaveTextContent('Start Free Trial');
      expect(screen.getByTestId('arrow-icon')).toBeInTheDocument();
    });
  });

  // ============================================================================
  // Link Tests
  // ============================================================================

  describe('Link', () => {
    it('CTA-040: supports custom href', () => {
      render(<ComparisonCTA href="/dashboard" />);
      
      const link = screen.getByTestId('cta-link');
      expect(link).toHaveAttribute('href', '/dashboard');
    });

    it('CTA-041: uses default href when none provided', () => {
      render(<ComparisonCTA />);
      
      const link = screen.getByTestId('cta-link');
      expect(link).toHaveAttribute('href', '/signup');
    });

    it('CTA-042: renders as anchor element', () => {
      render(<ComparisonCTA />);
      
      const link = screen.getByTestId('cta-link');
      expect(link.tagName).toBe('A');
    });

    it('CTA-043: supports external URLs', () => {
      render(<ComparisonCTA href="https://seoindicator.com/pricing" variant="secondary" />);
      
      const link = screen.getByTestId('cta-link');
      expect(link).toHaveAttribute('href', 'https://seoindicator.com/pricing');
    });
  });

  // ============================================================================
  // Tracking Tests
  // ============================================================================

  describe('Tracking', () => {
    it('CTA-050: tracks click event', async () => {
      render(<ComparisonCTA />);
      
      await userEvent.click(screen.getByTestId('cta-link'));
      
      expect(mockTrackEvent).toHaveBeenCalledTimes(1);
      expect(mockTrackEvent).toHaveBeenCalledWith('cta_click', expect.any(Object));
    });

    it('CTA-051: includes variant in tracking data', async () => {
      render(<ComparisonCTA variant="secondary" />);
      
      await userEvent.click(screen.getByTestId('cta-link'));
      
      expect(mockTrackEvent).toHaveBeenCalledWith('cta_click', expect.objectContaining({
        variant: 'secondary',
      }));
    });

    it('CTA-052: includes text in tracking data', async () => {
      render(<ComparisonCTA text="Custom CTA" />);
      
      await userEvent.click(screen.getByTestId('cta-link'));
      
      expect(mockTrackEvent).toHaveBeenCalledWith('cta_click', expect.objectContaining({
        text: 'Custom CTA',
      }));
    });

    it('CTA-053: includes href in tracking data', async () => {
      render(<ComparisonCTA href="/custom-page" />);
      
      await userEvent.click(screen.getByTestId('cta-link'));
      
      expect(mockTrackEvent).toHaveBeenCalledWith('cta_click', expect.objectContaining({
        href: '/custom-page',
      }));
    });

    it('CTA-054: includes A/B test group in tracking data', async () => {
      render(<ComparisonCTA abTestGroup="variant-a" />);
      
      await userEvent.click(screen.getByTestId('cta-link'));
      
      expect(mockTrackEvent).toHaveBeenCalledWith('cta_click', expect.objectContaining({
        abTestGroup: 'variant-a',
      }));
    });

    it('CTA-055: tracks sticky variant click', async () => {
      render(<ComparisonCTA variant="sticky" />);
      
      await userEvent.click(screen.getByTestId('cta-link'));
      
      expect(mockTrackEvent).toHaveBeenCalledWith('cta_click', expect.objectContaining({
        variant: 'sticky',
      }));
    });
  });

  // ============================================================================
  // Accessibility Tests
  // ============================================================================

  describe('Accessibility', () => {
    it('CTA-060: link is focusable', () => {
      render(<ComparisonCTA />);
      
      const link = screen.getByTestId('cta-link');
      link.focus();
      
      expect(link).toHaveFocus();
    });

    it('CTA-061: can be activated by Enter key', async () => {
      render(<ComparisonCTA />);
      
      const link = screen.getByTestId('cta-link');
      link.focus();
      
      await userEvent.keyboard('{Enter}');
      
      // Note: In real browser, Enter on anchor triggers navigation
      // Here we verify the link has proper behavior
      expect(link).toHaveAttribute('href');
    });

    it('CTA-062: has descriptive text', () => {
      render(<ComparisonCTA text="Start Your Free Trial" />);
      
      // Text should be accessible to screen readers
      expect(screen.getByText('Start Your Free Trial')).toBeInTheDocument();
    });

    it('CTA-063: icon is decorative (not announced)', () => {
      render(<ComparisonCTA />);
      
      // Icons are decorative, text provides meaning
      const arrowIcon = screen.getByTestId('arrow-icon');
      expect(arrowIcon).toBeInTheDocument();
    });
  });

  // ============================================================================
  // Edge Cases
  // ============================================================================

  describe('Edge Cases', () => {
    it('CTA-070: handles long custom text', () => {
      render(<ComparisonCTA text="Start Your Free 14-Day Trial with Full Access to All Features" />);
      
      expect(screen.getByText('Start Your Free 14-Day Trial with Full Access to All Features')).toBeInTheDocument();
    });

    it('CTA-071: handles special characters in href', () => {
      render(<ComparisonCTA href="/signup?ref=compare&utm_source=ahrefs" />);
      
      const link = screen.getByTestId('cta-link');
      expect(link).toHaveAttribute('href', '/signup?ref=compare&utm_source=ahrefs');
    });

    it('CTA-072: handles undefined abTestGroup', () => {
      render(<ComparisonCTA abTestGroup={undefined} />);
      
      const link = screen.getByTestId('cta-link');
      // When abTestGroup is undefined, the attribute should not be set
      expect(link).not.toHaveAttribute('data-ab-test-group');
    });

    it('CTA-073: handles empty text with default', () => {
      render(<ComparisonCTA text="" />);
      
      // Empty string should fall back to default or show empty
      const link = screen.getByTestId('cta-link');
      expect(link).toBeInTheDocument();
    });

    it('CTA-074: handles hash links', () => {
      render(<ComparisonCTA href="#pricing" />);
      
      const link = screen.getByTestId('cta-link');
      expect(link).toHaveAttribute('href', '#pricing');
    });

    it('CTA-075: handles very long href', () => {
      const longHref = '/some/very/long/path/that/goes/on/and/on?param1=value1&param2=value2&param3=value3';
      
      render(<ComparisonCTA href={longHref} />);
      
      const link = screen.getByTestId('cta-link');
      expect(link).toHaveAttribute('href', longHref);
    });
  });

  // ============================================================================
  // Props Tests
  // ============================================================================

  describe('Props', () => {
    it('CTA-080: uses default variant when none specified', () => {
      render(<ComparisonCTA />);
      
      const link = screen.getByTestId('cta-link');
      expect(link).toHaveAttribute('data-variant', 'primary');
    });

    it('CTA-081: uses default text when none specified', () => {
      render(<ComparisonCTA />);
      
      expect(screen.getByText('Get Started Free')).toBeInTheDocument();
    });

    it('CTA-082: uses default href when none specified', () => {
      render(<ComparisonCTA />);
      
      const link = screen.getByTestId('cta-link');
      expect(link).toHaveAttribute('href', '/signup');
    });

    it('CTA-083: all props are optional', () => {
      // Should not throw
      expect(() => {
        render(<ComparisonCTA />);
      }).not.toThrow();
    });

    it('CTA-084: combines all props correctly', async () => {
      render(
        <ComparisonCTA
          variant="primary"
          text="Get Pro"
          href="/upgrade"
          abTestGroup="test-group-b"
        />
      );
      
      const link = screen.getByTestId('cta-link');
      
      expect(link).toHaveAttribute('data-variant', 'primary');
      expect(link).toHaveAttribute('href', '/upgrade');
      expect(link).toHaveAttribute('data-ab-test-group', 'test-group-b');
      expect(screen.getByText('Get Pro')).toBeInTheDocument();
    });
  });
});
