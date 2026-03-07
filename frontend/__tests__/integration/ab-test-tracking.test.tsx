/**
 * A/B Test Tracking Integration Tests
 * Task: TASK-32-18 - A/B Test Infrastructure for CTAs
 *
 * Integration tests for A/B test tracking with the ComparisonCTA component.
 * Tests conversion tracking, CTA integration, and full user flows.
 */

import React from 'react';
import { describe, it, expect, beforeEach, vi } from 'vitest';
import { render, screen, fireEvent } from '@testing-library/react';
import '@testing-library/jest-dom';

// ============================================================================
// Mock localStorage
// ============================================================================

const createLocalStorageMock = () => {
  let store: Record<string, string> = {};
  return {
    getItem: (key: string) => store[key] || null,
    setItem: (key: string, value: string) => { store[key] = value; },
    removeItem: (key: string) => { delete store[key]; },
    clear: () => { store = {}; },
    get store() { return store; },
    get length() { return Object.keys(store).length; },
    key: (index: number) => Object.keys(store)[index] || null,
  };
};

const localStorageMock = createLocalStorageMock();

Object.defineProperty(global, 'localStorage', {
  value: localStorageMock,
  writable: true,
  configurable: true,
});

// ============================================================================
// Mock analytics
// ============================================================================

const mockTrackEvent = vi.fn();

vi.mock('@/lib/analytics', () => ({
  trackEvent: (...args: unknown[]) => mockTrackEvent(...args),
}));

// ============================================================================
// Mock Lucide icons
// ============================================================================

vi.mock('lucide-react', () => ({
  ArrowRight: () => <span data-testid="arrow-icon">→</span>,
  ExternalLink: () => <span data-testid="external-icon">↗</span>,
}));

// ============================================================================
// Import after mocks
// ============================================================================

import { ComparisonCTA, ComparisonCTAWithABTest } from '@/components/compare/ComparisonCTA';

// ============================================================================
// Tests
// ============================================================================

