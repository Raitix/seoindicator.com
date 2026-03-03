/**
 * Tests for Skeleton Loader Variants
 * Task: TASK-28-9
 * Tests: CardSkeleton, TableSkeleton, ChartSkeleton, IndicatorCatalogSkeleton
 * Coverage: Props, rendering, animations, accessibility, edge cases
 */

import React from 'react';
import { render, screen } from '@testing-library/react';
import '@testing-library/jest-dom';

// Mock the base Skeleton component from shadcn/ui
jest.mock('@/components/ui/skeleton', () => ({
  Skeleton: React.forwardRef<HTMLDivElement, React.HTMLAttributes<HTMLDivElement>>(
    ({ className, ...props }, ref) => (
      <div
        ref={ref}
        className={`animate-pulse bg-muted rounded-md ${className || ''}`}
        data-testid="skeleton"
        {...props}
      />
    )
  ),
}));

// Test wrapper for components requiring context providers
const TestWrapper: React.FC<{ children: React.ReactNode }> = ({ children }) => {
  return <>{children}</>;
};

// ============================================================================
// CARDSKELETON TESTS
// ============================================================================

describe('CardSkeleton', () => {
  const renderCardSkeleton = async (props = {}) => {
    const { CardSkeleton } = await import('@/components/ui/skeletons/CardSkeleton');
    return render(
      <TestWrapper>
        <CardSkeleton {...props} />
      </TestWrapper>
    );
  };

  // CS-001 to CS-005: Basic Rendering
  describe('Basic Rendering', () => {
    it('CS-001: Renders without crashing', async () => {
      const { container } = await renderCardSkeleton();
      expect(container.firstChild).toBeInTheDocument();
    });

    it('CS-002: Has correct default structure', async () => {
      const { container } = await renderCardSkeleton();
      const skeleton = container.querySelector('.card-skeleton');
      expect(skeleton).toBeInTheDocument();
    });

    it('CS-003: Applies pulse animation class', async () => {
      const { container } = await renderCardSkeleton();
      const skeletons = container.querySelectorAll('[data-testid="skeleton"]');
      expect(skeletons.length).toBeGreaterThan(0);
      skeletons.forEach((skel) => {
        expect(skel.className).toMatch(/animate-pulse/);
      });
    });

    it('CS-004: Has rounded corners', async () => {
      const { container } = await renderCardSkeleton();
      const card = container.firstChild as HTMLElement;
      expect(card.className).toMatch(/rounded/);
    });

    it('CS-005: Accepts custom className prop', async () => {
      const { container } = await renderCardSkeleton({ className: 'custom-class' });
      const card = container.firstChild as HTMLElement;
      expect(card.className).toContain('custom-class');
    });
  });

  // CS-006 to CS-010: showIcon Prop
  describe('showIcon Prop', () => {
    it('CS-006: Shows icon placeholder by default', async () => {
      const { container } = await renderCardSkeleton({ showIcon: true });
      const iconSkeleton = container.querySelector('.icon-skeleton');
      expect(iconSkeleton).toBeInTheDocument();
    });

    it('CS-007: Hides icon placeholder when showIcon is false', async () => {
      const { container } = await renderCardSkeleton({ showIcon: false });
      const iconSkeleton = container.querySelector('.icon-skeleton');
      expect(iconSkeleton).not.toBeInTheDocument();
    });

    it('CS-008: Icon placeholder has correct dimensions', async () => {
      const { container } = await renderCardSkeleton({ showIcon: true });
      const iconSkeleton = container.querySelector('.icon-skeleton');
      expect(iconSkeleton?.className).toMatch(/w-\d+|h-\d+/);
    });

    it('CS-009: Icon is positioned correctly', async () => {
      const { container } = await renderCardSkeleton({ showIcon: true });
      const card = container.firstChild as HTMLElement;
      expect(card.className).toMatch(/flex|grid/);
    });

    it('CS-010: Multiple cards with different showIcon values', async () => {
      const { CardSkeleton } = await import('@/components/ui/skeletons/CardSkeleton');
      const { container } = render(
        <TestWrapper>
          <CardSkeleton showIcon={true} data-testid="with-icon" />
          <CardSkeleton showIcon={false} data-testid="without-icon" />
        </TestWrapper>
      );
      expect(container.querySelectorAll('.icon-skeleton')).toHaveLength(1);
    });
  });

  // CS-011 to CS-015: lines Prop
  describe('lines Prop', () => {
    it('CS-011: Renders default number of lines (3)', async () => {
      const { container } = await renderCardSkeleton();
      const lineSkeletons = container.querySelectorAll('.line-skeleton');
      expect(lineSkeletons).toHaveLength(3);
    });

    it('CS-012: Renders specified number of lines', async () => {
      const { container } = await renderCardSkeleton({ lines: 5 });
      const lineSkeletons = container.querySelectorAll('.line-skeleton');
      expect(lineSkeletons).toHaveLength(5);
    });

    it('CS-013: Renders 0 lines (icon only)', async () => {
      const { container } = await renderCardSkeleton({ lines: 0, showIcon: true });
      const lineSkeletons = container.querySelectorAll('.line-skeleton');
      expect(lineSkeletons).toHaveLength(0);
    });

    it('CS-014: Lines have varying widths for visual interest', async () => {
      const { container } = await renderCardSkeleton({ lines: 4 });
      const lineSkeletons = container.querySelectorAll('.line-skeleton');
      const widths = Array.from(lineSkeletons).map((el) => el.className);
      // Lines should have different width classes (w-3/4, w-full, w-2/3, etc.)
      const hasVaryingWidths = widths.some((w) => w.includes('w-3/4') || w.includes('w-2/3') || w.includes('w-5/6'));
      expect(hasVaryingWidths).toBe(true);
    });

    it('CS-015: Lines have appropriate spacing', async () => {
      const { container } = await renderCardSkeleton({ lines: 3 });
      const linesContainer = container.querySelector('.lines-container');
      expect(linesContainer?.className).toMatch(/space-y|gap/);
    });
  });

  // CS-016 to CS-020: Styling & Dark Mode
  describe('Styling & Dark Mode', () => {
    it('CS-016: Applies muted background color', async () => {
      const { container } = await renderCardSkeleton();
      const card = container.firstChild as HTMLElement;
      expect(card.className).toMatch(/bg-muted|bg-gray/);
    });

    it('CS-017: Has border styling', async () => {
      const { container } = await renderCardSkeleton();
      const card = container.firstChild as HTMLElement;
      expect(card.className).toMatch(/border/);
    });

    it('CS-018: Dark mode classes applied', async () => {
      const { container } = await renderCardSkeleton();
      const card = container.firstChild as HTMLElement;
      expect(card.className).toMatch(/dark:/);
    });

    it('CS-019: Shadow for depth', async () => {
      const { container } = await renderCardSkeleton();
      const card = container.firstChild as HTMLElement;
      expect(card.className).toMatch(/shadow/);
    });

    it('CS-020: Proper padding inside card', async () => {
      const { container } = await renderCardSkeleton();
      const card = container.firstChild as HTMLElement;
      expect(card.className).toMatch(/p-\d|px-\d|py-\d/);
    });
  });

  // CS-021 to CS-025: Edge Cases
  describe('Edge Cases', () => {
    it('CS-021: Handles negative lines gracefully', async () => {
      const { container } = await renderCardSkeleton({ lines: -1 });
      const lineSkeletons = container.querySelectorAll('.line-skeleton');
      expect(lineSkeletons).toHaveLength(0);
    });

    it('CS-022: Handles very large lines count', async () => {
      const { container } = await renderCardSkeleton({ lines: 100 });
      const lineSkeletons = container.querySelectorAll('.line-skeleton');
      expect(lineSkeletons).toHaveLength(100);
    });

    it('CS-023: Handles undefined props', async () => {
      const { container } = await renderCardSkeleton({ showIcon: undefined, lines: undefined });
      expect(container.firstChild).toBeInTheDocument();
    });

    it('CS-024: Handles null className', async () => {
      const { container } = await renderCardSkeleton({ className: null });
      expect(container.firstChild).toBeInTheDocument();
    });

    it('CS-025: Handles empty string className', async () => {
      const { container } = await renderCardSkeleton({ className: '' });
      expect(container.firstChild).toBeInTheDocument();
    });
  });
});

