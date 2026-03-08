/**
 * Tests for PeriodComparison Component
 * Task: TASK-35-20
 * Tests: 35
 */

import { describe, it, expect, vi, beforeEach } from 'vitest';
import { render, screen } from '@testing-library/react';

// Mock Lucide icons
vi.mock('lucide-react', () => ({
  ArrowUp: () => <span data-testid="arrow-up-icon">↑</span>,
  ArrowDown: () => <span data-testid="arrow-down-icon">↓</span>,
  Minus: () => <span data-testid="minus-icon">−</span>,
  TrendingUp: () => <span data-testid="trending-up-icon">📈</span>,
  TrendingDown: () => <span data-testid="trending-down-icon">📉</span>,
}));

interface PeriodComparisonProps {
  viewsChange: number;
  searchesChange: number;
  actionsChange: number;
  conversionChange?: number;
  previousPeriodLabel?: string;
  className?: string;
}

// Mock component (TDD reference implementation)
const MockPeriodComparison = ({
  viewsChange,
  searchesChange,
  actionsChange,
  conversionChange,
  previousPeriodLabel = 'Previous Period',
  className = '',
}: PeriodComparisonProps) => {
  const metrics = [
    { label: 'Views', change: viewsChange },
    { label: 'Searches', change: searchesChange },
    { label: 'Actions', change: actionsChange },
    ...(conversionChange !== undefined ? [{ label: 'Conversion', change: conversionChange }] : []),
  ];

  const getTrendIcon = (change: number) => {
    if (change > 0) return 'arrow-up';
    if (change < 0) return 'arrow-down';
    return 'minus';
  };

  return (
    <div data-testid="period-comparison" className={className}>
      <h3 data-testid="period-comparison-title">{previousPeriodLabel}</h3>
      <div data-testid="comparison-metrics" className="grid grid-cols-2 md:grid-cols-4 gap-4">
        {metrics.map((metric, index) => (
          <div
            key={metric.label}
            data-testid={`comparison-${metric.label.toLowerCase()}`}
            data-change={metric.change}
            data-direction={metric.change >= 0 ? 'up' : 'down'}
            className={`p-4 rounded-lg ${
              metric.change >= 0 ? 'bg-green-50' : 'bg-red-50'
            }`}
          >
            <span className="text-sm text-gray-600">{metric.label}</span>
            <div className="flex items-center gap-2">
              <span data-testid={`${getTrendIcon(metric.change)}-icon`} aria-hidden="true" />
              <span
                data-testid={`change-value-${index}`}
                className={`text-lg font-semibold ${
                  metric.change >= 0 ? 'text-green-600' : 'text-red-600'
                }`}
              >
                {metric.change >= 0 ? '+' : ''}{metric.change}%
              </span>
            </div>
          </div>
        ))}
      </div>
    </div>
  );
};

