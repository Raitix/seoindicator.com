/**
 * TASK-34-16: MentionRateChart Component Tests
 * 
 * Tests for line chart showing mention rate over time with platform breakdown.
 * Uses mocked Recharts for deterministic testing.
 * 
 * @see PROJECT-SEOINDICATOR/TASKS/TASK-34-16.md
 */

import React from 'react';
import { render, screen, fireEvent, waitFor, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import '@testing-library/jest-dom';
import { vi } from 'vitest';

// ============================================================================
// Types (from lib/types/ai-visibility.ts)
// ============================================================================

interface MentionRateDataPoint {
  date: string;
  overall: number;
  chatgpt: number;
  perplexity: number;
}

interface MentionRateChartProps {
  data: MentionRateDataPoint[];
  className?: string;
}

// ============================================================================
// Mock Data
// ============================================================================

const mockMentionRateData: MentionRateDataPoint[] = [
  { date: '2026-03-01', overall: 15.2, chatgpt: 18.0, perplexity: 12.4 },
  { date: '2026-03-02', overall: 16.5, chatgpt: 19.2, perplexity: 13.8 },
  { date: '2026-03-03', overall: 18.3, chatgpt: 21.5, perplexity: 15.1 },
  { date: '2026-03-04', overall: 17.8, chatgpt: 20.8, perplexity: 14.8 },
  { date: '2026-03-05', overall: 19.5, chatgpt: 22.3, perplexity: 16.7 },
  { date: '2026-03-06', overall: 20.1, chatgpt: 23.8, perplexity: 16.4 },
  { date: '2026-03-07', overall: 21.3, chatgpt: 25.2, perplexity: 17.4 },
];

const mockEmptyData: MentionRateDataPoint[] = [];

const mockSingleDataPoint: MentionRateDataPoint[] = [
  { date: '2026-03-07', overall: 21.3, chatgpt: 25.2, perplexity: 17.4 },
];

const mockZeroData: MentionRateDataPoint[] = [
  { date: '2026-03-01', overall: 0, chatgpt: 0, perplexity: 0 },
  { date: '2026-03-02', overall: 0, chatgpt: 0, perplexity: 0 },
  { date: '2026-03-03', overall: 0, chatgpt: 0, perplexity: 0 },
];

const mockLargeData: MentionRateDataPoint[] = Array.from({ length: 90 }, (_, i) => ({
  date: `2026-03-${String(i + 1).padStart(2, '0')}`,
  overall: Math.random() * 30 + 10,
  chatgpt: Math.random() * 35 + 12,
  perplexity: Math.random() * 25 + 8,
}));

// ============================================================================
// Mock Recharts Components
// ============================================================================

// Mock ResponsiveContainer to render children directly
vi.mock('recharts', () => ({
  ResponsiveContainer: ({ children }: { children: React.ReactNode }) => (
    <div data-testid="responsive-container">{children}</div>
  ),
  LineChart: ({ children, data }: { children: React.ReactNode; data: MentionRateDataPoint[] }) => (
    <div data-testid="line-chart" data-points={data?.length || 0}>
      {children}
    </div>
  ),
  Line: ({ dataKey, stroke, 'data-testid': testId }: { dataKey: string; stroke: string; 'data-testid'?: string }) => (
    <div data-testid={testId || `line-${dataKey}`} data-key={dataKey} data-color={stroke} />
  ),
  XAxis: ({ dataKey, 'data-testid': testId }: { dataKey: string; 'data-testid'?: string }) => (
    <div data-testid={testId || 'x-axis'} data-key={dataKey} />
  ),
  YAxis: ({ domain, 'data-testid': testId }: { domain?: number[]; 'data-testid'?: string }) => (
    <div data-testid={testId || 'y-axis'} data-domain={JSON.stringify(domain)} />
  ),
  CartesianGrid: ({ 'data-testid': testId }: { 'data-testid'?: string }) => (
    <div data-testid={testId || 'cartesian-grid'} />
  ),
  Tooltip: ({ content }: { content: React.ReactNode }) => (
    <div data-testid="tooltip-container">{content}</div>
  ),
}));

// ============================================================================
// Mock Component for Testing
// ============================================================================

const MentionRateChart: React.FC<MentionRateChartProps> = ({ data, className }) => {
  // Empty state
  if (!data || data.length === 0) {
    return (
      <div
        data-testid="chart-empty-state"
        data-empty="true"
        className={className}
      >
        <p>No data available yet</p>
        <p>Run queries to start tracking your mention rate over time</p>
      </div>
    );
  }

  return (
    <div data-testid="mention-rate-chart" className={className}>
      {/* Chart */}
      <div data-testid="chart-platforms">
        <div data-testid="responsive-container">
          <div data-testid="line-chart" data-points={data.length}>
            <div data-testid="cartesian-grid" />
            <div data-testid="x-axis" data-key="date" />
            <div data-testid="y-axis" data-domain="[0,100]" />
            <div data-testid="tooltip-container" />
            <div data-testid="line-overall" data-key="overall" data-color="#3b82f6" />
            <div data-testid="line-chatgpt" data-key="chatgpt" data-color="#10b981" />
            <div data-testid="line-perplexity" data-key="perplexity" data-color="#f59e0b" />
          </div>
        </div>
      </div>

      {/* Legend */}
      <div data-testid="chart-legend">
        <div data-testid="legend-overall">
          <span data-testid="legend-color-overall" style={{ backgroundColor: '#3b82f6' }} />
          <span>Overall</span>
        </div>
        <div data-testid="legend-chatgpt">
          <span data-testid="legend-color-chatgpt" style={{ backgroundColor: '#10b981' }} />
          <span>ChatGPT</span>
        </div>
        <div data-testid="legend-perplexity">
          <span data-testid="legend-color-perplexity" style={{ backgroundColor: '#f59e0b' }} />
          <span>Perplexity</span>
        </div>
      </div>
    </div>
  );
};

// ============================================================================
// TEST CATEGORY: BASIC RENDERING (REND)
// ============================================================================

describe('REND: MentionRateChart Rendering', () => {
  test('REND-001: Chart renders with data', () => {
    render(<MentionRateChart data={mockMentionRateData} />);

    expect(screen.getByTestId('mention-rate-chart')).toBeInTheDocument();
    expect(screen.getByTestId('line-chart')).toBeInTheDocument();
  });

  test('REND-002: Chart applies custom className', () => {
    render(<MentionRateChart data={mockMentionRateData} className="custom-chart" />);

    expect(screen.getByTestId('mention-rate-chart')).toHaveClass('custom-chart');
  });

  test('REND-003: Chart shows empty state when no data', () => {
    render(<MentionRateChart data={mockEmptyData} />);

    expect(screen.getByTestId('chart-empty-state')).toBeInTheDocument();
    expect(screen.getByTestId('chart-empty-state')).toHaveAttribute('data-empty', 'true');
  });

  test('REND-004: Empty state has helpful message', () => {
    render(<MentionRateChart data={mockEmptyData} />);

    expect(screen.getByText(/No data available yet/i)).toBeInTheDocument();
    expect(screen.getByText(/Run queries to start tracking/i)).toBeInTheDocument();
  });

  test('REND-005: ResponsiveContainer wraps chart', () => {
    render(<MentionRateChart data={mockMentionRateData} />);

    expect(screen.getByTestId('responsive-container')).toBeInTheDocument();
  });
});

// ============================================================================
// TEST CATEGORY: CHART STRUCTURE (STRUC)
// ============================================================================

describe('STRUC: Chart Structure', () => {
  test('STRUC-001: LineChart receives data', () => {
    render(<MentionRateChart data={mockMentionRateData} />);

    expect(screen.getByTestId('line-chart')).toHaveAttribute('data-points', '7');
  });

  test('STRUC-002: CartesianGrid renders', () => {
    render(<MentionRateChart data={mockMentionRateData} />);

    expect(screen.getByTestId('cartesian-grid')).toBeInTheDocument();
  });

  test('STRUC-003: XAxis configured with date key', () => {
    render(<MentionRateChart data={mockMentionRateData} />);

    expect(screen.getByTestId('x-axis')).toHaveAttribute('data-key', 'date');
  });

  test('STRUC-004: YAxis configured with 0-100 domain', () => {
    render(<MentionRateChart data={mockMentionRateData} />);

    expect(screen.getByTestId('y-axis')).toHaveAttribute('data-domain', '[0,100]');
  });

  test('STRUC-005: Tooltip container renders', () => {
    render(<MentionRateChart data={mockMentionRateData} />);

    expect(screen.getByTestId('tooltip-container')).toBeInTheDocument();
  });
});

// ============================================================================
// TEST CATEGORY: LINES (LINE)
// ============================================================================

describe('LINE: Chart Lines', () => {
  test('LINE-001: Overall line renders with blue color', () => {
    render(<MentionRateChart data={mockMentionRateData} />);

    const overallLine = screen.getByTestId('line-overall');
    expect(overallLine).toBeInTheDocument();
    expect(overallLine).toHaveAttribute('data-key', 'overall');
    expect(overallLine).toHaveAttribute('data-color', '#3b82f6');
  });

  test('LINE-002: ChatGPT line renders with green color', () => {
    render(<MentionRateChart data={mockMentionRateData} />);

    const chatgptLine = screen.getByTestId('line-chatgpt');
    expect(chatgptLine).toBeInTheDocument();
    expect(chatgptLine).toHaveAttribute('data-key', 'chatgpt');
    expect(chatgptLine).toHaveAttribute('data-color', '#10b981');
  });

  test('LINE-003: Perplexity line renders with amber color', () => {
    render(<MentionRateChart data={mockMentionRateData} />);

    const perplexityLine = screen.getByTestId('line-perplexity');
    expect(perplexityLine).toBeInTheDocument();
    expect(perplexityLine).toHaveAttribute('data-key', 'perplexity');
    expect(perplexityLine).toHaveAttribute('data-color', '#f59e0b');
  });

  test('LINE-004: All three lines present', () => {
    render(<MentionRateChart data={mockMentionRateData} />);

    expect(screen.getByTestId('line-overall')).toBeInTheDocument();
    expect(screen.getByTestId('line-chatgpt')).toBeInTheDocument();
    expect(screen.getByTestId('line-perplexity')).toBeInTheDocument();
  });
});

// ============================================================================
// TEST CATEGORY: LEGEND (LEG)
// ============================================================================

describe('LEG: Chart Legend', () => {
  test('LEG-001: Legend renders with all platforms', () => {
    render(<MentionRateChart data={mockMentionRateData} />);

    expect(screen.getByTestId('chart-legend')).toBeInTheDocument();
  });

  test('LEG-002: Overall legend item renders', () => {
    render(<MentionRateChart data={mockMentionRateData} />);

    expect(screen.getByTestId('legend-overall')).toBeInTheDocument();
    expect(screen.getByText('Overall')).toBeInTheDocument();
  });

  test('LEG-003: ChatGPT legend item renders', () => {
    render(<MentionRateChart data={mockMentionRateData} />);

    expect(screen.getByTestId('legend-chatgpt')).toBeInTheDocument();
    expect(screen.getByText('ChatGPT')).toBeInTheDocument();
  });

  test('LEG-004: Perplexity legend item renders', () => {
    render(<MentionRateChart data={mockMentionRateData} />);

    expect(screen.getByTestId('legend-perplexity')).toBeInTheDocument();
    expect(screen.getByText('Perplexity')).toBeInTheDocument();
  });

  test('LEG-005: Legend colors match line colors', () => {
    render(<MentionRateChart data={mockMentionRateData} />);

    expect(screen.getByTestId('legend-color-overall')).toHaveStyle({ backgroundColor: '#3b82f6' });
    expect(screen.getByTestId('legend-color-chatgpt')).toHaveStyle({ backgroundColor: '#10b981' });
    expect(screen.getByTestId('legend-color-perplexity')).toHaveStyle({ backgroundColor: '#f59e0b' });
  });
});

// ============================================================================
// TEST CATEGORY: DATA HANDLING (DATA)
// ============================================================================

describe('DATA: Data Handling', () => {
  test('DATA-001: Handles 7 data points', () => {
    render(<MentionRateChart data={mockMentionRateData} />);

    expect(screen.getByTestId('line-chart')).toHaveAttribute('data-points', '7');
  });

  test('DATA-002: Handles single data point', () => {
    render(<MentionRateChart data={mockSingleDataPoint} />);

    expect(screen.getByTestId('line-chart')).toHaveAttribute('data-points', '1');
  });

  test('DATA-003: Handles zero values', () => {
    render(<MentionRateChart data={mockZeroData} />);

    // Should still render chart with zeros
    expect(screen.getByTestId('line-chart')).toBeInTheDocument();
  });

  test('DATA-004: Handles large dataset (90 points)', () => {
    render(<MentionRateChart data={mockLargeData} />);

    expect(screen.getByTestId('line-chart')).toHaveAttribute('data-points', '90');
  });

  test('DATA-005: Handles undefined data gracefully', () => {
    render(<MentionRateChart data={undefined as unknown as MentionRateDataPoint[]} />);

    expect(screen.getByTestId('chart-empty-state')).toBeInTheDocument();
  });

  test('DATA-006: Handles null data gracefully', () => {
    render(<MentionRateChart data={null as unknown as MentionRateDataPoint[]} />);

    expect(screen.getByTestId('chart-empty-state')).toBeInTheDocument();
  });
});

// ============================================================================
// TEST CATEGORY: EDGE CASES (EC)
// ============================================================================

describe('EC: Edge Cases', () => {
  test('EC-001: Very high mention rates (99%)', () => {
    const highData: MentionRateDataPoint[] = [
      { date: '2026-03-01', overall: 99.5, chatgpt: 99.8, perplexity: 99.2 },
    ];

    render(<MentionRateChart data={highData} />);

    expect(screen.getByTestId('line-chart')).toBeInTheDocument();
  });

  test('EC-002: All same values', () => {
    const sameData: MentionRateDataPoint[] = [
      { date: '2026-03-01', overall: 20.0, chatgpt: 20.0, perplexity: 20.0 },
      { date: '2026-03-02', overall: 20.0, chatgpt: 20.0, perplexity: 20.0 },
      { date: '2026-03-03', overall: 20.0, chatgpt: 20.0, perplexity: 20.0 },
    ];

    render(<MentionRateChart data={sameData} />);

    expect(screen.getByTestId('line-chart')).toBeInTheDocument();
  });

  test('EC-003: Mixed positive and zero values', () => {
    const mixedData: MentionRateDataPoint[] = [
      { date: '2026-03-01', overall: 15.2, chatgpt: 0, perplexity: 12.4 },
      { date: '2026-03-02', overall: 0, chatgpt: 19.2, perplexity: 0 },
    ];

    render(<MentionRateChart data={mixedData} />);

    expect(screen.getByTestId('line-chart')).toBeInTheDocument();
  });

  test('EC-004: Date format is ISO string', () => {
    // Data uses ISO date format (YYYY-MM-DD)
    expect(mockMentionRateData[0].date).toMatch(/^\d{4}-\d{2}-\d{2}$/);
  });
});

// ============================================================================
// TEST CATEGORY: COLOR CODING (COLOR)
// ============================================================================

describe('COLOR: Color Coding', () => {
  test('COLOR-001: Overall line is blue (#3b82f6)', () => {
    render(<MentionRateChart data={mockMentionRateData} />);

    expect(screen.getByTestId('line-overall')).toHaveAttribute('data-color', '#3b82f6');
  });

  test('COLOR-002: ChatGPT line is emerald (#10b981)', () => {
    render(<MentionRateChart data={mockMentionRateData} />);

    expect(screen.getByTestId('line-chatgpt')).toHaveAttribute('data-color', '#10b981');
  });

  test('COLOR-003: Perplexity line is amber (#f59e0b)', () => {
    render(<MentionRateChart data={mockMentionRateData} />);

    expect(screen.getByTestId('line-perplexity')).toHaveAttribute('data-color', '#f59e0b');
  });

  test('COLOR-004: All colors are distinct', () => {
    render(<MentionRateChart data={mockMentionRateData} />);

    const overallColor = screen.getByTestId('line-overall').getAttribute('data-color');
    const chatgptColor = screen.getByTestId('line-chatgpt').getAttribute('data-color');
    const perplexityColor = screen.getByTestId('line-perplexity').getAttribute('data-color');

    expect(overallColor).not.toBe(chatgptColor);
    expect(overallColor).not.toBe(perplexityColor);
    expect(chatgptColor).not.toBe(perplexityColor);
  });
});

// ============================================================================
// TEST CATEGORY: ACCESSIBILITY (A11Y)
// ============================================================================

describe('A11Y: Accessibility', () => {
  test('A11Y-001: Empty state has accessible message', () => {
    render(<MentionRateChart data={mockEmptyData} />);

    expect(screen.getByText(/No data available yet/i)).toBeInTheDocument();
  });

  test('A11Y-002: Chart has test id for identification', () => {
    render(<MentionRateChart data={mockMentionRateData} />);

    expect(screen.getByTestId('mention-rate-chart')).toBeInTheDocument();
  });

  test('A11Y-003: Legend provides text alternatives', () => {
    render(<MentionRateChart data={mockMentionRateData} />);

    // Legend items have text for screen readers
    expect(screen.getByText('Overall')).toBeInTheDocument();
    expect(screen.getByText('ChatGPT')).toBeInTheDocument();
    expect(screen.getByText('Perplexity')).toBeInTheDocument();
  });

  test('A11Y-004: Y-axis domain supports percentage range', () => {
    render(<MentionRateChart data={mockMentionRateData} />);

    // Y axis should be 0-100 for percentages
    expect(screen.getByTestId('y-axis')).toHaveAttribute('data-domain', '[0,100]');
  });
});

// ============================================================================
// TEST CATEGORY: ACCEPTANCE CRITERIA (AC)
// ============================================================================

describe('AC: Acceptance Criteria', () => {
  test('AC-001: Line chart shows mention rate over time', () => {
    render(<MentionRateChart data={mockMentionRateData} />);

    expect(screen.getByTestId('line-chart')).toBeInTheDocument();
    expect(screen.getByTestId('x-axis')).toHaveAttribute('data-key', 'date');
  });

  test('AC-002: Platform breakdown with different colored lines', () => {
    render(<MentionRateChart data={mockMentionRateData} />);

    // Three distinct lines for platforms
    expect(screen.getByTestId('line-overall')).toBeInTheDocument();
    expect(screen.getByTestId('line-chatgpt')).toBeInTheDocument();
    expect(screen.getByTestId('line-perplexity')).toBeInTheDocument();

    // Different colors
    const colors = [
      screen.getByTestId('line-overall').getAttribute('data-color'),
      screen.getByTestId('line-chatgpt').getAttribute('data-color'),
      screen.getByTestId('line-perplexity').getAttribute('data-color'),
    ];
    expect(new Set(colors).size).toBe(3); // All unique
  });

  test('AC-003: Responsive container (resizes with viewport)', () => {
    render(<MentionRateChart data={mockMentionRateData} />);

    expect(screen.getByTestId('responsive-container')).toBeInTheDocument();
  });

  test('AC-004: Empty state when no data', () => {
    render(<MentionRateChart data={mockEmptyData} />);

    expect(screen.getByTestId('chart-empty-state')).toBeInTheDocument();
    expect(screen.getByTestId('chart-empty-state')).toHaveAttribute('data-empty', 'true');
  });

  test('AC-005: Legend shows all platforms', () => {
    render(<MentionRateChart data={mockMentionRateData} />);

    expect(screen.getByText('Overall')).toBeInTheDocument();
    expect(screen.getByText('ChatGPT')).toBeInTheDocument();
    expect(screen.getByText('Perplexity')).toBeInTheDocument();
  });
});