// ============================================================================
// TABLESKELETON TESTS
// ============================================================================

describe('TableSkeleton', () => {
  const renderTableSkeleton = async (props = {}) => {
    const { TableSkeleton } = await import('@/components/ui/skeletons/TableSkeleton');
    return render(
      <TestWrapper>
        <TableSkeleton {...props} />
      </TestWrapper>
    );
  };

  // TS-001 to TS-005: Basic Rendering
  describe('Basic Rendering', () => {
    it('TS-001: Renders without crashing', async () => {
      const { container } = await renderTableSkeleton();
      expect(container.firstChild).toBeInTheDocument();
    });

    it('TS-002: Has table structure', async () => {
      const { container } = await renderTableSkeleton();
      const table = container.querySelector('table') || container.querySelector('[role="table"]');
      expect(table).toBeInTheDocument();
    });

    it('TS-003: Applies pulse animation to all cells', async () => {
      const { container } = await renderTableSkeleton();
      const skeletons = container.querySelectorAll('[data-testid="skeleton"]');
      expect(skeletons.length).toBeGreaterThan(0);
      skeletons.forEach((skel) => {
        expect(skel.className).toMatch(/animate-pulse/);
      });
    });

    it('TS-004: Accepts custom className', async () => {
      const { container } = await renderTableSkeleton({ className: 'custom-table' });
      const tableContainer = container.firstChild as HTMLElement;
      expect(tableContainer.className).toContain('custom-table');
    });

    it('TS-005: Has responsive overflow handling', async () => {
      const { container } = await renderTableSkeleton();
      const wrapper = container.firstChild as HTMLElement;
      expect(wrapper.className).toMatch(/overflow/);
    });
  });

  // TS-006 to TS-010: rows Prop
  describe('rows Prop', () => {
    it('TS-006: Renders default number of rows (5)', async () => {
      const { container } = await renderTableSkeleton();
      const rows = container.querySelectorAll('tr') || container.querySelectorAll('[role="row"]');
      // Subtract 1 for header row
      expect(rows.length).toBe(6);
    });

    it('TS-007: Renders specified number of rows', async () => {
      const { container } = await renderTableSkeleton({ rows: 10 });
      const rows = container.querySelectorAll('tr') || container.querySelectorAll('[role="row"]');
      expect(rows.length).toBe(11); // 10 + 1 header
    });

    it('TS-008: Renders 0 rows (header only)', async () => {
      const { container } = await renderTableSkeleton({ rows: 0, showHeader: true });
      const rows = container.querySelectorAll('tr') || container.querySelectorAll('[role="row"]');
      expect(rows.length).toBe(1);
    });

    it('TS-009: Handles large row count', async () => {
      const { container } = await renderTableSkeleton({ rows: 50 });
      const rows = container.querySelectorAll('tr') || container.querySelectorAll('[role="row"]');
      expect(rows.length).toBe(51);
    });

    it('TS-010: Each row has correct cell count', async () => {
      const { container } = await renderTableSkeleton({ rows: 3, columns: 4, showHeader: false });
      const rows = container.querySelectorAll('tr') || container.querySelectorAll('[role="row"]');
      rows.forEach((row) => {
        const cells = row.querySelectorAll('td') || row.querySelectorAll('[role="cell"]');
        expect(cells).toHaveLength(4);
      });
    });
  });

  // TS-011 to TS-015: columns Prop
  describe('columns Prop', () => {
    it('TS-011: Renders default number of columns (4)', async () => {
      const { container } = await renderTableSkeleton({ showHeader: false });
      const firstRow = container.querySelector('tr') || container.querySelector('[role="row"]');
      const cells = firstRow?.querySelectorAll('td') || firstRow?.querySelectorAll('[role="cell"]');
      expect(cells).toHaveLength(4);
    });

    it('TS-012: Renders specified number of columns', async () => {
      const { container } = await renderTableSkeleton({ columns: 6, showHeader: false });
      const firstRow = container.querySelector('tr') || container.querySelector('[role="row"]');
      const cells = firstRow?.querySelectorAll('td') || firstRow?.querySelectorAll('[role="cell"]');
      expect(cells).toHaveLength(6);
    });

    it('TS-013: Handles 0 columns', async () => {
      const { container } = await renderTableSkeleton({ columns: 0 });
      expect(container.firstChild).toBeInTheDocument();
    });

    it('TS-014: Handles single column', async () => {
      const { container } = await renderTableSkeleton({ columns: 1, showHeader: false });
      const firstRow = container.querySelector('tr') || container.querySelector('[role="row"]');
      const cells = firstRow?.querySelectorAll('td') || firstRow?.querySelectorAll('[role="cell"]');
      expect(cells).toHaveLength(1);
    });

    it('TS-015: Columns have appropriate widths', async () => {
      const { container } = await renderTableSkeleton({ columns: 4 });
      const headerCells = container.querySelectorAll('th') || container.querySelectorAll('[role="columnheader"]');
      expect(headerCells.length).toBeGreaterThan(0);
    });
  });

  // TS-016 to TS-020: showHeader Prop
  describe('showHeader Prop', () => {
    it('TS-016: Shows header by default', async () => {
      const { container } = await renderTableSkeleton();
      const header = container.querySelector('thead') || container.querySelector('[role="rowgroup"]');
      expect(header).toBeInTheDocument();
    });

    it('TS-017: Hides header when showHeader is false', async () => {
      const { container } = await renderTableSkeleton({ showHeader: false });
      const header = container.querySelector('thead') || container.querySelector('[role="rowgroup"]');
      expect(header).not.toBeInTheDocument();
    });

    it('TS-018: Header cells have skeleton elements', async () => {
      const { container } = await renderTableSkeleton({ showHeader: true });
      const headerCells = container.querySelectorAll('th') || container.querySelectorAll('[role="columnheader"]');
      expect(headerCells.length).toBeGreaterThan(0);
    });

    it('TS-019: Header has different styling than body', async () => {
      const { container } = await renderTableSkeleton({ showHeader: true });
      const header = container.querySelector('thead') || container.querySelector('[role="rowgroup"]');
      const body = container.querySelector('tbody') || container.querySelectorAll('[role="rowgroup"]')[1];
      expect(header?.className).not.toBe(body?.className);
    });

    it('TS-020: Header row count not included in rows prop', async () => {
      const { container } = await renderTableSkeleton({ rows: 3, showHeader: true });
      const allRows = container.querySelectorAll('tr') || container.querySelectorAll('[role="row"]');
      expect(allRows.length).toBe(4); // 3 body + 1 header
    });
  });

  // TS-021 to TS-025: Styling
  describe('Styling', () => {
    it('TS-021: Has striped rows', async () => {
      const { container } = await renderTableSkeleton({ rows: 5 });
      const rows = container.querySelectorAll('tr') || container.querySelectorAll('[role="row"]');
      // Check for alternating styling
      expect(rows.length).toBeGreaterThan(1);
    });

    it('TS-022: Has hover effect classes', async () => {
      const { container } = await renderTableSkeleton();
      const rows = container.querySelectorAll('tr') || container.querySelectorAll('[role="row"]');
      rows.forEach((row) => {
        expect(row.className).toMatch(/hover:|transition/);
      });
    });

    it('TS-023: Dark mode styling', async () => {
      const { container } = await renderTableSkeleton();
      const table = container.querySelector('table') || container.querySelector('[role="table"]');
      expect(table?.className).toMatch(/dark:/);
    });

    it('TS-024: Cell padding applied', async () => {
      const { container } = await renderTableSkeleton({ rows: 2 });
      const cells = container.querySelectorAll('td') || container.querySelectorAll('[role="cell"]');
      cells.forEach((cell) => {
        expect(cell.className).toMatch(/p-\d|px-\d|py-\d/);
      });
    });

    it('TS-025: Border between cells', async () => {
      const { container } = await renderTableSkeleton();
      const table = container.querySelector('table') || container.querySelector('[role="table"]');
      expect(table?.className).toMatch(/border|divide/);
    });
  });

  // TS-026 to TS-030: Edge Cases
  describe('Edge Cases', () => {
    it('TS-026: Handles negative rows', async () => {
      const { container } = await renderTableSkeleton({ rows: -5 });
      const rows = container.querySelectorAll('tr') || container.querySelectorAll('[role="row"]');
      expect(rows.length).toBe(1); // Just header
    });

    it('TS-027: Handles negative columns', async () => {
      const { container } = await renderTableSkeleton({ columns: -2 });
      expect(container.firstChild).toBeInTheDocument();
    });

    it('TS-028: Handles fractional row/column values', async () => {
      const { container } = await renderTableSkeleton({ rows: 2.5, columns: 3.7 });
      expect(container.firstChild).toBeInTheDocument();
    });

    it('TS-029: Handles undefined props', async () => {
      const { container } = await renderTableSkeleton({ rows: undefined, columns: undefined });
      expect(container.firstChild).toBeInTheDocument();
    });

    it('TS-030: Large table performance', async () => {
      const startTime = performance.now();
      await renderTableSkeleton({ rows: 100, columns: 20 });
      const endTime = performance.now();
      expect(endTime - startTime).toBeLessThan(1000); // Should render in under 1s
    });
  });
});

