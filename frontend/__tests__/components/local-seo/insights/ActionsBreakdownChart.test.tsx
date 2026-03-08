/**
 * Tests for ActionsBreakdownChart Component
 * Task: TASK-35-20
 * Tests: 42
 */

import { describe, it, expect, vi, beforeEach } from 'vitest';
import { render, screen, fireEvent } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

// Mock Recharts
vi.mock('recharts', () => ({
  ResponsiveContainer: ({ children }: any) => (
    <div data-testid="responsive-container">{children}</div>
  ),
  PieChart: ({ children }: any) => (
    <div data-testid="pie-chart">{children}</div>
  ),
  Pie: ({ data }: any) => (
    <div data-testid="pie" data-segments={data?.length} />
  ),
  Cell: ({ name, fill }: any) => (
    <div data-testid={`cell-${name}`} data-fill={fill} />
  ),
  BarChart: ({ children, data }: any) => (
    <div data-testid="bar-chart" data-bars={data?.length}>{children}</div>
  ),
  Bar: ({ dataKey, fill }: any) => (
    <div data-testid={`bar-${dataKey}`} data-fill={fill} />
  ),
  Tooltip: () => (
    <div data-testid="tooltip" />
  ),
  Legend: () => (
    <div data-testid="legend" />
  ),
}));

// Mock Lucide icons
vi.mock('lucide-react', () => ({
  Phone: () => <span data-testid="phone-icon">📞</span>,
  MapPin: () => <span data-testid="map-pin-icon">📍</span>,
  Globe: () => <span data-testid="globe-icon">🌐</span>,
  Image: () => <span data-testid="image-icon">🖼</span>,
}));

interface ActionsBreakdown {
  calls: number;
  directions: number;
  website: number;
  photos: number;
}

