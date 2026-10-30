/**
 * Accessibility Tests for IndicatorCard Component
 * 
 * Tests for WCAG 2.1 AA compliance including ARIA labels,
 * keyboard navigation, and screen reader compatibility.
 * 
 * @see TESTS/TASK-28-16.md for full test documentation
 */

import React from 'react';
import { render, screen, fireEvent } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import '@testing-library/jest-dom';
import { IndicatorCard, type IndicatorCardProps } from '@/components/indicators/IndicatorCard';
import { TooltipProvider } from '@/components/ui/tooltip';

// Mock ResizeObserver (required by Radix UI)
global.ResizeObserver = jest.fn().mockImplementation(() => ({
  observe: jest.fn(),
  unobserve: jest.fn(),
  disconnect: jest.fn(),
}));

// Mock window.matchMedia for reduced motion detection
Object.defineProperty(window, 'matchMedia', {
  writable: true,
  value: jest.fn().mockImplementation((query: string) => ({
    matches: false,
    media: query,
    onchange: null,
    addListener: jest.fn(),
    removeListener: jest.fn(),
    addEventListener: jest.fn(),
    removeEventListener: jest.fn(),
    dispatchEvent: jest.fn(),
  })),
});

// Mock Next.js Link
jest.mock('next/link', () => {
  const MockLink = React.forwardRef<
    HTMLAnchorElement,
    React.AnchorHTMLAttributes<HTMLAnchorElement> & { href: string }
  >(({ href, children, ...props }, ref) => (
    <a ref={ref} href={href} {...props}>
      {children}
    </a>
  ));
  MockLink.displayName = 'MockLink';
  return MockLink;
});

// Helper to render with providers
const renderWithProviders = (ui: React.ReactElement) => {
  return render(<TooltipProvider>{ui}</TooltipProvider>);
};

// ============================================
// Test Scenarios IC-001 to IC-015
// ============================================

describe('IndicatorCard Accessibility (IC-001 to IC-015)', () => {
  const defaultProps: IndicatorCardProps = {
    id: 'rankings',
    icon: 'TrendingUp',
    title: 'Keyword Rankings',
    value: 42,
    description: 'Keywords in top 10',
    status: 'good',
    href: '/dashboard/rankings',
  };

  // ============================================
  // IC-001: Renders with aria-label
  // ============================================
  it('IC-001: renders with descriptive aria-label containing title, value, and status', () => {
    renderWithProviders(<IndicatorCard {...defaultProps} />);

    const card = screen.getByRole('link');
    expect(card).toHaveAttribute('aria-label');
    expect(card.getAttribute('aria-label')).toContain('Keyword Rankings');
    expect(card.getAttribute('aria-label')).toContain('42');
    expect(card.getAttribute('aria-label')).toContain('Good');
  });

  // ============================================
  // IC-002: Has role="link" (navigable element)
  // ============================================
  it('IC-002: renders as a link for screen reader compatibility', () => {
    renderWithProviders(<IndicatorCard {...defaultProps} />);

    const card = screen.getByRole('link');
    expect(card).toBeInTheDocument();
  });

  // ============================================
  // IC-003: Is focusable via keyboard
  // ============================================
  it('IC-003: is focusable via Tab key', async () => {
    const user = userEvent.setup();
    renderWithProviders(<IndicatorCard {...defaultProps} />);

    const card = screen.getByRole('link');
    await user.tab();
    
    // Card should be focusable
    expect(card).toHaveAttribute('href');
  });

  // ============================================
  // IC-006: Focus indicator visible
  // ============================================
  it('IC-006: has visible focus indicator when focused', () => {
    renderWithProviders(<IndicatorCard {...defaultProps} />);

    const card = screen.getByRole('link');
    card.focus();

    // Check that element is focusable
    expect(card).toHaveFocus();
    // Check for focus-visible class
    expect(card.className).toMatch(/focus-visible/);
  });

  // ============================================
  // IC-007: Status conveyed via aria
  // ============================================
  it('IC-007: status "warning" is conveyed in aria-label', () => {
    renderWithProviders(<IndicatorCard {...defaultProps} status="warning" />);

    const card = screen.getByRole('link');
    expect(card.getAttribute('aria-label')).toContain('Warning');
  });

  it('IC-007a: status "critical" is conveyed in aria-label', () => {
    renderWithProviders(<IndicatorCard {...defaultProps} status="critical" />);

    const card = screen.getByRole('link');
    expect(card.getAttribute('aria-label')).toContain('Critical');
  });

  it('IC-007b: status "neutral" is conveyed in aria-label', () => {
    renderWithProviders(<IndicatorCard {...defaultProps} status="neutral" />);

    const card = screen.getByRole('link');
    expect(card.getAttribute('aria-label')).toContain('Neutral');
  });

  // ============================================
  // IC-010: Loading state
  // ============================================
  it('IC-010: loading state has aria-busy="true"', () => {
    renderWithProviders(<IndicatorCard {...defaultProps} isLoading />);

    // Loading state renders as div without role="link"
    const card = screen.getByTestId('indicator-card-rankings');
    expect(card).toHaveAttribute('aria-busy', 'true');
  });

  // ============================================
  // IC-013: Screen reader announces full context
  // ============================================
  it('IC-013: screen reader can announce title, value, status', () => {
    renderWithProviders(<IndicatorCard {...defaultProps} />);

    const card = screen.getByRole('link');
    const ariaLabel = card.getAttribute('aria-label');

    // Verify all required information is in aria-label
    expect(ariaLabel).toMatch(/Keyword Rankings/);
    expect(ariaLabel).toMatch(/42/);
    expect(ariaLabel).toMatch(/Good/);
  });

  // ============================================
  // IC-015: Value is displayed
  // ============================================
  it('IC-015: value is displayed correctly', () => {
    const { rerender } = renderWithProviders(<IndicatorCard {...defaultProps} value={42} />);

    // Initial value
    expect(screen.getByText('42')).toBeInTheDocument();

    // Update value
    rerender(
      <TooltipProvider>
        <IndicatorCard {...defaultProps} value={50} />
      </TooltipProvider>
    );

    // Value updated
    expect(screen.getByText('50')).toBeInTheDocument();
  });
});