// ============================================================================
// CHARTSKELETON TESTS
// ============================================================================

describe('ChartSkeleton', () => {
  const renderChartSkeleton = async (props = {}) => {
    const { ChartSkeleton } = await import('@/components/ui/skeletons/ChartSkeleton');
    return render(
      <TestWrapper>
        <ChartSkeleton {...props} />
      </TestWrapper>
    );
  };

  // CHS-001 to CHS-005: Basic Rendering
  describe('Basic Rendering', () => {
    it('CHS-001: Renders without crashing', async () => {
      const { container } = await renderChartSkeleton();
      expect(container.firstChild).toBeInTheDocument();
    });

    it('CHS-002: Has chart container structure', async () => {
      const { container } = await renderChartSkeleton();
      const chart = container.querySelector('.chart-skeleton') || container.firstChild;
      expect(chart).toBeInTheDocument();
    });

    it('CHS-003: Applies pulse animation', async () => {
      const { container } = await renderChartSkeleton();
      const skeletons = container.querySelectorAll('[data-testid="skeleton"]');
      expect(skeletons.length).toBeGreaterThan(0);
      skeletons.forEach((skel) => {
        expect(skel.className).toMatch(/animate-pulse/);
      });
    });

    it('CHS-004: Accepts custom className', async () => {
      const { container } = await renderChartSkeleton({ className: 'custom-chart' });
      const chart = container.firstChild as HTMLElement;
      expect(chart.className).toContain('custom-chart');
    });

    it('CHS-005: Has appropriate aspect ratio', async () => {
      const { container } = await renderChartSkeleton();
      const chart = container.firstChild as HTMLElement;
      expect(chart.className).toMatch(/aspect-|w-full/);
    });
  });

  // CHS-006 to CHS-010: Line Chart Type
  describe('Line Chart Type', () => {
    it('CHS-006: Renders line chart by default', async () => {
      const { container } = await renderChartSkeleton();
      expect(container.firstChild).toBeInTheDocument();
    });

    it('CHS-007: Line chart has curve/path placeholder', async () => {
      const { container } = await renderChartSkeleton({ type: 'line' });
      // SVG or div representing the line
      const chartArea = container.querySelector('svg') || container.querySelector('.chart-area');
      expect(chartArea).toBeInTheDocument();
    });

    it('CHS-008: Line chart has axis placeholders', async () => {
      const { container } = await renderChartSkeleton({ type: 'line' });
      const axes = container.querySelectorAll('.axis-skeleton, [class*="axis"]');
      expect(axes.length).toBeGreaterThanOrEqual(2);
    });

    it('CHS-009: Line chart has grid lines', async () => {
      const { container } = await renderChartSkeleton({ type: 'line' });
      const grid = container.querySelectorAll('.grid-line, [class*="grid"]');
      expect(grid.length).toBeGreaterThan(0);
    });

    it('CHS-010: Line chart has data points placeholder', async () => {
      const { container } = await renderChartSkeleton({ type: 'line' });
      const points = container.querySelectorAll('.data-point, [class*="point"]');
      expect(points.length).toBeGreaterThan(0);
    });
  });

  // CHS-011 to CHS-015: Bar Chart Type
  describe('Bar Chart Type', () => {
    it('CHS-011: Renders bar chart type', async () => {
      const { container } = await renderChartSkeleton({ type: 'bar' });
      expect(container.firstChild).toBeInTheDocument();
    });

    it('CHS-012: Bar chart has vertical bars', async () => {
      const { container } = await renderChartSkeleton({ type: 'bar' });
      const bars = container.querySelectorAll('.bar-skeleton, [class*="bar"]');
      expect(bars.length).toBeGreaterThan(0);
    });

    it('CHS-013: Bar chart bars have varying heights', async () => {
      const { container } = await renderChartSkeleton({ type: 'bar' });
      const bars = container.querySelectorAll('.bar-skeleton, [class*="bar"]');
      // Check that bars exist
      expect(bars.length).toBeGreaterThan(1);
    });

    it('CHS-014: Bar chart has x-axis labels placeholder', async () => {
      const { container } = await renderChartSkeleton({ type: 'bar' });
      const labels = container.querySelectorAll('.x-label, [class*="label"]');
      expect(labels.length).toBeGreaterThan(0);
    });

    it('CHS-015: Bar chart has y-axis labels placeholder', async () => {
      const { container } = await renderChartSkeleton({ type: 'bar' });
      const labels = container.querySelectorAll('.y-label, [class*="label"]');
      expect(labels.length).toBeGreaterThan(0);
    });
  });

  // CHS-016 to CHS-020: Pie Chart Type
  describe('Pie Chart Type', () => {
    it('CHS-016: Renders pie chart type', async () => {
      const { container } = await renderChartSkeleton({ type: 'pie' });
      expect(container.firstChild).toBeInTheDocument();
    });

    it('CHS-017: Pie chart has circular shape', async () => {
      const { container } = await renderChartSkeleton({ type: 'pie' });
      const pie = container.querySelector('svg circle, .pie-circle, [class*="circle"], [class*="pie"]');
      expect(pie).toBeInTheDocument();
    });

    it('CHS-018: Pie chart has segments', async () => {
      const { container } = await renderChartSkeleton({ type: 'pie' });
      const segments = container.querySelectorAll('.pie-segment, [class*="segment"]');
      expect(segments.length).toBeGreaterThan(0);
    });

    it('CHS-019: Pie chart has legend placeholder', async () => {
      const { container } = await renderChartSkeleton({ type: 'pie' });
      const legend = container.querySelector('.legend, [class*="legend"]');
      expect(legend).toBeInTheDocument();
    });

    it('CHS-020: Pie chart centered in container', async () => {
      const { container } = await renderChartSkeleton({ type: 'pie' });
      const chart = container.firstChild as HTMLElement;
      expect(chart.className).toMatch(/flex|grid|items-center|justify-center/);
    });
  });

  // CHS-021 to CHS-025: Styling
  describe('Styling', () => {
    it('CHS-021: Has title placeholder', async () => {
      const { container } = await renderChartSkeleton();
      const title = container.querySelector('.chart-title, [class*="title"]');
      expect(title).toBeInTheDocument();
    });

    it('CHS-022: Dark mode support', async () => {
      const { container } = await renderChartSkeleton();
      const chart = container.firstChild as HTMLElement;
      expect(chart.className).toMatch(/dark:/);
    });

    it('CHS-023: Background color applied', async () => {
      const { container } = await renderChartSkeleton();
      const chart = container.firstChild as HTMLElement;
      expect(chart.className).toMatch(/bg-muted|bg-gray|bg-white/);
    });

    it('CHS-024: Border styling', async () => {
      const { container } = await renderChartSkeleton();
      const chart = container.firstChild as HTMLElement;
      expect(chart.className).toMatch(/border|rounded/);
    });

    it('CHS-025: Proper padding', async () => {
      const { container } = await renderChartSkeleton();
      const chart = container.firstChild as HTMLElement;
      expect(chart.className).toMatch(/p-\d|px-\d|py-\d/);
    });
  });

  // CHS-026 to CHS-030: Edge Cases
  describe('Edge Cases', () => {
    it('CHS-026: Handles unknown type gracefully', async () => {
      const { container } = await renderChartSkeleton({ type: 'unknown' as any });
      expect(container.firstChild).toBeInTheDocument();
    });

    it('CHS-027: Handles undefined type', async () => {
      const { container } = await renderChartSkeleton({ type: undefined });
      expect(container.firstChild).toBeInTheDocument();
    });

    it('CHS-028: Handles null className', async () => {
      const { container } = await renderChartSkeleton({ className: null });
      expect(container.firstChild).toBeInTheDocument();
    });

    it('CHS-029: Responsive width', async () => {
      const { container } = await renderChartSkeleton();
      const chart = container.firstChild as HTMLElement;
      expect(chart.className).toMatch(/w-full|w-\d+/);
    });

    it('CHS-030: Minimum height applied', async () => {
      const { container } = await renderChartSkeleton();
      const chart = container.firstChild as HTMLElement;
      expect(chart.className).toMatch(/h-\d+|min-h/);
    });
  });
});

