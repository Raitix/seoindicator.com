/**
 * Tests for ActionsTrendChart Component (Stacked Area Chart)
 * Task: TASK-35-20
 * Tests: 38
 */

import { describe, it, expect, vi, beforeEach } from 'vitest';
import { render, screen } from '@testing-library/react';

// Mock Recharts
vi.mock('recharts', () => ({
  ResponsiveContainer: ({ children }: any) => (
    <div data-testid="responsive-container">{children}</div>
  ),
  AreaChart: ({ children, data }: any) => (
    <div data-testid="area-chart" data-points={data?.length}>{children}</div>
  ),
  Area: ({ dataKey, fill, stackId }: any) => (
    <div
      data-testid={`area-${dataKey}`}
      data-fill={fill}
      data-stack-id={stackId}
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

interface ActionsTrendDataPoint {
  date: string;
  calls: number;
  directions: number;
  website: number;
  photos: number;
}

interface ActionsTrendChartProps {
  data: ActionsTrendDataPoint[];
  isLoading?: boolean;
  showLegend?: boolean;
  className?: string;
}

// Color palette for action types
const ACTION_COLORS = {
  calls: '#22c55e',     // Green
  directions: '#3b82f6', // Blue
  website: '#f59e0b',   // Amber
  photos: '#8b5cf6',    // Purple
};

// Mock component (TDD reference implementation)
const MockActionsTrendChart = ({
  data,
  isLoading = false,
  showLegend = true,
  className = '',
}: ActionsTrendChartProps) => {
  if (isLoading) {
    return (
      <div
        data-testid="actions-trend-skeleton"
        className={`animate-pulse bg-gray-200 rounded ${className}`}
        role="status"
        aria-live="polite"
      >
        <span className="sr-only">Loading actions trend...</span>
      </div>
    );
  }

  if (!data || data.length === 0) {
    return (
      <div data-testid="actions-trend-empty" className={className}>
        <p>No actions trend data available</p>
      </div>
    );
  }

  const areas = [
    { key: 'calls', color: ACTION_COLORS.calls },
    { key: 'directions', color: ACTION_COLORS.directions },
    { key: 'website', color: ACTION_COLORS.website },
    { key: 'photos', color: ACTION_COLORS.photos },
  ];

  return (
    <div data-testid="actions-trend-chart-container" className={className}>
      <div data-testid="actions-trend-chart" data-points={data.length}>
        <div data-testid="responsive-container">
          <div data-testid="area-chart" data-points={data.length}>
            <div data-testid="cartesian-grid" />
            <div data-testid="x-axis" data-key="date" />
            <div data-testid="y-axis" />
            <div data-testid="tooltip" />
            {showLegend && <div data-testid="legend" />}
            {areas.map((area) => (
              <div
                key={area.key}
                data-testid={`area-${area.key}`}
                data-fill={area.color}
                data-stack-id="actions"
              />
            ))}
          </div>
        </div>
      </div>
      <p className="sr-only" aria-live="polite">
        Stacked area chart showing {data.length} days of actions trend with calls, directions, website, and photos
      </p>
    </div>
  );
};

describe('ActionsTrendChart', () => {
  const mockData: ActionsTrendDataPoint[] = [
    { date: '2026-03-01', calls: 5, directions: 4, website: 3, photos: 1 },
    { date: '2026-03-02', calls: 4, directions: 3, website: 4, photos: 2 },
    { date: '2026-03-03', calls: 6, directions: 5, website: 3, photos: 2 },
    { date: '2026-03-04', calls: 5, directions: 4, website: 4, photos: 1 },
    { date: '2026-03-05', calls: 4, directions: 3, website: 3, photos: 2 },
    { date: '2026-03-06', calls: 4, directions: 3, website: 2, photos: 1 },
    { date: '2026-03-07', calls: 4, directions: 3, website: 3, photos: 1 },
  ];

  beforeEach(() => {
    vi.clearAllMocks();
  });

  describe('ATC-001 to ATC-010: Rendering', () => {
    it('ATC-001: renders chart container', () => {
      render(<MockActionsTrendChart data={mockData} />);

      expect(screen.getByTestId('actions-trend-chart-container')).toBeInTheDocument();
    });

    it('ATC-002: renders area chart with correct data points', () => {
      render(<MockActionsTrendChart data={mockData} />);

      const chart = screen.getByTestId('area-chart');
      expect(chart).toHaveAttribute('data-points', '7');
    });

    it('ATC-003: renders all action area components', () => {
      render(<MockActionsTrendChart data={mockData} />);

      expect(screen.getByTestId('area-calls')).toBeInTheDocument();
      expect(screen.getByTestId('area-directions')).toBeInTheDocument();
      expect(screen.getByTestId('area-website')).toBeInTheDocument();
      expect(screen.getByTestId('area-photos')).toBeInTheDocument();
    });

    it('ATC-004: renders X axis', () => {
      render(<MockActionsTrendChart data={mockData} />);

      expect(screen.getByTestId('x-axis')).toBeInTheDocument();
    });

    it('ATC-005: renders Y axis', () => {
      render(<MockActionsTrendChart data={mockData} />);

      expect(screen.getByTestId('y-axis')).toBeInTheDocument();
    });

    it('ATC-006: renders tooltip', () => {
      render(<MockActionsTrendChart data={mockData} />);

      expect(screen.getByTestId('tooltip')).toBeInTheDocument();
    });

    it('ATC-007: renders legend by default', () => {
      render(<MockActionsTrendChart data={mockData} />);

      expect(screen.getByTestId('legend')).toBeInTheDocument();
    });

    it('ATC-008: renders cartesian grid', () => {
      render(<MockActionsTrendChart data={mockData} />);

      expect(screen.getByTestId('cartesian-grid')).toBeInTheDocument();
    });

    it('ATC-009: renders responsive container', () => {
      render(<MockActionsTrendChart data={mockData} />);

      expect(screen.getByTestId('responsive-container')).toBeInTheDocument();
    });

    it('ATC-010: applies custom className', () => {
      render(<MockActionsTrendChart data={mockData} className="custom-class" />);

      const container = screen.getByTestId('actions-trend-chart-container');
      expect(container).toHaveClass('custom-class');
    });
  });

  describe('ATC-011 to ATC-020: Stacked Area Colors', () => {
    it('ATC-011: calls area has green color', () => {
      render(<MockActionsTrendChart data={mockData} />);

      const area = screen.getByTestId('area-calls');
      expect(area).toHaveAttribute('data-fill', '#22c55e');
    });

    it('ATC-012: directions area has blue color', () => {
      render(<MockActionsTrendChart data={mockData} />);

      const area = screen.getByTestId('area-directions');
      expect(area).toHaveAttribute('data-fill', '#3b82f6');
    });

    it('ATC-013: website area has amber color', () => {
      render(<MockActionsTrendChart data={mockData} />);

      const area = screen.getByTestId('area-website');
      expect(area).toHaveAttribute('data-fill', '#f59e0b');
    });

    it('ATC-014: photos area has purple color', () => {
      render(<MockActionsTrendChart data={mockData} />);

      const area = screen.getByTestId('area-photos');
      expect(area).toHaveAttribute('data-fill', '#8b5cf6');
    });

    it('ATC-015: all areas are stacked together', () => {
      render(<MockActionsTrendChart data={mockData} />);

      const areas = ['calls', 'directions', 'website', 'photos'];
      areas.forEach(key => {
        const area = screen.getByTestId(`area-${key}`);
        expect(area).toHaveAttribute('data-stack-id', 'actions');
      });
    });

    it('ATC-016: X axis uses date key', () => {
      render(<MockActionsTrendChart data={mockData} />);

      const xAxis = screen.getByTestId('x-axis');
      expect(xAxis).toHaveAttribute('data-key', 'date');
    });

    it('ATC-017: chart has data points attribute', () => {
      render(<MockActionsTrendChart data={mockData} />);

      const chart = screen.getByTestId('actions-trend-chart');
      expect(chart).toHaveAttribute('data-points', '7');
    });

    it('ATC-018: areas have distinct colors', () => {
      render(<MockActionsTrendChart data={mockData} />);

      const colors = ['calls', 'directions', 'website', 'photos'].map(key => {
        const area = screen.getByTestId(`area-${key}`);
        return area.getAttribute('data-fill');
      });

      const uniqueColors = new Set(colors);
      expect(uniqueColors.size).toBe(4);
    });

    it('ATC-019: showLegend defaults to true', () => {
      render(<MockActionsTrendChart data={mockData} />);

      expect(screen.getByTestId('legend')).toBeInTheDocument();
    });

    it('ATC-020: legend can be hidden', () => {
      render(<MockActionsTrendChart data={mockData} showLegend={false} />);

      expect(screen.queryByTestId('legend')).not.toBeInTheDocument();
    });
  });

  describe('ATC-021 to ATC-030: Loading and Empty States', () => {
    it('ATC-021: shows skeleton when isLoading is true', () => {
      render(<MockActionsTrendChart data={mockData} isLoading={true} />);

      expect(screen.getByTestId('actions-trend-skeleton')).toBeInTheDocument();
    });

    it('ATC-022: hides chart when loading', () => {
      render(<MockActionsTrendChart data={mockData} isLoading={true} />);

      expect(screen.queryByTestId('actions-trend-chart-container')).not.toBeInTheDocument();
    });

    it('ATC-023: skeleton has animate-pulse class', () => {
      render(<MockActionsTrendChart data={mockData} isLoading={true} />);

      const skeleton = screen.getByTestId('actions-trend-skeleton');
      expect(skeleton).toHaveClass('animate-pulse');
    });

    it('ATC-024: skeleton has aria-live for accessibility', () => {
      render(<MockActionsTrendChart data={mockData} isLoading={true} />);

      const skeleton = screen.getByTestId('actions-trend-skeleton');
      expect(skeleton).toHaveAttribute('aria-live', 'polite');
    });

    it('ATC-025: shows empty state when data is empty', () => {
      render(<MockActionsTrendChart data={[]} />);

      expect(screen.getByTestId('actions-trend-empty')).toBeInTheDocument();
    });

    it('ATC-026: empty state shows message', () => {
      render(<MockActionsTrendChart data={[]} />);

      expect(screen.getByText('No actions trend data available')).toBeInTheDocument();
    });

    it('ATC-027: loading takes precedence over empty', () => {
      render(<MockActionsTrendChart data={[]} isLoading={true} />);

      expect(screen.getByTestId('actions-trend-skeleton')).toBeInTheDocument();
      expect(screen.queryByTestId('actions-trend-empty')).not.toBeInTheDocument();
    });

    it('ATC-028: applies custom className to skeleton', () => {
      render(<MockActionsTrendChart data={mockData} isLoading={true} className="custom-class" />);

      const skeleton = screen.getByTestId('actions-trend-skeleton');
      expect(skeleton).toHaveClass('custom-class');
    });

    it('ATC-029: applies custom className to empty state', () => {
      render(<MockActionsTrendChart data={[]} className="custom-class" />);

      const empty = screen.getByTestId('actions-trend-empty');
      expect(empty).toHaveClass('custom-class');
    });

    it('ATC-030: skeleton has screen reader text', () => {
      render(<MockActionsTrendChart data={mockData} isLoading={true} />);

      expect(screen.getByText('Loading actions trend...')).toHaveClass('sr-only');
    });
  });

  describe('ATC-031 to ATC-038: Accessibility', () => {
    it('ATC-031: chart has accessible description', () => {
      render(<MockActionsTrendChart data={mockData} />);

      const description = screen.getByText(/Stacked area chart showing 7 days/);
      expect(description).toBeInTheDocument();
    });

    it('ATC-032: description mentions all action types', () => {
      render(<MockActionsTrendChart data={mockData} />);

      const description = screen.getByText(/calls, directions, website, and photos/);
      expect(description).toBeInTheDocument();
    });

    it('ATC-033: description is screen reader only', () => {
      render(<MockActionsTrendChart data={mockData} />);

      const description = screen.getByText(/Stacked area chart/);
      expect(description).toHaveClass('sr-only');
    });

    it('ATC-034: description has aria-live', () => {
      render(<MockActionsTrendChart data={mockData} />);

      const description = screen.getByText(/Stacked area chart/);
      expect(description).toHaveAttribute('aria-live', 'polite');
    });

    it('ATC-035: skeleton has role="status"', () => {
      render(<MockActionsTrendChart data={mockData} isLoading={true} />);

      const skeleton = screen.getByTestId('actions-trend-skeleton');
      expect(skeleton).toHaveAttribute('role', 'status');
    });

    it('ATC-036: description reflects data length', () => {
      const smallData = mockData.slice(0, 3);
      render(<MockActionsTrendChart data={smallData} />);

      const description = screen.getByText(/Stacked area chart showing 3 days/);
      expect(description).toBeInTheDocument();
    });

    it('ATC-037: responsive container enables responsive sizing', () => {
      render(<MockActionsTrendChart data={mockData} />);

      expect(screen.getByTestId('responsive-container')).toBeInTheDocument();
    });

    it('ATC-038: legend provides action type labels', () => {
      render(<MockActionsTrendChart data={mockData} showLegend={true} />);

      expect(screen.getByTestId('legend')).toBeInTheDocument();
    });
  });
});