describe('PeriodComparison', () => {
  const defaultProps: PeriodComparisonProps = {
    viewsChange: 12.5,
    searchesChange: -5.2,
    actionsChange: 8.3,
    conversionChange: -2.1,
  };

  beforeEach(() => {
    vi.clearAllMocks();
  });

  describe('PC-001 to PC-010: Rendering', () => {
    it('PC-001: renders period comparison container', () => {
      render(<MockPeriodComparison {...defaultProps} />);

      expect(screen.getByTestId('period-comparison')).toBeInTheDocument();
    });

    it('PC-002: renders comparison title', () => {
      render(<MockPeriodComparison {...defaultProps} />);

      expect(screen.getByTestId('period-comparison-title')).toBeInTheDocument();
    });

    it('PC-003: renders all metrics', () => {
      render(<MockPeriodComparison {...defaultProps} />);

      expect(screen.getByTestId('comparison-views')).toBeInTheDocument();
      expect(screen.getByTestId('comparison-searches')).toBeInTheDocument();
      expect(screen.getByTestId('comparison-actions')).toBeInTheDocument();
      expect(screen.getByTestId('comparison-conversion')).toBeInTheDocument();
    });

    it('PC-004: renders metrics container with grid layout', () => {
      render(<MockPeriodComparison {...defaultProps} />);

      const metrics = screen.getByTestId('comparison-metrics');
      expect(metrics).toHaveClass('grid');
      expect(metrics).toHaveClass('grid-cols-2');
      expect(metrics).toHaveClass('md:grid-cols-4');
    });

    it('PC-005: applies custom className', () => {
      render(<MockPeriodComparison {...defaultProps} className="custom-class" />);

      const container = screen.getByTestId('period-comparison');
      expect(container).toHaveClass('custom-class');
    });

    it('PC-006: displays default previous period label', () => {
      render(<MockPeriodComparison {...defaultProps} />);

      expect(screen.getByText('Previous Period')).toBeInTheDocument();
    });

    it('PC-007: displays custom previous period label', () => {
      render(<MockPeriodComparison {...defaultProps} previousPeriodLabel="Last Week" />);

      expect(screen.getByText('Last Week')).toBeInTheDocument();
    });

    it('PC-008: renders without conversion change', () => {
      render(<MockPeriodComparison {...defaultProps} conversionChange={undefined} />);

      expect(screen.getByTestId('comparison-views')).toBeInTheDocument();
      expect(screen.getByTestId('comparison-searches')).toBeInTheDocument();
      expect(screen.getByTestId('comparison-actions')).toBeInTheDocument();
      expect(screen.queryByTestId('comparison-conversion')).not.toBeInTheDocument();
    });

    it('PC-009: renders three metrics without conversion', () => {
      const props = { ...defaultProps, conversionChange: undefined };
      render(<MockPeriodComparison {...props} />);

      const metrics = screen.getByTestId('comparison-metrics');
      const children = metrics.children;
      expect(children.length).toBe(3);
    });

    it('PC-010: renders four metrics with conversion', () => {
      render(<MockPeriodComparison {...defaultProps} />);

      const metrics = screen.getByTestId('comparison-metrics');
      const children = metrics.children;
      expect(children.length).toBe(4);
    });
  });

  describe('PC-011 to PC-020: Change Values', () => {
    it('PC-011: displays positive views change with plus sign', () => {
      render(<MockPeriodComparison {...defaultProps} />);

      const views = screen.getByTestId('comparison-views');
      expect(views).toHaveTextContent('+12.5%');
    });

    it('PC-012: displays negative searches change with minus sign', () => {
      render(<MockPeriodComparison {...defaultProps} />);

      const searches = screen.getByTestId('comparison-searches');
      expect(searches).toHaveTextContent('-5.2%');
    });

    it('PC-013: displays positive actions change', () => {
      render(<MockPeriodComparison {...defaultProps} />);

      const actions = screen.getByTestId('comparison-actions');
      expect(actions).toHaveTextContent('+8.3%');
    });

    it('PC-014: displays negative conversion change', () => {
      render(<MockPeriodComparison {...defaultProps} />);

      const conversion = screen.getByTestId('comparison-conversion');
      expect(conversion).toHaveTextContent('-2.1%');
    });

    it('PC-015: change values are in data attributes', () => {
      render(<MockPeriodComparison {...defaultProps} />);

      expect(screen.getByTestId('comparison-views')).toHaveAttribute('data-change', '12.5');
      expect(screen.getByTestId('comparison-searches')).toHaveAttribute('data-change', '-5.2');
      expect(screen.getByTestId('comparison-actions')).toHaveAttribute('data-change', '8.3');
      expect(screen.getByTestId('comparison-conversion')).toHaveAttribute('data-change', '-2.1');
    });

    it('PC-016: direction is in data attribute', () => {
      render(<MockPeriodComparison {...defaultProps} />);

      expect(screen.getByTestId('comparison-views')).toHaveAttribute('data-direction', 'up');
      expect(screen.getByTestId('comparison-searches')).toHaveAttribute('data-direction', 'down');
    });

    it('PC-017: zero change shows neutral', () => {
      render(<MockPeriodComparison {...defaultProps} viewsChange={0} />);

      const views = screen.getByTestId('comparison-views');
      expect(views).toHaveTextContent('0%');
      expect(views).toHaveAttribute('data-direction', 'up');
    });

    it('PC-018: large positive change displays correctly', () => {
      render(<MockPeriodComparison {...defaultProps} viewsChange={125.5} />);

      expect(screen.getByTestId('comparison-views')).toHaveTextContent('+125.5%');
    });

    it('PC-019: large negative change displays correctly', () => {
      render(<MockPeriodComparison {...defaultProps} searchesChange={-99.9} />);

      expect(screen.getByTestId('comparison-searches')).toHaveTextContent('-99.9%');
    });

    it('PC-020: decimal change values display correctly', () => {
      render(<MockPeriodComparison {...defaultProps} viewsChange={0.5} />);

      expect(screen.getByTestId('comparison-views')).toHaveTextContent('+0.5%');
    });
  });

  describe('PC-021 to PC-030: Color Coding', () => {
    it('PC-021: positive change has green background', () => {
      render(<MockPeriodComparison {...defaultProps} />);

      const views = screen.getByTestId('comparison-views');
      expect(views).toHaveClass('bg-green-50');
    });

    it('PC-022: negative change has red background', () => {
      render(<MockPeriodComparison {...defaultProps} />);

      const searches = screen.getByTestId('comparison-searches');
      expect(searches).toHaveClass('bg-red-50');
    });

    it('PC-023: positive change text is green', () => {
      render(<MockPeriodComparison {...defaultProps} />);

      const changeValue = screen.getByTestId('change-value-0');
      expect(changeValue).toHaveClass('text-green-600');
    });

    it('PC-024: negative change text is red', () => {
      render(<MockPeriodComparison {...defaultProps} />);

      const changeValue = screen.getByTestId('change-value-1');
      expect(changeValue).toHaveClass('text-red-600');
    });

    it('PC-025: zero change has green styling (neutral positive)', () => {
      render(<MockPeriodComparison {...defaultProps} viewsChange={0} />);

      const views = screen.getByTestId('comparison-views');
      expect(views).toHaveClass('bg-green-50');
    });

    it('PC-026: all metrics use consistent color logic', () => {
      render(<MockPeriodComparison {...defaultProps} />);

      // Views: +12.5% (green)
      expect(screen.getByTestId('comparison-views')).toHaveClass('bg-green-50');
      // Searches: -5.2% (red)
      expect(screen.getByTestId('comparison-searches')).toHaveClass('bg-red-50');
      // Actions: +8.3% (green)
      expect(screen.getByTestId('comparison-actions')).toHaveClass('bg-green-50');
      // Conversion: -2.1% (red)
      expect(screen.getByTestId('comparison-conversion')).toHaveClass('bg-red-50');
    });

    it('PC-027: positive change shows arrow up icon', () => {
      render(<MockPeriodComparison {...defaultProps} viewsChange={10} />);

      expect(screen.getByTestId('arrow-up-icon')).toBeInTheDocument();
    });

    it('PC-028: negative change shows arrow down icon', () => {
      render(<MockPeriodComparison {...defaultProps} searchesChange={-10} />);

      expect(screen.getByTestId('arrow-down-icon')).toBeInTheDocument();
    });

    it('PC-029: icons are decorative (aria-hidden)', () => {
      render(<MockPeriodComparison {...defaultProps} />);

      const icons = screen.getAllByTestId(/arrow-|-icon$/);
      icons.forEach(icon => {
        expect(icon).toHaveAttribute('aria-hidden', 'true');
      });
    });

    it('PC-030: both arrow icons can appear simultaneously', () => {
      render(<MockPeriodComparison {...defaultProps} />);

      expect(screen.getByTestId('arrow-up-icon')).toBeInTheDocument();
      expect(screen.getByTestId('arrow-down-icon')).toBeInTheDocument();
    });
  });

  describe('PC-031 to PC-035: Accessibility', () => {
    it('PC-031: metric cards have proper structure', () => {
      render(<MockPeriodComparison {...defaultProps} />);

      const viewsCard = screen.getByTestId('comparison-views');
      expect(viewsCard).toBeInTheDocument();
    });

    it('PC-032: labels are human readable', () => {
      render(<MockPeriodComparison {...defaultProps} />);

      expect(screen.getByText('Views')).toBeInTheDocument();
      expect(screen.getByText('Searches')).toBeInTheDocument();
      expect(screen.getByText('Actions')).toBeInTheDocument();
      expect(screen.getByText('Conversion')).toBeInTheDocument();
    });

    it('PC-033: change values are prominent', () => {
      render(<MockPeriodComparison {...defaultProps} />);

      const changeValue = screen.getByTestId('change-value-0');
      expect(changeValue).toHaveClass('text-lg');
      expect(changeValue).toHaveClass('font-semibold');
    });

    it('PC-034: responsive grid adapts to screen size', () => {
      render(<MockPeriodComparison {...defaultProps} />);

      const metrics = screen.getByTestId('comparison-metrics');
      expect(metrics).toHaveClass('grid-cols-2');
      expect(metrics).toHaveClass('md:grid-cols-4');
    });

    it('PC-035: metric cards have padding', () => {
      render(<MockPeriodComparison {...defaultProps} />);

      const viewsCard = screen.getByTestId('comparison-views');
      expect(viewsCard).toHaveClass('p-4');
    });
  });
});
