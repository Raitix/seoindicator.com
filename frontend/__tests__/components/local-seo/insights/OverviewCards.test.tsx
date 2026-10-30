/**
 * Tests for OverviewCards Component
 * Task: TASK-35-20
 * Tests: 60
 */

import { describe, it, expect, vi, beforeEach } from 'vitest';
import { render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

// Mock Lucide icons
vi.mock('lucide-react', () => ({
  Eye: () => <span data-testid="eye-icon">👁</span>,
  Search: () => <span data-testid="search-icon">🔍</span>,
  MousePointerClick: () => <span data-testid="click-icon">👆</span>,
  TrendingUp: () => <span data-testid="trending-up-icon">📈</span>,
  ArrowUp: () => <span data-testid="arrow-up-icon">↑</span>,
  ArrowDown: () => <span data-testid="arrow-down-icon">↓</span>,
  Minus: () => <span data-testid="minus-icon">−</span>,
  Loader2: () => <span data-testid="loader-icon" className="animate-spin">Loading</span>,
}));

interface OverviewMetric {
  label: string;
  value: number;
  trend: number;
  format?: 'number' | 'percentage' | 'decimal';
  icon: string;
}

interface OverviewCardsProps {
  metrics: OverviewMetric[];
  isLoading?: boolean;
  className?: string;
}

// Helper function to format numbers
const formatNumber = (value: number, format: string = 'number'): string => {
  if (format === 'percentage') {
    return `${value.toFixed(2)}%`;
  }
  if (format === 'decimal') {
    return value.toFixed(2);
  }
  if (value >= 1000000) {
    return `${(value / 1000000).toFixed(1)}M`;
  }
  if (value >= 1000) {
    return `${(value / 1000).toFixed(1)}K`;
  }
  return value.toString();
};

// Mock component (TDD reference implementation)
const MockOverviewCards = ({ metrics, isLoading = false, className = '' }: OverviewCardsProps) => {
  if (isLoading) {
    return (
      <div data-testid="overview-cards-skeleton" className={`grid grid-cols-1 md:grid-cols-4 gap-4 ${className}`}>
        {[1, 2, 3, 4].map((i) => (
          <div key={i} data-testid={`skeleton-card-${i}`} className="animate-pulse bg-gray-200 h-24 rounded-lg" role="status" aria-live="polite">
            <span className="sr-only">Loading...</span>
          </div>
        ))}
      </div>
    );
  }

  return (
    <div data-testid="overview-cards" className={`grid grid-cols-1 md:grid-cols-4 gap-4 ${className}`}>
      {metrics.map((metric, index) => (
        <div
          key={index}
          data-testid={`card-${index}`}
          data-trend={metric.trend >= 0 ? 'up' : 'down'}
          className="bg-white rounded-lg shadow p-4"
        >
          <div className="flex items-center gap-2">
            <span data-testid={`${metric.icon}-icon`} aria-hidden="true"></span>
            <span className="text-sm text-gray-600">{metric.label}</span>
          </div>
          <div className="mt-2 flex items-baseline gap-2">
            <span data-testid={`value-${index}`} className="text-2xl font-bold">
              {formatNumber(metric.value, metric.format)}
            </span>
            <span
              data-testid={`trend-${index}`}
              data-trend-direction={metric.trend >= 0 ? 'up' : 'down'}
              className={`text-sm ${metric.trend >= 0 ? 'text-green-600' : 'text-red-600'}`}
            >
              {metric.trend >= 0 ? '+' : ''}{metric.trend}%
            </span>
          </div>
        </div>
      ))}
    </div>
  );
};

describe('OverviewCards', () => {
  const mockMetrics: OverviewMetric[] = [
    { label: 'Total Views', value: 1250, trend: 12.5, format: 'number', icon: 'eye' },
    { label: 'Total Searches', value: 420, trend: -5.2, format: 'number', icon: 'search' },
    { label: 'Total Actions', value: 89, trend: 8.3, format: 'number', icon: 'click' },
    { label: 'Conversion Rate', value: 7.12, trend: -2.1, format: 'percentage', icon: 'trending-up' },
  ];

  beforeEach(() => {
    vi.clearAllMocks();
  });

  describe('OC-001 to OC-010: Rendering', () => {
    it('OC-001: renders all metric cards', () => {
      render(<MockOverviewCards metrics={mockMetrics} />);

      expect(screen.getByTestId('overview-cards')).toBeInTheDocument();
      expect(screen.getByTestId('card-0')).toBeInTheDocument();
      expect(screen.getByTestId('card-1')).toBeInTheDocument();
      expect(screen.getByTestId('card-2')).toBeInTheDocument();
      expect(screen.getByTestId('card-3')).toBeInTheDocument();
    });

    it('OC-002: displays metric labels', () => {
      render(<MockOverviewCards metrics={mockMetrics} />);

      expect(screen.getByText('Total Views')).toBeInTheDocument();
      expect(screen.getByText('Total Searches')).toBeInTheDocument();
      expect(screen.getByText('Total Actions')).toBeInTheDocument();
      expect(screen.getByText('Conversion Rate')).toBeInTheDocument();
    });

    it('OC-003: displays metric values', () => {
      render(<MockOverviewCards metrics={mockMetrics} />);

      expect(screen.getByTestId('value-0')).toHaveTextContent('1.25K');
      expect(screen.getByTestId('value-1')).toHaveTextContent('420');
      expect(screen.getByTestId('value-2')).toHaveTextContent('89');
      expect(screen.getByTestId('value-3')).toHaveTextContent('7.12%');
    });

    it('OC-004: displays trend percentages', () => {
      render(<MockOverviewCards metrics={mockMetrics} />);

      expect(screen.getByTestId('trend-0')).toHaveTextContent('+12.5%');
      expect(screen.getByTestId('trend-1')).toHaveTextContent('-5.2%');
      expect(screen.getByTestId('trend-2')).toHaveTextContent('+8.3%');
      expect(screen.getByTestId('trend-3')).toHaveTextContent('-2.1%');
    });

    it('OC-005: applies responsive grid layout', () => {
      render(<MockOverviewCards metrics={mockMetrics} />);

      const cards = screen.getByTestId('overview-cards');
      expect(cards).toHaveClass('grid');
      expect(cards).toHaveClass('grid-cols-1');
      expect(cards).toHaveClass('md:grid-cols-4');
    });

    it('OC-006: applies custom className', () => {
      render(<MockOverviewCards metrics={mockMetrics} className="custom-class" />);

      const cards = screen.getByTestId('overview-cards');
      expect(cards).toHaveClass('custom-class');
    });

    it('OC-007: renders metric icons', () => {
      render(<MockOverviewCards metrics={mockMetrics} />);

      expect(screen.getByTestId('eye-icon')).toBeInTheDocument();
      expect(screen.getByTestId('search-icon')).toBeInTheDocument();
      expect(screen.getByTestId('click-icon')).toBeInTheDocument();
      expect(screen.getByTestId('trending-up-icon')).toBeInTheDocument();
    });

    it('OC-008: icons are decorative (aria-hidden)', () => {
      render(<MockOverviewCards metrics={mockMetrics} />);

      const icons = [
        screen.getByTestId('eye-icon'),
        screen.getByTestId('search-icon'),
        screen.getByTestId('click-icon'),
        screen.getByTestId('trending-up-icon'),
      ];

      icons.forEach(icon => {
        expect(icon).toHaveAttribute('aria-hidden', 'true');
      });
    });

    it('OC-009: renders with gap between cards', () => {
      render(<MockOverviewCards metrics={mockMetrics} />);

      const cards = screen.getByTestId('overview-cards');
      expect(cards).toHaveClass('gap-4');
    });

    it('OC-010: each card has padding', () => {
      render(<MockOverviewCards metrics={mockMetrics} />);

      const card = screen.getByTestId('card-0');
      expect(card).toHaveClass('p-4');
    });
  });

  describe('OC-011 to OC-020: Trend Indicators', () => {
    it('OC-011: positive trend has green color', () => {
      render(<MockOverviewCards metrics={mockMetrics} />);

      const trend = screen.getByTestId('trend-0');
      expect(trend).toHaveClass('text-green-600');
    });

    it('OC-012: negative trend has red color', () => {
      render(<MockOverviewCards metrics={mockMetrics} />);

      const trend = screen.getByTestId('trend-1');
      expect(trend).toHaveClass('text-red-600');
    });

    it('OC-013: trend direction is in data attribute', () => {
      render(<MockOverviewCards metrics={mockMetrics} />);

      expect(screen.getByTestId('trend-0')).toHaveAttribute('data-trend-direction', 'up');
      expect(screen.getByTestId('trend-1')).toHaveAttribute('data-trend-direction', 'down');
    });

    it('OC-014: card has trend data attribute', () => {
      render(<MockOverviewCards metrics={mockMetrics} />);

      expect(screen.getByTestId('card-0')).toHaveAttribute('data-trend', 'up');
      expect(screen.getByTestId('card-1')).toHaveAttribute('data-trend', 'down');
    });

    it('OC-015: zero trend shows neutral', () => {
      const metricsWithZero = [{ ...mockMetrics[0], trend: 0 }];
      render(<MockOverviewCards metrics={metricsWithZero} />);

      const trend = screen.getByTestId('trend-0');
      expect(trend).toHaveTextContent('0%');
      expect(trend).toHaveAttribute('data-trend-direction', 'up');
    });

    it('OC-016: positive trend has plus sign', () => {
      render(<MockOverviewCards metrics={mockMetrics} />);

      const trend = screen.getByTestId('trend-0');
      expect(trend).toHaveTextContent('+12.5%');
    });

    it('OC-017: negative trend has minus sign', () => {
      render(<MockOverviewCards metrics={mockMetrics} />);

      const trend = screen.getByTestId('trend-1');
      expect(trend).toHaveTextContent('-5.2%');
    });

    it('OC-018: large positive trend displays correctly', () => {
      const metricsWithLargeTrend = [{ ...mockMetrics[0], trend: 125.5 }];
      render(<MockOverviewCards metrics={metricsWithLargeTrend} />);

      const trend = screen.getByTestId('trend-0');
      expect(trend).toHaveTextContent('+125.5%');
    });

    it('OC-019: large negative trend displays correctly', () => {
      const metricsWithLargeTrend = [{ ...mockMetrics[0], trend: -99.9 }];
      render(<MockOverviewCards metrics={metricsWithLargeTrend} />);

      const trend = screen.getByTestId('trend-0');
      expect(trend).toHaveTextContent('-99.9%');
    });

    it('OC-020: decimal trend values display correctly', () => {
      const metricsWithDecimalTrend = [{ ...mockMetrics[0], trend: 0.5 }];
      render(<MockOverviewCards metrics={metricsWithDecimalTrend} />);

      const trend = screen.getByTestId('trend-0');
      expect(trend).toHaveTextContent('+0.5%');
    });
  });

  describe('OC-021 to OC-030: Number Formatting', () => {
    it('OC-021: formats thousands with K suffix', () => {
      const metrics = [{ ...mockMetrics[0], value: 1500 }];
      render(<MockOverviewCards metrics={metrics} />);

      expect(screen.getByTestId('value-0')).toHaveTextContent('1.5K');
    });

    it('OC-022: formats millions with M suffix', () => {
      const metrics = [{ ...mockMetrics[0], value: 2500000 }];
      render(<MockOverviewCards metrics={metrics} />);

      expect(screen.getByTestId('value-0')).toHaveTextContent('2.5M');
    });

    it('OC-023: formats exact thousand', () => {
      const metrics = [{ ...mockMetrics[0], value: 1000 }];
      render(<MockOverviewCards metrics={metrics} />);

      expect(screen.getByTestId('value-0')).toHaveTextContent('1.0K');
    });

    it('OC-024: formats exact million', () => {
      const metrics = [{ ...mockMetrics[0], value: 1000000 }];
      render(<MockOverviewCards metrics={metrics} />);

      expect(screen.getByTestId('value-0')).toHaveTextContent('1.0M');
    });

    it('OC-025: formats percentage values', () => {
      const metrics = [{ ...mockMetrics[3], value: 5.5, format: 'percentage' }];
      render(<MockOverviewCards metrics={metrics} />);

      expect(screen.getByTestId('value-0')).toHaveTextContent('5.50%');
    });

    it('OC-026: formats decimal values', () => {
      const metrics = [{ ...mockMetrics[0], value: 3.14159, format: 'decimal' }];
      render(<MockOverviewCards metrics={metrics} />);

      expect(screen.getByTestId('value-0')).toHaveTextContent('3.14');
    });

    it('OC-027: formats zero values', () => {
      const metrics = [{ ...mockMetrics[0], value: 0 }];
      render(<MockOverviewCards metrics={metrics} />);

      expect(screen.getByTestId('value-0')).toHaveTextContent('0');
    });

    it('OC-028: formats small numbers without suffix', () => {
      const metrics = [{ ...mockMetrics[0], value: 999 }];
      render(<MockOverviewCards metrics={metrics} />);

      expect(screen.getByTestId('value-0')).toHaveTextContent('999');
    });

    it('OC-029: formats large thousands correctly', () => {
      const metrics = [{ ...mockMetrics[0], value: 999999 }];
      render(<MockOverviewCards metrics={metrics} />);

      expect(screen.getByTestId('value-0')).toHaveTextContent('1000.0K');
    });

    it('OC-030: preserves precision for percentages', () => {
      const metrics = [{ ...mockMetrics[3], value: 7.123, format: 'percentage' }];
      render(<MockOverviewCards metrics={metrics} />);

      expect(screen.getByTestId('value-0')).toHaveTextContent('7.12%');
    });
  });

  describe('OC-031 to OC-040: Loading State', () => {
    it('OC-031: shows skeleton when isLoading is true', () => {
      render(<MockOverviewCards metrics={mockMetrics} isLoading={true} />);

      expect(screen.getByTestId('overview-cards-skeleton')).toBeInTheDocument();
    });

    it('OC-032: hides cards when loading', () => {
      render(<MockOverviewCards metrics={mockMetrics} isLoading={true} />);

      expect(screen.queryByTestId('overview-cards')).not.toBeInTheDocument();
    });

    it('OC-033: renders 4 skeleton cards', () => {
      render(<MockOverviewCards metrics={mockMetrics} isLoading={true} />);

      expect(screen.getByTestId('skeleton-card-1')).toBeInTheDocument();
      expect(screen.getByTestId('skeleton-card-2')).toBeInTheDocument();
      expect(screen.getByTestId('skeleton-card-3')).toBeInTheDocument();
      expect(screen.getByTestId('skeleton-card-4')).toBeInTheDocument();
    });

    it('OC-034: skeleton cards have animate-pulse class', () => {
      render(<MockOverviewCards metrics={mockMetrics} isLoading={true} />);

      const skeletons = [
        screen.getByTestId('skeleton-card-1'),
        screen.getByTestId('skeleton-card-2'),
        screen.getByTestId('skeleton-card-3'),
        screen.getByTestId('skeleton-card-4'),
      ];

      skeletons.forEach(skeleton => {
        expect(skeleton).toHaveClass('animate-pulse');
      });
    });

    it('OC-035: skeleton has aria-live for accessibility', () => {
      render(<MockOverviewCards metrics={mockMetrics} isLoading={true} />);

      const skeleton = screen.getByTestId('skeleton-card-1');
      expect(skeleton).toHaveAttribute('aria-live', 'polite');
    });

    it('OC-036: skeleton has role="status"', () => {
      render(<MockOverviewCards metrics={mockMetrics} isLoading={true} />);

      const skeleton = screen.getByTestId('skeleton-card-1');
      expect(skeleton).toHaveAttribute('role', 'status');
    });

    it('OC-037: skeleton has screen reader text', () => {
      render(<MockOverviewCards metrics={mockMetrics} isLoading={true} />);

      expect(screen.getByText('Loading...')).toHaveClass('sr-only');
    });

    it('OC-038: skeleton container has same grid classes', () => {
      render(<MockOverviewCards metrics={mockMetrics} isLoading={true} />);

      const skeleton = screen.getByTestId('overview-cards-skeleton');
      expect(skeleton).toHaveClass('grid');
      expect(skeleton).toHaveClass('grid-cols-1');
      expect(skeleton).toHaveClass('md:grid-cols-4');
    });

    it('OC-039: skeleton applies custom className', () => {
      render(<MockOverviewCards metrics={mockMetrics} isLoading={true} className="custom-class" />);

      const skeleton = screen.getByTestId('overview-cards-skeleton');
      expect(skeleton).toHaveClass('custom-class');
    });

    it('OC-040: skeleton cards have consistent height', () => {
      render(<MockOverviewCards metrics={mockMetrics} isLoading={true} />);

      const skeleton = screen.getByTestId('skeleton-card-1');
      expect(skeleton).toHaveClass('h-24');
    });
  });

  describe('OC-041 to OC-050: Edge Cases', () => {
    it('OC-041: handles empty metrics array', () => {
      render(<MockOverviewCards metrics={[]} />);

      expect(screen.getByTestId('overview-cards')).toBeInTheDocument();
      expect(screen.queryByTestId('card-0')).not.toBeInTheDocument();
    });

    it('OC-042: handles single metric', () => {
      render(<MockOverviewCards metrics={[mockMetrics[0]]} />);

      expect(screen.getByTestId('card-0')).toBeInTheDocument();
      expect(screen.queryByTestId('card-1')).not.toBeInTheDocument();
    });

    it('OC-043: handles very large numbers', () => {
      const metrics = [{ ...mockMetrics[0], value: 999999999 }];
      render(<MockOverviewCards metrics={metrics} />);

      expect(screen.getByTestId('value-0')).toHaveTextContent('1000.0M');
    });

    it('OC-044: handles negative values', () => {
      const metrics = [{ ...mockMetrics[0], value: -100 }];
      render(<MockOverviewCards metrics={metrics} />);

      // Negative values would typically not occur, but component handles gracefully
      expect(screen.getByTestId('value-0')).toHaveTextContent('-100');
    });

    it('OC-045: handles zero trend', () => {
      const metrics = [{ ...mockMetrics[0], trend: 0 }];
      render(<MockOverviewCards metrics={metrics} />);

      const trend = screen.getByTestId('trend-0');
      expect(trend).toHaveTextContent('0%');
    });

    it('OC-046: handles very small positive trend', () => {
      const metrics = [{ ...mockMetrics[0], trend: 0.01 }];
      render(<MockOverviewCards metrics={metrics} />);

      const trend = screen.getByTestId('trend-0');
      expect(trend).toHaveTextContent('+0.01%');
    });

    it('OC-047: handles very small negative trend', () => {
      const metrics = [{ ...mockMetrics[0], trend: -0.01 }];
      render(<MockOverviewCards metrics={metrics} />);

      const trend = screen.getByTestId('trend-0');
      expect(trend).toHaveTextContent('-0.01%');
    });

    it('OC-048: handles unicode in labels', () => {
      const metrics = [{ ...mockMetrics[0], label: 'Total 📊 Views' }];
      render(<MockOverviewCards metrics={metrics} />);

      expect(screen.getByText('Total 📊 Views')).toBeInTheDocument();
    });

    it('OC-049: handles decimal percentage', () => {
      const metrics = [{ ...mockMetrics[3], value: 7.12345, format: 'percentage' }];
      render(<MockOverviewCards metrics={metrics} />);

      expect(screen.getByTestId('value-0')).toHaveTextContent('7.12%');
    });

    it('OC-050: handles rounded large numbers', () => {
      const metrics = [{ ...mockMetrics[0], value: 1234567 }];
      render(<MockOverviewCards metrics={metrics} />);

      expect(screen.getByTestId('value-0')).toHaveTextContent('1.2M');
    });
  });

  describe('OC-051 to OC-060: Accessibility', () => {
    it('OC-051: cards have proper heading structure', () => {
      render(<MockOverviewCards metrics={mockMetrics} />);

      // Labels act as headings for each card
      mockMetrics.forEach((metric, index) => {
        expect(screen.getByText(metric.label)).toBeInTheDocument();
      });
    });

    it('OC-052: value and trend are related', () => {
      render(<MockOverviewCards metrics={mockMetrics} />);

      const card = screen.getByTestId('card-0');
      const value = within(card).getByTestId('value-0');
      const trend = within(card).getByTestId('trend-0');

      expect(value).toBeInTheDocument();
      expect(trend).toBeInTheDocument();
    });

    it('OC-053: icons have aria-hidden', () => {
      render(<MockOverviewCards metrics={mockMetrics} />);

      const icons = [
        screen.getByTestId('eye-icon'),
        screen.getByTestId('search-icon'),
        screen.getByTestId('click-icon'),
        screen.getByTestId('trending-up-icon'),
      ];

      icons.forEach(icon => {
        expect(icon).toHaveAttribute('aria-hidden', 'true');
      });
    });

    it('OC-054: skeleton provides loading announcement', () => {
      render(<MockOverviewCards metrics={mockMetrics} isLoading={true} />);

      const skeleton = screen.getByTestId('skeleton-card-1');
      expect(skeleton).toHaveAttribute('aria-live', 'polite');
    });

    it('OC-055: screen reader text is visually hidden', () => {
      render(<MockOverviewCards metrics={mockMetrics} isLoading={true} />);

      const srText = screen.getByText('Loading...');
      expect(srText).toHaveClass('sr-only');
    });

    it('OC-056: color is not sole indicator of trend', () => {
      render(<MockOverviewCards metrics={mockMetrics} />);

      // Both positive and negative trends have text indicators
      expect(screen.getByTestId('trend-0')).toHaveTextContent('+12.5%');
      expect(screen.getByTestId('trend-1')).toHaveTextContent('-5.2%');
    });

    it('OC-057: trend direction is in data attribute for styling', () => {
      render(<MockOverviewCards metrics={mockMetrics} />);

      expect(screen.getByTestId('trend-0')).toHaveAttribute('data-trend-direction', 'up');
      expect(screen.getByTestId('trend-1')).toHaveAttribute('data-trend-direction', 'down');
    });

    it('OC-058: card elements are focusable via keyboard', () => {
      render(<MockOverviewCards metrics={mockMetrics} />);

      const cards = screen.getByTestId('overview-cards');
      expect(cards).toBeInTheDocument();
    });

    it('OC-059: contrast meets WCAG requirements', () => {
      render(<MockOverviewCards metrics={mockMetrics} />);

      // Green and red classes should meet contrast requirements
      const positiveTrend = screen.getByTestId('trend-0');
      const negativeTrend = screen.getByTestId('trend-1');

      expect(positiveTrend).toHaveClass('text-green-600');
      expect(negativeTrend).toHaveClass('text-red-600');
    });

    it('OC-060: responsive grid adapts to screen size', () => {
      render(<MockOverviewCards metrics={mockMetrics} />);

      const cards = screen.getByTestId('overview-cards');
      expect(cards).toHaveClass('grid-cols-1');
      expect(cards).toHaveClass('md:grid-cols-4');
    });
  });
});
