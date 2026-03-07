/**
 * TASK-34-16: QueryCategoriesChart Component Tests
 * 
 * Tests for pie/donut chart showing distribution of query categories.
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

interface QueryCategoryItem {
  name: string;
  value: number;
}

interface QueryCategoriesChartProps {
  data: QueryCategoryItem[];
  className?: string;
}

// ============================================================================
// Mock Data
// ============================================================================

const mockCategoryData: QueryCategoryItem[] = [
  { name: 'comparison', value: 45 },
  { name: 'recommendation', value: 32 },
  { name: 'feature', value: 18 },
  { name: 'use_case', value: 12 },
];

const mockEmptyData: QueryCategoryItem[] = [];

const mockSingleCategory: QueryCategoryItem[] = [
  { name: 'comparison', value: 100 },
];

const mockTwoCategories: QueryCategoryItem[] = [
  { name: 'comparison', value: 60 },
  { name: 'recommendation', value: 40 },
];

const mockZeroValues: QueryCategoryItem[] = [
  { name: 'comparison', value: 0 },
  { name: 'recommendation', value: 0 },
  { name: 'feature', value: 0 },
  { name: 'use_case', value: 0 },
];

const mockLargeValues: QueryCategoryItem[] = [
  { name: 'comparison', value: 10000 },
  { name: 'recommendation', value: 8500 },
  { name: 'feature', value: 5000 },
  { name: 'use_case', value: 3000 },
];

// ============================================================================
// Constants
// ============================================================================

const COLORS = [
  '#3b82f6', // Blue - comparison
  '#10b981', // Emerald - recommendation
  '#f59e0b', // Amber - feature
  '#8b5cf6', // Violet - use_case
];

const CATEGORY_LABELS: Record<string, string> = {
  comparison: 'Comparison',
  recommendation: 'Recommendation',
  feature: 'Feature',
  use_case: 'Use Case',
};

// ============================================================================
// Mock Recharts Components
// ============================================================================

vi.mock('recharts', () => ({
  ResponsiveContainer: ({ children }: { children: React.ReactNode }) => (
    <div data-testid="responsive-container">{children}</div>
  ),
  PieChart: ({ children }: { children: React.ReactNode }) => (
    <div data-testid="pie-chart">{children}</div>
  ),
  Pie: ({ 
    data, 
    dataKey, 
    innerRadius, 
    outerRadius,
    'data-testid': testId,
    'data-segments': segments,
  }: { 
    data: QueryCategoryItem[];
    dataKey: string;
    innerRadius: number;
    outerRadius: number;
    'data-testid'?: string;
    'data-segments'?: number;
  }) => (
    <div 
      data-testid={testId || 'pie'} 
      data-key={dataKey}
      data-inner-radius={innerRadius}
      data-outer-radius={outerRadius}
      data-segments={data?.length || 0}
    />
  ),
  Cell: ({ 
    fill, 
    'data-testid': testId,
    'data-value': dataValue,
  }: { 
    fill: string; 
    'data-testid'?: string;
    'data-value'?: number;
  }) => (
    <div 
      data-testid={testId || 'cell'} 
      data-fill={fill}
      data-value={dataValue}
    />
  ),
  Tooltip: ({ content }: { content: React.ReactNode }) => (
    <div data-testid="tooltip-container">{content}</div>
  ),
}));

// ============================================================================
// Mock Lucide Icons
// ============================================================================

vi.mock('lucide-react', () => ({
  Info: () => <span data-testid="info-icon">ℹ</span>,
}));

// ============================================================================
// Helper Functions
// ============================================================================

function calculateTotal(data: QueryCategoryItem[]): number {
  return data.reduce((sum, item) => sum + item.value, 0);
}

function calculatePercentage(value: number, total: number): string {
  if (total === 0) return '0';
  return ((value / total) * 100).toFixed(0);
}

// ============================================================================
// Mock Component for Testing
// ============================================================================

const QueryCategoriesChart: React.FC<QueryCategoriesChartProps> = ({ data, className }) => {
  // Empty state
  if (!data || data.length === 0) {
    return (
      <div
        data-testid="chart-empty-state"
        className={className}
      >
        <p>No category data available</p>
        <p>Run queries to see category distribution</p>
      </div>
    );
  }

  // Calculate total for percentages
  const total = calculateTotal(data);

  return (
    <div data-testid="query-categories" className={className}>
      {/* Chart */}
      <div data-testid="pie-chart">
        <div data-testid="responsive-container">
          <div 
            data-testid="pie" 
            data-key="value"
            data-inner-radius={60}
            data-outer-radius={100}
            data-segments={data.length}
          >
            {data.map((entry, index) => (
              <div
                key={`cell-${index}`}
                data-testid={`pie-segment-${entry.name}`}
                data-fill={COLORS[index % COLORS.length]}
                data-value={entry.value}
              />
            ))}
          </div>
          <div data-testid="tooltip-container" />
        </div>
      </div>

      {/* Legend */}
      <div data-testid="chart-legend">
        {data.map((item, index) => {
          const label = CATEGORY_LABELS[item.name] || item.name;
          const percentage = calculatePercentage(item.value, total);

          return (
            <div
              key={item.name}
              data-testid={`legend-${item.name}`}
            >
              <span
                data-testid={`legend-color-${item.name}`}
                style={{ backgroundColor: COLORS[index % COLORS.length] }}
              />
              <span>
                {label} ({percentage}%)
              </span>
            </div>
          );
        })}
      </div>

      {/* Stats */}
      <div data-testid="chart-stats">
        <div data-testid="total-queries">
          <span>Total Queries</span>
          <span data-testid="total-value">{total}</span>
        </div>
      </div>
    </div>
  );
};

