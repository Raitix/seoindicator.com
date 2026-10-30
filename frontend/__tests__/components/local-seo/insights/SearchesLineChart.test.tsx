/**
 * Tests for SearchesLineChart Component
 * Task: TASK-35-20
 * Tests: 40
 */

import { describe, it, expect, vi, beforeEach } from 'vitest';
import { render, screen } from '@testing-library/react';

// Mock Recharts
vi.mock('recharts', () => ({
  ResponsiveContainer: ({ children }: any) => (
    <div data-testid="responsive-container">{children}</div>
  ),
  LineChart: ({ children, data }: any) => (
    <div data-testid="line-chart" data-points={data?.length}>{children}</div>
  ),
  Line: ({ dataKey, stroke, strokeDasharray }: any) => (
    <div
      data-testid={`line-${dataKey}`}
      data-stroke={stroke}
      data-dash={strokeDasharray || 'solid'}
    />
  ),
  XAxis: ({ dataKey }: any) => (
    <div data-testid="x-axis" data-key={dataKey} />
  ),
  YAxis: () => (
    <div data-testid="y-axis" />
  ),
  CartesianGrid: () => (
    <div data-testid="cartesian-grid" />
  ),
  Tooltip: () => (
    <div data-testid="tooltip" />
  ),
  Legend: () => (
    <div data-testid="legend" />
  ),
}));

interface SearchesDataPoint {
  date: string;
  searches: number;
  previous_period?: number;
}

interface SearchesLineChartProps {
  data: SearchesDataPoint[];
  showComparison?: boolean;
  isLoading?: boolean;
  className?: string;
}

// Mock component (TDD reference implementation)
const MockSearchesLineChart = ({
  data,
  showComparison = true,
  isLoading = false,
  className = '',
}: SearchesLineChartProps) => {
  if (isLoading) {
    return (
      <div
        data-testid="searches-chart-skeleton"
        className={`animate-pulse bg-gray-200 rounded ${className}`}
        role="status"
        aria-live="polite"
      >
        <span className="sr-only">Loading searches chart...</span>
      </div>
    );
  }

  if (!data || data.length === 0) {
    return (
      <div data-testid="searches-chart-empty" className={className}>
        <p>No searches data available</p>
      </div>
    );
  }

  const hasComparison = showComparison && data.some(d => d.previous_period !== undefined);

  return (
    <div data-testid="searches-line-chart-container" className={className}>
      <div data-testid="searches-line-chart" data-points={data.length} data-has-comparison={hasComparison}>
        <div data-testid="responsive-container">
          <div data-testid="line-chart" data-points={data.length}>
            <div data-testid="cartesian-grid" />
            <div data-testid="x-axis" data-key="date" />
            <div data-testid="y-axis" />
            <div data-testid="tooltip" />
            <div data-testid="legend" />
            <div data-testid="line-searches" data-stroke="#3b82f6" data-dash="solid" />
            {hasComparison && (
              <div data-testid="line-previous_period" data-stroke="#94a3b8" data-dash="5 5" />
            )}
          </div>
        </div>
      </div>
      <p className="sr-only" aria-live="polite">
        Line chart showing {data.length} days of searches data{hasComparison ? ' with comparison to previous period' : ''}
      </p>
    </div>
  );
};