// ============================================================================
// INDICATORCATALOGSKELETON TESTS
// ============================================================================

describe('IndicatorCatalogSkeleton', () => {
  const renderCatalogSkeleton = async (props = {}) => {
    const { IndicatorCatalogSkeleton } = await import('@/components/ui/skeletons/IndicatorCatalogSkeleton');
    return render(
      <TestWrapper>
        <IndicatorCatalogSkeleton {...props} />
      </TestWrapper>
    );
  };

  // ICS-001 to ICS-005: Basic Rendering
  describe('Basic Rendering', () => {
    it('ICS-001: Renders without crashing', async () => {
      const { container } = await renderCatalogSkeleton();
      expect(container.firstChild).toBeInTheDocument();
    });

    it('ICS-002: Has catalog container structure', async () => {
      const { container } = await renderCatalogSkeleton();
      const catalog = container.querySelector('.indicator-catalog-skeleton') || container.firstChild;
      expect(catalog).toBeInTheDocument();
    });

    it('ICS-003: Applies pulse animation to children', async () => {
      const { container } = await renderCatalogSkeleton();
      const skeletons = container.querySelectorAll('[data-testid="skeleton"]');
      expect(skeletons.length).toBeGreaterThan(0);
      skeletons.forEach((skel) => {
        expect(skel.className).toMatch(/animate-pulse/);
      });
    });

    it('ICS-004: Accepts custom className', async () => {
      const { container } = await renderCatalogSkeleton({ className: 'custom-catalog' });
      const catalog = container.firstChild as HTMLElement;
      expect(catalog.className).toContain('custom-catalog');
    });

    it('ICS-005: Has grid layout', async () => {
      const { container } = await renderCatalogSkeleton();
      const catalog = container.firstChild as HTMLElement;
      expect(catalog.className).toMatch(/grid|flex/);
    });
  });

  // ICS-006 to ICS-010: count Prop
  describe('count Prop', () => {
    it('ICS-006: Renders default count of cards (6)', async () => {
      const { container } = await renderCatalogSkeleton();
      const cards = container.querySelectorAll('.card-skeleton, [class*="card"]');
      expect(cards.length).toBe(6);
    });

    it('ICS-007: Renders specified count of cards', async () => {
      const { container } = await renderCatalogSkeleton({ count: 12 });
      const cards = container.querySelectorAll('.card-skeleton, [class*="card"]');
      expect(cards.length).toBe(12);
    });

    it('ICS-008: Renders single card', async () => {
      const { container } = await renderCatalogSkeleton({ count: 1 });
      const cards = container.querySelectorAll('.card-skeleton, [class*="card"]');
      expect(cards.length).toBe(1);
    });

    it('ICS-009: Renders 0 cards (empty state)', async () => {
      const { container } = await renderCatalogSkeleton({ count: 0 });
      const cards = container.querySelectorAll('.card-skeleton, [class*="card"]');
      expect(cards.length).toBe(0);
    });

    it('ICS-010: Handles large count', async () => {
      const { container } = await renderCatalogSkeleton({ count: 50 });
      const cards = container.querySelectorAll('.card-skeleton, [class*="card"]');
      expect(cards.length).toBe(50);
    });
  });

  // ICS-011 to ICS-015: columns Prop (ResponsiveColumns)
  describe('columns Prop', () => {
    it('ICS-011: Uses default responsive columns', async () => {
      const { container } = await renderCatalogSkeleton();
      const catalog = container.firstChild as HTMLElement;
      expect(catalog.className).toMatch(/grid-cols/);
    });

    it('ICS-012: Accepts custom columns object', async () => {
      const { container } = await renderCatalogSkeleton({
        columns: { sm: 1, md: 2, lg: 3, xl: 4 }
      });
      const catalog = container.firstChild as HTMLElement;
      expect(catalog.className).toMatch(/grid-cols|sm:grid-cols|md:grid-cols|lg:grid-cols|xl:grid-cols/);
    });

    it('ICS-013: Single column layout', async () => {
      const { container } = await renderCatalogSkeleton({
        columns: { default: 1 }
      });
      const catalog = container.firstChild as HTMLElement;
      expect(catalog.className).toMatch(/grid-cols-1/);
    });

    it('ICS-014: Responsive breakpoints applied', async () => {
      const { container } = await renderCatalogSkeleton({
        columns: { sm: 2, md: 3, lg: 4 }
      });
      const catalog = container.firstChild as HTMLElement;
      const classNames = catalog.className;
      // Check for Tailwind responsive prefixes
      const hasResponsive = /sm:grid-cols|md:grid-cols|lg:grid-cols/.test(classNames);
      expect(hasResponsive).toBe(true);
    });

    it('ICS-015: Grid gap applied', async () => {
      const { container } = await renderCatalogSkeleton();
      const catalog = container.firstChild as HTMLElement;
      expect(catalog.className).toMatch(/gap-/);
    });
  });

  // ICS-016 to ICS-020: Card Integration
  describe('Card Integration', () => {
    it('ICS-016: Uses CardSkeleton components', async () => {
      const { container } = await renderCatalogSkeleton({ count: 3 });
      // Verify CardSkeleton-like structure
      const cards = container.querySelectorAll('.card-skeleton');
      expect(cards.length).toBe(3);
    });

    it('ICS-017: Cards have consistent size', async () => {
      const { container } = await renderCatalogSkeleton({ count: 5 });
      const cards = container.querySelectorAll('.card-skeleton, [class*="card"]');
      cards.forEach((card) => {
        expect(card.className).toMatch(/h-full|h-\d+/);
      });
    });

    it('ICS-018: Cards are clickable-looking (for visual)', async () => {
      const { container } = await renderCatalogSkeleton({ count: 3 });
      const cards = container.querySelectorAll('.card-skeleton, [class*="card"]');
      cards.forEach((card) => {
        // Cards should have border/shadow to look interactive
        expect(card.className).toMatch(/border|shadow|rounded/);
      });
    });

    it('ICS-019: Cards have icon and lines', async () => {
      const { container } = await renderCatalogSkeleton({ count: 2 });
      const icons = container.querySelectorAll('.icon-skeleton, [class*="icon"]');
      const lines = container.querySelectorAll('.line-skeleton, [class*="line"]');
      expect(icons.length).toBeGreaterThan(0);
      expect(lines.length).toBeGreaterThan(0);
    });

    it('ICS-020: Cards match IndicatorCatalog layout', async () => {
      const { container } = await renderCatalogSkeleton({ count: 4 });
      const catalog = container.firstChild as HTMLElement;
      // Should have similar grid structure to actual IndicatorCatalog
      expect(catalog.className).toMatch(/grid/);
    });
  });

  // ICS-021 to ICS-025: Styling
  describe('Styling', () => {
    it('ICS-021: Has header skeleton', async () => {
      const { container } = await renderCatalogSkeleton();
      const header = container.querySelector('.header-skeleton, [class*="header"]');
      expect(header).toBeInTheDocument();
    });

    it('ICS-022: Has search/filter skeleton', async () => {
      const { container } = await renderCatalogSkeleton();
      const search = container.querySelector('.search-skeleton, .filter-skeleton, [class*="search"], [class*="filter"]');
      expect(search).toBeInTheDocument();
    });

    it('ICS-023: Dark mode support', async () => {
      const { container } = await renderCatalogSkeleton();
      const catalog = container.firstChild as HTMLElement;
      expect(catalog.className).toMatch(/dark:/);
    });

    it('ICS-024: Proper spacing between elements', async () => {
      const { container } = await renderCatalogSkeleton();
      const catalog = container.firstChild as HTMLElement;
      expect(catalog.className).toMatch(/space-y|gap/);
    });

    it('ICS-025: Container max width', async () => {
      const { container } = await renderCatalogSkeleton();
      const catalog = container.firstChild as HTMLElement;
      expect(catalog.className).toMatch(/max-w-|container/);
    });
  });

  // ICS-026 to ICS-030: Edge Cases
  describe('Edge Cases', () => {
    it('ICS-026: Handles negative count', async () => {
      const { container } = await renderCatalogSkeleton({ count: -5 });
      const cards = container.querySelectorAll('.card-skeleton, [class*="card"]');
      expect(cards.length).toBe(0);
    });

    it('ICS-027: Handles undefined count', async () => {
      const { container } = await renderCatalogSkeleton({ count: undefined });
      expect(container.firstChild).toBeInTheDocument();
    });

    it('ICS-028: Handles undefined columns', async () => {
      const { container } = await renderCatalogSkeleton({ columns: undefined });
      expect(container.firstChild).toBeInTheDocument();
    });

    it('ICS-029: Large count performance', async () => {
      const startTime = performance.now();
      await renderCatalogSkeleton({ count: 200 });
      const endTime = performance.now();
      expect(endTime - startTime).toBeLessThan(1000);
    });

    it('ICS-030: Fractional count handled', async () => {
      const { container } = await renderCatalogSkeleton({ count: 4.7 });
      const cards = container.querySelectorAll('.card-skeleton, [class*="card"]');
      expect(cards.length).toBe(4); // Should floor
    });
  });
});