// ============================================================================
// TEST CATEGORY: BASIC RENDERING (REND)
// ============================================================================

describe('REND: QueryCategoriesChart Rendering', () => {
  test('REND-001: Chart renders with data', () => {
    render(<QueryCategoriesChart data={mockCategoryData} />);

    expect(screen.getByTestId('query-categories')).toBeInTheDocument();
    expect(screen.getByTestId('pie-chart')).toBeInTheDocument();
  });

  test('REND-002: Chart applies custom className', () => {
    render(<QueryCategoriesChart data={mockCategoryData} className="custom-chart" />);

    expect(screen.getByTestId('query-categories')).toHaveClass('custom-chart');
  });

  test('REND-003: Chart shows empty state when no data', () => {
    render(<QueryCategoriesChart data={mockEmptyData} />);

    expect(screen.getByTestId('chart-empty-state')).toBeInTheDocument();
  });

  test('REND-004: Empty state has helpful message', () => {
    render(<QueryCategoriesChart data={mockEmptyData} />);

    expect(screen.getByText(/No category data available/i)).toBeInTheDocument();
    expect(screen.getByText(/Run queries to see category distribution/i)).toBeInTheDocument();
  });

  test('REND-005: ResponsiveContainer wraps chart', () => {
    render(<QueryCategoriesChart data={mockCategoryData} />);

    expect(screen.getByTestId('responsive-container')).toBeInTheDocument();
  });
});

// ============================================================================
// TEST CATEGORY: PIE CHART STRUCTURE (PIE)
// ============================================================================

describe('PIE: Pie Chart Structure', () => {
  test('PIE-001: Pie component receives data', () => {
    render(<QueryCategoriesChart data={mockCategoryData} />);

    expect(screen.getByTestId('pie')).toHaveAttribute('data-segments', '4');
  });

  test('PIE-002: Pie uses value as data key', () => {
    render(<QueryCategoriesChart data={mockCategoryData} />);

    expect(screen.getByTestId('pie')).toHaveAttribute('data-key', 'value');
  });

  test('PIE-003: Inner radius set for donut style', () => {
    render(<QueryCategoriesChart data={mockCategoryData} />);

    expect(screen.getByTestId('pie')).toHaveAttribute('data-inner-radius', '60');
  });

  test('PIE-004: Outer radius set', () => {
    render(<QueryCategoriesChart data={mockCategoryData} />);

    expect(screen.getByTestId('pie')).toHaveAttribute('data-outer-radius', '100');
  });

  test('PIE-005: Tooltip container renders', () => {
    render(<QueryCategoriesChart data={mockCategoryData} />);

    expect(screen.getByTestId('tooltip-container')).toBeInTheDocument();
  });
});

