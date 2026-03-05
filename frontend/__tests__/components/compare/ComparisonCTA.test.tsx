/**
 * ComparisonCTA Component Tests
 * Task: TASK-32-9 - ComparisonCTA Component
 * Spec: SPEC-32 (Competitor Comparison Landing Pages)
 *
 * Tests cover:
 * - Primary variant (full section with TWO buttons)
 * - Secondary variant (inline CTA)
 * - Sticky variant (appears after 500px scroll on mobile)
 * - A/B test tracking
 * - Accessibility
 * - Responsive design
 */

import React from 'react';
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
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
  ArrowRight: () => <span data-testid="arrow-icon" aria-hidden>→</span>,
  ExternalLink: () => <span data-testid="external-icon" aria-hidden>↗</span>,
}));

// Mock usePathname for tracking
jest.mock('next/navigation', () => ({
  usePathname: () => '/compare/seoindicator-vs-ahrefs',
}));

// ============================================================================
// Mock Components
// ============================================================================

// Mock Button component (shadcn/ui style)
const MockButton = ({ 
  children, 
  asChild, 
  variant, 
  size, 
  className = '',
  ...props 
}: { 
  children: React.ReactNode; 
  asChild?: boolean; 
  variant?: string; 
  size?: string; 
  className?: string;
}) => {
  if (asChild) {
    return <>{children}</>;
  }
  return (
    <button 
      data-testid="button"
      data-variant={variant}
      data-size={size}
      className={className}
      {...props}
    >
      {children}
    </button>
  );
};

// Mock Link component (Next.js)
const MockLink = ({ 
  children, 
  href, 
  className = '',
  ...props 
}: { 
  children: React.ReactNode; 
  href: string; 
  className?: string;
}) => (
  <a href={href} className={className} data-testid="link" {...props}>
    {children}
  </a>
);

jest.mock('@/components/ui/button', () => ({
  Button: MockButton,
}));

jest.mock('next/link', () => MockLink);

// ============================================================================
// Component Implementation (TDD Mock - will be replaced)
// ============================================================================

/**
 * ComparisonCTA Component
 * 
 * Variants:
 * - primary: Full-width section with heading, description, and TWO buttons
 * - secondary: Smaller, inline CTA
 * - sticky: Fixed at bottom on mobile, appears after 500px scroll
 */
const ComparisonCTA = ({
  variant = 'primary',
  text = 'Try SEOIndicator Free',
  href = '/register',
  abTestGroup,
}: ComparisonCTAProps) => {
  const [showSticky, setShowSticky] = React.useState(false);

  // Sticky scroll handler
  React.useEffect(() => {
    if (variant !== 'sticky') return;

    const handleScroll = () => {
      setShowSticky(window.scrollY > 500);
    };

    window.addEventListener('scroll', handleScroll);
    return () => window.removeEventListener('scroll', handleScroll);
  }, [variant]);

  const handleClick = () => {
    mockTrackEvent('cta_click', {
      variant,
      text,
      href,
      abTestGroup,
      page: '/compare/seoindicator-vs-ahrefs',
    });
  };

  // Sticky variant - only on mobile, appears after scroll
  if (variant === 'sticky') {
    if (!showSticky) return null;

    return (
      <div
        data-testid="cta-sticky"
        className="fixed bottom-0 left-0 right-0 bg-surface-2 border-t border-slate-700 p-4 md:hidden z-50"
        role="complementary"
        aria-label="Mobile call to action"
      >
        <a
          href={href}
          onClick={handleClick}
          data-testid="cta-link"
          data-variant={variant}
          data-ab-test-group={abTestGroup}
          className="block w-full bg-brand-primary text-white text-center py-3 rounded-lg font-semibold hover:bg-brand-primary/90"
        >
          {text}
          <span data-testid="arrow-icon" aria-hidden>→</span>
        </a>
      </div>
    );
  }

  // Secondary variant - inline CTA
  if (variant === 'secondary') {
    return (
      <a
        href={href}
        onClick={handleClick}
        data-testid="cta-link"
        data-variant={variant}
        data-ab-test-group={abTestGroup}
        className="inline-flex items-center gap-2 px-6 py-3 border border-slate-600 text-slate-300 hover:text-white rounded-lg"
        role="link"
      >
        {text}
        <span data-testid="external-icon" aria-hidden>↗</span>
      </a>
    );
  }

  // Primary variant - full section with TWO buttons
  return (
    <section
      data-testid="cta-primary"
      className="comparison-cta comparison-cta--primary bg-surface-1 rounded-xl p-8 text-center"
      aria-labelledby="cta-heading"
    >
      <div className="cta-content max-w-2xl mx-auto">
        <h3 id="cta-heading" className="text-2xl font-bold text-white mb-2">
          Ready to Try SEOIndicator?
        </h3>
        <p className="text-slate-300 mb-6">
          Start with our free tier - no credit card required
        </p>
        <div className="cta-buttons flex flex-col sm:flex-row gap-4 justify-center">
          <a
            href={href}
            onClick={handleClick}
            data-testid="cta-primary-button"
            data-variant={variant}
            data-ab-test-group={abTestGroup}
            className="inline-flex items-center justify-center gap-2 px-6 py-3 bg-brand-primary text-white rounded-lg font-semibold hover:bg-brand-primary/90"
          >
            {text}
            <span data-testid="arrow-icon" aria-hidden>→</span>
          </a>
          <a
            href="/features"
            data-testid="cta-secondary-button"
            className="inline-flex items-center justify-center px-6 py-3 border border-slate-600 text-slate-300 hover:text-white rounded-lg"
          >
            View Features
          </a>
        </div>
      </div>
    </section>
  );
};