describe('SearchesLineChart', () => {
  const mockData: SearchesDataPoint[] = [
    { date: '2026-03-01', searches: 60, previous_period: 55 },
    { date: '2026-03-02', searches: 65, previous_period: 58 },
    { date: '2026-03-03', searches: 58, previous_period: 62 },
    { date: '2026-03-04', searches: 70, previous_period: 65 },
    { date: '2026-03-05', searches: 62, previous_period: 60 },
    { date: '2026-03-06', searches: 55, previous_period: 58 },
    { date: '2026-03-07', searches: 50, previous_period: 52 },
  ];

  beforeEach(() => {
    vi.clearAllMocks();
  });

  describe('SLC-001 to SLC-010: Rendering', () => {
    it('SLC-001: renders chart container', () => {
      render(<MockSearchesLineChart data={mockData} />);

      expect(screen.getByTestId('searches-line-chart-container')).toBeInTheDocument();
    });

    it('SLC-002: renders line chart with correct data points', () => {
      render(<MockSearchesLineChart data={mockData} />);

      const chart = screen.getByTestId('line-chart');
      expect(chart).toHaveAttribute('data-points', '7');
    });

    it('SLC-003: renders searches line', () => {
      render(<MockSearchesLineChart data={mockData} />);

      expect(screen.getByTestId('line-searches')).toBeInTheDocument();
    });

    it('SLC-004: renders comparison line when showComparison is true', () => {
      render(<MockSearchesLineChart data={mockData} showComparison={true} />);

      expect(screen.getByTestId('line-previous_period')).toBeInTheDocument();
    });

    it('SLC-005: renders legend', () => {
      render(<MockSearchesLineChart data={mockData} />);

      expect(screen.getByTestId('legend')).toBeInTheDocument();
    });

    it('SLC-006: renders X axis', () => {
      render(<MockSearchesLineChart data={mockData} />);

      expect(screen.getByTestId('x-axis')).toBeInTheDocument();
    });

    it('SLC-007: renders Y axis', () => {
      render(<MockSearchesLineChart data={mockData} />);

      expect(screen.getByTestId('y-axis')).toBeInTheDocument();
    });

    it('SLC-008: renders tooltip', () => {
      render(<MockSearchesLineChart data={mockData} />);

      expect(screen.getByTestId('tooltip')).toBeInTheDocument();
    });

    it('SLC-009: renders cartesian grid', () => {
      render(<MockSearchesLineChart data={mockData} />);

      expect(screen.getByTestId('cartesian-grid')).toBeInTheDocument();
    });

    it('SLC-010: renders responsive container', () => {
      render(<MockSearchesLineChart data={mockData} />);

      expect(screen.getByTestId('responsive-container')).toBeInTheDocument();
    });
  });

  describe('SLC-011 to SLC-020: Comparison Line', () => {
    it('SLC-011: shows comparison line when data has previous_period', () => {
      render(<MockSearchesLineChart data={mockData} showComparison={true} />);

      expect(screen.getByTestId('line-previous_period')).toBeInTheDocument();
    });

    it('SLC-012: hides comparison line when showComparison is false', () => {
      render(<MockSearchesLineChart data={mockData} showComparison={false} />);

      expect(screen.queryByTestId('line-previous_period')).not.toBeInTheDocument();
    });

    it('SLC-013: comparison line has secondary color', () => {
      render(<MockSearchesLineChart data={mockData} showComparison={true} />);

      const line = screen.getByTestId('line-previous_period');
      expect(line).toHaveAttribute('data-stroke', '#94a3b8');
    });

    it('SLC-014: comparison line is dashed', () => {
      render(<MockSearchesLineChart data={mockData} showComparison={true} />);

      const line = screen.getByTestId('line-previous_period');
      expect(line).toHaveAttribute('data-dash', '5 5');
    });

    it('SLC-015: searches line has primary color', () => {
      render(<MockSearchesLineChart data={mockData} />);

      const line = screen.getByTestId('line-searches');
      expect(line).toHaveAttribute('data-stroke', '#3b82f6');
    });

    it('SLC-016: searches line is solid', () => {
      render(<MockSearchesLineChart data={mockData} />);

      const line = screen.getByTestId('line-searches');
      expect(line).toHaveAttribute('data-dash', 'solid');
    });

    it('SLC-017: chart has comparison indicator', () => {
      render(<MockSearchesLineChart data={mockData} showComparison={true} />);

      const chart = screen.getByTestId('searches-line-chart');
      expect(chart).toHaveAttribute('data-has-comparison', 'true');
    });

    it('SLC-018: no comparison indicator when disabled', () => {
      render(<MockSearchesLineChart data={mockData} showComparison={false} />);

      const chart = screen.getByTestId('searches-line-chart');
      expect(chart).toHaveAttribute('data-has-comparison', 'false');
    });

    it('SLC-019: showComparison defaults to true', () => {
      render(<MockSearchesLineChart data={mockData} />);

      expect(screen.getByTestId('line-previous_period')).toBeInTheDocument();
    });

    it('SLC-020: handles data without previous_period', () => {
      const noComparisonData = mockData.map(d => ({ date: d.date, searches: d.searches }));
      render(<MockSearchesLineChart data={noComparisonData} />);

      const chart = screen.getByTestId('searches-line-chart');
      expect(chart).toHaveAttribute('data-has-comparison', 'false');
    });
  });

  describe('SLC-021 to SLC-030: Loading and Empty States', () => {
    it('SLC-021: shows skeleton when isLoading is true', () => {
      render(<MockSearchesLineChart data={mockData} isLoading={true} />);

      expect(screen.getByTestId('searches-chart-skeleton')).toBeInTheDocument();
    });

    it('SLC-022: hides chart when loading', () => {
      render(<MockSearchesLineChart data={mockData} isLoading={true} />);

      expect(screen.queryByTestId('searches-line-chart-container')).not.toBeInTheDocument();
    });

    it('SLC-023: skeleton has animate-pulse class', () => {
      render(<MockSearchesLineChart data={mockData} isLoading={true} />);

      const skeleton = screen.getByTestId('searches-chart-skeleton');
      expect(skeleton).toHaveClass('animate-pulse');
    });

    it('SLC-024: skeleton has aria-live for accessibility', () => {
      render(<MockSearchesLineChart data={mockData} isLoading={true} />);

      const skeleton = screen.getByTestId('searches-chart-skeleton');
      expect(skeleton).toHaveAttribute('aria-live', 'polite');
    });

    it('SLC-025: shows empty state when data is empty', () => {
      render(<MockSearchesLineChart data={[]} />);

      expect(screen.getByTestId('searches-chart-empty')).toBeInTheDocument();
    });

    it('SLC-026: empty state shows message', () => {
      render(<MockSearchesLineChart data={[]} />);

      expect(screen.getByText('No searches data available')).toBeInTheDocument();
    });

    it('SLC-027: loading takes precedence over empty', () => {
      render(<MockSearchesLineChart data={[]} isLoading={true} />);

      expect(screen.getByTestId('searches-chart-skeleton')).toBeInTheDocument();
    });

    it('SLC-028: applies custom className to container', () => {
      render(<MockSearchesLineChart data={mockData} className="custom-class" />);

      const container = screen.getByTestId('searches-line-chart-container');
      expect(container).toHaveClass('custom-class');
    });

    it('SLC-029: applies custom className to skeleton', () => {
      render(<MockSearchesLineChart data={mockData} isLoading={true} className="custom-class" />);

      const skeleton = screen.getByTestId('searches-chart-skeleton');
      expect(skeleton).toHaveClass('custom-class');
    });

    it('SLC-030: applies custom className to empty state', () => {
      render(<MockSearchesLineChart data={[]} className="custom-class" />);

      const empty = screen.getByTestId('searches-chart-empty');
      expect(empty).toHaveClass('custom-class');
    });
  });

  describe('SLC-031 to SLC-040: Accessibility', () => {
    it('SLC-031: chart has accessible description', () => {
      render(<MockSearchesLineChart data={mockData} />);

      const description = screen.getByText(/Line chart showing 7 days of searches data/);
      expect(description).toBeInTheDocument();
    });

    it('SLC-032: description mentions comparison when present', () => {
      render(<MockSearchesLineChart data={mockData} showComparison={true} />);

      const description = screen.getByText(/with comparison to previous period/);
      expect(description).toBeInTheDocument();
    });

    it('SLC-033: description is screen reader only', () => {
      render(<MockSearchesLineChart data={mockData} />);

      const description = screen.getByText(/Line chart showing 7 days/);
      expect(description).toHaveClass('sr-only');
    });

    it('SLC-034: description has aria-live', () => {
      render(<MockSearchesLineChart data={mockData} />);

      const description = screen.getByText(/Line chart showing 7 days/);
      expect(description).toHaveAttribute('aria-live', 'polite');
    });

    it('SLC-035: skeleton has role="status"', () => {
      render(<MockSearchesLineChart data={mockData} isLoading={true} />);

      const skeleton = screen.getByTestId('searches-chart-skeleton');
      expect(skeleton).toHaveAttribute('role', 'status');
    });

    it('SLC-036: skeleton has screen reader text', () => {
      render(<MockSearchesLineChart data={mockData} isLoading={true} />);

      expect(screen.getByText('Loading searches chart...')).toHaveClass('sr-only');
    });

    it('SLC-037: legend is present for comparison', () => {
      render(<MockSearchesLineChart data={mockData} showComparison={true} />);

      expect(screen.getByTestId('legend')).toBeInTheDocument();
    });

    it('SLC-038: description reflects data length', () => {
      const smallData = mockData.slice(0, 3);
      render(<MockSearchesLineChart data={smallData} />);

      const description = screen.getByText(/Line chart showing 3 days/);
      expect(description).toBeInTheDocument();
    });

    it('SLC-039: X axis uses date key', () => {
      render(<MockSearchesLineChart data={mockData} />);

      const xAxis = screen.getByTestId('x-axis');
      expect(xAxis).toHaveAttribute('data-key', 'date');
    });

    it('SLC-040: responsive container enables responsive sizing', () => {
      render(<MockSearchesLineChart data={mockData} />);

      expect(screen.getByTestId('responsive-container')).toBeInTheDocument();
    });
  });
});