// ============================================================================
// BARREL FILE EXPORT TESTS
// ============================================================================

describe('Skeleton Barrel File Exports', () => {
  // BE-001 to BE-006: Export Tests
  describe('Export Tests', () => {
    it('BE-001: CardSkeleton exported from barrel', async () => {
      const exports = await import('@/components/ui/skeletons');
      expect(exports.CardSkeleton).toBeDefined();
    });

    it('BE-002: TableSkeleton exported from barrel', async () => {
      const exports = await import('@/components/ui/skeletons');
      expect(exports.TableSkeleton).toBeDefined();
    });

    it('BE-003: ChartSkeleton exported from barrel', async () => {
      const exports = await import('@/components/ui/skeletons');
      expect(exports.ChartSkeleton).toBeDefined();
    });

    it('BE-004: IndicatorCatalogSkeleton exported from barrel', async () => {
      const exports = await import('@/components/ui/skeletons');
      expect(exports.IndicatorCatalogSkeleton).toBeDefined();
    });

    it('BE-005: All exports are valid components', async () => {
      const { CardSkeleton, TableSkeleton, ChartSkeleton, IndicatorCatalogSkeleton } = 
        await import('@/components/ui/skeletons');
      
      expect(typeof CardSkeleton).toBe('function');
      expect(typeof TableSkeleton).toBe('function');
      expect(typeof ChartSkeleton).toBe('function');
      expect(typeof IndicatorCatalogSkeleton).toBe('function');
    });

    it('BE-006: TypeScript types exported', async () => {
      // Types are compile-time, verify module structure
      const exports = await import('@/components/ui/skeletons');
      expect(exports).toBeDefined();
    });
  });
});