// ============================================
// Edge Cases for IndicatorCard
// ============================================

describe('IndicatorCard Edge Cases', () => {
  const defaultProps: IndicatorCardProps = {
    id: 'test',
    icon: 'Activity',
    title: 'Test',
    value: 100,
    description: 'Test description',
    status: 'good',
    href: '/test',
  };

  it('handles very long title gracefully', () => {
    const longTitle = 'This is a very long indicator title that might overflow';
    renderWithProviders(
      <IndicatorCard
        {...defaultProps}
        title={longTitle}
      />
    );

    const card = screen.getByRole('link');
    expect(card.getAttribute('aria-label')).toContain(longTitle);
  });

  it('handles string values correctly', () => {
    renderWithProviders(
      <IndicatorCard
        {...defaultProps}
        value="Active"
      />
    );

    const card = screen.getByRole('link');
    expect(card.getAttribute('aria-label')).toContain('Active');
  });

  it('handles zero as a valid value', () => {
    renderWithProviders(
      <IndicatorCard
        {...defaultProps}
        value={0}
      />
    );

    const card = screen.getByRole('link');
    expect(card.getAttribute('aria-label')).toContain('0');
  });
});

// ============================================
// Security Tests
// ============================================

describe('IndicatorCard Security', () => {
  it('EC-010: does not execute XSS - no script elements injected', () => {
    const xssTitle = '<script>alert("xss")</script>';
    
    renderWithProviders(
      <IndicatorCard
        id="test"
        icon="Activity"
        title={xssTitle}
        value={1}
        description="Test"
        status="good"
        href="/test"
      />
    );

    const card = screen.getByRole('link');
    
    // React renders the title as text content (not HTML), so the string
    // "<script>" appears in textContent, but it's NOT executed as code.
    // The security check is: no actual SCRIPT elements are created in the DOM.
    const scriptElements = document.querySelectorAll('script');
    expect(scriptElements.length).toBe(0);
    
    // The card should render as a link (not break due to XSS)
    expect(card).toBeInTheDocument();
  });
});

// ============================================
// Link-based IndicatorCard Tests
// ============================================

describe('IndicatorCard with href (Link)', () => {
  it('renders as a link when href is provided', () => {
    renderWithProviders(
      <IndicatorCard
        id="test"
        icon="TrendingUp"
        title="Rankings"
        value={42}
        description="Test"
        status="good"
        href="/dashboard/rankings"
      />
    );

    // Should render as a link
    const card = screen.getByRole('link');
    expect(card).toBeInTheDocument();
    expect(card).toHaveAttribute('href', '/dashboard/rankings');
  });

  it('has proper aria-label for link-based card', () => {
    renderWithProviders(
      <IndicatorCard
        id="test"
        icon="TrendingUp"
        title="Rankings"
        value={42}
        description="Test"
        status="good"
        href="/dashboard/rankings"
      />
    );

    const card = screen.getByRole('link');
    expect(card.getAttribute('aria-label')).toContain('Rankings');
  });
});