describe('A/B Test Tracking Integration', () => {
  beforeEach(() => {
    localStorageMock.clear();
    mockTrackEvent.mockClear();
  });

  describe('INT-CONVERSION: Conversion Tracking', () => {
    it('INT-001: CTA click should track conversion', async () => {
      render(<ComparisonCTA abTestGroup="a" />);
      
      const button = screen.getByTestId('cta-primary-button');
      fireEvent.click(button);
      
      expect(mockTrackEvent).toHaveBeenCalledWith(
        'ab_test_conversion',
        expect.objectContaining({
          test_id: expect.any(String),
        })
      );
    });

    it('INT-002: Conversion event should include test ID', async () => {
      render(<ComparisonCTA abTestGroup="b" abTestId="comparison-cta-text" />);
      
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
      render(<ComparisonCTA abTestGroup="a" abTestId="comparison-cta-text" />);
      
      const button = screen.getByTestId('cta-primary-button');
      fireEvent.click(button);
      
      expect(mockTrackEvent).toHaveBeenCalledWith(
        'ab_test_conversion',
        expect.objectContaining({
          variant: 'a',
        })
      );
    });

    it('INT-006: Conversion event should include conversion type', async () => {
      render(<ComparisonCTA abTestGroup="a" abTestId="comparison-cta-text" />);
      
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
      const { rerender } = render(<ComparisonCTA abTestGroup="a" abTestId="comparison-cta-text" />);
      
      fireEvent.click(screen.getByTestId('cta-primary-button'));
      
      mockTrackEvent.mockClear();
      
      rerender(<ComparisonCTA abTestGroup="b" abTestId="comparison-cta-text" />);
      fireEvent.click(screen.getByTestId('cta-primary-button'));
      
      expect(mockTrackEvent).toHaveBeenCalledWith(
        'ab_test_conversion',
        expect.objectContaining({
          variant: 'b',
        })
      );
    });

    it('INT-009: Conversion should be tracked only on actual click', async () => {
      render(<ComparisonCTA abTestGroup="a" abTestId="comparison-cta-text" />);
      
      // No click yet
      expect(mockTrackEvent).not.toHaveBeenCalledWith('ab_test_conversion', expect.anything());
      
      // Click
      fireEvent.click(screen.getByTestId('cta-primary-button'));
      
      expect(mockTrackEvent).toHaveBeenCalledWith('ab_test_conversion', expect.anything());
    });
  });

  describe('INT-CTA: CTA Component Integration', () => {
    it('INT-013: Primary CTA with A/B test should show dynamic text', async () => {
      render(<ComparisonCTAWithABTest testId="comparison-cta-text" variant="primary" />);
      
      // Should show one of the variant texts
      const button = screen.getByTestId('cta-primary-button');
      expect(button).toHaveTextContent(/Try|Start/);
    });

    it('INT-014: Secondary CTA with A/B test should show dynamic text', async () => {
      render(<ComparisonCTAWithABTest testId="comparison-cta-text" variant="secondary" />);
      
      const link = screen.getByTestId('cta-link');
      expect(link).toHaveTextContent(/Try|Start/);
    });

    it('INT-016: CTA without A/B test should show default text', async () => {
      render(<ComparisonCTA />);
      
      expect(screen.getByText('Try SEOIndicator Free')).toBeInTheDocument();
      expect(screen.getByTestId('cta-primary-button')).not.toHaveAttribute('data-ab-test-group');
    });

    it('INT-017: CTA with abTestGroup prop should pass group', async () => {
      render(<ComparisonCTA abTestGroup="b" />);
      
      expect(screen.getByTestId('cta-primary-button')).toHaveAttribute('data-ab-test-group', 'b');
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

    it('INT-024: Expired test should use default text', async () => {
      render(<ComparisonCTAWithABTest testId="expired-test" />);
      
      const button = screen.getByTestId('cta-primary-button');
      expect(button).toHaveTextContent('Control');
    });
  });

  describe('INT-ANALYTICS: Analytics Integration', () => {
    it('INT-025: trackEvent should be called with correct params', async () => {
      render(<ComparisonCTA abTestGroup="a" />);
      
      fireEvent.click(screen.getByTestId('cta-primary-button'));
      
      expect(mockTrackEvent).toHaveBeenCalledWith(
        'cta_click',
        expect.objectContaining({
          variant: 'primary',
          href: '/register',
          abTestGroup: 'a',
        })
      );
    });

    it('INT-026: Event name should be cta_click', async () => {
      render(<ComparisonCTA abTestGroup="a" />);
      
      fireEvent.click(screen.getByTestId('cta-primary-button'));
      
      expect(mockTrackEvent).toHaveBeenCalledWith('cta_click', expect.anything());
    });

    it('INT-027: Event should include all required properties', async () => {
      render(<ComparisonCTA abTestGroup="a" />);
      
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

    it('INT-031: Analytics error should not break CTA', async () => {
      mockTrackEvent.mockImplementation(() => {
        throw new Error('Analytics error');
      });
      
      expect(() => {
        render(<ComparisonCTA abTestGroup="a" />);
        fireEvent.click(screen.getByTestId('cta-primary-button'));
      }).not.toThrow();
    });
  });

  describe('INT-FLOW: Full User Flows', () => {
    it('INT-035: User lands on page and sees variant', async () => {
      render(<ComparisonCTAWithABTest />);
      
      const button = screen.getByTestId('cta-primary-button');
      expect(button).toBeInTheDocument();
      expect(button).toHaveTextContent(/Try|Start/);
    });

    it('INT-036: User clicks CTA and conversion is tracked', async () => {
      render(<ComparisonCTAWithABTest />);
      
      fireEvent.click(screen.getByTestId('cta-primary-button'));
      
      expect(mockTrackEvent).toHaveBeenCalledWith(
        'ab_test_conversion',
        expect.objectContaining({
          conversion_type: 'cta_click',
        })
      );
    });

    it('INT-037: User refreshes and sees same variant', async () => {
      const { rerender } = render(<ComparisonCTAWithABTest />);
      const firstText = screen.getByTestId('cta-primary-button').textContent;
      
      // Simulate refresh
      rerender(<ComparisonCTAWithABTest />);
      
      // Should still show same variant
      expect(screen.getByTestId('cta-primary-button').textContent).toBe(firstText);
    });

    it('INT-040: User clears storage and gets new variant', async () => {
      render(<ComparisonCTAWithABTest />);
      const firstText = screen.getByTestId('cta-primary-button').textContent;
      
      // Clear storage
      localStorageMock.clear();
      
      // New variant assignment (might be same or different)
      const { rerender } = render(<ComparisonCTAWithABTest />);
      rerender(<ComparisonCTAWithABTest />);
      
      // Should show valid text
      expect(screen.getByTestId('cta-primary-button')).toHaveTextContent(/Try|Start/);
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

  describe('INT-VARIANTS: Variant Rendering', () => {
    it('should render primary variant correctly', () => {
      render(<ComparisonCTA variant="primary" />);
      
      expect(screen.getByTestId('cta-primary')).toBeInTheDocument();
      expect(screen.getByTestId('cta-primary-button')).toBeInTheDocument();
    });

    it('should render secondary variant correctly', () => {
      render(<ComparisonCTA variant="secondary" />);
      
      expect(screen.getByTestId('cta-link')).toBeInTheDocument();
      expect(screen.getByTestId('external-icon')).toBeInTheDocument();
    });

    it('should render sticky variant correctly', () => {
      // Sticky variant appears after 500px scroll
      render(<ComparisonCTA variant="sticky" />);
      
      // Initially not visible
      expect(screen.queryByTestId('cta-sticky')).not.toBeInTheDocument();
    });

    it('should use custom text when provided', () => {
      render(<ComparisonCTA text="Custom CTA Text" />);
      
      expect(screen.getByText('Custom CTA Text')).toBeInTheDocument();
    });

    it('should use custom href when provided', () => {
      render(<ComparisonCTA href="/signup" />);
      
      expect(screen.getByTestId('cta-primary-button')).toHaveAttribute('href', '/signup');
    });
  });
});