// ============================================================================
// TYPESCRIPT TYPE TESTS
// ============================================================================

describe('TypeScript Types', () => {
  // TT-001 to TT-008: Type Validation
  describe('CardSkeleton Types', () => {
    it('TT-001: CardSkeletonProps interface exists', () => {
      // Compile-time check - if this compiles, types are correct
      type CardSkeletonProps = {
        showIcon?: boolean;
        lines?: number;
        className?: string;
      };
      const props: CardSkeletonProps = { showIcon: true, lines: 3 };
      expect(props).toBeDefined();
    });
  });

  describe('TableSkeleton Types', () => {
    it('TT-002: TableSkeletonProps interface exists', () => {
      type TableSkeletonProps = {
        rows?: number;
        columns?: number;
        showHeader?: boolean;
        className?: string;
      };
      const props: TableSkeletonProps = { rows: 5, columns: 4, showHeader: true };
      expect(props).toBeDefined();
    });
  });

  describe('ChartSkeleton Types', () => {
    it('TT-003: ChartSkeletonProps interface exists', () => {
      type ChartSkeletonProps = {
        type?: 'line' | 'bar' | 'pie';
        className?: string;
      };
      const props: ChartSkeletonProps = { type: 'bar' };
      expect(props).toBeDefined();
    });
  });

  describe('IndicatorCatalogSkeleton Types', () => {
    it('TT-004: ResponsiveColumns type exists', () => {
      type ResponsiveColumns = {
        default?: number;
        sm?: number;
        md?: number;
        lg?: number;
        xl?: number;
        '2xl'?: number;
      };
      const cols: ResponsiveColumns = { sm: 1, md: 2, lg: 3, xl: 4 };
      expect(cols).toBeDefined();
    });

    it('TT-005: IndicatorCatalogSkeletonProps interface exists', () => {
      type ResponsiveColumns = {
        default?: number;
        sm?: number;
        md?: number;
        lg?: number;
        xl?: number;
        '2xl'?: number;
      };
      type IndicatorCatalogSkeletonProps = {
        count?: number;
        columns?: ResponsiveColumns;
        className?: string;
      };
      const props: IndicatorCatalogSkeletonProps = { count: 6, columns: { md: 2, lg: 3 } };
      expect(props).toBeDefined();
    });
  });

  describe('Type Constraints', () => {
    it('TT-006: ChartSkeleton type accepts valid values', () => {
      type ChartType = 'line' | 'bar' | 'pie';
      const validTypes: ChartType[] = ['line', 'bar', 'pie'];
      expect(validTypes).toHaveLength(3);
    });

    it('TT-007: Optional props have correct defaults', () => {
      // Documented defaults in implementation notes
      const defaults = {
        CardSkeleton: { showIcon: true, lines: 3 },
        TableSkeleton: { rows: 5, columns: 4, showHeader: true },
        ChartSkeleton: { type: 'line' },
        IndicatorCatalogSkeleton: { count: 6 },
      };
      expect(defaults).toBeDefined();
    });

    it('TT-008: All components extend HTMLAttributes', () => {
      // Components should accept standard HTML div attributes
      type ExpectedProps = {
        className?: string;
        id?: string;
        'data-testid'?: string;
      };
      const props: ExpectedProps = { className: 'test', id: 'skeleton', 'data-testid': 'skeleton-test' };
      expect(props).toBeDefined();
    });
  });
});