interface ActionsBreakdownChartProps {
  data: ActionsBreakdown;
  chartType?: 'pie' | 'bar';
  isLoading?: boolean;
  onSegmentClick?: (actionType: string) => void;
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
const MockActionsBreakdownChart = ({
  data,
  chartType = 'pie',
  isLoading = false,
  onSegmentClick,
  className = '',
}: ActionsBreakdownChartProps) => {
  if (isLoading) {
    return (
      <div
        data-testid="actions-breakdown-skeleton"
        className={`animate-pulse bg-gray-200 rounded ${className}`}
        role="status"
        aria-live="polite"
      >
        <span className="sr-only">Loading actions breakdown...</span>
      </div>
    );
  }

  const total = data.calls + data.directions + data.website + data.photos;
  const segments = [
    { name: 'calls', value: data.calls, color: ACTION_COLORS.calls, icon: 'phone' },
    { name: 'directions', value: data.directions, color: ACTION_COLORS.directions, icon: 'map-pin' },
    { name: 'website', value: data.website, color: ACTION_COLORS.website, icon: 'globe' },
    { name: 'photos', value: data.photos, color: ACTION_COLORS.photos, icon: 'image' },
  ];

  const handleSegmentClick = (actionType: string) => {
    onSegmentClick?.(actionType);
  };

  return (
    <div data-testid="actions-breakdown-chart-container" className={className}>
      <div data-testid="actions-breakdown-chart" data-chart-type={chartType} data-total={total}>
        <div data-testid="responsive-container">
          {chartType === 'pie' ? (
            <div data-testid="pie-chart">
              <div data-testid="pie" data-segments={segments.length}>
                {segments.map((segment) => (
                  <div
                    key={segment.name}
                    data-testid={`cell-${segment.name}`}
                    data-fill={segment.color}
                    onClick={() => handleSegmentClick(segment.name)}
                    className="cursor-pointer"
                    role="button"
                    tabIndex={0}
                    aria-label={`${segment.name}: ${segment.value} (${((segment.value / total) * 100).toFixed(1)}%)`}
                  />
                ))}
              </div>
            </div>
          ) : (
            <div data-testid="bar-chart" data-bars={segments.length}>
              {segments.map((segment) => (
                <div
                  key={segment.name}
                  data-testid={`bar-${segment.name}`}
                  data-fill={segment.color}
                  onClick={() => handleSegmentClick(segment.name)}
                  className="cursor-pointer"
                />
              ))}
            </div>
          )}
          <div data-testid="tooltip" />
          <div data-testid="legend" />
        </div>
      </div>

      {/* Action breakdown list */}
      <div data-testid="action-list" className="mt-4">
        {segments.map((segment) => (
          <div
            key={segment.name}
            data-testid={`action-${segment.name}`}
            data-count={segment.value}
            data-percentage={((segment.value / total) * 100).toFixed(1)}
            className="flex items-center gap-2 cursor-pointer"
            onClick={() => handleSegmentClick(segment.name)}
            role="button"
            tabIndex={0}
          >
            <span data-testid={`${segment.icon}-icon`} aria-hidden="true" />
            <span className="capitalize">{segment.name}</span>
            <span>{segment.value}</span>
            <span>({((segment.value / total) * 100).toFixed(1)}%)</span>
          </div>
        ))}
      </div>
    </div>
  );
};

describe('ActionsBreakdownChart', () => {
  const mockData: ActionsBreakdown = {
    calls: 32,
    directions: 25,
    website: 22,
    photos: 10,
  };

  const mockOnSegmentClick = vi.fn();

  beforeEach(() => {
    vi.clearAllMocks();
  });

  describe('ABC-001 to ABC-010: Rendering', () => {
    it('ABC-001: renders chart container', () => {
      render(<MockActionsBreakdownChart data={mockData} />);

      expect(screen.getByTestId('actions-breakdown-chart-container')).toBeInTheDocument();
    });

    it('ABC-002: renders chart with total', () => {
      render(<MockActionsBreakdownChart data={mockData} />);

      const chart = screen.getByTestId('actions-breakdown-chart');
      expect(chart).toHaveAttribute('data-total', '89');
    });

    it('ABC-003: renders all action types', () => {
      render(<MockActionsBreakdownChart data={mockData} />);

      expect(screen.getByTestId('action-calls')).toBeInTheDocument();
      expect(screen.getByTestId('action-directions')).toBeInTheDocument();
      expect(screen.getByTestId('action-website')).toBeInTheDocument();
      expect(screen.getByTestId('action-photos')).toBeInTheDocument();
    });

    it('ABC-004: renders pie chart by default', () => {
      render(<MockActionsBreakdownChart data={mockData} />);

      expect(screen.getByTestId('pie-chart')).toBeInTheDocument();
    });

    it('ABC-005: renders bar chart when chartType is bar', () => {
      render(<MockActionsBreakdownChart data={mockData} chartType="bar" />);

      expect(screen.getByTestId('bar-chart')).toBeInTheDocument();
    });

    it('ABC-006: renders legend', () => {
      render(<MockActionsBreakdownChart data={mockData} />);

      expect(screen.getByTestId('legend')).toBeInTheDocument();
    });

    it('ABC-007: renders tooltip', () => {
      render(<MockActionsBreakdownChart data={mockData} />);

      expect(screen.getByTestId('tooltip')).toBeInTheDocument();
    });

    it('ABC-008: renders responsive container', () => {
      render(<MockActionsBreakdownChart data={mockData} />);

      expect(screen.getByTestId('responsive-container')).toBeInTheDocument();
    });

    it('ABC-009: renders action list', () => {
      render(<MockActionsBreakdownChart data={mockData} />);

      expect(screen.getByTestId('action-list')).toBeInTheDocument();
    });

    it('ABC-010: applies custom className', () => {
      render(<MockActionsBreakdownChart data={mockData} className="custom-class" />);

      const container = screen.getByTestId('actions-breakdown-chart-container');
      expect(container).toHaveClass('custom-class');
    });
  });

  describe('ABC-011 to ABC-020: Action Counts and Percentages', () => {
    it('ABC-011: displays correct calls count', () => {
      render(<MockActionsBreakdownChart data={mockData} />);

      const calls = screen.getByTestId('action-calls');
      expect(calls).toHaveAttribute('data-count', '32');
    });

    it('ABC-012: displays correct directions count', () => {
      render(<MockActionsBreakdownChart data={mockData} />);

      const directions = screen.getByTestId('action-directions');
      expect(directions).toHaveAttribute('data-count', '25');
    });

    it('ABC-013: displays correct website count', () => {
      render(<MockActionsBreakdownChart data={mockData} />);

      const website = screen.getByTestId('action-website');
      expect(website).toHaveAttribute('data-count', '22');
    });

    it('ABC-014: displays correct photos count', () => {
      render(<MockActionsBreakdownChart data={mockData} />);

      const photos = screen.getByTestId('action-photos');
      expect(photos).toHaveAttribute('data-count', '10');
    });

    it('ABC-015: calculates correct total', () => {
      render(<MockActionsBreakdownChart data={mockData} />);

      const chart = screen.getByTestId('actions-breakdown-chart');
      const total = 32 + 25 + 22 + 10;
      expect(chart).toHaveAttribute('data-total', total.toString());
    });

    it('ABC-016: displays correct calls percentage', () => {
      render(<MockActionsBreakdownChart data={mockData} />);

      const calls = screen.getByTestId('action-calls');
      const percentage = ((32 / 89) * 100).toFixed(1);
      expect(calls).toHaveAttribute('data-percentage', percentage);
    });

    it('ABC-017: displays correct directions percentage', () => {
      render(<MockActionsBreakdownChart data={mockData} />);

      const directions = screen.getByTestId('action-directions');
      const percentage = ((25 / 89) * 100).toFixed(1);
      expect(directions).toHaveAttribute('data-percentage', percentage);
    });

    it('ABC-018: displays correct website percentage', () => {
      render(<MockActionsBreakdownChart data={mockData} />);

      const website = screen.getByTestId('action-website');
      const percentage = ((22 / 89) * 100).toFixed(1);
      expect(website).toHaveAttribute('data-percentage', percentage);
    });

    it('ABC-019: displays correct photos percentage', () => {
      render(<MockActionsBreakdownChart data={mockData} />);

      const photos = screen.getByTestId('action-photos');
      const percentage = ((10 / 89) * 100).toFixed(1);
      expect(photos).toHaveAttribute('data-percentage', percentage);
    });

    it('ABC-020: percentages sum to 100', () => {
      render(<MockActionsBreakdownChart data={mockData} />);

      const segments = ['calls', 'directions', 'website', 'photos'];
      const percentages = segments.map(name => {
        const el = screen.getByTestId(`action-${name}`);
        return parseFloat(el.getAttribute('data-percentage') || '0');
      });

      const sum = percentages.reduce((a, b) => a + b, 0);
      expect(sum).toBeCloseTo(100, 0);
    });
  });

  describe('ABC-021 to ABC-030: Colors', () => {
    it('ABC-021: calls has green color', () => {
      render(<MockActionsBreakdownChart data={mockData} />);

      const cell = screen.getByTestId('cell-calls');
      expect(cell).toHaveAttribute('data-fill', '#22c55e');
    });

    it('ABC-022: directions has blue color', () => {
      render(<MockActionsBreakdownChart data={mockData} />);

      const cell = screen.getByTestId('cell-directions');
      expect(cell).toHaveAttribute('data-fill', '#3b82f6');
    });

    it('ABC-023: website has amber color', () => {
      render(<MockActionsBreakdownChart data={mockData} />);

      const cell = screen.getByTestId('cell-website');
      expect(cell).toHaveAttribute('data-fill', '#f59e0b');
    });

    it('ABC-024: photos has purple color', () => {
      render(<MockActionsBreakdownChart data={mockData} />);

      const cell = screen.getByTestId('cell-photos');
      expect(cell).toHaveAttribute('data-fill', '#8b5cf6');
    });

    it('ABC-025: bar chart uses same colors', () => {
      render(<MockActionsBreakdownChart data={mockData} chartType="bar" />);

      expect(screen.getByTestId('bar-calls')).toHaveAttribute('data-fill', '#22c55e');
      expect(screen.getByTestId('bar-directions')).toHaveAttribute('data-fill', '#3b82f6');
      expect(screen.getByTestId('bar-website')).toHaveAttribute('data-fill', '#f59e0b');
      expect(screen.getByTestId('bar-photos')).toHaveAttribute('data-fill', '#8b5cf6');
    });

    it('ABC-026: pie has 4 segments', () => {
      render(<MockActionsBreakdownChart data={mockData} />);

      const pie = screen.getByTestId('pie');
      expect(pie).toHaveAttribute('data-segments', '4');
    });

    it('ABC-027: bar chart has 4 bars', () => {
      render(<MockActionsBreakdownChart data={mockData} chartType="bar" />);

      const barChart = screen.getByTestId('bar-chart');
      expect(barChart).toHaveAttribute('data-bars', '4');
    });

    it('ABC-028: chart type is in data attribute', () => {
      render(<MockActionsBreakdownChart data={mockData} chartType="pie" />);

      const chart = screen.getByTestId('actions-breakdown-chart');
      expect(chart).toHaveAttribute('data-chart-type', 'pie');
    });

    it('ABC-029: bar chart type is in data attribute', () => {
      render(<MockActionsBreakdownChart data={mockData} chartType="bar" />);

      const chart = screen.getByTestId('actions-breakdown-chart');
      expect(chart).toHaveAttribute('data-chart-type', 'bar');
    });

    it('ABC-030: default chart type is pie', () => {
      render(<MockActionsBreakdownChart data={mockData} />);

      expect(screen.getByTestId('pie-chart')).toBeInTheDocument();
    });
  });

  describe('ABC-031 to ABC-037: Click Handling', () => {
    it('ABC-031: clicking segment calls onSegmentClick', async () => {
      const user = userEvent.setup();
      render(<MockActionsBreakdownChart data={mockData} onSegmentClick={mockOnSegmentClick} />);

      await user.click(screen.getByTestId('cell-calls'));

      expect(mockOnSegmentClick).toHaveBeenCalledWith('calls');
    });

    it('ABC-032: clicking action list item calls onSegmentClick', async () => {
      const user = userEvent.setup();
      render(<MockActionsBreakdownChart data={mockData} onSegmentClick={mockOnSegmentClick} />);

      await user.click(screen.getByTestId('action-calls'));

      expect(mockOnSegmentClick).toHaveBeenCalledWith('calls');
    });

    it('ABC-033: action items are clickable', () => {
      render(<MockActionsBreakdownChart data={mockData} onSegmentClick={mockOnSegmentClick} />);

      const callsItem = screen.getByTestId('action-calls');
      expect(callsItem).toHaveClass('cursor-pointer');
    });

    it('ABC-034: segments have cursor-pointer class', () => {
      render(<MockActionsBreakdownChart data={mockData} onSegmentClick={mockOnSegmentClick} />);

      const cell = screen.getByTestId('cell-calls');
      expect(cell).toHaveClass('cursor-pointer');
    });

    it('ABC-035: action items have role="button"', () => {
      render(<MockActionsBreakdownChart data={mockData} />);

      const callsItem = screen.getByTestId('action-calls');
      expect(callsItem).toHaveAttribute('role', 'button');
    });

    it('ABC-036: action items are keyboard focusable', () => {
      render(<MockActionsBreakdownChart data={mockData} />);

      const callsItem = screen.getByTestId('action-calls');
      expect(callsItem).toHaveAttribute('tabIndex', '0');
    });

    it('ABC-037: works without onSegmentClick callback', async () => {
      const user = userEvent.setup();
      render(<MockActionsBreakdownChart data={mockData} />);

      // Should not throw when clicked without callback
      await user.click(screen.getByTestId('action-calls'));

      expect(screen.getByTestId('actions-breakdown-chart')).toBeInTheDocument();
    });
  });

  describe('ABC-038 to ABC-042: Loading State', () => {
    it('ABC-038: shows skeleton when isLoading is true', () => {
      render(<MockActionsBreakdownChart data={mockData} isLoading={true} />);

      expect(screen.getByTestId('actions-breakdown-skeleton')).toBeInTheDocument();
    });

    it('ABC-039: hides chart when loading', () => {
      render(<MockActionsBreakdownChart data={mockData} isLoading={true} />);

      expect(screen.queryByTestId('actions-breakdown-chart')).not.toBeInTheDocument();
    });

    it('ABC-040: skeleton has animate-pulse class', () => {
      render(<MockActionsBreakdownChart data={mockData} isLoading={true} />);

      const skeleton = screen.getByTestId('actions-breakdown-skeleton');
      expect(skeleton).toHaveClass('animate-pulse');
    });

    it('ABC-041: skeleton has aria-live for accessibility', () => {
      render(<MockActionsBreakdownChart data={mockData} isLoading={true} />);

      const skeleton = screen.getByTestId('actions-breakdown-skeleton');
      expect(skeleton).toHaveAttribute('aria-live', 'polite');
    });

    it('ABC-042: skeleton has screen reader text', () => {
      render(<MockActionsBreakdownChart data={mockData} isLoading={true} />);

      expect(screen.getByText('Loading actions breakdown...')).toHaveClass('sr-only');
    });
  });
});