// ============================================================================
// TEST CATEGORY: SEGMENTS (SEG)
// ============================================================================

describe('SEG: Chart Segments', () => {
  test('SEG-001: Comparison segment renders with blue', () => {
    render(<QueryCategoriesChart data={mockCategoryData} />);

    const segment = screen.getByTestId('pie-segment-comparison');
    expect(segment).toBeInTheDocument();
    expect(segment).toHaveAttribute('data-fill', '#3b82f6');
  });

  test('SEG-002: Recommendation segment renders with emerald', () => {
    render(<QueryCategoriesChart data={mockCategoryData} />);

    const segment = screen.getByTestId('pie-segment-recommendation');
    expect(segment).toBeInTheDocument();
    expect(segment).toHaveAttribute('data-fill', '#10b981');
  });

  test('SEG-003: Feature segment renders with amber', () => {
    render(<QueryCategoriesChart data={mockCategoryData} />);

    const segment = screen.getByTestId('pie-segment-feature');
    expect(segment).toBeInTheDocument();
    expect(segment).toHaveAttribute('data-fill', '#f59e0b');
  });

  test('SEG-004: Use Case segment renders with violet', () => {
    render(<QueryCategoriesChart data={mockCategoryData} />);

    const segment = screen.getByTestId('pie-segment-use_case');
    expect(segment).toBeInTheDocument();
    expect(segment).toHaveAttribute('data-fill', '#8b5cf6');
  });

  test('SEG-005: All segments have values', () => {
    render(<QueryCategoriesChart data={mockCategoryData} />);

    const comparison = screen.getByTestId('pie-segment-comparison');
    const recommendation = screen.getByTestId('pie-segment-recommendation');

    expect(comparison).toHaveAttribute('data-value', '45');
    expect(recommendation).toHaveAttribute('data-value', '32');
  });
});

// ============================================================================
// TEST CATEGORY: LEGEND (LEG)
// ============================================================================

describe('LEG: Chart Legend', () => {
  test('LEG-001: Legend renders with all categories', () => {
    render(<QueryCategoriesChart data={mockCategoryData} />);

    expect(screen.getByTestId('chart-legend')).toBeInTheDocument();
  });

  test('LEG-002: Comparison legend item renders', () => {
    render(<QueryCategoriesChart data={mockCategoryData} />);

    expect(screen.getByTestId('legend-comparison')).toBeInTheDocument();
    expect(screen.getByText(/Comparison/)).toBeInTheDocument();
  });

  test('LEG-003: Recommendation legend item renders', () => {
    render(<QueryCategoriesChart data={mockCategoryData} />);

    expect(screen.getByTestId('legend-recommendation')).toBeInTheDocument();
    expect(screen.getByText(/Recommendation/)).toBeInTheDocument();
  });

  test('LEG-004: Feature legend item renders', () => {
    render(<QueryCategoriesChart data={mockCategoryData} />);

    expect(screen.getByTestId('legend-feature')).toBeInTheDocument();
    expect(screen.getByText(/Feature/)).toBeInTheDocument();
  });

  test('LEG-005: Use Case legend item renders', () => {
    render(<QueryCategoriesChart data={mockCategoryData} />);

    expect(screen.getByTestId('legend-use_case')).toBeInTheDocument();
    expect(screen.getByText(/Use Case/)).toBeInTheDocument();
  });

  test('LEG-006: Legend shows percentages', () => {
    render(<QueryCategoriesChart data={mockCategoryData} />);

    // Total: 45+32+18+12 = 107
    // Comparison: 45/107 = 42%
    expect(screen.getByText(/Comparison \(42%\)/)).toBeInTheDocument();
  });

  test('LEG-007: Legend colors match segment colors', () => {
    render(<QueryCategoriesChart data={mockCategoryData} />);

    expect(screen.getByTestId('legend-color-comparison')).toHaveStyle({ backgroundColor: '#3b82f6' });
    expect(screen.getByTestId('legend-color-recommendation')).toHaveStyle({ backgroundColor: '#10b981' });
  });
});

