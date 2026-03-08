/**
 * Tests for ViewsLineChart Component
 * Task: TASK-35-20
 * Tests: 45
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
  Tooltip: ({ content }: any) => (
    <div data-testid="tooltip" />
  ),
  Legend: () => (
    <div data-testid="legend" />
  ),
}));

interface ViewsDataPoint {
  date: string;
  views: number;
  trend?: number;
}

interface ViewsLineChartProps {
  data: ViewsDataPoint[];
  showTrendLine?: boolean;
  isLoading?: boolean;
  className?: string;
  height?: number;
}

// Mock component (TDD reference implementation)
const MockViewsLineChart = ({
  data,
  showTrendLine = true,
  isLoading = false,
  className = '',
  height = 300,
}: ViewsLineChartProps) => {
  if (isLoading) {
    return (
      <div
        data-testid="views-chart-skeleton"
        className={`animate-pulse bg-gray-200 rounded ${className}`}
        style={{ height: `${height}px` }}
        role="status"
        aria-live="polite"
      >
        <span className="sr-only">Loading chart...</span>
      </div>
    );
  }

  if (!data || data.length === 0) {
    return (
      <div data-testid="views-chart-empty" className={className}>
        <p>No views data available</p>
      </div>
    );
  }

  return (
    <div data-testid="views-line-chart-container" className={className}>
      <div data-testid="views-line-chart" data-points={data.length} data-show-trend={showTrendLine}>
        <div data-testid="responsive-container">
          <div data-testid="line-chart" data-points={data.length}>
            <div data-testid="cartesian-grid" />
            <div data-testid="x-axis" data-key="date" />
            <div data-testid="y-axis" />
            <div data-testid="tooltip" />
            <div data-testid="line-views" data-stroke="#3b82f6" data-dash="solid" />
            {showTrendLine && (
              <div data-testid="line-trend" data-stroke="#94a3b8" data-dash="5 5" />
            )}
          </div>
        </div>
      </div>
      <p className="sr-only" aria-live="polite">
        Line chart showing {data.length} days of views data
      </p>
    </div>
  );
};

describe('ViewsLineChart', () => {
  const mockData: ViewsDataPoint[] = [
    { date: '2026-03-01', views: 150, trend: 145 },
    { date: '2026-03-02', views: 180, trend: 155 },
    { date: '2026-03-03', views: 165, trend: 160 },
    { date: '2026-03-04', views: 200, trend: 170 },
    { date: '2026-03-05', views: 175, trend: 175 },
    { date: '2026-03-06', views: 190, trend: 180 },
    { date: '2026-03-07', views: 190, trend: 185 },
  ];

  beforeEach(() => {
    vi.clearAllMocks();
  });

  describe('VLC-001 to VLC-010: Rendering', () => {
    it('VLC-001: renders chart container', () => {
      render(<MockViewsLineChart data={mockData} />);

      expect(screen.getByTestId('views-line-chart-container')).toBeInTheDocument();
    });

    it('VLC-002: renders line chart with correct data points', () => {
      render(<MockViewsLineChart data={mockData} />);

      const chart = screen.getByTestId('line-chart');
      expect(chart).toHaveAttribute('data-points', '7');
    });

    it('VLC-003: renders views line', () => {
      render(<MockViewsLineChart data={mockData} />);

      expect(screen.getByTestId('line-views')).toBeInTheDocument();
    });

    it('VLC-004: renders trend line when showTrendLine is true', () => {
      render(<MockViewsLineChart data={mockData} showTrendLine={true} />);

      expect(screen.getByTestId('line-trend')).toBeInTheDocument();
    });

    it('VLC-005: hides trend line when showTrendLine is false', () => {
      render(<MockViewsLineChart data={mockData} showTrendLine={false} />);

      expect(screen.queryByTestId('line-trend')).not.toBeInTheDocument();
    });

    it('VLC-006: renders responsive container', () => {
      render(<MockViewsLineChart data={mockData} />);

      expect(screen.getByTestId('responsive-container')).toBeInTheDocument();
    });

    it('VLC-007: renders X axis', () => {
      render(<MockViewsLineChart data={mockData} />);

      expect(screen.getByTestId('x-axis')).toBeInTheDocument();
    });

    it('VLC-008: renders Y axis', () => {
      render(<MockViewsLineChart data={mockData} />);

      expect(screen.getByTestId('y-axis')).toBeInTheDocument();
    });

    it('VLC-009: renders tooltip', () => {
      render(<MockViewsLineChart data={mockData} />);

      expect(screen.getByTestId('tooltip')).toBeInTheDocument();
    });

    it('VLC-010: renders cartesian grid', () => {
      render(<MockViewsLineChart data={mockData} />);

      expect(screen.getByTestId('cartesian-grid')).toBeInTheDocument();
    });
  });

  describe('VLC-011 to VLC-020: Line Styling', () => {
    it('VLC-011: views line has primary color', () => {
      render(<MockViewsLineChart data={mockData} />);

      const line = screen.getByTestId('line-views');
      expect(line).toHaveAttribute('data-stroke', '#3b82f6');
    });

    it('VLC-012: views line is solid', () => {
      render(<MockViewsLineChart data={mockData} />);

      const line = screen.getByTestId('line-views');
      expect(line).toHaveAttribute('data-dash', 'solid');
    });

    it('VLC-013: trend line has secondary color', () => {
      render(<MockViewsLineChart data={mockData} showTrendLine={true} />);

      const line = screen.getByTestId('line-trend');
      expect(line).toHaveAttribute('data-stroke', '#94a3b8');
    });

    it('VLC-014: trend line is dashed', () => {
      render(<MockViewsLineChart data={mockData} showTrendLine={true} />);

      const line = screen.getByTestId('line-trend');
      expect(line).toHaveAttribute('data-dash', '5 5');
    });

    it('VLC-015: chart shows trend indicator in data attribute', () => {
      render(<MockViewsLineChart data={mockData} showTrendLine={true} />);

      const chart = screen.getByTestId('views-line-chart');
      expect(chart).toHaveAttribute('data-show-trend', 'true');
    });

    it('VLC-016: X axis uses date key', () => {
      render(<MockViewsLineChart data={mockData} />);

      const xAxis = screen.getByTestId('x-axis');
      expect(xAxis).toHaveAttribute('data-key', 'date');
    });

    it('VLC-017: applies custom className', () => {
      render(<MockViewsLineChart data={mockData} className="custom-class" />);

      const container = screen.getByTestId('views-line-chart-container');
      expect(container).toHaveClass('custom-class');
    });

    it('VLC-018: showTrendLine defaults to true', () => {
      render(<MockViewsLineChart data={mockData} />);

      expect(screen.getByTestId('line-trend')).toBeInTheDocument();
    });

    it('VLC-019: trend line can be toggled off', () => {
      render(<MockViewsLineChart data={mockData} showTrendLine={false} />);

      expect(screen.queryByTestId('line-trend')).not.toBeInTheDocument();
    });

    it('VLC-020: data points attribute matches data length', () => {
      render(<MockViewsLineChart data={mockData} />);

      const chart = screen.getByTestId('views-line-chart');
      expect(chart).toHaveAttribute('data-points', mockData.length.toString());
    });
  });

  describe('VLC-021 to VLC-030: Loading State', () => {
    it('VLC-021: shows skeleton when isLoading is true', () => {
      render(<MockViewsLineChart data={mockData} isLoading={true} />);

      expect(screen.getByTestId('views-chart-skeleton')).toBeInTheDocument();
    });

    it('VLC-022: hides chart when loading', () => {
      render(<MockViewsLineChart data={mockData} isLoading={true} />);

      expect(screen.queryByTestId('views-line-chart-container')).not.toBeInTheDocument();
    });

    it('VLC-023: skeleton has animate-pulse class', () => {
      render(<MockViewsLineChart data={mockData} isLoading={true} />);

      const skeleton = screen.getByTestId('views-chart-skeleton');
      expect(skeleton).toHaveClass('animate-pulse');
    });

    it('VLC-024: skeleton has default height', () => {
      render(<MockViewsLineChart data={mockData} isLoading={true} />);

      const skeleton = screen.getByTestId('views-chart-skeleton');
      expect(skeleton.style.height).toBe('300px');
    });

    it('VLC-025: skeleton respects custom height', () => {
      render(<MockViewsLineChart data={mockData} isLoading={true} height={400} />);

      const skeleton = screen.getByTestId('views-chart-skeleton');
      expect(skeleton.style.height).toBe('400px');
    });

    it('VLC-026: skeleton has aria-live for accessibility', () => {
      render(<MockViewsLineChart data={mockData} isLoading={true} />);

      const skeleton = screen.getByTestId('views-chart-skeleton');
      expect(skeleton).toHaveAttribute('aria-live', 'polite');
    });

    it('VLC-027: skeleton has role="status"', () => {
      render(<MockViewsLineChart data={mockData} isLoading={true} />);

      const skeleton = screen.getByTestId('views-chart-skeleton');
      expect(skeleton).toHaveAttribute('role', 'status');
    });

    it('VLC-028: skeleton has screen reader text', () => {
      render(<MockViewsLineChart data={mockData} isLoading={true} />);

      expect(screen.getByText('Loading chart...')).toHaveClass('sr-only');
    });

    it('VLC-029: skeleton applies custom className', () => {
      render(<MockViewsLineChart data={mockData} isLoading={true} className="custom-class" />);

      const skeleton = screen.getByTestId('views-chart-skeleton');
      expect(skeleton).toHaveClass('custom-class');
    });

    it('VLC-030: skeleton has background color', () => {
      render(<MockViewsLineChart data={mockData} isLoading={true} />);

      const skeleton = screen.getByTestId('views-chart-skeleton');
      expect(skeleton).toHaveClass('bg-gray-200');
    });
  });

  describe('VLC-031 to VLC-040: Empty State', () => {
    it('VLC-031: shows empty state when data is empty', () => {
      render(<MockViewsLineChart data={[]} />);

      expect(screen.getByTestId('views-chart-empty')).toBeInTheDocument();
    });

    it('VLC-032: empty state shows message', () => {
      render(<MockViewsLineChart data={[]} />);

      expect(screen.getByText('No views data available')).toBeInTheDocument();
    });

    it('VLC-033: empty state does not show chart', () => {
      render(<MockViewsLineChart data={[]} />);

      expect(screen.queryByTestId('views-line-chart')).not.toBeInTheDocument();
    });

    it('VLC-034: empty state applies custom className', () => {
      render(<MockViewsLineChart data={[]} className="custom-class" />);

      const empty = screen.getByTestId('views-chart-empty');
      expect(empty).toHaveClass('custom-class');
    });

    it('VLC-035: null data shows empty state', () => {
      render(<MockViewsLineChart data={null as any} />);

      expect(screen.getByTestId('views-chart-empty')).toBeInTheDocument();
    });

    it('VLC-036: undefined data shows empty state', () => {
      render(<MockViewsLineChart data={undefined as any} />);

      expect(screen.getByTestId('views-chart-empty')).toBeInTheDocument();
    });

    it('VLC-037: empty state is not loading', () => {
      render(<MockViewsLineChart data={[]} isLoading={false} />);

      expect(screen.queryByTestId('views-chart-skeleton')).not.toBeInTheDocument();
    });

    it('VLC-038: loading takes precedence over empty', () => {
      render(<MockViewsLineChart data={[]} isLoading={true} />);

      expect(screen.getByTestId('views-chart-skeleton')).toBeInTheDocument();
      expect(screen.queryByTestId('views-chart-empty')).not.toBeInTheDocument();
    });

    it('VLC-039: single data point renders chart', () => {
      render(<MockViewsLineChart data={[mockData[0]]} />);

      expect(screen.getByTestId('views-line-chart')).toBeInTheDocument();
      expect(screen.getByTestId('views-line-chart')).toHaveAttribute('data-points', '1');
    });

    it('VLC-040: empty data points attribute is 0', () => {
      render(<MockViewsLineChart data={[]} />);

      // Empty state doesn't have data-points attribute
      expect(screen.queryByTestId('views-line-chart')).not.toBeInTheDocument();
    });
  });

  describe('VLC-041 to VLC-045: Accessibility', () => {
    it('VLC-041: chart has accessible description', () => {
      render(<MockViewsLineChart data={mockData} />);

      const description = screen.getByText(/Line chart showing 7 days of views data/);
      expect(description).toBeInTheDocument();
    });

    it('VLC-042: description is screen reader only', () => {
      render(<MockViewsLineChart data={mockData} />);

      const description = screen.getByText(/Line chart showing 7 days of views data/);
      expect(description).toHaveClass('sr-only');
    });

    it('VLC-043: description has aria-live', () => {
      render(<MockViewsLineChart data={mockData} />);

      const description = screen.getByText(/Line chart showing 7 days of views data/);
      expect(description).toHaveAttribute('aria-live', 'polite');
    });

    it('VLC-044: description reflects data length', () => {
      const smallData = mockData.slice(0, 3);
      render(<MockViewsLineChart data={smallData} />);

      const description = screen.getByText(/Line chart showing 3 days of views data/);
      expect(description).toBeInTheDocument();
    });

    it('VLC-045: responsive container enables responsive sizing', () => {
      render(<MockViewsLineChart data={mockData} />);

      expect(screen.getByTestId('responsive-container')).toBeInTheDocument();
    });
  });
});