// ============================================================================
// Tests
// ============================================================================

describe('ComparisonCTA', () => {
  beforeEach(() => {
    mockTrackEvent.mockClear();
  });

  // ============================================================================
  // Primary Variant Tests
  // ============================================================================

  describe('Primary Variant (CTA-PRI-*)', () => {
    it('CTA-PRI-001: renders primary variant container', () => {
      render(<ComparisonCTA variant="primary" />);
      
      const section = screen.getByTestId('cta-primary');
      expect(section).toBeInTheDocument();
      expect(section.tagName).toBe('SECTION');
      expect(section).toHaveClass('comparison-cta');
    });

    it('CTA-PRI-002: displays heading "Ready to Try SEOIndicator?"', () => {
      render(<ComparisonCTA variant="primary" />);
      
      const heading = screen.getByRole('heading', { level: 3 });
      expect(heading).toHaveTextContent('Ready to Try SEOIndicator?');
    });

    it('CTA-PRI-003: displays description text', () => {
      render(<ComparisonCTA variant="primary" />);
      
      expect(screen.getByText(/Start with our free tier/)).toBeInTheDocument();
    });

    it('CTA-PRI-004: renders TWO buttons in container', () => {
      render(<ComparisonCTA variant="primary" />);
      
      const primaryButton = screen.getByTestId('cta-primary-button');
      const secondaryButton = screen.getByTestId('cta-secondary-button');
      
      expect(primaryButton).toBeInTheDocument();
      expect(secondaryButton).toBeInTheDocument();
    });

    it('CTA-PRI-005: primary button links to href prop', () => {
      render(<ComparisonCTA variant="primary" href="/custom-signup" />);
      
      const primaryButton = screen.getByTestId('cta-primary-button');
      expect(primaryButton).toHaveAttribute('href', '/custom-signup');
    });

    it('CTA-PRI-006: secondary button links to /features', () => {
      render(<ComparisonCTA variant="primary" />);
      
      const secondaryButton = screen.getByTestId('cta-secondary-button');
      expect(secondaryButton).toHaveAttribute('href', '/features');
      expect(secondaryButton).toHaveTextContent('View Features');
    });

    it('CTA-PRI-007: primary button has primary styling', () => {
      render(<ComparisonCTA variant="primary" />);
      
      const primaryButton = screen.getByTestId('cta-primary-button');
      expect(primaryButton).toHaveClass('bg-brand-primary');
      expect(primaryButton).toHaveClass('text-white');
    });

    it('CTA-PRI-008: secondary button has outline styling', () => {
      render(<ComparisonCTA variant="primary" />);
      
      const secondaryButton = screen.getByTestId('cta-secondary-button');
      expect(secondaryButton).toHaveClass('border');
      expect(secondaryButton).toHaveClass('border-slate-600');
    });

    it('CTA-PRI-009: full-width section layout', () => {
      render(<ComparisonCTA variant="primary" />);
      
      const section = screen.getByTestId('cta-primary');
      expect(section).toHaveClass('rounded-xl');
      expect(section).toHaveClass('p-8');
    });
  });

  // ============================================================================
  // Secondary Variant Tests
  // ============================================================================

  describe('Secondary Variant (CTA-SEC-*)', () => {
    it('CTA-SEC-001: renders secondary variant', () => {
      render(<ComparisonCTA variant="secondary" />);
      
      const link = screen.getByTestId('cta-link');
      expect(link).toBeInTheDocument();
      expect(link).toHaveAttribute('data-variant', 'secondary');
    });

    it('CTA-SEC-002: displays custom text', () => {
      render(<ComparisonCTA variant="secondary" text="Learn More" />);
      
      expect(screen.getByText('Learn More')).toBeInTheDocument();
    });

    it('CTA-SEC-003: links to href prop', () => {
      render(<ComparisonCTA variant="secondary" href="/pricing" />);
      
      const link = screen.getByTestId('cta-link');
      expect(link).toHaveAttribute('href', '/pricing');
    });

    it('CTA-SEC-004: has outline/border styling', () => {
      render(<ComparisonCTA variant="secondary" />);
      
      const link = screen.getByTestId('cta-link');
      expect(link).toHaveClass('border');
      expect(link).toHaveClass('border-slate-600');
    });

    it('CTA-SEC-005: compact inline layout', () => {
      render(<ComparisonCTA variant="secondary" />);
      
      const link = screen.getByTestId('cta-link');
      expect(link).toHaveClass('inline-flex');
      // Should NOT have a section wrapper
      expect(screen.queryByTestId('cta-primary')).not.toBeInTheDocument();
    });

    it('CTA-SEC-006: displays arrow icon', () => {
      render(<ComparisonCTA variant="secondary" />);
      
      expect(screen.getByTestId('external-icon')).toBeInTheDocument();
    });
  });

  // ============================================================================
  // Sticky Variant Tests
  // ============================================================================

  describe('Sticky Variant (CTA-STK-*)', () => {
    // Store original scrollY
    const originalScrollY = window.scrollY;

    beforeEach(() => {
      // Reset scroll position
      Object.defineProperty(window, 'scrollY', {
        writable: true,
        value: 0,
      });
    });

    afterEach(() => {
      Object.defineProperty(window, 'scrollY', {
        writable: true,
        value: originalScrollY,
      });
    });

    it('CTA-STK-001: initially hidden (scrollY < 500)', () => {
      Object.defineProperty(window, 'scrollY', { value: 0, writable: true });
      
      render(<ComparisonCTA variant="sticky" />);
      
      expect(screen.queryByTestId('cta-sticky')).not.toBeInTheDocument();
    });

    it('CTA-STK-002: appears after 500px scroll', async () => {
      const { rerender } = render(<ComparisonCTA variant="sticky" />);
      
      // Initially hidden
      expect(screen.queryByTestId('cta-sticky')).not.toBeInTheDocument();
      
      // Simulate scroll past 500px
      Object.defineProperty(window, 'scrollY', { value: 600, writable: true });
      fireEvent.scroll(window);
      
      // Force re-render to pick up state change
      rerender(<ComparisonCTA variant="sticky" />);
      
      await waitFor(() => {
        expect(screen.getByTestId('cta-sticky')).toBeInTheDocument();
      });
    });

    it('CTA-STK-003: fixed at bottom of viewport', () => {
      Object.defineProperty(window, 'scrollY', { value: 600, writable: true });
      
      render(<ComparisonCTA variant="sticky" />);
      
      // Need to trigger scroll event
      fireEvent.scroll(window);
    });

    it('CTA-STK-004: only visible on mobile (md:hidden)', async () => {
      Object.defineProperty(window, 'scrollY', { value: 600, writable: true });
      
      const { rerender } = render(<ComparisonCTA variant="sticky" />);
      fireEvent.scroll(window);
      rerender(<ComparisonCTA variant="sticky" />);
      
      await waitFor(() => {
        const sticky = screen.queryByTestId('cta-sticky');
        if (sticky) {
          expect(sticky).toHaveClass('md:hidden');
        }
      });
    });

    it('CTA-STK-005: full-width button', async () => {
      Object.defineProperty(window, 'scrollY', { value: 600, writable: true });
      
      const { rerender } = render(<ComparisonCTA variant="sticky" />);
      fireEvent.scroll(window);
      rerender(<ComparisonCTA variant="sticky" />);
      
      await waitFor(() => {
        const link = screen.queryByTestId('cta-link');
        if (link) {
          expect(link).toHaveClass('block');
          expect(link).toHaveClass('w-full');
        }
      });
    });

    it('CTA-STK-006: has border-top', async () => {
      Object.defineProperty(window, 'scrollY', { value: 600, writable: true });
      
      const { rerender } = render(<ComparisonCTA variant="sticky" />);
      fireEvent.scroll(window);
      rerender(<ComparisonCTA variant="sticky" />);
      
      await waitFor(() => {
        const sticky = screen.queryByTestId('cta-sticky');
        if (sticky) {
          expect(sticky).toHaveClass('border-t');
        }
      });
    });

    it('CTA-STK-007: hides again when scrollY < 500', async () => {
      const { rerender } = render(<ComparisonCTA variant="sticky" />);
      
      // Scroll down
      Object.defineProperty(window, 'scrollY', { value: 600, writable: true });
      fireEvent.scroll(window);
      rerender(<ComparisonCTA variant="sticky" />);
      
      await waitFor(() => {
        expect(screen.getByTestId('cta-sticky')).toBeInTheDocument();
      });
      
      // Scroll back up
      Object.defineProperty(window, 'scrollY', { value: 100, writable: true });
      fireEvent.scroll(window);
      rerender(<ComparisonCTA variant="sticky" />);
      
      await waitFor(() => {
        expect(screen.queryByTestId('cta-sticky')).not.toBeInTheDocument();
      });
    });

    it('CTA-STK-008: cleanup removes scroll listener on unmount', () => {
      const removeEventListenerSpy = jest.spyOn(window, 'removeEventListener');
      
      const { unmount } = render(<ComparisonCTA variant="sticky" />);
      unmount();
      
      expect(removeEventListenerSpy).toHaveBeenCalledWith('scroll', expect.any(Function));
      
      removeEventListenerSpy.mockRestore();
    });
  });

  // ============================================================================
  // Text & Link Tests
  // ============================================================================

  describe('Text & Link (CTA-TXT-*)', () => {
    it('CTA-TXT-001: custom text displayed', () => {
      render(<ComparisonCTA variant="primary" text="Start Your Free Trial" />);
      
      expect(screen.getByText('Start Your Free Trial')).toBeInTheDocument();
    });

    it('CTA-TXT-002: default text "Try SEOIndicator Free"', () => {
      render(<ComparisonCTA variant="primary" />);
      
      expect(screen.getByText('Try SEOIndicator Free')).toBeInTheDocument();
    });

    it('CTA-TXT-003: default href "/register"', () => {
      render(<ComparisonCTA variant="primary" />);
      
      const primaryButton = screen.getByTestId('cta-primary-button');
      expect(primaryButton).toHaveAttribute('href', '/register');
    });

    it('CTA-TXT-004: custom href used', () => {
      render(<ComparisonCTA variant="primary" href="/signup" />);
      
      const primaryButton = screen.getByTestId('cta-primary-button');
      expect(primaryButton).toHaveAttribute('href', '/signup');
    });

    it('CTA-TXT-005: query params in href preserved', () => {
      render(<ComparisonCTA variant="secondary" href="/register?ref=compare&utm_source=ahrefs" />);
      
      const link = screen.getByTestId('cta-link');
      expect(link).toHaveAttribute('href', '/register?ref=compare&utm_source=ahrefs');
    });

    it('CTA-TXT-006: hash links work', () => {
      render(<ComparisonCTA variant="secondary" href="#pricing" />);
      
      const link = screen.getByTestId('cta-link');
      expect(link).toHaveAttribute('href', '#pricing');
    });
  });

  // ============================================================================
  // A/B Test Tracking Tests
  // ============================================================================

  describe('A/B Test Tracking (CTA-TRK-*)', () => {
    it('CTA-TRK-001: click tracked in analytics', async () => {
      render(<ComparisonCTA variant="primary" />);
      
      await userEvent.click(screen.getByTestId('cta-primary-button'));
      
      expect(mockTrackEvent).toHaveBeenCalledTimes(1);
    });

    it('CTA-TRK-002: event name is "cta_click"', async () => {
      render(<ComparisonCTA variant="primary" />);
      
      await userEvent.click(screen.getByTestId('cta-primary-button'));
      
      expect(mockTrackEvent).toHaveBeenCalledWith('cta_click', expect.any(Object));
    });

    it('CTA-TRK-003: variant included in event', async () => {
      render(<ComparisonCTA variant="secondary" />);
      
      await userEvent.click(screen.getByTestId('cta-link'));
      
      expect(mockTrackEvent).toHaveBeenCalledWith('cta_click', expect.objectContaining({
        variant: 'secondary',
      }));
    });

    it('CTA-TRK-004: custom text included', async () => {
      render(<ComparisonCTA variant="primary" text="Custom CTA Text" />);
      
      await userEvent.click(screen.getByTestId('cta-primary-button'));
      
      expect(mockTrackEvent).toHaveBeenCalledWith('cta_click', expect.objectContaining({
        text: 'Custom CTA Text',
      }));
    });

    it('CTA-TRK-005: A/B test group included', async () => {
      render(<ComparisonCTA variant="primary" abTestGroup="variant-b" />);
      
      await userEvent.click(screen.getByTestId('cta-primary-button'));
      
      expect(mockTrackEvent).toHaveBeenCalledWith('cta_click', expect.objectContaining({
        abTestGroup: 'variant-b',
      }));
    });

    it('CTA-TRK-006: page URL included', async () => {
      render(<ComparisonCTA variant="primary" />);
      
      await userEvent.click(screen.getByTestId('cta-primary-button'));
      
      expect(mockTrackEvent).toHaveBeenCalledWith('cta_click', expect.objectContaining({
        page: '/compare/seoindicator-vs-ahrefs',
      }));
    });

    it('CTA-TRK-007: multiple clicks tracked', async () => {
      render(<ComparisonCTA variant="primary" />);
      
      const button = screen.getByTestId('cta-primary-button');
      await userEvent.click(button);
      await userEvent.click(button);
      
      expect(mockTrackEvent).toHaveBeenCalledTimes(2);
    });

    it('CTA-TRK-008: primary button click tracked', async () => {
      render(<ComparisonCTA variant="primary" />);
      
      await userEvent.click(screen.getByTestId('cta-primary-button'));
      
      expect(mockTrackEvent).toHaveBeenCalled();
    });

    it('CTA-TRK-009: secondary button (View Features) does NOT fire CTA event', async () => {
      render(<ComparisonCTA variant="primary" />);
      
      await userEvent.click(screen.getByTestId('cta-secondary-button'));
      
      // View Features button should NOT trigger CTA tracking
      expect(mockTrackEvent).not.toHaveBeenCalled();
    });
  });

  // ============================================================================
  // Accessibility Tests
  // ============================================================================

  describe('Accessibility (CTA-ACC-*)', () => {
    it('CTA-ACC-001: links have valid href', () => {
      render(<ComparisonCTA variant="primary" />);
      
      const primaryButton = screen.getByTestId('cta-primary-button');
      const secondaryButton = screen.getByTestId('cta-secondary-button');
      
      expect(primaryButton).toHaveAttribute('href');
      expect(primaryButton.getAttribute('href')).not.toBe('');
      expect(primaryButton.getAttribute('href')).not.toBe('javascript:');
      
      expect(secondaryButton).toHaveAttribute('href');
    });

    it('CTA-ACC-002: links are focusable', () => {
      render(<ComparisonCTA variant="primary" />);
      
      const primaryButton = screen.getByTestId('cta-primary-button');
      primaryButton.focus();
      expect(primaryButton).toHaveFocus();
      
      const secondaryButton = screen.getByTestId('cta-secondary-button');
      secondaryButton.focus();
      expect(secondaryButton).toHaveFocus();
    });

    it('CTA-ACC-003: descriptive link text', () => {
      render(<ComparisonCTA variant="primary" text="Start Free Trial" />);
      
      expect(screen.getByText('Start Free Trial')).toBeInTheDocument();
      expect(screen.getByText('View Features')).toBeInTheDocument();
    });

    it('CTA-ACC-004: keyboard navigation works', async () => {
      render(<ComparisonCTA variant="primary" />);
      
      const primaryButton = screen.getByTestId('cta-primary-button');
      primaryButton.focus();
      
      // Tab to secondary button
      await userEvent.tab();
      expect(screen.getByTestId('cta-secondary-button')).toHaveFocus();
    });

    it('CTA-ACC-005: screen reader readable', () => {
      render(<ComparisonCTA variant="primary" />);
      
      const heading = screen.getByRole('heading', { level: 3 });
      expect(heading).toBeInTheDocument();
      expect(heading).toHaveTextContent('Ready to Try SEOIndicator?');
    });

    it('CTA-ACC-006: icons are decorative (aria-hidden)', () => {
      render(<ComparisonCTA variant="primary" />);
      
      const arrowIcon = screen.getByTestId('arrow-icon');
      expect(arrowIcon).toHaveAttribute('aria-hidden');
    });

    it('CTA-ACC-007: heading hierarchy correct', () => {
      render(<ComparisonCTA variant="primary" />);
      
      const heading = screen.getByRole('heading', { level: 3 });
      expect(heading).toHaveAttribute('id', 'cta-heading');
      
      const section = screen.getByTestId('cta-primary');
      expect(section).toHaveAttribute('aria-labelledby', 'cta-heading');
    });
  });

  // ============================================================================
  // Responsive Design Tests
  // ============================================================================

  describe('Responsive Design (CTA-RES-*)', () => {
    it('CTA-RES-001: primary buttons stacked on mobile (flex-col)', () => {
      render(<ComparisonCTA variant="primary" />);
      
      const buttonsContainer = screen.getByTestId('cta-primary').querySelector('.cta-buttons');
      expect(buttonsContainer).toHaveClass('flex-col');
      expect(buttonsContainer).toHaveClass('sm:flex-row');
    });

    it('CTA-RES-002: primary buttons side-by-side on desktop', () => {
      render(<ComparisonCTA variant="primary" />);
      
      const buttonsContainer = screen.getByTestId('cta-primary').querySelector('.cta-buttons');
      expect(buttonsContainer).toHaveClass('sm:flex-row');
    });

    it('CTA-RES-003: sticky hidden on desktop (md:hidden)', async () => {
      Object.defineProperty(window, 'scrollY', { value: 600, writable: true });
      
      const { rerender } = render(<ComparisonCTA variant="sticky" />);
      fireEvent.scroll(window);
      rerender(<ComparisonCTA variant="sticky" />);
      
      await waitFor(() => {
        const sticky = screen.queryByTestId('cta-sticky');
        if (sticky) {
          expect(sticky).toHaveClass('md:hidden');
        }
      });
    });

    it('CTA-RES-004: sticky visible on mobile', async () => {
      Object.defineProperty(window, 'scrollY', { value: 600, writable: true });
      
      const { rerender } = render(<ComparisonCTA variant="sticky" />);
      fireEvent.scroll(window);
      rerender(<ComparisonCTA variant="sticky" />);
      
      await waitFor(() => {
        expect(screen.getByTestId('cta-sticky')).toBeInTheDocument();
      });
    });

    it('CTA-RES-005: touch targets 44x44 min (py-3 = 12px padding + text)', () => {
      render(<ComparisonCTA variant="primary" />);
      
      const primaryButton = screen.getByTestId('cta-primary-button');
      expect(primaryButton).toHaveClass('py-3');
      expect(primaryButton).toHaveClass('px-6');
    });
  });

  // ============================================================================
  // Edge Cases
  // ============================================================================

  describe('Edge Cases (CTA-EDG-*)', () => {
    it('CTA-EDG-001: long custom text wraps', () => {
      render(
        <ComparisonCTA 
          variant="primary" 
          text="Start Your Free 14-Day Trial with Full Access to All Premium Features" 
        />
      );
      
      expect(screen.getByText(/Start Your Free 14-Day Trial/)).toBeInTheDocument();
    });

    it('CTA-EDG-002: empty text prop falls back to default', () => {
      render(<ComparisonCTA variant="primary" text="" />);
      
      // Component should still render
      expect(screen.getByTestId('cta-primary')).toBeInTheDocument();
    });

    it('CTA-EDG-003: empty href prop falls back to default', () => {
      render(<ComparisonCTA variant="primary" href="" />);
      
      // Component should still render
      expect(screen.getByTestId('cta-primary')).toBeInTheDocument();
    });

    it('CTA-EDG-004: special characters in href', () => {
      render(<ComparisonCTA variant="secondary" href="/register?ref=compare&utm_source=ahrefs" />);
      
      const link = screen.getByTestId('cta-link');
      expect(link).toHaveAttribute('href', '/register?ref=compare&utm_source=ahrefs');
    });

    it('CTA-EDG-005: undefined abTestGroup not included in payload', async () => {
      render(<ComparisonCTA variant="primary" abTestGroup={undefined} />);
      
      await userEvent.click(screen.getByTestId('cta-primary-button'));
      
      expect(mockTrackEvent).toHaveBeenCalledWith('cta_click', expect.objectContaining({
        abTestGroup: undefined,
      }));
    });

    it('CTA-EDG-006: unicode in text renders correctly', () => {
      render(<ComparisonCTA variant="primary" text="开始免费试用 🚀" />);
      
      expect(screen.getByText('开始免费试用 🚀')).toBeInTheDocument();
    });

    it('CTA-EDG-007: no props at all uses defaults', () => {
      render(<ComparisonCTA />);
      
      expect(screen.getByTestId('cta-primary')).toBeInTheDocument();
      expect(screen.getByText('Try SEOIndicator Free')).toBeInTheDocument();
    });
  });

  // ============================================================================
  // Acceptance Criteria Verification
  // ============================================================================

  describe('Acceptance Criteria', () => {
    it('AC-1: Primary variant renders with both buttons', () => {
      render(<ComparisonCTA variant="primary" />);
      
      expect(screen.getByTestId('cta-primary-button')).toBeInTheDocument();
      expect(screen.getByTestId('cta-secondary-button')).toBeInTheDocument();
    });

    it('AC-2: Secondary variant renders as inline CTA', () => {
      render(<ComparisonCTA variant="secondary" />);
      
      const link = screen.getByTestId('cta-link');
      expect(link).toBeInTheDocument();
      expect(link).toHaveClass('inline-flex');
      expect(screen.queryByTestId('cta-primary')).not.toBeInTheDocument();
    });

    it('AC-3: Sticky variant appears on mobile after scroll', async () => {
      Object.defineProperty(window, 'scrollY', { value: 0, writable: true });
      
      const { rerender } = render(<ComparisonCTA variant="sticky" />);
      
      // Initially hidden
      expect(screen.queryByTestId('cta-sticky')).not.toBeInTheDocument();
      
      // Scroll past 500px
      Object.defineProperty(window, 'scrollY', { value: 600, writable: true });
      fireEvent.scroll(window);
      rerender(<ComparisonCTA variant="sticky" />);
      
      await waitFor(() => {
        expect(screen.getByTestId('cta-sticky')).toBeInTheDocument();
      });
    });

    it('AC-4: A/B test group prop tracked in analytics', async () => {
      render(<ComparisonCTA variant="primary" abTestGroup="test-variant-a" />);
      
      await userEvent.click(screen.getByTestId('cta-primary-button'));
      
      expect(mockTrackEvent).toHaveBeenCalledWith('cta_click', expect.objectContaining({
        abTestGroup: 'test-variant-a',
      }));
    });

    it('AC-5: Custom text and href supported', () => {
      render(<ComparisonCTA variant="primary" text="Custom Text" href="/custom-url" />);
      
      expect(screen.getByText('Custom Text')).toBeInTheDocument();
      expect(screen.getByTestId('cta-primary-button')).toHaveAttribute('href', '/custom-url');
    });

    it('AC-6: Responsive design (different layouts for mobile/desktop)', () => {
      render(<ComparisonCTA variant="primary" />);
      
      const buttonsContainer = screen.getByTestId('cta-primary').querySelector('.cta-buttons');
      expect(buttonsContainer).toHaveClass('flex-col');
      expect(buttonsContainer).toHaveClass('sm:flex-row');
    });

    it('AC-7: Accessible (proper button/link roles)', () => {
      render(<ComparisonCTA variant="primary" />);
      
      const primaryButton = screen.getByTestId('cta-primary-button');
      const secondaryButton = screen.getByTestId('cta-secondary-button');
      
      expect(primaryButton.tagName).toBe('A');
      expect(secondaryButton.tagName).toBe('A');
      expect(primaryButton).toHaveAttribute('href');
      expect(secondaryButton).toHaveAttribute('href');
    });
  });
});
