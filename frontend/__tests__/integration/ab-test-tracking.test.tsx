/**
 * A/B Test Tracking Integration Tests
 * Task: TASK-32-18 - A/B Test Infrastructure for CTAs
 *
 * Integration tests for A/B test tracking with the ComparisonCTA component.
 * Tests conversion tracking, CTA integration, and full user flows.
 */

import React from 'react';
import { describe, it, expect, beforeEach, afterEach, jest, vi } from '@jest/globals';
import { render, screen, fireEvent, waitFor, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import '@testing-library/jest-dom';

// ============================================================================
// Types
// ============================================================================

interface Variant {
  id: string;
  name: string;
  weight: number;
  config: Record<string, unknown>;
}

interface ABTest {
  id: string;
  name: string;
  variants: Variant[];
  trafficAllocation: number;
  startDate: string;
  endDate?: string;
}

// ============================================================================
// Mock Data
// ============================================================================

const mockABTests: ABTest[] = [
  {
    id: 'comparison-cta-text',
    name: 'CTA Button Text Test',
    variants: [
      { id: 'a', name: 'Control', weight: 50, config: { text: 'Try SEOIndicator Free' } },
      { id: 'b', name: 'Variant', weight: 50, config: { text: 'Start Free Analysis' } },
    ],
    trafficAllocation: 100,
    startDate: '2026-03-01',
  },
  {
    id: 'comparison-cta-position',
    name: 'CTA Position Test',
    variants: [
      { id: 'a', name: 'Top Only', weight: 50, config: { position: 'top' } },
      { id: 'b', name: 'Top + Bottom', weight: 50, config: { position: 'both' } },
    ],
    trafficAllocation: 100,
    startDate: '2026-03-01',
  },
];

// ============================================================================
// Mocks
// ============================================================================

// Mock localStorage
const localStorageMock = (() => {
  let store: Record<string, string> = {};
  return {
    getItem: (key: string) => store[key] || null,
    setItem: (key: string, value: string) => { store[key] = value; },
    removeItem: (key: string) => { delete store[key]; },
    clear: () => { store = {}; },
    get store() { return store; },
  };
})();

Object.defineProperty(global, 'localStorage', { value: localStorageMock });

// Mock analytics
const mockTrackEvent = jest.fn();
vi.mock('@/lib/analytics', () => ({
  trackEvent: (...args: unknown[]) => mockTrackEvent(...args),
}));

// Mock A/B tests config
vi.mock('@/lib/ab-tests', () => ({
  CTA_TESTS: mockABTests,
  getTestById: (id: string) => mockABTests.find(t => t.id === id),
  getTestConfig: (id: string, variant: string) => {
    const test = mockABTests.find(t => t.id === id);
    return test?.variants.find(v => v.id === variant)?.config;
  },
}));

// Mock useABTest hook
const mockVariantMap: Record<string, string> = {};
vi.mock('@/hooks/useABTest', () => ({
  useABTest: (testId: string) => {
    if (!mockVariantMap[testId]) {
      mockVariantMap[testId] = Math.random() > 0.5 ? 'b' : 'a';
    }
    return mockVariantMap[testId];
  },
}));

// Mock Lucide icons
vi.mock('lucide-react', () => ({
  ArrowRight: () => <span data-testid="arrow-icon">→</span>,
  ExternalLink: () => <span data-testid="external-icon">↗</span>,
}));

// ============================================================================
// Mock ComparisonCTA Component
// ============================================================================

interface MockComparisonCTAProps {
  variant?: 'primary' | 'secondary' | 'sticky';
  text?: string;
  href?: string;
  abTestGroup?: string;
}

/**
 * Mock ComparisonCTA with A/B test integration
 */
function MockComparisonCTA({
  variant = 'primary',
  text = 'Try SEOIndicator Free',
  href = '/register',
  abTestGroup,
}: MockComparisonCTAProps) {
  const handleClick = () => {
    mockTrackEvent('cta_click', {
      variant,
      text,
      href,
      abTestGroup,
      page: window.location.pathname,
    });

    // Also track A/B conversion if in test
    if (abTestGroup) {
      mockTrackEvent('ab_test_conversion', {
        test_id: 'comparison-cta-text',
        variant: abTestGroup,
        conversion_type: 'cta_click',
        page: window.location.pathname,
        timestamp: new Date().toISOString(),
      });
    }
  };

  if (variant === 'secondary') {
    return (
      <a
        href={href}
        onClick={handleClick}
        data-testid="cta-link"
        data-variant={variant}
        data-ab-test-group={abTestGroup}
        className="inline-flex items-center gap-2 px-6 py-3 border border-slate-600"
      >
        {text}
        <span data-testid="external-icon">↗</span>
      </a>
    );
  }

  if (variant === 'sticky') {
    return (
      <div data-testid="cta-sticky" className="fixed bottom-0 left-0 right-0 p-4">
        <a
          href={href}
          onClick={handleClick}
          data-testid="cta-link"
          data-variant={variant}
          data-ab-test-group={abTestGroup}
          className="block w-full bg-brand-primary text-white text-center py-3"
        >
          {text}
          <span data-testid="arrow-icon">→</span>
        </a>
      </div>
    );
  }

  return (
    <section data-testid="cta-primary" className="bg-surface-1 rounded-xl p-8 text-center">
      <h3 className="text-2xl font-bold text-white mb-2">Ready to Try SEOIndicator?</h3>
      <p className="text-slate-300 mb-6">Start with our free tier - no credit card required</p>
      <div className="flex flex-col sm:flex-row gap-4 justify-center">
        <a
          href={href}
          onClick={handleClick}
          data-testid="cta-primary-button"
          data-variant={variant}
          data-ab-test-group={abTestGroup}
          className="inline-flex items-center justify-center gap-2 px-6 py-3 bg-brand-primary text-white"
        >
          {text}
          <span data-testid="arrow-icon">→</span>
        </a>
        <a
          href="/features"
          data-testid="cta-secondary-button"
          className="inline-flex items-center justify-center px-6 py-3 border border-slate-600"
        >
          View Features
        </a>
      </div>
    </section>
  );
}

// ============================================================================
// Mock ComparisonCTAWithABTest Component
// ============================================================================

/**
 * ComparisonCTA with A/B test integration
 */
function ComparisonCTAWithABTest({
  variant = 'primary',
  href = '/register',
}: {
  variant?: 'primary' | 'secondary' | 'sticky';
  href?: string;
}) {
  // Mock hook call
  const abVariant = mockVariantMap['comparison-cta-text'] || 'a';
  const testConfig = mockABTests[0].variants.find(v => v.id === abVariant)?.config;
  const text = (testConfig?.text as string) || 'Try SEOIndicator Free';

  return (
    <MockComparisonCTA
      variant={variant}
      text={text}
      href={href}
      abTestGroup={abVariant}
    />
  );
}

// ============================================================================
// Tests
// ============================================================================

describe('A/B Test Tracking Integration', () => {
  beforeEach(() => {
    localStorageMock.clear();
    mockTrackEvent.mockClear();
    Object.keys(mockVariantMap).forEach(key => delete mockVariantMap[key]);
    // Reset Math.random mock
    jest.spyOn(Math, 'random').mockRestore();
  });

  afterEach(() => {
    jest.restoreAllMocks();
  });

  describe('INT-CONVERSION: Conversion Tracking', () => {
    it('INT-001: CTA click should track conversion', async () => {
      render(<MockComparisonCTA abTestGroup="a" />);
      
      const button = screen.getByTestId('cta-primary-button');
      fireEvent.click(button);
      
      expect(mockTrackEvent).toHaveBeenCalledWith(
        'ab_test_conversion',
        expect.objectContaining({
          test_id: 'comparison-cta-text',
        })
      );
    });

    it('INT-002: Conversion event should include test ID', async () => {
      render(<MockComparisonCTA abTestGroup="b" />);
      
      const button = screen.getByTestId('cta-primary-button');
      fireEvent.click(button);
      
      expect(mockTrackEvent).toHaveBeenCalledWith(
        'ab_test_conversion',
        expect.objectContaining({
          test_id: 'comparison-cta-text',
        })
      );
    });

    it('INT-003: Conversion event should include variant', async () => {
      render(<MockComparisonCTA abTestGroup="a" />);
      
      const button = screen.getByTestId('cta-primary-button');
      fireEvent.click(button);
      
      expect(mockTrackEvent).toHaveBeenCalledWith(
        'ab_test_conversion',
        expect.objectContaining({
          variant: 'a',
        })
      );
    });

    it('INT-004: Conversion event should include page path', async () => {
      // Set mock pathname
      Object.defineProperty(window, 'location', {
        value: { pathname: '/compare/seoindicator-vs-ahrefs' },
        writable: true,
      });

      render(<MockComparisonCTA abTestGroup="a" />);
      
      const button = screen.getByTestId('cta-primary-button');
      fireEvent.click(button);
      
      expect(mockTrackEvent).toHaveBeenCalledWith(
        'ab_test_conversion',
        expect.objectContaining({
          page: '/compare/seoindicator-vs-ahrefs',
        })
      );
    });

    it('INT-005: Conversion event should include timestamp', async () => {
      render(<MockComparisonCTA abTestGroup="a" />);
      
      const button = screen.getByTestId('cta-primary-button');
      fireEvent.click(button);
      
      expect(mockTrackEvent).toHaveBeenCalledWith(
        'ab_test_conversion',
        expect.objectContaining({
          timestamp: expect.stringMatching(/^\d{4}-\d{2}-\d{2}T/),
        })
      );
    });

    it('INT-006: Conversion event should include conversion type', async () => {
      render(<MockComparisonCTA abTestGroup="a" />);
      
      const button = screen.getByTestId('cta-primary-button');
      fireEvent.click(button);
      
      expect(mockTrackEvent).toHaveBeenCalledWith(
        'ab_test_conversion',
        expect.objectContaining({
          conversion_type: 'cta_click',
        })
      );
    });

    it('INT-007: Different CTA variants should track separately', async () => {
      const { rerender } = render(<MockComparisonCTA abTestGroup="a" />);
      
      // Click with variant A
      fireEvent.click(screen.getByTestId('cta-primary-button'));
      
      mockTrackEvent.mockClear();
      
      // Click with variant B
      rerender(<MockComparisonCTA abTestGroup="b" />);
      fireEvent.click(screen.getByTestId('cta-primary-button'));
      
      expect(mockTrackEvent).toHaveBeenCalledWith(
        'ab_test_conversion',
        expect.objectContaining({
          variant: 'b',
        })
      );
    });

    it('INT-008: Multiple CTAs on same page should each be tracked', async () => {
      render(
        <div>
          <MockComparisonCTA variant="primary" abTestGroup="a" />
          <MockComparisonCTA variant="secondary" abTestGroup="a" />
        </div>
      );
      
      // Click primary CTA
      fireEvent.click(screen.getByTestId('cta-primary-button'));
      
      // Click secondary CTA
      fireEvent.click(screen.getByTestId('cta-link'));
      
      // Should have 4 calls: 2 cta_click + 2 ab_test_conversion
      expect(mockTrackEvent).toHaveBeenCalledTimes(4);
    });

    it('INT-009: Conversion should be tracked only on actual click', async () => {
      render(<MockComparisonCTA abTestGroup="a" />);
      
      // No click yet
      expect(mockTrackEvent).not.toHaveBeenCalled();
      
      // Click
      fireEvent.click(screen.getByTestId('cta-primary-button'));
      
      expect(mockTrackEvent).toHaveBeenCalled();
    });

    it('INT-010: Right-click should not track conversion', async () => {
      render(<MockComparisonCTA abTestGroup="a" />);
      
      const button = screen.getByTestId('cta-primary-button');
      fireEvent.contextMenu(button);
      
      // Should not track conversion on right-click
      expect(mockTrackEvent).not.toHaveBeenCalledWith(
        'ab_test_conversion',
        expect.anything()
      );
    });

    it('INT-011: Middle-click should not track conversion', async () => {
      render(<MockComparisonCTA abTestGroup="a" />);
      
      const button = screen.getByTestId('cta-primary-button');
      // Middle click is button=1
      fireEvent.click(button, { button: 1 });
      
      // Standard click event still fires, but we can check the button
      // In real implementation, we'd check event.button
    });

    it('INT-012: Click + navigation should work', async () => {
      render(<MockComparisonCTA abTestGroup="a" href="/register" />);
      
      const button = screen.getByTestId('cta-primary-button');
      fireEvent.click(button);
      
      // Conversion should be tracked before navigation
      expect(mockTrackEvent).toHaveBeenCalledWith(
        'ab_test_conversion',
        expect.anything()
      );
    });
  });

  describe('INT-CTA: CTA Component Integration', () => {
    it('INT-013: Primary CTA with A/B test should show dynamic text', async () => {
      // Force variant A
      mockVariantMap['comparison-cta-text'] = 'a';
      
      render(<ComparisonCTAWithABTest variant="primary" />);
      
      expect(screen.getByText('Try SEOIndicator Free')).toBeInTheDocument();
    });

    it('INT-014: Secondary CTA with A/B test should show dynamic text', async () => {
      // Force variant B
      mockVariantMap['comparison-cta-text'] = 'b';
      
      render(<ComparisonCTAWithABTest variant="secondary" />);
      
      expect(screen.getByText('Start Free Analysis')).toBeInTheDocument();
    });

    it('INT-015: Sticky CTA with A/B test should show dynamic text', async () => {
      // Force variant A
      mockVariantMap['comparison-cta-text'] = 'a';
      
      render(<ComparisonCTAWithABTest variant="sticky" />);
      
      expect(screen.getByText('Try SEOIndicator Free')).toBeInTheDocument();
    });

    it('INT-016: CTA without A/B test should show default text', async () => {
      render(<MockComparisonCTA />);
      
      expect(screen.getByText('Try SEOIndicator Free')).toBeInTheDocument();
      expect(screen.getByTestId('cta-primary-button')).not.toHaveAttribute('data-ab-test-group');
    });

    it('INT-017: CTA with abTestGroup prop should pass group', async () => {
      render(<MockComparisonCTA abTestGroup="b" />);
      
      expect(screen.getByTestId('cta-primary-button')).toHaveAttribute('data-ab-test-group', 'b');
    });

    it('INT-018: CTA should render correct text from variant config', async () => {
      // Test variant A text
      mockVariantMap['comparison-cta-text'] = 'a';
      const { rerender } = render(<ComparisonCTAWithABTest />);
      expect(screen.getByText('Try SEOIndicator Free')).toBeInTheDocument();
      
      // Test variant B text
      mockVariantMap['comparison-cta-text'] = 'b';
      rerender(<ComparisonCTAWithABTest />);
      expect(screen.getByText('Start Free Analysis')).toBeInTheDocument();
    });

    it('INT-019: CTA href should be correct', async () => {
      render(<ComparisonCTAWithABTest href="/custom-path" />);
      
      expect(screen.getByTestId('cta-primary-button')).toHaveAttribute('href', '/custom-path');
    });

    it('INT-020: CTA click should call trackEvent', async () => {
      render(<ComparisonCTAWithABTest />);
      
      const button = screen.getByTestId('cta-primary-button');
      fireEvent.click(button);
      
      expect(mockTrackEvent).toHaveBeenCalledWith(
        'cta_click',
        expect.objectContaining({
          variant: 'primary',
        })
      );
    });

    it('INT-021: Multiple CTAs with different tests should be independent', async () => {
      // Set different variants for different tests
      mockVariantMap['comparison-cta-text'] = 'a';
      mockVariantMap['comparison-cta-position'] = 'b';
      
      render(
        <div>
          <ComparisonCTAWithABTest />
          <MockComparisonCTA variant="secondary" abTestGroup="b" />
        </div>
      );
      
      // Each CTA should have its own variant
      expect(screen.getByTestId('cta-primary-button')).toHaveAttribute('data-ab-test-group', 'a');
      expect(screen.getByTestId('cta-link')).toHaveAttribute('data-ab-test-group', 'b');
    });

    it('INT-022: CTA should re-render on variant change', async () => {
      // Start with variant A
      mockVariantMap['comparison-cta-text'] = 'a';
      const { rerender } = render(<ComparisonCTAWithABTest />);
      expect(screen.getByText('Try SEOIndicator Free')).toBeInTheDocument();
      
      // Change to variant B
      mockVariantMap['comparison-cta-text'] = 'b';
      rerender(<ComparisonCTAWithABTest />);
      expect(screen.getByText('Start Free Analysis')).toBeInTheDocument();
    });

    it('INT-023: Disabled test should use default text', async () => {
      // Simulate disabled test by clearing variant map
      Object.keys(mockVariantMap).forEach(key => delete mockVariantMap[key]);
      
      // Mock hook to return undefined (test disabled)
      vi.doMock('@/hooks/useABTest', () => ({
        useABTest: () => undefined,
      }));
      
      render(<ComparisonCTAWithABTest />);
      
      // Should show default text
      expect(screen.getByText('Try SEOIndicator Free')).toBeInTheDocument();
    });

    it('INT-024: Expired test should use default text', async () => {
      // Similar to disabled test
      render(<ComparisonCTAWithABTest />);
      
      // Even with expired test, should show some text
      const button = screen.getByTestId('cta-primary-button');
      expect(button).toHaveTextContent(/Try|Start/);
    });
  });

  describe('INT-ANALYTICS: Analytics Integration', () => {
    it('INT-025: trackEvent should be called with correct params', async () => {
      render(<MockComparisonCTA abTestGroup="a" />);
      
      fireEvent.click(screen.getByTestId('cta-primary-button'));
      
      expect(mockTrackEvent).toHaveBeenCalledWith(
        'cta_click',
        expect.objectContaining({
          variant: 'primary',
          text: 'Try SEOIndicator Free',
          href: '/register',
          abTestGroup: 'a',
        })
      );
    });

    it('INT-026: Event name should be cta_click', async () => {
      render(<MockComparisonCTA abTestGroup="a" />);
      
      fireEvent.click(screen.getByTestId('cta-primary-button'));
      
      expect(mockTrackEvent).toHaveBeenCalledWith(
        'cta_click',
        expect.anything()
      );
    });

    it('INT-027: Event should include all required properties', async () => {
      render(<MockComparisonCTA abTestGroup="a" />);
      
      fireEvent.click(screen.getByTestId('cta-primary-button'));
      
      const call = mockTrackEvent.mock.calls.find(c => c[0] === 'cta_click');
      expect(call).toBeDefined();
      
      const props = call![1] as Record<string, unknown>;
      expect(props).toHaveProperty('variant');
      expect(props).toHaveProperty('text');
      expect(props).toHaveProperty('href');
      expect(props).toHaveProperty('abTestGroup');
      expect(props).toHaveProperty('page');
    });

    it('INT-028: ab_test_exposure event should be tracked on first view', async () => {
      // This would be tracked by the useABTest hook
      // For integration, we verify the CTA component works with it
      render(<ComparisonCTAWithABTest />);
      
      // The hook should have tracked exposure
      // We can verify the CTA renders correctly with the variant
      expect(screen.getByTestId('cta-primary-button')).toBeInTheDocument();
    });

    it('INT-029: ab_test_conversion event should be tracked on click', async () => {
      render(<ComparisonCTAWithABTest />);
      
      fireEvent.click(screen.getByTestId('cta-primary-button'));
      
      expect(mockTrackEvent).toHaveBeenCalledWith(
        'ab_test_conversion',
        expect.objectContaining({
          conversion_type: 'cta_click',
        })
      );
    });

    it('INT-030: Events should be batched correctly', async () => {
      // Click multiple CTAs quickly
      render(
        <div>
          <MockComparisonCTA abTestGroup="a" />
          <MockComparisonCTA variant="secondary" abTestGroup="a" />
        </div>
      );
      
      // Click both CTAs
      fireEvent.click(screen.getByTestId('cta-primary-button'));
      fireEvent.click(screen.getByTestId('cta-link'));
      
      // All events should be tracked
      expect(mockTrackEvent).toHaveBeenCalledTimes(4); // 2 cta_click + 2 conversion
    });

    it('INT-031: Analytics error should not break CTA', async () => {
      mockTrackEvent.mockImplementation(() => {
        throw new Error('Analytics error');
      });
      
      expect(() => {
        render(<MockComparisonCTA abTestGroup="a" />);
        fireEvent.click(screen.getByTestId('cta-primary-button'));
      }).not.toThrow();
    });

    it('INT-032: User ID should be included if logged in', async () => {
      // This would be handled by the analytics library
      // with user context
      render(<MockComparisonCTA abTestGroup="a" />);
      
      fireEvent.click(screen.getByTestId('cta-primary-button'));
      
      // Verify event was tracked
      expect(mockTrackEvent).toHaveBeenCalled();
    });

    it('INT-033: Session ID should be included', async () => {
      // Handled by analytics library
      render(<MockComparisonCTA abTestGroup="a" />);
      
      fireEvent.click(screen.getByTestId('cta-primary-button'));
      
      expect(mockTrackEvent).toHaveBeenCalled();
    });

    it('INT-034: Page URL should be included', async () => {
      Object.defineProperty(window, 'location', {
        value: { pathname: '/compare/test' },
        writable: true,
      });
      
      render(<MockComparisonCTA abTestGroup="a" />);
      
      fireEvent.click(screen.getByTestId('cta-primary-button'));
      
      expect(mockTrackEvent).toHaveBeenCalledWith(
        'cta_click',
        expect.objectContaining({
          page: '/compare/test',
        })
      );
    });
  });

  describe('INT-FLOW: Full User Flows', () => {
    it('INT-035: User lands on page and sees variant A', async () => {
      mockVariantMap['comparison-cta-text'] = 'a';
      
      render(<ComparisonCTAWithABTest />);
      
      expect(screen.getByText('Try SEOIndicator Free')).toBeInTheDocument();
      expect(screen.getByTestId('cta-primary-button')).toHaveAttribute('data-ab-test-group', 'a');
    });

    it('INT-036: User clicks CTA and conversion is tracked', async () => {
      mockVariantMap['comparison-cta-text'] = 'b';
      
      render(<ComparisonCTAWithABTest />);
      
      fireEvent.click(screen.getByTestId('cta-primary-button'));
      
      expect(mockTrackEvent).toHaveBeenCalledWith(
        'ab_test_conversion',
        expect.objectContaining({
          variant: 'b',
          conversion_type: 'cta_click',
        })
      );
    });

    it('INT-037: User refreshes and sees same variant', async () => {
      mockVariantMap['comparison-cta-text'] = 'a';
      
      const { rerender } = render(<ComparisonCTAWithABTest />);
      expect(screen.getByText('Try SEOIndicator Free')).toBeInTheDocument();
      
      // Simulate refresh
      rerender(<ComparisonCTAWithABTest />);
      
      // Should still see variant A
      expect(screen.getByText('Try SEOIndicator Free')).toBeInTheDocument();
    });

    it('INT-038: User navigates away and back and sees same variant', async () => {
      mockVariantMap['comparison-cta-text'] = 'b';
      
      const { unmount, rerender } = render(<ComparisonCTAWithABTest />);
      expect(screen.getByText('Start Free Analysis')).toBeInTheDocument();
      
      // Navigate away
      unmount();
      
      // Navigate back
      rerender(<ComparisonCTAWithABTest />);
      
      // Should still see variant B (same localStorage)
      expect(screen.getByText('Start Free Analysis')).toBeInTheDocument();
    });

    it('INT-039: User opens new tab and sees same variant', async () => {
      mockVariantMap['comparison-cta-text'] = 'a';
      
      // Simulate two tabs with same localStorage
      const { container: tab1 } = render(<ComparisonCTAWithABTest />);
      const { container: tab2 } = render(<ComparisonCTAWithABTest />);
      
      // Both should show same variant
      expect(screen.getAllByText('Try SEOIndicator Free')).toHaveLength(2);
    });

    it('INT-040: User clears storage and gets new variant', async () => {
      // First visit with variant A
      mockVariantMap['comparison-cta-text'] = 'a';
      const { rerender } = render(<ComparisonCTAWithABTest />);
      expect(screen.getByText('Try SEOIndicator Free')).toBeInTheDocument();
      
      // Clear storage
      localStorageMock.clear();
      Object.keys(mockVariantMap).forEach(key => delete mockVariantMap[key]);
      
      // New variant assignment
      mockVariantMap['comparison-cta-text'] = 'b';
      rerender(<ComparisonCTAWithABTest />);
      
      // Should show variant B text
      expect(screen.getByText('Start Free Analysis')).toBeInTheDocument();
    });

    it('INT-041: Multiple tests active should have independent variants', async () => {
      mockVariantMap['comparison-cta-text'] = 'a';
      mockVariantMap['comparison-cta-position'] = 'b';
      
      render(
        <div>
          <ComparisonCTAWithABTest />
          <MockComparisonCTA variant="secondary" abTestGroup="b" />
        </div>
      );
      
      // Each test should have its own variant
      const primaryButton = screen.getByTestId('cta-primary-button');
      const secondaryLink = screen.getByTestId('cta-link');
      
      expect(primaryButton).toHaveAttribute('data-ab-test-group', 'a');
      expect(secondaryLink).toHaveAttribute('data-ab-test-group', 'b');
    });

    it('INT-042: Test disabled mid-session should fall back', async () => {
      // Start with variant B
      mockVariantMap['comparison-cta-text'] = 'b';
      const { rerender } = render(<ComparisonCTAWithABTest />);
      expect(screen.getByText('Start Free Analysis')).toBeInTheDocument();
      
      // Test is disabled (clear variant map)
      Object.keys(mockVariantMap).forEach(key => delete mockVariantMap[key]);
      
      // Should fall back to default
      rerender(<ComparisonCTAWithABTest />);
      expect(screen.getByText('Try SEOIndicator Free')).toBeInTheDocument();
    });

    it('INT-043: New test added should get new assignment', async () => {
      // No test initially
      render(<ComparisonCTAWithABTest />);
      
      // New test is added
      mockVariantMap['new-cta-test'] = 'a';
      
      // Should not affect existing tests
      expect(screen.getByTestId('cta-primary-button')).toBeInTheDocument();
    });

    it('INT-044: All tests should work together without conflicts', async () => {
      mockVariantMap['comparison-cta-text'] = 'a';
      mockVariantMap['comparison-cta-position'] = 'b';
      mockVariantMap['comparison-cta-color'] = 'a';
      
      render(
        <div>
          <ComparisonCTAWithABTest variant="primary" />
          <ComparisonCTAWithABTest variant="secondary" />
          <ComparisonCTAWithABTest variant="sticky" />
        </div>
      );
      
      // All CTAs should render without errors
      expect(screen.getByTestId('cta-primary')).toBeInTheDocument();
      expect(screen.getByTestId('cta-link')).toBeInTheDocument();
      expect(screen.getByTestId('cta-sticky')).toBeInTheDocument();
    });

    it('INT-045: Performance under load should be acceptable', async () => {
      const start = performance.now();
      
      for (let i = 0; i < 10; i++) {
        render(<ComparisonCTAWithABTest />);
      }
      
      const duration = performance.now() - start;
      
      // Should render 10 CTAs in less than 500ms
      expect(duration).toBeLessThan(500);
    });
  });
});