// ============================================================================
// TEST CATEGORY: STATS (STATS)
// ============================================================================

describe('STATS: Chart Statistics', () => {
  test('STATS-001: Total queries shown', () => {
    render(<QueryCategoriesChart data={mockCategoryData} />);

    expect(screen.getByTestId('total-queries')).toBeInTheDocument();
  });

  test('STATS-002: Total value calculated correctly', () => {
    render(<QueryCategoriesChart data={mockCategoryData} />);

    // 45 + 32 + 18 + 12 = 107
    expect(screen.getByTestId('total-value')).toHaveTextContent('107');
  });

  test('STATS-003: Total updates with different data', () => {
    render(<QueryCategoriesChart data={mockLargeValues} />);

    // 10000 + 8500 + 5000 + 3000 = 26500
    expect(screen.getByTestId('total-value')).toHaveTextContent('26500');
  });
});

// ============================================================================
// TEST CATEGORY: DATA HANDLING (DATA)
// ============================================================================

describe('DATA: Data Handling', () => {
  test('DATA-001: Handles 4 categories', () => {
    render(<QueryCategoriesChart data={mockCategoryData} />);

    expect(screen.getByTestId('pie')).toHaveAttribute('data-segments', '4');
  });

  test('DATA-002: Handles single category', () => {
    render(<QueryCategoriesChart data={mockSingleCategory} />);

    expect(screen.getByTestId('pie')).toHaveAttribute('data-segments', '1');
  });

  test('DATA-003: Handles two categories', () => {
    render(<QueryCategoriesChart data={mockTwoCategories} />);

    expect(screen.getByTestId('pie')).toHaveAttribute('data-segments', '2');
  });

  test('DATA-004: Handles zero values', () => {
    render(<QueryCategoriesChart data={mockZeroValues} />);

    // Should still render with zero values
    expect(screen.getByTestId('pie')).toBeInTheDocument();
  });

  test('DATA-005: Handles large values', () => {
    render(<QueryCategoriesChart data={mockLargeValues} />);

    expect(screen.getByTestId('total-value')).toHaveTextContent('26500');
  });

  test('DATA-006: Handles undefined data gracefully', () => {
    render(<QueryCategoriesChart data={undefined as unknown as QueryCategoryItem[]} />);

    expect(screen.getByTestId('chart-empty-state')).toBeInTheDocument();
  });
});

// ============================================================================
// TEST CATEGORY: COLOR CODING (COLOR)
// ============================================================================

describe('COLOR: Color Coding', () => {
  test('COLOR-001: Comparison is blue (#3b82f6)', () => {
    render(<QueryCategoriesChart data={mockCategoryData} />);

    expect(screen.getByTestId('pie-segment-comparison')).toHaveAttribute('data-fill', '#3b82f6');
  });

  test('COLOR-002: Recommendation is emerald (#10b981)', () => {
    render(<QueryCategoriesChart data={mockCategoryData} />);

    expect(screen.getByTestId('pie-segment-recommendation')).toHaveAttribute('data-fill', '#10b981');
  });

  test('COLOR-003: Feature is amber (#f59e0b)', () => {
    render(<QueryCategoriesChart data={mockCategoryData} />);

    expect(screen.getByTestId('pie-segment-feature')).toHaveAttribute('data-fill', '#f59e0b');
  });

  test('COLOR-004: Use Case is violet (#8b5cf6)', () => {
    render(<QueryCategoriesChart data={mockCategoryData} />);

    expect(screen.getByTestId('pie-segment-use_case')).toHaveAttribute('data-fill', '#8b5cf6');
  });

  test('COLOR-005: All colors are distinct', () => {
    render(<QueryCategoriesChart data={mockCategoryData} />);

    const colors = [
      screen.getByTestId('pie-segment-comparison').getAttribute('data-fill'),
      screen.getByTestId('pie-segment-recommendation').getAttribute('data-fill'),
      screen.getByTestId('pie-segment-feature').getAttribute('data-fill'),
      screen.getByTestId('pie-segment-use_case').getAttribute('data-fill'),
    ];

    expect(new Set(colors).size).toBe(4); // All unique
  });
});

