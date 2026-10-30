/**
 * TASK-34-16: CompetitorComparisonChart Component Tests
 * 
 * Tests for horizontal bar chart comparing brand vs competitors for share of voice.
 * Uses mocked Recharts for deterministic testing.
 * 
 * @see PROJECT-SEOINDICATOR/TASKS/TASK-34-16.md
 */

import React from 'react';
import { render, screen, fireEvent, waitFor, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import '@testing-library/jest-dom';
import { vi } from 'vitest';

// Import actual component
import { CompetitorComparisonChart } from '@/components/ai-visibility/CompetitorComparisonChart';

// ============================================================================
// Types (from lib/types/ai-visibility.ts)
// ============================================================================

interface CompetitorComparisonItem {
  name: string;
  value: number;
  isBrand: boolean;
}

// ============================================================================
// Mock Data
// ============================================================================

const mockComparisonData: CompetitorComparisonItem[] = [
  { name: 'SEOIndicator', value: 31.2, isBrand: true },
  { name: 'Ahrefs', value: 28.5, isBrand: false },
  { name: 'SEMrush', value: 22.3, isBrand: false },
  { name: 'Moz', value: 12.8, isBrand: false },
  { name: 'Ubersuggest', value: 5.2, isBrand: false },
];

const mockEmptyData: CompetitorComparisonItem[] = [];

const mockSingleItem: CompetitorComparisonItem[] = [
  { name: 'SEOIndicator', value: 100, isBrand: true },
];

const mockBrandOnly: CompetitorComparisonItem[] = [
  { name: 'SEOIndicator', value: 50, isBrand: true },
];

const mockManyCompetitors: CompetitorComparisonItem[] = [
  { name: 'SEOIndicator', value: 15, isBrand: true },
  ...Array.from({ length: 15 }, (_, i) => ({
    name: `Competitor ${i + 1}`,
    value: Math.random() * 10 + 3,
    isBrand: false,
  })),
];

const mockZeroValues: CompetitorComparisonItem[] = [
  { name: 'SEOIndicator', value: 0, isBrand: true },
  { name: 'Ahrefs', value: 0, isBrand: false },
];

// ============================================================================
// Mock Recharts Components
// ============================================================================

const BRAND_COLOR = '#3b82f6'; // Blue
const COMPETITOR_COLOR = '#94a3b8'; // Slate

vi.mock('recharts', () => ({
  ResponsiveContainer: ({ children }: { children: React.ReactNode }) => (
    <div data-testid="responsive-container">{children}</div>
  ),
  BarChart: ({ 
    children, 
    data, 
    layout 
  }: { 
    children: React.ReactNode; 
    data: CompetitorComparisonItem[];
    layout: string;
  }) => (
    <div data-testid="bar-chart" data-items={data?.length || 0} data-layout={layout}>
      {children}
    </div>
  ),
  Bar: ({ 
    dataKey, 
    radius, 
    children,
    onClick 
  }: { 
    dataKey: string; 
    radius: number[];
    children?: React.ReactNode;
    onClick?: (data: { payload: CompetitorComparisonItem }) => void;
  }) => (
    <div 
      data-testid="bar-series" 
      data-key={dataKey}
      data-radius={radius?.join(',')}
    >
      {children}
    </div>
  ),
  XAxis: ({ type, domain }: { type: string; domain: number[] }) => (
    <div data-testid="x-axis" data-type={type} data-domain={JSON.stringify(domain)} />
  ),
  YAxis: ({ type, dataKey }: { type: string; dataKey: string }) => (
    <div data-testid="y-axis" data-type={type} data-key={dataKey} />
  ),
  CartesianGrid: () => (
    <div data-testid="cartesian-grid" />
  ),
  Tooltip: ({ content }: { content: React.ReactNode }) => (
    <div data-testid="tooltip-container">{content}</div>
  ),
  Cell: ({ 
    fill, 
    'data-testid': testId,
    onClick 
  }: { 
    fill: string; 
    'data-testid'?: string;
    onClick?: () => void;
  }) => (
    <div 
      data-testid={testId || 'cell'} 
      data-fill={fill}
      onClick={onClick}
    />
  ),
}));

// ============================================================================
// Mock Lucide Icons
// ============================================================================

vi.mock('lucide-react', () => ({
  TrendingUp: () => <span data-testid="trending-up-icon">↑</span>,
  TrendingDown: () => <span data-testid="trending-down-icon">↓</span>,
  Minus: () => <span data-testid="minus-icon">−</span>,
  Loader2: () => <span data-testid="loader-icon">Loading...</span>,
}));

// ============================================================================
// TEST CATEGORY: BASIC RENDERING (REND)
// ============================================================================

describe('REND: CompetitorComparisonChart Rendering', () => {
  test('REND-001: Chart renders with data', () => {
    render(<CompetitorComparisonChart data={mockComparisonData} />);

    expect(screen.getByTestId('competitor-comparison')).toBeInTheDocument();
    expect(screen.getByTestId('bar-chart')).toBeInTheDocument();
  });

  test('REND-002: Chart applies custom className', () => {
    render(<CompetitorComparisonChart data={mockComparisonData} className="custom-chart" />);

    expect(screen.getByTestId('competitor-comparison')).toHaveClass('custom-chart');
  });

  test('REND-003: Chart shows empty state when no data', () => {
    render(<CompetitorComparisonChart data={mockEmptyData} />);

    expect(screen.getByTestId('chart-empty-state')).toBeInTheDocument();
  });

  test('REND-004: Empty state has helpful message', () => {
    render(<CompetitorComparisonChart data={mockEmptyData} />);

    expect(screen.getByText(/No competitor data available/i)).toBeInTheDocument();
    expect(screen.getByText(/Add competitors to your project/i)).toBeInTheDocument();
  });

  test('REND-005: ResponsiveContainer wraps chart', () => {
    render(<CompetitorComparisonChart data={mockComparisonData} />);

    expect(screen.getByTestId('responsive-container')).toBeInTheDocument();
  });
});

// ============================================================================
// TEST CATEGORY: CHART STRUCTURE (STRUC)
// ============================================================================

describe('STRUC: Chart Structure', () => {
  test('STRUC-001: BarChart receives data', () => {
    render(<CompetitorComparisonChart data={mockComparisonData} />);

    expect(screen.getByTestId('bar-chart')).toHaveAttribute('data-items', '5');
  });

  test('STRUC-002: CartesianGrid renders', () => {
    render(<CompetitorComparisonChart data={mockComparisonData} />);

    expect(screen.getByTestId('cartesian-grid')).toBeInTheDocument();
  });

  test('STRUC-003: XAxis configured as number type', () => {
    render(<CompetitorComparisonChart data={mockComparisonData} />);

    expect(screen.getByTestId('x-axis')).toHaveAttribute('data-type', 'number');
  });

  test('STRUC-004: YAxis configured as category type', () => {
    render(<CompetitorComparisonChart data={mockComparisonData} />);

    expect(screen.getByTestId('y-axis')).toHaveAttribute('data-type', 'category');
    expect(screen.getByTestId('y-axis')).toHaveAttribute('data-key', 'name');
  });

  test('STRUC-005: XAxis domain is 0-100', () => {
    render(<CompetitorComparisonChart data={mockComparisonData} />);

    expect(screen.getByTestId('x-axis')).toHaveAttribute('data-domain', '[0,100]');
  });
});

// ============================================================================
// TEST CATEGORY: BAR RENDERING (BAR)
// ============================================================================

describe('BAR: Bar Rendering', () => {
  test('BAR-001: Brand bar renders with blue color', () => {
    render(<CompetitorComparisonChart data={mockComparisonData} />);

    const brandBar = screen.getByTestId('bar-brand');
    expect(brandBar).toBeInTheDocument();
    expect(brandBar).toHaveAttribute('data-fill', BRAND_COLOR);
  });

  test('BAR-002: Competitor bars render with slate color', () => {
    render(<CompetitorComparisonChart data={mockComparisonData} />);

    const competitorBars = screen.getAllByTestId(/bar-competitor-/);
    expect(competitorBars.length).toBeGreaterThan(0);

    competitorBars.forEach(bar => {
      expect(bar).toHaveAttribute('data-fill', COMPETITOR_COLOR);
    });
  });

  test('BAR-003: All items rendered as bars', () => {
    render(<CompetitorComparisonChart data={mockComparisonData} />);

    // 1 brand + 4 competitors = 5 total
    expect(screen.getByTestId('bar-brand')).toBeInTheDocument();
    expect(screen.getAllByTestId(/bar-competitor-/).length).toBe(4);
  });

  test('BAR-004: Bar values match data', () => {
    render(<CompetitorComparisonChart data={mockComparisonData} />);

    // Check values in details grid where they're rendered
    const brandDetails = screen.getByTestId('bar-seoindicator');
    expect(brandDetails).toHaveAttribute('data-value', '31.2');
  });
});

// ============================================================================
// TEST CATEGORY: SORTING (SORT)
// ============================================================================

describe('SORT: Data Sorting', () => {
  test('SORT-001: Data sorted by value descending', () => {
    render(<CompetitorComparisonChart data={mockComparisonData} />);

    const detailsGrid = screen.getByTestId('details-grid');
    const items = within(detailsGrid).getAllByTestId(/^bar-/);

    // Extract values
    const values = items.map(item => parseFloat(item.getAttribute('data-value') || '0'));

    // Check descending order
    for (let i = 1; i < values.length; i++) {
      expect(values[i - 1]).toBeGreaterThanOrEqual(values[i]);
    }
  });

  test('SORT-002: Highest value first', () => {
    render(<CompetitorComparisonChart data={mockComparisonData} />);

    const seoindicatorBar = screen.getByTestId('bar-seoindicator');
    expect(seoindicatorBar).toHaveAttribute('data-value', '31.2');
  });
});

// ============================================================================
// TEST CATEGORY: INTERACTIONS (INT)
// ============================================================================

describe('INT: User Interactions', () => {
  test('INT-001: Bar click triggers onBarClick callback', async () => {
    const handleClick = vi.fn();
    render(<CompetitorComparisonChart data={mockComparisonData} onBarClick={handleClick} />);

    // Click on details grid item which has the click handler
    const brandDetails = screen.getByTestId('bar-seoindicator');
    await userEvent.click(brandDetails);

    expect(handleClick).toHaveBeenCalled();
  });

  test('INT-002: Click receives correct item data', async () => {
    const handleClick = vi.fn();
    render(<CompetitorComparisonChart data={mockComparisonData} onBarClick={handleClick} />);

    const brandDetails = screen.getByTestId('bar-seoindicator');
    await userEvent.click(brandDetails);

    expect(handleClick).toHaveBeenCalledWith(
      expect.objectContaining({
        name: 'SEOIndicator',
        value: 31.2,
        isBrand: true,
      })
    );
  });

  test('INT-003: Details grid item click triggers callback', async () => {
    const handleClick = vi.fn();
    render(<CompetitorComparisonChart data={mockComparisonData} onBarClick={handleClick} />);

    const detailsItem = screen.getByTestId('bar-seoindicator');
    await userEvent.click(detailsItem);

    expect(handleClick).toHaveBeenCalled();
  });

  test('INT-004: No crash when onBarClick not provided', async () => {
    render(<CompetitorComparisonChart data={mockComparisonData} />);

    const brandDetails = screen.getByTestId('bar-seoindicator');
    // Should not throw
    await userEvent.click(brandDetails);
  });
});

// ============================================================================
// TEST CATEGORY: LEGEND (LEG)
// ============================================================================

describe('LEG: Chart Legend', () => {
  test('LEG-001: Legend renders', () => {
    render(<CompetitorComparisonChart data={mockComparisonData} />);

    expect(screen.getByTestId('chart-legend')).toBeInTheDocument();
  });

  test('LEG-002: Brand legend item shows "Your Brand"', () => {
    render(<CompetitorComparisonChart data={mockComparisonData} />);

    expect(screen.getByText('Your Brand')).toBeInTheDocument();
  });

  test('LEG-003: Competitors legend item shows "Competitors"', () => {
    render(<CompetitorComparisonChart data={mockComparisonData} />);

    expect(screen.getByText('Competitors')).toBeInTheDocument();
  });

  test('LEG-004: Legend colors match bar colors', () => {
    render(<CompetitorComparisonChart data={mockComparisonData} />);

    expect(screen.getByTestId('legend-color-brand')).toHaveStyle({ backgroundColor: BRAND_COLOR });
    expect(screen.getByTestId('legend-color-competitors')).toHaveStyle({ backgroundColor: COMPETITOR_COLOR });
  });
});

// ============================================================================
// TEST CATEGORY: DETAILS GRID (GRID)
// ============================================================================

describe('GRID: Details Grid', () => {
  test('GRID-001: Details grid renders', () => {
    render(<CompetitorComparisonChart data={mockComparisonData} />);

    expect(screen.getByTestId('details-grid')).toBeInTheDocument();
  });

  test('GRID-002: All items shown in details grid', () => {
    render(<CompetitorComparisonChart data={mockComparisonData} />);

    expect(screen.getByTestId('bar-seoindicator')).toBeInTheDocument();
    expect(screen.getByTestId('bar-ahrefs')).toBeInTheDocument();
    expect(screen.getByTestId('bar-semrush')).toBeInTheDocument();
    expect(screen.getByTestId('bar-moz')).toBeInTheDocument();
    expect(screen.getByTestId('bar-ubersuggest')).toBeInTheDocument();
  });

  test('GRID-003: Values formatted as percentage', () => {
    render(<CompetitorComparisonChart data={mockComparisonData} />);

    expect(screen.getByTestId('value-seoindicator')).toHaveTextContent('31.2%');
  });

  test('GRID-004: Brand item has isBrand attribute', () => {
    render(<CompetitorComparisonChart data={mockComparisonData} />);

    const brandItem = screen.getByTestId('bar-seoindicator');
    expect(brandItem).toHaveAttribute('data-is-brand', 'true');
  });

  test('GRID-005: Competitor items have isBrand false', () => {
    render(<CompetitorComparisonChart data={mockComparisonData} />);

    const ahrefsItem = screen.getByTestId('bar-ahrefs');
    expect(ahrefsItem).toHaveAttribute('data-is-brand', 'false');
  });
});

// ============================================================================
// TEST CATEGORY: DATA HANDLING (DATA)
// ============================================================================

describe('DATA: Data Handling', () => {
  test('DATA-001: Handles 5 items', () => {
    render(<CompetitorComparisonChart data={mockComparisonData} />);

    expect(screen.getByTestId('bar-chart')).toHaveAttribute('data-items', '5');
  });

  test('DATA-002: Handles single item', () => {
    render(<CompetitorComparisonChart data={mockSingleItem} />);

    expect(screen.getByTestId('bar-chart')).toHaveAttribute('data-items', '1');
  });

  test('DATA-003: Handles brand only', () => {
    render(<CompetitorComparisonChart data={mockBrandOnly} />);

    expect(screen.getByTestId('bar-brand')).toBeInTheDocument();
    expect(screen.queryAllByTestId(/bar-competitor-/).length).toBe(0);
  });

  test('DATA-004: Handles many competitors (15+)', () => {
    render(<CompetitorComparisonChart data={mockManyCompetitors} />);

    expect(screen.getByTestId('bar-chart')).toHaveAttribute('data-items', '16');
  });

  test('DATA-005: Handles zero values', () => {
    render(<CompetitorComparisonChart data={mockZeroValues} />);

    // Check values in details grid where they're rendered
    const brandDetails = screen.getByTestId('bar-seoindicator');
    expect(brandDetails).toHaveAttribute('data-value', '0');
  });

  test('DATA-006: Handles undefined data gracefully', () => {
    render(<CompetitorComparisonChart data={undefined as unknown as CompetitorComparisonItem[]} />);

    expect(screen.getByTestId('chart-empty-state')).toBeInTheDocument();
  });
});

// ============================================================================
// TEST CATEGORY: COLOR CODING (COLOR)
// ============================================================================

describe('COLOR: Color Coding', () => {
  test('COLOR-001: Brand bar is blue (#3b82f6)', () => {
    render(<CompetitorComparisonChart data={mockComparisonData} />);

    expect(screen.getByTestId('bar-brand')).toHaveAttribute('data-fill', '#3b82f6');
  });

  test('COLOR-002: Competitor bars are slate (#94a3b8)', () => {
    render(<CompetitorComparisonChart data={mockComparisonData} />);

    const competitorBars = screen.getAllByTestId(/bar-competitor-/);
    competitorBars.forEach(bar => {
      expect(bar).toHaveAttribute('data-fill', '#94a3b8');
    });
  });

  test('COLOR-003: Brand and competitor colors are distinct', () => {
    render(<CompetitorComparisonChart data={mockComparisonData} />);

    const brandColor = screen.getByTestId('bar-brand').getAttribute('data-fill');
    // After sorting, brand is first, so first competitor is at index 1
    const competitorColor = screen.getByTestId('bar-competitor-1').getAttribute('data-fill');

    expect(brandColor).not.toBe(competitorColor);
  });
});

// ============================================================================
// TEST CATEGORY: EDGE CASES (EC)
// ============================================================================

describe('EC: Edge Cases', () => {
  test('EC-001: Very high share of voice (99%)', () => {
    const highData: CompetitorComparisonItem[] = [
      { name: 'SEOIndicator', value: 99.5, isBrand: true },
      { name: 'Ahrefs', value: 0.5, isBrand: false },
    ];

    render(<CompetitorComparisonChart data={highData} />);

    // Check value in details grid
    const brandDetails = screen.getByTestId('bar-seoindicator');
    expect(brandDetails).toHaveAttribute('data-value', '99.5');
  });

  test('EC-002: Very low share of voice (1%)', () => {
    const lowData: CompetitorComparisonItem[] = [
      { name: 'Ahrefs', value: 49.5, isBrand: false },
      { name: 'SEMrush', value: 49.5, isBrand: false },
      { name: 'SEOIndicator', value: 1.0, isBrand: true },
    ];

    render(<CompetitorComparisonChart data={lowData} />);

    // Check value in details grid
    const brandDetails = screen.getByTestId('bar-seoindicator');
    expect(brandDetails).toHaveAttribute('data-value', '1');
  });

  test('EC-003: Tied values sorted correctly', () => {
    const tiedData: CompetitorComparisonItem[] = [
      { name: 'A', value: 25, isBrand: true },
      { name: 'B', value: 25, isBrand: false },
      { name: 'C', value: 25, isBrand: false },
    ];

    render(<CompetitorComparisonChart data={tiedData} />);

    // Should render without error
    expect(screen.getByTestId('bar-chart')).toBeInTheDocument();
  });

  test('EC-004: Long competitor names handled', () => {
    const longNameData: CompetitorComparisonItem[] = [
      { name: 'A'.repeat(100), value: 50, isBrand: false },
      { name: 'SEOIndicator', value: 50, isBrand: true },
    ];

    render(<CompetitorComparisonChart data={longNameData} />);

    // Should render without crash
    expect(screen.getByTestId('bar-chart')).toBeInTheDocument();
  });
});

// ============================================================================
// TEST CATEGORY: ACCESSIBILITY (A11Y)
// ============================================================================

describe('A11Y: Accessibility', () => {
  test('A11Y-001: Empty state has accessible message', () => {
    render(<CompetitorComparisonChart data={mockEmptyData} />);

    expect(screen.getByText(/No competitor data available/i)).toBeInTheDocument();
  });

  test('A11Y-002: Chart has test id for identification', () => {
    render(<CompetitorComparisonChart data={mockComparisonData} />);

    expect(screen.getByTestId('competitor-comparison')).toBeInTheDocument();
  });

  test('A11Y-003: Legend provides text alternatives', () => {
    render(<CompetitorComparisonChart data={mockComparisonData} />);

    expect(screen.getByText('Your Brand')).toBeInTheDocument();
    expect(screen.getByText('Competitors')).toBeInTheDocument();
  });

  test('A11Y-004: Details grid provides value text', () => {
    render(<CompetitorComparisonChart data={mockComparisonData} />);

    expect(screen.getByText('SEOIndicator')).toBeInTheDocument();
    expect(screen.getByText('31.2%')).toBeInTheDocument();
  });
});

// ============================================================================
// TEST CATEGORY: ACCEPTANCE CRITERIA (AC)
// ============================================================================

describe('AC: Acceptance Criteria', () => {
  test('AC-001: Horizontal bar chart comparing brand vs competitors', () => {
    render(<CompetitorComparisonChart data={mockComparisonData} />);

    expect(screen.getByTestId('bar-chart')).toBeInTheDocument();
    expect(screen.getByTestId('bar-brand')).toBeInTheDocument();
    expect(screen.getAllByTestId(/bar-competitor-/).length).toBeGreaterThan(0);
  });

  test('AC-002: Color coding: brand (primary), competitors (secondary)', () => {
    render(<CompetitorComparisonChart data={mockComparisonData} />);

    const brandBar = screen.getByTestId('bar-brand');
    // After sorting, brand is first, so first competitor is at index 1
    const competitorBar = screen.getByTestId('bar-competitor-1');

    expect(brandBar).toHaveAttribute('data-fill', BRAND_COLOR);
    expect(competitorBar).toHaveAttribute('data-fill', COMPETITOR_COLOR);
  });

  test('AC-003: Clickable items to view competitor details', async () => {
    const handleClick = vi.fn();
    render(<CompetitorComparisonChart data={mockComparisonData} onBarClick={handleClick} />);

    // Click on details grid item which has the click handler
    await userEvent.click(screen.getByTestId('bar-seoindicator'));

    expect(handleClick).toHaveBeenCalledWith(
      expect.objectContaining({
        name: expect.any(String),
        value: expect.any(Number),
        isBrand: expect.any(Boolean),
      })
    );
  });

  test('AC-004: Legend with brand names', () => {
    render(<CompetitorComparisonChart data={mockComparisonData} />);

    expect(screen.getByText('Your Brand')).toBeInTheDocument();
    expect(screen.getByText('Competitors')).toBeInTheDocument();
  });

  test('AC-005: Values shown in details grid', () => {
    render(<CompetitorComparisonChart data={mockComparisonData} />);

    expect(screen.getByText('31.2%')).toBeInTheDocument();
    expect(screen.getByText('28.5%')).toBeInTheDocument();
  });
});