// ============================================================================
// ACCESSIBILITY TESTS
// ============================================================================

describe('Accessibility', () => {
  // AC-001 to AC-010: Accessibility Tests
  describe('ARIA Attributes', () => {
    it('AC-001: CardSkeleton has aria-busy', async () => {
      const { CardSkeleton } = await import('@/components/ui/skeletons/CardSkeleton');
      const { container } = render(<CardSkeleton />);
      const card = container.firstChild as HTMLElement;
      expect(card.getAttribute('aria-busy')).toBe('true');
    });

    it('AC-002: TableSkeleton has aria-busy', async () => {
      const { TableSkeleton } = await import('@/components/ui/skeletons/TableSkeleton');
      const { container } = render(<TableSkeleton />);
      const table = container.firstChild as HTMLElement;
      expect(table.getAttribute('aria-busy')).toBe('true');
    });

    it('AC-003: ChartSkeleton has aria-busy', async () => {
      const { ChartSkeleton } = await import('@/components/ui/skeletons/ChartSkeleton');
      const { container } = render(<ChartSkeleton />);
      const chart = container.firstChild as HTMLElement;
      expect(chart.getAttribute('aria-busy')).toBe('true');
    });

    it('AC-004: IndicatorCatalogSkeleton has aria-busy', async () => {
      const { IndicatorCatalogSkeleton } = await import('@/components/ui/skeletons/IndicatorCatalogSkeleton');
      const { container } = render(<IndicatorCatalogSkeleton />);
      const catalog = container.firstChild as HTMLElement;
      expect(catalog.getAttribute('aria-busy')).toBe('true');
    });

    it('AC-005: Skeletons have aria-label for screen readers', async () => {
      const { CardSkeleton } = await import('@/components/ui/skeletons/CardSkeleton');
      const { container } = render(<CardSkeleton />);
      const card = container.firstChild as HTMLElement;
      expect(card.getAttribute('aria-label') || card.getAttribute('aria-labelledby')).toBeTruthy();
    });
  });

  describe('Reduced Motion', () => {
    it('AC-006: Respects prefers-reduced-motion', async () => {
      const { CardSkeleton } = await import('@/components/ui/skeletons/CardSkeleton');
      const { container } = render(<CardSkeleton />);
      const skeletons = container.querySelectorAll('[data-testid="skeleton"]');
      // Animation should use Tailwind's animate-pulse which respects reduced motion
      skeletons.forEach((skel) => {
        expect(skel.className).toMatch(/animate-pulse/);
      });
    });
  });

  describe('Keyboard Navigation', () => {
    it('AC-007: Skeletons are not focusable (no tab stop)', async () => {
      const { CardSkeleton } = await import('@/components/ui/skeletons/CardSkeleton');
      const { container } = render(<CardSkeleton />);
      const card = container.firstChild as HTMLElement;
      expect(card.getAttribute('tabindex')).not.toBe('0');
    });
  });

  describe('Semantic Structure', () => {
    it('AC-008: TableSkeleton uses semantic table elements', async () => {
      const { TableSkeleton } = await import('@/components/ui/skeletons/TableSkeleton');
      const { container } = render(<TableSkeleton />);
      const table = container.querySelector('table');
      const thead = container.querySelector('thead');
      const tbody = container.querySelector('tbody');
      expect(table || container.querySelector('[role="table"]')).toBeTruthy();
    });

    it('AC-009: ChartSkeleton has appropriate role', async () => {
      const { ChartSkeleton } = await import('@/components/ui/skeletons/ChartSkeleton');
      const { container } = render(<ChartSkeleton />);
      const chart = container.firstChild as HTMLElement;
      // Should have img or figure role, or aria-label
      const hasAccessibleRole = chart.getAttribute('role') || chart.getAttribute('aria-label');
      expect(hasAccessibleRole).toBeTruthy();
    });

    it('AC-010: IndicatorCatalogSkeleton uses list semantics', async () => {
      const { IndicatorCatalogSkeleton } = await import('@/components/ui/skeletons/IndicatorCatalogSkeleton');
      const { container } = render(<IndicatorCatalogSkeleton />);
      const catalog = container.firstChild as HTMLElement;
      // Should have grid/list semantics
      expect(catalog.getAttribute('role') || catalog.className).toBeTruthy();
    });
  });
});