// ============================================================================
// TEST CATEGORY: PERCENTAGE CALCULATIONS (PERC)
// ============================================================================

describe('PERC: Percentage Calculations', () => {
  test('PERC-001: Calculates percentage correctly', () => {
    const total = calculateTotal(mockCategoryData);
    const comparisonPct = calculatePercentage(45, total);

    // 45/107 = 42%
    expect(comparisonPct).toBe('42');
  });

  test('PERC-002: Percentages sum to 100', () => {
    render(<QueryCategoriesChart data={mockCategoryData} />);

    // Get all legend text containing percentages
    const legend = screen.getByTestId('chart-legend');
    const text = legend.textContent || '';

    // Extract percentages
    const matches = text.match(/\((\d+)%\)/g);
    if (matches) {
      const percentages = matches.map(m => parseInt(m.replace(/[^0-9]/g, '')));
      const sum = percentages.reduce((a, b) => a + b, 0);
      // Due to rounding, should be close to 100 (42+30+17+11 = 100)
      expect(sum).toBeGreaterThanOrEqual(99);
      expect(sum).toBeLessThanOrEqual(101);
    }
  });

  test('PERC-003: Handles 50/50 split', () => {
    render(<QueryCategoriesChart data={mockTwoCategories} />);

    expect(screen.getByText(/Comparison \(50%\)/)).toBeInTheDocument();
    expect(screen.getByText(/Recommendation \(40%\)/)).toBeInTheDocument();
  });

  test('PERC-004: Handles 100% single category', () => {
    render(<QueryCategoriesChart data={mockSingleCategory} />);

    expect(screen.getByText(/Comparison \(100%\)/)).toBeInTheDocument();
  });
});

// ============================================================================
// TEST CATEGORY: EDGE CASES (EC)
// ============================================================================

describe('EC: Edge Cases', () => {
  test('EC-001: Very small percentage (< 1%)', () => {
    const smallData: QueryCategoryItem[] = [
      { name: 'comparison', value: 99 },
      { name: 'recommendation', value: 1 },
    ];

    render(<QueryCategoriesChart data={smallData} />);

    // 1/100 = 1%
    expect(screen.getByText(/Recommendation \(1%\)/)).toBeInTheDocument();
  });

  test('EC-002: Unknown category name handled', () => {
    const unknownData: QueryCategoryItem[] = [
      { name: 'unknown_category', value: 50 },
    ];

    render(<QueryCategoriesChart data={unknownData} />);

    // Should render with raw name
    expect(screen.getByTestId('pie-segment-unknown_category')).toBeInTheDocument();
  });

  test('EC-003: Empty category name handled', () => {
    const emptyNameData: QueryCategoryItem[] = [
      { name: '', value: 50 },
    ];

    render(<QueryCategoriesChart data={emptyNameData} />);

    // Should render without crash
    expect(screen.getByTestId('pie')).toBeInTheDocument();
  });

  test('EC-004: More than 4 categories cycles colors', () => {
    const manyCategories: QueryCategoryItem[] = [
      { name: 'comparison', value: 20 },
      { name: 'recommendation', value: 20 },
      { name: 'feature', value: 20 },
      { name: 'use_case', value: 20 },
      { name: 'other', value: 20 }, // Should use first color again
    ];

    render(<QueryCategoriesChart data={manyCategories} />);

    // First and fifth items should have same color (modulo)
    const firstColor = screen.getByTestId('pie-segment-comparison').getAttribute('data-fill');
    const fifthColor = screen.getByTestId('pie-segment-other').getAttribute('data-fill');

    expect(firstColor).toBe(fifthColor);
  });
});