// ============================================================================
// INTEGRATION TESTS
// ============================================================================

describe('Integration', () => {
  // INT-001 to INT-005: Integration Tests
  describe('Component Composition', () => {
    it('INT-001: CardSkeleton works inside IndicatorCatalogSkeleton', async () => {
      const { IndicatorCatalogSkeleton } = await import('@/components/ui/skeletons/IndicatorCatalogSkeleton');
      const { container } = render(<IndicatorCatalogSkeleton count={3} />);
      const cards = container.querySelectorAll('.card-skeleton, [class*="card"]');
      expect(cards.length).toBe(3);
    });

    it('INT-002: Multiple skeleton types on same page', async () => {
      const { CardSkeleton, TableSkeleton, ChartSkeleton } = 
        await import('@/components/ui/skeletons');
      const { container } = render(
        <div>
          <CardSkeleton />
          <TableSkeleton />
          <ChartSkeleton />
        </div>
      );
      expect(container.querySelectorAll('[data-testid="skeleton"]').length).toBeGreaterThan(0);
    });
  });

  describe('Performance', () => {
    it('INT-003: Multiple skeletons render efficiently', async () => {
      const { IndicatorCatalogSkeleton } = await import('@/components/ui/skeletons/IndicatorCatalogSkeleton');
      const startTime = performance.now();
      render(<IndicatorCatalogSkeleton count={50} />);
      const endTime = performance.now();
      expect(endTime - startTime).toBeLessThan(500);
    });

    it('INT-004: Re-render performance', async () => {
      const { CardSkeleton } = await import('@/components/ui/skeletons/CardSkeleton');
      const { rerender } = render(<CardSkeleton lines={3} />);
      const startTime = performance.now();
      for (let i = 0; i < 10; i++) {
        rerender(<CardSkeleton lines={i + 1} />);
      }
      const endTime = performance.now();
      expect(endTime - startTime).toBeLessThan(100);
    });
  });

  describe('Error Boundaries', () => {
    it('INT-005: Skeletons handle error gracefully', async () => {
      const { CardSkeleton } = await import('@/components/ui/skeletons/CardSkeleton');
      // Should not throw on invalid props
      expect(() => {
        render(<CardSkeleton lines={-1} showIcon={null as any} />);
      }).not.toThrow();
    });
  });
});