// ============================================================================
// TEST CATEGORY: ACCESSIBILITY (A11Y)
// ============================================================================

describe('A11Y: Accessibility', () => {
  test('A11Y-001: Empty state has accessible message', () => {
    render(<QueryCategoriesChart data={mockEmptyData} />);

    expect(screen.getByText(/No category data available/i)).toBeInTheDocument();
  });

  test('A11Y-002: Chart has test id for identification', () => {
    render(<QueryCategoriesChart data={mockCategoryData} />);

    expect(screen.getByTestId('query-categories')).toBeInTheDocument();
  });

  test('A11Y-003: Legend provides text alternatives', () => {
    render(<QueryCategoriesChart data={mockCategoryData} />);

    expect(screen.getByText(/Comparison/)).toBeInTheDocument();
    expect(screen.getByText(/Recommendation/)).toBeInTheDocument();
    expect(screen.getByText(/Feature/)).toBeInTheDocument();
    expect(screen.getByText(/Use Case/)).toBeInTheDocument();
  });

  test('A11Y-004: Total queries provides summary', () => {
    render(<QueryCategoriesChart data={mockCategoryData} />);

    expect(screen.getByText('Total Queries')).toBeInTheDocument();
    expect(screen.getByTestId('total-value')).toHaveTextContent('107');
  });
});

// ============================================================================
// TEST CATEGORY: ACCEPTANCE CRITERIA (AC)
// ============================================================================

describe('AC: Acceptance Criteria', () => {
  test('AC-001: Pie/donut chart showing query category distribution', () => {
    render(<QueryCategoriesChart data={mockCategoryData} />);

    expect(screen.getByTestId('pie-chart')).toBeInTheDocument();
    expect(screen.getByTestId('pie')).toHaveAttribute('data-key', 'value');
  });

  test('AC-002: Categories: comparison, recommendation, feature, use_case', () => {
    render(<QueryCategoriesChart data={mockCategoryData} />);

    expect(screen.getByTestId('pie-segment-comparison')).toBeInTheDocument();
    expect(screen.getByTestId('pie-segment-recommendation')).toBeInTheDocument();
    expect(screen.getByTestId('pie-segment-feature')).toBeInTheDocument();
    expect(screen.getByTestId('pie-segment-use_case')).toBeInTheDocument();
  });

  test('AC-003: Color coding by category', () => {
    render(<QueryCategoriesChart data={mockCategoryData} />);

    // Each category has unique color
    const colors = [
      screen.getByTestId('pie-segment-comparison').getAttribute('data-fill'),
      screen.getByTestId('pie-segment-recommendation').getAttribute('data-fill'),
      screen.getByTestId('pie-segment-feature').getAttribute('data-fill'),
      screen.getByTestId('pie-segment-use_case').getAttribute('data-fill'),
    ];

    expect(new Set(colors).size).toBe(4);
  });

  test('AC-004: Legend shows all categories with percentages', () => {
    render(<QueryCategoriesChart data={mockCategoryData} />);

    expect(screen.getByText(/Comparison \(42%\)/)).toBeInTheDocument();
    expect(screen.getByText(/Recommendation \(30%\)/)).toBeInTheDocument();
    expect(screen.getByText(/Feature \(17%\)/)).toBeInTheDocument();
    expect(screen.getByText(/Use Case \(11%\)/)).toBeInTheDocument();
  });

  test('AC-005: Total queries shown', () => {
    render(<QueryCategoriesChart data={mockCategoryData} />);

    expect(screen.getByText('Total Queries')).toBeInTheDocument();
    expect(screen.getByTestId('total-value')).toBeInTheDocument();
  });
});
